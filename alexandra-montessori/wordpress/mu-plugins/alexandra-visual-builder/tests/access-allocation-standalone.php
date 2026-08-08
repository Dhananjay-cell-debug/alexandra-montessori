<?php
/**
 * Standalone contract for per-page Visual Builder allocation.
 *
 * Runs without a database, exactly like the other suites here:
 *   php tests/access-allocation-standalone.php
 *
 * What it pins down is the part that must never regress quietly: an Editor
 * sees only the pages an Administrator ticked, an Administrator is never
 * scoped, and an account with no explicit allocation gets nothing at all.
 */

define('ABSPATH', __DIR__);

// ---------------------------------------------------------------------------
// Minimal WordPress surface used by AM_VB_Access.
// ---------------------------------------------------------------------------
$GLOBALS['am_test_users']     = array();
$GLOBALS['am_test_user_meta'] = array();
$GLOBALS['am_test_current']   = 0;

class WP_User {
	public $ID;
	public $roles = array();
	public $caps  = array();

	public function __construct($id = 0, $roles = array(), $caps = array()) {
		$this->ID    = $id;
		$this->roles = $roles;
		$this->caps  = $caps;
	}

	public function exists() {
		return $this->ID > 0;
	}
}

class WP_Error {
	public $code;
	public $message;
	public $data;

	public function __construct($code = '', $message = '', $data = array()) {
		$this->code    = $code;
		$this->message = $message;
		$this->data    = $data;
	}
}

function is_wp_error($thing) {
	return $thing instanceof WP_Error;
}

function sanitize_key($key) {
	return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $key));
}

function get_userdata($id) {
	return $GLOBALS['am_test_users'][$id] ?? false;
}

function wp_get_current_user() {
	return $GLOBALS['am_test_users'][$GLOBALS['am_test_current']] ?? new WP_User(0);
}

function get_current_user_id() {
	return $GLOBALS['am_test_current'];
}

function user_can($user, $cap) {
	$user = $user instanceof WP_User ? $user : get_userdata($user);
	if (!$user || !$user->exists()) {
		return false;
	}
	if (in_array('administrator', $user->roles, true)) {
		return true;
	}
	return !empty($user->caps[$cap]);
}

function get_user_meta($user_id, $key, $single = false) {
	return $GLOBALS['am_test_user_meta'][$user_id][$key] ?? '';
}

function update_user_meta($user_id, $key, $value) {
	$GLOBALS['am_test_user_meta'][$user_id][$key] = $value;
	return true;
}

function get_post_meta($post_id, $key, $single = false) {
	return $GLOBALS['am_test_post_meta'][$post_id][$key] ?? '';
}

function translate_user_role($name) {
	return $name;
}

function wp_roles() {
	return new class {
		public function get_names() {
			return array(
				'administrator'      => 'Administrator',
				'am_content_manager' => 'Editor',
				'am_viewer'          => 'Viewer',
			);
		}
	};
}

function __($text, $domain = '') { return $text; }
function esc_html__($text, $domain = '') { return $text; }
function esc_html_e($text, $domain = '') { echo $text; }
function esc_attr($text) { return $text; }
function esc_html($text) { return $text; }
function add_action() {}
function add_filter() {}
function current_user_can($cap) { return user_can(wp_get_current_user(), $cap); }

// ---------------------------------------------------------------------------
// Collaborator doubles. Only the members AM_VB_Access actually touches.
// ---------------------------------------------------------------------------
class AM_VB_REST {
	const NAMESPACE = 'am-visual-builder/v1';
}

class AM_VB_Document {
	const HOME_SLUG = 'home-poc';
}

class AM_VB_Saved_Sessions {
	const META_SLUG = '_am_vb_session_slug';
}

class AM_VB_Site_Design {
	public static function registry() {
		return array(
			'home-poc'             => array('title' => 'Home page'),
			'nurseries'            => array('title' => 'Our Nurseries'),
			'curriculum'           => array('title' => 'Our Curriculum'),
			'blogs'                => array('title' => 'Blog'),
			'fees'                 => array('title' => 'Fees'),
			'careers-apply'        => array('title' => 'Apply'),
			'blog-detail-template' => array('title' => 'Blog article template'),
		);
	}
}

class AM_VB_Organizer {
	public static function get() {
		return array(
			// Deliberately not registry order, to prove the rail order wins.
			'order'  => array('home-poc', 'fees', 'blogs', 'nurseries', 'curriculum'),
			'labels' => array('home-poc' => 'Home section'),
			// The two pages the client hid must not be allocatable at all.
			'hidden' => array('careers-apply', 'blog-detail-template'),
		);
	}
}

require_once dirname(__DIR__) . '/includes/class-am-vb-access.php';

// ---------------------------------------------------------------------------
// Harness.
// ---------------------------------------------------------------------------
$passed = 0;
$failed = 0;

function check($label, $actual, $expected) {
	global $passed, $failed;
	$ok = $actual === $expected;
	if ($ok) {
		$passed++;
		echo "  PASS  {$label}\n";
		return;
	}
	$failed++;
	echo "  FAIL  {$label}\n";
	echo "        expected: " . json_encode($expected) . "\n";
	echo "        actual:   " . json_encode($actual) . "\n";
}

function as_user($id) {
	$GLOBALS['am_test_current'] = $id;
}

$admin  = new WP_User(1, array('administrator'));
$editor = new WP_User(2, array('am_content_manager'), array('edit_am_visual_pages' => true));
$fresh  = new WP_User(3, array('am_content_manager'), array('edit_am_visual_pages' => true));
$viewer = new WP_User(4, array('am_viewer'));

$GLOBALS['am_test_users'] = array(1 => $admin, 2 => $editor, 3 => $fresh, 4 => $viewer);

echo "\nPer-page allocation\n";

check(
	'allocatable list follows the rail order and drops hidden pages',
	array_keys(AM_VB_Access::allocatable_pages()),
	array('home-poc', 'fees', 'blogs', 'nurseries', 'curriculum')
);

check(
	'allocatable list uses the client\'s renamed label',
	AM_VB_Access::allocatable_pages()['home-poc'],
	'Home section'
);

as_user(1);
check('administrator is unrestricted', AM_VB_Access::is_unrestricted(), true);
check(
	'administrator gets every page, hidden ones included',
	AM_VB_Access::allowed_pages(),
	array('home-poc', 'nurseries', 'curriculum', 'blogs', 'fees', 'careers-apply', 'blog-detail-template')
);
check('administrator can edit a hidden page', AM_VB_Access::can_edit_page('careers-apply'), true);
check('administrator role label', AM_VB_Access::role_label(), 'Administrator');

as_user(3);
check('editor with no allocation is unrestricted? no', AM_VB_Access::is_unrestricted(), false);
check('editor with no allocation gets nothing (fail-closed)', AM_VB_Access::allowed_pages(), array());
check('editor with no allocation cannot use the builder', AM_VB_Access::can_use_builder(), false);
check('editor with no allocation cannot edit Home', AM_VB_Access::can_edit_page('home-poc'), false);

update_user_meta(2, AM_VB_Access::USER_META, array('blogs', 'fees'));
as_user(2);
check('allocated editor gets exactly the ticked pages', AM_VB_Access::allowed_pages(), array('blogs', 'fees'));
check('allocated editor can use the builder', AM_VB_Access::can_use_builder(), true);
check('allocated editor can edit an allocated page', AM_VB_Access::can_edit_page('blogs'), true);
check('allocated editor cannot edit an unallocated page', AM_VB_Access::can_edit_page('curriculum'), false);
check('allocated editor cannot edit Home', AM_VB_Access::can_edit_page('home-poc'), false);
check('editor role label', AM_VB_Access::role_label(), 'Editor');
check('allocated editor labels resolve', AM_VB_Access::allowed_page_labels(), array('Blog', 'Fees'));

echo "\nHostile allocation input\n";

update_user_meta(2, AM_VB_Access::USER_META, array('blogs', 'careers-apply'));
check(
	'a hidden page saved into the meta is refused',
	AM_VB_Access::allowed_pages(),
	array('blogs')
);

update_user_meta(2, AM_VB_Access::USER_META, array('blogs', 'not-a-real-page', '', '../../etc/passwd'));
check(
	'unknown and traversal slugs are refused',
	AM_VB_Access::allowed_pages(),
	array('blogs')
);

update_user_meta(2, AM_VB_Access::USER_META, array('blogs', 'blogs', 'blogs'));
check('duplicates collapse', AM_VB_Access::allowed_pages(), array('blogs'));

update_user_meta(2, AM_VB_Access::USER_META, array());
check('an empty tick list revokes access', AM_VB_Access::allowed_pages(), array());
check('an empty tick list closes the builder', AM_VB_Access::can_use_builder(), false);

update_user_meta(2, AM_VB_Access::USER_META, 'not-an-array');
check('a corrupt meta value fails closed', AM_VB_Access::allowed_pages(), array());

as_user(4);
check('a viewer without the capability gets nothing', AM_VB_Access::allowed_pages(), array());
check('a viewer cannot use the builder', AM_VB_Access::can_use_builder(), false);

echo "\nRoute to page resolution\n";

$base = '/' . AM_VB_REST::NAMESPACE;
$routes = array(
	$base . '/page-design/curriculum'                 => 'curriculum',
	$base . '/page/blogs'                             => 'blogs',
	$base . '/page/blogs/lock'                        => 'blogs',
	$base . '/page/blogs/status'                      => 'blogs',
	$base . '/page/blogs/revisions'                   => 'blogs',
	$base . '/page/blogs/revisions/42/restore'        => 'blogs',
	$base . '/page/blogs/audit'                       => 'blogs',
	$base . '/public/page/fees'                       => 'fees',
	$base . '/home-design'                            => 'home-poc',
	$base . '/pages'                                  => '',
	$base . '/templates'                              => '',
	$base . '/media'                                  => '',
	$base . '/organizer'                              => '',
	$base . '/saved-sessions'                         => '',
	'/wp/v2/posts'                                    => '',
	'/alexandra/v1/submissions'                       => '',
);
foreach ($routes as $route => $expected) {
	check("route_slug({$route})", AM_VB_Access::route_slug($route), $expected);
}

echo "\nREST guard\n";

update_user_meta(2, AM_VB_Access::USER_META, array('blogs'));
as_user(2);

check(
	'allocated page passes the guard',
	AM_VB_Access::guard_rest(null, null, new class($base . '/page-design/blogs', 'POST') {
		private $route;
		private $method;
		public function __construct($route, $method) { $this->route = $route; $this->method = $method; }
		public function get_route() { return $this->route; }
		public function get_method() { return $this->method; }
	}),
	null
);

$denied = AM_VB_Access::guard_rest(null, null, new class($base . '/page-design/curriculum', 'POST') {
	private $route;
	private $method;
	public function __construct($route, $method) { $this->route = $route; $this->method = $method; }
	public function get_route() { return $this->route; }
	public function get_method() { return $this->method; }
});
check('unallocated page is refused by the guard', is_wp_error($denied), true);
check('refusal is a 403', $denied->data['status'] ?? 0, 403);

$home = AM_VB_Access::guard_rest(null, null, new class($base . '/home-design', 'POST') {
	private $route;
	private $method;
	public function __construct($route, $method) { $this->route = $route; $this->method = $method; }
	public function get_route() { return $this->route; }
	public function get_method() { return $this->method; }
});
check('an editor without Home cannot save the home design', is_wp_error($home), true);

$organizer = AM_VB_Access::guard_rest(null, null, new class($base . '/organizer', 'POST') {
	private $route;
	private $method;
	public function __construct($route, $method) { $this->route = $route; $this->method = $method; }
	public function get_route() { return $this->route; }
	public function get_method() { return $this->method; }
});
check('rearranging the page list stays an administrator action', is_wp_error($organizer), true);

$GLOBALS['am_test_post_meta'] = array(
	77 => array('_am_vb_session_slug' => 'curriculum'),
	78 => array('_am_vb_session_slug' => 'blogs'),
);
$session_denied = AM_VB_Access::guard_rest(null, null, new class($base . '/saved-sessions/77/restore', 'POST') {
	private $route;
	private $method;
	public function __construct($route, $method) { $this->route = $route; $this->method = $method; }
	public function get_route() { return $this->route; }
	public function get_method() { return $this->method; }
});
check('restoring another page\'s saved session is refused', is_wp_error($session_denied), true);

$session_ok = AM_VB_Access::guard_rest(null, null, new class($base . '/saved-sessions/78/restore', 'POST') {
	private $route;
	private $method;
	public function __construct($route, $method) { $this->route = $route; $this->method = $method; }
	public function get_route() { return $this->route; }
	public function get_method() { return $this->method; }
});
check('restoring an allocated page\'s saved session passes', $session_ok, null);

as_user(1);
$admin_organizer = AM_VB_Access::guard_rest(null, null, new class($base . '/organizer', 'POST') {
	private $route;
	private $method;
	public function __construct($route, $method) { $this->route = $route; $this->method = $method; }
	public function get_route() { return $this->route; }
	public function get_method() { return $this->method; }
});
check('an administrator may rearrange the page list', $admin_organizer, null);

as_user(3);
$unallocated = AM_VB_Access::guard_rest(null, null, new class($base . '/pages', 'GET') {
	private $route;
	private $method;
	public function __construct($route, $method) { $this->route = $route; $this->method = $method; }
	public function get_route() { return $this->route; }
	public function get_method() { return $this->method; }
});
check('an unallocated editor is refused even on a listing route', is_wp_error($unallocated), true);

$public = AM_VB_Access::guard_rest(null, null, new class($base . '/public/page/fees', 'GET') {
	private $route;
	private $method;
	public function __construct($route, $method) { $this->route = $route; $this->method = $method; }
	public function get_route() { return $this->route; }
	public function get_method() { return $this->method; }
});
check('the public read route stays open for the live site', $public, null);

$foreign = AM_VB_Access::guard_rest(null, null, new class('/wp/v2/posts', 'POST') {
	private $route;
	private $method;
	public function __construct($route, $method) { $this->route = $route; $this->method = $method; }
	public function get_route() { return $this->route; }
	public function get_method() { return $this->method; }
});
check('routes outside the builder are left to their own guards', $foreign, null);

echo "\n" . str_repeat('=', 60) . "\n";
echo "{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
