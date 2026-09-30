
Vue.component('wcap-thumbnail', {
		props: [ 'value', 'templates', 'keyt', 'showThumbnail' ],
		data: function () {
			return {
				count: 0
			}
		},
		template: `
			<div class="col-sm-12 col-md-3 col-lg-3 wcap-container text-center">
				<label :for="keyt" class="wcap-image-label">
					<input 
						type="radio" 
						name="wcap-template-select" 
						:id="keyt" 
						:value="keyt" 
						class="wcap-radio" 
						v-model="wcap_template"
					>
					<img v-bind:src="templates.url" class="wcap-image">
					Template {{keyt}}
				<button class="wcap-preview-btn" v-on:click="clickMethodPreview"><span class="dashicons dashicons-search"></span></button>
				</label>
			</div>`,
		methods: {
			clickMethodPreview: function (event) {
				this.$root.showThumbnail = false;
				this.$root.label = this.templates.url;
				this.$root.wcap_template_selected = this.templates.html;
			}
		},
		computed: {
			wcap_template: {
				get: function(){
					return this.value;
				},
				set: function(){
					this.$emit( 'checked', this.templates.html );
				}
			}
		}
	});


var edit_template = new Vue({
    //el: '#template_add_edit',
    el: '#secondary-nav-wrap',
    data() {		
	
		return {
			settings:{},
			currentSettingsTab: 'email_templates',
		settings_tabs: [ {
				id: 'email_templates',
				text: "Email Templates"
			},
			{
				id: 'sms_notifications',
				text: "SMS Notifications"
			},
			{
				id: 'facebook_messenger',
				text: "Facebook Messenger"
			}
		],
			saving: false,
			saved_message: false,		
			message: wcap_send_manual_email.message_loading,
			message_saved:'',
			popup_data:'',
			showThumbnail: true,
				label: '',
				templateList: wcap_template_params, // JS Global Variable Localized
				wcap_template_selected: null
						
		}
    },    
    mounted: function() {
		
		this.get_template_data();
    },
	methods: {
get_template_data : function(){
var data = new FormData();
	
		data.append( 'action', 'wcap_change_manual_email_data' );
		data.append( 'security', wcap_send_manual_email.wcap_settings_nonce );
		data.append( 'wcap_template_id', jQuery('#wcap_manual_template_name').val() );
		
		
		var self = this;
		this.saving = true;
		jQuery( "#ac_events_loader" ).show();
		
		axios.post( ajaxurl, data )
            .then( function (response) {
				this.saving = false;
				jQuery( "#ac_events_loader" ).hide();
                self.settings = response.data;
				var discount_expiry_split            =  self.settings.discount_expiry.split("-");
				  self.settings.wcac_coupon_expiry = discount_expiry_split[0];
                   self.settings.expiry_day_or_hour =  discount_expiry_split[1];
				wcap_body= self.settings.body;
				if ( jQuery("#wp-woocommerce_ac_email_body-wrap").hasClass( "tmce-active" ) ){
                        tinyMCE.activeEditor.setContent(wcap_body);
                    }else{
                        jQuery("#woocommerce_ac_email_body").val(wcap_body);
                    }
            })
            .catch(function (error) {
				jQuery( "#ac_events_loader" ).hide();				
});
},	
		set_popup_data : function ( action ) {
		jQuery( "#ac_events_loader" ).show();
		var data = new FormData();
	
		data.append( 'action', action );
		
		var email_body            = '';
                if ( jQuery("#wp-woocommerce_ac_email_body-wrap").hasClass( "tmce-active" ) ){
                    email_body =  tinyMCE.get('woocommerce_ac_email_body').getContent();
                }else{
                    email_body =  jQuery('#woocommerce_ac_email_body').val();
                }
		email_body = jQuery('<div />').text(email_body).html();		
		data.append( 'wc_template_header', this.settings.wc_email_header );		
		data.append( 'body_email_preview', email_body );	
		data.append( 'send_email_id', jQuery( '#send_test_email' ).val() );
		
		
		
		
		var self = this;
		this.saving = true;
		jQuery( "#ac_events_loader" ).show();
		
		self.popup_data = '';
		axios.post( ajaxurl, data )
            .then( function (response) {
				self.saving = false;
				jQuery( "#ac_events_loader" ).hide();
                self.popup_data = this.popup_data = response.data;
				
            })
            .catch(function (error) {

				jQuery( "#ac_events_loader" ).hide();				
            });
		},
		wcap_send_test_email : function ( ev = false ) {		

			send_email_id = '';
			
			
			if ( ev ) {
				var send_email_id = jQuery( '#send_test_email_preview' ).val()
				
				var $wcap_get_selected_button = jQuery( ev );
				var type = $wcap_get_selected_button.data('wcap-email-type');			

				if ( 'wc_preview' == type ) {
					is_wc_template = 'true';
				} else if ( 'normal_preview' == type ) {
					is_wc_template = 'false';
				}
			} else if ( 'undefined' !== typeof ( this.settings.send_test_email_preview ) ) {
				send_email_id = this.settings.send_test_email_preview;
			}
			
			
			if ( ! send_email_id.match(
			/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
			  ) ) {
				  
				  if ( ev ) {
					jQuery( "#preview_test_email_sent_msg" ).html( wcap_send_manual_email.wcap_email_warn_message );
					jQuery( "#preview_test_email_sent_msg" ).fadeIn();
					setTimeout( function(){jQuery( "#preview_test_email_sent_msg" ).fadeOut();},3000);
				  } else {
					jQuery( "#preview_email_sent_msg" ).html( wcap_send_manual_email.wcap_email_warn_message );
					jQuery( "#preview_email_sent_msg" ).fadeIn();
					setTimeout( function(){jQuery( "#preview_email_sent_msg" ).fadeOut();},3000);						  
				  }
				  
				 return; 
			}
		
			
			jQuery( "#ac_events_loader" ).show();
			
			var data = new FormData();
			data.append( 'send_email_id', send_email_id );
			
			data.append( 'action', 'wcap_preview_email_sent' );

			var email_body            = '';
			if ( jQuery("#wp-woocommerce_ac_email_body-wrap").hasClass( "tmce-active" ) ){
				email_body =  tinyMCE.get('woocommerce_ac_email_body').getContent();
			} else {
				email_body =  jQuery('#woocommerce_ac_email_body').val();
			}

			if ( !ev ) {
				is_wc_template = this.settings.is_wc_template;
			}
		email_body = jQuery('<div />').text(email_body).html();		
		data.append( 'body_email_preview', email_body );		
		data.append( 'subject_email_preview', this.settings.subject );	
		data.append( 'send_email_id', send_email_id );	
		data.append( 'is_wc_template', is_wc_template );		
		data.append( 'wc_template_header', this.settings.wc_email_header );
		
		jQuery( "#ac_events_loader" ).show();
		var self = this;
		axios.post( ajaxurl, data )
            .then( function (response) {
				jQuery( "#ac_events_loader" ).hide();
				
				var wcap_image_url = wcap_send_manual_email.wcap_email_sent_image_path;
				flash_div  = jQuery( "#preview_email_sent_msg" );
				if ( ev ){
					flash_div  = jQuery( "#preview_test_email_sent_msg" );
				}
				jQuery( flash_div ).html( "<img style = 'height: 18px; width:20px;' src="+ wcap_image_url +"> &nbsp;Email has been sent successfully." );
				jQuery( flash_div ).fadeIn();
				setTimeout( function(){jQuery( flash_div ).fadeOut();},3000);
			
            })
            .catch(function (error) {
				jQuery( "#ac_events_loader" ).hide();				
            });
			
		},
		wcap_send_email : function () {
        	var self = this;
        	var data = new FormData();
        	Object.entries(self.settings).forEach(([key, value]) => {
        		if ( key !== 'body' ) {
    				data.append(key, value);
    			}
			});
			var email_body = '';
			if ( jQuery("#wp-woocommerce_ac_email_body-wrap").hasClass( "tmce-active" ) ){
				email_body =  tinyMCE.get('woocommerce_ac_email_body').getContent();
			} else {
				email_body =  jQuery('#woocommerce_ac_email_body').val();
			}
			email_body = jQuery('<div />').text(email_body).html();
			data.append( 'wcap_wc_email_header', jQuery( '#wcap_wc_email_header').val() );
			data.append( 'abandoned_cart_id', jQuery( '#abandoned_cart_id' ).val() );
			data.append( 'wcap_manual_template_name', jQuery( '#wcap_manual_template_name' ).val() );
			data.append( 'woocommerce_ac_email_body', email_body );
			data.append( 'coupon_ids_new', jQuery( '#coupon_ids' ).val() );
	    	data.append( 'action', 'wcap_send_manual_email' );
	    	data.append( 'security', wcap_send_manual_email.wcap_settings_nonce );
	    	axios.post( ajaxurl, data )
            .then( function (response) {
				if ( response.data.success ) {
                		self.update_notification = response.data;
	                	self.message_saved = self.update_notification.success;
	                    self.saving = false;
	                    self.saved_message = true;
	                    setTimeout(() => {
	                        var elmnt = document.getElementById("save_message");
	                        elmnt.scrollIntoView( { behavior: "smooth", block: 'end' } ) });
                	}
            })
            .catch(function (error) {
            });

	    },
	    back_wcap_order_list : function() {
	    	window.history.back();
	    },
		change_link : function( link, ele ){
			event.target.href = event.target.href + link
		}
		,clickMethod: function (item) {
				this.showThumbnail = false;
				this.label = item.text;
			},
			on_select: function(value) {
				this.wcap_template_selected = value;
			},
			wcap_view_back: function() {
				this.showThumbnail = true;
				this.wcap_template_selected = null;
			},
			wcap_insert_html: function() {				
				if ( this.wcap_template_selected ) {
					axios.get(this.wcap_template_selected).then( function( response ){
						jQuery( '#wcap-preview-modal' ).modal('hide');
						if ( jQuery('#wp-woocommerce_ac_email_body-wrap').hasClass('html-active') ) { // We are in text mode.
						    jQuery('#woocommerce_ac_email_body').val(response.data); // Update the textarea's content.
						} else { // We are in tinyMCE mode.
						    var activeEditor = tinyMCE.get('woocommerce_ac_email_body');
						    if ( activeEditor!== null ) { // Make sure we're not calling setContent on null.
						        activeEditor.setContent(response.data); // Update tinyMCE's content.
						    }
						}
					}).catch(function( error ){
						console.log( error );
					});
				}
			},
		
		add_rule : function(){
			this.settings.rules.push( {
				rule_type: '',
				rule_condition: '',
				rule_value: [],
				rule_text:[],
				rule_selText:'',
				edit: true,
				add: true
			} );			
		},
		save_rule : function(  index, row ) {
			var data = {
				rule_type: row.rule_type,
				rule_condition: row.rule_condition,				
				rule_selText:'',
				rule_value:[],
				rule_text:[],
				edit: false,
				add: false
			};
			
			var selText = [];
			selected_vals = jQuery('#wcap_rule_value_' + index ).val();
			if ( jQuery('#wcap_rule_value_' +  + index )[0].tagName == 'SELECT' ) {
				jQuery('#wcap_rule_value_' + index + ' option').each(function () {
				var $this = jQuery(this);
				if ($this.length) {
					   optval = $this.val();
					   if ( jQuery.isArray( selected_vals ) ) {
					   for( i = 0; i < selected_vals.length; i ++ ) {
							if ( selected_vals[i] == optval ) {
								selText.push(  $this.text() );
							}
						}
					   } else if ( ( typeof selected_vals === 'string' || selected_vals instanceof String ) && selected_vals == optval ) {
						  selText.push(  $this.text() );
						  return false;
					   }					   
				   }
				});
			} else if ( jQuery('#wcap_rule_value_' +  + index )[0].tagName == 'INPUT' ){
				selText.push( selected_vals );
			}
			
			this.settings.rules[index].rule_selText = selText.join(', ');
			
			this.settings.rules[index].rule_selText = selText.join(', ');
			this.settings.rules[index].edit = false;
			this.settings.rules[index].add = false;


			//Vue.set( this.settings.rules, index, data );			
		},
		update_rule : function(  index, row ) {
			
			var data = {
				rule_type: row.rule_type,
				rule_condition: row.rule_condition,				
				rule_selText:'',
				rule_value:[],
				rule_text:[],
				edit: false,
				add: false
			};
			
			var selText = [];
			selected_vals = jQuery('#wcap_rule_value_' + index ).val();
			if ( jQuery('#wcap_rule_value_' +  + index )[0].tagName == 'SELECT' ) {
				jQuery('#wcap_rule_value_' + index + ' option').each(function () {
				var $this = jQuery(this);
				if ($this.length) {
					   optval = $this.val();
					   if ( jQuery.isArray( selected_vals ) ) {
					   for( i = 0; i < selected_vals.length; i ++ ) {
							if ( selected_vals[i] == optval ) {
								selText.push(  $this.text() );
							}
						}
					   } else if ( ( typeof selected_vals === 'string' || selected_vals instanceof String ) && selected_vals == optval ) {
						  selText.push(  $this.text() );
						  return false;
					   }					   
				   }
				});
			} else if ( jQuery('#wcap_rule_value_' +  + index )[0].tagName == 'INPUT' ){
				selText.push( selected_vals );
			}
			
			this.settings.rules[index].rule_selText = selText.join(', ');
			this.settings.rules[index].edit = false;
			this.settings.rules[index].add = false;
			
			//Vue.set( this.settings.rules, index, data );			
		},
		edit_rule : function( index, row ) {
			var data = {
				rule_type: row.rule_type,
				rule_condition: row.rule_condition,
				rule_value: row.rule_value,
				rule_text: row.rule_text,
				rule_selText:'',
				edit: true,
				add: false
			};
			Vue.set( this.settings.rules, index, data );
		},
		delete_rule :  function( index ) {
			this.settings.rules.splice( index, 1 );
		},
		
wcap_rule_values : function ( event ) {
	
	object_id = event.target.id;
	this.wcap_trigger_selectwoo( object_id );
    
},
 
 wcap_trigger_selectwoo : function ( object_id ) {
	 
	 var rule_type = document.getElementById( object_id ).value;
    var id = object_id.substr(-1);
    var select_id = 'wcap_rule_value_' + id;
    var select_box = document.getElementById( select_id );

    var select_cond_id = 'wcap_rule_condition_' + id;
    var select_cond_box = document.getElementById( select_cond_id );

    if ( '' !== rule_type ) {
		
		// Update the conditions select box.
        while( select_cond_box.options.length > 0 ) {
            select_cond_box.remove(0);
        }
        switch( rule_type ) {
            case 'cart_items_count':
            case 'cart_total':
                var wcap_count = Object.entries( wcap_send_manual_email.wcap_counts );
                for ( const[key, value ] of wcap_count ) {
                    let option1 = new Option( `${value}`, `${key}` );
                    select_cond_box.add( option1, undefined );
                }
                select_cond_box.removeAttribute( 'style' );
                break;
            default:
                var wcap_cond_includes = Object.entries( wcap_send_manual_email.wcap_cond_includes );
                for ( const[key, value ] of wcap_cond_includes ) {
                    let option1 = new Option( `${value}`, `${key}` );
                    select_cond_box.add( option1, undefined );
                }
                select_cond_box.setAttribute( 'style', 'width: 80%;' );
                break;
        }
		
        if ( select_box.nodeName === 'SELECT' ) {
            while (select_box.options.length > 0) {
                select_box.remove(0);
            }
            select_box.removeAttribute( 'onChange' );
		}
		
		if ( jQuery( '#' + select_id ).hasClass('select2-hidden-accessible') ) {
			jQuery( '#' + select_id ).select2('destroy');
			const selectValue = document.querySelector( select_id );
			const selectNew = document.createElement('select');
			selectNew.setAttribute( 'class', 'wcap_rule_value' );
			selectNew.setAttribute( 'id', select_id );
			selectNew.setAttribute( 'name', select_id );
			select_box.parentNode.replaceChild( selectNew, select_box );
			select_box = document.getElementById( select_id );
		}	
        switch( rule_type ) {
            case 'send_to':
                select_box.setAttribute( 'onChange', 'wcap_rule_value_updated( this.id )' );
                select_box.setAttribute( 'class', 'wcap_rule_value wc-product-search' );
                select_box.setAttribute( 'multiple', 'multiple' );
                select_box.setAttribute( 'data-action', 'wcap_json_find_send_to' );
                select_box.setAttribute( 'name', select_id + '[]' );
                select_box.setAttribute( 'data-placeholder', wcap_send_manual_email.wcap_send_to_select );
                select_box.setAttribute( 'style', 'width:90%;' );
                jQuery('.wc-product-search').selectWoo();
                jQuery( document.body ).trigger( 'wc-enhanced-select-init' );
                break;
            case 'cart_items_count':
            case 'cart_total':
                const selectValue = document.querySelector( select_id );
                const inputField = document.createElement('input');
                inputField.setAttribute( 'class', 'wcap_rule_value' );
                inputField.setAttribute( 'type', 'number' );
                inputField.setAttribute( 'id', select_id );
                inputField.setAttribute( 'name', select_id );
                inputField.min = 1;
                select_box.parentNode.replaceChild( inputField, select_box );
                break;
            case 'payment_gateways':
                var wcap_payment_gateways = Object.entries( wcap_send_manual_email.wcap_payment_gateways );
                for ( const[key, value ] of wcap_payment_gateways ) {
                    let option1 = new Option( `${value}`, `${key}` );
                    select_box.add( option1, undefined );
                }
                break;
            case 'cart_items':
                select_box.setAttribute( 'class', 'wcap_rule_value wc-product-search' );
                select_box.setAttribute( 'multiple', 'multiple' );
                select_box.setAttribute( 'data-action', 'wcap_json_find_products' );
                select_box.setAttribute( 'name', select_id + '[]' );
                select_box.setAttribute( 'data-placeholder', wcap_send_manual_email.wcap_product_select );
                select_box.setAttribute( 'style', 'width:90%;' );
                jQuery('.wc-product-search').selectWoo();
                jQuery( document.body ).trigger( 'wc-enhanced-select-init' );
                break;
            case 'coupons':
                select_box.setAttribute( 'class', 'wcap_rule_value wc-product-search' );
                select_box.setAttribute( 'multiple', 'multiple' );
                select_box.setAttribute( 'data-action', 'wcap_json_find_coupons' );
                select_box.setAttribute( 'name', select_id + '[]' );
                select_box.setAttribute( 'data-placeholder', wcap_send_manual_email.wcap_coupon_select );
                select_box.setAttribute( 'style', 'width:90%;' );
                jQuery('.wc-product-search').selectWoo();
                jQuery( document.body ).trigger( 'wc-enhanced-select-init' );
                break;
            case 'product_cat':
                var tr = document.getElementById(id);
                var td = tr.cells[2];
                console.log( td );
                select_box.setAttribute( 'class', 'wcap_rule_value wc-product-search' );
                select_box.setAttribute( 'multiple', 'multiple' );
                select_box.setAttribute( 'data-action', 'wcap_json_find_product_cat' );
                select_box.setAttribute( 'name', select_id + '[]' );
                select_box.setAttribute( 'data-placeholder', wcap_send_manual_email.wcap_prod_cat_select );
                select_box.setAttribute( 'style', 'width:90%;' );
                jQuery('.wc-product-search').selectWoo();
                jQuery( document.body ).trigger( 'wc-enhanced-select-init' );
                break;
            case 'product_tag':
                select_box.setAttribute( 'class', 'wcap_rule_value wc-product-search' );
                select_box.setAttribute( 'multiple', 'multiple' );
                select_box.setAttribute( 'data-action', 'wcap_json_find_product_tag' );
                select_box.setAttribute( 'name', select_id + '[]' );
                select_box.setAttribute( 'data-placeholder', wcap_send_manual_email.wcap_prod_tag_select );
                select_box.setAttribute( 'style', 'width:90%;' );
                jQuery('.wc-product-search').selectWoo();
                jQuery( document.body ).trigger( 'wc-enhanced-select-init' );
                break;
            case 'cart_status':
                select_box.setAttribute( 'class', 'wcap_rule_value wc-product-search' );
                select_box.setAttribute( 'multiple', 'multiple' );
                select_box.setAttribute( 'data-action', 'wcap_json_find_cart_status' );
                select_box.setAttribute( 'name', select_id + '[]' );
                select_box.setAttribute( 'data-placeholder', wcap_send_manual_email.wcap_cart_status_select );
                select_box.setAttribute( 'style', 'width:90%;' );
                jQuery('.wc-product-search').selectWoo();
                jQuery( document.body ).trigger( 'wc-enhanced-select-init' );
                break;
            case 'countries':
                select_box.setAttribute( 'class', 'wc-enhanced-select' );
                select_box.setAttribute( 'multiple', 'multiple' );
                select_box.setAttribute( 'name', select_id + '[]' );
                var wcap_available_countries = Object.entries( wcap_send_manual_email.wcap_available_countries );
                for ( const[key, value ] of wcap_available_countries ) {
                    let option1 = new Option( `${value}`, `${key}` );
                    //select_box.add( option1, undefined );
					jQuery( '#' + select_id ).append(option1, undefined);
                }
                jQuery( '#' + select_id ).select2();
                break;
            case 'order_status':
                select_box.setAttribute( 'class', 'wcap_rule_value wc-product-search' );
                select_box.setAttribute( 'multiple', 'multiple' );
                select_box.setAttribute( 'data-action', 'wcap_json_find_order_status' );
                select_box.setAttribute( 'name', select_id + '[]' );
                select_box.setAttribute( 'data-placeholder', wcap_send_manual_email.wcap_order_status_select );
                select_box.setAttribute( 'style', 'width:90%;' );
                jQuery('.wc-product-search').selectWoo();
                jQuery( document.body ).trigger( 'wc-enhanced-select-init' );
                break;
        }
    }
	 
 }
    }
})


jQuery( '.ac-import-template' ).on( 'click', function(){

	var frame;

	// If the media frame already exists, reopen it.
	if ( frame ) {
		frame.open();
		return;
	}
	
	// Create a new media frame
	frame = wp.media({
		className: 'media-frame',
		library: {
			type: 'text',
			subtype: 'html'
		},
		menu: 'default',
		view: {
			EmbedUrl: true
		},
		editing: true,
		//states: states,
		title: 'Select or Upload HTML Files',
		button: {
			text: 'Import this file'
		},
		multiple: false  // Set to true to allow multiple files to be selected
	});

	// When an image is selected in the media frame...
	frame.on( 'select', function() {

		// Get media attachment details from the frame state
		var attachment = frame.state().get('selection').first().toJSON();

		axios.get( attachment.url ).then( function( response ) {
			jQuery( '#wcap-preview-modal' ).modal( 'hide' );
			if ( jQuery('#wp-woocommerce_ac_email_body-wrap').hasClass('html-active') ) { // We are in text mode.
                jQuery('#woocommerce_ac_email_body').val(response.data); // Update the textarea's content.
            } else { // We are in tinyMCE mode.
                var activeEditor = tinyMCE.get('woocommerce_ac_email_body');
                if ( activeEditor!== null ) { // Make sure we're not calling setContent on null.
                    activeEditor.setContent(response.data); // Update tinyMCE's content.
                }
            }
		}).catch( function( error ) {
			console.log(error);
		});
	});

	// Finally, open the modal on click
	frame.open();
});

jQuery(document.body).on( 'click', '#preview_test_email',  function(){
edit_template.wcap_send_test_email( this );
});


function wcap_rule_value_updated( id ) {
    var select_box = document.getElementById( id );
    var rule_value = getSelectValues( select_box );

    // append a text area to the td.
    var td = select_box.parentNode;
    var textArea = document.getElementById( 'wcap_rules_email_addresses' );

    if ( rule_value.indexOf( 'email_addresses' ) != -1 && textArea === null ) {

        var newField = document.createElement( 'textarea' );
        newField.setAttribute( 'rows', 3 );
        newField.setAttribute( 'cols', 35 );
        newField.setAttribute( 'name', 'wcap_rules_email_addresses' );
        newField.setAttribute( 'id', 'wcap_rules_email_addresses' );
        newField.setAttribute( 'style', 'margin-top: 10px;' );
        newField.setAttribute( 'placeholder', 'Please enter email addresses separated by a comma' );
        newField.innerHTML = '';
        td.append( newField );
        
    } else if ( rule_value.indexOf( 'email_addresses' ) == -1 && null !== textArea ) {
        textArea.parentNode.removeChild( textArea );
    }

}