<div class="agency-nexus-wrap">
	<div style="max-width: 900px; margin: 20px auto; background: #fff; padding: 50px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
		<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px;">
			<div>
				<h1 style="margin: 0; color: var(--an-indigo-600);"><?php _e( 'PROJECT PROPOSAL', 'agency-nexus' ); ?></h1>
				<p style="color: #64748b; font-size: 1.1rem;"><?php echo esc_html( $proposal->title ); ?></p>
			</div>
			<div style="text-align: right;">
				<span class="badge status-<?php echo $proposal->status; ?>" style="font-size: 1rem; padding: 8px 16px;">
					<?php echo strtoupper( $proposal->status ); ?>
				</span>
				<p style="margin-top: 10px; color: #64748b;">Date: <?php echo date('M d, Y', strtotime($proposal->created_at)); ?></p>
			</div>
		</div>

		<hr style="border: 0; border-top: 1px solid #e2e8f0; margin-bottom: 40px;">

		<div style="margin-bottom: 40px;">
			<h3 style="color: #1e293b;"><?php _e( 'Client Information', 'agency-nexus' ); ?></h3>
			<p><strong><?php _e( 'Prepared for:', 'agency-nexus' ); ?></strong> <?php echo esc_html( $proposal->client_name ); ?></p>
		</div>

		<div style="margin-bottom: 40px;">
			<h3 style="color: #1e293b;"><?php _e( 'Project Scope & Deliverables', 'agency-nexus' ); ?></h3>
			<div style="background: #f8fafc; padding: 30px; border-radius: 8px; line-height: 1.6; color: #334155;">
				<?php echo nl2br( wp_kses_post( $proposal->description ) ); ?>
			</div>
		</div>

		<div style="margin-bottom: 40px; text-align: right;">
			<h2 style="color: #1e293b;"><?php _e( 'Investment:', 'agency-nexus' ); ?> $<?php echo number_format( $proposal->budget, 2 ); ?></h2>
		</div>

		<hr style="border: 0; border-top: 1px solid #e2e8f0; margin-bottom: 40px;">

		<?php if ( $proposal->status === 'sent' && Agency_Nexus_Permissions::is_client() ) : ?>
			<div id="sign-section" style="background: #f1f5f9; padding: 40px; border-radius: 12px; text-align: center;">
				<h3 style="margin-top: 0;"><?php _e( 'Ready to get started?', 'agency-nexus' ); ?></h3>
				<p><?php _e( 'By typing your name below and clicking "Accept & Sign", you agree to the scope and investment outlined above.', 'agency-nexus' ); ?></p>
				<div style="max-width: 400px; margin: 20px auto;">
					<input type="text" id="client-signature" placeholder="Type your full name as signature" style="width: 100%; padding: 12px; font-size: 1.1rem; border: 2px solid #cbd5e1; border-radius: 6px; text-align: center; font-family: 'Lexend', cursive;">
					<button type="button" id="btn-sign-proposal" class="button button-primary" style="width: 100%; margin-top: 15px; padding: 12px; font-size: 1.1rem;">
						<?php _e( 'Accept & Sign Proposal', 'agency-nexus' ); ?>
					</button>
				</div>
			</div>
		<?php elseif ( $proposal->status === 'accepted' ) : ?>
			<div style="background: #ecfdf5; border: 1px solid #10b981; padding: 30px; border-radius: 8px; text-align: center;">
				<h3 style="color: #065f46; margin-top: 0;"><?php _e( 'Proposal Signed & Accepted', 'agency-nexus' ); ?></h3>
				<p>Signed by: <strong><?php echo esc_html( $proposal->signature ); ?></strong> on <?php echo $proposal->signed_at; ?></p>
				<p style="margin-bottom: 0;"><small><?php _e( 'A project record has been created and our team is notified.', 'agency-nexus' ); ?></small></p>
			</div>
		<?php endif; ?>

		<div style="margin-top: 40px; text-align: center;">
			<a href="?page=an-proposals" class="button"><?php _e( 'Back to Proposals', 'agency-nexus' ); ?></a>
			<button onclick="window.print()" class="button"><?php _e( 'Download PDF', 'agency-nexus' ); ?></button>
		</div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	$('#btn-sign-proposal').click(function() {
		var sig = $('#client-signature').val();
		if (!sig) {
			alert('Please type your name to sign.');
			return;
		}

		if (!confirm('Are you sure you want to sign this proposal?')) return;

		$(this).prop('disabled', true).text('Signing...');

		var data = {
			action: 'an_sign_proposal',
			id: <?php echo $proposal->id; ?>,
			signature: sig,
			security: '<?php echo wp_create_nonce("an_proposal_nonce"); ?>'
		};

		$.post(ajaxurl, data, function(response) {
			if (response.success) {
				location.reload();
			} else {
				alert('Error: ' + response.data);
				$('#btn-sign-proposal').prop('disabled', false).text('Accept & Sign Proposal');
			}
		});
	});
});
</script>
