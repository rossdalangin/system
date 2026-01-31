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
		add_action( 'admin_init', [ $this, 'handle_post' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	public function handle_post() {
		if ( ! is_admin() || ! Agency_Nexus_Permissions::is_team_member() ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? $_GET['page'] : '';

		if ( 'an-expenses' === $page ) {
			$this->process_expense_actions();
		} elseif ( 'an-invoices' === $page ) {
			$this->process_invoice_actions();
		}
	}

	private function process_expense_actions() {
		global $wpdb;
		$expenses_table = $wpdb->prefix . 'an_expenses';
		$action = isset( $_GET['action'] ) ? $_GET['action'] : '';
		$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		if ( 'delete' === $action && $id ) {
			check_admin_referer( 'an_delete_expense_' . $id );
			$wpdb->delete( $expenses_table, [ 'id' => $id ] );
			wp_redirect( admin_url( 'admin.php?page=an-expenses&msg=deleted' ) );
			exit;
		}

		if ( isset( $_POST['an_save_expense'] ) && check_admin_referer( 'an_save_expense_nonce' ) ) {
			$data = [
				'project_id'  => intval( $_POST['project_id'] ),
				'amount'      => floatval( $_POST['amount'] ),
				'category'    => sanitize_text_field( $_POST['category'] ),
				'note'        => sanitize_textarea_field( $_POST['note'] ),
				'receipt_url' => esc_url_raw( $_POST['receipt_url'] ),
			];
			if ( $id ) {
				$wpdb->update( $expenses_table, $data, [ 'id' => $id ] );
				$msg = 'updated';
			} else {
				$data['created_at'] = current_time( 'mysql' );
				$wpdb->insert( $expenses_table, $data );
				$msg = 'recorded';
			}
			wp_redirect( admin_url( 'admin.php?page=an-expenses&msg=' . $msg ) );
			exit;
		}
	}

	private function process_invoice_actions() {
		global $wpdb;
		$invoices_table = $wpdb->prefix . 'an_invoices';
		$payments_table = $wpdb->prefix . 'an_payments';
		$action = isset( $_GET['action'] ) ? $_GET['action'] : '';
		$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		if ( 'delete' === $action && $id ) {
			check_admin_referer( 'an_delete_invoice_' . $id );
			$wpdb->delete( $invoices_table, [ 'id' => $id ] );
			wp_redirect( admin_url( 'admin.php?page=an-invoices&msg=deleted' ) );
			exit;
		}

		if ( isset( $_POST['an_save_invoice'] ) && check_admin_referer( 'an_save_invoice_nonce' ) ) {
			$data = [
				'project_id' => intval( $_POST['project_id'] ),
				'client_id'  => intval( $_POST['client_id'] ),
				'number'     => sanitize_text_field( $_POST['number'] ),
				'amount'     => floatval( $_POST['amount'] ),
				'status'     => sanitize_text_field( $_POST['status'] ),
				'due_date'   => sanitize_text_field( $_POST['due_date'] ),
			];
			if ( $id ) {
				$wpdb->update( $invoices_table, $data, [ 'id' => $id ] );
				$msg = 'updated';
			} else {
				$data['created_at'] = current_time( 'mysql' );
				$wpdb->insert( $invoices_table, $data );
				$msg = 'created';
			}
			wp_redirect( admin_url( 'admin.php?page=an-invoices&msg=' . $msg ) );
			exit;
		}

		if ( isset( $_POST['an_add_payment'] ) && check_admin_referer( 'an_add_payment_nonce' ) ) {
			$inv_id = intval( $_POST['id'] );
			$wpdb->insert( $payments_table, [
				'invoice_id'     => $inv_id,
				'amount'         => floatval( $_POST['pay_amount'] ),
				'method'         => 'other',
				'transaction_id' => '',
				'created_at'     => current_time( 'mysql' )
			] );
			$total_paid = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(amount) FROM $payments_table WHERE invoice_id = %d", $inv_id ) );
			$inv_amount = $wpdb->get_var( $wpdb->prepare( "SELECT amount FROM $invoices_table WHERE id = %d", $inv_id ) );
			if ( $total_paid >= $inv_amount ) {
				$wpdb->update( $invoices_table, [ 'status' => 'paid' ], [ 'id' => $inv_id ] );
			}
			wp_redirect( admin_url( 'admin.php?page=an-invoices&msg=paid' ) );
			exit;
		}
	}

	public function register_submenu() {
		if ( Agency_Nexus_Permissions::is_team_member() ) {
			add_submenu_page(
				'agency-nexus',
				__( 'Expenses', 'agency-nexus' ),
				__( 'Expenses', 'agency-nexus' ),
				'read',
				'an-expenses',
				[ $this, 'render_expenses' ]
			);

			add_submenu_page(
				'agency-nexus',
				__( 'Invoices', 'agency-nexus' ),
				__( 'Invoices', 'agency-nexus' ),
				'read',
				'an-invoices',
				[ $this, 'render_invoices' ]
			);
		}
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
		$action = isset( $_GET['action'] ) ? $_GET['action'] : 'list';
		$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		if ( isset( $_GET['msg'] ) ) {
			$m = '';
			switch ( $_GET['msg'] ) {
				case 'recorded': $m = 'Expense recorded!'; break;
				case 'updated':  $m = 'Expense updated!'; break;
				case 'deleted':  $m = 'Expense deleted!'; break;
			}
			if ( $m ) echo '<div class="updated"><p>' . esc_html( $m ) . '</p></div>';
		}

		if ( $action === 'edit' || $action === 'add' ) {
			$expense = $id ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $expenses_table WHERE id = %d", $id ) ) : null;
			$projects = $wpdb->get_results( "SELECT id, title FROM $projects_table" );
			?>
			<div class="wrap">
				<h1><?php echo $id ? __( 'Edit Expense', 'agency-nexus' ) : __( 'Add New Expense', 'agency-nexus' ); ?></h1>
				<form method="post">
					<?php wp_nonce_field( 'an_save_expense_nonce' ); ?>
					<table class="form-table">
						<tr>
							<th><label for="project_id">Project</label></th>
							<td>
								<select name="project_id" id="project_id">
									<option value="0"><?php _e( 'General / No Project', 'agency-nexus' ); ?></option>
									<?php foreach ( $projects as $project ) : ?>
										<option value="<?php echo $project->id; ?>" <?php selected( $expense ? $expense->project_id : 0, $project->id ); ?>><?php echo esc_html( $project->title ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="amount">Amount ($)</label></th>
							<td><input type="number" step="0.01" name="amount" id="amount" value="<?php echo $expense ? esc_attr($expense->amount) : ''; ?>" class="regular-text" required></td>
						</tr>
						<tr>
							<th><label for="category">Category</label></th>
							<td>
								<select name="category" id="category">
									<option value="software" <?php selected( $expense ? $expense->category : '', 'software' ); ?>><?php _e( 'Software / Tools', 'agency-nexus' ); ?></option>
									<option value="outsourcing" <?php selected( $expense ? $expense->category : '', 'outsourcing' ); ?>><?php _e( 'Outsourcing', 'agency-nexus' ); ?></option>
									<option value="marketing" <?php selected( $expense ? $expense->category : '', 'marketing' ); ?>><?php _e( 'Marketing', 'agency-nexus' ); ?></option>
									<option value="travel" <?php selected( $expense ? $expense->category : '', 'travel' ); ?>><?php _e( 'Travel', 'agency-nexus' ); ?></option>
									<option value="other" <?php selected( $expense ? $expense->category : '', 'other' ); ?>><?php _e( 'Other', 'agency-nexus' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="receipt_url">Receipt (URL or Upload)</label></th>
							<td>
								<input type="text" name="receipt_url" id="receipt_url" value="<?php echo $expense ? esc_attr($expense->receipt_url) : ''; ?>" class="regular-text">
								<button type="button" id="upload_receipt_button" class="button"><?php _e( 'Upload Receipt', 'agency-nexus' ); ?></button>
							</td>
						</tr>
						<tr>
							<th><label for="note">Note</label></th>
							<td><textarea name="note" id="note" class="regular-text"><?php echo $expense ? esc_textarea($expense->note) : ''; ?></textarea></td>
						</tr>
					</table>
					<p class="submit">
						<input type="submit" name="an_save_expense" class="button button-primary" value="<?php _e('Save Expense', 'agency-nexus'); ?>">
						<a href="?page=an-expenses" class="button"><?php _e('Cancel', 'agency-nexus'); ?></a>
					</p>
				</form>
			</div>
			<script>
			jQuery(document).ready(function($){
				$('#upload_receipt_button').click(function(e) {
					e.preventDefault();
					var frame = wp.media({ title: 'Upload Receipt', multiple: false }).open().on('select', function(e){
						var uploaded_image = frame.state().get('selection').first().toJSON();
						$('#receipt_url').val(uploaded_image.url);
					});
				});
			});
			</script>
			<?php
			return;
		}

		$expenses = $wpdb->get_results( "SELECT e.*, p.title as project_title FROM $expenses_table e LEFT JOIN $projects_table p ON e.project_id = p.id ORDER BY e.created_at DESC" );

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e( 'Expense Management', 'agency-nexus' ); ?></h1>
			<a href="?page=an-expenses&action=add" class="page-title-action"><?php _e('Add New', 'agency-nexus'); ?></a>
			<hr class="wp-header-end">
			<p><?php _e( 'Guidance: Track your agency and project-specific expenses here. You can upload receipts to keep your records organized for tax season.', 'agency-nexus' ); ?></p>

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
						<th>Actions</th>
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
							<td>
								<a href="?page=an-expenses&action=edit&id=<?php echo $expense->id; ?>"><?php _e('Edit', 'agency-nexus'); ?></a> |
								<a href="<?php echo wp_nonce_url('?page=an-expenses&action=delete&id=' . $expense->id, 'an_delete_expense_' . $expense->id); ?>" style="color:red;" onclick="return confirm('Delete this expense?')"><?php _e('Delete', 'agency-nexus'); ?></a>
							</td>
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
	 * Render the invoices management page.
	 */
	public function render_invoices() {
		global $wpdb;
		$invoices_table = $wpdb->prefix . 'an_invoices';
		$projects_table = $wpdb->prefix . 'an_projects';
		$clients_table  = $wpdb->prefix . 'an_clients';
		$payments_table = $wpdb->prefix . 'an_payments';

		$action = isset( $_GET['action'] ) ? $_GET['action'] : 'list';
		$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		if ( isset( $_GET['msg'] ) ) {
			$m = '';
			switch ( $_GET['msg'] ) {
				case 'created': $m = 'Invoice created!'; break;
				case 'updated': $m = 'Invoice updated!'; break;
				case 'deleted': $m = 'Invoice deleted!'; break;
				case 'paid':    $m = 'Payment recorded!'; break;
			}
			if ( $m ) echo '<div class="updated"><p>' . esc_html( $m ) . '</p></div>';
		}

		if ($action === 'print' && $id) {
			$invoice = $wpdb->get_row($wpdb->prepare("SELECT i.*, c.name as client_name, c.email as client_email, p.title as project_title FROM $invoices_table i JOIN $clients_table c ON i.client_id = c.id JOIN $projects_table p ON i.project_id = p.id WHERE i.id = %d", $id));
			?>
			<div class="wrap" id="printable-invoice" style="background: white; padding: 40px; font-family: sans-serif;">
				<div style="display:flex; justify-content: space-between;">
					<h1>INVOICE</h1>
					<div style="text-align:right;">
						<strong><?php echo esc_html($invoice->number); ?></strong><br>
						Date: <?php echo date('Y-m-d', strtotime($invoice->created_at)); ?><br>
						Due: <?php echo esc_html($invoice->due_date); ?>
					</div>
				</div>
				<hr>
				<div style="margin: 40px 0;">
					<strong>Bill To:</strong><br>
					<?php echo esc_html($invoice->client_name); ?><br>
					<?php echo esc_html($invoice->client_email); ?>
				</div>
				<table style="width:100%; border-collapse: collapse;">
					<thead><tr style="background:#eee;"><th style="padding:10px; text-align:left;">Description</th><th style="padding:10px; text-align:right;">Amount</th></tr></thead>
					<tbody>
						<tr>
							<td style="padding:10px; border-bottom:1px solid #eee;"><?php echo esc_html($invoice->project_title); ?></td>
							<td style="padding:10px; border-bottom:1px solid #eee; text-align:right;">$<?php echo number_format($invoice->amount, 2); ?></td>
						</tr>
					</tbody>
					<tfoot>
						<tr><td style="padding:10px; text-align:right;"><strong>Total:</strong></td><td style="padding:10px; text-align:right;"><strong>$<?php echo number_format($invoice->amount, 2); ?></strong></td></tr>
					</tfoot>
				</table>
				<div style="margin-top: 50px; text-align:center;">
					<button onclick="window.print()" class="button button-primary no-print">Print Invoice</button>
					<a href="?page=an-invoices" class="button no-print">Back</a>
				</div>
				<style>@media print { .no-print { display:none; } }</style>
			</div>
			<?php
			return;
		}

		if ($action === 'edit' || $action === 'add') {
			$invoice = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $invoices_table WHERE id = %d", $id)) : null;
			$projects = $wpdb->get_results("SELECT id, title, client_id FROM $projects_table");
			$clients = $wpdb->get_results("SELECT id, name FROM $clients_table");
			?>
			<div class="wrap">
				<h1><?php echo $id ? __('Edit Invoice', 'agency-nexus') : __('Create New Invoice', 'agency-nexus'); ?></h1>
				<form method="post">
					<?php wp_nonce_field('an_save_invoice_nonce'); ?>
					<table class="form-table">
						<tr><th>Invoice Number</th><td><input type="text" name="number" value="<?php echo $invoice ? esc_attr($invoice->number) : 'INV-' . time(); ?>" required></td></tr>
						<tr><th>Project</th><td>
							<select name="project_id" required>
								<?php foreach ($projects as $p) : ?>
									<option value="<?php echo $p->id; ?>" <?php selected($invoice ? $invoice->project_id : 0, $p->id); ?>><?php echo esc_html($p->title); ?></option>
								<?php endforeach; ?>
							</select>
						</td></tr>
						<tr><th>Client</th><td>
							<select name="client_id" required>
								<?php foreach ($clients as $c) : ?>
									<option value="<?php echo $c->id; ?>" <?php selected($invoice ? $invoice->client_id : 0, $c->id); ?>><?php echo esc_html($c->name); ?></option>
								<?php endforeach; ?>
							</select>
						</td></tr>
						<tr><th>Amount ($)</th><td><input type="number" step="0.01" name="amount" value="<?php echo $invoice ? esc_attr($invoice->amount) : ''; ?>" required></td></tr>
						<tr><th>Due Date</th><td><input type="date" name="due_date" value="<?php echo $invoice ? esc_attr($invoice->due_date) : ''; ?>" required></td></tr>
						<tr><th>Status</th><td>
							<select name="status">
								<option value="draft" <?php selected($invoice ? $invoice->status : '', 'draft'); ?>>Draft</option>
								<option value="sent" <?php selected($invoice ? $invoice->status : '', 'sent'); ?>>Sent</option>
								<option value="paid" <?php selected($invoice ? $invoice->status : '', 'paid'); ?>>Paid</option>
								<option value="overdue" <?php selected($invoice ? $invoice->status : '', 'overdue'); ?>>Overdue</option>
							</select>
						</td></tr>
					</table>
					<input type="submit" name="an_save_invoice" class="button button-primary" value="Save Invoice">
				</form>
			</div>
			<?php
			return;
		}

		$invoices = $wpdb->get_results("SELECT i.*, c.name as client_name, p.title as project_title FROM $invoices_table i JOIN $clients_table c ON i.client_id = c.id JOIN $projects_table p ON i.project_id = p.id ORDER BY i.created_at DESC");
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e('Invoices', 'agency-nexus'); ?></h1>
			<p><?php _e( 'Guidance: Create and manage client invoices. You can track payments against each invoice and print professional reports for your clients.', 'agency-nexus' ); ?></p>
			<a href="?page=an-invoices&action=add" class="page-title-action">Add New</a>
			<hr class="wp-header-end">

			<table class="wp-list-table widefat fixed striped">
				<thead><tr><th>Number</th><th>Project</th><th>Client</th><th>Amount</th><th>Status</th><th>Due</th><th>Actions</th></tr></thead>
				<tbody>
					<?php foreach ($invoices as $inv) :
						$total_paid = $wpdb->get_var($wpdb->prepare("SELECT SUM(amount) FROM $payments_table WHERE invoice_id = %d", $inv->id));
					?>
						<tr>
							<td><strong><?php echo esc_html($inv->number); ?></strong></td>
							<td><?php echo esc_html($inv->project_title); ?></td>
							<td><?php echo esc_html($inv->client_name); ?></td>
							<td>$<?php echo number_format($inv->amount, 2); ?> <br><small>Paid: $<?php echo number_format($total_paid, 2); ?></small></td>
							<td><span class="badge status-<?php echo $inv->status; ?>"><?php echo ucfirst($inv->status); ?></span></td>
							<td><?php echo esc_html($inv->due_date); ?></td>
							<td>
								<a href="?page=an-invoices&action=print&id=<?php echo $inv->id; ?>">Print</a> |
								<a href="?page=an-invoices&action=edit&id=<?php echo $inv->id; ?>">Edit</a> |
								<a href="<?php echo wp_nonce_url('?page=an-invoices&action=delete&id=' . $inv->id, 'an_delete_invoice_' . $inv->id); ?>" style="color:red;">Delete</a>
								<br>
								<form method="post" style="display:inline-block; margin-top:5px;">
									<?php wp_nonce_field('an_add_payment_nonce'); ?>
									<input type="hidden" name="id" value="<?php echo $inv->id; ?>">
									<input type="number" step="0.01" name="pay_amount" placeholder="Amt" style="width:60px;">
									<input type="submit" name="an_add_payment" value="Pay" class="button button-small">
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
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
		if ( ! Agency_Nexus_Permissions::is_team_member() ) {
			return;
		}
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
