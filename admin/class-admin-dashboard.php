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
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
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

		// Modules will add their own submenus or sections.
	}

	/**
	 * Render the clients page.
	 */
	public function render_clients() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'an_clients';

		// Handle submission
		if ( isset( $_POST['an_add_client'] ) && check_admin_referer( 'an_add_client_nonce' ) ) {
			$wpdb->insert(
				$table_name,
				[
					'name'    => sanitize_text_field( $_POST['name'] ),
					'email'   => sanitize_email( $_POST['email'] ),
					'company' => sanitize_text_field( $_POST['company'] )
				]
			);
			echo '<div class="updated"><p>Client added!</p></div>';
		}

		$clients = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC" );
		?>
		<div class="wrap">
			<h1><?php _e( 'Client Management', 'agency-nexus' ); ?></h1>

			<div class="postbox" style="padding: 20px; margin-top: 20px;">
				<h2><?php _e( 'Add New Client', 'agency-nexus' ); ?></h2>
				<form method="post">
					<?php wp_nonce_field( 'an_add_client_nonce' ); ?>
					<table class="form-table">
						<tr>
							<th><label for="name">Name</label></th>
							<td><input type="text" name="name" id="name" class="regular-text" required></td>
						</tr>
						<tr>
							<th><label for="email">Email</label></th>
							<td><input type="email" name="email" id="email" class="regular-text" required></td>
						</tr>
						<tr>
							<th><label for="company">Company</label></th>
							<td><input type="text" name="company" id="company" class="regular-text"></td>
						</tr>
					</table>
					<p class="submit">
						<input type="submit" name="an_add_client" class="button button-primary" value="Add Client">
					</p>
				</form>
			</div>

			<h2><?php _e( 'Existing Clients', 'agency-nexus' ); ?></h2>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>ID</th>
						<th>Name</th>
						<th>Email</th>
						<th>Company</th>
						<th>Created</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( $clients ) : foreach ( $clients as $client ) : ?>
						<tr>
							<td><?php echo $client->id; ?></td>
							<td><?php echo esc_html( $client->name ); ?></td>
							<td><?php echo esc_html( $client->email ); ?></td>
							<td><?php echo esc_html( $client->company ); ?></td>
							<td><?php echo $client->created_at; ?></td>
						</tr>
					<?php endforeach; else : ?>
						<tr><td colspan="5">No clients found.</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
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

		// Handle project creation
		if ( isset( $_POST['an_add_project'] ) && check_admin_referer( 'an_add_project_nonce' ) ) {
			$wpdb->insert(
				$projects_table,
				[
					'client_id'   => intval( $_POST['client_id'] ),
					'title'       => sanitize_text_field( $_POST['title'] ),
					'budget'      => floatval( $_POST['budget'] ),
					'status'      => 'planned',
					'description' => sanitize_textarea_field( $_POST['description'] )
				]
			);
			echo '<div class="updated"><p>Project created!</p></div>';
		}

		// Handle task creation
		if ( isset( $_POST['an_add_task'] ) && check_admin_referer( 'an_add_task_nonce' ) ) {
			$wpdb->insert(
				$tasks_table,
				[
					'project_id' => intval( $_POST['project_id'] ),
					'title'      => sanitize_text_field( $_POST['title'] ),
					'status'     => 'todo',
					'priority'   => 'medium'
				]
			);
			echo '<div class="updated"><p>Task added!</p></div>';
		}

		// Handle time logging
		if ( isset( $_POST['an_log_time'] ) && check_admin_referer( 'an_log_time_nonce' ) ) {
			$wpdb->insert(
				$time_table,
				[
					'task_id'  => intval( $_POST['task_id'] ),
					'user_id'  => get_current_user_id(),
					'duration' => intval( $_POST['hours'] ) * 3600,
					'date'     => current_time( 'mysql' ),
					'note'     => sanitize_textarea_field( $_POST['note'] )
				]
			);
			echo '<div class="updated"><p>Time logged!</p></div>';
		}

		$projects = $wpdb->get_results( "SELECT p.*, c.name as client_name FROM $projects_table p JOIN $clients_table c ON p.client_id = c.id ORDER BY p.created_at DESC" );
		$clients  = $wpdb->get_results( "SELECT id, name FROM $clients_table" );
		?>
		<div class="wrap">
			<h1><?php _e( 'Project Management', 'agency-nexus' ); ?></h1>

			<div class="postbox" style="padding: 20px; margin-top: 20px;">
				<h2><?php _e( 'Create New Project', 'agency-nexus' ); ?></h2>
				<form method="post">
					<?php wp_nonce_field( 'an_add_project_nonce' ); ?>
					<table class="form-table">
						<tr>
							<th><label for="client_id">Client</label></th>
							<td>
								<select name="client_id" id="client_id" required>
									<?php foreach ( $clients as $client ) : ?>
										<option value="<?php echo $client->id; ?>"><?php echo esc_html( $client->name ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="title">Project Title</label></th>
							<td><input type="text" name="title" id="title" class="regular-text" required></td>
						</tr>
						<tr>
							<th><label for="budget">Budget ($)</label></th>
							<td><input type="number" step="0.01" name="budget" id="budget" class="regular-text" value="0.00"></td>
						</tr>
						<tr>
							<th><label for="description">Description</label></th>
							<td><textarea name="description" id="description" class="regular-text"></textarea></td>
						</tr>
					</table>
					<p class="submit">
						<input type="submit" name="an_add_project" class="button button-primary" value="Create Project">
					</p>
				</form>
			</div>

			<h2><?php _e( 'Existing Projects', 'agency-nexus' ); ?></h2>
			<?php if ( $projects ) : foreach ( $projects as $project ) :
				$tasks = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $tasks_table WHERE project_id = %d", $project->id ) );
				?>
				<div class="postbox" style="padding: 20px;">
					<h3><?php echo esc_html( $project->title ); ?> (<?php echo esc_html( $project->client_name ); ?>) - $<?php echo number_format($project->budget, 2); ?></h3>
					<p><?php echo esc_html( $project->description ); ?></p>

					<h4>Tasks</h4>
					<ul>
						<?php foreach ( $tasks as $task ) :
							$total_time = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(duration) FROM $time_table WHERE task_id = %d", $task->id ) );
							?>
							<li>
								<strong><?php echo esc_html( $task->title ); ?></strong> - <?php echo round($total_time / 3600, 2); ?> hours logged
								<form method="post" style="display:inline-block; margin-left: 10px;">
									<?php wp_nonce_field( 'an_log_time_nonce' ); ?>
									<input type="hidden" name="task_id" value="<?php echo $task->id; ?>">
									<input type="number" name="hours" placeholder="Hours" style="width: 60px;" required>
									<input type="submit" name="an_log_time" class="button" value="Log Time">
								</form>
							</li>
						<?php endforeach; ?>
					</ul>

					<form method="post" style="margin-top: 10px;">
						<?php wp_nonce_field( 'an_add_task_nonce' ); ?>
						<input type="hidden" name="project_id" value="<?php echo $project->id; ?>">
						<input type="text" name="title" placeholder="New Task Title" required>
						<input type="submit" name="an_add_task" class="button" value="Add Task">
					</form>
				</div>
			<?php endforeach; else : ?>
				<p>No projects found.</p>
			<?php endif; ?>
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
