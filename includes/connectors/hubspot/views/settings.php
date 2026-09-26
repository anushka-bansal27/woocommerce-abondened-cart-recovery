<?php
$ajax_url         = esc_url( WCAP_ADMIN_AJAX_URL );
$connector_name   = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';//phpcs:ignore
$prefixed_cn_name = '' !== $connector_name && 'wcap' !== substr( $connector_name, 0, 4 ) ? "wcap_$connector_name" : $connector_name;
$saved_data       = Wcap_Connectors_Common::wcap_get_connectors_data( $prefixed_cn_name );
$old_data         = ( isset( $saved_data ) && is_array( $saved_data ) && count( $saved_data ) > 0 ) ? $saved_data : array();
$api_key          = isset( $old_data['api_key'] ) ? $old_data['api_key'] : '';
$connector_status = isset( $old_data['status'] ) ? $old_data['status'] : '';
?>
<div class="wcap-form-group featured field-input">
	<label for="automation-name"><?php echo esc_html__( 'Enter Access Token', 'woocommerce-ac' ); ?></label>
	<div class="field-wrap">
		<div class="wrapper">
			<input type="text" name="api_key" placeholder="<?php echo esc_attr__( 'Enter Access Token', 'woocommerce-ac' ); ?>" class="form-control wcap-hubspot-api-key" required value="<?php echo esc_attr__( $api_key );//phpcs:ignore ?>">
		</div>
	</div>
</div>
<div class="wcap-form-groups wcap_form_submit">
	<input type="hidden" name="wcap_connector" value="<?php echo esc_attr( "wcap_$connector_name" ); ?>"/>
	<input type="hidden" name="wcap_connector_status" id="wcap_connector_status" value="<?php echo esc_attr( $connector_status ); ?>"/>
	<?php
	if ( 'active' === $connector_status ) {
		?>
		<input type="submit" class="wcap_update_btn_style wcap_hubspot_save_btn button-primary" name="autoresponderSubmit" value="<?php echo esc_attr__( 'Update', 'woocommerce-ac' ); ?>">
	<?php } else { ?>
		<input type="submit" class="wcap_hubspot_save_btn button-primary" name="autoresponderSubmit" value="<?php echo esc_attr__( 'Save', 'woocommerce-ac' ); ?>">
	<?php } ?>
</div>
<div class="wcap_form_response" style="text-align: center;font-size: 15px;margin-top: 10px;"></div>

<script>
	jQuery( function( $ ) {
		$(document).ready(function () {
			$('body').on('click', '.wcap_hubspot_save_btn', wcap_hubspot_save_connector_settings );
		});

		function wcap_hubspot_save_connector_settings() {

			var settings = {
				'api_key': $('.wcap-hubspot-api-key').val(),
			};
			var ajax_data = {
				'action': 'wcap_save_connector_settings',
				'security': '<?php echo esc_js( wp_create_nonce( "wcap_settings_nonce" ) ); ?>',
				'settings': settings,
				'connector': 'hubspot'
			};
			$(document.body).addClass('wcap-modal-loading');
			$.post( '<?php echo esc_url( esc_url( WCAP_ADMIN_AJAX_URL ) ); ?>', ajax_data, function( response ) {
				$(document.body).removeClass('wcap-modal-loading');
				var inactive_count = $('#wcap_inactive').attr('data-wcap-count');
				var active_count = $('#wcap_active').attr('data-wcap-count');

				if ( response.status === 'success' ) {
					$(document.body).addClass('wcap-modal-success');

					if ( 'active' !== $('#wcap_connector_status').val() ) {
						inactive_count--;
						active_count++;
						$('#wcap_active_count').text( active_count );
						$('#wcap_inactive_count').text( inactive_count );
						$('#wcap_active').attr( 'data-wcap-count', active_count );
						$('#wcap_inactive').attr( 'data-wcap-count', inactive_count );

						remove_nolinks( active_count, inactive_count );
					}

					Modal.contents( '<p class="wcap_msg">' + response.message + '</p>');
					setTimeout(() => {
						$(document.body).removeClass('wcap-modal-success');
						// close the modal.
						$(document.body).removeClass('wcap-modal-open');
						$('.wcap-modal, .wcap-modal-overlay').remove();	

						// Update the buttons.
						document.getElementById("wcap_hubspot_connect_div").style.display = "none";
						document.getElementById("wcap_hubspot_connected_div").style.display = "block";
					}, 2000);
				} else {
					$(document.body).addClass('wcap-modal-failure');
					Modal.contents( '<p class="wcap_msg">' + response.message + '</p>'); 
					setTimeout(() => {
						$(document.body).removeClass('wcap-modal-failure');
						// close the modal.
						$(document.body).removeClass('wcap-modal-open'); 
						$('.wcap-modal, .wcap-modal-overlay').remove();	    
					}, 2000);
				}
			//	localStorage.removeItem( 'wcap_mc_stores' );
			//	localStorage.removeItem( 'wcap_mc_lists' );
			});
		}
	});
</script>
