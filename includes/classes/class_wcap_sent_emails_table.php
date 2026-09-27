<?php
/**
 * Abandoned Cart Pro for WooCommerce
 *
 * It will show records of the Abandoned cart reminder email which is sent to customers on Sent Email tab.
 * 
 * @author   Tyche Softwares
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Classes
 * @category Classes
 * @since    5.0
 */
// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Show abandoned cart reminder emails record on Send Emails tab.
 * 
 * @since 2.4.7
 */
class Wcap_Sent_Emails_Table extends WP_List_Table {

	/**
	 * Number of results to show per page
	 *
	 * @var string
	 * @since 2.5
	 */
	public $per_page = 30;

	/**
	 * URL of this page
	 *
	 * @var string
	 * @since 2.5
	 */
	public $base_url;

	/**
	 * Total number of Sent Emails
	 *
	 * @var int
	 * @since 2.5
	 */
	public $total_count;

	/**
	 * Total number of Open Emails
	 *
	 * @var int
	 * @since 2.5
	 */
	public $open_emails;

	/**
	 * Total amount of Links clicked
	 *
	 * @var int
	 * @since 2.5
	 */
	public $link_click_count;

	/**
	 * Start date
	 *
	 * @var int
	 * @since 2.5
	 */
	public $start_date_db;

	/**
	 * End date
	 *
	 * @var int
	 * @since 2.5
	 */
	public $end_date_db;

	/**
	 * Duration
	 *
	 * @var int
	 * @since 2.5
	 */
	public $duration;

   	/**
	 * It will add the bulk action and other variable needed for the class.
	 *
	 * @since 2.5
	 * @see WP_List_Table::__construct()
	 */
	public function __construct() {
		global $status, $page;
		// Set parent defaults
		parent::__construct( array(
	        'singular' => __( 'sent_email_id', 'woocommerce-ac' ), //singular name of the listed records
	        'plural'   => __( 'sent_email_ids', 'woocommerce-ac' ), //plural name of the listed records
			'ajax'      => true             			// Does this table support ajax?
		) );

		$this->base_url = admin_url( 'admin.php?page=woocommerce_ac_page&action=stats' );
	}
	
	/**
	 * It will prepare the list of the User Email Address, columns, pagination, sortable column and other data.
	 *
	 * @since 2.5
	 */
	public function wcap_sent_emails_prepare_items() {

		
		$hidden                = array(); // No hidden columns
		$data                  = $this->wcap_sent_emails_data();
		$total_items           = $this->total_count;
 		$open_emails           = $this->open_emails;
 		$link_click_count      = $this->link_click_count;
 		$end_date_db           = $this->end_date_db;
 		$start_date_db         = $this->start_date_db;
        $duration              = $this->duration;
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
		$pageparams        = '';
		
		return array( 
		'email_reminders' => $data,
		'open_emails' => $open_emails,
		'link_click_count' => $link_click_count,
		'total_items' => $total_items,
		'total_pages' =>  ceil( $total_items / $this->per_page ),
		'current_page'             => $current_page,
			'previous_page'            => $previous_page,
			'next_page'                => $next_page,			
			'last_page'                => $last_page,
			'previous_disabled'        => $previous_disabled,
			'next_disabled'            => $next_disabled,
			'pageparams'               => $pageparams,
		);
	
	}

	/**
	 * If the customer click on the cart or checkout page link from email notification then we get the record of abandoned cart reminder email. 
	 *
	 * @param array $start_date_db Selected start date
	 * @param array $end_date_db Selected end date
	 * @return string $wcap_link_clicked_count Link clicked count
	 * @since 2.0
	 */
	public static function wcap_get_link_click_count ( $start_date_db, $end_date_db ) {
		global $wpdb;

		$wcap_link_clicked       = "SELECT COUNT( DISTINCT( wplc.notification_sent_id ) ) FROM " . WCAP_LINK_CLICKED_TABLE . " as wplc 
									LEFT JOIN " . WCAP_EMAIL_SENT_HISTORY_TABLE . " AS wpsh ON wplc.notification_sent_id = wpsh.id 
									WHERE wplc.time_clicked >= '" . $start_date_db . "' AND 
									wplc.time_clicked <= '" . $end_date_db . "' 
									AND wpsh.notification_type='email'";//phpcs:disable
		$wcap_link_clicked_count = $wpdb->get_var( $wcap_link_clicked );

		return $wcap_link_clicked_count;
	}
	/**
	 * We get the abandoned cart reminder email opened record once the customer openes the email notification. 
	 *
	 * @param array $start_date_db Selected start date
	 * @param array $end_date_db Selected end date
	 * @return string $wcap_email_open_count Email opened count
	 * @since 2.0
	 */
	public static function wcap_get_open_count ( $start_date_db, $end_date_db ) {
		global $wpdb;
		$wcap_email_open       = "SELECT COUNT( DISTINCT( wpoe.notification_sent_id ) ) FROM " . WCAP_NOTIFICATION_OPENED_TABLE . " as wpoe 
									LEFT JOIN " . WCAP_EMAIL_SENT_HISTORY_TABLE . " AS wpsh ON wpoe.notification_sent_id = wpsh.id 
									WHERE time_opened >= '" . $start_date_db . "' AND time_opened <= '" . $end_date_db . "' 
									AND wpsh.id = wpoe.notification_sent_id ";
		$wcap_email_open_count = $wpdb->get_var( $wcap_email_open );

		return $wcap_email_open_count;
	}
	/**
     * It will generate the sent abandoned cart reminder emails list for Sent Emails tab.
     *
     * @globals mixed $wpdb
     * @globals mixed $woocommerce
     * @return array $return_sent_email_display Key and value of all the columns
     * @since 2.0
     */
	public function wcap_sent_emails_data() {

		if( session_id() === '' ){
		    //session has not started
		    session_start();
		}
		global $wpdb, $start_end_dates;
		$ac_results_sent  = $ac_results_opened = array();
		$duration_range   = '';
		$wcap_date_format = get_option( 'date_format' );
		$wcap_time_format = get_option( 'time_format' );

		if ( isset( $_POST['duration_select_email'] ) && '' != $_POST['duration_select_email'] ){
		    $duration_range         = $_POST['duration_select_email'];
		    $_SESSION['duration']   = $duration_range;
		}

		if ( isset( $_SESSION ['duration'] ) && '' != $_SESSION ['duration'] ){
            $duration_range         = $_SESSION ['duration'];
		}

		if ( '' == $duration_range ) {
		    $duration_range         = "last_seven";
		    $_SESSION['duration']   = $duration_range;
		}

		$start_date_range = '';
		if ( isset( $_POST['hidden_start'] ) && '' != $_POST['hidden_start'] ) {
		    $start_date_range        = $_POST['hidden_start'];
		    $_SESSION ['start_date'] = $start_date_range;
		}

		if ( isset( $_SESSION ['start_date'] ) &&  '' != $_SESSION ['start_date'] ) {
            $start_date_range = $_SESSION ['start_date'];
		}

		if ( '' == $start_date_range ) {
		   $start_date_range = $start_end_dates[$duration_range]['start_date'];
		   $_SESSION ['start_date'] = $start_date_range;
		}

		$end_date_range = '';
		if ( isset( $_POST['hidden_end'] ) && '' != $_POST['hidden_end'] ) {
            $end_date_range = $_POST['hidden_end'];
            $_SESSION ['end_date'] = $end_date_range;
        }

		if ( isset($_SESSION ['end_date'] ) && '' != $_SESSION ['end_date'] ){
            $end_date_range = $_SESSION ['end_date'];
		}

		if ( '' == $end_date_range ) {
		    $end_date_range = $start_end_dates[$duration_range]['end_date'];
		    $_SESSION ['end_date'] = $end_date_range;
		}

		$start_date    		= strtotime( $start_date_range." 00:01:01" );
		$end_date      		= strtotime( $end_date_range." 23:59:59" );
		$start_date_db 		= date( 'Y-m-d H:i:s', $start_date );
		$end_date_db   		= date( 'Y-m-d H:i:s', $end_date );

        if( version_compare( WOOCOMMERCE_VERSION, "2.3" ) < 0 ) {
		    $checkout_page_id   = get_option( 'woocommerce_checkout_page_id' );
		    
		    if( $checkout_page_id ) {
                $checkout_page      = get_post( $checkout_page_id );
                $checkout_page_link = $checkout_page->guid;
		    } else {
		        $checkout_page_link = '';
		    }
		    
		    $cart_page_id       = get_option( 'woocommerce_cart_page_id' );
		    
		    if( $cart_page_id ) {
    		    $cart_page          = get_post( $cart_page_id );
    		    $cart_page_link     = $cart_page->guid;
		    } else {
		        $cart_page_link     = '';
		    }
		    
		} else {
            $checkout_page_id   = wc_get_page_id( 'checkout' );
            $checkout_page_link = $checkout_page_id ? get_permalink( $checkout_page_id ) : '';
            
            $cart_page_id   = wc_get_page_id( 'cart' );
            $cart_page_link = $cart_page_id ? get_permalink( $cart_page_id ) : '';
            
		}
		
		$per_page        = $this->per_page;
		$offset           = 0;
		if ( isset( $_GET['paged'] ) && $_GET['paged'] > 1 ) {
			$page_number = $_GET['paged'] - 1;
			$offset           = $per_page * $page_number;
		}

		$total_query = "SELECT COUNT(wpsh.id) as count FROM   " . WCAP_EMAIL_SENT_HISTORY_TABLE . "   AS wpsh  LEFT JOIN    ". WCAP_ABANDONED_CART_HISTORY_TABLE . "   AS wpac  ON  
						( wpsh.cart_id = wpac.id) 
						WHERE 
 						wpsh.sent_time >= %s  AND wpsh.sent_time <= %s  AND  notification_type = %s  ";

		$ac_get_count = $wpdb->get_results( $wpdb->prepare( $total_query, $start_date_db, $end_date_db, 'email' ) );
		$this->total_count = $ac_get_count[0]->count;

		/* Now we use the LIMIT clause to grab a range of rows */
		$ac_results_sent = $wpdb->get_results( // phpcs:ignore
			$wpdb->prepare(
				'SELECT    wpsh.sent_time, wpsh.template_id, wpsh.cart_id, wpsh.id, wpsh.sent_notification_contact as sent_email_id, wpsh.wc_order_id, wpac.email_reminder_status, wpac.recovered_cart 
				FROM   ' . WCAP_EMAIL_SENT_HISTORY_TABLE . '   AS wpsh  LEFT JOIN    ' . WCAP_ABANDONED_CART_HISTORY_TABLE . '   AS wpac  ON  
				( wpsh.cart_id = wpac.id ) 
				WHERE 
 				wpsh.sent_time >= %s  AND wpsh.sent_time <= %s AND  notification_type = %s 
				ORDER BY wpsh.id DESC LIMIT %d OFFSET %d',				
				$start_date_db,
				$end_date_db,
				'email',
				$per_page,
				$offset
			)
		);
		$this->open_emails      = $this->wcap_get_open_count ( $start_date_db, $end_date_db ); //count ( $ac_results_opened );
		$this->link_click_count = $this->wcap_get_link_click_count ($start_date_db, $end_date_db); // count ( $ac_results_clicked );

		$i = 0;
		
    	foreach ( $ac_results_sent as $key => $value ) {

		    $sent_tmstmp                = strtotime( $value->sent_time );
		    $sent_date_format  			= date_i18n( $wcap_date_format, $sent_tmstmp );
            $sent_time_format   	    = date_i18n( $wcap_time_format, $sent_tmstmp );
		    $sent_date                  = $sent_date_format . ' ' . $sent_time_format;
		    $query_template_name        = "SELECT template_name FROM " . WCAP_NOTIFICATION_TEMPLATES_TABLE . " WHERE id= %d";
		    $ac_results_template_name   = $wpdb->get_results( $wpdb->prepare( $query_template_name, $value->template_id ) );

		    $link_clicked               = '';

		    $ac_email_template_name     = '';
		    if ( isset( $ac_results_template_name[0]->template_name ) ) {
		        $ac_email_template_name = $ac_results_template_name[0]->template_name;
		    }

		    if ( isset( $value->email_reminder_status ) && 'manual' == $value->email_reminder_status ) {
		        $ac_email_template_name .= ' (#'. $value->template_id  .' manual )' ;
		    }
			if ( '' === $ac_email_template_name ) {
				$ac_email_template_name = '#' . $value->template_id;
			}
            $return_sent_emails[ $i ]     = new stdClass();

			$query_ac_clicked   = "SELECT DISTINCT wplc.notification_sent_id, wplc.link_clicked FROM " . WCAP_LINK_CLICKED_TABLE . " as wplc 
									LEFT JOIN ".WCAP_EMAIL_SENT_HISTORY_TABLE." AS wpsh ON wplc.notification_sent_id = wpsh.id 
									WHERE wplc.notification_sent_id = %d
									 ORDER BY wplc.id DESC ";
			$ac_results_clicked = $wpdb->get_results( $wpdb->prepare( $query_ac_clicked, $value->id  ) ) ;

			if ( count( $ac_results_clicked ) > 0 ) {
				if( $ac_results_clicked[0]->link_clicked == $checkout_page_link ) {
	                $link_clicked   = "Checkout Page";
	            } elseif( $ac_results_clicked[0]->link_clicked == $cart_page_link ) {
	                $link_clicked   = "Cart Page";
	            }
        	}
			
			$query_ac_opened   = "SELECT DISTINCT wpoe.time_opened  FROM " . WCAP_NOTIFICATION_OPENED_TABLE . " as 
        							wpoe LEFT JOIN ".WCAP_EMAIL_SENT_HISTORY_TABLE." AS wpsh ON wpsh.id = wpoe.notification_sent_id 
									WHERE wpoe.notification_sent_id = %d ";
			$ac_results_opened = $wpdb->get_results( $wpdb->prepare( $query_ac_opened, $value->id  ) );

			
		    $email_opened = "";

		    if ( count( $ac_results_opened ) >  0 ) {
		    	$opened_tmstmp 		= strtotime( $ac_results_opened[0]->time_opened );
	    		$opened_date_format = date_i18n( $wcap_date_format, $opened_tmstmp );
        		$opened_time_format = date_i18n( $wcap_time_format, $opened_tmstmp );
	            $email_opened  		=  $opened_date_format . ' ' . $opened_time_format;
		    } 
		    $recover_id 	= '';
            $view_name_flag = 'Abandoned';
			$edit_link      = '';

		    if ( isset( $value->recovered_cart ) && $value->recovered_cart != 0 ) {
                $recover_id = $value->recovered_cart;
	            if ( is_hpos_enabled() ) { // HPOS usage is enabled.
					$edit_link = admin_url( 'admin.php?page=wc-orders&action=edit&id=' . absint( $recover_id ) );
				} else {
					$edit_link = admin_url( 'post.php?post=' . absint( $recover_id ) . '&action=edit' );
				}
	            $view_name_flag = __( 'Recovered', 'woocommerce-ac' );
		    } elseif ( isset( $value->wc_order_id ) && $value->wc_order_id > 0 ) {
				$recover_id     = $value->wc_order_id;
				$view_name_flag = '';
			}
		    $return_sent_emails[ $i ]->sent_time          = $sent_date ;
		    $return_sent_emails[ $i ]->user_email_id      = $value->sent_email_id;
		    $return_sent_emails[ $i ]->date_time_opened   = $email_opened;
		    $return_sent_emails[ $i ]->link_clicked       = $link_clicked;
		    $return_sent_emails[ $i ]->template_name      = $ac_email_template_name;
		    $return_sent_emails[ $i ]->display_link       = $view_name_flag;
		    $return_sent_emails[ $i ]->abandoned_order_id = $value->cart_id;
		    $return_sent_emails[ $i ]->recover_order_id   = $recover_id;
		    $return_sent_emails[ $i ]->email_sent_id      = $value->id;
			$return_sent_emails[ $i ]->recover_order_link = $edit_link;

		    $i++;
		 }

		$return_sent_email_display = ( isset( $return_sent_emails ) ) ? $return_sent_emails : array();
		return apply_filters( 'wcap_sent_emails_table_data', $return_sent_email_display );
	}

}
