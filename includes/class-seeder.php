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

		// 1. Create Sample Client User (Subscriber role usually)
		$client_user_id = self::get_or_create_user( 'sample_client', 'client@example.com', 'subscriber', 'Sample Client' );

		// 2. Create Sample Team Member User (Author role for testing permissions)
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
				'assigned_to' => $team_user_id,
				'title'       => 'Sample Web Design Project',
				'description' => 'A sample project to demonstrate Agency Nexus features.',
				'budget'      => 5000.00,
				'status'      => 'in_progress',
				'start_date'  => date( 'Y-m-d' )
			] );
			$project_id = $wpdb->insert_id;
		} else {
			// Ensure it's assigned to the team member if it already exists
			$wpdb->update( $table_projects, [ 'assigned_to' => $team_user_id ], [ 'id' => $project_id ] );
		}

		// 5. Create Sample Tasks
		$table_tasks = $wpdb->prefix . 'an_tasks';
		$tasks = [
			[
				'title' => 'Initial Discovery Call',
				'status' => 'completed',
				'assigned_to' => $team_user_id,
				'start_date' => date('Y-m-d', strtotime('-5 days')),
				'due_date' => date('Y-m-d', strtotime('-4 days'))
			],
			[
				'title' => 'Homepage Wireframes',
				'status' => 'todo',
				'assigned_to' => $team_user_id,
				'start_date' => date('Y-m-d'),
				'due_date' => date('Y-m-d', strtotime('+3 days'))
			],
			[
				'title' => 'Brand Identity Design',
				'status' => 'todo',
				'assigned_to' => 0,
				'start_date' => date('Y-m-d', strtotime('+2 days')),
				'due_date' => date('Y-m-d', strtotime('+7 days'))
			],
		];

		foreach ( $tasks as $task ) {
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_tasks WHERE project_id = %d AND title = %s", $project_id, $task['title'] ) );
			if ( ! $exists ) {
				$wpdb->insert( $table_tasks, array_merge( $task, [ 'project_id' => $project_id, 'priority' => 'medium' ] ) );
			}
		}

		// 6. Create Sample Messages (ClientSync)
		$table_messages = $wpdb->prefix . 'an_messages';
		$messages = [
			[ 'sender_id' => $team_user_id, 'message' => 'Hello! Welcome to our portal. We are starting on your project today.' ],
			[ 'sender_id' => $client_user_id, 'message' => 'Thanks! I am excited to see the progress.' ],
		];
		foreach ( $messages as $msg ) {
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_messages WHERE client_id = %d AND message = %s", $client_id, $msg['message'] ) );
			if ( ! $exists ) {
				$wpdb->insert( $table_messages, array_merge( $msg, [ 'client_id' => $client_id, 'created_at' => current_time('mysql') ] ) );
			}
		}

		// 7. Create Sample Content (ContentMatrix / ApprovalFlow)
		$table_content = $wpdb->prefix . 'an_content';
		$contents = [
			[ 'title' => 'Social Media Post #1', 'content' => 'Check out our new website design!', 'status' => 'pending_approval' ],
			[ 'title' => 'Draft Blog Post', 'content' => '5 Tips for Better Web Design...', 'status' => 'draft' ],
		];
		foreach ( $contents as $cnt ) {
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_content WHERE project_id = %d AND title = %s", $project_id, $cnt['title'] ) );
			if ( ! $exists ) {
				$wpdb->insert( $table_content, array_merge( $cnt, [ 'project_id' => $project_id, 'platform' => 'instagram' ] ) );
			}
		}

		// 8. Create Sample Expenses (MoneyFlow)
		$table_expenses = $wpdb->prefix . 'an_expenses';
		$expenses = [
			[ 'amount' => 50.00, 'category' => 'software', 'note' => 'Subscription for design tools' ],
			[ 'amount' => 200.00, 'category' => 'outsourcing', 'note' => 'Logo design draft' ],
		];
		foreach ( $expenses as $exp ) {
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_expenses WHERE project_id = %d AND note = %s", $project_id, $exp['note'] ) );
			if ( ! $exists ) {
				$wpdb->insert( $table_expenses, array_merge( $exp, [ 'project_id' => $project_id, 'created_at' => current_time('mysql') ] ) );
			}
		}

		// 9. Create Sample Invoices
		$table_invoices = $wpdb->prefix . 'an_invoices';
		$inv_number = 'INV-SAMPLE-001';
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_invoices WHERE number = %s", $inv_number ) );
		if ( ! $exists ) {
			$wpdb->insert( $table_invoices, [
				'project_id' => $project_id,
				'client_id'  => $client_id,
				'number'     => $inv_number,
				'amount'     => 1500.00,
				'status'     => 'sent',
				'due_date'   => date('Y-m-d', strtotime('+30 days')),
				'created_at' => current_time('mysql')
			] );
		}

		// 10. Create Sample Resources (FreebieFactory)
		$table_resources = $wpdb->prefix . 'an_resources';
		$resources = [
			[ 'title' => 'Standard Services Agreement', 'type' => 'template', 'content' => 'Standard contract terms...' ],
			[ 'title' => 'Marketing Email Swipe', 'type' => 'swipe', 'content' => 'Subject: How we can help...' ],
		];
		foreach ( $resources as $res ) {
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_resources WHERE title = %s", $res['title'] ) );
			if ( ! $exists ) {
				$wpdb->insert( $table_resources, array_merge( $res, [ 'created_at' => current_time('mysql') ] ) );
			}
		}

		// 11. Create Sample Leads (EngageTrack)
		$table_leads = $wpdb->prefix . 'an_leads';
		$leads = [
			[ 'name' => 'Potential Lead #1', 'email' => 'lead1@example.com', 'source' => 'referral', 'status' => 'new', 'conversion_value' => 2000.00 ],
			[ 'name' => 'Hot Lead #2', 'email' => 'lead2@example.com', 'source' => 'direct', 'status' => 'qualified', 'conversion_value' => 5000.00 ],
		];
		foreach ( $leads as $lead ) {
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_leads WHERE email = %s", $lead['email'] ) );
			if ( ! $exists ) {
				$wpdb->insert( $table_leads, array_merge( $lead, [ 'created_at' => current_time('mysql') ] ) );
			}
		}

		return true;
	}

	public static function clear_all() {
		global $wpdb;
		$tables = [
			'an_clients', 'an_projects', 'an_tasks', 'an_time_entries', 'an_content',
			'an_time_blocks', 'an_messages', 'an_leads', 'an_expenses', 'an_shared_files',
			'an_canned_responses', 'an_resources', 'an_burnout_logs', 'an_invoices',
			'an_payments', 'an_autopilot_rules'
		];
		foreach ( $tables as $table ) {
			$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}$table" );
		}
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
