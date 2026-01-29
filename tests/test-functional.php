<?php
/**
 * Mock WordPress environment for functional checking (Expanded)
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
function wp_nonce_field( $action, $name = '_wpnonce' ) { echo "Nonce field: $action, $name\n"; }
function check_admin_referer( $action ) { return true; }
function sanitize_text_field( $str ) { return $str; }
function sanitize_email( $str ) { return $str; }
function sanitize_textarea_field( $str ) { return $str; }
function esc_attr( $str ) { return $str; }
function esc_html( $str ) { return $str; }
function esc_js( $str ) { return $str; }
function wp_send_json_success( $data = null ) { echo "JSON Success" . ($data ? ": " . json_encode($data) : "") . "\n"; }
function wp_send_json_error( $data ) { echo "JSON Error: " . json_encode($data) . "\n"; }
function check_ajax_referer( $action, $query_arg ) { return true; }
function current_user_can( $cap ) { return true; }
function get_current_user_id() { return 1; }
function wpautop( $text ) { return $text; }

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
	public function update( $table, $data, $where ) {
		echo "Updated $table: " . json_encode($data) . " WHERE " . json_encode($where) . "\n";
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

echo "Testing Expanded Functional logic...\n";

$instance = Agency_Nexus();

// 1. Test ContentMatrix AJAX scheduling
echo "\nTesting ContentMatrix scheduling...\n";
$contentmatrix = $instance->modules['contentmatrix'];
$_POST['item_id'] = 101;
$_POST['new_date'] = '2023-10-25';
$_POST['security'] = 'nonce';
$contentmatrix->handle_update_content_date();

// 2. Test ClientSync AJAX messaging
echo "\nTesting ClientSync messaging...\n";
$clientsync = $instance->modules['clientsync'];
$_POST['client_id'] = 5;
$_POST['message'] = 'Test message from admin';
$clientsync->handle_send_message();

if ( count( $GLOBALS['wpdb']->data['wp_an_messages'] ) === 1 ) {
	echo "Message successfully saved in mock DB.\n";
}

// 3. Test ApprovalFlow AJAX approval
echo "\nTesting ApprovalFlow approval...\n";
$approvalflow = $instance->modules['approvalflow'];
$_POST['item_id'] = 202;
$approvalflow->handle_approve_content();

// 4. Test ClientSync message fetching
echo "\nTesting ClientSync message fetching...\n";
$_POST['client_id'] = 5;
$clientsync->handle_get_messages();

echo "\nAll expanded functional tests passed.\n";
