<div class="wrap">
	<h1><?php _e( 'Smart Content Calendar', 'agency-nexus' ); ?></h1>
	<p><?php _e( 'Drag and drop content items to schedule them.', 'agency-nexus' ); ?></p>

	<?php wp_nonce_field( 'an_calendar_nonce', 'security' ); ?>

	<div id="an-content-creation" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-bottom: 20px;">
		<h3>Create New Content</h3>
		<form id="an-create-content-form" style="display: flex; gap: 10px; align-items: flex-end;">
			<div>
				<label>Project</label><br>
				<select name="project_id" required>
					<?php foreach ( $projects as $project ) : ?>
						<option value="<?php echo $project->id; ?>"><?php echo esc_html( $project->title ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div>
				<label>Title</label><br>
				<input type="text" name="title" required>
			</div>
			<button type="submit" class="button button-primary">Add to Unscheduled</button>
		</form>
	</div>

	<div id="an-calendar-container" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 10px; margin-top: 20px;">
		<?php
		$days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
		foreach ($days as $day) : ?>
			<div class="calendar-day-header" style="font-weight: bold; text-align: center; background: #eee; padding: 10px; border: 1px solid #ddd;">
				<?php echo $day; ?>
			</div>
		<?php endforeach; ?>

		<?php
		// Simple representation of a week for demo
		for ($i = 1; $i <= 7; $i++) : ?>
			<div class="calendar-day" data-date="2023-10-<?php echo sprintf('%02d', $i + 15); ?>" style="min-height: 150px; background: #fff; border: 1px solid #ddd; padding: 10px;" ondrop="drop(event)" ondragover="allowDrop(event)">
				<div class="day-number" style="color: #999; margin-bottom: 5px;"><?php echo $i + 15; ?></div>
				<?php
				foreach ($content_items as $item) {
					$item_day = date('j', strtotime($item->scheduled_date));
					if ($item_day == ($i + 15)) {
						?>
						<div class="content-item" draggable="true" ondragstart="drag(event)" id="content-<?php echo $item->id; ?>" data-id="<?php echo $item->id; ?>" style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 5px; margin-bottom: 5px; cursor: move; font-size: 12px;">
							<?php echo esc_html($item->title); ?>
						</div>
						<?php
					}
				}
				?>
			</div>
		<?php endfor; ?>
	</div>

	<div id="unscheduled-content" style="margin-top: 30px; background: #f9f9f9; padding: 20px; border: 1px dashed #ccc;">
		<h3><?php _e( 'Unscheduled Content', 'agency-nexus' ); ?></h3>
		<div class="calendar-day" data-date="" style="min-height: 50px; display: flex; flex-wrap: wrap; gap: 10px;" ondrop="drop(event)" ondragover="allowDrop(event)">
			<?php
			foreach ($content_items as $item) {
				if (empty($item->scheduled_date)) {
					?>
					<div class="content-item" draggable="true" ondragstart="drag(event)" id="content-<?php echo $item->id; ?>" data-id="<?php echo $item->id; ?>" style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 5px; cursor: move; font-size: 12px;">
						<?php echo esc_html($item->title); ?>
					</div>
					<?php
				}
			}
			?>
		</div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	$('#an-create-content-form').on('submit', function(e) {
		e.preventDefault();
		const data = $(this).serialize() + '&action=an_create_content&security=' + $('#security').val();
		$.post(ajaxurl, data, function(response) {
			if (response.success) {
				const newItem = $('<div class="content-item" draggable="true" ondragstart="drag(event)" id="content-' + response.data.id + '" data-id="' + response.data.id + '" style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 5px; cursor: move; font-size: 12px;">' + response.data.title + '</div>');
				$('#unscheduled-content .calendar-day').append(newItem);
				$('#an-create-content-form')[0].reset();
			}
		});
	});
});

function allowDrop(ev) {
	ev.preventDefault();
}

function drag(ev) {
	ev.dataTransfer.setData("text", ev.target.id);
}

function drop(ev) {
	ev.preventDefault();
	var data = ev.dataTransfer.getData("text");
	var target = ev.target;

	// Ensure we drop on a calendar-day div
	while (target && !target.classList.contains('calendar-day')) {
		target = target.parentElement;
	}

	if (target) {
		target.appendChild(document.getElementById(data));
		var itemId = document.getElementById(data).getAttribute('data-id');
		var newDate = target.getAttribute('data-date');

		updateContentDate(itemId, newDate);
	}
}

function updateContentDate(itemId, newDate) {
	var jQuery = window.jQuery;
	var data = {
		action: 'an_update_content_date',
		item_id: itemId,
		new_date: newDate,
		security: jQuery('#security').val()
	};

	jQuery.post(ajaxurl, data, function(response) {
		if (response.success) {
			console.log('Date updated successfully');
		} else {
			alert('Failed to update date');
		}
	});
}
</script>
<style>
.content-item:hover {
	background: #bbdefb !important;
}
</style>
