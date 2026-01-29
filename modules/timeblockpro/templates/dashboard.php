<div class="wrap">
	<h1><?php _e( 'Intelligent Time Blocking', 'agency-nexus' ); ?></h1>
	<p><?php _e( 'Organize your day into deep work and shallow work blocks.', 'agency-nexus' ); ?></p>

	<div style="display: flex; gap: 20px; margin-top: 20px;">
		<div id="time-blocks-list" style="flex: 2; background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
			<h3><?php _e( 'Today\'s Blocks', 'agency-nexus' ); ?></h3>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php _e( 'Time', 'agency-nexus' ); ?></th>
						<th><?php _e( 'Task/Block Name', 'agency-nexus' ); ?></th>
						<th><?php _e( 'Type', 'agency-nexus' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ($blocks) : foreach ($blocks as $block) : ?>
						<tr>
							<td><?php echo date('H:i', strtotime($block->start_time)) . ' - ' . date('H:i', strtotime($block->end_time)); ?></td>
							<td><strong><?php echo esc_html($block->title); ?></strong></td>
							<td><span class="badge" style="background: <?php echo $block->type == 'deep_work' ? '#d32f2f' : '#1976d2'; ?>; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 11px;"><?php echo esc_html(ucfirst(str_replace('_', ' ', $block->type))); ?></span></td>
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
