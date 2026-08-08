<?php
/**
 * Standalone verification for operations-mail identity and DKIM configuration.
 *
 * This intentionally avoids booting WordPress or sending email.
 *
 * Usage:
 * php scripts/verify-am-ops-mail-identity.php "C:\path\to\dkim-private.pem"
 */

if (PHP_SAPI !== 'cli') {
	exit(1);
}

$key_path = isset($argv[1]) ? (string) $argv[1] : '';
if ('' === $key_path || !is_readable($key_path)) {
	fwrite(STDERR, "Pass the readable DKIM private-key path.\n");
	exit(2);
}

define('ABSPATH', __DIR__ . '/fake/domains/alexandramontessori.co.uk/public_html/');
define('AM_OPS_DKIM_PRIVATE_KEY', $key_path);
define('AM_OPS_MONITOR_BCC', 'legacy-monitor@gmail.com');

final class WP_Error {
	public function __construct($code = '', $message = '') {
		unset($code, $message);
	}
}

function sanitize_email($email) {
	return filter_var((string) $email, FILTER_VALIDATE_EMAIL) ?: '';
}

function is_email($email) {
	return filter_var((string) $email, FILTER_VALIDATE_EMAIL) ?: false;
}

function wp_strip_all_tags($value) {
	return strip_tags((string) $value);
}

function home_url($path = '') {
	return 'https://alexandramontessori.co.uk' . (string) $path;
}

function wp_parse_url($url, $component = -1) {
	return parse_url((string) $url, $component);
}

function is_wp_error($value) {
	return $value instanceof WP_Error;
}

require_once dirname(__DIR__) . '/wordpress/mu-plugins/alexandra-operations/includes/class-am-ops-queue.php';

$passed = 0;
$failed = 0;
$check  = static function ($condition, $label) use (&$passed, &$failed) {
	if ($condition) {
		$passed++;
		echo "[PASS] {$label}\n";
		return;
	}

	$failed++;
	echo "[FAIL] {$label}\n";
};
$invoke = static function ($object, $method, array $arguments = array()) {
	$reflection = new ReflectionMethod($object, $method);
	$reflection->setAccessible(true);

	return $reflection->invokeArgs($object, $arguments);
};

$queue   = new AM_Ops_Queue();
$headers = $invoke(
	$queue,
	'sender_identity_headers',
	array(
		array(
			'customer_name'  => "Example Parent\r\nBcc: injected@example.com",
			'customer_email' => 'parent@gmail.com',
		)
	)
);
$from_headers  = array_values(array_filter($headers, static function ($header) {
	return 0 === stripos((string) $header, 'From:');
}));
$reply_headers = array_values(array_filter($headers, static function ($header) {
	return 0 === stripos((string) $header, 'Reply-To:');
}));

$check(1 === count($from_headers), 'Exactly one From header is emitted');
$check(
	false !== strpos($from_headers[0] ?? '', '<parent@gmail.com>'),
	'From uses the current form submitter email'
);
$check(
	false === stripos($from_headers[0] ?? '', ' via '),
	'The From display name contains no via wording'
);
$check(
	1 === count($reply_headers) && false !== strpos($reply_headers[0], '<parent@gmail.com>'),
	'Reply-To uses the current form submitter email'
);
$notification_headers = $invoke(
	$queue,
	'notification_headers',
	array(
		array(
			'customer_name'  => 'Example Parent',
			'customer_email' => 'parent@gmail.com',
		)
	)
);
$check(
	0 === count(array_filter($notification_headers, static function ($header) {
		return 0 === stripos((string) $header, 'Bcc:');
	})),
	'Form notification is not copied to the legacy personal monitoring mailbox'
);
$check(
	0 === count(array_filter($headers, static function ($header) {
		return false !== strpos((string) $header, "\r") || false !== strpos((string) $header, "\n");
	})),
	'Visitor-supplied names cannot inject another mail header'
);

$digest_headers = $invoke($queue, 'sender_identity_headers', array(array()));
$check(
	in_array(
		'From: "Alexandra Montessori Website" <website@forms.alexandramontessori.co.uk>',
		$digest_headers,
		true
	),
	'Digest sender is the Alexandra Montessori website identity'
);
$check(
	0 === count(array_filter($digest_headers, static function ($header) {
		return 0 === stripos((string) $header, 'Reply-To:');
	})),
	'Digest does not assign one submitter as its reply destination'
);

$configuration = $invoke(
	$queue,
	'production_mailer_configuration',
	array('parent@gmail.com', 'Example Parent')
);
$check(is_callable($configuration), 'Production mailer configuration is available with the signing key');
if (is_callable($configuration)) {
	$mailer = new class() {
		public $transport = '';
		public $From = '';
		public $FromName = '';
		public $Sender = 'legacy-personal@gmail.com';
		public $DKIM_domain = '';
		public $DKIM_selector = '';
		public $DKIM_private = '';
		public $DKIM_passphrase = 'unexpected';
		public $DKIM_identity = '';

		public function isMail() {
			$this->transport = 'mail';
		}

		public function setFrom($address, $name = '', $auto = true) {
			$this->From = $address;
			$this->FromName = $name;
			if ($auto && '' === $this->Sender) {
				$this->Sender = $address;
			}
		}
	};
	$configuration($mailer);
	$check('mail' === $mailer->transport, 'Operations mail bypasses the legacy personal-Gmail SMTP hook');
	$check(
		'parent@gmail.com' === $mailer->From && 'Example Parent' === $mailer->FromName,
		'Production mailer preserves the literal submitter From identity'
	);
	$check(
		'website@forms.alexandramontessori.co.uk' === $mailer->Sender,
		'Legacy personal Gmail is removed from the envelope sender'
	);
	$check(
		'forms.alexandramontessori.co.uk' === $mailer->DKIM_domain
		&& 'website' === $mailer->DKIM_selector
		&& $key_path === $mailer->DKIM_private
		&& 'website@forms.alexandramontessori.co.uk' === $mailer->DKIM_identity,
		'Operations mail is signed with the isolated Alexandra Montessori identity'
	);
}

echo "\n{$passed} passed, {$failed} failed.\n";
exit($failed > 0 ? 1 : 0);
