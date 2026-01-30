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
	}

	/**
	 * Register the main menu and submenus.
	 */
	public function register_menu() {
		add_menu_page(
			__( 'Agency Nexus', 'agency-nexus' ),
			__( 'Agency Nexus', 'agency-nexus' ),
			'manage_options',
			'agency-nexus',
			[ $this, 'render_dashboard' ],
			'dashicons-chart-area',
			30
		);

		add_submenu_page(
			'agency-nexus',
			__( 'Dashboard', 'agency-nexus' ),
			__( 'Dashboard', 'agency-nexus' ),
			'manage_options',
			'agency-nexus',
			[ $this, 'render_dashboard' ]
		);

		add_submenu_page(
			'agency-nexus',
			__( 'Clients', 'agency-nexus' ),
			__( 'Clients', 'agency-nexus' ),
			'manage_options',
			'an-clients',
			[ $this, 'render_clients' ]
		);

		add_submenu_page(
			'agency-nexus',
			__( 'Projects', 'agency-nexus' ),
			__( 'Projects', 'agency-nexus' ),
			'manage_options',
			'an-projects',
			[ $this, 'render_projects' ]
		);

		add_submenu_page(
			'agency-nexus',
			__( 'Team', 'agency-nexus' ),
			__( 'Team', 'agency-nexus' ),
			'manage_options',
			'an-team',
			[ $this, 'render_team' ]
		);

		// Modules will add their own submenus or sections.
	}

	/**
	 * Render the clients page.
	 */
	public function render_clients() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'an_clients';
		$action = isset($_GET['action']) ? $_GET['action'] : 'list';
		$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

		// Handle Deletion
		if ($action === 'delete' && $id) {
			check_admin_referer('an_delete_client_' . $id);
			$wpdb->delete($table_name, ['id' => $id]);
			echo '<div class="updated"><p>Client deleted!</p></div>';
			$action = 'list';
		}

		// Handle Save (Add/Edit)
		if (isset($_POST['an_save_client']) && check_admin_referer('an_save_client_nonce')) {
			$data = [
				'name'    => sanitize_text_field($_POST['name']),
				'email'   => sanitize_email($_POST['email']),
				'company' => sanitize_text_field($_POST['company'])
			];
			if ($id) {
				$wpdb->update($table_name, $data, ['id' => $id]);
				echo '<div class="updated"><p>Client updated!</p></div>';
			} else {
				$wpdb->insert($table_name, $data);
				echo '<div class="updated"><p>Client added!</p></div>';
			}
			$action = 'list';
		}

		if ($action === 'edit' || $action === 'add') {
			$client = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id)) : null;
			?>
			<div class="wrap">
				<h1><?php echo $id ? __('Edit Client', 'agency-nexus') : __('Add New Client', 'agency-nexus'); ?></h1>
				<form method="post">
					<?php wp_nonce_field('an_save_client_nonce'); ?>
					<table class="form-table">
						<tr>
							<th><label for="name">Name</label></th>
							<td><input type="text" name="name" id="name" value="<?php echo $client ? esc_attr($client->name) : ''; ?>" class="regular-text" required></td>
						</tr>
						<tr>
							<th><label for="email">Email</label></th>
							<td><input type="email" name="email" id="email" value="<?php echo $client ? esc_attr($client->email) : ''; ?>" class="regular-text" required></td>
						</tr>
						<tr>
							<th><label for="company">Company</label></th>
							<td><input type="text" name="company" id="company" value="<?php echo $client ? esc_attr($client->company) : ''; ?>" class="regular-text"></td>
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

		$clients = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC" );
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e( 'Client Management', 'agency-nexus' ); ?></h1>
			<a href="?page=an-clients&action=add" class="page-title-action"><?php _e('Add New', 'agency-nexus'); ?></a>
			<hr class="wp-header-end">

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
		$users = get_users();
		$action = isset($_GET['action']) ? $_GET['action'] : 'list';
		$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

		if ($action === 'performance' && $user_id) {
			$this->render_performance_view($user_id);
			return;
		}

		?>
		<div class="wrap">
			<h1><?php _e('Agency Team Management', 'agency-nexus'); ?></h1>
			<p><?php _e('Guidance: Manage your team members and monitor their workload and productivity. Click "View Performance" to see detailed metrics for a specific member.', 'agency-nexus'); ?></p>

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
		$action = isset($_GET['action']) ? $_GET['action'] : 'list';
		$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

		// Handle Deletion
		if ($action === 'delete' && $id) {
			check_admin_referer('an_delete_project_' . $id);
			$wpdb->delete($projects_table, ['id' => $id]);
			echo '<div class="updated"><p>Project deleted!</p></div>';
			$action = 'list';
		}

		// Handle Save (Add/Edit)
		if (isset($_POST['an_save_project']) && check_admin_referer('an_save_project_nonce')) {
			$data = [
				'client_id'   => intval($_POST['client_id']),
				'title'       => sanitize_text_field($_POST['title']),
				'budget'      => floatval($_POST['budget']),
				'status'      => sanitize_text_field($_POST['status']),
				'description' => sanitize_textarea_field($_POST['description'])
			];
			if ($id) {
				$wpdb->update($projects_table, $data, ['id' => $id]);
				echo '<div class="updated"><p>Project updated!</p></div>';
			} else {
				$wpdb->insert($projects_table, $data);
				echo '<div class="updated"><p>Project created!</p></div>';
			}
			$action = 'list';
		}

		// Handle Task Creation
		if (isset($_POST['an_add_task']) && check_admin_referer('an_add_task_nonce')) {
			$wpdb->insert($tasks_table, [
				'project_id'  => intval($_POST['project_id']),
				'title'       => sanitize_text_field($_POST['title']),
				'assigned_to' => isset($_POST['assigned_to']) ? intval($_POST['assigned_to']) : 0,
				'status'      => 'todo',
				'priority'    => 'medium'
			]);
			echo '<div class="updated"><p>Task added!</p></div>';
		}

		// Handle Time Logging
		if (isset($_POST['an_log_time']) && check_admin_referer('an_log_time_nonce')) {
			$wpdb->insert($time_table, [
				'task_id'  => intval($_POST['task_id']),
				'user_id'  => get_current_user_id(),
				'duration' => intval($_POST['hours']) * 3600,
				'date'     => current_time('mysql'),
				'note'     => sanitize_textarea_field($_POST['note'])
			]);
			echo '<div class="updated"><p>Time logged!</p></div>';
		}

		if ($action === 'view' && $id) {
			$project = $wpdb->get_row($wpdb->prepare("SELECT p.*, c.name as client_name FROM $projects_table p JOIN $clients_table c ON p.client_id = c.id WHERE p.id = %d", $id));
			$tasks = $wpdb->get_results($wpdb->prepare("SELECT * FROM $tasks_table WHERE project_id = %d", $id));
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
						<thead><tr><th>Task</th><th>Assigned To</th><th>Status</th><th>Time Logged</th><th>Action</th></tr></thead>
						<tbody>
							<?php foreach ($tasks as $task) :
								$total_time = $wpdb->get_var($wpdb->prepare("SELECT SUM(duration) FROM $time_table WHERE task_id = %d", $task->id));
								$user = $task->assigned_to ? get_userdata($task->assigned_to) : null;
								$assigned = $user ? $user->display_name : 'Unassigned';
							?>
								<tr>
									<td><?php echo esc_html($task->title); ?></td>
									<td><?php echo esc_html($assigned); ?></td>
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
					<form method="post" style="margin-top: 15px; display:flex; gap: 10px; align-items: center;">
						<?php wp_nonce_field('an_add_task_nonce'); ?>
						<input type="hidden" name="project_id" value="<?php echo $project->id; ?>">
						<input type="text" name="title" placeholder="New Task Title" required>
						<select name="assigned_to">
							<option value="0"><?php _e('Assign to...', 'agency-nexus'); ?></option>
							<?php foreach (get_users() as $u) : ?>
								<option value="<?php echo $u->ID; ?>"><?php echo esc_html($u->display_name); ?></option>
							<?php endforeach; ?>
						</select>
						<input type="submit" name="an_add_task" class="button button-primary" value="Add Task">
					</form>
				</div>
				<a href="?page=an-projects" class="button"><?php _e('Back to Projects', 'agency-nexus'); ?></a>
				<button onclick="window.print()" class="button"><?php _e('Print Report', 'agency-nexus'); ?></button>
			</div>
			<?php
			return;
		}

		if ($action === 'edit' || $action === 'add') {
			$project = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $projects_table WHERE id = %d", $id)) : null;
			$clients = $wpdb->get_results("SELECT id, name FROM $clients_table");
			?>
			<div class="wrap">
				<h1><?php echo $id ? __('Edit Project', 'agency-nexus') : __('Create New Project', 'agency-nexus'); ?></h1>
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
							</td>
						</tr>
						<tr>
							<th><label for="title">Project Title</label></th>
							<td><input type="text" name="title" id="title" value="<?php echo $project ? esc_attr($project->title) : ''; ?>" class="regular-text" required></td>
						</tr>
						<tr>
							<th><label for="budget">Budget ($)</label></th>
							<td><input type="number" step="0.01" name="budget" id="budget" value="<?php echo $project ? esc_attr($project->budget) : '0.00'; ?>" class="regular-text"></td>
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
							</td>
						</tr>
						<tr>
							<th><label for="description">Description</label></th>
							<td><textarea name="description" id="description" class="regular-text"><?php echo $project ? esc_textarea($project->description) : ''; ?></textarea></td>
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

		$projects = $wpdb->get_results( "SELECT p.*, c.name as client_name FROM $projects_table p JOIN $clients_table c ON p.client_id = c.id ORDER BY p.created_at DESC" );
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e( 'Project Management', 'agency-nexus' ); ?></h1>
			<a href="?page=an-projects&action=add" class="page-title-action"><?php _e('Add New', 'agency-nexus'); ?></a>
			<hr class="wp-header-end">

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>Title</th>
						<th>Client</th>
						<th>Budget</th>
						<th>Status</th>
						<th>Created</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( $projects ) : foreach ( $projects as $project ) : ?>
						<tr>
							<td><strong><a href="?page=an-projects&action=view&id=<?php echo $project->id; ?>"><?php echo esc_html( $project->title ); ?></a></strong></td>
							<td><?php echo esc_html( $project->client_name ); ?></td>
							<td>$<?php echo number_format($project->budget, 2); ?></td>
							<td><span class="badge status-<?php echo $project->status; ?>"><?php echo ucfirst($project->status); ?></span></td>
							<td><?php echo $project->created_at; ?></td>
							<td>
								<a href="?page=an-projects&action=view&id=<?php echo $project->id; ?>"><?php _e('View', 'agency-nexus'); ?></a> |
								<a href="?page=an-projects&action=edit&id=<?php echo $project->id; ?>"><?php _e('Edit', 'agency-nexus'); ?></a> |
								<a href="<?php echo wp_nonce_url('?page=an-projects&action=delete&id=' . $project->id, 'an_delete_project_' . $project->id); ?>" style="color:red;" onclick="return confirm('Delete this project?')"><?php _e('Delete', 'agency-nexus'); ?></a>
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
	 * Render the main dashboard page.
	 */
	public function render_dashboard() {
		?>
		<div class="wrap">
			<h1><?php _e( 'Agency Nexus Dashboard', 'agency-nexus' ); ?></h1>
			<p><?php _e( 'Welcome to your comprehensive agency management dashboard.', 'agency-nexus' ); ?></p>

			<div class="agency-nexus-widgets" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
				<?php do_action( 'agency_nexus_dashboard_widgets' ); ?>
			</div>
		</div>
		<?php
	}
}
