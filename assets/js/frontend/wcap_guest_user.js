jQuery( function( $ ) {

    if( localStorage.wcap_hidden_email_id !== 'undefined' && localStorage.wcap_hidden_email_id && !$( '#billing_email' ).val() ){
        $( '#billing_email' ).val( localStorage.wcap_hidden_email_id );
        $('input[name=billing_email]').val( localStorage.wcap_hidden_email_id );
    }
    if ( localStorage.wcap_atc_phone_number !== 'undefined' && localStorage.wcap_atc_phone_number && !$( '#billing_phone' ).val() ) {
        $( '#billing_phone' ).val( localStorage.wcap_atc_phone_number );
    }

    var wcap_checkout_fields = {
            billing_first_name: '',
            billing_last_name : '',
            billing_phone     : '',
            billing_email     : ''
        },
        timer                = 0,
        wcap_record_added    = false;

    var gdpr_consent = true;
    var gdpr_email_consent = true;
    var display_consent_box = '';
    var wcap_sms_consent_box_val = '';
    var wcap_email_consent_box_val = '';
    var wcap_sms_consent = false;
    var wcap_email_consent = false;
    var wcap_whatsapp_consent_box_val = '';
    var wcap_whatsapp_consent = false;
    var display_wa_consent_box = '';

    var email_field = 'billing_email';
    if ( !$( 'input#billing_email' ).length && $('input#shipping_email').length ) {
        email_field = 'shipping_email';
    }

    var phone_field = 'billing_phone';
    if ( !$( 'input#billing_phone' ).length && $('input#shipping_phone').length ) {
        phone_field = 'shipping_phone';
    }
    // This has been added for a custom field for a client. The billing_phone field is hidden. The class stated below is the one which is populated with the number.
    var custom_field = wcap_capture_guest_user_params.custom_fields;
    if ( $( 'input.phone-without-phone-code' ).length > 0 ) {
        custom_field += ', input.phone-without-phone-code';
    }
    wcap_email_consent = localStorage.getItem( 'wcap_email_consent' );
    if ( 'opt-in' === wcap_capture_guest_user_params.selected_cart_tracking ) {
        gdpr_email_consent = 'true' === wcap_email_consent;
    }

    $( document ).ready( function() {
        $('input#' + email_field + ', input#' + phone_field + ', input#billing_first_name, input#billing_last_name' + custom_field ).trigger( 'load' );
    });
    $( 'input#' + email_field + ', input#' + phone_field + ', input#billing_first_name, input#billing_last_name' + custom_field ).on( 'load', function() {
        wcap_store_gueat_cart_timestamp(this);
    } );
    $( 'input#' + email_field + ', input#' + phone_field + ', input#billing_first_name, input#billing_last_name' + custom_field ).on( 'change', function() {
        wcap_store_gueat_cart_timestamp(this);
    } );
    $( 'input#shipping_first_name, input#shipping_last_name, input#shipping_postcode' ).on( 'change', function() {
        wcap_store_gueat_cart_timestamp(this);
    });


    function wcap_store_gueat_cart_timestamp( wcap_this ) {

        if ( wcap_this.id && wcap_this.id !== email_field ) {
            timer = 3000;
        }

        setTimeout( function(){
            var $wcap_is_valid_field_value = $(wcap_this).closest(".form-row"),
                wcap_validated             = true,
                wcap_validate_required     = $wcap_is_valid_field_value.is( '.validate-required' ),
                wcap_validate_email        = $wcap_is_valid_field_value.is( '.validate-email' ),
                $wcap_this                 = $( wcap_this );

            if ( wcap_validate_email ) {
                if ( $wcap_this.val() ) {
                    var pattern = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i);

                    if ( ! pattern.test( $wcap_this.val()  ) ) {
                        wcap_validated = false;
                    }
                }
            }

            if ( wcap_validate_required ) {
                if ( $wcap_this.val() === '' ) {
                    wcap_validated = false;
                }
            }

            if ( wcap_this.id.includes('shipping_postcode') ) {
                wcap_validated    = true;
                wcap_record_added = false;
                wcap_checkout_fields.billing_first_name = '';
                wcap_checkout_fields.billing_last_name  = '';
                wcap_checkout_fields.billing_phone      = '';
                wcap_checkout_fields.billing_email      = '';
            }

            var message_data = wcap_capture_guest_user_params._show_gdpr_message ? wcap_capture_guest_user_params._show_gdpr_message : true;
            if ( wcap_validated &&
                 ( wcap_checkout_fields.billing_first_name !== $( '#billing_first_name' ).val() ||
                   wcap_checkout_fields.billing_last_name  !== $( '#billing_last_name' ).val() || 
                   wcap_checkout_fields.billing_phone      !== $( '#' + phone_field ).val() || 
                   wcap_checkout_fields.billing_email      !== $( '#' + email_field ).val() ||
                   '' !== custom_field ) && 
                   ( gdpr_consent && message_data ) && ( gdpr_email_consent ) ) {

                var data = {
                    billing_first_name  : $( '#billing_first_name' ).val(),
                    billing_last_name   : $( '#billing_last_name' ).val(),
                    billing_company     : $( '#billing_company' ).val(),
                    billing_address_1   : $( '#billing_address_1' ).val(),
                    billing_address_2   : $( '#billing_address_2' ).val(),
                    billing_city        : $( '#billing_city' ).val(),
                    billing_state       : $( '#billing_state' ).val(),
                    billing_postcode    : $( '#billing_postcode' ).val(),
                    billing_country     : $( '#billing_country' ).val(),
                    billing_phone       : $( '#' + phone_field ).val(),
                    billing_email       : $( '#' + email_field ).val(),
                    order_notes         : $( '#order_comments' ).val(),
                    shipping_first_name : $( '#shipping_first_name' ).val(),
                    shipping_last_name  : $( '#shipping_last_name' ).val(),
                    shipping_company    : $( '#shipping_company' ).val(),
                    shipping_address_1  : $( '#shipping_address_1' ).val(),
                    shipping_address_2  : $( '#shipping_address_2' ).val(),
                    shipping_city       : $( '#shipping_city' ).val(),
                    shipping_state      : $( '#shipping_state' ).val(),
                    shipping_postcode   : $( '#shipping_postcode' ).val(),
                    shipping_country    : $( '#shipping_country' ).val(),
                    ship_to_billing     : $( '#ship-to-different-address-checkbox' ).val(),
                    action              : 'wcap_save_guest_data'
                };

                if ( localStorage.wcap_abandoned_id ) {
                    data.wcap_abandoned_id = localStorage.wcap_abandoned_id;
                }

                if ( localStorage.wcap_atc_user_action && localStorage.wcap_atc_user_action === 'yes' ) {
                    wcap_record_added = true;
                }

                data.wcap_record_added = wcap_record_added;

                wcap_checkout_fields.billing_first_name = data.billing_first_name;
                wcap_checkout_fields.billing_last_name  = data.billing_last_name;
                wcap_checkout_fields.billing_phone      = data.billing_phone;
                wcap_checkout_fields.billing_email      = data.billing_email;

                $.post( wcap_capture_guest_user_params.ajax_url, data, function( response ) {

                    wcap_record_added = true;
                } );
            }
        }, timer );
    }


    $( document ).ready( function() {

	    if ( wcap_capture_guest_user_params._show_gdpr_message && ! $( "#wcap_gdpr_message_block" ).length && gdpr_consent && 'opt-in' !== wcap_capture_guest_user_params.selected_cart_tracking ) {
	        $("#" + email_field ).after("<span id='wcap_gdpr_message_block'> <span style='font-size: small'> "+ wcap_capture_guest_user_params._gdpr_message +" <a style='cursor: pointer' id='wcap_gdpr_no_thanks'> "+ wcap_capture_guest_user_params._gdpr_nothanks_msg +" </a></span></span>");
	    }

        if ( 'true' == localStorage.getItem( 'wcap_email_consent' ) ) {
            wcap_email_consent_box_val = 'checked';
        }
        display_email_consent_box = "<input type='checkbox' id='wcap_email_consent' " + wcap_email_consent_box_val + "/>";

        if ( wcap_capture_guest_user_params._show_gdpr_message && ! $( "#wcap_gdpr_message_block" ).length && gdpr_consent && 'opt-in' === wcap_capture_guest_user_params.selected_cart_tracking ) {
             $("#" + email_field ).after(display_email_consent_box + "<span id='wcap_sms_consent_block'> <span style='font-size: small'> "+ wcap_capture_guest_user_params._gdpr_optin_msg +" </span></span>");
        }

        if ( 'true' == localStorage.getItem( 'wcap_sms_consent' ) ) {
            wcap_sms_consent_box_val = 'checked';
        }

        if ( 'true' == localStorage.getItem( 'wcap_whatsapp_consent' ) ) {
            wcap_whatsapp_consent_box_val = 'checked';
        }

        if ( wcap_capture_guest_user_params._display_consent_box ) {
            display_consent_box = "<input type='checkbox' id='wcap_sms_consent' " + wcap_sms_consent_box_val + "/>";
        }

        if ( wcap_capture_guest_user_params._display_whatsapp_consent_box ) {
            display_wa_consent_box = "<input type='checkbox' id='wcap_whatsapp_consent' " + wcap_whatsapp_consent_box_val + "/>";
        }
        
        if ( undefined !== wcap_capture_guest_user_params._sms_consent_msg && '' !== wcap_capture_guest_user_params._sms_consent_msg ) {
            $("#billing_phone" ).after(display_consent_box + "<span id='wcap_sms_consent_block'> <span style='font-size: small'> "+ wcap_capture_guest_user_params._sms_consent_msg +" </span></span>");
        }

        if ( undefined !== wcap_capture_guest_user_params._whatsapp_consent_msg && '' !== wcap_capture_guest_user_params._whatsapp_consent_msg ) {
            var waConsentBox = `
                <p class="form-row form-row-wide" id="wcap_wa_consent_block" class="form-row">
                    ${display_wa_consent_box}
                    <span style="font-size: small">
                        ${wcap_capture_guest_user_params._whatsapp_consent_msg}
                    </span>
                </p>
            `;
            jQuery("#billing_phone_field" ).after(waConsentBox);
        }

        if ( wcap_capture_guest_user_params._display_consent_box ) {
            $("#wcap_sms_consent").click( function () {
                wcap_sms_consent = document.getElementById( 'wcap_sms_consent' ).checked;
                localStorage.setItem( 'wcap_sms_consent', wcap_sms_consent );
    
                // run an ajax call and save the data that user gave consent.
                var data = {
                    action : 'wcap_sms_consent',
                    consent: wcap_sms_consent
                };
                $.post( wcap_capture_guest_user_params.ajax_url, data, function() {
                });
                
            });
        }

        if ( wcap_capture_guest_user_params._display_whatsapp_consent_box ) {
            $("#wcap_whatsapp_consent").click( function () {
                wcap_whatsapp_consent = document.getElementById( 'wcap_whatsapp_consent' ).checked;
                localStorage.setItem( 'wcap_whatsapp_consent', wcap_whatsapp_consent );
    
                // run an ajax call and save the data that user gave consent.
                var data = {
                    action : 'wcap_whatsapp_consent',
                    consent: wcap_whatsapp_consent
                };
                $.post( wcap_capture_guest_user_params.ajax_url, data, function() {
                });
                
            });
        }

        $("#wcap_email_consent").click( function () { 
            wcap_email_consent = document.getElementById( 'wcap_email_consent' ).checked;
            gdpr_consent = false === wcap_email_consent ? false : true;
            gdpr_email_consent = false === wcap_email_consent ? false : true;
            localStorage.setItem( 'wcap_email_consent', wcap_email_consent );
            if ( wcap_email_consent === true ) {
                $('input#' + email_field + ', input#' + phone_field + ', input#billing_first_name, input#billing_last_name' + custom_field ).trigger( 'load' );
            }
    
            // run an ajax call and save the data that user gave consent.
            var data = {
                action : 'wcap_email_consent',
                consent: wcap_email_consent,
                email: $( '#' + email_field ).val(),
            };
            $.post( wcap_capture_guest_user_params.ajax_url, data, function() {
            });
                
        });

	    $("#wcap_gdpr_no_thanks").click( function () {
	        wcap_capture_guest_user_params._show_gdpr_message = false;
	        
	        gdpr_consent = false;        
	        
	        // run an ajax call and save the data that user did not give consent
	        var data = {
	            action : 'wcap_gdpr_refused'
	        };
	        $.post( wcap_capture_guest_user_params.ajax_url, data, function() {
	            $("#wcap_gdpr_message_block").empty().append("<span style='font-size: small'>" + 
	            wcap_capture_guest_user_params._gdpr_after_no_thanks_msg + "</span>").delay(5000).fadeOut();
	        });
	        
	    });
	});
});
