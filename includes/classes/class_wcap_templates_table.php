<?php 
/**
 * Abandoned Cart Pro for WooCommerce
 *
 * It will show list of Active and deactive email templates on Emails Templates tab.
 * 
 * @author   LittleCodr
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Classes
 * @category Classes
 * @since    5.0
 */
// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
/**
 * Show Email templates list on Email Templates tab.
 * 
 * @since 2.0
 */
class Wcap_Templates_Table extends WP_List_Table {

	/**
	 * Number of results to show per page
	 *
	 * @var string
	 * @since 2.0
	 */
	public $per_page = 30;

	/**
	 * URL of this page
	 *
	 * @var string
	 * @since 2.0
	 */
	public $base_url;

	/**
	 * Total number of templates
	 *
	 * @var int
	 * @since 2.0
	 */
	public $total_count;

    /**
	 * It will add the bulk action and other variable needed for the class.
	 *
	 * @since 2.0
	 * @see WP_List_Table::__construct()
	 */
	public function __construct() {

		// Set parent defaults
		parent::__construct( array(
	        'singular' => __( 'template_id', 'woocommerce-ac' ), //singular name of the listed records
	        'plural'   => __( 'template_ids', 'woocommerce-ac' ), //plural name of the listed records
			'ajax'      => false             			// Does this table support ajax?
		) );
		$this->wcap_get_templates_count();
		$this->process_bulk_action();
        $this->base_url = admin_url( 'admin.php?page=woocommerce_ac_page&action=cart_recovery&section=emailtemplates' );
	}

	/**
	 * It will prepare the list of the Email Templates, columns, pagination, sortable column and other data.
	 *
	 * @since 2.0
	 */
	public function wcap_templates_prepare_items() {
		
		$hidden                = array(); // No hidden columns
		$sortable              = $this->wcap_templates_get_sortable_columns();
		$data                  = $this->wcap_templates_data();		
		$total_items           = $this->total_count;
		$this->items           = $data;
		
		$this->set_pagination_args( array(
				'total_items' => $total_items,                  	// WE have to calculate the total number of items
				'per_page'    => $this->per_page,                     	// WE have to determine how many items to show on a page
				'total_pages' => ceil( $total_items / $this->per_page )   // WE have to calculate the total number of pages
		      )
		);

		$first_page   = 1;
		$current_page = 1;
		$last_page    = ceil( $total_items / $this->per_page );		

		if ( isset( $_GET['paged'] ) ) {//phpcs:ignore
			$current_page  = ( $_GET['paged'] < 0 ) ? 1 : ( $_GET['paged'] > $last_page ? $last_page : sanitize_text_field( wp_unslash( $_GET['paged'] ) ) );//phpcs:ignore
		}
		
		$previous_page     = ( $current_page > 1 ) ? $current_page - 1 : false;
		$next_page         = ( $current_page < $last_page ) ? $current_page + 1 : false;
		$previous_disabled = ! $previous_page ? 'disabled' : '';
		$next_disabled     = ! $next_page ? 'disabled' : '';
		
		return array(
			'email_templates'   => $data,
			'total_items'       => $total_items,
			'per_page'          => $this->per_page,
			'total_pages'       => ceil( $total_items / $this->per_page ),
			'current_page'      => $current_page,
			'previous_page'     => $previous_page,
			'next_page'         => $next_page,			
			'last_page'         => $last_page,
			'previous_disabled' => $previous_disabled,
			'next_disabled'     => $next_disabled,
			 );
	}

	/**
     * This function is used for email templates count.
     *
     * @globals mixed $wpdb
     * @return int $this->total_count total count of Email Templates.
     * @since   2.0
     */
    public function wcap_get_templates_count() {	
        global $wpdb;	
		$this->total_count = $wpdb->get_var( "SELECT COUNT(`id`) FROM `" . WCAP_NOTIFICATION_TEMPLATES_TABLE . "` WHERE notification_type = 'email'" );//phpcs:disable
    }
	/**
     * It will manage for the Email Template list. 
     *
     * @globals mixed $wpdb
     * @return array $return_templates_display Key and value of all the columns
     * @since 2.0
     */
	public function wcap_templates_data() { 
    	global $wpdb;    	
    	$return_templates_data = array();
    	$per_page              = $this->per_page;
    	$results               = array();    	     
        $wcap_get_decimal      = wc_get_price_decimals();
		$results               = $wpdb->get_results( // phpcs:ignore
			$wpdb->prepare(
				'SELECT id, is_active, frequency, day_or_hour, rules, template_name, body, email_type FROM `' . WCAP_NOTIFICATION_TEMPLATES_TABLE . '` WHERE notification_type = %s',
				'email'
			)
		);
    
    	$i = 0;    	
    	$minute_seconds        = 60;
        $hour_seconds          = 3600; // 60 * 60
        $day_seconds           = 86400; // 24 * 60 * 60
		$time_to_send_template_after = '';
		

		$send_labels = array(
			'all'                       => __( 'All', 'woocommerce-ac' ),
			'registered_users'          => __( 'Registered Users', 'woocommerce-ac' ),
			'guest_users'               => __( 'Guest Users', 'woocommerce-ac' ),
			'wcap_email_customer'       => __( 'Customers', 'woocommerce-ac' ),
			'wcap_email_admin'          => __( 'Admin', 'woocommerce-ac' ),
			'wcap_email_customer_admin' => __( 'Customers & Admin', 'woocommerce-ac' ),
			'email_addresses'           => __( 'Email Addresses', 'woocommerce-ac' ),
		);
    	foreach( $results as $key => $value ) {    	    
    	    $return_templates_data[ $i ] = new stdClass();    	    
    	    $id                          = $value->id;
    	    $query_no_emails             = "SELECT COUNT(`id`) FROM " . WCAP_EMAIL_SENT_HISTORY_TABLE . " WHERE template_id= %d AND notification_type='email'";
    	    $ac_emails_count	         = $wpdb->get_var( $wpdb->prepare( $query_no_emails, $id ) );
			
			$query_no_recovers_test      = "SELECT COUNT(`id`) FROM " . WCAP_EMAIL_SENT_HISTORY_TABLE . " WHERE recovered_order = '1' AND template_id = %d";
    	    $wcap_number_of_time_recover = $wpdb->get_var( $wpdb->prepare( $query_no_recovers_test, $id ) );

			$is_active   = $value->is_active;
    	
    	    $frequency   = $value->frequency;
    	    $day_or_hour = $value->day_or_hour;
    	    $day_or_hour_text = __( 'Minutes', 'woocommerce-ac' );

    	    if( 'Minutes' == $value->day_or_hour ) {
                $time_to_send_template_after = $value->frequency * $minute_seconds;
				$day_or_hour_text            = __( 'Minutes', 'woocommerce-ac' );
            } else if( 'Days' == $value->day_or_hour ) {
                $time_to_send_template_after = $value->frequency * $day_seconds;
				$day_or_hour_text            = __( 'Days', 'woocommerce-ac' );
            } else if( 'Hours' == $value->day_or_hour ) {
                $time_to_send_template_after = $value->frequency * $hour_seconds;
				$day_or_hour_text            = __( 'Hours', 'woocommerce-ac' );
            }

			// Open Rate.
			$opened_emails = $wpdb->get_var( $wpdb->prepare( 'SELECT count( DISTINCT( open.notification_sent_id ) ) FROM ' . WCAP_NOTIFICATION_OPENED_TABLE . ' AS open INNER JOIN ' . WCAP_EMAIL_SENT_HISTORY_TABLE . ' AS sent ON open.notification_sent_id = sent.id WHERE sent.template_id = %d', $id ) ); //phpcs:ignore
			$open_rate     = $opened_emails > 0 && $ac_emails_count > 0 ? round( ( $opened_emails / $ac_emails_count ) * 100, $wcap_get_decimal ) : 0;

			// Links Clicked Rate.
			$links_clicked = $wpdb->get_var( $wpdb->prepare( 'SELECT count( DISTINCT( link.notification_sent_id ) ) FROM ' . WCAP_LINK_CLICKED_TABLE . ' AS link INNER JOIN ' . WCAP_EMAIL_SENT_HISTORY_TABLE . ' AS sent ON link.notification_sent_id = sent.id WHERE sent.template_id = %d', $id ) ); //phpcs:ignore
			$links_rate    = $links_clicked > 0 && $ac_emails_count > 0 ? round( ( $links_clicked / $ac_emails_count ) * 100, $wcap_get_decimal ) : 0; 

			// coupon redemption rate.
			$coupon_rate = 0;
			if( stripos( $value->body, "{{coupon.code}}" ) ) {
				$coupon_rate = $links_clicked > 0 && $opened_emails > 0 ? round( ( $links_clicked / $opened_emails ) * 100, $wcap_get_decimal ) : 0;
			}
			

    		$wcap_recover_ratio = 0;
    	    if ( $ac_emails_count != 0 ) {
    	        $wcap_recover_ratio = $wcap_number_of_time_recover / $ac_emails_count * 100;
    	    }
    	    $rules = json_decode( $value->rules );
			$template_filter = '';
			if ( is_array( $rules ) && count( $rules ) > 0 ) {
				foreach ( $rules as $rule_list ) {
					if( 'send_to' === $rule_list->rule_type ) {
						if ( is_array( $rule_list->rule_value ) ) {
							foreach ( $rule_list->rule_value as $r_option ) {
								$template_filter .= $send_labels[ $r_option ] . ', ';
							}
						}
					}
				}
				$template_filter = rtrim( $template_filter, ', ' );
			}
			$freq_text = 'abandoned_cart_email' === $value->email_type ? __( 'After Abandonment', 'woocommerce-ac' ) : __( 'After WC Order Status change', 'woocommerce-ac' );

			$return_templates_data[ $i ]->sr                  = $i + 1;
			$return_templates_data[ $i ]->id                  = $id;
			$return_templates_data[ $i ]->template_name       = $value->template_name;
			$return_templates_data[ $i ]->sent_time           = "$frequency $day_or_hour_text $freq_text";
			$return_templates_data[ $i ]->template_time       = $time_to_send_template_after;
			$return_templates_data[ $i ]->template_filter     = $template_filter;
			$return_templates_data[ $i ]->email_sent          = __( ( string )$ac_emails_count, 'woocommerce-ac' );
			$return_templates_data[ $i ]->percentage_recovery = round ( $wcap_recover_ratio , $wcap_get_decimal )."%";
			$return_templates_data[ $i ]->is_active           = $is_active;
			$return_templates_data[ $i ]->open_rate           = $open_rate;
			$return_templates_data[ $i ]->link_rate           = $links_rate;
			$return_templates_data[ $i ]->coupon_rate         = $coupon_rate;
			
			$return_templates_data[ $i ]->edit_link   = str_replace( '&amp;', '&', wp_nonce_url( add_query_arg( array( 'action' => 'cart_recovery', 'section' => 'emailtemplates', 'mode'=>'edittemplate', 'id' => $id ), $this->base_url ), 'abandoned_order_nonce' ) );
			$return_templates_data[ $i ]->copy_link   = str_replace( '&amp;', '&', wp_nonce_url( add_query_arg( array( 'action' => 'cart_recovery', 'section' => 'emailtemplates', 'mode'=>'copytemplate', 'id' => $id ), $this->base_url ), 'abandoned_order_nonce' ) );
			$return_templates_data[ $i ]->delete_link = str_replace( '&amp;', '&', wp_nonce_url( add_query_arg( array( 'action' => 'wcap_delete_template', 'section' => 'emailtemplates', 'template_id' => $id ), $this->base_url ), 'abandoned_order_nonce' ) );
			
			$i++;
    	}
    	
        // sort for order date
        if( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'template_name' ) {
        	if( isset( $_GET['order'] ) && $_GET['order'] == 'asc') {
        		usort( $return_templates_data, array( __CLASS__ ,"wcap_class_template_name_asc") ); 
        	} else {
        		usort( $return_templates_data, array( __CLASS__ ,"wcap_class_template_name_dsc") );
        	}
        }
        // sort for customer name
        else if( isset( $_GET['orderby']) && $_GET['orderby'] == 'sent_time' ) {
            if( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
        		usort( $return_templates_data, array( __CLASS__ ,"wcap_class_sent_time_asc" ) );
        	} else {
        		usort( $return_templates_data, array( __CLASS__ ,"wcap_class_sent_time_dsc" ) );
        	}
        }

        if ( isset( $_GET['paged'] ) && $_GET['paged'] > 1 ) {
		    $page_number = $_GET['paged'] - 1;
		    $k           = $per_page * $page_number;
		}else {
		    $k           = 0;
		}
		 
		$return_templates_display = array();
		for ( $j = $k; $j < ( $k + $per_page ); $j++ ) {
            if ( isset( $return_templates_data[ $j ] ) ) {
		        $return_templates_display[ $j ] = $return_templates_data[ $j ];
		    }else {
		        break;
		    }
		}
        return apply_filters( 'wcap_templates_table_data', $return_templates_display );
	}
	/**
	 * It will sort the alphabetically ascending on Name Of Template column.
	 *
	 * @param  array | object $value1 All data of the list
	 * @param  array | object $value2 All data of the list
	 * @return sorted array  
	 * @since  3.4
	 */
	function wcap_class_template_name_asc($value1,$value2) {
	    return strcasecmp($value1->template_name,$value2->template_name );
	}
	/**
	 * It will sort the alphabetically descending on Name Of Template column.
	 *
	 * @param  array | object $value1 All data of the list
	 * @param  array | object $value2 All data of the list
	 * @return sorted array  
	 * @since  3.4
	 */
	function wcap_class_template_name_dsc ($value1,$value2) {
	    return strcasecmp($value2->template_name,$value1->template_name );
	}
	/**
	 * It will sort the alphabetically ascending on the Sent After Set Time column.
	 *
	 * @param  array | object $value1 All data of the list
	 * @param  array | object $value2 All data of the list
	 * @return sorted array  
	 * @since  3.4
	 */
	function wcap_class_sent_time_asc($value1,$value2) {
	    return strnatcasecmp($value1->template_time,$value2->template_time );
	}
	/**
	 * It will sort the alphabetically descending on the Sent After Set Time column.
	 *
	 * @param  array | object $value1 All data of the list
	 * @param  array | object $value2 All data of the list
	 * @return sorted array  
	 * @since  3.4
	 */
	function wcap_class_sent_time_dsc ($value1,$value2) {
	    return strnatcasecmp($value2->template_time,$value1->template_time );
	}
}
