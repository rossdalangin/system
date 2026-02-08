<?php
/**
 * EngageTrack Module
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency_Nexus_Module_Engagetrack extends Agency_Nexus_Base_Module {

	protected $name = 'EngageTrack';

	public function init() {
		add_action( 'admin_menu', [ $this, 'register_submenu' ] );
		add_action( 'agency_nexus_dashboard_widgets', [ $this, 'render_dashboard_widget' ] );
		add_action( 'admin_init', [ $this, 'handle_post' ] );
		add_shortcode( 'agency_nexus_thank_you', [ $this, 'render_thank_you_page' ] );
	}

	public function handle_post() {
		if ( ! is_admin() || ! Agency_Nexus_Permissions::is_team_member() ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? $_GET['page'] : '';

		if ( 'an-leads' === $page ) {
			if ( Agency_Nexus_Permissions::is_admin() ) {
				$this->process_lead_actions();
			}
		} elseif ( 'an-email-lead' === $page ) {
			if ( Agency_Nexus_Permissions::is_admin() ) {
				$this->process_email_lead();
			}
		} elseif ( 'an-lead-settings' === $page ) {
			if ( Agency_Nexus_Permissions::is_admin() ) {
				$this->process_settings_actions();
			}
		} elseif ( 'an-canned-responses' === $page ) {
			if ( Agency_Nexus_Permissions::is_admin() ) {
				$this->process_response_actions();
			}
		}
	}

	private function process_lead_actions() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'an_leads';
		$action = isset( $_GET['action'] ) ? $_GET['action'] : '';
		$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		if ( 'delete' === $action && $id ) {
			check_admin_referer( 'an_delete_lead_' . $id );
			$wpdb->delete( $table_name, [ 'id' => $id ] );
			wp_redirect( admin_url( 'admin.php?page=an-leads&msg=deleted' ) );
			exit;
		}

		if ( 'convert' === $action && $id ) {
			check_admin_referer( 'an_convert_lead_' . $id );
			$this->process_convert_lead( $id );
			exit;
		}

		if ( isset( $_POST['an_save_lead'] ) && check_admin_referer( 'an_save_lead_nonce' ) ) {
			$status = sanitize_text_field( $_POST['status'] );
			$data = [
				'name'             => sanitize_text_field( $_POST['name'] ),
				'email'            => sanitize_email( $_POST['email'] ),
				'source'           => sanitize_text_field( $_POST['source'] ),
				'status'           => $status,
				'conversion_value' => floatval( $_POST['conversion_value'] )
			];
			if ( $id ) {
				$wpdb->update( $table_name, $data, [ 'id' => $id ] );
				$msg = 'updated';

				// If status was changed to converted, trigger conversion logic
				if ( 'converted' === $status ) {
					$this->process_convert_lead( $id );
					return;
				}
			} else {
				$wpdb->insert( $table_name, $data );
				$id = $wpdb->insert_id;
				$msg = 'added';

				if ( 'converted' === $status ) {
					$this->process_convert_lead( $id );
					return;
				}
			}
			wp_redirect( admin_url( 'admin.php?page=an-leads&msg=' . $msg ) );
			exit;
		}
	}

	private function process_convert_lead( $lead_id ) {
		global $wpdb;
		$lead = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}an_leads WHERE id = %d", $lead_id ) );

		if ( $lead ) {
			// Check if client already exists
			$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}an_clients WHERE email = %s", $lead->email ) );

			if ( ! $existing ) {
				$inserted = $wpdb->insert( $wpdb->prefix . 'an_clients', [
					'name'       => $lead->name,
					'email'      => $lead->email,
					'address'    => '', // Required column
					'notes'      => sprintf( __( 'Converted from lead source: %s', 'agency-nexus' ), $lead->source ),
					'created_at' => current_time( 'mysql' )
				] );
				$client_id = $inserted ? $wpdb->insert_id : 0;
			} else {
				$client_id = $existing;
			}

			// Update lead status
			$wpdb->update( $wpdb->prefix . 'an_leads', [ 'status' => 'converted' ], [ 'id' => $lead_id ] );

			if ( $client_id ) {
				wp_redirect( admin_url( 'admin.php?page=an-clients&action=view&id=' . $client_id . '&msg=added' ) );
			} else {
				wp_redirect( admin_url( 'admin.php?page=an-leads&msg=updated' ) );
			}
		} else {
			wp_redirect( admin_url( 'admin.php?page=an-leads&msg=error' ) );
		}
		exit;
	}

	private function process_response_actions() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'an_canned_responses';
		$action = isset( $_GET['action'] ) ? $_GET['action'] : '';
		$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		if ( 'delete' === $action && $id ) {
			check_admin_referer( 'an_delete_response_' . $id );
			$wpdb->delete( $table_name, [ 'id' => $id ] );
			wp_redirect( admin_url( 'admin.php?page=an-canned-responses&msg=deleted' ) );
			exit;
		}

		if ( isset( $_POST['an_save_response'] ) && check_admin_referer( 'an_save_response_nonce' ) ) {
			$data = [
				'title'   => sanitize_text_field( $_POST['title'] ),
				'content' => wp_kses_post( $_POST['content'] )
			];
			if ( $id ) {
				$wpdb->update( $table_name, $data, [ 'id' => $id ] );
				$msg = 'updated';
			} else {
				$wpdb->insert( $table_name, $data );
				$msg = 'saved';
			}
			wp_redirect( admin_url( 'admin.php?page=an-canned-responses&msg=' . $msg ) );
			exit;
		}
	}

	public function register_submenu() {
		if ( ! Agency_Nexus_License_Manager::get_instance()->is_feature_enabled( 'lead_intelligence' ) ) {
			return;
		}

		if ( Agency_Nexus_Permissions::is_admin() ) {
			add_submenu_page(
				'agency-nexus',
				__( 'Leads', 'agency-nexus' ),
				__( 'Leads', 'agency-nexus' ),
				'read',
				'an-leads',
				[ $this, 'render_leads' ]
			);

			add_submenu_page(
				null, // Hidden from menu
				__( 'Email Lead', 'agency-nexus' ),
				__( 'Email Lead', 'agency-nexus' ),
				'read',
				'an-email-lead',
				[ $this, 'render_email_lead' ]
			);

			add_submenu_page(
				'agency-nexus',
				__( 'Lead Settings', 'agency-nexus' ),
				__( 'Lead Settings', 'agency-nexus' ),
				'read',
				'an-lead-settings',
				[ $this, 'render_lead_settings' ]
			);

			add_submenu_page(
				'agency-nexus',
				__( 'Canned Responses', 'agency-nexus' ),
				__( 'Canned Responses', 'agency-nexus' ),
				'read',
				'an-canned-responses',
				[ $this, 'render_canned_responses' ]
			);
		}
	}

	private function process_settings_actions() {
		if ( isset( $_POST['an_save_thankyou_settings'] ) && check_admin_referer( 'an_save_thankyou_nonce' ) ) {
			update_option( 'an_ty_title', sanitize_text_field( $_POST['an_ty_title'] ) );
			update_option( 'an_ty_subtitle', sanitize_text_field( $_POST['an_ty_subtitle'] ) );
			update_option( 'an_ty_product', sanitize_text_field( $_POST['an_ty_product'] ) );
			update_option( 'an_ty_cta_text', sanitize_text_field( $_POST['an_ty_cta_text'] ) );
			update_option( 'an_ty_cta_url', esc_url_raw( $_POST['an_ty_cta_url'] ) );
			wp_redirect( admin_url( 'admin.php?page=an-lead-settings&msg=saved' ) );
			exit;
		}
	}

	public function render_lead_settings() {
		if ( isset( $_GET['msg'] ) && 'saved' === $_GET['msg'] ) {
			echo '<div class="updated"><p>' . __( 'Settings saved!', 'agency-nexus' ) . '</p></div>';
		}
		?>
		<div class="agency-nexus-wrap">
			<h1><?php _e( 'Lead Intelligence Settings', 'agency-nexus' ); ?></h1>
			<p class="description"><?php _e( 'Customize your lead capture experience and the "Thank You" page content.', 'agency-nexus' ); ?></p>

			<form method="post">
				<?php wp_nonce_field( 'an_save_thankyou_nonce' ); ?>
				<div class="postbox" style="padding: 20px;">
					<h3><?php _e( 'Thank You Page Content (Shortcode Defaults)', 'agency-nexus' ); ?></h3>
					<p class="description"><?php _e( 'These settings control the default output of the [agency_nexus_thank_you] shortcode.', 'agency-nexus' ); ?></p>

					<table class="form-table">
						<tr>
							<th><label><?php _e( 'Main Title', 'agency-nexus' ); ?></label></th>
							<td>
								<input type="text" name="an_ty_title" value="<?php echo esc_attr( get_option( 'an_ty_title', __( 'Your submission was successful!', 'agency-nexus' ) ) ); ?>" class="regular-text">
							</td>
						</tr>
						<tr>
							<th><label><?php _e( 'Sub-title', 'agency-nexus' ); ?></label></th>
							<td>
								<input type="text" name="an_ty_subtitle" value="<?php echo esc_attr( get_option( 'an_ty_subtitle', __( 'We have received your details and will be in touch shortly.', 'agency-nexus' ) ) ); ?>" class="regular-text">
							</td>
						</tr>
						<tr>
							<th><label><?php _e( 'Offer/Product Name', 'agency-nexus' ); ?></label></th>
							<td>
								<input type="text" name="an_ty_product" value="<?php echo esc_attr( get_option( 'an_ty_product', __( 'our premium solutions', 'agency-nexus' ) ) ); ?>" class="regular-text">
								<p class="description"><?php _e( 'This will appear in the exclusive offer box.', 'agency-nexus' ); ?></p>
							</td>
						</tr>
						<tr>
							<th><label><?php _e( 'CTA Button Text', 'agency-nexus' ); ?></label></th>
							<td>
								<input type="text" name="an_ty_cta_text" value="<?php echo esc_attr( get_option( 'an_ty_cta_text', __( 'Check this out while you wait', 'agency-nexus' ) ) ); ?>" class="regular-text">
							</td>
						</tr>
						<tr>
							<th><label><?php _e( 'CTA Button URL', 'agency-nexus' ); ?></label></th>
							<td>
								<input type="url" name="an_ty_cta_url" value="<?php echo esc_attr( get_option( 'an_ty_cta_url', '#' ) ); ?>" class="regular-text">
							</td>
						</tr>
					</table>
				</div>
				<p class="submit">
					<input type="submit" name="an_save_thankyou_settings" class="button button-primary" value="<?php _e( 'Save Settings', 'agency-nexus' ); ?>">
				</p>
			</form>
		</div>
		<?php
	}

	public function process_email_lead() {
		if ( isset( $_POST['an_send_lead_email'] ) && check_admin_referer( 'an_send_lead_email_nonce' ) ) {
			global $wpdb;
			$lead_id = intval( $_POST['lead_id'] );
			$subject = sanitize_text_field( $_POST['subject'] );
			$message = wp_kses_post( $_POST['message'] );
			$lead = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}an_leads WHERE id = %d", $lead_id ) );

			if ( $lead ) {
				$sent = wp_mail( $lead->email, $subject, $message, [ 'Content-Type: text/html; charset=UTF-8' ] );
				if ( $sent ) {
					$wpdb->insert( $wpdb->prefix . 'an_lead_communications', [
						'lead_id'    => $lead_id,
						'user_id'    => get_current_user_id(),
						'subject'    => $subject,
						'message'    => $message,
						'created_at' => current_time( 'mysql' )
					] );
					wp_redirect( admin_url( 'admin.php?page=an-leads&msg=email_sent' ) );
				} else {
					wp_redirect( admin_url( 'admin.php?page=an-leads&msg=email_failed' ) );
				}
				exit;
			}
		}
	}

	public function render_email_lead() {
		global $wpdb;
		$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
		$lead = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}an_leads WHERE id = %d", $id ) );
		$responses = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}an_canned_responses ORDER BY title ASC" );

		if ( ! $lead ) {
			echo '<div class="error"><p>' . __( 'Lead not found.', 'agency-nexus' ) . '</p></div>';
			return;
		}
		?>
		<div class="agency-nexus-wrap">
			<h1><?php echo sprintf( __( 'Email Lead: %s', 'agency-nexus' ), esc_html( $lead->name ) ); ?></h1>
			<p class="description"><?php echo sprintf( __( 'Send a message to %s (%s).', 'agency-nexus' ), esc_html( $lead->name ), esc_html( $lead->email ) ); ?></p>

			<form method="post">
				<?php wp_nonce_field( 'an_send_lead_email_nonce' ); ?>
				<input type="hidden" name="lead_id" value="<?php echo $lead->id; ?>">

				<table class="form-table">
					<tr>
						<th><label><?php _e( 'Canned Response', 'agency-nexus' ); ?></label></th>
						<td>
							<select id="an-canned-response-select">
								<option value=""><?php _e( 'Select a template...', 'agency-nexus' ); ?></option>
								<?php foreach ( $responses as $res ) : ?>
									<option value="<?php echo esc_attr( $res->id ); ?>" data-content="<?php echo esc_attr( $res->content ); ?>"><?php echo esc_html( $res->title ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php _e( 'Select a template to quickly populate the subject and message.', 'agency-nexus' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="subject"><?php _e( 'Subject', 'agency-nexus' ); ?></label></th>
						<td>
							<input type="text" name="subject" id="subject" class="regular-text" required>
						</td>
					</tr>
					<tr>
						<th><label for="message"><?php _e( 'Message', 'agency-nexus' ); ?></label></th>
						<td>
							<?php wp_editor( '', 'message', [ 'textarea_name' => 'message', 'rows' => 10 ] ); ?>
						</td>
					</tr>
				</table>

				<p class="submit">
					<input type="submit" name="an_send_lead_email" class="button button-primary" value="<?php _e( 'Send Email', 'agency-nexus' ); ?>">
					<a href="?page=an-leads" class="button"><?php _e( 'Cancel', 'agency-nexus' ); ?></a>
				</p>
			</form>
		</div>

		<script>
		jQuery(document).ready(function($){
			$('#an-canned-response-select').change(function(){
				var content = $(this).find(':selected').data('content');
				var title = $(this).find(':selected').text();
				if (content) {
					$('#subject').val(title);
					if (typeof tinyMCE !== 'undefined' && tinyMCE.get('message')) {
						tinyMCE.get('message').setContent(content);
					} else {
						$('#message').val(content);
					}
				}
			});
		});
		</script>
		<?php
	}

	public function render_leads() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'an_leads';
		$action = isset( $_GET['action'] ) ? $_GET['action'] : 'list';
		$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		if ( isset( $_GET['msg'] ) ) {
			$m = '';
			switch ( $_GET['msg'] ) {
				case 'added':        $m = 'Lead recorded!'; break;
				case 'updated':      $m = 'Lead updated!'; break;
				case 'deleted':      $m = 'Lead deleted!'; break;
				case 'email_sent':   $m = 'Email sent to lead!'; break;
				case 'email_failed': $m = 'Failed to send email.'; break;
				case 'converted':    $m = 'Lead converted to client successfully!'; break;
			}
			if ( $m ) echo '<div class="updated"><p>' . esc_html( $m ) . '</p></div>';
		}

		if ($action === 'edit' || $action === 'add') {
			$lead = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id)) : null;
			?>
			<div class="agency-nexus-wrap">
				<h1><?php echo $id ? __('Edit Lead', 'agency-nexus') : __('Add New Lead', 'agency-nexus'); ?></h1>
				<p class="description"><?php _e('Track a potential client in your sales pipeline.', 'agency-nexus'); ?></p>
				<form method="post">
					<?php wp_nonce_field( 'an_save_lead_nonce' ); ?>
					<table class="form-table">
						<tr>
							<th><label><?php _e('Name', 'agency-nexus'); ?></label></th>
							<td>
								<input type="text" name="name" value="<?php echo $lead ? esc_attr($lead->name) : ''; ?>" required class="regular-text">
								<p class="description"><?php _e('Full name of the prospect.', 'agency-nexus'); ?></p>
							</td>
						</tr>
						<tr>
							<th><label><?php _e('Email', 'agency-nexus'); ?></label></th>
							<td>
								<input type="email" name="email" value="<?php echo $lead ? esc_attr($lead->email) : ''; ?>" required class="regular-text">
								<p class="description"><?php _e('Contact email for follow-ups.', 'agency-nexus'); ?></p>
							</td>
						</tr>
						<tr>
							<th><label><?php _e('Source', 'agency-nexus'); ?></label></th>
							<td>
								<input type="text" name="source" value="<?php echo $lead ? esc_attr($lead->source) : ''; ?>" class="regular-text">
								<p class="description"><?php _e('Where did this lead come from? e.g., LinkedIn, Referral, Google Ads', 'agency-nexus'); ?></p>
							</td>
						</tr>
						<tr>
							<th><label><?php _e('Status', 'agency-nexus'); ?></label></th>
							<td>
								<select name="status">
									<option value="new" <?php selected($lead ? $lead->status : '', 'new'); ?>>New</option>
									<option value="qualified" <?php selected($lead ? $lead->status : '', 'qualified'); ?>>Qualified</option>
									<option value="converted" <?php selected($lead ? $lead->status : '', 'converted'); ?>>Converted</option>
									<option value="lost" <?php selected($lead ? $lead->status : '', 'lost'); ?>>Lost</option>
								</select>
								<p class="description"><?php _e('Current stage in your sales process.', 'agency-nexus'); ?></p>
							</td>
						</tr>
						<tr>
							<th><label><?php _e('Potential Value ($)', 'agency-nexus'); ?></label></th>
							<td>
								<input type="number" step="0.01" name="conversion_value" value="<?php echo $lead ? esc_attr($lead->conversion_value) : ''; ?>" class="regular-text">
								<p class="description"><?php _e('Estimated project value if this lead converts.', 'agency-nexus'); ?></p>
							</td>
						</tr>
					</table>
					<input type="submit" name="an_save_lead" class="button button-primary" value="Save Lead">
					<a href="?page=an-leads" class="button">Cancel</a>
				</form>

				<?php if ( $id ) :
					$comms = $wpdb->get_results( $wpdb->prepare( "SELECT c.*, u.display_name FROM {$wpdb->prefix}an_lead_communications c LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID WHERE c.lead_id = %d ORDER BY c.created_at DESC", $id ) );
				?>
					<div style="margin-top: 40px;">
						<h2><?php _e( 'Communication History', 'agency-nexus' ); ?></h2>
						<?php if ( $comms ) : ?>
							<table class="wp-list-table widefat fixed striped">
								<thead>
									<tr>
										<th><?php _e( 'Date', 'agency-nexus' ); ?></th>
										<th><?php _e( 'Sent By', 'agency-nexus' ); ?></th>
										<th><?php _e( 'Subject', 'agency-nexus' ); ?></th>
										<th><?php _e( 'Message', 'agency-nexus' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $comms as $comm ) : ?>
										<tr>
											<td><?php echo esc_html( $comm->created_at ); ?></td>
											<td><?php echo esc_html( $comm->display_name ); ?></td>
											<td><?php echo esc_html( $comm->subject ); ?></td>
											<td><?php echo wp_kses_post( nl2br( $comm->message ) ); ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php else : ?>
							<p><?php _e( 'No communication history found for this lead.', 'agency-nexus' ); ?></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
			<?php
			return;
		}

		$leads = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC" );
		?>
		<div class="agency-nexus-wrap">
			<h1 class="wp-heading-inline"><?php _e( 'Lead Intelligence System', 'agency-nexus' ); ?></h1>

			<div style="background: #fff; border-left: 4px solid #46b450; padding: 15px; margin: 20px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
				<h3><?php _e( 'Fuel Your Agency\'s Growth', 'agency-nexus' ); ?></h3>
				<p><?php _e( 'This system allows you to capture and manage prospects throughout your sales pipeline. Tracking potential values helps you forecast revenue and prioritize high-value deals.', 'agency-nexus' ); ?></p>
				<ul style="list-style: disc; margin-left: 20px;">
					<li><strong>Capture:</strong> Use the "Generate Embed Code" button to create a form for your marketing site.</li>
					<li><strong>Convert:</strong> Update a lead\'s status to "Converted" once they sign a contract.</li>
					<li><strong>Automate:</strong> Connect leads to <strong>AutoPilot</strong> to trigger onboarding emails instantly upon capture.</li>
				</ul>
			</div>

			<a href="?page=an-leads&action=add" class="page-title-action">Add New</a>
			<button type="button" class="page-title-action" id="generate-lead-form-btn"><?php _e( 'Generate Embed Code', 'agency-nexus' ); ?></button>
			<hr class="wp-header-end">
			<div id="embed-code-container" style="display: none; background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin-bottom: 20px;">
				<h3><?php _e('Generate Capture Form & Thank You Page', 'agency-nexus'); ?></h3>

				<div style="background: #fdfaf0; border-left: 4px solid #f0b849; padding: 15px; margin-bottom: 20px;">
					<p><strong><?php _e('Step 1: Create your Thank You Page', 'agency-nexus'); ?></strong></p>
					<p><?php _e('Create a new Page in WordPress and use this shortcode to display a high-converting confirmation message:', 'agency-nexus'); ?></p>
					<code>[agency_nexus_thank_you product_name="Your Course Name" cta_url="https://your-upsell-link.com"]</code>
				</div>

				<div style="margin-bottom: 15px;">
					<label><strong><?php _e('Step 2: Enter Redirect URL (Full URL to your Thank You page):', 'agency-nexus'); ?></strong></label><br>
					<input type="url" id="lead-redirect-url" class="large-text" placeholder="https://your-site.com/thank-you/">
				</div>

				<p><strong><?php _e('Step 3: Copy the Embed Code', 'agency-nexus'); ?></strong></p>
				<textarea id="lead-embed-textarea" class="large-text" rows="14" readonly><?php
					$api_url = get_rest_url( null, 'agency-nexus/v1/leads/capture' );
					echo esc_textarea('<form action="' . $api_url . '" method="POST">
    <div>
        <label>Name:</label><br>
        <input type="text" name="name" required style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">
    </div>
    <div>
        <label>Email:</label><br>
        <input type="email" name="email" required style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">
    </div>
    <input type="hidden" name="source" value="Website Embed">
    <input type="hidden" name="redirect_url" value="" class="an-redirect-field">
    <button type="submit" style="background: #2271b1; color: #fff; border: none; padding: 12px 25px; border-radius: 4px; cursor: pointer; font-weight: bold; width: 100%;">Submit & Get Access</button>
</form>');
				?></textarea>
				<p><button type="button" class="button" onclick="jQuery('#embed-code-container').slideUp();">Close</button></p>
			</div>

			<script>
			jQuery(document).ready(function($){
				$('#generate-lead-form-btn').click(function(){
					$('#embed-code-container').slideToggle();
				});

				$('#lead-redirect-url').on('input', function(){
					var url = $(this).val();
					var $textarea = $('#lead-embed-textarea');
					// We use a clean template to avoid regex issues with multiple edits
					var api_url = '<?php echo get_rest_url( null, "agency-nexus/v1/leads/capture" ); ?>';
					var template = '<form action="' + api_url + '" method="POST">\n' +
'    <div>\n' +
'        <label>Name:</label><br>\n' +
'        <input type="text" name="name" required style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">\n' +
'    </div>\n' +
'    <div>\n' +
'        <label>Email:</label><br>\n' +
'        <input type="email" name="email" required style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">\n' +
'    </div>\n' +
'    <input type="hidden" name="source" value="Website Embed">\n' +
'    <input type="hidden" name="redirect_url" value="' + url + '">\n' +
'    <button type="submit" style="background: #2271b1; color: #fff; border: none; padding: 12px 25px; border-radius: 4px; cursor: pointer; font-weight: bold; width: 100%;">Submit & Get Access</button>\n' +
'</form>';
					$textarea.val(template);
				});
			});
			</script>

			<table class="wp-list-table widefat fixed striped">
				<thead><tr><th>Name</th><th>Email</th><th>Source</th><th>Status</th><th>Value</th><th>Actions</th></tr></thead>
				<tbody>
					<?php foreach ($leads as $lead) : ?>
						<tr>
							<td><strong><a href="?page=an-leads&action=edit&id=<?php echo $lead->id; ?>"><?php echo esc_html($lead->name); ?></a></strong></td>
							<td><?php echo esc_html($lead->email); ?></td>
							<td><?php echo esc_html($lead->source); ?></td>
							<td><span class="badge status-<?php echo $lead->status; ?>"><?php echo ucfirst($lead->status); ?></span></td>
							<td>$<?php echo number_format($lead->conversion_value, 2); ?></td>
							<td>
								<a href="?page=an-email-lead&id=<?php echo $lead->id; ?>"><?php _e('Email', 'agency-nexus'); ?></a> |
								<?php if ( $lead->status !== 'converted' ) : ?>
									<a href="<?php echo wp_nonce_url('?page=an-leads&action=convert&id=' . $lead->id, 'an_convert_lead_' . $lead->id); ?>" style="color:green;" onclick="return confirm('Convert this lead to a client?')"><?php _e('Convert', 'agency-nexus'); ?></a> |
								<?php endif; ?>
								<a href="?page=an-leads&action=edit&id=<?php echo $lead->id; ?>">Edit</a> |
								<a href="<?php echo wp_nonce_url('?page=an-leads&action=delete&id=' . $lead->id, 'an_delete_lead_' . $lead->id); ?>" style="color:red;" onclick="return confirm('Delete lead?')">Delete</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public function render_canned_responses() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'an_canned_responses';
		$action = isset( $_GET['action'] ) ? $_GET['action'] : 'list';
		$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		if ( isset( $_GET['msg'] ) ) {
			$m = '';
			switch ( $_GET['msg'] ) {
				case 'saved':   $m = 'Response saved!'; break;
				case 'updated': $m = 'Response updated!'; break;
				case 'deleted': $m = 'Response deleted!'; break;
			}
			if ( $m ) echo '<div class="updated"><p>' . esc_html( $m ) . '</p></div>';
		}

		if ($action === 'edit' || $action === 'add') {
			$resp = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id)) : null;
			?>
			<div class="agency-nexus-wrap">
				<h1><?php echo $id ? __('Edit Response', 'agency-nexus') : __('Add New Response', 'agency-nexus'); ?></h1>
				<p class="description"><?php _e('Save reusable text snippets for common client inquiries.', 'agency-nexus'); ?></p>
				<form method="post">
					<?php wp_nonce_field( 'an_save_response_nonce' ); ?>
					<table class="form-table">
						<tr>
							<th><label><?php _e('Shortcut Title', 'agency-nexus'); ?></label></th>
							<td>
								<input type="text" name="title" value="<?php echo $resp ? esc_attr($resp->title) : ''; ?>" required class="regular-text">
								<p class="description"><?php _e('A short name to identify this template. e.g., Welcome Message', 'agency-nexus'); ?></p>
							</td>
						</tr>
						<tr>
							<th><label><?php _e('Content', 'agency-nexus'); ?></label></th>
							<td>
								<textarea name="content" required class="regular-text" rows="5"><?php echo $resp ? esc_textarea($resp->content) : ''; ?></textarea>
								<p class="description"><?php _e('The full text that will be inserted when you use this shortcut.', 'agency-nexus'); ?></p>
							</td>
						</tr>
					</table>
					<input type="submit" name="an_save_response" class="button button-primary" value="Save Response">
					<a href="?page=an-canned-responses" class="button">Cancel</a>
				</form>
			</div>
			<?php
			return;
		}

		$responses = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC" );
		?>
		<div class="agency-nexus-wrap">
			<h1 class="wp-heading-inline"><?php _e( 'Canned Responses', 'agency-nexus' ); ?></h1>
			<a href="?page=an-canned-responses&action=add" class="page-title-action"><?php _e('Add New', 'agency-nexus'); ?></a>
			<hr class="wp-header-end">
			<p><?php _e( 'Guidance: Store reusable message templates here. These can be quickly accessed and used within the Messaging Hub.', 'agency-nexus' ); ?></p>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php _e('Title', 'agency-nexus'); ?></th>
						<th><?php _e('Content Snippet', 'agency-nexus'); ?></th>
						<th><?php _e('Actions', 'agency-nexus'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ($responses) : foreach ($responses as $resp) : ?>
						<tr>
							<td><strong><?php echo esc_html($resp->title); ?></strong></td>
							<td><?php echo esc_html(wp_trim_words($resp->content, 15)); ?></td>
							<td>
								<a href="?page=an-canned-responses&action=edit&id=<?php echo $resp->id; ?>"><?php _e('Edit', 'agency-nexus'); ?></a> |
								<a href="<?php echo wp_nonce_url('?page=an-canned-responses&action=delete&id=' . $resp->id, 'an_delete_response_' . $resp->id); ?>" style="color:red;" onclick="return confirm('Delete response?')"><?php _e('Delete', 'agency-nexus'); ?></a>
							</td>
						</tr>
					<?php endforeach; else : ?>
						<tr><td colspan="3"><?php _e('No canned responses found.', 'agency-nexus'); ?></td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render high-converting Thank You page content via shortcode.
	 * Usage: [agency_nexus_thank_you product_name="Awesome Course" cta_url="https://..."]
	 */
	public function render_thank_you_page( $atts ) {
		if ( ! Agency_Nexus_License_Manager::get_instance()->is_feature_enabled( 'lead_intelligence' ) ) {
			return '<p style="color:red;">' . __( 'The Lead Intelligence feature requires a Pro or Agency license.', 'agency-nexus' ) . '</p>';
		}

		$atts = shortcode_atts( [
			'title'        => get_option( 'an_ty_title', __( 'Your submission was successful!', 'agency-nexus' ) ),
			'sub_title'    => get_option( 'an_ty_subtitle', __( 'We have received your details and will be in touch shortly.', 'agency-nexus' ) ),
			'product_name' => get_option( 'an_ty_product', __( 'our premium solutions', 'agency-nexus' ) ),
			'cta_text'     => get_option( 'an_ty_cta_text', __( 'Check this out while you wait', 'agency-nexus' ) ),
			'cta_url'      => get_option( 'an_ty_cta_url', '#' ),
			'video_url'    => '', // Optional: Add a VSL (Video Sales Letter) URL
		], $atts );

		ob_start();
		?>
		<div class="an-thank-you-container" style="max-width: 800px; margin: 40px auto; text-align: center; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif; padding: 20px; border-radius: 12px; background: #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
			<div class="an-success-icon" style="width: 80px; height: 80px; background: #46b450; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 40px;">
				✓
			</div>

			<h1 style="color: #1d2327; margin-bottom: 10px;"><?php echo esc_html( $atts['title'] ); ?></h1>
			<p style="font-size: 18px; color: #646970; margin-bottom: 40px;"><?php echo esc_html( $atts['sub_title'] ); ?></p>

			<div class="an-upsell-box" style="background: #f0f6fb; padding: 30px; border-radius: 8px; border-left: 5px solid #2271b1; text-align: left; margin-bottom: 30px;">
				<h2 style="margin-top: 0; color: #2271b1;"><?php echo sprintf( __( 'Exclusive Offer: Get 20%% off %s', 'agency-nexus' ), esc_html( $atts['product_name'] ) ); ?></h2>
				<p><?php _e( 'Since you are here, we want to give you a special head start. Most of our clients see immediate results by jumping on this limited-time offer.', 'agency-nexus' ); ?></p>

				<?php if ( ! empty( $atts['video_url'] ) ) : ?>
					<div style="margin: 20px 0; aspect-ratio: 16/9; background: #000; border-radius: 4px;">
						<!-- Placeholder for video if provided -->
						<iframe width="100%" height="100%" src="<?php echo esc_url( $atts['video_url'] ); ?>" frameborder="0" allowfullscreen></iframe>
					</div>
				<?php endif; ?>

				<a href="<?php echo esc_url( $atts['cta_url'] ); ?>" class="button button-primary" style="display: inline-block; background: #2271b1; color: #fff; text-decoration: none; padding: 15px 30px; border-radius: 4px; font-weight: bold; font-size: 18px; margin-top: 10px;">
					<?php echo esc_html( $atts['cta_text'] ); ?> &rarr;
				</a>
			</div>

			<div class="an-social-proof" style="color: #8c8f94; font-size: 14px;">
				<p><?php _e( 'Joined by 1,000+ professionals worldwide.', 'agency-nexus' ); ?></p>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public function render_dashboard_widget() {
		if ( ! Agency_Nexus_License_Manager::get_instance()->is_feature_enabled( 'lead_intelligence' ) ) {
			return;
		}

		if ( ! Agency_Nexus_Permissions::is_admin() ) {
			return;
		}
		global $wpdb;
		$leads_table = $wpdb->prefix . 'an_leads';

		$total_leads = $wpdb->get_var( "SELECT COUNT(*) FROM $leads_table" );
		$conversions = $wpdb->get_var( "SELECT COUNT(*) FROM $leads_table WHERE status = 'converted'" );
		$conv_rate   = $total_leads > 0 ? ($conversions / $total_leads) * 100 : 0;

		?>
		<div class="postbox" style="padding: 20px;">
			<h2><?php _e( 'EngageTrack', 'agency-nexus' ); ?></h2>
			<div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
				<div>
					<strong><?php _e( 'Total Leads', 'agency-nexus' ); ?></strong>
					<div style="font-size: 20px; font-weight: bold;"><?php echo intval($total_leads); ?></div>
				</div>
				<div>
					<strong><?php _e( 'Conv. Rate', 'agency-nexus' ); ?></strong>
					<div style="font-size: 20px; font-weight: bold; color: #46b450;"><?php echo round($conv_rate, 1); ?>%</div>
				</div>
			</div>
			<p><strong><?php _e( 'Sentiment Analysis:', 'agency-nexus' ); ?></strong></p>
			<div style="display: flex; align-items: center; gap: 10px; margin: 10px 0;">
				<div style="flex: 1; height: 20px; background: #eee; border-radius: 10px; overflow: hidden; display: flex;">
					<div style="width: 70%; background: #46b450;" title="Positive"></div>
					<div style="width: 20%; background: #ffb900;" title="Neutral"></div>
					<div style="width: 10%; background: #dc3232;" title="Negative"></div>
				</div>
				<span style="font-weight: bold; color: #46b450;">70% <?php _e( 'Positive', 'agency-nexus' ); ?></span>
			</div>
		</div>
		<?php
	}
}
