<?php
/**
 * Cart response schema and row formatter.
 *
 * Defines the cart response schema once (used for OPTIONS discovery) and
 * provides the formatter that turns a raw DB row into the documented API
 * shape. The shape mirrors the `wcap_cart.recovered` webhook payload so
 * push and pull integrations stay consistent. (BRD 2.3 / 2.4.1.)
 *
 * @package WooCommerce Abandoned Cart Pro
 * @since   10.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCAP_Cart_Schema class.
 */
class WCAP_Cart_Schema {

	/**
	 * The threshold at/above which a user_id denotes a GUEST record.
	 *
	 * @var int
	 */
	const GUEST_ID_THRESHOLD = 63000000;

	/**
	 * Return the JSON schema for a single cart object.
	 *
	 * Used by the controllers' get_item_schema() so /wp-json/wcap/v1
	 * discovery (the OPTIONS request) advertises the correct shape.
	 *
	 * @return array
	 */
	public static function get_cart_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'wcap_cart',
			'type'       => 'object',
			'properties' => array(
				'id'                      => array(
					'description' => __( 'Unique cart record identifier.', 'woocommerce-ac' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'status'                  => array(
					'description' => __( 'Derived cart status.', 'woocommerce-ac' ),
					'type'        => 'string',
					'enum'        => array( 'abandoned', 'recovered', 'unpaid', 'received', 'cancelled' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'user_id'                 => array(
					'description' => __( 'WordPress user id, or guest id (>= 63000000), or 0.', 'woocommerce-ac' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'user_type'               => array(
					'description' => __( 'REGISTERED or GUEST.', 'woocommerce-ac' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'email_id'                => array(
					'description' => __( 'Customer email address.', 'woocommerce-ac' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'phone'                   => array(
					'description' => __( 'Customer phone number.', 'woocommerce-ac' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'abandoned_cart_time'     => array(
					'description' => __( 'Abandoned timestamp (Unix).', 'woocommerce-ac' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'abandoned_cart_time_iso' => array(
					'description' => __( 'Abandoned timestamp (ISO 8601, UTC).', 'woocommerce-ac' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'session_id'              => array(
					'description' => __( 'Session identifier.', 'woocommerce-ac' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'language'                => array(
					'description' => __( 'Locale of the cart.', 'woocommerce-ac' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'billing'                 => array(
					'description' => __( 'Billing details.', 'woocommerce-ac' ),
					'type'        => 'object',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'shipping'                => array(
					'description' => __( 'Shipping details.', 'woocommerce-ac' ),
					'type'        => 'object',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'products'                => array(
					'description' => __( 'Line items in the cart.', 'woocommerce-ac' ),
					'type'        => 'array',
					'context'     => array( 'view' ),
					'readonly'    => true,
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'product_id'    => array( 'type' => 'integer' ),
							'variation_id'  => array( 'type' => 'integer' ),
							'name'          => array( 'type' => 'string' ),
							'quantity'      => array( 'type' => 'integer' ),
							'line_subtotal' => array( 'type' => 'number' ),
							'line_tax'      => array( 'type' => 'number' ),
						),
					),
				),
				'totals'                  => array(
					'description' => __( 'Cart totals.', 'woocommerce-ac' ),
					'type'        => 'object',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'reminder_activity'       => array(
					'description' => __( 'Reminder send/open/click activity.', 'woocommerce-ac' ),
					'type'        => 'object',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'recovered_order_id'      => array(
					'description' => __( 'WooCommerce order id if recovered, else 0.', 'woocommerce-ac' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);
	}

	/**
	 * Format a raw cart-history DB row into the documented API object.
	 *
	 * Reuses the webhook payload builders (wcap_get_product_details and the
	 * customer block from wcap_reminders_webhook_data) so the response stays
	 * field-consistent with what webhook subscribers receive. Malformed or
	 * partially-encoded cart payloads do not crash the request: products
	 * falls back to an empty array and a warning is logged. (BRD 2.4.3.)
	 *
	 * @param object $row             Row from ac_abandoned_cart_history.
	 * @param bool   $with_opens_clicks Include opens/clicks counts (single-record only).
	 * @return array
	 */
	public static function format_cart_row( $row, $with_opens_clicks = false ) {

		$cart_id        = isset( $row->id ) ? absint( $row->id ) : 0;
		$user_id        = isset( $row->user_id ) ? (int) $row->user_id : 0;
		$user_type      = isset( $row->user_type ) ? (string) $row->user_type : '';
		$recovered_cart = isset( $row->recovered_cart ) ? (int) $row->recovered_cart : 0;
		$cart_ignored   = isset( $row->cart_ignored ) ? (string) $row->cart_ignored : '0';
		$abandoned_time = isset( $row->abandoned_cart_time ) ? (int) $row->abandoned_cart_time : 0;

		// Resolve the customer block (billing/email/phone). Mirrors the
		// branching used by wcap_reminders_webhook_data().
		$customer = self::get_customer_block( $user_id, $user_type );

		// Products — reuse the canonical webhook builder for parity.
		$products = self::get_products( isset( $row->abandoned_cart_info ) ? $row->abandoned_cart_info : '', $cart_id );

		// Totals derived from the (already parsed) line items + shipping.
		$totals = self::get_totals( $products, $customer['shipping_charges'] );

		$formatted = array(
			'id'                      => $cart_id,
			'status'                  => WCAP_REST_Status_Helper::derive_status( $cart_ignored, $recovered_cart ),
			'user_id'                 => $user_id,
			'user_type'               => '' !== $user_type ? $user_type : ( $user_id >= self::GUEST_ID_THRESHOLD ? 'GUEST' : ( $user_id > 0 ? 'REGISTERED' : 'GUEST' ) ),
			'email_id'                => $customer['email_id'],
			'phone'                   => $customer['phone'],
			'abandoned_cart_time'     => $abandoned_time,
			'abandoned_cart_time_iso' => $abandoned_time > 0 ? gmdate( 'Y-m-d\TH:i:s\Z', $abandoned_time ) : '',
			'session_id'              => isset( $row->session_id ) ? (string) $row->session_id : '',
			'language'                => isset( $row->language ) ? (string) $row->language : '',
			'billing'                 => $customer['billing'],
			'shipping'                => $customer['shipping'],
			'products'                => $products,
			'totals'                  => $totals,
			'reminder_activity'       => self::get_reminder_activity( $cart_id, $with_opens_clicks ),
			'recovered_order_id'      => $recovered_cart > 0 ? $recovered_cart : 0,
		);

		/**
		 * Filter the formatted REST cart object.
		 *
		 * @param array  $formatted The shaped cart array.
		 * @param object $row       The raw DB row.
		 * @since 10.5.0
		 */
		return apply_filters( 'wcap_rest_format_cart', $formatted, $row );
	}

	/**
	 * Resolve the customer billing/shipping/email/phone block.
	 *
	 * @param int    $user_id   Cart user id.
	 * @param string $user_type REGISTERED or GUEST.
	 * @return array
	 */
	private static function get_customer_block( $user_id, $user_type ) {

		$block = array(
			'email_id'         => '',
			'phone'            => '',
			'shipping_charges' => 0,
			'billing'          => array(
				'first_name' => '',
				'last_name'  => '',
				'country'    => '',
				'zipcode'    => '',
			),
			'shipping'         => array(
				'first_name' => '',
				'last_name'  => '',
				'address_1'  => '',
				'city'       => '',
				'country'    => '',
				'zipcode'    => '',
			),
		);

		if ( $user_id >= self::GUEST_ID_THRESHOLD && 'REGISTERED' !== $user_type ) {
			// Guest record — pull from the guest table.
			$guest = function_exists( 'wcap_get_data_guest_history' ) ? wcap_get_data_guest_history( $user_id ) : false;
			if ( $guest ) {
				$block['email_id'] = isset( $guest->email_id ) ? (string) $guest->email_id : '';
				$block['phone']    = isset( $guest->phone ) ? (string) $guest->phone : '';

				$block['shipping_charges'] = isset( $guest->shipping_charges ) ? (float) $guest->shipping_charges : 0;

				$block['billing'] = array(
					'first_name' => isset( $guest->billing_first_name ) ? (string) $guest->billing_first_name : '',
					'last_name'  => isset( $guest->billing_last_name ) ? (string) $guest->billing_last_name : '',
					'country'    => isset( $guest->billing_country ) ? (string) $guest->billing_country : '',
					'zipcode'    => isset( $guest->billing_zipcode ) ? (string) $guest->billing_zipcode : '',
				);

				$block['shipping'] = array(
					'first_name' => isset( $guest->shipping_first_name ) ? (string) $guest->shipping_first_name : '',
					'last_name'  => isset( $guest->shipping_last_name ) ? (string) $guest->shipping_last_name : '',
					'address_1'  => isset( $guest->shipping_address_1 ) ? (string) $guest->shipping_address_1 : '',
					'city'       => isset( $guest->shipping_city ) ? (string) $guest->shipping_city : '',
					'country'    => isset( $guest->shipping_county ) ? (string) $guest->shipping_county : '',
					'zipcode'    => isset( $guest->shipping_zipcode ) ? (string) $guest->shipping_zipcode : '',
				);
			}
		} elseif ( $user_id > 0 ) {
			// Registered user — pull from usermeta.
			$block['email_id'] = (string) get_user_meta( $user_id, 'billing_email', true );
			$block['phone']    = (string) get_user_meta( $user_id, 'billing_phone', true );

			$block['billing'] = array(
				'first_name' => (string) get_user_meta( $user_id, 'billing_first_name', true ),
				'last_name'  => (string) get_user_meta( $user_id, 'billing_last_name', true ),
				'country'    => (string) get_user_meta( $user_id, 'billing_country', true ),
				'zipcode'    => (string) get_user_meta( $user_id, 'billing_postcode', true ),
			);

			$block['shipping'] = array(
				'first_name' => (string) get_user_meta( $user_id, 'shipping_first_name', true ),
				'last_name'  => (string) get_user_meta( $user_id, 'shipping_last_name', true ),
				'address_1'  => (string) get_user_meta( $user_id, 'shipping_address_1', true ),
				'city'       => (string) get_user_meta( $user_id, 'shipping_city', true ),
				'country'    => (string) get_user_meta( $user_id, 'shipping_country', true ),
				'zipcode'    => (string) get_user_meta( $user_id, 'shipping_postcode', true ),
			);
		}

		return $block;
	}

	/**
	 * Build the products array, reusing the webhook product builder.
	 *
	 * @param string $cart_info Raw abandoned_cart_info JSON string.
	 * @param int    $cart_id   Cart id, for logging context.
	 * @return array
	 */
	private static function get_products( $cart_info, $cart_id ) {

		if ( '' === trim( (string) $cart_info ) ) {
			return array();
		}

		// Guard against malformed / partially-encoded JSON. wcap_get_product_details
		// itself json_decodes; we pre-check so we can log and degrade gracefully
		// instead of emitting PHP warnings on a broken record.
		$probe = json_decode( stripslashes( (string) $cart_info ) );
		if ( null === $probe && JSON_ERROR_NONE !== json_last_error() ) {
			self::log_warning( sprintf( 'Malformed abandoned_cart_info for cart #%d: %s', $cart_id, json_last_error_msg() ) );
			return array();
		}

		$raw = array();
		if ( function_exists( 'wcap_get_product_details' ) ) {
			$raw = wcap_get_product_details( $cart_info );
		}

		if ( ! is_array( $raw ) ) {
			return array();
		}

		$products = array();
		foreach ( $raw as $item ) {
			// wcap_get_product_details returns objects keyed by product_name;
			// map to the documented API key `name` while keeping the values
			// identical to the webhook builder for parity.
			$products[] = array(
				'product_id'    => isset( $item->product_id ) ? absint( $item->product_id ) : 0,
				'variation_id'  => isset( $item->variation_id ) ? absint( $item->variation_id ) : 0,
				'name'          => isset( $item->product_name ) ? wp_strip_all_tags( (string) $item->product_name ) : '',
				'quantity'      => isset( $item->quantity ) ? (int) $item->quantity : 0,
				'line_subtotal' => isset( $item->line_subtotal ) ? (float) $item->line_subtotal : 0,
				'line_tax'      => isset( $item->line_tax ) ? (float) $item->line_tax : 0,
			);
		}

		return $products;
	}

	/**
	 * Compute cart totals from the line items.
	 *
	 * @param array $products         Formatted products array.
	 * @param float $shipping_charges Shipping charges (guest records only).
	 * @return array
	 */
	private static function get_totals( $products, $shipping_charges = 0 ) {

		$subtotal = 0;
		$tax      = 0;

		foreach ( $products as $product ) {
			$subtotal += isset( $product['line_subtotal'] ) ? (float) $product['line_subtotal'] : 0;
			$tax      += isset( $product['line_tax'] ) ? (float) $product['line_tax'] : 0;
		}

		$shipping = (float) $shipping_charges;
		$total    = $subtotal + $tax + $shipping;

		return array(
			'subtotal' => self::round_amount( $subtotal ),
			'tax'      => self::round_amount( $tax ),
			'total'    => self::round_amount( $total ),
		);
	}

	/**
	 * Build the reminder_activity block from the sent / opened / clicked tables.
	 *
	 * Counts of email / SMS / FB / WhatsApp sends plus last sent time come from
	 * ac_sent_history. opens and clicks (joined via notification_sent_id) are
	 * only included when $with_opens_clicks is true so list responses stay fast.
	 * (BRD 2.4.2.)
	 *
	 * @param int  $cart_id           Cart id.
	 * @param bool $with_opens_clicks Whether to include opens/clicks counts.
	 * @return array
	 */
	private static function get_reminder_activity( $cart_id, $with_opens_clicks = false ) {

		global $wpdb;

		$activity = array(
			'emails_sent'   => 0,
			'sms_sent'      => 0,
			'fb_sent'       => 0,
			'whatsapp_sent' => 0,
			'last_sent_at'  => '',
		);

		if ( $cart_id <= 0 ) {
			return $with_opens_clicks ? array_merge(
				$activity,
				array(
					'opens'  => 0,
					'clicks' => 0,
				)
			) : $activity;
		}

		// Per-type send counts + last sent time. Prepared, single grouped query.
		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare(
				'SELECT notification_type, COUNT(*) AS total, MAX(sent_time) AS last_sent FROM ' . WCAP_EMAIL_SENT_HISTORY_TABLE . ' WHERE cart_id = %d GROUP BY notification_type', // phpcs:ignore
				$cart_id
			)
		);

		$last_sent_ts = 0;
		if ( is_array( $rows ) ) {
			foreach ( $rows as $r ) {
				switch ( $r->notification_type ) {
					case 'email':
						$activity['emails_sent'] = (int) $r->total;
						break;
					case 'sms':
						$activity['sms_sent'] = (int) $r->total;
						break;
					case 'fb':
						$activity['fb_sent'] = (int) $r->total;
						break;
					case 'whatsapp':
						$activity['whatsapp_sent'] = (int) $r->total;
						break;
				}
				if ( ! empty( $r->last_sent ) ) {
					$ts = strtotime( $r->last_sent . ' UTC' );
					if ( $ts && $ts > $last_sent_ts ) {
						$last_sent_ts = $ts;
					}
				}
			}
		}

		$activity['last_sent_at'] = $last_sent_ts > 0 ? gmdate( 'Y-m-d\TH:i:s\Z', $last_sent_ts ) : '';

		if ( $with_opens_clicks ) {
			$activity['opens']  = self::count_opens( $cart_id );
			$activity['clicks'] = self::count_clicks( $cart_id );
		}

		return $activity;
	}

	/**
	 * Count opened notifications for a cart (joined via notification_sent_id).
	 *
	 * @param int $cart_id Cart id.
	 * @return int
	 */
	private static function count_opens( $cart_id ) {
		global $wpdb;

		$count = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . WCAP_NOTIFICATION_OPENED_TABLE . ' AS o INNER JOIN ' . WCAP_EMAIL_SENT_HISTORY_TABLE . ' AS s ON o.notification_sent_id = s.id WHERE s.cart_id = %d', // phpcs:ignore
				$cart_id
			)
		);

		return (int) $count;
	}

	/**
	 * Count clicked notifications for a cart (joined via notification_sent_id).
	 *
	 * @param int $cart_id Cart id.
	 * @return int
	 */
	private static function count_clicks( $cart_id ) {
		global $wpdb;

		$count = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . WCAP_LINK_CLICKED_TABLE . ' AS c INNER JOIN ' . WCAP_EMAIL_SENT_HISTORY_TABLE . ' AS s ON c.notification_sent_id = s.id WHERE s.cart_id = %d', // phpcs:ignore
				$cart_id
			)
		);

		return (int) $count;
	}

	/**
	 * Round a monetary amount to the store's decimal precision.
	 *
	 * @param float $amount Amount.
	 * @return float
	 */
	private static function round_amount( $amount ) {
		$decimals = function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 2;
		return round( (float) $amount, (int) $decimals );
	}

	/**
	 * Log a warning without crashing the request.
	 *
	 * @param string $message Message to log.
	 * @return void
	 */
	private static function log_warning( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->warning( $message, array( 'source' => 'abandoned-cart-pro' ) );
		}
		/**
		 * Fires when the formatter encounters a record it cannot fully parse.
		 *
		 * @param string $message Warning message.
		 * @since 10.5.0
		 */
		do_action( 'wcap_rest_format_warning', $message );
	}
}
