<?php
/**
 * Settings for the card
 *
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Connectors/MAiljet
 */

$ajax_url            = esc_url( WCAP_ADMIN_AJAX_URL );
$connector_name      = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : ''; // phpcs:ignore
$saved_data          = Wcap_Connectors_Common::wcap_get_connectors_data( $connector_name );
$old_data            = ( is_array( $saved_data ) && count( $saved_data ) > 0 ) ? $saved_data : array();
$smtp_host           = isset( $old_data['smtp_host'] ) ? $old_data['smtp_host'] : '';
$smtp_encryption     = isset( $old_data['smtp_encryption'] ) ? $old_data['smtp_encryption'] : '';
$smtp_port           = isset( $old_data['smtp_port'] ) ? $old_data['smtp_port'] : '';
$smtp_autotls        = isset( $old_data['smtp_autotls'] ) ? $old_data['smtp_autotls'] : 'no';
$smtp_authentication = isset( $old_data['smtp_authentication'] ) ? $old_data['smtp_authentication'] : 'no';
$smtp_username       = isset( $old_data['smtp_username'] ) ? $old_data['smtp_username'] : '';
$smtp_password       = isset( $old_data['smtp_password'] ) ? Wcap_Custom_SMTP::decrypt( $old_data['smtp_password'] ) : '';
$from_email          = isset( $old_data['smtp_from_email'] ) ? $old_data['smtp_from_email'] : '';
$connector_status    = isset( $old_data['status'] ) ? $old_data['status'] : '';
?>
<div class='wcap-settings-body'>
<div id="host_port_cover">
<div class="wcap-form-group featured field-input wcap_custom-smtp-host">
		<label for="automation-name"><?php echo esc_html__( 'SMTP Host', 'woocommerce-ac' ); ?></label>
		<div class="field-wrap">
			<div class="wrapper">
				<input type="text" name="smtp_host" placeholder="<?php echo esc_attr__( 'Enter SMTP Host', 'woocommerce-ac' ); ?>" class="wcap_test form-control wcap_custom-smtpapi_user" required value="<?php echo esc_attr__( $smtp_host );//phpcs:ignore ?>">
				<div class="warning" id="smtp_host_warn">Please enter SMTP Host</div>
			</div>
		</div>
	</div>
	<div class="wcap-form-group featured field-input wcap_custom-smtp-port">
		<label for="automation-name"><?php echo esc_html__( 'SMTP Port', 'woocommerce-ac' ); ?></label>
		<div class="field-wrap">
			<div class="wrapper">
				<input type="number" name="smtp_port" placeholder="<?php echo esc_attr__( 'SMTP Port', 'woocommerce-ac' ); ?>" class="wcap_test form-control wcap_custom-smtpsmtp_port" required value="<?php echo esc_attr__( $smtp_port ); // phpcs:ignore ?>">
				<div class="warning" id="smtp_port_warn">Please enter SMTP Port</div>
			</div>
		</div>
	</div>
</div>

	<div class="wcap-form-group featured field-input wcap_custom-smtp-encryption_cover">
		<label for="smtp_encryption"><?php echo esc_html__( 'SMTP Encryption', 'woocommerce-ac' ); ?></label>
		<div class="field-wrap encryption_cover">
			<div class="rb-flx-style">
				<input type="radio" name="smtp_encryption"  class="wcap_test form-control wcap_custom-smtpapi_key" required value="none" <?php if ( $smtp_encryption === 'none' ) { echo 'checked="checked"'; } // phpcs:ignore ?> >
				<label>None</label>
			</div>
			<div class="rb-flx-style">
				<input type="radio" name="smtp_encryption"  class="wcap_test form-control wcap_custom-smtp-encryption" required value="ssl" <?php if ( $smtp_encryption === 'ssl' ) { echo 'checked="checked"'; } // phpcs:ignore ?> >
				<label>SSL</label>
			</div>
			<div class="rb-flx-style">
				<input type="radio" name="smtp_encryption"  class="wcap_test form-control wcap_custom-smtp-encryption" required value="tls" <?php if ( $smtp_encryption === 'tls' ) { echo 'checked="checked"'; } // phpcs:ignore ?> >
				<label>TLS</label>
			</div>
			<div class="warning" id="smtp_encryption_warn">Please chose an encryption method</div>			
		</div>
	</div>	
	
		<div class="wcap-form-group featured field-input smtp_autotls_cover">
			<label for="smtp_authentication"><?php echo esc_html__( 'SMTP AutoTLS', 'woocommerce-ac' ); ?></label>
			<button type="button" id="smtp_autotls-switch" data-target="smtp_autotls" class="wcap-switch " state="<?php echo esc_html( $smtp_autotls ); ?>">on</button>
			<div class="field-wrap switch_cover">
				<div class="wrapper">
					<input type="radio" name="smtp_autotls"  class="wcap_test form-control wcap_custom-smtp-autotls" required value="yes" <?php if ( $smtp_autotls === 'yes' ) { echo 'checked="checked"'; } // phpcs:ignore ?> >
					<label>Yes</label>
				</div>
				<div class="wrapper">
					<input type="radio" name="smtp_autotls"  class="wcap_test form-control wcap_custom-smtpsmtp-autotls" required value="no" <?php if ( $smtp_autotls === 'no' ) { echo 'checked="checked"'; } // phpcs:ignore ?> >
					<label>No</label>
				</div>
			</div>
		</div>
		<div class="wcap-form-group featured field-input">
			<label for="smtp_authentication"><?php echo esc_html__( 'SMTP Authentication', 'woocommerce-ac' ); ?></label>
			<button type="button" id="authentication-switch" data-target="smtp_authentication" class="wcap-switch " state="<?php echo esc_html( $smtp_authentication ); ?>">on</button>
			<div class="field-wrap switch_cover">
				<div class="wrapper">
					<input type="radio" name="smtp_authentication"  class="wcap_test form-control wcap_custom-smtp-authentication" required value="yes" <?php if ( $smtp_authentication === 'yes' ) { echo 'checked="checked"'; } // phpcs:ignore ?> >
					<label>Yes</label>
				</div>
				<div class="wrapper">
					<input type="radio" name="smtp_authentication"  class="wcap_test form-control wcap_custom-smtpsmtp-authentication" required value="no" <?php if ( $smtp_authentication === 'no' ) { echo 'checked="checked"'; } // phpcs:ignore ?> >
					<label>No</label>
				</div>
			</div>
		</div>
	
	<div id="smtp_username_cover" class="wcap-form-group featured field-input  wcap_custom-smtp-username">
		<label for="smtp_username"><?php echo esc_html__( 'SMTP Username', 'woocommerce-ac' ); ?></label>
		<div class="field-wrap">
			<div class="wrapper">
				<input type="text" name="smtp_username" placeholder="<?php echo esc_attr__( 'Enter SMTP Username', 'woocommerce-ac' ); ?>" class="wcap_test form-control wcap_custom-smtp-user_name" required value="<?php echo esc_attr__( $smtp_username );//phpcs:ignore ?>">
				<div class="warning" id="smtp_username_warn">Please enter SMTP usernme</div>	
			</div>
		</div>
	</div>
	<div id="smtp_password_cover" class="wcap-form-group featured field-input">
		<label for="smtp_password"><?php echo esc_html__( 'SMTP Password', 'woocommerce-ac' ); ?></label>
		<div class="field-wrap">
			<div class="wrapper">
				<input type="password" name="smtp_password" placeholder="<?php echo esc_attr__( 'Enter SMTP Password', 'woocommerce-ac' ); ?>" class="wcap_test form-control wcap_custom-smtp-user_name" required value="<?php echo esc_attr__( $smtp_password );//phpcs:ignore ?>">
				<div class="warning" id="smtp_password_warn">Please enter password</div>
			</div>
		</div>
	</div>
	<div id="smtp_from_email_cover" class="wcap-form-group featured field-input">
		<label for="smtp_from_email"><?php echo esc_html__( 'From Email', 'woocommerce-ac' ); ?></label>
		<div class="field-wrap">
			<div class="wrapper">
				<input type="text" name="smtp_from_email" placeholder="<?php echo esc_attr__( 'Enter From Email', 'woocommerce-ac' ); ?>" class="wcap_test form-control wcap_custom-smtp-user_name" required value="<?php echo esc_attr__( $from_email );//phpcs:ignore ?>">
				<div>The email address must be one of the sender's addresses verified in Postmark.</div>
				<div class="warning" id="smtp_email_warn">Please enter from email address</div>
			</div>
		</div>
	</div>
	<div class="wcap-form-group featured field-input test_email_cover">
		<label for="test_email"><?php echo esc_html__( 'Send a test email to', 'woocommerce-ac' ); ?></label>
		<div class="field-wrap" style="width:60%">
			<div class="wrapper">
				<input type="email" name="test_email" placeholder="<?php echo esc_attr__( 'Enter email address', 'woocommerce-ac' ); ?>" class="wcap_test form-control wcap_custom-smtp-test_email" required value="">
				<input type="button" id="test_button" onclick='debug_smtp();' class="button" value="Send Email"/>
				<div id="valid_email"></div>
				<img class="ajax_img" src="<?php echo esc_url( WCAP_PLUGIN_URL ) . '/assets/images/ajax-loader.gif';?>" />
			</div>
			<div id="send_result"> </div>
			<div id="debug_result"> </div>
		</div>
	</div>
	<div class="wcap-form-groups wcap_form_submit wcap_custom-smtpmain_submit">
		<input type="hidden" name="wcap_connector" value="<?php echo esc_attr__( $this->get_slug() ); //phpcs:ignore ?>"/>
		<input type="hidden" name="wcap_connector_status" id="wcap_connector_status" value="<?php echo esc_attr( $connector_status ); ?>"/>
		<input type="submit" class="button-primary wcap_save_btn" name="autoresponderSubmit" value="<?php echo esc_attr__( 'Save', 'woocommerce-ac' ); ?>">
		</div>
	</div>
<div class="wcap_form_response" style="text-align: center;font-size: 20px;margin-top: 10px; font-weight:600; "></div>

<script>
	jQuery( function( $ ) {
		$(document).ready(function () {
			jQuery('#smtp_from_email_cover').hide();	

			jQuery('.wcap-switch').on( 'click', function(){
				state = jQuery(this).attr('state');
				new_state = state == 'yes' ? 'no' : 'yes';	

				jQuery(this).attr( 'state', new_state );
				target = jQuery(this).attr('data-target');

				jQuery( 'input[name=' + target + ']' ).each( function(){
				value = jQuery(this).attr('value');
				value === new_state ? jQuery(this).prop('checked', true) : jQuery(this).prop('checked', false);	

				})

				divid= jQuery(this).attr('id');				
				if ( divid == 'authentication-switch' ) {
					smtp_authentication_hs( new_state );
				}
			})

			jQuery("input[name='smtp_encryption']").on( 'click', function(){	
				value = jQuery(this).attr('value');
				smtp_auto_cover_hs( value );	
			})
			smtp_auto_cover_hs( '<?php echo esc_html( $smtp_encryption ); ?>' );
			smtp_authentication_hs( '<?php echo esc_html( $smtp_authentication ); ?>' );

			if ( window.custom_smtp_settings_loaded !=='yes' ) {
				$('body').on('click', '.wcap_save_btn', wcap_save_connector_settings );
			}
			smtpHost = jQuery("input[name='smtp_host']").val();
			if ( smtpHost.includes('postmarkapp') ) {
				jQuery('#smtp_from_email_cover').show();
			}
			$('input[name="smtp_host"]').on( 'keyup', function() {
        		const smtpHost = $( this ).val().trim();
		        if ( smtpHost.includes('postmarkapp') ) {
		            jQuery('#smtp_from_email_cover').show();
		        } else {
		            jQuery('#smtp_from_email_cover').hide();
		        }
		    });
			window.custom_smtp_settings_loaded = 'yes';	

		});

		function wcap_save_connector_settings() {
			var settings = get_settings();
			var ajax_data = {
				'action': 'wcap_save_connector_settings',
				'security': '<?php echo esc_js( wp_create_nonce( "wcap_settings_nonce" ) ); ?>',
				'settings': settings,
				'connector': 'custom_smtp'
			};
			var show_error = false;		
			if ( settings.smtp_host =='' ) {
				jQuery('#smtp_host_warn').show();	
				show_error = true;			
			}
			else {
				jQuery('#smtp_host_warn').hide();
			}
			if ( settings.smtp_port =='' ) {
				jQuery('#smtp_port_warn').show();
				show_error = true;
			}
			else {
				jQuery('#smtp_port_warn').hide();
			}
			if ( settings.smtp_encryption =='' || typeof( settings.smtp_encryption )=='undefined' ) {
				jQuery('#smtp_encryption_warn').show();
				show_error = true;
			}
			else {
				jQuery('#smtp_encryption_warn').hide();
			}

			if ( settings.smtp_port.includes('postmarkapp') ) {

				if ( settings.smtp_from_email =='' ) {
					jQuery('#smtp_email_warn').show();	
					show_error = true;			
				}
				else {
					jQuery('#smtp_email_warn').hide();
				}
			}
			

			if ( settings.smtp_authentication =='yes' )	{
				if( settings.smtp_username =='' ) {
					jQuery('#smtp_username_warn').show();
					show_error = true;
				}
				else{
					jQuery('#smtp_username_warn').hide();
				}
				if( settings.smtp_password =='' ) {
					jQuery('#smtp_password_warn').show();
					show_error = true;
				}
				else{
					jQuery('#smtp_password_warn').hide();
				}
			}
			console.log('erros is'+ show_error );
			if ( show_error == true ) {
				return false;
			}

			jQuery('.warning').hide();

			$(document.body).addClass('wcap-modal-loading');
			$.post( '<?php echo esc_url( WCAP_ADMIN_AJAX_URL ); //phpcs:ignore ?>', ajax_data, function( response ) {
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
						document.getElementById("wcap_custom_smtp_connect_div").style.display = "none";
						document.getElementById("wcap_custom_smtp_connected_div").style.display = "block";
						
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
		}
	});
	function smtp_auto_cover_hs( value ) {
			value !== 'tls' ? jQuery( '.smtp_autotls_cover' ).show() : jQuery( '.smtp_autotls_cover' ).hide();
	}
	function smtp_authentication_hs( value ){
		
		if ( value == 'yes' ) {
				jQuery('#smtp_username_cover').show();
				jQuery('#smtp_password_cover').show();
			}
			else {
				jQuery('#smtp_username_cover').hide();
				jQuery('#smtp_password_cover').hide();
			}
	}

	function get_settings() {
		return {
			'smtp_host': jQuery("input[name='smtp_host']").val(),
			'smtp_encryption': jQuery("input[name='smtp_encryption']:checked").val(),
			'smtp_port': jQuery("input[name='smtp_port']").val(),
			'smtp_autotls': jQuery("input[name='smtp_autotls']:checked").val(),
			'smtp_authentication': jQuery("input[name='smtp_authentication']:checked").val(),
			'smtp_username': jQuery("input[name='smtp_username']").val(),
			'smtp_password': jQuery("input[name='smtp_password']").val(),
			'smtp_from_email': jQuery("input[name='smtp_from_email']").val()

		};
	}

	function debug_smtp() {
		email = jQuery( "input[name='test_email']" ).val();
		if (! validateEmail( email ) )
		{
			jQuery('#valid_email').html('<?php echo esc_html__( 'Please enter a valid email-id' );//phpcs:ignore ?>');
			return;	
		}
		else
		{
			jQuery('#valid_email').html('');
		}
		jQuery('.ajax_img').show();
		jQuery( '#test_button' ).attr( 'disabled', 'disabled' );
		jQuery( '#send_result' ).hide();
		jQuery( '#debug_result' ).hide();
		settings            = get_settings();
		settings.test_email = email;
		ajax_data = {
			'action': 'wcap_debug_smtp_settings',
			'settings': settings
		};

		jQuery.post( '<?php echo esc_url( WCAP_ADMIN_AJAX_URL ); //phpcs:ignore ?>', ajax_data, function( response ) {
			if ( response.result ) {
				jQuery( '#send_result' ).html( response.result );
			} else {
				jQuery( '#send_result' ).html( response );
			}
			jQuery( '#send_result' ).show();
			jQuery( '#debug_result' ).html( response.debug );
			if( response.result !== '<?php echo esc_html__( 'Email sent' );//phpcs:ignore ?>' ) {
				jQuery( '#debug_result' ).show();
			}
			jQuery('.ajax_img').hide();
			jQuery( '#test_button' ).removeAttr( 'disabled' );
		})
	}
	if( typeof( window.custom_smtp_settings_loaded ) =='undefined' ) {
		var custom_smtp_settings_loaded = 'no';
	}
</script>
<style type="text/css">
input.form-control[type='radio'] {
	width: auto;
}
.encryption_cover .wrapper, .authentication_cover .wrapper {
	float:left;
}
.wcap_custom-smtp-encryption_cover, .smtp_autotls_cover, .wcap_custom-smtp-username {
	clear:both;
}
.encryption_cover .wrapper label ,  .authentication_cover .wrapper label {
	padding:0px 20px 0px 0px;
}
input.wcap_custom-smtpsmtp_port {
    width: 85px;
}
.smtp_autotls_cover, .switch_cover, .ajax_img {
	display:none;
}
.wcap-switch {
	cursor: pointer;
	text-indent: -999em;
	display: block;
	width: 38px !important;
	height: 22px;
	border-radius: 30px;
	border: none;
	position: relative;
	box-sizing: border-box;
	-webkit-transition: all .3s ease;
	transition: all .3s ease;
	box-shadow: inset 0 0 0 0 transparent;
	margin: 10px 0px 10px 0px;
	padding:0px;
}
.wcap-switch::before {
	border-radius: 50%;
	background: #fff;
	content: '';
	position: absolute;
	display: block;
	width: 18px;
	height: 18px;
	top: 2px;
	left: 2px;
	-webkit-transition: all .15s ease;
	transition: all .15s ease;
	box-shadow: 0 1px 3px rgba(0,0,0,.3);
}
.wcap-switch[state="no"] {
	background: #ccc;
}
.wcap-switch[state="yes"] {
	box-shadow: inset 0 0 0 11px green;
}
.wcap-switch[state="yes"]::before {
	-webkit-transform: translateX(16px);
	transform: translateX(16px);
}
#debug_result {
	height: 100px;
	overflow-y: auto;
	display: none;
}

.wcap_custom-smtp-host {
}
.wcap_custom-smtp-port {	
	minwidth:80px
}
.wcap_form_submit {
	margin-top: 10px;
	clear: both;
}
input.wcap_custom-smtp-test_email {
	width: 62%;
	float: left;
}
input#test_button {
	float: left;
	margin-left: 2%;
	clear: right;
	margin-top: 10px;
}
input.button-primary.wcap_save_btn {
	clear: both;
}
div#valid_email {
	clear: both;
}
div#smtp_toggle_cover .wcap-form-group.featured {

}
.test_email_cover {
	clear: both;
}
.wcap-modal__body {
	height: 500px;
	max-height: 90%;
}
.warning {
	display: none;
	color: red;
	font-size: 10px;
	line-height: 12px;
}
#smtp_toggle_cover{
	clear:both;
}
input.form-control[type="text"], input.form-control[type="email"], input.form-control[type="password"] {
    width: 200px;
}
.field-wrap.encryption_cover  {
    width: 50%;
}
.wcap_custom-smtp-encryption_cover .rb-flx-style {
    float: left;
    width: calc(33.33% - 20px);
}


</style>
