<?php
/**
 * Mock WordPress environment for integration checking
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
function current_time( $type ) { return date('Y-m-d H:i:s'); }
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

echo "Testing Integration logic...\n";

$instance = Agency_Nexus();
$instance->init_plugin(); // Trigger module loading
$dashboard = Agency_Nexus_Admin_Dashboard::get_instance();

// 1. Create a client
echo "\nStep 1: Adding a client...\n";
$_POST['an_add_client'] = true;
$_POST['name'] = 'John Doe';
$_POST['email'] = 'john@example.com';
$_POST['company'] = 'Doe Inc';
$dashboard->render_clients();

// 2. Create a project
echo "\nStep 2: Creating a project...\n";
$_POST['an_add_project'] = true;
$_POST['client_id'] = 1;
$_POST['title'] = 'Website Overhaul';
$_POST['budget'] = 5000;
$_POST['description'] = 'Full site redesign';
$dashboard->render_projects();

// 3. Add a task
echo "\nStep 3: Adding a task...\n";
$_POST['an_add_task'] = true;
$_POST['project_id'] = 1;
$_POST['title'] = 'Design Mockups';
$dashboard->render_projects();

// 4. Log time
echo "\nStep 4: Logging time...\n";
$_POST['an_log_time'] = true;
$_POST['task_id'] = 1;
$_POST['hours'] = 5;
$_POST['note'] = 'Worked on Figma';
$dashboard->render_projects();

// 5. Create content
echo "\nStep 5: Creating content...\n";
$contentmatrix = $instance->modules['contentmatrix'];
$_POST['project_id'] = 1;
$_POST['title'] = 'Announcing Site Launch';
$_POST['security'] = 'nonce';
$contentmatrix->handle_create_content();

// 6. Approve content
echo "\nStep 6: Approving content...\n";
$approvalflow = $instance->modules['approvalflow'];
$_POST['item_id'] = 1;
$approvalflow->handle_approve_content();

echo "\nIntegration test complete.\n";
