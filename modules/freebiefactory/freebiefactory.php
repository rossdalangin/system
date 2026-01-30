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
		add_action( 'admin_init', [ $this, 'handle_post' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	public function handle_post() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_GET['page'] ) && 'an-resources' === $_GET['page'] ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'an_resources';
			$action = isset( $_GET['action'] ) ? $_GET['action'] : '';
			$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

			if ( 'delete' === $action && $id ) {
				check_admin_referer( 'an_delete_resource_' . $id );
				$wpdb->delete( $table_name, [ 'id' => $id ] );
				wp_redirect( admin_url( 'admin.php?page=an-resources&msg=deleted' ) );
				exit;
			}

			if ( isset( $_POST['an_save_resource'] ) && check_admin_referer( 'an_save_resource_nonce' ) ) {
				$data = [
					'title'      => sanitize_text_field( $_POST['title'] ),
					'type'       => sanitize_text_field( $_POST['type'] ),
					'file_url'   => esc_url_raw( $_POST['file_url'] ),
					'content'    => wp_kses_post( $_POST['content'] ),
				];
				if ( $id ) {
					$wpdb->update( $table_name, $data, [ 'id' => $id ] );
					$msg = 'updated';
				} else {
					$data['created_at'] = current_time( 'mysql' );
					$wpdb->insert( $table_name, $data );
					$msg = 'added';
				}
				wp_redirect( admin_url( 'admin.php?page=an-resources&msg=' . $msg ) );
				exit;
			}
		}
	}

	public function enqueue_scripts( $hook ) {
		if ( 'agency-nexus_page_an-resources' !== $hook ) {
			return;
		}
		wp_enqueue_media();
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
		global $wpdb;
		$table_name = $wpdb->prefix . 'an_resources';
		$action = isset( $_GET['action'] ) ? $_GET['action'] : 'list';
		$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		if ( isset( $_GET['msg'] ) ) {
			$m = '';
			switch ( $_GET['msg'] ) {
				case 'added':   $m = 'Resource added!'; break;
				case 'updated': $m = 'Resource updated!'; break;
				case 'deleted': $m = 'Resource deleted!'; break;
			}
			if ( $m ) echo '<div class="updated"><p>' . esc_html( $m ) . '</p></div>';
		}

		if ($action === 'edit' || $action === 'add') {
			$resource = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id)) : null;
			?>
			<div class="wrap">
				<h1><?php echo $id ? __('Edit Resource', 'agency-nexus') : __('Add New Resource', 'agency-nexus'); ?></h1>
				<form method="post">
					<?php wp_nonce_field( 'an_save_resource_nonce' ); ?>
					<table class="form-table">
						<tr>
							<th><label for="title">Title</label></th>
							<td><input type="text" name="title" id="title" value="<?php echo $resource ? esc_attr($resource->title) : ''; ?>" class="regular-text" required></td>
						</tr>
						<tr>
							<th><label for="type">Type</label></th>
							<td>
								<select name="type" id="type">
									<option value="template" <?php selected($resource ? $resource->type : '', 'template'); ?>>Template</option>
									<option value="swipe" <?php selected($resource ? $resource->type : '', 'swipe'); ?>>Swipe File</option>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="file_url">File URL</label></th>
							<td>
								<input type="text" name="file_url" id="file_url" value="<?php echo $resource ? esc_attr($resource->file_url) : ''; ?>" class="regular-text">
								<button type="button" id="upload_resource_btn" class="button">Upload File</button>
							</td>
						</tr>
						<tr>
							<th><label for="content">Text Content / Description</label></th>
							<td><textarea name="content" id="content" class="regular-text"><?php echo $resource ? esc_textarea($resource->content) : ''; ?></textarea></td>
						</tr>
					</table>
					<p class="submit">
						<input type="submit" name="an_save_resource" class="button button-primary" value="Save Resource">
						<a href="?page=an-resources" class="button">Cancel</a>
					</p>
				</form>
			</div>
			<script>
			jQuery(document).ready(function($){
				$('#upload_resource_btn').click(function(e) {
					e.preventDefault();
					var frame = wp.media({ title: 'Upload Resource', multiple: false }).open().on('select', function(e){
						var attachment = frame.state().get('selection').first().toJSON();
						$('#file_url').val(attachment.url);
					});
				});
			});
			</script>
			<?php
			return;
		}

		$resources = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC" );
		$templates = array_filter( $resources, function($r) { return $r->type === 'template'; } );
		$swipes    = array_filter( $resources, function($r) { return $r->type === 'swipe'; } );

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e( 'Agency Resource Library', 'agency-nexus' ); ?></h1>
			<a href="?page=an-resources&action=add" class="page-title-action"><?php _e('Add New', 'agency-nexus'); ?></a>
			<hr class="wp-header-end">
			<p><?php _e( 'Guidance: Store and organize your agency assets here. Keep contract templates and marketing swipe files easily accessible for your team.', 'agency-nexus' ); ?></p>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php _e('Type', 'agency-nexus'); ?></th>
						<th><?php _e('Title', 'agency-nexus'); ?></th>
						<th><?php _e('Actions', 'agency-nexus'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ($resources) : foreach ($resources as $res) : ?>
						<tr>
							<td><span class="badge"><?php echo esc_html(ucfirst($res->type)); ?></span></td>
							<td><strong><?php echo esc_html($res->title); ?></strong></td>
							<td>
								<?php if ($res->file_url) : ?><a href="<?php echo esc_url($res->file_url); ?>" target="_blank"><?php _e('View', 'agency-nexus'); ?></a> | <?php endif; ?>
								<a href="?page=an-resources&action=edit&id=<?php echo $res->id; ?>"><?php _e('Edit', 'agency-nexus'); ?></a> |
								<a href="<?php echo wp_nonce_url( admin_url('admin.php?page=an-resources&action=delete&id=' . $res->id), 'an_delete_resource_' . $res->id ); ?>" style="color:red;" onclick="return confirm('Are you sure?')"><?php _e('Delete', 'agency-nexus'); ?></a>
							</td>
						</tr>
					<?php endforeach; else : ?>
						<tr><td colspan="3"><?php _e('No resources found.', 'agency-nexus'); ?></td></tr>
					<?php endif; ?>
				</tbody>
			</table>
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
