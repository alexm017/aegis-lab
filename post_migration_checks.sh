#!/usr/bin/env bash
set -euo pipefail

# Basic verification after migration on the new server.
# Usage:
#   ./post_migration_checks.sh [base_2026_dir]
#
# Example:
#   ./post_migration_checks.sh /srv/aegis/2026

BASE_2026="${1:-/srv/aegis/2026}"
MAIN_DIR="$BASE_2026/ZeroSec_Main"
CTF_DIR="$BASE_2026/ZeroSec_CTF"

echo "== Service status =="
systemctl is-active apache2 || true
systemctl is-active docker || true
systemctl is-active aegis-discord-bot || true
systemctl is-active aegis-uptime-bot || true
systemctl is-active aegis-node-uptime-bot || true

echo
echo "== HTTP/HTTPS checks =="
curl -k -I -m 8 https://aegislab.ro | sed -n '1,3p' || true
curl -k -I -m 8 https://ctf.aegislab.ro | sed -n '1,3p' || true
curl -I -m 5 http://127.0.0.1:8000 | sed -n '1,3p' || true

echo
echo "== Docker status (CTFd + challenges) =="
docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}' | sed -n '1,200p' || true

if [[ -f "$CTF_DIR/web_challenges/docker-compose.yml" ]]; then
  docker compose -f "$CTF_DIR/web_challenges/docker-compose.yml" ps || true
fi

echo
echo "== Main website writable data paths =="
if [[ -d "$MAIN_DIR/data" ]]; then
  ls -ld "$MAIN_DIR/data" || true
  ls -l "$MAIN_DIR/data" | sed -n '1,40p' || true
fi

echo
echo "== Apache config test =="
apache2ctl configtest || true

echo
echo "Checks completed."
