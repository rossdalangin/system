<?php
/**
 * AutoPilot Module
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Nexus_Module_Autopilot extends Agency_Nexus_Base_Module {

	protected $name = 'AutoPilot';

	public function init() {
		add_action( 'admin_menu', [ $this, 'register_submenu' ] );
		add_action( 'agency_nexus_dashboard_widgets', [ $this, 'render_dashboard_widget' ] );
	}

	public function register_submenu() {
		add_submenu_page(
			'agency-nexus',
			__( 'Automations', 'agency-nexus' ),
			__( 'Automations', 'agency-nexus' ),
			'manage_options',
			'an-automations',
			[ $this, 'render_automations' ]
		);
	}

	public function render_automations() {
		?>
		<div class="wrap">
			<h1><?php _e( 'Automation Center', 'agency-nexus' ); ?></h1>
			<div class="card" style="max-width: 600px; margin-top: 20px; padding: 20px;">
				<h3><?php _e( 'Zapier/Make Integration', 'agency-nexus' ); ?></h3>
				<p><?php _e( 'Connect your agency workflow to 5000+ apps.', 'agency-nexus' ); ?></p>
				<table class="form-table">
					<tr>
						<th>Webhook URL</th>
						<td><input type="text" value="https://hooks.zapier.com/v1/..." class="large-text" readonly></td>
					</tr>
					<tr>
						<th>Events to Trigger</th>
						<td>
							<label><input type="checkbox" checked> Project Created</label><br>
							<label><input type="checkbox" checked> Payment Received</label><br>
							<label><input type="checkbox"> Lead Interaction</label>
						</td>
					</tr>
				</table>
				<button class="button button-primary"><?php _e( 'Save Integration', 'agency-nexus' ); ?></button>
			</div>
		</div>
		<?php
	}

	public function render_dashboard_widget() {
		?>
		<div class="postbox" style="padding: 20px;">
			<h2><?php _e( 'AutoPilot', 'agency-nexus' ); ?></h2>
			<p><?php _e( '12 automations ran successfully in the last 24 hours.', 'agency-nexus' ); ?></p>
			<a href="<?php echo admin_url( 'admin.php?page=an-automations' ); ?>" class="button"><?php _e( 'Manage Automations', 'agency-nexus' ); ?></a>
		</div>
		<?php
	}
}
