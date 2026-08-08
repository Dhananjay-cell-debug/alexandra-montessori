#!/usr/bin/env bash
set -Eeuo pipefail

docroot="${1:?WordPress document root is required}"
release="${2:?Remote release directory is required}"
stamp="${3:?Release timestamp is required}"
monitor_email="${4:?Private monitor recipient is required}"
backup="$HOME/backups/alexandra-predeploy-$stamp"
theme_root="$docroot/wp-content/themes"
mu_root="$docroot/wp-content/mu-plugins"
stage="$release/stage"
rollback="$release/rollback"
theme_swapped=0
mu_swapped=0
config_changed=0

case "$docroot" in
	*/public_html/alexandra) ;;
	*/alexandramontessori.co.uk/public_html) ;;
	*)
		echo "Refusing unexpected document root." >&2
		exit 1
		;;
esac

rollback_release() {
	local exit_code="${1:-1}"
	trap - ERR
	set +e

	if [[ "$config_changed" -eq 1 && -f "$backup/wp-config.php" ]]; then
		cp "$backup/wp-config.php" "$docroot/wp-config.php"
	fi

	if [[ "$mu_swapped" -eq 1 ]]; then
		rm -rf "$mu_root/alexandra-operations"
		rm -f "$mu_root/alexandra-operations.php"

		if [[ -d "$rollback/mu/alexandra-operations" ]]; then
			mv "$rollback/mu/alexandra-operations" "$mu_root/alexandra-operations"
		fi
		if [[ -f "$rollback/mu/alexandra-operations.php" ]]; then
			mv "$rollback/mu/alexandra-operations.php" "$mu_root/alexandra-operations.php"
		fi
	fi

	if [[ "$theme_swapped" -eq 1 ]]; then
		rm -rf "$theme_root/alexandra-theme"
		if [[ -d "$rollback/alexandra-theme" ]]; then
			mv "$rollback/alexandra-theme" "$theme_root/alexandra-theme"
		fi
	fi

	wp option update am_ops_replacement_enabled 0 --path="$docroot" --quiet >/dev/null 2>&1 || true
	wp maintenance-mode deactivate --path="$docroot" >/dev/null 2>&1 || true
	echo "Deployment rolled back after a failed validation." >&2
	exit "$exit_code"
}

trap 'rollback_release $?' ERR

for required in \
	"$backup/database.sql" \
	"$backup/alexandra-theme.tar.gz" \
	"$backup/mu-plugins.tar.gz" \
	"$backup/wp-config.php" \
	"$release/alexandra-theme.tar.gz" \
	"$release/mu-plugins.tar.gz" \
	"$release/production-activate-operations.php"; do
	test -s "$required"
done

mkdir -p "$stage" "$rollback/mu"
rm -rf "$stage/alexandra-theme" "$stage/mu-plugins"
tar -xzf "$release/alexandra-theme.tar.gz" -C "$stage"
tar -xzf "$release/mu-plugins.tar.gz" -C "$stage"

test -f "$stage/alexandra-theme/functions.php"
test -f "$stage/alexandra-theme/dist/.vite/manifest.json"
test -f "$stage/mu-plugins/alexandra-operations.php"
test -f "$stage/mu-plugins/alexandra-operations/alexandra-operations.php"

find "$stage/alexandra-theme" "$stage/mu-plugins" -type f -name '*.php' -print0 \
	| while IFS= read -r -d '' php_file; do
		php -l "$php_file" >/dev/null
	done

php -r '
$manifest = json_decode(file_get_contents($argv[1]), true);
if (!is_array($manifest) || empty($manifest["index.html"]["file"])) {
	fwrite(STDERR, "Invalid Vite manifest.\n");
	exit(1);
}
$entry = dirname(dirname($argv[1])) . "/" . $manifest["index.html"]["file"];
if (!is_file($entry)) {
	fwrite(STDERR, "Manifest entry asset is missing.\n");
	exit(1);
}
' "$stage/alexandra-theme/dist/.vite/manifest.json"

wp maintenance-mode activate --path="$docroot" >/dev/null

mv "$theme_root/alexandra-theme" "$rollback/alexandra-theme"
mv "$stage/alexandra-theme" "$theme_root/alexandra-theme"
theme_swapped=1

if [[ -d "$mu_root/alexandra-operations" ]]; then
	mv "$mu_root/alexandra-operations" "$rollback/mu/alexandra-operations"
fi
if [[ -f "$mu_root/alexandra-operations.php" ]]; then
	mv "$mu_root/alexandra-operations.php" "$rollback/mu/alexandra-operations.php"
fi
mv "$stage/mu-plugins/alexandra-operations" "$mu_root/alexandra-operations"
mv "$stage/mu-plugins/alexandra-operations.php" "$mu_root/alexandra-operations.php"
mu_swapped=1

wp config set AM_TEST_NOTIFY_EMAIL '' --type=constant --path="$docroot" --quiet
wp config set AM_OPS_MONITOR_BCC "$monitor_email" --type=constant --path="$docroot" --quiet
wp config set AM_MONITOR_BCC "$monitor_email" --type=constant --path="$docroot" --quiet
config_changed=1

activation_output="$(
	wp eval-file "$release/production-activate-operations.php" --path="$docroot"
)"
echo "$activation_output"

wp cache flush --path="$docroot" >/dev/null
wp maintenance-mode deactivate --path="$docroot" >/dev/null

trap - ERR

# ---------------------------------------------------------------------------
# Post-deploy CDN staleness check (added 2026-07-25).
#
# On 2026-07-25 the live homepage served a stale copy of the old Hostinger AI
# placeholder for ~24h. The origin was correct the entire time — Hostinger's CDN
# was replaying a 7-day object it had stored during the domain move. A deploy
# that only verifies the origin will happily report success while every real
# visitor sees something else, so check what the PUBLIC url actually returns.
#
# Optional 5th argument, e.g. https://alexandramontessori.co.uk/
# Runs after `trap - ERR` on purpose: a network blip here must never trigger a
# rollback of an otherwise good deploy.
# ---------------------------------------------------------------------------
public_url="${5:-}"

if [[ -n "$public_url" ]]; then
	cdn_stale=0
	origin_body="$(curl -fsS --max-time 25 "${public_url}?cachebust=$stamp" 2>/dev/null || true)"
	public_body="$(curl -fsS --max-time 25 "$public_url" 2>/dev/null || true)"

	if ! grep -q 'amData' <<<"$origin_body"; then
		echo "WARNING: cache-busted response is missing window.amData — the ORIGIN itself looks wrong." >&2
		cdn_stale=1
	fi

	if ! grep -q 'amData' <<<"$public_body"; then
		echo "WARNING: the public (cacheable) response is missing window.amData." >&2
		cdn_stale=1
	fi

	if grep -q 'trans-menu' <<<"$public_body"; then
		echo "WARNING: the public response is the Hostinger AI PLACEHOLDER page." >&2
		cdn_stale=1
	fi

	if [[ "$cdn_stale" -eq 1 ]]; then
		echo "CDN=stale"
		echo "ACTION REQUIRED: flush the edge cache before calling this deploy done —" >&2
		echo "  hPanel (client account u205707676) -> Websites -> alexandramontessori.co.uk" >&2
		echo "  -> Performance -> CDN -> Flush cache. Then re-run this verification." >&2
	else
		echo "CDN=fresh"
	fi
fi

echo "DEPLOYMENT=complete"
echo "ROLLBACK=$rollback"
