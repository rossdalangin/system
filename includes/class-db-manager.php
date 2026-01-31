<?php
/**
 * Database Manager Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Nexus_DB_Manager {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		// Ensure tables exist on every load during development
		if ( is_admin() ) {
			$this->maybe_create_tables();
		}
	}

	/**
	 * Check if a core table exists, if not, run creation.
	 */
	private function maybe_create_tables() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'an_clients';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
			self::create_tables();
		}
	}

	/**
	 * Create custom tables on plugin activation.
	 * Follows WordPress dbDelta requirements strictly.
	 */
	public static function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$queries = [];

		// Clients Table
		$table_clients = $wpdb->prefix . 'an_clients';
		$queries[] = "CREATE TABLE $table_clients (
id bigint(20) NOT NULL AUTO_INCREMENT,
name varchar(255) NOT NULL,
email varchar(255) NOT NULL,
company varchar(255) DEFAULT '' NOT NULL,
status varchar(50) DEFAULT 'active' NOT NULL,
created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
PRIMARY KEY  (id)
) $charset_collate;";

		// Projects Table
		$table_projects = $wpdb->prefix . 'an_projects';
		$queries[] = "CREATE TABLE $table_projects (
id bigint(20) NOT NULL AUTO_INCREMENT,
client_id bigint(20) NOT NULL,
title varchar(255) NOT NULL,
description text NOT NULL,
budget decimal(10,2) DEFAULT 0.00 NOT NULL,
status varchar(50) DEFAULT 'planned' NOT NULL,
start_date date DEFAULT '0000-00-00',
end_date date DEFAULT '0000-00-00',
created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
PRIMARY KEY  (id)
) $charset_collate;";

		// Tasks Table
		$table_tasks = $wpdb->prefix . 'an_tasks';
		$queries[] = "CREATE TABLE $table_tasks (
id bigint(20) NOT NULL AUTO_INCREMENT,
project_id bigint(20) NOT NULL,
title varchar(255) NOT NULL,
description text NOT NULL,
assigned_to bigint(20) DEFAULT 0 NOT NULL,
priority varchar(20) DEFAULT 'medium' NOT NULL,
status varchar(50) DEFAULT 'todo' NOT NULL,
start_date date DEFAULT '0000-00-00',
due_date date DEFAULT '0000-00-00',
created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
PRIMARY KEY  (id)
) $charset_collate;";

		// Time Entries Table
		$table_time = $wpdb->prefix . 'an_time_entries';
		$queries[] = "CREATE TABLE $table_time (
id bigint(20) NOT NULL AUTO_INCREMENT,
task_id bigint(20) NOT NULL,
user_id bigint(20) NOT NULL,
duration int(11) NOT NULL,
note text NOT NULL,
date date DEFAULT '0000-00-00' NOT NULL,
created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
PRIMARY KEY  (id)
) $charset_collate;";

		// Content Table (ContentMatrix)
		$table_content = $wpdb->prefix . 'an_content';
		$queries[] = "CREATE TABLE $table_content (
id bigint(20) NOT NULL AUTO_INCREMENT,
project_id bigint(20) NOT NULL,
title varchar(255) NOT NULL,
content longtext NOT NULL,
media_url varchar(255) DEFAULT '' NOT NULL,
status varchar(50) DEFAULT 'draft' NOT NULL,
scheduled_date datetime DEFAULT '0000-00-00 00:00:00',
platform varchar(50) DEFAULT 'wordpress' NOT NULL,
created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
PRIMARY KEY  (id)
) $charset_collate;";

		// Time Blocks Table (TimeBlock Pro)
		$table_blocks = $wpdb->prefix . 'an_time_blocks';
		$queries[] = "CREATE TABLE $table_blocks (
id bigint(20) NOT NULL AUTO_INCREMENT,
user_id bigint(20) NOT NULL,
title varchar(255) NOT NULL,
start_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
end_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
type varchar(50) DEFAULT 'work' NOT NULL,
created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
PRIMARY KEY  (id)
) $charset_collate;";

		// Messages Table (ClientSync)
		$table_messages = $wpdb->prefix . 'an_messages';
		$queries[] = "CREATE TABLE $table_messages (
id bigint(20) NOT NULL AUTO_INCREMENT,
client_id bigint(20) NOT NULL,
sender_id bigint(20) NOT NULL,
message text NOT NULL,
is_read tinyint(1) DEFAULT 0 NOT NULL,
created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
PRIMARY KEY  (id)
) $charset_collate;";

		// Leads Table (EngageTrack)
		$table_leads = $wpdb->prefix . 'an_leads';
		$queries[] = "CREATE TABLE $table_leads (
id bigint(20) NOT NULL AUTO_INCREMENT,
name varchar(255) NOT NULL,
email varchar(255) NOT NULL,
source varchar(255) DEFAULT 'direct' NOT NULL,
status varchar(50) DEFAULT 'new' NOT NULL,
conversion_value decimal(10,2) DEFAULT 0.00 NOT NULL,
created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
PRIMARY KEY  (id)
) $charset_collate;";

		// Expenses Table (MoneyFlow)
		$table_expenses = $wpdb->prefix . 'an_expenses';
		$queries[] = "CREATE TABLE $table_expenses (
id bigint(20) NOT NULL AUTO_INCREMENT,
project_id bigint(20) DEFAULT 0 NOT NULL,
amount decimal(10,2) NOT NULL,
category varchar(255) DEFAULT 'general' NOT NULL,
note text NOT NULL,
receipt_url varchar(255) DEFAULT '' NOT NULL,
created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
PRIMARY KEY  (id)
) $charset_collate;";

		// Shared Files Table (ClientSync)
		$table_files = $wpdb->prefix . 'an_shared_files';
		$queries[] = "CREATE TABLE $table_files (
id bigint(20) NOT NULL AUTO_INCREMENT,
client_id bigint(20) NOT NULL,
user_id bigint(20) NOT NULL,
file_url varchar(255) NOT NULL,
file_name varchar(255) NOT NULL,
created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
PRIMARY KEY  (id)
) $charset_collate;";

		// Canned Responses Table (EngageTrack)
		$table_responses = $wpdb->prefix . 'an_canned_responses';
		$queries[] = "CREATE TABLE $table_responses (
id bigint(20) NOT NULL AUTO_INCREMENT,
title varchar(255) NOT NULL,
content text NOT NULL,
created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
PRIMARY KEY  (id)
) $charset_collate;";

		// Resources Table (FreebieFactory)
		$table_resources = $wpdb->prefix . 'an_resources';
		$queries[] = "CREATE TABLE $table_resources (
id bigint(20) NOT NULL AUTO_INCREMENT,
title varchar(255) NOT NULL,
type varchar(50) DEFAULT 'template' NOT NULL,
file_url varchar(255) DEFAULT '' NOT NULL,
content text NOT NULL,
created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
PRIMARY KEY  (id)
) $charset_collate;";

		// Burnout Logs Table (BurnoutGuard)
		$table_burnout = $wpdb->prefix . 'an_burnout_logs';
		$queries[] = "CREATE TABLE $table_burnout (
id bigint(20) NOT NULL AUTO_INCREMENT,
user_id bigint(20) NOT NULL,
stress_level int(11) NOT NULL,
note text NOT NULL,
created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
PRIMARY KEY  (id)
) $charset_collate;";

		// Invoices Table (MoneyFlow)
		$table_invoices = $wpdb->prefix . 'an_invoices';
		$queries[] = "CREATE TABLE $table_invoices (
id bigint(20) NOT NULL AUTO_INCREMENT,
project_id bigint(20) NOT NULL,
client_id bigint(20) NOT NULL,
number varchar(50) NOT NULL,
amount decimal(10,2) NOT NULL,
status varchar(50) DEFAULT 'draft' NOT NULL,
due_date date DEFAULT '0000-00-00' NOT NULL,
created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
PRIMARY KEY  (id)
) $charset_collate;";

		// Payments Table (MoneyFlow)
		$table_payments = $wpdb->prefix . 'an_payments';
		$queries[] = "CREATE TABLE $table_payments (
id bigint(20) NOT NULL AUTO_INCREMENT,
invoice_id bigint(20) NOT NULL,
amount decimal(10,2) NOT NULL,
method varchar(50) DEFAULT 'stripe' NOT NULL,
transaction_id varchar(255) DEFAULT '' NOT NULL,
created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
PRIMARY KEY  (id)
) $charset_collate;";

		// Autopilot Rules Table (AutoPilot)
		$table_rules = $wpdb->prefix . 'an_autopilot_rules';
		$queries[] = "CREATE TABLE $table_rules (
id bigint(20) NOT NULL AUTO_INCREMENT,
title varchar(255) NOT NULL,
trigger_evt varchar(100) NOT NULL,
action_evt varchar(100) NOT NULL,
is_active tinyint(1) DEFAULT 1 NOT NULL,
created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
PRIMARY KEY  (id)
) $charset_collate;";

		foreach ( $queries as $sql ) {
			dbDelta( $sql );
		}
	}
}
