jQuery( function( $ ) {

	$(document).on('mouseout',function(evt) {
	
		if ( ( evt.toElement === null || evt.relatedTarget === null ) && evt.clientY < 10 ) {
		
			var storageKeys = Object.keys(localStorage);
			for (i = 0; i < storageKeys.length; i++) {
				if ( storageKeys[i].includes( 'wc_cart_hash_' ) ) {
					var key = storageKeys[i];
					break;
				}
			}
			var wcap_cart_hash = localStorage.getItem(key);
			var ei_displayed = localStorage.getItem( 'wcap_ei_displayed' );
			if ( null === ei_displayed && '' !== wcap_cart_hash  && null !== wcap_cart_hash ) {
				wcap_open_ei_popup();
				localStorage.setItem('wcap_ei_displayed', 'yes' );
				var wcap_next_date = new Date();
				wcap_next_date.setHours( wcap_next_date.getHours() + 24 );
				localStorage.setItem('wcap_ei_popup_displayed_next_time', wcap_next_date.getTime() );
			}
		}
	});

	jQuery(document).ready(function(){

		var wcap_last_check_date = localStorage.getItem( 'wcap_ei_popup_displayed_next_time' );
		if ( null != wcap_last_check_date ){
			if ( (new Date()).getTime() > wcap_last_check_date ){
				localStorage.removeItem( 'wcap_ei_popup_displayed_next_time' );
				localStorage.removeItem( 'wcap_ei_displayed' );
				localStorage.removeItem( 'wcap_ei_template_id' );
			}
		}
		jQuery( document ).on( 'touchstart', function() {
			setTimeout(() => {
				document.addEventListener("scroll", wcap_scrollSpeed);
			}, 10000);
			
			wcap_scrollSpeed = () => {
				var storageKeys = Object.keys(localStorage);
				for (i = 0; i < storageKeys.length; i++) {
					if ( storageKeys[i].includes( 'wc_cart_hash_' ) ) {
						var key = storageKeys[i];
						break;
					}
				}
				var wcap_cart_hash = localStorage.getItem(key);
				var ei_displayed = localStorage.getItem( 'wcap_ei_displayed' );
				
				var lastPosition = window.scrollY;
				setTimeout(() => {
				newPosition = window.scrollY;
				}, 100);
				var currentSpeed = 0;
				if ( newPosition > 0 ) {
					currentSpeed = newPosition - lastPosition;
				}
			
				if (currentSpeed > 160) {
					if ( null === ei_displayed && '' !== wcap_cart_hash ) {			
						wcap_open_ei_popup();
						localStorage.setItem('wcap_ei_displayed', 'yes' );
						document.removeEventListener("scroll", wcap_scrollSpeed);
					}
				}
			};
		});
	});
	var wcap_ei_modal_data = {
		wcap_heading_section_text_email: wcap_ei_modal_param.wcap_atc_head,
		wcap_image_path : wcap_ei_modal_param.wcap_image_path,
        wcap_image_file_name : wcap_ei_modal_param.wcap_image_file_name,
        wcap_image_file_name_ei : wcap_ei_modal_param.wcap_image_file_name_ei,
		wcap_text_section_text_field:    wcap_ei_modal_param.wcap_atc_text,
		wcap_email_placeholder_section_input_text: wcap_ei_modal_param.wcap_atc_email_place,
		wcap_button_section_input_text : wcap_ei_modal_param.wcap_atc_button,
		wcap_button_bg_color : wcap_ei_modal_param.wcap_atc_button_bg_color,
		wcap_button_text_color : wcap_ei_modal_param.wcap_atc_button_text_color,
		wcap_popup_text_color : wcap_ei_modal_param.wcap_atc_popup_text_color,
		wcap_popup_heading_color : wcap_ei_modal_param.wcap_atc_popup_heading_color,
		wcap_non_mandatory_modal_input_text : wcap_ei_modal_param.wcap_atc_non_mandatory_input_text,
		wcap_atc_button: {
			backgroundColor: wcap_ei_modal_param.wcap_atc_button_bg_color,
			color          : wcap_ei_modal_param.wcap_atc_button_text_color  
		},
		wcap_atc_popup_text:{
			color          : wcap_ei_modal_param.wcap_atc_popup_text_color,  
		},
		wcap_atc_popup_heading:{
			color          : wcap_ei_modal_param.wcap_atc_popup_heading_color,   
		},
		wcap_quick_ck_heading: wcap_ei_modal_param.wcap_quick_ck_heading,
		wcap_quick_ck_heading_color: wcap_ei_modal_param.wcap_ei_heading_text_color,
		wcap_quick_ck_text: wcap_ei_modal_param.wcap_quick_ck_text,
		wcap_quick_ck_text_color: wcap_ei_modal_param.wcap_ei_text_color,
		wcap_quick_ck_button: wcap_ei_modal_param.wcap_ei_button_text, 
		wcap_quick_ck_button_bg_color: wcap_ei_modal_param.wcap_ei_button_bg_color,
		wcap_quick_ck_button_text_color: wcap_ei_modal_param.wcap_ei_button_text_color,
		wcap_ei_button: {
			backgroundColor: wcap_ei_modal_param.wcap_ei_button_bg_color,
			color          : wcap_ei_modal_param.wcap_ei_button_text_color,
		},
		wcap_ei_popup_heading: {
			color: wcap_ei_modal_param.wcap_ei_heading_text_color,
		},
		wcap_ei_popup_text: {
			color: wcap_ei_modal_param.wcap_ei_text_color,
		},
		wcap_atc_coupon_applied_msg: wcap_ei_modal_param.wcap_atc_coupon_applied_msg
	};

	// Exit Intent Handler class.
	var wcap_ei_popup = function() {
		$( document )
			.on( 'click', '#wcap_ei_quick_ck_button', wcap_redirect_link )
			.on( 'change', '#wcap_ei_popup_input', wcap_capture_email )
			.on( 'click', '.wcap_popup_non_mandatory_button', wcap_no_thanks )
			.on( 'click', '#wcap_ei_popup_close', wcap_ei_dismissed );

		$( document ).on('keydown', function(e) {
			if (e.keyCode == 27) {
				close();
			}
		});
	}

	function  wcap_open_ei_popup (){

		var wcap_email = localStorage.getItem( 'wcap_hidden_email_id' );
		var billing_email = '';
		if ( jQuery( '#billing_email' ).length > 0 && '' !== jQuery( '#billing_email' ).val() ) {
			billing_email = jQuery( '#billing_email' ).val();
			localStorage.setItem('wcap_hidden_email_id', billing_email );
		}
		if ( null === wcap_email && '' === billing_email ) {
			var wcap_modal = wcap_ei_modal_param.wcap_ei_modal_data;
		} else {
			var wcap_modal = wcap_ei_modal_param.wcap_ei_modal_no_email_data;
		}
		$(document.body).addClass('wcap-atc-modal-open').append('<div class="wcap-modal-overlay"></div>');
		$(document.body).append('<div class="wcap-modal" style="overflow-y:auto; max-height:90%;"><div class="wcap-modal__contents"> '+ wcap_modal+ ' </div> </div>');
		wcap_atc_position();

		var wcap_stats = {
			template_id: wcap_ei_modal_param.wcap_ei_template_id,
			stats_action: 'wcap_ei_opened',
			action: 'wcap_ei_stats_update'
		}
		// Run an ajax to capture the stats.
		$.post( wcap_ei_modal_param.wc_ajax_url.toString().replace( '%%endpoint%%', 'wcap_ei_stats_update' ), wcap_stats, function( response_dat, status, xhr ) {
			localStorage.setItem( 'wcap_ei_template_id', wcap_ei_modal_param.wcap_ei_template_id );
		} );
		var myViewModel = new Vue({
			el: '#wcap_popup_main_div',
			data: wcap_ei_modal_data,
		});
	}

	function verify_email( user_email ) {
		var pattern = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i);

		if ( ! pattern.test( user_email  ) ) {
			$('#wcap_placeholder_validated_msg').text(wcap_ei_modal_param.wcap_mandatory_email_text);
			$( "#wcap_placeholder_validated_msg" ).fadeIn();
			setTimeout( function(){$( "#wcap_placeholder_validated_msg" ).fadeOut();},3000);
//			return false;
		} else {
			if ( wcap_ei_modal_param.wcap_debounce_key ) {
				var settings = {
					"async": true,
					"crossDomain": true,
					"url": "https://api.debounce.io/v1/?api=" + wcap_ei_modal_param.wcap_debounce_key + "&email=" + user_email,
					"method": "GET",
					"headers": {}
				}

				$.ajax(settings)
				.done(function (response) {
					if ( response.success === '1' ) {
						if( '4' === response.debounce.code || '5' === response.debounce.code || '8' === response.debounce.code ) {
							wcap_log_cart( user_email );
						} else {
							$( '#wcap_placeholder_validated_msg' ).text(wcap_ei_modal_param.wcap_mandatory_email_text);
							$( "#wcap_placeholder_validated_msg" ).fadeIn();
							setTimeout( function(){$( "#wcap_placeholder_validated_msg" ).fadeOut();},3000);
//							return false;
						}
					} else {
						wcap_log_cart( user_email );
					}
				})
				.fail(function (error) {
					wcap_log_cart( user_email );
				});
			} else {
				wcap_trigger_validate_email( user_email );
			}
		}
	}

	function wcap_capture_email() {
		var user_email = $( '#wcap_ei_popup_input' ).val();
		verify_email( user_email );
	}

	function wcap_log_cart( user_email ) {
		// Disable the button.
		var button_element = document.getElementById('wcap_ei_quick_ck_button');
		button_element.setAttribute( 'disabled', 'disabled' );
		button_element.classList.add('loading');
		// Stats update.
		var wcap_stats = {
			template_id: wcap_ei_modal_param.wcap_ei_template_id,
			stats_action: 'wcap_email_capture',
			billing_email: user_email,
			ei_update: 'yes',
			action: 'wcap_ei_stats_update'
		}
		// Run an ajax to capture the stats & create a cart history record.
		$.post( wcap_ei_modal_param.wc_ajax_url.toString().replace( '%%endpoint%%', 'wcap_ei_stats_update' ), wcap_stats, function( response_dat, status, xhr ) {
			localStorage.setItem( 'wcap_hidden_email_id', user_email );
			if ( 'success' === status && response_dat ) {
				localStorage.setItem( "wcap_abandoned_id", response_dat );
				// Enable the button.
				button_element.classList.remove('loading');
				button_element.removeAttribute( 'disabled' );
			}
		});
	}

	function wcap_redirect_link( e ) {
		e.preventDefault();

		// Stats update.
		var wcap_stats = {
			template_id: wcap_ei_modal_param.wcap_ei_template_id,
			stats_action: 'wcap_ei_button_click',
			action: 'wcap_ei_stats_update'
		}
		// Run an ajax to capture the stats & apply the coupon.
		$.post( wcap_ei_modal_param.wc_ajax_url.toString().replace( '%%endpoint%%', 'wcap_ei_stats_update' ), wcap_stats, function( response_dat, status, xhr ) {
			// Redirect.
			var url = wcap_ei_modal_param.wcap_ei_redirect_to_link;

			if ( '' !== url ) {
				window.location.href = url;
			} else {
				close();
			}
		});
	
	}

	function wcap_no_thanks(e) {
		e.preventDefault();
		var wcap_stats = {
			template_id: wcap_ei_modal_param.wcap_ei_template_id,
			stats_action: 'wcap_ei_no_thanks',
			action: 'wcap_ei_stats_update'
		}
		// Run an ajax to capture the stats.
		$.post( wcap_ei_modal_param.wc_ajax_url.toString().replace( '%%endpoint%%', 'wcap_ei_stats_update' ), wcap_stats, function( response_dat, status, xhr ) {
			close();
		} );
	}
	function wcap_ei_dismissed() {
		var wcap_stats = {
			template_id: wcap_ei_modal_param.wcap_ei_template_id,
			stats_action: 'wcap_ei_dismissed',
			action: 'wcap_ei_stats_update'
		}
		// Run an ajax to capture the stats.
		$.post( wcap_ei_modal_param.wc_ajax_url.toString().replace( '%%endpoint%%', 'wcap_ei_stats_update' ), wcap_stats, function( response_dat, status, xhr ) {
			close();	
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

		if ( modal_height < modal_contents_height - 5 ) { // To ensure popup size depends on the contents.
			$('.wcap-modal__body').height( modal_height - modal_header_height );
		}
	}
	function wcap_trigger_validate_email( email ) {
		
		var data = {
		wcap_atc_email : email,
		action: 'wcap_verify_email_validity'
		}
		return jQuery.ajax({
			type: 'POST',
			url : wcap_ei_modal_param.wc_ajax_url,             
			data: data,
			dataType: 'JSON',
		}).done( function( response ) {
			if ( typeof response.status !== 'undefined' && 'success' !== response.status ) {
				
				jQuery( '#wcap_placeholder_validated_msg' ).text( response.msg );
				jQuery( "#wcap_placeholder_validated_msg" ).fadeIn();
				setTimeout( function(){$( "#wcap_placeholder_validated_msg" ).fadeOut();},3000);
			} else {				
				wcap_log_cart( email );
			}
			}).fail(function (error) {				
				wcap_log_cart( email );
		});	
		
	}
	new wcap_ei_popup();
});