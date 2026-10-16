# Changelog

All notable changes to PixiCDN are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Added
- Health check endpoint `/health.php` (database, storage, disk).
- `.env.example` template (works via native `getenv()`, no extra dependency).
- `SETUP.md` manual installation guide (Indonesian).
- `SECURITY.md` security policy (English).
- Gallery image preview modal (metadata, copy buttons, detail link) and
  in-place rename (`rename.php`: S3 copy, URL auto-updates, flash messages).
- Realtime dashboard metrics: `get_dashboard_stats()` helper plus
  `dashboard/stats.php` JSON endpoint (session-guarded, rate-limited) with
  60-second auto-refresh, manual refresh button, and load-failure warning.
- Generic confirm modal for destructive actions (delete, remove, revoke);
  delete/rename action group on gallery cards.
- Landing page Bootstrap tabs demo, API response modal, toast-on-copy,
  and mobile offcanvas navigation.
- Auth UI polish: centered cards, logo + home link, password-strength meter,
  show/hide password toggle, inline validation with error focus.
- `format_bytes()` / `format_tanggal_id()` helpers; `MAX_IMAGE_PIXELS`
  decompression-bomb guard (25 MP); `s3_copy()` / `s3_download()` helpers.

### Changed
- Full UI migration from Tailwind CDN to Bootstrap 5.3 (Trust Blue theme
  now lives in `includes/header.php`; `assets/app.css` removed).
- Reusable `header` / `sidebar` / `footer` includes with offcanvas mobile nav,
  global tooltips, flash-to-toast redirects, and skip-to-content link.
- `render_breadcrumbs()` no longer renders a back button (breadcrumb only).
- Uniform 8px button radius; smaller contextual landing icons.
- Upload flow hardened: file input reset, invalid files excluded from queue,
  preview clicks no longer reopen the file dialog.

### Fixed
- S3 `Content-Type` for inline preview (`inline` disposition, 1-year cache).
- Config loading conflict (`const` vs `define`): `config.php` first, guarded example as dev-fill.
- Restored missing `xhr.open()` in upload (stuck "loading terus").
- Removed bogus Chart.js SRI hash that blocked chart loading; chart canvases
  wrapped in fixed-height containers.

### Security
- Login/reset timing-attack resistance (constant-time dummy bcrypt verify).
- Strict upload MIME match (extension must equal sniffed content).
- `Secure` session cookies honored behind TLS-terminating proxies
  (`X-Forwarded-Proto`).
- Rename endpoint: CSRF + ownership + filename whitelist + global uniqueness,
  best-effort S3/DB rollback.

## [1.0.0] - 2026-10-11

### Added
- Image upload (max 5 files, 5 MB/file; JPG/PNG/GIF/WebP) with drag-drop UI,
  progress bar, expiry options, and 300px thumbnails.
- Direct link + BBCode + HTML formats with copy buttons.
- Gallery with filter, sort, and pagination (login required, personal collection).
- User registration (bcrypt cost 12, one-time recovery code) + auto-login.
- Login/logout with session regeneration; password reset via recovery code.
- User profile (edit username, change password) and dashboard with charts.
- Album management with public share links.
- REST API with `X-API-Key` (`/api/upload`, `/api/images`, `/api/delete`, `/api/me`).
- Rate limiting (uploads, login, registration, API) with cron cleanup.
- Soft delete with quota accounting and expiry cleanup cron.
- Trust Blue light theme across landing and all app pages.
- Deployment pack: Quadlets, backup script, cleanup timer, RustFS unit.

### Changed
- Anonymous upload disabled: upload/gallery/dashboard require an account.
  Image viewer and public album links stay public so embeds keep working.

### Security
- CSRF protection on every POST (constant-time comparison).
- Session hardening (`httponly`, `samesite=Lax`, custom save path).
- PDO prepared statements everywhere; HTML-escaped output.
- Upload validation (extension + MIME + size) with randomized filenames.
- No credentials in source code; `config.php`/`.env` git-ignored.

## [0.x] - 2026-10-10 — Build history (pre-release)

- **Tahap 1 / Foundation + DB:** folder structure, `composer.json`
  (`aws/aws-sdk-php` 3.400.1), PDO helpers, S3 client (custom endpoint +
  path-style), `migrations/001_initial_schema.sql` (9 tables).
- **Tahap 2 / Auth:** register, login/logout, forgot/reset via recovery code.
- **Tahap 3 / Fase 1:** upload UI, 3 link formats, gallery, delete, view counter.
- **Tahap 4 / Fase 2 (end):** profile, API keys, REST API, thumbnails, charts.
- **Tahap 5 / Fase 3:** albums, analytics, expiry, cleanup cron, docs.
- **Tahap 6 / Hardening:** login rate limit, public albums, seed data, deploy pack.
