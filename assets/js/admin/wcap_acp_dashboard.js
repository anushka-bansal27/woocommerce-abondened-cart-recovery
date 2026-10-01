jQuery(window).load(function() {

    new Vue({
        el: '#wcap-dashboard-area',
        data() {
            return {     
                message : {},
                main_chart_data : {},
                settings : {},
                reports : {},
                wcap_data_filter: 'this_month',
                wcap_duration_select: 'last_seven',
                wcap_start_date : '',
                wcap_end_date : '',
                section : 'wcap_all_abandoned',
                saving: false,
                saved_message: false,       
                message_report: '',
                message_saved:''            
            }
        },    
        mounted: function() {
			jQuery("#wcap_start_date_div").hide();
			jQuery("#wcap_end_date_div").hide();
            var self = this;
            var dataset = [];
            var abandoned_amount = [];
            var recovered_amount = [];
            var data = new FormData();
            data.append( 'wcap_start_date', self.wcap_start_date );
            data.append( 'wcap_end_date', self.wcap_end_date );
            data.append( 'duration_select', self.wcap_data_filter );
            data.append( 'action', 'wcap_display_dashboard' );
            data.append( 'security', wcap_strings.wcap_settings_nonce );
            axios.post( ajaxurl, data )
                .then( function (response) {
                    self.message = response.data;
                    var data     = self.message.graph_data;
                    for(i = 0; i < Object.keys(data).length; i++ ) {
                      var date = Object.keys(data)[i];
                      dataset[i] = Object.keys(data)[i];
                      abandoned_amount[i] = data[date]['abandoned_amount'];
                      recovered_amount[i] = data[date]['recovered_amount'];
                    }
                        //console.log(abandoned_amount);
                        LittleCodr.wcap.Highcharts_line_chart(abandoned_amount,recovered_amount,dataset);
                })
                    .catch(function (error) {
                    console.log(error);
                });
            data.append( 'duration_select', 'last_12_months' );
            data.append( 'action', 'wcap_display_dashboard_line_graph_data' );
            data.append( 'security', wcap_strings.wcap_settings_nonce );
            axios.post( ajaxurl, data )
                .then( function (response) {
                    self.main_chart_data = response.data;
                    var data     = self.main_chart_data.graph_data;
                    //console.log(data);
                    for(i = 0; i < Object.keys(data).length; i++ ) {
                      var date = Object.keys(data)[i];
                      dataset[i] = Object.keys(data)[i];
                      abandoned_amount[i] = data[date]['abandoned_amount'];
                      recovered_amount[i] = data[date]['recovered_amount'];
                    }
                        //console.log(abandoned_amount);
                        LittleCodr.wcap.Highcharts_main(abandoned_amount,recovered_amount,dataset);
                })
                    .catch(function (error) {
                    console.log(error);
                }); 
            data.append( 'action', 'ac_get_abandoned_orders' );
            data.append('wcap_section', self.section);
            data.append( 'duration_select', self.wcap_duration_select );
            data.append( 'security', wcap_strings.wcap_settings_nonce );

            axios.post( ajaxurl, data )
            .then( function (response) {
                self.settings  = response.data;
                //console.log(self.settings.abandoned_carts);
            })
            .catch(function (error) {
                console.log(error);
            });
            data.append( 'action', 'ac_get_product_reports' );
            data.append( 'security', wcap_strings.wcap_settings_nonce );
            axios.post( ajaxurl, data )
                .then( function (response) {
                    self.reports  = response.data;
                })
                .catch(function (error) {
                    console.log(error);
                });    
        },
        methods: {
            change_data_filter() {
                var self = this;
                var data = new FormData();
                var dataset = [];
                var abandoned_amount = [];
                var recovered_amount = [];
                data.append( 'wcap_start_date', self.wcap_start_date );
                data.append( 'wcap_end_date', self.wcap_end_date );
                data.append( 'duration_select', self.wcap_data_filter );
                data.append( 'action', 'wcap_display_dashboard' );
                data.append( 'security', wcap_strings.wcap_settings_nonce );
                axios.post( ajaxurl, data )
                .then( function (response) {
                    self.message = response.data;
                    var data     = self.message.graph_data;
                    for(i = 0; i < Object.keys(data).length; i++ ) {
                      var date = Object.keys(data)[i];
                      dataset[i] = Object.keys(data)[i];
                      abandoned_amount[i] = data[date]['abandoned_amount'];
                      recovered_amount[i] = data[date]['recovered_amount'];
                    }
                        LittleCodr.wcap.Highcharts_line_chart(abandoned_amount,recovered_amount,dataset);
                })
                .catch(function (error) {
                    console.log(error);
                });
            },
            show_data_filter() {
                var select_value = this.wcap_data_filter;
				if ( 'other' === select_value.trim() ) {
                    jQuery("#wcap_start_date_div").show();
                    jQuery("#wcap_end_date_div").show();
                } else {
                    jQuery("#wcap_start_date_div").hide();
                    jQuery("#wcap_end_date_div").hide();
                }
            },
            get_sorted_data : function( sortparams ) {
                this.reports.pageparams = sortparams;
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
                jQuery( "#ac_events_loader" ).show();
                pageurl = ajaxurl + '?paged=' + paged + '&' + this.reports.pageparams;
                axios.post( pageurl, data )
                    .then(function (response) {
                        jQuery( "#ac_events_loader" ).hide();
                        if ( 'undefined' !== typeof( response.data.product_reports ) ) {               
                            self.saving = false;                                    
                            self.reports =  response.data; 
                        }
                    })
                    .catch( function (error) {
                          jQuery( "#ac_events_loader" ).hide();
                    });
            }
        }
    });
});