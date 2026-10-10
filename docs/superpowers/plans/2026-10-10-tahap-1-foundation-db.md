# Tahap 1 Foundation + DB Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Siapkan fondasi PixiCDN lokal (struktur, config, composer, helpers, migrasi DB) tanpa Auth/Upload.

**Architecture:** PHP 8.2 native, PDO MariaDB, AWS SDK for PHP untuk RustFS (S3-compatible, custom endpoint + path-style), Tailwind v4 CDN untuk UI nanti. Sesuai `docs/architecture.md` §4-5 dan `docs/rules.md`.

**Tech Stack:** PHP 8.2, MariaDB 11, AWS SDK for PHP (`aws/aws-sdk-php`), Tailwind v4 (CDN), Caddy (example only).

**Spec:** `docs/prd.md` §3.1 Fase 1/Hari 1 (M1), `docs/schema.md` §7, `docs/rules.md` §2/§4-6, `docs/architecture.md` §5.2-5.3

## Global Constraints

- PHP >= 8.2, `declare(strict_types=1)` di setiap file PHP.
- Naming: variable/function `snake_case`, Class `PascalCase`, const `UPPER_SNAKE`.
- Password/recovery nanti bcrypt cost 12 (tidak di Tahap 1, dicatat saja).
- Semua query PDO prepared statements.
- Semua output `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` via `sanitize()`.
- File upload validation (ext+MIME+size) untuk Tahap 3, tidak di sini.
- Config kredensial tidak di-commit (`config.php` di `.gitignore`, hanya `config.example.php`).
- Bahasa kode Inggris, UI Indonesia.
- Lokal saja: tidak deploy ke 192.168.1.48/50, tidak butuh tunnel.

## Review Focus

- Kredensial default tidak ikut ter-commit (config.php di-ignore, hanya example).
- PDO DSN memakai utf8mb4 + ERRMODE_EXCEPTION + FETCH_ASSOC.
- S3Client memakai `endpoint` custom + `use_path_style_endpoint=true` untuk RustFS.
- Migration idempotent (`IF NOT EXISTS`) + `FOREIGN_KEY_CHECKS`.
- `public_url` format konsisten `https://pixicdn.erdtreeproject.my.id/{filename}`.

---

### Task 1: Composer + Gitignore + Folder skeleton

**Files:**
- Create: `composer.json`
- Create: `.gitignore`
- Create: `logs/.gitkeep`

**Interfaces:**
- Consumes: —
- Produces: `vendor/autoload.php` (setelah `composer install`) dipakai semua `includes/*.php`.

- [ ] **Step 1: Tulis composer.json (aws-sdk-php only, Tahap 1)**

```json
{
    "name": "pixicdn/pixicdn",
    "description": "PixiCDN - self-hosted image hosting with direct link",
    "type": "project",
    "require": {
        "php": ">=8.2",
        "aws/aws-sdk-php": "^3.0"
    },
    "autoload": {
        "files": [
            "includes/helpers.php"
        ]
    },
    "config": {
        "optimize-autoloader": true
    }
}
```

- [ ] **Step 2: Tulis .gitignore (dari rules.md §6.5)**

```gitignore
.env
config.php
logs/*.log
logs/*.txt
vendor/
uploads/
migrations/*.local.sql
*.db
*.sqlite
.DS_Store
Thumbs.db
.vscode/
.idea/
*.swp
*.bak
*.backup
*.old
```

- [ ] **Step 3: Install lokal (verifikasi)**

Run: `composer install`
Expected: PASS, muncul `vendor/autoload.php`.

- [ ] **Step 4: Commit**

```bash
git add composer.json .gitignore logs/.gitkeep
git commit -m "chore(setup): composer foundation tahap 1"
```

### Task 2: Config example + DB PDO + Helpers + S3 helper

**Files:**
- Create: `config.example.php`
- Create: `includes/db.php`
- Create: `includes/helpers.php`
- Create: `includes/s3.php`

**Interfaces:**
- Consumes: `vendor/autoload.php`, const dari `config.php` (`DB_HOST`, `DB_NAME`, `S3_ENDPOINT`, dll).
- Produces: `getDB(): PDO`, `sanitize(string): string`, `csrfToken(): string`, `verifyCsrf(string): bool`, `jsonResponse(array, int): void`, `getS3Client(): S3Client`, `env(string, string): string`.

- [ ] **Step 1: Tulis config.example.php** — copy ke `config.php` lokal, isi kredensial asli. Lihat file aktual untuk isi lengkap (DB_* , S3_* , APP_* , SECURITY).

- [ ] **Step 2: Tulis includes/db.php** — singleton PDO `mysql:host=...;dbname=...;charset=utf8mb4`, `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, `EMULATE_PREPARES=false`.

- [ ] **Step 3: Tulis includes/helpers.php** — `env()`, `sanitize()`, `csrfToken()`, `verifyCsrf()` (`hash_equals`), `jsonResponse()`, `redirect()`, `generate_filename()` (`Ymd_random.ext`).

- [ ] **Step 4: Tulis includes/s3.php** — Context7 AWS SDK (`/aws/aws-sdk-php`): `new S3Client(['version'=>'latest','region'=>'us-east-1','endpoint'=>S3_ENDPOINT,'use_path_style_endpoint'=>true,'credentials'=>[...]])`, helper `s3_put()`, `s3_delete()`, `s3_exists()` dengan try/catch `S3Exception` → `error_log` + return false/null, pesan user generik Indonesia.

- [ ] **Step 5: Verifikasi syntax**

Run: `php -l config.example.php && php -l includes/db.php && php -l includes/helpers.php && php -l includes/s3.php`
Expected: PASS semua `No syntax errors`.

- [ ] **Step 6: Commit**

```bash
git add config.example.php includes/
git commit -m "feat(setup): config db helpers s3 tahap 1"
```

### Task 3: Migration 001_initial_schema.sql

**Files:**
- Create: `migrations/001_initial_schema.sql`
- Test: manual import ke MariaDB lokal.

**Interfaces:**
- Consumes: `docs/schema.md` §7.
- Produces: tabel `users`, `images`, `password_resets`, `view_logs`, `api_logs`, `albums`, `album_images`, `rate_limits`, `audit_logs`.

- [ ] **Step 1: Tulis migration dari schema.md §7 verbatim** (IF NOT EXISTS, InnoDB utf8mb4_unicode_ci, FK CASCADE/SET NULL sesuai §6).

- [ ] **Step 2: Verifikasi import lokal (jika MariaDB tersedia)**

Run: `mariadb -u root pixicdn_db < migrations/001_initial_schema.sql`
Expected: PASS, `SHOW TABLES` tampil 9 tabel.

- [ ] **Step 3: Commit**

```bash
git add migrations/001_initial_schema.sql
git commit -m "feat(db): migration 001 initial schema 9 tabel"
```

### Task 4: Landing minimal + Caddy example ( Burns ... no, local only)

**Files:**
- Create: `index.php`
- Create: `Caddyfile.example`

**Interfaces:**
- Consumes: Tailwind v4 CDN docs (`/tailwindlabs/tailwindcss.com` — Play CDN dev-only, `dark:` variant, responsive `grid md:grid-cols-4`).
- Produces: landing placeholder gelap slate-950 indigo gradient, link ke tahap berikutnya.

- [ ] **Step 1: Tulis index.php** — semantic HTML5 `lang=id`, Tailwind browser CDN, Lucide via unpkg + `lucide.createIcons()`, status Tahap 1.

- [ ] **Step 2: Tulis Caddyfile.example** — `php_fastcgi 127.0.0.1:9000`, `encode gzip`, security headers dari architecture.md §6.4, block `/config.php /vendor/* /logs/* /migrations/*`.

- [ ] **Step 3: Verifikasi**

Run: `php -l index.php`
Expected: PASS. Buka `php -S localhost:8000` manual, cek landing render.

- [ ] **Step 4: Commit**

```bash
git add index.php Caddyfile.example
git commit -m "feat(setup): landing minimal + caddy example tahap 1"
```
