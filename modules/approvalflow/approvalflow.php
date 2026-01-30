<?php
/**
 * ApprovalFlow Module
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Nexus_Module_Approvalflow extends Agency_Nexus_Base_Module {

	protected $name = 'ApprovalFlow';

	public function init() {
		add_action( 'admin_menu', [ $this, 'register_submenu' ] );
		add_action( 'agency_nexus_dashboard_widgets', [ $this, 'render_dashboard_widget' ] );
		add_action( 'wp_ajax_an_approve_content', [ $this, 'handle_approve_content' ] );
		add_action( 'wp_ajax_an_disapprove_content', [ $this, 'handle_disapprove_content' ] );
	}

	public function register_submenu() {
		add_submenu_page(
			'agency-nexus',
			__( 'Approvals', 'agency-nexus' ),
			__( 'Approvals', 'agency-nexus' ),
			'manage_options',
			'an-approvals',
			[ $this, 'render_approvals' ]
		);
	}

	public function render_approvals() {
		global $wpdb;
		$pending_items = $wpdb->get_results( "
			SELECT c.*, p.title as project_title
			FROM {$wpdb->prefix}an_content c
			JOIN {$wpdb->prefix}an_projects p ON c.project_id = p.id
			WHERE c.status = 'pending_approval'
		" );
		$history = $wpdb->get_results( "
			SELECT c.*, p.title as project_title
			FROM {$wpdb->prefix}an_content c
			JOIN {$wpdb->prefix}an_projects p ON c.project_id = p.id
			WHERE c.status != 'pending_approval'
			ORDER BY c.created_at DESC LIMIT 20
		" );
		$this->get_template( 'approvals', [ 'pending_items' => $pending_items, 'history' => $history ] );
	}

	public function handle_approve_content() {
		check_ajax_referer( 'an_approval_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		global $wpdb;
		$item_id = intval( $_POST['item_id'] );

		$wpdb->update(
			$wpdb->prefix . 'an_content',
			[ 'status' => 'approved' ],
			[ 'id' => $item_id ]
		);

		wp_send_json_success();
	}

	public function handle_disapprove_content() {
		check_ajax_referer( 'an_approval_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		global $wpdb;
		$item_id = intval( $_POST['item_id'] );

		$wpdb->update(
			$wpdb->prefix . 'an_content',
			[ 'status' => 'pending_approval' ],
			[ 'id' => $item_id ]
		);

		wp_send_json_success();
	}

	public function render_dashboard_widget() {
		?>
		<div class="postbox" style="padding: 20px;">
			<h2><?php _e( 'ApprovalFlow', 'agency-nexus' ); ?></h2>
			<p><?php _e( '2 content items are waiting for your final sign-off.', 'agency-nexus' ); ?></p>
			<a href="<?php echo admin_url( 'admin.php?page=an-approvals' ); ?>" class="button"><?php _e( 'Review Items', 'agency-nexus' ); ?></a>
		</div>
		<?php
	}
}
