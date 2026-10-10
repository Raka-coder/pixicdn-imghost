# TESTING PixiCDN (checklist manual + otomatis)

Sumber: `docs/rules.md` §14. Otomatis: `php tests/test_auth.php && php tests/test_upload.php && php tests/test_api.php && php tests/test_album.php`.

## 0. Smoke otomatis (wajib hijau sebelum klaim selesai)

```bash
php tests/test_auth.php && php tests/test_upload.php && php tests/test_api.php && php tests/test_album.php
```

## 1. Upload (`/upload.php` → `upload_handler.php`)

- [ ] Upload 1 file JPG 1 MB → sukses, 3 format muncul, copy bekerja.
- [ ] Upload 5 file sekaligus → sukses semua.
- [ ] Upload 6 file → ditolak "Maksimal 5 file".
- [ ] Upload file > 5 MB → ditolak server-side.
- [ ] Upload `shell.php` (atau JPG diganti ext PHP) → ditolak "bukan gambar valid".
- [ ] Upload tanpa file → error "Tidak ada file".
- [ ] CSRF salah → 403.
- [ ] > 100 upload/jam/IP → 429.
- [ ] Expiry `7d` → `expires_at` terisi ~7 hari (cek DB).

## 2. Auth (`/auth/*`)

- [ ] Register username baru → sukses + recovery code tampil sekali → dashboard.
- [ ] Register username dipakai → error.
- [ ] Register username `ab` / password < 8 → error.
- [ ] Login benar → dashboard; login salah → error generik.
- [ ] Logout → sesi hilang, `/dashboard/` redirect login.
- [ ] Forgot code benar → lanjut reset; code salah → pesan SAMA (no enumeration).
- [ ] Reset password baru → login dengan password baru bisa; token tidak bisa dipakai 2x.
- [ ] Profile: edit username duplikat → error; ubah password lama salah → error.

## 3. Gallery / Viewer / Delete

- [ ] Gallery tampil, filter Today/Week/Month + sort Newest/Oldest/MostViewed bekerja.
- [ ] Pagination 20/hal; empty state jika kosong.
- [ ] Klik gambar → viewer + view count +1 (refresh < 1 jam tidak +1).
- [ ] Viewer file expired → 404.
- [ ] Delete milik sendiri → hilang dari gallery, quota berkurang.
- [ ] Delete milik orang lain (POST manual id) → 403.
- [ ] Thumbnail: gallery pakai `thumb_*` jika ada; klik buka original.

## 4. Album (`/dashboard/albums.php`)

- [ ] Buat album → muncul di list; nama duplikat → slug `-2`.
- [ ] Tambah gambar milik sendiri by filename → masuk; gambar orang lain → ditolak.
- [ ] Set cover, keluarkan gambar, hapus album (gambar utuh).
- [ ] Album user lain via `?id=` → 404.

## 5. API (`X-API-Key`)

```bash
K=...; B=http://localhost:8000
curl -H "X-API-Key: $K" $B/api/me.php
curl -H "X-API-Key: $K" -F image=@foto.jpg $B/api/upload.php
curl -H "X-API-Key: salah" $B/api/me.php            # → 401
curl -H "X-API-Key: $K" "$B/api/images.php?page=1&per_page=5"
curl -X DELETE -H "X-API-Key: $K" "$B/api/delete.php?id=ID_ORANG"  # → 404
```

- [ ] Tanpa header → 401. Key user A hapus gambar user B → 404.
- [ ] Regenerate → key lama 401. Revoke → 401.

## 6. Cron

```bash
php cron/cleanup.php
```

- [ ] Output 5 baris count + `OK cleanup`; dijalankan 2x tetap aman (idempotent).
- [ ] Gambar expired kemarin → `is_deleted=1` setelah run.

## 7. Browser + responsif

| Browser | Status |
|---|---|
| Chrome latest | |
| Firefox latest | |
| Mobile Chrome | |

| Lebar | Status |
|---|---|
| 375px (1 kolom) | |
| 768px (2 kolom) | |
| 1440px (4 kolom + chart) | |
