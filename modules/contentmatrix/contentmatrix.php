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
		add_action( 'wp_ajax_an_delete_content', [ $this, 'handle_delete_content' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
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
			'manage_options',
			'an-content-calendar',
			[ $this, 'render_calendar' ]
		);

		add_submenu_page(
			'agency-nexus',
			__( 'Content List', 'agency-nexus' ),
			__( 'Content List', 'agency-nexus' ),
			'manage_options',
			'an-content-list',
			[ $this, 'render_content_list' ]
		);
	}

	public function render_calendar() {
		global $wpdb;
		$content_items = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}an_content" );
		$projects      = $wpdb->get_results( "SELECT id, title FROM {$wpdb->prefix}an_projects" );
		?>
		<div class="wrap">
			<h1><?php _e( 'Content Calendar', 'agency-nexus' ); ?></h1>
			<p><?php _e( 'Guidance: Drag and drop items to reschedule. Click on a date to create new content placeholders.', 'agency-nexus' ); ?></p>
		</div>
		<?php
		$this->get_template( 'calendar', [ 'content_items' => $content_items, 'projects' => $projects ] );
	}

	public function render_content_list() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'an_content';
		$projects_table = $wpdb->prefix . 'an_projects';
		$action = isset($_GET['action']) ? $_GET['action'] : 'list';
		$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

		if ($action === 'delete' && $id) {
			check_admin_referer('an_delete_content_' . $id);
			$wpdb->delete($table_name, ['id' => $id]);
			echo '<div class="updated"><p>Content item deleted!</p></div>';
			$action = 'list';
		}

		if (isset($_POST['an_save_content']) && check_admin_referer('an_save_content_nonce')) {
			$data = [
				'project_id'     => intval($_POST['project_id']),
				'title'          => sanitize_text_field($_POST['title']),
				'content'        => sanitize_textarea_field($_POST['content']),
				'media_url'      => esc_url_raw($_POST['media_url']),
				'status'         => sanitize_text_field($_POST['status']),
				'scheduled_date' => sanitize_text_field($_POST['scheduled_date']),
				'platform'       => sanitize_text_field($_POST['platform'])
			];
			if ($id) {
				$wpdb->update($table_name, $data, ['id' => $id]);
				echo '<div class="updated"><p>Content updated!</p></div>';
			} else {
				$wpdb->insert($table_name, $data);
				echo '<div class="updated"><p>Content added!</p></div>';
			}
			$action = 'list';
		}

		if ($action === 'edit' || $action === 'add') {
			$content = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id)) : null;
			$projects = $wpdb->get_results("SELECT id, title FROM $projects_table");
			?>
			<div class="wrap">
				<h1><?php echo $id ? __('Edit Content', 'agency-nexus') : __('Add New Content', 'agency-nexus'); ?></h1>
				<form method="post">
					<?php wp_nonce_field('an_save_content_nonce'); ?>
					<table class="form-table">
						<tr><th>Project</th><td>
							<select name="project_id" required>
								<?php foreach ($projects as $p) : ?>
									<option value="<?php echo $p->id; ?>" <?php selected($content ? $content->project_id : 0, $p->id); ?>><?php echo esc_html($p->title); ?></option>
								<?php endforeach; ?>
							</select>
						</td></tr>
						<tr><th>Title</th><td><input type="text" name="title" value="<?php echo $content ? esc_attr($content->title) : ''; ?>" required class="regular-text"></td></tr>
						<tr><th>Body Content</th><td><textarea name="content" class="regular-text" rows="10"><?php echo $content ? esc_textarea($content->content) : ''; ?></textarea></td></tr>
						<tr><th>Media URL</th><td>
							<input type="text" name="media_url" id="media_url" value="<?php echo $content ? esc_attr($content->media_url) : ''; ?>" class="regular-text">
							<button type="button" id="upload_media_btn" class="button">Upload/Select Media</button>
						</td></tr>
						<tr><th>Platform</th><td><input type="text" name="platform" value="<?php echo $content ? esc_attr($content->platform) : 'wordpress'; ?>" class="regular-text"></td></tr>
						<tr><th>Status</th><td>
							<select name="status">
								<option value="draft" <?php selected($content ? $content->status : '', 'draft'); ?>>Draft</option>
								<option value="pending_approval" <?php selected($content ? $content->status : '', 'pending_approval'); ?>>Pending Approval</option>
								<option value="approved" <?php selected($content ? $content->status : '', 'approved'); ?>>Approved</option>
								<option value="published" <?php selected($content ? $content->status : '', 'published'); ?>>Published</option>
							</select>
						</td></tr>
						<tr><th>Scheduled Date</th><td><input type="datetime-local" name="scheduled_date" value="<?php echo ($content && $content->scheduled_date) ? date('Y-m-d\TH:i', strtotime($content->scheduled_date)) : ''; ?>"></td></tr>
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

		$items = $wpdb->get_results("SELECT c.*, p.title as project_title FROM $table_name c JOIN $projects_table p ON c.project_id = p.id ORDER BY c.created_at DESC");
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e('Content Management', 'agency-nexus'); ?></h1>
			<p><?php _e( 'Guidance: Manage all your content assets here. Use the "Content Calendar" for a visual overview of your publishing schedule.', 'agency-nexus' ); ?></p>
			<a href="?page=an-content-list&action=add" class="page-title-action">Add New</a>
			<hr class="wp-header-end">

			<table class="wp-list-table widefat fixed striped">
				<thead><tr><th>Title</th><th>Project</th><th>Platform</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
				<tbody>
					<?php foreach ($items as $item) : ?>
						<tr>
							<td><strong><?php echo esc_html($item->title); ?></strong></td>
							<td><?php echo esc_html($item->project_title); ?></td>
							<td><?php echo esc_html(ucfirst($item->platform)); ?></td>
							<td><span class="badge status-<?php echo $item->status; ?>"><?php echo ucfirst(str_replace('_', ' ', $item->status)); ?></span></td>
							<td><?php echo $item->scheduled_date; ?></td>
							<td>
								<a href="?page=an-content-list&action=edit&id=<?php echo $item->id; ?>">Edit</a> |
								<a href="<?php echo wp_nonce_url('?page=an-content-list&action=delete&id=' . $item->id, 'an_delete_content_' . $item->id); ?>" style="color:red;" onclick="return confirm('Delete item?')">Delete</a>
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

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		global $wpdb;
		$project_id = intval( $_POST['project_id'] );
		$title      = sanitize_text_field( $_POST['title'] );
		$media_url  = esc_url_raw( $_POST['media_url'] );

		$wpdb->insert(
			$wpdb->prefix . 'an_content',
			[
				'project_id' => $project_id,
				'title'      => $title,
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

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		global $wpdb;
		$item_id = intval( $_POST['item_id'] );
		$wpdb->delete( $wpdb->prefix . 'an_content', [ 'id' => $item_id ] );
		wp_send_json_success();
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
