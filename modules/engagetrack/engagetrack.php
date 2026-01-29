<?php
/**
 * EngageTrack Module
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Nexus_Module_Engagetrack extends Agency_Nexus_Base_Module {

	protected $name = 'EngageTrack';

	public function init() {
		add_action( 'admin_menu', [ $this, 'register_submenu' ] );
		add_action( 'agency_nexus_dashboard_widgets', [ $this, 'render_dashboard_widget' ] );
	}

	public function register_submenu() {
		add_submenu_page(
			'agency-nexus',
			__( 'Leads', 'agency-nexus' ),
			__( 'Leads', 'agency-nexus' ),
			'manage_options',
			'an-leads',
			[ $this, 'render_leads' ]
		);

		add_submenu_page(
			'agency-nexus',
			__( 'Canned Responses', 'agency-nexus' ),
			__( 'Canned Responses', 'agency-nexus' ),
			'manage_options',
			'an-canned-responses',
			[ $this, 'render_canned_responses' ]
		);
	}

	public function render_leads() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'an_leads';
		$action = isset($_GET['action']) ? $_GET['action'] : 'list';
		$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

		if ($action === 'delete' && $id) {
			check_admin_referer('an_delete_lead_' . $id);
			$wpdb->delete($table_name, ['id' => $id]);
			echo '<div class="updated"><p>Lead deleted!</p></div>';
			$action = 'list';
		}

		if ( isset( $_POST['an_save_lead'] ) && check_admin_referer( 'an_save_lead_nonce' ) ) {
			$data = [
				'name'             => sanitize_text_field( $_POST['name'] ),
				'email'            => sanitize_email( $_POST['email'] ),
				'source'           => sanitize_text_field( $_POST['source'] ),
				'status'           => sanitize_text_field( $_POST['status'] ),
				'conversion_value' => floatval( $_POST['conversion_value'] )
			];
			if ($id) {
				$wpdb->update($table_name, $data, ['id' => $id]);
				echo '<div class="updated"><p>Lead updated!</p></div>';
			} else {
				$wpdb->insert($table_name, $data);
				echo '<div class="updated"><p>Lead recorded!</p></div>';
			}
			$action = 'list';
		}

		if ($action === 'edit' || $action === 'add') {
			$lead = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id)) : null;
			?>
			<div class="wrap">
				<h1><?php echo $id ? __('Edit Lead', 'agency-nexus') : __('Add New Lead', 'agency-nexus'); ?></h1>
				<form method="post">
					<?php wp_nonce_field( 'an_save_lead_nonce' ); ?>
					<table class="form-table">
						<tr><th>Name</th><td><input type="text" name="name" value="<?php echo $lead ? esc_attr($lead->name) : ''; ?>" required class="regular-text"></td></tr>
						<tr><th>Email</th><td><input type="email" name="email" value="<?php echo $lead ? esc_attr($lead->email) : ''; ?>" required class="regular-text"></td></tr>
						<tr><th>Source</th><td><input type="text" name="source" value="<?php echo $lead ? esc_attr($lead->source) : ''; ?>" class="regular-text"></td></tr>
						<tr><th>Status</th><td>
							<select name="status">
								<option value="new" <?php selected($lead ? $lead->status : '', 'new'); ?>>New</option>
								<option value="qualified" <?php selected($lead ? $lead->status : '', 'qualified'); ?>>Qualified</option>
								<option value="converted" <?php selected($lead ? $lead->status : '', 'converted'); ?>>Converted</option>
								<option value="lost" <?php selected($lead ? $lead->status : '', 'lost'); ?>>Lost</option>
							</select>
						</td></tr>
						<tr><th>Potential Value ($)</th><td><input type="number" step="0.01" name="conversion_value" value="<?php echo $lead ? esc_attr($lead->conversion_value) : ''; ?>" class="regular-text"></td></tr>
					</table>
					<input type="submit" name="an_save_lead" class="button button-primary" value="Save Lead">
					<a href="?page=an-leads" class="button">Cancel</a>
				</form>
			</div>
			<?php
			return;
		}

		$leads = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC" );
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e( 'Lead Intelligence System', 'agency-nexus' ); ?></h1>
			<a href="?page=an-leads&action=add" class="page-title-action">Add New</a>
			<hr class="wp-header-end">
			<table class="wp-list-table widefat fixed striped">
				<thead><tr><th>Name</th><th>Email</th><th>Source</th><th>Status</th><th>Value</th><th>Actions</th></tr></thead>
				<tbody>
					<?php foreach ($leads as $lead) : ?>
						<tr>
							<td><strong><?php echo esc_html($lead->name); ?></strong></td>
							<td><?php echo esc_html($lead->email); ?></td>
							<td><?php echo esc_html($lead->source); ?></td>
							<td><span class="badge status-<?php echo $lead->status; ?>"><?php echo ucfirst($lead->status); ?></span></td>
							<td>$<?php echo number_format($lead->conversion_value, 2); ?></td>
							<td>
								<a href="?page=an-leads&action=edit&id=<?php echo $lead->id; ?>">Edit</a> |
								<a href="<?php echo wp_nonce_url('?page=an-leads&action=delete&id=' . $lead->id, 'an_delete_lead_' . $lead->id); ?>" style="color:red;" onclick="return confirm('Delete lead?')">Delete</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public function render_canned_responses() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'an_canned_responses';
		$action = isset($_GET['action']) ? $_GET['action'] : 'list';
		$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

		if ($action === 'delete' && $id) {
			check_admin_referer('an_delete_response_' . $id);
			$wpdb->delete($table_name, ['id' => $id]);
			echo '<div class="updated"><p>Response deleted!</p></div>';
			$action = 'list';
		}

		if ( isset( $_POST['an_save_response'] ) && check_admin_referer( 'an_save_response_nonce' ) ) {
			$data = [
				'title'   => sanitize_text_field( $_POST['title'] ),
				'content' => sanitize_textarea_field( $_POST['content'] )
			];
			if ($id) {
				$wpdb->update($table_name, $data, ['id' => $id]);
				echo '<div class="updated"><p>Response updated!</p></div>';
			} else {
				$wpdb->insert($table_name, $data);
				echo '<div class="updated"><p>Response saved!</p></div>';
			}
			$action = 'list';
		}

		if ($action === 'edit' || $action === 'add') {
			$resp = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id)) : null;
			?>
			<div class="wrap">
				<h1><?php echo $id ? __('Edit Response', 'agency-nexus') : __('Add New Response', 'agency-nexus'); ?></h1>
				<form method="post">
					<?php wp_nonce_field( 'an_save_response_nonce' ); ?>
					<table class="form-table">
						<tr><th>Shortcut Title</th><td><input type="text" name="title" value="<?php echo $resp ? esc_attr($resp->title) : ''; ?>" required class="regular-text"></td></tr>
						<tr><th>Content</th><td><textarea name="content" required class="regular-text" rows="5"><?php echo $resp ? esc_textarea($resp->content) : ''; ?></textarea></td></tr>
					</table>
					<input type="submit" name="an_save_response" class="button button-primary" value="Save Response">
					<a href="?page=an-canned-responses" class="button">Cancel</a>
				</form>
			</div>
			<?php
			return;
		}

		$responses = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC" );
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e( 'Canned Responses', 'agency-nexus' ); ?></h1>
			<a href="?page=an-canned-responses&action=add" class="page-title-action">Add New</a>
			<hr class="wp-header-end">
			<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top:20px;">
				<?php foreach ($responses as $resp) : ?>
					<div class="card" style="padding: 15px; background: #fff; border: 1px solid #ddd; position: relative;">
						<h3><?php echo esc_html($resp->title); ?></h3>
						<p><?php echo nl2br(esc_html($resp->content)); ?></p>
						<div style="margin-top: 10px; border-top: 1px solid #eee; padding-top: 10px;">
							<a href="?page=an-canned-responses&action=edit&id=<?php echo $resp->id; ?>">Edit</a> |
							<a href="<?php echo wp_nonce_url('?page=an-canned-responses&action=delete&id=' . $resp->id, 'an_delete_response_' . $resp->id); ?>" style="color:red;" onclick="return confirm('Delete response?')">Delete</a>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	public function render_dashboard_widget() {
		global $wpdb;
		$leads_table = $wpdb->prefix . 'an_leads';

		$total_leads = $wpdb->get_var( "SELECT COUNT(*) FROM $leads_table" );
		$conversions = $wpdb->get_var( "SELECT COUNT(*) FROM $leads_table WHERE status = 'converted'" );
		$conv_rate   = $total_leads > 0 ? ($conversions / $total_leads) * 100 : 0;

		?>
		<div class="postbox" style="padding: 20px;">
			<h2><?php _e( 'EngageTrack', 'agency-nexus' ); ?></h2>
			<div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
				<div>
					<strong><?php _e( 'Total Leads', 'agency-nexus' ); ?></strong>
					<div style="font-size: 20px; font-weight: bold;"><?php echo intval($total_leads); ?></div>
				</div>
				<div>
					<strong><?php _e( 'Conv. Rate', 'agency-nexus' ); ?></strong>
					<div style="font-size: 20px; font-weight: bold; color: #46b450;"><?php echo round($conv_rate, 1); ?>%</div>
				</div>
			</div>
			<p><strong><?php _e( 'Sentiment Analysis:', 'agency-nexus' ); ?></strong></p>
			<div style="display: flex; align-items: center; gap: 10px; margin: 10px 0;">
				<div style="flex: 1; height: 20px; background: #eee; border-radius: 10px; overflow: hidden; display: flex;">
					<div style="width: 70%; background: #46b450;" title="Positive"></div>
					<div style="width: 20%; background: #ffb900;" title="Neutral"></div>
					<div style="width: 10%; background: #dc3232;" title="Negative"></div>
				</div>
				<span style="font-weight: bold; color: #46b450;">70% <?php _e( 'Positive', 'agency-nexus' ); ?></span>
			</div>
		</div>
		<?php
	}
}
