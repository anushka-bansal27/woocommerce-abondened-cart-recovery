jQuery( document ).ready( function() {
    var wcap_whatsapp_consent_box_val = '';

    jQuery("#wcap_gdpr_no_thanks").click(  function () {
        
        // run an ajax call and save the data that user did not give consent
        var data = {
            action          : 'wcap_gdpr_refused'
        };
        jQuery.post( wcap_registered_capture_params.ajax_url, data, function() {
            jQuery("#wcap_gdpr_message_block").empty().append("<span style='font-size: small'>" + 
            wcap_registered_capture_params._gdpr_after_no_thanks_msg + "</span>").delay(5000).fadeOut();
        });
        
    } );

    if ( 'true' == localStorage.getItem( 'wcap_whatsapp_consent' ) || wcap_registered_capture_params.consent_given ) {
        wcap_whatsapp_consent_box_val = 'checked';
    }

    if ( wcap_registered_capture_params._display_whatsapp_consent_box ) {
        display_wa_consent_box = "<input type='checkbox' id='wcap_whatsapp_consent' " + wcap_whatsapp_consent_box_val + "/>";
    }

    if ( undefined !== wcap_registered_capture_params._whatsapp_consent_msg && '' !== wcap_registered_capture_params._whatsapp_consent_msg ) {
        var waConsentBox = `
        <p class="form-row form-row-wide" id="wcap_wa_consent_block" class="form-row">
            ${display_wa_consent_box}
            <span style="font-size: small">
                ${wcap_registered_capture_params._whatsapp_consent_msg}
            </span>
        </p>
    `;
        jQuery("#billing_phone_field" ).after(waConsentBox);
    }

    if ( wcap_registered_capture_params._display_whatsapp_consent_box ) {
        jQuery( document ).on( 'change', '#wcap_whatsapp_consent', function () {
            wcap_whatsapp_consent = document.getElementById( 'wcap_whatsapp_consent' ).checked;
            localStorage.setItem( 'wcap_whatsapp_consent', wcap_whatsapp_consent );

            // run an ajax call and save the data that user gave consent.
            var data = {
                action : 'wcap_whatsapp_consent',
                consent: wcap_whatsapp_consent
            };
            jQuery.post( wcap_registered_capture_params.ajax_url, data, function() {
            });
            
        });
    }

});
