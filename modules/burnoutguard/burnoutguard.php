<?php
/**
 * BurnoutGuard Module
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Nexus_Module_Burnoutguard extends Agency_Nexus_Base_Module {

	protected $name = 'BurnoutGuard';

	public function init() {
		add_action( 'admin_menu', [ $this, 'register_submenu' ] );
		add_action( 'agency_nexus_dashboard_widgets', [ $this, 'render_dashboard_widget' ] );
		add_action( 'admin_init', [ $this, 'handle_post' ] );
	}

	public function handle_post() {
		if ( ! is_admin() || ! Agency_Nexus_Permissions::is_team_member() ) {
			return;
		}

		if ( isset( $_GET['page'] ) && 'an-health-check' === $_GET['page'] ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'an_burnout_logs';
			$action = isset( $_GET['action'] ) ? $_GET['action'] : '';
			$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

			if ( 'delete' === $action && $id ) {
				check_admin_referer( 'an_delete_check_' . $id );
				$wpdb->delete( $table_name, [ 'id' => $id ] );
				wp_redirect( admin_url( 'admin.php?page=an-health-check&msg=deleted' ) );
				exit;
			}

			if ( isset( $_POST['an_save_check'] ) && check_admin_referer( 'an_save_check_nonce' ) ) {
				$data = [
					'user_id'      => get_current_user_id(),
					'stress_level' => intval( $_POST['stress_level'] ),
					'note'         => sanitize_textarea_field( $_POST['note'] ),
					'created_at'   => current_time( 'mysql' )
				];
				if ( $id ) {
					$wpdb->update( $table_name, $data, [ 'id' => $id ] );
					$msg = 'updated';
				} else {
					$wpdb->insert( $table_name, $data );
					$msg = 'recorded';
				}
				wp_redirect( admin_url( 'admin.php?page=an-health-check&msg=' . $msg ) );
				exit;
			}
		}
	}

	public function register_submenu() {
		if ( Agency_Nexus_Permissions::is_team_member() ) {
			add_submenu_page(
				'agency-nexus',
				__( 'Health Check', 'agency-nexus' ),
				__( 'Health Check', 'agency-nexus' ),
				'read',
				'an-health-check',
				[ $this, 'render_health_check' ]
			);
		}
	}

	public function render_health_check() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'an_burnout_logs';
		$action = isset( $_GET['action'] ) ? $_GET['action'] : 'list';
		$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		if ( isset( $_GET['msg'] ) ) {
			$m = '';
			switch ( $_GET['msg'] ) {
				case 'recorded': $m = 'Log recorded. Remember to take breaks!'; break;
				case 'updated': $m = 'Log updated!'; break;
				case 'deleted': $m = 'Log entry deleted!'; break;
			}
			if ( $m ) echo '<div class="updated"><p>' . esc_html( $m ) . '</p></div>';
		}

		if ($action === 'edit' || $action === 'add') {
			$log = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id)) : null;
			?>
			<div class="wrap">
				<h1><?php echo $id ? __('Edit Health Check', 'agency-nexus') : __('New Health Check', 'agency-nexus'); ?></h1>
				<p class="description"><?php _e('Log your mental well-being to monitor agency capacity and prevent burnout.', 'agency-nexus'); ?></p>
				<form method="post">
					<?php wp_nonce_field( 'an_save_check_nonce' ); ?>
					<table class="form-table">
						<tr>
							<th><label><?php _e('Stress Level (1-10)', 'agency-nexus'); ?></label></th>
							<td>
								<input type="range" name="stress_level" min="1" max="10" value="<?php echo $log ? $log->stress_level : 5; ?>" class="regular-text" oninput="this.nextElementSibling.value = this.value">
								<output><?php echo $log ? $log->stress_level : 5; ?></output>
								<p class="description"><?php _e('1 = Very relaxed, 10 = Critically stressed.', 'agency-nexus'); ?></p>
							</td>
						</tr>
						<tr>
							<th><label><?php _e('Notes', 'agency-nexus'); ?></label></th>
							<td>
								<textarea name="note" class="regular-text" rows="3"><?php echo $log ? esc_textarea($log->note) : ''; ?></textarea>
								<p class="description"><?php _e('Describe any factors affecting your well-being or workload.', 'agency-nexus'); ?></p>
							</td>
						</tr>
					</table>
					<input type="submit" name="an_save_check" class="button button-primary" value="Save Entry">
					<a href="?page=an-health-check" class="button">Cancel</a>
				</form>
			</div>
			<?php
			return;
		}

		$logs = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 20" );
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e( 'Health & Sustainability System', 'agency-nexus' ); ?></h1>
			<p><?php _e( 'Guidance: Use this system to monitor your mental well-being and agency capacity. Regular check-ins help identify burnout risks early.', 'agency-nexus' ); ?></p>
			<a href="?page=an-health-check&action=add" class="page-title-action">New Check-in</a>
			<hr class="wp-header-end">

			<h2>Recent Check-ins</h2>
			<table class="wp-list-table widefat fixed striped">
				<thead><tr><th>Date</th><th>Stress Level</th><th>Note</th><th>Actions</th></tr></thead>
				<tbody>
					<?php foreach ($logs as $log) :
						$color = $log->stress_level > 7 ? 'red' : ($log->stress_level > 4 ? 'orange' : 'green');
					?>
						<tr>
							<td><?php echo $log->created_at; ?></td>
							<td style="color: <?php echo $color; ?>; font-weight: bold;"><?php echo $log->stress_level; ?> / 10</td>
							<td><?php echo esc_html($log->note); ?></td>
							<td>
								<a href="?page=an-health-check&action=edit&id=<?php echo $log->id; ?>">Edit</a> |
								<a href="<?php echo wp_nonce_url('?page=an-health-check&action=delete&id=' . $log->id, 'an_delete_check_' . $log->id); ?>" style="color:red;" onclick="return confirm('Delete log?')">Delete</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public function render_dashboard_widget() {
		if ( ! Agency_Nexus_Permissions::is_team_member() ) {
			return;
		}
		global $wpdb;
		$time_table = $wpdb->prefix . 'an_time_entries';

		// Get hours logged in the last 7 days
		$last_week_seconds = $wpdb->get_var( "SELECT SUM(duration) FROM $time_table WHERE date >= DATE_SUB(NOW(), INTERVAL 7 DAY)" );
		$last_week_hours = $last_week_seconds / 3600;

		// Assuming 40 hours is 100% capacity
		$capacity = min(100, round(($last_week_hours / 40) * 100));
		$color = $capacity > 80 ? '#dc3232' : ($capacity > 50 ? '#ffb900' : '#46b450');

		?>
		<div class="postbox" style="padding: 20px;">
			<h2><?php _e( 'BurnoutGuard', 'agency-nexus' ); ?></h2>
			<p><strong><?php _e( 'Workload Capacity (Last 7 Days):', 'agency-nexus' ); ?></strong></p>
			<div style="text-align: center; margin: 20px 0;">
				<div style="display: inline-block; width: 100px; height: 100px; border-radius: 50%; border: 8px solid <?php echo $color; ?>; line-height: 84px; font-size: 24px; font-weight: bold;">
					<?php echo $capacity; ?>%
				</div>
				<?php if ($capacity > 80) : ?>
					<p style="color: #dc3232; font-weight: bold; margin-top: 10px;"><?php _e( 'High Workload Warning', 'agency-nexus' ); ?></p>
				<?php else : ?>
					<p style="color: #46b450; font-weight: bold; margin-top: 10px;"><?php _e( 'Healthy Workload', 'agency-nexus' ); ?></p>
				<?php endif; ?>
			</div>
			<p><small><?php echo sprintf( __( 'You have logged %.1f hours this week.', 'agency-nexus' ), $last_week_hours ); ?></small></p>
		</div>
		<?php
	}
}
