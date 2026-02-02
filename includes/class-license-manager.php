<?php
/**
 * License Manager Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Nexus_License_Manager {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Get the current license tier.
	 * Returns: 'free', 'pro', or 'agency'
	 */
	public function get_tier() {
		$license_data = get_option( 'an_license_data' );

		if ( ! $license_data || empty( $license_data['status'] ) || $license_data['status'] !== 'active' ) {
			return 'free';
		}

		return isset( $license_data['tier'] ) ? $license_data['tier'] : 'free';
	}

	/**
	 * Check if a specific feature is enabled for the current tier.
	 */
	public function is_feature_enabled( $feature ) {
		$tier = $this->get_tier();

		$features = [
			'project_management' => [ 'free', 'starter', 'pro', 'agency' ],
			'client_management'  => [ 'free', 'starter', 'pro', 'agency' ],
			'messaging'          => [ 'free', 'starter', 'pro', 'agency' ],
			'content_calendar'   => [ 'free', 'starter', 'pro', 'agency' ],
			'time_blocking'      => [ 'free', 'starter', 'pro', 'agency' ],
			'burnout_guard'      => [ 'free', 'starter', 'pro', 'agency' ],
			'money_flow'         => [ 'pro', 'agency' ],
			'autopilot'          => [ 'pro', 'agency' ],
			'lead_intelligence'  => [ 'pro', 'agency' ],
			'white_label'        => [ 'agency' ],
		];

		if ( ! isset( $features[ $feature ] ) ) {
			return true; // Default to true for unknown features
		}

		return in_array( $tier, $features[ $feature ] );
	}

	/**
	 * Validate a license key with a remote server.
	 */
	public function activate_license( $key ) {
		$store_url = get_option( 'an_store_url' );

		if ( empty( $store_url ) ) {
			// Fallback to simulation if no store URL is set, for demo purposes
			return $this->simulate_activation( $key );
		}

		$api_url = trailingslashit( $store_url ) . 'wp-json/agency-nexus-store/v1/validate';

		$response = wp_remote_post( $api_url, [
			'body' => [
				'license_key' => $key,
				'site_url'    => get_site_url()
			]
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code !== 200 ) {
			$message = isset( $body['message'] ) ? $body['message'] : 'Invalid response from licensing server.';
			return new WP_Error( 'activation_failed', $message );
		}

		$license_data = [
			'key'        => $key,
			'status'     => $body['status'],
			'tier'       => $body['tier'],
			'activated'  => $body['activated'],
			'expires'    => $body['expires']
		];

		update_option( 'an_license_data', $license_data );
		return true;
	}

	/**
	 * Simulated activation for testing when no store URL is configured.
	 */
	private function simulate_activation( $key ) {
		$tier = 'free';
		if ( strpos( $key, 'STR-' ) === 0 ) {
			$tier = 'starter';
		} elseif ( strpos( $key, 'PRO-' ) === 0 ) {
			$tier = 'pro';
		} elseif ( strpos( $key, 'AGY-' ) === 0 ) {
			$tier = 'agency';
		} else {
			return new WP_Error( 'invalid_key', 'The license key provided is invalid. (Simulated)' );
		}

		$license_data = [
			'key'        => $key,
			'status'     => 'active',
			'tier'       => $tier,
			'activated'  => current_time( 'mysql' ),
			'expires'    => date( 'Y-m-d H:i:s', strtotime( '+1 year' ) )
		];

		update_option( 'an_license_data', $license_data );
		return true;
	}

	/**
	 * Deactivate the current license.
	 */
	public function deactivate_license() {
		delete_option( 'an_license_data' );
		return true;
	}
}
