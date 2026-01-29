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
		// Initialization if needed
	}

	/**
	 * Create custom tables on plugin activation.
	 */
	public static function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Clients Table
		$table_clients = $wpdb->prefix . 'an_clients';
		$sql_clients = "CREATE TABLE $table_clients (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			email varchar(255) NOT NULL,
			company varchar(255) DEFAULT '',
			status varchar(50) DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $charset_collate;";
		dbDelta( $sql_clients );

		// Projects Table
		$table_projects = $wpdb->prefix . 'an_projects';
		$sql_projects = "CREATE TABLE $table_projects (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			client_id bigint(20) NOT NULL,
			title varchar(255) NOT NULL,
			description text,
			budget decimal(10,2) DEFAULT 0.00,
			status varchar(50) DEFAULT 'planned',
			start_date date,
			end_date date,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $charset_collate;";
		dbDelta( $sql_projects );

		// Tasks Table
		$table_tasks = $wpdb->prefix . 'an_tasks';
		$sql_tasks = "CREATE TABLE $table_tasks (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			project_id bigint(20) NOT NULL,
			title varchar(255) NOT NULL,
			description text,
			assigned_to bigint(20),
			priority varchar(20) DEFAULT 'medium',
			status varchar(50) DEFAULT 'todo',
			due_date date,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $charset_collate;";
		dbDelta( $sql_tasks );

		// Time Entries Table
		$table_time = $wpdb->prefix . 'an_time_entries';
		$sql_time = "CREATE TABLE $table_time (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			task_id bigint(20) NOT NULL,
			user_id bigint(20) NOT NULL,
			duration int NOT NULL, -- in seconds
			note text,
			date date,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $charset_collate;";
		dbDelta( $sql_time );

		// Content Table (ContentMatrix)
		$table_content = $wpdb->prefix . 'an_content';
		$sql_content = "CREATE TABLE $table_content (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			project_id bigint(20) NOT NULL,
			title varchar(255) NOT NULL,
			content longtext,
			media_url varchar(255),
			status varchar(50) DEFAULT 'draft',
			scheduled_date datetime,
			platform varchar(50) DEFAULT 'wordpress',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $charset_collate;";
		dbDelta( $sql_content );

		// Time Blocks Table (TimeBlock Pro)
		$table_blocks = $wpdb->prefix . 'an_time_blocks';
		$sql_blocks = "CREATE TABLE $table_blocks (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			title varchar(255) NOT NULL,
			start_time datetime NOT NULL,
			end_time datetime NOT NULL,
			type varchar(50) DEFAULT 'work',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $charset_collate;";
		dbDelta( $sql_blocks );

		// Messages Table (ClientSync)
		$table_messages = $wpdb->prefix . 'an_messages';
		$sql_messages = "CREATE TABLE $table_messages (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			client_id bigint(20) NOT NULL,
			sender_id bigint(20) NOT NULL,
			message text NOT NULL,
			is_read tinyint(1) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $charset_collate;";
		dbDelta( $sql_messages );

		// Leads Table (EngageTrack)
		$table_leads = $wpdb->prefix . 'an_leads';
		$sql_leads = "CREATE TABLE $table_leads (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			email varchar(255) NOT NULL,
			source varchar(255) DEFAULT 'direct',
			status varchar(50) DEFAULT 'new',
			conversion_value decimal(10,2) DEFAULT 0.00,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $charset_collate;";
		dbDelta( $sql_leads );

		// Expenses Table (MoneyFlow)
		$table_expenses = $wpdb->prefix . 'an_expenses';
		$sql_expenses = "CREATE TABLE $table_expenses (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			project_id bigint(20),
			amount decimal(10,2) NOT NULL,
			category varchar(255) DEFAULT 'general',
			note text,
			receipt_url varchar(255),
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $charset_collate;";
		dbDelta( $sql_expenses );

		// Shared Files Table (ClientSync)
		$table_files = $wpdb->prefix . 'an_shared_files';
		$sql_files = "CREATE TABLE $table_files (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			client_id bigint(20) NOT NULL,
			user_id bigint(20) NOT NULL,
			file_url varchar(255) NOT NULL,
			file_name varchar(255) NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $charset_collate;";
		dbDelta( $sql_files );

		// Canned Responses Table (EngageTrack)
		$table_responses = $wpdb->prefix . 'an_canned_responses';
		$sql_responses = "CREATE TABLE $table_responses (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			title varchar(255) NOT NULL,
			content text NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $charset_collate;";
		dbDelta( $sql_responses );

		// Resources Table (FreebieFactory)
		$table_resources = $wpdb->prefix . 'an_resources';
		$sql_resources = "CREATE TABLE $table_resources (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			title varchar(255) NOT NULL,
			type varchar(50) DEFAULT 'template',
			file_url varchar(255),
			content text,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $charset_collate;";
		dbDelta( $sql_resources );

		// Burnout Logs Table (BurnoutGuard)
		$table_burnout = $wpdb->prefix . 'an_burnout_logs';
		$sql_burnout = "CREATE TABLE $table_burnout (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			stress_level int NOT NULL,
			note text,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $charset_collate;";
		dbDelta( $sql_burnout );
	}
}
