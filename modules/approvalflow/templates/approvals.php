<div class="wrap">
	<h1><?php _e( 'Client Sign-off Portal', 'agency-nexus' ); ?></h1>
	<p><?php _e( 'Review and approve content drafts before they go live.', 'agency-nexus' ); ?></p>

	<div style="margin-top: 20px;">
		<?php if ($pending_items) : foreach ($pending_items as $item) : ?>
			<div class="approval-card" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin-bottom: 20px;">
				<div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px;">
					<h3 style="margin: 0;"><?php echo esc_html($item->title); ?> <small style="color: #666; font-weight: normal;">(Project: <?php echo esc_html($item->project_title); ?>)</small></h3>
					<span class="badge" style="background: #ffb900; padding: 4px 10px; border-radius: 15px; font-size: 12px;"><?php _e( 'Pending Approval', 'agency-nexus' ); ?></span>
				</div>

				<div class="content-preview" style="background: #f9f9f9; padding: 15px; border: 1px inset #eee; max-height: 200px; overflow-y: auto; margin-bottom: 15px;">
					<?php echo wpautop(esc_html($item->content)); ?>
				</div>

				<div style="display: flex; gap: 10px;">
					<button class="button button-primary approve-btn" data-id="<?php echo $item->id; ?>"><?php _e( 'Approve', 'agency-nexus' ); ?></button>
					<button class="button reject-btn"><?php _e( 'Request Changes', 'agency-nexus' ); ?></button>
				</div>
			</div>
		<?php endforeach; else : ?>
			<div class="welcome-panel" style="padding: 20px; text-align: center;">
				<h3><?php _e( 'All caught up!', 'agency-nexus' ); ?></h3>
				<p><?php _e( 'There are no items currently pending approval.', 'agency-nexus' ); ?></p>
			</div>
		<?php endif; ?>
	</div>

	<?php wp_nonce_field( 'an_approval_nonce', 'security' ); ?>
</div>

<script>
jQuery(document).ready(function($) {
	$('.approve-btn').on('click', function() {
		const btn = $(this);
		const id = btn.data('id');
		btn.prop('disabled', true).text('Approving...');

		const data = {
			action: 'an_approve_content',
			item_id: id,
			security: $('#security').val()
		};

		$.post(ajaxurl, data, function(response) {
			if (response.success) {
				btn.closest('.approval-card').fadeOut();
			} else {
				alert('Approval failed');
				btn.prop('disabled', false).text('Approve');
			}
		});
	});
});
</script>
