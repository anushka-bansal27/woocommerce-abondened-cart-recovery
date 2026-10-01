<?php
/**
 * Abandoned Cart Pro for WooCommerce
 *
 * It will show Abandoned Carts data on Abandoned Orders tab.
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
 * @since 2.4.7
 */
class Wcap_Abandoned_Orders_Table extends WP_List_Table {

	/**
	 * Number of results to show per page
	 *
	 * @var string
	 * @since 2.4.7
	 */
	public $per_page = 30;

	/**
	 * URL of this page
	 *
	 * @var string
	 * @since 2.4.7
	 */
	public $base_url;

	/**
	 * Total number of abandoned carts
	 *
	 * @var int
	 * @since 2.4.7
	 */
	public $total_count;

	/**
	 * Total number of carts
	 *
	 * @var int
	 * @since 7.14.0
	 */
	public $total_all_count;

	/**
	 * Total number of registered user carts
	 *
	 * @var int
	 * @since 7.14.0
	 */
	public $total_registered_count;

	/**
	 * Total number of guest carts
	 *
	 * @var int
	 * @since 7.14.0
	 */
	public $total_guest_count;

	/**
	 * Total number of visitor carts
	 *
	 * @var int
	 * @since 7.14.0
	 */
	public $total_visitors_count;

	/**
	 * Total number of recovered carts
	 *
	 * @var int
	 * @since 8.3
	 */
	public static $recovered_count;

	/**
	 * Total amount of recovered orders
	 *
	 * @var float
	 * @since 8.3
	 */
	public static $recovered_amount;

    /**
	 * It will add the bulk action and other variable needed for the class.
	 *
	 * @since 2.4.7
	 * @see WP_List_Table::__construct()
	 */
	public function __construct() {
		global $status, $page;
		// Set parent defaults
		parent::__construct( array(
    	        'singular' => __( 'abandoned_order_id', 'woocommerce-ac' ),  //singular name of the listed records
    	        'plural'   => __( 'abandoned_order_ids', 'woocommerce-ac' ), //plural name of the listed records
    			'ajax'     => false             			                 // Does this table support ajax?
    		    )
		);
		$this->process_bulk_action();
        $this->base_url = admin_url( 'admin.php?page=woocommerce_ac_page&action=listcart' );//phpcs:disable
	}

	/**
	 * It will prepare the list of the abandoned carts, columns, pagination, sortable column and other data.
	 *
	 * @since 2.0
	 */
	public function wcap_abandoned_order_prepare_items() {
		$this->per_page = apply_filters( 'wcap_abandoned_orders_per_page_count', $this->per_page );

		if ( ! isset( $_POST['paged'] ) ) {
			unset( $_SESSION['duration'] );
			unset( $_SESSION['hidden_start'] );
			unset( $_SESSION['hidden_end'] );
			unset( $_SESSION['cart_source'] );
			unset( $_SESSION['cart_status'] );
		}

		$hidden                = array(); // No hidden columns
		$this->total_count     = $this->wcap_get_total_abandoned_count();
		$redirected_to_all     = false;
		if ( ! $this->total_count ) {
			unset( $_REQUEST[ 'wcap_section' ] );
			$this->total_count     = $this->wcap_get_total_abandoned_count();
			$redirected_to_all = true;
		}
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
		
		if ( self::$recovered_count > 0 ) {
			$recovered_text = wp_kses_post(
					sprintf(
							// Translators: Recovered carts count & amount of the orders recovered.
						__( '<b>%1$d</b> carts worth <b>%2$s</b> were recovered during the selected range.', 'woocommerce-ac' ),
						esc_attr( self::$recovered_count ),
						esc_attr( get_woocommerce_currency_symbol() . self::$recovered_amount )
					)
				);
		} else {
			$recovered_text = __( 'No carts were recovered during the selected range', 'woocommerce-ac' );
		}
		
		
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

		$google_sheets_class = Wcap_Google_Sheets::get_instance();
		$google_sheet_status = $google_sheets_class->wcap_return_google_sheet_connection_status();
		return array(
			'abandoned_carts'          => $data,
			'total_items'              => $total_items,
			'total_all_count'          => $this->total_all_count,
			'total_pages'              => ceil( $total_items / $this->per_page ),
			'trash_count'              => Wcap_Common::wcap_get_abandoned_order_count( 'wcap_trash_abandoned' ),
			'registered_count'         => $this->total_registered_count,
			'guest_user_count'         => $this->total_guest_count,
			'visitor_user_count'       => $this->total_visitors_count,
			'unsubscribe_carts_count'  => Wcap_Common::wcap_get_abandoned_order_count( 'wcap_all_unsubscribe_carts' ),
			'recovered_text'           => $recovered_text,
			'current_page'             => $current_page,
			'previous_page'            => $previous_page,
			'next_page'                => $next_page,			
			'last_page'                => $last_page,
			'previous_disabled'        => $previous_disabled,
			'next_disabled'            => $next_disabled,
			'redirected_to_all'        => $redirected_to_all,
			'google_sheets_enabled'    => $google_sheet_status,
		);
	}

	/**
     * It will get the abandoned cart data from data base.
     *
     * @globals mixed $wpdb
     * @return int $results_count total count of Abandoned Cart data.
     * @since   2.0
     */
	public function wcap_get_total_abandoned_count() {
	    global $wpdb, $start_end_dates;
	    $results               = array();
	    $blank_cart_info       = '%"cart":[]%';
	    $blank_cart_info_guest = '[]';
	    $ac_cutoff_time  	   = is_numeric( get_option( 'ac_cart_abandoned_time', 10 ) ) ? get_option( 'ac_cart_abandoned_time', 10 ) : 10;
	    $cut_off_time   	   = $ac_cutoff_time * 60;
	    $current_time   	   = current_time( 'timestamp' );
	    $compare_time  		   = $current_time - $cut_off_time;
	    $ac_cutoff_time_guest  = is_numeric( get_option( 'ac_cart_abandoned_time_guest', 10 ) ) ? get_option( 'ac_cart_abandoned_time_guest', 10 ) : 10;
	    $cut_off_time_guest    = $ac_cutoff_time_guest * 60;
	    $compare_time_guest    = $current_time - $cut_off_time_guest;
	    $get_section_of_page   = Wcap_Abandoned_Orders_Table::wcap_get_current_section ();
		$duration_range = "";

		if ( session_id() === '' ) {
			//session has not started
			session_start();
		}

		if ( isset( $_SESSION ['duration'] ) && '' != $_SESSION ['duration'] ) {
            $duration_range     = sanitize_text_field( wp_unslash( $_SESSION ['duration'] ) );
		}

		if ( isset( $_POST['duration_select'] ) ) {
		    $duration_range = $_POST['duration_select'];
		}
		if( "" == $duration_range ) {
		    if ( isset( $_GET['duration_select'] ) && '' != $_GET['duration_select'] ) {
		        $duration_range = sanitize_text_field( wp_unslash( $_GET['duration_select'] ) );
		    }
		}
		
		if ( "" == $duration_range ) {
		    $duration_range = "last_seven";
		}
		$start_date_range = "";

		if ( isset( $_SESSION ['hidden_start'] ) &&  '' != $_SESSION ['hidden_start'] ) {
			$start_date_range = sanitize_text_field( wp_unslash( $_SESSION ['hidden_start'] ) );
		}

		if ( isset( $_POST['hidden_start'] ) && '' != $_POST['hidden_start'] ){
			$start_date_range = sanitize_text_field( wp_unslash( $_POST['hidden_start'] ) );//phpcs:diable
		}

		if ( "" == $start_date_range ) {
			$start_date_range = $start_end_dates[$duration_range]['start_date'];
		}
		$end_date_range = "";

		if ( isset( $_SESSION ['hidden_end'] ) && '' != $_SESSION ['hidden_end'] ){
			$end_date_range = $_SESSION ['hidden_end'];
		}

		if ( isset( $_POST['hidden_end'] ) && '' != $_POST['hidden_end'] ){
			$end_date_range = $_POST['hidden_end'];
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

		$filtered_source = 'all';
		if ( isset( $_POST['cart_source'] ) && '' !== $_POST['cart_source'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			$filtered_source = sanitize_text_field( wp_unslash( $_POST['cart_source'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		} elseif ( isset( $_SESSION['cart_source'] ) && '' !== $_SESSION['cart_source'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			$filtered_source = sanitize_text_field( wp_unslash( $_SESSION['cart_source'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		switch ( $filtered_status ) {
			case 'abandoned':
				$status_filter = "( cart_ignored = '0' AND recovered_cart = 0 ) ";
				break;
			case 'recovered':
				$status_filter = "( cart_ignored = '1' AND recovered_cart > 0 ) ";
				break;
			case 'unpaid':
				$status_filter = "( cart_ignored = '4' AND recovered_cart = 0 ) ";
				break;
			case 'received':
				$status_filter = "( cart_ignored = '3' AND recovered_cart = 0 ) ";
				break;
			case 'cancelled':
				$status_filter = "( cart_ignored = '2' AND recovered_cart = 0 ) ";
				break;
			default:
				$status_filter = "( ( cart_ignored <> '1' AND recovered_cart = 0) OR ( cart_ignored = '1' AND recovered_cart > 0 ) ) ";
				break;
		}

		switch ( $filtered_source ) {
			case 'all':
			default:
				$source_filter = '';
				break;
			case 'checkout_page':
				$source_filter = "AND abandoned_cart_info like '%checkout%' AND user_id >= 63000000";
				break;
			case 'product_page':
				$source_filter = 'AND user_id > 0 AND user_id < 63000000';
				break;
			case 'atc':
				$source_filter = "AND abandoned_cart_info like '%atc%' AND user_id >= 63000000";
				break;
			case 'exit_intent':
				$source_filter = "AND abandoned_cart_info like '%exit_intent%'";
				break;
			case 'custom_form':
				$source_filter = "AND abandoned_cart_info like '%custom_form%'";
				break;
			case 'url':
				$source_filter = "AND abandoned_cart_info LIKE '%url%'";
				break;
		}

		$results_count = array();

		$this->total_all_count = $wpdb->get_var( // phpcs:ignore
			$wpdb->prepare(
				'SELECT COUNT(id) FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE ( ( user_type = 'REGISTERED' AND abandoned_cart_time <= %d ) OR ( user_type = 'GUEST' AND abandoned_cart_info NOT LIKE '%s'  AND abandoned_cart_time <= %d ) ) AND abandoned_cart_time >= %d AND abandoned_cart_time <= %d AND abandoned_cart_info NOT LIKE %s AND wcap_trash = '' AND $status_filter $source_filter ORDER BY abandoned_cart_time DESC", // phpcs:ignore
				$compare_time,
				$blank_cart_info_guest,
				$compare_time_guest,
				$start_date,
				$end_date,
				"%$blank_cart_info%"
			)
		);

		$this->total_registered_count = $wpdb->get_var( // phpcs:ignore
			$wpdb->prepare(
				'SELECT COUNT(id) FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE user_type = 'REGISTERED' AND abandoned_cart_time >= %d AND abandoned_cart_time <= %d AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_time <= %d AND wcap_trash = '' AND $status_filter $source_filter ORDER BY abandoned_cart_time DESC", // phpcs:ignore
				$start_date,
				$end_date,
				"%$blank_cart_info%",
				$compare_time
			)
		);

		$this->total_guest_count = $wpdb->get_var( // phpcs:ignore
			$wpdb->prepare(
				'SELECT COUNT(id) FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE user_type = 'GUEST' AND abandoned_cart_time >= %d AND abandoned_cart_time <= %d AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_time <= %d AND wcap_trash = '' AND user_id >= 63000000 AND $status_filter $source_filter ORDER BY abandoned_cart_time DESC", // phpcs:ignore
				$start_date,
				$end_date,
				$blank_cart_info_guest,
				"%$blank_cart_info%",
				$compare_time_guest,
			)
		);

		$this->total_visitors_count = $wpdb->get_var( // phpcs:ignore
			$wpdb->prepare(
				'SELECT COUNT(id) FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE user_type = 'GUEST' AND abandoned_cart_time >= %d AND abandoned_cart_time <= %d AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_time <= %d AND wcap_trash = '' AND user_id = 0 AND $status_filter $source_filter ORDER BY abandoned_cart_time DESC", // phpcs:ignore
				$start_date,
				$end_date,
				$blank_cart_info_guest,
				"%$blank_cart_info%",
				$compare_time_guest
			)
		);

		// Set up the total count for the view being displayed.
		switch ( $get_section_of_page ) {
			case 'wcap_all_abandoned':
				$results_count = $this->total_all_count;
				break;
			case 'wcap_all_registered':
				$results_count = $this->total_registered_count;
				break;
			case 'wcap_all_guest':
				$results_count = $this->total_guest_count;
				break;
			case 'wcap_all_visitor':
				$results_count = $this->total_visitors_count;
				break;
			case 'wcap_all_unsubscribe_carts':
				$results_count = $wpdb->get_var( // phpcs:ignore
					$wpdb->prepare(
						'SELECT COUNT(id) FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "`  WHERE abandoned_cart_time >= %d AND abandoned_cart_time <= %d AND wcap_trash = '' AND unsubscribe_link = '1' ORDER BY abandoned_cart_time DESC", // phpcs:ignore
						$start_date,
						$end_date
					)
				);
				break;
			default:
				$results_count = 0;
				break;
		}
		return $results_count;
	}
	/**
     * It will generate the abandoned cart list data.
     *
     * @globals mixed $wpdb
     * @globals mixed $woocommerce
     * @return array $return_abandoned_orders Key and value of all the columns
     * @since 2.0
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
		$get_section_of_page   = Wcap_Abandoned_Orders_Table::wcap_get_current_section ();
		$duration_range = "";
		if ( isset( $_POST['duration_select'] ) ) {
		    $duration_range       = $_POST['duration_select'];
		    $_SESSION['duration'] = $duration_range;
		}
		if ( "" == $duration_range ) {
		    if ( isset( $_GET['duration_select'] ) && '' != $_GET['duration_select'] ) {
		        $duration_range = $_GET['duration_select'];
		    }
		}
		if ( '' === $duration_range && isset( $_SESSION ['duration'] ) && '' != $_SESSION ['duration'] ) {
            $duration_range = $_SESSION ['duration'];
		}

		if ( "" === $duration_range ) {
		    $duration_range = "last_seven";
		}
		$start_date_range = "";
		if ( isset( $_POST['hidden_start'] ) && '' != $_POST['hidden_start'] ){
			$start_date_range         = $_POST['hidden_start'];
			$_SESSION['hidden_start'] = $start_date_range;
		}
		if ( '' === $start_date_range && isset( $_SESSION ['hidden_start'] ) &&  '' != $_SESSION ['hidden_start'] ) {
			$start_date_range = $_SESSION ['hidden_start'];
		}

		if ( "" == $start_date_range ) {
			$start_date_range = $start_end_dates[$duration_range]['start_date'];
		}
		$end_date_range = "";
		if ( isset( $_POST['hidden_end'] ) && '' != $_POST['hidden_end'] ){
			$end_date_range          = $_POST['hidden_end'];
			$_SESSION ['hidden_end'] = $end_date_range;
		}

		if ( '' === $end_date_range && isset( $_SESSION ['hidden_end'] ) && '' != $_SESSION ['hidden_end'] ){
			$end_date_range = $_SESSION ['hidden_end'];
		}

		if ( "" == $end_date_range ) {
		    $end_date_range = $start_end_dates[$duration_range]['end_date'];
		}

		$start_date = strtotime( $start_date_range." 00:01:01" );
		$end_date   = strtotime( $end_date_range." 23:59:59" );

		$filtered_status = 'all';
		if ( isset( $_POST['cart_status'] ) && '' !== $_POST['cart_status'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			$filtered_status = sanitize_text_field( wp_unslash( $_POST['cart_status'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			$_SESSION['cart_status'] = $filtered_status;
		} elseif ( isset( $_SESSION['cart_status'] ) && '' !== $_SESSION['cart_status'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			$filtered_status = sanitize_text_field( wp_unslash( $_SESSION['cart_status'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		$filtered_source = 'all';
		if ( isset( $_POST['cart_source'] ) && '' !== $_POST['cart_source'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			$filtered_source = sanitize_text_field( wp_unslash( $_POST['cart_source'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			$_SESSION['cart_source'] = $filtered_source;
		} elseif ( isset( $_SESSION['cart_source'] ) && '' !== $_SESSION['cart_source'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			$filtered_source = sanitize_text_field( wp_unslash( $_SESSION['cart_source'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		switch ( $filtered_status ) {
			case 'abandoned':
				$status_filter = "( wpac.cart_ignored = '0' AND wpac.recovered_cart = 0 ) ";
				break;
			case 'recovered':
				$status_filter = "( wpac.cart_ignored = '1' AND wpac.recovered_cart > 0 ) ";
				break;
			case 'unpaid':
				$status_filter = "( wpac.cart_ignored = '4' AND wpac.recovered_cart = 0 ) ";
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

		switch ( $filtered_source ) {
			case 'all':
			default:
				$source_filter = '';
				break;
			case 'checkout_page':
				$source_filter = "AND wpac.abandoned_cart_info like '%checkout%' AND wpac.user_id >= 63000000";
				break;
			case 'product_page':
				$source_filter = 'AND wpac.user_id > 0 AND wpac.user_id < 63000000';
				break;
			case 'atc':
				$source_filter = "AND wpac.abandoned_cart_info like '%atc%' AND wpac.user_id >= 63000000";
				break;
			case 'exit_intent':
				$source_filter = "AND wpac.abandoned_cart_info like '%exit_intent%'";
				break;
			case 'custom_form':
				$source_filter = "AND wpac.abandoned_cart_info like '%custom_form%'";
				break;
			case 'url':
				$source_filter = "AND wpac.abandoned_cart_info LIKE '%url%'";
				break;
		}
		$results = array();
		// nosemgrep:audit.php.wp.security.sqli.input-in-sinks
		switch ( $get_section_of_page ) {
			case 'wcap_all_abandoned':
				if ( is_multisite() ) {
					// get main site's table prefix.
					$main_prefix = $wpdb->get_blog_prefix( 1 );
					$results     = $wpdb->get_results( // phpcs:ignore
						$wpdb->prepare(// nosemgrep:audit.php.wp.security.sqli.input-in-sinks
							'SELECT wpac.*, wpu.user_login, wpu.user_email FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` AS wpac LEFT JOIN ' . $main_prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE wpac.abandoned_cart_time >= %d AND wpac.abandoned_cart_time <= %d AND $status_filter $source_filter AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.wcap_trash = '' ORDER BY wpac.abandoned_cart_time DESC $limit", // phpcs:ignore
							$start_date,
							$end_date,
							"%$blank_cart_info%",
							$blank_cart_info_guest
						)
					);
				} else {
					// non-multisite - regular table name.
					$results = $wpdb->get_results( // phpcs:ignore
						$wpdb->prepare(// nosemgrep:audit.php.wp.security.sqli.input-in-sinks
							'SELECT wpac.*, wpu.user_login, wpu.user_email FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` AS wpac LEFT JOIN ' . $wpdb->prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE wpac.abandoned_cart_time >= %d AND wpac.abandoned_cart_time <= %d AND $status_filter $source_filter AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.wcap_trash = '' ORDER BY wpac.abandoned_cart_time DESC $limit", // phpcs:ignore
							$start_date,
							$end_date,
							"%$blank_cart_info%",
							$blank_cart_info_guest
						)
					);
				}

				// get the total recovered carts list.
				$recovered_list = $wpdb->get_col( // phpcs:ignore
					$wpdb->prepare(
						'SELECT recovered_cart FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE recovered_cart > 0 AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_time >= %d AND abandoned_cart_time <= %d AND wcap_trash = ''", // phpcs:ignore
						"%$blank_cart_info%",
						$blank_cart_info_guest,
						$start_date,
						$end_date
					)
				);
				break;

			case 'wcap_all_registered':
				if ( is_multisite() ) {
					$main_prefix = $wpdb->get_blog_prefix( 1 ); // get main site's table prefix.
					$results     = $wpdb->get_results( // phpcs:ignore
						$wpdb->prepare(// nosemgrep:audit.php.wp.security.sqli.input-in-sinks
							'SELECT wpac.* , wpu.user_login, wpu.user_email FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` AS wpac LEFT JOIN ' . $main_prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE wpac.abandoned_cart_time >= %d AND wpac.abandoned_cart_time <= %d AND $status_filter $source_filter AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.wcap_trash = '' ORDER BY wpac.abandoned_cart_time DESC $limit", // phpcs:ignore
							$start_date,
							$end_date,
							"%$blank_cart_info%",
							$blank_cart_info_guest
						)
					);
				} else {
					// non-multisite - regular table name.
					$results = $wpdb->get_results( // phpcs:ignore
						$wpdb->prepare(// nosemgrep:audit.php.wp.security.sqli.input-in-sinks
							'SELECT wpac . * , wpu.user_login, wpu.user_email FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` AS wpac LEFT JOIN ' . $wpdb->prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE wpac.abandoned_cart_time >= %d AND wpac.abandoned_cart_time <= %d AND $status_filter $source_filter AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.user_type = 'REGISTERED' AND  wpac.wcap_trash = '' ORDER BY wpac.abandoned_cart_time DESC $limit", // phpcs:ignore
							$start_date,
							$end_date,
							"%$blank_cart_info%"
						)
					);
				}
				$recovered_list = $wpdb->get_col( // phpcs:ignore
					$wpdb->prepare(
						'SELECT recovered_cart FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE recovered_cart > 0 AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s AND wcap_trash = '' AND user_type = %s AND abandoned_cart_time >= %d AND abandoned_cart_time <= %d", //phpcs:ignore
						"%$blank_cart_info%",
						$blank_cart_info_guest,
						'REGISTERED',
						$start_date,
						$end_date
					)
				);
				break;

			case 'wcap_all_guest':
				if ( is_multisite() ) {
					$main_prefix = $wpdb->get_blog_prefix( 1 ); // get main site's table prefix.
					$results     = $wpdb->get_results( // phpcs:ignore
						$wpdb->prepare(// nosemgrep:audit.php.wp.security.sqli.input-in-sinks
							'SELECT wpac.* FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "` AS wpac WHERE wpac.abandoned_cart_time >= %d AND wpac.abandoned_cart_time <= %d AND $status_filter $source_filter AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.wcap_trash = '' AND wpac.user_id >= 63000000  AND wpac.abandoned_cart_info NOT LIKE %s ORDER BY wpac.abandoned_cart_time DESC $limit", // phpcs:ignore
							$start_date,
							$end_date,
							$blank_cart_info_guest,
							"%$blank_cart_info%"
						)
					);
				} else {
					// non-multisite - regular table name.
					$results = $wpdb->get_results( // phpcs:ignore
						$wpdb->prepare(// nosemgrep:audit.php.wp.security.sqli.input-in-sinks
							'SELECT wpac.*, wpu.user_login, wpu.user_email FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` AS wpac LEFT JOIN ' . $wpdb->prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE wpac.abandoned_cart_time >= %d AND wpac.abandoned_cart_time <= %d AND $status_filter $source_filter AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.wcap_trash = '' AND wpac.user_id >= 63000000 AND wpac.abandoned_cart_info NOT LIKE %s ORDER BY wpac.abandoned_cart_time DESC $limit",
							$start_date,
							$end_date,
							$blank_cart_info_guest,
							"%$blank_cart_info%"
						)
					);
				}
				$recovered_list = $wpdb->get_col( // phpcs:ignore
					$wpdb->prepare(
						'SELECT recovered_cart FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE recovered_cart > 0 AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s AND wcap_trash = '' AND abandoned_cart_time >= %d AND abandoned_cart_time <= %d AND user_type = %s AND user_id >= 63000000", // phpcs:ignore
						"%$blank_cart_info%",
						$blank_cart_info_guest,
						$start_date,
						$end_date,
						'GUEST'
					)
				);
				break;

			case 'wcap_all_visitor':
				if ( is_multisite() ) {
					$main_prefix = $wpdb->get_blog_prefix( 1 ); // get main site's table prefix.
					$results     = $wpdb->get_results( // phpcs:ignore
						$wpdb->prepare(// nosemgrep:audit.php.wp.security.sqli.input-in-sinks
							'SELECT wpac.* FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "` AS wpac WHERE wpac.abandoned_cart_time >= %d AND wpac.abandoned_cart_time <= %d AND $status_filter $source_filter AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.wcap_trash = '' AND wpac.user_id = 0 AND wpac.abandoned_cart_info NOT LIKE %s ORDER BY wpac.abandoned_cart_time DESC $limit", // phpcs:ignore
							$start_date,
							$end_date,
							$blank_cart_info_guest,
							"%$blank_cart_info%"
						)
					);
				} else {
					// non-multisite - regular table name.
					$results = $wpdb->get_results( // phpcs:ignore
						$wpdb->prepare(// nosemgrep:audit.php.wp.security.sqli.input-in-sinks
							'SELECT wpac.*, wpu.user_login, wpu.user_email FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` AS wpac LEFT JOIN ' . $wpdb->prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE wpac.abandoned_cart_time >= %d AND wpac.abandoned_cart_time <= %d AND $status_filter $source_filter AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.wcap_trash = '' AND wpac.user_id = 0 AND wpac.abandoned_cart_info NOT LIKE %s ORDER BY wpac.abandoned_cart_time DESC $limit", // phpcs:ignore
							$start_date,
							$end_date,
							$blank_cart_info_guest,
							"%$blank_cart_info%"
						)
					);
				}
				$recovered_list = $wpdb->get_col( // phpcs:ignore
					$wpdb->prepare(
						'SELECT recovered_cart FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE recovered_cart > 0 AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s AND wcap_trash = '' AND abandoned_cart_time >= %d AND abandoned_cart_time <= %d AND user_type = %s AND user_id = 0", // phpcs:ignore
						"%$blank_cart_info%",
						$blank_cart_info_guest,
						$start_date,
						$end_date,
						'GUEST'
					)
				);
				break;

			case 'wcap_all_unsubscribe_carts':
				if ( is_multisite() ) {
					$main_prefix = $wpdb->get_blog_prefix( 1 ); // get main site's table prefix.
					$results     = $wpdb->get_results( // phpcs:ignore
						$wpdb->prepare(// nosemgrep:audit.php.wp.security.sqli.input-in-sinks
							"SELECT wpac.* FROM `".WCAP_ABANDONED_CART_HISTORY_TABLE."` AS wpac WHERE abandoned_cart_time >= %d AND abandoned_cart_time <= %d AND abandoned_cart_time <= %d AND wcap_trash = '' AND unsubscribe_link = '1' ORDER BY wpac.abandoned_cart_time DESC $limit", // phpcs:ignore
							$start_date,
							$end_date,
							$compare_time_guest
						)
					);
				} else {
					// non-multisite - regular table name.
					$results = $wpdb->get_results( // phpcs:ignore
						$wpdb->prepare(// nosemgrep:audit.php.wp.security.sqli.input-in-sinks
							'SELECT wpac.*, wpu.user_login, wpu.user_email FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` AS wpac LEFT JOIN ' . $wpdb->prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE wpac.abandoned_cart_time >= %d AND wpac.abandoned_cart_time <= %d AND wpac.wcap_trash = '' AND wpac.unsubscribe_link = '1' ORDER BY wpac.abandoned_cart_time DESC $limit", // phpcs:ignore
							$start_date,
							$end_date
						)
					);
				}
				break;

			default:
				break;
		}

		if ( isset( $recovered_list ) && is_array( $recovered_list ) && count( $recovered_list ) > 0 ) {
			$recovered_total = 0;
			foreach ( $recovered_list as $order_id ) {
				$order            = wc_get_order( $order_id );
				if ( ! $order  ){
					continue;	
				}
				$order_total      = $order->get_total();
				$recovered_total += is_numeric( $order_total ) && $order_total > 0 ? $order_total : 0;
			}
			self::$recovered_count = count( $recovered_list );
			self::$recovered_amount = $recovered_total;
		} else {
			self::$recovered_count = 0;
			self::$recovered_amount = 0;
		}
		
		$i = 0;
		$display_tracked_coupons   = get_option( 'ac_track_coupons' );
		$wp_date_format            = get_option( 'date_format' );
        $wp_time_format            = get_option( 'time_format' );
     	$guest_ac_cutoff_time      = is_numeric( get_option( 'ac_cart_abandoned_time_guest', 10 ) ) ? get_option( 'ac_cart_abandoned_time_guest', 10 ) : 10;
		$ac_cutoff_time            = is_numeric( get_option( 'ac_cart_abandoned_time', 10 ) ) ? get_option( 'ac_cart_abandoned_time', 10 ) : 10;
		$current_time              = current_time( 'timestamp' );
       	$wcap_include_tax          = get_option( 'woocommerce_prices_include_tax' );
        $wcap_include_tax_setting  = get_option( 'woocommerce_calc_taxes' );
		
		$connector_common = Wcap_Connectors_Common::get_instance();	

		foreach( $results as $key => $value ) {
		    if( $value->user_type == "GUEST" ) {
		        $query_guest   = "SELECT * from `" . WCAP_GUEST_CART_HISTORY_TABLE . "` WHERE id = %d";
		        $results_guest = $wpdb->get_results( $wpdb->prepare( $query_guest, $value->user_id ) );
		    }
		    $abandoned_order_id = $value->id;
		    $user_id            = $value->user_id;
		    $user_first_name    = '';
			$user_last_name     = '';
			$user_email         = '';

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
		    $cart_info        = json_decode( stripslashes( $value->abandoned_cart_info ) );
		    $order_date       = "";
		    $cart_update_time = $value->abandoned_cart_time;
			$captured_by      = isset( $cart_info->captured_by ) && '' !== $cart_info->captured_by ? $cart_info->captured_by : '';
		    if( $cart_update_time != "" && $cart_update_time != 0 ) {
		    	$date_format = date_i18n( $wp_date_format, $cart_update_time );
                $time_format = date_i18n( $wp_time_format, $cart_update_time );
				$time_format = '' === $time_format ? '' : ' | ' . $time_format;
                $order_date  = $date_format . ' ' . $time_format;
		    }

		    if( "GUEST" == $value->user_type ) {
                $ac_cutoff_time = $guest_ac_cutoff_time;
		    }
		    $cut_off_time   = $ac_cutoff_time * 60;
		    $compare_time   = $current_time - $cart_update_time;
		    $cart_details   = new stdClass();
		    $line_total     = 0;
		    $cart_total     = $item_subtotal = $item_total = $line_subtotal_tax_display =  $after_item_subtotal = $after_item_subtotal_display = 0;
            $line_subtotal_tax = 0;

		    if( isset( $cart_info->cart ) ) {
		        $cart_details = $cart_info->cart;
		    }
		    $quantity_total = 0;
		    $currency = isset( $cart_info->currency ) ? $cart_info->currency : '';
		    if( gettype( $cart_details ) !== 'array' && count( get_object_vars( $cart_details ) ) > 0 ) {

		    //$currency = isset( $cart_info->currency ) ? $cart_info->currency : '';

    	        foreach( $cart_details as $k => $v ) {
    	        	if( version_compare( $woocommerce->version, '3.0.0', ">=" ) ) {
                      $wcap_product   = wc_get_product($v->product_id );
                      $product        = wc_get_product($v->product_id );
                    }else {
                        $product      = get_product( $v->product_id );
                        $wcap_product = get_product($v->product_id );
                    }
                    if ( false !== $product ) {
	    	            if( isset($wcap_include_tax) && $wcap_include_tax == 'no' &&
	                        isset($wcap_include_tax_setting) && $wcap_include_tax_setting == 'yes' ) {
	                        $line_total        += isset( $v->line_total ) ? $v->line_total : 0;
	                        $line_subtotal_tax += isset( $v->line_tax ) ? $v->line_tax : 0; // This is fix

	                    }else if ( isset($wcap_include_tax) && $wcap_include_tax == 'yes' &&
	                        isset($wcap_include_tax_setting) && $wcap_include_tax_setting == 'yes' ) {
	                        // Item subtotal is calculated as product total including taxes
							if ( isset( $v->line_tax, $v->line_subtotal_tax ) && $v->line_tax > 0 ) {
								$line_subtotal_tax_display += $v->line_tax;
	                            /* After copon code price */
	                            $after_item_subtotal = $item_subtotal + $v->line_total + $v->line_tax;
								/*Calculate the product price*/
								$item_subtotal = $item_subtotal + $v->line_subtotal + $v->line_subtotal_tax;
								$line_total    = $line_total +   $v->line_subtotal + $v->line_subtotal_tax;
	                        } else {
	                            $item_subtotal             += isset( $v->line_total ) ? $v->line_total : 0;
	                            $line_total                += isset( $v->line_total ) ? $v->line_total : 0;
	                            $line_subtotal_tax_display += isset( $v->line_tax ) ? $v->line_tax : 0;
	                        }
	                    } else {
							$line_total += isset( $v->line_total ) ? $v->line_total : 0;
	                    }
	                    $quantity_total = $quantity_total + $v->quantity;
	    	        }
    	    	}
		    } else {
				continue;
			}

		    $wcap_check_order_total = $line_total;
		    $wcap_item_total_with_tax = $line_total + $line_subtotal_tax;
		    $line_total     		= apply_filters ( 'acfac_change_currency', Wcap_Common::wcap_get_price( $line_total, $currency ), $abandoned_order_id, $line_total, 'wcap_order_page' );
			$show_taxes = apply_filters('wcap_show_taxes', true);
		    if( $show_taxes && isset( $wcap_include_tax ) && $wcap_include_tax == 'no' && isset( $wcap_include_tax_setting ) && $wcap_include_tax_setting == 'yes' ) {
		    	$wcap_item_total_tax_display     = apply_filters ( 'acfac_change_currency', Wcap_Common::wcap_get_price( $wcap_item_total_with_tax, $currency ), $abandoned_order_id, $wcap_item_total_with_tax, 'wcap_order_page' );
		    	$line_total = $wcap_item_total_tax_display . '<br> ('. __( "incl. tax", "woocommerce-ac" ) . ')';
            }else if( isset( $wcap_include_tax ) && $wcap_include_tax == 'yes' && isset( $wcap_include_tax_setting ) && $wcap_include_tax_setting == 'yes' ) {
            	$line_subtotal_tax_display     = apply_filters ( 'acfac_change_currency', Wcap_Common::wcap_get_price( $line_subtotal_tax_display, $currency ), $abandoned_order_id, $line_subtotal_tax_display, 'wcap_order_page' );
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
			$ac_status = '';
			if ( $value->cart_ignored == 1 && $value->recovered_cart > 0 ) {
				 $ac_status_original = $ac_status = __( "Recovered", "woocommerce-ac" );
		        $ac_status = "<span id ='wcap_abandoned' class = 'wcap_abandoned'  >" . $ac_status . "</span>";		       
		    } elseif( $value->cart_ignored == 0 && $value->recovered_cart == 0 ) {
		        $ac_status_original = $ac_status = __( "Abandoned", "woocommerce-ac" );
		        $ac_status = "<span id ='wcap_abandoned_new' class = 'wcap_abandoned_new'  >" . $ac_status . "</span>";
		    } elseif ( $value->cart_ignored == 1 && $value->recovered_cart == 0 ) {
		        $ac_status_original = $ac_status = __( "Abandoned but <br> new cart created after this", "woocommerce-ac" );
		        $ac_status = "<span id ='wcap_abandoned_new' class = 'wcap_abandoned_new'  >" . $ac_status . "</span>";
		    } elseif ( $value->cart_ignored == 4 && $value->recovered_cart == 0 ) {
		        $ac_status_original = $ac_status = __( "Abandoned - Pending Payment", "woocommerce-ac" );
		        $ac_status = "<span id ='wcap_abandoned_unpaid' class = 'wcap_abandoned_unpaid'  >" . $ac_status . "</span>";
		    } elseif ( $value->cart_ignored == 2 && $value->recovered_cart == 0 ) {
		        $ac_status_original = $ac_status = __( "Abandoned - Order Cancelled", "woocommerce-ac" );
		        $ac_status = "<span id ='wcap_abandoned_cancelled' class = 'wcap_abandoned_cancelled'  >" . $ac_status . "</span>";
		    } elseif ( $value->cart_ignored == 3 && $value->recovered_cart == 0 ) {
		        $ac_status_original = $ac_status = __( "Abandoned - Order Received", "woocommerce-ac" );
		        $ac_status = "<span id ='wcap_abandoned_received' class = 'wcap_abandoned_received'  >" . $ac_status . "</span>";
		    } else {
		    	$ac_status_original = $ac_status = "";
		    }
			
			if ( $value->unsubscribe_link == 1 &&   ! $value->recovered_cart > 0 ) {
		        $ac_status_original = __( "Unsubscribed", "woocommerce-ac" );
		        $ac_status = "<span id ='wcap_unsubscribe_link' class = 'unsubscribe_link'  >" . $ac_status_original . "</span>";
		    } 

		    $coupon_code_used = $coupon_code_message = "";
		    if ( $compare_time > $cut_off_time && $ac_status != "" ) {
		        $return_abandoned_orders[$i] 				 = new stdClass();
		        $customer_information = '';
				if ( $user_first_name !== '' ) {					
                    $customer_information = $user_first_name . " ".$user_last_name . "<br/>";
                }
                $return_abandoned_orders[ $i ]->id           = $abandoned_order_id;
                $return_abandoned_orders[ $i ]->email        = $user_email;
                if( $phone == '' ) {
                    $return_abandoned_orders[ $i ]->customer = $customer_information;
                } else {
                    $return_abandoned_orders[ $i ]->customer = $customer_information . $phone;
                }
				$return_abandoned_orders[ $i ]->captured_by  = $captured_by;
                $return_abandoned_orders[ $i ]->check_cart_total = $wcap_check_order_total;
                $return_abandoned_orders[ $i ]->user_id      = $user_id;
                $return_abandoned_orders[ $i ]->date         = $order_date;
                $return_abandoned_orders[ $i ]->status       = $ac_status;
				
                $return_abandoned_orders[ $i ]->status_original        = $ac_status_original;
				$return_abandoned_orders[ $i ]->manual_email_link      = '';				
				$return_abandoned_orders[ $i ]->unsubscribe_link       = $value->unsubscribe_link;
				$return_abandoned_orders[ $i ]->recovered_cart         = $value->recovered_cart;
				
				$abandoned_row_info = $return_abandoned_orders[ $i ];
				if ( '0' !== $abandoned_row_info->user_id && ! stripos( $abandoned_row_info->status, 'recovered' ) && ! stripos( $abandoned_row_info->status, 'unsubscribed' ) ) {
					$wcap_edit_array = array(
						'action'     => 'wcap_abandoned_cart_info',
						'user_id'    => $abandoned_row_info->user_id,
						'user_email' => $abandoned_row_info->email,
						'cart_id'    => $abandoned_row_info->id,
					);

					$wcap_edit_url = add_query_arg(
						$wcap_edit_array,
						admin_url( 'admin-ajax.php' )
					);
					$return_abandoned_orders[ $i ]->wcap_js_edit_email = $wcap_edit_url;
				}

				if( isset( $abandoned_row_info->email ) ) {					
					$wcap_cart_status   = strip_tags($abandoned_row_info->status_original );
					if ( $abandoned_row_info->user_id != 0 && 
						 '' != $abandoned_row_info->email &&
						 'User Deleted' != $abandoned_row_info->email &&
						  !strstr( $wcap_cart_status, "Unsubscribed" ) &&
						  !strstr( $wcap_cart_status, "Recovered" )
						 ) {

					   $return_abandoned_orders[ $i ]->manual_email_link = str_replace( '&amp;', '&', wp_nonce_url( add_query_arg( array( 'action' => 'cart_recovery', 'section' => 'emailtemplates', 'mode'     => 'wcap_manual_email', ' abandoned_order_id' => $abandoned_row_info->id ), $this->base_url ), 'abandoned_order_nonce') );
					}
				}
				$return_abandoned_orders[$i]->trash_link = str_replace( '&amp;', '&', wp_nonce_url( add_query_arg( array( 'action' => 'wcap_abandoned_trash',    'abandoned_order_id' => $abandoned_row_info->id ), $this->base_url ), 'abandoned_order_nonce') );
    	    
				if ( '0' !== $abandoned_row_info->user_id && ! stripos( $abandoned_row_info->status, 'recovered' ) && ! stripos( $abandoned_row_info->status, 'unsubscribed' ) ) {
					$return_abandoned_orders[ $i ]->unsubscribe_link = str_replace( '&amp;', '&', wp_nonce_url( add_query_arg( array( 'action' => 'listcart', 'mode' => 'unsubscribe', 'abandoned_order_id' => $abandoned_row_info->id ), $this->base_url ), 'abandoned_order_nonce') );
				}
				if ( '0' !== $abandoned_row_info->user_id && ! stripos( $abandoned_row_info->status, 'recovered' ) ) {
					$return_abandoned_orders[ $i ]->mark_as_recovered_link = str_replace( '&amp;', '&', wp_nonce_url( add_query_arg( array( 'action' => 'listcart', 'mode' => 'mark_recovered', 'abandoned_order_id' => $abandoned_row_info->id ), $this->base_url ), 'abandoned_order_nonce') );
				}

                if ( isset( $cart_info->wcap_user_ref ) && $cart_info->wcap_user_ref != '' ) {
                	$return_abandoned_orders[ $i ]->fb_consent = 'yes';
                }
				
				if( isset( $abandoned_row_info->customer ) ) {
			        $user_role = '';
			        if ( $abandoned_row_info->user_id == 0 ) {
			            $user_role = 'Guest';
			        }
			        elseif ( $abandoned_row_info->user_id >= 63000000 ) {
			            $user_role = 'Guest';
			        }else{
			            $user_role = Wcap_Common::wcap_get_user_role ( $abandoned_row_info->user_id );
			        }
			        $fb_image = '';
			        if ( isset( $abandoned_row_info->fb_consent ) && $abandoned_row_info->fb_consent == 'yes' ) {
			        	$fb_image = '<div class="clear"></div>
			        				 <img src="' . esc_url( WCAP_PLUGIN_URL ) . "/assets/images/fb-messenger.png" . '" width="15" title="' . __( 'Facebook Messenger consent given', 'woocommerce-ac' ) . '">';
			        }
					$customer_info = '' !== $abandoned_row_info->customer ? $abandoned_row_info->customer . "<br>" : '';
			        $return_abandoned_orders[ $i ]->customer_details = $customer_info . $user_role . $fb_image;
			    }

                if( $quantity_total > 0 ) {
                    $return_abandoned_orders[ $i ]->order_total = $line_total . "<br>" . $quantity_total . " " . $item_disp;
                    $return_abandoned_orders[ $i ]->quantity    = $quantity_total . " " . $item_disp;
					if( is_array( $coupon_detail_post_meta ) && count( $coupon_detail_post_meta ) > 0 ) {
						foreach( $coupon_detail_post_meta as $key => $value ) {
							if( '' !== $key ) {
								$coupon_code_used .= $key ;
								$coupon_code_message .= $value ;
							}
						}
						$return_abandoned_orders[ $i ]->coupon_code_used = $coupon_code_used;
						if ( strstr( $coupon_code_message, 'success' ) ) {
							$coupon_code_message = __( 'Successfully Applied', 'woocommerce-ac' );
						} else {
							$coupon_code_message = __( 'Application failed', 'woocommerce-ac' );
						}
						$return_abandoned_orders[ $i ]->coupon_code_status = str_replace( '.', '', $coupon_code_message );
					}
            	}
					
				$return_abandoned_orders[ $i ]->needs_manual_sync = $connector_common->wcap_needs_manual_sync( $return_abandoned_orders[ $i ]->id ); 
				$return_abandoned_orders[ $i ]->manual_sync_link  = '';
				if ( $return_abandoned_orders[ $i ]->needs_manual_sync ) {
					$return_abandoned_orders[ $i ]->manual_sync_link = str_replace( '&amp;', '&', wp_nonce_url( add_query_arg( array( 'action' => 'listcart', 'mode' => 'sync_manually', 'abandoned_order_id' => $return_abandoned_orders[ $i ]->id ), $this->base_url ), 'abandoned_order_nonce') );
				}
				
				$recovered_order_text = $ac_status_original;
				$recovered_order = isset( $value->recovered_cart ) && $value->recovered_cart > 0 ? absint( $value->recovered_cart ) : 0;
				if ( $recovered_order > 0 ) {
				$order_post     = wc_get_order( $recovered_order );
				$recovered_date = '';
				if ( $order_post ) {
					$date_format    = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
					$recovered_date = get_date_from_gmt(  $order_post->get_date_created(), $date_format );
				}

				if ( is_hpos_enabled() ) { // HPOS usage is enabled.
					$order_url = admin_url( "admin.php?page=wc-orders&id=$recovered_order&action=edit" );
				} else { // Traditional CPT-based orders are in use.
					$order_url = admin_url( "post.php?post=$recovered_order&action=edit" );
				}

				$recovered_order_text =  wp_kses_post(
					sprintf(
						// Translators: Recovered Order Link, Recovered Order ID, Recovered Order Date.
						'<span id="order_status_text">' . __( 'Order', 'woocommerce-ac' ) . " <a href='%s' target='_blank'>#%s</a> " . __( 'Recovered on %s', 'woocommerce-ac' ) . '</span>',
						esc_url( $order_url ),
						esc_attr( $recovered_order ),
						esc_attr( $recovered_date )
					)
				);
			}
			
				$captured_by_ab = $return_abandoned_orders[ $i ]->captured_by;
					switch( $captured_by ) {
						case '':
						default:
						case 'user_profile':
							$captured_by_full = __( 'User Profile', 'woocommerce-ac' );
							break;
						case 'exit_intent':
							$captured_by_full = __( 'Exit Intent Popup', 'woocommerce-ac' );
							break;
						case 'atc':
							$captured_by_full = __( 'Add to Cart Popup', 'woocommerce-ac' );
							break;
						case 'checkout':
							$captured_by_full = __( 'Checkout page', 'woocommerce-ac' );
							break;
						case 'custom_form':
							$captured_by_full = __( 'Custom Form', 'woocommerce-ac' );
							break;
						case 'url':
							$captured_by_full = __( 'URL', 'woocommerce-ac' );
							break;
						case 'visitor':
						   $captured_by_full = '';
					}
					
				$return_abandoned_orders[ $i ]->captured_by = $captured_by_full;

				$return_abandoned_orders[ $i ]->recovered_order_text = $recovered_order_text;
				$return_abandoned_orders[ $i ]->connector_status = '';
				$return_abandoned_orders[ $i ] = apply_filters( 'wcap_abandoned_orders_data_object', $return_abandoned_orders[ $i ], $i, $abandoned_order_id );
           		$i++;
            }
		}
		
    	return apply_filters( 'wcap_abandoned_orders_table_data', $return_abandoned_orders );
    }

	/**
	 * It will give the section name.
	 *
	 * @return string $section Name of the current section
	 * @since  2.4.7
	 */
	public function wcap_get_current_section() {
		$section = 'wcap_all_abandoned';
		if ( isset( $_REQUEST[ 'wcap_section' ] ) ) {
			$section = $_REQUEST[ 'wcap_section' ];
		}
		return $section;
	}
}
