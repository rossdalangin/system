<div class="wrap">
	<h1><?php _e( 'Interactive Scope Builder', 'agency-nexus' ); ?></h1>
	<p><?php _e( 'Guidance: Use this tool to quickly define a project scope and create a corresponding project record. Select a client, service type, and project scale to auto-calculate the budget.', 'agency-nexus' ); ?></p>

	<div id="an-scope-builder-app" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-top: 20px;">
		<form id="an-scope-form">
			<?php wp_nonce_field( 'an_scope_nonce', 'security' ); ?>

			<div class="scope-section">
				<h3><?php _e( '0. Select Client', 'agency-nexus' ); ?></h3>
				<select name="client_id" required>
					<option value=""><?php _e( 'Select a Client', 'agency-nexus' ); ?></option>
					<?php foreach ( $clients as $client ) : ?>
						<option value="<?php echo esc_attr( $client->id ); ?>"><?php echo esc_html( $client->name ); ?></option>
					<?php endforeach; ?>
				</select>
				<p><small><?php _e( 'Don\'t see your client? Add them in the Clients section.', 'agency-nexus' ); ?></small></p>
			</div>

			<div class="scope-section" style="margin-top: 20px;">
				<h3><?php _e( '1. Service Type', 'agency-nexus' ); ?></h3>
				<select name="service_type" id="an-service-type">
					<?php foreach ( $services as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="scope-section" style="margin-top: 20px;">
				<h3><?php _e( '2. Project Scale', 'agency-nexus' ); ?></h3>
				<?php
				$first = true;
				foreach ( $scales as $key => $data ) : ?>
					<label>
						<input type="radio" name="scale" value="<?php echo esc_attr($key); ?>" <?php checked($first); ?>> <?php echo esc_html($data['label']); ?> ($<?php echo number_format($data['budget']); ?>)
					</label><br>
				<?php
				$first = false;
				endforeach; ?>
			</div>

			<div class="scope-section" style="margin-top: 20px;">
				<h3><?php _e( '3. Deliverables', 'agency-nexus' ); ?></h3>
				<div id="an-deliverables-container">
					<!-- Deliverables will be populated via JS -->
					<p style="color:#999;"><?php _e('Select a service type to see deliverables.', 'agency-nexus'); ?></p>
				</div>
			</div>

			<div class="scope-section" style="margin-top: 20px;">
				<h3><?php _e( '4. Custom Proposal Fields', 'agency-nexus' ); ?></h3>
				<label>Proposal Expiry Date</label><br>
				<input type="date" name="expiry_date" class="regular-text"><br><br>
				<label>Special Terms / Discounts</label><br>
				<textarea name="special_terms" class="regular-text" rows="2"></textarea>
			</div>

			<div style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">
				<button type="submit" class="button button-primary" id="an-generate-proposal"><?php _e( 'Create Project', 'agency-nexus' ); ?></button>
				<span class="spinner"></span>
			</div>
		</form>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	const deliverables = <?php echo json_encode($deliverables); ?>;

	$('#an-service-type').on('change', function() {
		const service = $(this).val();
		const container = $('#an-deliverables-container');
		container.empty();

		if (deliverables[service]) {
			deliverables[service].forEach(function(item) {
				container.append('<label><input type="checkbox" name="deliverables[]" value="' + item + '" checked> ' + item + '</label><br>');
			});
		} else {
			container.html('<p style="color:#999;"><?php _e('No preset deliverables for this service.', 'agency-nexus'); ?></p>');
		}
	}).trigger('change');

	$('#an-scope-form').on('submit', function(e) {
		e.preventDefault();
		const btn = $('#an-generate-proposal');
		btn.prop('disabled', true);
		$('.spinner').addClass('is-active');

		const data = $(this).serialize() + '&action=an_save_scope';

		$.post(ajaxurl, data, function(response) {
			$('.spinner').removeClass('is-active');
			btn.prop('disabled', false);
			if (response.success) {
				alert('Project created successfully! ID: ' + response.data.project_id);
			} else {
				alert('Error: ' + response.data);
			}
		});
	});
});
</script>
