#!/usr/bin/env bash
# Setup bucket RustFS (Server 3). Butuh: MinIO client (mc) + env RUSTFS_*.
# Jalankan sekali setelah RustFS hidup.
set -euo pipefail
: "${RUSTFS_ENDPOINT:?isi RUSTFS_ENDPOINT, mis. http://192.168.1.50:9000}"
: "${RUSTFS_KEY:?isi RUSTFS_KEY}"
: "${RUSTFS_SECRET:?isi RUSTFS_SECRET}"
mc alias set rustfs "$RUSTFS_ENDPOINT" "$RUSTFS_KEY" "$RUSTFS_SECRET" >/dev/null
mc mb --ignore-existing rustfs/pixicdn
mc anonymous set download rustfs/pixicdn
echo "OK bucket pixicdn (public download)"
