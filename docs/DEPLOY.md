# DEPLOY PixiCDN ke Server 2 & 3

Sumber: `docs/architecture.md` §3 + `docs/rules.md` §13. File contoh di `deploy/` — salin ke path absolut di server, jangan edit langsung di repo untuk kredensial.

## 0. Prasyarat

- Server 2 `192.168.1.48` (Debian 12 LXC): Podman + kode di `/opt/caddy/html/pixicdn`.
- Server 3 `192.168.1.50`: Podman + direktori `/opt/mariadb`, `/opt/rustfs`, `/opt/backup/mariadb`.
- Cloudflare tunnel `proxmox-home` dengan route `docs/architecture.md` §6.2.
- `DB_PASS`, `RUSTFS_*` siap (tidak ada di repo).

## 1. Server 3 dulu (data)

```bash
# MariaDB
sudo mkdir -p /opt/mariadb/{data,conf} && sudo cp deploy/mariadb.container /etc/containers/systemd/
sudo cp deploy/mariadb.env.example /opt/mariadb/env && sudo chmod 600 /opt/mariadb/env && sudo nano /opt/mariadb/env
sudo systemctl daemon-reload && sudo systemctl enable --now mariadb.service
mariadb -h 127.0.0.1 -u pixicdn_user -p pixicdn_db < migrations/001_initial_schema.sql
# Seed opsional (testing saja):
# mariadb -h 127.0.0.1 -u pixicdn_user -p pixicdn_db < migrations/002_sample_data.sql

# RustFS (binary native :9000/:9001) + bucket
sudo mkdir -p /data/rustfs0 /opt/rustfs
# install binary ke /usr/local/bin/rustfs (lihat komentar atas deploy/rustfs.service)
sudo cp deploy/rustfs.env.example /opt/rustfs/env && sudo chmod 600 /opt/rustfs/env && sudo nano /opt/rustfs/env
sudo cp deploy/rustfs.service /etc/systemd/system/ && sudo systemctl daemon-reload && sudo systemctl enable --now rustfs.service
RUSTFS_ENDPOINT=http://127.0.0.1:9000 RUSTFS_KEY=... RUSTFS_SECRET=... bash deploy/rustfs-setup.sh

# phpMyAdmin + backup + cleanup timer
sudo cp deploy/phpmyadmin.container /etc/containers/systemd/ && sudo systemctl daemon-reload && sudo systemctl enable --now phpmyadmin.service
sudo cp deploy/backup-db.sh /opt/scripts/ && sudo chmod +x /opt/scripts/backup-db.sh
sudo cp deploy/pixicdn-cleanup.{service,timer} /etc/systemd/system/ && sudo systemctl daemon-reload && sudo systemctl enable --now pixicdn-cleanup.timer
```

## 2. Server 2 (aplikasi)

```bash
sudo mkdir -p /opt/caddy/html && sudo cp -r pixicdn /opt/caddy/html/
cd /opt/caddy/html/pixicdn && sudo cp config.example.php config.php && sudo nano config.php  # APP_DEBUG=false
sudo composer install --no-dev --optimize-autoloader
podman build -t pixicdn-php -f deploy/phpfpm.Containerfile deploy/
sudo cp deploy/phpfpm.container deploy/caddy.container deploy/filebrowser.container deploy/beszel-agent.container /etc/containers/systemd/
sudo cp Caddyfile.example /opt/caddy/Caddyfile && sudo nano /opt/caddy/Caddyfile
sudo chown -R 1000:1000 /opt/caddy/html/pixicdn && sudo chmod 640 /opt/caddy/html/pixicdn/config.php
sudo systemctl daemon-reload && sudo systemctl enable --now phpfpm.service caddy.service
```

## 3. Pre-deploy checklist (rules §13.1)

- [ ] Semua `php tests/test_*.php` hijau (4 suite).
- [ ] Tidak ada `console.log` / `var_dump` / `print_r`.
- [ ] `APP_DEBUG=false`, kredensial di `config.php` + `/opt/mariadb/env` (bukan repo).
- [ ] Backup DB terbaru ada.
- [ ] `curl -I https://pixicdn.erdtreeproject.my.id` → 200; upload 1 file smoke; login smoke.

## 4. Rollback (rules §13.3)

```bash
cd /opt/caddy/html/pixicdn && sudo git reset --hard HEAD~1
mariadb -h 127.0.0.1 -u pixicdn_user -p pixicdn_db < /opt/backup/mariadb/<file-sebelum-deploy>.sql
sudo systemctl restart phpfpm.service
```

## 5. Operasional

- Cleanup: timer 02:00 (`systemctl list-timers | grep pixicdn`), log via `journalctl -u pixicdn-cleanup`.
- Backup: cron 01:00, retensi 7 hari di `/opt/backup/mariadb/`.
- Monitor: Beszel + Uptime Kuma target 99%.
