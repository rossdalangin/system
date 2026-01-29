<?php
/**
 * MoneyFlow Module
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Nexus_Module_Moneyflow extends Agency_Nexus_Base_Module {

	protected $name = 'MoneyFlow';

	public function init() {
		add_action( 'admin_menu', [ $this, 'register_submenu' ] );
		add_action( 'agency_nexus_dashboard_widgets', [ $this, 'render_dashboard_widget' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	public function register_submenu() {
		add_submenu_page(
			'agency-nexus',
			__( 'Expenses', 'agency-nexus' ),
			__( 'Expenses', 'agency-nexus' ),
			'manage_options',
			'an-expenses',
			[ $this, 'render_expenses' ]
		);
	}

	public function enqueue_scripts( $hook ) {
		if ( 'agency-nexus_page_an-expenses' !== $hook ) {
			return;
		}
		wp_enqueue_media();
	}

	/**
	 * Render the expenses management page.
	 */
	public function render_expenses() {
		global $wpdb;
		$projects_table = $wpdb->prefix . 'an_projects';
		$expenses_table = $wpdb->prefix . 'an_expenses';

		// Handle form submission
		if ( isset( $_POST['an_add_expense'] ) && check_admin_referer( 'an_add_expense_nonce' ) ) {
			$wpdb->insert(
				$expenses_table,
				[
					'project_id'  => intval( $_POST['project_id'] ),
					'amount'      => floatval( $_POST['amount'] ),
					'category'    => sanitize_text_field( $_POST['category'] ),
					'note'        => sanitize_textarea_field( $_POST['note'] ),
					'receipt_url' => esc_url_raw( $_POST['receipt_url'] ),
					'created_at'  => current_time( 'mysql' )
				]
			);
			echo '<div class="updated"><p>Expense recorded!</p></div>';
		}

		$projects = $wpdb->get_results( "SELECT id, title FROM $projects_table" );
		$expenses = $wpdb->get_results( "SELECT e.*, p.title as project_title FROM $expenses_table e LEFT JOIN $projects_table p ON e.project_id = p.id ORDER BY e.created_at DESC" );

		?>
		<div class="wrap">
			<h1><?php _e( 'Expense Management', 'agency-nexus' ); ?></h1>

			<div class="postbox" style="padding: 20px; margin-top: 20px;">
				<h2><?php _e( 'Add New Expense', 'agency-nexus' ); ?></h2>
				<form method="post">
					<?php wp_nonce_field( 'an_add_expense_nonce' ); ?>
					<table class="form-table">
						<tr>
							<th><label for="project_id">Project</label></th>
							<td>
								<select name="project_id" id="project_id">
									<option value="0"><?php _e( 'General / No Project', 'agency-nexus' ); ?></option>
									<?php foreach ( $projects as $project ) : ?>
										<option value="<?php echo $project->id; ?>"><?php echo esc_html( $project->title ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="amount">Amount ($)</label></th>
							<td><input type="number" step="0.01" name="amount" id="amount" class="regular-text" required></td>
						</tr>
						<tr>
							<th><label for="category">Category</label></th>
							<td>
								<select name="category" id="category">
									<option value="software"><?php _e( 'Software / Tools', 'agency-nexus' ); ?></option>
									<option value="outsourcing"><?php _e( 'Outsourcing', 'agency-nexus' ); ?></option>
									<option value="marketing"><?php _e( 'Marketing', 'agency-nexus' ); ?></option>
									<option value="travel"><?php _e( 'Travel', 'agency-nexus' ); ?></option>
									<option value="other"><?php _e( 'Other', 'agency-nexus' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="receipt_url">Receipt (URL or Upload)</label></th>
							<td>
								<input type="text" name="receipt_url" id="receipt_url" class="regular-text">
								<button type="button" id="upload_receipt_button" class="button"><?php _e( 'Upload Receipt', 'agency-nexus' ); ?></button>
							</td>
						</tr>
						<tr>
							<th><label for="note">Note</label></th>
							<td><textarea name="note" id="note" class="regular-text"></textarea></td>
						</tr>
					</table>
					<p class="submit">
						<input type="submit" name="an_add_expense" class="button button-primary" value="Add Expense">
					</p>
				</form>
			</div>

			<h2><?php _e( 'Expense History', 'agency-nexus' ); ?></h2>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>Date</th>
						<th>Project</th>
						<th>Category</th>
						<th>Amount</th>
						<th>Receipt</th>
						<th>Note</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( $expenses ) : foreach ( $expenses as $expense ) : ?>
						<tr>
							<td><?php echo date( 'Y-m-d', strtotime( $expense->created_at ) ); ?></td>
							<td><?php echo $expense->project_id ? esc_html( $expense->project_title ) : '<em>General</em>'; ?></td>
							<td><?php echo esc_html( ucfirst( $expense->category ) ); ?></td>
							<td>$<?php echo number_format( $expense->amount, 2 ); ?></td>
							<td><?php if ( $expense->receipt_url ) : ?><a href="<?php echo esc_url( $expense->receipt_url ); ?>" target="_blank">View Receipt</a><?php endif; ?></td>
							<td><?php echo esc_html( $expense->note ); ?></td>
						</tr>
					<?php endforeach; else : ?>
						<tr><td colspan="6">No expenses found.</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<script>
		jQuery(document).ready(function($){
			$('#upload_receipt_button').click(function(e) {
				e.preventDefault();
				var image = wp.media({
					title: 'Upload Receipt',
					multiple: false
				}).open()
				.on('select', function(e){
					var uploaded_image = image.state().get('selection').first();
					var image_url = uploaded_image.toJSON().url;
					$('#receipt_url').val(image_url);
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Calculate project profitability.
	 * Formula: (Total Budget - Total Expenses - (Total Hours * Hourly Rate))
	 */
	public function calculate_project_profitability( $project_id ) {
		global $wpdb;
		$table_projects = $wpdb->prefix . 'an_projects';
		$table_time = $wpdb->prefix . 'an_time_entries';
		$table_tasks = $wpdb->prefix . 'an_tasks';

		// Get project budget
		$budget = $wpdb->get_var( $wpdb->prepare( "SELECT budget FROM $table_projects WHERE id = %d", $project_id ) );

		// Get total hours spent
		$total_seconds = $wpdb->get_var( $wpdb->prepare( "
			SELECT SUM(t.duration)
			FROM $table_time t
			JOIN $table_tasks tk ON t.task_id = tk.id
			WHERE tk.project_id = %d
		", $project_id ) );

		$total_hours = $total_seconds / 3600;

		// Assuming a default hourly cost of 50 for the agency
		$hourly_cost = 50;
		$labor_cost = $total_hours * $hourly_cost;

		// Get expenses for project
		$expenses = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(amount) FROM {$wpdb->prefix}an_expenses WHERE project_id = %d", $project_id ) );
		$expenses = $expenses ? $expenses : 0;

		$profitability = $budget - $labor_cost - $expenses;

		return [
			'budget'        => $budget,
			'labor_cost'    => $labor_cost,
			'expenses'      => $expenses,
			'profitability' => $profitability,
			'margin'        => $budget > 0 ? ( $profitability / $budget ) * 100 : 0
		];
	}

	public function render_dashboard_widget() {
		global $wpdb;
		$projects = $wpdb->get_results( "SELECT id, budget FROM {$wpdb->prefix}an_projects" );

		$total_budget = 0;
		$total_labor  = 0;

		foreach ( $projects as $project ) {
			$profit = $this->calculate_project_profitability( $project->id );
			$total_budget += $profit['budget'];
			$total_labor  += $profit['labor_cost'];
		}

		$total_profit = $total_budget - $total_labor;
		$color = $total_profit >= 0 ? '#46b450' : '#dc3232';

		?>
		<div class="postbox" style="padding: 20px;">
			<h2><?php _e( 'MoneyFlow', 'agency-nexus' ); ?></h2>
			<p><?php _e( 'Real-time Profitability (All Projects):', 'agency-nexus' ); ?></p>
			<div style="font-size: 24px; font-weight: bold; color: <?php echo $color; ?>;">
				$<?php echo number_format( $total_profit, 2 ); ?>
			</div>
			<p><small>
				<?php echo sprintf( __( 'Revenue: $%.2f | Labor Cost: $%.2f', 'agency-nexus' ), $total_budget, $total_labor ); ?>
			</small></p>
			<p><a href="<?php echo admin_url('admin.php?page=an-projects'); ?>"><?php _e( 'Manage Projects', 'agency-nexus' ); ?></a></p>
		</div>
		<?php
	}
}
