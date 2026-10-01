<?php
/**
 * Abandoned Cart Pro for WooCommerce
 *
 * It will show records of the Abandoned cart reminder WhatApp messages which is sent to customers
 * and displayed in Reminders Sent->WhatsApp Sent.
 *
 * @author   LittleCodr
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Classes
 * @category Classes
 * @since    9.19.0
 */

// Load WP_List_Table if not loaded.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Show abandoned cart reminder WhatsApp record in Reminders Sent.
 *
 * @since 9.19.0
 */
class Wcap_Sent_WhatsApp_Table extends WP_List_Table {

	/**
	 * Number of results to show per page
	 *
	 * @var string
	 * @since 9.19.0
	 */
	public $per_page = 30;

	/**
	 * URL of this page
	 *
	 * @var string
	 * @since 9.19.0
	 */
	public $base_url;

	/**
	 * Total number of Sent WhatsApp messages
	 *
	 * @var int
	 * @since 9.19.0
	 */
	public $total_count;

	/**
	 * Total amount of Links clicked
	 *
	 * @var int
	 * @since 9.19.0
	 */
	public $link_click_count;

	/**
	 * Start date
	 *
	 * @var int
	 * @since 9.19.0
	 */
	public $start_date_db;

	/**
	 * End date
	 *
	 * @var int
	 * @since 9.19.0
	 */
	public $end_date_db;

	/**
	 * Duration
	 *
	 * @var int
	 * @since 9.19.0
	 */
	public $duration;

	/**
	 * It will add the bulk action and other variable needed for the class.
	 *
	 * @since 9.19.0
	 * @see WP_List_Table::__construct()
	 */
	public function __construct() {
		global $status, $page;
		// Set parent defaults.
		parent::__construct(
			array(
				'singular' => __( 'sent_whatsapp_id', 'woocommerce-ac' ), // singular name of the listed records.
				'plural'   => __( 'sent_whatsapp_ids', 'woocommerce-ac' ), // plural name of the listed records.
				'ajax'     => true, // Does this table support ajax?
			)
		);

		$this->base_url = admin_url( 'admin.php?page=woocommerce_ac_page&action=stats&section=whatsapp' );
	}

	/**
	 * It will prepare the list of the User Phone Number, columns, pagination, sortable column and other data.
	 *
	 * @since 9.19.0
	 */
	public function wcap_sent_whatsapp_prepare_items() {

		$hidden           = array(); // No hidden columns.
		$data             = $this->wcap_sent_whatsapp_data();
		$total_items      = $this->total_count;
		$link_click_count = $this->link_click_count;
		$this->items      = $data;
		$this->set_pagination_args(
			array(
				'total_items' => $total_items, // WE have to calculate the total number of items.
				'per_page'    => $this->per_page, // WE have to determine how many items to show on a page.
				'total_pages' => ceil( $total_items / $this->per_page ), // WE have to calculate the total number of pages.
			)
		);

		$first_page   = 1;
		$current_page = 1;
		$last_page    = ceil( $total_items / $this->per_page );

		if ( isset( $_GET['paged'] ) ) {//phpcs:disable
			$current_page  = ( $_GET['paged'] < 0 ) ? 1 : ( $_GET['paged'] > $last_page ? $last_page : sanitize_text_field( wp_unslash( $_GET['paged'] ) ) );
		}

		$previous_page     = ( $current_page > 1 ) ? $current_page - 1 : false;
		$next_page         = ( $current_page < $last_page ) ? $current_page + 1 : false;
		$previous_disabled = ! $previous_page ? 'disabled' : '';
		$next_disabled     = ! $next_page ? 'disabled' : '';
		$pageparams        = '';
		return array(
			'whatsapp_reminders' => $data,
			'link_click_count'   => $link_click_count,
			'total_items'        => $total_items,
			'total_pages'        => ceil( $total_items / $this->per_page ),
			'current_page'       => $current_page,
			'previous_page'      => $previous_page,
			'next_page'          => $next_page,
			'last_page'          => $last_page,
			'previous_disabled'  => $previous_disabled,
			'next_disabled'      => $next_disabled,
			'pageparams'         => $pageparams,
		);

	}

	/**
     * It will generate the sent abandoned cart reminder WhatsApp list in Reminders Sent->WhatsApp Sent
     *
     * @globals mixed $wpdb
     * @return array $return_sent_whatsapp_display Key and value of all the columns
     * @since 9.19.0
     */
	public function wcap_sent_whatsapp_data() {
		global $wpdb, $start_end_dates;
		// duration.
	    if ( isset( $_POST['duration_select_whatsapp'] ) && '' != $_POST['duration_select_whatsapp'] ){
	        $duration_range         = $_POST['duration_select_whatsapp'];
	        $_SESSION['duration']   = $duration_range;
	    } else if ( isset( $_SESSION ['duration'] ) && '' != $_SESSION ['duration'] ){
	        $duration_range         = $_SESSION ['duration'];
	    }
	    
	    if ( ! isset( $duration_range ) || ( isset( $duration_range ) && '' == $duration_range ) ) {
	        $duration_range         = "last_seven";
	        $_SESSION['duration']   = $duration_range;
	    }
	     
	    // start date
	    $start_date_range = '';
	    if ( isset( $_POST['start_date_whatsapp'] ) && '' != $_POST['start_date_whatsapp'] ) {
	        $start_date_range        = $_POST['start_date_whatsapp'];
	        $_SESSION ['start_date'] = $start_date_range;
	    } else if ( isset( $_SESSION ['start_date'] ) &&  '' != $_SESSION ['start_date'] ) {
	        $start_date_range = $_SESSION ['start_date'];
	    }
	    
	    if ( '' == $start_date_range ) {
	        $start_date_range = $start_end_dates[$duration_range]['start_date'];
	        $_SESSION ['start_date'] = $start_date_range;
	    }
	    
	    // end date
	    $end_date_range = '';
	    if ( isset( $_POST['end_date_whatsapp'] ) && '' != $_POST['end_date_whatsapp'] ){
	        $end_date_range = $_POST['end_date_whatsapp'];
	        $_SESSION ['end_date'] = $end_date_range;
	    } else if ( isset($_SESSION ['end_date'] ) && '' != $_SESSION ['end_date'] ){
	        $end_date_range = $_SESSION ['end_date'];
	    }
	    
	    if ( '' == $end_date_range ) {
	        $end_date_range = $start_end_dates[$duration_range]['end_date'];
	        $_SESSION ['end_date'] = $end_date_range;
	    }

		$start_date    = strtotime( $start_date_range." 00:00:00" );
		$end_date      = strtotime( $end_date_range." 23:59:59" );
		$start_date_db = date( 'Y-m-d H:i:s', $start_date );
		$end_date_db   = date( 'Y-m-d H:i:s', $end_date );

		$results_whatsapp_sent = array();

		$total_whatsapp_sent = 0;
		$total_links_clicked = 0;

		$checkout_page_id   = wc_get_page_id( 'checkout' );
		$checkout_page_link = $checkout_page_id ? get_permalink( $checkout_page_id ) : '';

		$per_page = $this->per_page;
		$offset   = 0;
		if ( isset( $_GET['paged'] ) && $_GET['paged'] > 1 ) {
			$page_number = $_GET['paged'] - 1;
			$offset           = $per_page * $page_number;
		}

		$ac_get_count = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT COUNT(wpsh.id) as count FROM   " . WCAP_EMAIL_SENT_HISTORY_TABLE . "   AS wpsh  LEFT JOIN    ". WCAP_ABANDONED_CART_HISTORY_TABLE . "   AS wpac  ON  
				( wpsh.cart_id = wpac.id  ) 
				WHERE 
 				wpsh.sent_time >= %s  AND wpsh.sent_time <= %s AND  notification_type = %s ",				
				$start_date_db,
				$end_date_db,
				'whatsapp'				
			)
		);
		$total_whatsapp_sent = $ac_get_count[0]->count;

		/* Now we use the LIMIT clause to grab a range of rows */
		$query_ac_sent = "SELECT wpsh.sent_time, wpsh.template_id, wpsh.cart_id, wpsh.id, wpsh.sent_notification_contact, wpsh.wc_order_id, wpac.email_reminder_status, wpac.recovered_cart 
				FROM   " . WCAP_EMAIL_SENT_HISTORY_TABLE . "   AS wpsh  LEFT JOIN    " . WCAP_ABANDONED_CART_HISTORY_TABLE . "   AS wpac  ON  
				( wpsh.cart_id = wpac.id ) 
				WHERE 
 				wpsh.sent_time >= %s  AND wpsh.sent_time <= %s AND  notification_type = %s 
				ORDER BY wpsh.id DESC LIMIT %d OFFSET %d";
		$whatsapp_data = $wpdb->get_results(
			$wpdb->prepare(
				$query_ac_sent,
				$start_date_db,
				$end_date_db,
				'whatsapp',
				$per_page,
				$offset
			)
		);
		// for the sms templates, get the sms data.
		//if( is_array( $sms_data ) && count( $sms_data ) > 0 ) {

			$i                = 0;
			$wcap_date_format = get_option( 'date_format' );
			$wcap_time_format = get_option( 'time_format' );

			foreach ( $whatsapp_data as $display_data ) {

				$template_details = $wpdb->get_row( $wpdb->prepare( "SELECT template_name FROM " . WCAP_NOTIFICATION_TEMPLATES_TABLE . " WHERE ID = %d", $display_data->template_id ) );
				if ( isset( $template_details->template_name ) && '' !== $template_details->template_name ) {
					$template_id = $template_details->template_name;
				} else {
					$template_id = 'Template ' . $display_data->template_id;
				}
				$results_whatsapp_sent[ $i ] = new stdClass();
				// SMS Sent ID.
				$results_whatsapp_sent[ $i ]->sms_sent_id = $display_data->id;
				// Phone Number.
				$results_whatsapp_sent[ $i ]->user_phone_number = $display_data->sent_notification_contact;

				// WhatsApp Sent Time.
				$sent_tmstmp      = strtotime( $display_data->sent_time );
				$sent_date_format = date_i18n( $wcap_date_format, $sent_tmstmp );
				$sent_time_format = date_i18n( $wcap_time_format, $sent_tmstmp );
				$sent_date        = "$sent_date_format $sent_time_format";

				$results_whatsapp_sent[ $i ]->sent_time = $display_data->sent_time;

				// Link Clicked Details.
				$query_ac_clicked   = "SELECT DISTINCT wplc.notification_sent_id, wplc.link_clicked, wplc.time_clicked FROM " . WCAP_LINK_CLICKED_TABLE . " as wplc 
										LEFT JOIN " . WCAP_EMAIL_SENT_HISTORY_TABLE." AS wpsh ON wplc.notification_sent_id = wpsh.id 
										WHERE wplc.notification_sent_id = %d
										ORDER BY wplc.id DESC ";
				$ac_results_clicked = $wpdb->get_results( $wpdb->prepare( $query_ac_clicked, $display_data->id  ) );

				if ( count( $ac_results_clicked ) > 0 ) {
					$link_clicked = $ac_results_clicked[0]->link_clicked == $checkout_page_link ? __( 'Checkout Page', 'woocommerce-ac' ) : __( 'Shop Page', 'woocommerce-ac' );
				}

				$results_whatsapp_sent[ $i ]->date_time_opened = isset( $ac_results_clicked[0]->time_clicked ) ? $ac_results_clicked[0]->time_clicked : '';

				// Link Clicked.
				$results_whatsapp_sent[ $i ]->link_clicked = isset( $link_clicked ) && '' != $results_whatsapp_sent[ $i ]->date_time_opened ? $link_clicked : '';  	                    

				// Template ID.
				$results_whatsapp_sent[$i]->template_name = $template_id;

				$recover_id 	= '';
				$view_name_flag = 'Abandoned';
				$edit_link      = '';

			if ( isset( $display_data->recovered_cart ) && $display_data->recovered_cart != 0 ) {
				$recover_id = $display_data->recovered_cart;
				if ( is_hpos_enabled() ) { // HPOS usage is enabled.
					$edit_link = admin_url( 'admin.php?page=wc-orders&action=edit&id=' . absint( $recover_id ) );
				} else {
					$edit_link = admin_url( 'post.php?post=' . absint( $recover_id ) . '&action=edit' );
				}
				$view_name_flag = __( 'Recovered', 'woocommerce-ac' );
			}

				// display link.
				$results_whatsapp_sent[ $i ]->display_link       = $view_name_flag;
				$results_whatsapp_sent[ $i ]->abandoned_order_id = $display_data->cart_id;
				$results_whatsapp_sent[ $i ]->recovered_order_id = $display_data->recovered_cart;
				$results_whatsapp_sent[ $i ]->recover_order_link = $edit_link;
				if ( '' != $results_whatsapp_sent[ $i ]->date_time_opened ) {
					$total_links_clicked++;
				}
				$i++;
			}
			$this->total_count = $total_whatsapp_sent;
			$this->link_click_count = $total_links_clicked;

			return apply_filters( 'wcap_sent_whatsapp_table_data', $results_whatsapp_sent );
		//}
	} 
} 
