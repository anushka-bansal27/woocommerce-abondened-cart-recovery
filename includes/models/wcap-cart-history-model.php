<?php
/**
 * cart History Model class 
 *
 * @author   LittleCodr
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Database-Layer
 * @since 8.15
 */
//namespace AC\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCAP_CART_HISTORY_MODEL' ) ) {

	/**
	 * Database access layer for performing DB related activities
	 */
	class WCAP_CART_HISTORY_MODEL extends WCAP_BASE_MODEL {


		/**
		 * Short name of the tablename, used for hooks.
		 *
		 * @var string $table_short.
		 */
		public static $table_short = 'cart_history';

		/**
		 * Table Constant name.
		 *
		 * @var instance single instance.
		 */
		public const TABLE_COSNT = WCAP_ABANDONED_CART_HISTORY_TABLE;


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
		public static function insert(  $data = array()  ) {

			if ( isset( $data['abandoned_cart_info'] ) && ! Wcap_Common::wcap_validate_cart( $data['abandoned_cart_info'] ) ) {
				return false;
			}

			global $wpdb;

			if ( function_exists( 'icl_object_id' ) ) {
				$data['abandoned_cart_info'] = self::add_wcml_currency( $data['abandoned_cart_info']  );
			}
			if ( defined ( 'WOOCOMMERCE_MULTICURRENCY_VERSION' ) ) {
				$data['abandoned_cart_info'] = self::add_wc_multicurrency( $data['abandoned_cart_info']  );
			}

			$data['abandoned_cart_info']  = apply_filters( 'wcap_cart_info_before_insert', $data['abandoned_cart_info'] );

			$data['unsubscribe_link']  = !empty( $data['unsubscribe_link'] ) ? $data['unsubscribe_link'] : 0;
			$insert_id                 = parent::insert( $data );

			do_action( 'wcap_after_insert_cart_details', $insert_id );

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

				$client_currency = function_exists( 'WC' ) ? WC()->session->get( 'wcml_client_currency' ) : $woocommerce->session->get( 'wcml_client_currency' );

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
		 * Add user currency in abandoned cart info for WOOCS.
		 *
		 * @param string $cart_info - json encoded cart data.
		 * @return string $cart_info - json encoded cart data.
		 * @since 8.10.0
		 */
		public static function add_woocs_currency( $cart_info ) {
			global $WOOCS;

			if ( ! is_object( $WOOCS ) ) {
				return $cart_info;
			}

			$cart_info = json_decode( stripslashes( $cart_info ), true );

			if ( ! empty( $cart_info ) && isset( $cart_info['cart'] ) && ! empty( $cart_info['cart'] ) ) {

				$current_currency = $WOOCS->current_currency;

				if ( ! empty( $current_currency ) ) {
					$cart_info['currency'] = $current_currency;
				}
			}

			$cart_info = wp_json_encode( $cart_info );

			return $cart_info;
		}


		/**
		 * Unsubscribe the cart.
		 *
		 * @param string $wcap_cart_id Cart ID.
		 * @since 8.8.0
		 */
		public static function wcap_unsubscribe_cart( $wcap_cart_id ) {
			return parent::update( array( 'unsubscribe_link' => 1 ), array( 'id' => $wcap_cart_id ) );
		}

	}
}
