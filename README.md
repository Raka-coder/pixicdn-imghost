# PixiCDN

Self-hosted image hosting dengan direct link permanen. Upload gambar, dapatkan link yang bisa di-embed di forum/blog, kelola via dashboard, integrasi via REST API.

## Fitur

- **Fase 1 (selesai):** Upload anonymous (max 5 file, 5 MB, JPG/PNG/GIF/WebP), direct link 3 format (Direct/BBCode/HTML) + copy, gallery (filter/sort/pagination 20), delete (ownership + konfirmasi), view counter (1 IP/jam).
- **Fase 2 (selesai):** Register/login/logout (bcrypt cost 12, session regenerate), recovery code 8 char untuk reset password, profile (edit username + ubah password), API key (generate/regenerate/delete, tampil sekali), REST API (`/api/upload`, `/api/images`, `/api/delete`, `/api/me`), thumbnail 300px, dashboard + chart upload.
- **Fase 3 (selesai):** Album CRUD (slug unik/user, tambah/keluarkan gambar, cover), analytics (top 10, views 30 hari, upload 7 hari), expiry (never/1h/1d/7d/30d) + `cron/cleanup.php` (expired, fisik >30 hari, token, log 90 hari).

Non-goal: payment, AI tagging, editor gambar, video, social.

## Tech Stack

- PHP 8.2 native, MariaDB 11, RustFS (S3-compatible) via `aws/aws-sdk-php`, Caddy, Podman + Quadlet, Cloudflare (CDN + tunnel).
- Frontend: Tailwind CSS v4 (CDN), Toastify-js, Chart.js, Lucide. Upload zone native drag-drop (siap ganti FileBokz).

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
