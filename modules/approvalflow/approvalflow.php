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
		add_action( 'admin_init', [ $this, 'handle_post' ] );
	}

	public function handle_post() {
		if ( ! is_admin() || ! Agency_Nexus_Permissions::can_access_nexus() ) {
			return;
		}

		if ( isset( $_POST['an_save_content_comment'] ) && check_admin_referer( 'an_content_comment_nonce' ) ) {
			global $wpdb;
			$content_id = intval( $_POST['content_id'] );
			$wpdb->insert( $wpdb->prefix . 'an_content_comments', [
				'content_id' => $content_id,
				'user_id'    => get_current_user_id(),
				'comment'    => sanitize_textarea_field( $_POST['comment'] ),
				'created_at' => current_time( 'mysql' )
			] );
			wp_redirect( admin_url( 'admin.php?page=an-approvals&action=compare&id=' . $content_id ) );
			exit;
		}
	}

	public function register_submenu() {
		if ( ! Agency_Nexus_License_Manager::get_instance()->is_feature_enabled( 'content_calendar' ) ) {
			return;
		}

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
		$action = isset( $_GET['action'] ) ? $_GET['action'] : 'list';
		$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		if ( $action === 'compare' && $id ) {
			$this->render_comparison_view( $id );
			return;
		}
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

		do_action( 'agency_nexus_content_status_updated', $item_id, 'approved' );

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

		do_action( 'agency_nexus_content_status_updated', $item_id, 'draft' );

		wp_send_json_success();
	}

	public function render_comparison_view( $id ) {
		global $wpdb;
		$item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}an_content WHERE id = %d", $id ) );
		$versions = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}an_content_versions WHERE content_id = %d ORDER BY version_number DESC", $id ) );
		$comments = $wpdb->get_results( $wpdb->prepare( "SELECT c.*, u.display_name FROM {$wpdb->prefix}an_content_comments c JOIN {$wpdb->users} u ON c.user_id = u.ID WHERE c.content_id = %d ORDER BY c.created_at ASC", $id ) );

		$current_v = $item->content;
		$prev_v = !empty($versions) ? $versions[0]->content : '<em>No previous version.</em>';
		?>
		<div class="agency-nexus-wrap">
			<h1><?php echo sprintf( __( 'Review: %s', 'agency-nexus' ), esc_html($item->title) ); ?></h1>
			<p><a href="?page=an-approvals" class="button">Back to Portal</a></p>

			<div class="an-comparison-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
				<div class="postbox" style="padding: 20px;">
					<h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">Previous Version</h3>
					<div style="background:#f9f9f9; padding:15px; border-radius:5px; min-height: 200px;">
						<?php echo wpautop($prev_v); ?>
					</div>
				</div>
				<div class="postbox" style="padding: 20px; border-left: 4px solid var(--an-indigo-600);">
					<h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">Current Version</h3>
					<div style="background:#fff; padding:15px; border-radius:5px; min-height: 200px;">
						<?php echo wpautop($current_v); ?>
					</div>
				</div>
			</div>

			<div class="an-comments-section postbox" style="margin-top: 30px; padding: 20px;">
				<h3><?php _e( 'Revision Feedback & Annotations', 'agency-nexus' ); ?></h3>
				<div class="comments-list" style="margin-bottom: 20px; max-height: 300px; overflow-y: auto;">
					<?php foreach ( $comments as $c ) : ?>
						<div class="comment-item" style="margin-bottom: 15px; padding: 10px; background: #f1f1f1; border-radius: 5px;">
							<strong><?php echo esc_html($c->display_name); ?></strong> <small>(<?php echo $c->created_at; ?>)</small>
							<p style="margin: 5px 0 0;"><?php echo esc_html($c->comment); ?></p>
						</div>
					<?php endforeach; if(empty($comments)) echo '<p>No feedback provided yet.</p>'; ?>
				</div>
				<hr>
				<form method="post">
					<?php wp_nonce_field( 'an_content_comment_nonce' ); ?>
					<input type="hidden" name="content_id" value="<?php echo $id; ?>">
					<textarea name="comment" style="width:100%;" rows="3" placeholder="Suggest a change or leave feedback..."></textarea>
					<p><input type="submit" name="an_save_content_comment" class="button button-primary" value="Post Feedback"></p>
				</form>
			</div>

			<div class="action-bar" style="margin-top: 30px; display:flex; gap: 10px;">
				<button class="button button-primary button-large" onclick="an_approve_item(<?php echo $id; ?>)"><?php _e('Approve this version', 'agency-nexus'); ?></button>
				<button class="button button-large" onclick="an_reject_item(<?php echo $id; ?>)"><?php _e('Request Revisions', 'agency-nexus'); ?></button>
			</div>
		</div>
		<script>
		function an_approve_item(id) {
			if(!confirm('Approve this content?')) return;
			jQuery.post(ajaxurl, { action: 'an_approve_content', item_id: id, security: '<?php echo wp_create_nonce("an_approval_nonce"); ?>' }, function(r) {
				if(r.success) window.location.href = '?page=an-approvals';
			});
		}
		function an_reject_item(id) {
			if(!confirm('Reject and send back to draft?')) return;
			jQuery.post(ajaxurl, { action: 'an_reject_content', item_id: id, security: '<?php echo wp_create_nonce("an_approval_nonce"); ?>' }, function(r) {
				if(r.success) window.location.href = '?page=an-approvals';
			});
		}
		</script>
		<?php
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

		do_action( 'agency_nexus_content_status_updated', $item_id, 'pending_approval' );

		wp_send_json_success();
	}

	public function render_dashboard_widget() {
		if ( ! Agency_Nexus_License_Manager::get_instance()->is_feature_enabled( 'content_calendar' ) ) {
			return;
		}

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
