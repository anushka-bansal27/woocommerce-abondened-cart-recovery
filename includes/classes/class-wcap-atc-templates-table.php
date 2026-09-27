<?php
/**
 * Abandoned Cart Pro for WooCommerce
 *
 * It will show list of Active and deactive email templates on Emails Templates tab.
 *
 * @author   Tyche Softwares
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Classes
 * @category Classes
 * @since    5.0
 */

// Load WP_List_Table if not loaded.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
/**
 * Show Email templates list on Email Templates tab.
 *
 * @since 2.0
 */
class Wcap_ATC_Templates_Table extends WP_List_Table {

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

		// Set parent defaults.
		parent::__construct(
			array(
				'singular' => __( 'template_id', 'woocommerce-ac' ), // singular name of the listed records.
				'plural'   => __( 'template_ids', 'woocommerce-ac' ), // plural name of the listed records.
				'ajax'     => false,                        // Does this table support ajax?
			)
		);
		$this->wcap_get_templates_count();
		$this->process_bulk_action();
		$this->base_url = admin_url( 'admin.php?page=woocommerce_ac_page&action=emailsettings&section=wcap_atc_settings' );
	}

	/**
	 * It will prepare the list of the Email Templates, columns, pagination, sortable column and other data.
	 *
	 * @since 2.0
	 */
	public function wcap_templates_prepare_items() {
		$hidden      = array(); // No hidden columns.
		$sortable    = $this->wcap_templates_get_sortable_columns();
		$data        = $this->wcap_templates_data();
		$total_items = $this->total_count;
		$this->items = $data;

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,                      // WE have to calculate the total number of items.
				'per_page'    => $this->per_page,                       // WE have to determine how many items to show on a page.
				'total_pages' => ceil( $total_items / $this->per_page ),   // WE have to calculate the total number of pages.
			)
		);
		return array(
			'popup_templates' => $data,
			'total_items'     => $total_items,
			'total_pages'     => ceil(
				$total_items / $this->per_page
			),
		);
	}

	/**
	 * This function is used for email templates count.
	 *
	 * @globals mixed $wpdb
	 * @since   2.0
	 */
	public function wcap_get_templates_count() {
		global $wpdb;
		$this->total_count = $wpdb->get_var( 'SELECT COUNT(`id`) FROM `' . WCAP_ATC_RULES_TABLE . '`' ); // phpcs:ignore
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

		$results = $wpdb->get_results( // phpcs:ignore
			'SELECT id, popup_type, is_active, rules, name FROM `' . WCAP_ATC_RULES_TABLE . '` ORDER BY id asc' // phpcs:ignore
		);

		$i = 0;

		$rule_data = array(
			'custom_pages' => __( 'Custom Page', 'woocommerce-ac' ),
			'product_cat'  => __( 'Product Category', 'woocommerce-ac' ),
			'products'     => __( 'Products', 'woocommerce-ac' ),
		);
		foreach ( $results as $key => $value ) {
			$return_templates_data[ $i ] = new stdClass();

			$id            = $value->id;
			$is_active     = $value->is_active;
			$template_type = isset( $value->popup_type ) && 'exit_intent' === $value->popup_type ? __( 'Exit Intent', 'woocommerce-ac' ) : __( 'Add to Cart', 'woocommerce-ac' );

			// Viewed.
			$viewed = WCAP_STATISTICS_MODEL::wcap_get_stats_for_event( absint( $id ), '0' );
			// Email Captured.
			$email_captured = WCAP_STATISTICS_MODEL::wcap_get_stats_for_event( absint( $id ), '1' );
			// No Thanks.
			$no_thanks = WCAP_STATISTICS_MODEL::wcap_get_stats_for_event( absint( $id ), '2' );

			$template_dismissed_cnt  = WCAP_STATISTICS_MODEL::wcap_get_stats_for_event( $id, '3' );
			$template_coupons_cnt    = WCAP_STATISTICS_MODEL::wcap_get_stats_for_event( $id, '4' );
			$template_redirected_cnt = WCAP_STATISTICS_MODEL::wcap_get_stats_for_event( $id, '5' );

			$rules        = json_decode( $value->rules );
			$rule_display = '';
			if ( is_array( $rules ) && count( $rules ) > 0 ) {
				foreach ( $rules as $rule_list ) {
					if ( '' !== $rule_list->rule_type ) {
						$rule_type     = $rule_list->rule_type;
						$rule_display .= $rule_data[ $rule_type ] . ', ';
					}
				}
				$rule_display = rtrim( $rule_display, ', ' );
			}
			$return_templates_data[ $i ]->sr             = $i + 1;
			$return_templates_data[ $i ]->id             = $id;
			$return_templates_data[ $i ]->template_name  = $value->name;
			$return_templates_data[ $i ]->type           = $template_type;
			$return_templates_data[ $i ]->rules          = $rule_display;
			$return_templates_data[ $i ]->is_active      = $is_active;
			$return_templates_data[ $i ]->viewed         = $viewed;
			$return_templates_data[ $i ]->no_thanks      = $no_thanks;
			$return_templates_data[ $i ]->email_captured = $email_captured;

			$return_templates_data[ $i ]->template_dismissed_cnt  = $template_dismissed_cnt;
			$return_templates_data[ $i ]->template_coupons_cnt    = $template_coupons_cnt;
			$return_templates_data[ $i ]->template_redirected_cnt = $template_redirected_cnt;

			$return_templates_data[ $i ]->edit_link   = str_replace( '&amp;', '&', wp_nonce_url( add_query_arg( array( 'action' => 'emailsettings', 'wcap_section' => 'wcap_atc_settings', 'mode' => 'edittemplate', 'id' => $id ), $this->base_url ), 'abandoned_order_nonce' ) );// phpcs:ignore
			$return_templates_data[ $i ]->copy_link   = str_replace( '&amp;', '&', wp_nonce_url( add_query_arg( array( 'action' => 'emailsettings', 'wcap_section' => 'wcap_atc_settings', 'mode' => 'copytemplate', 'id' => $id ), $this->base_url ), 'abandoned_order_nonce' ) );// phpcs:ignore
			$return_templates_data[ $i ]->delete_link = str_replace( '&amp;', '&', wp_nonce_url( add_query_arg( array( 'action' => 'emailsettings', 'wcap_section' => 'wcap_atc_settings', 'mode' => 'deleteatctemplate', 'id' => $id ), $this->base_url ), 'abandoned_order_nonce' ) );// phpcs:ignore

			// Number of orders which came through.
			$orders_cnt = $wpdb->get_var(// phpcs:ignore
				$wpdb->prepare(
					'SELECT count( post_id ) FROM `' . $wpdb->prefix . 'postmeta` WHERE meta_key = %s AND meta_value = %d',
					'wcap_' . $template_type . '_template_id',
					$id
				)
			);

			$orders_cnt                                = 0 < $orders_cnt ? absint( $orders_cnt ) : 0;
			$return_templates_data[ $i ]->orders_count = $orders_cnt;

			$i++;
		}

		// Sort for order date.
		if ( isset( $_GET['orderby'] ) && 'template_name' === $_GET['orderby'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			if ( isset( $_GET['order'] ) && 'asc' === $_GET['order'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				usort( $return_templates_data, array( __CLASS__, 'wcap_class_template_name_asc' ) );
			} else {
				usort( $return_templates_data, array( __CLASS__, 'wcap_class_template_name_dsc' ) );
			}
		}

		if ( isset( $_GET['paged'] ) && $_GET['paged'] > 1 ) { // phpcs:ignore WordPress.Security.NonceVerification
			$page_number = absint( sanitize_text_field( wp_unslash( $_GET['paged'] ) ) ) - 1; // phpcs:ignore WordPress.Security.NonceVerification
			$k           = $per_page * $page_number;
		} else {
			$k = 0;
		}

		$return_templates_display = array();
		for ( $j = $k; $j < ( $k + $per_page ); $j++ ) {
			if ( isset( $return_templates_data[ $j ] ) ) {
				$return_templates_display[ $j ] = $return_templates_data[ $j ];
			} else {
				break;
			}
		}
		return apply_filters( 'wcap_atc_templates_table_data', $return_templates_display );
	}
	/**
	 * It will sort the alphabetically ascending on Name Of Template column.
	 *
	 * @param  array | object $value1 All data of the list.
	 * @param  array | object $value2 All data of the list.
	 * @return sorted array
	 * @since  3.4
	 */
	public function wcap_class_template_name_asc( $value1, $value2 ) {
		return strcasecmp( $value1->template_name, $value2->template_name );
	}

	/**
	 * It will sort the alphabetically descending on Name Of Template column.
	 *
	 * @param  array | object $value1 All data of the list.
	 * @param  array | object $value2 All data of the list.
	 * @return sorted array
	 * @since  3.4
	 */
	public function wcap_class_template_name_dsc( $value1, $value2 ) {
		return strcasecmp( $value2->template_name, $value1->template_name );
	}
}
