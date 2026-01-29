<?php
/**
 * Mock WordPress environment for syntax checking
 */

define( 'ABSPATH', __DIR__ . '/' );

function plugin_dir_path( $file ) {
	return __DIR__ . '/';
}

function plugin_dir_url( $file ) {
	return 'http://example.com/wp-content/plugins/agency-nexus/';
}

function register_activation_hook( $file, $callback ) {}
function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {}
function add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function = '', $icon_url = '', $position = null ) {}
function add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function = '', $position = null ) {}
function __return_true() { return true; }
function register_rest_route( $namespace, $route, $args = [], $override = false ) {}
function __( $text, $domain ) { return $text; }
function _e( $text, $domain ) { echo $text; }
function is_admin() { return true; }
function admin_url( $path ) { return 'http://example.com/wp-admin/' . $path; }

// Mock $wpdb
class MockWPDB {
	public $prefix = 'wp_';
	public function get_charset_collate() { return 'DEFAULT CHARSET utf8'; }
}
$GLOBALS['wpdb'] = new MockWPDB();

// Mock dbDelta
function dbDelta( $sql ) {
	echo "Mocking dbDelta for SQL: " . substr($sql, 0, 50) . "...\n";
}

echo "Testing Agency Nexus Syntax...\n";

try {
	require_once 'agency-nexus.php';
	echo "Main plugin file loaded successfully.\n";

	$instance = Agency_Nexus();
	if ( $instance instanceof Agency_Nexus ) {
		echo "Agency_Nexus instance created successfully.\n";
	}

	echo "Modules loaded: " . implode( ', ', array_keys( $instance->modules ) ) . "\n";

	foreach ( $instance->modules as $name => $module ) {
		if ( $module instanceof Agency_Nexus_Base_Module ) {
			echo "Module $name is valid.\n";
		}
	}

	echo "All tests passed (Syntax & Basic Structure).\n";
} catch ( Throwable $e ) {
	echo "Test failed: " . $e->getMessage() . "\n";
	echo "In file: " . $e->getFile() . " on line " . $e->getLine() . "\n";
	exit( 1 );
}
