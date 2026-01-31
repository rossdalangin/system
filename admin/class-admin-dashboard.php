<?php
/**
 * Admin Dashboard Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Nexus_Admin_Dashboard {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		// Use priority 5 to ensure this fires before modules (default 10)
		add_action( 'admin_menu', [ $this, 'register_menu' ], 5 );
		add_action( 'admin_init', [ $this, 'handle_admin_actions' ] );
	}

	/**
	 * Handle POST and GET actions before output starts.
	 */
	public function handle_admin_actions() {
		if ( ! is_admin() || ! Agency_Nexus_Permissions::can_access_nexus() ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? $_GET['page'] : '';

		if ( 'an-settings' === $page && isset( $_POST['an_save_global_settings'] ) && check_admin_referer( 'an_global_settings_nonce' ) ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			update_option( 'an_stripe_key', sanitize_text_field( $_POST['an_stripe_key'] ) );
			update_option( 'an_zapier_webhook', esc_url_raw( $_POST['an_zapier_webhook'] ) );
			update_option( 'an_hourly_rate', floatval( $_POST['an_hourly_rate'] ) );
			wp_redirect( admin_url( 'admin.php?page=an-settings&msg=saved' ) );
			exit;
		}

		if ( isset( $_POST['an_seed_data'] ) && check_admin_referer( 'an_seed_data_nonce' ) ) {
			Agency_Nexus_Seeder::seed();
			wp_redirect( admin_url( 'admin.php?page=agency-nexus&msg=seeded' ) );
			exit;
		}

		if ( isset( $_POST['an_reset_data'] ) && check_admin_referer( 'an_reset_data_nonce' ) ) {
			Agency_Nexus_Seeder::clear_all();
			wp_redirect( admin_url( 'admin.php?page=agency-nexus&msg=reset' ) );
			exit;
		}

		if ( 'an-clients' === $page ) {
			$this->process_client_actions();
		} elseif ( 'an-projects' === $page ) {
			$this->process_project_actions();
		}
	}

	/**
	 * Process client-related actions.
	 */
	private function process_client_actions() {
		if ( ! Agency_Nexus_Permissions::is_team_member() ) {
			return;
		}
		global $wpdb;
		$table_name = $wpdb->prefix . 'an_clients';
		$action = isset( $_GET['action'] ) ? $_GET['action'] : '';
		$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		if ( $id && ! Agency_Nexus_Permissions::can_view_project( $id ) ) {
			return;
		}

		// Handle Deletion
		if ( 'delete' === $action && $id ) {
			check_admin_referer( 'an_delete_client_' . $id );
			$wpdb->delete( $table_name, [ 'id' => $id ] );
			wp_redirect( admin_url( 'admin.php?page=an-clients&msg=deleted' ) );
			exit;
		}

		// Handle Save (Add/Edit)
		if ( isset( $_POST['an_save_client'] ) && check_admin_referer( 'an_save_client_nonce' ) ) {
			$data = [
				'name'    => sanitize_text_field( $_POST['name'] ),
				'email'   => sanitize_email( $_POST['email'] ),
				'company' => isset( $_POST['company'] ) ? sanitize_text_field( $_POST['company'] ) : ''
			];
			if ( $id ) {
				$wpdb->update( $table_name, $data, [ 'id' => $id ] );
				$msg = 'updated';
			} else {
				$wpdb->insert( $table_name, $data );
				$msg = 'added';
			}
			wp_redirect( admin_url( 'admin.php?page=an-clients&msg=' . $msg ) );
			exit;
		}
	}

	/**
	 * Process project-related actions.
	 */
	private function process_project_actions() {
		global $wpdb;
		$projects_table = $wpdb->prefix . 'an_projects';
		$tasks_table    = $wpdb->prefix . 'an_tasks';
		$time_table     = $wpdb->prefix . 'an_time_entries';
		$action = isset( $_GET['action'] ) ? $_GET['action'] : '';
		$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		// Handle Deletion
		if ( 'delete' === $action && $id ) {
			check_admin_referer( 'an_delete_project_' . $id );
			$wpdb->delete( $projects_table, [ 'id' => $id ] );
			wp_redirect( admin_url( 'admin.php?page=an-projects&msg=deleted' ) );
			exit;
		}

		// Handle Save (Add/Edit)
		if ( isset( $_POST['an_save_project'] ) && check_admin_referer( 'an_save_project_nonce' ) ) {
			if ( ! Agency_Nexus_Permissions::is_team_member() ) {
				return;
			}
			$data = [
				'client_id'   => intval( $_POST['client_id'] ),
				'assigned_to' => isset( $_POST['assigned_to'] ) ? intval( $_POST['assigned_to'] ) : 0,
				'title'       => sanitize_text_field( $_POST['title'] ),
				'budget'      => floatval( $_POST['budget'] ),
				'status'      => sanitize_text_field( $_POST['status'] ),
				'description' => sanitize_textarea_field( $_POST['description'] )
			];
			if ( $id ) {
				$wpdb->update( $projects_table, $data, [ 'id' => $id ] );
				$msg = 'updated';
			} else {
				$wpdb->insert( $projects_table, $data );
				$msg = 'created';
			}
			wp_redirect( admin_url( 'admin.php?page=an-projects&msg=' . $msg ) );
			exit;
		}

		// Handle Task Creation
		if ( isset( $_POST['an_add_task'] ) && check_admin_referer( 'an_add_task_nonce' ) ) {
			if ( ! Agency_Nexus_Permissions::is_team_member() ) {
				return;
			}
			$wpdb->insert( $tasks_table, [
				'project_id'  => intval( $_POST['project_id'] ),
				'title'       => sanitize_text_field( $_POST['title'] ),
				'assigned_to' => isset( $_POST['assigned_to'] ) ? intval( $_POST['assigned_to'] ) : 0,
				'status'      => 'todo',
				'priority'    => 'medium',
				'start_date'  => ! empty( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : date('Y-m-d'),
				'due_date'    => ! empty( $_POST['due_date'] ) ? sanitize_text_field( $_POST['due_date'] ) : date('Y-m-d', strtotime('+7 days'))
			] );
			wp_redirect( admin_url( 'admin.php?page=an-projects&action=view&id=' . intval( $_POST['project_id'] ) . '&msg=task_added' ) );
			exit;
		}

		// Handle Task Assignment Update
		if ( isset( $_POST['an_assign_task'] ) && check_admin_referer( 'an_assign_task_nonce' ) ) {
			if ( ! Agency_Nexus_Permissions::is_team_member() ) {
				return;
			}
			$wpdb->update( $tasks_table, [
				'assigned_to' => intval( $_POST['assigned_to'] )
			], [ 'id' => intval( $_POST['task_id'] ) ] );
			wp_redirect( admin_url( 'admin.php?page=an-projects&action=view&id=' . $id . '&msg=task_updated' ) );
			exit;
		}

		// Handle Time Logging
		if ( isset( $_POST['an_log_time'] ) && check_admin_referer( 'an_log_time_nonce' ) ) {
			$wpdb->insert( $time_table, [
				'task_id'  => intval( $_POST['task_id'] ),
				'user_id'  => get_current_user_id(),
				'duration' => intval( $_POST['hours'] ) * 3600,
				'date'     => current_time( 'mysql' ),
				'note'     => sanitize_textarea_field( $_POST['note'] )
			] );
			wp_redirect( admin_url( 'admin.php?page=an-projects&action=view&id=' . $id . '&msg=time_logged' ) );
			exit;
		}
	}

	/**
	 * Register the main menu and submenus.
	 */
	public function register_menu() {
		if ( ! Agency_Nexus_Permissions::can_access_nexus() ) {
			return;
		}

		add_menu_page(
			__( 'Agency Nexus', 'agency-nexus' ),
			__( 'Agency Nexus', 'agency-nexus' ),
			'read',
			'agency-nexus',
			[ $this, 'render_dashboard' ],
			'dashicons-chart-area',
			30
		);

		add_submenu_page(
			'agency-nexus',
			__( 'Dashboard', 'agency-nexus' ),
			__( 'Dashboard', 'agency-nexus' ),
			'read',
			'agency-nexus',
			[ $this, 'render_dashboard' ]
		);

		if ( Agency_Nexus_Permissions::is_team_member() ) {
			add_submenu_page(
				'agency-nexus',
				__( 'Clients', 'agency-nexus' ),
				__( 'Clients', 'agency-nexus' ),
				'read',
				'an-clients',
				[ $this, 'render_clients' ]
			);
		}

		add_submenu_page(
			'agency-nexus',
			__( 'Projects', 'agency-nexus' ),
			__( 'Projects', 'agency-nexus' ),
			'read',
			'an-projects',
			[ $this, 'render_projects' ]
		);

		if ( current_user_can( 'manage_options' ) ) {
			add_submenu_page(
				'agency-nexus',
				__( 'Team', 'agency-nexus' ),
				__( 'Team', 'agency-nexus' ),
				'manage_options',
				'an-team',
				[ $this, 'render_team' ]
			);

			add_submenu_page(
				'agency-nexus',
				__( 'Settings', 'agency-nexus' ),
				__( 'Settings', 'agency-nexus' ),
				'manage_options',
				'an-settings',
				[ $this, 'render_settings' ]
			);
		}
	}

	/**
	 * Render the clients page.
	 */
	public function render_clients() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'an_clients';
		$action = isset( $_GET['action'] ) ? $_GET['action'] : 'list';
		$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		if ( $id && ! Agency_Nexus_Permissions::can_view_project( $id ) ) {
			echo '<div class="error"><p>Unauthorized</p></div>';
			return;
		}

		if ( isset( $_GET['msg'] ) ) {
			$m = '';
			switch($_GET['msg']) {
				case 'added': $m = 'Client added!'; break;
				case 'updated': $m = 'Client updated!'; break;
				case 'deleted': $m = 'Client deleted!'; break;
			}
			if ($m) echo '<div class="updated"><p>' . esc_html($m) . '</p></div>';
		}

		if ($action === 'edit' || $action === 'add') {
			$client = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id)) : null;
			?>
			<div class="wrap">
				<h1><?php echo $id ? __('Edit Client', 'agency-nexus') : __('Add New Client', 'agency-nexus'); ?></h1>
				<p class="description"><?php _e('Register a new client to start managing their projects and communication. This record is for internal tracking.', 'agency-nexus'); ?></p>
				<form method="post">
					<?php wp_nonce_field('an_save_client_nonce'); ?>
					<table class="form-table">
						<tr>
							<th><label for="name">Name</label></th>
							<td>
								<input type="text" name="name" id="name" value="<?php echo $client ? esc_attr($client->name) : ''; ?>" class="regular-text" required>
								<p class="description"><?php _e('Full name of the client or primary contact. e.g., John Doe', 'agency-nexus'); ?></p>
							</td>
						</tr>
						<tr>
							<th><label for="email">Email</label></th>
							<td>
								<input type="email" name="email" id="email" value="<?php echo $client ? esc_attr($client->email) : ''; ?>" class="regular-text" required>
								<p class="description"><?php _e('The email address used for communication and to link their WordPress user account. e.g., john@example.com', 'agency-nexus'); ?></p>
							</td>
						</tr>
						<tr>
							<th><label for="company">Company</label></th>
							<td>
								<input type="text" name="company" id="company" value="<?php echo $client ? esc_attr($client->company) : ''; ?>" class="regular-text">
								<p class="description"><?php _e('The legal name of the client\'s organization. e.g., Acme Corp', 'agency-nexus'); ?></p>
							</td>
						</tr>
					</table>
					<p class="submit">
						<input type="submit" name="an_save_client" class="button button-primary" value="<?php _e('Save Client', 'agency-nexus'); ?>">
						<a href="?page=an-clients" class="button"><?php _e('Cancel', 'agency-nexus'); ?></a>
					</p>
				</form>
			</div>
			<?php
			return;
		}

		$clients_query = "SELECT * FROM $table_name";
		$authorised_ids = Agency_Nexus_Permissions::get_authorised_project_ids();
		if ( is_array( $authorised_ids ) ) {
			if ( empty( $authorised_ids ) ) {
				$clients_query .= " WHERE 1=0";
			} else {
				// Get clients belonging to authorised projects
				$authorised_client_ids = $wpdb->get_col( "SELECT client_id FROM {$wpdb->prefix}an_projects WHERE id IN (" . implode( ',', array_map( 'intval', $authorised_ids ) ) . ")" );
				if ( ! empty( $authorised_client_ids ) ) {
					$clients_query .= " WHERE id IN (" . implode( ',', array_map( 'intval', $authorised_client_ids ) ) . ")";
				} else {
					$clients_query .= " WHERE 1=0";
				}
			}
		}
		$clients_query .= " ORDER BY created_at DESC";
		$clients = $wpdb->get_results( $clients_query );
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e( 'Client Management', 'agency-nexus' ); ?></h1>
			<a href="?page=an-clients&action=add" class="page-title-action"><?php _e('Add New', 'agency-nexus'); ?></a>
			<hr class="wp-header-end">
			<p><?php _e('Guidance: These records are for internal project management. If you want a client to be able to log in, create a WordPress user for them and ensure their email matches here.', 'agency-nexus'); ?></p>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>ID</th>
						<th>Name</th>
						<th>Email</th>
						<th>Company</th>
						<th>Created</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( $clients ) : foreach ( $clients as $client ) : ?>
						<tr>
							<td><?php echo $client->id; ?></td>
							<td><strong><a href="?page=an-clients&action=edit&id=<?php echo $client->id; ?>"><?php echo esc_html( $client->name ); ?></a></strong></td>
							<td><?php echo esc_html( $client->email ); ?></td>
							<td><?php echo esc_html( $client->company ); ?></td>
							<td><?php echo $client->created_at; ?></td>
							<td>
								<a href="?page=an-clients&action=edit&id=<?php echo $client->id; ?>"><?php _e('Edit', 'agency-nexus'); ?></a> |
								<a href="<?php echo wp_nonce_url('?page=an-clients&action=delete&id=' . $client->id, 'an_delete_client_' . $client->id); ?>" style="color:red;" onclick="return confirm('Delete this client?')"><?php _e('Delete', 'agency-nexus'); ?></a>
							</td>
						</tr>
					<?php endforeach; else : ?>
						<tr><td colspan="6">No clients found.</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render the team management page.
	 */
	public function render_team() {
		if ( ! Agency_Nexus_Permissions::is_admin() ) {
			echo '<div class="error"><p>Unauthorized</p></div>'; return;
		}
		$users = get_users();
		$action = isset($_GET['action']) ? $_GET['action'] : 'list';
		$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

		if ($action === 'performance' && $user_id) {
			$this->render_performance_view($user_id);
			return;
		}

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e('Agency Team Management', 'agency-nexus'); ?></h1>
			<a href="<?php echo admin_url('user-new.php'); ?>" class="page-title-action"><?php _e('Add New Team Member', 'agency-nexus'); ?></a>
			<hr class="wp-header-end">

			<p><?php _e('Guidance: Manage your team members and monitor their workload and productivity. Agency Nexus uses standard WordPress users for team members. Click "Add New Team Member" to create a new WordPress user for your team.', 'agency-nexus'); ?></p>
			<p><strong><?php _e('How to assign tasks:', 'agency-nexus'); ?></strong> <?php _e('Navigate to a specific Project, and use the "Add Task" form to create and assign tasks to any of the team members listed below. Any WordPress user role (Subscriber to Administrator) can be assigned tasks.', 'agency-nexus'); ?></p>

			<table class="wp-list-table widefat fixed striped">
				<thead><tr><th>User</th><th>Email</th><th>WP Role</th><th>Productivity</th></tr></thead>
				<tbody>
					<?php foreach ($users as $user) : ?>
						<tr>
							<td><strong><?php echo esc_html($user->display_name); ?></strong></td>
							<td><?php echo esc_html($user->user_email); ?></td>
							<td><?php echo implode(', ', $user->roles); ?></td>
							<td>
								<a href="?page=an-team&action=performance&user_id=<?php echo $user->ID; ?>" class="button button-small"><?php _e('View Performance', 'agency-nexus'); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render detailed performance for a user.
	 */
	private function render_performance_view($user_id) {
		if ( ! Agency_Nexus_Permissions::is_admin() && $user_id != get_current_user_id() ) {
			echo '<div class="error"><p>Unauthorized</p></div>'; return;
		}
		global $wpdb;
		$user = get_userdata($user_id);
		$time_table = $wpdb->prefix . 'an_time_entries';
		$tasks_table = $wpdb->prefix . 'an_tasks';

		$total_seconds = $wpdb->get_var($wpdb->prepare("SELECT SUM(duration) FROM $time_table WHERE user_id = %d", $user_id));
		$total_hours = round($total_seconds / 3600, 2);

		$assigned_tasks = $wpdb->get_results($wpdb->prepare("SELECT * FROM $tasks_table WHERE assigned_to = %d", $user_id));

		?>
		<div class="wrap">
			<h1><?php echo sprintf(__('Performance Report: %s', 'agency-nexus'), esc_html($user->display_name)); ?></h1>
			<div class="postbox" style="padding: 20px; margin-top: 20px;">
				<h3>Overview</h3>
				<div style="display: flex; gap: 40px;">
					<div>
						<span style="font-size: 14px; color: #666;"><?php _e('Total Hours Logged', 'agency-nexus'); ?></span>
						<div style="font-size: 24px; font-weight: bold;"><?php echo $total_hours; ?> hrs</div>
					</div>
					<div>
						<span style="font-size: 14px; color: #666;"><?php _e('Active Tasks', 'agency-nexus'); ?></span>
						<div style="font-size: 24px; font-weight: bold;"><?php echo count($assigned_tasks); ?></div>
					</div>
				</div>

				<hr>
				<h3>Assigned Tasks</h3>
				<table class="wp-list-table widefat fixed striped">
					<thead><tr><th>Task</th><th>Project</th><th>Status</th></tr></thead>
					<tbody>
						<?php foreach ($assigned_tasks as $task) :
							$project_title = $wpdb->get_var($wpdb->prepare("SELECT title FROM {$wpdb->prefix}an_projects WHERE id = %d", $task->project_id));
						?>
							<tr>
								<td><?php echo esc_html($task->title); ?></td>
								<td><?php echo esc_html($project_title); ?></td>
								<td><?php echo esc_html($task->status); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<a href="?page=an-team" class="button"><?php _e('Back to Team', 'agency-nexus'); ?></a>
		</div>
		<?php
	}

	/**
	 * Render the projects page.
	 */
	public function render_projects() {
		global $wpdb;
		$projects_table = $wpdb->prefix . 'an_projects';
		$clients_table  = $wpdb->prefix . 'an_clients';
		$tasks_table    = $wpdb->prefix . 'an_tasks';
		$time_table     = $wpdb->prefix . 'an_time_entries';
		$action = isset( $_GET['action'] ) ? $_GET['action'] : 'list';
		$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		if ( isset( $_GET['msg'] ) ) {
			$m = '';
			switch ( $_GET['msg'] ) {
				case 'created': $m = 'Project created!'; break;
				case 'updated': $m = 'Project updated!'; break;
				case 'deleted': $m = 'Project deleted!'; break;
				case 'task_added': $m = 'Task added!'; break;
				case 'task_updated': $m = 'Task updated!'; break;
				case 'time_logged': $m = 'Time logged!'; break;
			}
			if ( $m ) echo '<div class="updated"><p>' . esc_html( $m ) . '</p></div>';
		}

		if ($action === 'view' && $id) {
			if ( ! Agency_Nexus_Permissions::can_view_project( $id ) ) {
				echo '<div class="error"><p>Unauthorized</p></div>'; return;
			}
			$is_admin = Agency_Nexus_Permissions::is_admin();
			$is_team = Agency_Nexus_Permissions::is_team_member();
			$project = $wpdb->get_row($wpdb->prepare("SELECT p.*, c.name as client_name FROM $projects_table p JOIN $clients_table c ON p.client_id = c.id WHERE p.id = %d", $id));

			$tasks_query = $wpdb->prepare("SELECT * FROM $tasks_table WHERE project_id = %d", $id);
			if ( ! $is_admin && $is_team ) {
				$tasks_query .= $wpdb->prepare(" AND assigned_to = %d", get_current_user_id());
			}
			$tasks = $wpdb->get_results($tasks_query);
			?>
			<div class="wrap">
				<h1><?php echo esc_html($project->title); ?> <small>(<?php echo esc_html($project->client_name); ?>)</small></h1>
				<div class="postbox" style="padding: 20px;">
					<h2>Details</h2>
					<p><strong>Status:</strong> <?php echo esc_html(ucfirst($project->status)); ?></p>
					<p><strong>Budget:</strong> $<?php echo number_format($project->budget, 2); ?></p>
					<p><strong>Description:</strong><br><?php echo nl2br(esc_html($project->description)); ?></p>

					<hr>
					<h3>Tasks & Time</h3>
					<table class="wp-list-table widefat fixed striped">
						<thead><tr><th>Task</th><th>Assigned To</th><th>Timeline</th><th>Status</th><th>Time Logged</th><th>Action</th></tr></thead>
						<tbody>
							<?php foreach ($tasks as $task) :
								$total_time = $wpdb->get_var($wpdb->prepare("SELECT SUM(duration) FROM $time_table WHERE task_id = %d", $task->id));
								$user = $task->assigned_to ? get_userdata($task->assigned_to) : null;
								$assigned = $user ? $user->display_name : 'Unassigned';
							?>
								<tr>
									<td><?php echo esc_html($task->title); ?></td>
									<td>
										<form method="post" style="display:inline-block;">
											<?php wp_nonce_field('an_assign_task_nonce'); ?>
											<input type="hidden" name="task_id" value="<?php echo $task->id; ?>">
											<select name="assigned_to" onchange="this.form.submit()">
												<option value="0"><?php _e('Unassigned', 'agency-nexus'); ?></option>
												<?php foreach (get_users() as $u) : ?>
													<option value="<?php echo $u->ID; ?>" <?php selected($task->assigned_to, $u->ID); ?>><?php echo esc_html($u->display_name); ?></option>
												<?php endforeach; ?>
											</select>
											<input type="hidden" name="an_assign_task" value="1">
										</form>
									</td>
									<td><small><?php echo esc_html($task->start_date); ?> to <?php echo esc_html($task->due_date); ?></small></td>
									<td><?php echo esc_html($task->status); ?></td>
									<td><?php echo round($total_time / 3600, 2); ?> hrs</td>
									<td>
										<form method="post" style="display:flex; gap: 5px;">
											<?php wp_nonce_field('an_log_time_nonce'); ?>
											<input type="hidden" name="task_id" value="<?php echo $task->id; ?>">
											<input type="number" name="hours" placeholder="Hrs" style="width: 50px;" required>
											<input type="submit" name="an_log_time" class="button" value="Log">
										</form>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<form method="post" style="margin-top: 15px; display:flex; flex-wrap: wrap; gap: 10px; align-items: center;">
						<?php wp_nonce_field('an_add_task_nonce'); ?>
						<input type="hidden" name="project_id" value="<?php echo $project->id; ?>">
						<input type="text" name="title" placeholder="New Task Title" required>
						<input type="date" name="start_date" title="Start Date" value="<?php echo date('Y-m-d'); ?>">
						<input type="date" name="due_date" title="Due Date" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
						<?php if ( $is_team ) : ?>
						<select name="assigned_to">
							<option value="0"><?php _e('Assign to...', 'agency-nexus'); ?></option>
							<?php foreach (get_users() as $u) : ?>
								<option value="<?php echo $u->ID; ?>"><?php echo esc_html($u->display_name); ?></option>
							<?php endforeach; ?>
						</select>
						<?php endif; ?>
						<input type="submit" name="an_add_task" class="button button-primary" value="Add Task">
					</form>
				</div>

				<div class="postbox" style="padding: 20px; margin-top: 20px;">
					<h3>Timeline Visualizer (Gantt)</h3>
					<?php $this->render_gantt_chart($tasks); ?>
				</div>
				<a href="?page=an-projects" class="button"><?php _e('Back to Projects', 'agency-nexus'); ?></a>
				<button onclick="window.print()" class="button"><?php _e('Print Report', 'agency-nexus'); ?></button>
			</div>
			<?php
			return;
		}

		if ($action === 'edit' || $action === 'add') {
			if ( ! Agency_Nexus_Permissions::is_team_member() ) {
				echo '<div class="error"><p>Unauthorized</p></div>'; return;
			}
			$project = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $projects_table WHERE id = %d", $id)) : null;
			$clients = $wpdb->get_results("SELECT id, name FROM $clients_table");
			?>
			<div class="wrap">
				<h1><?php echo $id ? __('Edit Project', 'agency-nexus') : __('Create New Project', 'agency-nexus'); ?></h1>
				<p class="description"><?php _e('Define the project scope, budget, and timeline for your client.', 'agency-nexus'); ?></p>
				<form method="post">
					<?php wp_nonce_field('an_save_project_nonce'); ?>
					<table class="form-table">
						<tr>
							<th><label for="client_id">Client</label></th>
							<td>
								<select name="client_id" id="client_id" required>
									<?php foreach ($clients as $client) : ?>
										<option value="<?php echo $client->id; ?>" <?php selected($project ? $project->client_id : 0, $client->id); ?>><?php echo esc_html($client->name); ?></option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php _e('Select the client who owns this project.', 'agency-nexus'); ?></p>
							</td>
						</tr>
						<tr>
							<th><label for="assigned_to"><?php _e('Project Lead / Assigned To', 'agency-nexus'); ?></label></th>
							<td>
								<select name="assigned_to" id="assigned_to">
									<option value="0"><?php _e('Unassigned', 'agency-nexus'); ?></option>
									<?php foreach (get_users() as $u) : ?>
										<option value="<?php echo $u->ID; ?>" <?php selected($project ? $project->assigned_to : 0, $u->ID); ?>><?php echo esc_html($u->display_name); ?></option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php _e('Assign an internal team member to manage this project.', 'agency-nexus'); ?></p>
							</td>
						</tr>
						<tr>
							<th><label for="title">Project Title</label></th>
							<td>
								<input type="text" name="title" id="title" value="<?php echo $project ? esc_attr($project->title) : ''; ?>" class="regular-text" required>
								<p class="description"><?php _e('Short, descriptive name for the project. e.g., Website Redesign 2024', 'agency-nexus'); ?></p>
							</td>
						</tr>
						<tr>
							<th><label for="budget">Budget ($)</label></th>
							<td>
								<input type="number" step="0.01" name="budget" id="budget" value="<?php echo $project ? esc_attr($project->budget) : '0.00'; ?>" class="regular-text">
								<p class="description"><?php _e('Total project value. Used for profitability tracking.', 'agency-nexus'); ?></p>
							</td>
						</tr>
						<tr>
							<th><label for="status">Status</label></th>
							<td>
								<select name="status" id="status">
									<option value="planned" <?php selected($project ? $project->status : '', 'planned'); ?>>Planned</option>
									<option value="in_progress" <?php selected($project ? $project->status : '', 'in_progress'); ?>>In Progress</option>
									<option value="on_hold" <?php selected($project ? $project->status : '', 'on_hold'); ?>>On Hold</option>
									<option value="completed" <?php selected($project ? $project->status : '', 'completed'); ?>>Completed</option>
								</select>
								<p class="description"><?php _e('The current stage of the project.', 'agency-nexus'); ?></p>
							</td>
						</tr>
						<tr>
							<th><label for="description">Description</label></th>
							<td>
								<textarea name="description" id="description" class="regular-text"><?php echo $project ? esc_textarea($project->description) : ''; ?></textarea>
								<p class="description"><?php _e('Detailed overview of goals and deliverables.', 'agency-nexus'); ?></p>
							</td>
						</tr>
					</table>
					<p class="submit">
						<input type="submit" name="an_save_project" class="button button-primary" value="<?php _e('Save Project', 'agency-nexus'); ?>">
						<a href="?page=an-projects" class="button"><?php _e('Cancel', 'agency-nexus'); ?></a>
					</p>
				</form>
			</div>
			<?php
			return;
		}

		$query = "SELECT p.*, c.name as client_name FROM $projects_table p JOIN $clients_table c ON p.client_id = c.id";
		$authorised_ids = Agency_Nexus_Permissions::get_authorised_project_ids();
		if ( is_array( $authorised_ids ) ) {
			if ( empty( $authorised_ids ) ) {
				$query .= " WHERE 1=0";
			} else {
				$query .= " WHERE p.id IN (" . implode( ',', array_map( 'intval', $authorised_ids ) ) . ")";
			}
		}
		$query .= " ORDER BY p.created_at DESC";
		$projects = $wpdb->get_results( $query );
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e( 'Project Management', 'agency-nexus' ); ?></h1>
			<?php if ( Agency_Nexus_Permissions::is_team_member() ) : ?>
			<a href="?page=an-projects&action=add" class="page-title-action"><?php _e('Add New', 'agency-nexus'); ?></a>
			<?php endif; ?>
			<hr class="wp-header-end">

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>Title</th>
						<th>Client</th>
						<th>Lead</th>
						<th>Budget</th>
						<th>Status</th>
						<th>Created</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( $projects ) : foreach ( $projects as $project ) : ?>
						<?php
							$lead_user = $project->assigned_to ? get_userdata($project->assigned_to) : null;
							$lead_name = $lead_user ? $lead_user->display_name : '<em>Unassigned</em>';
						?>
						<tr>
							<td><strong><a href="?page=an-projects&action=view&id=<?php echo $project->id; ?>"><?php echo esc_html( $project->title ); ?></a></strong></td>
							<td><?php echo esc_html( $project->client_name ); ?></td>
							<td><?php echo $lead_name; ?></td>
							<td>$<?php echo number_format($project->budget, 2); ?></td>
							<td><span class="badge status-<?php echo $project->status; ?>"><?php echo ucfirst($project->status); ?></span></td>
							<td><?php echo $project->created_at; ?></td>
							<td>
								<a href="?page=an-projects&action=view&id=<?php echo $project->id; ?>"><?php _e('View', 'agency-nexus'); ?></a>
								<?php if ( Agency_Nexus_Permissions::is_team_member() ) : ?>
								| <a href="?page=an-projects&action=edit&id=<?php echo $project->id; ?>"><?php _e('Edit', 'agency-nexus'); ?></a> |
								<a href="<?php echo wp_nonce_url('?page=an-projects&action=delete&id=' . $project->id, 'an_delete_project_' . $project->id); ?>" style="color:red;" onclick="return confirm('Delete this project?')"><?php _e('Delete', 'agency-nexus'); ?></a>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; else : ?>
						<tr><td colspan="6">No projects found.</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render a basic Gantt chart for tasks.
	 */
	private function render_gantt_chart($tasks) {
		if ( empty($tasks) ) {
			echo '<p>No tasks to display in timeline.</p>';
			return;
		}

		// Calculate date range
		$start_dates = array_map(function($t){ return strtotime($t->start_date); }, $tasks);
		$end_dates   = array_map(function($t){ return strtotime($t->due_date); }, $tasks);
		$min_date = min($start_dates);
		$max_date = max($end_dates);
		$total_days = ceil(($max_date - $min_date) / 86400) + 1;
		if ($total_days < 7) $total_days = 7;

		?>
		<div style="overflow-x: auto; background: #f9f9f9; padding: 15px; border: 1px solid #ddd;">
			<div style="min-width: 800px; position: relative;">
				<!-- Header with dates -->
				<div style="display: flex; border-bottom: 1px solid #ccc; margin-bottom: 10px;">
					<div style="width: 200px; font-weight: bold;">Task</div>
					<div style="flex: 1; display: flex;">
						<?php for($i=0; $i<$total_days; $i+=max(1, floor($total_days/10))):
							$d = date('M d', $min_date + ($i * 86400));
						?>
							<div style="flex: 1; font-size: 10px; border-left: 1px solid #eee; padding-left: 2px;"><?php echo $d; ?></div>
						<?php endfor; ?>
					</div>
				</div>

				<?php foreach($tasks as $task):
					$t_start = strtotime($task->start_date);
					$t_end   = strtotime($task->due_date);
					$offset = ($t_start - $min_date) / 86400;
					$duration = ($t_end - $t_start) / 86400 + 1;
					$left_pct = ($offset / $total_days) * 100;
					$width_pct = ($duration / $total_days) * 100;
					$color = ($task->status === 'completed') ? '#46b450' : '#0073aa';
				?>
					<div style="display: flex; height: 30px; align-items: center; border-bottom: 1px solid #eee;">
						<div style="width: 200px; font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo esc_attr($task->title); ?>">
							<?php echo esc_html($task->title); ?>
						</div>
						<div style="flex: 1; position: relative; height: 100%;">
							<div style="position: absolute; left: <?php echo $left_pct; ?>%; width: <?php echo $width_pct; ?>%; height: 12px; background: <?php echo $color; ?>; border-radius: 6px; top: 9px;" title="<?php echo $task->start_date; ?> to <?php echo $task->due_date; ?>"></div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the global settings page.
	 */
	public function render_settings() {
		if ( isset( $_GET['msg'] ) && 'saved' === $_GET['msg'] ) {
			echo '<div class="updated"><p>Settings saved!</p></div>';
		}
		?>
		<div class="wrap">
			<h1><?php _e( 'Agency Nexus Settings', 'agency-nexus' ); ?></h1>
			<p class="description"><?php _e('Configure your agency\'s global parameters for finances and external integrations.', 'agency-nexus'); ?></p>
			<form method="post">
				<?php wp_nonce_field( 'an_global_settings_nonce' ); ?>
				<table class="form-table">
					<tr>
						<th><label for="an_hourly_rate">Default Hourly Rate ($)</label></th>
						<td>
							<input type="number" name="an_hourly_rate" id="an_hourly_rate" value="<?php echo esc_attr( get_option( 'an_hourly_rate', 50 ) ); ?>" class="regular-text">
							<p class="description"><?php _e('Used to calculate internal labor costs in the Financial Dashboard. Default: $50/hr', 'agency-nexus'); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="an_stripe_key">Stripe Secret Key</label></th>
						<td>
							<input type="password" name="an_stripe_key" id="an_stripe_key" value="<?php echo esc_attr( get_option( 'an_stripe_key' ) ); ?>" class="regular-text">
							<p class="description"><?php _e('Required for automating subscription payments and invoice processing.', 'agency-nexus'); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="an_zapier_webhook">Zapier Webhook URL</label></th>
						<td>
							<input type="text" name="an_zapier_webhook" id="an_zapier_webhook" value="<?php echo esc_attr( get_option( 'an_zapier_webhook' ) ); ?>" class="large-text">
							<p class="description"><?php _e('Trigger external workflows in Zapier or Make.com when project milestones are met.', 'agency-nexus'); ?></p>
						</td>
					</tr>
				</table>
				<p class="submit">
					<input type="submit" name="an_save_global_settings" class="button button-primary" value="Save Settings">
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the main dashboard page.
	 */
	public function render_dashboard() {
		if ( isset( $_GET['msg'] ) ) {
			if ( 'seeded' === $_GET['msg'] ) {
				echo '<div class="updated"><p>Sample data seeded successfully! Created "Sample Client" and "Sample Team Member" accounts.</p></div>';
			} elseif ( 'reset' === $_GET['msg'] ) {
				echo '<div class="updated"><p>All Agency Nexus data has been cleared.</p></div>';
			}
		}
		$is_admin = current_user_can( 'manage_options' );
		?>
		<div class="wrap">
			<h1><?php _e( 'Agency Nexus Dashboard', 'agency-nexus' ); ?></h1>
			<p class="description"><?php _e( 'Your central command center for agency operations. Monitor project health, team productivity, and financial performance at a glance.', 'agency-nexus' ); ?></p>

			<?php if ( $is_admin ) : ?>
			<div class="welcome-panel" style="padding: 20px; margin-top: 20px;">
				<div class="welcome-panel-content">
					<h2>Getting Started</h2>
					<p>To help you explore the features, you can seed the dashboard with sample accounts and data.</p>
					<form method="post" style="display:flex; gap: 10px;">
						<?php wp_nonce_field('an_seed_data_nonce'); ?>
						<input type="submit" name="an_seed_data" class="button button-primary button-hero" value="Seed Sample Data">

						<?php wp_nonce_field('an_reset_data_nonce', 'an_reset_data_nonce'); ?>
						<input type="submit" name="an_reset_data" class="button button-link-delete" value="Clear All Data" onclick="return confirm('This will delete ALL agency projects, clients, and records. Continue?')">
					</form>
				</div>
			</div>
			<?php endif; ?>

			<div class="agency-nexus-widgets" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
				<?php do_action( 'agency_nexus_dashboard_widgets' ); ?>
			</div>
		</div>
		<?php
	}
}
