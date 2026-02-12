<?php
/**
 * Email Handler Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Nexus_Email_Handler {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		add_action( 'phpmailer_init', [ $this, 'configure_smtp' ] );
		add_filter( 'wp_mail_from', [ $this, 'set_from_email' ] );
		add_filter( 'wp_mail_from_name', [ $this, 'set_from_name' ] );
	}

	/**
	 * Configure PHPMailer to use SMTP if enabled.
	 */
	public function configure_smtp( $phpmailer ) {
		if ( 'yes' !== get_option( 'an_smtp_enabled', 'no' ) ) {
			return;
		}

		$phpmailer->isSMTP();
		$phpmailer->Host       = get_option( 'an_smtp_host' );
		$phpmailer->SMTPAuth   = ( 'yes' === get_option( 'an_smtp_auth', 'no' ) );
		$phpmailer->Port       = get_option( 'an_smtp_port', 587 );
		$phpmailer->Username   = get_option( 'an_smtp_username' );
		$phpmailer->Password   = get_option( 'an_smtp_password' );
		$phpmailer->SMTPSecure = get_option( 'an_smtp_encryption', 'tls' );

		// If encryption is 'none', set SMTPSecure to empty string
		if ( 'none' === $phpmailer->SMTPSecure ) {
			$phpmailer->SMTPSecure = '';
		}
	}

	/**
	 * Set the "From" email address.
	 */
	public function set_from_email( $original_email ) {
		$from_email = get_option( 'an_email_from_address' );
		return ! empty( $from_email ) ? $from_email : $original_email;
	}

	/**
	 * Set the "From" name.
	 */
	public function set_from_name( $original_name ) {
		$from_name = get_option( 'an_email_from_name' );
		return ! empty( $from_name ) ? $from_name : $original_name;
	}

	/**
	 * Send a daily digest email to the administrator.
	 */
	public function send_daily_digest() {
		global $wpdb;
		$admin_email = get_option( 'admin_email' );

		// 1. Overdue Tasks
		$overdue_tasks = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}an_tasks WHERE status != 'completed' AND due_date < CURDATE()" );

		// 2. New Leads
		$new_leads = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}an_leads WHERE status = 'new' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)" );

		// 3. Urgent Social
		$urgent_social = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}an_social_interactions WHERE is_priority = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)" );

		if ( empty($overdue_tasks) && empty($new_leads) && empty($urgent_social) ) return;

		$subject = sprintf( __( '[Agency Nexus] Daily Briefing - %s', 'agency-nexus' ), date('Y-m-d') );

		ob_start();
		?>
		<h1>Daily Agency Briefing</h1>
		<p>Here is your overview for today.</p>

		<?php if ($overdue_tasks) : ?>
		<h2>⚠️ Overdue Tasks (<?php echo count($overdue_tasks); ?>)</h2>
		<ul>
			<?php foreach ($overdue_tasks as $t) : ?>
				<li><strong><?php echo esc_html($t->title); ?></strong> (Due: <?php echo $t->due_date; ?>)</li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>

		<?php if ($new_leads) : ?>
		<h2>💎 New Leads (<?php echo count($new_leads); ?>)</h2>
		<ul>
			<?php foreach ($new_leads as $l) : ?>
				<li><?php echo esc_html($l->name); ?> (<?php echo esc_html($l->email); ?>) - $<?php echo number_format($l->conversion_value); ?></li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>

		<?php if ($urgent_social) : ?>
		<h2>💬 Urgent Social Interactions (<?php echo count($urgent_social); ?>)</h2>
		<ul>
			<?php foreach ($urgent_social as $s) : ?>
				<li><strong>@<?php echo esc_html($s->username); ?></strong> (<?php echo $s->platform; ?>): <?php echo esc_html($s->content); ?></li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>

		<p><a href="<?php echo admin_url('admin.php?page=agency-nexus'); ?>">Open Dashboard</a></p>
		<?php
		$message = ob_get_clean();

		wp_mail( $admin_email, $subject, $message, [ 'Content-Type: text/html; charset=UTF-8' ] );
	}
}
