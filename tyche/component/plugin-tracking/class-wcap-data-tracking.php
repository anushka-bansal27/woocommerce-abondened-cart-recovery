<?php
/**
 * Abandon Cart Pro for WooCommerce - Data Tracking Class
 *
 * @version 1.0.0
 * @since   9.7.0
 * @package Abandon Cart Pro/Data Tracking
 * @author  Tyche Softwares
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Wcap_Data_Tracking' ) ) :

	/**
	 * Abandon Cart Pro Data Tracking Core.
	 */
	class Wcap_Data_Tracking {

		/**
		 * Construct.
		 *
		 * @since 9.7.0
		 */
		public function __construct() {

			// Include JS script for the notice.
			add_filter( 'ts_tracker_data', array( __CLASS__, 'wcap_pro_ts_add_plugin_tracking_data' ), 10, 1 );
			add_action( 'admin_footer', array( __CLASS__, 'ts_admin_notices_scripts' ) );
			// Send Tracker Data.
			add_action( 'wcap_init_tracker_completed', array( __CLASS__, 'init_tracker_completed' ), 10, 2 );
			add_filter( 'wcap_ts_tracker_display_notice', array( __CLASS__, 'wcap_pro_ts_tracker_display_notice' ), 10, 1 );
			add_filter( 'wcap_ts_tracker_data', array( __CLASS__, 'wcap_pro_plugin_tracking_data' ), 10, 1 );
		}

		/**
		 * Send the plugin data when the user has opted in
		 *
		 * @hook ts_tracker_data
		 * @param array $data All data to send to server.
		 *
		 * @return array $plugin_data All data to send to server.
		 */
		public static function wcap_pro_ts_add_plugin_tracking_data( $data ) {

			$plugin_short_name = 'wcap';
			if ( ! isset( $_GET[ $plugin_short_name . '_tracker_nonce' ] ) ) {
				return $data;
			}

			$tracker_option = isset( $_GET[ $plugin_short_name . '_tracker_optin' ] ) ? $plugin_short_name . '_tracker_optin' : ( isset( $_GET[ $plugin_short_name . '_tracker_optout' ] ) ? $plugin_short_name . '_tracker_optout' : '' ); // phpcs:ignore
			if ( '' === $tracker_option || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[ $plugin_short_name . '_tracker_nonce' ] ) ), $tracker_option ) ) {
				return $data;
			}

			$data = self::wcap_pro_plugin_tracking_data( $data );
			return $data;
		}

		/**
		 * Add admin notice script.
		 */
		public static function ts_admin_notices_scripts() {
			$nonce      = wp_create_nonce( 'tracking_notice' );
			$plugin_url = plugins_url() . '/woocommerce-abandon-cart-pro';

			wp_enqueue_script(
				'wcap_ts_dismiss_notice',
				$plugin_url . '/tyche/assets/tyche-dismiss-tracking-notice.js',
				'',
				WCAP_PLUGIN_VERSION . '_' . time(),
				false
			);

			wp_localize_script(
				'wcap_ts_dismiss_notice',
				'wcap_ts_dismiss_notice_params',
				array(
					'ts_prefix_of_plugin' => 'wcap',
					'ts_admin_url'        => admin_url( 'admin-ajax.php' ),
					'tracking_notice'     => $nonce,
				)
			);
		}

		/**
		 * Add tracker completed.
		 */
		public static function init_tracker_completed() {
			header( 'Location: ' . admin_url( 'admin.php?page=woocommerce_ac_page' ) );
			exit;
		}

		/**
		 * Display admin notice on specific page.
		 *
		 * @param array $is_flag Is Flag defailt value true.
		 */
		public static function wcap_pro_ts_tracker_display_notice( $is_flag ) {
			global $current_section;
			if ( isset( $_GET['page'] ) && 'woocommerce_ac_page' === $_GET['page'] ) { // phpcs:ignore
				$is_flag = true;
			}
			return $is_flag;
		}

		/**
		 * Returns plugin data for tracking.
		 *
		 * @param array $data - Generic data related to WP, WC, Theme, Server and so on.
		 * @return array $data - Plugin data included in the original data received.
		 * @since 1.3.0
		 */
		public static function wcap_pro_plugin_tracking_data( $data ) {

			$plugin_data = array(
				'ts_meta_data_table_name' => 'ts_tracking_wcap_meta_data',
				'ts_plugin_name'          => 'Abandoned Cart Pro for WooCommerce',
			);
			// Store abandoned count info.
			$plugin_data['abandoned_orders'] = Wcap_Common::wcap_get_abandoned_order_count( 'wcap_all_abandoned_lifetime' );

			// Store recovred count info.
			$plugin_data['recovered_orders'] = self::wcap_get_recovered_order_count();

			// Store abandoned orders amount.
			$plugin_data['abandoned_orders_amount'] = self::wcap_get_abandoned_amount();

			// Store recovered count info.
			$plugin_data['recovered_orders_amount'] = self::wcap_get_recovered_amount();

			// get all email template count.
			$plugin_data['email_template_count'] = self::wcap_get_template_count( 'email' );

			// get all sms template count.
			$plugin_data['sms_template_count'] = self::wcap_get_template_count( 'sms' );

			// get all fb template count.
			$plugin_data['fb_template_count'] = self::wcap_get_template_count( 'fb' );

			// Store abandoned cart emails sent count info.
			$plugin_data['sent_emails'] = self::wcap_get_send_reminder_count( 'email' );

			// Store email templates info.
			$plugin_data['all_templates_data'] = self::wcap_get_all_templates_data();

			// All popup templates.
			$plugin_data['all_popup_templates_data'] = self::wcap_get_popup_data();

			// Store only logged-in users abandoned cart count info.
			$plugin_data['logged_in_abandoned_orders'] = self::wcap_get_loggedin_abandoned_carts();

			// Store only logged-in users abandoned cart count info.
			$plugin_data['guest_abandoned_orders'] = self::wcap_get_guest_abandoned_carts();

			// Store only logged-in users abandoned cart amount info.
			$plugin_data['logged_in_abandoned_orders_amount'] = self::wcap_get_loggedin_user_abandoned_cart_amount();

			// store only guest users abandoned cart amount.
			$plugin_data['guest_abandoned_orders_amount'] = self::wcap_get_guest_user_abandoned_cart_amount();

			// Store only logged-in users recovered cart amount info.
			$plugin_data['logged_in_recovered_orders_amount'] = self::wcap_get_loggedin_user_recovered_cart_amount();

			// Store only guest users recovered cart amount.
			$plugin_data['guest_recovered_orders_amount'] = self::wcap_get_guest_user_recovered_cart_amount();

			// Store abandoned cart SMS reminders sent count info.
			$plugin_data['sent_sms'] = self::wcap_get_send_reminder_count( 'sms' );

			// Get connectors list.
			$connectors_list                = self::wcap_get_connectors_list();
			$plugin_data['connectors_list'] = wp_json_encode( $connectors_list );

			// get connectors entries.
			$plugin_data['connectors_cart_entries'] = self::wcap_get_connectors_entries( $connectors_list );

			// License Data.
			$plugin_data['license_type']   = get_option( 'wcap_edd_license_type' );
			$plugin_data['license_status'] = get_option( 'edd_sample_license_status_ac_woo' );

			// Migrated from Lite?
			$plugin_data['migration_status'] = get_option( 'wcap_migrated_from_lite' );
			// Get all plugin options info.
			$plugin_data['settings']       = self::wcap_get_plugin_settings();
			$plugin_data['plugin_version'] = WCAP_PLUGIN_VERSION;
			$plugin_data['tracking_usage'] = get_option( 'wcap_allow_tracking' );

			$data['plugin_data'] = $plugin_data;

			return $data;
		}

		/**
		 * We will return the recovered order amount based on the time period has been given.
		 *
		 * @globals mixed $wpdb
		 * @return int $return_recovered_count Count of recovered order
		 * @since 5.0
		 */
		public static function wcap_get_recovered_order_count() {
			global $wpdb;
			$return_recovered_count = 0;

			$blank_cart_info       = '{"cart":[]}';
			$blank_cart_info_guest = '[]';

			$return_recovered_count = $wpdb->get_var( // phpcs:ignore
				'SELECT count( recovered_cart ) FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE recovered_cart > 0 AND wcap_trash = '' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND cart_ignored = '1' ORDER BY recovered_cart desc " // phpcs:ignore
			);
			return $return_recovered_count;
		}

		/**
		 * Get abandoned amount of all users.
		 *
		 * @return int $wcap_abandoned_amount All abandoned amount.
		 */
		public static function wcap_get_abandoned_amount() {
			global $wpdb;
			$wcap_data = array();

			$blank_cart_info       = '{"cart":[]}';
			$blank_cart_info_guest = '[]';

			$ac_cutoff_time       = is_numeric( get_option( 'ac_cart_abandoned_time', 10 ) ) ? get_option( 'ac_cart_abandoned_time', 10 ) : 10;
			$cut_off_time         = $ac_cutoff_time * 60;
			$current_time         = current_time( 'timestamp' ); // phpcs:ignore
			$compare_time         = $current_time - $cut_off_time;
			$ac_cutoff_time_guest = is_numeric( get_option( 'ac_cart_abandoned_time_guest', 10 ) ) ? get_option( 'ac_cart_abandoned_time_guest', 10 ) : 10;
			$cut_off_time_guest   = $ac_cutoff_time_guest * 60;
			$current_time         = current_time( 'timestamp' ); // phpcs:ignore
			$compare_time_guest   = $current_time - $cut_off_time_guest;

			$wcap_abandoned_amount = 0;

			$wcap_get_abandoned_amount_results = $wpdb->get_results( // phpcs:ignore
				$wpdb->prepare(
					'SELECT abandoned_cart_info FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE ( user_type = 'REGISTERED' AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_time <= %d AND recovered_cart = 0 AND wcap_trash = '') OR ( user_type = 'GUEST' AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_time <= %d AND recovered_cart = 0 AND wcap_trash = '' ) ", // phpcs:ignore
					"%$blank_cart_info%",
					$compare_time,
					$blank_cart_info_guest,
					"%$blank_cart_info%",
					$compare_time_guest
				),
				ARRAY_A
			);

			$wcap_abandoned_amount = self::wcap_get_amount( $wcap_get_abandoned_amount_results );

			return $wcap_abandoned_amount;
		}

		/**
		 * It will fetch all amount of the recovered orders.
		 *
		 * @return int $wcap_recovered_amount All recovered amount.
		 */
		public static function wcap_get_recovered_amount() {
			global $wpdb;
			$wcap_data = array();

			$blank_cart_info       = '{"cart":[]}';
			$blank_cart_info_guest = '[]';

			$ac_cutoff_time       = is_numeric( get_option( 'ac_cart_abandoned_time', 10 ) ) ? get_option( 'ac_cart_abandoned_time', 10 ) : 10;
			$cut_off_time         = $ac_cutoff_time * 60;
			$current_time         = current_time( 'timestamp' ); // phpcs:ignore
			$compare_time         = $current_time - $cut_off_time;
			$ac_cutoff_time_guest = is_numeric( get_option( 'ac_cart_abandoned_time_guest', 10 ) ) ? get_option( 'ac_cart_abandoned_time_guest', 10 ) : 10;
			$cut_off_time_guest   = $ac_cutoff_time_guest * 60;
			$current_time         = current_time( 'timestamp' ); // phpcs:ignore
			$compare_time_guest   = $current_time - $cut_off_time_guest;

			$wcap_recovered_amount = 0;

			$wcap_get_recovered_results = $wpdb->get_results( // phpcs:ignore
				$wpdb->prepare( // phpcs:ignore
					'SELECT abandoned_cart_info FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE ( user_type = 'REGISTERED' AND abandoned_cart_info NOT LIKE %s AND recovered_cart != 0 AND wcap_trash = '') OR ( user_type = 'GUEST' AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s AND recovered_cart != 0 AND wcap_trash = '' )", // phpcs:ignore
					"%$blank_cart_info%",
					$blank_cart_info_guest,
					"%$blank_cart_info%"
				),
				ARRAY_A
			);

			$wcap_recovered_amount = self::wcap_get_amount( $wcap_get_recovered_results );

			return $wcap_recovered_amount;
		}

		/**
		 * Get count of the fb templates.
		 *
		 * @param string $notification_type - type of notification.
		 * @return int   $wcap_fb_template_count All Sent emails amount.
		 */
		public static function wcap_get_template_count( $notification_type = 'email' ) {
			global $wpdb;
			$wcap_fb_template_count = $wpdb->get_var( // phpcs:ignore
				$wpdb->prepare(
					'SELECT COUNT(id) FROM ' . WCAP_NOTIFICATION_TEMPLATES_TABLE . ' where notification_type = %s', // phpcs:ignore
					$notification_type
				)
			);
			return $wcap_fb_template_count;
		}

		/**
		 * Get all sent reminders count.
		 *
		 * @param string $type - reminder type.
		 * @return int   $wcap_reminders_sent_count All Sent emails amount.
		 */
		public static function wcap_get_send_reminder_count( $type = 'email' ) {
			global $wpdb;
			$wcap_reminders_sent_count = $wpdb->get_var( // phpcs:ignore
				$wpdb->prepare(
					'SELECT COUNT(id) FROM ' . WCAP_EMAIL_SENT_HISTORY_TABLE . ' where notification_type = %s', // phpcs:ignore
					$type,
				)
			);

			return $wcap_reminders_sent_count;
		}

		/**
		 * It will fetch all the template data.
		 *
		 * @return array $wcap_templates_data All data of template
		 */
		private static function wcap_get_all_templates_data() {

			global $wpdb;
			$wcap_email_templates_count   = 0;
			$wcap_email_templates_results = $wpdb->get_results( // phpcs:ignore
				'SELECT id, is_active, is_wc_template,frequency, day_or_hour, subject, match_rules, rules, notification_type, email_type, generate_unique_coupon_code, coupon_code FROM `' . WCAP_NOTIFICATION_TEMPLATES_TABLE . '`' // phpcs:ignore
			);

			$wcap_email_templates_count = count( $wcap_email_templates_results );

			$wcap_templates_data                     = array();
			$wcap_templates_data ['total_templates'] = $wcap_email_templates_count;

			foreach ( $wcap_email_templates_results as $wcap_email_templates_results_key => $wcap_email_templates_results_value ) {

				$wcap_template_id   = $wcap_email_templates_results_value->id;
				$wcap_template_time = $wcap_email_templates_results_value->frequency . ' ' . $wcap_email_templates_results_value->day_or_hour;
				$wcap_template_type = $wcap_email_templates_results_value->notification_type;

				$wcap_get_total_email_sent_for_template_count = $wpdb->get_var( // phpcs:ignore
					$wpdb->prepare(
						'SELECT COUNT(id) FROM `' . WCAP_EMAIL_SENT_HISTORY_TABLE . '` WHERE template_id = %d', // phpcs:ignore
						(int) $wcap_email_templates_results_value->id
					)
				);

				$wcap_number_of_time_recover = $wpdb->get_var( // phpcs:ignore
					$wpdb->prepare(
						'SELECT COUNT(`id`) FROM ' . WCAP_EMAIL_SENT_HISTORY_TABLE . " WHERE recovered_order = '1' AND template_id = %d ", // phpcs:ignore
						(int) $wcap_email_templates_results_value->id,
					)
				);

				$wcap_templates_data[ 'template_id_' . $wcap_template_id ]['is_activate']      = 1 == $wcap_email_templates_results_value->is_active ? 'Active' : 'Deactive'; // phpcs:ignore
				$wcap_templates_data[ 'template_id_' . $wcap_template_id ]['is_wc_template']   = 1 == $wcap_email_templates_results_value->is_wc_template ? 'Yes' : 'No'; // phpcs:ignore
				$wcap_templates_data[ 'template_id_' . $wcap_template_id ]['template_time']    = $wcap_template_time;
				$wcap_templates_data[ 'template_id_' . $wcap_template_id ]['template_type']    = $wcap_template_type;
				$wcap_templates_data[ 'template_id_' . $wcap_template_id ]['total_email_sent'] = $wcap_get_total_email_sent_for_template_count;

				$wcap_templates_data[ 'template_id_' . $wcap_template_id ]['subject'] = $wcap_email_templates_results_value->subject;

				$wcap_templates_data[ 'template_id_' . $wcap_template_id ]['coupon_code']                 = $wcap_email_templates_results_value->coupon_code;
				$wcap_templates_data[ 'template_id_' . $wcap_template_id ]['generate_unique_coupon_code'] = $wcap_email_templates_results_value->generate_unique_coupon_code;

				$wcap_templates_data[ 'template_id_' . $wcap_template_id ]['email_type']  = $wcap_email_templates_results_value->email_type;
				$wcap_templates_data[ 'template_id_' . $wcap_template_id ]['match_rules'] = $wcap_email_templates_results_value->match_rules;

				$rules = isset( $wcap_email_templates_results_value->rules ) && '' !== $wcap_email_templates_results_value->rules ? json_decode( $wcap_email_templates_results_value->rules, true ) : '';
				$wcap_templates_data[ 'template_id_' . $wcap_template_id ]['rules'] = $rules;

				$wcap_recover_ratio = 0;
				if ( 0 != $wcap_get_total_email_sent_for_template_count ) { // phpcs:ignore
					$wcap_recover_ratio = $wcap_number_of_time_recover / $wcap_get_total_email_sent_for_template_count * 100;
				}

				$wcap_template_ratio = round( $wcap_recover_ratio, 2 ) . '%';
				$wcap_templates_data [ 'template_id_' . $wcap_email_templates_results_value->id ] ['wcap_recover_ratio'] = $wcap_template_ratio;

			}

			return $wcap_templates_data;
		}

		/**
		 * Return popup data.
		 *
		 * @since 9.7.0
		 */
		public static function wcap_get_popup_data() {

			global $wpdb;
			$results = $wpdb->get_results( // phpcs:ignore
				'SELECT id, popup_type, is_active, rules, name FROM `' . WCAP_ATC_RULES_TABLE . '` ORDER BY id asc' // phpcs:ignore
			);

			$i = 0;

			$rule_data = array(
				'custom_pages' => __( 'Custom Page', 'woocommerce-ac' ),
				'product_cat'  => __( 'Product Category', 'woocommerce-ac' ),
				'products'     => __( 'Products', 'woocommerce-ac' ),
			);

			$return_templates_data = array();

			if ( is_array( $results ) && count( $results ) > 0 ) {
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

					// Number of orders which came through.
					$orders_cnt = $wpdb->get_var( // phpcs:ignore
						$wpdb->prepare(
							'SELECT count( post_id ) FROM `' . $wpdb->prefix . 'postmeta` WHERE meta_key = %s AND meta_value = %d',
							'wcap_' . $template_type . '_template_id',
							$id
						)
					);

					$orders_cnt = 0 < $orders_cnt ? absint( $orders_cnt ) : 0;

					$return_templates_data[ $i ]->orders_count = $orders_cnt;

					++$i;
				}
			}

			$popup_templates = array();
			foreach ( $return_templates_data as $k => $v_data ) {
				$popup_templates[ "popup_$k" ] = $v_data;
			}
			return $popup_templates;
		}

		/**
		 * It will fetch the total abandoned cart of the logged in users.
		 *
		 * @return int $wcap_loggedin_users_carts_count Count of loggedin users cart.
		 */
		public static function wcap_get_loggedin_abandoned_carts() {
			global $wpdb;
			$wcap_data = array();

			$blank_cart_info       = '{"cart":[]}';
			$blank_cart_info_guest = '[]';

			$ac_cutoff_time                  = is_numeric( get_option( 'ac_cart_abandoned_time', 10 ) ) ? get_option( 'ac_cart_abandoned_time', 10 ) : 10;
			$cut_off_time                    = $ac_cutoff_time * 60;
			$current_time                    = current_time( 'timestamp' ); // phpcs:ignore
			$compare_time                    = $current_time - $cut_off_time;
			$wcap_loggedin_users_carts_count = 0;

			$wcap_loggedin_users_carts_count = $wpdb->get_var( // phpcs:ignore
				$wpdb->prepare(
					'SELECT COUNT(id) FROM ' . WCAP_ABANDONED_CART_HISTORY_TABLE . " WHERE ( user_type = 'REGISTERED' AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_time <= %d AND recovered_cart = 0 AND wcap_trash = '')", // phpcs:ignore
					"%$blank_cart_info%",
					$compare_time,
				)
			);

			return $wcap_loggedin_users_carts_count;
		}

		/**
		 * It will fetch the total abandoned cart of the guest users.
		 *
		 * @return int $wcap_guest_user_cart_count Count of guest users carts..
		 */
		public static function wcap_get_guest_abandoned_carts() {
			global $wpdb;
			$wcap_data = array();

			$blank_cart_info       = '{"cart":[]}';
			$blank_cart_info_guest = '[]';

			$ac_cutoff_time_guest       = is_numeric( get_option( 'ac_cart_abandoned_time_guest', 10 ) ) ? get_option( 'ac_cart_abandoned_time_guest', 10 ) : 10;
			$cut_off_time_guest         = $ac_cutoff_time_guest * 60;
			$current_time               = current_time( 'timestamp' ); // phpcs:ignore
			$compare_time_guest         = $current_time - $cut_off_time_guest;
			$wcap_guest_user_cart_count = 0;

			$wcap_guest_user_cart_count = $wpdb->get_var( // phpcs:ignore
				$wpdb->prepare(
					'SELECT COUNT(id) FROM ' . WCAP_ABANDONED_CART_HISTORY_TABLE . " WHERE  ( user_type = 'GUEST' AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_time <= %d AND recovered_cart = 0 AND wcap_trash = '' )", // phpcs:ignore
					$blank_cart_info_guest,
					"%$blank_cart_info%",
					$compare_time_guest,
				)
			);

			return $wcap_guest_user_cart_count;
		}

		/**
		 * It will fetch all the loggedin user abandoned cart amount.
		 *
		 * @return int $wcap_loggedin_abandoned_amount All loggedin users abandoned amount.
		 */
		public static function wcap_get_loggedin_user_abandoned_cart_amount() {

			global $wpdb;
			$wcap_data = array();

			$blank_cart_info       = '{"cart":[]}';
			$blank_cart_info_guest = '[]';

			$ac_cutoff_time = is_numeric( get_option( 'ac_cart_abandoned_time', 10 ) ) ? get_option( 'ac_cart_abandoned_time', 10 ) : 10;
			$cut_off_time   = $ac_cutoff_time * 60;
			$current_time   = current_time( 'timestamp' ); // phpcS:ignore
			$compare_time   = $current_time - $cut_off_time;

			$wcap_loggedin_abandoned_amount = 0;

			$wcap_get_loggedin_abandoned_amount_results = $wpdb->get_results( // phpcs:ignore
				$wpdb->prepare(
					'SELECT abandoned_cart_info FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE ( user_type = 'REGISTERED' AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_time <= %d AND recovered_cart = 0 AND wcap_trash = '') ", // phpcs:ignore
					"%$blank_cart_info%",
					$compare_time
				),
				ARRAY_A
			);

			$wcap_loggedin_abandoned_amount = self::wcap_get_amount( $wcap_get_loggedin_abandoned_amount_results );

			return $wcap_loggedin_abandoned_amount;
		}

		/**
		 * It will fetch all the Guest user abandoned cart amount.
		 *
		 * @return int $wcap_guest_abandoned_amount All Guest users abandoned amount.
		 */
		public static function wcap_get_guest_user_abandoned_cart_amount() {

			global $wpdb;
			$wcap_data             = array();
			$blank_cart_info       = '{"cart":[]}';
			$blank_cart_info_guest = '[]';
			$ac_cutoff_time_guest  = is_numeric( get_option( 'ac_cart_abandoned_time_guest', 10 ) ) ? get_option( 'ac_cart_abandoned_time_guest', 10 ) : 10;
			$cut_off_time_guest    = $ac_cutoff_time_guest * 60;
			$current_time          = current_time( 'timestamp' ); // phpcs:ignore
			$compare_time_guest    = $current_time - $cut_off_time_guest;

			$wcap_loggedin_abandoned_amount = 0;

			$wcap_get_guest_abandoned_amount_results = $wpdb->get_results( // phpcs:ignore
				$wpdb->prepare(
					'SELECT abandoned_cart_info FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE ( user_type = 'GUEST' AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_time <= %d AND recovered_cart = 0 AND wcap_trash = '' )", // phpcs:ignore
					$blank_cart_info_guest,
					"%$blank_cart_info%",
					$compare_time_guest
				),
				ARRAY_A
			);

			$wcap_guest_abandoned_amount = self::wcap_get_amount( $wcap_get_guest_abandoned_amount_results );

			return $wcap_guest_abandoned_amount;
		}

		/**
		 * It will fetch all the loggedin user recovered cart amount.
		 *
		 * @return int $wcap_loggedin_recovered_amount All loggedin users recovered amount.
		 */
		public static function wcap_get_loggedin_user_recovered_cart_amount() {

			global $wpdb;
			$wcap_data = array();

			$blank_cart_info       = '{"cart":[]}';
			$blank_cart_info_guest = '[]';

			$ac_cutoff_time = is_numeric( get_option( 'ac_cart_abandoned_time', 10 ) ) ? get_option( 'ac_cart_abandoned_time', 10 ) : 10;
			$cut_off_time   = $ac_cutoff_time * 60;
			$current_time   = current_time( 'timestamp' ); // phpcs:ignore
			$compare_time   = $current_time - $cut_off_time;

			$wcap_loggedin_recovered_amount = 0;

			$wcap_get_loggedin_recovered_results = $wpdb->get_results( // phpcs:ignore
				$wpdb->prepare(
					'SELECT abandoned_cart_info FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE ( user_type = 'REGISTERED' AND abandoned_cart_info NOT LIKE %s AND recovered_cart != 0 AND wcap_trash = '')", // phpcs:ignore
					"%$blank_cart_info%"
				),
				ARRAY_A
			);

			$wcap_loggedin_recovered_amount = self::wcap_get_amount( $wcap_get_loggedin_recovered_results );

			return $wcap_loggedin_recovered_amount;
		}

		/**
		 * It will fetch all the Guest user recovered cart amount.
		 *
		 * @return int $wcap_guest_recovered_amount All Guest users recovered amount.
		 */
		public static function wcap_get_guest_user_recovered_cart_amount() {

			global $wpdb;
			$wcap_data             = array();
			$blank_cart_info       = '{"cart":[]}';
			$blank_cart_info_guest = '[]';
			$ac_cutoff_time_guest  = is_numeric( get_option( 'ac_cart_abandoned_time_guest', 10 ) ) ? get_option( 'ac_cart_abandoned_time_guest', 10 ) : 10;
			$cut_off_time_guest    = $ac_cutoff_time_guest * 60;
			$current_time          = current_time( 'timestamp' ); // phpcs:ignore
			$compare_time_guest    = $current_time - $cut_off_time_guest;

			$wcap_guest_recovered_amount = 0;

			$wcap_get_guest_recovered_results = $wpdb->get_results( // phpcs:ignore
				$wpdb->prepare(
					'SELECT abandoned_cart_info FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE ( user_type = 'GUEST' AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s AND recovered_cart != 0 AND wcap_trash = '' )", // phpcs:ignore
					$blank_cart_info_guest,
					"%$blank_cart_info%"
				),
				ARRAY_A
			);

			$wcap_guest_recovered_amount = self::wcap_get_amount( $wcap_get_guest_recovered_results );

			return $wcap_guest_recovered_amount;
		}

		/**
		 * It will fetch the Abandoned and Recovered orders total amount.
		 *
		 * @param array $wcap_result - Result of query.
		 * @return string $wcap_amount - Total Abandoned | Recovered amount.
		 */
		public static function wcap_get_amount( $wcap_result ) {
			$wcap_amount = 0;
			foreach ( $wcap_result as $wcap_result_key => $wcap_result_value ) {
				$wcap_cart_info = json_decode( stripslashes( $wcap_result_value['abandoned_cart_info'] ) );

				$wcap_cart_details = array();
				if ( isset( $wcap_cart_info->cart ) ) {
					$wcap_cart_details = $wcap_cart_info->cart;
				}

				if ( is_object( $wcap_cart_details ) && count( get_object_vars( $wcap_cart_details ) ) > 0 ) {
					foreach ( $wcap_cart_details as $k => $v ) {
						if ( $v->line_subtotal_tax > 0 ) {
							$wcap_amount = $wcap_amount + $v->line_total + $v->line_subtotal_tax;
						} else {
							$wcap_amount = $wcap_amount + $v->line_total;
						}
					}
				}
			}
			return $wcap_amount;
		}

		/**
		 * Gets list of active connectors.
		 *
		 * @return array $connectors_list - list of all active connectors.
		 *
		 * @since 8.20.0
		 */
		public static function wcap_get_connectors_list() {
			$connectors_list = json_decode( get_option( 'wcap_active_connectors' ), true );
			$send_list       = array();
			if ( is_array( $connectors_list ) && count( $connectors_list ) > 0 ) {
				foreach ( $connectors_list as $conn_name => $conn_details ) {
					array_push( $send_list, $conn_name );
				}
			}
			return $send_list;
		}

		/**
		 * Gets list of connectors entries.
		 *
		 * @param  array $connectors_list - List of connectors.
		 * @return array $select_connectors_entries - list of all entries added by connectors.
		 *
		 * @since 8.20.0
		 */
		public static function wcap_get_connectors_entries( $connectors_list ) {
			global $wpdb;
			$select_connectors_entries = array();
			if ( ! empty( $connectors_list ) ) {
				foreach ( $connectors_list as $connector_name ) {
					$failed_entries = $wpdb->get_var( // phpcs:ignore
						$wpdb->prepare(
							'SELECT count(id) FROM `' . $wpdb->prefix . 'ac_connector_sync` WHERE status = "failed" AND connector_name = %s',
							$connector_name
						)
					);
					$select_connectors_entries[ $connector_name ]['failed_entries'] = $failed_entries;

					$complete_entries = $wpdb->get_var( // phpcs:ignore
						$wpdb->prepare(
							'SELECT count(id) FROM `' . $wpdb->prefix . 'ac_connector_sync` WHERE status = "complete" AND connector_name = %s',
							$connector_name
						)
					);
					$select_connectors_entries[ $connector_name ]['complete_entries'] = $complete_entries;
				}
			}
			return $select_connectors_entries;
		}

		/**
		 * Get all options of the plugin.
		 *
		 * @return array $wcap_settings  All settings
		 */
		private static function wcap_get_plugin_settings() {

			$wcap_settings['ac_enable_cart_emails']              = get_option( 'ac_enable_cart_emails' );
			$wcap_settings['ac_cart_abandoned_time']             = get_option( 'ac_cart_abandoned_time' );
			$wcap_settings['ac_cart_abandoned_time_guest']       = get_option( 'ac_cart_abandoned_time_guest' );
			$wcap_settings['ac_delete_abandoned_order_days']     = get_option( 'ac_delete_abandoned_order_days' );
			$wcap_settings['ac_email_admin_on_recovery']         = get_option( 'ac_email_admin_on_recovery' );
			$wcap_settings['ac_disable_guest_cart_email']        = get_option( 'ac_disable_guest_cart_email' );
			$wcap_settings['wcap_use_auto_cron']                 = get_option( 'wcap_use_auto_cron' );
			$wcap_settings['wcap_cron_time_duration']            = get_option( 'wcap_cron_time_duration' );
			$wcap_settings['ac_track_guest_cart_from_cart_page'] = get_option( 'ac_track_guest_cart_from_cart_page' );
			$wcap_settings['ac_disable_logged_in_cart_email']    = get_option( 'ac_disable_logged_in_cart_email' );

			// Added the missing settings.
			$wcap_settings['ac_cart_abandoned_after_x_days_order_placed'] = get_option( 'ac_cart_abandoned_after_x_days_order_placed' );
			$wcap_settings['ac_capture_email_from_forms']                 = get_option( 'ac_capture_email_from_forms' );

			$wcap_settings['ac_capture_email_address_from_url'] = get_option( 'ac_capture_email_address_from_url' );
			$wcap_settings['wcac_delete_plugin_data']           = get_option( 'wcac_delete_plugin_data' );
			$wcap_settings['wcap_enable_debounce']              = get_option( 'wcap_enable_debounce' );
			$wcap_settings['wcap_enable_gdpr_consent']          = get_option( 'wcap_enable_gdpr_consent' );
			$wcap_settings['wcap_enable_sms_consent']           = get_option( 'wcap_enable_sms_consent' );

			$wcap_settings['wcap_atc_close_icon_add_product_to_cart'] = get_option( 'wcap_atc_close_icon_add_product_to_cart' );
			$wcap_settings['wcap_product_name_redirect']              = get_option( 'wcap_product_name_redirect' );
			$wcap_settings['wcap_add_utm_to_links']                   = get_option( 'wcap_add_utm_to_links' );

			$wcap_settings['wcap_use_auto_cron']            = get_option( 'wcap_use_auto_cron' );
			$wcap_settings['wcap_cron_time_duration']       = get_option( 'wcap_cron_time_duration' );
			$wcap_settings['wcap_restrict_ip_address']      = get_option( 'wcap_restrict_ip_address' );
			$wcap_settings['wcap_restrict_email_address']   = get_option( 'wcap_restrict_email_address' );
			$wcap_settings['wcap_restrict_domain_address']  = get_option( 'wcap_restrict_domain_address' );
			$wcap_settings['wcap_restrict_countries']       = get_option( 'wcap_restrict_countries' );
			$wcap_settings['wcap_unsubscribe_landing_page'] = get_option( 'wcap_unsubscribe_landing_page' );

			$wcap_settings['wcap_email_reports_frequency'] = get_option( 'wcap_email_reports_frequency' );
			$wcap_settings['wcap_is_email_reports_enable'] = '' !== get_option( 'wcap_email_reports_emails_list' ) ? 'yes' : 'no';
			$wcap_settings['sms_reminders_enabled']        = get_option( 'wcap_enable_sms_reminders' );
			$wcap_settings['fb_reminders_enabled']         = get_option( 'wcap_enable_fb_reminders' );
			$wcap_settings['wcap_admin_notifications']     = get_option( 'wcap_email_admin_on_abandonment' );
			return $wcap_settings;
		}
	}

endif;

$wcap_data_tracking = new Wcap_Data_Tracking();
