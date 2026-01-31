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
		add_action( 'admin_init', [ $this, 'handle_post' ] );
		add_action( 'wp_ajax_an_update_content_date', [ $this, 'handle_update_content_date' ] );
		add_action( 'wp_ajax_an_create_content', [ $this, 'handle_create_content' ] );
		add_action( 'wp_ajax_an_delete_content', [ $this, 'handle_delete_content' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	public function handle_post() {
		if ( ! is_admin() || ! Agency_Nexus_Permissions::can_access_nexus() ) {
			return;
		}

		if ( isset( $_GET['page'] ) && 'an-content-list' === $_GET['page'] ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'an_content';
			$action = isset( $_GET['action'] ) ? $_GET['action'] : '';
			$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

			if ( 'delete' === $action && $id ) {
				check_admin_referer( 'an_delete_content_' . $id );
				$wpdb->delete( $table_name, [ 'id' => $id ] );
				wp_redirect( admin_url( 'admin.php?page=an-content-list&msg=deleted' ) );
				exit;
			}

			if ( isset( $_POST['an_save_content'] ) && check_admin_referer( 'an_save_content_nonce' ) ) {
				$data = [
					'project_id'     => intval( $_POST['project_id'] ),
					'title'          => sanitize_text_field( $_POST['title'] ),
					'content'        => wp_kses_post( $_POST['content'] ),
					'media_url'      => esc_url_raw( $_POST['media_url'] ),
					'status'         => sanitize_text_field( $_POST['status'] ),
					'scheduled_date' => ! empty( $_POST['scheduled_date'] ) ? date( 'Y-m-d H:i:s', strtotime( $_POST['scheduled_date'] ) ) : null,
					'platform'       => sanitize_text_field( $_POST['platform'] )
				];
				if ( $id ) {
					$wpdb->update( $table_name, $data, [ 'id' => $id ] );
					$msg = 'updated';
				} else {
					$wpdb->insert( $table_name, $data );
					$msg = 'added';
				}
				wp_redirect( admin_url( 'admin.php?page=an-content-list&msg=' . $msg ) );
				exit;
			}
		}
	}

	public function enqueue_scripts( $hook ) {
		if ( 'agency-nexus_page_an-content-calendar' !== $hook ) {
			return;
		}
		wp_enqueue_media();
	}

	public function register_submenu() {
		add_submenu_page(
			'agency-nexus',
			__( 'Content Calendar', 'agency-nexus' ),
			__( 'Content Calendar', 'agency-nexus' ),
			'read',
			'an-content-calendar',
			[ $this, 'render_calendar' ]
		);

		add_submenu_page(
			'agency-nexus',
			__( 'Content List', 'agency-nexus' ),
			__( 'Content List', 'agency-nexus' ),
			'read',
			'an-content-list',
			[ $this, 'render_content_list' ]
		);
	}

	public function render_calendar() {
		global $wpdb;
		$content_query = "SELECT * FROM {$wpdb->prefix}an_content";
		$projects_query = "SELECT id, title FROM {$wpdb->prefix}an_projects";

		if ( ! Agency_Nexus_Permissions::is_team_member() ) {
			$client_id = Agency_Nexus_Permissions::get_client_id_for_user( get_current_user_id() );
			$content_query = $wpdb->prepare( "SELECT c.* FROM {$wpdb->prefix}an_content c JOIN {$wpdb->prefix}an_projects p ON c.project_id = p.id WHERE p.client_id = %d", $client_id );
			$projects_query = $wpdb->prepare( "SELECT id, title FROM {$wpdb->prefix}an_projects WHERE client_id = %d", $client_id );
		}

		$content_items = $wpdb->get_results( $content_query );
		$projects      = $wpdb->get_results( $projects_query );
		?>
		<div class="wrap">
			<h1><?php _e( 'Content Calendar', 'agency-nexus' ); ?></h1>
			<p class="description"><?php _e( 'A visual overview of your cross-platform content strategy. Drag unscheduled items onto the calendar to set a publication date, or move existing items to reschedule.', 'agency-nexus' ); ?></p>
		</div>
		<?php
		$this->get_template( 'calendar', [ 'content_items' => $content_items, 'projects' => $projects ] );
	}

	public function render_content_list() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'an_content';
		$projects_table = $wpdb->prefix . 'an_projects';
		$action = isset( $_GET['action'] ) ? $_GET['action'] : 'list';
		$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		if ( isset( $_GET['msg'] ) ) {
			$m = '';
			switch ( $_GET['msg'] ) {
				case 'added': $m = 'Content added!'; break;
				case 'updated': $m = 'Content updated!'; break;
				case 'deleted': $m = 'Content item deleted!'; break;
			}
			if ( $m ) echo '<div class="updated"><p>' . esc_html( $m ) . '</p></div>';
		}

		if ($action === 'edit' || $action === 'add') {
			$content = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id)) : null;
			$projects = $wpdb->get_results("SELECT id, title FROM $projects_table");
			?>
			<div class="wrap">
				<h1><?php echo $id ? __('Edit Content', 'agency-nexus') : __('Add New Content', 'agency-nexus'); ?></h1>
				<p class="description"><?php _e('Create a content piece for a specific project. This can be a blog post, social media update, or newsletter.', 'agency-nexus'); ?></p>
				<form method="post">
					<?php wp_nonce_field('an_save_content_nonce'); ?>
					<table class="form-table">
						<tr>
							<th><label><?php _e('Project', 'agency-nexus'); ?></label></th>
							<td>
								<select name="project_id" required>
									<?php foreach ($projects as $p) : ?>
										<option value="<?php echo $p->id; ?>" <?php selected($content ? $content->project_id : 0, $p->id); ?>><?php echo esc_html($p->title); ?></option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php _e('Which project does this content belong to?', 'agency-nexus'); ?></p>
							</td>
						</tr>
						<tr>
							<th><label><?php _e('Title', 'agency-nexus'); ?></label></th>
							<td>
								<input type="text" name="title" value="<?php echo $content ? esc_attr($content->title) : ''; ?>" required class="regular-text">
								<p class="description"><?php _e('Internal name or headline for the content.', 'agency-nexus'); ?></p>
							</td>
						</tr>
						<tr>
							<th><label><?php _e('Body Content', 'agency-nexus'); ?></label></th>
							<td>
								<textarea name="content" class="regular-text" rows="10"><?php echo $content ? esc_textarea($content->content) : ''; ?></textarea>
								<p class="description"><?php _e('The actual text or copy for the post.', 'agency-nexus'); ?></p>
							</td>
						</tr>
						<tr>
							<th><label><?php _e('Media URL', 'agency-nexus'); ?></label></th>
							<td>
								<input type="text" name="media_url" id="media_url" value="<?php echo $content ? esc_attr($content->media_url) : ''; ?>" class="regular-text">
								<button type="button" id="upload_media_btn" class="button">Upload/Select Media</button>
								<p class="description"><?php _e('URL of an image or video asset from the Media Library.', 'agency-nexus'); ?></p>
							</td>
						</tr>
						<tr>
							<th><label><?php _e('Platform', 'agency-nexus'); ?></label></th>
							<td>
								<input type="text" name="platform" value="<?php echo $content ? esc_attr($content->platform) : 'wordpress'; ?>" class="regular-text">
								<p class="description"><?php _e('Where will this be published? e.g., WordPress, Instagram, LinkedIn', 'agency-nexus'); ?></p>
							</td>
						</tr>
						<tr>
							<th><label><?php _e('Status', 'agency-nexus'); ?></label></th>
							<td>
								<select name="status">
									<option value="draft" <?php selected($content ? $content->status : '', 'draft'); ?>>Draft</option>
									<option value="pending_approval" <?php selected($content ? $content->status : '', 'pending_approval'); ?>>Pending Approval</option>
									<option value="approved" <?php selected($content ? $content->status : '', 'approved'); ?>>Approved</option>
									<option value="published" <?php selected($content ? $content->status : '', 'published'); ?>>Published</option>
								</select>
								<p class="description"><?php _e('Clients can only approve items set to "Pending Approval".', 'agency-nexus'); ?></p>
							</td>
						</tr>
						<tr>
							<th><label><?php _e('Scheduled Date', 'agency-nexus'); ?></label></th>
							<td>
								<input type="datetime-local" name="scheduled_date" value="<?php echo ($content && $content->scheduled_date) ? date('Y-m-d\TH:i', strtotime($content->scheduled_date)) : ''; ?>">
								<p class="description"><?php _e('When should this content go live?', 'agency-nexus'); ?></p>
							</td>
						</tr>
					</table>
					<input type="submit" name="an_save_content" class="button button-primary" value="Save Content">
					<a href="?page=an-content-list" class="button">Cancel</a>
				</form>
			</div>
			<script>
			jQuery(document).ready(function($){
				$('#upload_media_btn').click(function(e) {
					e.preventDefault();
					var frame = wp.media({ title: 'Select Media', multiple: false }).open().on('select', function(e){
						var attachment = frame.state().get('selection').first().toJSON();
						$('#media_url').val(attachment.url);
					});
				});
			});
			</script>
			<?php
			return;
		}

		$query = "SELECT c.*, p.title as project_title FROM $table_name c JOIN $projects_table p ON c.project_id = p.id";
		if ( ! Agency_Nexus_Permissions::is_team_member() ) {
			$client_id = Agency_Nexus_Permissions::get_client_id_for_user( get_current_user_id() );
			$query .= $wpdb->prepare( " WHERE p.client_id = %d", $client_id );
		}
		$query .= " ORDER BY c.created_at DESC";
		$items = $wpdb->get_results($query);
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e('Content Management', 'agency-nexus'); ?></h1>
			<p><?php _e( 'Guidance: Manage all your content assets here. Use the "Content Calendar" for a visual overview of your publishing schedule.', 'agency-nexus' ); ?></p>
			<?php if ( Agency_Nexus_Permissions::is_team_member() ) : ?>
			<a href="?page=an-content-list&action=add" class="page-title-action">Add New</a>
			<?php endif; ?>
			<hr class="wp-header-end">

			<table class="wp-list-table widefat fixed striped">
				<thead><tr><th>Title</th><th>Content</th><th>Project</th><th>Platform</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
				<tbody>
					<?php foreach ($items as $item) : ?>
						<tr>
							<td><strong><?php echo esc_html($item->title); ?></strong></td>
							<td><small><?php echo esc_html(wp_trim_words($item->content, 10)); ?></small></td>
							<td><?php echo esc_html($item->project_title); ?></td>
							<td><?php echo esc_html(ucfirst($item->platform)); ?></td>
							<td><span class="badge status-<?php echo $item->status; ?>"><?php echo ucfirst(str_replace('_', ' ', $item->status)); ?></span></td>
							<td><?php echo $item->scheduled_date; ?></td>
							<td>
								<a href="?page=an-content-list&action=edit&id=<?php echo $item->id; ?>">Edit</a>
								<?php if ( Agency_Nexus_Permissions::is_team_member() ) : ?>
								| <a href="<?php echo wp_nonce_url('?page=an-content-list&action=delete&id=' . $item->id, 'an_delete_content_' . $item->id); ?>" style="color:red;" onclick="return confirm('Delete item?')">Delete</a>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * AJAX handler for creating content.
	 */
	public function handle_create_content() {
		check_ajax_referer( 'an_calendar_nonce', 'security' );

		$project_id = intval( $_POST['project_id'] );
		if ( ! Agency_Nexus_Permissions::can_view_project( $project_id ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		global $wpdb;
		$title      = sanitize_text_field( $_POST['title'] );
		$content    = isset($_POST['content']) ? sanitize_textarea_field( $_POST['content'] ) : '';
		$media_url  = esc_url_raw( $_POST['media_url'] );

		$wpdb->insert(
			$wpdb->prefix . 'an_content',
			[
				'project_id' => $project_id,
				'title'      => $title,
				'content'    => $content,
				'media_url'  => $media_url,
				'status'     => 'pending_approval'
			]
		);

		wp_send_json_success( [ 'id' => $wpdb->insert_id, 'title' => $title ] );
	}

	/**
	 * AJAX handler for deleting content.
	 */
	public function handle_delete_content() {
		check_ajax_referer( 'an_calendar_nonce', 'security' );

		global $wpdb;
		$item_id = intval( $_POST['item_id'] );
		$project_id = $wpdb->get_var( $wpdb->prepare( "SELECT project_id FROM {$wpdb->prefix}an_content WHERE id = %d", $item_id ) );

		if ( ! Agency_Nexus_Permissions::can_view_project( $project_id ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$wpdb->delete( $wpdb->prefix . 'an_content', [ 'id' => $item_id ] );
		wp_send_json_success();
	}

	/**
	 * AJAX handler for drag and drop scheduling.
	 */
	public function handle_update_content_date() {
		check_ajax_referer( 'an_calendar_nonce', 'security' );

		global $wpdb;
		$item_id = intval( $_POST['item_id'] );
		$project_id = $wpdb->get_var( $wpdb->prepare( "SELECT project_id FROM {$wpdb->prefix}an_content WHERE id = %d", $item_id ) );

		if ( ! Agency_Nexus_Permissions::can_view_project( $project_id ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$new_date = sanitize_text_field( $_POST['new_date'] );

		$wpdb->update(
			$wpdb->prefix . 'an_content',
			[ 'scheduled_date' => $new_date ],
			[ 'id' => $item_id ]
		);

		wp_send_json_success();
	}

	public function render_dashboard_widget() {
		if ( ! Agency_Nexus_Permissions::can_access_nexus() ) {
			return;
		}
		?>
		<div class="postbox" style="padding: 20px;">
			<h2><?php _e( 'ContentMatrix', 'agency-nexus' ); ?></h2>
			<p><?php _e( 'Plan and schedule your content across platforms.', 'agency-nexus' ); ?></p>
			<a href="<?php echo admin_url( 'admin.php?page=an-content-calendar' ); ?>" class="button"><?php _e( 'Open Calendar', 'agency-nexus' ); ?></a>
		</div>
		<?php
	}
}
