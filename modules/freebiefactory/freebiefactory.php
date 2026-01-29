<?php
/**
 * FreebieFactory Module
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Nexus_Module_Freebiefactory extends Agency_Nexus_Base_Module {

	protected $name = 'FreebieFactory';

	public function init() {
		add_action( 'admin_menu', [ $this, 'register_submenu' ] );
		add_action( 'agency_nexus_dashboard_widgets', [ $this, 'render_dashboard_widget' ] );
	}

	public function register_submenu() {
		add_submenu_page(
			'agency-nexus',
			__( 'Resource Library', 'agency-nexus' ),
			__( 'Resource Library', 'agency-nexus' ),
			'manage_options',
			'an-resources',
			[ $this, 'render_resources' ]
		);
	}

	public function render_resources() {
		?>
		<div class="wrap">
			<h1><?php _e( 'Agency Resource Library', 'agency-nexus' ); ?></h1>
			<p><?php _e( 'Access your swipe files, templates, and brand assets.', 'agency-nexus' ); ?></p>

			<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
				<div class="card" style="padding: 15px; background: #fff; border: 1px solid #ddd;">
					<h3>📄 <?php _e( 'Contract Templates', 'agency-nexus' ); ?></h3>
					<ul>
						<li><a href="#"><?php _e( 'Standard MSA', 'agency-nexus' ); ?></a></li>
						<li><a href="#"><?php _e( 'Website SOW', 'agency-nexus' ); ?></a></li>
					</ul>
				</div>
				<div class="card" style="padding: 15px; background: #fff; border: 1px solid #ddd;">
					<h3>💡 <?php _e( 'Swipe Files', 'agency-nexus' ); ?></h3>
					<ul>
						<li><a href="#"><?php _e( 'High-Converting Ad Copy', 'agency-nexus' ); ?></a></li>
						<li><a href="#"><?php _e( 'Onboarding Emails', 'agency-nexus' ); ?></a></li>
					</ul>
				</div>
			</div>
		</div>
		<?php
	}

	public function render_dashboard_widget() {
		?>
		<div class="postbox" style="padding: 20px;">
			<h2><?php _e( 'FreebieFactory', 'agency-nexus' ); ?></h2>
			<p><?php _e( 'Need a template? 2 new swipe files added this week.', 'agency-nexus' ); ?></p>
			<a href="<?php echo admin_url( 'admin.php?page=an-resources' ); ?>" class="button"><?php _e( 'Browse Resources', 'agency-nexus' ); ?></a>
		</div>
		<?php
	}
}
