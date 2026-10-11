# Security Policy

## Supported Versions

| Version | Supported |
|---------|-----------|
| 1.0.x   | ✅        |

Security fixes are applied to the latest `1.0.x` release only.

## Security Features

- **Password hashing:** bcrypt, cost 12 (passwords and recovery codes).
- **Session:** `httponly`, `secure` (when HTTPS), `samesite=Lax`; ID regenerated
  after login/register; custom writable save path (`tmp-sessions/`).
- **CSRF:** per-form token verified with `hash_equals()` on every POST.
- **SQL injection:** PDO prepared statements for all queries.
- **File upload:** extension whitelist + `getimagesize()` + `finfo` MIME check +
  5 MB limit + randomized filenames.
- **Rate limiting:** 100 uploads/hour/IP, 5 logins/15 min/IP, 5 registrations/hour/IP,
  100–1000 API requests/hour/key, with expired-window cleanup via cron.
- **Request logging:** API requests logged to `api_logs`; PHP errors logged server-side
  while users only see generic messages.
- **Secrets:** no credentials in source code; `config.php` and `.env` are git-ignored;
  `config.example.php` / `.env.example` contain placeholders only.

## Reporting a Vulnerability

- **Where:** open a **private security advisory** on the GitHub repository
  (Security tab → Advisories). Do not open a public issue for vulnerabilities.
- **Response time:** initial response within 48 hours.
- **Disclosure policy:** coordinated disclosure — please allow time to fix and
  release before publishing details. Credit is given unless anonymity is requested.

## Best Practices for Deployment

1. Rotate all default credentials (database, object storage, app accounts).
2. Enforce HTTPS end to end; set `APP_DEBUG=false` in production.
3. Restrict admin/database consoles (phpMyAdmin, RustFS console) behind the
   tunnel or IP allowlist — never expose them anonymously.
4. Run automated backups (database daily, object storage mirror) and test restores.
5. Keep PHP, MariaDB, RustFS, Caddy, and Composer dependencies updated.
6. Monitor `/health.php` with an uptime checker and review logs regularly.

## Known Limitations

- Thumbnails require the GD extension with JPEG support; without it, uploads still
  succeed but `thumbnail_url` stays empty (graceful degradation, logged server-side).
- Very large images skip thumbnail generation when estimated memory exceeds 400 MB.
- Session storage is file-based (`tmp-sessions/`) — a single web node only; use
  sticky sessions or shared storage before scaling horizontally.
- No Redis/object cache yet (optional, roadmap).
- Full audit trail (login/register/delete events in `audit_logs`) is schema-ready
  but event wiring is on the roadmap.
