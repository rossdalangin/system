<?php
/**
 * ClientSync Module
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Nexus_Module_Clientsync extends Agency_Nexus_Base_Module {

	protected $name = 'ClientSync';

	public function init() {
		add_action( 'admin_menu', [ $this, 'register_submenu' ] );
		add_action( 'agency_nexus_dashboard_widgets', [ $this, 'render_dashboard_widget' ] );
		add_action( 'wp_ajax_an_send_message', [ $this, 'handle_send_message' ] );
	}

	public function register_submenu() {
		add_submenu_page(
			'agency-nexus',
			__( 'Messages', 'agency-nexus' ),
			__( 'Messages', 'agency-nexus' ),
			'manage_options',
			'an-messages',
			[ $this, 'render_messages' ]
		);
	}

	public function render_messages() {
		global $wpdb;
		$clients = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}an_clients" );
		$this->get_template( 'messages', [ 'clients' => $clients ] );
	}

	/**
	 * AJAX handler for sending messages.
	 */
	public function handle_send_message() {
		check_ajax_referer( 'an_message_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		global $wpdb;
		$client_id = intval( $_POST['client_id'] );
		$message   = sanitize_textarea_field( $_POST['message'] );

		$wpdb->insert(
			$wpdb->prefix . 'an_messages',
			[
				'client_id' => $client_id,
				'sender_id' => get_current_user_id(),
				'message'   => $message
			]
		);

		wp_send_json_success();
	}

	public function render_dashboard_widget() {
		?>
		<div class="postbox" style="padding: 20px;">
			<h2><?php _e( 'ClientSync', 'agency-nexus' ); ?></h2>
			<p><?php _e( 'You have 3 unread messages from clients.', 'agency-nexus' ); ?></p>
			<a href="<?php echo admin_url( 'admin.php?page=an-messages' ); ?>" class="button"><?php _e( 'Open Inbox', 'agency-nexus' ); ?></a>
		</div>
		<?php
	}
}
