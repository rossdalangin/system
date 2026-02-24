<?php
/**
 * Shortcodes Handler Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Nexus_Shortcodes {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		add_shortcode( 'an_lead_form', [ $this, 'render_lead_form' ] );
		add_shortcode( 'an_client_portal', [ $this, 'render_client_portal' ] );
		add_shortcode( 'an_marketplace', [ $this, 'render_marketplace' ] );
		add_shortcode( 'an_approval_portal', [ $this, 'render_approval_portal' ] );
	}

	/**
	 * Render Lead Capture Form.
	 */
	public function render_lead_form( $atts ) {
		if ( ! Agency_Nexus_License_Manager::get_instance()->is_feature_enabled( 'lead_intelligence' ) ) {
			return '<p>' . __( 'Lead capture requires a Pro or Agency license.', 'agency-nexus' ) . '</p>';
		}

		$api_url = get_rest_url( null, 'agency-nexus/v1/leads/capture' );

		ob_start();
		?>
		<div class="an-lead-form-wrap" style="max-width: 500px; margin: 0 auto; padding: 30px; border: 1px solid #eee; border-radius: 12px; background: #fff;">
			<form action="<?php echo esc_url( $api_url ); ?>" method="POST">
				<div style="margin-bottom: 15px;">
					<label style="display:block; margin-bottom:5px; font-weight:bold;"><?php _e('Name:', 'agency-nexus'); ?></label>
					<input type="text" name="name" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
				</div>
				<div style="margin-bottom: 15px;">
					<label style="display:block; margin-bottom:5px; font-weight:bold;"><?php _e('Email:', 'agency-nexus'); ?></label>
					<input type="email" name="email" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
				</div>
				<input type="hidden" name="source" value="Frontend Shortcode">
				<button type="submit" style="background: var(--an-indigo-600, #4f46e5); color: #fff; border: none; padding: 12px 25px; border-radius: 6px; cursor: pointer; font-weight: bold; width: 100%;">
					<?php _e('Get Started Now', 'agency-nexus'); ?>
				</button>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render Client Portal.
	 */
	public function render_client_portal() {
		if ( ! is_user_logged_in() ) {
			return '<p>' . __( 'Please log in to access your portal.', 'agency-nexus' ) . '</p>' . wp_login_form( ['echo' => false] );
		}

		if ( ! Agency_Nexus_Permissions::is_client() ) {
			return '<p>' . __( 'Access denied. This portal is for clients only.', 'agency-nexus' ) . '</p>';
		}

		// Since we're on the frontend, we might need to enqueue styles or provide a simplified dashboard.
		// For a prototype, we can use an iframe to the admin view or replicate parts of it.
		// Replicating parts is cleaner.

		ob_start();
		?>
		<div class="an-client-portal-wrap" style="font-family: sans-serif;">
			<div style="display: grid; grid-template-columns: 200px 1fr; gap: 30px;">
				<div style="background: #f8fafc; padding: 20px; border-radius: 8px;">
					<ul style="list-style:none; padding:0;">
						<li style="margin-bottom:10px;"><a href="?an_view=projects" style="text-decoration:none; color:#334155; font-weight:bold;"><?php _e('Projects', 'agency-nexus'); ?></a></li>
						<li style="margin-bottom:10px;"><a href="?an_view=messages" style="text-decoration:none; color:#334155; font-weight:bold;"><?php _e('Messages', 'agency-nexus'); ?></a></li>
						<li style="margin-bottom:10px;"><a href="?an_view=invoices" style="text-decoration:none; color:#334155; font-weight:bold;"><?php _e('Invoices', 'agency-nexus'); ?></a></li>
					</ul>
				</div>
				<div style="background: #fff; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
					<?php
						$view = isset($_GET['an_view']) ? $_GET['an_view'] : 'projects';
						switch($view) {
							case 'messages':
								$this->render_client_messages();
								break;
							case 'invoices':
								$this->render_client_invoices();
								break;
							default:
								$this->render_client_projects();
						}
					?>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	private function render_client_projects() {
		global $wpdb;
		$client_id = Agency_Nexus_Permissions::get_client_id_for_user(get_current_user_id());
		$projects = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}an_projects WHERE client_id = %d", $client_id));

		echo '<h2>' . __('Your Projects', 'agency-nexus') . '</h2>';
		echo '<table style="width:100%; text-align:left; border-collapse:collapse;">';
		echo '<thead><tr style="border-bottom:2px solid #eee;"><th style="padding:10px;">Project</th><th style="padding:10px;">Status</th></tr></thead>';
		echo '<tbody>';
		foreach($projects as $p) {
			echo '<tr style="border-bottom:1px solid #eee;">';
			echo '<td style="padding:10px;"><strong>'.esc_html($p->title).'</strong></td>';
			echo '<td style="padding:10px;"><span class="badge" style="background:#eee; padding:2px 8px; border-radius:4px;">'.ucfirst($p->status).'</span></td>';
			echo '</tr>';
		}
		if(empty($projects)) echo '<tr><td colspan="2">' . __('No active projects.', 'agency-nexus') . '</td></tr>';
		echo '</tbody></table>';
	}

	private function render_client_messages() {
		echo '<h2>' . __('Messages', 'agency-nexus') . '</h2>';
		echo '<p>' . __('Please use the dashboard messaging hub for secure collaboration.', 'agency-nexus') . '</p>';
		echo '<a href="'.admin_url('admin.php?page=an-messages').'" class="button">' . __('Open Messaging Hub', 'agency-nexus') . '</a>';
	}

	private function render_client_invoices() {
		global $wpdb;
		$client_id = Agency_Nexus_Permissions::get_client_id_for_user(get_current_user_id());
		$invoices = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}an_invoices WHERE client_id = %d", $client_id));

		echo '<h2>' . __('Your Invoices', 'agency-nexus') . '</h2>';
		echo '<table style="width:100%; text-align:left; border-collapse:collapse;">';
		echo '<thead><tr style="border-bottom:2px solid #eee;"><th style="padding:10px;">Invoice</th><th style="padding:10px;">Amount</th><th style="padding:10px;">Status</th></tr></thead>';
		echo '<tbody>';
		foreach($invoices as $i) {
			echo '<tr style="border-bottom:1px solid #eee;">';
			echo '<td style="padding:10px;">'.esc_html($i->number).'</td>';
			echo '<td style="padding:10px;">$'.number_format($i->amount, 2).'</td>';
			echo '<td style="padding:10px;">'.ucfirst($i->status).'</td>';
			echo '</tr>';
		}
		if(empty($invoices)) echo '<tr><td colspan="3">' . __('No invoices found.', 'agency-nexus') . '</td></tr>';
		echo '</tbody></table>';
	}

	/**
	 * Render Marketplace.
	 */
	/**
	 * Render Approval Portal.
	 */
	public function render_approval_portal() {
		if ( ! is_user_logged_in() || ! Agency_Nexus_Permissions::is_client() ) {
			return '<p>' . __( 'Access restricted to logged-in clients.', 'agency-nexus' ) . '</p>';
		}

		global $wpdb;
		$client_id = Agency_Nexus_Permissions::get_client_id_for_user( get_current_user_id() );
		$project_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}an_projects WHERE client_id = %d", $client_id ) );

		if ( empty($project_ids) ) return '<p>' . __('No active projects found.', 'agency-nexus') . '</p>';

		$in_clause = "(" . implode( ',', array_map( 'intval', $project_ids ) ) . ")";
		$pending_items = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}an_content WHERE project_id IN $in_clause AND status = 'pending_approval'" );

		ob_start();
		?>
		<div class="an-frontend-approval-portal">
			<h2><?php _e('Items Awaiting Your Approval', 'agency-nexus'); ?></h2>
			<?php if ( $pending_items ) : ?>
				<?php foreach ( $pending_items as $item ) : ?>
					<div style="border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 8px;">
						<h3><?php echo esc_html( $item->title ); ?></h3>
						<div style="background: #f9f9f9; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
							<?php echo wpautop( esc_html( $item->content ) ); ?>
						</div>
						<a href="<?php echo admin_url('admin.php?page=an-approvals&action=compare&id=' . $item->id); ?>" class="button"><?php _e('Review & Sign-off', 'agency-nexus'); ?></a>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<p><?php _e('All caught up! No items pending approval.', 'agency-nexus'); ?></p>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	public function render_marketplace() {
		global $wpdb;
		$items = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}an_resources WHERE is_marketplace = 1 ORDER BY created_at DESC" );

		ob_start();
		?>
		<div class="an-frontend-marketplace" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
			<?php foreach ( $items as $item ) : ?>
				<div style="border: 1px solid #ddd; padding: 20px; border-radius: 8px; text-align: center;">
					<h3><?php echo esc_html( $item->title ); ?></h3>
					<p style="font-weight: bold; color: var(--an-indigo-600, #4f46e5);">$<?php echo number_format($item->price, 2); ?></p>
					<p style="font-size: 14px; color: #666;"><?php echo esc_html( $item->content ); ?></p>
					<button style="background: #000; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;"><?php _e('Buy Now', 'agency-nexus'); ?></button>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
