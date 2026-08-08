<?php
/**
 * Standalone contract for media normalisation and portability.
 *
 *   php tests/media-portability-standalone.php
 *
 * Pins down the failure that put `http://alexandra-montessori.local/...` on the
 * live site: a design carries media across hosts, so a bare URL is not enough.
 */

define('ABSPATH', __DIR__);

$GLOBALS['am_site_url']    = 'https://alexandramontessori.co.uk';
$GLOBALS['am_attachments'] = array(
	188 => 'https://alexandramontessori.co.uk/wp-content/uploads/2026/08/IMG_1998-scaled-1-600x800-1.jpeg',
	131 => 'https://alexandramontessori.co.uk/wp-content/uploads/2026/08/about-us-children.webp',
);
$GLOBALS['am_srcsets'] = array(
	188 => 'https://alexandramontessori.co.uk/wp-content/uploads/2026/08/IMG_1998-300x400.jpeg 300w, https://alexandramontessori.co.uk/wp-content/uploads/2026/08/IMG_1998-scaled-1-600x800-1.jpeg 600w',
);
$GLOBALS['am_files_on_disk'] = array(
	'/var/www/uploads/2026/08/IMG_1998-scaled-1-600x800-1.jpeg' => true,
	'/var/www/uploads/2026/08/about-us-children.webp'           => true,
);

function home_url($path = '/') {
	return rtrim($GLOBALS['am_site_url'], '/') . $path;
}

function is_ssl() {
	return true;
}

function wp_parse_url($url, $component = -1) {
	return -1 === $component ? parse_url($url) : parse_url($url, $component);
}

function absint($v) {
	return abs((int) $v);
}

function trailingslashit($v) {
	return rtrim($v, '/\\') . '/';
}

function wp_upload_dir() {
	return array(
		'basedir' => '/var/www/uploads',
		'baseurl' => $GLOBALS['am_site_url'] . '/wp-content/uploads',
		'error'   => false,
	);
}

function file_exists_stub($path) {
	return isset($GLOBALS['am_files_on_disk'][$path]);
}

function wp_get_attachment_url($id) {
	return $GLOBALS['am_attachments'][(int) $id] ?? false;
}

function wp_get_attachment_image_srcset($id, $size = 'medium') {
	return $GLOBALS['am_srcsets'][(int) $id] ?? false;
}

function get_post_type($id) {
	return isset($GLOBALS['am_attachments'][(int) $id]) ? 'attachment' : false;
}

function attachment_url_to_postid($url) {
	foreach ($GLOBALS['am_attachments'] as $id => $known) {
		if ($known === $url) {
			return $id;
		}
	}
	return 0;
}

/**
 * Mirror of esc_url_raw for the protocols this class permits.
 */
function esc_url_raw($url, $protocols = null) {
	$url = trim((string) $url);
	if ('' === $url) {
		return '';
	}
	$allowed = $protocols ?: array('http', 'https');
	$scheme  = strtolower((string) parse_url($url, PHP_URL_SCHEME));
	if ('' === $scheme) {
		// Site-relative path.
		return 0 === strpos($url, '/') ? $url : '';
	}
	if (!in_array($scheme, $allowed, true)) {
		return '';
	}
	return $url;
}

require_once dirname(__DIR__) . '/includes/class-am-vb-media.php';

$passed = 0;
$failed = 0;

function check($label, $actual, $expected) {
	global $passed, $failed;
	if ($actual === $expected) {
		$passed++;
		echo "  PASS  {$label}\n";
		return;
	}
	$failed++;
	echo "  FAIL  {$label}\n";
	echo "        expected: " . var_export($expected, true) . "\n";
	echo "        actual:   " . var_export($actual, true) . "\n";
}

$site = 'https://alexandramontessori.co.uk';

echo "\nnormalise_src - the host rules\n";

check('empty stays empty', AM_VB_Media::normalise_src(''), '');
check('whitespace only stays empty', AM_VB_Media::normalise_src('   '), '');
check(
	'our own URL passes through',
	AM_VB_Media::normalise_src($site . '/wp-content/uploads/2026/08/x.webp'),
	$site . '/wp-content/uploads/2026/08/x.webp'
);
check(
	'a site-relative path passes through',
	AM_VB_Media::normalise_src('/wp-content/uploads/2026/08/x.webp'),
	'/wp-content/uploads/2026/08/x.webp'
);

echo "\nThe bug that reached production\n";

check(
	'a .local uploads URL is rehomed to this site',
	AM_VB_Media::normalise_src('http://alexandra-montessori.local/wp-content/uploads/2026/08/IMG_1998-scaled-1-600x800-1.jpeg'),
	$site . '/wp-content/uploads/2026/08/IMG_1998-scaled-1-600x800-1.jpeg'
);
check(
	'the old staging domain is rehomed too',
	AM_VB_Media::normalise_src('https://alexandra.krildigital.com/wp-content/uploads/2026/07/a.webp'),
	$site . '/wp-content/uploads/2026/07/a.webp'
);
check(
	'localhost with a port is rehomed',
	AM_VB_Media::normalise_src('http://localhost:10005/wp-content/uploads/2026/01/b.png'),
	$site . '/wp-content/uploads/2026/01/b.png'
);

echo "\nHostile and unusable input is refused\n";

check('javascript: is refused', AM_VB_Media::normalise_src('javascript:alert(1)'), '');
check('data:text/html is refused', AM_VB_Media::normalise_src('data:text/html;base64,PHNjcmlwdD4='), '');
check('data:image is refused too', AM_VB_Media::normalise_src('data:image/svg+xml;base64,PHN2Zz4='), '');
check('file: is refused', AM_VB_Media::normalise_src('file:///etc/passwd'), '');
check('ftp: is refused', AM_VB_Media::normalise_src('ftp://example.com/a.jpg'), '');
check(
	'hotlinking a foreign host is refused',
	AM_VB_Media::normalise_src('https://evil.example.com/tracker.gif'),
	''
);
check(
	'a foreign non-uploads path is refused',
	AM_VB_Media::normalise_src('https://evil.example.com/wp-content/themes/x/a.jpg'),
	''
);
check(
	'a protocol-relative foreign host is refused, not silently trusted',
	AM_VB_Media::normalise_src('//evil.example.com/a.jpg'),
	''
);
check(
	'a protocol-relative uploads path is rehomed',
	AM_VB_Media::normalise_src('//alexandra-montessori.local/wp-content/uploads/2026/08/c.jpg'),
	$site . '/wp-content/uploads/2026/08/c.jpg'
);

echo "\nattachment_id\n";

check(
	'an exact attachment URL resolves',
	AM_VB_Media::attachment_id($site . '/wp-content/uploads/2026/08/about-us-children.webp'),
	131
);
check(
	'a resized URL resolves back to its original',
	AM_VB_Media::attachment_id($site . '/wp-content/uploads/2026/08/about-us-children-300x300.webp'),
	131
);
check('an unknown URL resolves to 0', AM_VB_Media::attachment_id($site . '/wp-content/uploads/nope.webp'), 0);
check('empty resolves to 0', AM_VB_Media::attachment_id(''), 0);

echo "\nsanitize_pair - what gets stored\n";

check(
	'a local URL is rehomed and its ID recovered',
	AM_VB_Media::sanitize_pair(array('src' => 'http://alexandra-montessori.local/wp-content/uploads/2026/08/IMG_1998-scaled-1-600x800-1.jpeg')),
	array('src' => $site . '/wp-content/uploads/2026/08/IMG_1998-scaled-1-600x800-1.jpeg', 'srcId' => 188)
);
check(
	'a supplied ID is kept',
	AM_VB_Media::sanitize_pair(array('src' => $site . '/wp-content/uploads/2026/08/about-us-children.webp', 'srcId' => 131)),
	array('src' => $site . '/wp-content/uploads/2026/08/about-us-children.webp', 'srcId' => 131)
);
check(
	'an ID that is not an attachment here is discarded',
	AM_VB_Media::sanitize_pair(array('src' => '/wp-content/uploads/x.webp', 'srcId' => 99999)),
	array('src' => '/wp-content/uploads/x.webp', 'srcId' => 0)
);
check(
	'an ID alone still yields a URL',
	AM_VB_Media::sanitize_pair(array('srcId' => 131)),
	array('src' => $site . '/wp-content/uploads/2026/08/about-us-children.webp', 'srcId' => 131)
);
check(
	'a refused URL with a good ID still renders',
	AM_VB_Media::sanitize_pair(array('src' => 'https://evil.example.com/a.gif', 'srcId' => 131)),
	array('src' => $site . '/wp-content/uploads/2026/08/about-us-children.webp', 'srcId' => 131)
);
check(
	'nothing usable stores nothing',
	AM_VB_Media::sanitize_pair(array('src' => 'javascript:alert(1)')),
	array('src' => '', 'srcId' => 0)
);

echo "\nresolve - the ID wins so designs are portable\n";

check(
	'the ID beats a stale URL from another domain',
	AM_VB_Media::resolve('http://alexandra-montessori.local/wp-content/uploads/2026/08/old.jpg', 188),
	$site . '/wp-content/uploads/2026/08/IMG_1998-scaled-1-600x800-1.jpeg'
);
check(
	'without an ID the URL is still normalised',
	AM_VB_Media::resolve('http://alexandra-montessori.local/wp-content/uploads/2026/08/z.jpg', 0),
	$site . '/wp-content/uploads/2026/08/z.jpg'
);
check(
	'a dangling ID falls back to the URL',
	AM_VB_Media::resolve('/wp-content/uploads/2026/08/z.jpg', 4242),
	'/wp-content/uploads/2026/08/z.jpg'
);

echo "\nresolve_design - the whole tree, at any depth\n";

$design = array(
	'elements' => array(
		'about-image' => array(
			'type'  => 'image',
			'src'   => 'http://alexandra-montessori.local/wp-content/uploads/2026/08/old.jpg',
			'srcId' => 188,
		),
		'hero-poster' => array(
			'type' => 'image',
			'src'  => 'http://alexandra-montessori.local/wp-content/uploads/2026/08/poster.webp',
		),
		'heading' => array('type' => 'text', 'value' => 'no media here'),
	),
	'customSections' => array(
		array('layers' => array(array('type' => 'image', 'src' => '//alexandra-montessori.local/wp-content/uploads/2026/08/deep.png'))),
	),
);
$resolved = AM_VB_Media::resolve_design($design);

check(
	'a nested element with an ID resolves through the ID',
	$resolved['elements']['about-image']['src'],
	$site . '/wp-content/uploads/2026/08/IMG_1998-scaled-1-600x800-1.jpeg'
);
check(
	'a nested attachment also exposes its responsive candidates',
	$resolved['elements']['about-image']['srcSet'],
	$GLOBALS['am_srcsets'][188]
);
check(
	'a nested element without an ID is still rehomed',
	$resolved['elements']['hero-poster']['src'],
	$site . '/wp-content/uploads/2026/08/poster.webp'
);
check(
	'media nested inside a custom section is reached',
	$resolved['customSections'][0]['layers'][0]['src'],
	$site . '/wp-content/uploads/2026/08/deep.png'
);
check('a text element is untouched', $resolved['elements']['heading']['value'], 'no media here');

echo "\naudit_design - what a repair pass would report\n";

$problems = AM_VB_Media::audit_design(array(
	'elements' => array(
		'good'    => array('src' => $site . '/wp-content/uploads/2026/08/about-us-children.webp', 'srcId' => 131),
		'foreign' => array('src' => 'https://evil.example.com/a.gif'),
		'text'    => array('value' => 'nothing'),
	),
));
check('exactly one problem is reported', count($problems), 1);
check('the foreign host is the one flagged', $problems[0]['path'], 'elements/foreign');
check('and it is described as foreign', $problems[0]['issue'], 'points at a foreign host (evil.example.com)');

echo "\n" . str_repeat('=', 60) . "\n";
echo "{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
