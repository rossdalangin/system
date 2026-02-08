<?php
/**
 * Email Handler Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Nexus_Email_Handler {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		add_action( 'phpmailer_init', [ $this, 'configure_smtp' ] );
		add_filter( 'wp_mail_from', [ $this, 'set_from_email' ] );
		add_filter( 'wp_mail_from_name', [ $this, 'set_from_name' ] );
	}

	/**
	 * Configure PHPMailer to use SMTP if enabled.
	 */
	public function configure_smtp( $phpmailer ) {
		if ( 'yes' !== get_option( 'an_smtp_enabled', 'no' ) ) {
			return;
		}

		$phpmailer->isSMTP();
		$phpmailer->Host       = get_option( 'an_smtp_host' );
		$phpmailer->SMTPAuth   = ( 'yes' === get_option( 'an_smtp_auth', 'no' ) );
		$phpmailer->Port       = get_option( 'an_smtp_port', 587 );
		$phpmailer->Username   = get_option( 'an_smtp_username' );
		$phpmailer->Password   = get_option( 'an_smtp_password' );
		$phpmailer->SMTPSecure = get_option( 'an_smtp_encryption', 'tls' );

		// If encryption is 'none', set SMTPSecure to empty string
		if ( 'none' === $phpmailer->SMTPSecure ) {
			$phpmailer->SMTPSecure = '';
		}
	}

	/**
	 * Set the "From" email address.
	 */
	public function set_from_email( $original_email ) {
		$from_email = get_option( 'an_email_from_address' );
		return ! empty( $from_email ) ? $from_email : $original_email;
	}

	/**
	 * Set the "From" name.
	 */
	public function set_from_name( $original_name ) {
		$from_name = get_option( 'an_email_from_name' );
		return ! empty( $from_name ) ? $from_name : $original_name;
	}
}
