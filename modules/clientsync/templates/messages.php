<div class="wrap">
	<h1><?php _e( 'Unified Communication Hub', 'agency-nexus' ); ?></h1>
	<p><?php _e( 'Communicate with your clients via Email, Slack, or Dashboard.', 'agency-nexus' ); ?></p>

	<div style="display: flex; height: 600px; border: 1px solid #ccd0d4; background: #fff; margin-top: 20px;">
		<div id="client-list" style="width: 250px; border-right: 1px solid #eee; overflow-y: auto;">
			<?php foreach ($clients as $client) : ?>
				<div class="client-item" style="padding: 15px; border-bottom: 1px solid #eee; cursor: pointer;" onclick="selectClient(<?php echo $client->id; ?>, '<?php echo esc_js($client->name); ?>')">
					<strong><?php echo esc_html($client->name); ?></strong>
					<div style="font-size: 11px; color: #666;">Last active: 2h ago</div>
				</div>
			<?php endforeach; ?>
		</div>

		<div id="chat-window" style="flex: 1; display: flex; flex-direction: column;">
			<div id="chat-header" style="padding: 15px; border-bottom: 1px solid #eee; background: #f9f9f9; display: flex; justify-content: space-between; align-items: center;">
				<h3 id="selected-client-name" style="margin: 0;"><?php _e( 'Select a client to start chatting', 'agency-nexus' ); ?></h3>
				<div id="chat-tabs" style="display: none;">
					<button class="button chat-tab-btn active" data-tab="messages">Messages</button>
					<button class="button chat-tab-btn" data-tab="files">Shared Files</button>
				</div>
			</div>

			<div id="chat-content-panes" style="flex: 1; overflow: hidden; display: flex; flex-direction: column;">
			<div id="chat-messages" class="chat-pane" style="flex: 1; padding: 20px; overflow-y: auto; background: #fdfdfd;">
				<!-- Messages will appear here -->
				<div class="system-msg" style="text-align: center; color: #999; margin: 20px 0;"><?php _e( 'End-to-end encrypted conversation', 'agency-nexus' ); ?></div>
			</div>

			</div>
			<div id="chat-files" class="chat-pane" style="flex: 1; padding: 20px; overflow-y: auto; background: #fdfdfd; display: none;">
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
					<h4>Shared Files</h4>
					<button type="button" id="share-file-btn" class="button button-primary">Upload & Share</button>
				</div>
				<ul id="shared-files-list"></ul>
			</div>
			</div>

			<div id="chat-input" style="padding: 15px; border-top: 1px solid #eee; display: none;">
				<form id="an-message-form">
					<?php wp_nonce_field( 'an_message_nonce', 'security' ); ?>
					<input type="hidden" name="client_id" id="chat-client-id">
					<div style="display: flex; gap: 10px;">
						<textarea name="message" id="chat-message-text" style="flex: 1; height: 60px;" placeholder="<?php _e( 'Type your message...', 'agency-nexus' ); ?>" disabled></textarea>
						<button type="submit" class="button button-primary" id="send-msg-btn" disabled><?php _e( 'Send', 'agency-nexus' ); ?></button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<script>
function selectClient(id, name) {
	var jQuery = window.jQuery;
	jQuery('#selected-client-name').text(name);
	jQuery('#chat-client-id').val(id);
	jQuery('#chat-message-text').prop('disabled', false).focus();
	jQuery('#send-msg-btn').prop('disabled', false);
	jQuery('#chat-tabs, #chat-input').show();

	loadMessages(id);
	loadFiles(id);
}

function loadMessages(id) {
	var jQuery = window.jQuery;
	var data = {
		action: 'an_get_messages',
		client_id: id,
		security: jQuery('#security').val()
	};

	jQuery.post(ajaxurl, data, function(response) {
		if (response.success) {
			var html = '';
			var currentUserId = <?php echo get_current_user_id(); ?>;
			response.data.forEach(function(msg) {
				var isSent = msg.sender_id == currentUserId;
				var align = isSent ? 'right' : 'left';
				var bg = isSent ? '#0073aa' : '#eee';
				var color = isSent ? '#fff' : '#333';
				html += '<div class="msg" style="margin-bottom: 15px; text-align: ' + align + ';">';
				html += '<div style="background: ' + bg + '; color: ' + color + '; padding: 10px; border-radius: 10px; display: inline-block; max-width: 80%;">' + msg.message + '</div>';
				html += '</div>';
			});
			if (html === '') {
				html = '<div class="system-msg" style="text-align: center; color: #999; margin: 20px 0;">No messages yet.</div>';
			}
			jQuery('#chat-messages').html(html);
			// Scroll to bottom
			var chatMessages = document.getElementById('chat-messages');
			chatMessages.scrollTop = chatMessages.scrollHeight;
		}
	});
}

function loadFiles(id) {
	var jQuery = window.jQuery;
	var data = {
		action: 'an_get_files',
		client_id: id,
		security: jQuery('#security').val()
	};

	jQuery.post(ajaxurl, data, function(response) {
		if (response.success) {
			var html = '';
			response.data.forEach(function(file) {
				html += '<li style="padding: 10px; background: #fff; border: 1px solid #eee; margin-bottom: 5px; display: flex; justify-content: space-between;">';
				html += '<span>' + file.file_name + '</span>';
				html += '<a href="' + file.file_url + '" class="button button-small" target="_blank">Download</a>';
				html += '</li>';
			});
			if (html === '') {
				html = '<p style="color: #999; text-align: center;">No files shared yet.</p>';
			}
			jQuery('#shared-files-list').html(html);
		}
	});
}

jQuery(document).ready(function($) {
	$('.chat-tab-btn').on('click', function() {
		$('.chat-tab-btn').removeClass('active');
		$(this).addClass('active');
		$('.chat-pane').hide();
		$('#chat-' + $(this).data('tab')).show();
		if ($(this).data('tab') === 'messages') {
			$('#chat-input').show();
		} else {
			$('#chat-input').hide();
		}
	});

	$('#share-file-btn').on('click', function(e) {
		e.preventDefault();
		const client_id = $('#chat-client-id').val();
		var file_frame = wp.media({
			title: 'Select File to Share',
			multiple: false
		}).open().on('select', function() {
			var attachment = file_frame.state().get('selection').first().toJSON();
			var data = {
				action: 'an_share_file',
				client_id: client_id,
				file_url: attachment.url,
				file_name: attachment.filename,
				security: $('#security').val()
			};
			$.post(ajaxurl, data, function() {
				loadFiles(client_id);
			});
		});
	});

	$('#an-message-form').on('submit', function(e) {
		e.preventDefault();
		const msg = $('#chat-message-text').val();
		if (!msg) return;

		$('#chat-messages').append('<div class="msg sent" style="margin-bottom: 15px; text-align: right;"><div style="background: #0073aa; color: #fff; padding: 10px; border-radius: 10px; display: inline-block; max-width: 80%;">' + msg + '</div></div>');
		$('#chat-message-text').val('');

		const data = $(this).serialize() + '&action=an_send_message';
		$.post(ajaxurl, data, function(response) {
			console.log('Message sent');
		});
	});
});
</script>
