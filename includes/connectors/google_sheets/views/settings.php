<?php
/**
 * Settings file for Connector popup.
 *
 * @package Abandoned Carts Pro/Connectors
 */

$ajax_url            = esc_url( WCAP_ADMIN_AJAX_URL );
$connector_name      = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : ''; // phpcs:ignore
$prefixed_cn_name    = '' !== $connector_name && 'wcap' !== substr( $connector_name, 0, 4 ) ? "wcap_$connector_name" : $connector_name;
$saved_data          = Wcap_Connectors_Common::wcap_get_connectors_data( $prefixed_cn_name );
$old_data            = ( isset( $saved_data ) && is_array( $saved_data ) && count( $saved_data ) > 0 ) ? $saved_data : array();
$client_id           = isset( $old_data['client_id'] ) ? $old_data['client_id'] : '';
$secret_key          = isset( $old_data['secret_key'] ) ? $old_data['secret_key'] : '';
$redirect_uri        = isset( $old_data['redirect_uri'] ) ? $old_data['redirect_uri'] : esc_url( WCAP_ADMIN_URL ) . '?page=woocommerce_ac_page&wcap_google_auth=1';
$sheet_title         = isset( $old_data['sheet_title'] ) ? $old_data['sheet_title'] : 'Abandoned Carts Pro - Cart Data';
$connector_status    = isset( $old_data['status'] ) ? $old_data['status'] : '';
$connect_url         = apply_filters( 'wcap_connect_to_google', '' );
$refresh_token_found = isset( $old_data['wcap_gsheets_refresh_token'] ) && '' !== $old_data['wcap_gsheets_refresh_token'] ? true : false;
$display_connect     = ! $refresh_token_found ? 'display:block;' : 'display:none;';
$display_live_link   = $refresh_token_found ? 'display:block;' : 'display:none;';
$google_sheet_id     = null !== get_option( 'wcap_google_sheet_id', null ) ? get_option( 'wcap_google_sheet_id' ) : '';
$google_sheet_url    = '' !== $google_sheet_id ? 'https://docs.google.com/spreadsheets/d/' . $google_sheet_id : '';
?>
<div class='wcap-settings-body'>
	<div class="wcap-form-group featured field-input">
		<label for="client_id"><?php echo esc_html__( 'Enter Client ID', 'woocommerce-ac' ); ?></label>
		<div class="field-wrap">
			<div class="wrapper">
				<input type="text" name="client_id" placeholder="<?php echo esc_attr__( 'Client ID', 'woocommerce-ac' ); ?>" class="wcap_test form-control wcap_google_sheets wcap_gsheets_client_id" required value="<?php echo esc_attr( $client_id ); ?>">
			</div>
		</div>
	</div>
	<div class="wcap-form-group featured field-input">
		<label for="secret_key"><?php echo esc_html__( 'Enter Secret Key', 'woocommerce-ac' ); ?></label>
		<div class="field-wrap">
			<div class="wrapper">
			<input type="text" name="secret_key" placeholder="<?php echo esc_attr__( 'Secret Key', 'woocommerce-ac' ); ?>" class="wcap_test form-control wcap_google_sheets wcap_gsheets_secret_key" required value="<?php echo esc_attr( $secret_key ); ?>">
			</div>
		</div>
	</div>
	<div class="wcap-form-group featured field-input">
		<label for="redirect_uri"><?php echo esc_html__( 'Redirect URI', 'woocommerce-ac' ); ?></label>
		<div class="field-wrap">
			<div class="wrapper">
			<input type="text" name="redirect_uri" readonly placeholder="<?php echo esc_attr__( 'Redirect URI to be saved in the Google OAUth credential settings.', 'woocommerce-ac' ); ?>" class="wcap_test form-control wcap_google_sheets wcap_gsheets_redirect_uri" required value="<?php echo esc_attr( $redirect_uri ); ?>">
			</div>
		</div>
	</div>
	<div class="wcap-form-group featured field-input">
		<label for="sheet_title"><?php echo esc_html__( 'Sheet Title', 'woocommerce-ac' ); ?></label>
		<div class="field-wrap">
			<div class="wrapper">
				<input type="text" name="sheet_title" placeholder="<?php echo esc_attr__( 'Sheet Title', 'woocommerce-ac' ); ?>" class="wcap_test form-control wcap_google_sheets wcap_gsheets_sheet_title" required value="<?php echo esc_attr( $sheet_title ); ?>">
			</div>
		</div>
	</div>
	<div class="wcap-form-groups wcap_form_submit wcap_gsheets_main_submit">
		<input type="hidden" name="wcap_connector" value="<?php echo esc_attr( $this->get_slug() ); ?>"/>
		<input type="hidden" name="wcap_connector_status" id="wcap_connector_status" value="<?php echo esc_attr( $connector_status ); ?>"/>
		<input type="hidden" name="wcap_gsheets_key_fields_modified" id="wcap_gsheets_key_fields_modified" value="0" />
		<?php
		if ( isset( $old_data['status'] ) && 'active' === $old_data['status'] ) {
			?>
			<input type="submit" class="wcap_update_btn_style button-primary wcap_save_btn" name="autoresponderSubmit" value="<?php echo esc_attr__( 'Update', 'woocommerce-ac' ); ?>">
		<?php } else { ?>
			<input type="submit" class="button-primary wcap_save_btn" name="autoresponderSubmit" value="<?php echo esc_attr__( 'Save', 'woocommerce-ac' ); ?>">
		<?php } ?>
			<a style='float:right; <?php echo esc_attr( $display_connect ); ?>' href="<?php echo esc_url( $connect_url ); ?>" class="button-primary wcap_connect_btn" name="autoresponderSubmit"><?php echo esc_attr__( 'Connect to Google', 'woocommerce-ac' ); ?></a>
			<a style='float:right; <?php echo esc_attr( $display_live_link ); ?>' href="<?php echo esc_url( $google_sheet_url ); ?>" target="_blank" class="mr-3"><i class="fas fa-file-alt"></i> <?php esc_html_e( 'Live Google Sheet', 'woocommerce-ac' ); ?></a>
	</div>
	</div>
<div class="wcap_form_response" style="text-align: center;font-size: 20px;margin-top: 10px; font-weight:600; "></div>

<script>
	jQuery( function( $ ) {
		$(document).ready(function () {
			$('body').on('click', '.wcap_save_btn', wcap_save_connector_settings );
			localStorage.removeItem( 'wcap_gs_save' );
			$('body').on('change', '.wcap_gsheets_secret_key', wcap_key_fields_modified );
			$('body').on('change', '.wcap_gsheets_client_id', wcap_key_fields_modified );
		});

		function wcap_key_fields_modified() {
			$('#wcap_gsheets_key_fields_modified').val('1');
		}
		function wcap_save_connector_settings() {
			if ( '1' == localStorage.getItem( 'wcap_gs_save' ) ) {
				return;
			}
			localStorage.setItem( 'wcap_gs_save', '1' );

			var settings = {
				'client_id': $('.wcap_gsheets_client_id').val(),
				'secret_key': $('.wcap_gsheets_secret_key').val(),
				'redirect_uri': $('.wcap_gsheets_redirect_uri').val(),
				'sheet_title': $('.wcap_gsheets_sheet_title').val()
			};
			var ajax_data = {
				'action': 'wcap_save_connector_settings',
				'security': '<?php echo esc_js( wp_create_nonce( "wcap_settings_nonce" ) ); ?>',
				'settings': settings,
				'connector': 'google_sheets'
			};
			$(document.body).addClass('wcap-modal-loading');
			$.post( '<?php echo esc_url( esc_url( WCAP_ADMIN_AJAX_URL ) ); ?>', ajax_data, function( response ) {
				$(document.body).removeClass('wcap-modal-loading');
				var inactive_count = $('#wcap_inactive').attr('data-wcap-count');
				var active_count = $('#wcap_active').attr('data-wcap-count');

				if ( response.status === 'success' ) {
					if ( '1' === $( '#wcap_gsheets_key_fields_modified' ).val() ) {
						$( '.wcap_connect_btn' ).show();
					} else {
						$(document.body).addClass('wcap-modal-success');
						Modal.contents( '<p class="wcap_msg">' + response.message + '</p>'); 
						setTimeout(() => {
							$(document.body).removeClass('wcap-modal-failure');
							// close the modal.
							$(document.body).removeClass('wcap-modal-open');
							$('.wcap-modal, .wcap-modal-overlay').remove();
						}, 2000 );
					}

				} else {
					if ( 'active' == $( '#wcap_connector_status' ).val() ) {
						inactive_count++;
						active_count--;
						$('#wcap_active_count').text( active_count );
						$('#wcap_inactive_count').text( inactive_count );
						$('#wcap_active').attr( 'data-wcap-count', active_count );
						$('#wcap_inactive').attr( 'data-wcap-count', inactive_count );	
					}
					$(document.body).addClass('wcap-modal-failure');
					Modal.contents( '<p class="wcap_msg">' + response.message + '</p>'); 
					setTimeout(() => {
						$(document.body).removeClass('wcap-modal-failure');
						// close the modal.
						$(document.body).removeClass('wcap-modal-open');
						$('.wcap-modal, .wcap-modal-overlay').remove();
						// Update the buttons.
						document.getElementById("wcap_google_sheets_connect_div").style.display = "block";
						document.getElementById("wcap_google_sheets_connected_div").style.display = "none";
					}, 2000);
				}
			});
		}
	});	
</script>
