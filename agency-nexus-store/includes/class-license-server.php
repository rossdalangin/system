<?php
/**
 * License Server API Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Nexus_License_Server {

	private static $instance = null;

	protected $namespace = 'agency-nexus-store/v1';

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes() {
		register_rest_route( $this->namespace, '/validate', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'validate_license' ],
			'permission_callback' => '__return_true',
		] );
	}

	/**
	 * Validate a license key.
	 */
	public function validate_license( $request ) {
		$params = $request->get_params();
		$key    = isset( $params['license_key'] ) ? sanitize_text_field( $params['license_key'] ) : '';

		if ( empty( $key ) ) {
			return new WP_Error( 'missing_key', 'License key is required.', [ 'status' => 400 ] );
		}

		global $wpdb;
		$license = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}an_issued_licenses WHERE license_key = %s",
			$key
		) );

		if ( ! $license ) {
			return new WP_Error( 'invalid_key', 'The license key provided is invalid.', [ 'status' => 404 ] );
		}

		if ( $license->status !== 'active' ) {
			return new WP_Error( 'inactive_key', 'This license is no longer active.', [ 'status' => 403 ] );
		}

		return new WP_REST_Response( [
			'status'    => 'active',
			'tier'      => $license->tier,
			'activated' => $license->created_at,
			'expires'   => date( 'Y-m-d H:i:s', strtotime( $license->created_at . ' + 1 year' ) )
		], 200 );
	}
}
