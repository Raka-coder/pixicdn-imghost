# Tahap 4 Profile + API + Thumbnail Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Profile (edit username + ubah password), API key management, REST API 4 endpoint, thumbnail 300px, dashboard chart 7 hari.

**Architecture:** `includes/api.php` (key gen/validasi, JSON envelope, api_logs) + `includes/thumbnail.php` (GD, graceful-null jika ext hilang) → hook ke `handle_upload` (isi `thumbnail_url`) → `auth/profile.php`, `dashboard/api-key.php`, `api/upload.php`, `api/images.php`, `api/delete.php`, `api/me.php` → dashboard chart Chart.js inline query. Sesuai PRD §3.2.8-3.2.14, rules §7 API, schema `users.api_key` + `images.thumbnail_url` + `api_logs`.

**Tech Stack:** PHP 8.2 + GD (opsional lokal, wajib prod), PDO, AWS SDK S3, Chart.js CDN.

**Spec:** `docs/prd.md` §3.2.8/3.2.9/3.2.12/3.2.13/3.2.14, `docs/rules.md` §7, `docs/schema.md` §3.2.

## Global Constraints

- strict_types + header, prepared statements, `sanitize()` output.
- API key: `bin2hex(random_bytes(32))` 64 hex, tampil sekali, simpan plain-unique (cukup, key random 256-bit) + `api_key_created_at`.
- Auth API: header `X-API-Key`, 401 jika invalid, rate limit upload 100/jam, list 1000/jam.
- Response sukses: `{success:true,data:{...},meta:{timestamp}}`; error: `{success:false,error:{code,message}}` + HTTP code tepat (200/201/400/401/403/404/429/500).
- Thumbnail: max 300px sisi panjang, simpan `thumbnails/YYYY/MM/thumb_{file}`, JPEG q80; GIF animasi → ambil frame pertama (GD) atau skip (null) — ruling di bawah.
- Ubah password wajib verify password lama.
- GD tidak ada di PHP lokal → `make_thumbnail` return null + `error_log`, upload tetap sukses tanpa thumbnail.

## Review Focus

- API key tidak bisa dilihat ulang (hanya saat generate, via session flash sekali).
- Key lama langsung invalid setelah regenerate/delete.
- API tanpa key / key salah → 401 generik (tidak bocor user).
- API delete user A tidak bisa hapus gambar user B → 403/404.
- Thumbnail gagal (GD hilang/GIF animasi) → upload tetap ok, `thumbnail_url=NULL`.

## Rulings

- Ruling: GD absen lokal → graceful-null + log; prod (Debian image PHP-FPM) wajib ext gd. Cost if wrong: thumbnail NULL di lokal dikira bug — bedakan via `thumbnail_status` di log.
- Ruling: GIF animasi → thumbnail frame pertama via GD (kehilangan animasi di thumb saja, original tetap). Cost if wrong: thumb statis untuk GIF — acceptable, original tidak berubah.
- Ruling: gallery tampilkan `thumbnail_url` jika ada else `public_url`; klik tetap buka original. Cost if wrong: 2x transfer untuk thumb-missing — minor.

---

### Task 1: includes/api.php + includes/thumbnail.php (pure + DB)

**Files:**
- Create: `includes/api.php`, `includes/thumbnail.php`
- Test: `tests/test_api.php`

**Interfaces:**
- Consumes: `getDB()`, `s3_put()`.
- Produces: `generate_api_key(): string`, `validate_api_key(string): ?array`, `rotate_api_key(int): string`, `revoke_api_key(int): void`, `api_success(array,int): void`, `api_error(string,string,int,array): void`, `log_api(?int,string,string,int): void`, `thumb_dimensions(int,int,int): array{int,int}`, `make_thumbnail(string $src_tmp, string $dst_tmp, int $max=300): bool`, `upload_thumbnail(string $src_tmp, string $filename): ?string`.

- [ ] **Step 1: Write failing test** `tests/test_api.php` — key 64 hex, dimensions scale (1920x1080→300x169, 100x100→100x100 no upscale), portrait.
- [ ] **Step 2: Run** `php tests/test_api.php` Expected: FAIL function not defined.
- [ ] **Step 3: Implement minimal** dua file di atas.
- [ ] **Step 4: Run** Expected: PASS.
- [ ] **Step 5: Commit** `git add includes/api.php includes/thumbnail.php tests/test_api.php && git commit -m "feat(api): key management dan thumbnail tahap 4"`

### Task 2: Profile + API key page + upload hook thumbnail

**Files:**
- Create: `auth/profile.php`, `dashboard/api-key.php`
- Modify: `includes/upload.php` (hook thumbnail di `handle_upload`, update record `thumbnail_url`)

**Interfaces:**
- Consumes: Task 1 + `current_user/require_login`.
- Produces: halaman profile (info + edit username + ubah password), halaman API key (generate/regenerate/delete + tampil sekali).

- [ ] **Step 1: Tulis profile** — CSRF, edit username (cek duplikat), ubah password (verify lama + min 8).
- [ ] **Step 2: Tulis api-key page** — POST actions, key plain hanya di flash session sekali.
- [ ] **Step 3: Hook thumbnail** — setelah `s3_put` original: `upload_thumbnail` → update `images.thumbnail_url`.
- [ ] **Step 4: php -l** Expected: PASS.
- [ ] **Step 5: Commit** `git add auth/profile.php dashboard/api-key.php includes/upload.php && git commit -m "feat(account): profile api-key thumbnail-hook tahap 4"`

### Task 3: REST API 4 endpoint + gallery thumb + dashboard chart

**Files:**
- Create: `api/upload.php`, `api/images.php`, `api/delete.php`, `api/me.php`
- Modify: `gallery.php` (pakai thumbnail_url), `dashboard/index.php` (chart 7 hari Chart.js + link profile/api-key)

**Interfaces:**
- Consumes: Task 1-2.
- Produces: JSON API sesuai PRD §3.2.13 + rules §7.3-7.7 (pagination `page/per_page/total/total_pages/has_next/has_prev`).

- [ ] **Step 1: Tulis 4 endpoint** — key via `X-API-Key` (atau `?api_key` fallback? TIDAK — header only, ruling), rate limit, `log_api`, status tepat.
- [ ] **Step 2: Update gallery + dashboard** — thumb prefer, chart query `DATE(uploaded_at)` 7 hari.
- [ ] **Step 3: php -l + full suite** `php tests/test_auth.php && php tests/test_upload.php && php tests/test_api.php` Expected: PASS.
- [ ] **Step 4: Commit** `git add api/ gallery.php dashboard/index.php && git commit -m "feat(api): rest endpoints gallery-thumb dashboard-chart tahap 4"`
