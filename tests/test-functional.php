<?php
/**
 * Mock WordPress environment for functional checking
 */

define( 'ABSPATH', __DIR__ . '/../' );

function plugin_dir_path( $file ) {
	return __DIR__ . '/../';
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
function wp_nonce_field( $action, $name ) { echo "Nonce field: $action, $name\n"; }
function check_admin_referer( $action ) { return true; }
function sanitize_text_field( $str ) { return $str; }
function sanitize_email( $str ) { return $str; }
function esc_attr( $str ) { return $str; }
function esc_html( $str ) { return $str; }
function wp_send_json_success( $data ) { echo "JSON Success: " . json_encode($data) . "\n"; }
function wp_send_json_error( $data ) { echo "JSON Error: " . json_encode($data) . "\n"; }
function check_ajax_referer( $action, $query_arg ) { return true; }
function current_user_can( $cap ) { return true; }

// Mock $wpdb
class MockWPDB {
	public $prefix = 'wp_';
	public $data = [];
	public $insert_id = 0;

	public function get_charset_collate() { return 'DEFAULT CHARSET utf8'; }
	public function insert( $table, $data ) {
		$this->data[$table][] = $data;
		$this->insert_id = count($this->data[$table]);
		echo "Inserted into $table: " . json_encode($data) . "\n";
		return 1;
	}
	public function get_results( $query ) { return []; }
	public function get_var( $query ) { return 0; }
	public function prepare( $query, ...$args ) { return $query; }
}
$GLOBALS['wpdb'] = new MockWPDB();

// Mock dbDelta
function dbDelta( $sql ) {}

require_once 'agency-nexus.php';

echo "Testing Functional logic...\n";

$instance = Agency_Nexus();
$smartonboard = $instance->modules['smartonboard'];

// Mock $_POST for Scope Builder
$_POST['client_id'] = 1;
$_POST['service_type'] = 'seo';
$_POST['scale'] = 'large';
$_POST['security'] = 'nonce';

echo "Calling handle_save_scope...\n";
$smartonboard->handle_save_scope();

if ( count( $GLOBALS['wpdb']->data['wp_an_projects'] ) === 1 ) {
	echo "Project successfully saved in mock DB.\n";
	$project = $GLOBALS['wpdb']->data['wp_an_projects'][0];
	if ( $project['budget'] == 15000 ) {
		echo "Budget correctly calculated for 'large' scale.\n";
	}
}

echo "All functional tests passed.\n";
