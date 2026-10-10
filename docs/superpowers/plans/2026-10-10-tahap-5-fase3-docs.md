# Tahap 5 Fase 3 + Dokumentasi Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Album CRUD + analytics dashboard + expiry/cleanup + dokumentasi lengkap (README, CHANGELOG, TESTING).

**Architecture:** `includes/album.php` (slug, CRUD, pivot, ownership) → `dashboard/albums.php` (list+buat), `dashboard/album.php` (detail+tambah/hapus/cover) → analytics: upgrade `dashboard/index.php` (top 10, views 30 hari chart, storage) → expiry: `expires_in` di `upload.php/upload_handler.php/api/upload.php` → `images.expires_at`, `cron/cleanup.php` (CLI) hapus expired + fisik >30 hari + token/view/api_logs lama → docs `README.md`, `CHANGELOG.md`, `docs/TESTING.md`. Sesuai PRD Fase 3, schema §4, rules §14-15.

**Tech Stack:** PHP 8.2, PDO, S3, Chart.js, cron/systemd timer.

**Spec:** `docs/prd.md` §3.1 Fase 3, `docs/schema.md` §4, `docs/rules.md` §14/§15.

## Global Constraints

- strict_types + header, prepared, sanitize, CSRF, require_login untuk album.
- Slug: lowercase alnum-dash, unik per user (`uk_user_slug`).
- Album ownership: user hanya CRUD album sendiri; tambah gambar hanya gambar sendiri/owned-session.
- `image_count` dijaga via COUNT trigger aplikasi (update setelah add/remove).
- Expiry opsi: never/1jam/1hari/7hari/30hari; gallery sembunyikan expired (`expires_at > NOW()` sudah ada).
- Cleanup CLI only (`php_sapi_name()==='cli'`), idempotent, echo ringkas per aksi.
- Docs Indonesia, struktur README per rules §15.2.

## Review Focus

- Album user A tidak bisa diakses/diubah user B (direct URL tebak → 403/404).
- Gambar orang lain tidak bisa dimasukkan ke album sendiri.
- Expired tidak muncul di gallery/viewer (viewer 404-kan expired).
- Cleanup tidak hapus data aktif (WHERE ketat + LIMIT batch).
- README tidak berisi kredensial asli.

## Rulings

- Ruling: album publik tanpa login BISA lihat (is_public) tapi tidak ubah; default is_public=TRUE agar bisa share. Cost if wrong: album privat bocor → cek `is_public OR owner` di view.
- Ruling: cleanup fisik S3 untuk soft-deleted >30 hari langsung `s3_delete` + `DELETE` row; jika S3 gagal, row tetap (coba lagi besok). Cost if wrong: file yatim — acceptable, log jelas.
- Ruling: docs TESTING terpisah `docs/TESTING.md` (checklist manual rules §14 + smoke API via curl) agar README tetap ringkas. Cost if wrong: duplikasi — minor.

---

### Task 1: includes/album.php (pure + DB)

**Files:**
- Create: `includes/album.php`
- Test: `tests/test_album.php`

**Interfaces:**
- Consumes: `getDB()`.
- Produces: `slugify(string): string`, `validate_album_name(string): bool`, `create_album(int,string,?string): int`, `get_album(int,int): ?array`, `add_to_album(int,int,int): bool`, `remove_from_album(int,int): bool`, `expiry_to_sql(?string): ?string` (never/1h/1d/7d/30d → interval SQL atau null).

- [ ] **Step 1: Write failing test** — slugify (`Halo Dunia!`→`halo-dunia`), nama valid/invalid, expiry mapping.
- [ ] **Step 2: Run** `php tests/test_album.php` Expected: FAIL function not defined.
- [ ] **Step 3: Implement minimal.**
- [ ] **Step 4: Run** Expected: PASS.
- [ ] **Step 5: Commit** `git add includes/album.php tests/test_album.php && git commit -m "feat(album): core crud slug expiry tahap 5"`

### Task 2: Album UI + analytics + expiry + cleanup

**Files:**
- Create: `dashboard/albums.php`, `dashboard/album.php`, `cron/cleanup.php`
- Modify: `upload.php` (+select expires), `upload_handler.php` + `api/upload.php` (terima expires_in), `image.php` (404 jika expired), `gallery.php` (sudah filter, pastikan), `dashboard/index.php` (top10 + views30 + link album)

**Interfaces:**
- Consumes: Task 1.
- Produces: CRUD album login-only, viewer album publik, cleanup CLI.

- [ ] **Step 1: Tulis album UI** — list + buat (nama/deskripsi/publik), detail (tambah by filename milik sendiri, hapus, set cover, hapus album soft).
- [ ] **Step 2: Expiry** — form select + handler mapping + viewer/gallery enforcement.
- [ ] **Step 3: Analytics** — top10 + line views 30 hari + storage bar di dashboard.
- [ ] **Step 4: Cleanup CLI** — expired→soft-delete; soft>30hari→s3_delete+DELETE LIMIT 100; token used/expired→DELETE; view_logs>90hari→DELETE LIMIT 1000; echo counts.
- [ ] **Step 5: php -l** Expected: PASS.
- [ ] **Step 6: Commit** `git add dashboard/albums.php dashboard/album.php cron/cleanup.php upload.php upload_handler.php api/upload.php image.php dashboard/index.php && git commit -m "feat(fase3): album analytics expiry cleanup tahap 5"`

### Task 3: Dokumentasi

**Files:**
- Create: `README.md`, `CHANGELOG.md`, `docs/TESTING.md`
- Modify: `index.php` (nav: upload/gallery/dashboard/auth sesuai login? minimal link)

**Interfaces:**
- Consumes: semua tahap 1-5.
- Produces: docs siap demo UTS.

- [ ] **Step 1: README** — deskripsi, fitur per fase + status, tech stack, syarat (PHP8.2+ext, MariaDB, RustFS), instalasi (composer, config.php, migration, Caddy), cara pakai (web + API curl), struktur folder, keamanan ringkas, lisensi.
- [ ] **Step 2: CHANGELOG** — Tahap 1-5 + tanggal.
- [ ] **Step 3: TESTING** — checklist rules §14 (upload/auth/gallery) + browser/responsive + API curl + cron dry-run.
- [ ] **Step 4: Commit** `git add README.md CHANGELOG.md docs/TESTING.md index.php && git commit -m "docs: readme changelog testing tahap 5"`
