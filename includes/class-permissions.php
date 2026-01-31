<?php
/**
 * Permissions Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Nexus_Permissions {

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
	 * Check if user is a Team Member (Staff).
	 */
	public static function is_team_member( $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		if ( user_can( $user_id, 'manage_options' ) ) {
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
		if ( self::is_team_member() ) {
			return true;
		}

		global $wpdb;
		$client_id = self::get_client_id_for_user( get_current_user_id() );
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
	 * Granular check for message access.
	 */
	public static function can_access_messages( $requested_client_id ) {
		if ( self::is_team_member() ) {
			return true;
		}

		$client_id = self::get_client_id_for_user( get_current_user_id() );
		return $client_id && $client_id === (int) $requested_client_id;
	}
}
