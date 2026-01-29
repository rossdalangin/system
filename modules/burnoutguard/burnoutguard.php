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
		add_action( 'agency_nexus_dashboard_widgets', [ $this, 'render_dashboard_widget' ] );
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
