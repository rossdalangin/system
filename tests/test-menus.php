<?php
/**
 * Mock WordPress environment for menu hierarchy checking
 */

define( 'ABSPATH', __DIR__ . '/../' );

function plugin_dir_path( $file ) {
	return __DIR__ . '/../';
}

function plugin_dir_url( $file ) {
	return 'http://example.com/wp-content/plugins/agency-nexus/';
}

$hooks = [];
function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
	global $hooks;
	$hooks[$tag][] = [
		'callback' => $callback,
		'priority' => $priority
	];
	// Sort by priority
	usort($hooks[$tag], function($a, $b) {
		return $a['priority'] - $b['priority'];
	});
}

function register_activation_hook( $file, $callback ) {}

$menu = [];
function add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function = '', $icon_url = '', $position = null ) {
	global $menu;
	$menu[$menu_slug] = [
		'page_title' => $page_title,
		'menu_title' => $menu_title,
		'capability' => $capability,
		'menu_slug'  => $menu_slug
	];
	echo "Registered Menu: $menu_slug\n";
}

$submenu = [];
function add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function = '', $position = null ) {
	global $menu, $submenu;
	if (!isset($menu[$parent_slug])) {
		echo "WARNING: Parent menu '$parent_slug' NOT found when registering submenu '$menu_slug'!\n";
	} else {
		echo "Registered Submenu: $menu_slug under $parent_slug\n";
	}
	$submenu[$parent_slug][] = [
		'page_title' => $page_title,
		'menu_title' => $menu_title,
		'capability' => $capability,
		'menu_slug'  => $menu_slug
	];
}

function __return_true() { return true; }
function register_rest_route( $namespace, $route, $args = [], $override = false ) {}
function __( $text, $domain ) { return $text; }
function _e( $text, $domain ) { echo $text; }
function is_admin() { return true; }
function admin_url( $path ) { return 'http://example.com/wp-admin/' . $path; }
function get_current_user_id() { return 1; }

// Mock $wpdb
class MockWPDB {
	public $prefix = 'wp_';
	public function get_charset_collate() { return 'DEFAULT CHARSET utf8'; }
}
$GLOBALS['wpdb'] = new MockWPDB();

require_once 'agency-nexus.php';

echo "Simulating Plugin Load...\n";
$instance = Agency_Nexus();

echo "Simulating plugins_loaded hook...\n";
foreach ($hooks['plugins_loaded'] as $hook) {
	call_user_func($hook['callback']);
}

echo "Simulating admin_menu hook...\n";
if (isset($hooks['admin_menu'])) {
	foreach ($hooks['admin_menu'] as $hook) {
		echo "Executing hook at priority {$hook['priority']}\n";
		call_user_func($hook['callback']);
	}
}

echo "\nMenu Registration Summary:\n";
foreach ($menu as $slug => $data) {
	echo "- Parent: $slug\n";
	if (isset($submenu[$slug])) {
		foreach ($submenu[$slug] as $sub) {
			echo "  - Submenu: {$sub['menu_slug']}\n";
		}
	}
}

// Verification
$expected_submenus = [
	'agency-nexus',
	'an-clients',
	'an-projects',
	'an-approvals',
	'an-automations',
	'an-messages',
	'an-content-calendar',
	'an-resources',
	'an-scope-builder',
	'an-time-blocking'
];

$registered_submenus = [];
if (isset($submenu['agency-nexus'])) {
	foreach ($submenu['agency-nexus'] as $sub) {
		$registered_submenus[] = $sub['menu_slug'];
	}
}

$missing = array_diff($expected_submenus, $registered_submenus);

if (empty($missing)) {
	echo "\nSUCCESS: All expected submenus registered correctly.\n";
} else {
	echo "\nFAILURE: Missing submenus: " . implode(', ', $missing) . "\n";
	exit(1);
}
