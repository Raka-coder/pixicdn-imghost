# SETUP PixiCDN — Panduan Instalasi Manual dari Nol

Panduan instalasi PixiCDN (self-hosted image hosting) di server Debian.
Untuk arsitektur lengkap (IP, tunnel, Quadlet), lihat `docs/DEPLOY.md`
(dokumen lokal, tidak ikut repo).

## 1. Persyaratan Sistem

| Kebutuhan | Minimal | Keterangan |
|:---|:---|:---|
| PHP | 8.2+ | Ext: `pdo_mysql`, `mbstring`, `gd`, `fileinfo`, `openssl`, `json` |
| MariaDB | 11+ | Database metadata |
| RustFS | terbaru | Object storage S3-compatible |
| Composer | 2.x | Dependency PHP |
| Caddy | 2.x | Web server + reverse proxy |
| Podman | 4.x | Container (opsional, bisa native) |

Cek cepat: `php -v && php -m | grep -iE 'gd|pdo_mysql' && composer --version`.

## 2. Langkah Instalasi

### a. Clone repo

```bash
git clone https://github.com/Raka-coder/pixicdn-imghost.git pixicdn
cd pixicdn
```

### b. Install dependency

```bash
composer install --no-dev --optimize-autoloader
```

### c. Copy `config.example.php` → `config.php`

```bash
cp config.example.php config.php
nano config.php   # isi DB_*, S3_*, APP_URL — JANGAN commit file ini
```

Alternatif tanpa edit file: set environment variable (nama sama dengan
konstanta, mis. `DB_PASS`, `S3_SECRET`). Kode membaca `getenv()` terlebih
dulu. Template variabel ada di `.env.example` (salin ke `.env` yang
di-gitignore bila memakai workflow dotenv manual).

### d. Isi `config.php` dengan kredensial Anda

Minimal yang wajib benar: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`,
`S3_ENDPOINT`, `S3_KEY`, `S3_SECRET`, `S3_BUCKET`, `APP_URL`.
`APP_DEBUG` harus `false` di production.

### e. Setup database (SQL migration)

```bash
mariadb -h 127.0.0.1 -u pixicdn_user -p pixicdn_db < migrations/001_initial_schema.sql
# Opsional (data testing saja):
# mariadb -h 127.0.0.1 -u pixicdn_user -p pixicdn_db < migrations/002_sample_data.sql
```

### f. Setup bucket RustFS

```bash
mc alias set rustfs http://127.0.0.1:9000 <key> <secret>
mc mb --ignore-existing rustfs/pixicdn
mc anonymous set download rustfs/pixicdn
```

### g. Set permission

```bash
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod 640 config.php
chown -R 1000:1000 .
# Grup PHP-FPM pada image bookworm adalah www-data (gid 33),
# BUKAN 82 (itu untuk Alpine). Sesuaikan bila base image diganti.
chgrp 33 config.php
chmod 775 logs tmp-sessions
chmod 770 tmp-sessions
```

### h. Configure Caddy

Salin `Caddyfile.example` ke lokasi Caddy (`/opt/caddy/Caddyfile` di
panduan deploy), sesuaikan domain, reload Caddy.

### i. Test

```bash
curl -I https://your-domain.com            # harus 200
curl https://your-domain.com/health.php    # {"status":"ok",...}
php tests/test_auth.php && php tests/test_upload.php \
  && php tests/test_api.php && php tests/test_album.php \
  && php tests/test_config.php && php tests/test_hardening.php
```

## 3. Konfigurasi Environment

### Cara setup `config.php`

Prioritas nilai (dari tertinggi): environment variable → `config.php` →
`config.example.php` (dev-fill). Di production, isi `config.php` lengkap
dan `APP_DEBUG=false`.

### Cara setup RustFS

```bash
RUSTFS_VOLUMES=/data/rustfs0
RUSTFS_ADDRESS=:9000
RUSTFS_CONSOLE_ADDRESS=:9001
RUSTFS_ACCESS_KEY=<isi-disini>
RUSTFS_SECRET_KEY=<isi-disini>
RUSTFS_SERVER_DOMAINS=erdtreeproject.my.id
RUSTFS_CORS_ALLOWED_ORIGINS=https://pixicdn.erdtreeproject.my.id,https://s3.erdtreeproject.my.id
```

Contoh unit systemd: `deploy/rustfs.service`. Wajib path-style
(`S3_USE_PATH_STYLE=true`) karena domain kustom.

### Cara setup Cloudflare Tunnel routes

| Subdomain | Target |
|:---|:---|
| `pixicdn` | web server `:80` |
| `s3` | RustFS `:9000` |
| `rustfs` | console `:9001` |
| `files` | file browser `:8080` |
| `db` | phpMyAdmin `:8081` |

SSL mode Flexible + Host Header `pixicdn.erdtreeproject.my.id` + No TLS Verify.

## 4. Deployment

- **Podman/Quadlet:** contoh unit di `deploy/*.container`, enable via systemd.
- **Systemd service:** `deploy/pixicdn-cleanup.{service,timer}` untuk cleanup harian 02:00.
- **Backup setup:** `deploy/backup-db.sh` (cron 01:00, retensi 7 hari). Backup RustFS: `mc mirror` bucket ke `/opt/backup/rustfs/`. Restore DB: import file `.sql.gz` terbaru; restore file: `mc mirror` balik.

## 5. Troubleshooting

| Gejala | Penyebab umum | Solusi |
|:---|:---|:---|
| Upload: "Response tidak valid" | PHP fatal (bukan JSON), mis. memory/GD | `memory_limit=512M`, pastikan GD + JPEG (`php -m \| grep gd`, `php -r "var_dump(gd_info());"`) |
| Gambar auto-download | `ContentType` tidak tersimpan | Sudah fix di `s3_put()`; upload ulang file lama bila perlu |
| Gambar 404 padahal ada | `public_url` lama tanpa path `uploads/...` | Upload ulang (URL baru memakai path S3 penuh) |
| Session hilang / logout sendiri | Save path tidak writable | Pastikan `tmp-sessions/` ada dan writable PHP-FPM |
| 500 misterius | `config.php` belum diisi / DB down | Cek `logs/`, jalankan `/health.php` |
| CSRF invalid terus | Cookie session terblokir | Pastikan cookie `PIXICDN_SESSION` terkirim (HTTPS + SameSite=Lax) |
