
new Vue({
    //el: '#template_add_edit',
    el: '#secondary-nav-wrap',
    data() {		
	
		return {
			settings:wcap_template_settings,
			currentSettingsTab: 'popup_templates',
		settings_tabs: [ {
				id: 'general',
				text: "General",
				link:''
			},
			{
				id: 'popup_templates',
				text: "Popup Templates",
				link:'#/popup-templates'
			},
			{
				id: 'facebook_messenger',
				text: "Facebook Messenger",
				link:'#/facebook-messenger'
			},
			{
				id: 'sms',
				text: "SMS",
				link:'#/sms'
			},
			{
				id: 'whatsapp',
				text: "WhatsApp",
				link:'#/whatsapp'
			},
			{
				id: 'connectors',
				text: "Connectors",
				link:'#/connectors'
			},
			{
				id: 'email_report',
				text: "Email Report",
				link:'#/email-report'
			},
			{
				id: 'license',
				text: "License",
				link:'#/license'
			},
		],
			saving: false,
			saved_message: wcap_template_settings.saved_message,		
			message: '',
			update_notification:{},
			coupon_id : [],
			file : wcap_template_settings.wcap_heading_section_text_image,
			file_ei : wcap_template_settings.wcap_heading_section_text_image_ei,
			message_saved:wcap_template_settings.message_saved,
			wcap_popup_heading_color_picker:wcap_template_settings.wcap_popup_heading_color_picker,			
			wcap_popup_text_color_picker:wcap_template_settings.wcap_popup_text_color_picker,		
			wcap_button_color_picker:wcap_template_settings.wcap_button_color_picker,			
			wcap_button_text_color_picker:wcap_template_settings.wcap_button_text_color_picker,			
			wcap_button_text_color_picker:wcap_template_settings.wcap_button_text_color_picker,			
			wcap_quick_ck_popup_heading_color_picker:wcap_template_settings.wcap_quick_ck_popup_heading_color_picker,			
			wcap_quick_ck_popup_text_color_picker:wcap_template_settings.wcap_quick_ck_popup_text_color_picker,			
			wcap_quick_ck_button_color_picker:wcap_template_settings.wcap_quick_ck_button_color_picker,			
			wcap_quick_ck_button_text_color_picker:wcap_template_settings.wcap_quick_ck_button_text_color_picker,			
		}
    },    
    mounted: function() {
		
        var self = this;
		this.coupon_enabled = this.settings.wcap_auto_apply_coupons_atc;
		self.coupon_id = Object.keys(self.settings.coupon_ids);
		for ( var wookey in this.settings.rules ) {
			this.wcap_trigger_selectwoo( 'wcap_rule_type_' + wookey );
			for ( var rule_key in this.settings.rules[wookey]['rule_value'] ) {
				opt_key = this.settings.rules[wookey]['rule_value'][rule_key];
				 opt_val = this.settings.rules[wookey]['rule_text'][rule_key];
				 
				var newOption = new Option(opt_val, opt_key, false, true);
				jQuery('#wcap_rule_value_' + wookey).append(newOption);
			}
		}
		
	
    },
	methods: {        
		set_popup_data : function ( data ) {
			this.popup_data = data;
		},
		change_atc_text :function(){
		var self = this;
		if ( 'exit_intent' === this.settings.wcap_template_type ) {
			this.settings.wcap_text_section_text = this.settings.wcap_ei_modal_text;
			this.settings.wcap_quick_ck_heading_section_text_email = this.settings.wcap_ei_modal_text;
		} else {
			this.settings.wcap_text_section_text = this.settings.wcap_atc_modal_text;
			this.settings.wcap_quick_ck_heading_section_text_email = this.settings.wcap_atc_modal_text;
		}
			
		},
		change_link : function( link, ele ){
			event.target.href = event.target.href + link
		}
		,
		reset_template : function( template_id ){
			var data = new FormData();
		data.append( 'action', 'wcap_atc_reset_setting' );
		data.append( 'template_id', template_id );
		jQuery( "#ac_events_loader" ).show();
		
		axios.post( ajaxurl, data )
            .then( function (response) {
				jQuery( "#ac_events_loader" ).hide();
                window.location = window.location
            })
            .catch(function (error) {
				jQuery( "#ac_events_loader" ).hide();
				window.location = window.location
            });
		
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
			// Capture the data in an object to send to the DB.
			var data = {
				rule_type: row.rule_type,
				rule_condition: row.rule_condition,				
				rule_value:[],
				rule_text:[],
				edit: false,
				add: true
			};
			// prepare the row to be displayed.
			var selText = [];
			selected_vals = jQuery('#wcap_rule_value_' + index ).val();		

			jQuery('#wcap_rule_value_' + index + ' option').each(function () {
			   var $this = jQuery(this);
			   if ($this.length) {
				   optval = $this.val();
				   for( i = 0; i < selected_vals.length; i ++ ) {
						if ( selected_vals[i] == optval ) {
							selText.push(  $this.text() );
						}
					}			   
			   }
			});
			this.settings.rules[index].rule_value = jQuery('#wcap_rule_value_' + index ).val();
			this.settings.rules[index].rule_selText = selText.join(', ');
			this.settings.rules[index].edit = false;
			this.settings.rules[index].add = false;
			
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

			jQuery('#wcap_rule_value_' + index + ' option').each(function () {
			   var $this = jQuery(this);
			   if ($this.length) {
				   optval = $this.val();
				   for( i = 0; i < selected_vals.length; i ++ ) {
						if ( selected_vals[i] == optval ) {
							selText.push(  $this.text() );
						}
					}			   
			   }
			});
			this.settings.rules[index].rule_value = jQuery('#wcap_rule_value_' + index ).val();
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

	    wcap_add_to_cart_popup_save_settings : function () {
        	var self = this;
        	var data = new FormData();
        	Object.entries(self.settings).forEach(([key, value]) => {
    			data.append(key, value);
			});
			data.append( 'wcap_rule_type_', JSON.stringify(this.settings.rules));
			data.append( 'coupon_ids_new', jQuery( '#coupon_ids' ).val() );
			data.append( 'wcap_heading_section_text_image', this.file );
			data.append( 'wcap_heading_section_text_image_ei', this.file_ei );
	    	data.append( 'action', 'wcap_add_to_cart_popup_save_settings' );
	    	data.append( 'security', wcap_template_settings.wcap_settings_nonce);
	    	axios.post( ajaxurl, data )
            .then( function (response) {
				if ( response.data.success ) {
						//console.log(self.settings);
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

	    back_popup_templates_list : function() {
	    	window.history.back();
		},
		selectFile_atc() {
			var self = this;
      		var data  = new FormData();
      		data.append( 'atc_add_edit_nonce', this.settings.atc_add_edit_nonce );
      		data.append( 'wcap_file', this.$refs.file_atc.files[0] );
      		data.append( 'action', 'wcap_add_to_cart_popup_upload_files' );
      		axios.post( ajaxurl, data )
			.then(function( response ) {
				self.file = self.$refs.file_atc.files[0].name;
				console.log(response.data);
			})
			.catch(function(){
			  console.log('FAILURE!!');
			});
    	},
		selectFile() {
			var self = this;
      		var data  = new FormData();
      		data.append( 'atc_add_edit_nonce', this.settings.atc_add_edit_nonce );
      		data.append( 'wcap_file', this.$refs.file.files[0] );
      		data.append( 'action', 'wcap_add_to_cart_popup_upload_files' );
      		axios.post( ajaxurl, data )
			.then(function( response ) {
				self.file = self.$refs.file.files[0].name;
				console.log(response.data);
			})
			.catch(function(){
			  console.log('FAILURE!!');
			});
    	},
    	selectFile_ei() {
			var self = this;
      		var data  = new FormData();
      		data.append( 'atc_add_edit_nonce', this.settings.atc_add_edit_nonce );
      		data.append( 'wcap_file', this.$refs.file1.files[0] );
      		data.append( 'action', 'wcap_add_to_cart_popup_upload_files' );
      		axios.post( ajaxurl, data )
			.then(function( response ) {
				self.file_ei = self.$refs.file1.files[0].name;
				console.log(response.data);
			})
			.catch(function(){
			  console.log('FAILURE!!');
			});
    	},
    	deleteFile_image() {
      		var data  = new FormData();
      		data.append( 'wcap_image_path', this.file );
      		data.append( 'action', 'wcap_add_to_cart_popup_delete_files' );
      		data.append( 'atc_add_edit_nonce', this.settings.atc_add_edit_nonce );
      		this.file = '';
      		axios.post( ajaxurl, data )
			.then(function( response ) {
				console.log(response.data);
			})
			.catch(function(){
			  console.log('FAILURE!!');
			});
    	},

    	deleteFile_image_ei() {
      		var data  = new FormData();
      		data.append( 'wcap_image_path', this.file_ei );
      		data.append( 'action', 'wcap_add_to_cart_popup_delete_files' );
      		data.append( 'atc_add_edit_nonce', this.settings.atc_add_edit_nonce );
      		this.file_ei = '';
      		axios.post( ajaxurl, data )
			.then(function( response ) {
				console.log(response.data);
			})
			.catch(function(){
			  console.log('FAILURE!!');
			});
    	},

 
 wcap_trigger_selectwoo : function ( object_id ) {
	 
	 var rule_type = document.getElementById( object_id ).value;
    var id = object_id.substr(-1);
    var select_id = 'wcap_rule_value_' + id;
    var select_box = document.getElementById( select_id );

    var select_cond_id = 'wcap_rule_condition_' + id;
    var select_cond_box = document.getElementById( select_cond_id );

    if ( '' !== rule_type ) {
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
            case 'custom_pages':
                select_box.setAttribute( 'class', 'wcap_rule_value wc-product-search' );
                select_box.setAttribute( 'multiple', 'multiple' );
                select_box.setAttribute( 'data-action', 'wcap_json_find_pages' );
                select_box.setAttribute( 'name', select_id + '[]' );
                select_box.setAttribute( 'data-placeholder', wcap_atc_rules_params.wcap_custom_pages );
                select_box.setAttribute( 'style', 'width:90%;' );
                jQuery('.wc-product-search').selectWoo();
                jQuery( document.body ).trigger( 'wc-enhanced-select-init' );
                break;
            case 'product_cat':
                var tr = document.getElementById(id);
                var td = tr.cells[2];
                select_box.setAttribute( 'class', 'wcap_rule_value wc-product-search' );
                select_box.setAttribute( 'multiple', 'multiple' );
                select_box.setAttribute( 'data-action', 'wcap_json_find_product_cat' );
                select_box.setAttribute( 'name', select_id + '[]' );
                select_box.setAttribute( 'data-placeholder', wcap_atc_rules_params.wcap_prod_cat_select );
                select_box.setAttribute( 'style', 'width:90%;' );
                jQuery('.wc-product-search').selectWoo();
                jQuery( document.body ).trigger( 'wc-enhanced-select-init' );
                break;
            case 'products':
                select_box.setAttribute( 'class', 'wcap_rule_value wc-product-search' );
                select_box.setAttribute( 'multiple', 'multiple' );
                select_box.setAttribute( 'data-action', 'wcap_json_find_products' );
                select_box.setAttribute( 'name', select_id + '[]' );
                select_box.setAttribute( 'data-placeholder', wcap_atc_rules_params.wcap_products_select );
                select_box.setAttribute( 'style', 'width:90%;' );
                jQuery('.wc-product-search').selectWoo();
                jQuery( document.body ).trigger( 'wc-enhanced-select-init' );
                break;
        }
    }
	 
 }
    }
})


jQuery( document ).ready( function(){
	jQuery( document.body ).on( 'click', '.color-picker span', function() {
        parent = jQuery(this).parent();
        jQuery( 'input', parent ).click();
    })
})