<div class="wrap">
	<h1><?php _e( 'Interactive Scope Builder', 'agency-nexus' ); ?></h1>
	<p><?php _e( 'Define the scope of your project by selecting services and parameters.', 'agency-nexus' ); ?></p>

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
				<select name="service_type">
					<option value="seo"><?php _e( 'SEO Strategy', 'agency-nexus' ); ?></option>
					<option value="web_design"><?php _e( 'Web Design & Development', 'agency-nexus' ); ?></option>
					<option value="social_media"><?php _e( 'Social Media Management', 'agency-nexus' ); ?></option>
					<option value="branding"><?php _e( 'Brand Identity & Design', 'agency-nexus' ); ?></option>
					<option value="content_marketing"><?php _e( 'Content Marketing', 'agency-nexus' ); ?></option>
					<option value="ppc"><?php _e( 'PPC & Ad Management', 'agency-nexus' ); ?></option>
				</select>
			</div>

			<div class="scope-section" style="margin-top: 20px;">
				<h3><?php _e( '2. Project Scale', 'agency-nexus' ); ?></h3>
				<label>
					<input type="radio" name="scale" value="small" checked> <?php _e( 'Small (1-2 weeks)', 'agency-nexus' ); ?>
				</label><br>
				<label>
					<input type="radio" name="scale" value="medium"> <?php _e( 'Medium (1 month)', 'agency-nexus' ); ?>
				</label><br>
				<label>
					<input type="radio" name="scale" value="large"> <?php _e( 'Large (3+ months)', 'agency-nexus' ); ?>
				</label>
			</div>

			<div class="scope-section" style="margin-top: 20px;">
				<h3><?php _e( '3. Deliverables', 'agency-nexus' ); ?></h3>
				<label><input type="checkbox" name="deliverables[]" value="audit"> <?php _e( 'Initial Audit', 'agency-nexus' ); ?></label><br>
				<label><input type="checkbox" name="deliverables[]" value="strategy"> <?php _e( 'Content Strategy', 'agency-nexus' ); ?></label><br>
				<label><input type="checkbox" name="deliverables[]" value="implementation"> <?php _e( 'Implementation', 'agency-nexus' ); ?></label><br>
				<label><input type="checkbox" name="deliverables[]" value="support"> <?php _e( 'Ongoing Support', 'agency-nexus' ); ?></label>
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
