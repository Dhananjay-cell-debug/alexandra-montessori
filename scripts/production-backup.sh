#!/usr/bin/env bash
set -euo pipefail

docroot="${1:?WordPress document root is required}"
stamp="${2:?Release timestamp is required}"
db_defaults="${3:?Temporary MySQL defaults file is required}"
db_name="${4:?Database name is required}"
backup="$HOME/backups/alexandra-predeploy-$stamp"

umask 077
mkdir -p "$backup"

mysqldump \
  --defaults-extra-file="$db_defaults" \
  --single-transaction \
  --quick \
  --skip-lock-tables \
  "$db_name" \
  > "$backup/database.sql"
tar -C "$docroot/wp-content/themes" -czf "$backup/alexandra-theme.tar.gz" alexandra-theme
tar -C "$docroot/wp-content" -czf "$backup/mu-plugins.tar.gz" mu-plugins
cp "$docroot/wp-config.php" "$backup/wp-config.php"

if [[ -f "$docroot/.htaccess" ]]; then
  cp "$docroot/.htaccess" "$backup/htaccess"
fi

wp core version --path="$docroot" > "$backup/wp-core-version.txt"
wp option get stylesheet --path="$docroot" > "$backup/active-theme.txt"

test -s "$backup/database.sql"
grep -Eq -- "^-- (MySQL|MariaDB) dump" "$backup/database.sql"
gzip -t "$backup/alexandra-theme.tar.gz"
gzip -t "$backup/mu-plugins.tar.gz"

sha256sum \
  "$backup/database.sql" \
  "$backup/alexandra-theme.tar.gz" \
  "$backup/mu-plugins.tar.gz" \
  "$backup/wp-config.php" \
  > "$backup/SHA256SUMS"

echo "REMOTE_BACKUP=$backup"
du -sh "$backup" | awk '{print "BACKUP_SIZE=" $1}'
