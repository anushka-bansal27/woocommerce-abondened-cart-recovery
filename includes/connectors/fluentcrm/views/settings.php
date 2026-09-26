<?php
/**
 * Settings for the card
 *
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Connectors/Fluentcrm
 */


$ajax_url         = esc_url( WCAP_ADMIN_AJAX_URL );
$connector_name   = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : ''; //phpcs:ignore
$saved_data       = Wcap_Connectors_Common::wcap_get_connectors_data( $connector_name );
$old_data         = ( is_array( $saved_data ) && count( $saved_data ) > 0 ) ? $saved_data : array();
$api_name         = isset( $old_data['api_name'] ) ? $old_data['api_name'] : '';
$api_key          = isset( $old_data['api_key'] ) ? $old_data['api_key'] : '';
$selected_list   = isset( $old_data['default_list'] ) ? $old_data['default_list'] : '';
$connector_status = isset( $old_data['status'] ) ? $old_data['status'] : '';
?>
<div class='wcap-settings-body'>
<?php
if ( ! is_plugin_active( 'fluent-crm/fluent-crm.php' ) ) {
	esc_html_e( 'FluentCRM plugin is not active. Please install and activate it.', 'woocommerce-ac' );
	return;
}
?>
<div class="wcap-form-group featured field-input">
		<label for="automation-name"><?php echo esc_html__( 'Enter API Username', 'woocommerce-ac' ); ?></label>
		<div class="field-wrap">
			<div class="wrapper">
				<input type="text" name="api_name" placeholder="<?php echo esc_attr__( 'Enter API Username', 'woocommerce-ac' ); ?>" class="wcap_test form-control wcap_fluentcrm_api_name" required value="<?php echo esc_attr( $api_name ); ?>">
				<div class="warning" id="api_name_warn"><?php echo esc_html__( 'Please enter a API Username', 'woocommerce-ac' ); ?></div>
			</div>
		</div>
	</div>
	<div class="wcap-form-group featured field-input">
		<label for="automation-name"><?php echo esc_html__( 'Enter API Password', 'woocommerce-ac' ); ?></label>
		<div class="field-wrap">
			<div class="wrapper">
				<input type="text" name="api_key" placeholder="<?php echo esc_attr__( 'Enter API Password', 'woocommerce-ac' ); ?>" class="wcap_test form-control wcap_fluentcrm_api_key" required value="<?php echo esc_attr( $api_key ); ?>">
				<div class="warning" id="api_key_warn"><?php echo esc_html__( 'Please enter a API Password', 'woocommerce-ac' ); ?></div>
			</div>
		</div>
	</div>
	<div class="wcap-form-group featured field-input wcap_fluentcrm_select_list_box">
		<label for="automation-name"><?php echo esc_html__( 'Select Default List', 'woocommerce-ac' ); ?></label>
		<div class="field-wrap">
			<div class="wrapper">
				<select name="default_list" class="wcap_test wcap_fluentcrm_select_lists form-control">
					<option value=""><?php echo esc_html__( 'Choose a list', 'woocommerce-ac' ); ?></option>
				</select>
				<div class="warning" id="api_list_warn"><?php echo esc_html__( 'Please choose a list', 'woocommerce-ac' ); ?></div>
			</div>
		</div>
	</div>
	<?php
	if ( 'active' !== $connector_status ) {
		?>
	<div class="wcap-form-groups wcap_form_submit wcap_fluentcrm_next_step">
		<button class="wcap_fluentcrm_fetch_lists button-primary"><?php echo esc_html__( 'Next Step', 'woocommerce-ac' ); ?></button>
	</div>
	<?php } ?>
	<div class="wcap-form-groups wcap_form_submit wcap_fluentcrm_main_submit">
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
				$('.wcap_fluentcrm_main_submit').hide();
				$('.wcap_fluentcrm_select_list_box').hide();				

				if (!_.isEmpty($('.wcap_fluentcrm_api_key').val())) {
					fetch_lists_with_update_ui();
				}
			} else {
				// place an ajax call to fetch the List ID.
				var data = {
					'action': 'wcap_get_existing_settings_fluentcrm'
				};
				$(document.body).addClass('wcap-modal-loading');
				$.post( '<?php echo esc_attr( esc_url( WCAP_ADMIN_AJAX_URL ) ); ?>', data, function( result ) {

					if ( result.status === 'success' ) {
						for (const [key, value] of Object.entries( result.lists )) {
							let selected = (`${key}` === '<?php echo esc_attr( $selected_list ); ?>') ? 'selected' : '';
							$('.wcap_fluentcrm_select_lists').append('<option ' + selected + ' value="' + `${key}` + '">' + `${value}` + '</option>');
						}						
					} else {
						Modal.contents( '<p class="wcap_msg">' + response.message + '</p>');
					}
					$(document.body).removeClass('wcap-modal-loading');
				});
			}

			if ( window.ac_settings_loaded !=='yes' ) {
				$('body').on('click', '.wcap_fluentcrm_fetch_lists', fetch_lists_with_update_ui);
				$('body').on('click', '.wcap_save_btn', wcap_save_connector_settings );
			}
			window.ac_settings_loaded = 'yes';
		});

		function fetch_lists_with_update_ui() {

			var ajax_data = {
				'api_name': $('.wcap_fluentcrm_api_name').val(),
				'api_key': $('.wcap_fluentcrm_api_key').val(),
				'action': 'wcap_get_fluentcrm_lists'
			};

			if ( ajax_data.api_name =='' ) {
				jQuery('#api_name_warn').show();
				return;
			}
			jQuery('#api_name_warn').hide();
			if( ajax_data.api_key =='' ) {
				jQuery('#api_key_warn').show();
				return;
			}			
			jQuery('#api_key_warn').hide();
			if ($('.wcap_fluentcrm_select_list_box').css('display') !== 'none') {
				if ('' === $('.wcap_fluentcrm_select_lists').val()) {
					$('.wcap_fluentcrm_next_step').prepend('<p><?php echo esc_html__( 'Select a list to continue', 'woocommerce-ac' ); ?></p>');
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

			$.post( '<?php echo esc_attr( esc_url( WCAP_ADMIN_AJAX_URL ) ); ?>', ajax_data, function( result ) {
				enable_fields();
				if (_.has(result, 'status') && (false === result.status || 'failed' === result.status)) {
					$('.wcap_fluentcrm_next_step').prepend('<p>' + result.message + '</p>');
					return;
				}

				_.each(result, function (item, key) {
					let selected = (key === '<?php echo esc_attr( $selected_list ); ?>') ? 'selected' : '';
					selected = (1 === _.size(result)) ? 'selected' : selected;
					$('.wcap_fluentcrm_select_lists').append('<option ' + selected + ' value="' + key + '">' + item + '</option>');
				});

				$('.wcap_fluentcrm_fetch_lists').hide();
				$('.wcap_fluentcrm_select_list_box').show();
				$('.wcap_fluentcrm_main_submit').show();

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
				'api_name': $('.wcap_fluentcrm_api_name').val(),
				'api_key': $('.wcap_fluentcrm_api_key').val(),
				'action': 'wcap_get_fluentcrm_lists'
			};
			$.post( '<?php echo esc_attr( esc_url( WCAP_ADMIN_AJAX_URL ) ); ?>', ajax_data, function( result ) {
				enable_fields();
				if (_.has(result, 'status') && (false === result.status || 'failed' === result.status)) {
					$('.wcap_fluentcrm_next_step').prepend('<p>' + result.message + '</p>');
					return;
				}

				$('.wcap_fluentcrm_fetch_lists').hide();
				$('.wcap_fluentcrm_main_submit').show();
			});
		}

		function wcap_save_connector_settings() {
			var settings = {
				'api_name': $('.wcap_fluentcrm_api_name').val(),
				'api_key': $('.wcap_fluentcrm_api_key').val(),
				'default_list': $('.wcap_fluentcrm_select_lists').val()
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
				'connector': 'fluentcrm'
			};
			$(document.body).addClass('wcap-modal-loading');
			$.post( '<?php echo esc_attr( esc_url( WCAP_ADMIN_AJAX_URL ) ); ?>', ajax_data, function( response ) {
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
						document.getElementById("wcap_fluentcrm_connect_div").style.display = "none";
						document.getElementById("wcap_fluentcrm_connected_div").style.display = "block";
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
			$('.wcap_fluentcrm_api_key').removeAttr('disabled');
			$('.wcap_fluentcrm_fetch_lists').removeAttr('disabled').text('<?php echo esc_html__( 'Next Step', 'woocommerce-ac' ); ?>');
		}

		function disable_fields() {
			$('.wcap_fluentcrm_next_step p').remove();
			$('.wcap_fluentcrm_api_key').attr('disabled', 'disabled');
			//$('.wcap_fluentcrm_fetch_lists').attr('disabled', 'disabled').text('<?php echo esc_html__( 'Loading...', 'woocommerce-ac' ); ?>');
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
