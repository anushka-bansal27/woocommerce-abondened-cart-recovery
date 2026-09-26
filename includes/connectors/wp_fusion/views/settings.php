<?php
/**
 * Settings for the card
 *
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Connectors/wp_fusion
 */

$ajax_url         = esc_url( WCAP_ADMIN_AJAX_URL );
$connector_name   = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : ''; //phpcs:ignore 
$saved_data       = Wcap_Connectors_Common::wcap_get_connectors_data( $connector_name );
$old_data         = ( is_array( $saved_data ) && count( $saved_data ) > 0 ) ? $saved_data : array();
$connector_status = isset( $old_data['status'] ) ? $old_data['status'] : '';
?>
<div class='wcap-settings-body'>
<?php

if ( ! ( class_exists( 'WP_Fusion' ) || class_exists( 'WP_Fusion_Lite' ) ) ) {
	esc_html_e( 'WP Fusion plugin is not active. Please install and activate it.', 'woocommerce-ac' );
	return;
}
if ( empty( wp_fusion()->crm->name ) ) {
	esc_html_e( 'WP Fusion settings are empty. Please set up WP Fusion.', 'woocommerce-ac' );
} else {
	?>
	<div class="wcap-form-groups wcap_form_submit wcap_wp_fusion_main_submit">
		<input type="hidden" name="wcap_connector" value="<?php echo esc_attr( $this->get_slug() ); ?>"/>
		<input type="hidden" name="wcap_connector_status" id="wcap_connector_status" value="<?php echo esc_attr( $connector_status ); ?>"/>
		<?php
		if ( isset( $old_data['status'] ) && 'active' === $old_data['status'] ) {
			esc_html_e( 'Connected with WP Fusion. No other settings needed.', 'woocommerce-ac' );
			?>
		<?php } else { ?>
			<input type="submit" class="button-primary wcap_save_btn" name="autoresponderSubmit" value="<?php echo esc_attr__( 'Activate', 'woocommerce-ac' ); ?>">
		<?php } ?>
	</div>
	<?php
}
?>
	</div>
<div class="wcap_form_response" style="text-align: center;font-size: 20px;margin-top: 10px; font-weight:600; "></div>

<script>
	jQuery( function( $ ) {
		$(document).ready(function () {
			if ( window.ac_settings_loaded !=='yes' ) {
				$('body').on('click', '.wcap_save_btn', wcap_save_connector_settings );
			}
			window.ac_settings_loaded = 'yes';
		});

		function wcap_save_connector_settings() {
			var settings = {
				'name': '<?php esc_attr( wp_fusion()->crm->name ); ?>'
			};

			var ajax_data = {
				'action': 'wcap_save_connector_settings',
				'security': '<?php echo esc_js( wp_create_nonce( "wcap_settings_nonce" ) ); ?>',
				'settings': settings,
				'connector': 'wp_fusion'
			};
			$(document.body).addClass('wcap-modal-loading');
			$.post( '<?php echo addslashes( esc_url( WCAP_ADMIN_AJAX_URL ) ); //phpcs:ignore ?>', ajax_data, function( response ) {
				$(document.body).removeClass('wcap-modal-loading');
				var inactive_count = $('#wcap_inactive').attr('data-wcap-count');
				var active_count = $('#wcap_active').attr('data-wcap-count');

				if ( response.status === 'success' ) {
					$(document.body).addClass('wcap-modal-success');
					inactive_count--;
					active_count++;
					$('#wcap_active_count').text( active_count );
					$('#wcap_inactive_count').text( inactive_count );
					$('#wcap_active').attr( 'data-wcap-count', active_count );
					$('#wcap_inactive').attr( 'data-wcap-count', inactive_count );

					remove_nolinks( active_count, inactive_count );

					Modal.contents( '<p class="wcap_msg">' + response.message + '</p>');
					setTimeout(() => {
						$(document.body).removeClass('wcap-modal-success');
						// close the modal.
						$(document.body).removeClass('wcap-modal-open');
						$('.wcap-modal, .wcap-modal-overlay').remove();	

						// Update the buttons.
						document.getElementById("wcap_wp_fusion_connect_div").style.display = "none";
						document.getElementById("wcap_wp_fusion_connected_div").style.display = "block";
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
				localStorage.removeItem( 'wcap_ac_stores' );
			});
		}
	});	
	if( typeof( window.ac_settings_loaded ) =='undefined' ) {
		var ac_settings_loaded = 'no';
	}
	</script>
	<style type="text/css">
		.warning {
			display:none;
			color: red;
		}
	</style>
