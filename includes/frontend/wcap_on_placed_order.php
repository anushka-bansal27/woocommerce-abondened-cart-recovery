<?php
/**
 * It will delete the abandoned cart if order is placed before the cutoff time.
 * It will also, create the post meta for the abandoned cart. It will create after the cutoff time.
 * @author   LittleCodr
 * @package Abandoned-Cart-Pro-for-WooCommerce/Frontend/Place-Order
 * @since 5.0
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if ( !class_exists('WCAP_On_Placed_Order' ) ) {
    /**
     * It will delete the abandoned cart if order is placed before the cutoff time.
     * It will also, create the post meta for the abandoned cart. It will create after the cutoff time.
     * Also, when order status is changes it will check if abandoned cart needs to delete or keep it.
     */
	class WCAP_On_Placed_Order {
		/**
		 * Calls the actions to be performed on order placement in the Checkout Blocks page.
		 *
		 * @param obj $order - WC Order Object.
		 * @since 9.6.0
		 */
		public static function wcap_order_placed_blocks( $order ) {
			if ( ! $order ) {
				return false;
			}
			$order_id = $order->get_id();

			if ( $order_id > 0 ) {
				self::wcap_order_placed( $order_id );
			}
		}
		/**
		 * It will delete the abandoned cart if order is placed before the cutoff time.
		 * It will also, create the post meta for the abandoned cart. It will create after the cutoff time.
		 * This post meta contain the abandoned cart id and if the email is sent to that cart then the email sent id.
		 * @hook woocommerce_checkout_order_processed
		 * @param int | string $order_id Order id
		 * @globals mixed $wpdb
		 * @since 5.0
		 */
		public static function wcap_order_placed( $order_id ) {

			global $wpdb;
			$email_sent_id         = wcap_get_cart_session( 'wcap_email_sent_id' );
			$abandoned_order_id    = wcap_get_cart_session( 'wcap_abandoned_id' );
			$wcap_user_id_of_guest = wcap_get_cart_session( 'wcap_user_id' );

			// if user becomes the registered user
			$guest_turned_registered = false;//phpcs:disable
			if ( isset( $_POST['account_password'] ) && $_POST['account_password'] != '' ) { // guest logged in on the Checkout page
				$guest_turned_registered = true;//phpcs:ignore
			} else if( isset( $_POST['createaccount'] ) && $_POST['createaccount'] != '' ) { // guest created an account at Checkout
				$guest_turned_registered = true;
			} else if( !isset( $_POST[ 'createaccount' ] ) && 'no' == get_option( 'woocommerce_enable_guest_checkout', 'no' ) ) { // Guest Checkouts are not allowed, user registration is forced (Subscriptions)
				$guest_turned_registered = true;//phpcs:enable
			}
			$order = wc_get_order( $order_id );
			if( $email_sent_id != '' && $email_sent_id > 0 ) { // recovered cart

				if( $abandoned_order_id == '' || $abandoned_order_id == false ) {

					$get_ac_id_query    = "SELECT cart_id FROM `" . WCAP_EMAIL_SENT_HISTORY_TABLE ."` WHERE id = %d";
					$get_ac_id_results  = $wpdb->get_results( $wpdb->prepare( $get_ac_id_query, $email_sent_id ) );//phpcs:ignore

					$abandoned_order_id = $get_ac_id_results[0]->cart_id;
				}

				$order->add_meta_data( 'wcap_recover_order_placed_sent_id', $email_sent_id );
				$order->add_meta_data( 'wcap_recover_order_placed', $abandoned_order_id );

			} else {
				$recovered = wcap_get_cart_session( 'wcap_recovered_cart' );
				if ( $recovered ) {
					$order->add_meta_data( 'wcap_recover_order_placed', $abandoned_order_id );
				}
			}

			if ( $abandoned_order_id != '' && $guest_turned_registered && $wcap_user_id_of_guest != '' ) {

				if( version_compare( WC()->version, '3.7.0', '>=' ) ) {
					$update_id = $wpdb->update(// phpcs:ignore
						WCAP_ABANDONED_CART_HISTORY_TABLE,
						array(
							'user_id'   => get_current_user_id(),
							'user_type' => 'REGISTERED',
						),
						array( 'user_id' => $wcap_user_id_of_guest )
					);
				} else {
					$update_id = $wpdb->update(// phpcs:ignore
						WCAP_ABANDONED_CART_HISTORY_TABLE,
						array(
							'user_id'   => get_current_user_id(),
							'user_type' => 'REGISTERED',
						),
						array( 'id' => $abandoned_order_id )
					);
				}

				WCAP_GUEST_CART_MODEL::delete( array( 'id' => $wcap_user_id_of_guest ) );

			}

			$order->add_meta_data( 'wcap_abandoned_cart_id', $abandoned_order_id );
			$order_status = $order ? $order->get_status() : '';

			if ( 'pending' === $order_status ) {
				$update_data = array( 'cart_ignored' => '4' );
				WCAP_CART_HISTORY_MODEL::update( $update_data, array( 'id' => $abandoned_order_id ) );
			}
			$order->save();
		}

		/**
		 * Deletes Abandoned Cart records once order payment is completed
		 * 
		 * @param integer $order_id - WC Order ID
		 * @param string $wc_old_status - Old WC Order Status
		 * @param string $wc_new_status - New WC Order Status
		 * 
		 * @since 7.11.0
		 * @hook woocommerce_order_status_changed
		 */
		public static function wcap_cart_details_update( $order_id, $wc_old_status, $wc_new_status ) {

			if ( 'pending' !== $wc_new_status && 'failed' !== $wc_new_status && 'cancelled' !== $wc_new_status && 'trash' !== $wc_new_status && 'checkout-draft' !== $wc_new_status ) {

				global $wpdb;

				if ( $order_id > 0 ) {
					$order                     = wc_get_order( $order_id );
					$get_abandoned_id_of_order = $order->get_meta( 'wcap_recover_order_placed' );
					$abandoned_id              = $get_abandoned_id_of_order;

					if ( $get_abandoned_id_of_order > 0 || wcap_get_cart_session( 'wcap_email_sent_id' ) != '' ) {
						// recovered order
						$get_sent_email_id_of_order = $order->get_meta( 'wcap_recover_order_placed_sent_id' );
						$recovered                  = wcap_get_cart_session( 'wcap_recovered_cart' );
						// Order Status passed in the function is either 'processing' or 'complete' and may or may not reflect the actual order status.
						// Hence, always use the status fetched from the order object.

						$order_status = ( $order ) ? $order->get_status() : '';

						if ( 'pending' !== $order_status &&
							'failed' !== $order_status &&
							'cancelled' !== $order_status &&
							'trash' !== $order_status &&
							'checkout-draft' !== $order_status ) {

							// Mark as recovered 
							if ( ( isset( $get_sent_email_id_of_order ) && '' != $get_sent_email_id_of_order ) || $recovered ) {
								wcap_common::wcap_updated_recovered_cart( $get_abandoned_id_of_order, $order_id, $get_sent_email_id_of_order, $order );
							}
						}
					} else {

						$wcap_abandoned_id = $order->get_meta( 'wcap_abandoned_cart_id' );
						$abandoned_id      = $wcap_abandoned_id;

						// check if it's a guest cart
						$query_cart_data = "SELECT user_id, user_type FROM `" . WCAP_ABANDONED_CART_HISTORY_TABLE . "`
											WHERE id = %d";
						$get_cart_data = $wpdb->get_results( $wpdb->prepare( $query_cart_data, $wcap_abandoned_id ) );//phpcs:ignore

						if( is_array( $get_cart_data ) && count( $get_cart_data ) > 0 ) {
							$user_type = $get_cart_data[0]->user_type;
							$user_id = $get_cart_data[0]->user_id;

							if( 'GUEST' === $user_type && $user_id >= 63000000 ) {
								WCAP_GUEST_CART_MODEL::delete( array( 'id' => $user_id ) );
								WCAP_CART_HISTORY_MODEL::delete( array( 'user_id'=> $user_id ) );
							}
						}

						WCAP_CART_HISTORY_MODEL::delete( array( 'id' => $wcap_abandoned_id ) );

						// remove the cart ID from the list to which SMS reminders will be sent
						Wcap_Common::wcap_delete_cart_notification( $wcap_abandoned_id );
						do_action( 'wcap_cart_cycle_completed', $wcap_abandoned_id, $order_id );

						// Remove the scheduled action for admin notifications.
						wp_unschedule_event( 'wcap_send_admin_notification', array( 'id' => (int) $wcap_abandoned_id ) );

					}

					$template_list = array(
						'atc',
						'exit_intent',
					);
					// Add the ATC coupon details.
					if ( $order_id > 0 && $abandoned_id > 0 ) {
						foreach ( $template_list as $template_type ) {
							self::wcap_add_coupon_details( $abandoned_id, $order_id, $template_type );
						}

					}
				}
			}

		}

		/**
		 * Add coupon & popup template details in WC Order.
		 *
		 * @param int    $abandoned_id - Abandoned Order ID.
		 * @param int    $order_id - WC Order ID.
		 * @param string $template_type - Popup Template Type.
		 */
		public static function wcap_add_coupon_details( $abandoned_id, $order_id, $template_type ) {
			$order                 = wc_get_order( $order_id ); // WC Order.
			if ( $order ) {
				$template_type_display = 'atc' === $template_type ? strtoupper( $template_type ) : ucwords( str_replace( '_', ' ', $template_type ) );
				// Template ID.
				$template_id = wcap_get_cart_session( 'wcap_' . $template_type . '_template_id' );
				if ( null !== $template_id ) {
					$order->update_meta_data( 'wcap_' . $template_type . '_template_id', $template_id );
					// Get the template name.
					$template_data = wcap_get_atc_template( $template_id );
					$template_name = isset( $template_data->name ) ? $template_data->name : '';
					$order->add_order_note( __( $template_type_display . ' Popup "' . $template_name . '" was displayed.', 'woocommerce-ac' ) );//phpcs:ignore
				}
				$coupons_meta = get_post_meta( $abandoned_id, '_woocommerce_ac_coupon', true );

				if ( is_array( $coupons_meta ) && count( $coupons_meta ) > 0 ) {
					foreach ( $coupons_meta as $key => $coupon_details ) {

						$details_added = isset( $coupon_details[ 'order_' . $template_type . '_details_added' ] ) && $coupon_details[ 'order_' . $template_type . '_details_added' ] ? true : false;

						if ( is_array( $coupon_details ) && isset( $coupon_details[ $template_type . '_coupon_code' ] ) && '' !== $coupon_details[ $template_type . '_coupon_code' ] && ! $details_added ) {
							$coupon_code = $coupon_details[ $template_type . '_coupon_code' ]; // Get the coupon name.

							// Add post meta record & Note.
							$order->update_meta_data( 'wcap_' . $template_type . '_coupon', $coupon_code );
							$order->add_order_note( __( "$template_type_display Coupon $coupon_code was used.", 'woocommerce-ac' ) );//phpcs:ignore
							$coupons_meta[$key][ 'order_' . $template_type . '_details_added' ] = true;
							update_post_meta( $abandoned_id, '_woocommerce_ac_coupon', $coupons_meta );

						}
					}
				}
				$order->save();
			}
		}
		/**
		 * When an order status is changed we check the order status, if the status is pending or falied then we consider that cart as an abandoned.
		 * Apart from the pending and failed we delete the abandoned cart.
		 * @hook woocommerce_payment_complete_order_status
		 * @param string $woo_order_status New order status
		 * @param int | string $order_id Order id
		 * @return string $woo_order_status
		 * @globals mixed $wpdb
		 * @since 5.0
		 * 
		 */
		public static function wcap_order_complete_action( $woo_order_status, $order_id ) {

			$order = wc_get_order( $order_id );

			$get_abandoned_id_of_order  = $order->get_meta( 'wcap_recover_order_placed' );
			$get_sent_email_id_of_order = $order->get_meta( 'wcap_recover_order_placed_sent_id' );

			// Order Status passed in the function is either 'processing' or 'complete' and may or may not reflect the actual order status.
			// Hence, always use the status fetched from the order object.

			$order_status = ( $order ) ? $order->get_status() : '';

			if ( 'pending' !== $order_status &&
				'failed' !== $order_status &&
				'cancelled' !== $order_status &&
				'trash' !== $order_status ) {

				// Mark as recovered 
				if ( isset( $get_sent_email_id_of_order ) && '' != $get_sent_email_id_of_order ) {
					wcap_common::wcap_updated_recovered_cart( $get_abandoned_id_of_order, $order_id, $get_sent_email_id_of_order, $order );
				}
			}

			return $woo_order_status;
		}
		
		/**
		 * Updates cart status to 'Abandoned - Order Unpaid'
		 * when the order is cancelled by WooCommerce once
		 * Hold Stock Limit is reached.
		 *
		 * @param string $created_via - From where the order has been created.
		 * @param WC_order $order - Order Object
		 * @return string $created_via
		 * @global mixed $wpdb
		 * 
		 * @since 7.7
		 * @hook woocommerce_cancel_unpaid_order
		 */
		static function wcap_update_cart_status( $created_via, $order ) {
			global $wpdb;
		
			$order_id = ( $order ) ? $order->get_id() : 0;
		
			if( isset( $order_id ) && $order_id > 0 ) {
				$abandoned_id  = $order->get_meta( 'wcap_abandoned_cart_id' );
		
				if( isset( $abandoned_id ) && $abandoned_id > 0 ) {
					$update_data = array( 'cart_ignored' => '2' );
		
					WCAP_CART_HISTORY_MODEL::update( $update_data, array( 'id' => $abandoned_id ) );
				}
			}
			return $created_via;
		}
	}
}
