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

		// Clear first to ensure fresh seed
		self::clear_all();

		// 1. Create Sample Users
		$team_members = [];
		for ( $i = 1; $i <= 5; $i++ ) {
			$team_members[] = self::get_or_create_user( "team_member_$i", "team$i@example.com", 'author', "Team Member $i" );
		}

		// 2. Create 10+ Clients
		$client_ids = [];
		for ( $i = 1; $i <= 12; $i++ ) {
			$wpdb->insert( $wpdb->prefix . 'an_clients', [
				'name'    => "Client $i",
				'email'   => "client$i@example.com",
				'company' => "Company $i Ltd",
				'status'  => 'active',
				'created_at' => date('Y-m-d H:i:s', strtotime("-$i days"))
			] );
			$client_ids[] = $wpdb->insert_id;

			// Occasionally create a WP user for the client
			if ( $i % 3 === 0 ) {
				self::get_or_create_user( "client_user_$i", "client$i@example.com", 'subscriber', "Client $i" );
			}
		}

		// 3. Create 10+ Projects
		$project_ids = [];
		$statuses = ['planned', 'in_progress', 'on_hold', 'completed'];
		for ( $i = 1; $i <= 15; $i++ ) {
			$client_id = $client_ids[ array_rand($client_ids) ];
			$team_id   = $team_members[ array_rand($team_members) ];
			$status    = $statuses[ array_rand($statuses) ];

			$wpdb->insert( $wpdb->prefix . 'an_projects', [
				'client_id'   => $client_id,
				'assigned_to' => $team_id,
				'title'       => "Project " . ($i <= 5 ? "Web Design $i" : ($i <= 10 ? "SEO Campaign $i" : "Social Media $i")),
				'description' => "Sample project description for project $i.",
				'budget'      => rand(1000, 10000),
				'status'      => $status,
				'start_date'  => date( 'Y-m-d', strtotime("-" . rand(1, 30) . " days") )
			] );
			$project_id = $wpdb->insert_id;
			$project_ids[] = $project_id;

			// 4. Create Tasks for each project
			for ( $j = 1; $j <= rand(3, 7); $j++ ) {
				$wpdb->insert( $wpdb->prefix . 'an_tasks', [
					'project_id'  => $project_id,
					'title'       => "Task $j for Project $i",
					'assigned_to' => (rand(0, 1) ? $team_id : 0),
					'status'      => (rand(0, 1) ? 'todo' : 'completed'),
					'priority'    => (rand(0, 1) ? 'high' : 'medium'),
					'start_date'  => date('Y-m-d'),
					'due_date'    => date('Y-m-d', strtotime('+' . rand(1, 14) . ' days'))
				] );
				$task_id = $wpdb->insert_id;

				// Log some time
				if ( rand(0, 1) ) {
					$wpdb->insert( $wpdb->prefix . 'an_time_entries', [
						'task_id'  => $task_id,
						'user_id'  => $team_id,
						'duration' => rand(1, 8) * 3600,
						'date'     => date('Y-m-d H:i:s'),
						'note'     => "Worked on task $j"
					] );
				}
			}

			// 5. Create Invoices for some projects
			if ( rand(0, 1) ) {
				$wpdb->insert( $wpdb->prefix . 'an_invoices', [
					'project_id' => $project_id,
					'client_id'  => $client_id,
					'number'     => "INV-" . str_pad($i, 4, '0', STR_PAD_LEFT),
					'amount'     => rand(500, 3000),
					'status'     => (rand(0, 1) ? 'paid' : 'sent'),
					'due_date'   => date('Y-m-d', strtotime('+15 days')),
					'created_at' => current_time('mysql')
				] );
			}
		}

		// 6. Create 10+ Leads
		$lead_statuses = ['new', 'qualified', 'converted', 'lost'];
		$sources = ['referral', 'google', 'linkedin', 'website'];
		for ( $i = 1; $i <= 12; $i++ ) {
			$wpdb->insert( $wpdb->prefix . 'an_leads', [
				'name'             => "Prospective Lead $i",
				'email'            => "lead$i@example.com",
				'source'           => $sources[ array_rand($sources) ],
				'status'           => $lead_statuses[ array_rand($lead_statuses) ],
				'conversion_value' => rand(1000, 5000),
				'created_at'       => date('Y-m-d H:i:s', strtotime("-" . rand(1, 60) . " days"))
			] );
		}

		// 7. Create Canned Responses
		$responses = [
			['Welcome Message', 'Hi there! Welcome to our agency. How can we help you today?'],
			['Pricing Inquiry', 'Our standard rates start at $50/hr for most services.'],
			['Onboarding Link', 'Please fill out this onboarding form to get started: [link]'],
			['Meeting Request', 'I would love to hop on a call. Are you free tomorrow?'],
		];
		foreach ( $responses as $res ) {
			$wpdb->insert( $wpdb->prefix . 'an_canned_responses', [
				'title' => $res[0],
				'content' => $res[1],
				'created_at' => current_time('mysql')
			] );
		}

		// 8. Create Resources
		for ( $i = 1; $i <= 5; $i++ ) {
			$wpdb->insert( $wpdb->prefix . 'an_resources', [
				'title' => "Helpful Document $i",
				'type' => (rand(0, 1) ? 'template' : 'swipe'),
				'content' => "Sample content for resource $i...",
				'created_at' => current_time('mysql')
			] );
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
			$table_name = $wpdb->prefix . $table;
			// Use DELETE if TRUNCATE fails or to be safer across all environments
			$wpdb->query( "DELETE FROM $table_name" );
			$wpdb->query( "ALTER TABLE $table_name AUTO_INCREMENT = 1" );
		}

		// Clean up sample users (optional but helpful for a truly "clear" state)
		// We only delete users we created with our sample emails to be safe.
		$wpdb->query( "DELETE FROM $wpdb->users WHERE user_email LIKE '%@example.com'" );
		$wpdb->query( "DELETE FROM $wpdb->usermeta WHERE user_id NOT IN (SELECT ID FROM $wpdb->users)" );
	}

	private static function get_or_create_user( $username, $email, $role, $display_name ) {
		$user = get_user_by( 'email', $email );
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
