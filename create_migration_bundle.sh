#!/usr/bin/env bash
set -euo pipefail

# Create a migration bundle for:
# - ZeroSec_Main
# - ZeroSec_CTF
# - OpenML_Alphabit/discord_uptime_bot
# - OpenML_Alphabit/uptime_bot
# - OpenML_Alphabit/openai/assets/includes/app_config.php
#
# Run with sudo so CTFd .data/mysql is included correctly.

if [[ "${EUID:-$(id -u)}" -ne 0 ]]; then
  echo "Run as root so Docker volume data is fully readable:"
  echo "  sudo $0 [output_dir]"
  exit 1
fi

ROOT_2026="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT_DIR="${1:-/tmp}"
TS="$(date +%Y%m%d_%H%M%S)"
BUNDLE_NAME="aegis_migration_${TS}.tar.gz"
BUNDLE_PATH="${OUT_DIR%/}/${BUNDLE_NAME}"
MANIFEST_PATH="${OUT_DIR%/}/aegis_migration_manifest_${TS}.txt"

mkdir -p "$OUT_DIR"

cat > "$MANIFEST_PATH" <<EOF
Aegis Lab migration bundle
Generated: $(date -Iseconds)
Source root: $ROOT_2026
Included:
- ZeroSec_Main
- ZeroSec_CTF
- OpenML_Alphabit/discord_uptime_bot
- OpenML_Alphabit/uptime_bot
- OpenML_Alphabit/openai/assets/includes/app_config.php
EOF

tar \
  --exclude='*.pyc' \
  --exclude='__pycache__' \
  --exclude='.cache' \
  --exclude='node_modules' \
  --exclude='bot.log' \
  --exclude='runtime.log' \
  -C "$ROOT_2026" \
  -czf "$BUNDLE_PATH" \
  ZeroSec_Main \
  ZeroSec_CTF \
  OpenML_Alphabit/discord_uptime_bot \
  OpenML_Alphabit/uptime_bot \
  OpenML_Alphabit/openai/assets/includes/app_config.php

sha256sum "$BUNDLE_PATH" | tee "${BUNDLE_PATH}.sha256"

echo
echo "Bundle created:"
echo "  $BUNDLE_PATH"
echo "  ${BUNDLE_PATH}.sha256"
echo "  $MANIFEST_PATH"
echo
echo "Transfer example:"
echo "  scp \"$BUNDLE_PATH\" \"${BUNDLE_PATH}.sha256\" user@NEW_SERVER_IP:/tmp/"
