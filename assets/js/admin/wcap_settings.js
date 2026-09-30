Vue.use( VueRouter );
var settings_data   = null;
var connectors_data = null;
var templates_data  = null;

var general_section = Vue.component( "general_section", {
    template: '#general_settings',
	data() {
		return {
			settings:{
				'wcap_email_admin_cart_source': {}
			},
			saving: false,
			saved_message: false,		
			message: wcap_strings.loading_message,
			message_saved:wcap_strings.message_saved,
			restriced_countries: [],
		}
    },    
    mounted: function() {
		
		
		var self = this;
		jQuery('#wcap_restrict_countries').selectWoo();
		
		if ( null  !== window.settings_data ) {
			self.settings = window.settings_data;
			return;
		}
		
		jQuery( "#ac_events_loader" ).show();
        
		var data = new FormData();
		data.append( 'action', 'wcap_get_settings' );
		data.append( 'security', wcap_strings.wcap_settings_nonce );
				
        axios.post( ajaxurl, data )
            .then( function (response) {				
                self.settings = window.settings_data = response.data;				
				self.restriced_countries = self.settings.wcap_restrict_countries.split(',');				
				jQuery('#wcap_restrict_countries').val(self.restriced_countries).change();
				jQuery( "#ac_events_loader" ).hide();
            })
            .catch(function (error) {
				jQuery( "#ac_events_loader" ).hide();
            });
    },
	
    methods: {
        save_general_settings: function( parent_key = '' ) {
            this.saved_message = false;
            this.saving = true;
            let self = this;

            var data = new FormData();
            
			for ( let key in this.settings ) {
				if ( key.includes('/') ) {
					key = key.replaceAll('/', '__');
				}
				if( '' === parent_key ) {
					if ( 'wcap_email_admin_cart_source' === key ) {
						var list = JSON.stringify( self.settings.wcap_email_admin_cart_source );
						data.append( key, list );
					} else {
						if ( jQuery( '#' + key ).length > 0 ) {
							if ( 'wcap_unsubscribe_custom_wp_page' === key ) {
								this.settings[key] = jQuery( '#wcap_unsubscribe_custom_wp_page' ).val();
							}
							if ( 'wcap_restrict_countries' === key ) {
								this.settings[key] = jQuery( '#wcap_restrict_countries' ).val();
							}
							if ( 'wcap_unsubscribe_custom_content' === key ) {
								if ( jQuery( "#wp-wcap_unsubscribe_custom_content-wrap" ).hasClass( "tmce-active" ) ) {
									this.settings[key] = tinyMCE.get( 'wcap_unsubscribe_custom_content' ).getContent();
								} else {
									this.settings[key] = jQuery( '#wcap_unsubscribe_custom_content' ).val();
								}
							}
							data.append( key, this.settings[key] );
						}
					}
				}
				else {
					if ( 'wcap_email_admin_cart_source' === key ) {
						var list = JSON.stringify( self.settings.wcap_email_admin_cart_source );
						data.append( key, list );
						
					} else {
						if ( jQuery('#' + key, jQuery('#' + parent_key ) ).length > 0 ) {
							val = this.settings[key];
							if ( 'wcap_unsubscribe_custom_wp_page' === key ) {
								val = jQuery('#wcap_unsubscribe_custom_wp_page').val();
							}
							if ( 'wcap_restrict_countries' === key ) {
								val =  jQuery('#wcap_restrict_countries').val();
							}
							if ( 'wcap_unsubscribe_custom_content' === key ) {
								if ( jQuery("#wp-wcap_unsubscribe_custom_content-wrap").hasClass( "tmce-active" ) ) {
									val =  tinyMCE.get('wcap_unsubscribe_custom_content').getContent();
								} else {
									val =  jQuery('#wcap_unsubscribe_custom_content').val();
								}
							}
							data.append( key, val );
						}
					}
				}
			}
            data.append( 'action', 'wcap_save_settings');
            data.append( 'security', wcap_strings.wcap_settings_nonce );
			this.message = wcap_strings.saving_message;
			jQuery( "#ac_events_loader" ).show();
			var source = true;
			if ( 'on' === self.settings.wcap_email_admin_on_abandonment ) {
				source = false;
				var list = Object.values( self.settings.wcap_email_admin_cart_source );

				if ( list.indexOf('on') > -1 ) {
					source = true;
				}
			}
			var ac_delete_abandoned_order_days = parseInt(jQuery('#ac_delete_abandoned_order_days').val());
			if ( typeof ac_delete_abandoned_order_days !== 'number' || ac_delete_abandoned_order_days <= 0 || ! ac_delete_abandoned_order_days )
			{
				jQuery('#ac_delete_abandoned_order_days').val('365');
				jQuery( "#ac_events_loader" ).hide();
				jQuery(".alert-success").css("background-color","#f1213e");
				self.message_saved = wcap_strings.oldCartsValidationError;
				self.saving = false;
                self.saved_message = true;
                setTimeout(() => {
                    var elmnt = document.getElementById("save_message");
                    elmnt.scrollIntoView( { behavior: "smooth", block: 'end' } ) });
			} else if ( ! source ) {
				jQuery('#ac_delete_abandoned_order_days').val('365');
				jQuery( "#ac_events_loader" ).hide();
				jQuery(".alert-success").css("background-color","#f1213e");
				self.message_saved = wcap_strings.cartSourceValidationError;
				self.saving = false;
				self.saved_message = true;
				setTimeout(() => {
					var elmnt = document.getElementById("save_message");
					elmnt.scrollIntoView( { behavior: "smooth", block: 'end' } ) });
			} else {
				axios.post( ajaxurl, data )
              		.then(function (response) {
				  	jQuery( "#ac_events_loader" ).hide();
                	if ( 'success' == response.data ) {
                		jQuery(".alert-success").css("background-color","#d4edda");
						self.message_saved = wcap_strings.message_saved;
                    	self.saving = false;
                    	self.saved_message = true;
                    setTimeout(() => {
                        var elmnt = document.getElementById("save_message");
                        elmnt.scrollIntoView( { behavior: "smooth", block: 'end' } ) });
            	} })
            	.catch(function (error) {
            	});
            }
			if ( 'on' === this.settings.wcap_auto_login_users ) {
				jQuery( "#ac_events_loader" ).show();
				setTimeout( () => {
    				location.reload();
				}, 1000 );
			}
        },

		process_source_all: function() {
			let self = this;

			if ( 'on' === self.settings.wcap_email_admin_cart_source.all ) {
				self.settings.wcap_email_admin_cart_source.checkout    = 'on';
				self.settings.wcap_email_admin_cart_source.profile     = 'on';
				self.settings.wcap_email_admin_cart_source.atc         = 'on';
				self.settings.wcap_email_admin_cart_source.exit_intent = 'on';
				self.settings.wcap_email_admin_cart_source.url         = 'on';
				self.settings.wcap_email_admin_cart_source.custom_form = 'on';
			} else if ( '' === self.settings.wcap_email_admin_cart_source.all ) {
				self.settings.wcap_email_admin_cart_source.checkout    = '';
				self.settings.wcap_email_admin_cart_source.profile     = '';
				self.settings.wcap_email_admin_cart_source.atc         = '';
				self.settings.wcap_email_admin_cart_source.exit_intent = '';
				self.settings.wcap_email_admin_cart_source.url         = '';
				self.settings.wcap_email_admin_cart_source.custom_form = '';
			}

		},

		process_source: function() {
			let self = this;

			if ( '' === self.settings.wcap_email_admin_cart_source.all && 'on' === self.settings.wcap_email_admin_cart_source.checkout && 'on' === self.settings.wcap_email_admin_cart_source.profile && 'on' === self.settings.wcap_email_admin_cart_source.atc && 'on' === self.settings.wcap_email_admin_cart_source.exit_intent && 'on' === self.settings.wcap_email_admin_cart_source.url && 'on' === self.settings.wcap_email_admin_cart_source.custom_form ) {
				self.settings.wcap_email_admin_cart_source.all = 'on';
			} else if ( 'on' === self.settings.wcap_email_admin_cart_source.all && ( '' === self.settings.wcap_email_admin_cart_source.checkout || '' === self.settings.wcap_email_admin_cart_source.profile || '' === self.settings.wcap_email_admin_cart_source.atc || '' === self.settings.wcap_email_admin_cart_source.exit_intent || '' === self.settings.wcap_email_admin_cart_source.url || '' === self.settings.wcap_email_admin_cart_source.custom_form ) ) {
				self.settings.wcap_email_admin_cart_source.all = '';
			}
		},

		reset_usage_tracking: function() {
			let self = this;
			this.message = wcap_strings.tracking_reset_warn;
			jQuery( "#ac_events_loader" ).show();
			axios.get( wcap_strings.admin_url + '/admin.php?page=woocommerce_ac_page&action=emailsettings&ts_action=reset_tracking' )
			  .then(function (response) {
				  jQuery( "#ac_events_loader" ).hide();
                if ( 200 == response.status ) {
					self.message_saved = wcap_strings.tracking_reset;
                    self.saving = false;
                    self.saved_message = true;
                    setTimeout(() => {
                        var elmnt = document.getElementById("save_message");
                        elmnt.scrollIntoView( { behavior: "smooth", block: 'end' } ) });
					location.reload();
                }
            })
            .catch(function (error) {
            });
		},
		disable_atc_popup( event ) {
			if (jQuery('#ac_disable_guest_cart_email').is(':checked')) {
				jQuery.post( ajaxurl, {
					action    : 'wcap_is_atc_enable',
				}, function( wcap_is_atc_enable ) {
					if ( 'on' == wcap_is_atc_enable.on ) {
						jQuery( "#wcap_atc_disable_msg" ).html( wcap_is_atc_enable.error );
						jQuery("#wcap_atc_disable_msg" ).css({ 'color': 'red' });

			            jQuery( "#wcap_atc_disable_msg" ).fadeIn();
			            setTimeout( function(){jQuery( "#wcap_atc_disable_msg" ).fadeOut();},3000);		
					}
				});
			}
		},

		delete_coupons_manually: function() {

		
		let self = this;
		this.message = wcap_strings.coupons_delete_message;
		var data = new FormData();
		var status 	= confirm( wcap_strings.coupon_delete_message );
        if ( status == true ) {
        	// disable delete button and show loader			
			
			data.append('action', 'wcap_delete_expired_used_coupon_code');
			jQuery( "#ac_events_loader" ).show();

			axios.post( ajaxurl, data )
              .then(function (response) {
				  jQuery( "#ac_events_loader" ).hide();
                if ( response.data.success ) {
					self.message_saved = wcap_strings.coupons_deleted;
                    self.saving = false;
                    self.saved_message = true;
                    setTimeout(() => {
                        var elmnt = document.getElementById("save_message");
                        elmnt.scrollIntoView( { behavior: "smooth", block: 'end' } ) });
                }
            })
            .catch(function (error) {
            });

        }

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
		}
    },    
    mounted: function() {
        var self = this;
		if ( null  !== window.settings_data ) {
			self.settings = window.settings_data;
			return;
		}
		jQuery( "#ac_events_loader" ).show();
		var data = new FormData();
		data.append( 'action', 'wcap_get_settings' );
		data.append( 'security', wcap_strings.wcap_settings_nonce );
        axios.post( ajaxurl, data )
            .then( function (response) {
                self.settings  = window.settings_data = response.data;
				jQuery( "#ac_events_loader" ).hide();
            })
            .catch(function (error) {
				jQuery( "#ac_events_loader" ).hide();
            });
    },
	methods: {
        save_fb_settings: function() {
            this.saved_message = false;
            this.saving = true;
            let self = this;

            var data = new FormData();

            
			for ( let key in this.settings ) {
				if ( key.includes('/') ) {
					key = key.replaceAll('/', '__');
				}
				if ( jQuery('#' + key, jQuery('#fb_cover') ).length > 0 ) {
					data.append( key, this.settings[key] );
				}
			}
            data.append( 'action', 'wcap_save_settings');
            data.append( 'security', wcap_strings.wcap_settings_nonce );
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
        }
    }
})


var sms = Vue.component( "sms", {
    template: '#sms',
	data() {
		return {
			settings:{},
			saving: false,
			saved_message: false,		
			message: wcap_strings.loading_message,
			message_saved:wcap_strings.message_saved,
			sms_alert:wcap_strings.sms_alert,
			show_sms_alert: true,
		}
    },    
    mounted: function() {
        var self = this;
		if ( null  !== window.settings_data ) {
			self.settings = window.settings_data;
			return;
		}
		jQuery( "#ac_events_loader" ).show();
		var data = new FormData();
		data.append( 'action', 'wcap_get_settings' );
		data.append( 'security', wcap_strings.wcap_settings_nonce );
        axios.post( ajaxurl, data )
            .then( function (response) {
                self.settings  = window.settings_data = response.data;
                self.settings['test_msg'] = wcap_strings.test_msg; 
				jQuery( "#ac_events_loader" ).hide();				
            })
            .catch(function (error) {
				jQuery( "#ac_events_loader" ).hide();
            });
    },
	methods: {
        save_sms_settings: function() {
            this.saved_message = false;
            this.saving = true;
            let self = this;

            var data = new FormData();

            
			for ( let key in this.settings ) {
				if ( key.includes('/') ) {
					key = key.replaceAll('/', '__');
				}
				if ( jQuery('#' + key, jQuery('#sms_cover') ).length > 0 ) {
					data.append( key, this.settings[key] );
				}
			}
            data.append( 'action', 'wcap_save_settings');
            data.append( 'security', wcap_strings.wcap_settings_nonce );
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
		 send_test_sms: function() {
            this.show_sms_alert = false;
            this.saving = true;
            let self = this;

            var data = new FormData();
			data.append( 'number', this.settings[ 'test_number' ] );
			data.append( 'msg', this.settings[ 'test_msg' ] );

            data.append('action', 'wcap_test_sms');

			this.message = wcap_strings.send_test_sms;
			jQuery( "#ac_events_loader" ).show();
            axios.post( ajaxurl, data )
              .then(function (response) {
				  jQuery( "#ac_events_loader" ).hide();
                if ( '' !== response.data ) {
					self.sms_alert = response.data;
                    self.saving = false;
                    self.show_sms_alert = true;
                    setTimeout( function() {
                    	self.show_sms_alert = false;
                    }, 9000);             
                }
            })
            .catch(function (error) {
            });
        }
    }
})

var whatsapp = Vue.component( "whatsapp", {
    template: '#whatsapp',
	data() {
		return {
			settings:{},
			saving: false,
			saved_message: false,		
			message: wcap_strings.loading_message,
			message_saved:wcap_strings.message_saved,
			sms_alert:wcap_strings.sms_alert,
		}
    },    
    mounted: function() {
        var self = this;
		if ( null  !== window.settings_data ) {
			self.settings = window.settings_data;
			return;
		}
		jQuery( "#ac_events_loader" ).show();
		var data = new FormData();
		data.append( 'action', 'wcap_get_settings' );
		data.append( 'security', wcap_strings.wcap_settings_nonce );
        axios.post( ajaxurl, data )
            .then( function (response) {
                self.settings  = window.settings_data = response.data;
                self.settings['test_msg'] = wcap_strings.test_msg; 
				jQuery( "#ac_events_loader" ).hide();				
            })
            .catch(function (error) {
				jQuery( "#ac_events_loader" ).hide();
            });
    },
	methods: {
        save_whatsapp_settings: function() {
            this.saved_message = false;
            this.saving = true;
            let self = this;

            var data = new FormData();
            
			for ( let key in this.settings ) {
				if ( key.includes('/') ) {
					key = key.replaceAll('/', '__');
				}
				if ( jQuery('#' + key, jQuery('#whatsapp_cover') ).length > 0 ) {
					data.append( key, this.settings[key] );
				}
			}
            data.append( 'action', 'wcap_save_settings');
            data.append( 'security', wcap_strings.wcap_settings_nonce );
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
    }
})

var email_reports = Vue.component( "email_reports", {
    template: '#email_reports',
	data() {
		return {
			settings:{},
			saving: false,
			saved_message: false,		
			message: wcap_strings.loading_message,
			message_saved:wcap_strings.message_saved,
		}
    },    
    mounted: function() {
        var self = this;
		if ( null  !== window.settings_data ) {
			self.settings = window.settings_data;
			return;
		}
		jQuery( "#ac_events_loader" ).show();
		var data = new FormData();
		data.append( 'action', 'wcap_get_settings' );
		data.append( 'security', wcap_strings.wcap_settings_nonce );
        axios.post( ajaxurl, data )
            .then( function (response) {
                self.settings  = window.settings_data = response.data;
				jQuery( "#ac_events_loader" ).hide();
            })
            .catch(function (error) {
				jQuery( "#ac_events_loader" ).hide();
            });
    },
	methods: {
        save_email_settings: function() {
            this.saved_message = false;
            this.saving = true;
            let self = this;

            var data = new FormData();

            
			for ( let key in this.settings ) {
				if ( key.includes('/') ) {
					key = key.replaceAll('/', '__');
				}
				if ( jQuery('#' + key, jQuery('#email_reports_cover') ).length > 0 ) {
					data.append( key, this.settings[key] );
				}
			}
            data.append( 'action', 'wcap_save_settings');
            data.append( 'security', wcap_strings.wcap_settings_nonce );
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
        }
    }
})

 
if ( wcap_config.show_tabs ) {
	var license = Vue.component( "license", {
		template: '#license',
		data() {
			return {
				settings:{},
				saving: false,
				saved_message: false,		
				message: wcap_strings.loading_message,
				message_saved:wcap_strings.message_saved,
			}
		},    
		mounted: function() {
			var self = this;
			if ( null  !== window.settings_data ) {
				self.settings = window.settings_data;
				return;
			}
			jQuery( "#ac_events_loader" ).show();
			var data = new FormData();
			data.append( 'action', 'wcap_get_settings' );
			data.append( 'security', wcap_strings.wcap_settings_nonce );
			axios.post( ajaxurl, data )
				.then( function (response) {
					self.settings  = window.settings_data = response.data;
					jQuery( "#ac_events_loader" ).hide();
				})
				.catch(function (error) {
					jQuery( "#ac_events_loader" ).show();
				});
		},
		methods: {
			save_license_settings: function() {
	
				var data = new FormData();
				
				for ( let key in this.settings ) {
					if ( key.includes('/') ) {
						key = key.replaceAll('/', '__');
					}
					if ( jQuery('#' + key, jQuery('#license_cover') ).length > 0 ) {
						if ( jQuery('#edd_sample_license_key_ac_woo').val() === '' ) {
							return false;
						}
						data.append( key, this.settings[key] );
					}
				}
				
				this.saved_message = false;
				this.saving        = true;
				let self           = this;
				
				if ( this.settings['edd_sample_license_status_ac_woo'] =='valid' ) {
					data.append( 'action', 'ac_deactivate_license' );
					data.append( 'edd_ac_license_deactivate', 'edd_ac_license_deactivate' );
				} else {
					data.append( 'action', 'ac_activate_license' );
					data.append( 'edd_ac_license_activate', 'edd_ac_license_activate' );
				}
				data.append( 'edd_license_nonce', wcap_strings.edd_license_nonce );
				
				this.message = wcap_strings.saving_message;
				jQuery( "#ac_events_loader" ).show();
				axios.post( ajaxurl, data )
				  .then(function (response) {
					  jQuery( "#ac_events_loader" ).hide();
					if ( 'valid' == response.data ) {
						self.settings.edd_sample_license_status_ac_woo = 'valid';
						self.message_saved = wcap_strings.license_activated;
						self.saving        = false;
						self.saved_message = true;
						setTimeout(() => {
							var elmnt = document.getElementById("save_message");
							elmnt.scrollIntoView( { behavior: "smooth", block: 'end' } )
							location.reload();
						});
					} else {
						self.settings.edd_sample_license_status_ac_woo = 'invalid';
						self.message_saved = wcap_strings.license_deativated;
						self.saving = false;
						self.saved_message = true;
						setTimeout(() => {
							var elmnt = document.getElementById("save_message");
							elmnt.scrollIntoView( { behavior: "smooth", block: 'end' } )
							location.reload();
						});
					}
				})
				.catch(function (error) {
				});
			}
		}
	})
}

var connectors = Vue.component( "connectors", {
    template: '#connectors',
	data() {
		return {
			settings:{},
		}
    }
})


var popup_templates = Vue.component( "popup_templates", {
    template: '#popup_templates',
	data() {
		return {
			settings:{},
			saving: false,
			delete_notification:{},
			saved_message: false,		
			message: wcap_strings.loading_message,
			message_saved:wcap_strings.message_saved,
			popup_data:{},
			bulk_select_popup_ids: [],
			bulk_action: '',
			select_all: false
		}
    },    
    mounted: function() {
        var self = this;
		
		if ( null  !== window.templates_data ) {
			self.settings = window.templates_data;
			return;
		}		
		jQuery( "#ac_events_loader" ).show();
		
		var data = new FormData();
		data.append( 'action', 'ac_get_popup_templates' );
		data.append( 'security', wcap_strings.wcap_settings_nonce );
        axios.post( ajaxurl, data )
            .then( function (response) {
                self.settings  = window.templates_data = response.data;
				
				for ( let row in self.settings.popup_templates ) {
							
								
			}
			
			
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
        delete_atc_template: function( row_id ) {
        	this.saved_message = false;
            this.saving = true;
        	var self = this;
        	var data = new FormData();
        	this.message = wcap_strings.saving_message;
        	data.append( 'id', row_id );
			data.append( 'wcap_section', 'wcap_atc_settings' );
			data.append( 'wcap_mode', 'deleteatctemplate' );
			data.append( 'action', 'wcap_delete_atc_template' );
			data.append( 'security', wcap_strings.wcap_settings_nonce );
			jQuery( "#ac_events_loader" ).show();
			axios.post( ajaxurl, data )
                .then( function (response) {
                	jQuery( "#ac_events_loader" ).hide();
                	if ( response.data.success ) {
                		self.delete_notification = response.data;
	                	self.message_saved = self.delete_notification.success;
	                	console.log(self.message_saved);
	                    self.saving = false;
	                    self.saved_message = true;
	                    setTimeout(() => {
	                        var elmnt = document.getElementById("save_message");
	                        elmnt.scrollIntoView( { behavior: "smooth", block: 'end' } ) });
	                    window.location.reload();
                	}
                        
                })
                .catch(function (error) {
                    console.log(error);
                });

		},
		toggle_delete_checkbox: function( setting_id ) {
			let data = this.bulk_select_popup_ids[ setting_id ];
			data.status = !data.status;
			Vue.set( this.bulk_select_popup_ids, setting_id, data );
		},
		bulk_select_for_delete: function( select_all ) {
			for ( var i in this.settings.popup_templates ) {				
				row_id = this.settings.popup_templates[i].id;
				this.bulk_select_popup_ids[row_id] = select_all;				
			}
		},
		bulk_delete_popup_templates: function() {

			let setting_ids = [];

			this.bulk_select_popup_ids.forEach( function( item, setting_id ) {
				if ( 'true' === item ) {
					setting_ids.push( setting_id );
				}
			} );
			console.log( setting_ids );
			if ( 0 === setting_ids.length ) {
				return;
			}

			var self = this;
			const data = new FormData();
			
			data.append( 'action', 'wcap_delete_atc_template' );
			data.append( 'id', setting_ids );
			data.append( 'security', wcap_strings.wcap_settings_nonce );

			this.saving = true;
			this.message = wcap_strings.message_processing;			
			jQuery( "#ac_events_loader" ).show();
			axios.post( ajaxurl, data )
			  .then(function (response) {
				if ( response.data.success ) {
					self.saved_message = true;
					self.message_saved = response.data.success;
					// Hide the template rows.
					setting_ids.forEach( function( v ) {
						self.settings.popup_templates.forEach( function( val, key ) {
							var i = val.id;
							if ( i == v ) {
								self.settings.popup_templates.splice( key, 1 );		
							}
						});
					});
					Vue.set( self.settings.popup_templates );
					// Remove the loader.
					jQuery( "#ac_events_loader" ).hide();
				} else if ( response.data.error ) {
					self.saved_message = true;
					self.message_saved = response.data.error;
				}
					
			})
			.catch(function (error) {
				jQuery( "#ac_events_loader" ).hide();
			});
			
		},
		set_popup_data : function ( data ) {
			this.popup_data = data;
			if ( this.popup_data.is_active === '' || this.popup_data.is_active === '0' ) {
				this.popup_data.template_status = 'Inactive';
			} else {
				this.popup_data.template_status = 'Active';
			}
		}
    }
})

var import_export = Vue.component("import_export", {
    template: '#import_export',

    data() {
        return {
            settings: {},
            exporting: false,
            export_type: 'all',
            export_settings: {
                general_settings: true,
                gdpr_settings: true,
                email_templates: true,
                sms_settings: true,
                whatsapp_settings: true,
                popup_templates: true,
                restriction_settings: true,
                smtp_connector: true,
                recovery_emails_settings: true,
                sms_templates: true,
                whatsapp_templates:true,
                facebook_templates:true,
            },
            export_setting_title:wcap_strings.export_setting_title,
            exporting_message:wcap_strings.exporting_message,
			import_file: null,
			import_file_data: {},
			preview_sections: [],
			selected_import_sections: [],
			exported_version: '',
			current_version: wcap_strings.current_plugin_version,
			is_compatible: true,
			show_import_preview: false,
			import_mode: 'replace',
			saved_message: false,
			message_saved:wcap_strings.message_saved,
			message: '',
			saving: false,
        }
    },
    computed: {
        selected_settings() {
            return Object.keys(this.export_settings).filter(key => this.export_settings[key]);
        }
    },
    watch: {
	    export_type(newVal) {
	        if (newVal === 'all') {
	            // Check all
	            Object.keys(this.export_settings).forEach(key => {
	                this.export_settings[key] = true;
	            });
	        } else if (newVal === 'specific') {
	            // Uncheck all
	            Object.keys(this.export_settings).forEach(key => {
	                this.export_settings[key] = false;
	            });
	        }
	    }
	},
    methods: {
        export_settings_json() {

            this.exporting = true;// start loading
            this.saving   = true;
            this.message = "Exporting settings";
            let export_data = {
                export_type: this.export_type,
                settings: this.selected_settings,
            };
            axios.post(
				`${wcap_strings.rest_url}export-settings`,
				export_data,
				{
					headers: {
						'X-WP-Nonce': wcap_strings.import_export_nonce
					}
				}
			).then((response) => {

                this.download_json(response.data.data);
                this.exporting = false; // stop loading
                this.saving    = false;

            }).catch((error) => {

                console.log(error);
                this.exporting = false;
                this.saving    = false;

            });
        },
        download_json(data) {
        	const json = JSON.stringify(data, null, 2);
        	const blob = new Blob([json], { type: "application/json" });
        	const url = window.URL.createObjectURL(blob);

	        const link = document.createElement("a");
	        link.href = url;
	        link.download = `wcap-settings-${new Date().toISOString().slice(0,10)}.json`;

	        document.body.appendChild(link);
	        link.click();

	        document.body.removeChild(link);
	        window.URL.revokeObjectURL(url);
    	},
    	handle_file_upload(event) {
		    const file = event.target.files[0];
		    if (!file) return;
		    if (file.type !== "application/json") {
		        alert("Invalid file format.");
		        return;
		    }
		    this.import_file = file;
		},
		preview_import() {
		    if (!this.import_file) {
		        alert("Please upload JSON file.");
		        return;
		    }
		    const reader = new FileReader();
		    reader.onload = (event) => {
		        try {
		            const data = JSON.parse(event.target.result);
		            this.import_file_data = data;
		            this.exported_version = data.plugin_version ?? '';
		            this.check_version_compatibility();
		            this.prepare_preview_sections(data);
		            // OPEN BOOTSTRAP MODAL
		            jQuery('#wcap-import-preview-modal').modal('show');
		        } catch (e) {
		            alert("Invalid JSON file.");
		        }
		    };
		    reader.readAsText(this.import_file);
		},
		check_version_compatibility() {
		    const min_version = '10.2.0';
		    const versionToNumber = (v) => v.split('.').map(n => n.padStart(3,'0')).join('');
		    this.is_compatible = versionToNumber(this.current_version) >= versionToNumber(min_version);
		},
		prepare_preview_sections(data) {
		    const section_map = {
		        general_settings: "General Settings",
		        gdpr_settings: "GDPR Consent Settings",
		        restriction_settings: "Restriction Rules",
		        recovery_emails_settings: "Recovery Emails Settings",
		        sms_settings: "SMS Settings",
		        whatsapp_settings: "WhatsApp Settings",
		        smtp_connector: 'SMTP / Email Connector',
		        email_templates: "Email Templates",
		        sms_templates: "SMS Templates",
		        whatsapp_templates: "WhatsApp Templates",
		        fb_templates: "Facebook Messenger Templates",
		        popup_templates: "Popup Templates (Add to Cart)",
		    };
		    this.preview_sections = [];
		    this.selected_import_sections = [];
		    Object.keys(section_map).forEach(key => {
		        if (data[key]) {
		            let count = '';

		            // Only show count for template sections
		            if (key.includes('templates') && Array.isArray(data[key])) {
		                count = data[key].length;
		            }

		            this.preview_sections.push({
		                key: key,
		                label: section_map[key],
		                count: count
		            });

		            this.selected_import_sections.push(key);
		        }
    		});
		},
		toggle_section(section) {
		    const index = this.selected_import_sections.indexOf(section);

		    if (index > -1) {
		        this.selected_import_sections.splice(index, 1);
		    } else {
		        this.selected_import_sections.push(section);
		    }
		},
		confirm_import() {
		    if (this.selected_import_sections.length === 0) {
		        alert("Please select at least one setting.");
		        return;
		    }
		    let import_data = {
		        sections: this.selected_import_sections,
		        import_mode: this.import_mode, // replace | merge
		        settings: this.import_file_data
		    };
		    this.import_loading = true;
		    this.saving = true;
    		this.message = "Importing settings";
		    axios.post(
		        `${wcap_strings.rest_url}import-settings`,
		        import_data,
		        {
		            headers: {
		                'X-WP-Nonce': wcap_strings.import_export_nonce
		            }
		        }
		    ).then((response) => {
		        this.import_loading = false;
		        if (response.data.success) {
		        	this.saving = false;
		            jQuery('#wcap-import-preview-modal').modal('hide');
		            this.message_saved = response.data.message;
		            this.saved_message = true;
		            this.error_message = '';
		        } else {
		            this.error_message = response.data.message;
		            this.saved_message = false;
		        }
		        this.scroll_to_message();

		    }).catch((error) => {
		    	this.saving = false;
		        this.import_loading = false;
		        this.error_message = "Import failed. Please try again.";
		        this.saved_message = false;
		        this.scroll_to_message();
		        console.log(error);
		    });
		},
		cancel_import() {
		    this.show_import_preview = false;
		    this.import_file = null;
		    this.preview_sections = [];
		},
		scroll_to_message() {
		    this.$nextTick(() => {
		        const elmnt = document.getElementById("save_message");
		        if (elmnt) {
		            elmnt.scrollIntoView({ behavior: "smooth", block: "start" });
		        }
		    });
		},
    }
});

let routes        = {};
let settings_tabs = [];
if ( 'undefined' != wcap_strings.wcap_show_only_license_page && 'yes' === wcap_strings.wcap_show_only_license_page ) {
	routes = [ {
			path: '/',
			name: 'license',
			component: license
		},
		{
			path: '/license',
			name: 'license1',
			component: license
		},
		{
			path: '*',
			redirect: '/'
		}
	];

	settings_tabs = [
		{
			id: 'license',
			text: "License"
		},
	];
} else {
	 routes = [ {
			path: '/',
			name: 'general',
			component: general_section
		},
		{
			path: '/popup-templates',
			name: 'popup-templates',
			component: popup_templates
		},	
		{
			path: '/facebook-messenger',
			name: 'facebook-messenger',
			component: facebook_messenger
		},
		{
			path: '/connectors',
			name: 'connectors',
			component: connectors
		},
		{
			path: '/sms',
			name: 'sms',
			component: sms
		},
		{
			path: '/whatsapp',
			name: 'whatsapp',
			component: whatsapp
		},
		{
			path: '/email-report',
			name: 'email-report',
			component: email_reports
		},
		{
			path: '/import_export',
			name: 'import_export',
			component: import_export
		},
	];

	 settings_tabs = [ {
			id: 'general',
			text: "General"
		},
		{
			id: 'popup-templates',
			text: "Popup Templates"
		},
		{
			id: 'facebook-messenger',
			text: "Facebook Messenger"
		},
		{
			id: 'sms',
			text: "SMS"
		},
		{
			id: 'whatsapp',
			text: "WhatsApp"
		},
		{
			id: 'connectors',
			text: "Connectors"
		},
		{
			id: 'email-report',
			text: "Email Report"
		},
		{
			id: 'import_export',
			text: "Import/Export"
		},
	];

	if ( wcap_config.show_tabs ) {
		routes.push( {
			path: '/license',
			name: 'license',
			component: license
		} );

		settings_tabs.push( {
			id: 'license',
			text: "License"
		} );

	}
}

const router = new VueRouter( {
	routes
} );

new Vue( {
	el: "#secondary-nav-wrap",
	id: '#wcap_general_tab',
	data: {
		currentSettingsTab: 'general',
		settings_tabs: settings_tabs,
		settings: {}
	},
	mounted: function() {
		
		if ( '' !== location.hash ) {
			var sub_router = location.hash.substr(2);
			if ( 'popup-templates' === sub_router || 'facebook-messenger' === sub_router || 'sms' === sub_router || 'connectors' === sub_router || 'email-report' === sub_router || 'whatsapp' === sub_router || 'import_export' === sub_router ) {
				this.currentSettingsTab = sub_router ;
			}
			
			if ( wcap_config.show_tabs && 'license' === sub_router ) {
				this.currentSettingsTab = sub_router ;
			}
		}
    },
	router: router
})