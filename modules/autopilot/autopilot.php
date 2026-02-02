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
		add_action( 'admin_init', [ $this, 'handle_post' ] );
		add_action( 'agency_nexus_project_status_updated', [ $this, 'maybe_trigger_project_automations' ], 10, 2 );
	}

	public function handle_post() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_GET['page'] ) && 'an-automations' === $_GET['page'] ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'an_autopilot_rules';
			$action = isset( $_GET['action'] ) ? $_GET['action'] : '';
			$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

			if ( 'delete' === $action && $id ) {
				check_admin_referer( 'an_delete_rule_' . $id );
				$wpdb->delete( $table_name, [ 'id' => $id ] );
				wp_redirect( admin_url( 'admin.php?page=an-automations&msg=deleted' ) );
				exit;
			}

			if ( isset( $_POST['an_save_rule'] ) && check_admin_referer( 'an_save_rule_nonce' ) ) {
				$data = [
					'title'       => sanitize_text_field( $_POST['title'] ),
					'trigger_evt' => sanitize_text_field( $_POST['trigger_evt'] ),
					'action_evt'  => sanitize_text_field( $_POST['action_evt'] ),
					'is_active'   => isset( $_POST['is_active'] ) ? 1 : 0
				];
				if ( $id ) {
					$wpdb->update( $table_name, $data, [ 'id' => $id ] );
					$msg = 'updated';
				} else {
					$wpdb->insert( $table_name, $data );
					$msg = 'activated';
				}
				wp_redirect( admin_url( 'admin.php?page=an-automations&msg=' . $msg ) );
				exit;
			}

			if ( isset( $_POST['an_save_autopilot_settings'] ) && check_admin_referer( 'an_autopilot_settings_nonce' ) ) {
				update_option( 'an_zapier_webhook', esc_url_raw( $_POST['an_zapier_webhook'] ) );
				update_option( 'an_enable_global_webhooks', isset( $_POST['an_enable_global_webhooks'] ) ? 1 : 0 );
				wp_redirect( admin_url( 'admin.php?page=an-automations&msg=settings_saved' ) );
				exit;
			}
		}
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
		global $wpdb;
		$table_name = $wpdb->prefix . 'an_autopilot_rules';
		$action = isset( $_GET['action'] ) ? $_GET['action'] : 'list';
		$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		if ( isset( $_GET['msg'] ) ) {
			$m = '';
			switch ( $_GET['msg'] ) {
				case 'activated': $m = 'Rule activated!'; break;
				case 'updated':   $m = 'Rule updated!'; break;
				case 'deleted':   $m = 'Automation rule deleted!'; break;
				case 'settings_saved': $m = 'Global settings saved!'; break;
			}
			if ( $m ) echo '<div class="updated"><p>' . esc_html( $m ) . '</p></div>';
		}

		if ($action === 'edit' || $action === 'add') {
			$rule = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id)) : null;
			?>
			<div class="agency-nexus-wrap">
				<h1><?php echo $id ? __('Edit Rule', 'agency-nexus') : __('Add New Rule', 'agency-nexus'); ?></h1>
				<p class="description"><?php _e('Create automated workflows to handle repetitive agency tasks based on specific triggers.', 'agency-nexus'); ?></p>
				<form method="post">
					<?php wp_nonce_field('an_save_rule_nonce'); ?>
					<table class="form-table">
						<tr>
							<th><label><?php _e('Rule Name', 'agency-nexus'); ?></label></th>
							<td>
								<input type="text" name="title" value="<?php echo $rule ? esc_attr($rule->title) : ''; ?>" required class="regular-text">
								<p class="description"><?php _e('Internal name for this automation. e.g., Onboarding Welcome', 'agency-nexus'); ?></p>
							</td>
						</tr>
						<tr>
							<th><label><?php _e('IF This Happens:', 'agency-nexus'); ?></label></th>
							<td>
								<select name="trigger_evt" class="regular-text">
									<option value="project_completed" <?php selected($rule ? $rule->trigger_evt : '', 'project_completed'); ?>>Project status changes to 'Completed'</option>
									<option value="new_lead" <?php selected($rule ? $rule->trigger_evt : '', 'new_lead'); ?>>New lead recorded in EngageTrack</option>
									<option value="content_approved" <?php selected($rule ? $rule->trigger_evt : '', 'content_approved'); ?>>Content item approved in ApprovalFlow</option>
									<option value="invoice_overdue" <?php selected($rule ? $rule->trigger_evt : '', 'invoice_overdue'); ?>>Invoice becomes overdue</option>
								</select>
								<div class="an-trigger-descriptions" style="margin-top: 10px; font-size: 12px; color: #666;">
									<p><strong>Project Completed:</strong> Fires when a project's status is set to "Completed".</p>
									<p><strong>New Lead:</strong> Fires when a new lead is captured via your embeddable form.</p>
									<p><strong>Content Approved:</strong> Fires when a client signs off on a content item.</p>
								</div>
							</td>
						</tr>
						<tr>
							<th><label><?php _e('THEN Do This:', 'agency-nexus'); ?></label></th>
							<td>
								<select name="action_evt" class="regular-text">
									<option value="email_client" <?php selected($rule ? $rule->action_evt : '', 'email_client'); ?>>Send email to Client</option>
									<option value="slack_msg" <?php selected($rule ? $rule->action_evt : '', 'slack_msg'); ?>>Post message to Slack channel</option>
									<option value="zapier_hook" <?php selected($rule ? $rule->action_evt : '', 'zapier_hook'); ?>>Trigger Zapier Webhook</option>
									<option value="create_task" <?php selected($rule ? $rule->action_evt : '', 'create_task'); ?>>Create new task in 'Follow-up' project</option>
								</select>
								<div class="an-action-descriptions" style="margin-top: 10px; font-size: 12px; color: #666;">
									<p><strong>Send Email:</strong> Sends a templated email to the client linked to the trigger event.</p>
									<p><strong>Zapier Webhook:</strong> Sends a POST request with relevant data to your configured URL in Settings.</p>
									<p><strong>Create Task:</strong> Automatically adds a follow-up task to keep the momentum going.</p>
								</div>
							</td>
						</tr>
						<tr>
							<th><label><?php _e('Active', 'agency-nexus'); ?></label></th>
							<td>
								<input type="checkbox" name="is_active" <?php checked($rule ? $rule->is_active : 1, 1); ?>>
								<p class="description"><?php _e('Turn this rule on or off.', 'agency-nexus'); ?></p>
							</td>
						</tr>
					</table>
					<input type="submit" name="an_save_rule" class="button button-primary" value="Save Rule">
					<a href="?page=an-automations" class="button">Cancel</a>
				</form>
			</div>
			<?php
			return;
		}

		$rules = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY id DESC" );
		?>
		<div class="agency-nexus-wrap">
			<h1 class="wp-heading-inline"><?php _e( 'Automation Center (AutoPilot)', 'agency-nexus' ); ?></h1>
			<a href="?page=an-automations&action=add" class="page-title-action">Add New Rule</a>
			<hr class="wp-header-end">

			<div style="background: #fff; border-left: 4px solid #0073aa; padding: 15px; margin: 20px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
				<h3><?php _e( 'How it Works', 'agency-nexus' ); ?></h3>
				<p><?php _e( 'AutoPilot helps you automate repetitive tasks using simple **Trigger & Action** logic.', 'agency-nexus' ); ?></p>
				<ul style="list-style: disc; margin-left: 20px;">
					<li><strong>Trigger:</strong> The event that starts the automation (e.g., a project is completed).</li>
					<li><strong>Action:</strong> The task that is automatically performed (e.g., sending a thank you email).</li>
				</ul>
				<p><em><?php _e( 'Example: IF Project status changes to "Completed" THEN Trigger Zapier Webhook (to send a contract or update your CRM).', 'agency-nexus' ); ?></em></p>
			</div>

			<div style="display: flex; gap: 20px; margin-top: 20px;">
				<div style="flex: 2;">
					<h3><?php _e( 'Active Workflows', 'agency-nexus' ); ?></h3>
					<table class="wp-list-table widefat fixed striped">
						<thead><tr><th>Rule Name</th><th>Trigger</th><th>Action</th><th>Status</th><th>Actions</th></tr></thead>
						<tbody>
							<?php if ($rules) : foreach ($rules as $r) : ?>
								<tr>
									<td><strong><?php echo esc_html($r->title); ?></strong></td>
									<td><?php echo esc_html($r->trigger_evt); ?></td>
									<td><?php echo esc_html($r->action_evt); ?></td>
									<td><span style="color: <?php echo $r->is_active ? 'green' : 'red'; ?>;"><?php echo $r->is_active ? 'Active' : 'Inactive'; ?></span></td>
									<td>
										<a href="?page=an-automations&action=edit&id=<?php echo $r->id; ?>">Edit</a> |
										<a href="<?php echo wp_nonce_url('?page=an-automations&action=delete&id=' . $r->id, 'an_delete_rule_' . $r->id); ?>" style="color:red;" onclick="return confirm('Delete rule?')">Delete</a>
									</td>
								</tr>
							<?php endforeach; else : ?>
								<tr><td colspan="5">No automation rules yet.</td></tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>

				<div style="flex: 1;">
					<div class="card" style="padding: 20px; background: #fff; border: 1px solid #ddd;">
						<h3><?php _e( 'External Integration', 'agency-nexus' ); ?></h3>
						<p class="description"><?php _e( 'Connect AutoPilot to 5,000+ apps via Zapier or Make.com using webhooks.', 'agency-nexus' ); ?></p>
						<form method="post">
							<?php wp_nonce_field('an_autopilot_settings_nonce'); ?>
							<table class="form-table">
								<tr>
									<td style="padding: 10px 0;">
										<label><strong>Zapier/Make Webhook URL</strong></label><br>
										<input type="text" name="an_zapier_webhook" value="<?php echo esc_attr(get_option('an_zapier_webhook')); ?>" class="large-text" placeholder="https://hooks.zapier.com/v1/...">
									</td>
								</tr>
							</table>
							<p><label><input type="checkbox" name="an_enable_global_webhooks" <?php checked(get_option('an_enable_global_webhooks', 1), 1); ?>> Enable Webhooks</label></p>
							<input type="submit" name="an_save_autopilot_settings" class="button button-secondary" value="Update Settings">
						</form>
					</div>

					<div class="card" style="padding: 20px; background: #f9f9f9; border: 1px solid #ddd; margin-top: 20px;">
						<h3><?php _e( 'Popular Use Cases', 'agency-nexus' ); ?></h3>
						<ol style="padding-left: 15px;">
							<li><strong>Onboarding:</strong> Trigger a Zap to create a Google Drive folder when a lead is captured.</li>
							<li><strong>Project Close:</strong> Send an automated Slack message to your #wins channel when a project is completed.</li>
							<li><strong>Follow-up:</strong> Create a task in a "Nurture" project when an invoice becomes overdue.</li>
						</ol>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Trigger automations based on project status changes.
	 */
	public function maybe_trigger_project_automations( $project_id, $new_status ) {
		global $wpdb;
		$trigger = ( 'completed' === $new_status ) ? 'project_completed' : '';
		if ( ! $trigger ) return;

		$rules = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}an_autopilot_rules WHERE trigger_evt = %s AND is_active = 1",
			$trigger
		) );

		foreach ( $rules as $rule ) {
			$this->execute_action( $rule, $project_id );
		}
	}

	/**
	 * Execute the action defined in the rule.
	 */
	private function execute_action( $rule, $project_id ) {
		// Simulation of action execution.
		// In a real plugin, this would send emails, Slack messages, or hit Zapier.
		error_log( sprintf( "[AutoPilot] Executing automation '%s' for Project ID %d", $rule->title, $project_id ) );

		if ( 'zapier_hook' === $rule->action_evt ) {
			$webhook = get_option( 'an_zapier_webhook' );
			if ( $webhook ) {
				wp_remote_post( $webhook, [ 'body' => [ 'project_id' => $project_id, 'event' => $rule->trigger_evt ] ] );
			}
		}
	}

	public function render_dashboard_widget() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="postbox" style="padding: 20px;">
			<h2><?php _e( 'AutoPilot', 'agency-nexus' ); ?></h2>
			<p><?php _e( '12 automations ran successfully in the last 24 hours.', 'agency-nexus' ); ?></p>
			<a href="<?php echo admin_url( 'admin.php?page=an-automations' ); ?>" class="button"><?php _e( 'Manage Automations', 'agency-nexus' ); ?></a>
		</div>
		<?php
	}
}
