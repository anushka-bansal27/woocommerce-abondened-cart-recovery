/**
 * wcap Main JS.
 * 
 * @since 5.19.0
 */

( function() {

	// Stop if either tyche object or wcap parameters are missing.
	if ( 'undefined' === typeof tyche  ) {
		return;
	}

	tyche.extend( tyche.wcap, {

		/**
		 * Choice JS.
		 * @since 5.19.0
		 */
			Highcharts_main: function( abandoned_amount, recovered_amount, dataset ) {
                Highcharts.chart('abandone-chart', {
                    chart: {
                        type: 'areaspline'
                    },
                    title: {
                        text: ''
                    },
                    subtitle: {
                        align: 'center',
                        text: 'Source: <a href="https://www.ssb.no/jord-skog-jakt-og-fiskeri/jakt" target="_blank">SSB</a>'
                    },
                    legend: {
                        layout: 'vertical',
                        align: 'left',
                        verticalAlign: 'top',
                        x: 120,
                        y: 70,
                        floating: true,
                        borderWidth: 1,
                        backgroundColor:
                            Highcharts.defaultOptions.legend.backgroundColor || '#FFFFFF'
                    },
                    xAxis: {
                        categories: dataset,
                        crosshair: true,
                    },
                    yAxis: {
                        title: {
                            text: ''
                        }
                    },
                    tooltip: {
                        shared: true,
                        headerFormat: '<b>WooCommerce - Abandon Cart {point.x}</b><br>'
                    },
                    credits: {
                        enabled: false
                    },
                    plotOptions: {
                        areaspline: {
                            fillOpacity: 1.25
                        }
                    },
                    series: [{
                        name: 'Abandoned Amount',
                        data: abandoned_amount
                    }, {
                        name: 'Recovered Amount',
                        data: recovered_amount
                    }]
                })
			},

		Highcharts_line_chart: function ( abandoned_amount,recovered_amount,dataset ) {
                Highcharts.chart('abandone-line-chart', {
            chart: {
                type: 'column'
            },
            title: {
                text: ''
            },
            subtitle: {
                text: 'Source: ' +
                    '<a href="https://www.ssb.no/en/statbank/table/08940/" ' +
                    'target="_blank">SSB</a>'
            },
            xAxis: {
                categories: dataset,
                crosshair: true
            },
            yAxis: {
                title: {
                    useHTML: true,
                    text: ''
                }
            },
            tooltip: {
                headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
                pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                    '<td style="padding:0"><b>{point.y:.1f}</b></td></tr>',
                footerFormat: '</table>',
                shared: true,
                useHTML: true
            },
            plotOptions: {
                column: {
                    pointPadding: 0.2,
                    borderWidth: 0
                }
            },
            series: [{
                name: 'Abandoned Amount',
                data: abandoned_amount
        
            }, 
            {
                name: 'Recovered Amount',
                data: recovered_amount
        
            }]
        })
    },

	printCarts: function () {
		let printThis = document.getElementById('wcap_print_data').outerHTML;  
		let win = window.open();
		win.document.open();
		win.document.write('<'+'html'+'><head><title>Print Abandoned Carts</title><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head><'+'body'+'>');
		win.document.write( printThis );
		win.document.write('<'+'/body'+'><'+'/html'+'>');
		win.document.close();
		win.print();
	},

} );

	jQuery( document ).ready( function() {
		//tyche.wcap.add_tool_tips_under_label_text();
        jQuery( '#wcap_auto_login_notice' ).on( 'click', '.notice-dismiss', function() {
            var data = {
                notice: 'wcap_auto_login_notice_dismiss',
                action: "wcap_dismiss_admin_notice",
                security: wcap_strings.wcap_settings_nonce

            };
            var admin_url = ajaxurl;
            jQuery.post( admin_url, data, function( response ) {
            });
        });
        jQuery( '#wcap_ai_abilities_intro_notice' ).on( 'click', '.notice-dismiss', function() {
            var data = {
                action: "wcap_dismiss_ai_abilities_notice",
                security: wcap_strings.wcap_settings_nonce
            };
            jQuery.post( ajaxurl, data, function( response ) {
            });
        });
	} );

}() );
