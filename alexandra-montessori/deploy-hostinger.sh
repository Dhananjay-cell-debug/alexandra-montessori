#!/usr/bin/env bash
# Upload dist/ to Hostinger via FTPS.
# Usage:
#   FTP_HOST=... FTP_USER=... FTP_PASS=... REMOTE_DIR=/public_html/<sub> ./deploy-hostinger.sh
set -euo pipefail

HERE="$(cd "$(dirname "$0")" && pwd)"
LOCAL_DIR="$HERE/dist"
: "${FTP_HOST:?set FTP_HOST}"
: "${FTP_USER:?set FTP_USER}"
: "${FTP_PASS:?set FTP_PASS}"
: "${REMOTE_DIR:?set REMOTE_DIR (e.g. /public_html/alexandra)}"
PORT="${FTP_PORT:-21}"

cd "$LOCAL_DIR"
count=0
# include dotfiles like .htaccess
find . -type f | while read -r f; do
  rel="${f#./}"
  curl -sS --ftp-create-dirs --ftp-method nocwd --ssl-reqd -k \
    -T "$f" \
    --user "$FTP_USER:$FTP_PASS" \
    "ftp://$FTP_HOST:$PORT$REMOTE_DIR/$rel"
  count=$((count+1))
  echo "  uploaded: $rel"
done
echo "Done."
