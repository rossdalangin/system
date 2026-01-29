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
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
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

		// Handle adding
		if ( isset( $_POST['an_add_resource'] ) && check_admin_referer( 'an_add_resource_nonce' ) ) {
			$wpdb->insert(
				$table_name,
				[
					'title'      => sanitize_text_field( $_POST['title'] ),
					'type'       => sanitize_text_field( $_POST['type'] ),
					'file_url'   => esc_url_raw( $_POST['file_url'] ),
					'content'    => sanitize_textarea_field( $_POST['content'] ),
					'created_at' => current_time( 'mysql' )
				]
			);
			echo '<div class="updated"><p>Resource added!</p></div>';
		}

		// Handle deleting
		if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) ) {
			check_admin_referer( 'an_delete_resource_' . $_GET['id'] );
			$wpdb->delete( $table_name, [ 'id' => intval( $_GET['id'] ) ] );
			echo '<div class="updated"><p>Resource deleted!</p></div>';
		}

		$resources = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC" );
		$templates = array_filter( $resources, function($r) { return $r->type === 'template'; } );
		$swipes    = array_filter( $resources, function($r) { return $r->type === 'swipe'; } );

		?>
		<div class="wrap">
			<h1><?php _e( 'Agency Resource Library', 'agency-nexus' ); ?></h1>

			<div class="postbox" style="padding: 20px; margin-top: 20px;">
				<h2><?php _e( 'Add New Resource', 'agency-nexus' ); ?></h2>
				<form method="post">
					<?php wp_nonce_field( 'an_add_resource_nonce' ); ?>
					<table class="form-table">
						<tr>
							<th><label for="title">Title</label></th>
							<td><input type="text" name="title" id="title" class="regular-text" required></td>
						</tr>
						<tr>
							<th><label for="type">Type</label></th>
							<td>
								<select name="type" id="type">
									<option value="template">Template</option>
									<option value="swipe">Swipe File</option>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="file_url">File URL</label></th>
							<td>
								<input type="text" name="file_url" id="file_url" class="regular-text">
								<button type="button" id="upload_resource_btn" class="button">Upload File</button>
							</td>
						</tr>
						<tr>
							<th><label for="content">Text Content / Description</label></th>
							<td><textarea name="content" id="content" class="regular-text"></textarea></td>
						</tr>
					</table>
					<p class="submit">
						<input type="submit" name="an_add_resource" class="button button-primary" value="Add Resource">
					</p>
				</form>
			</div>

			<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
				<div class="card" style="padding: 15px; background: #fff; border: 1px solid #ddd;">
					<h3>📄 <?php _e( 'Contract & Proposal Templates', 'agency-nexus' ); ?></h3>
					<ul>
						<?php foreach ( $templates as $resource ) : ?>
							<li style="margin-bottom: 10px; display: flex; justify-content: space-between;">
								<span><?php echo esc_html( $resource->title ); ?></span>
								<div>
									<?php if ($resource->file_url) : ?><a href="<?php echo esc_url($resource->file_url); ?>" target="_blank">View</a> | <?php endif; ?>
									<a href="<?php echo wp_nonce_url( admin_url('admin.php?page=an-resources&action=delete&id=' . $resource->id), 'an_delete_resource_' . $resource->id ); ?>" style="color:red;" onclick="return confirm('Are you sure?')">Delete</a>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
				<div class="card" style="padding: 15px; background: #fff; border: 1px solid #ddd;">
					<h3>💡 <?php _e( 'Swipe Files & Assets', 'agency-nexus' ); ?></h3>
					<ul>
						<?php foreach ( $swipes as $resource ) : ?>
							<li style="margin-bottom: 10px; display: flex; justify-content: space-between;">
								<span><?php echo esc_html( $resource->title ); ?></span>
								<div>
									<?php if ($resource->file_url) : ?><a href="<?php echo esc_url($resource->file_url); ?>" target="_blank">View</a> | <?php endif; ?>
									<a href="<?php echo wp_nonce_url( admin_url('admin.php?page=an-resources&action=delete&id=' . $resource->id), 'an_delete_resource_' . $resource->id ); ?>" style="color:red;" onclick="return confirm('Are you sure?')">Delete</a>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($){
			$('#upload_resource_btn').click(function(e) {
				e.preventDefault();
				var frame = wp.media({
					title: 'Upload Resource',
					multiple: false
				}).open()
				.on('select', function(e){
					var attachment = frame.state().get('selection').first().toJSON();
					$('#file_url').val(attachment.url);
				});
			});
		});
		</script>
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
