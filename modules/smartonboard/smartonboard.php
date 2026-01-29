<?php
/**
 * SmartOnboard Module
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Nexus_Module_Smartonboard extends Agency_Nexus_Base_Module {

	protected $name = 'SmartOnboard';

	public function init() {
		add_action( 'admin_menu', [ $this, 'register_submenu' ] );
		add_action( 'agency_nexus_dashboard_widgets', [ $this, 'render_dashboard_widget' ] );
		add_action( 'wp_ajax_an_save_scope', [ $this, 'handle_save_scope' ] );
	}

	public function register_submenu() {
		add_submenu_page(
			'agency-nexus',
			__( 'Scope Builder', 'agency-nexus' ),
			__( 'Scope Builder', 'agency-nexus' ),
			'manage_options',
			'an-scope-builder',
			[ $this, 'render_scope_builder' ]
		);
	}

	public function render_scope_builder() {
		// Ensure we have some clients for the dropdown
		global $wpdb;
		$clients = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}an_clients" );
		$this->get_template( 'scope-builder', [ 'clients' => $clients ] );
	}

	/**
	 * AJAX handler to save scope as a project.
	 */
	public function handle_save_scope() {
		check_ajax_referer( 'an_scope_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		global $wpdb;
		$client_id    = intval( $_POST['client_id'] );
		$service_type = sanitize_text_field( $_POST['service_type'] );
		$scale        = sanitize_text_field( $_POST['scale'] );
		$budget       = 0;

		switch ( $scale ) {
			case 'small': $budget = 1000; break;
			case 'medium': $budget = 5000; break;
			case 'large': $budget = 15000; break;
		}

		$wpdb->insert(
			$wpdb->prefix . 'an_projects',
			[
				'client_id'   => $client_id,
				'title'       => sprintf( '%s Project (%s)', ucfirst( $service_type ), ucfirst( $scale ) ),
				'description' => 'Generated from Scope Builder',
				'budget'      => $budget,
				'status'      => 'planned'
			]
		);

		wp_send_json_success( [ 'project_id' => $wpdb->insert_id ] );
	}

	public function render_dashboard_widget() {
		?>
		<div class="postbox" style="padding: 20px;">
			<h2><?php _e( 'SmartOnboard', 'agency-nexus' ); ?></h2>
			<p><?php _e( 'Ready to onboard new clients? Use the Scope Builder to get started.', 'agency-nexus' ); ?></p>
			<a href="<?php echo admin_url( 'admin.php?page=an-scope-builder' ); ?>" class="button button-primary"><?php _e( 'Open Scope Builder', 'agency-nexus' ); ?></a>
		</div>
		<?php
	}
}
