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
			'read',
			'an-messages',
			[ $this, 'render_messages' ]
		);
	}

	public function render_messages() {
		global $wpdb;
		$is_team = Agency_Nexus_Permissions::is_team_member();
		$is_admin = Agency_Nexus_Permissions::is_admin();
		?>
		<div class="wrap">
			<h1><?php _e('Messaging Hub', 'agency-nexus'); ?></h1>
			<p class="description"><?php _e('Collaborate with clients in real-time. Share project updates, files, and feedback within a secure, dedicated environment.', 'agency-nexus'); ?></p>
		</div>
		<?php
		if ( $is_admin ) {
			$clients = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}an_clients" );
			$responses = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}an_canned_responses" );
		} elseif ( $is_team ) {
			$authorised_ids = Agency_Nexus_Permissions::get_authorised_project_ids();
			if ( ! empty($authorised_ids) ) {
				$authorised_client_ids = $wpdb->get_col( "SELECT client_id FROM {$wpdb->prefix}an_projects WHERE id IN (" . implode( ',', array_map( 'intval', $authorised_ids ) ) . ")" );
				if ( ! empty($authorised_client_ids) ) {
					$clients = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}an_clients WHERE id IN (" . implode( ',', array_map( 'intval', $authorised_client_ids ) ) . ")" );
				} else {
					$clients = [];
				}
			} else {
				$clients = [];
			}
			$responses = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}an_canned_responses" );
		} else {
			$client_id = Agency_Nexus_Permissions::get_client_id_for_user( get_current_user_id() );
			$clients = $wpdb->get_results( $wpdb->prepare( "SELECT id, name FROM {$wpdb->prefix}an_clients WHERE id = %d", $client_id ) );
			$responses = []; // Clients don't get canned responses
		}

		$this->get_template( 'messages', [ 'clients' => $clients, 'responses' => $responses, 'is_team' => $is_team ] );
	}

	/**
	 * AJAX handler for fetching messages.
	 */
	public function handle_get_messages() {
		check_ajax_referer( 'an_message_nonce', 'security' );

		$client_id = intval( $_POST['client_id'] );
		if ( ! Agency_Nexus_Permissions::can_access_messages( $client_id ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		global $wpdb;

		$messages = $wpdb->get_results( $wpdb->prepare( "
			SELECT m.*, u.display_name as sender_name
			FROM {$wpdb->prefix}an_messages m
			LEFT JOIN {$wpdb->users} u ON m.sender_id = u.ID
			WHERE m.client_id = %d
			ORDER BY m.created_at ASC
		", $client_id ) );

		wp_send_json_success( $messages );
	}

	/**
	 * AJAX handler for sharing files.
	 */
	public function handle_share_file() {
		check_ajax_referer( 'an_message_nonce', 'security' );

		$client_id = intval( $_POST['client_id'] );
		if ( ! Agency_Nexus_Permissions::can_access_messages( $client_id ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		global $wpdb;
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
		if ( ! Agency_Nexus_Permissions::is_team_member() ) wp_send_json_error( 'Unauthorized' );
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'an_messages', [ 'id' => intval( $_POST['message_id'] ) ] );
		wp_send_json_success();
	}

	/**
	 * AJAX handler for deleting a file.
	 */
	public function handle_delete_file() {
		check_ajax_referer( 'an_message_nonce', 'security' );
		if ( ! Agency_Nexus_Permissions::is_team_member() ) wp_send_json_error( 'Unauthorized' );
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'an_shared_files', [ 'id' => intval( $_POST['file_id'] ) ] );
		wp_send_json_success();
	}

	/**
	 * AJAX handler for fetching shared files.
	 */
	public function handle_get_files() {
		check_ajax_referer( 'an_message_nonce', 'security' );

		$client_id = intval( $_POST['client_id'] );
		if ( ! Agency_Nexus_Permissions::can_access_messages( $client_id ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		global $wpdb;

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

		$client_id = intval( $_POST['client_id'] );
		if ( ! Agency_Nexus_Permissions::can_access_messages( $client_id ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		global $wpdb;
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
