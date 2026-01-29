<?php
/**
 * ContentMatrix Module
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Nexus_Module_Contentmatrix extends Agency_Nexus_Base_Module {

	protected $name = 'ContentMatrix';

	public function init() {
		add_action( 'admin_menu', [ $this, 'register_submenu' ] );
		add_action( 'agency_nexus_dashboard_widgets', [ $this, 'render_dashboard_widget' ] );
		add_action( 'wp_ajax_an_update_content_date', [ $this, 'handle_update_content_date' ] );
		add_action( 'wp_ajax_an_create_content', [ $this, 'handle_create_content' ] );
	}

	public function register_submenu() {
		add_submenu_page(
			'agency-nexus',
			__( 'Content Calendar', 'agency-nexus' ),
			__( 'Content Calendar', 'agency-nexus' ),
			'manage_options',
			'an-content-calendar',
			[ $this, 'render_calendar' ]
		);
	}

	public function render_calendar() {
		global $wpdb;
		$content_items = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}an_content" );
		$projects      = $wpdb->get_results( "SELECT id, title FROM {$wpdb->prefix}an_projects" );
		$this->get_template( 'calendar', [ 'content_items' => $content_items, 'projects' => $projects ] );
	}

	/**
	 * AJAX handler for creating content.
	 */
	public function handle_create_content() {
		check_ajax_referer( 'an_calendar_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		global $wpdb;
		$project_id = intval( $_POST['project_id'] );
		$title      = sanitize_text_field( $_POST['title'] );

		$wpdb->insert(
			$wpdb->prefix . 'an_content',
			[
				'project_id' => $project_id,
				'title'      => $title,
				'status'     => 'pending_approval'
			]
		);

		wp_send_json_success( [ 'id' => $wpdb->insert_id, 'title' => $title ] );
	}

	/**
	 * AJAX handler for drag and drop scheduling.
	 */
	public function handle_update_content_date() {
		check_ajax_referer( 'an_calendar_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		global $wpdb;
		$item_id = intval( $_POST['item_id'] );
		$new_date = sanitize_text_field( $_POST['new_date'] );

		$wpdb->update(
			$wpdb->prefix . 'an_content',
			[ 'scheduled_date' => $new_date ],
			[ 'id' => $item_id ]
		);

		wp_send_json_success();
	}

	public function render_dashboard_widget() {
		?>
		<div class="postbox" style="padding: 20px;">
			<h2><?php _e( 'ContentMatrix', 'agency-nexus' ); ?></h2>
			<p><?php _e( 'Plan and schedule your content across platforms.', 'agency-nexus' ); ?></p>
			<a href="<?php echo admin_url( 'admin.php?page=an-content-calendar' ); ?>" class="button"><?php _e( 'Open Calendar', 'agency-nexus' ); ?></a>
		</div>
		<?php
	}
}
