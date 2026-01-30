<?php
/**
 * Seeder Class for Sample Data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Nexus_Seeder {

	public static function seed() {
		global $wpdb;

		// 1. Create Sample Client User
		$client_user_id = self::get_or_create_user( 'sample_client', 'client@example.com', 'subscriber', 'Sample Client' );

		// 2. Create Sample Team Member User
		$team_user_id = self::get_or_create_user( 'sample_team', 'team@example.com', 'author', 'Sample Team Member' );

		// 3. Create an_clients entry
		$table_clients = $wpdb->prefix . 'an_clients';
		$client_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_clients WHERE email = %s", 'client@example.com' ) );
		if ( ! $client_id ) {
			$wpdb->insert( $table_clients, [
				'name'    => 'Sample Client',
				'email'   => 'client@example.com',
				'company' => 'Example Corp',
				'status'  => 'active'
			] );
			$client_id = $wpdb->insert_id;
		}

		// 4. Create Sample Project
		$table_projects = $wpdb->prefix . 'an_projects';
		$project_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_projects WHERE title = %s", 'Sample Web Design Project' ) );
		if ( ! $project_id ) {
			$wpdb->insert( $table_projects, [
				'client_id'   => $client_id,
				'title'       => 'Sample Web Design Project',
				'description' => 'A sample project to demonstrate Agency Nexus features.',
				'budget'      => 5000.00,
				'status'      => 'in_progress',
				'start_date'  => date( 'Y-m-d' )
			] );
			$project_id = $wpdb->insert_id;
		}

		// 5. Create Sample Tasks
		$table_tasks = $wpdb->prefix . 'an_tasks';
		$tasks = [
			[ 'title' => 'Initial Discovery Call', 'status' => 'completed', 'assigned_to' => $team_user_id ],
			[ 'title' => 'Homepage Wireframes', 'status' => 'todo', 'assigned_to' => $team_user_id ],
			[ 'title' => 'Brand Identity Design', 'status' => 'todo', 'assigned_to' => 0 ],
		];

		foreach ( $tasks as $task ) {
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_tasks WHERE project_id = %d AND title = %s", $project_id, $task['title'] ) );
			if ( ! $exists ) {
				$wpdb->insert( $table_tasks, array_merge( $task, [ 'project_id' => $project_id, 'priority' => 'medium' ] ) );
			}
		}

		return true;
	}

	private static function get_or_create_user( $username, $email, $role, $display_name ) {
		$user = get_user_by( 'login', $username );
		if ( ! $user ) {
			$user_id = wp_create_user( $username, 'password123', $email );
			if ( ! is_wp_error( $user_id ) ) {
				$user = new WP_User( $user_id );
				$user->set_role( $role );
				wp_update_user( [ 'ID' => $user_id, 'display_name' => $display_name ] );
				return $user_id;
			}
		}
		return $user ? $user->ID : 0;
	}
}
