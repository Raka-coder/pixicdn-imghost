# CHANGELOG PixiCDN

## 2026-10-10 — Middleware wajib akun (membatalkan upload anonymous PRD Fase 1)
- `upload.php`/`gallery.php`/`dashboard/*`/`auth/profile.php`/`delete.php`: tamu → 302 login. `upload_handler.php`: tamu → 401 JSON (dicek sebelum file, terverifikasi).
- `gallery.php` kini koleksi pribadi (WHERE user_id), bukan gallery publik. `delete.php` owner-only (jalur session anonymous dihapus).
- Viewer `image.php` + share `album.php` tetap publik (embed forum tidak rusak — batas teknis).
- Landing: semua CTA upload → `/auth/register.php` + banner "Wajib daftar".
- Keputusan tercatat: menonaktifkan "Upload anonymous" PRD §3.2.1; PRD perlu revisi bila keputusan ini final.

## 2026-10-10 — Migrasi tema Trust Blue (design.md) ke semua halaman
- Landing `index.php` rebuild total ala SaaS: nav putih 64px, hero + demo format link, strip statistik nyata dari DB (disembunyikan jika DB belum ada), fitur, 3 langkah, persona, teaser API curl, CTA navy/emas, footer.
- 13 halaman app ke terang: bg `#F5F7FA`, kartu putih, navy `#003087`, emas `#F5BA2E`, input 48px, font Inter + JetBrains Mono, angka tabular.
- Keputusan tercatat: design.md (terang) menang atas PRD/rules §dark-mode untuk SELURUH halaman; `docs/rules.md` §8 + PRD §7 belum diselaraskan teksnya — lakukan saat revisi dokumen berikutnya.
- Verifikasi: `php -l` 14 file ok, `slate-` = 0 di halaman UI, curl 200/302/401/404 tepat, 5 suite 39/39 PASS.

## 2026-10-10 — Tahap 6 / Hardening + Deploy-Ready (final)
- Login rate limit 5/15 mnt/IP (429 generik, reset saat sukses) + smoke `tests/test_hardening.php`.
- Album publik `album.php?u=<user>&s=<slug>` (privat → 404, ubah tetap owner-only).
- `migrations/002_sample_data.sql` idempotent (INSERT IGNORE / NOT EXISTS).
- `deploy/`: 6 Quadlet (caddy, phpfpm+Containerfile+php.ini, mariadb+env.example, phpmyadmin, filebrowser, beszel-agent), `rustfs-setup.sh`, `backup-db.sh` (retensi 7 hari), `pixicdn-cleanup.{service,timer}`.
- `docs/DEPLOY.md` (order Server 3→2, checklist §13.1, rollback §13.3).

## 2026-10-10 — Tahap 5 / Fase 3 + Dokumentasi
- Album: CRUD, slug unik/user, tambah/keluarkan gambar milik sendiri, cover, `image_count`, album publik bisa dilihat.
- Analytics: dashboard top 10, views 30 hari (line), upload 7 hari (bar), link navigasi lengkap.
- Expiry: opsi never/1h/1d/7d/30d di web + API, enforcement gallery/viewer, `cron/cleanup.php` (expired, fisik >30 hari, token, view/api logs 90 hari).
- Docs: `README.md`, `CHANGELOG.md`, `docs/TESTING.md`, plan `2026-10-10-tahap-5-fase3-docs.md`.

## 2026-10-10 — Tahap 4 / Fase 2 (akhir)
- Profile (edit username, ubah password verify lama), API key (generate/regenerate/delete, tampil sekali).
- REST API: `api/upload.php` (201), `api/images.php` (pagination), `api/delete.php` (ownership), `api/me.php`.
- Thumbnail GD 300px (`thumbnails/YYYY/MM/thumb_*`), graceful-null tanpa ext-gd, gallery prefer thumb.
- Dashboard chart 7 hari (Chart.js).

## 2026-10-10 — Tahap 3 / Fase 1
- Upload anonymous (native drop zone + preview + XHR progress + Toastify), handler max 5 file + rate limit.
- Direct link 3 format + copy, gallery filter/sort/pagination + lazy + empty state.
- Delete ownership (session/user) + soft-delete + quota, viewer + counter 1 IP/jam.

## 2026-10-10 — Tahap 2 / Fase 2 (auth)
- Register (bcrypt 12 + recovery code 8 char tampil sekali) + auto-login, login/logout regenerate.
- Forgot/reset via recovery code (SHA-256 token 1 jam sekali pakai, no enumeration), dashboard minimal.

## 2026-10-10 — Tahap 1 / Foundation + DB
- Struktur folder, `composer.json` (`aws/aws-sdk-php` 3.400.1), `config.example.php`, `.gitignore`.
- `includes/db.php` (PDO), `helpers.php`, `s3.php` (custom endpoint + path-style).
- `migrations/001_initial_schema.sql` (9 tabel), `index.php` landing, `Caddyfile.example`.
