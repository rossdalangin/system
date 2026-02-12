<div class="wrap">
	<h1><?php _e( 'Client Sign-off Portal', 'agency-nexus' ); ?></h1>

	<div style="background: #fff; border-left: 4px solid #ffb900; padding: 15px; margin: 20px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
		<h3><?php _e( 'How to Use the Portal', 'agency-nexus' ); ?></h3>
		<p><?php _e( 'This portal acts as the final checkpoint before content goes live. It ensures your agency maintains high quality and avoids disputes by obtaining formal client sign-off.', 'agency-nexus' ); ?></p>
		<ul style="list-style: disc; margin-left: 20px;">
			<li><strong>Review:</strong> Carefully read through the draft content shown in the cards below.</li>
			<li><strong>Approve:</strong> Click "Approve" if the content is ready for publishing. This marks the item as "Approved" in your content history.</li>
			<li><strong>Request Changes:</strong> Click "Request Changes" to send the item back to your team for revision.</li>
		</ul>
	</div>

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
					<button class="button reject-btn" data-id="<?php echo $item->id; ?>"><?php _e( 'Request Changes', 'agency-nexus' ); ?></button>
					<a href="?page=an-approvals&action=compare&id=<?php echo $item->id; ?>" class="button"><?php _e( 'Review & Compare', 'agency-nexus' ); ?></a>
				</div>
			</div>
		<?php endforeach; else : ?>
			<div class="welcome-panel" style="padding: 20px; text-align: center;">
				<h3><?php _e( 'All caught up!', 'agency-nexus' ); ?></h3>
				<p><?php _e( 'There are no items currently pending approval.', 'agency-nexus' ); ?></p>
			</div>
		<?php endif; ?>
	</div>

	<h2 style="margin-top: 40px;">Approval History</h2>
	<table class="wp-list-table widefat fixed striped">
		<thead><tr><th>Title</th><th>Project</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
		<tbody>
			<?php foreach ($history as $item) : ?>
				<tr id="history-row-<?php echo $item->id; ?>">
					<td><?php echo esc_html($item->title); ?></td>
					<td><?php echo esc_html($item->project_title); ?></td>
					<td><span class="badge status-<?php echo $item->status; ?>"><?php echo ucfirst($item->status); ?></span></td>
					<td><?php echo $item->created_at; ?></td>
					<td>
						<button class="button disapprove-btn" data-id="<?php echo $item->id; ?>"><?php _e( 'Undo / Disapprove', 'agency-nexus' ); ?></button>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php wp_nonce_field( 'an_approval_nonce', 'security' ); ?>
</div>

<script>
jQuery(document).ready(function($) {
	$('.disapprove-btn').on('click', function() {
		const btn = $(this);
		const id = btn.data('id');
		btn.prop('disabled', true);

		const data = {
			action: 'an_disapprove_content',
			item_id: id,
			security: $('#security').val()
		};

		$.post(ajaxurl, data, function(response) {
			if (response.success) {
				location.reload(); // Simplest to reload to move item back to pending
			} else {
				alert('Action failed');
				btn.prop('disabled', false);
			}
		});
	});

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

	$('.reject-btn').on('click', function() {
		const btn = $(this);
		const id = btn.data('id');
		btn.prop('disabled', true).text('Processing...');

		const data = {
			action: 'an_reject_content',
			item_id: id,
			security: $('#security').val()
		};

		$.post(ajaxurl, data, function(response) {
			if (response.success) {
				btn.closest('.approval-card').fadeOut();
			} else {
				alert('Rejection failed');
				btn.prop('disabled', false).text('Request Changes');
			}
		});
	});
});
</script>
