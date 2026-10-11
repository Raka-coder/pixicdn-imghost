# PixiCDN

![PHP](https://img.shields.io/badge/PHP-8.2-blue)
![MariaDB](https://img.shields.io/badge/MariaDB-11-orange)
![License](https://img.shields.io/badge/License-MIT-green)

Self-hosted image hosting dengan direct link permanen. Upload gambar, dapatkan link yang bisa di-embed di forum/blog, kelola via dashboard, integrasi via REST API.

> **Quick Start:** instalasi step-by-step ada di [SETUP.md](SETUP.md).

## Fitur

- **Upload & link:** max 5 file, 5 MB/file (JPG/PNG/GIF/WebP), direct link + BBCode + HTML + tombol copy, thumbnails 300px, expiry otomatis.
- **Galeri & album:** filter/sort/pagination, album dengan share link publik, view counter anti-spam.
- **Akun:** register + recovery code, login/logout, reset password, profile, dashboard + analytics.
- **Developer:** REST API (`X-API-Key`), API key management, health endpoint `/health.php`.
- Wajib akun untuk upload & kelola; viewer gambar dan album publik tetap terbuka.

## Screenshot

> Placeholder — tambahkan `screenshots/landing.png`, `screenshots/dashboard.png`,
> `screenshots/upload.png`, dan `screenshots/gallery.png` di sini.
>
> `![Landing](screenshots/landing.png)` · `![Dashboard](screenshots/dashboard.png)` ·
> `![Upload](screenshots/upload.png)` · `![Gallery](screenshots/gallery.png)`

## Tech Stack

| Layer | Teknologi |
|:---|:---|
| Backend | PHP 8.2 native, `aws/aws-sdk-php` ^3.0 |
| Database | MariaDB 11 (`pixicdn_db`, 9 tabel) |
| Storage | RustFS S3-compatible (bucket `pixicdn`) |
| Web | Caddy 2 + PHP-FPM, Podman Quadlet |
| CDN | Cloudflare (tunnel + edge cache) |
| Frontend | Tailwind CSS v4 (CDN), Toastify-js, Chart.js, Lucide, Inter |

## Dokumentasi

- [SETUP.md](SETUP.md) — cara instalasi manual.
- [SECURITY.md](SECURITY.md) — security policy.
- [CHANGELOG.md](CHANGELOG.md) — changelog.

## Contributing

1. Fork + branch `feature/nama-fitur` dari `main`.
2. Ikuti aturan: `strict_types`, PDO prepared, escape output, CSRF di form.
3. Tambahkan/ perbarui test di `tests/` dan pastikan semua hijau.
4. Commit jelas (`feat: ...`, `fix: ...`, `docs: ...`), buka pull request.

## License

MIT — bebas dipakai, diubah, dan didistribusikan dengan mencantumkan atribusi.

## Syarat

- PHP >= 8.2 dengan ext: `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `json`, `gd` (wajib di production untuk thumbnail; lokal tanpa gd → thumbnail NULL + tetap upload).
- Composer, MariaDB 11, RustFS dengan bucket `pixicdn`.

## Jalankan Lokal & Preview Browser (5 menit, tanpa DB)

Cukup PHP + Composer. Tanpa database, yang tampil: landing, form upload (submit butuh DB+S3),
gallery kosong, form register/login. Cukup untuk preview tampilan.

```powershell
cd C:\pixicdn
composer install
php -S localhost:8000 -t C:\pixicdn
```

Buka di browser:

| URL | Tampil tanpa DB |
|---|---|
| `http://localhost:8000/` | Landing + navigasi |
| `http://localhost:8000/upload.php` | Form drag-drop + preview + expiry |
| `http://localhost:8000/gallery.php` | Empty state "Belum ada gambar" |
| `http://localhost:8000/auth/register.php` | Form daftar |
| `http://localhost:8000/auth/login.php` | Form masuk |
| `http://localhost:8000/dashboard/` | Redirect ke login (302, benar) |
| `http://localhost:8000/api/me.php` | 401 JSON (benar, tanpa key) |

Berhentikan server: `Ctrl+C` di terminal.

## Full Lokal (CRUD beneran, perlu MariaDB + S3)

1. **Database** (XAMPP: start MySQL, atau MariaDB sendiri):
   ```powershell
   copy config.example.php config.php   # isi DB_PASS sesuai MySQL lokal
   ```
   Buat DB `pixicdn_db` via phpMyAdmin (`http://localhost/phpmyadmin`), import
   `migrations/001_initial_schema.sql` (+ opsional `002_sample_data.sql` untuk akun
   `admin` / `raka_restu`, password `password123`).
2. **Storage S3** (salah satu):
   - RustFS lokal, atau MinIO sebagai pengganti (`mc mb pixicdn`, public download),
     lalu sesuaikan `S3_ENDPOINT/S3_KEY/S3_SECRET` di `config.php`.
   - Tanpa ini, upload mengembalikan "Gagal menyimpan ke storage" — UI lain tetap jalan.
3. `php -S localhost:8000 -t C:\pixicdn` → daftar → upload → gallery → album → API key.

## Instalasi (ringkas, produksi lihat `docs/DEPLOY.md`)

Caddy production: lihat `Caddyfile.example` (php_fastcgi, security headers, blokir `/config.php /vendor/* /logs/* /migrations/*`). Deploy penuh: `docs/DEPLOY.md` + `deploy/` (Quadlet, backup, cleanup timer).

Cleanup harian:

```bash
php cron/cleanup.php   # cron 02:00 / systemd timer
```

## Cara Pakai (wajib daftar dulu — upload anonymous dinonaktifkan)

- Daftar: `/auth/register.php` (simpan recovery code!) → masuk → `/upload.php` (pilih expiry) → salin Direct/BBCode/HTML → `/gallery.php` (koleksi sendiri) → `/image.php?f=nama.jpg` (publik, bisa di-embed).
- Auth: `/auth/register.php` (simpan recovery code!) → `/dashboard/` → `/auth/profile.php`, `/dashboard/api-key.php`.
- Album: `/dashboard/albums.php`.
- API (header `X-API-Key` wajib):

```bash
curl -H "X-API-Key: <key>" http://localhost:8000/api/me.php
curl -H "X-API-Key: <key>" -F image=@foto.jpg http://localhost:8000/api/upload.php
curl -H "X-API-Key: <key>" "http://localhost:8000/api/images.php?page=1&per_page=20"
curl -X DELETE -H "X-API-Key: <key>" "http://localhost:8000/api/delete.php?id=123"
```

## Struktur Folder

```
pixicdn/
├── index.php gallery.php image.php upload.php upload_handler.php delete.php
├── auth/ (register, login, logout, profile, forgot/reset-password)
├── dashboard/ (index, albums, album, api-key)
├── api/ (upload, images, delete, me)
├── includes/ (db, helpers, auth, upload, s3, api, thumbnail, album)
├── cron/cleanup.php  logs/  migrations/  tests/  assets/  vendor/
├── config.example.php  Caddyfile.example
└── docs/ (prd, architecture, rules, schema, TESTING)
```

## Keamanan Ringkas

Bcrypt cost 12, session `httponly/secure/samesite=Lax` + regenerate, CSRF semua form, PDO prepared, `htmlspecialchars` output, validasi upload ext+MIME+size + nama acak, rate limit 100 upload/jam/IP, ownership check, API key 256-bit, response forgot seragam (no enumeration).

## Testing

Lihat `docs/TESTING.md` + `php tests/test_*.php` (auth/upload/api/album).

## Lisensi

Proyek tugas UTS — Raka Restu Saputra.
