<?php
/**
 * Checkout Handler Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Nexus_Checkout_Handler {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		add_action( 'template_redirect', [ $this, 'handle_checkout' ] );
	}

	public function handle_checkout() {
		if ( ! isset( $_GET['an_checkout'] ) ) {
			return;
		}

		$tier = sanitize_text_field( $_GET['an_checkout'] );
		$this->render_checkout_page($tier);
		exit;
	}

	private function render_checkout_page($tier) {
		if ( isset( $_POST['process_payment'] ) ) {
			$this->process_simulated_payment($tier);
			return;
		}

		$price = ( $tier === 'pro' ) ? get_option( 'an_pro_price', '199' ) : get_option( 'an_agency_price', '999' );

		get_header();
		?>
		<div style="max-width: 600px; margin: 50px auto; padding: 40px; border: 1px solid #ddd; border-radius: 12px; font-family: sans-serif;">
			<h2>Complete Your Purchase</h2>
			<p>You are purchasing the <strong>Agency Nexus <?php echo ucfirst($tier); ?></strong> license.</p>
			<p style="font-size: 24px; font-weight: bold;">Total: $<?php echo esc_html($price); ?></p>

			<form method="post" style="margin-top: 30px;">
				<div style="margin-bottom: 15px;">
					<label>Email Address:</label><br>
					<input type="email" name="customer_email" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
				</div>
				<p><small>(Simulation: Click below to "pay" via Stripe/PayPal)</small></p>
				<button type="submit" name="process_payment" style="background: #6366f1; color: #fff; border: none; padding: 15px 30px; border-radius: 8px; font-weight: bold; cursor: pointer; width: 100%;">Pay with Stripe / PayPal</button>
			</form>
		</div>
		<?php
		get_footer();
	}

	private function process_simulated_payment($tier) {
		$email = sanitize_email( $_POST['customer_email'] );

		// Generate Key
		$prefix = ( $tier === 'pro' ) ? 'PRO-' : 'AGY-';
		$key = $prefix . strtoupper( bin2hex( random_bytes( 8 ) ) );

		global $wpdb;
		$wpdb->insert( $wpdb->prefix . 'an_issued_licenses', [
			'license_key' => $key,
			'user_email'  => $email,
			'tier'        => $tier,
			'status'      => 'active'
		] );

		$download_url = get_option( 'an_download_url' );

		get_header();
		?>
		<div style="max-width: 800px; margin: 50px auto; padding: 40px; border: 1px solid #46b450; border-radius: 12px; font-family: sans-serif; text-align: center;">
			<h2 style="color: #46b450;">🎉 Purchase Successful!</h2>
			<p>Thank you for joining Agency Nexus. Your agency is about to become much more profitable.</p>

			<div style="background: #f0fdf4; padding: 30px; border-radius: 8px; margin: 30px 0; border: 1px solid #bbf7d0;">
				<p><strong>Your License Key:</strong></p>
				<code style="font-size: 24px; color: #166534;"><?php echo esc_html($key); ?></code>
				<p><small>Save this key! You will need to enter it in your WordPress dashboard.</small></p>
			</div>

			<a href="<?php echo esc_url($download_url); ?>" style="display: inline-block; background: #2271b1; color: #fff; text-decoration: none; padding: 15px 40px; border-radius: 8px; font-weight: bold; font-size: 18px;">Download Agency Nexus Plugin (.zip) &darr;</a>

			<p style="margin-top: 30px;"><a href="/">Return to Homepage</a></p>
		</div>
		<?php
		get_footer();
	}
}
