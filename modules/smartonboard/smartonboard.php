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
		add_action( 'admin_init', [ $this, 'handle_post' ] );
		add_action( 'wp_ajax_an_save_scope', [ $this, 'handle_save_scope' ] );
	}

	public function handle_post() {
		if ( ! is_admin() || ! Agency_Nexus_Permissions::is_team_member() ) {
			return;
		}

		if ( isset( $_GET['page'] ) && 'an-scope-settings' === $_GET['page'] ) {
			if ( isset( $_POST['an_save_scope_settings'] ) && check_admin_referer( 'an_scope_settings_nonce' ) ) {
				$services_json     = json_decode( stripslashes( $_POST['services_json'] ), true );
				$scales_json       = json_decode( stripslashes( $_POST['scales_json'] ), true );
				$deliverables_json = json_decode( stripslashes( $_POST['deliverables_json'] ), true );

				if ( is_array( $services_json ) && is_array( $scales_json ) && is_array( $deliverables_json ) ) {
					update_option( 'an_scope_services', $services_json );
					update_option( 'an_scope_scales', $scales_json );
					update_option( 'an_scope_deliverables', $deliverables_json );
					wp_redirect( admin_url( 'admin.php?page=an-scope-settings&msg=saved' ) );
					exit;
				} else {
					wp_redirect( admin_url( 'admin.php?page=an-scope-settings&msg=error' ) );
					exit;
				}
			}
		}
	}

	public function register_submenu() {
		if ( ! Agency_Nexus_License_Manager::get_instance()->is_feature_enabled( 'project_management' ) ) {
			return;
		}

		if ( Agency_Nexus_Permissions::is_admin() ) {
			add_submenu_page(
				'agency-nexus',
				__( 'Scope Builder', 'agency-nexus' ),
				__( 'Scope Builder', 'agency-nexus' ),
				'read',
				'an-scope-builder',
				[ $this, 'render_scope_builder' ]
			);

			add_submenu_page(
				'agency-nexus',
				__( 'Scope Settings', 'agency-nexus' ),
				__( 'Scope Settings', 'agency-nexus' ),
				'read',
				'an-scope-settings',
				[ $this, 'render_settings' ]
			);
		}
	}

	public function render_scope_builder() {
		global $wpdb;
		$authorised_ids = Agency_Nexus_Permissions::get_authorised_project_ids();
		$query = "SELECT id, name FROM {$wpdb->prefix}an_clients";
		if ( is_array( $authorised_ids ) ) {
			if ( empty( $authorised_ids ) ) {
				$query .= " WHERE 1=0";
			} else {
				$authorised_client_ids = $wpdb->get_col( "SELECT client_id FROM {$wpdb->prefix}an_projects WHERE id IN (" . implode( ',', array_map( 'intval', $authorised_ids ) ) . ")" );
				if ( ! empty( $authorised_client_ids ) ) {
					$query .= " WHERE id IN (" . implode( ',', array_map( 'intval', array_unique($authorised_client_ids) ) ) . ")";
				} else {
					$query .= " WHERE 1=0";
				}
			}
		}
		$clients = $wpdb->get_results( $query );

		$services = get_option( 'an_scope_services', [
			'seo' => 'SEO Strategy',
			'web_design' => 'Web Design',
			'social_media' => 'Social Media'
		] );
		$scales = get_option( 'an_scope_scales', [
			'small' => [ 'label' => 'Small', 'budget' => 1000 ],
			'medium' => [ 'label' => 'Medium', 'budget' => 5000 ],
			'large' => [ 'label' => 'Large', 'budget' => 15000 ]
		] );
		$deliverables = get_option( 'an_scope_deliverables', [
			'seo' => [ 'Keyword Report', 'Backlink Audit', 'On-page Optimization' ],
			'web_design' => [ 'Figma Mockups', 'WordPress Setup', 'Responsive Testing' ],
			'social_media' => [ 'Content Calendar', '30 Posts', 'Engagement Report' ]
		] );
		?>
		<div class="agency-nexus-wrap">
			<h1 class="wp-heading-inline"><?php _e( 'Interactive Scope Builder', 'agency-nexus' ); ?></h1>
			<a href="<?php echo admin_url('admin.php?page=an-scope-settings'); ?>" class="page-title-action"><?php _e('Configure Services & Scales', 'agency-nexus'); ?></a>
			<hr class="wp-header-end">
			<p class="description"><?php _e( 'The Scope Builder helps you standardize your service offerings. Select a client, a service type, and the project scale to automatically generate a detailed project plan and budget.', 'agency-nexus' ); ?></p>
			<p><strong><?php _e('Pro Tip:', 'agency-nexus'); ?></strong> <?php _e('You can customize the budgets and deliverables for each service type in the Settings page.', 'agency-nexus'); ?></p>
		</div>
		<?php
		$this->get_template( 'scope-builder', [
			'clients' => $clients,
			'services' => $services,
			'scales' => $scales,
			'deliverables' => $deliverables
		] );
	}

	public function render_settings() {
		if ( isset( $_GET['msg'] ) ) {
			if ( 'saved' === $_GET['msg'] ) {
				echo '<div class="updated"><p>' . __( 'Settings saved!', 'agency-nexus' ) . '</p></div>';
			} elseif ( 'error' === $_GET['msg'] ) {
				echo '<div class="error"><p>' . __( 'Invalid JSON format. Please check your settings.', 'agency-nexus' ) . '</p></div>';
			}
		}

		$services = get_option( 'an_scope_services', [
			'seo' => 'SEO Strategy',
			'web_design' => 'Web Design',
			'social_media' => 'Social Media'
		] );
		$scales = get_option( 'an_scope_scales', [
			'small' => [ 'label' => 'Small', 'budget' => 1000 ],
			'medium' => [ 'label' => 'Medium', 'budget' => 5000 ],
			'large' => [ 'label' => 'Large', 'budget' => 15000 ]
		] );
		$deliverables = get_option( 'an_scope_deliverables', [
			'seo' => [ 'Keyword Report', 'Backlink Audit', 'On-page Optimization' ],
			'web_design' => [ 'Figma Mockups', 'WordPress Setup', 'Responsive Testing' ],
			'social_media' => [ 'Content Calendar', '30 Posts', 'Engagement Report' ]
		] );

		?>
		<div class="agency-nexus-wrap">
			<h1><?php _e( 'Scope Builder Settings', 'agency-nexus' ); ?></h1>
			<p class="description"><?php _e( 'Define your service packages here using JSON. This data powers the interactive Scope Builder dropdowns.', 'agency-nexus' ); ?></p>
			<form method="post">
				<?php wp_nonce_field( 'an_scope_settings_nonce' ); ?>

				<h3><?php _e('Services', 'agency-nexus'); ?></h3>
				<p class="description"><?php _e('Format: "unique_key": "Display Label". e.g., "seo": "SEO Strategy"', 'agency-nexus'); ?></p>
				<textarea name="services_json" rows="5" class="large-text"><?php echo esc_textarea( json_encode( $services, JSON_PRETTY_PRINT ) ); ?></textarea>

				<h3><?php _e('Scales & Budgets', 'agency-nexus'); ?></h3>
				<p class="description"><?php _e('Define tiers like Small, Medium, Large with their associated budgets.', 'agency-nexus'); ?></p>
				<textarea name="scales_json" rows="8" class="large-text"><?php echo esc_textarea( json_encode( $scales, JSON_PRETTY_PRINT ) ); ?></textarea>

				<h3><?php _e('Deliverables by Service', 'agency-nexus'); ?></h3>
				<p class="description"><?php _e('List specific items included for each service type defined above.', 'agency-nexus'); ?></p>
				<textarea name="deliverables_json" rows="8" class="large-text"><?php echo esc_textarea( json_encode( $deliverables, JSON_PRETTY_PRINT ) ); ?></textarea>

				<p class="submit">
					<input type="submit" name="an_save_scope_settings" class="button button-primary" value="Save Settings">
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * AJAX handler to save scope as a project.
	 */
	public function handle_save_scope() {
		check_ajax_referer( 'an_scope_nonce', 'security' );

		if ( ! Agency_Nexus_Permissions::is_team_member() ) {
			wp_send_json_error( 'Unauthorized' );
		}

		global $wpdb;
		$client_id    = intval( $_POST['client_id'] );
		$service_type = sanitize_text_field( $_POST['service_type'] );
		$scale_key    = sanitize_text_field( $_POST['scale'] );
		$scales       = get_option( 'an_scope_scales', [] );
		$budget       = isset( $scales[ $scale_key ]['budget'] ) ? $scales[ $scale_key ]['budget'] : 0;
		$deliverables = isset( $_POST['deliverables'] ) ? (array) $_POST['deliverables'] : [];

		$description = __( 'Generated from Scope Builder.', 'agency-nexus' ) . "\n\n";
		if ( ! empty( $deliverables ) ) {
			$description .= __( 'Selected Deliverables:', 'agency-nexus' ) . "\n- " . implode( "\n- ", array_map( 'sanitize_text_field', $deliverables ) );
		}

		$wpdb->insert(
			$wpdb->prefix . 'an_projects',
			[
				'client_id'   => $client_id,
				'title'       => sprintf( '%s Project (%s)', ucfirst( $service_type ), ucfirst( $scale_key ) ),
				'description' => $description,
				'budget'      => $budget,
				'status'      => 'planned'
			]
		);

		wp_send_json_success( [ 'project_id' => $wpdb->insert_id ] );
	}

	public function render_dashboard_widget() {
		if ( ! Agency_Nexus_Permissions::is_team_member() ) {
			return;
		}
		?>
		<div class="postbox" style="padding: 20px;">
			<h2><?php _e( 'SmartOnboard', 'agency-nexus' ); ?></h2>
			<p><?php _e( 'Ready to onboard new clients? Use the Scope Builder to get started.', 'agency-nexus' ); ?></p>
			<a href="<?php echo admin_url( 'admin.php?page=an-scope-builder' ); ?>" class="button button-primary"><?php _e( 'Open Scope Builder', 'agency-nexus' ); ?></a>
		</div>
		<?php
	}
}
