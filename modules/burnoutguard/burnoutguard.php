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
		?>
		<div class="postbox" style="padding: 20px;">
			<h2><?php _e( 'BurnoutGuard', 'agency-nexus' ); ?></h2>
			<p><strong><?php _e( 'Workload Capacity:', 'agency-nexus' ); ?></strong></p>
			<div style="text-align: center; margin: 20px 0;">
				<div style="display: inline-block; width: 100px; height: 100px; border-radius: 50%; border: 8px solid #ffb900; line-height: 84px; font-size: 24px; font-weight: bold;">
					85%
				</div>
				<p style="color: #d94f4f; font-weight: bold; margin-top: 10px;"><?php _e( 'High Workload Warning', 'agency-nexus' ); ?></p>
			</div>
			<p><small><?php _e( 'Recommendation: Postpone non-essential tasks or delegate to overflow partners.', 'agency-nexus' ); ?></small></p>
		</div>
		<?php
	}
}
