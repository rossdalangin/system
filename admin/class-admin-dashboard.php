<?php
/**
 * Admin Dashboard Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Nexus_Admin_Dashboard {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
	}

	/**
	 * Register the main menu and submenus.
	 */
	public function register_menu() {
		add_menu_page(
			__( 'Agency Nexus', 'agency-nexus' ),
			__( 'Agency Nexus', 'agency-nexus' ),
			'manage_options',
			'agency-nexus',
			[ $this, 'render_dashboard' ],
			'dashicons-chart-area',
			30
		);

		add_submenu_page(
			'agency-nexus',
			__( 'Dashboard', 'agency-nexus' ),
			__( 'Dashboard', 'agency-nexus' ),
			'manage_options',
			'agency-nexus',
			[ $this, 'render_dashboard' ]
		);

		add_submenu_page(
			'agency-nexus',
			__( 'Clients', 'agency-nexus' ),
			__( 'Clients', 'agency-nexus' ),
			'manage_options',
			'an-clients',
			[ $this, 'render_clients' ]
		);

		// Modules will add their own submenus or sections.
	}

	/**
	 * Render the clients page.
	 */
	public function render_clients() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'an_clients';

		// Handle submission
		if ( isset( $_POST['an_add_client'] ) && check_admin_referer( 'an_add_client_nonce' ) ) {
			$wpdb->insert(
				$table_name,
				[
					'name'    => sanitize_text_field( $_POST['name'] ),
					'email'   => sanitize_email( $_POST['email'] ),
					'company' => sanitize_text_field( $_POST['company'] )
				]
			);
			echo '<div class="updated"><p>Client added!</p></div>';
		}

		$clients = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC" );
		?>
		<div class="wrap">
			<h1><?php _e( 'Client Management', 'agency-nexus' ); ?></h1>

			<div class="postbox" style="padding: 20px; margin-top: 20px;">
				<h2><?php _e( 'Add New Client', 'agency-nexus' ); ?></h2>
				<form method="post">
					<?php wp_nonce_field( 'an_add_client_nonce' ); ?>
					<table class="form-table">
						<tr>
							<th><label for="name">Name</label></th>
							<td><input type="text" name="name" id="name" class="regular-text" required></td>
						</tr>
						<tr>
							<th><label for="email">Email</label></th>
							<td><input type="email" name="email" id="email" class="regular-text" required></td>
						</tr>
						<tr>
							<th><label for="company">Company</label></th>
							<td><input type="text" name="company" id="company" class="regular-text"></td>
						</tr>
					</table>
					<p class="submit">
						<input type="submit" name="an_add_client" class="button button-primary" value="Add Client">
					</p>
				</form>
			</div>

			<h2><?php _e( 'Existing Clients', 'agency-nexus' ); ?></h2>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>ID</th>
						<th>Name</th>
						<th>Email</th>
						<th>Company</th>
						<th>Created</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( $clients ) : foreach ( $clients as $client ) : ?>
						<tr>
							<td><?php echo $client->id; ?></td>
							<td><?php echo esc_html( $client->name ); ?></td>
							<td><?php echo esc_html( $client->email ); ?></td>
							<td><?php echo esc_html( $client->company ); ?></td>
							<td><?php echo $client->created_at; ?></td>
						</tr>
					<?php endforeach; else : ?>
						<tr><td colspan="5">No clients found.</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render the main dashboard page.
	 */
	public function render_dashboard() {
		?>
		<div class="wrap">
			<h1><?php _e( 'Agency Nexus Dashboard', 'agency-nexus' ); ?></h1>
			<p><?php _e( 'Welcome to your comprehensive agency management dashboard.', 'agency-nexus' ); ?></p>

			<div class="agency-nexus-widgets" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
				<?php do_action( 'agency_nexus_dashboard_widgets' ); ?>
			</div>
		</div>
		<?php
	}
}
