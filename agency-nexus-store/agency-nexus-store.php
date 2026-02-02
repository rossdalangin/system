<?php
/**
 * Plugin Name: Agency Nexus Store & Licensing
 * Description: Management plugin for the main domain to sell Agency Nexus and issue license keys.
 * Version: 1.0.0
 * Author: Jules
 * Text Domain: agency-nexus-store
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AN_STORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'AN_STORE_URL', plugin_dir_url( __FILE__ ) );

require_once AN_STORE_PATH . 'includes/class-license-server.php';
require_once AN_STORE_PATH . 'includes/class-checkout-handler.php';

class Agency_Nexus_Store {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		add_action( 'init', [ $this, 'init' ] );
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'admin_init', [ $this, 'handle_license_actions' ] );
		add_shortcode( 'an_pricing_table', [ $this, 'render_pricing_table' ] );

		register_activation_hook( __FILE__, [ $this, 'activate' ] );
	}

	public function activate() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		// Licenses Table
		$table_licenses = $wpdb->prefix . 'an_issued_licenses';
		$sql_licenses = "CREATE TABLE $table_licenses (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			license_key varchar(100) NOT NULL,
			user_email varchar(100) NOT NULL,
			tier varchar(20) NOT NULL,
			status varchar(20) DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY license_key (license_key)
		) $charset_collate;";

		// Activations Table (Multi-site tracking)
		$table_activations = $wpdb->prefix . 'an_license_activations';
		$sql_activations = "CREATE TABLE $table_activations (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			license_id bigint(20) NOT NULL,
			site_url varchar(255) NOT NULL,
			activated_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) $charset_collate;";

		// Store Payments Table
		$table_payments = $wpdb->prefix . 'an_store_payments';
		$sql_payments = "CREATE TABLE $table_payments (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			customer_email varchar(100) NOT NULL,
			license_key varchar(100) NOT NULL,
			amount decimal(10,2) NOT NULL,
			currency varchar(10) DEFAULT 'USD',
			gateway varchar(50) DEFAULT 'simulated',
			transaction_id varchar(100) DEFAULT '',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_licenses );
		dbDelta( $sql_activations );
		dbDelta( $sql_payments );
	}

	public function init() {
		Agency_Nexus_License_Server::get_instance();
		Agency_Nexus_Checkout_Handler::get_instance();
	}

	public function add_admin_menu() {
		add_menu_page(
			'AN Store',
			'AN Store',
			'manage_options',
			'an-store',
			[ $this, 'render_settings' ],
			'dashicons-store',
			30
		);

		add_submenu_page(
			'an-store',
			'Licenses',
			'Licenses',
			'manage_options',
			'an-store-licenses',
			[ $this, 'render_licenses_list' ]
		);

		add_submenu_page(
			'an-store',
			'Payments',
			'Payments',
			'manage_options',
			'an-store-payments',
			[ $this, 'render_payments_list' ]
		);
	}

	public function render_settings() {
		if ( isset( $_POST['an_save_store_settings'] ) ) {
			update_option( 'an_starter_price', sanitize_text_field( $_POST['an_starter_price'] ) );
			update_option( 'an_pro_price', sanitize_text_field( $_POST['an_pro_price'] ) );
			update_option( 'an_agency_price', sanitize_text_field( $_POST['an_agency_price'] ) );
			update_option( 'an_download_url', esc_url_raw( $_POST['an_download_url'] ) );

			// Payment Gateway Credentials
			update_option( 'an_store_stripe_publishable_key', sanitize_text_field( $_POST['an_store_stripe_publishable_key'] ) );
			update_option( 'an_store_stripe_secret_key', sanitize_text_field( $_POST['an_store_stripe_secret_key'] ) );
			update_option( 'an_store_paypal_email', sanitize_email( $_POST['an_store_paypal_email'] ) );

			echo '<div class="updated"><p>Settings saved!</p></div>';
		}

		$starter_price = get_option( 'an_starter_price', '0' );
		$pro_price = get_option( 'an_pro_price', '199' );
		$agency_price = get_option( 'an_agency_price', '999' );
		$download_url = get_option( 'an_download_url', '' );

		$stripe_pub = get_option( 'an_store_stripe_publishable_key', '' );
		$stripe_sec = get_option( 'an_store_stripe_secret_key', '' );
		$paypal_email = get_option( 'an_store_paypal_email', '' );
		?>
		<div class="wrap">
			<h1>Agency Nexus Store Settings</h1>
			<form method="post">
				<table class="form-table">
					<tr>
						<th>Starter Tier Price ($)</th>
						<td><input type="text" name="an_starter_price" value="<?php echo esc_attr($starter_price); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th>Pro Tier Price ($)</th>
						<td><input type="text" name="an_pro_price" value="<?php echo esc_attr($pro_price); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th>Agency Tier Price ($)</th>
						<td><input type="text" name="an_agency_price" value="<?php echo esc_attr($agency_price); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th>Plugin Download Link (.zip)</th>
						<td>
							<input type="url" name="an_download_url" value="<?php echo esc_url($download_url); ?>" class="large-text">
							<p class="description">This link will be provided to customers after successful purchase.</p>
						</td>
					</tr>
				</table>
				</table>

				<hr>
				<h2>Payment Gateways</h2>
				<p class="description">Enter your credentials below to receive payments from customers. Currently supports Stripe and PayPal.</p>

				<table class="form-table">
					<tr>
						<th>Stripe Publishable Key</th>
						<td><input type="text" name="an_store_stripe_publishable_key" value="<?php echo esc_attr($stripe_pub); ?>" class="large-text" placeholder="pk_live_..."></td>
					</tr>
					<tr>
						<th>Stripe Secret Key</th>
						<td><input type="password" name="an_store_stripe_secret_key" value="<?php echo esc_attr($stripe_sec); ?>" class="large-text" placeholder="sk_live_..."></td>
					</tr>
					<tr>
						<th>PayPal Business Email</th>
						<td><input type="email" name="an_store_paypal_email" value="<?php echo esc_attr($paypal_email); ?>" class="regular-text" placeholder="billing@yourdomain.com"></td>
					</tr>
				</table>

				<input type="submit" name="an_save_store_settings" class="button button-primary" value="Save Settings">
			</form>
		</div>
		<?php
	}

	public function handle_license_actions() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? $_GET['page'] : '';
		if ( 'an-store-licenses' !== $page ) {
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'an_issued_licenses';
		$action = isset( $_GET['action'] ) ? $_GET['action'] : '';
		$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		if ( 'delete' === $action && $id ) {
			check_admin_referer( 'an_delete_license_' . $id );
			$wpdb->delete( $table_name, [ 'id' => $id ] );
			$wpdb->delete( $wpdb->prefix . 'an_license_activations', [ 'license_id' => $id ] );
			wp_redirect( admin_url( 'admin.php?page=an-store-licenses&msg=deleted' ) );
			exit;
		}

		if ( 'suspend' === $action && $id ) {
			check_admin_referer( 'an_suspend_license_' . $id );
			$wpdb->update( $table_name, [ 'status' => 'suspended' ], [ 'id' => $id ] );
			wp_redirect( admin_url( 'admin.php?page=an-store-licenses&msg=suspended' ) );
			exit;
		}

		if ( 'unsuspend' === $action && $id ) {
			check_admin_referer( 'an_unsuspend_license_' . $id );
			$wpdb->update( $table_name, [ 'status' => 'active' ], [ 'id' => $id ] );
			wp_redirect( admin_url( 'admin.php?page=an-store-licenses&msg=unsuspended' ) );
			exit;
		}

		if ( isset( $_POST['an_manual_create_license'] ) && check_admin_referer( 'an_create_license_nonce' ) ) {
			$tier = sanitize_text_field( $_POST['tier'] );
			$email = sanitize_email( $_POST['email'] );

			$prefix = 'PRO-';
			if ( $tier === 'starter' ) $prefix = 'STR-';
			if ( $tier === 'agency' )  $prefix = 'AGY-';

			$key = $prefix . strtoupper( bin2hex( random_bytes( 8 ) ) );

			$wpdb->insert( $table_name, [
				'license_key' => $key,
				'user_email'  => $email,
				'tier'        => $tier,
				'status'      => 'active'
			] );

			wp_redirect( admin_url( 'admin.php?page=an-store-licenses&msg=created' ) );
			exit;
		}
	}

	public function render_payments_list() {
		global $wpdb;
		$payments = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}an_store_payments ORDER BY created_at DESC" );
		?>
		<div class="wrap">
			<h1>Store Payments</h1>
			<p class="description">List of all successful transactions processed through your main domain store.</p>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>ID</th>
						<th>Customer</th>
						<th>License Key</th>
						<th>Amount</th>
						<th>Gateway</th>
						<th>Transaction ID</th>
						<th>Date</th>
					</tr>
				</thead>
				<tbody>
					<?php if ($payments) : foreach ( $payments as $p ) : ?>
						<tr>
							<td><?php echo $p->id; ?></td>
							<td><?php echo esc_html($p->customer_email); ?></td>
							<td><code><?php echo esc_html($p->license_key); ?></code></td>
							<td><strong>$<?php echo number_format($p->amount, 2); ?></strong></td>
							<td><?php echo esc_html(ucfirst($p->gateway)); ?></td>
							<td><small><?php echo esc_html($p->transaction_id); ?></small></td>
							<td><?php echo esc_html($p->created_at); ?></td>
						</tr>
					<?php endforeach; else : ?>
						<tr><td colspan="7">No payments recorded yet.</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public function render_licenses_list() {
		global $wpdb;
		$licenses = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}an_issued_licenses ORDER BY created_at DESC" );
		$action = isset( $_GET['action'] ) ? $_GET['action'] : 'list';

		if ( isset( $_GET['msg'] ) ) {
			$m = '';
			switch($_GET['msg']) {
				case 'created': $m = 'License created successfully!'; break;
				case 'deleted': $m = 'License deleted!'; break;
				case 'suspended': $m = 'License suspended!'; break;
				case 'unsuspended': $m = 'License activated!'; break;
			}
			if ($m) echo '<div class="updated"><p>' . esc_html($m) . '</p></div>';
		}

		if ( 'add' === $action ) {
			?>
			<div class="wrap">
				<h1>Create License Manually</h1>
				<form method="post">
					<?php wp_nonce_field( 'an_create_license_nonce' ); ?>
					<table class="form-table">
						<tr>
							<th>Email Address</th>
							<td><input type="email" name="email" required class="regular-text"></td>
						</tr>
						<tr>
							<th>Tier</th>
							<td>
								<select name="tier">
									<option value="starter">Starter (Free)</option>
									<option value="pro">Pro</option>
									<option value="agency">Agency (Lifetime)</option>
								</select>
							</td>
						</tr>
					</table>
					<input type="submit" name="an_manual_create_license" class="button button-primary" value="Generate License">
					<a href="?page=an-store-licenses" class="button">Cancel</a>
				</form>
			</div>
			<?php
			return;
		}
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">Issued Licenses</h1>
			<a href="?page=an-store-licenses&action=add" class="page-title-action">Add New</a>
			<hr class="wp-header-end">

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>Key</th>
						<th>Email</th>
						<th>Tier</th>
						<th>Status</th>
						<th>Activations</th>
						<th>Date</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $licenses as $l ) :
						$act_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}an_license_activations WHERE license_id = %d", $l->id ) );
						$sites = $wpdb->get_col( $wpdb->prepare( "SELECT site_url FROM {$wpdb->prefix}an_license_activations WHERE license_id = %d", $l->id ) );
					?>
						<tr>
							<td><code><?php echo esc_html($l->license_key); ?></code></td>
							<td><?php echo esc_html($l->user_email); ?></td>
							<td><?php echo strtoupper($l->tier); ?></td>
							<td>
								<span class="badge" style="background: <?php echo $l->status === 'active' ? '#46b450' : '#dc3232'; ?>; color:#fff; padding: 2px 8px; border-radius: 4px;">
									<?php echo esc_html(ucfirst($l->status)); ?>
								</span>
							</td>
							<td>
								<strong><?php echo $act_count; ?></strong>
								<?php if ($sites) : ?>
									<br><small><?php echo implode(', ', array_map('esc_html', $sites)); ?></small>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html($l->created_at); ?></td>
							<td>
								<?php if ($l->status === 'active') : ?>
									<a href="<?php echo wp_nonce_url('?page=an-store-licenses&action=suspend&id=' . $l->id, 'an_suspend_license_' . $l->id); ?>" style="color:orange;">Suspend</a>
								<?php else : ?>
									<a href="<?php echo wp_nonce_url('?page=an-store-licenses&action=unsuspend&id=' . $l->id, 'an_unsuspend_license_' . $l->id); ?>" style="color:green;">Unsuspend</a>
								<?php endif; ?>
								|
								<a href="<?php echo wp_nonce_url('?page=an-store-licenses&action=delete&id=' . $l->id, 'an_delete_license_' . $l->id); ?>" style="color:red;" onclick="return confirm('Delete this license and all its activations?')">Delete</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public function render_pricing_table() {
		$starter_price = get_option( 'an_starter_price', '0' );
		$pro_price = get_option( 'an_pro_price', '199' );
		$agency_price = get_option( 'an_agency_price', '999' );

		ob_start();
		?>
		<style>
			.an-pricing-container { display: flex; gap: 20px; justify-content: center; margin: 40px 0; font-family: sans-serif; }
			.an-pricing-card { border: 1px solid #ddd; border-radius: 12px; padding: 30px; width: 300px; text-align: center; transition: transform 0.3s; }
			.an-pricing-card:hover { transform: translateY(-10px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
			.an-pricing-card.featured { border: 2px solid #6366f1; }
			.an-price { font-size: 48px; font-weight: bold; margin: 20px 0; }
			.an-price span { font-size: 16px; color: #666; }
			.an-features { list-style: none; padding: 0; margin: 30px 0; text-align: left; }
			.an-features li { margin-bottom: 10px; }
			.an-buy-btn { display: block; background: #6366f1; color: #fff; text-decoration: none; padding: 15px; border-radius: 8px; font-weight: bold; }
			.an-buy-btn.free { background: #666; }
		</style>

		<div class="an-pricing-container">
			<div class="an-pricing-card">
				<h3>Starter</h3>
				<div class="an-price">$<?php echo esc_html($starter_price); ?><span>/forever</span></div>
				<ul class="an-features">
					<li>✓ Core Project Management</li>
					<li>✓ Client CRM</li>
					<li>✓ Unified Messaging</li>
					<li>✓ Community Support</li>
				</ul>
				<a href="https://wordpress.org/plugins/agency-nexus/" class="an-buy-btn free">Download Free</a>
			</div>

			<div class="an-pricing-card featured">
				<h3>Pro</h3>
				<div class="an-price">$<?php echo esc_html($pro_price); ?><span>/year</span></div>
				<ul class="an-features">
					<li>✓ Everything in Starter</li>
					<li>✓ MoneyFlow ROI Tracker</li>
					<li>✓ AutoPilot Automations</li>
					<li>✓ Lead Intelligence</li>
					<li>✓ Priority Support</li>
				</ul>
				<a href="?an_checkout=pro" class="an-buy-btn">Buy Pro Now</a>
			</div>

			<div class="an-pricing-card">
				<h3>Agency</h3>
				<div class="an-price">$<?php echo esc_html($agency_price); ?><span>/lifetime</span></div>
				<ul class="an-features">
					<li>✓ Everything in Pro</li>
					<li>✓ Full White-Labeling</li>
					<li>✓ Unlimited Sites</li>
					<li>✓ Dedicated Account Manager</li>
					<li>✓ Early Beta Access</li>
				</ul>
				<a href="?an_checkout=agency" class="an-buy-btn">Buy Agency Now</a>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}

function Agency_Nexus_Store() {
	return Agency_Nexus_Store::get_instance();
}

Agency_Nexus_Store();
