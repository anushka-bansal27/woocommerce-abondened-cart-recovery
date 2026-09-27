<?php
/**
 * Abandoned Cart Pro for WooCommerce
 *
 * It will show list of Active and deactive SMS templates in
 * WooCommerce->Abandoned Carts->Cart Recovery->SMS Notifications
 *
 * @author   Tyche Softwares
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Classes
 * @category Classes
 * @since    7.9
 */

// Load WP_List_Table if not loaded.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
/**
 * Show SMS templates list in Cart Recovery->SMS Notifications
 *
 * @since 7.9
 */
class Wcap_WhatsApp_Templates extends WP_List_Table {

	/**
	 * Number of results to show per page
	 *
	 * @var string
	 * @since 7.9
	 */
	public $per_page = 20;

	/**
	 * URL of this page
	 *
	 * @var string
	 * @since 7.9
	 */
	public $base_url;

	/**
	 * Total number of templates
	 *
	 * @var int
	 * @since 7.9
	 */
	public $total_count;

	/**
	 * It will add the bulk action and other variable needed for the class.
	 *
	 * @since 7.9
	 * @see WP_List_Table::__construct()
	 */
	public function __construct() {

		// Set parent defaults.
		parent::__construct(
			array(
				'singular' => __( 'whatsapp_template_id', 'woocommerce-ac' ), // singular name of the listed records.
				'plural'   => __( 'whatsapp_template_ids', 'woocommerce-ac' ), // plural name of the listed records.
				'ajax'     => true, // Does this table support ajax?
			)
		);
		$this->wcap_get_whatsapp_templates_count();
		$this->process_bulk_action();
		$this->base_url = admin_url( 'admin.php?page=woocommerce_ac_page&action=cart_recovery&section=whatsapp' );
	}
	/**
	 * It will prepare the list of the SMS Templates, columns, pagination, sortable column and other data.
	 *
	 * @since 7.9
	 */
	public function wcap_whatsapp_templates_prepare_items() {

		$hidden      = $this->wcap_hidden_whatsapp_cols();
		$sortable    = array();
		$data        = $this->wcap_whatsapp_templates();
		$total_items = $this->total_count;
		$this->items = $data;

		$this->set_pagination_args(
			array(
				'total_items' => $total_items, // WE have to calculate the total number of items.
				'per_page'    => $this->per_page, // WE have to determine how many items to show on a page.
				'total_pages' => ceil(
					$total_items / $this->per_page
				), // WE have to calculate the total number of pages.
			)
		);
		return array( 'whatsapp_templates' => $data, 'total_items' => $total_items, 'total_pages' => $this->per_page, ceil( $total_items / $this->per_page ) );//phpcs:disable
	}

	/**
	 * Returns the data for the SMS Template list.
	 *
	 * @globals mixed $wpdb
	 * @return array $return_templates_display Key and value of all the columns
	 * @since 7.9
	 */
	public function wcap_whatsapp_templates() {
		global $wpdb;

		$return_templates_data = array();

		// Get the count of sms templates from the DB.
		$whatsapp_list = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . WCAP_NOTIFICATION_TEMPLATES_TABLE .' WHERE notification_type = %s',
				'whatsapp'
			),
		);

		$template_count = 0;
		if ( is_array( $whatsapp_list ) && count( $whatsapp_list ) > 0 && false !== $whatsapp_list ) {
			$wcap_get_decimal = wc_get_price_decimals();
			foreach ( $whatsapp_list as $whatsapp_details ) {

				$template_id  = $whatsapp_details->id; // SMS ID.
				$ac_whatsapp_count = $wpdb->get_var( // phpcs:ignore
					$wpdb->prepare(
						"SELECT COUNT(`id`) FROM " . WCAP_EMAIL_SENT_HISTORY_TABLE . " WHERE template_id= %d AND notification_type='whatsapp'",// phpcs:ignore
						$template_id
					)
				);

                $links_clicked = $wpdb->get_var( //phpcs:ignore
					$wpdb->prepare(
						'SELECT count( DISTINCT( link.notification_sent_id ) ) FROM ' . WCAP_LINK_CLICKED_TABLE . ' AS link INNER JOIN ' . WCAP_EMAIL_SENT_HISTORY_TABLE . ' AS sent ON link.notification_sent_id = sent.id WHERE sent.template_id = %d',// phpcs:ignore
						$template_id
					)
				);
				$links_rate    = $links_clicked > 0 && $ac_whatsapp_count > 0 ? round( ( $links_clicked / $ac_whatsapp_count ) * 100, $wcap_get_decimal ) : 0;

				$wcap_number_of_time_recover = $wpdb->get_var( // phpcs:ignore
					$wpdb->prepare(
						"SELECT COUNT(`id`) FROM " . WCAP_EMAIL_SENT_HISTORY_TABLE . " WHERE recovered_order = '1' AND template_id = %d",// phpcs:ignore
						$template_id
					)
				);

				$wcap_recover_ratio = 0;
				if ( 0 !== (int) $ac_whatsapp_count ) {
					$wcap_recover_ratio = $wcap_number_of_time_recover / $ac_whatsapp_count * 100;
				}
				// Default.
				$return_templates_data[ $template_count ]          = new stdClass();
				$return_templates_data[ $template_count ]->id      = $template_id;
				$return_templates_data[ $template_count ]->updated = 0;

				$return_templates_data[ $template_count ]->template_name = $whatsapp_details->template_name;

				$return_templates_data[ $template_count ]->body        = $whatsapp_details->body; // Complete Message.
				$return_templates_data[ $template_count ]->coupon_code = $whatsapp_details->coupon_code; // Coupon Code.

				$return_templates_data[ $template_count ]->sent_time       = $whatsapp_details->frequency . ' ' . $whatsapp_details->day_or_hour;
				$return_templates_data[ $template_count ]->sms_sent        = 0;
				$return_templates_data[ $template_count ]->click_rate      = $links_rate . '%';
				$return_templates_data[ $template_count ]->conversion_rate = round( $wcap_recover_ratio, $wcap_get_decimal ) . '%';
				$return_templates_data[ $template_count ]->is_active       = $whatsapp_details->is_active;
				$return_templates_data[ $template_count ]->frequency       = $whatsapp_details->frequency;
				$return_templates_data[ $template_count ]->day_or_hour     = $whatsapp_details->day_or_hour;

				if ( $whatsapp_details->coupon_code > 0 ) {

					$coupon_title = get_the_title( $whatsapp_details->coupon_code );
					$return_templates_data[ $template_count ]->coupon_ids[ $whatsapp_details->coupon_code ] = $coupon_title;
					$return_templates_data[ $template_count ]->coupon_text                             = $coupon_title;
				}

				$template_count++;
			}
		}


		return apply_filters( 'wcap_whatsapp_templates_data', $return_templates_data );

	}
	/**
	 * This function is used for sms templates count.
	 *
	 * @globals mixed $wpdb
	 * @since   8.2
	 */
	public function wcap_get_whatsapp_templates_count() {
		global $wpdb;
		$this->total_count = $wpdb->get_var( 'SELECT COUNT(`id`) FROM `' . WCAP_NOTIFICATION_TEMPLATES_TABLE . "` where notification_type='whatsapp'" ); // phpcs:ignore
	}
}
