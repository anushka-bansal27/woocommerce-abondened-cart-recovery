/**
 * Abandoned cart detail Modal
 */

var Modal;
var wcap_clicked_cart_id;
var $wcap_get_email_address;
var $wcap_customer_details;
var $wcap_cart_total;
var $wcap_abandoned_date;
var $wcap_abandoned_status; 
var email_body;
var $wcap_cart_status;
var $wcap_show_customer_detail;

jQuery(function($) {

    Modal = {
        init: function(){

            $(document.body).on( 'click', '.wcap_customer_detail_modal', this.handle_customer_details );
            $(document.body).on( 'click', '.wcap-js-edit_email', this.edit_email_popup );
            $(document.body).on( 'click', '.wcap-js-close-modal', this.close );
            $(document.body).on( 'click', '.wcap-modal-overlay', this.close );
            $(document.body).on( 'click', '.wcap_update_email', this.wcap_update_email );

            $(document).keydown(function(e) {
                if (e.keyCode == 27) {
                    jQuery('#Fb-2').trigger('click');
                }
            });

        },
        handle_customer_details: function ( event ){
            event.preventDefault();
            var wcap_text_of_event = $(event.target).text();
            if ( wcap_text_of_event.indexOf ('Hide') == -1 ){
                $( ".wcap_modal_customer_all_details" ).fadeIn();
               
                $(event.target).text('Hide Details') ;
            }else{
                $( ".wcap_modal_customer_all_details" ).fadeOut();
               
                $(event.target).text('Show Details') ;
            }
        },
        handle_link_mouse_middle_click: function( e ){
            
           if( e.which == 2 ) {
                var wcap_get_currentpage = window.location.href;    
                this.href = wcap_get_currentpage;
                e.preventDefault();
                return false;
           }
        },
        edit_email_popup: function( e ){
            e.preventDefault();

            var $a = $( this );
            var current_page   = ''; 
            var wcap_get_currentpage = window.location.href;
            var $wcap_get_email_address;
            var $wcap_break_email_text;
            var $email_text;
            var $wcap_row_data;

            if ( wcap_get_currentpage.indexOf('action=emailstats') == -1 ){ 
                $wcap_row_data = $a.closest("tr")[0];
                $email_text = $wcap_row_data.getElementsByTagName('td')[2].innerHTML;
                $wcap_break_email_text   = $email_text.split('<div');
                $wcap_break_email_text_1 = $wcap_break_email_text[0].split('<a');
                $wcap_get_email_address  = $wcap_break_email_text_1[0];
                $wcap_user_id = $(this).data('wcap-user-id');

                $wcap_cart_status = '';
            }

            email_body = `
                <div class="wcap-modal__body">
                    <div class="wcap-modal__body-inner">
                        <label for="wcap_edit_guest_email">Update Guest Email:</label>
                        <input
                            type="email"
                            id="wcap_edit_guest_email"
                            name="wcap_edit_guest_email"
                            class="wcap_edit_guest_email"
                            value="${$wcap_get_email_address}"
                            style="width:256px;"/>
                        <div class="wcap_edit_footer" style="padding:25px 0px;">
                            <a
                                class="button wcap_update_email trietary-btn"
                                style="color:white;"
                                data-wcap-user-id="${$wcap_user_id}"
                                data-modal-type="ajax">
                                Update Email Address
                            </a>
                        </div>
                    </div>
                </div>`;

            var type = $a.data('modal-type');
            
            if ( type == 'ajax' ) {
                wcap_clicked_cart_id = $a.data('wcap-cart-id');
                Modal.open( 'type-ajax' );

                $( '.wcap-modal-cart-content-hide' ).hide();
            }
        },
        open: function( classes ) {

            $(document.body).addClass('wcap-modal-open').append('<div class="wcap-modal-overlay"></div>');
            var modal_body = '<div class="wcap-modal ' + classes + '"><div class="modal-content"> <div class="wcap-modal__header" style="background-color: rgb(88, 65, 156);"><h1 style="color:white;">Cart #'+wcap_clicked_cart_id+'</h1>'+$wcap_cart_status+'</div>'+ email_body +' <div class = "wcap-modal-cart-content-hide" id ="wcap_remove_class">  </div> </div>  <div class="wcap-icon-close wcap-js-close-modal"></div>    </div>';

            $(document.body).append( modal_body );

            this.position();
        },
        close: function() {
            $(document.body).removeClass('wcap-modal-open wcap-modal-loading');
            
            $('.wcap-modal, .wcap-modal-overlay').remove();
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
        },
        wcap_update_email: function() {
            const $a = $( this );
            const wcap_user_id = $a.data('wcap-user-id');
            const wcap_email = $( '.wcap_edit_guest_email' ).val();
            const edit_data = {
                action : 'wcap_edit_guest_email',
                wcap_user_id,
                wcap_email
            };

            $a.text('Processing...');

            $.post( ajaxurl, edit_data, function( response ){
                $a.text(response.data);
                
                setTimeout( function(){
                    $('.wcap-js-close-modal' ).click();
                    location.reload();
                },500);
            });

            return false;
        },
    };
    Modal.init();
});