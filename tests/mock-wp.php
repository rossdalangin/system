<?php
/**
 * Mock WordPress environment for testing
 */

if (!defined('ABSPATH')) {
    define( 'ABSPATH', __DIR__ . '/../' );
}

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
	usort($hooks[$tag], function($a, $b) {
		return $a['priority'] - $b['priority'];
	});
}

function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {}
function register_activation_hook( $file, $callback ) {}
function wp_die($msg) { die($msg); }

function add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function = '', $icon_url = '', $position = null ) {}
function add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function = '', $position = null ) {}

function __return_true() { return true; }
function register_rest_route( $namespace, $route, $args = [], $override = false ) {}
function __( $text, $domain = '' ) { return $text; }
function _e( $text, $domain = '' ) { echo $text; }
function esc_html( $text ) { return htmlspecialchars($text); }
function esc_attr( $text ) { return htmlspecialchars($text); }
function esc_textarea( $text ) { return htmlspecialchars($text); }
function sanitize_text_field( $text ) { return trim($text); }
function sanitize_email( $email ) { return trim($email); }
function sanitize_textarea_field( $text ) { return trim($text); }

function is_admin() { return true; }
function admin_url( $path = '' ) { return 'http://example.com/wp-admin/' . $path; }
function get_current_user_id() { return 1; }
function check_admin_referer($action, $query_arg = '_wpnonce') { return true; }
function wp_nonce_field($action, $name = '_wpnonce', $referer = true, $echo = true) { return ''; }
function wp_nonce_url($url, $action) { return $url; }
function selected($a, $b, $echo = true) { return $a == $b ? ' selected' : ''; }
function current_time($type) { return date('Y-m-d H:i:s'); }
function get_userdata($id) {
    $user = new stdClass();
    $user->display_name = "Mock User $id";
    $user->user_email = "mock$id@example.com";
    $user->roles = ['administrator'];
    return $user;
}
function get_users() {
    return [get_userdata(1)];
}

class MockWPDB {
	public $prefix = 'wp_';
    public $last_query;
    public $rows = [];
    public $insert_id = 0;

	public function get_charset_collate() { return 'DEFAULT CHARSET utf8'; }
    public function prepare($query, ...$args) {
        return vsprintf(str_replace('%d', '%s', str_replace('%s', "'%s'", $query)), $args);
    }
    public function get_var($query) {
        $this->last_query = $query;
        if (strpos($query, 'SUM(duration)') !== false) return 3600;
        if (strpos($query, 'COUNT(*)') !== false) return 1;
        return 1;
    }
    public function get_results($query) {
        $this->last_query = $query;
        if (strpos($query, 'an_tasks') !== false) {
            $task = new stdClass();
            $task->id = 1;
            $task->title = "Test Task 1";
            $task->project_id = 1;
            $task->status = "todo";
            return [$task];
        }
        return [];
    }
    public function get_row($query) {
        $this->last_query = $query;
        return null;
    }
    public function insert($table, $data) { $this->insert_id++; return true; }
    public function update($table, $data, $where) { return true; }
    public function delete($table, $where) { return true; }
}
$GLOBALS['wpdb'] = new MockWPDB();
