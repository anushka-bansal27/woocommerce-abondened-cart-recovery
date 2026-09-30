var Modal;
var connector_body = '';

jQuery( function( $ ) {

    Modal = {
        init: function(){
            $(document.body).on( 'click', '.wcap-js-close-modal', this.close );
            $(document.body).on( 'click', '.wcap-modal-overlay', this.close );
        },
        open: function( classes, elem ) {

            if ( '' !== elem ) {
                var connector_name  = elem.data('wcap-name');
				var connector_title = elem.data('wcap-title');
                connector_name = connector_name.replace( 'wcap_', '' );
				if ( typeof( connector_title ) !='undefined' ) {
					connector_title = connector_title.replace( 'wcap_', '' );
				}
				else{
					connector_title = connector_name;
				}
				
                connector_name = connector_name.replace( connector_name[0], connector_name[0].toUpperCase() );
            
                $(document.body).addClass('wcap-modal-open').append('<div class="wcap-modal-overlay"></div>');
                var modal_body = '<div id="wcap_modal_1" class="wcap-modal ' + classes + '"><div class="wcap-modal__contents"> <div class="wcap-modal__header"><h1>Connect with '+connector_title+'</h1></div><div class="wcap-modal__body">'+ connector_body +' </div><div class = "wcap-modal-cart-content-hide" id ="wcap_remove_class">  </div> </div>  <div class="wcap-icon-close wcap-js-close-modal"></div>    </div>';

            } else {
                $(document.body).addClass('wcap-modal-open').append('<div class="wcap-modal-overlay"></div>');
                var modal_body = '<div class="wcap-modal ' + classes + '"><div class="wcap-modal__contents"> <div class="wcap-modal__body"></div><div class = "wcap-modal-cart-content-hide" id ="wcap_remove_class">  </div> </div>     </div>';
            }
            $(document.body).append( modal_body );

            this.position();
        },
        loading: function() {
            $(document.body).addClass('wcap-modal-loading');
        },

        contents: function ( contents ) {
            $(document.body).removeClass('wcap-modal-loading');

            contents = contents.replace(/\\(.)/mg, "$1");

            $('.wcap-modal__body').html(contents);

            this.position();
        },
        close: function( event, ui ) {
            $(document.body).removeClass('wcap-modal-open wcap-modal-loading');
            
            $('.wcap-modal, .wcap-modal-overlay').remove();
            localStorage.removeItem( 'wcap_mc_stores' );
            localStorage.removeItem( 'wcap_mc_lists' );
            
        },

        position: function() {

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
        

    };
    Modal.init();

    $( document ).ready(function() {

        $( document ).on( 'click', '.wcap_integrators_view', function() {
            count = parseInt( jQuery( this ).attr( 'data-wcap-count' ));
            if ( count == 0 ){
                return false;
            }
            var $id = this.id;
            $id = $id.split('_');
            var data = {
                'action': 'wcap_display_connectors',
                'type': $id[1]
            }
			jQuery( '.wcap_integrators_view' ).removeClass( 'current' );
			jQuery(this).addClass( 'current' );
			jQuery('#ac_events_loader').show();
            $.post( wcap_int_params.ajax_url, data, function( response ) {
                $( '#wcap_connectors_list' ).html( response );
				jQuery('#ac_events_loader').hide();
            });
			
			setTimeout(() => {
     			jQuery('#ac_events_loader').hide();
             }, 5000);
        });

        $( document ).on( 'click', '.wcap_button_connect', function() {
            var elem = $(this);
            
            Modal.open( 'type-ajax', elem );
            Modal.loading();
            var button_name = elem.data('wcap-name');
            button_name = button_name.replace( 'wcap_', '' );
            var data = {
                'action': 'wcap_display_connector_settings',
                'name': button_name
            };
            $.post( wcap_int_params.ajax_url, data, function( response ) {
                Modal.contents( response ); 
            });
        });

        $( document ).on( 'click', '.wcap_button_disconnect', function() {
            var elem = $(this);
            var button_name = elem.data('wcap-name');
            button_name = button_name.replace( 'wcap_', '' );
            var data = {
                'action': 'wcap_disconnect_connector',
                'name': button_name
            };
            Modal.open( 'type-ajax', '' );
            Modal.loading();
            $.post( wcap_int_params.ajax_url, data, function( response ) {
                $(document.body).removeClass('wcap-modal-loading');
                var inactive_count = $('#wcap_inactive').attr('data-wcap-count');
                var active_count = $('#wcap_active').attr('data-wcap-count');

                if ( response.status === 'success' ) {
                    $(document.body).addClass('wcap-modal-success');
                    if ( $('#wcap_logout_url').length > 0 ) {
						window.location = $('#wcap_logout_url').val(); // Go to the logout URL to disconnect with Google.
					}
                    inactive_count++;
                    active_count--;
                    $('#wcap_active_count').text( active_count );
                    $('#wcap_inactive_count').text( inactive_count );
                    $('#wcap_active').attr( 'data-wcap-count', active_count );
					$('#wcap_inactive').attr( 'data-wcap-count', inactive_count );

                    if ( parseInt( active_count ) == 0 ) {
                        $('#wcap_active').addClass('no_link');
                    }
                    if ( parseInt( inactive_count ) == 0 ) {
                        $('#wcap_inactive').addClass('no_link');
                    }

                    Modal.contents( '<p class="wcap_msg">' + response.message + '</p>'); 
                    setTimeout(() => {
                        $(document.body).removeClass('wcap-modal-success');
                        // close the modal.
                        $(document.body).removeClass('wcap-modal-open');
                        $('.wcap-modal, .wcap-modal-overlay').remove();	
        
                        // Update the buttons.
                        var connect_div = 'wcap_' + button_name + '_connect_div';
                        var connected_div = 'wcap_' + button_name + '_connected_div';
                        $( '#' + connect_div ).css( "display", "block" );
                        $( '#' + connected_div ).css( "display", "none" );
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
        });

        $( document ).on( 'click', '.wcap_button_sync', function() {
            // 
            
            var elem = $(this);
            var button_name = elem.data('wcap-name');
            button_name = button_name.replace( 'wcap_', '' );
            var data = {
                'action': 'wcap_sync_connector',
                'name': button_name
            };
            Modal.open( 'type-ajax', '' );
            Modal.loading();
            $.post( wcap_int_params.ajax_url, data, function( response ) {
                $(document.body).removeClass('wcap-modal-loading');
                if ( response.status === 'success' ) {
                    $(document.body).addClass('wcap-modal-success');
                    Modal.contents( '<p class="wcap_msg">' + response.message + '</p>'); 
                    setTimeout(() => {
                        $(document.body).removeClass('wcap-modal-success');
                        // close the modal.
                        $(document.body).removeClass('wcap-modal-open');
                        $('.wcap-modal, .wcap-modal-overlay').remove();
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
        });
    })
    
});

function validateEmail(email) 
{
	var re = /^(([^<>()[\]\.,;:\s@\"]+(\.[^<>()[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i;
	return re.test(email);
}
function remove_nolinks( active_count, inactive_count ) {
    if ( parseInt( active_count ) > 0) {
        jQuery('#wcap_active').removeClass('no_link');
    }
    if ( parseInt( inactive_count ) > 0) {
        jQuery('#wcap_inactive').removeClass('no_link');
    }
}