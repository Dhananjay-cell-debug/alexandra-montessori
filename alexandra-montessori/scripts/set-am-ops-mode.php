<?php
/**
 * Explicitly switch a WordPress install between legacy and operations routes.
 *
 * Enabling is refused unless schema and legacy reconciliation are healthy.
 *
 * Usage:
 * php scripts/set-am-ops-mode.php "C:\path\to\wordpress" enable
 * php scripts/set-am-ops-mode.php "C:\path\to\wordpress" disable
 */

if (PHP_SAPI !== 'cli') {
	exit(1);
}

$wordpress_root = isset($argv[1]) ? rtrim((string) $argv[1], '/\\') : '';
$mode           = strtolower(trim((string) ($argv[2] ?? '')));
if ('' === $wordpress_root || !is_file($wordpress_root . '/wp-load.php')) {
	fwrite(STDERR, "Pass a valid WordPress root containing wp-load.php.\n");
	exit(2);
}
if (!in_array($mode, array('enable', 'disable'), true)) {
	fwrite(STDERR, "Mode must be enable or disable.\n");
	exit(2);
}

require_once $wordpress_root . '/wp-load.php';

if ('disable' === $mode) {
	update_option('am_ops_replacement_enabled', false, false);
	echo "Alexandra Operations replacement disabled. Legacy routes resume on the next request.\n";
	exit(0);
}

$schema = AM_Ops_Schema::health();
if (!$schema['healthy']) {
	fwrite(STDERR, 'Refused: operations schema is missing ' . implode(', ', $schema['missing']) . ".\n");
	exit(3);
}

$reconciliation = (new AM_Ops_Migration())->reconcile();
if (!$reconciliation['healthy']) {
	fwrite(STDERR, 'Refused: legacy reconciliation is not healthy: ' . wp_json_encode($reconciliation) . "\n");
	exit(4);
}

update_option('am_ops_replacement_enabled', true, false);
echo "Alexandra Operations replacement enabled. New routes and command centre load on the next request.\n";
echo 'Reconciliation: ' . wp_json_encode($reconciliation) . "\n";
