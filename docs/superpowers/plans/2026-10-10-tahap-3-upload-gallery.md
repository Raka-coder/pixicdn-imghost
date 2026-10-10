# Tahap 3 Upload + Gallery Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Upload anonymous (max 5 file, 5MB, JPG/PNG/GIF/WebP) → direct link 3 format → gallery + delete + view counter.

**Architecture:** Form `upload.php` (native drag-drop + preview + XHR progress, siap ganti FileBokz) → POST `upload_handler.php` (validasi ext+MIME via `getimagesize`+`finfo`, nama `Ymd_random.ext`, `s3_put` ke `uploads/YYYY/MM/`, insert `images`) → JSON kembali ke UI (3 format link + copy + Toastify `/apvarun/toastify-js`). Gallery `gallery.php` (grid responsif, filter All/Today/Week/Month, sort Newest/Oldest/MostViewed, pagination 20, hover view+delete). Delete `delete.php` (konfirmasi SweetAlert2/native confirm, `s3_delete` + soft-delete + update quota). View `image.php` (increment max 1 IP/jam via `view_logs`, async-ish inline).

**Tech Stack:** PHP 8.2 native, PDO MariaDB (`images`, `view_logs`), AWS SDK S3 (`s3_put/s3_delete`), Toastify-js CDN (Context7 `/apvarun/toastify-js`), Tailwind v4 CDN.

**Spec:** `docs/prd.md` §3.2.1-3.2.5, `docs/rules.md` §4.5 upload security, `docs/schema.md` §2.2/§3.1.

## Global Constraints

- `declare(strict_types=1)` + header semua PHP.
- Validasi: whitelist ext, `getimagesize!==false`, `finfo` MIME `image/jpeg|png|gif|webp`, size ≤5MB, max 5 file/request.
- Nama file acak `generate_filename()`, S3 key `uploads/YYYY/MM/{file}`.
- Prepared statements, output `sanitize()`, CSRF di form upload/delete.
- Rate limit: max 100 upload/jam/IP (tabel `rate_limits`, sederhana).
- View: 1 IP = 1 view/jam (`view_logs` check).
- Anonymous: `user_id=NULL` jika belum login, isi jika login.
- UI Indonesia, dark slate-950 indigo gradient.

## Review Focus

- File non-gambar (php/web shell) ditolak walau ext diganti.
- File >5MB ditolak server-side walau client lolos.
- Delete milik orang lain ditolak (ownership check, anonymous via IP? — anonymous delete hanya via gallery sesi? Ruling di bawah).
- View spam dari IP sama dalam 1 jam tidak nambah.
- S3 gagal → DB tidak insert setengah jalan (order: S3 dulu baru DB; jika DB gagal, hapus S3).

## Rulings

- Ruling: FileBokz tidak ada di Context7 → upload zone native (drop+input+preview+XHR). Cost if wrong: ganti 1 file `upload.php` saat FileBokz tersedia, backend tidak berubah.
- Ruling: Delete anonymous — hanya owner sesi upload (simpan `image_ids` di session) atau user login pemilik. Tanpa itu siapa pun bisa hapus. Gallery tampilkan tombol delete hanya jika boleh. Cost if wrong: delete link ditebak → tambah delete-token per image bila perlu.

---

### Task 1: includes/upload.php (validasi + simpan)

**Files:**
- Create: `includes/upload.php`
- Test: `tests/test_upload.php`

**Interfaces:**
- Consumes: `getDB()`, `s3_put()`, `generate_filename()`, const `MAX_FILE_SIZE/ALLOWED_EXTENSIONS/APP_URL`.
- Produces: `validate_image_file(array $file): ?string` (null=valid, string=pesan error ID), `build_s3_key(string $filename): string`, `save_image_record(array $meta): int` (insert images, return id), `handle_upload(array $file, ?int $user_id, string $ip, string $ua): array{ok,url?,error?}`.

- [ ] **Step 1: Write failing test** `tests/test_upload.php` — ext valid/invalid, size over, mime palsu, s3 key format `uploads/YYYY/MM/`.
- [ ] **Step 2: Run** `php tests/test_upload.php` Expected: FAIL function not defined.
- [ ] **Step 3: Implement** `includes/upload.php` minimal.
- [ ] **Step 4: Run** Expected: PASS.
- [ ] **Step 5: Commit** `git add includes/upload.php tests/test_upload.php && git commit -m "feat(upload): validasi dan simpan tahap 3"`

### Task 2: upload.php + upload_handler.php (UI + endpoint)

**Files:**
- Create: `upload.php`, `upload_handler.php`
- Modify: `index.php` (link ke upload) — kecil.

**Interfaces:**
- Consumes: Task 1 `handle_upload()`, `s3_put`, Toastify `Toastify({text,duration,gravity,position,style})`.
- Produces: POST JSON `{success,data:[{url,bbcode,html}],error?}`; UI tampil 3 format + tombol copy + preview.

- [ ] **Step 1: Tulis handler** — CSRF, loop max 5 file, `handle_upload`, JSON response 200/400/429.
- [ ] **Step 2: Tulis UI** — drop zone native, preview, XHR progress bar, Toastify sukses/gagal, hasil 3 format + copy (`navigator.clipboard`).
- [ ] **Step 3: php -l** Expected: PASS.
- [ ] **Step 4: Commit** `git add upload.php upload_handler.php index.php && git commit -m "feat(upload): ui dan handler tahap 3"`

### Task 3: gallery.php + image.php + delete.php (lihat + hapus + counter)

**Files:**
- Create: `gallery.php`, `image.php`, `delete.php`

**Interfaces:**
- Consumes: `getDB()`, `s3_delete()`, session ownership.
- Produces: gallery grid + pagination, viewer increment view, delete soft-delete.

- [ ] **Step 1: Tulis gallery** — filter/sort/pagination, `WHERE is_deleted=FALSE`, lazy `loading=lazy`, hover info.
- [ ] **Step 2: Tulis image viewer** — by filename, cek `view_logs` 1 jam terakhir untuk IP, insert log + `views+1` jika baru.
- [ ] **Step 3: Tulis delete** — CSRF + ownership (user_id cocok atau session owns), `s3_delete` + `is_deleted=TRUE,deleted_at=NOW()` + quota update.
- [ ] **Step 4: php -l + tests lama** `php tests/test_auth.php && php tests/test_upload.php` Expected: PASS semua.
- [ ] **Step 5: Commit** `git add gallery.php image.php delete.php && git commit -m "feat(gallery): gallery viewer delete tahap 3"`
