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
		$team_data = [
			['jules_dev', 'jules@example.com', 'administrator', 'Jules (Admin)'],
			['sarah_pm', 'sarah@example.com', 'editor', 'Sarah Miller (Project Manager)'],
			['mark_dev', 'mark@example.com', 'author', 'Mark Johnson (Senior Developer)'],
			['elena_design', 'elena@example.com', 'author', 'Elena Rodriguez (UX Designer)'],
			['david_seo', 'david@example.com', 'author', 'David Chen (SEO Specialist)']
		];
		foreach ( $team_data as $t ) {
			$team_members[] = self::get_or_create_user( $t[0], $t[1], $t[2], $t[3] );
		}

		// 2. Create 10+ Clients
		$client_ids = [];
		$clients_data = [
			['Robert Fox', 'robert@acme.com', 'Acme Corp', '123 Business Way, NY'],
			['Jane Cooper', 'jane@globex.com', 'Globex Corporation', '456 Tech Blvd, SF'],
			['Cody Fisher', 'cody@soylent.com', 'Soylent Corp', '789 Green St, Austin'],
			['Esther Howard', 'esther@stark.com', 'Stark Industries', '101 Tower Rd, Malibu'],
			['Jenny Wilson', 'jenny@wayne.com', 'Wayne Enterprises', '202 Manor Ln, Gotham'],
			['Guy Hawkins', 'guy@umbrella.com', 'Umbrella Corp', '303 Raccoon City'],
			['Theresa Webb', 'theresa@initech.com', 'Initech', '404 Cubicle Dr, Houston'],
			['Marvin McKinney', 'marvin@massive.com', 'Massive Dynamic', '505 Fringe St, Boston'],
			['Jerome Bell', 'jerome@hooli.com', 'Hooli', '606 Nucleus Ave, Palo Alto'],
			['Eleanor Pena', 'eleanor@wonka.com', 'Wonka Industries', '707 Chocolate Way'],
			['Ralph Edwards', 'ralph@dunder.com', 'Dunder Mifflin', '1725 Slough Ave, Scranton'],
			['Arlene McCoy', 'arlene@vandelay.com', 'Vandelay Industries', '808 Latex Cir, NY']
		];

		foreach ( $clients_data as $i => $c ) {
			$wpdb->insert( $wpdb->prefix . 'an_clients', [
				'name'    => $c[0],
				'email'   => $c[1],
				'company' => $c[2],
				'address' => $c[3],
				'status'  => 'active',
				'created_at' => date('Y-m-d H:i:s', strtotime("-" . ($i+5) . " days"))
			] );
			$client_id = $wpdb->insert_id;
			$client_ids[] = $client_id;

			// Create a WP user for the first 5 clients
			if ( $i < 5 ) {
				self::get_or_create_user( strtolower(str_replace(' ', '_', $c[0])), $c[1], 'subscriber', $c[0] );
			}
		}

		// 3. Create 10+ Projects
		$project_ids = [];
		$project_templates = [
			['Website Redesign & Brand Refresh', 8500, 'Complete overhaul of corporate identity and digital presence.'],
			['Quarterly SEO Strategy', 3200, 'Content gap analysis, backlink building, and technical audit.'],
			['Social Media Campaign (Launch)', 4500, 'Creative assets and distribution for the new product line.'],
			['Mobile App Development (MVP)', 12000, 'Phase 1 development of the consumer-facing mobile application.'],
			['E-commerce Migration', 9500, 'Moving from Shopify to WooCommerce with full data integrity.'],
			['Google Ads Optimization', 1500, 'Performance marketing audit and landing page A/B testing.'],
			['Email Marketing Automation', 2800, 'Setting up nurture sequences and customer retention flows.'],
			['B2B Content Pillar Strategy', 5500, 'Creating high-authority whitepapers and supporting blog posts.'],
			['API Integration Project', 6000, 'Connecting internal ERP with third-party logistics providers.'],
			['Custom CRM Implementation', 15000, 'Building a tailored sales pipeline management tool.'],
			['Video Production & Editing', 7200, 'Series of 5 high-quality explainer videos for YouTube.'],
			['Server Migration & Security Audit', 3500, 'Hardening infrastructure and migrating to AWS.']
		];

		foreach ( $project_templates as $i => $pt ) {
			$client_id = $client_ids[ $i % count($client_ids) ];
			$team_id   = $team_members[ array_rand($team_members) ];
			$status    = ($i < 3) ? 'completed' : (($i < 8) ? 'in_progress' : 'planned');

			$wpdb->insert( $wpdb->prefix . 'an_projects', [
				'client_id'   => $client_id,
				'assigned_to' => $team_id,
				'title'       => $pt[0],
				'description' => Agency_Nexus::encrypt($pt[2]),
				'budget'      => $pt[1],
				'status'      => $status,
				'start_date'  => date( 'Y-m-d', strtotime("-" . (rand(10, 60)) . " days") )
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
		$lead_data = [
			['Arthur Dent', 'arthur@hitchhiker.com', 'Referral', 'qualified', 5000],
			['Ford Prefect', 'ford@guide.com', 'Website', 'new', 2500],
			['Tricia McMillan', 'trillian@earth.com', 'LinkedIn', 'converted', 8000],
			['Zaphod Beeblebrox', 'president@galaxy.com', 'Google', 'lost', 100000],
			['Marvin Robot', 'paranoid@sirius.com', 'Direct', 'qualified', 1200],
			['Slartibartfast', 'fjords@magrathea.com', 'Website', 'new', 15000],
			['Beeblebrox IV', 'fourth@galaxy.com', 'Referral', 'new', 3000],
			['Fenchurch', 'fen@flying.com', 'LinkedIn', 'converted', 4500],
			['Random Dent', 'random@history.com', 'Google', 'new', 1800],
			['Wonko Sane', 'wonko@asylum.com', 'Direct', 'lost', 500]
		];

		foreach ( $lead_data as $l ) {
			$wpdb->insert( $wpdb->prefix . 'an_leads', [
				'name'             => $l[0],
				'email'            => $l[1],
				'source'           => $l[2],
				'status'           => $l[3],
				'conversion_value' => $l[4],
				'score'            => rand(30, 95),
				'created_at'       => date('Y-m-d H:i:s', strtotime("-" . rand(5, 45) . " days"))
			] );
		}

		// 7. Create Canned Responses
		$responses = [
			['Initial Inquiry Response', 'Hi {{name}}, thanks for reaching out! We specialize in helping companies like yours scale their digital footprint. Would you be open to a 15-minute discovery call next Tuesday?'],
			['Proposal Follow-up', 'Hi there, just checking in on the proposal I sent over last week. Do you have any questions about the deliverables or the timeline?'],
			['Project Kickoff Welcome', 'Welcome to the agency! We are excited to start on {{project_name}}. Your dedicated Project Manager will be Sarah. You can track all progress in your Client Portal.'],
			['Standard Service Rates', 'Our agency operates on a value-based pricing model, with standard implementation packages starting at $2,500. For custom development, our blended hourly rate is $150.'],
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

		// 9. Create Marketplace Items
		$m_items = [
			['Premium Web Contract', 'template', 99, 'Legal', '1.2.0', 'Universal'],
			['SEO Outreach Swipe File', 'swipe', 49, 'SEO', '1.0.5', 'Gmail/Outlook'],
			['Agency Onboarding Deck', 'template', 149, 'Operations', '2.1.0', 'PowerPoint/Canva']
		];
		foreach($m_items as $item) {
			$wpdb->insert( $wpdb->prefix . 'an_resources', [
				'title'           => $item[0],
				'type'            => $item[1],
				'price'           => $item[2],
				'category'        => $item[3],
				'version'         => $item[4],
				'compatible_with' => $item[5],
				'content'         => "This is a premium " . $item[1] . " for your agency.",
				'is_marketplace'  => 1,
				'created_at'      => current_time('mysql'),
				'last_updated_at' => current_time('mysql')
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
			'an_payments', 'an_autopilot_rules', 'an_proposals', 'an_vacations',
			'an_meetings', 'an_content_versions', 'an_keyword_gap', 'an_content_comments',
			'an_lead_communications', 'an_social_interactions', 'an_referral_partners',
			'an_referrals'
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
