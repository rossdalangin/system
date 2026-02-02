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

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_licenses );
		dbDelta( $sql_activations );
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
	}

	public function render_settings() {
		if ( isset( $_POST['an_save_store_settings'] ) ) {
			update_option( 'an_pro_price', sanitize_text_field( $_POST['an_pro_price'] ) );
			update_option( 'an_agency_price', sanitize_text_field( $_POST['an_agency_price'] ) );
			update_option( 'an_download_url', esc_url_raw( $_POST['an_download_url'] ) );
			echo '<div class="updated"><p>Settings saved!</p></div>';
		}

		$pro_price = get_option( 'an_pro_price', '199' );
		$agency_price = get_option( 'an_agency_price', '999' );
		$download_url = get_option( 'an_download_url', '' );
		?>
		<div class="wrap">
			<h1>Agency Nexus Store Settings</h1>
			<form method="post">
				<table class="form-table">
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
				<input type="submit" name="an_save_store_settings" class="button button-primary" value="Save Settings">
			</form>
		</div>
		<?php
	}

	public function render_licenses_list() {
		global $wpdb;
		$licenses = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}an_issued_licenses ORDER BY created_at DESC" );
		?>
		<div class="wrap">
			<h1>Issued Licenses</h1>
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
							<td><?php echo esc_html($l->status); ?></td>
							<td>
								<strong><?php echo $act_count; ?></strong>
								<?php if ($sites) : ?>
									<br><small><?php echo implode(', ', array_map('esc_html', $sites)); ?></small>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html($l->created_at); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public function render_pricing_table() {
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
				<div class="an-price">$0<span>/forever</span></div>
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
