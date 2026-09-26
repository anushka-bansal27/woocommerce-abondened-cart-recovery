<?php
/**
 * AC insert and update file
 *
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Connectors/Salesforce
 */

$ajax_url         = esc_url( WCAP_ADMIN_AJAX_URL );
$connector_name   = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';//phpcs:ignore
$prefixed_cn_name = '' !== $connector_name && 'wcap' !== substr( $connector_name, 0, 4 ) ? "wcap_$connector_name" : $connector_name;
$saved_data       = Wcap_Connectors_Common::wcap_get_connectors_data( $prefixed_cn_name );
$old_data         = ( isset( $saved_data ) && is_array( $saved_data ) && count( $saved_data ) > 0 ) ? $saved_data : array();
$client_id        = isset( $old_data['client_id'] ) ? $old_data['client_id'] : '';
$client_secret    = isset( $old_data['client_secret'] ) ? $old_data['client_secret'] : '';
$redirect_uri     = isset( $old_data['redirect_uri'] ) ? $old_data['redirect_uri'] : '';
$connector_status = isset( $old_data['status'] ) ? $old_data['status'] : '';
$display_connect  = 'active' === $connector_status ? 'display:block;' : 'display:none;';
?>
<div class='wcap-settings-body'>
	<div class="wcap-form-group featured field-input">
		<label for="client_id"><?php echo esc_html__( 'Enter Consumer Key', 'woocommerce-ac' ); ?></label>
		<div class="field-wrap">
			<div class="wrapper">
				<input type="text" name="client_id" placeholder="<?php echo esc_attr__( 'Consumer Key', 'woocommerce-ac' ); ?>" class="wcap_test form-control wcap_salesforce_crm wcap_salesforce_client_id" required value="<?php echo esc_attr( $client_id ); ?>">
				<div class="warning" id="client_id_warn"><?php echo esc_attr__( 'Please enter a Consumer Key', 'woocommerce-ac' ); ?></div>
			</div>
		</div>
	</div>
	<div class="wcap-form-group featured field-input">
		<label for="client_secret"><?php echo esc_html__( 'Enter Consumer Secret', 'woocommerce-ac' ); ?></label>
		<div class="field-wrap">
			<div class="wrapper">
			<input type="text" name="client_secret" placeholder="<?php echo esc_attr__( 'Consumer Secret', 'woocommerce-ac' ); ?>" class="wcap_test form-control wcap_salesforce_crm wcap_salesforce_secret_key" required value="<?php echo esc_attr( $client_secret ); ?>">
			<div class="warning" id="client_secret_warn"><?php echo esc_attr__( 'Please enter a Consumer Secret', 'woocommerce-ac' ); ?></div>
			</div>
		</div>
	</div>
	<div class="wcap-form-group featured field-input">
		<label for="redirect_uri"><?php echo esc_html__( 'Redirect URI', 'woocommerce-ac' ); ?></label>
		<div class="field-wrap">
			<div class="wrapper">
			<input type="text" name="redirect_uri" placeholder="<?php echo esc_attr__( 'Redirect URI to be saved in the Salesforce App Manager settings.', 'woocommerce-ac' ); ?>" class="wcap_test form-control wcap_salesforce_crm wcap_salesforce_redirect_uri" required value="<?php echo esc_attr( $redirect_uri ); ?>">
			<div class="warning" id="redirect_uri_warn"><?php echo esc_attr__( 'Please enter a Redirect URI', 'woocommerce-ac' ); ?></div>
			</div>
		</div>
	</div>
	<?php
	if ( 'active' !== $connector_status ) {
		?>
	<div class="wcap-form-groups wcap_form_submit wcap_salesforce_next_step">
		<button class="wcap_salesforce_fetch_lists button-primary"><?php echo esc_html__( 'Next Step', 'woocommerce-ac' ); ?></button>
	</div>
	<?php } ?>
	<div class="wcap-form-groups wcap_form_submit wcap_salesforce_main_submit">
		<input type="hidden" name="wcap_connector" value="<?php echo esc_attr__( $this->get_slug() );//phpcs:ignore ?>"/>
		<input type="hidden" name="wcap_connector_status" id="wcap_connector_status" value="<?php echo esc_attr( $connector_status ); ?>"/>
		<?php
		$current_url = admin_url( 'admin.php?page=woocommerce_ac_page&action=emailsettings#/connectors' );

		// Add a query parameter to the current URL.
		$current_url = add_query_arg( 'code', '', $current_url );
		?>
		<a style='float:right; <?php echo esc_attr( ! $display_connect ); ?>' href="<?php echo $current_url;//phpcs:ignore ?>" class="button-primary wcap_connect_btn" name="autoresponderSubmit"><?php echo esc_attr__( 'Connect to Salesforce', 'woocommerce-ac' ); ?></a>
		<a  data-wcap-name="wcap_salesforce_crm" class="button-primary wcap_button_disconnect" style='float:right; <?php echo esc_attr( $display_connect ); ?>' href=""  name="autoresponderSubmit"><?php echo esc_attr__( 'Disconnect', 'woocommerce-ac' ); ?></a>
	</div>
	</div>
<div class="wcap_form_response" style="font-size: 16px;margin-top: 10px;color:green; "></div>

<script>
	jQuery( function( $ ) {
		$(document).ready(function () {
			if ( 'active' !== $('#wcap_connector_status').val() ) {
				$('.wcap_salesforce_main_submit').hide();
				jQuery('#client_id_warn').hide();
				jQuery('#client_secret_warn').hide();
				jQuery('#redirect_uri_warn').hide();
			} else {
				$('.wcap_salesforce_main_submit').show();
				$('.wcap_connect_btn').hide();
				// place an ajax call to fetch the list & store ID.
			}
			$('body').on('click', '.wcap_salesforce_fetch_lists', fetch_lists_with_update_ui);
			localStorage.removeItem( 'wcap_mc_save' );
		});

		function fetch_lists_with_update_ui() {
			var ajax_data = {
				'client_id': $('.wcap_salesforce_client_id').val(),
				'client_secret': $('.wcap_salesforce_secret_key').val(),
				'redirect_uri': $('.wcap_salesforce_redirect_uri').val(),
			};	
			if ( ajax_data.client_id =='' ) {
				jQuery('#client_id_warn').show();
				return;
			}
			jQuery('#client_id_warn').hide();
			if( ajax_data.client_secret =='' ) {
				jQuery('#client_secret_warn').show();
				return;
			}			
			jQuery('#client_secret_warn').hide();
			if( ajax_data.redirect_uri =='' ) {
				jQuery('#redirect_uri_warn').show();
				return;
			}			
			jQuery('#redirect_uri_warn').hide();
			$('.wcap_salesforce_main_submit').show();
			$('.wcap_salesforce_fetch_lists').hide();
			wcap_save_connector_settings();
			return false;
		}

		function wcap_save_connector_settings() {
			if ( '1' == localStorage.getItem( 'wcap_mc_save' ) ) {
				return;
			}
			localStorage.setItem( 'wcap_mc_save', '1' );

			var settings = {
				'client_id': $('.wcap_salesforce_client_id').val(),
				'client_secret': $('.wcap_salesforce_secret_key').val(),
				'redirect_uri': $('.wcap_salesforce_redirect_uri').val(),
			};
			var ajax_data = {
				'action': 'wcap_save_connector_settings',
				'security': '<?php echo esc_js( wp_create_nonce( "wcap_settings_nonce" ) ); ?>',
				'settings': settings,
				'connector': 'salesforce_crm'
			};
			$(document.body).addClass('wcap-modal-loading');
			$.post( '<?php echo esc_url( WCAP_ADMIN_AJAX_URL );//phpcs:ignore ?>', ajax_data, function( response ) {
				$(document.body).removeClass('wcap-modal-loading');
				var inactive_count = $('#wcap_inactive').attr('data-wcap-count');
				var active_count = $('#wcap_active').attr('data-wcap-count');

				if ( response.status === 'success' ) {
					$('.wcap_form_response').text('Data saved successfully');
				} else {
					$('.wcap_form_response').text('Some settings are blanks. Please fill all the fields.');
				}
				localStorage.removeItem( 'wcap_mc_stores' );
				localStorage.removeItem( 'wcap_mc_lists' );
			});
		}
	});	
</script>
<style type="text/css">
	.warning {
		display:none;
		color: red;
	}
</style>
