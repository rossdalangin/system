<?php
/**
 * Permissions Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Nexus_Permissions {

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_filter( 'user_has_cap', [ __CLASS__, 'grant_client_upload_cap' ], 10, 3 );
		add_filter( 'ajax_query_attachments_args', [ __CLASS__, 'filter_media_library' ] );
	}

	/**
	 * Grant upload_files capability to Clients so they can use the Shared File repository.
	 */
	public static function grant_client_upload_cap( $allcaps, $caps, $args ) {
		if ( ! is_user_logged_in() ) {
			return $allcaps;
		}

		if ( in_array( 'upload_files', $caps ) ) {
			if ( self::is_client() ) {
				$allcaps['upload_files'] = true;
			}
		}

		return $allcaps;
	}

	/**
	 * Filter the Media Library to show only own uploads for non-staff.
	 */
	public static function filter_media_library( $query ) {
		if ( ! self::is_team_member() ) {
			$user_id = get_current_user_id();
			if ( $user_id ) {
				$query['author'] = $user_id;
			}
		}
		return $query;
	}

	/**
	 * Check if the user can access any part of Agency Nexus.
	 */
	public static function can_access_nexus() {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		$user = wp_get_current_user();
		if ( ! $user->ID ) {
			return false;
		}

		// Team Members (Editor, Author)
		if ( array_intersect( [ 'editor', 'author' ], $user->roles ) ) {
			return true;
		}

		// Clients (Check by email)
		if ( self::get_client_id_for_user( $user->ID ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get the agency client ID associated with a WordPress User ID.
	 */
	public static function get_client_id_for_user( $user_id ) {
		global $wpdb;
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return 0;
		}

		return (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}an_clients WHERE email = %s",
			$user->user_email
		) );
	}

	/**
	 * Check if user is an Administrator.
	 */
	public static function is_admin( $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		return user_can( $user_id, 'manage_options' );
	}

	/**
	 * Check if user is a Team Member (Staff).
	 */
	public static function is_team_member( $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		if ( self::is_admin( $user_id ) ) {
			return true;
		}
		$user = get_userdata( $user_id );
		return $user && array_intersect( [ 'editor', 'author' ], $user->roles );
	}

	/**
	 * Check if user is a Client.
	 */
	public static function is_client( $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		if ( user_can( $user_id, 'manage_options' ) ) {
			return false; // Admins are not clients
		}
		return self::get_client_id_for_user( $user_id ) > 0;
	}

	/**
	 * Granular check for project access.
	 */
	public static function can_view_project( $project_id ) {
		$user_id = get_current_user_id();

		if ( self::is_admin( $user_id ) ) {
			return true;
		}

		global $wpdb;

		// If Team Member, check if project or any task in this project is assigned to them.
		if ( self::is_team_member( $user_id ) ) {
			$project_assigned = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}an_projects WHERE id = %d AND assigned_to = %d",
				$project_id,
				$user_id
			) );
			if ( $project_assigned ) return true;

			$task_assigned = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}an_tasks WHERE project_id = %d AND assigned_to = %d LIMIT 1",
				$project_id,
				$user_id
			) );
			return (bool) $task_assigned;
		}

		// If Client, check if they own the project.
		$client_id = self::get_client_id_for_user( $user_id );
		if ( ! $client_id ) {
			return false;
		}

		$project_owner = $wpdb->get_var( $wpdb->prepare(
			"SELECT client_id FROM {$wpdb->prefix}an_projects WHERE id = %d",
			$project_id
		) );

		return (int) $project_owner === $client_id;
	}

	/**
	 * Check if user can view/manage a specific client.
	 */
	public static function can_view_client( $client_id ) {
		$user_id = get_current_user_id();
		if ( self::is_admin( $user_id ) ) return true;
		if ( ! self::is_team_member( $user_id ) ) return false;

		global $wpdb;
		$authorised_projects = self::get_authorised_project_ids();
		if ( empty( $authorised_projects ) ) return false;

		$client_authorised = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}an_projects WHERE client_id = %d AND id IN (" . implode( ',', array_map( 'intval', $authorised_projects ) ) . ") LIMIT 1",
			$client_id
		) );

		return (bool) $client_authorised;
	}

	/**
	 * Get authorized project IDs for the current user.
	 * Returns null if the user is an admin (authorized for all).
	 */
	public static function get_authorised_project_ids() {
		$user_id = get_current_user_id();
		if ( self::is_admin( $user_id ) ) {
			return null;
		}

		global $wpdb;
		if ( self::is_team_member( $user_id ) ) {
			return $wpdb->get_col( $wpdb->prepare( "
				SELECT id FROM {$wpdb->prefix}an_projects
				WHERE (assigned_to = %d OR id IN (SELECT project_id FROM {$wpdb->prefix}an_tasks WHERE assigned_to = %d))",
				$user_id, $user_id
			) );
		}

		$client_id = self::get_client_id_for_user( $user_id );
		if ( ! $client_id ) {
			return [];
		}

		return $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}an_projects WHERE client_id = %d", $client_id ) );
	}

	/**
	 * Granular check for message access.
	 */
	public static function can_access_messages( $requested_client_id ) {
		$user_id = get_current_user_id();
		if ( self::is_admin( $user_id ) ) {
			return true;
		}

		global $wpdb;
		// If Team Member, can access if assigned to at least one project of this client.
		if ( self::is_team_member( $user_id ) ) {
			$authorised_projects = self::get_authorised_project_ids();
			if ( empty($authorised_projects) ) return false;

			$client_has_authorised_project = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}an_projects WHERE client_id = %d AND id IN (" . implode(',', array_map('intval', $authorised_projects)) . ") LIMIT 1",
				$requested_client_id
			) );
			return (bool) $client_has_authorised_project;
		}

		$client_id = self::get_client_id_for_user( $user_id );
		return $client_id && $client_id === (int) $requested_client_id;
	}

	/**
	 * Granular check for invoice access.
	 */
	public static function can_view_invoice( $invoice_id ) {
		$user_id = get_current_user_id();
		if ( self::is_admin( $user_id ) ) {
			return true;
		}

		global $wpdb;
		$invoice = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}an_invoices WHERE id = %d", $invoice_id ) );
		if ( ! $invoice ) return false;

		// If Team Member, check if authorized for the project linked to this invoice.
		if ( self::is_team_member( $user_id ) ) {
			return self::can_view_project( $invoice->project_id );
		}

		// If Client, check if they own the invoice.
		$client_id = self::get_client_id_for_user( $user_id );
		return $client_id && (int) $invoice->client_id === $client_id;
	}
}
