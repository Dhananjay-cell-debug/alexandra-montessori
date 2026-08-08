#!/usr/bin/env bash
set -Eeuo pipefail

docroot="${1:?WordPress document root is required}"
stamp="${2:?Backup timestamp is required}"
private_uploads="${3:-}"
backup="$HOME/backups/alexandra-complete-$stamp"
defaults_file="$backup/mysql-client.cnf"

case "$docroot" in
	*/alexandramontessori.co.uk/public_html) ;;
	*)
		echo "Refusing unexpected document root." >&2
		exit 1
		;;
esac

umask 077
mkdir -p "$backup"

cleanup() {
	rm -f "$defaults_file"
}
trap cleanup EXIT

mysql_option_value() {
	local value="$1"
	value="${value//\\/\\\\}"
	value="${value//\"/\\\"}"
	printf '"%s"' "$value"
}

db_name="$(wp config get DB_NAME --path="$docroot")"
db_user="$(wp config get DB_USER --path="$docroot")"
db_password="$(wp config get DB_PASSWORD --path="$docroot")"
db_host="$(wp config get DB_HOST --path="$docroot")"

{
	echo '[client]'
	printf 'user=%s\n' "$(mysql_option_value "$db_user")"
	printf 'password=%s\n' "$(mysql_option_value "$db_password")"

	if [[ "$db_host" =~ ^(.+):([0-9]+)$ ]]; then
		printf 'host=%s\n' "$(mysql_option_value "${BASH_REMATCH[1]}")"
		printf 'port=%s\n' "${BASH_REMATCH[2]}"
	elif [[ "$db_host" == /* ]]; then
		printf 'socket=%s\n' "$(mysql_option_value "$db_host")"
	else
		printf 'host=%s\n' "$(mysql_option_value "$db_host")"
	fi
} > "$defaults_file"

mysqldump \
	--defaults-extra-file="$defaults_file" \
	--single-transaction \
	--quick \
	--skip-lock-tables \
	--no-tablespaces \
	--add-drop-table \
	"$db_name" \
	> "$backup/database.sql"

test -s "$backup/database.sql"
gzip -9 "$backup/database.sql"

tar \
	-C "$(dirname "$docroot")" \
	-czf "$backup/wordpress-files.tar.gz" \
	"$(basename "$docroot")"

if [[ -n "$private_uploads" && -d "$private_uploads" ]]; then
	tar \
		-C "$(dirname "$private_uploads")" \
		-czf "$backup/private-uploads.tar.gz" \
		"$(basename "$private_uploads")"
else
	tar -czf "$backup/private-uploads.tar.gz" --files-from /dev/null
fi

{
	printf 'snapshot=%s\n' "$stamp"
	printf 'created_utc=%s\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)"
	printf 'home=%s\n' "$(wp option get home --path="$docroot")"
	printf 'siteurl=%s\n' "$(wp option get siteurl --path="$docroot")"
	printf 'core_version=%s\n' "$(wp core version --path="$docroot")"
	printf 'active_theme=%s\n' "$(wp option get stylesheet --path="$docroot")"
	printf 'maintenance=%s\n' "$(wp maintenance-mode status --path="$docroot" 2>&1 || true)"
} > "$backup/server-state.txt"

wp plugin list --path="$docroot" --format=csv > "$backup/plugins.csv"
wp theme list --path="$docroot" --format=csv > "$backup/themes.csv"

(
	cd "$backup"
	sha256sum \
		database.sql.gz \
		wordpress-files.tar.gz \
		private-uploads.tar.gz \
		server-state.txt \
		plugins.csv \
		themes.csv \
		> SHA256SUMS
)

chmod -R go-rwx "$backup"
printf 'REMOTE_BACKUP=%s\n' "$backup"
du -sh "$backup"
ls -lh "$backup"
