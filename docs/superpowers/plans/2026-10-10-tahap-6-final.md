# Tahap 6 Hardening + Deploy-Ready (Final) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Tutup semua gap verifikasi: login rate limit, halaman album publik, seed 002, file deploy (Quadlet, backup, timer), DEPLOY.md. Setelah ini tidak ada tahap kode lagi — sisa hanya live-test + demo di server.

**Architecture:** Wire `check_rate_limit()` (ada di `includes/upload.php`) ke `auth/login.php` (5 gagal/15 mnt/IP, pesan generik) → `album.php` publik (slug, hormati `is_public`, reuse `get_album(..., allow_public=true)`) → `migrations/002_sample_data.sql` dari `docs/schema.md` §8 → `deploy/` (quadlet per architecture §3.4, backup, cleanup timer) + `docs/DEPLOY.md` (checklist rules §13).

**Tech Stack:** PHP 8.2, PDO, Podman Quadlet, systemd timer, mariadb-client.

**Spec:** `docs/rules.md` §4.7/§12-13, `docs/schema.md` §8, `docs/architecture.md` §3.4.

## Global Constraints

- strict_types + header PHP; prepared; sanitize; CSRF form web (API tetap header-only).
- Login gagal ke-6 dalam 15 mnt → 429 generik "Terlalu banyak percobaan. Coba lagi nanti." (jangan bedakan user ada/tidak).
- Rate key login: `login:<ip>`; sukses → reset window (DELETE row) agar user sah tidak terkunci.
- Album publik: tanpa login bisa lihat jika `is_public`; tombol ubah hanya owner; slug via `?u=user&s=slug` (tanpa rewrite rule, works di Caddy default).
- Seed 002 hanya data testing, password `password123` — tandai jelas, jangan dipakai di prod.
- Quadlet file: contoh lokal, path `/opt/...` sesuai architecture; tidak dieksekusi di sini.

## Review Focus

- Rate limit login tidak bocorkan user valid (pesan sama untuk semua gagal).
- Album privat user lain via slug → 404 (bukan 403 beda pesan — samakan 404 agar tidak enumerable).
- Seed tidak menimpa data (INSERT IGNORE / cek duplikat sederhana).
- Backup script tidak menulis kredensial ke log.

## Rulings

- Ruling: batas login 5/15 mnt (bukan 100/jam seperti upload) karena credential stuffing lebih sensitif. Cost if wrong: user sah terkunci 15 mnt setelah 5 typo — acceptable, pesan jelas.
- Ruling: album publik di `album.php` root (bukan dashboard) agar tanpa login. Cost if wrong: satu file publik baru — kecil.
- Ruling: deploy files di `deploy/` (bukan root) agar repo rapi; README link ke sana. Cost if wrong: path docs vs file — dijaga konsisten.

---

### Task 1: Hardening login + album publik (TDD)

**Files:**
- Modify: `auth/login.php` (rate limit)
- Create: `album.php` (publik viewer), `tests/test_hardening.php`

**Interfaces:**
- Consumes: `check_rate_limit()` dari `includes/upload.php`, `get_album()` dari `includes/album.php`.
- Produces: login 429 setelah 5 gagal/15 mnt; `GET /album.php?u=<user>&s=<slug>` publik.

- [ ] **Step 1: Write failing test** `tests/test_hardening.php` — assert `auth/login.php` memuat `check_rate_limit` + pesan 429; assert `album.php` memuat `allow_public` + `is_public`. (Smoke test integrasi file, jujur dicatat sebagai smoke bukan unit murni.)
- [ ] **Step 2: Run** `php tests/test_hardening.php` Expected: FAIL (string belum ada).
- [ ] **Step 3: Implement** kedua file.
- [ ] **Step 4: Run** Expected: PASS + regresi 4 suite lama.
- [ ] **Step 5: Commit** `git add auth/login.php album.php tests/test_hardening.php && git commit -m "feat(security): login rate-limit album publik tahap 6"`

### Task 2: Seed 002 sample data

**Files:**
- Create: `migrations/002_sample_data.sql`

**Interfaces:**
- Consumes: `docs/schema.md` §8.
- Produces: admin + user testing + 3 gambar contoh (idempotent via INSERT IGNORE + WHERE NOT EXISTS pattern sederhana).

- [ ] **Step 1: Tulis seed** dari schema §8 (hash bcrypt `password123` bawaan docs).
- [ ] **Step 2: Sanity** — file dibaca, hitung statement INSERT (tanpa DB live).
- [ ] **Step 3: Commit** `git add migrations/002_sample_data.sql && git commit -m "feat(db): seed testing 002 tahap 6"`

### Task 3: Deploy-ready files + DEPLOY.md

**Files:**
- Create: `deploy/caddy.container`, `deploy/phpfpm.container`, `deploy/mariadb.container`, `deploy/phpmyadmin.container`, `deploy/filebrowser.container`, `deploy/beszel-agent.container`, `deploy/rustfs-setup.sh`, `deploy/backup-db.sh`, `deploy/pixicdn-cleanup.timer`, `deploy/pixicdn-cleanup.service`, `docs/DEPLOY.md`
- Modify: `README.md` (link deploy), `CHANGELOG.md` (tahap 6)

**Interfaces:**
- Consumes: `docs/architecture.md` §3 (port, path, IP), `docs/rules.md` §13.
- Produces: paket deploy contoh siap salin ke Server 2/3. (Exception TDD: configuration files.)

- [ ] **Step 1: Tulis 6 quadlet + 2 script + timer/service** — path/volume sesuai architecture, `WantedBy=default.target`, backup `mysqldump` + retensi 7 hari.
- [ ] **Step 2: Tulis DEPLOY.md** — prasyarat, order deploy (Server 3 dulu: mariadb→rustfs→seed, lalu Server 2: php→caddy→tunnel→smoke), pre-deploy checklist rules §13.1, rollback §13.3, cron cleanup.
- [ ] **Step 3: Commit** `git add deploy/ docs/DEPLOY.md README.md CHANGELOG.md && git commit -m "feat(deploy): quadlet backup timer docs tahap 6"`
