<?php
$ajax_url         = esc_url( WCAP_ADMIN_AJAX_URL );
$connector_name   = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';//phpcs:ignore
$prefixed_cn_name = '' !== $connector_name && 'wcap' !== substr( $connector_name, 0, 4 ) ? "wcap_$connector_name" : $connector_name;
$saved_data       = Wcap_Connectors_Common::wcap_get_connectors_data( $prefixed_cn_name );
$old_data         = ( isset( $saved_data ) && is_array( $saved_data ) && count( $saved_data ) > 0 ) ? $saved_data : array();
$api_token        = isset( $old_data['api_token'] ) ? $old_data['api_token'] : '';
$account_id       = isset( $old_data['account_id'] ) ? $old_data['account_id'] : '';
$workflow_id      = isset( $old_data['workflow_id'] ) ? $old_data['workflow_id'] : '';
$connector_status = isset( $old_data['status'] ) ? $old_data['status'] : '';
?>
<div class="wcap-form-group featured field-input">
    <label for="automation-name"><?php echo esc_html__( 'Enter API Token', 'woocommerce-ac' ); ?></label>
    <div class="field-wrap">
        <div class="wrapper">
            <input type="text" name="api_token" placeholder="<?php echo esc_attr__( 'Enter API Token', 'woocommerce-ac' ); ?>" class="form-control wcap-drip-api-token" required value="<?php echo esc_attr__( $api_token );//phpcs:ignore ?>">
        </div>
    </div>
</div>
<div class="wcap-form-group featured field-input">
	<label for="automation-name"><?php echo esc_html__( 'Enter Account ID', 'woocommerce-ac' ); ?></label>
    <div class="field-wrap">
        <div class="wrapper">
            <input type="text" name="account_id" placeholder="<?php echo esc_attr__( 'Enter Account ID', 'woocommerce-ac' ); ?>" class="form-control wcap-drip-account-id" required value="<?php echo esc_attr__( $account_id );//phpcs:ignore ?>">
        </div>
    </div>
</div>
	<div class="wcap-form-group featured field-input wcap_drip_select_workflow_box">
		<label for="automation-name"><?php echo esc_html__( 'Select Workflow', 'woocommerce-ac' ); ?></label>
		<div class="field-wrap">
			<div class="wrapper">
				<select name="default_list" class="wcap_test wcap_drip_select_workflow form-control">
					<option value=""><?php echo esc_html__( 'Choose a workflow', 'woocommerce-ac' ); ?></option>
				</select>
			</div>
		</div>
	</div>
	<?php
	if ( 'active' !== $connector_status ) {
		?>
		<div class="wcap-form-groups wcap_form_submit wcap_drip_next_step">
			<button class="wcap_drip_fetch_workflows button-primary"><?php echo esc_html__( 'Next Step', 'woocommerce-ac' ); ?></button>
		</div>
		<?php
	}
	?>

<div class="wcap-form-groups wcap_form_main_submit">
    <input type="hidden" name="wcap_connector" value="<?php echo esc_attr( "wcap_$connector_name" ); ?>"/>
    <input type="hidden" name="wcap_connector_status" id="wcap_connector_status" value="<?php echo esc_attr( $connector_status ); ?>"/>
	<?php
	if ( 'active' === $connector_status ) {
		?>
        <input type="submit" class="wcap_update_btn_style wcap_drip_save_btn button-primary" name="autoresponderSubmit" value="<?php echo esc_attr__( 'Update', 'woocommerce-ac' ); ?>">
	<?php } else { ?>
        <input type="submit" class="wcap_drip_save_btn button-primary" name="autoresponderSubmit" value="<?php echo esc_attr__( 'Save', 'woocommerce-ac' ); ?>">
	<?php } ?>
</div>
<div class="wcap_form_response" style="text-align: center;font-size: 15px;margin-top: 10px;"></div>

<script>
	jQuery( function( $ ) {
		$(document).ready(function () {
			if ( 'active' !== $('#wcap_connector_status').val() ) {
				$('.wcap_form_main_submit').hide();
				$('.wcap_drip_select_workflow_box').hide();

				if (!_.isEmpty( $('.wcap-drip-api-token').val() ) && !_.isEmpty( $('.wcap-drip-account-id').val() ) ) {
					fetch_workflows_with_update_ui();
				}
			} else {
				// place an ajax call to fetch the workflow ID.
				var data = {
					'action': 'wcap_get_existing_settings',
					'name': 'wcap_drip'
				};
				$(document.body).addClass('wcap-modal-loading');
				$.post( '<?php echo esc_url( WCAP_ADMIN_AJAX_URL ); ?>', data, function( result ) {

					if ( result.status === 'success' ) {
						for (const [key, value] of Object.entries( result.workflows )) {
							let selected = (`${key}` === '<?php echo esc_html( $workflow_id ); ?>') ? 'selected' : '';
							$('.wcap_drip_select_workflow').append('<option ' + selected + ' value="' + `${key}` + '">' + `${value}` + '</option>');
						}
					} else {
						Modal.contents( '<p class="wcap_msg">' + response.message + '</p>');
					}
					$(document.body).removeClass('wcap-modal-loading');
				});
			}
			$('body').on('click', '.wcap_drip_fetch_workflows', fetch_workflows_with_update_ui);
			$('body').on('click', '.wcap_drip_save_btn', wcap_drip_save_connector_settings );
			localStorage.removeItem( 'wcap_dp_save' );
		});

		function fetch_workflows_with_update_ui() {

			var api_token = $('.wcap-drip-api-token').val();
			var account_id = $('.wcap-drip-account-id').val();

			if ( !_.isEmpty( api_token ) && !_.isEmpty( account_id ) ) {
				if ( '1' == localStorage.getItem( 'wcap_dp_workflows' ) ) {
					return;
				}
				disable_fields();
				localStorage.setItem( 'wcap_dp_workflows', '1' );
				var ajax_data = {
					'api_token': api_token,
					'account_id': account_id,
					'action': 'wcap_get_drip_workflows'
				};
				$.post( '<?php echo esc_url( WCAP_ADMIN_AJAX_URL ); ?>', ajax_data, function( result ) {
					enable_fields();
					if (_.has(result, 'status') && (false === result.status || 'failed' === result.status)) {
						$('.wcap_drip_next_step').prepend('<p>' + result.message + '</p>');
						return;
					}

					_.each(result, function (item, key) {
						let selected = (key === '<?php echo esc_html( $workflow_id ); ?>') ? 'selected' : '';
						selected = (1 === _.size(result)) ? 'selected' : selected;
						$('.wcap_drip_select_workflow').append('<option ' + selected + ' value="' + key + '">' + item + '</option>');
					});

					$( '.wcap_drip_select_workflow_box' ).show();
					$( '.wcap_drip_next_step' ).hide();
					$( '.wcap_form_main_submit' ).show();
				});
				return false;
			} else {
				$('.wcap_drip_next_step').prepend('<p>Please enter the API Token & Account ID</p>' ); // + <?php // echo esc_html__( 'Please enter the API Key and Account ID', 'woocommerce-ac' ); ?> + '</p>' );
			}

		}

		function wcap_drip_save_connector_settings() {

			if ( '1' == localStorage.getItem( 'wcap_dp_save' ) ) {
				return;
			}
			localStorage.setItem( 'wcap_dp_save', '1' );

			var settings = {
				'api_token': $('.wcap-drip-api-token').val(),
				'account_id': $('.wcap-drip-account-id').val(),
				'workflow_id': $('.wcap_drip_select_workflow').val(),
			};
			var ajax_data = {
				'action': 'wcap_save_connector_settings',
				'security': '<?php echo esc_js( wp_create_nonce( "wcap_settings_nonce" ) ); ?>',
				'settings': settings,
				'connector': 'drip'
			};
			$(document.body).addClass('wcap-modal-loading');
			$.post( '<?php echo esc_url( WCAP_ADMIN_AJAX_URL ); ?>', ajax_data, function( response ) {
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
						document.getElementById("wcap_drip_connect_div").style.display = "none";
						document.getElementById("wcap_drip_connected_div").style.display = "block";
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
			});
			localStorage.removeItem( 'wcap_dp_workflows' );
		}

		function enable_fields() {
			$('.wcap-drip-api-token').removeAttr('disabled');
			$('.wcap-drip-account-id').removeAttr('disabled');
			$('.wcap_drip_fetch_workflows').removeAttr('disabled').text('<?php echo esc_html__( 'Next Step', 'woocommerce-ac'); ?>');
		}

		function disable_fields() {
			$('.wcap_drip_next_step p').remove();
			$('.wcap-drip-api-token').attr('disabled', 'disabled');
			$('.wcap-drip-account-id').attr('disabled', 'disabled');
			$('.wcap_drip_fetch_workflows').attr('disabled', 'disabled').text('<?php echo esc_html__( 'Loading...', 'woocommerce-ac'); ?>');
		}

	});
</script>