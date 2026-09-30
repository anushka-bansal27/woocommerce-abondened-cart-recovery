
new Vue({
    el: '#product_reports',
    data() {
		return {
			settings:{},
			saving: false,
			saved_message: false,		
			message: '',
			message_saved:'',
			popup_data:{}
		}
    },    
    mounted: function() {
		
        var self = this;	
		
		jQuery( "#ac_events_loader" ).show();
		
		var data = new FormData();
		data.append( 'action', 'ac_get_product_reports' );
		data.append( 'security', wcap_strings.wcap_settings_nonce );
        axios.post( ajaxurl, data )
            .then( function (response) {
                self.settings  = response.data;
				jQuery( "#ac_events_loader" ).hide();
            })
            .catch(function (error) {
				jQuery( "#ac_events_loader" ).hide();
            });
    },
	methods: {
		get_sorted_data : function( sortparams ) {
			this.settings.pageparams = sortparams;
			this.get_paginated_data( 1, '' )
		},
		
		get_paginated_data : function( paged, disabled ='' ) {
		if ( 'disabled' === disabled ) {
			return false;
		}
		var self  = this;
		
		const data = new FormData();		
		
		data.append( 'action', 'ac_get_product_reports' );
		data.append( 'security', wcap_strings.wcap_settings_nonce );
		
		this.saving = true;
		this.message = wcap_strings.loading_message;			
		jQuery( "#ac_events_loader" ).show();
		
		pageurl = ajaxurl + '?paged=' + paged + '&' + this.settings.pageparams;
		
		axios.post( pageurl, data )
			  .then(function (response) {
				  jQuery( "#ac_events_loader" ).hide();
                 if ( 'undefined' !== typeof( response.data.product_reports ) ) {				
					
					self.saving = false;									
					self.settings =  response.data;	
				}
            })
            .catch( function (error) {
				  jQuery( "#ac_events_loader" ).hide();
            } );
		}
    }
})