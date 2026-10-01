

new Vue({
    el: '#abandoned_orders',
    data() {
		return {
			settings : {},
			saving : false,
			saved_message : false,		
			message : wcap_strings.loading_message,
			message_saved :'',
			bulk_selected_ids : [],
			select_all : false,
			wcap_action : '',
			wcap_abandoned_bulk_actions: wcap_strings.wcap_abandoned_bulk_actions,
			wcap_trash_bulk_actions:wcap_strings.wcap_trash_bulk_actions,
			duration_range_select: wcap_strings.duration_range_select,
			valid_statuses : wcap_strings.valid_statuses,
			valid_sources : wcap_strings.valid_sources,
			start_end_dates: wcap_strings.start_end_dates,
			popup_id : '',
			popup_status : '',
			popup_html : '',
			popup_unsubscribe_link  : '',
			popup_manual_email_link : '',
			popup_view:'',
			recovered_order_text:'',
			mark_order_type:'wcap_existing',
			wcap_hidden_order_id :'',
			recover_search_id:'',
			page_url :wcap_strings.page_url,
			duration_select : wcap_strings.wcap_filter_data.duration_select,
			start_date : wcap_strings.wcap_filter_data.start_date,
			hidden_start : wcap_strings.wcap_filter_data.hidden_start,
			end_date : wcap_strings.wcap_filter_data.end_date,
			hidden_end : wcap_strings.wcap_filter_data.hidden_end,
			cart_status : wcap_strings.wcap_filter_data.cart_status,
			cart_source : wcap_strings.wcap_filter_data.cart_source,
			bulk_action : '',
			section : 'wcap_all_abandoned',
			recovered_text:'',
			print_csv_diabled : false,
			show_progess_bar : 'hidden',
			progress : 0,
			progress_text :'',
			added:0,
			wcap_manual_email_sent:wcap_strings.wcap_manual_email_sent,
			mail_sent_message:wcap_strings.mail_sent_message,					
		}
    },    
    mounted: function() {
		
        var self = this;
		if ( this.wcap_manual_email_sent ) {
			this.saved_message = true;
			this.message_saved = this.mail_sent_message;
		}
		
		jQuery( "#ac_events_loader" ).show();
		
				
		var data = new FormData();
		data.append( 'wcap_section', 'wcap_all_abandoned' );
		data.append( 'action', 'ac_get_abandoned_orders' );
		data.append( 'security', wcap_strings.wcap_settings_nonce );
		


        axios.post( ajaxurl, data )
            .then( function (response) {
                self.settings  = response.data;
				//self.settings.abandoned_carts = self.settings.abandoned_carts.slice().reverse()
				jQuery( "#ac_events_loader" ).hide();
				self.load_dates( self.duration_select );
            })
            .catch(function (error) {
				jQuery( "#ac_events_loader" ).hide();
            });		
			
    },
	methods: {
			set_pop_up_data : function ( row ) {
			
			var self = this;
			this.popup_view = 'show_cart';
			this.popup_html = '';
			self.popup_id = row.id;
			self.popup_status = row.status;
			self.recovered_order_text = row.recovered_order_text;
		
			
			this.popup_unsubscribe_link  = row.unsubscribe_link;
			this.popup_manual_email_link = row.manual_email_link;
			this.popup_view = 'show_cart';
				
			this.saved_message = false;
			this.saving        = true;
			this.message       = wcap_strings.loading_message;
			jQuery( "#ac_events_loader" ).show();
			
			const data = new FormData();
			data.append( 'action', 'wcap_abandoned_cart_info' );
			data.append( 'security', wcap_strings.wcap_settings_nonce );
			data.append( 'wcap_cart_id', row.id );
			data.append( 'wcap_email_address', row.email );
			data.append( 'wcap_customer_details', row.customer_details );
			data.append( 'wcap_cart_total', row.order_total );
			data.append( 'wcap_abandoned_date', row.date );
			data.append( 'wcap_abandoned_status', row.status_original );
			data.append( 'wcap_current_page', '' );
			
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
		modal_close : function(){
			jQuery('#Fb-2').trigger('click');
		},
		set_mark_recover_in_popup : function (){
			this.popup_html = jQuery('#mark_recover_popup').html()
		},
		open_mark_as_recovered : function ( row_id ) {
			this.popup_id   = row_id;
			this.popup_view = 'mark_as_recovered';
			this.recover_search_id = ''
		},
		mark_as_recovered : function () {
			
		},
		recovered_order : function () {
			
			if ( ! this.wcap_hidden_order_id && 'wcap_existing' === this.mark_order_type ) {
				return 
			}
			
			var self = this;
			const data = new FormData();
			
			data.append( 'action', 'wcap_mark_recovered_admin' );
			data.append( 'cart_id', this.popup_id  );
			data.append( 'order_id', this.wcap_hidden_order_id );
			data.append( 'order_type', this.mark_order_type );
			
			this.message = wcap_strings.loading_message;			
			jQuery( "#ac_events_loader" ).show();
			
			  axios.post( ajaxurl, data )
			  .then(function (response) {
				  jQuery( "#ac_events_loader" ).hide();
                if ( 'success' == response.data || 'undefined' !== typeof( response.data.abandoned_carts ) ) {					
					
					self.saving = false;
					self.saved_message = true;
					self.message_saved = wcap_strings.message_recovered;
					
					self.settings =  response.data;	
					self.bulk_selected_ids = [];
					if ( bulk_action == 'wcap_empty_trash' ) {
						this.section='';
					}
					
                    setTimeout(() => {
                       var elmnt = document.getElementById("save_message");
                       elmnt.scrollIntoView( { behavior: "smooth", block: 'end' } ) 
					});
					
					jQuery('#Fb-2').trigger('click');
				}
            })
            .catch(function (error) {
				jQuery( "#ac_events_loader" ).hide();
            });
			
			
		},
		search_mark_order : function () {
			
			
			var self = this;
			const data = new FormData();
			
			data.append( 'action', 'wcap_json_search_wc_order' );
			data.append( 'order_id', this.recover_search_id  );
			
			if ( '' == this.recover_search_id ) {
				return;
			}
			
			this.message = wcap_strings.loading_message;			
			jQuery( "#ac_events_loader" ).show();
			
			  axios.post( ajaxurl, data )
			  .then(function (response) {
				  jQuery( "#ac_events_loader" ).hide();
                if ( response.data == 'failed' ) {	
                jQuery( "#order_warn_msg" ).html( wcap_strings.order_id_not_found_msg );				
					jQuery( "#order_warn_msg" ).fadeIn();
					setTimeout( function(){jQuery( "#order_warn_msg" ).fadeOut();},3000);
				}
				else {					
					self.wcap_hidden_order_id = self.recover_search_id;
					self.recover_search_id    = response.data;
				}
            })
            .catch(function (error) {
				jQuery( "#ac_events_loader" ).hide();
            });
			
			
		},
		bulk_action_apply : function ( bulk_action = '' ) {
			
			if ( bulk_action == '' ) {
				bulk_action = this.bulk_action;
				if ( this.bulk_action === '' || 'undefined' === typeof( this.bulk_action ) ) {
					alert(wcap_strings.no_action_chosen);
					return;
				}
			}
			if ( 'wcap_manual_email' !== bulk_action ) {
				if ( this.section !== 'wcap_trash_abandoned' ) {
					warn_string = wcap_strings.wcap_abandoned_bulk_actions[bulk_action];
				} else {
					warn_string = wcap_strings.wcap_trash_bulk_actions[bulk_action];
				}
				var req  = confirm( wcap_strings.confirm_bulk_action + ' ' + warn_string );			
				if ( ! req ) { return; }				
			}
			var self = this;
			const data = new FormData();
			
			data.append( 'action', 'wcap_cart_bulk_action' );
			data.append('security', wcap_strings.wcap_settings_nonce);
			data.append( 'bulk_action', bulk_action );
			if ( '' !== this.section ) {
				data.append( 'wcap_section', this.section );
			}
			var selected       = false;
			var can_send_email = false;
			var abandoned_order_id = '';
			if ( 'wcap_sync_manually' === bulk_action || 'wcap_abandoned_trash' === bulk_action || 'wcap_manual_email' === bulk_action || 'wcap_abandoned_restore' === bulk_action || 'wcap_abandoned_delete' === bulk_action   ) {
				
				for ( var i in this.settings.abandoned_carts )	{
					
					row_id = this.settings.abandoned_carts[i].id;					
					if ( typeof( this.bulk_selected_ids[ row_id ] ) !== 'undefined' && this.bulk_selected_ids[ row_id ] !== 'false' ) {
						selected = true;
						if (  'wcap_manual_email' !== bulk_action ) {							
							data.append('abandoned_order_id[]', row_id );
						} else if ( '' !==  this.settings.abandoned_carts[ i ].manual_email_link )  {
									can_send_email = true;
									abandoned_order_id += row_id + ', '
						}
					}
					
				}
				
				
				if ( ! selected ) {
					self.saved_message = true;
					self.message_saved = wcap_strings.please_chose_ids;
					  setTimeout(() => {
                       var elmnt = document.getElementById("save_message");
                       elmnt.scrollIntoView( { behavior: "smooth", block: 'end' } ) 
					   });
					return;
				}
				if ( 'wcap_manual_email' === bulk_action ) {
					if ( ! can_send_email ) {
						self.saved_message = true;
						self.message_saved = wcap_strings.cannot_send_email;
						setTimeout(() => {
                       var elmnt = document.getElementById("save_message");
                       elmnt.scrollIntoView( { behavior: "smooth", block: 'end' } ) 
					   });
						return;
					}
					window.location = wcap_strings.manual_email_url+'abandoned_order_id='+abandoned_order_id;
				}				
			}
			this.saving = true;
			this.message = wcap_strings.message_processing;			
			jQuery( "#ac_events_loader" ).show();
			var self = this;
			axios.post( ajaxurl, data )
			  .then(function (response) {
				  jQuery( "#ac_events_loader" ).hide();
                 if ( 'success' == response.data || 'undefined' !== typeof( response.data.abandoned_carts ) ) {					
					self.saved_message = true;
					self.message_saved = wcap_strings.message_moved;
					if ( bulk_action == 'wcap_abandoned_restore' ) {
						self.message_saved = wcap_strings.message_restored;
					}
					if ( bulk_action == 'wcap_abandoned_delete' ){
						self.message_saved = wcap_strings.message_deleted;
					}
					if ( 'wcap_sync_manually' === bulk_action ) {
						self.message_saved = wcap_strings.message_synced;
					}
					self.saving = false;
					
					
					self.settings =  response.data;	
					self.bulk_selected_ids = [];
					if ( bulk_action == 'wcap_empty_trash' ) {
						this.section='wcap_all_abandoned';
						self.message_saved = wcap_strings.message_trash_emptied;
					}
					
					if ( response.data.redirected_to_all ) {
						self.section = 'wcap_all_abandoned';
					}
					
                    setTimeout(() => {
                       var elmnt = document.getElementById("save_message");
                       elmnt.scrollIntoView( { behavior: "smooth", block: 'end' } ) 
					   });
				}
            })
            .catch(function (error) {
               jQuery( "#ac_events_loader" ).hide();
            });
						
		},
		trash_row : function( row_id ) {
			this.bulk_selected_ids = [];
			this.bulk_selected_ids[ row_id ] = true
			this.bulk_action_apply( 'wcap_abandoned_trash');
		},
		restore_row : function( row_id ) {
			this.bulk_selected_ids[ row_id ] = true
			this.bulk_action_apply( 'wcap_abandoned_restore');
		},
		delete_row : function( row_id ) {
			this.bulk_selected_ids[ row_id ] = true
			this.bulk_action_apply( 'wcap_abandoned_delete');
		},
		filter_orders : function( ev ) {
			
			var self = this;
			const data = new FormData();
			
			data.append( 'action', 'ac_get_abandoned_orders' );
			data.append( 'security', wcap_strings.wcap_settings_nonce );
			if ( '' !== this.section ) {
				data.append( 'wcap_section', this.section );
			}
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
			data.append( 'cart_status', this.cart_status );
			data.append( 'cart_source', this.cart_source );

			this.saving = true;
			this.message = wcap_strings.loading_message;			
			jQuery( "#ac_events_loader" ).show();			
			
              axios.post( ajaxurl, data )
			  .then(function (response) {
				  jQuery( "#ac_events_loader" ).hide();
                 if ( 'success' == response.data || 'undefined' !== typeof( response.data.abandoned_carts ) ) {			
					
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
		print_csv : function ( step, type ) {
			var self = this;
			const data = new FormData();
			data.append( 'action', 'wcap_data_export' );
			data.append( 'filter_status', this.cart_status );
			data.append( 'filter_source', this.cart_source );
			data.append( 'start_date', this.start_date  );
			data.append( 'end_date', this.end_date  );
			data.append( 'wcap_section', this.section );
			data.append( 'total_items', this.settings.total_items );
			data.append( 'csv_print', type );
			data.append( 'step', step );
			data.append( 'done_items', this.added  );
			data.append( 'security', wcap_strings.wcap_settings_nonce );
			
			if (  step === 1 ){
				self.progress = 0;
			}
			
			setTimeout( function(){					
					self.show_progess_bar = 'visible';
									
				}, 2000 );
			
			
			axios.post( ajaxurl, data )
			  .then(function ( res ) {
				  response = res.data;
				 jQuery( "#ac_events_loader" ).hide();
                if ( 'done' == response.step || response.error || response.success ) {
				// We need to get the actual in progress form, not all forms on the page
				jQuery('.wcap-export').removeClass('button-disabled');

				if ( response.error ) {
					var error_message = response.message;
					jQuery('#wcap-view-abandoned-orders-msg').html('<div class="updated error"><p>' + error_message + '</p></div>')

				} else {
					self.progress = 100;
					setTimeout( function(){					
					self.show_progess_bar = 'hidden';
					self.progress_text = '';
					self.added = 0;
									
				}, 9000 );
					
					
					if ( 'print' == type ) {
						LittleCodr.wcap.printCarts();
						jQuery( '#wcap-view-cart-data' ).html( '' )
					} else {
						window.location = response.url;
					}
					
					
					
				}

			} else {
				if ( 'print' == type ) {
					let content = response.html_data;

					if ( step == 1 ) {
						jQuery( '#wcap-view-cart-data' ).html( content );
					} else {
						jQuery('#wcap_print_data').append( content );
					}
				 }
				jQuery('#wcap_myBar').data( 'added', response.added );
				
				if ( parseInt( response.percentage ) > 100 ) {
					response.percentage = 100;
					self.progress = 100;					
				} else {
					self.progress      = response.percentage;
				}
				self.added      = response.added;
				
				self.progress_text = response.percentage + '%';	
				
				console.log( response.step );
				self.print_csv( parseInt( response.step ), type );
			}
            })
            .catch(function (error) {
				jQuery( "#ac_events_loader" ).hide();
            });
			
			
			
		},
	get_paginated_data : function( paged, disabled ='' ) {
		if ( 'disabled' === disabled ) {
			return false;
		}
		var self  = this;
		
		const data = new FormData();
		if ( '' !== this.section || null !== this.section ) {
			data.append( 'wcap_section', this.section );
		}
		data.append( 'paged', paged );
		data.append( 'action', 'ac_get_abandoned_orders' );
		data.append( 'security', wcap_strings.wcap_settings_nonce );
		
		this.saving = true;
		this.message = wcap_strings.loading_message;			
		jQuery( "#ac_events_loader" ).show();
		
		 axios.post( ajaxurl, data )
			  .then(function (response) {
				  jQuery( "#ac_events_loader" ).hide();
                 if ( 'success' == response.data || 'undefined' !== typeof( response.data.abandoned_carts ) ) {				
					
					self.saving = false;									
					self.settings =  response.data;	
				}
            })
            .catch( function (error) {
				  jQuery( "#ac_events_loader" ).hide();
            } );
		},
		ajax_link : function ( section ) {
			
			this.section = section;
			this.get_paginated_data( 1 );		
			return false;
		},
		row_action : function ( action , row_id ) {
			var self = this;
			
			this.saving = true;
			this.message = wcap_strings.loading_message;			
			const data = new FormData();
		if ( '' !== this.section || null !== this.section ) {
			data.append( 'wcap_section', this.section );
		}
		
		data.append( 'action', 'wcap_cart_row_action' );
		data.append( 'row_action', action );
		data.append( 'abandoned_order_id', row_id );
		data.append( 'security', wcap_strings.wcap_settings_nonce );
		
		this.saving = true;
		this.message = wcap_strings.message_processing;			
		jQuery( "#ac_events_loader" ).show();
		
		 axios.post( ajaxurl, data )
			  .then(function (response) {
				  jQuery( "#ac_events_loader" ).hide();
                 if ( 'success' == response.data || 'undefined' !== typeof( response.data.abandoned_carts ) ) {	

					self.message_saved = wcap_strings.message_unsubscribed;
					if ( action === 'sync_manually' ) {
						self.message_saved = wcap_strings.message_synced;
					}
					self.saved_message = true;
									 
					
					self.saving = false;									
					self.settings =  response.data;	
					jQuery('#Fb-2').trigger('click');
					
					 setTimeout(() => {
                       var elmnt = document.getElementById("save_message");
                       elmnt.scrollIntoView( { behavior: "smooth", block: 'end' } ) 
					   });
				}
            })
            .catch( function (error) {
				jQuery( "#ac_events_loader" ).hide();
				jQuery('#Fb-2').trigger('click');
            } );
			
			return false;
			
		},
		toggle_bulk_select : function ( row_id ) {
			
			this.bulk_selected_ids[row_id] = this.bulk_selected_ids[row_id] ? false : true;
						
		},
		bulk_select_ids : function ( select_all ) {
			
			for ( var i in this.settings.abandoned_carts )
			{				
				row_id = this.settings.abandoned_carts[i].id;
				this.bulk_selected_ids[row_id] = select_all;				
			}			
		}
    }
})