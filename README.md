# PixiCDN

[![Build](https://github.com/Raka-coder/pixicdn-imghost/actions/workflows/php.yml/badge.svg)](https://github.com/Raka-coder/pixicdn-imghost/actions)
![PHP](https://img.shields.io/badge/PHP-8.2-blue)
![MariaDB](https://img.shields.io/badge/MariaDB-11-orange)
![License](https://img.shields.io/badge/License-MIT-green)

Self-hosted image hosting with permanent direct links. Upload images, get embeddable URLs for forums and blogs, manage them from a dashboard, or integrate via REST API. No ads, no tracking, your data stays on your server.

> [!NOTE]
> Uploading and managing images requires a (free) account. Image viewer links and public album links stay open so embeds keep working.

## Features

- **Upload & share** — up to 5 files per batch, 5 MB each (JPG/PNG/GIF/WebP); Direct, BBCode, and HTML links with one-click copy; 300px thumbnails; optional auto-expiry.
- **Organize** — personal gallery with filter/sort/pagination, albums with public share links, spam-resistant view counter.
- **Accounts** — registration with one-time recovery code (no email needed), login/logout, password reset, profile, analytics dashboard.
- **Developer** — REST API authenticated via `X-API-Key`, one-time-display API keys, `/health.php` monitoring endpoint.

## Architecture

```
Browser ──HTTPS──▶ Cloudflare ──tunnel──▶ Caddy + PHP-FPM (Server 2)
                                                │ PDO            │ S3 API
                                                ▼                ▼
                                          MariaDB 11 ◀──── RustFS (Server 3)
```

## Prerequisites

| Requirement | Version | Notes |
|:---|:---|:---|
| PHP | >= 8.2 | Extensions: `pdo_mysql`, `mbstring`, `gd`, `fileinfo`, `openssl`, `json` |
| Composer | 2.x | PHP dependencies |
| MariaDB | 11+ | Metadata database (`pixicdn_db`) |
| RustFS or MinIO | any recent | S3-compatible object storage, bucket `pixicdn` |
| Caddy | 2.x | Production web server (see `Caddyfile.example`) |

## Quick start (UI preview, no database)

Renders the landing page, upload form, empty gallery, and auth forms. Enough to review the UI.

```powershell
cd C:\pixicdn
composer install
php -S localhost:8000 -t C:\pixicdn
```

```bash
cd /path/to/pixicdn
composer install
php -S localhost:8000 -t .
```

Open `http://localhost:8000/` in your browser. Stop the server with `Ctrl+C`.

> [!TIP]
> Without a database you can browse every page, but submitting forms needs the full setup below.

## Full local setup (working CRUD)

**1. Database.** Start MySQL/MariaDB (e.g. via XAMPP), create database `pixicdn_db`, then import the schema:

```bash
mariadb -h 127.0.0.1 -u pixicdn_user -p pixicdn_db < migrations/001_initial_schema.sql
# Optional test data (admin / raka_restu, password: password123):
mariadb -h 127.0.0.1 -u pixicdn_user -p pixicdn_db < migrations/002_sample_data.sql
```

**2. App config.** Copy the template and fill in your local credentials:

```powershell
copy config.example.php config.php
```

```bash
cp config.example.php config.php
```

> [!WARNING]
> `config.php` is git-ignored and must never be committed. All secrets live there or in environment variables of the same name.

**3. Object storage.** Run RustFS locally, or MinIO as a drop-in replacement:

```bash
mc alias set local http://127.0.0.1:9000 <key> <secret>
mc mb --ignore-existing local/pixicdn
mc anonymous set download local/pixicdn
```

Point `S3_ENDPOINT`, `S3_KEY`, and `S3_SECRET` in `config.php` at it. Without storage, uploads fail gracefully with "Gagal menyimpan ke storage" while everything else works.

**4. Run and try it:** `php -S localhost:8000` → register → upload → gallery → albums → API key.

## Configuration

`config.php` values can also come from environment variables of the same name (environment wins). Copy defaults from `config.example.php`; a full variable template is in `.env.example`.

| Key | Purpose | Example |
|:---|:---|:---|
| `DB_HOST` / `DB_PORT` / `DB_NAME` / `DB_USER` / `DB_PASS` | MariaDB connection | `192.168.1.50`, `pixicdn_db`, … |
| `S3_ENDPOINT` / `S3_BUCKET` / `S3_REGION` / `S3_KEY` / `S3_SECRET` | Object storage (path-style forced on) | `http://192.168.1.50:9000`, `pixicdn`, … |
| `S3_PUBLIC_URL` | Base URL stamped into stored image URLs | `https://s3.erdtreeproject.my.id/pixicdn` |
| `APP_URL` / `APP_DEBUG` | Public base URL / debug mode (always `false` in production) | … |
| `MAX_FILE_SIZE` / `MAX_FILES_PER_UPLOAD` | Upload limits (5 MB / 5 files) | … |

## Usage

**Web flow:** `/auth/register.php` (save the recovery code!) → `/upload.php` → copy a link format → `/gallery.php` → `/image.php?f=name.jpg` → `/dashboard/albums.php`.

**REST API** (header `X-API-Key` required):

```bash
curl -H "X-API-Key: <key>" http://localhost:8000/api/me.php
curl -H "X-API-Key: <key>" -F image=@photo.jpg http://localhost:8000/api/upload.php
curl -H "X-API-Key: <key>" "http://localhost:8000/api/images.php?page=1&per_page=20"
curl -X DELETE -H "X-API-Key: <key>" "http://localhost:8000/api/delete.php?id=123"
```

## Project structure

```
pixicdn/
├── index.php  gallery.php  image.php  album.php
├── upload.php upload_handler.php  delete.php  health.php
├── auth/        # register, login, logout, profile, password reset
├── dashboard/   # stats, albums, api-key
├── api/         # upload, images, delete, me (JSON)
├── includes/    # db, auth, upload, s3, api, thumbnail, album, helpers
├── cron/        # cleanup.php (expiry, purge, log retention)
├── migrations/ # 001 schema, 002 test seed
├── tests/       # php test scripts (auth, upload, api, album, config, hardening)
├── deploy/      # Quadlet units, backup + cleanup timer, rustfs unit
├── assets/      # app.css (Trust Blue component classes)
└── SETUP.md / SECURITY.md / CHANGELOG.md / Caddyfile.example
```

## Testing

```bash
php tests/test_auth.php && php tests/test_upload.php && php tests/test_api.php \
  && php tests/test_album.php && php tests/test_config.php && php tests/test_hardening.php
vendor/bin/php-cs-fixer fix --dry-run --diff   # PSR-12 check
```

## Deployment

Production runs on Proxmox LXC: Server 2 serves the app (Caddy + PHP-FPM via Quadlet), Server 3 holds MariaDB + RustFS behind a Cloudflare tunnel. Ready-made units, backup script, and cleanup timer live in `deploy/` — see `SETUP.md` for the full walkthrough.

## Troubleshooting

| Symptom | Likely cause | Fix |
|:---|:---|:---|
| "Response tidak valid" on upload | PHP fatal (memory/GD), not JSON | `memory_limit=512M`, GD with JPEG support |
| Images auto-download | Missing `ContentType` on old uploads | Re-upload (new uploads set `inline`) |
| Logged out unexpectedly | `tmp-sessions/` not writable | Create it, writable by PHP-FPM |
| 500 with no detail | `config.php` missing values / DB down | Check logs, then `/health.php` |
| CSRF invalid loop | Session cookie blocked | Allow `PIXICDN_SESSION` (HTTPS + SameSite=Lax) |
