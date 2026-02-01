<?php
/**
 * API Handler Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Nexus_API_Handler {

	private static $instance = null;

	/**
	 * API Namespace
	 */
	protected $namespace = 'agency-nexus/v1';

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_base_routes' ] );
	}

	/**
	 * Register base routes.
	 */
	public function register_base_routes() {
		register_rest_route( $this->namespace, '/status', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_status' ],
			'permission_callback' => '__return_true',
		] );

		register_rest_route( $this->namespace, '/projects', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_projects' ],
			'permission_callback' => [ $this, 'check_permission' ],
		] );

		register_rest_route( $this->namespace, '/projects/(?P<id>\d+)/tasks', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_project_tasks' ],
			'permission_callback' => [ $this, 'check_permission' ],
		] );

		register_rest_route( $this->namespace, '/messages', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_messages' ],
			'permission_callback' => [ $this, 'check_permission' ],
		] );

		register_rest_route( $this->namespace, '/leads/capture', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'capture_lead' ],
			'permission_callback' => '__return_true',
		] );
	}

	/**
	 * Permission check for API routes.
	 */
	public function check_permission() {
		return Agency_Nexus_Permissions::can_access_nexus();
	}

	/**
	 * Get projects list.
	 */
	public function get_projects() {
		global $wpdb;
		$query = "SELECT * FROM {$wpdb->prefix}an_projects";
		if ( ! Agency_Nexus_Permissions::is_team_member() ) {
			$client_id = Agency_Nexus_Permissions::get_client_id_for_user( get_current_user_id() );
			$query .= $wpdb->prepare( " WHERE client_id = %d", $client_id );
		}
		return new WP_REST_Response( $wpdb->get_results( $query ), 200 );
	}

	/**
	 * Get tasks for a specific project.
	 */
	public function get_project_tasks( $request ) {
		$project_id = $request['id'];
		if ( ! Agency_Nexus_Permissions::can_view_project( $project_id ) ) {
			return new WP_Error( 'rest_forbidden', 'Unauthorized', [ 'status' => 403 ] );
		}

		global $wpdb;
		$tasks = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}an_tasks WHERE project_id = %d", $project_id ) );
		return new WP_REST_Response( $tasks, 200 );
	}

	/**
	 * Get messages.
	 */
	public function get_messages() {
		global $wpdb;
		if ( Agency_Nexus_Permissions::is_team_member() ) {
			$messages = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}an_messages ORDER BY created_at DESC LIMIT 50" );
		} else {
			$client_id = Agency_Nexus_Permissions::get_client_id_for_user( get_current_user_id() );
			$messages = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}an_messages WHERE client_id = %d ORDER BY created_at DESC", $client_id ) );
		}
		return new WP_REST_Response( $messages, 200 );
	}

	/**
	 * Capture a lead from an external form.
	 */
	public function capture_lead( $request ) {
		$params = $request->get_params();

		$name   = isset( $params['name'] ) ? sanitize_text_field( $params['name'] ) : '';
		$email  = isset( $params['email'] ) ? sanitize_email( $params['email'] ) : '';
		$source = isset( $params['source'] ) ? sanitize_text_field( $params['source'] ) : 'external_form';

		if ( empty( $name ) || empty( $email ) ) {
			return new WP_Error( 'missing_fields', 'Name and email are required.', [ 'status' => 400 ] );
		}

		global $wpdb;
		$result = $wpdb->insert(
			$wpdb->prefix . 'an_leads',
			[
				'name'             => $name,
				'email'            => $email,
				'source'           => $source,
				'status'           => 'new',
				'conversion_value' => 0.00,
				'created_at'       => current_time( 'mysql' )
			]
		);

		if ( false === $result ) {
			return new WP_Error( 'db_error', 'Failed to save lead.', [ 'status' => 500 ] );
		}

		$redirect_url = isset( $params['redirect_url'] ) ? esc_url_raw( $params['redirect_url'] ) : '';

		// If a redirect URL is provided, we perform a redirect.
		// Standard HTML forms use this to send the user to a Thank You page.
		if ( ! empty( $redirect_url ) ) {
			wp_redirect( $redirect_url );
			exit;
		}

		return new WP_REST_Response( [
			'message'      => 'Lead captured successfully.',
			'id'           => $wpdb->insert_id,
			'redirect_url' => $redirect_url
		], 200 );
	}

	/**
	 * Get API status.
	 */
	public function get_status() {
		return new WP_REST_Response( [
			'status'  => 'ok',
			'version' => AGENCY_NEXUS_VERSION,
		], 200 );
	}
}
