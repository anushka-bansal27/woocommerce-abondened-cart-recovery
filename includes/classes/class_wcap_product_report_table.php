<?php
/**
 * Abandoned Cart Pro for WooCommerce
 *
 * It will show Abandoned & Recovered Product data on Product Reports tab.
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
 * The Product Report Page will display the total amount of the abandoned products as well as the total recovered amount for the products.
 *
 * @since 2.3.7
 */
class Wcap_Product_Report_Table extends WP_List_Table {

	/**
	 * Number of results to show per page
	 *
	 * @var string
	 * @since 2.3.7
	 */
	public $per_page = 30;

	/**
	 * URL of this page
	 *
	 * @var string
	 * @since 2.3.7
	 */
	public $base_url;

	/**
	 * Total number of recovred orders
	 *
	 * @var int
	 * @since 2.3.7
	 */
	public $total_count;

	/**
	 * It will add the bulk action and other variable needed for the class.
	 *
	 * @since 2.3.7
	 * @see WP_List_Table::__construct()
	 */
	public function __construct() {
		global $status, $page;
		// Set parent defaults.
		parent::__construct(
			array(
				'singular' => __( 'product_id', 'woocommerce-ac' ), // singular name of the listed records.
				'plural'   => __( 'product_ids', 'woocommerce-ac' ), // plural name of the listed records.
				'ajax'     => false // Does this table support ajax?
			)
		);

		$this->base_url = admin_url( 'admin.php?page=woocommerce_ac_page&action=stats' );
	}
	/**
	 * It will prepare the list of the abandoned products, columns and other data.
	 *
	 * @since 2.3.7
	 */
	public function wcap_product_report_prepare_items() {
		$hidden      = array(); // No hidden columns.
		$sortable    = $this->product_report_sortable_columns();
		$data        = $this->wacp_product_report_data();
		$total_items = $this->total_count;
		$this->items = $data;//phpcs:disable
		
		$this->set_pagination_args(
			array(
				'total_items' => $total_items, // WE have to calculate the total number of items.
				'per_page'    => $this->per_page, // WE have to determine how many items to show on a page.
				'total_pages' => ceil( $total_items / $this->per_page ) // WE have to calculate the total number of pages.
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
		$pageparams        = '';
		
		$params                 = array();
		$product_name_order     = 'asc';
		$abandoned_number_order = 'asc';
		$recover_number_order   = 'asc';
		$orderby                = '';
		$order                  = '';
		$next_order             = '';
		if ( isset( $_GET['orderby'] ) ) {
			$orderby = sanitize_text_field( wp_unslash( $_GET['orderby'] ) );
			$params[] ='orderby=' . $orderby;			
		}
		if ( isset( $_GET['order'] ) ) {
			
			$order = sanitize_text_field( wp_unslash( $_GET['order'] ) );
			$params[] ='order=' . $order;
			$next_order = 'asc' === $order ? 'desc' : 'asc';
			
			switch ( $orderby ) {				
				case 'product_name':
				$product_name_order = $next_order ;
				break;
			case 'abandoned_number':
				$abandoned_number_order = $next_order;
				break;
			case 'recover_number':
				$recover_number_order = $next_order;
				break;				
			}
		}
		
		$pageparams = implode( '&', $params );
		
		
		
		return array(
			'product_reports' => $data,
			'total_items' => $total_items,
			'total_pages' => ceil( $total_items / $this->per_page ),			
			'current_page'             => $current_page,
			'previous_page'            => $previous_page,
			'next_page'                => $next_page,			
			'last_page'                => $last_page,
			'previous_disabled'        => $previous_disabled,
			'next_disabled'            => $next_disabled,
			'pageparams'               => $pageparams,
			'order'                    => $order,
			'orderby'                  => $orderby,
			'product_name_order'       => 'orderby=product_name&order=' . $product_name_order,
			'abandoned_number_order'   => 'orderby=abandoned_number&order=' . $abandoned_number_order,
			'recover_number_order'     => 'orderby=recover_number&order=' . $recover_number_order,
		);
	}

	/**
	 * It will get the abandoned product data from database and calculate the number of abandoned & recovered products.
	 *
	 * @return String $return_product_report_display Data shown in the Email column
	 * @since  2.3.7
	 * @globals mixed $wpdb
	 */
	public function wacp_product_report_data() {
		global $wpdb;

		$i                     = 0;
		$wc_round_value        = wc_get_price_decimals();
		$ac_cutoff_time        = is_numeric( get_option( 'ac_cart_abandoned_time', 10 ) ) ? get_option( 'ac_cart_abandoned_time', 10 ) : 10;
		$cut_off_time          = $ac_cutoff_time * 60;
		$current_time          = current_time('timestamp'); // phpcs:ignore
		$compare_time          = $current_time - $cut_off_time;
		$ac_cutoff_time_guest  = is_numeric( get_option( 'ac_cart_abandoned_time_guest', 10 ) ) ? get_option( 'ac_cart_abandoned_time_guest', 10 ) : 10;
		$cut_off_time_guest    = $ac_cutoff_time_guest * 60;
		$compare_time_guest    = $current_time - $cut_off_time_guest;
		$blank_cart_info       = '{"cart":[]}';
		$blank_cart_info_guest = '[]';
		$blank_cart            = '""';
		$recover_query         = $wpdb->get_results( // phpcs:ignore
			$wpdb->prepare(
				'SELECT id, abandoned_cart_time, abandoned_cart_info, recovered_cart FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s AND wcap_trash = ''", // phpcs:ignore
				"%$blank_cart_info%",
				$blank_cart_info_guest
			)
		);
		$rec_carts_array       = array();
		$recover_product_array = array();
		$return_product_report = array();
		$quantity_array        = array();
		$recover_price         = array();

		foreach ( $recover_query as $recovered_cart_key => $recovered_cart_value ) {
			$coupon              = '';
			$used_coupon         = 'NO';
			$recovered_cart_info = json_decode( stripslashes( $recovered_cart_value->abandoned_cart_info ) );
			$recovered_cart_dat  = $recovered_cart_value->recovered_cart;
			$abandoned_order_id  = $recovered_cart_value->id;
			if ( $recovered_cart_dat > 0 ) {
				$order = array();
				try {
					$order = new WC_Order( $recovered_cart_dat );
					$items = $order->get_items();

					foreach ( $items as $items_key => $items_value ) {
						$item_subtotal = 0;

						$recover_product_id                           = $items_value['product_id'];
						$recover_product_array[ $recover_product_id ] = isset( $recover_product_array[ $recover_product_id ] ) ? $recover_product_array[ $recover_product_id ] + $items_value['quantity'] : $items_value['quantity'];
						if ( 0 != $items_value['line_subtotal_tax'] && $items_value['line_tax'] > 0 ) { // phpcs:ignore
							$item_subtotal = $item_subtotal + $items_value['line_total'] + $items_value['line_tax'];
						} else {
							$item_subtotal = $item_subtotal + $items_value['line_total'];
						}
						// Line total.
						$total_price = round( $item_subtotal, $wc_round_value );
						if ( isset( $recover_price[ $recover_product_id ] ) && array_key_exists( $recover_product_id, $recover_price ) ) {
							$wcap_recover_price                    = $total_price + $recover_price[ $recover_product_id ];
							$recover_price [ $recover_product_id ] = $wcap_recover_price;
						} else {
							$recover_price [ $recover_product_id ] = $total_price;
						}
					}
				} catch ( Exception $e ) { // Nothing to handle.
				}
			}

			$cart_update_time = $recovered_cart_value->abandoned_cart_time;
			$cart_details     = new stdClass();
			if ( isset( $recovered_cart_info->cart ) ) {
				$cart_details = $recovered_cart_info->cart;
			}
			if ( is_object( $cart_details ) && false !== $cart_details && count( get_object_vars( $cart_details ) ) > 0 ) {
				foreach ( $cart_details as $k => $v ) {
					$item_subtotal = 0;
					if ( isset( $v->product_id ) ) {

						if ( isset( $v->line_subtotal_tax ) && $v->line_subtotal_tax != 0 && $v->line_subtotal_tax > 0 ) { // phpcs:ignore
							$item_subtotal = $item_subtotal + $v->line_total + $v->line_subtotal_tax;
						} else {
							$item_subtotal += isset( $v->line_total ) ? $v->line_total : 0;
						}
						// Line total.
						$total_price = $item_subtotal;
						if ( isset( $quantity_array [ $v->product_id ] ) && array_key_exists( $v->product_id, $quantity_array ) ) {
							$wcap_abandoned_amount             = $total_price + $quantity_array[ $v->product_id ];
							$quantity_array [ $v->product_id ] = $wcap_abandoned_amount;
						} else {
							$quantity_array[ $v->product_id ] = $total_price;
						}
					}
				}
			}

			$cut_off_time = $ac_cutoff_time * 60;
			$compare_time = $current_time - $cart_update_time;
			if ( is_array( $recovered_cart_info ) || is_object( $recovered_cart_info ) ) {
				foreach ( $recovered_cart_info as $rec_cart_key => $rec_cart_value ) {

					if ( is_array( $rec_cart_value ) || is_object( $rec_cart_value ) ) {
						foreach ( $rec_cart_value as $rec_product_id_key => $rec_product_id_value ) {
							if ( isset( $rec_product_id_value->key ) && $rec_product_id_key === $rec_product_id_value->key ) { // We only want to loop through cart items and not other cart data. Fix #3505.
								if ( is_object( $rec_product_id_value ) && isset( $rec_product_id_value->product_id ) ) {
									$product_id = $rec_product_id_value->product_id;
									if ( $compare_time > $cut_off_time ) {
										$rec_carts_array[ $product_id ] = isset( $rec_carts_array[ $product_id ] ) ? $rec_carts_array[ $product_id ] + $rec_product_id_value->quantity : $rec_product_id_value->quantity;
									}
								}
							}
						}
					}
				}
			}
		}

		arsort( $rec_carts_array );
		arsort( $recover_product_array );

		foreach ( $rec_carts_array as $count_abandoned_array_key => $count_abandoned_array_value ) {
			$return_product_report[ $i ] = new stdClass();
			if ( array_key_exists( $count_abandoned_array_key, $recover_product_array ) ) {
				$recover_cart = $recover_product_array[ $count_abandoned_array_key ];
			}
			if ( ! array_key_exists( $count_abandoned_array_key, $recover_product_array ) ) {
				$recover_cart = '0';
			}
			$prod_name = get_post( $count_abandoned_array_key );
			if ( null !== $prod_name || '' !== $prod_name ) {
				$product_name         = $prod_name->post_title;
				$abandoned_count      = $count_abandoned_array_value;
				$recover_price_amount = array_key_exists( $count_abandoned_array_key, $recover_price ) ? $recover_price[ $count_abandoned_array_key ] : 0;

				$return_product_report[ $i ]->product_name        = $product_name;
				$return_product_report[ $i ]->abandoned_number    = $abandoned_count;
				$return_product_report[ $i ]->recover_number      = $recover_cart;
				$return_product_report[ $i ]->product_id          = $count_abandoned_array_key;
				$return_product_report[ $i ]->product_total_price = get_woocommerce_currency_symbol() . number_format( $quantity_array [ $count_abandoned_array_key ], wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() );

				$return_product_report[ $i ]->recover_total_price = get_woocommerce_currency_symbol() . number_format( $recover_price_amount, wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() );
				$i++;
			}
		}
		$this->total_count = count( $return_product_report ) >= 0 ? count( $return_product_report ) : 0;
		// Sort for 1. abandoned_number, recover_number, product name.
		if ( isset( $_GET['orderby'] ) && 'abandoned_number' === $_GET['orderby'] ) { // phpcs:ignore
			if ( isset( $_GET['order' ]) && $_GET['order'] == 'asc' ) { // phpcs:ignore
				usort( $return_product_report, array( __CLASS__, 'wcap_class_abandoned_number_asc' ) );
			} else {
				usort( $return_product_report, array( __CLASS__, 'wcap_class_abandoned_number_dsc' ) );
			}
		} elseif ( isset( $_GET['orderby'] ) && 'recover_number' === $_GET['orderby'] ) { // phpcs:ignore
			if ( isset( $_GET['order' ]) && $_GET['order'] == 'asc' ) { // phpcs:ignore
				usort( $return_product_report, array( __CLASS__, 'wcap_class_recover_number_asc' ) );
			} else {
				usort( $return_product_report, array( __CLASS__, 'wcap_class_recover_number_dsc' ) );
			}
		} elseif ( isset( $_GET['orderby'] ) && 'product_name' === $_GET['orderby'] ) { // phpcs:ignore
			if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) { // phpcs:ignore
				usort( $return_product_report, array( __CLASS__, 'wcap_class_product_name_asc' ) );
			} else {
				usort( $return_product_report, array( __CLASS__, 'wcap_class_product_name_dsc' ) );
			}
		}
		$per_page = $this->per_page;
		if ( isset( $_GET['paged'] ) && $_GET['paged'] > 1 ) { // phpcs:ignore
			$page_number = sanitize_text_field( wp_unslash( $_GET['paged'] ) ) - 1;// phpcs:ignore
			$k           = $per_page * $page_number;
		} else {
			$k = 0;
		}

		$return_product_report_display = array();
		for ( $j = $k; $j < ( $k + $per_page ); $j++ ) {
			if ( isset( $return_product_report[ $j ] ) ) {
				$return_product_report_display[ $j ] = $return_product_report[ $j ];
			} else {
				break;
			}
		}
		return apply_filters( 'wcap_product_report_table_data', $return_product_report_display );
	}

	/**
	 * It will sort the alphabetically ascending on the Number of Times Abandoned.
	 *
	 * @param  array | object $value1 All data of the list.
	 * @param  array | object $value2 All data of the list.
	 * @return sorted array
	 * @since  3.4
	 */
	public function wcap_class_abandoned_number_asc( $value1, $value2 ) {
		return $value1->abandoned_number - $value2->abandoned_number;
	}
	/**
	 * It will sort the alphabetically descending on the Number of Times Abandoned.
	 *
	 * @param  array | object $value1 All data of the list.
	 * @param  array | object $value2 All data of the list.
	 * @return sorted array
	 * @since  3.4
	 */
	public function wcap_class_abandoned_number_dsc( $value1, $value2 ) {
		return $value2->abandoned_number - $value1->abandoned_number;
	}
	/**
	 * It will sort the alphabetically ascending on the Number of Times Recovered.
	 *
	 * @param  array | object $value1 All data of the list.
	 * @param  array | object $value2 All data of the list.
	 * @return sorted array
	 * @since  3.4
	 */
	public function wcap_class_recover_number_asc( $value1, $value2 ) {
		return $value1->recover_number - $value2->recover_number;
	}
	/**
	 * It will sort the alphabetically descending on the Number of Times Recovered.
	 *
	 * @param  array | object $value1 All data of the list.
	 * @param  array | object $value2 All data of the list.
	 * @return sorted array
	 * @since  3.4
	 */
	public function wcap_class_recover_number_dsc( $value1, $value2 ) {
		return $value2->recover_number - $value1->recover_number;
	}
	/**
	 * It will sort the alphabetically ascending on the Product Name.
	 *
	 * @param  array | object $value1 All data of the list.
	 * @param  array | object $value2 All data of the list.
	 * @return sorted array
	 * @since  3.4
	 */
	public function wcap_class_product_name_asc( $value1, $value2 ) {
		return strcasecmp( $value1->product_name, $value2->product_name );
	}
	/**
	 * It will sort the alphabetically descending on the Product Name.
	 *
	 * @param  array | object $value1 All data of the list.
	 * @param  array | object $value2 All data of the list.
	 * @return sorted array
	 * @since  3.4
	 */
	public function wcap_class_product_name_dsc( $value1, $value2 ) {
		return strcasecmp( $value2->product_name, $value1->product_name );
	}
}
