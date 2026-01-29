<?php
/**
 * BurnoutGuard Module
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Nexus_Module_Burnoutguard extends Agency_Nexus_Base_Module {

	protected $name = 'BurnoutGuard';

	public function init() {
		add_action( 'admin_menu', [ $this, 'register_submenu' ] );
		add_action( 'agency_nexus_dashboard_widgets', [ $this, 'render_dashboard_widget' ] );
	}

	public function register_submenu() {
		add_submenu_page(
			'agency-nexus',
			__( 'Health Check', 'agency-nexus' ),
			__( 'Health Check', 'agency-nexus' ),
			'manage_options',
			'an-health-check',
			[ $this, 'render_health_check' ]
		);
	}

	public function render_health_check() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'an_burnout_logs';

		if ( isset( $_POST['an_add_check'] ) && check_admin_referer( 'an_add_check_nonce' ) ) {
			$wpdb->insert(
				$table_name,
				[
					'user_id'      => get_current_user_id(),
					'stress_level' => intval( $_POST['stress_level'] ),
					'note'         => sanitize_textarea_field( $_POST['note'] ),
					'created_at'   => current_time( 'mysql' )
				]
			);
			echo '<div class="updated"><p>Log recorded. Remember to take breaks!</p></div>';
		}

		$logs = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 10" );
		?>
		<div class="wrap">
			<h1><?php _e( 'Health & Sustainability System', 'agency-nexus' ); ?></h1>
			<div class="postbox" style="padding: 20px; margin-top: 20px;">
				<h2><?php _e( 'Daily Stress Self-Check', 'agency-nexus' ); ?></h2>
				<form method="post">
					<?php wp_nonce_field( 'an_add_check_nonce' ); ?>
					<table class="form-table">
						<tr>
							<th><label>Stress Level (1-10)</label></th>
							<td>
								<input type="range" name="stress_level" min="1" max="10" value="5" class="regular-text" oninput="this.nextElementSibling.value = this.value">
								<output>5</output>
							</td>
						</tr>
						<tr>
							<th><label>Notes / How are you feeling?</label></th>
							<td><textarea name="note" class="regular-text" rows="3"></textarea></td>
						</tr>
					</table>
					<input type="submit" name="an_add_check" class="button button-primary" value="Log Check-in">
				</form>
			</div>

			<h2>Recent Check-ins</h2>
			<table class="wp-list-table widefat fixed striped">
				<thead><tr><th>Date</th><th>Stress Level</th><th>Note</th></tr></thead>
				<tbody>
					<?php foreach ($logs as $log) :
						$color = $log->stress_level > 7 ? 'red' : ($log->stress_level > 4 ? 'orange' : 'green');
					?>
						<tr>
							<td><?php echo $log->created_at; ?></td>
							<td style="color: <?php echo $color; ?>; font-weight: bold;"><?php echo $log->stress_level; ?> / 10</td>
							<td><?php echo esc_html($log->note); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public function render_dashboard_widget() {
		global $wpdb;
		$time_table = $wpdb->prefix . 'an_time_entries';

		// Get hours logged in the last 7 days
		$last_week_seconds = $wpdb->get_var( "SELECT SUM(duration) FROM $time_table WHERE date >= DATE_SUB(NOW(), INTERVAL 7 DAY)" );
		$last_week_hours = $last_week_seconds / 3600;

		// Assuming 40 hours is 100% capacity
		$capacity = min(100, round(($last_week_hours / 40) * 100));
		$color = $capacity > 80 ? '#dc3232' : ($capacity > 50 ? '#ffb900' : '#46b450');

		?>
		<div class="postbox" style="padding: 20px;">
			<h2><?php _e( 'BurnoutGuard', 'agency-nexus' ); ?></h2>
			<p><strong><?php _e( 'Workload Capacity (Last 7 Days):', 'agency-nexus' ); ?></strong></p>
			<div style="text-align: center; margin: 20px 0;">
				<div style="display: inline-block; width: 100px; height: 100px; border-radius: 50%; border: 8px solid <?php echo $color; ?>; line-height: 84px; font-size: 24px; font-weight: bold;">
					<?php echo $capacity; ?>%
				</div>
				<?php if ($capacity > 80) : ?>
					<p style="color: #dc3232; font-weight: bold; margin-top: 10px;"><?php _e( 'High Workload Warning', 'agency-nexus' ); ?></p>
				<?php else : ?>
					<p style="color: #46b450; font-weight: bold; margin-top: 10px;"><?php _e( 'Healthy Workload', 'agency-nexus' ); ?></p>
				<?php endif; ?>
			</div>
			<p><small><?php echo sprintf( __( 'You have logged %.1f hours this week.', 'agency-nexus' ), $last_week_hours ); ?></small></p>
		</div>
		<?php
	}
}
