<?php
/**
 * Alexandra Operations 100k-record Local scale proof.
 *
 * Generates a prefix-locked synthetic workload without notifications, records
 * representative query plans and timings, compares deep cursor pagination with
 * a deep OFFSET, verifies the cached overview model, and removes only the exact
 * synthetic run.
 *
 * Usage:
 * php verify-am-ops-scale.php "C:\path\to\wordpress" [record-count]
 */

if (PHP_SAPI !== 'cli') {
	exit(1);
}

$wordpress_root = isset($argv[1]) ? rtrim((string) $argv[1], '/\\') : '';
if ('' === $wordpress_root || !is_file($wordpress_root . '/wp-load.php')) {
	fwrite(STDERR, "Pass a valid WordPress root containing wp-load.php.\n");
	exit(2);
}

$target_count = isset($argv[2]) ? (int) $argv[2] : 100000;
if ($target_count < 100000 || $target_count > 250000) {
	fwrite(STDERR, "Record count must be between 100000 and 250000.\n");
	exit(2);
}

require_once $wordpress_root . '/wp-load.php';

global $wpdb;

if (!class_exists('AM_Ops_Repository') || !class_exists('AM_Ops_Tables')) {
	fwrite(STDERR, "Alexandra Operations is not active.\n");
	exit(2);
}

$table       = AM_Ops_Tables::name(AM_Ops_Tables::SUBMISSIONS);
$token       = 'scale-' . bin2hex(random_bytes(6));
$token_short = strtoupper(substr(hash('sha256', $token), 0, 10));
$source      = 'scale_test_' . strtolower($token_short);
$base_time   = time();
$batch_size  = 1000;
$first_id    = 0;
$last_id     = 0;
$inserted    = 0;
$passed      = 0;
$failed      = 0;
$timings     = array();
$plans       = array();

$check = static function ($condition, $label, $details = '') use (&$passed, &$failed) {
	if ($condition) {
		$passed++;
		echo "[PASS] {$label}";
	} else {
		$failed++;
		echo "[FAIL] {$label}";
	}
	if ('' !== $details) {
		echo ": {$details}";
	}
	echo "\n";
};

$literal = static function ($value) use ($wpdb) {
	if (null === $value) {
		return 'NULL';
	}
	if (is_int($value) || is_float($value)) {
		return (string) $value;
	}
	return "'" . esc_sql((string) $value) . "'";
};

$measure = static function ($label, $sql, $iterations = 5) use ($wpdb, &$timings) {
	$wpdb->get_results($sql, ARRAY_A);
	if ('' !== $wpdb->last_error) {
		throw new RuntimeException("{$label} warm-up failed: {$wpdb->last_error}");
	}

	$samples = array();
	for ($run = 0; $run < $iterations; $run++) {
		$started = microtime(true);
		$wpdb->get_results($sql, ARRAY_A);
		if ('' !== $wpdb->last_error) {
			throw new RuntimeException("{$label} failed: {$wpdb->last_error}");
		}
		$samples[] = (microtime(true) - $started) * 1000;
	}
	sort($samples, SORT_NUMERIC);
	$median = $samples[(int) floor(count($samples) / 2)];
	$timings[$label] = array(
		'median_ms' => round($median, 2),
		'max_ms'    => round(max($samples), 2),
	);
	echo '[TIMING] ' . $label . ': median '
		. number_format($median, 2)
		. ' ms; max '
		. number_format(max($samples), 2)
		. " ms\n";

	return $median;
};

$explain = static function ($label, $sql) use ($wpdb, &$plans, $check) {
	$rows = $wpdb->get_results('EXPLAIN ' . $sql, ARRAY_A);
	if ('' !== $wpdb->last_error || !$rows) {
		$check(false, "{$label} has a readable EXPLAIN plan", $wpdb->last_error);
		return;
	}

	$access_types  = array_values(array_unique(array_column($rows, 'type')));
	$keys          = array_values(array_unique(array_filter(array_column($rows, 'key'))));
	$rows_examined = array_sum(array_map('intval', array_column($rows, 'rows')));
	$plans[$label] = array(
		'access'         => $access_types,
		'keys'           => $keys,
		'estimated_rows' => $rows_examined,
	);
	$check(
		!in_array('ALL', $access_types, true) && !empty($keys),
		"{$label} uses an index-backed plan",
		'access=' . implode(',', $access_types)
			. '; key=' . implode(',', $keys)
			. '; estimated rows=' . $rows_examined
	);
};

$columns = array(
	'public_ref',
	'type',
	'source',
	'status',
	'priority',
	'owner_user_id',
	'branch_slug',
	'branch_label',
	'customer_name',
	'customer_email',
	'customer_email_hash',
	'customer_phone',
	'subject',
	'payload',
	'internal_notes',
	'submitted_at',
	'updated_at',
	'follow_up_at',
	'spam_state',
	'notification_state',
	'version',
);

$types      = array('contact', 'visit', 'availability', 'application');
$statuses   = array('new', 'in_progress', 'waiting', 'replied', 'resolved');
$branches   = array(
	array('heston', 'Heston'),
	array('hounslow', 'Hounslow'),
	array('hammersmith', 'Hammersmith'),
);
$actor_ids  = get_users(array('role' => 'administrator', 'number' => 1, 'fields' => 'ID'));
$actor_id   = !empty($actor_ids) ? (int) $actor_ids[0] : 1;
$baseline   = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
$started_at = microtime(true);

echo "[RUN] Prefix-locked source: {$source}\n";
echo '[RUN] Baseline rows: ' . number_format($baseline) . "\n";
echo '[RUN] Generating ' . number_format($target_count) . " synthetic submissions in {$batch_size}-row batches.\n";

try {
	for ($batch_start = 0; $batch_start < $target_count; $batch_start += $batch_size) {
		$batch_count = min($batch_size, $target_count - $batch_start);
		$values      = array();

		for ($offset = 0; $offset < $batch_count; $offset++) {
			$index       = $batch_start + $offset;
			$type        = $types[$index % count($types)];
			$status      = $statuses[$index % count($statuses)];
			$branch      = $branches[$index % count($branches)];
			$email       = 'scale.' . strtolower($token_short) . '.' . $index . '@example.com';
			$submitted   = gmdate('Y-m-d H:i:s', $base_time - $index);
			$follow_up   = 0 === $index % 10
				? gmdate('Y-m-d H:i:s', $base_time - ($index % 3600))
				: null;
			$priority    = 0 === $index % 40
				? 'urgent'
				: (0 === $index % 10 ? 'high' : (0 === $index % 17 ? 'low' : 'normal'));
			$owner       = 0 === $index % 4 ? null : $actor_id;
			$spam_state  = 0 === $index % 100 ? 'suspected' : 'clean';
			$notify      = 0 === $index % 50 ? 'failed' : 'suppressed';
			$public_ref  = 'AM-2026-S' . $token_short . str_pad((string) $index, 6, '0', STR_PAD_LEFT);
			$phone       = '+447700' . str_pad((string) ($index % 1000000000), 9, '0', STR_PAD_LEFT);
			$payload     = wp_json_encode(
				array(
					'scale_run' => $token,
					'sequence'  => $index,
				),
				JSON_UNESCAPED_SLASHES
			);
			$record      = array(
				$public_ref,
				$type,
				$source,
				$status,
				$priority,
				$owner,
				$branch[0],
				$branch[1],
				'Scale Person ' . str_pad((string) $index, 6, '0', STR_PAD_LEFT),
				$email,
				hash('sha256', strtolower($email)),
				$phone,
				'Local scale verification ' . $token_short,
				$payload,
				'',
				$submitted,
				$submitted,
				$follow_up,
				$spam_state,
				$notify,
				1,
			);
			$values[] = '(' . implode(',', array_map($literal, $record)) . ')';
		}

		$sql = 'INSERT INTO ' . $table
			. ' (`' . implode('`,`', $columns) . '`) VALUES '
			. implode(',', $values);
		$result = $wpdb->query($sql);
		if (false === $result || $result !== $batch_count) {
			throw new RuntimeException(
				"Batch {$batch_start} inserted "
				. (false === $result ? 'no rows' : (int) $result)
				. " of {$batch_count}: {$wpdb->last_error}"
			);
		}

		$batch_first = (int) $wpdb->insert_id;
		if (0 === $first_id) {
			$first_id = $batch_first;
		}
		$last_id  = $batch_first + $batch_count - 1;
		$inserted += $batch_count;

		if (0 === $inserted % 10000 || $inserted === $target_count) {
			echo '[PROGRESS] Inserted ' . number_format($inserted) . " rows.\n";
		}
	}

	$insert_seconds = microtime(true) - $started_at;
	$source_count   = (int) $wpdb->get_var(
		$wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE source = %s", $source)
	);
	$range_count = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$table}
			 WHERE id BETWEEN %d AND %d AND source = %s",
			$first_id,
			$last_id,
			$source
		)
	);
	$total_after = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");

	$check(
		$target_count === $source_count && $target_count === $range_count,
		'The synthetic workload contains every requested row',
		number_format($source_count) . ' exact-source rows'
	);
	$check(
		$baseline + $target_count === $total_after,
		'No unrelated submission row changed during generation',
		'expected ' . number_format($baseline + $target_count)
			. '; found ' . number_format($total_after)
	);
	$check(
		$last_id - $first_id + 1 === $target_count,
		'The test run owns one contiguous, auditable ID range',
		"{$first_id}–{$last_id}"
	);
	echo '[TIMING] Insert workload: ' . number_format($insert_seconds, 2)
		. ' seconds (' . number_format($target_count / max(0.001, $insert_seconds), 0)
		. " rows/second).\n";

	$sample_index = (int) floor($target_count / 2);
	$sample_email = 'scale.' . strtolower($token_short) . '.' . $sample_index . '@example.com';
	$sample_hash  = hash('sha256', $sample_email);
	$deep_index   = min($target_count - 1000, 90000);
	$deep_time    = gmdate('Y-m-d H:i:s', $base_time - $deep_index);
	$deep_id      = $first_id + $deep_index;

	$queries = array(
		'Latest active inbox' => "SELECT id FROM {$table}
			WHERE archived_at IS NULL
			ORDER BY submitted_at DESC, id DESC LIMIT 51",
		'New unassigned queue' => "SELECT id FROM {$table}
			WHERE owner_user_id IS NULL AND status = 'new' AND archived_at IS NULL
			ORDER BY submitted_at DESC, id DESC LIMIT 51",
		'Type and status filter' => "SELECT id FROM {$table}
			WHERE type = 'contact' AND status = 'new' AND archived_at IS NULL
			ORDER BY submitted_at DESC, id DESC LIMIT 51",
		'Nursery and status filter' => "SELECT id FROM {$table}
			WHERE branch_slug = 'heston' AND status = 'new' AND archived_at IS NULL
			ORDER BY submitted_at DESC, id DESC LIMIT 51",
		'Owner follow-up queue' => "SELECT id FROM {$table}
			WHERE owner_user_id = {$actor_id}
				AND status = 'in_progress'
				AND follow_up_at IS NOT NULL
				AND archived_at IS NULL
			ORDER BY follow_up_at ASC, id ASC LIMIT 51",
		'Global due follow-up queue' => "SELECT id FROM {$table}
			WHERE follow_up_at IS NOT NULL
				AND follow_up_at <= UTC_TIMESTAMP()
				AND status NOT IN ('resolved','closed')
				AND archived_at IS NULL
			ORDER BY submitted_at DESC, id DESC LIMIT 51",
		'Exact email search' => "SELECT id FROM {$table}
			WHERE customer_email_hash = '{$sample_hash}' AND archived_at IS NULL
			ORDER BY submitted_at DESC, id DESC LIMIT 10",
		'Name-prefix search' => "SELECT id FROM {$table}
			WHERE customer_name LIKE 'Scale Person 0500%' AND archived_at IS NULL
			ORDER BY submitted_at DESC, id DESC LIMIT 51",
		'Deep keyset page' => $wpdb->prepare(
			"SELECT id FROM {$table}
			 WHERE archived_at IS NULL
				AND (submitted_at < %s OR (submitted_at = %s AND id < %d))
			 ORDER BY submitted_at DESC, id DESC LIMIT 51",
			$deep_time,
			$deep_time,
			$deep_id
		),
	);

	foreach ($queries as $label => $sql) {
		$explain($label, $sql);
		$measure($label, $sql);
	}

	$deep_offset_sql = "SELECT id FROM {$table}
		WHERE archived_at IS NULL
		ORDER BY submitted_at DESC, id DESC LIMIT 51 OFFSET {$deep_index}";
	$offset_median = $measure('Deep OFFSET comparison', $deep_offset_sql);
	$cursor_median = $timings['Deep keyset page']['median_ms'];
	$check(
		$cursor_median <= ($offset_median * 1.5) + 2,
		'Deep cursor navigation remains predictable instead of degrading with history',
		'cursor ' . number_format($cursor_median, 2)
			. ' ms vs OFFSET ' . number_format($offset_median, 2) . ' ms'
	);

	$repository   = new AM_Ops_Repository();
	$repository_page = $repository->query(array('limit' => 50));
	$check(
		50 === count($repository_page['items']) && '' !== $repository_page['older_cursor'],
		'The production repository still returns one bounded cursor page at scale'
	);
	$exact_page = $repository->query(array('search' => $sample_email, 'limit' => 10));
	$check(
		1 === count($exact_page['items'])
			&& $sample_email === $exact_page['items'][0]['customer_email'],
		'Indexed exact-email search returns one immutable record at scale'
	);

	delete_transient('am_ops_overview_metrics');
	$metrics_started = microtime(true);
	$metrics         = $repository->overview_metrics();
	$metrics_cold_ms = (microtime(true) - $metrics_started) * 1000;
	$cache_started   = microtime(true);
	$cached_metrics  = $repository->overview_metrics();
	$metrics_cache_ms = (microtime(true) - $cache_started) * 1000;
	echo '[TIMING] Overview model: cold ' . number_format($metrics_cold_ms, 2)
		. ' ms; cached ' . number_format($metrics_cache_ms, 2) . " ms.\n";
	$check(
		(int) $metrics['total'] === $total_after
			&& (int) $cached_metrics['total'] === $total_after,
		'Overview metrics reconcile with the full high-volume table'
	);
	$check(
		$metrics_cache_ms < max(5, $metrics_cold_ms),
		'Repeated dashboard loads use the short-lived metrics cache'
	);

	echo '[REPORT] Query plans: ' . wp_json_encode($plans, JSON_UNESCAPED_SLASHES) . "\n";
	echo '[REPORT] Query timings: ' . wp_json_encode($timings, JSON_UNESCAPED_SLASHES) . "\n";
} catch (Throwable $error) {
	$failed++;
	echo '[FAIL] Scale verifier exception: ' . $error->getMessage() . "\n";
} finally {
	$source_count = (int) $wpdb->get_var(
		$wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE source = %s", $source)
	);
	$marker_count = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$table}
			 WHERE source = %s AND payload LIKE %s",
			$source,
			'%"scale_run":"' . $wpdb->esc_like($token) . '"%'
		)
	);

	if ($source_count > 0 && $source_count === $marker_count) {
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table}
				 WHERE source = %s AND payload LIKE %s",
				$source,
				'%"scale_run":"' . $wpdb->esc_like($token) . '"%'
			)
		);
		$remaining = (int) $wpdb->get_var(
			$wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE source = %s", $source)
		);
		if ($deleted === $source_count && 0 === $remaining) {
			echo '[CLEANUP] Removed exactly ' . number_format($deleted)
				. " prefix-locked synthetic rows.\n";
		} else {
			$failed++;
			echo '[FAIL] Cleanup mismatch: deleted ' . (int) $deleted
				. '; remaining ' . $remaining . ".\n";
		}
	} elseif (0 === $source_count) {
		echo "[CLEANUP] No synthetic rows remained.\n";
	} else {
		$failed++;
		echo '[FAIL] Cleanup refused because the exact-source and payload-marker counts differ: '
			. $source_count . ' vs ' . $marker_count . ".\n";
	}

	delete_transient('am_ops_overview_metrics');
	$final_total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
	$check(
		$baseline === $final_total,
		'Cleanup restored the exact pre-test submission count',
		'baseline ' . number_format($baseline) . '; final ' . number_format($final_total)
	);
}

echo "Passed: {$passed}; Failed: {$failed}\n";
exit($failed > 0 ? 1 : 0);
