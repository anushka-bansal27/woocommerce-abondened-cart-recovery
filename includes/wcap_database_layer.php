<?php
/**
 * It will have all the common function needed all over the plugin.
 * 
 * @author   LittleCodr
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Database-Layer
 * @since 7.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCAP_DB_Layer' ) ) {

	/**
	 * Database access layer for performing DB related activities
	 */
	class WCAP_DB_Layer {

		/**
		 * Function ton insert data in database
		 *
		 * @param int $user_id User ID
		 * @param string $cart_info Cart Info Object encoded as string
		 * @param int $abandoned_time Time
		 * @param string $ignored If Cart Ignored
		 * @param int $recovered Recovered Order Number
		 * @param string $unsubscribe If unsubscribed
		 * @param string $user_type User Type
		 * @param string $language Current Language
		 * @param string $session_id Session ID
		 * @param string $ip_address IP Address
		 * @param string $email_reminder_status Reminder Email Status
		 * @param string $wcap_trash If trashed
		 *
		 * @globals mixed $wpdb Global Variable
		 *
		 * @since 7.7
		 *
		 * @return int Inserted ID
		 */
		//public static function insert( $user_id = '', $cart_info = '', $abandoned_time = '', $ignored = '', $recovered = '', $user_type = '', $language = '' , $session_id = '' , $ip_address = '' , $email_reminder_status = '', $wcap_trash = '' , $unsubscribe = '0' ) {
		public static function insert(  $data = array()  ) {

			if ( ! Wcap_Common::wcap_validate_cart( $data['abandoned_cart_info'] ) ) {
				return false;
			}

			global $wpdb;

			if ( function_exists( 'icl_object_id' ) ) {
				$cart_info = self::add_wcml_currency( $data['abandoned_cart_info']  );
			}
			if ( defined ( 'WOOCOMMERCE_MULTICURRENCY_VERSION' ) ) {
				$cart_info = self::add_wc_multicurrency( $data['abandoned_cart_info']  );
			}

			$data['abandoned_cart_info']  = apply_filters( 'wcap_cart_info_before_insert', $cart_info );

			$wpdb->insert(// phpcs:ignore 
				WCAP_ABANDONED_CART_HISTORY_TABLE,
				$data
			);

			$insert_id = $wpdb->insert_id;

			do_action( 'wcap_after_insert_cart_details', $insert_id );

			$reminder_types = array();

			if ( 'on' === get_option( 'wcap_enable_sms_reminders' ) ) {
				array_push( $reminder_types, 'type = "sms"' );
			}

			$reminder_types = apply_filters( 'wcap_add_meta_reminder_types', $reminder_types );

			if ( count( $reminder_types ) > 0 ) {
				wcap_common::wcap_insert_cart_id( $insert_id, $reminder_types );
			}

			return $insert_id;
		}

		/**
		 * Add Currency to cart info object with WPML active and currency switcher present
		 *
		 * @param string $cart_info Cart Info object as string
		 *
		 * @return string cart_info object with currency added
		 *
		 * @since 7.7
		 *
		 * @globals mixed Global woocommerce wpml object
		 */
		public static function add_wcml_currency( $cart_info ) {
			global $woocommerce_wpml;

			$cart_info = stripslashes( $cart_info );

			if ( isset( $woocommerce_wpml->settings['enable_multi_currency'] ) &&
				$woocommerce_wpml->settings['enable_multi_currency'] == '2' ) {

				$client_currency = function_exists( 'WC' ) ? WC()->session->get( 'client_currency' ) : $woocommerce->session->get( 'client_currency' );

				$cart_info = json_decode( $cart_info, true );

				if ( ! empty( $cart_info ) && isset( $cart_info['cart'] ) && ! empty( $cart_info['cart'] ) &&
					isset( $client_currency ) && $client_currency !== '' ) {

					$cart_info['currency'] = $client_currency;
				}

				$cart_info = json_encode( $cart_info );
			}

			return ( $cart_info );
			//return addslashes( $cart_info );
		}

		/**
		 * Add user currency in abandoned cart info for WC Multicurrency.
		 *
		 * @param string $cart_info - json encoded cart data.
		 * @return string $cart_info - json encoded cart data.
		 * @since 8.10.0
		 */
		public static function add_wc_multicurrency( $cart_info ) {

			$cart_info = json_decode( stripslashes( $cart_info ), true );

			if ( ! empty( $cart_info ) && isset( $cart_info['cart'] ) && ! empty( $cart_info['cart'] ) ) {

				$cart_info['currency'] = get_woocommerce_currency();
			}

			$cart_info = json_encode( $cart_info );

			return $cart_info;
		}

		/**
		 * Delete Abandoned Cart Record
		 *
		 * @param array $value Key => Value pair to be deleted.
		 *
		 * @since 7.10.0
		 *
		 * @globals mixed Global $wpdb object
		 */
		public static function delete( $where = array() ) {
			// return without updating if no condition provided.
			if ( empty( $where ) ) {
				return false;
			}
			global $wpdb;

			do_action( 'wcap_before_delete_cart_history', $where );
			$wpdb->delete( WCAP_ABANDONED_CART_HISTORY_TABLE, $where ); // phpcs:ignore
			do_action( 'wcap_after_delete_cart_history', $where );

		}

		/**
		 * Update Abandoneded Cart Record
		 *
		 * @param array $value Key => Value pair to be updated.
		 *
		 * @since 7.10.0
		 *
		 * @globals mixed Global $wpdb object
		 */
		public static function update( $value = array(), $where = array() ) {
					
			// return without updating if no condition provided.
			if ( empty( $where ) ) {
				return;
			}
			global $wpdb;

			do_action( 'wcap_before_update_cart_history', $value, $where );
			$wpdb->update( WCAP_ABANDONED_CART_HISTORY_TABLE, $value , $where); // phpcs:ignore
			do_action( 'wcap_after_update_cart_history', $value, $where );

		}

		/**
		 * Unsubscribe the cart.
		 *
		 * @param string $wcap_cart_id Cart ID.
		 * @since 8.8.0
		 */
		public static function wcap_unsubscribe_cart( $wcap_cart_id ) {
			global $wpdb;
			//phpcs:disable
			$wpdb->query(
				$wpdb->prepare(
					'UPDATE `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '`
					SET unsubscribe_link = "1"
					WHERE id= %d',
					$wcap_cart_id
				)
			);
		}

		/**
		 * Update Guest Email
		 *
		 * @param string $user_id  User ID.
		 * @param string $email_id Email ID.
		 */
		public static function wcap_update_email( $user_id, $email_id ) {
			global $wpdb;

			$wpdb->update(
				WCAP_GUEST_CART_HISTORY_TABLE,
				array(
					'email_id' => $email_id,
				),
				array(
					'id' => $user_id,
				)
			);
		}

		/**
		 * Return count of events for given template ID across time.
		 *
		 * @param int    $template_id - Popup Template ID.
		 * @param string $event - Event ID (o... 5).
		 * @return int   $count - Count of the event records present.
		 *
		 * @since 8.14.0
		 */
		public static function wcap_get_stats_for_event( $template_id, $event ) {
			global $wpdb;
			$cnt = $wpdb->get_var( // phpcs:ignore
				$wpdb->prepare(
					'SELECT count(id) FROM ' . WCAP_AC_STATS . ' WHERE event=%s AND template_id = %d',
					$event,
					$template_id
				)
			);
			return $cnt;
		}

		/**
		 * Fetch count of event across all popup templates in a time range.
		 *
		 * @param int    $template_id - Popup Template ID.
		 * @param string $start - Start timestamp.
		 * @param string $end - End timestamp.
		 * @return int   $count - Count of events.
		 *
		 * @since 8.14.0
		 */
		public static function wcap_get_stats_for_daterange( $event, $start, $end ) {
			global $wpdb;
			$count = $wpdb->get_var( // phpcs:ignore
				$wpdb->prepare(
					'SELECT count(id) FROM ' . WCAP_AC_STATS . ' WHERE event = %s AND timestamp >= %d AND timestamp <= %d',
					$event,
					$start,
					$end
				)
			);
			return $count;
			//phpcs:enable
		}

		/**
		 * Insert event record in wp_ac_statistics.
		 *
		 * @param int    $template_id - Popup Template ID.
		 * @param string $template_type - Popup Template Type.
		 * @param string $event - Event (0...5).
		 *
		 * @since 8.14.0
		 */
		public static function wcap_insert_event( $template_id, $template_type, $event ) {

			global $wpdb;
			$insert_data = array(
				'timestamp'     => current_time( 'timestamp' ), // phpcs:ignore
				'template_type' => $template_type,
				'template_id'   => $template_id,
				'event'         => $event,
			);

			$wpdb->insert( // phpcs:ignore
				WCAP_AC_STATS,
				$insert_data
			);
		}

	}

}

return new WCAP_DB_Layer;
