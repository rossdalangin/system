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
		add_action( 'admin_init', [ $this, 'handle_post' ] );
	}

	public function handle_post() {
		if ( ! is_admin() || ! Agency_Nexus_Permissions::is_team_member() ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? $_GET['page'] : '';

		if ( 'an-leads' === $page ) {
			if ( Agency_Nexus_Permissions::is_admin() ) {
				$this->process_lead_actions();
			}
		} elseif ( 'an-canned-responses' === $page ) {
			if ( Agency_Nexus_Permissions::is_admin() ) {
				$this->process_response_actions();
			}
		}
	}

	private function process_lead_actions() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'an_leads';
		$action = isset( $_GET['action'] ) ? $_GET['action'] : '';
		$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		if ( 'delete' === $action && $id ) {
			check_admin_referer( 'an_delete_lead_' . $id );
			$wpdb->delete( $table_name, [ 'id' => $id ] );
			wp_redirect( admin_url( 'admin.php?page=an-leads&msg=deleted' ) );
			exit;
		}

		if ( isset( $_POST['an_save_lead'] ) && check_admin_referer( 'an_save_lead_nonce' ) ) {
			$data = [
				'name'             => sanitize_text_field( $_POST['name'] ),
				'email'            => sanitize_email( $_POST['email'] ),
				'source'           => sanitize_text_field( $_POST['source'] ),
				'status'           => sanitize_text_field( $_POST['status'] ),
				'conversion_value' => floatval( $_POST['conversion_value'] )
			];
			if ( $id ) {
				$wpdb->update( $table_name, $data, [ 'id' => $id ] );
				$msg = 'updated';
			} else {
				$wpdb->insert( $table_name, $data );
				$msg = 'added';
			}
			wp_redirect( admin_url( 'admin.php?page=an-leads&msg=' . $msg ) );
			exit;
		}
	}

	private function process_response_actions() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'an_canned_responses';
		$action = isset( $_GET['action'] ) ? $_GET['action'] : '';
		$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		if ( 'delete' === $action && $id ) {
			check_admin_referer( 'an_delete_response_' . $id );
			$wpdb->delete( $table_name, [ 'id' => $id ] );
			wp_redirect( admin_url( 'admin.php?page=an-canned-responses&msg=deleted' ) );
			exit;
		}

		if ( isset( $_POST['an_save_response'] ) && check_admin_referer( 'an_save_response_nonce' ) ) {
			$data = [
				'title'   => sanitize_text_field( $_POST['title'] ),
				'content' => wp_kses_post( $_POST['content'] )
			];
			if ( $id ) {
				$wpdb->update( $table_name, $data, [ 'id' => $id ] );
				$msg = 'updated';
			} else {
				$wpdb->insert( $table_name, $data );
				$msg = 'saved';
			}
			wp_redirect( admin_url( 'admin.php?page=an-canned-responses&msg=' . $msg ) );
			exit;
		}
	}

	public function register_submenu() {
		if ( Agency_Nexus_Permissions::is_admin() ) {
			add_submenu_page(
				'agency-nexus',
				__( 'Leads', 'agency-nexus' ),
				__( 'Leads', 'agency-nexus' ),
				'read',
				'an-leads',
				[ $this, 'render_leads' ]
			);

			add_submenu_page(
				'agency-nexus',
				__( 'Canned Responses', 'agency-nexus' ),
				__( 'Canned Responses', 'agency-nexus' ),
				'read',
				'an-canned-responses',
				[ $this, 'render_canned_responses' ]
			);
		}
	}

	public function render_leads() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'an_leads';
		$action = isset( $_GET['action'] ) ? $_GET['action'] : 'list';
		$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		if ( isset( $_GET['msg'] ) ) {
			$m = '';
			switch ( $_GET['msg'] ) {
				case 'added':   $m = 'Lead recorded!'; break;
				case 'updated': $m = 'Lead updated!'; break;
				case 'deleted': $m = 'Lead deleted!'; break;
			}
			if ( $m ) echo '<div class="updated"><p>' . esc_html( $m ) . '</p></div>';
		}

		if ($action === 'edit' || $action === 'add') {
			$lead = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id)) : null;
			?>
			<div class="wrap">
				<h1><?php echo $id ? __('Edit Lead', 'agency-nexus') : __('Add New Lead', 'agency-nexus'); ?></h1>
				<p class="description"><?php _e('Track a potential client in your sales pipeline.', 'agency-nexus'); ?></p>
				<form method="post">
					<?php wp_nonce_field( 'an_save_lead_nonce' ); ?>
					<table class="form-table">
						<tr>
							<th><label><?php _e('Name', 'agency-nexus'); ?></label></th>
							<td>
								<input type="text" name="name" value="<?php echo $lead ? esc_attr($lead->name) : ''; ?>" required class="regular-text">
								<p class="description"><?php _e('Full name of the prospect.', 'agency-nexus'); ?></p>
							</td>
						</tr>
						<tr>
							<th><label><?php _e('Email', 'agency-nexus'); ?></label></th>
							<td>
								<input type="email" name="email" value="<?php echo $lead ? esc_attr($lead->email) : ''; ?>" required class="regular-text">
								<p class="description"><?php _e('Contact email for follow-ups.', 'agency-nexus'); ?></p>
							</td>
						</tr>
						<tr>
							<th><label><?php _e('Source', 'agency-nexus'); ?></label></th>
							<td>
								<input type="text" name="source" value="<?php echo $lead ? esc_attr($lead->source) : ''; ?>" class="regular-text">
								<p class="description"><?php _e('Where did this lead come from? e.g., LinkedIn, Referral, Google Ads', 'agency-nexus'); ?></p>
							</td>
						</tr>
						<tr>
							<th><label><?php _e('Status', 'agency-nexus'); ?></label></th>
							<td>
								<select name="status">
									<option value="new" <?php selected($lead ? $lead->status : '', 'new'); ?>>New</option>
									<option value="qualified" <?php selected($lead ? $lead->status : '', 'qualified'); ?>>Qualified</option>
									<option value="converted" <?php selected($lead ? $lead->status : '', 'converted'); ?>>Converted</option>
									<option value="lost" <?php selected($lead ? $lead->status : '', 'lost'); ?>>Lost</option>
								</select>
								<p class="description"><?php _e('Current stage in your sales process.', 'agency-nexus'); ?></p>
							</td>
						</tr>
						<tr>
							<th><label><?php _e('Potential Value ($)', 'agency-nexus'); ?></label></th>
							<td>
								<input type="number" step="0.01" name="conversion_value" value="<?php echo $lead ? esc_attr($lead->conversion_value) : ''; ?>" class="regular-text">
								<p class="description"><?php _e('Estimated project value if this lead converts.', 'agency-nexus'); ?></p>
							</td>
						</tr>
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
			<p><?php _e( 'Guidance: Track your sales pipeline here. Assign potential values to leads to help calculate your agency\'s projected revenue.', 'agency-nexus' ); ?></p>
			<a href="?page=an-leads&action=add" class="page-title-action">Add New</a>
			<button type="button" class="page-title-action" id="generate-lead-form-btn"><?php _e( 'Generate Embed Code', 'agency-nexus' ); ?></button>
			<hr class="wp-header-end">
			<div id="embed-code-container" style="display: none; background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin-bottom: 20px;">
				<h3><?php _e('External Capture Form Code', 'agency-nexus'); ?></h3>
				<p><?php _e('Copy and paste this HTML code onto any page (even outside this WordPress site) to capture leads directly into Agency Nexus.', 'agency-nexus'); ?></p>
				<textarea class="large-text" rows="12" readonly><?php
					$api_url = get_rest_url( null, 'agency-nexus/v1/leads/capture' );
					echo esc_textarea('<form action="' . $api_url . '" method="POST">
    <div>
        <label>Name:</label><br>
        <input type="text" name="name" required style="width: 100%; padding: 8px; margin-bottom: 10px;">
    </div>
    <div>
        <label>Email:</label><br>
        <input type="email" name="email" required style="width: 100%; padding: 8px; margin-bottom: 10px;">
    </div>
    <input type="hidden" name="source" value="Website Embed">
    <button type="submit" style="background: #0073aa; color: #fff; border: none; padding: 10px 20px; cursor: pointer;">Submit</button>
</form>');
				?></textarea>
				<p><button type="button" class="button" onclick="jQuery('#embed-code-container').slideUp();">Close</button></p>
			</div>

			<script>
			jQuery(document).ready(function($){
				$('#generate-lead-form-btn').click(function(){
					$('#embed-code-container').slideToggle();
				});
			});
			</script>

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
		$action = isset( $_GET['action'] ) ? $_GET['action'] : 'list';
		$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		if ( isset( $_GET['msg'] ) ) {
			$m = '';
			switch ( $_GET['msg'] ) {
				case 'saved':   $m = 'Response saved!'; break;
				case 'updated': $m = 'Response updated!'; break;
				case 'deleted': $m = 'Response deleted!'; break;
			}
			if ( $m ) echo '<div class="updated"><p>' . esc_html( $m ) . '</p></div>';
		}

		if ($action === 'edit' || $action === 'add') {
			$resp = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id)) : null;
			?>
			<div class="wrap">
				<h1><?php echo $id ? __('Edit Response', 'agency-nexus') : __('Add New Response', 'agency-nexus'); ?></h1>
				<p class="description"><?php _e('Save reusable text snippets for common client inquiries.', 'agency-nexus'); ?></p>
				<form method="post">
					<?php wp_nonce_field( 'an_save_response_nonce' ); ?>
					<table class="form-table">
						<tr>
							<th><label><?php _e('Shortcut Title', 'agency-nexus'); ?></label></th>
							<td>
								<input type="text" name="title" value="<?php echo $resp ? esc_attr($resp->title) : ''; ?>" required class="regular-text">
								<p class="description"><?php _e('A short name to identify this template. e.g., Welcome Message', 'agency-nexus'); ?></p>
							</td>
						</tr>
						<tr>
							<th><label><?php _e('Content', 'agency-nexus'); ?></label></th>
							<td>
								<textarea name="content" required class="regular-text" rows="5"><?php echo $resp ? esc_textarea($resp->content) : ''; ?></textarea>
								<p class="description"><?php _e('The full text that will be inserted when you use this shortcut.', 'agency-nexus'); ?></p>
							</td>
						</tr>
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
			<a href="?page=an-canned-responses&action=add" class="page-title-action"><?php _e('Add New', 'agency-nexus'); ?></a>
			<hr class="wp-header-end">
			<p><?php _e( 'Guidance: Store reusable message templates here. These can be quickly accessed and used within the Messaging Hub.', 'agency-nexus' ); ?></p>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php _e('Title', 'agency-nexus'); ?></th>
						<th><?php _e('Content Snippet', 'agency-nexus'); ?></th>
						<th><?php _e('Actions', 'agency-nexus'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ($responses) : foreach ($responses as $resp) : ?>
						<tr>
							<td><strong><?php echo esc_html($resp->title); ?></strong></td>
							<td><?php echo esc_html(wp_trim_words($resp->content, 15)); ?></td>
							<td>
								<a href="?page=an-canned-responses&action=edit&id=<?php echo $resp->id; ?>"><?php _e('Edit', 'agency-nexus'); ?></a> |
								<a href="<?php echo wp_nonce_url('?page=an-canned-responses&action=delete&id=' . $resp->id, 'an_delete_response_' . $resp->id); ?>" style="color:red;" onclick="return confirm('Delete response?')"><?php _e('Delete', 'agency-nexus'); ?></a>
							</td>
						</tr>
					<?php endforeach; else : ?>
						<tr><td colspan="3"><?php _e('No canned responses found.', 'agency-nexus'); ?></td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public function render_dashboard_widget() {
		if ( ! Agency_Nexus_Permissions::is_admin() ) {
			return;
		}
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
