<?php
/**
 * TimeBlock Pro Module
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Nexus_Module_Timeblockpro extends Agency_Nexus_Base_Module {

	protected $name = 'TimeBlockPro';

	public function init() {
		add_action( 'admin_menu', [ $this, 'register_submenu' ] );
		add_action( 'agency_nexus_dashboard_widgets', [ $this, 'render_dashboard_widget' ] );
	}

	public function register_submenu() {
		add_submenu_page(
			'agency-nexus',
			__( 'Time Blocking', 'agency-nexus' ),
			__( 'Time Blocking', 'agency-nexus' ),
			'manage_options',
			'an-time-blocking',
			[ $this, 'render_dashboard' ]
		);
	}

	public function render_dashboard() {
		global $wpdb;
		$user_id = get_current_user_id();
		$blocks = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}an_time_blocks WHERE user_id = %d", $user_id ) );
		$this->get_template( 'dashboard', [ 'blocks' => $blocks ] );
	}

	public function render_dashboard_widget() {
		?>
		<div class="postbox" style="padding: 20px;">
			<h2><?php _e( 'TimeBlock Pro', 'agency-nexus' ); ?></h2>
			<p><?php _e( 'Focus on what matters. Your next deep work block starts in 15 minutes.', 'agency-nexus' ); ?></p>
			<a href="<?php echo admin_url( 'admin.php?page=an-time-blocking' ); ?>" class="button"><?php _e( 'Manage Schedule', 'agency-nexus' ); ?></a>
		</div>
		<?php
	}
}
