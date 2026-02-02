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
		$gateway_pref = isset($_GET['gateway']) ? sanitize_text_field($_GET['gateway']) : 'both';

		// If success token is present, show success page
		if ( isset( $_GET['an_success'] ) ) {
			$this->render_success_page($_GET['an_success']);
			return;
		}

		if ( isset( $_POST['process_payment'] ) ) {
			$this->process_simulated_payment($tier);
			return;
		}

		$price = get_option( 'an_pro_price', '199' );
		if ( $tier === 'starter' ) $price = get_option( 'an_starter_price', '0' );
		if ( $tier === 'agency' )  $price = get_option( 'an_agency_price', '999' );

		get_header();
		?>
		<div style="max-width: 600px; margin: 50px auto; padding: 40px; border: 1px solid #ddd; border-radius: 12px; font-family: sans-serif;">
			<h2>Complete Your Purchase</h2>
			<p>You are purchasing the <strong>Agency Nexus <?php echo ucfirst($tier); ?></strong> license.</p>
			<p style="font-size: 24px; font-weight: bold;">Total: $<?php echo esc_html($price); ?></p>

			<form method="post" style="margin-top: 30px;">
				<div style="margin-bottom: 20px;">
					<label style="font-weight: bold;">Email Address:</label><br>
					<input type="email" name="customer_email" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin-top: 5px;">
				</div>

				<div style="margin-bottom: 25px;">
					<label style="font-weight: bold;">Select Payment Method:</label><br>
					<div style="margin-top: 10px;">
						<?php if ( $gateway_pref === 'both' || $gateway_pref === 'stripe' ) : ?>
							<label style="display: block; margin-bottom: 10px; cursor: pointer;">
								<input type="radio" name="selected_gateway" value="stripe" checked>
								<span style="margin-left: 10px;">💳 Pay with Credit Card (Stripe)</span>
							</label>
						<?php endif; ?>

						<?php if ( $gateway_pref === 'both' || $gateway_pref === 'paypal' ) : ?>
							<label style="display: block; cursor: pointer;">
								<input type="radio" name="selected_gateway" value="paypal" <?php echo ($gateway_pref === 'paypal' ? 'checked' : ''); ?>>
								<span style="margin-left: 10px;">🅿️ Pay with PayPal</span>
							</label>
						<?php endif; ?>
					</div>
				</div>

				<p style="background: #fff8e1; padding: 10px; border-left: 4px solid #ffc107; font-size: 13px;">
					<strong>Note:</strong> This is a secure checkout. Once payment is confirmed, you will receive your license key and download link immediately.
				</p>

				<button type="submit" name="process_payment" style="background: #6366f1; color: #fff; border: none; padding: 15px 30px; border-radius: 8px; font-weight: bold; cursor: pointer; width: 100%; font-size: 16px;">
					Proceed to Secure Payment &rarr;
				</button>
			</form>
		</div>
		<?php
		get_footer();
	}

	private function process_simulated_payment($tier) {
		$email = sanitize_email( $_POST['customer_email'] );
		$gateway = isset($_POST['selected_gateway']) ? sanitize_text_field($_POST['selected_gateway']) : 'stripe';

		// Generate Key
		$prefix = 'PRO-';
		if ( $tier === 'starter' ) $prefix = 'STR-';
		if ( $tier === 'agency' )  $prefix = 'AGY-';

		$key = $prefix . strtoupper( bin2hex( random_bytes( 8 ) ) );

		global $wpdb;
		$wpdb->insert( $wpdb->prefix . 'an_issued_licenses', [
			'license_key' => $key,
			'user_email'  => $email,
			'tier'        => $tier,
			'status'      => 'active'
		] );

		// Record the Payment
		$price = get_option( 'an_pro_price', '199' );
		if ( $tier === 'starter' ) $price = get_option( 'an_starter_price', '0' );
		if ( $tier === 'agency' )  $price = get_option( 'an_agency_price', '999' );

		$wpdb->insert( $wpdb->prefix . 'an_store_payments', [
			'customer_email' => $email,
			'license_key'    => $key,
			'amount'         => floatval($price),
			'currency'       => 'USD',
			'gateway'        => $gateway,
			'transaction_id' => strtoupper($gateway[0]) . '-' . time()
		] );

		// Generate a success token (session-based for simulation)
		$token = bin2hex(random_bytes(16));
		set_transient('an_success_' . $token, [
			'key'   => $key,
			'email' => $email,
			'tier'  => $tier
		], 3600); // Valid for 1 hour

		// Redirect to success page to prevent resubmission and ensure "paid" state
		wp_redirect( add_query_arg( [
			'an_checkout' => $tier,
			'an_success'  => $token
		], home_url('/') ) );
		exit;
	}

	private function render_success_page($token) {
		$data = get_transient('an_success_' . $token);

		if ( ! $data ) {
			get_header();
			echo '<div style="max-width: 600px; margin: 100px auto; text-align: center; font-family: sans-serif;">';
			echo '<h2>Session Expired</h2>';
			echo '<p>We could not verify your payment session. If you have already paid, please check your email or contact support.</p>';
			echo '</div>';
			get_footer();
			return;
		}

		$key = $data['key'];
		$download_url = get_option( 'an_download_url' );

		get_header();
		?>
		<div style="max-width: 800px; margin: 50px auto; padding: 40px; border: 1px solid #46b450; border-radius: 12px; font-family: sans-serif; text-align: center;">
			<div style="font-size: 60px; margin-bottom: 20px;">✅</div>
			<h2 style="color: #46b450; font-size: 32px; margin-top: 0;">Purchase Successful!</h2>
			<p style="font-size: 18px; color: #555;">Thank you for your purchase. Your license for <strong>Agency Nexus <?php echo ucfirst($data['tier']); ?></strong> is now active.</p>

			<div style="background: #f0fdf4; padding: 30px; border-radius: 8px; margin: 30px 0; border: 1px solid #bbf7d0; position: relative;">
				<p style="margin-top: 0; color: #166534; font-weight: bold;">YOUR LICENSE KEY</p>
				<code style="font-size: 28px; color: #166534; letter-spacing: 2px;"><?php echo esc_html($key); ?></code>
				<p style="margin-bottom: 0;"><small>Enter this key in your WordPress Dashboard > Agency Nexus > Settings to activate premium features.</small></p>
			</div>

			<div style="margin-top: 40px;">
				<a href="<?php echo esc_url($download_url); ?>" style="display: inline-block; background: #2271b1; color: #fff; text-decoration: none; padding: 18px 45px; border-radius: 8px; font-weight: bold; font-size: 20px; box-shadow: 0 4px 12px rgba(34,113,177,0.3);">
					Download Agency Nexus Plugin (.zip) &darr;
				</a>
				<p style="color: #666; font-size: 14px; margin-top: 15px;">Version 1.0.0 | Compatible with WordPress 5.8+</p>
			</div>

			<hr style="margin: 40px 0; border: 0; border-top: 1px solid #eee;">
			<p><a href="<?php echo home_url('/'); ?>" style="color: #6366f1; text-decoration: none; font-weight: bold;">&larr; Return to Dashboard</a></p>
		</div>
		<?php
		get_footer();
	}
}
