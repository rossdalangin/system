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
	}
}
