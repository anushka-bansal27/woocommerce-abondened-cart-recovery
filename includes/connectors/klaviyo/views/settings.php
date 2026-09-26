<?php
/**
 * Settings for the card
 *
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Connectors/klaviyo
 */

$ajax_url         = esc_url( WCAP_ADMIN_AJAX_URL );
$connector_name   = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : ''; //phpcs:ignore 
$saved_data       = Wcap_Connectors_Common::wcap_get_connectors_data( $connector_name );
$old_data         = ( is_array( $saved_data ) && count( $saved_data ) > 0 ) ? $saved_data : array();
$public_key       = isset( $old_data['public_key'] ) ? $old_data['public_key'] : '';
$private_key      = isset( $old_data['private_key'] ) ? $old_data['private_key'] : '';
$selected_lists   = isset( $old_data['default_list'] ) ? $old_data['default_list'] : '';
$connector_status = isset( $old_data['status'] ) ? $old_data['status'] : '';
?>
<div class='wcap-settings-body'>
<div class="wcap-form-group featured field-input">
		<label for="automation-name"><?php echo esc_html__( 'Enter Public Key', 'woocommerce-ac' ); ?></label>
		<div class="field-wrap">
			<div class="wrapper">
				<input type="text" name="public_key" placeholder="<?php echo esc_attr__( 'Enter API URL', 'woocommerce-ac' ); ?>" class="wcap_test form-control wcap_klaviyo_public_key" required value="<?php echo esc_attr( $public_key ); ?>">
				<div class="warning" id="public_key_warn">Please enter a Public API Key</div>
			</div>
		</div>
	</div>
	<div class="wcap-form-group featured field-input">
		<label for="automation-name"><?php echo esc_html__( 'Enter Private Key', 'woocommerce-ac' ); ?></label>
		<div class="field-wrap">
			<div class="wrapper">
				<input type="text" name="private_key" placeholder="<?php echo esc_attr__( 'Enter API Key', 'woocommerce-ac' ); ?>" class="wcap_test form-control wcap_klaviyo_private_key" required value="<?php echo esc_attr( $private_key ); ?>">
				<div class="warning" id="private_key_warn">Please enter a Public API Key</div>
			</div>
		</div>
	</div>
	<div class="wcap-form-group featured field-input wcap_klaviyo_select_list_box">
		<label for="automation-name"><?php echo esc_html__( 'Select Default List', 'woocommerce-ac' ); ?></label>
		<div class="field-wrap">
			<div class="wrapper">
				<select name="default_list" class="wcap_test wcap_klaviyo_select_lists form-control">
					<option value=""><?php echo esc_html__( 'Choose a list', 'woocommerce-ac' ); ?></option>
				</select>
				<div class="warning" id="api_list_warn">Please choose a list</div>
			</div>
		</div>
	</div>
	<?php
	if ( 'active' !== $connector_status ) {
		?>
	<div class="wcap-form-groups wcap_form_submit wcap_klaviyo_next_step">
		<button class="wcap_klaviyo_fetch_lists button-primary"><?php echo esc_html__( 'Next Step', 'woocommerce-ac' ); ?></button>
	</div>
	<?php } ?>
	<div class="wcap-form-groups wcap_form_submit wcap_klaviyo_main_submit">
		<input type="hidden" name="wcap_connector" value="<?php echo esc_attr( $this->get_slug() ); ?>"/>
		<input type="hidden" name="wcap_connector_status" id="wcap_connector_status" value="<?php echo esc_attr( $connector_status ); ?>"/>
		<?php
		if ( isset( $old_data['status'] ) && 'active' === $old_data['status'] ) {
			?>
			<input type="submit" class="wcap_update_btn_style button-primary wcap_save_btn" name="autoresponderSubmit" value="<?php echo esc_attr__( 'Update', 'woocommerce-ac' ); ?>">
		<?php } else { ?>
			<input type="submit" class="button-primary wcap_save_btn" name="autoresponderSubmit" value="<?php echo esc_attr__( 'Save', 'woocommerce-ac' ); ?>">
		<?php } ?>
	</div>
	</div>
<div class="wcap_form_response" style="text-align: center;font-size: 20px;margin-top: 10px; font-weight:600; "></div>

<script>
	jQuery( function( $ ) {
		$(document).ready(function () {
			if ( 'active' !== $('#wcap_connector_status').val() ) {
				$('.wcap_klaviyo_main_submit').hide();
				$('.wcap_klaviyo_select_list_box').hide();
				$('.wcap_klaviyo_select_store_box').hide();

				if (!_.isEmpty($('.wcap_klaviyo_private_key').val())) {
					fetch_lists_with_update_ui();
				}
			} else {
				// place an ajax call to fetch the List ID.
				var data = {
					'action': 'wcap_get_existing_settings_klaviyo'
				};
				$(document.body).addClass('wcap-modal-loading');
				$.post( '<?php echo esc_url( WCAP_ADMIN_AJAX_URL ); // phpcs:ignore ?>', data, function( result ) {

					if ( result.status === 'success' ) {
						for (const [key, value] of Object.entries( result.lists )) {
							let selected = (`${key}` === '<?php echo addslashes( $selected_lists ); // phpcs:ignore ?>') ? 'selected' : '';
							$('.wcap_klaviyo_select_lists').append('<option ' + selected + ' value="' + `${key}` + '">' + `${value}` + '</option>');
						}						
					} else {
						Modal.contents( '<p class="wcap_msg">' + response.message + '</p>');
					}
					$(document.body).removeClass('wcap-modal-loading');
				});
			}

			if ( window.ac_settings_loaded !=='yes' ) {
				$('body').on('click', '.wcap_klaviyo_fetch_lists', fetch_lists_with_update_ui);
				$('body').on('click', '.wcap_save_btn', wcap_save_connector_settings );
			}
			window.ac_settings_loaded = 'yes';
		});

		function fetch_lists_with_update_ui() {

			var ajax_data = {
				'public_key': $('.wcap_klaviyo_public_key').val(),
				'private_key': $('.wcap_klaviyo_private_key').val(),
				'action': 'wcap_get_klaviyo_lists'
			};

			if ( ajax_data.public_key =='' ) {
				jQuery('#public_key_warn').show();
				return;
			}
			jQuery('#public_key_warn').hide();
			if( ajax_data.private_key =='' ) {
				jQuery('#private_key_warn').show();
				return;
			}			
			jQuery('#private_key_warn').hide();
			if ($('.wcap_klaviyo_select_list_box').css('display') !== 'none') {
				if ('' === $('.wcap_klaviyo_select_lists').val()) {
					$('.wcap_klaviyo_next_step').prepend('<p><?php echo esc_html__( 'Select a list to continue', 'woocommerce-ac' ); ?></p>');
				} else {
					fetch_lists();
				}
				return false;
			}
			if ( '1' == localStorage.getItem( 'wcap_ac_lists' ) ) {
				// return;
			}
			disable_fields();
			//localStorage.setItem( 'wcap_ac_lists', '1' );

			$.post( '<?php echo addslashes( esc_url( WCAP_ADMIN_AJAX_URL ) ); //phpcs:ignore ?>', ajax_data, function( result ) {
				enable_fields();
				if (_.has(result, 'status') && (false === result.status || 'failed' === result.status)) {
					$('.wcap_klaviyo_next_step').prepend('<p>' + result.message + '</p>');
					return;
				}

				_.each(result, function (item, key) {
					let selected = (key === '<?php echo addslashes( $selected_lists ); // phpcs:ignore ?>') ? 'selected' : '';
					selected = (1 === _.size(result)) ? 'selected' : selected;
					$('.wcap_klaviyo_select_lists').append('<option ' + selected + ' value="' + key + '">' + item + '</option>');
				});

				$('.wcap_klaviyo_fetch_lists').hide();
				$('.wcap_klaviyo_select_list_box').show();
				$('.wcap_klaviyo_main_submit').show();

			});
			return false;
		}

		function fetch_lists() {
			if ( '1' == localStorage.getItem( 'wcap_ac_stores' ) ) {
				//return;
			}
			disable_fields();
			// localStorage.setItem( 'wcap_ac_stores', '1' )
			var ajax_data = {
				'private_key': $('.wcap_klaviyo_private_key').val(),
				'list_id': $('.wcap_klaviyo_select_lists').val(),
				'action': 'wcap_get_klaviyo_stores'
			};
			$.post( '<?php echo addslashes( esc_url( WCAP_ADMIN_AJAX_URL ) ); // phpcs:ignore  ?>', ajax_data, function( result ) {
				enable_fields();
				if (_.has(result, 'status') && (false === result.status || 'failed' === result.status)) {
					$('.wcap_klaviyo_next_step').prepend('<p>' + result.message + '</p>');
					return;
				}

				$('.wcap_klaviyo_fetch_lists').hide();
				$('.wcap_klaviyo_main_submit').show();
			});
		}

		function wcap_save_connector_settings() {
			var settings = {
				'public_key': $('.wcap_klaviyo_public_key').val(),
				'private_key': $('.wcap_klaviyo_private_key').val(),
				'default_list': $('.wcap_klaviyo_select_lists').val()
			};
			if( settings.default_list =='' ) {
				jQuery('#api_list_warn').show();
				return;
			}
			jQuery('#api_list_warn').hide();
			var ajax_data = {
				'action': 'wcap_save_connector_settings',
				'security': '<?php echo esc_js( wp_create_nonce( "wcap_settings_nonce" ) ); ?>',
				'settings': settings,
				'connector': 'klaviyo'
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
						document.getElementById("wcap_klaviyo_connect_div").style.display = "none";
						document.getElementById("wcap_klaviyo_connected_div").style.display = "block";
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

		function enable_fields() {
			$('.wcap_klaviyo_private_key').removeAttr('disabled');
			$('.wcap_klaviyo_fetch_lists').removeAttr('disabled').text('<?php echo esc_html__( 'Next Step', 'woocommerce-ac' ); ?>' );
		}

		function disable_fields() {
			$('.wcap_klaviyo_next_step p').remove();
			$('.wcap_klaviyo_private_key').attr('disabled', 'disabled');
			//$('.wcap_klaviyo_fetch_lists').attr('disabled', 'disabled').text('<?php echo esc_html__( 'Loading...', 'woocommerce-ac' ); ?>' );
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
