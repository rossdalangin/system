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
