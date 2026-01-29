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
			<h1><?php _e( 'Automation Center (AutoPilot)', 'agency-nexus' ); ?></h1>

			<div style="display: flex; gap: 20px; margin-top: 20px;">
				<div style="flex: 1;">
					<div class="postbox" style="padding: 20px;">
						<h3><?php _e( 'Custom Automation Builder (IFTTT)', 'agency-nexus' ); ?></h3>
						<form>
							<table class="form-table">
								<tr>
									<th>IF This Happens:</th>
									<td>
										<select class="regular-text">
											<option>Project status changes to 'Completed'</option>
											<option>New lead recorded in EngageTrack</option>
											<option>Content item approved in ApprovalFlow</option>
											<option>Invoice becomes overdue</option>
										</select>
									</td>
								</tr>
								<tr>
									<th>THEN Do This:</th>
									<td>
										<select class="regular-text">
											<option>Send email to Client</option>
											<option>Post message to Slack channel</option>
											<option>Trigger Zapier Webhook</option>
											<option>Create new task in 'Follow-up' project</option>
										</select>
									</td>
								</tr>
							</table>
							<button type="button" class="button button-primary" onclick="alert('Rule saved!')"><?php _e( 'Activate Rule', 'agency-nexus' ); ?></button>
						</form>
					</div>
				</div>

				<div style="flex: 1;">
					<div class="card" style="padding: 20px; background: #fff; border: 1px solid #ddd;">
						<h3><?php _e( 'Zapier/Make.com Integration', 'agency-nexus' ); ?></h3>
						<p><?php _e( 'Connect your agency workflow to 5000+ apps.', 'agency-nexus' ); ?></p>
						<table class="form-table">
							<tr>
								<th>Webhook URL</th>
								<td><input type="text" value="https://hooks.zapier.com/v1/..." class="large-text" readonly></td>
							</tr>
						</table>
						<p><label><input type="checkbox" checked> Enable Global Webhooks</label></p>
						<button class="button"><?php _e( 'Save Settings', 'agency-nexus' ); ?></button>
					</div>
				</div>
			</div>

			<h2 style="margin-top: 30px;">Active Workflows</h2>
			<table class="wp-list-table widefat fixed striped">
				<thead><tr><th>Workflow Name</th><th>Trigger</th><th>Action</th><th>Status</th></tr></thead>
				<tbody>
					<tr>
						<td>Client Welcome Sequence</td>
						<td>Project Created</td>
						<td>Email Client</td>
						<td><span style="color: green;">Active</span></td>
					</tr>
					<tr>
						<td>Slack Notifications</td>
						<td>Content Approved</td>
						<td>Slack Message</td>
						<td><span style="color: green;">Active</span></td>
					</tr>
				</tbody>
			</table>
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
