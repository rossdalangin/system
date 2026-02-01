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
		add_action( 'wp_ajax_an_reject_content', [ $this, 'handle_reject_content' ] );
		add_action( 'wp_ajax_an_disapprove_content', [ $this, 'handle_disapprove_content' ] );
	}

	public function register_submenu() {
		add_submenu_page(
			'agency-nexus',
			__( 'Approvals', 'agency-nexus' ),
			__( 'Approvals', 'agency-nexus' ),
			'read',
			'an-approvals',
			[ $this, 'render_approvals' ]
		);
	}

	public function render_approvals() {
		global $wpdb;
		?>
		<div class="agency-nexus-wrap">
			<h1><?php _e('Approval Portal', 'agency-nexus'); ?></h1>
			<p class="description"><?php _e('Review and approve content items before they are published. Items marked as "Pending Approval" in the Content Calendar will appear here.', 'agency-nexus'); ?></p>
		</div>
		<?php
		$where_pending = "WHERE c.status = 'pending_approval'";
		$where_history = "WHERE c.status != 'pending_approval'";

		$authorised_ids = Agency_Nexus_Permissions::get_authorised_project_ids();
		if ( is_array( $authorised_ids ) ) {
			if ( empty( $authorised_ids ) ) {
				$where_pending .= " AND 1=0";
				$where_history .= " AND 1=0";
			} else {
				$in_clause = "(" . implode( ',', array_map( 'intval', $authorised_ids ) ) . ")";
				$where_pending .= " AND c.project_id IN $in_clause";
				$where_history .= " AND c.project_id IN $in_clause";
			}
		}

		$pending_items = $wpdb->get_results( "
			SELECT c.*, p.title as project_title
			FROM {$wpdb->prefix}an_content c
			JOIN {$wpdb->prefix}an_projects p ON c.project_id = p.id
			$where_pending
		" );
		$history = $wpdb->get_results( "
			SELECT c.*, p.title as project_title
			FROM {$wpdb->prefix}an_content c
			JOIN {$wpdb->prefix}an_projects p ON c.project_id = p.id
			$where_history
			ORDER BY c.created_at DESC LIMIT 20
		" );
		$this->get_template( 'approvals', [ 'pending_items' => $pending_items, 'history' => $history ] );
	}

	public function handle_approve_content() {
		check_ajax_referer( 'an_approval_nonce', 'security' );

		global $wpdb;
		$item_id = intval( $_POST['item_id'] );
		$project_id = $wpdb->get_var( $wpdb->prepare( "SELECT project_id FROM {$wpdb->prefix}an_content WHERE id = %d", $item_id ) );

		if ( ! Agency_Nexus_Permissions::can_view_project( $project_id ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$wpdb->update(
			$wpdb->prefix . 'an_content',
			[ 'status' => 'approved' ],
			[ 'id' => $item_id ]
		);

		wp_send_json_success();
	}

	public function handle_reject_content() {
		check_ajax_referer( 'an_approval_nonce', 'security' );

		global $wpdb;
		$item_id = intval( $_POST['item_id'] );
		$project_id = $wpdb->get_var( $wpdb->prepare( "SELECT project_id FROM {$wpdb->prefix}an_content WHERE id = %d", $item_id ) );

		if ( ! Agency_Nexus_Permissions::can_view_project( $project_id ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$wpdb->update(
			$wpdb->prefix . 'an_content',
			[ 'status' => 'draft' ],
			[ 'id' => $item_id ]
		);

		wp_send_json_success();
	}

	public function handle_disapprove_content() {
		check_ajax_referer( 'an_approval_nonce', 'security' );

		global $wpdb;
		$item_id = intval( $_POST['item_id'] );
		$project_id = $wpdb->get_var( $wpdb->prepare( "SELECT project_id FROM {$wpdb->prefix}an_content WHERE id = %d", $item_id ) );

		if ( ! Agency_Nexus_Permissions::can_view_project( $project_id ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$wpdb->update(
			$wpdb->prefix . 'an_content',
			[ 'status' => 'pending_approval' ],
			[ 'id' => $item_id ]
		);

		wp_send_json_success();
	}

	public function render_dashboard_widget() {
		if ( ! Agency_Nexus_Permissions::can_access_nexus() ) {
			return;
		}
		global $wpdb;
		$authorised_ids = Agency_Nexus_Permissions::get_authorised_project_ids();
		$where = "WHERE status = 'pending_approval'";
		if ( is_array( $authorised_ids ) ) {
			if ( empty( $authorised_ids ) ) {
				$where .= " AND 1=0";
			} else {
				$where .= " AND project_id IN (" . implode( ',', array_map( 'intval', $authorised_ids ) ) . ")";
			}
		}
		$pending_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}an_content $where" );
		?>
		<div class="postbox" style="padding: 20px;">
			<h2><?php _e( 'ApprovalFlow', 'agency-nexus' ); ?></h2>
			<p><?php echo sprintf( _n( '%d content item is waiting for your final sign-off.', '%d content items are waiting for your final sign-off.', $pending_count, 'agency-nexus' ), $pending_count ); ?></p>
			<a href="<?php echo admin_url( 'admin.php?page=an-approvals' ); ?>" class="button"><?php _e( 'Review Items', 'agency-nexus' ); ?></a>
		</div>
		<?php
	}
}
