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
		add_action( 'admin_init', [ $this, 'handle_post' ] );
	}

	public function handle_post() {
		if ( ! is_admin() || ! Agency_Nexus_Permissions::is_team_member() ) {
			return;
		}

		if ( isset( $_GET['page'] ) && 'an-time-blocking' === $_GET['page'] ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'an_time_blocks';
			$action = isset( $_GET['action'] ) ? $_GET['action'] : '';
			$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

			if ( 'delete' === $action && $id ) {
				check_admin_referer( 'an_delete_block_' . $id );
				$wpdb->delete( $table_name, [ 'id' => $id ] );
				wp_redirect( admin_url( 'admin.php?page=an-time-blocking&msg=deleted' ) );
				exit;
			}

			if ( isset( $_POST['an_save_block'] ) && check_admin_referer( 'an_save_block_nonce' ) ) {
				$data = [
					'user_id'    => get_current_user_id(),
					'title'      => sanitize_text_field( $_POST['title'] ),
					'start_time' => ! empty( $_POST['start_time'] ) ? date( 'Y-m-d H:i:s', strtotime( $_POST['start_time'] ) ) : current_time( 'mysql' ),
					'end_time'   => ! empty( $_POST['end_time'] ) ? date( 'Y-m-d H:i:s', strtotime( $_POST['end_time'] ) ) : current_time( 'mysql' ),
					'type'       => sanitize_text_field( $_POST['type'] )
				];
				if ( $id ) {
					$wpdb->update( $table_name, $data, [ 'id' => $id ] );
					$msg = 'updated';
				} else {
					$wpdb->insert( $table_name, $data );
					$msg = 'added';
				}
				wp_redirect( admin_url( 'admin.php?page=an-time-blocking&msg=' . $msg ) );
				exit;
			}
		}
	}

	public function register_submenu() {
		if ( ! Agency_Nexus_License_Manager::get_instance()->is_feature_enabled( 'time_blocking' ) ) {
			return;
		}

		if ( Agency_Nexus_Permissions::is_team_member() ) {
			add_submenu_page(
				'agency-nexus',
				__( 'Time Blocking', 'agency-nexus' ),
				__( 'Time Blocking', 'agency-nexus' ),
				'read',
				'an-time-blocking',
				[ $this, 'render_dashboard' ]
			);
		}
	}

	public function render_dashboard() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'an_time_blocks';
		$user_id = get_current_user_id();
		$action = isset( $_GET['action'] ) ? $_GET['action'] : 'list';
		$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		if ( isset( $_GET['msg'] ) ) {
			$m = '';
			switch ( $_GET['msg'] ) {
				case 'added':   $m = 'Block added!'; break;
				case 'updated': $m = 'Block updated!'; break;
				case 'deleted': $m = 'Block deleted!'; break;
			}
			if ( $m ) echo '<div class="updated"><p>' . esc_html( $m ) . '</p></div>';
		}

		if ($action === 'edit' || $action === 'add') {
			$block = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id)) : null;
			?>
			<div class="agency-nexus-wrap">
				<h1><?php echo $id ? __('Edit Time Block', 'agency-nexus') : __('Add New Time Block', 'agency-nexus'); ?></h1>
				<p class="description"><?php _e('Schedule your focus periods to maximize productivity and avoid burnout.', 'agency-nexus'); ?></p>
				<form method="post">
					<?php wp_nonce_field('an_save_block_nonce'); ?>
					<table class="form-table">
						<tr>
							<th><label><?php _e('Title', 'agency-nexus'); ?></label></th>
							<td>
								<input type="text" name="title" value="<?php echo $block ? esc_attr($block->title) : ''; ?>" required class="regular-text">
								<p class="description"><?php _e('What are you working on? e.g., Code Review, Client Call', 'agency-nexus'); ?></p>
							</td>
						</tr>
						<tr>
							<th><label><?php _e('Start', 'agency-nexus'); ?></label></th>
							<td>
								<input type="datetime-local" name="start_time" value="<?php echo $block ? date('Y-m-d\TH:i', strtotime($block->start_time)) : ''; ?>" required>
								<p class="description"><?php _e('Beginning of the time block.', 'agency-nexus'); ?></p>
							</td>
						</tr>
						<tr>
							<th><label><?php _e('End', 'agency-nexus'); ?></label></th>
							<td>
								<input type="datetime-local" name="end_time" value="<?php echo $block ? date('Y-m-d\TH:i', strtotime($block->end_time)) : ''; ?>" required>
								<p class="description"><?php _e('End of the time block.', 'agency-nexus'); ?></p>
							</td>
						</tr>
						<tr>
							<th><label><?php _e('Type', 'agency-nexus'); ?></label></th>
							<td>
								<select name="type">
									<option value="deep_work" <?php selected($block ? $block->type : '', 'deep_work'); ?>>Deep Work</option>
									<option value="shallow_work" <?php selected($block ? $block->type : '', 'shallow_work'); ?>>Shallow Work</option>
									<option value="meeting" <?php selected($block ? $block->type : '', 'meeting'); ?>>Meeting</option>
									<option value="break" <?php selected($block ? $block->type : '', 'break'); ?>>Break</option>
								</select>
								<p class="description"><?php _e('Deep Work is for intense concentration; Shallow Work is for administrative tasks.', 'agency-nexus'); ?></p>
							</td>
						</tr>
					</table>
					<input type="submit" name="an_save_block" class="button button-primary" value="Save Block">
					<a href="?page=an-time-blocking" class="button">Cancel</a>
				</form>
			</div>
			<?php
			return;
		}

		$blocks = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name WHERE user_id = %d ORDER BY start_time ASC", $user_id ) );
		$this->get_template( 'dashboard', [ 'blocks' => $blocks ] );
	}

	public function render_dashboard_widget() {
		if ( ! Agency_Nexus_Permissions::is_team_member() ) {
			return;
		}
		?>
		<div class="postbox" style="padding: 20px;">
			<h2><?php _e( 'TimeBlock Pro', 'agency-nexus' ); ?></h2>
			<p><?php _e( 'Focus on what matters. Your next deep work block starts in 15 minutes.', 'agency-nexus' ); ?></p>
			<a href="<?php echo admin_url( 'admin.php?page=an-time-blocking' ); ?>" class="button"><?php _e( 'Manage Schedule', 'agency-nexus' ); ?></a>
		</div>
		<?php
	}
}
