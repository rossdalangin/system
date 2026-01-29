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
		add_action( 'agency_nexus_dashboard_widgets', [ $this, 'render_dashboard_widget' ] );
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

		// For now, expenses are not in our DB schema, let's assume 0
		$expenses = 0;

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
		$total_budget = $wpdb->get_var( "SELECT SUM(budget) FROM {$wpdb->prefix}an_projects" );
		$total_budget = $total_budget ? $total_budget : 0;
		?>
		<div class="postbox" style="padding: 20px;">
			<h2><?php _e( 'MoneyFlow', 'agency-nexus' ); ?></h2>
			<p><?php _e( 'Projected revenue based on planned projects.', 'agency-nexus' ); ?></p>
			<div style="font-size: 24px; font-weight: bold; color: #46b450;">
				$<?php echo number_format( $total_budget, 2 ); ?> <span style="font-size: 14px; font-weight: normal; color: #666;"><?php _e( 'Total Pipeline Value', 'agency-nexus' ); ?></span>
			</div>
			<p><a href="#"><?php _e( 'View full financial report', 'agency-nexus' ); ?></a></p>
		</div>
		<?php
	}
}
