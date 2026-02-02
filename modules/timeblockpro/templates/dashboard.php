<div class="agency-nexus-wrap">
	<h1><?php _e( 'Intelligent Time Blocking', 'agency-nexus' ); ?></h1>

	<div style="background: #fff; border-left: 4px solid #d32f2f; padding: 15px; margin: 20px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
		<h3><?php _e( 'Design Your High-Performance Day', 'agency-nexus' ); ?></h3>
		<p><?php _e( 'Time blocking is the practice of planning every part of your day. By creating distinct blocks for different types of work, you eliminate context-switching and protect your most valuable asset: your focus.', 'agency-nexus' ); ?></p>
		<ul style="list-style: disc; margin-left: 20px;">
			<li><strong>Deep Work:</strong> Intense, focused work without distractions (e.g., coding, writing, designing).</li>
			<li><strong>Shallow Work:</strong> Administrative tasks, emails, and meetings.</li>
			<li><strong>AI Suggestions:</strong> Look at the sidebar for personalized tips on how to optimize your schedule based on your productivity patterns.</li>
		</ul>
	</div>

	<div style="display: flex; gap: 20px; margin-top: 20px;">
		<div id="time-blocks-list" style="flex: 2; background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
			<div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
				<h3 style="margin:0;"><?php _e( 'Schedule Blocks', 'agency-nexus' ); ?></h3>
				<a href="?page=an-time-blocking&action=add" class="button button-primary"><?php _e('Add Block', 'agency-nexus'); ?></a>
			</div>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php _e( 'Time', 'agency-nexus' ); ?></th>
						<th><?php _e( 'Task/Block Name', 'agency-nexus' ); ?></th>
						<th><?php _e( 'Type', 'agency-nexus' ); ?></th>
						<th><?php _e( 'Actions', 'agency-nexus' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ($blocks) : foreach ($blocks as $block) : ?>
						<tr>
							<td><?php echo date('Y-m-d H:i', strtotime($block->start_time)) . ' - ' . date('H:i', strtotime($block->end_time)); ?></td>
							<td><strong><?php echo esc_html($block->title); ?></strong></td>
							<td><span class="badge" style="background: <?php echo $block->type == 'deep_work' ? '#d32f2f' : ($block->type == 'break' ? '#4caf50' : '#1976d2'); ?>; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 11px;"><?php echo esc_html(ucfirst(str_replace('_', ' ', $block->type))); ?></span></td>
							<td>
								<a href="?page=an-time-blocking&action=edit&id=<?php echo $block->id; ?>"><?php _e('Edit', 'agency-nexus'); ?></a> |
								<a href="<?php echo wp_nonce_url('?page=an-time-blocking&action=delete&id=' . $block->id, 'an_delete_block_' . $block->id); ?>" style="color:red;" onclick="return confirm('Delete block?')"><?php _e('Delete', 'agency-nexus'); ?></a>
							</td>
						</tr>
					<?php endforeach; else : ?>
						<tr>
							<td>09:00 - 11:00</td>
							<td><strong>Deep Work: Core Development</strong></td>
							<td><span class="badge" style="background: #d32f2f; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 11px;">Deep Work</span></td>
						</tr>
						<tr>
							<td>11:00 - 12:00</td>
							<td><strong>Emails & Slack</strong></td>
							<td><span class="badge" style="background: #1976d2; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 11px;">Shallow Work</span></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<div id="productivity-insights" style="flex: 1; background: #f0f0f1; padding: 20px; border: 1px solid #ccd0d4;">
			<h3><?php _e( 'Productivity Insights', 'agency-nexus' ); ?></h3>
			<div style="margin-bottom: 20px;">
				<label><?php _e( 'Deep Work Focus', 'agency-nexus' ); ?></label>
				<div style="background: #ddd; height: 10px; border-radius: 5px;">
					<div style="background: #d32f2f; width: 65%; height: 10px; border-radius: 5px;"></div>
				</div>
				<small>65% of target reached</small>
			</div>

			<div class="card" style="background: #fff; padding: 15px; border-radius: 4px; border: 1px solid #ddd;">
				<h4><?php _e( 'AI Suggestion', 'agency-nexus' ); ?></h4>
				<p><em>"Your cognitive load is usually lowest on Tuesday afternoons. Consider moving your 'Project Planning' block to then for better results."</em></p>
			</div>
		</div>
	</div>
</div>
