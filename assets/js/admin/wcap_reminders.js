Vue.use( VueRouter );

var email_reminders = Vue.component( "email_reminders", {
    template: '#email_reminders',
	data() {
		return {
			settings:{},
			saving: false,
			saved_message: false,		
			message: wcap_strings.loading_message,
			message_saved:wcap_strings.message_saved,
			duration_range_select: wcap_strings.duration_range_select,
			duration_select : wcap_strings.wcap_filter_data.duration_select,
			start_date : wcap_strings.wcap_filter_data.start_date,
			hidden_start : wcap_strings.wcap_filter_data.hidden_start,
			end_date : wcap_strings.wcap_filter_data.end_date,
			hidden_end : wcap_strings.wcap_filter_data.hidden_end,
			start_end_dates: wcap_strings.start_end_dates,			
			popup_id:'',
			recovered_order_text:'',
			popup_html:''			
		}
    },    
    mounted: function() {
        var self = this;
		
		jQuery( "#ac_events_loader" ).show();
		
		var data = new FormData();
		data.append( 'action', 'ac_get_email_reminders' );
		data.append( 'security', wcap_strings.wcap_settings_nonce);
        axios.post( ajaxurl, data )
            .then( function (response) {
                self.settings  = response.data;
				jQuery( "#ac_events_loader" ).hide();
				self.load_dates( self.duration_select );
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
			data.append( 'id', id );			
			data.append( 'action', 'wcap_toggle_atc_enable_status' );	
			new_state = 'off';
			if ( val == '1' ) {
				new_state = 'on';
			}
			data.append( 'new_state', new_state );			
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
		get_paginated_data : function( paged, disabled ='' ) {
			
		if ( 'disabled' === disabled ) {
			return false;
		}
		var self  = this;
		
		const data = new FormData();
		
		data.append( 'paged', paged );
		data.append( 'action', 'ac_get_email_reminders' );
		data.append( 'security', wcap_strings.wcap_settings_nonce);
		
		this.saving = true;
		this.message = wcap_strings.loading_message;			
		jQuery( "#ac_events_loader" ).show();
		
		 axios.post( ajaxurl, data )
			  .then(function (response) {
				  jQuery( "#ac_events_loader" ).hide();
                 if ( 'success' == response.data || 'undefined' !== typeof( response.data.email_reminders ) ) {				
					
					self.saving = false;									
					self.settings =  response.data;	
				}
            })
            .catch( function (error) {
				  jQuery( "#ac_events_loader" ).hide();
            } );
		},
		set_pop_up_data : function ( row ) {
			
			var self = this;
			this.popup_html = '';
			self.popup_id = row.abandoned_order_id;
			self.popup_status = row.status;
			
			this.popup_view = 'show_cart';
				
			this.saved_message = false;
			this.saving        = true;
			this.message       = wcap_strings.loading_message;
			jQuery( "#ac_events_loader" ).show();
			
			const data = new FormData();
			data.append( 'action', 'wcap_abandoned_cart_info' );
			data.append( 'security', wcap_strings.wcap_settings_nonce );
			data.append( 'wcap_cart_id', row.abandoned_order_id );
			data.append( 'wcap_current_page', 'send_email' );
			
            axios.post( ajaxurl, data )
			 .then(function (response) {
				  jQuery( "#ac_events_loader" ).hide();
                if ( response.data ) {				
					self.popup_html = response.data;					
				}
            })
            .catch(function (error) {
				jQuery( "#ac_events_loader" ).hide();
            });
		},
		filter_orders : function( ev ) {
			
			var self = this;
			const data = new FormData();
			
			data.append( 'action', 'ac_get_email_reminders' );
			data.append( 'security', wcap_strings.wcap_settings_nonce);
			
			data.append( 'duration_select', this.duration_select );
			
			if ( '' === this.start_date || '' === this.end_date ) {
				this.load_dates( this.duration_select );
			} 
			if ( '' !== this.start_date ) {
			data.append( 'start_date', this.start_date );
			var start_date = new Date(this.start_date);
			data.append( 'hidden_start', start_date.getDate() + '-' + ( start_date.getMonth() + 1 ) + '-' + start_date.getFullYear() );
			}
			if ( '' !== this.end_date ) {
			data.append( 'end_date', this.end_date );
			var end_date = new Date(this.end_date);
			data.append( 'hidden_end', end_date.getDate() + '-' + ( end_date.getMonth() + 1 ) + '-' + end_date.getFullYear() );
			}	

			this.saving = true;
			this.message = wcap_strings.loading_message;			
			jQuery( "#ac_events_loader" ).show();			
			
              axios.post( ajaxurl, data )
			  .then(function (response) {
				  jQuery( "#ac_events_loader" ).hide();
                 if ( 'success' == response.data || 'undefined' !== typeof( response.data.email_reminders ) ) {			
					
					self.saving = false;							
					self.settings =  response.data;
					if ( response.data.redirected_to_all ) {
						self.section = 'wcap_all_abandoned';
					}
					
				}
            })
            .catch(function (error) {
				jQuery( "#ac_events_loader" ).hide();
            });
			
		},
		load_dates : function( duration_select ) {
			
			var start_date    = new Date(this.start_end_dates[duration_select].start_date);
			var end_date    = new Date(this.start_end_dates[duration_select].end_date);
			
			this.start_date   = start_date.getFullYear() + '-' + ( ("0" + (start_date.getMonth() + 1)).slice(-2) ) + '-' + ("0" + (start_date.getDate())).slice(-2);
			this.hidden_start = ("0" + (start_date.getDate())).slice(-2) + '-' + ( ("0" + (start_date.getMonth() + 1)).slice(-2) ) + '-' + start_date.getFullYear();
			
			this.end_date   = end_date.getFullYear() + '-' + ( ("0" + (end_date.getMonth() + 1)).slice(-2) ) + '-' + ("0" + (end_date.getDate())).slice(-2);
			this.hidden_end = ("0" + (end_date.getDate())).slice(-2) + '-' + ( ("0" + (end_date.getMonth() + 1)).slice(-2) ) + '-' + end_date.getFullYear();
			
			
		},
    }
})


var sms_reminders = Vue.component( "sms_reminders", {
    template: '#sms_reminders',
	data() {
		return {
			settings:{},
			saving: false,
			saved_message: false,		
			message: wcap_strings.loading_message,
			message_saved:wcap_strings.message_saved,
			popup_data:{},
			duration_range_select: wcap_strings.duration_range_select,
			duration_select : wcap_strings.wcap_filter_data.duration_select,
			start_date : wcap_strings.wcap_filter_data.start_date,
			hidden_start : wcap_strings.wcap_filter_data.hidden_start,
			end_date : wcap_strings.wcap_filter_data.end_date,
			hidden_end : wcap_strings.wcap_filter_data.hidden_end,
			start_end_dates: wcap_strings.start_end_dates,			
			popup_id:'',
			recovered_order_text:'',
			popup_html:''
		}
    },    
    mounted: function() {
        var self = this;
		
		jQuery( "#ac_events_loader" ).show();
		
		var data = new FormData();
		data.append( 'action', 'ac_get_sms_reminders' );
		data.append( 'security', wcap_strings.wcap_settings_nonce);
        axios.post( ajaxurl, data )
            .then( function (response) {
                self.settings  = response.data;
				jQuery( "#ac_events_loader" ).hide();
				self.load_dates( self.duration_select );
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
			data.append( 'id', id );			
			data.append( 'action', 'wcap_toggle_atc_enable_status' );	
			new_state = 'off';
			if ( val == '1' ) {
				new_state = 'on';
			}
			data.append( 'new_state', new_state );			
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
        get_paginated_data : function( paged, disabled ='' ) {
			
		if ( 'disabled' === disabled ) {
			return false;
		}
		var self  = this;
		
		const data = new FormData();
		
		data.append( 'paged', paged );
		data.append( 'action', 'ac_get_sms_reminders' );
		
		this.saving = true;
		this.message = wcap_strings.loading_message;			
		jQuery( "#ac_events_loader" ).show();
		
		 axios.post( ajaxurl, data )
			  .then(function (response) {
				  jQuery( "#ac_events_loader" ).hide();
                 if ( 'success' == response.data || 'undefined' !== typeof( response.data.sms_reminders ) ) {				
					
					self.saving = false;									
					self.settings =  response.data;	
				}
            })
            .catch( function (error) {
				  jQuery( "#ac_events_loader" ).hide();
            } );
		},
		set_pop_up_data : function ( row ) {
			
			var self = this;
			this.popup_html = '';
			self.popup_id = row.abandoned_order_id;
			self.popup_status = row.status;
			
			this.popup_view = 'show_cart';
				
			this.saved_message = false;
			this.saving        = true;
			this.message       = wcap_strings.loading_message;
			jQuery( "#ac_events_loader" ).show();
			
			const data = new FormData();
			data.append( 'action', 'wcap_abandoned_cart_info' );
			data.append( 'security', wcap_strings.wcap_settings_nonce );
			data.append( 'wcap_cart_id', row.abandoned_order_id );
			data.append( 'wcap_current_page', 'send_email' );
			
            axios.post( ajaxurl, data )
			 .then(function (response) {
				  jQuery( "#ac_events_loader" ).hide();
                if ( response.data ) {				
					self.popup_html = response.data;					
				}
            })
            .catch(function (error) {
				jQuery( "#ac_events_loader" ).hide();
            });
		},
		filter_orders : function( ev ) {
			
			var self = this;
			const data = new FormData();
			
			data.append( 'action', 'ac_get_sms_reminders' );
			data.append( 'security', wcap_strings.wcap_settings_nonce);
			
			data.append( 'duration_select', this.duration_select );
			if ( '' !== this.start_date ) {
			data.append( 'start_date', this.start_date );
			var start_date = new Date(this.start_date);
			data.append( 'hidden_start', start_date.getDate() + '-' + ( start_date.getMonth() + 1 ) + '-' + start_date.getFullYear() );
			}
			if ( '' !== this.end_date ) {
			data.append( 'end_date', this.end_date );
			var end_date = new Date(this.end_date);
			data.append( 'hidden_end', end_date.getDate() + '-' + ( end_date.getMonth() + 1 ) + '-' + end_date.getFullYear() );
			}	

			this.saving = true;
			this.message = wcap_strings.loading_message;			
			jQuery( "#ac_events_loader" ).show();			
			
              axios.post( ajaxurl, data )
			  .then(function (response) {
				  jQuery( "#ac_events_loader" ).hide();
                 if ( 'success' == response.data || 'undefined' !== typeof( response.data.sms_reminders ) ) {			
					
					self.saving = false;							
					self.settings =  response.data;
					if ( response.data.redirected_to_all ) {
						self.section = 'wcap_all_abandoned';
					}
					
				}
            })
            .catch(function (error) {
				jQuery( "#ac_events_loader" ).hide();
            });
			
		},
		load_dates : function( duration_select ) {
			
			var start_date    = new Date(this.start_end_dates[duration_select].start_date);
			var end_date    = new Date(this.start_end_dates[duration_select].end_date);
			
			this.start_date   = start_date.getFullYear() + '-' + ( ("0" + (start_date.getMonth() + 1)).slice(-2) ) + '-' + ("0" + (start_date.getDate())).slice(-2);
			this.hidden_start = ("0" + (start_date.getDate())).slice(-2) + '-' + ( ("0" + (start_date.getMonth() + 1)).slice(-2) ) + '-' + start_date.getFullYear();
			
			this.end_date   = end_date.getFullYear() + '-' + ( ("0" + (end_date.getMonth() + 1)).slice(-2) ) + '-' + ("0" + (end_date.getDate())).slice(-2);
			this.hidden_end = ("0" + (end_date.getDate())).slice(-2) + '-' + ( ("0" + (end_date.getMonth() + 1)).slice(-2) ) + '-' + end_date.getFullYear();
			
			
		},
    }
})

var whatsapp_reminders = Vue.component( "whatsapp_reminders", {
    template: '#whatsapp_reminders',
	data() {
		return {
			settings:{},
			saving: false,
			saved_message: false,		
			message: wcap_strings.loading_message,
			message_saved:wcap_strings.message_saved,
			popup_data:{},
			duration_range_select: wcap_strings.duration_range_select,
			duration_select : wcap_strings.wcap_filter_data.duration_select,
			start_date : wcap_strings.wcap_filter_data.start_date,
			hidden_start : wcap_strings.wcap_filter_data.hidden_start,
			end_date : wcap_strings.wcap_filter_data.end_date,
			hidden_end : wcap_strings.wcap_filter_data.hidden_end,
			start_end_dates: wcap_strings.start_end_dates,			
			popup_id:'',
			recovered_order_text:'',
			popup_html:''
		}
    },    
    mounted: function() {
        var self = this;
		
		jQuery( "#ac_events_loader" ).show();
		
		var data = new FormData();
		data.append( 'action', 'ac_get_whatsapp_reminders' );
		data.append( 'security', wcap_strings.wcap_settings_nonce);
        axios.post( ajaxurl, data )
            .then( function (response) {
                self.settings  = response.data;
				jQuery( "#ac_events_loader" ).hide();
				self.load_dates( self.duration_select );
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
			data.append( 'id', id );			
			data.append( 'action', 'wcap_toggle_atc_enable_status' );	
			new_state = 'off';
			if ( val == '1' ) {
				new_state = 'on';
			}
			data.append( 'new_state', new_state );			
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
        get_paginated_data : function( paged, disabled ='' ) {
			
		if ( 'disabled' === disabled ) {
			return false;
		}
		var self  = this;
		
		const data = new FormData();
		
		data.append( 'paged', paged );
		data.append( 'action', 'ac_get_whatsapp_reminders' );
		data.append( 'security', wcap_strings.wcap_settings_nonce);
		
		this.saving = true;
		this.message = wcap_strings.loading_message;			
		jQuery( "#ac_events_loader" ).show();
		
		 axios.post( ajaxurl, data )
			  .then(function (response) {
				  jQuery( "#ac_events_loader" ).hide();
                 if ( 'success' == response.data || 'undefined' !== typeof( response.data.whatsapp_reminders ) ) {				
					
					self.saving = false;									
					self.settings =  response.data;	
				}
            })
            .catch( function (error) {
				  jQuery( "#ac_events_loader" ).hide();
            } );
		},
		set_pop_up_data : function ( row ) {
			
			var self = this;
			this.popup_html = '';
			self.popup_id = row.abandoned_order_id;
			self.popup_status = row.status;
			
			this.popup_view = 'show_cart';
				
			this.saved_message = false;
			this.saving        = true;
			this.message       = wcap_strings.loading_message;
			jQuery( "#ac_events_loader" ).show();
			
			const data = new FormData();
			data.append( 'action', 'wcap_abandoned_cart_info' );
			data.append( 'security', wcap_strings.wcap_settings_nonce );
			data.append( 'wcap_cart_id', row.abandoned_order_id );
			data.append( 'wcap_current_page', 'send_email' );
			
            axios.post( ajaxurl, data )
			 .then(function (response) {
				  jQuery( "#ac_events_loader" ).hide();
                if ( response.data ) {				
					self.popup_html = response.data;					
				}
            })
            .catch(function (error) {
				jQuery( "#ac_events_loader" ).hide();
            });
		},
		filter_orders : function( ev ) {
			
			var self = this;
			const data = new FormData();
			
			data.append( 'action', 'ac_get_whatsapp_reminders' );
			data.append( 'security', wcap_strings.wcap_settings_nonce);
			
			data.append( 'duration_select', this.duration_select );
			if ( '' !== this.start_date ) {
			data.append( 'start_date', this.start_date );
			var start_date = new Date(this.start_date);
			data.append( 'hidden_start', start_date.getDate() + '-' + ( start_date.getMonth() + 1 ) + '-' + start_date.getFullYear() );
			}
			if ( '' !== this.end_date ) {
			data.append( 'end_date', this.end_date );
			var end_date = new Date(this.end_date);
			data.append( 'hidden_end', end_date.getDate() + '-' + ( end_date.getMonth() + 1 ) + '-' + end_date.getFullYear() );
			}	

			this.saving = true;
			this.message = wcap_strings.loading_message;			
			jQuery( "#ac_events_loader" ).show();			
			
              axios.post( ajaxurl, data )
			  .then(function (response) {
				  jQuery( "#ac_events_loader" ).hide();
                 if ( 'success' == response.data || 'undefined' !== typeof( response.data.whatsapp_reminders ) ) {			
					
					self.saving = false;							
					self.settings =  response.data;
					if ( response.data.redirected_to_all ) {
						self.section = 'wcap_all_abandoned';
					}
					
				}
            })
            .catch(function (error) {
				jQuery( "#ac_events_loader" ).hide();
            });
			
		},
		load_dates : function( duration_select ) {
			
			var start_date    = new Date(this.start_end_dates[duration_select].start_date);
			var end_date    = new Date(this.start_end_dates[duration_select].end_date);
			
			this.start_date   = start_date.getFullYear() + '-' + ( ("0" + (start_date.getMonth() + 1)).slice(-2) ) + '-' + ("0" + (start_date.getDate())).slice(-2);
			this.hidden_start = ("0" + (start_date.getDate())).slice(-2) + '-' + ( ("0" + (start_date.getMonth() + 1)).slice(-2) ) + '-' + start_date.getFullYear();
			
			this.end_date   = end_date.getFullYear() + '-' + ( ("0" + (end_date.getMonth() + 1)).slice(-2) ) + '-' + ("0" + (end_date.getDate())).slice(-2);
			this.hidden_end = ("0" + (end_date.getDate())).slice(-2) + '-' + ( ("0" + (end_date.getMonth() + 1)).slice(-2) ) + '-' + end_date.getFullYear();
			
			
		},
    }
})

const routes = [ {
		path: '/',
		name: 'email_reminders',
		component: email_reminders
	},
	{
		path: '/sms_reminders',
		name: 'sms_reminders',
		component: sms_reminders
	},
	{
		path: '/whatsapp_reminders',
		name: 'whatsapp_reminders',
		component: whatsapp_reminders
	},
];

const router = new VueRouter( {
	routes
} );

new Vue( {
	el: "#secondary-nav-wrap",
	id: '#wcap_reminders_page',
	data: {
		currentSettingsTab: 'email_reminders',
		settings_tabs: [ {
				id: 'email_reminders',
				text: "Email Sent"
			},
			{
				id: 'sms_reminders',
				text: "SMS Sent"
			},
			{
				id: 'whatsapp_reminders',
				text: "WhatsApp Sent"
			}
		],
		settings: {}
	},
	mounted: function() {
		if ( '' !== location.hash ) {
			var sub_router = location.hash.substr(2);
			if ( 'email_reminders' === sub_router || 'sms_reminders' === sub_router || 'whatsapp_reminders' === sub_router ) {
				this.currentSettingsTab = sub_router ;
			}
		}
    },
	router: router
})