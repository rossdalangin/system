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
		add_action( 'wp_ajax_an_get_messages', [ $this, 'handle_get_messages' ] );
		add_action( 'wp_ajax_an_share_file', [ $this, 'handle_share_file' ] );
		add_action( 'wp_ajax_an_get_files', [ $this, 'handle_get_files' ] );
		add_action( 'wp_ajax_an_delete_message', [ $this, 'handle_delete_message' ] );
		add_action( 'wp_ajax_an_delete_file', [ $this, 'handle_delete_file' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	public function enqueue_scripts( $hook ) {
		if ( 'agency-nexus_page_an-messages' !== $hook ) {
			return;
		}
		wp_enqueue_media();
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
	 * AJAX handler for fetching messages.
	 */
	public function handle_get_messages() {
		check_ajax_referer( 'an_message_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		global $wpdb;
		$client_id = intval( $_POST['client_id'] );

		$messages = $wpdb->get_results( $wpdb->prepare( "
			SELECT * FROM {$wpdb->prefix}an_messages
			WHERE client_id = %d
			ORDER BY created_at ASC
		", $client_id ) );

		wp_send_json_success( $messages );
	}

	/**
	 * AJAX handler for sharing files.
	 */
	public function handle_share_file() {
		check_ajax_referer( 'an_message_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		global $wpdb;
		$client_id = intval( $_POST['client_id'] );
		$file_url  = esc_url_raw( $_POST['file_url'] );
		$file_name = sanitize_text_field( $_POST['file_name'] );

		$wpdb->insert(
			$wpdb->prefix . 'an_shared_files',
			[
				'client_id' => $client_id,
				'user_id'   => get_current_user_id(),
				'file_url'  => $file_url,
				'file_name' => $file_name
			]
		);

		wp_send_json_success();
	}

	/**
	 * AJAX handler for deleting a message.
	 */
	public function handle_delete_message() {
		check_ajax_referer( 'an_message_nonce', 'security' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'an_messages', [ 'id' => intval( $_POST['message_id'] ) ] );
		wp_send_json_success();
	}

	/**
	 * AJAX handler for deleting a file.
	 */
	public function handle_delete_file() {
		check_ajax_referer( 'an_message_nonce', 'security' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'an_shared_files', [ 'id' => intval( $_POST['file_id'] ) ] );
		wp_send_json_success();
	}

	/**
	 * AJAX handler for fetching shared files.
	 */
	public function handle_get_files() {
		check_ajax_referer( 'an_message_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		global $wpdb;
		$client_id = intval( $_POST['client_id'] );

		$files = $wpdb->get_results( $wpdb->prepare( "
			SELECT * FROM {$wpdb->prefix}an_shared_files
			WHERE client_id = %d
			ORDER BY created_at DESC
		", $client_id ) );

		wp_send_json_success( $files );
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
