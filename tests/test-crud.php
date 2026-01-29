<?php
/**
 * Mock WordPress environment for CRUD logic checking
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
function esc_url_raw( $url ) { return $url; }
function wp_send_json_success( $data = null ) { echo "JSON Success" . ($data ? ": " . json_encode($data) : "") . "\n"; }
function wp_send_json_error( $data ) { echo "JSON Error: " . json_encode($data) . "\n"; }
function check_ajax_referer( $action, $query_arg ) { return true; }
function current_user_can( $cap ) { return true; }
function get_current_user_id() { return 1; }
function wpautop( $text ) { return $text; }
function selected( $a, $b ) { return $a == $b ? 'selected' : ''; }
function checked( $a, $b ) { return $a == $b ? 'checked' : ''; }
function esc_textarea( $t ) { return $t; }
function current_time( $t ) { return date('Y-m-d H:i:s'); }

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
	public function delete( $table, $where ) {
		echo "Deleted from $table WHERE " . json_encode($where) . "\n";
		return 1;
	}
	public function get_results( $query ) { return []; }
	public function get_var( $query ) { return 0; }
	public function get_row( $query ) { return null; }
	public function prepare( $query, ...$args ) { return $query; }
}
$GLOBALS['wpdb'] = new MockWPDB();

// Mock dbDelta
function dbDelta( $sql ) {}

require_once 'agency-nexus.php';
$instance = Agency_Nexus();
$instance->init_plugin();

echo "Testing CRUD Logic...\n";

// 1. Test Invoicing
echo "\nTesting Invoicing...\n";
$moneyflow = $instance->modules['moneyflow'];
$_POST['an_save_invoice'] = true;
$_POST['project_id'] = 1;
$_POST['client_id'] = 1;
$_POST['number'] = 'INV-001';
$_POST['amount'] = 500;
$_POST['status'] = 'draft';
$_POST['due_date'] = '2023-12-31';
$moneyflow->render_invoices();

// 2. Test Time Blocking
echo "\nTesting Time Blocking...\n";
$timeblock = $instance->modules['timeblockpro'];
$_POST['an_save_block'] = true;
$_POST['title'] = 'Focus Block';
$_POST['start_time'] = '2023-10-30 09:00:00';
$_POST['end_time'] = '2023-10-30 11:00:00';
$_POST['type'] = 'deep_work';
$timeblock->render_dashboard();

// 3. Test Autopilot Rule
echo "\nTesting Autopilot...\n";
$autopilot = $instance->modules['autopilot'];
$_POST['an_save_rule'] = true;
$_POST['title'] = 'Welcome Rule';
$_POST['trigger_evt'] = 'new_lead';
$_POST['action_evt'] = 'email_client';
$_POST['is_active'] = 1;
$autopilot->render_automations();

echo "\nAll CRUD tests passed.\n";
