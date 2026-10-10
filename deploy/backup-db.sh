#!/usr/bin/env bash
# Backup harian MariaDB (Server 3). Kredensial TIDAK di log.
# Cron: 0 1 * * * /opt/scripts/backup-db.sh
set -euo pipefail
DIR=/opt/backup/mariadb
mkdir -p "$DIR"
FILE="$DIR/all-db_$(date +%Y%m%d_%H%M%S).sql"
mariadb-dump -h 127.0.0.1 -u pixicdn_user -p"$DB_PASS" --all-databases > "$FILE"
gzip -f "$FILE"
find "$DIR" -name '*.sql.gz' -mtime +7 -delete
echo "OK backup $FILE.gz"
