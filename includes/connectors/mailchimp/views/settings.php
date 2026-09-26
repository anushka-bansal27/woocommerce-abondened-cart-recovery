<?php
$ajax_url = esc_url( WCAP_ADMIN_AJAX_URL );
$connector_name   = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';//phpcs:ignore
$prefixed_cn_name = '' !== $connector_name && 'wcap' !== substr( $connector_name, 0, 4 ) ? "wcap_$connector_name" : $connector_name;
$saved_data       = Wcap_Connectors_Common::wcap_get_connectors_data( $prefixed_cn_name );
$old_data         = ( isset( $saved_data ) && is_array( $saved_data ) && count( $saved_data ) > 0 ) ? $saved_data : array();
$api_key          = isset( $old_data['api_key'] ) ? $old_data['api_key'] : '';
$selected_list    = isset( $old_data['default_list'] ) ? $old_data['default_list'] : '';
$selected_store   = isset( $old_data['default_store'] ) ? $old_data['default_store'] : '';
$connector_status = isset( $old_data['status'] ) ? $old_data['status'] : '';
?>
<div class='wcap-settings-body'>
	<div class="wcap-form-group featured field-input">
		<label for="automation-name"><?php echo esc_html__( 'Enter API Key', 'woocommerce-ac' ); ?></label>
		<div class="field-wrap">
			<div class="wrapper">
				<input type="text" name="api_key" placeholder="<?php echo esc_attr__( 'Enter API Key', 'woocommerce-ac' ); ?>" class="wcap_test form-control wcap_mailchimp_api_key" required value="<?php echo esc_attr__( $api_key );//phpcs:ignore ?>">
			</div>
		</div>
	</div>
	<div class="wcap-form-group featured field-input wcap_mailchimp_select_list_box">
		<label for="automation-name"><?php echo esc_html__( 'Select Default List', 'woocommerce-ac' ); ?></label>
		<div class="field-wrap">
			<div class="wrapper">
				<select name="default_list" class="wcap_test wcap_mailchimp_select_list form-control">
					<option value=""><?php echo esc_html__( 'Choose a list', 'woocommerce-ac' ); ?></option>
				</select>
			</div>
		</div>
	</div>
	<div class="wcap-form-group featured field-input wcap_mailchimp_select_store_box">
		<label for="automation-name"><?php echo esc_html__( 'Select Default Store', 'woocommerce-ac' ); ?></label>
		<div class="field-wrap">
			<div class="wrapper">
				<select name="default_store" class="wcap_test wcap_mailchimp_select_store form-control">
					<option value=""><?php echo esc_html__( 'Choose a Store', 'woocommerce-ac' ); ?></option>
				</select>
			</div>
		</div>
	</div>
	<?php
	if ( 'active' !== $connector_status ) {
	?>
	<div class="wcap-form-groups wcap_form_submit wcap_mailchimp_next_step">
		<button class="wcap_mailchimp_fetch_lists button-primary"><?php echo esc_html__( 'Next Step', 'woocommerce-ac' ); ?></button>
	</div>
	<?php } ?>
	<div class="wcap-form-groups wcap_form_submit wcap_mailchimp_main_submit">
		<input type="hidden" name="wcap_connector" value="<?php echo esc_attr__( $this->get_slug() );//phpcs:ignore ?>"/>
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
				$('.wcap_mailchimp_main_submit').hide();
				$('.wcap_mailchimp_select_list_box').hide();
				$('.wcap_mailchimp_select_store_box').hide();

				if (!_.isEmpty($('.wcap_mailchimp_api_key').val())) {
					fetch_lists_with_update_ui();
				}
			} else {
				// place an ajax call to fetch the list & store ID.
				var data = {
					'action': 'wcap_get_existing_settings',
					'name': 'wcap_mailchimp'
				};
				$(document.body).addClass('wcap-modal-loading');
				$.post( '<?php echo esc_url( WCAP_ADMIN_AJAX_URL ); ?>', data, function( result ) {

					if ( result.status === 'success' ) {
						for (const [key, value] of Object.entries( result.lists )) {
							let selected = (`${key}` === '<?php echo esc_html( $selected_list ); ?>') ? 'selected' : '';
							$('.wcap_mailchimp_select_list').append('<option ' + selected + ' value="' + `${key}` + '">' + `${value}` + '</option>');
						}
						for (const [key, value] of Object.entries( result.stores )) {
							let selected = (`${key}` === '<?php echo esc_html( $selected_store ); ?>') ? 'selected' : '';
							$('.wcap_mailchimp_select_store').append('<option ' + selected + ' value="' + `${key}` + '">' + `${value}` + '</option>');
						}
					} else {
						Modal.contents( '<p class="wcap_msg">' + response.message + '</p>');
					}
					$(document.body).removeClass('wcap-modal-loading');
				});
			}
			$('body').on('click', '.wcap_mailchimp_fetch_lists', fetch_lists_with_update_ui);

			$('body').on('click', '.wcap_save_btn', wcap_save_connector_settings );
			localStorage.removeItem( 'wcap_mc_save' );
		});

		function fetch_lists_with_update_ui() {
			if ($('.wcap_mailchimp_select_list_box').css('display') !== 'none') {
				if ('' === $('.wcap_mailchimp_select_list').val()) {
					$('.wcap_mailchimp_next_step').prepend('<p><?php echo esc_html__( 'Select a list to continue', 'woocommerce-ac' ); ?></p>');
				} else {
					fetch_stores();
				}
				return false;
			}
			if ( '1' == localStorage.getItem( 'wcap_mc_lists' ) ) {
				return;
			}
			disable_fields();
			localStorage.setItem( 'wcap_mc_lists', '1' );
			var ajax_data = {
				'api_key': $('.wcap_mailchimp_api_key').val(),
				'action': 'wcap_get_mailchimp_lists'
			};
			$.post( '<?php echo esc_url( WCAP_ADMIN_AJAX_URL ); ?>', ajax_data, function( result ) {
				enable_fields();
				if (_.has(result, 'status') && (false === result.status || 'failed' === result.status)) {
					$('.wcap_mailchimp_next_step').prepend('<p>' + result.message + '</p>');
					return;
				}

				_.each(result, function (item, key) {
					let selected = (key === '<?php echo esc_html( $selected_list ); ?>') ? 'selected' : '';
					selected = (1 === _.size(result)) ? 'selected' : selected;
					$('.wcap_mailchimp_select_list').append('<option ' + selected + ' value="' + key + '">' + item + '</option>');
				});

				$('.wcap_mailchimp_select_list_box').show();
			});
			return false;
		}

		function fetch_stores() {
			if ( '1' == localStorage.getItem( 'wcap_mc_stores' ) ) {
				return;
			}
			disable_fields();
			localStorage.setItem( 'wcap_mc_stores', '1' )
			var ajax_data = {
				'api_key': $('.wcap_mailchimp_api_key').val(),
				'list_id': $('.wcap_mailchimp_select_list').val(),
				'action': 'wcap_get_mailchimp_stores'
			};
			$.post( '<?php echo esc_url( WCAP_ADMIN_AJAX_URL ); ?>', ajax_data, function( result ) {
				enable_fields();
				if (_.has(result, 'status') && (false === result.status || 'failed' === result.status)) {
					$('.wcap_mailchimp_next_step').prepend('<p>' + result.message + '</p>');
					return;
				}

				_.each(result, function (item, key) {
					let selected = (key === '<?php echo esc_html( $selected_store ); ?>') ? 'selected' : '';
					selected = (1 === _.size(result)) ? 'selected' : selected;
					$('.wcap_mailchimp_select_store').append('<option ' + selected + ' value="' + key + '">' + item + '</option>');
				});

				$('.wcap_mailchimp_select_store_box').show();
				$('.wcap_mailchimp_fetch_lists').hide();
				$('.wcap_mailchimp_main_submit').show();
			});
		}

		function wcap_save_connector_settings() {
			if ( '1' == localStorage.getItem( 'wcap_mc_save' ) ) {
				return;
			}
			localStorage.setItem( 'wcap_mc_save', '1' );

			var settings = {
				'api_key': $('.wcap_mailchimp_api_key').val(),
				'default_list': $('.wcap_mailchimp_select_list').val(),
				'default_store': $('.wcap_mailchimp_select_store').val()
			};
			var ajax_data = {
				'action': 'wcap_save_connector_settings',
				'security': '<?php echo esc_js( wp_create_nonce( "wcap_settings_nonce" ) ); ?>',
				'settings': settings,
				'connector': 'mailchimp'
			};
			$(document.body).addClass('wcap-modal-loading');
			$.post( '<?php echo esc_url( WCAP_ADMIN_AJAX_URL ); ?>', ajax_data, function( response ) {
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
						document.getElementById("wcap_mailchimp_connect_div").style.display = "none";
						document.getElementById("wcap_mailchimp_connected_div").style.display = "block";
					}, 2000);
				} else {
					if ( 'active' == $( '#wcap_connector_status' ).val() ) {
						inactive_count++;
						active_count--;
						$('#wcap_active_count').text( active_count );
						$('#wcap_inactive_count').text( inactive_count );
						$('#wcap_active').attr( 'data-wcap-count', active_count );
						$('#wcap_inactive').attr( 'data-wcap-count', inactive_count );	
						console.log( "ELSE " + active_count + " " + inactive_count );
					}
					$(document.body).addClass('wcap-modal-failure');
					Modal.contents( '<p class="wcap_msg">' + response.message + '</p>'); 
					setTimeout(() => {
						$(document.body).removeClass('wcap-modal-failure');
						// close the modal.
						$(document.body).removeClass('wcap-modal-open'); 
						$('.wcap-modal, .wcap-modal-overlay').remove();	    
						// Update the buttons.
						document.getElementById("wcap_mailchimp_connect_div").style.display = "block";
						document.getElementById("wcap_mailchimp_connected_div").style.display = "none";
					}, 2000);
				}
				localStorage.removeItem( 'wcap_mc_stores' );
            	localStorage.removeItem( 'wcap_mc_lists' );
			});
		}

		function enable_fields() {
			$('.wcap_mailchimp_api_key').removeAttr('disabled');
			$('.wcap_mailchimp_fetch_lists').removeAttr('disabled').text('<?php echo esc_html__( 'Next Step', 'woocommerce-ac'); ?>');
		}

		function disable_fields() {
			$('.wcap_mailchimp_next_step p').remove();
			$('.wcap_mailchimp_api_key').attr('disabled', 'disabled');
			$('.wcap_mailchimp_fetch_lists').attr('disabled', 'disabled').text('<?php echo esc_html__( 'Loading...', 'woocommerce-ac'); ?>');
		}
	});	
</script>
