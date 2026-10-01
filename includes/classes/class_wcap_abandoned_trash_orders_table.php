<?php
/**
 *
 * Abandoned Cart Pro for WooCommerce
 *
 * It will show trashed abandoned carts data on Trash tab of Abandoned Orders page.
 * 
 * @author   LittleCodr
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Classes
 * @category Classes
 * @since    5.0
 */
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
/**
 * Show Abandoned Carts data on Abandoned Orders tab.
 * 
 * @since 4.3
 */
class Wcap_Abandoned_Trash_Orders_Table extends WP_List_Table {

	/**
	 * Number of results to show per page
	 *
	 * @var string
	 * @since 4.3
	 */
	public $per_page = 30;

	/**
	 * URL of this page
	 *
	 * @var string
	 * @since 4.3
	 */
	public $base_url;

	/**
	 * Total number of abandoned carts
	 *
	 * @var int
	 * @since 4.3
	 */
	public $total_count;

    /**
	 * It will add the bulk action and other variable needed for the class.
	 *
	 * @since 4.3
	 * @see WP_List_Table::__construct()
	 */
	public function __construct() {
		global $status, $page;
		// Set parent defaults
		//phpcs:disable
		parent::__construct( array(
	    	        'singular' => __( 'abandoned_order_id', 'woocommerce-ac' ),  //singular name of the listed records
	    	        'plural'   => __( 'abandoned_order_ids', 'woocommerce-ac' ), //plural name of the listed records
	    			'ajax'     => false             			                 // Does this table support ajax?
    		    )
		);
		$this->process_bulk_action();
        $this->base_url = admin_url( 'admin.php?page=woocommerce_ac_page&action=listcart' );
	}
	/**
	 * It will prepare the list of the abandoned carts, columns, pagination, sortable column and other data.
	 *
	 * @since 4.3
	 */
	public function wcap_abandoned_order_prepare_items() {
		
		$hidden                = array(); // No hidden columns
		$this->total_count     = $this->wcap_get_total_abandoned_count();
		$sortable              = $this->get_sortable_columns();
		$data                  = $this->wcap_abandoned_cart_data();		
		$total_items           = $this->total_count;

		if( count($data) > 0 ) {
		  $this->items = $data;
		} else {
		    $this->items = array();
		}
		$this->set_pagination_args( array(
				'total_items' => $total_items,                  	      // WE have to calculate the total number of items
				'per_page'    => $this->per_page,                     	  // WE have to determine how many items to show on a page
				'total_pages' => ceil( $total_items / $this->per_page )   // WE have to calculate the total number of pages
		      )
		);
		
				

				
		$first_page   = 1;
		$current_page = 1;
		$last_page    = ceil( $total_items / $this->per_page );		

		if ( isset( $_GET['paged'] ) ) {
			$current_page  = ( $_GET['paged'] < 0 ) ? 1 : ( $_GET['paged'] > $last_page ? $last_page : sanitize_text_field( wp_unslash( $_GET['paged'] ) ) );
		}
		
		$previous_page     = ( $current_page > 1 ) ? $current_page - 1 : false;
		$next_page         = ( $current_page < $last_page ) ? $current_page + 1 : false;
		$previous_disabled = ! $previous_page ? 'disabled' : '';
		$next_disabled     = ! $next_page ? 'disabled' : '';	
		
		
		unset( $_REQUEST['wcap_section'] );
		require_once( 'class_wcap_abandoned_orders_table.php' );
		$wcap_abandoned_order_list = new Wcap_Abandoned_Orders_Table();
		$data_abandoned = $wcap_abandoned_order_list->wcap_abandoned_order_prepare_items();
		
		
		$redirected_to_all = false;
		if ( ! $this->total_count ) {
			$redirected_to_all = true;
			$data = $data_abandoned['abandoned_carts'];
			$total_items = $wcap_abandoned_order_list->total_count;
		}
					
		
		return array(
			'abandoned_carts'          => $data,
			'total_items'              => $total_items,
			'total_pages'              => ceil( $total_items / $this->per_page ),
			'trash_count'              => $this->total_count,
			'total_all_count'         => $wcap_abandoned_order_list->total_all_count,
			'registered_count'         => $wcap_abandoned_order_list->total_registered_count,
			'guest_user_count'         => $wcap_abandoned_order_list->total_guest_count,
			'visitor_user_count'       => $wcap_abandoned_order_list->total_visitors_count,
			'unsubscribe_carts_count'  => Wcap_Common::wcap_get_abandoned_order_count( 'wcap_all_unsubscribe_carts' ),
			'current_page'             => $current_page,
			'previous_page'            => $previous_page,
			'next_page'                => $next_page,			
			'last_page'                => $last_page,
			'previous_disabled'        => $previous_disabled,
			'next_disabled'            => $next_disabled,
			'redirected_to_all'        => $redirected_to_all,
			'recovered_text'           => $data_abandoned['recovered_text']
			
		);
	}

	/**
     * It will get the abandoned cart data from data base.
     *
     * @globals mixed $wpdb
     * @return int $results_count total count of Abandoned Cart data.
     * @since  4.3
     */
	public function wcap_get_total_abandoned_count() {
		//phpcs:disable
	    global $wpdb, $start_end_dates;
	    $results                = array();
	    $blank_cart_info        = '{"cart":[]}';
	    $blank_cart_info_guest  = '[]';

	    $ac_cutoff_time 		= is_numeric( get_option( 'ac_cart_abandoned_time', 10 ) ) ? get_option( 'ac_cart_abandoned_time', 10 ) : 10;
	    $cut_off_time   		= $ac_cutoff_time * 60;
	    $current_time   		= current_time( 'timestamp' );
	    $compare_time   		= $current_time - $cut_off_time;

	    $ac_cutoff_time_guest  = is_numeric( get_option( 'ac_cart_abandoned_time_guest', 10 ) ) ? get_option( 'ac_cart_abandoned_time_guest', 10 ) : 10;
	    $cut_off_time_guest    = $ac_cutoff_time_guest * 60;
	    $current_time          = current_time ('timestamp');
	    $compare_time_guest    = $current_time - $cut_off_time_guest;
		$duration_range 	   = "";
		if ( isset( $_POST['duration_select'] ) ) {
		    $duration_range = $_POST['duration_select'];
		}
		if( "" == $duration_range ) {
		    if ( isset( $_GET['duration_select'] ) && '' != $_GET['duration_select'] ) {
		        $duration_range = $_GET['duration_select'];
		    }
		}
		if ( isset( $_SESSION ['duration'] ) && '' != $_SESSION ['duration'] ) {
            $duration_range     = $_SESSION ['duration'];
		}

		if ( "" == $duration_range ) {
		    $duration_range = "last_seven";
		}
		$start_date_range = "";
		if ( isset( $_POST['hidden_start'] ) && '' != $_POST['hidden_start'] ){
			$start_date_range = $_POST['hidden_start'];
		}
		if ( isset( $_SESSION ['hidden_start'] ) &&  '' != $_SESSION ['hidden_start'] ) {
			$start_date_range = $_SESSION ['hidden_start'];
		}
		if ( "" == $start_date_range ) {
			$start_date_range = $start_end_dates[$duration_range]['start_date'];
		}
		$end_date_range = "";
		if ( isset( $_POST['hidden_end'] ) && '' != $_POST['hidden_end'] ){
			$end_date_range = $_POST['hidden_end'];
		}

		if ( isset( $_SESSION ['hidden_end'] ) && '' != $_SESSION ['hidden_end'] ){
			$end_date_range = $_SESSION ['hidden_end'];
		}

		if ( "" == $end_date_range ) {
		    $end_date_range = $start_end_dates[$duration_range]['end_date'];
		}

		$start_date = strtotime( $start_date_range." 00:01:01" );
		$end_date   = strtotime( $end_date_range." 23:59:59" );

		$filtered_status = 'all';
		//phpcs:disable
		if ( isset( $_POST['cart_status'] ) && '' !== $_POST['cart_status'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			$filtered_status = sanitize_text_field( wp_unslash( $_POST['cart_status'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		} elseif ( isset( $_SESSION['cart_status'] ) && '' !== $_SESSION['cart_status'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			$filtered_status = sanitize_text_field( wp_unslash( $_SESSION['cart_status'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		switch ( $filtered_status ) {
			case 'abandoned':
				$status_filter = "( cart_ignored = '0' AND recovered_cart = 0 ) ";
				break;
			case 'recovered':
				$status_filter = "( cart_ignored = '1' AND recovered_cart > 0 ) ";
				break;
			case 'unpaid':
				$status_filter = "( cart_ignored = '2' AND recovered_cart = 0 ) ";
				break;
			case 'cancelled':
				$status_filter = "( wpac.cart_ignored = '2' AND wpac.recovered_cart = 0 ) ";
				break;
			case 'received':
				$status_filter = "( wpac.cart_ignored = '3' AND wpac.recovered_cart = 0 ) ";
				break;
			default:
				$status_filter = "( ( cart_ignored <> '1' AND recovered_cart = 0) OR ( cart_ignored = '1' AND recovered_cart > 0 ) ) ";
				break;
		}
	    // nosemgrep:audit.php.wp.security.sqli.input-in-sinks
	    if( is_multisite() ) {
	        // get main site's table prefix
	        $main_prefix = $wpdb->get_blog_prefix(1);
	        $query = "SELECT  * FROM `".WCAP_ABANDONED_CART_HISTORY_TABLE."` WHERE ( user_type = 'REGISTERED' AND abandoned_cart_time >=  $start_date AND abandoned_cart_time <= $end_date AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_time <= '$compare_time' AND wcap_trash = '1' AND $status_filter ) OR ( user_type = 'GUEST' AND abandoned_cart_time >=  $start_date AND abandoned_cart_time <= $end_date AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_time <= '$compare_time_guest' AND wcap_trash = '1' AND $status_filter ) ORDER BY abandoned_cart_time DESC";
	        $results = $wpdb->get_results($query);// nosemgrep:audit.php.wp.security.sqli.input-in-sinks
	    } else {
	        // non-multisite - regular table name
	        $query = "SELECT * FROM `".WCAP_ABANDONED_CART_HISTORY_TABLE."` WHERE ( user_type = 'REGISTERED' AND abandoned_cart_time >=  $start_date AND abandoned_cart_time <= $end_date AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_time <= '$compare_time' AND wcap_trash = '1' AND $status_filter ) OR ( user_type = 'GUEST' AND abandoned_cart_time >=  $start_date AND abandoned_cart_time <= $end_date AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_time <= '$compare_time_guest' AND wcap_trash = '1' AND $status_filter ) ORDER BY abandoned_cart_time DESC";
	        $results = $wpdb->get_results($query);// nosemgrep:audit.php.wp.security.sqli.input-in-sinks
	    }
	    return count( $results );
	}
	/**
     * It will generate the trashed abandoned cart list data.
     *
     * @globals mixed $wpdb
     * @globals mixed $woocommerce
     * @return array $return_abandoned_orders Key and value of all the columns
     * @since  4.3  
     */
	public function wcap_abandoned_cart_data() {
	    global $wpdb, $woocommerce, $start_end_dates;
		$return_abandoned_orders = array();
		$per_page                = $this->per_page;
		$results                 = array();
		$blank_cart_info         = '{"cart":[]}';
		$blank_cart_info_guest   = '[]';

		if( isset( $_GET['paged'] ) && $_GET['paged'] > 1 ) {
		    $page_number = $_GET['paged'] - 1;
		    $start_limit = ( $per_page * $page_number );
		    $end_limit   =  $per_page;
		    $limit       = 'limit' .' '.$start_limit . ','. $end_limit;

		} else {
		    $start_limit = 0;
		    $end_limit   = $per_page;
		    $limit       = 'limit' .' '.$start_limit . ','. $end_limit;

		}
		$duration_range = "";
		
		if ( isset( $_SESSION ['duration'] ) && '' != $_SESSION ['duration'] ) {
            $duration_range     = $_SESSION ['duration'];
		}
		
		if ( isset( $_POST['duration_select'] ) ) {
		    $duration_range = $_POST['duration_select'];
		}
		if( "" == $duration_range ) {
		    if ( isset( $_GET['duration_select'] ) && '' != $_GET['duration_select'] ) {
		        $duration_range = $_GET['duration_select'];
		    }
		}

		if ( "" == $duration_range ) {
		    $duration_range = "last_seven";
		}
		$start_date_range = "";
        
        if ( isset( $_SESSION ['start_date'] ) &&  '' != $_SESSION ['start_date'] ) {
            $start_date_range = $_SESSION ['start_date'];
		}
		
		if ( isset( $_POST['start_date'] ) && '' != $_POST['start_date'] ){
            $start_date_range = $_POST['start_date'];
        }
		
		if ( "" == $start_date_range ) {
		   $start_date_range = $start_end_dates[$duration_range]['start_date'];
		}
		$end_date_range = "";
        
        if ( isset($_SESSION ['end_date'] ) && '' != $_SESSION ['end_date'] ){
            $end_date_range = $_SESSION ['end_date'];
		}
		
		if ( isset( $_POST['end_date'] ) && '' != $_POST['end_date'] ){
            $end_date_range = $_POST['end_date'];
        }

		if ( "" == $end_date_range ) {
		    $end_date_range = $start_end_dates[$duration_range]['end_date'];
		}

		$start_date = strtotime( $start_date_range." 00:01:01" );
		$end_date   = strtotime( $end_date_range." 23:59:59" );

		$filtered_status = 'all';
		if ( isset( $_POST['cart_status'] ) && '' !== $_POST['cart_status'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			$filtered_status = sanitize_text_field( wp_unslash( $_POST['cart_status'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		} elseif ( isset( $_SESSION['cart_status'] ) && '' !== $_SESSION['cart_status'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			$filtered_status = sanitize_text_field( wp_unslash( $_SESSION['cart_status'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		switch ( $filtered_status ) {
			case 'abandoned':
				$status_filter = "( wpac.cart_ignored = '0' AND wpac.recovered_cart = 0 ) ";
				break;
			case 'recovered':
				$status_filter = "( wpac.cart_ignored = '1' AND wpac.recovered_cart > 0 ) ";
				break;
			case 'unpaid':
				$status_filter = "( wpac.cart_ignored = '2' AND wpac.recovered_cart = 0 ) ";
				break;
			case 'cancelled':
				$status_filter = "( wpac.cart_ignored = '2' AND wpac.recovered_cart = 0 ) ";
				break;
			case 'received':
				$status_filter = "( wpac.cart_ignored = '3' AND wpac.recovered_cart = 0 ) ";
				break;
			default:
				$status_filter = "( ( wpac.cart_ignored <> '1' AND wpac.recovered_cart = 0) OR ( wpac.cart_ignored = '1' AND wpac.recovered_cart > 0 ) ) ";
				break;
		}
		// nosemgrep:audit.php.wp.security.sqli.input-in-sinks
		if( is_multisite() ) {
		    // get main site's table prefix
		    $main_prefix = $wpdb->get_blog_prefix(1);
		    $query = "SELECT wpac . * , wpu.user_login, wpu.user_email FROM `".WCAP_ABANDONED_CART_HISTORY_TABLE."` AS wpac LEFT JOIN ".$main_prefix."users AS wpu ON wpac.user_id = wpu.id WHERE wpac.abandoned_cart_time >=  $start_date AND wpac.abandoned_cart_time <= $end_date AND wpac.abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND wpac.abandoned_cart_info NOT LIKE '$blank_cart_info_guest' AND wpac.wcap_trash = '1' AND $status_filter ORDER BY wpac.abandoned_cart_time DESC $limit";
		    $results = $wpdb->get_results($query);// nosemgrep:audit.php.wp.security.sqli.input-in-sinks
		} else {
		    // non-multisite - regular table name
		    $query = "SELECT wpac . * , wpu.user_login, wpu.user_email FROM `".WCAP_ABANDONED_CART_HISTORY_TABLE."` AS wpac LEFT JOIN ".$wpdb->prefix."users AS wpu ON wpac.user_id = wpu.id WHERE wpac.abandoned_cart_time >=  $start_date AND wpac.abandoned_cart_time <= $end_date AND wpac.abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND wpac.abandoned_cart_info NOT LIKE '$blank_cart_info_guest' AND wpac.wcap_trash = '1' AND $status_filter ORDER BY wpac.abandoned_cart_time DESC $limit";

		    $results = $wpdb->get_results($query);// nosemgrep:audit.php.wp.security.sqli.input-in-sinks
		}

		$i = 0;
		$display_tracked_coupons = get_option( 'ac_track_coupons' );
		foreach( $results as $key => $value ) {
		    if( $value->user_type == "GUEST" ) {
		        $query_guest   = "SELECT * from `" . WCAP_GUEST_CART_HISTORY_TABLE . "`
		                              WHERE id = %d";
		        $results_guest = $wpdb->get_results( $wpdb->prepare( $query_guest, $value->user_id ) );
		    }
		    $abandoned_order_id = $value->id;
		    $user_id            = $value->user_id;
		    $user_login         = $value->user_login;

		    if( $value->user_type == "GUEST" ) {
    		    if( isset( $results_guest[0]->email_id ) ) {
		            $user_email = $results_guest[0]->email_id;
		        } elseif ( $value->user_id == "0" ) {
		            $user_email = '';
		        } else {
		            $user_email = '';
	            }
		        if ( isset( $results_guest[0]->billing_first_name ) ) {
		            $user_first_name = $results_guest[0]->billing_first_name;
		        } else if( $value->user_id == "0" ) {
		            $user_first_name = "Visitor";
		        } else {
		            $user_first_name = "";
		        }
		        if( isset( $results_guest[0]->billing_last_name ) ) {
		            $user_last_name = $results_guest[0]->billing_last_name;
		        } else if( $value->user_id == "0" ) {
		            $user_last_name = "";
		        } else {
		            $user_last_name = "";
		        }
		        if( isset( $results_guest[0]->phone ) ) {
		            $phone = $results_guest[0]->phone;
		        } elseif ( $value->user_id == "0" ) {
		            $phone = '';
		        } else {
		            $phone = '';
		        }
		    } else {
		        $user_email_biiling = get_user_meta( $user_id, 'billing_email', true );
		        $user_email = __( "User Deleted" , "woocommerce-ac" );
		        if( isset( $user_email_biiling ) && "" == $user_email_biiling ) {
		            $user_data  = get_userdata( $user_id );
		            if( isset( $user_data->user_email ) && "" != $user_data->user_email ) {
		            	$user_email = $user_data->user_email;
		        	} 
		        } else if ( '' != $user_email_biiling ) {
		            $user_email = $user_email_biiling;
		        }
		        $user_first_name_temp = get_user_meta( $user_id, 'billing_first_name', true );
		        if( isset( $user_first_name_temp ) && "" == $user_first_name_temp ) {
		            $user_data  = get_userdata( $user_id );
		            if( isset( $user_data->first_name ) && "" != $user_data->first_name ) {
		            	$user_first_name = $user_data->first_name;
		            }  
		        } else {
		            $user_first_name = $user_first_name_temp;
		        }
		        $user_last_name_temp = get_user_meta( $user_id, 'billing_last_name', true );
		        if( isset( $user_last_name_temp ) && "" == $user_last_name_temp ) {
		            $user_data  = get_userdata( $user_id );
		            if( isset( $user_data->last_name ) && "" != $user_data->last_name ) {
		            	$user_last_name = $user_data->last_name;
		            }   
		        } else {
		            $user_last_name = $user_last_name_temp;
		        }
		        $user_phone_number = get_user_meta( $value->user_id, 'billing_phone' );
		        if( isset( $user_phone_number[0] ) ) {
		            $phone = $user_phone_number[0];
		        } else {
		            $phone = "";
		        }
		    }
		    $cart_info        = json_decode( $value->abandoned_cart_info );
		    $order_date       = "";
		    $cart_update_time = $value->abandoned_cart_time;
			$captured_by      = isset( $cart_info->captured_by ) && '' !== $cart_info->captured_by ? $cart_info->captured_by : '';
		    if( $cart_update_time != "" && $cart_update_time != 0 ) {
		        $date_format = date_i18n( get_option( 'date_format' ), $cart_update_time );
                $time_format = date_i18n( get_option( 'time_format' ), $cart_update_time );
                $order_date  = $date_format . ' ' . $time_format;
		    }

		    if( "GUEST" == $value->user_type ) {
                $ac_cutoff_time = is_numeric( get_option( 'ac_cart_abandoned_time_guest', 10 ) ) ? get_option( 'ac_cart_abandoned_time_guest', 10 ) : 10;
		    }else{
		        $ac_cutoff_time = is_numeric( get_option( 'ac_cart_abandoned_time', 10 ) ) ? get_option( 'ac_cart_abandoned_time', 10 ) : 10;
		    }
		    $cut_off_time   = $ac_cutoff_time * 60;
		    $current_time   = current_time( 'timestamp' );
		    $compare_time   = $current_time - $cart_update_time;
		    $cart_details   = new stdClass();
		    $line_total     = 0;
		    $cart_total     = $item_subtotal = $item_total = $line_subtotal_tax_display =  $after_item_subtotal = $after_item_subtotal_display = 0;
            $line_subtotal_tax = 0;
            $wcap_include_tax = get_option( 'woocommerce_prices_include_tax' );
            $wcap_include_tax_setting = get_option( 'woocommerce_calc_taxes' );

		    if( isset( $cart_info->cart ) ) {
		        $cart_details = $cart_info->cart;
		    }
		    $quantity_total = 0;
		    if( ( is_object( $cart_details ) && count( get_object_vars( $cart_details ) ) > 0 ) || ( is_array( $cart_details ) && count( $cart_details ) > 0 ) ) {
    	        foreach( $cart_details as $k => $v ) {

    	        	if( version_compare( $woocommerce->version, '3.0.0', ">=" ) ) {
                      $wcap_product   = wc_get_product($v->product_id );
                      $product        = wc_get_product($v->product_id );
                    }else {
                        $product      = get_product( $v->product_id );
                        $wcap_product = get_product($v->product_id );
                    }
                    if ( false !== $product ) {
	    	            if( isset($wcap_include_tax) && $wcap_include_tax == 'no' && isset($wcap_include_tax_setting) && $wcap_include_tax_setting == 'yes' ) {
	                        //$item_subtotal = $item_subtotal + $v->line_total;  
	                        $line_total    = $line_total + $v->line_total;
	                        $line_subtotal_tax += $v->line_tax; 
	                    }else if ( isset($wcap_include_tax) && $wcap_include_tax == 'yes' &&
	                        isset($wcap_include_tax_setting) && $wcap_include_tax_setting == 'yes' ) {
	                        // Item subtotal is calculated as product total including taxes
	                        if( $v->line_tax != 0 && $v->line_tax > 0 ) {
								$line_subtotal_tax_display += $v->line_tax;
	                            /* After copon code price */
	                            $after_item_subtotal = $item_subtotal + $v->line_total + $v->line_tax;
								/*Calculate the product price*/
								$item_subtotal = $item_subtotal + $v->line_subtotal + $v->line_subtotal_tax;
								$line_total    = $line_total +   $v->line_subtotal + $v->line_subtotal_tax;
	                        } else {
	                            $item_subtotal = $item_subtotal + $v->line_total;
	                            $line_total    = $line_total +  $v->line_total;
	                            $line_subtotal_tax_display += $v->line_tax;
	                        }
	                    }else{
	                    	$line_total = $line_total + $v->line_total;
	                    }
	                    $quantity_total = $quantity_total + $v->quantity;
                	}
    	        }
		    }
		    $line_total     = apply_filters ( 'acfac_change_currency', wc_price( $line_total ), $abandoned_order_id, $line_total, 'wcap_order_trash' );
		    $show_taxes = apply_filters('wcap_show_taxes', true);
		    if( $show_taxes && isset($wcap_include_tax) && $wcap_include_tax == 'no' && isset($wcap_include_tax_setting) && $wcap_include_tax_setting == 'yes' ) {
		    	//$line_subtotal_tax = wc_price( $line_subtotal_tax );
		    	$line_subtotal_tax     = apply_filters ( 'acfac_change_currency', wc_price( $line_subtotal_tax ), $abandoned_order_id, $line_subtotal_tax, 'wcap_order_trash' );
                $line_total = $line_total . '<br>'. __( "Tax: ", "woocommerce-ac" ) . $line_subtotal_tax;

            }else if( isset($wcap_include_tax) && $wcap_include_tax == 'yes' && isset($wcap_include_tax_setting) && $wcap_include_tax_setting == 'yes' ) {
            	//$line_subtotal_tax_display = wc_price( $line_subtotal_tax_display );
            	$line_subtotal_tax_display     = apply_filters ( 'acfac_change_currency', wc_price( $line_subtotal_tax_display ), $abandoned_order_id, $line_subtotal_tax_display, 'wcap_order_trash' );
                if ($show_taxes) {
                	$line_total = $line_total . ' (' . __( "includes Tax: " , "woocommerce-ac" ) . $line_subtotal_tax_display . ')';
            	} 
            	else {
            		$line_total = $line_total;
            	}
            }    
		    if( 1 == $quantity_total ) {
		        $item_disp = __( "item", "woocommerce-ac" );
		    } else {
		        $item_disp = __( "items", "woocommerce-ac" );
		    }
		    $coupon_details          = get_user_meta( $value->user_id, '_woocommerce_ac_coupon', true );
		    $coupon_detail_post_meta = Wcap_Common::wcap_get_coupon_post_meta( $value->id );
		    
		    if( $value->unsubscribe_link == 1 ) {
		        $ac_status_original = $ac_status = __( "Unsubscribed", "woocommerce-ac" );
		        $ac_status = "<span id ='wcap_unsubscribe_link' class = 'unsubscribe_link'  >" . $ac_status . "</span>";
		    }elseif( $value->cart_ignored == 0 && $value->recovered_cart == 0 ) {
		        $ac_status_original = $ac_status = __( "Abandoned", "woocommerce-ac" );
		        $ac_status = "<span id ='wcap_abandoned_new' class = 'wcap_abandoned_new'  >" . $ac_status . "</span>";
		    } elseif( $value->cart_ignored == 1 && $value->recovered_cart == 0 ) {
		        $ac_status_original = $ac_status = __( "Abandoned but <br> new cart created after this", "woocommerce-ac" );
		        $ac_status = "<span id ='wcap_abandoned_new' class = 'wcap_abandoned_new'  >" . $ac_status . "</span>";
		    } elseif( $value->cart_ignored == 1 && $value->recovered_cart > 0 ) {
		        $ac_status_original = $ac_status = __( "Recovered", "woocommerce-ac" );
		        $ac_status = "<span id ='wcap_abandoned' class = 'wcap_abandoned'  >" . $ac_status . "</span>";
		    } elseif( $value->cart_ignored == 2 && $value->recovered_cart == 0 ) {
		        $ac_status_original = $ac_status = __( "Abandoned - Order Unpaid", "woocommerce-ac" );
		        $ac_status = "<span id ='wcap_abandoned_unpaid' class = 'wcap_abandoned_unpaid'  >" . $ac_status . "</span>";
		    } else {
		        $ac_status_original = $ac_status = "";
		    }
			
			$user_role = '';
			$user_role = 'GUEST' === $value->user_type ? 'Guest' : Wcap_Common::wcap_get_user_role ( $user_id );

		    $coupon_code_used = $coupon_code_message = "";
		    if ( $compare_time > $cut_off_time && $ac_status != "" ) {
		        $return_abandoned_orders[$i] = new stdClass();
                if( $quantity_total > 0 ) {
                    $abandoned_order_id                         = $abandoned_order_id;
                    $customer_information                       = $user_first_name . " ".$user_last_name;
                    $return_abandoned_orders[ $i ]->id          = $abandoned_order_id;
                    $return_abandoned_orders[ $i ]->email       = $user_email;
                    if( $phone == '' ) {
                        $return_abandoned_orders[ $i ]->customer    = $customer_information . "<br>" . $user_role ;
                    } else {
                        $return_abandoned_orders[ $i ]->customer    = $customer_information . "<br>" . $phone . "<br>" . $user_role ;
						
                    }
					
					$return_abandoned_orders[ $i ]->customer_details = $return_abandoned_orders[ $i ]->customer;
					$return_abandoned_orders[ $i ]->status_original        = $ac_status_original;
					 
					$return_abandoned_orders[ $i ]->captured_by  = $captured_by;
					
                    $return_abandoned_orders[ $i ]->order_total = $line_total . "<br>" . $quantity_total . " " . $item_disp;;
                    $return_abandoned_orders[ $i ]->quantity    = $quantity_total . " " . $item_disp;
                    $return_abandoned_orders[ $i ]->date        = $order_date;
                    $return_abandoned_orders[ $i ]->status      = $ac_status;
                    $return_abandoned_orders[ $i ]->user_id     = $user_id;

                    if( $display_tracked_coupons == 'on' ) {
						if( is_array( $coupon_detail_post_meta ) && count( $coupon_detail_post_meta ) > 0 ) {
							foreach( $coupon_detail_post_meta as $key => $value ) {
								if( '' !== $key ) {
									$coupon_code_used .= $key . "</br>";
									$coupon_code_message .= $value . "</br>";
								}
							}
							$return_abandoned_orders[ $i ]->coupon_code_used = $coupon_code_used;
							$return_abandoned_orders[ $i ]->coupon_code_status = $coupon_code_message;
						}
                    }
                } else {
                   $abandoned_order_id                    = $abandoned_order_id;
                   $return_abandoned_orders[ $i ]->id     = $abandoned_order_id;
                   $return_abandoned_orders[ $i ]->date   = $order_date;
                   $return_abandoned_orders[ $i ]->status = $ac_status;
                }
                $i++;
            }
		}//phpcs:enable
    	return apply_filters( 'wcap_abandoned_orders_table_data', $return_abandoned_orders );
    }
}
