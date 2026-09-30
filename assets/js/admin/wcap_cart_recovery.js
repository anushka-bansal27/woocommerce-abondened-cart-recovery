Vue.use( VueRouter );
var email_templates_data  = null;
var sms_templates  = null;
var facebook_templates  = null;
var whatsapp_templates = null;

var email_templates = Vue.component( "email_templates", {
    template: '#email_templates',
	data() {
		return {
			settings:{},
			saving: false,
			saved_message: false,		
			message: wcap_strings.loading_message,
			message_saved:wcap_strings.message_saved,
			connector_message:wcap_strings.connector_message,
			popup_data:{},
			oldData:{},
			bulk_selected_ids: [],
			select_all: false,
			wcap_action:''
		}
    },    
    mounted: function() {
        var self = this;
		
		if ( null  !== window.email_templates_data ) {
			self.settings = window.email_templates_data;
			return;
		}		
		jQuery( "#ac_events_loader" ).show();
		
		var data = new FormData();
		data.append( 'action', 'ac_get_email_templates' );
		data.append( 'security', wcap_strings.wcap_settings_nonce );
        axios.post( ajaxurl, data )
            .then( function (response) {
                self.settings  = window.email_templates_data = response.data;
				jQuery( "#ac_events_loader" ).hide();
            })
            .catch(function (error) {
				jQuery( "#ac_events_loader" ).hide();
            });
    },
	methods: {
        toggle_template_status: function( val, id, type ) {
            this.saved_message = false;
            this.saving = true;
            let self = this;

            var data = new FormData();
			data.append( 'wcap_template_id', id );			
			data.append( 'action', 'wcap_toggle_template_status' );
			data.append( 'security', wcap_strings.wcap_settings_nonce );
			data.append( 'template_type', type );
			current_state = 'off';
			if ( val == '1' ) {
				current_state = 'on';
			}
			data.append( 'current_state', current_state );			
			this.message = wcap_strings.saving_message;
			jQuery( "#ac_events_loader" ).show();
            axios.post( ajaxurl, data )
              .then(function (response) {
				  jQuery( "#ac_events_loader" ).hide();
                if ( 'success' == response.data ) {
					self.message_saved = wcap_strings.message_saved;
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
		set_popup_data : function ( data ) {
			this.popup_data = data;
		},
		toggle_bulk_select : function ( row_id ) {
			
			this.bulk_selected_ids[row_id] = this.bulk_selected_ids[row_id] ? false : true;
						
		},
		bulk_select_ids : function ( select_all ) {
			
			for( var i in this.settings.email_templates )
			{				
				row_id = this.settings.email_templates[i].id;
				this.bulk_selected_ids[row_id] = select_all;				
			}			
		},
		delete_template : function( row_id ) {
			
			var req  = confirm( wcap_strings.confirm_delete );			
			if ( ! req ) { return; }
			this.bulk_selected_ids[row_id] =  'true';
			this.wcap_action = 'wcap_delete_template';
			this.bulk_delete();
			this.wcap_action = '';
			
		},
		get_paginated_data : function( paged, disabled ='' ) {
			if ( 'disabled' === disabled ) {
				return false;
			}
			var self  = this;
			
			const data = new FormData();		
			
			data.append( 'action', 'ac_get_email_templates' );
			data.append( 'security', wcap_strings.wcap_settings_nonce );
			
			this.saving = true;
			this.message = wcap_strings.loading_message;			
			jQuery( "#ac_events_loader" ).show();
		
			pageurl = ajaxurl + '?paged=' + paged;
		
			axios.post( pageurl, data )
				  .then(function (response) {
					  jQuery( "#ac_events_loader" ).hide();
	                if ( 'undefined' !== typeof( response.data ) ) {				
						
						self.saving = false;									
						self.settings =  response.data;	
					}
	            })
	            .catch( function (error) {
					  jQuery( "#ac_events_loader" ).hide();
	            });
		},
		bulk_action: function( ) {
			
			if ( this.wcap_action == '' ) {
				alert( wcap_strings.chose_action ); return;
			}
			if ( 'wcap_delete_template' === this.wcap_action ) {
				
				var req  = confirm( wcap_strings.confirm_delete );			
				if ( ! req ) { return; }				
				this.bulk_delete();
			}
		},
		bulk_delete : function() {
			
			var self = this;
				
			this.saved_message = false;
			this.saving        = true;
			this.message       = wcap_strings.deleting_template;
			jQuery( "#ac_events_loader" ).show();
				
			const data = new FormData();
			for ( var i in this.bulk_selected_ids )	{
				console.log( typeof( this.bulk_selected_ids[i] )  + ' ' + this.bulk_selected_ids[i] +'i is '+ i );
				if ( this.bulk_selected_ids[i] ) {
					data.append('template_id[]', i );
				}
			}
			
			data.append( 'action', this.wcap_action );
			data.append( 'template_type', 'email' );
			data.append( 'security', wcap_strings.wcap_settings_nonce );
			
              axios.post( ajaxurl, data )
			  .then(function (response) {
				  jQuery( "#ac_events_loader" ).hide();
                if ( 'success' == response.data || response.data.email_templates.length > 0 ) {
					
					self.message_saved = wcap_strings.template_deleted;
					self.saving = false;
					self.saved_message = true;
					
					self.settings.email_templates  =  response.data.email_templates;
					self.settings.total_items    =  response.data.total_items;
					
                    setTimeout(() => {
                       var elmnt = document.getElementById("save_message");
                       elmnt.scrollIntoView( { behavior: "smooth", block: 'end' } ) 
					   });
                }
            })
            .catch(function (error) {
            });
		}
    }
})


var sms_notifications = Vue.component( "sms_notifications", {
    template: '#sms_notifications',
	data() {
		return {
			settings:{},
			saving: false,
			saved_message: false,		
			message: wcap_strings.loading_message,
			message_saved:wcap_strings.message_saved,
			popup_data:{},
			settings_original : {},
			bulk_selected_ids: [],
			select_all: false,
			wcap_action:''
		}
    },    
    mounted: function() {
        var self = this;
		
		if ( null  !== window.sms_templates ) {
			self.settings = window.sms_templates;
			return;
		}		
		jQuery( "#ac_events_loader" ).show();
		
		var data = new FormData();
		data.append( 'action', 'ac_get_sms_templates' );
		data.append( 'security', wcap_strings.wcap_settings_nonce );
        axios.post( ajaxurl, data )
            .then( function (response) {
				
                self.settings  = window.sms_templates = response.data;

				var clone = JSON.parse(JSON.stringify(self.settings)); 
      			this.oldData = clone;
				jQuery( "#ac_events_loader" ).hide();
				setTimeout( function() {				
					jQuery('.wc-product-search').selectWoo();
					jQuery( document.body ).trigger( 'wc-enhanced-select-init' );				
				}, 1000 );
            })
            .catch(function (error) {
				jQuery( "#ac_events_loader" ).hide();
			});		
		
    },
	
	methods: {
        toggle_template_status: function( val, id, type ) {
			
			if ( 'undefined' === typeof( id ) ) {
				return;
			}

			let self = this;
            
			var data = new FormData();
			data.append( 'wcap_template_id', id );			
			data.append( 'action', 'wcap_toggle_template_status' );
			data.append( 'template_type', type );
			data.append( 'security', wcap_strings.wcap_settings_nonce );
			current_state = 'off';
			if ( val == '1' ) {
				current_state = 'on';
			}
			data.append( 'current_state', current_state );
			
			this.message = wcap_strings.saving_message;
			jQuery( "#ac_events_loader" ).show();
            axios.post( ajaxurl, data )
              .then(function (response) {
				  jQuery( "#ac_events_loader" ).hide();
                if ( 'success' == response.data ) {
					self.message_saved = wcap_strings.message_saved;
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
		save_template: function( index, row ) {
			this.update_template( index, row, true );
		},
		clean_pop_up_data : function ( data ) {
			this.popup_data = {};
			this.popup_data.frequency = '10';
			this.popup_data.day_or_hour = 'Minutes';
			this.popup_data.body = '';
			this.popup_data.coupon_code = '';
			this.popup_data.template_name = '';
		},
		edit_template: function( index, row ){
			this.popup_data = row;
			this.popup_data.index = index;
		},
		
		update_template : function( index, row, adding = false ) {
			var self = this;
			//this.settings.sms_templates[index].edit = false;
			//this.settings.sms_templates[index].add = false;			
			//Vue.set( this.settings.rules, index, data );
			
			var data = new FormData();
			if ( 'undefined' !== typeof( row.id ) ) {
				data.append( 'template_id', row.id );
			}			
			data.append( 'template_name', row.template_name );
			data.append( 'body', row.body );
			data.append( 'coupon_code', jQuery('#coupon_ids_' + index ).val() );
			data.append( 'frequency', row.frequency + ' ' + row.day_or_hour );
			data.append( 'is_active', row.is_active );
			data.append( 'day_or_hour', row.day_or_hour );
			data.append( 'action', 'wcap_save_sms_template' );
			data.append( 'security', wcap_strings.wcap_settings_nonce );
			jQuery( "#ac_events_loader" ).show();
			jQuery( '#coupon_ids_' + index ).val(''); // Empty the coupon code field. Issue #3273.
			this.saved_message = false;
			this.saving        = true;
			this.message       = wcap_strings.updating_template;
			if ( adding ) {
				this.message       = wcap_strings.adding_template;
			}
            axios.post( ajaxurl, data )
              .then(function (response) {
				  jQuery( "#ac_events_loader" ).hide();
				  jQuery('#Fb-2').trigger('click')
                if ( 'success' == response.data.status || response.data.sms_templates.length > 0 ) {
					self.message_saved = wcap_strings.template_updated;
					if ( adding ) {
						self.message       = wcap_strings.template_added;
					}
					self.saving = false;
                    self.saved_message = true;
                    self.settings.sms_templates  =  response.data.sms_templates;
					self.settings.total_items    =  response.data.total_items;
					var clone = JSON.parse(JSON.stringify(self.settings)); 
      				this.oldData = clone;
                    setTimeout(() => {
                        var elmnt = document.getElementById("save_message");
                        elmnt.scrollIntoView( { behavior: "smooth", block: 'end' } )
                    });
                }
            })
            .catch(function (error) {
            });
			
		},
		cancel_edit : function( index ){
			//original_data = this.save_get_original_settings();
			oldData.sms_templates[index].edit = false;
			oldData.sms_templates[index].add = false;	
			Vue.set( this.settings.sms_templates, index, oldData.sms_templates[index] );
		},
		toggle_bulk_select : function ( row_id ) {
			
			this.bulk_selected_ids[row_id] = this.bulk_selected_ids[row_id] ? false : true;
						
		},
		bulk_select_ids : function ( select_all ) {
			
			for ( var i in this.settings.sms_templates ) {				
				row_id = this.settings.sms_templates[i].id;				
				this.bulk_selected_ids[row_id] = select_all;
			}			
		},
		delete_template : function( row_id ) {
			
			var req  = confirm( wcap_strings.confirm_delete );			
			if ( ! req ) { return; }
			this.bulk_selected_ids[row_id] =  'true';
			this.wcap_action = 'wcap_delete_template';
			this.bulk_delete();
			this.wcap_action = '';
			
		},
		bulk_action: function( ) {
			
			if ( this.wcap_action == '' ) {
				alert( wcap_strings.chose_action ); return;
			}
			if ( 'wcap_delete_template' === this.wcap_action ) {
				
				var req  = confirm( wcap_strings.confirm_delete );			
				if ( ! req ) { return; }				
				this.bulk_delete();
			}
		},
		bulk_delete : function() {
			
			var self = this;
				
			this.saved_message = false;
			this.saving        = true;
			this.message       = wcap_strings.deleting_template;
			jQuery( "#ac_events_loader" ).show();
				
			const data = new FormData();
			for ( var i in this.bulk_selected_ids )	{
				if ( 'true' == this.bulk_selected_ids[i] ) {					
					data.append('template_id[]', i );
				}
			}
			
			data.append( 'action', this.wcap_action );
			data.append( 'template_type', 'sms' );
			data.append( 'security', wcap_strings.wcap_settings_nonce );
			
            axios.post( ajaxurl, data )
			  .then(function (response) {
				  jQuery( "#ac_events_loader" ).hide();
                if ( 'success' == response.data || response.data.sms_templates.length > 0 ) {
					
					self.message_saved = wcap_strings.template_deleted;
					self.saving = false;
					self.saved_message = true;
					
					self.settings.sms_templates  =  response.data.sms_templates;
					self.settings.total_items    =  response.data.total_items;
					
                    setTimeout(() => {
                       var elmnt = document.getElementById("save_message");
                       elmnt.scrollIntoView( { behavior: "smooth", block: 'end' } ) 
					   });
                }
            })
            .catch(function (error) {
            });
		},
		delete_row: function ( index, row ) {
			this.settings.sms_templates.splice( index, 1 )
		}			
    }
})

var whatsapp_notifications = Vue.component( "whatsapp_notifications", {
    template: '#whatsapp_notifications',
	data() {
		return {
			settings:{},
			saving: false,
			saved_message: false,		
			message: wcap_strings.loading_message,
			message_saved:wcap_strings.message_saved,
			popup_data:{},
			settings_original : {},
			bulk_selected_ids: [],
			select_all: false,
			wcap_action:''
		}
    },    
    mounted: function() {
        var self = this;
		
		if ( null  !== window.whatsapp_templates ) {
			self.settings = window.whatsapp_templates;
			return;
		}		
		jQuery( "#ac_events_loader" ).show();
		
		var data = new FormData();
		data.append( 'action', 'ac_get_whatsapp_templates' );
		data.append( 'security', wcap_strings.wcap_settings_nonce );
        axios.post( ajaxurl, data )
            .then( function (response) {
				
                self.settings  = window.whatsapp_templates = response.data;

				var clone = JSON.parse(JSON.stringify(self.settings)); 
      			this.oldData = clone;
				jQuery( "#ac_events_loader" ).hide();
				setTimeout( function() {				
					jQuery('.wc-product-search').selectWoo();
					jQuery( document.body ).trigger( 'wc-enhanced-select-init' );				
				}, 1000 );
            })
            .catch(function (error) {
				jQuery( "#ac_events_loader" ).hide();
			});		
		
    },
	
	methods: {
        toggle_template_status: function( val, id, type ) {
			
			if ( 'undefined' === typeof( id ) ) {
				return;
			}

			let self = this;
            
			var data = new FormData();
			data.append( 'wcap_template_id', id );			
			data.append( 'action', 'wcap_toggle_template_status' );
			data.append( 'template_type', type );
			data.append( 'security', wcap_strings.wcap_settings_nonce );
			current_state = 'off';
			if ( val == '1' ) {
				current_state = 'on';
			}
			data.append( 'current_state', current_state );
			
			this.message = wcap_strings.saving_message;
			jQuery( "#ac_events_loader" ).show();
            axios.post( ajaxurl, data )
              .then(function (response) {
				  jQuery( "#ac_events_loader" ).hide();
                if ( 'success' == response.data ) {
					self.message_saved = wcap_strings.message_saved;
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
		save_template: function( index, row ) {
			this.update_template( index, row, true );
		},
		clean_pop_up_data : function ( data ) {
			this.popup_data = {};
			this.popup_data.frequency = '10';
			this.popup_data.day_or_hour = 'Minutes';
			this.popup_data.template_name = '';
			this.popup_data.template_language = '';
			this.popup_data.header_variables = '';
			this.popup_data.body_variables = '';
			this.popup_data.button_variables = '';
			this.popup_data.coupon_code = '';
		},
		edit_template: function( index, row ){
			this.popup_data = row;
			var body = JSON.parse( row.body )
			this.popup_data.template_language = body.template_language;
			this.popup_data.header_variables = body.header_variables;
			this.popup_data.body_variables = body.body_variables;
			this.popup_data.button_variables = body.button_variables;
			this.popup_data.index = index;
		},
		
		update_template : function( index, row, adding = false ) {
			var self = this;
			var data = new FormData();
			if ( 'undefined' !== typeof( row.id ) ) {
				data.append( 'template_id', row.id );
			}			
			data.append( 'template_name', row.template_name );
			data.append( 'frequency', row.frequency + ' ' + row.day_or_hour );
			data.append( 'is_active', row.is_active );
			data.append( 'day_or_hour', row.day_or_hour );
			data.append( 'coupon_code', jQuery('#coupon_ids_' + index ).val() );
			data.append( 'body', JSON.stringify({
                'template_language': row.template_language,
                'header_variables': row.header_variables,
                'body_variables': row.body_variables,
                'button_variables': row.button_variables,
            }));
			data.append( 'action', 'wcap_save_whatsapp_template' );
			data.append( 'security', wcap_strings.wcap_settings_nonce );
			jQuery( "#ac_events_loader" ).show();
			jQuery( '#coupon_ids_' + index ).val(''); // Empty the coupon code field. Issue #3273.

			this.saved_message = false;
			this.saving        = true;
			this.message       = wcap_strings.updating_template;
			if ( adding ) {
				this.message       = wcap_strings.adding_template;
			}
            axios.post( ajaxurl, data )
              .then(function (response) {
				  jQuery( "#ac_events_loader" ).hide();
				  jQuery('#Fb-2').trigger('click')
                if ( 'success' == response.data.status || response.data.whatsapp_templates.length > 0 ) {
					self.message_saved = wcap_strings.template_updated;
					if ( adding ) {
						self.message       = wcap_strings.template_added;
					}
					self.saving = false;
                    self.saved_message = true;
                    self.settings.whatsapp_templates  =  response.data.whatsapp_templates;
					self.settings.total_items    =  response.data.total_items;
					var clone = JSON.parse(JSON.stringify(self.settings)); 
      				this.oldData = clone;
                    setTimeout(() => {
                        var elmnt = document.getElementById("save_message");
                        elmnt.scrollIntoView( { behavior: "smooth", block: 'end' } )
                    });
                }
            })
            .catch(function (error) {
            });
			
		},
		cancel_edit : function( index ){
			//original_data = this.save_get_original_settings();
			oldData.whatsapp_templates[index].edit = false;
			oldData.whatsapp_templates[index].add = false;	
			Vue.set( this.settings.whatsapp_templates, index, oldData.whatsapp_templates[index] );
		},
		toggle_bulk_select : function ( row_id ) {
			
			this.bulk_selected_ids[row_id] = this.bulk_selected_ids[row_id] ? false : true;
						
		},
		bulk_select_ids : function ( select_all ) {
			
			for ( var i in this.settings.whatsapp_templates ) {				
				row_id = this.settings.whatsapp_templates[i].id;				
				this.bulk_selected_ids[row_id] = select_all;
			}			
		},
		delete_template : function( row_id ) {
			
			var req  = confirm( wcap_strings.confirm_delete );			
			if ( ! req ) { return; }
			this.bulk_selected_ids[row_id] =  'true';
			this.wcap_action = 'wcap_delete_template';
			this.bulk_delete();
			this.wcap_action = '';
			
		},
		bulk_action: function( ) {
			
			if ( this.wcap_action == '' ) {
				alert( wcap_strings.chose_action ); return;
			}
			if ( 'wcap_delete_template' === this.wcap_action ) {
				
				var req  = confirm( wcap_strings.confirm_delete );			
				if ( ! req ) { return; }				
				this.bulk_delete();
			}
		},
		bulk_delete : function() {
			
			var self = this;
				
			this.saved_message = false;
			this.saving        = true;
			this.message       = wcap_strings.deleting_template;
			jQuery( "#ac_events_loader" ).show();
				
			const data = new FormData();
			for ( var i in this.bulk_selected_ids )	{
				if ( 'true' == this.bulk_selected_ids[i] ) {					
					data.append('template_id[]', i );
				}
			}
			
			data.append( 'action', this.wcap_action );
			data.append( 'template_type', 'whatsapp' );
			data.append( 'security', wcap_strings.wcap_settings_nonce );
			
            axios.post( ajaxurl, data )
			  .then(function (response) {
				  jQuery( "#ac_events_loader" ).hide();
                if ( 'success' == response.data || response.data.whatsapp_templates.length > 0 ) {
					
					self.message_saved = wcap_strings.template_deleted;
					self.saving = false;
					self.saved_message = true;
					
					self.settings.whatsapp_templates  =  response.data.whatsapp_templates;
					self.settings.total_items    =  response.data.total_items;
					
                    setTimeout(() => {
                       var elmnt = document.getElementById("save_message");
                       elmnt.scrollIntoView( { behavior: "smooth", block: 'end' } ) 
					   });
                }
            })
            .catch(function (error) {
            });
		},
		delete_row: function ( index, row ) {
			this.settings.whatsapp_templates.splice( index, 1 )
		}			
    }
})

var facebook_messenger = Vue.component( "facebook_messenger", {
    template: '#facebook_messenger',
	data() {
		return {
			settings:{},
			saving: false,
			saved_message: false,		
			message: wcap_strings.loading_message,
			message_saved:wcap_strings.message_saved,
			popup_data:{},
			modalShow: true,
			bulk_selected_ids: [],
			select_all: false,
			wcap_action:''
		}
    },    
    mounted: function() {
		
        var self = this;
		
		if ( null  !== window.facebook_templates ) {
			self.settings = window.facebook_templates;
			return;
		}		
		jQuery( "#ac_events_loader" ).show();
		
		var data = new FormData();
		data.append( 'action', 'ac_get_fb_templates' );
		data.append( 'security', wcap_strings.wcap_settings_nonce );
        axios.post( ajaxurl, data )
            .then( function (response) {
                self.settings  = window.facebook_templates = response.data;
				jQuery( "#ac_events_loader" ).hide();
            })
            .catch(function (error) {
				jQuery( "#ac_events_loader" ).hide();
            });
    },
	methods: {
        toggle_template_status: function( val, id ) {
            this.saved_message = false;
            this.saving = true;
            let self = this;

            var data = new FormData();
			data.append( 'wcap_template_id', id );		
			data.append( 'template_type', 'fb' );		
			data.append( 'action', 'wcap_toggle_template_status' );
			data.append( 'security', wcap_strings.wcap_settings_nonce );	
			current_state = 'off';
			if ( val == '1' ) {
				current_state = 'on';
			}
			data.append( 'current_state', current_state );			
			this.message = wcap_strings.saving_message;
			jQuery( "#ac_events_loader" ).show();
            axios.post( ajaxurl, data )
              .then(function (response) {
				  jQuery( "#ac_events_loader" ).hide();
                if ( 'success' == response.data ) {
					self.message_saved = wcap_strings.message_saved;
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
		clean_pop_up_data : function ( data ) {
			this.popup_data = {};
		},
		edit_template : function ( index, row ) {
			this.popup_data = row;
		},
		save_template : function( popup_data ) {
			var self = this;
			let add = false;
			var data = new FormData();
			
			this.message       = wcap_strings.updating_template;
			this.saved_message = false;
			this.saving        = true;
			
			if ( 'undefined' === typeof( popup_data.id ) ) {
				add = true;				
				this.message       = wcap_strings.adding_template;
			} else {
				data.append( 'template_id', popup_data.id );
			}
			let is_active = 0;

			
			if ( 'undefined' !== typeof( popup_data.is_active ) ) {				
				is_active = popup_data.is_active;				
			}
			data.append( 'active', is_active );			
			data.append( 'action', 'wcap_fb_save_template' );
			data.append( 'security', wcap_strings.wcap_settings_nonce );
			data.append( 'subject', popup_data.subject );
			data.append( 'sent_time', popup_data.frequency  + ' ' + popup_data.day_or_hour );
			data.append( 'body', JSON.stringify({
                'header': '',
                'subheader': '',
                'header_image': '',
                'checkout_text': popup_data.checkout_text,
                'unsubscribe_text': popup_data.unsubscribe_text
            }));
			
			jQuery( "#ac_events_loader" ).show();
			
			axios.post( ajaxurl, data )
              .then(function (response) {
				  jQuery( "#ac_events_loader" ).hide();
				  jQuery('#Fb-2').trigger('click')
                if ( 'success' == response.data.status || response.data.fb_templates.length > 0 ) {
					if ( add ){
						self.message_saved = wcap_strings.template_added;
					} else {
						self.message_saved = wcap_strings.template_updated;	
					}
                    self.saving = false;
                    self.saved_message = true;
					
					self.settings.fb_templates  =  response.data.fb_templates;
					self.settings.total_items    =  response.data.total_items;
										
                    setTimeout(() => {
                        var elmnt = document.getElementById("save_message");
                        elmnt.scrollIntoView( { behavior: "smooth", block: 'end' } ) });
                }
            })
            .catch(function (error) {
            });
			
			
			
		},		
		toggle_bulk_select : function ( row_id ) {
			
			this.bulk_selected_ids[row_id] = this.bulk_selected_ids[row_id] ? false : true;
						
		},
		bulk_select_ids : function ( select_all ) {
			
			for ( var i in this.settings.fb_templates ) {				
				row_id = this.settings.fb_templates[i].id;				
				this.bulk_selected_ids[row_id] = select_all;
			}			
		},
		delete_template : function( row_id ) {
			
			var req  = confirm( wcap_strings.confirm_delete );			
			if ( ! req ) { return; }
			this.bulk_selected_ids[row_id] =  'true';
			this.wcap_action = 'wcap_delete_template';
			this.bulk_delete();
			this.wcap_action = '';
			
		},
		bulk_action: function( ) {
			
			if ( this.wcap_action == '' ) {
				alert( wcap_strings.chose_action ); return;
			}
			if ( 'wcap_delete_template' === this.wcap_action ) {
				
				var req  = confirm( wcap_strings.confirm_delete );			
				if ( ! req ) { return; }				
				this.bulk_delete();
			}
		},
		bulk_delete : function() {
			
			var self = this;
				
			this.saved_message = false;
			this.saving        = true;
			this.message       = wcap_strings.deleting_template;
			jQuery( "#ac_events_loader" ).show();
				
			const data = new FormData();
			for ( var i in this.bulk_selected_ids )	{
				if ( 'true' == this.bulk_selected_ids[i] ) {					
					data.append('template_id[]', i );
				}
			}
			
			data.append( 'action', this.wcap_action );
			data.append( 'template_type', 'fb' );
			data.append( 'security', wcap_strings.wcap_settings_nonce );
			
              axios.post( ajaxurl, data )
			  .then(function (response) {
				  jQuery( "#ac_events_loader" ).hide();
                if ( 'success' == response.data || response.data.fb_templates.length > 0 ) {
					
					self.message_saved = wcap_strings.template_deleted;
					self.saving = false;
					self.saved_message = true;
					
					self.settings.fb_templates  =  response.data.fb_templates;
					self.settings.total_items    =  response.data.total_items;
					
                    setTimeout(() => {
                       var elmnt = document.getElementById("save_message");
                       elmnt.scrollIntoView( { behavior: "smooth", block: 'end' } ) 
					   });
                }
            })
            .catch(function (error) {
            });
		},
		delete_row: function ( index, row ) {
			this.settings.fb_templates.splice( index, 1 )
		}
    }
})

const routes = [ {
		path: '/',
		name: 'email_templates',
		component: email_templates
	},
	{
		path: '/sms_notifications',
		name: 'sms_notifications',
		component: sms_notifications
	},	
	{
		path: '/whatsapp_notifications',
		name: 'whatsapp_notifications',
		component: whatsapp_notifications
	},
	{
		path: '/facebook_messenger',
		name: 'facebook_messenger',
		component: facebook_messenger
	}
];

const router = new VueRouter( {
	routes
} );

new Vue( {
	el: "#secondary-nav-wrap",
	id: '#wcap_settings_tab',
	data: {
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
				id: 'whatsapp_notifications',
				text: "WhatsApp Notifications"
			},
			{
				id: 'facebook_messenger',
				text: "Facebook Messenger Templates"
			}
		],
		settings: {}
	},
	mounted: function() {
		if ( '' !== location.hash ) {
			var sub_router = location.hash.substr(2);
			if ( 'sms_notifications' === sub_router || 'facebook_messenger' === sub_router || 'whatsapp_notifications' === sub_router ) {
				this.currentSettingsTab = sub_router ;
			}
		}
    },
	router: router
})