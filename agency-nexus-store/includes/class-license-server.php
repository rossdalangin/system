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
		$params   = $request->get_params();
		$key      = isset( $params['license_key'] ) ? sanitize_text_field( $params['license_key'] ) : '';
		$site_url = isset( $params['site_url'] ) ? esc_url_raw( $params['site_url'] ) : '';

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

		// Check Site Activations
		$activations = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}an_license_activations WHERE license_id = %d",
			$license->id
		) );

		$already_active = false;
		foreach ( $activations as $act ) {
			if ( trailingslashit($act->site_url) === trailingslashit($site_url) ) {
				$already_active = true;
				break;
			}
		}

		if ( ! $already_active ) {
			// Check Limits
			$limit = ( $license->tier === 'pro' ) ? 1 : 9999; // Pro = 1, Agency = Unlimited
			if ( count($activations) >= $limit ) {
				return new WP_Error( 'limit_reached', 'This license key has reached its maximum activation limit. Upgrade to Agency for multi-site support.', [ 'status' => 403 ] );
			}

			// Record new activation
			$wpdb->insert( $wpdb->prefix . 'an_license_activations', [
				'license_id' => $license->id,
				'site_url'   => $site_url
			] );
		}

		return new WP_REST_Response( [
			'status'    => 'active',
			'tier'      => $license->tier,
			'activated' => $license->created_at,
			'expires'   => date( 'Y-m-d H:i:s', strtotime( $license->created_at . ' + 1 year' ) )
		], 200 );
	}
}
