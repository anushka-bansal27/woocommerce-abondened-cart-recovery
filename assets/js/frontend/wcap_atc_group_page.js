
var wcap_atc_modal_js_loaded = "yes";

jQuery( function( $ ) {

	$(document).ready(function() {
		var wcap_last_check_date = localStorage.getItem( "wcap_popup_displayed_next_time" );
		if ( null != wcap_last_check_date ) {
			if ( (new Date()).getTime() > wcap_last_check_date ) {
				localStorage.removeItem( "wcap_popup_displayed_next_time" );
				localStorage.removeItem( "wcap_popup_displayed" );
			}
		}		
	});

	var wcap_product_id = "";	
	var wcap_atc_modal_data = {
		wcap_image_path : wcap_atc_modal_param.wcap_image_path,
        wcap_image_file_name : wcap_atc_modal_param.wcap_image_file_name,
        wcap_heading_section_text_email: wcap_atc_modal_param.wcap_atc_head,
        wcap_text_section_text_field:    wcap_atc_modal_param.wcap_atc_text,
        wcap_email_placeholder_section_input_text: wcap_atc_modal_param.wcap_atc_email_place,
        wcap_button_section_input_text : wcap_atc_modal_param.wcap_atc_button,
        wcap_button_bg_color : wcap_atc_modal_param.wcap_atc_button_bg_color,
        wcap_button_text_color : wcap_atc_modal_param.wcap_atc_button_text_color,
        wcap_popup_text_color : wcap_atc_modal_param.wcap_atc_popup_text_color,
        wcap_popup_heading_color : wcap_atc_modal_param.wcap_atc_popup_heading_color,
        wcap_non_mandatory_modal_input_text : wcap_atc_modal_param.wcap_atc_non_mandatory_input_text,
		wcap_phone_placeholder_section_input_text : wcap_atc_modal_param.wcap_atc_phone_place,
        wcap_atc_button: {
            backgroundColor: wcap_atc_modal_param.wcap_atc_button_bg_color,
            color          : wcap_atc_modal_param.wcap_atc_button_text_color  
        },
        wcap_atc_popup_text:{
            color          : wcap_atc_modal_param.wcap_atc_popup_text_color,  
        },
        wcap_atc_popup_heading:{
            color          : wcap_atc_modal_param.wcap_atc_popup_heading_color,   
        },
		wcap_atc_coupon_applied_msg: wcap_atc_modal_param.wcap_atc_coupon_applied_msg
    };

    $( document.body ).on( 'wc_fragments_refreshed', function( response_dat ) {
    		
		var storageKeys = Object.keys(localStorage);
		for (i = 0; i < storageKeys.length; i++) {
			if ( storageKeys[i].includes( 'wc_cart_hash_' ) ) {
				var key = storageKeys[i];
				break;
			}
		}
		var wcap_cart_hash = localStorage.getItem(key);
		// Move forward if a product has been added to the cart.
		if ( '' !== wcap_cart_hash ) {
			
			var wcap_is_popup_displayed = localStorage.getItem("wcap_popup_displayed");
			var wcap_atc_abandoned_id   = localStorage.getItem( 'wcap_abandoned_id' );
			if ( ( typeof wcap_is_popup_displayed === undefined ) || ( wcap_is_popup_displayed != "yes" ) || null === wcap_atc_abandoned_id ) {
				localStorage.setItem("wcap_popup_displayed", "yes");
				
				var wcap_next_date = new Date();
				wcap_next_date.setHours( wcap_next_date.getHours() + 24);
				localStorage.setItem("wcap_popup_displayed_next_time", wcap_next_date.getTime() );

				var captured_by = null !== localStorage.getItem('wcap_captured_from') ? localStorage.getItem('wcap_captured_from') : 'atc';

    	        var wcap_email_data = {
					wcap_atc_email         : localStorage.getItem("wcap_hidden_email_id"),
					wcap_atc_user_action   : localStorage.getItem("wcap_atc_user_action"),
					wcap_atc_template_id   : wcap_atc_modal_param.wcap_atc_template_id,
					wcap_atc_phone         : localStorage.getItem('wcap_atc_phone_number'),
					wcap_sms_consent       : localStorage.getItem('wcap_sms_consent'),
					wcap_whatsapp_consent  : localStorage.getItem('wcap_whatsapp_consent'),
					wcap_captured_from     : captured_by,
					action: 'wcap_atc_store_guest_email'
				}
				
				$.post( wc_cart_fragments_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'wcap_atc_store_guest_email' ), wcap_email_data, function(response_dat , status, xhr ) {
					if ( status === 'success' && response_dat ) {
						localStorage.setItem( "wcap_abandoned_id", response_dat );
					}
		    	} );
			}
		}
    } );

	/**
	 * AddToCartHandler class.
	 */
	var wcap_single_simple_atc = function() {
		$( document )
			.on( 'click', '.single_add_to_cart_button', this.wcap_on_single_add_to_cart )
			.on( 'click', '.wcap_popup_button', this.wcap_add_to_cart_from_shop )
			.on( 'click', '.wcap_popup_non_mandatory_button', this.wcap_add_product_to_cart )
			.on( 'click', '.wcap_popup_close', wcap_atc_dismissed );

		$( document ).on('keydown', function(e) {
            if (e.keyCode == 27) {
                close();
            }
        });
	};

	/**
	* Handle the add to cart event.
	*/
	wcap_single_simple_atc.prototype.wcap_on_single_add_to_cart = function( e ) {
		
		var wcap_is_popup_displayed = localStorage.getItem("wcap_popup_displayed");
		var wcap_atc_user_action    = localStorage.getItem('wcap_atc_user_action');
		if( ( typeof wcap_is_popup_displayed === undefined  ||  wcap_is_popup_displayed != "yes" || null === wcap_atc_user_action ) && 
			"" == wcap_atc_modal_param.wcap_populate_email &&
			'yes' !== localStorage.getItem( 'wcap_mailchimp_captured' ) ) {
			
			var queryString = $('.cart').serializeArray();
			var arrayLength = ( queryString.length ) - 1 ;
			var wcap_child_product_quanity_set = 'false';
			for (var i = 0; i < arrayLength; i++) {
			    var wcap_check_qty_value = queryString[i]['value'];
				if ( wcap_check_qty_value > 0 ){
					wcap_child_product_quanity_set = 'true';
				}    
			}

			if ( 'true' == wcap_child_product_quanity_set ){
				e.preventDefault();	
				wcap_product_id = this.value;
				$("#wcap-add-to-cart").val(wcap_product_id);
				wcap_open_atc_modal();
			}
		}

		if ( "" != wcap_atc_modal_param.wcap_populate_email ) {
			localStorage.setItem( "wcap_hidden_email_id", wcap_atc_modal_param.wcap_populate_email );
			localStorage.setItem( "wcap_atc_user_action", "yes" );
		}
	};

	function  wcap_open_atc_modal (){

		$(document.body).addClass('wcap-atc-modal-open').append('<div class="wcap-modal-overlay"></div>');
        $(document.body).append('<div class="wcap-modal" style="overflow-y:auto; max-height:90%;"><div class="wcap-modal__contents"> '+ wcap_atc_modal_param.wcap_atc_modal_data+ ' </div> </div>');
        wcap_atc_position();

        $( document.body ).trigger( 'wcap_after_atc_load' );
		var wcap_stats = {
			template_id: wcap_atc_modal_param.wcap_atc_template_id,
			stats_action: 'wcap_atc_opened',
			action: 'wcap_atc_stats_record'
		}
		// Run an ajax to capture the stats.
		$.post( wcap_atc_modal_param.wc_ajax_url.toString().replace( '%%endpoint%%', 'wcap_atc_stats_record' ), wcap_stats, function( response_dat, status, xhr ) {
			localStorage.setItem( 'wcap_atc_template_id', wcap_atc_modal_param.wcap_atc_template_id );
		} );
        var myViewModel = new Vue({
        	el: '#wcap_popup_main_div',
        	data: wcap_atc_modal_data,
        });
        $(".wcap_popup_button").prop("disabled", true);

        $("#wcap_popup_input").on("input", function(e) {
		    var wcap_get_email_address = $('#wcap_popup_input').val();
		    var is_button_disabled = $(".wcap_popup_button").is(":disabled");
		    if ( wcap_get_email_address.length > 0 && is_button_disabled == true ) {
				$(".wcap_popup_button").prop("disabled", false);		    	
		    } else if ( wcap_get_email_address.length == 0 && is_button_disabled == false ){
		    	$(".wcap_popup_button").prop("disabled", true );
		    }
		});
	}

	function wcap_atc_dismissed() {
		var wcap_stats = {
			template_id: wcap_atc_modal_param.wcap_atc_template_id,
			stats_action: 'wcap_atc_dismissed',
			action: 'wcap_atc_stats_record'
		}
		close();
		// Run an ajax to capture the stats.
		$.post( wcap_atc_modal_param.wc_ajax_url.toString().replace( '%%endpoint%%', 'wcap_atc_stats_record' ), wcap_stats, function( response_dat, status, xhr ) {
			if ( 'on' === wcap_atc_modal_param.wcap_close_icon_add_to_cart ) {
				wcap_add_product_to_cart_for_all();
			} else {
				close();	
			}
		} );
	}

	function close () {
        $(document.body).removeClass('wcap-atc-modal-open wcap-modal-loading');
        $('.wcap-modal, .wcap-modal-overlay').remove();
    }

	function wcap_atc_position() {

        $('.wcap-modal__body').removeProp('style');

        var modal_header_height = $('.wcap-modal__header').outerHeight();
        var modal_height = $('.wcap-modal').height();
        var modal_width = $('.wcap-modal').width();
        var modal_body_height = $('.wcap-modal__body').outerHeight();
        var modal_contents_height = modal_body_height + modal_header_height;

        $('.wcap-modal').css({
            'margin-left': -modal_width / 2,
            'margin-top': -modal_height / 2
        });

        if ( modal_height < modal_contents_height - 5 ) {
            $('.wcap-modal__body').height( modal_height - modal_header_height );
        }
    }

    /**
	 * Handle the add to cart event.
	 */
	wcap_single_simple_atc.prototype.wcap_add_to_cart_from_shop = function( e ) {
		
		e.preventDefault();

		var wcap_get_email_address = $('#wcap_popup_input').val();
		var wcap_get_phone_number  = $('#wcap_atc_phone').val();	
		
		/* https://stackoverflow.com/questions/2855865/jquery-validate-e-mail-address-regex */
		var pattern = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i);

		if ( ! pattern.test( wcap_get_email_address  ) ) {
			$('#wcap_placeholder_validated_msg').text(wcap_atc_modal_param.wcap_mandatory_email_text);
			$( "#wcap_placeholder_validated_msg" ).fadeIn();
            setTimeout( function(){$( "#wcap_placeholder_validated_msg" ).fadeOut();},3000);
		} else {
			if ( 'on' === wcap_atc_modal_param.wcap_switch_atc_phone_mandatory && ( '' === wcap_get_phone_number || 'undefined' === typeof wcap_get_phone_number || wcap_get_phone_number.length < 7 ) ) {
				$('#wcap_placeholder_validated_msg').text(wcap_atc_modal_param.wcap_phone_mandatory_text);
				$( "#wcap_placeholder_validated_msg" ).fadeIn();
		        setTimeout( function(){$( "#wcap_placeholder_validated_msg" ).fadeOut();},3000);
			} else {
				if ( wcap_atc_modal_param.wcap_debounce_key ) {
					var settings = {
						"async": true,
						"crossDomain": true,
						"url": "https://api.debounce.io/v1/?api=" + wcap_atc_modal_param.wcap_debounce_key + "&email=" + wcap_get_email_address,
						"method": "GET",
						"headers": {}
					}

					$.ajax(settings)
					.done(function (response) {
						if ( response.success === '1' ) {
							if( '4' === response.debounce.code || '5' === response.debounce.code || '8' === response.debounce.code ) {
								wcap_add_to_cart_action();
							} else {
								$( '#wcap_placeholder_validated_msg' ).text(wcap_atc_modal_param.wcap_mandatory_email_text);
								$( "#wcap_placeholder_validated_msg" ).fadeIn();
								setTimeout( function(){$( "#wcap_placeholder_validated_msg" ).fadeOut();},3000);
							}
						} else {
							wcap_add_to_cart_action();
						}
					})
					.fail(function (error) {
						wcap_add_to_cart_action();
					});
				} else {
					wcap_trigger_validate_email( wcap_get_email_address );
				}
			}
		}
	};

	wcap_single_simple_atc.prototype.wcap_add_product_to_cart = function( e ) {
		e.preventDefault();
		var wcap_stats = {
			template_id: wcap_atc_modal_param.wcap_atc_template_id,
			stats_action: 'wcap_atc_no_thanks',
			action: 'wcap_atc_stats_record'
		}
		// Run an ajax to capture the stats.
		$.post( wcap_atc_modal_param.wc_ajax_url.toString().replace( '%%endpoint%%', 'wcap_atc_stats_record' ), wcap_stats, function( response_dat, status, xhr ) {
			if ( "off" == wcap_atc_modal_param.wcap_atc_mandatory_email ) {
				localStorage.setItem("wcap_atc_user_action", "no" );
				wcap_add_product_to_cart_for_all();	
			} else {
				var wcap_get_email_address = $('#wcap_popup_input').val();
				var wcap_validate_text = wcap_atc_modal_param.wcap_mandatory_text;
				if ( wcap_get_email_address ) {
					wcap_validate_text = wcap_atc_modal_param.wcap_mandatory_email_text;
				}
				$('#wcap_placeholder_validated_msg').text( wcap_validate_text );
	
				$( "#wcap_placeholder_validated_msg" ).fadeIn();
				
				setTimeout( function(){
					$( "#wcap_placeholder_validated_msg" ).fadeOut();
					//close();
				},3000);
			}
		} );
	}

	function wcap_add_to_cart_action() {
		if( $( '#wcap_coupon_auto_applied' ).length > 0 ) {
			$( '#wcap_coupon_auto_applied' ).text(wcap_atc_modal_param.wcap_atc_coupon_applied_msg);
			$( '#wcap_coupon_auto_applied' ).fadeIn();
			$timer = parseInt( wcap_atc_modal_param.wcap_coupon_msg_fadeout_timer );
			setTimeout( function() {
				$( '#wcap_coupon_auto_applied' ).fadeOut();
				wcap_get_client_email = $('#wcap_popup_input').val();
				localStorage.setItem("wcap_hidden_email_id", wcap_get_client_email);
				if ( $('#wcap_atc_phone').length > 0 ) {
					localStorage.setItem( 'wcap_atc_phone_number', $('#wcap_atc_phone').val() );
					if ( $( '#wcap_sms_consent' ).length > 0 ) {
						wcap_sms_consent = document.getElementById( 'wcap_sms_consent' ).checked;
						localStorage.setItem( 'wcap_sms_consent', wcap_sms_consent );
					}

					if ( $( '#wcap_whatsapp_consent' ).length > 0 ) {
						wcap_whatsapp_consent = document.getElementById( 'wcap_whatsapp_consent' ).checked;
						localStorage.setItem( 'wcap_whatsapp_consent', wcap_whatsapp_consent );
					}
				}
				localStorage.setItem("wcap_atc_user_action", "yes" );
				wcap_add_product_to_cart_for_all();
			}, $timer );
		} else {
			wcap_get_client_email = $('#wcap_popup_input').val();
			localStorage.setItem("wcap_hidden_email_id", wcap_get_client_email);
			if ( $('#wcap_atc_phone').length > 0 ) {
				localStorage.setItem( 'wcap_atc_phone_number', $('#wcap_atc_phone').val() );
				if ( $( '#wcap_sms_consent' ).length > 0 ) {
					wcap_sms_consent = document.getElementById( 'wcap_sms_consent' ).checked;
					localStorage.setItem( 'wcap_sms_consent', wcap_sms_consent );
				}

				if ( $( '#wcap_whatsapp_consent' ).length > 0 ) {
					wcap_whatsapp_consent = document.getElementById( 'wcap_whatsapp_consent' ).checked;
					localStorage.setItem( 'wcap_whatsapp_consent', wcap_whatsapp_consent );
				}
			}
			localStorage.setItem("wcap_atc_user_action", "yes" );
			wcap_add_product_to_cart_for_all();
		}
	}

	function wcap_add_product_to_cart_for_all (){
		var wcap_is_popup_displayed = localStorage.getItem("wcap_popup_displayed");

		if ( ( typeof wcap_is_popup_displayed === undefined ) || ( wcap_is_popup_displayed != "yes" ) ){
			wcap_get_client_email = $('#wcap_popup_input').val();
			localStorage.setItem("wcap_hidden_email_id", wcap_get_client_email);
		}

		$form = $('form.cart');
		//$form.append('<input type="hidden" name="add-to-cart" value="'+wcap_product_id+'" />');
		$form.submit();
	}

	function wcap_trigger_validate_email( email ) {
		
		var data = {
		wcap_atc_email : email,
		action: 'wcap_verify_email_validity'
		}
		return jQuery.ajax({
			type: 'POST',
			url : wcap_atc_modal_param.wc_ajax_url,             
			data: data,
			dataType: 'JSON',
		}).done( function( response ) {
			if ( typeof response.status !== 'undefined' && 'success' !== response.status ) {
				
				jQuery( '#wcap_placeholder_validated_msg' ).text( response.msg );
				jQuery( "#wcap_placeholder_validated_msg" ).fadeIn();
				setTimeout( function(){$( "#wcap_placeholder_validated_msg" ).fadeOut();},3000);
			} else {				
				wcap_add_to_cart_action();
			}
			}).fail(function (error) {				
				wcap_add_to_cart_action();
		});	
		
	}
	new wcap_single_simple_atc();
});
