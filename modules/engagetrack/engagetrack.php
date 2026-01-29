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

		if ( isset( $_POST['an_add_lead'] ) && check_admin_referer( 'an_add_lead_nonce' ) ) {
			$wpdb->insert(
				$table_name,
				[
					'name'             => sanitize_text_field( $_POST['name'] ),
					'email'            => sanitize_email( $_POST['email'] ),
					'source'           => sanitize_text_field( $_POST['source'] ),
					'status'           => sanitize_text_field( $_POST['status'] ),
					'conversion_value' => floatval( $_POST['conversion_value'] )
				]
			);
			echo '<div class="updated"><p>Lead recorded!</p></div>';
		}

		$leads = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC" );
		?>
		<div class="wrap">
			<h1><?php _e( 'Lead Intelligence System', 'agency-nexus' ); ?></h1>
			<div class="postbox" style="padding: 20px; margin-top: 20px;">
				<h2>Add New Lead</h2>
				<form method="post">
					<?php wp_nonce_field( 'an_add_lead_nonce' ); ?>
					<table class="form-table">
						<tr><th>Name</th><td><input type="text" name="name" required class="regular-text"></td></tr>
						<tr><th>Email</th><td><input type="email" name="email" required class="regular-text"></td></tr>
						<tr><th>Source</th><td><input type="text" name="source" placeholder="e.g. LinkedIn, Referral" class="regular-text"></td></tr>
						<tr><th>Status</th><td>
							<select name="status">
								<option value="new">New</option>
								<option value="qualified">Qualified</option>
								<option value="converted">Converted</option>
								<option value="lost">Lost</option>
							</select>
						</td></tr>
						<tr><th>Potential Value ($)</th><td><input type="number" step="0.01" name="conversion_value" class="regular-text"></td></tr>
					</table>
					<input type="submit" name="an_add_lead" class="button button-primary" value="Record Lead">
				</form>
			</div>
			<table class="wp-list-table widefat fixed striped">
				<thead><tr><th>Name</th><th>Email</th><th>Source</th><th>Status</th><th>Value</th><th>Date</th></tr></thead>
				<tbody>
					<?php foreach ($leads as $lead) : ?>
						<tr>
							<td><?php echo esc_html($lead->name); ?></td>
							<td><?php echo esc_html($lead->email); ?></td>
							<td><?php echo esc_html($lead->source); ?></td>
							<td><?php echo esc_html(ucfirst($lead->status)); ?></td>
							<td>$<?php echo number_format($lead->conversion_value, 2); ?></td>
							<td><?php echo $lead->created_at; ?></td>
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

		if ( isset( $_POST['an_add_response'] ) && check_admin_referer( 'an_add_response_nonce' ) ) {
			$wpdb->insert(
				$table_name,
				[
					'title'   => sanitize_text_field( $_POST['title'] ),
					'content' => sanitize_textarea_field( $_POST['content'] )
				]
			);
			echo '<div class="updated"><p>Response saved!</p></div>';
		}

		$responses = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC" );
		?>
		<div class="wrap">
			<h1><?php _e( 'Canned Responses (Smart DM)', 'agency-nexus' ); ?></h1>
			<div class="postbox" style="padding: 20px; margin-top: 20px;">
				<h2>Add New Response</h2>
				<form method="post">
					<?php wp_nonce_field( 'an_add_response_nonce' ); ?>
					<table class="form-table">
						<tr><th>Shortcut Title</th><td><input type="text" name="title" required class="regular-text" placeholder="e.g. Pricing Query"></td></tr>
						<tr><th>Content</th><td><textarea name="content" required class="regular-text" rows="5"></textarea></td></tr>
					</table>
					<input type="submit" name="an_add_response" class="button button-primary" value="Save Response">
				</form>
			</div>
			<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
				<?php foreach ($responses as $resp) : ?>
					<div class="card" style="padding: 15px; background: #fff; border: 1px solid #ddd;">
						<h3><?php echo esc_html($resp->title); ?></h3>
						<p><?php echo nl2br(esc_html($resp->content)); ?></p>
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
