<?php 
/**
 * Abandoned Cart Pro for WooCommerce
 *
 * This will display the list of FB Templates
 * 
 * @author   Tyche Softwares
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Classes
 * @category Classes
 * @since    7.10
 */

// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Show SMS templates list in Cart Recovery->SMS Notifications
 * 
 * @since 7.10
 */
class WCAP_FB_Templates_List extends WP_List_Table {


	/**
	 * Number of results to show per page
	 *
	 * @var string
	 * @since 7.10
	 */
	public $per_page = 20;

	/**
	 * URL of this page
	 *
	 * @var string
	 * @since 7.10
	 */
	public $base_url;

	/**
	 * Total number of templates
	 *
	 * @var int
	 * @since 7.10
	 */
	public $total_count;

	/**
	 * It will add the bulk action and other variable needed for the class.
	 *
	 * @since 7.10
	 * @see WP_List_Table::__construct()
	 */
	public function __construct() {

		// Set parent defaults
		parent::__construct( array(
			'singular' => __( 'fb_template_id', 'woocommerce-ac' ), //singular name of the listed records
			'plural'   => __( 'fb_template_ids', 'woocommerce-ac' ), //plural name of the listed records
			'ajax'      => true                         // Does this table support ajax?
		) );
		$this->wcap_get_fb_templates_count();
		$this->process_bulk_action();
		$this->base_url = admin_url( 'admin.php?page=woocommerce_ac_page&action=cart_recovery&section=fb_templates' );
	}

	/**
	 * It will prepare the list of the SMS Templates, columns, pagination, sortable column and other data.
	 *
	 * @since 7.10
	 */
	public function wcap_fb_templates_prepare_items() {

		$columns               = $this->get_columns();
		$hidden                = $this->wcap_hidden_fb_cols();
		$sortable              = array();
		$data                  = $this->wcap_fb_templates();       
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$total_items           = $this->total_count;
		$this->items           = $data;
		
		$this->set_pagination_args( array(
				'total_items' => $total_items,                      // WE have to calculate the total number of items
				'per_page'    => $this->per_page,                       // WE have to determine how many items to show on a page
				'total_pages' => ceil( $total_items / $this->per_page )   // WE have to calculate the total number of pages
			)
		);
		
		return array( 'fb_templates' => $data, 'total_items' => $total_items, 'total_pages' => $this->per_page, ceil( $total_items / $this->per_page ) );
	}
	
	function wcap_hidden_fb_cols() {
		return ( apply_filters( 'wcap_fb_hidden_cols', array() ) );
	}
	/**
	 * It will add the columns for Cart Recovery->SMS Notifications
	 *
	 * @return array $columns All columns name.
	 * @since  7.9
	 */
	public function get_columns() {
		
		$columns = array(
			'cb'              => '<input type="checkbox" />',
			'id'              => __( 'ID', 'woocommerce-ac' ),
			'template_name'   => __( 'Text', 'woocommerce-ac' ),
			'sent_time'       => __( 'Template send time after abandonment', 'woocommerce-ac' ),
			'click_rate'      => __( 'Link Click Rate', 'woocommerce-ac' ),
			'conversion_rate' => __( 'Conversion Rate', 'woocommerce-ac' ),
			'activate'        => __( 'Start Sending', 'woocommerce-ac' ),
			'actions'         => __( 'Actions', 'woocommerce-ac' )
		);
		return apply_filters( 'wcap_fb_templates_col', $columns );
	}
	
	/**
	 * It is used to add the check box for the items
	 *
	 * @param string $item 
	 * @return string 
	 * @since 7.9
	 */
	function column_cb( $item ){
		
		$template_id = '';
		if( isset( $item->id ) && "" != $item->id ){
		$template_id = $item->id; 
		}
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			'template_id',
			$template_id
		);
	}

	/**
	 * Returns the data for the SMS Template list. 
	 *
	 * @globals mixed $wpdb
	 * @return array $return_templates_display Key and value of all the columns
	 * @since 7.9
	 */
	public function wcap_fb_templates() { 
		global $wpdb;       
		
		$return_templates_data = array();//phpcs:disable
		
		// Get the count of sms templates from the DB
		$fb_query = "SELECT * FROM " . WCAP_NOTIFICATION_TEMPLATES_TABLE . "
					WHERE notification_type = %s";
		$fb_list = $wpdb->get_results( $wpdb->prepare( $fb_query, 'fb' ) );
		
		$template_count = 0;
		if ( is_array( $fb_list ) && count( $fb_list ) > 0 && false !== $fb_list ) {
			$wcap_get_decimal = wc_get_price_decimals();
			foreach( $fb_list as $fb_details ) {
				// SMS ID
				$template_id = $fb_details->id;
		
				$ac_fb_count = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(`id`) FROM " . WCAP_EMAIL_SENT_HISTORY_TABLE . " WHERE template_id= %d AND notification_type='fb'", 
						$template_id
					)
				);

				$links_clicked = $wpdb->get_var(  //phpcs:ignore
					$wpdb->prepare(
						'SELECT count( DISTINCT( link.notification_sent_id ) ) FROM ' . WCAP_LINK_CLICKED_TABLE . ' AS link INNER JOIN ' . WCAP_EMAIL_SENT_HISTORY_TABLE . ' AS sent ON link.notification_sent_id = sent.id WHERE sent.template_id = %d',
						$template_id
					)
				);
				$links_rate = $links_clicked > 0 && $ac_fb_count > 0 ? round( ( $links_clicked / $ac_fb_count ) * 100, $wcap_get_decimal ) : 0; 

				$query_no_recovers_test      = "SELECT COUNT(`id`) FROM " . WCAP_EMAIL_SENT_HISTORY_TABLE . " WHERE recovered_order = '1' AND template_id = %d";
				$wcap_number_of_time_recover = $wpdb->get_var( $wpdb->prepare( $query_no_recovers_test, $template_id ) );

				$wcap_recover_ratio = 0;
				if ( $ac_fb_count != 0 ) {
					$wcap_recover_ratio = $wcap_number_of_time_recover / $ac_fb_count * 100;
				}
				// Default
				$return_templates_data[ $template_count ] = new stdClass();
				
				$return_templates_data[ $template_count ]->id = $template_id;
				
				$return_templates_data[ $template_count ]->updated = 0;
				
				// Subject
				$return_templates_data[ $template_count ]->subject = wp_unslash( $fb_details->subject );

				// Subject
				$body = json_decode( $fb_details->body );
				
				$return_templates_data[ $template_count ]->body             = wp_unslash( $body );
				$return_templates_data[ $template_count ]->checkout_text    = $body->checkout_text ;
				$return_templates_data[ $template_count ]->unsubscribe_text = $body->unsubscribe_text ;
				
				$return_templates_data[ $template_count ]->frequency        = $fb_details->frequency;
				$return_templates_data[ $template_count ]->day_or_hour = $fb_details->day_or_hour;
				// Coupon Code
				$return_templates_data[ $template_count ]->coupon_code = $fb_details->coupon_code;
				
				$return_templates_data[ $template_count ]->sent_time       = $fb_details->frequency . ' ' . strtolower( $fb_details->day_or_hour );
				$return_templates_data[ $template_count ]->is_active       = $fb_details->is_active;
				$return_templates_data[ $template_count ]->click_rate      = $links_rate . '%';
				$return_templates_data[ $template_count ]->conversion_rate = round( $wcap_recover_ratio , $wcap_get_decimal ) . '%';
				$template_count++;
			}
			
		}
		
		return apply_filters( 'wcap_fb_templates_data', $return_templates_data );
		
	}
	/**
	 * Displays the column data. The data sent in is displayed with
	 * correct HTML or as needed.
	 *
	 * @param  array | object $wcap_fb_list All data of the list
	 * @param  stirng $column_name Name of the column
	 * @return string $value Data of the column
	 * @since  7.9
	 */
	public function column_default( $wcap_fb_list, $column_name ) {
		$value = '';

		$fb_id = $wcap_fb_list->id;
		
		switch ( $column_name ) {          
							
			case 'activate' :
				if( isset( $wcap_fb_list->active ) ) {                
				$id = $wcap_fb_list->id;
				$active = $wcap_fb_list->active;
		
				$active_text   = __( $active, 'woocommerce-ac' ); 
				$value =  "<button type='button' class='wcap-switch wcap-toggle-template-status' " 
					. "wcap-fb-id='$id' "
					. "wcap-template-switch='" . ( $active ) . "'>"
					. $active_text . '</button>'; 
				
				}
				break;  

			case 'template_name':
				// Display '-' if Subject not present
				$value = ( isset( $wcap_fb_list->template_subject ) && '' !== $wcap_fb_list->template_subject ) ? $wcap_fb_list->template_subject : '-';
				break;
			case 'actions':
				$fb_id = $wcap_fb_list->id;

				$template_string = json_encode( $wcap_fb_list );
				$value = "
				<button 
					id='edit_$fb_id' 
					data-wcap-template-id='$fb_id' 
					class='button-secondary edit_fb' 
					onclick='return false;' 
					data-toggle='modal' 
					data-target='.wcap-preview-modal'
					data-wcap-template='$template_string'
					>
					<i class='fa fa-edit'></i>
				</button>

				<button id='delete_$fb_id' class='button-secondary delete_fb' onclick='return false;'>
					<i class='fa fa-trash'></i>
				</button>";
				break;
			default:
				$value = isset( $wcap_fb_list->$column_name ) ? $wcap_fb_list->$column_name : '';
				break;
		}
		return apply_filters( 'wcap_fb_template_column_default', $value, $wcap_fb_list, $column_name );
	}

	/**
	 * It will add the 'Delete' bulk action in the SMS template list.
	 *
	 * @return array - Bulk action
	 * @since  7.9
	 */
	public function get_bulk_actions() {
		return array(
			'wcap_delete_fb_template' => __( 'Delete', 'woocommerce-ac' )
		);
	}
}
