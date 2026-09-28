<?php
/**
 * Ability: abandoned-cart-pro/template-performance.
 *
 * Compares recovery templates by the recovered revenue attributed to each,
 * for a given period (defaults to the current calendar month). Uses
 * last-touch attribution: the last reminder sent to a cart before it
 * recovered gets full credit.
 *
 * @package WooCommerce Abandoned Cart Pro
 * @since   10.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCAP_Ability_Template_Performance class.
 */
class WCAP_Ability_Template_Performance {

	/**
	 * Ability id.
	 *
	 * @var string
	 */
	const ID = 'abandoned-cart-pro/template-performance';

	/**
	 * Rows read per chunk while scanning the history table.
	 *
	 * @var int
	 */
	const SCAN_CHUNK = 2000;

	/**
	 * IDs per IN(...) clause / wc_get_orders() bulk-fetch call.
	 *
	 * @var int
	 */
	const ID_CHUNK = 500;

	/**
	 * Register this ability.
	 *
	 * @return void
	 * @since 10.6.0
	 */
	public static function register() {

		wp_register_ability(
			self::ID,
			array(
				'label'               => __( 'Template Performance', 'woocommerce-ac' ),
				'description'         => __( 'Compares recovery email/SMS templates by recovered revenue for a period (ISO 8601 dates, e.g. 2026-06-01). If start_date/end_date are omitted, defaults to the current calendar month. Last-touch attribution: the last reminder sent to a cart before it recovered gets full credit.', 'woocommerce-ac' ),
				'category'            => WCAP_Abilities_Bootstrap::CATEGORY,
				'input_schema'        => array(
					// null allowed: mcp-adapter turns an empty {} into null
					// before validating, so a strict 'object' type rejects
					// the no-filters case.
					'type'       => array( 'object', 'null' ),
					'properties' => array(
						'start_date' => array(
							'type'        => 'string',
							'description' => __( 'Inclusive lower bound (ISO 8601 date). Optional, defaults to the 1st of the current month.', 'woocommerce-ac' ),
						),
						'end_date'   => array(
							'type'        => 'string',
							'description' => __( 'Inclusive upper bound (ISO 8601 date). Optional, defaults to today.', 'woocommerce-ac' ),
						),
					),
				),
				'output_schema'       => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'template_id'            => array( 'type' => 'integer' ),
							'template_name'          => array( 'type' => 'string' ),
							'notification_type'      => array( 'type' => 'string' ),
							'recovered_revenue'      => array( 'type' => 'number' ),
							'recovered_order_count'  => array( 'type' => 'integer' ),
						),
					),
				),
				'execute_callback'    => array( __CLASS__, 'execute' ),
				'permission_callback' => function () {
					if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_woocommerce' ) ) {
						return new WP_Error(
							'wcap_ability_auth_failed',
							__( 'You do not have permission to use this ability.', 'woocommerce-ac' ),
							array( 'status' => 403 )
						);
					}
					return true;
				},
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
						'type'   => 'tool',
					),
				),
			)
		);
	}

	/**
	 * execute_callback.
	 *
	 * @param array $input Validated input.
	 * @return array|WP_Error
	 * @since 10.6.0
	 */
	public static function execute( $input ) {

		$rate_limit = WCAP_Ability_Utils::check_rate_limit( self::ID );
		if ( is_wp_error( $rate_limit ) ) {
			return $rate_limit;
		}

		$default_start = strtotime( gmdate( 'Y-m-01 00:00:00' ) . ' UTC' );
		$range         = WCAP_Ability_Utils::resolve_date_range( $input, false, array( $default_start, time() ) );
		if ( is_wp_error( $range ) ) {
			return $range;
		}
		list( $start, $end ) = $range;

		$window_check = WCAP_Ability_Utils::check_max_window( $start, $end );
		if ( is_wp_error( $window_check ) ) {
			return $window_check;
		}

		global $wpdb;
		$history_table  = WCAP_ABANDONED_CART_HISTORY_TABLE;
		$sent_table     = WCAP_EMAIL_SENT_HISTORY_TABLE;
		$template_table = WCAP_NOTIFICATION_TEMPLATES_TABLE;

		// Chunked scan, same shape as WCAP_Product_Aggregate::build_report().
		$carts  = array();
		$offset = 0;
		do {
			// Blank-cart exclusion matches recovered-revenue, so totals agree.
			$chunk = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
				$wpdb->prepare(
					"SELECT id, recovered_cart FROM `{$history_table}` WHERE abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s AND recovered_cart > 0 AND abandoned_cart_time >= %d AND abandoned_cart_time <= %d AND wcap_trash = '' ORDER BY id ASC LIMIT %d OFFSET %d", // phpcs:ignore
					'{"cart":[]}',
					'[]',
					$start,
					$end,
					self::SCAN_CHUNK,
					$offset
				)
			);

			if ( ! is_array( $chunk ) || 0 === count( $chunk ) ) {
				break;
			}

			$carts   = array_merge( $carts, $chunk );
			$offset += self::SCAN_CHUNK;
		} while ( count( $chunk ) === self::SCAN_CHUNK );

		if ( empty( $carts ) ) {
			WCAP_Ability_Utils::log_execution( self::ID, $input );
			return array();
		}

		$cart_ids = wp_list_pluck( $carts, 'id' );
		$cart_ids = array_map( 'absint', $cart_ids );

		// Last-touch: most recent ac_sent_history row per cart_id.
		$last_touch_template = array();
		foreach ( array_chunk( $cart_ids, self::ID_CHUNK ) as $id_chunk ) {
			$placeholders = implode( ', ', array_fill( 0, count( $id_chunk ), '%d' ) );
			$sent_rows    = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
				$wpdb->prepare(
					"SELECT cart_id, template_id, sent_time FROM `{$sent_table}` WHERE cart_id IN ( {$placeholders} ) ORDER BY sent_time ASC, id ASC", // phpcs:ignore
					$id_chunk
				)
			);

			// Last row per cart_id wins, since we scanned ascending by sent_time.
			if ( is_array( $sent_rows ) ) {
				foreach ( $sent_rows as $row ) {
					$last_touch_template[ (int) $row->cart_id ] = $row->template_id;
				}
			}
		}

		// Bulk-fetch instead of one wc_get_order() per cart below.
		$order_ids_needed = array();
		foreach ( $carts as $cart ) {
			if ( isset( $last_touch_template[ (int) $cart->id ] ) ) {
				$order_ids_needed[] = (int) $cart->recovered_cart;
			}
		}
		$order_ids_needed = array_unique( $order_ids_needed );

		$orders_by_id = array();
		foreach ( array_chunk( $order_ids_needed, self::ID_CHUNK ) as $id_chunk ) {
			foreach ( wc_get_orders( array( 'post__in' => $id_chunk, 'limit' => -1 ) ) as $order ) {
				$orders_by_id[ $order->get_id() ] = $order;
			}
		}

		$decimals = function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 2;

		// Group recovered revenue by template_id.
		$by_template = array();
		foreach ( $carts as $cart ) {
			$cart_id = (int) $cart->id;
			if ( ! isset( $last_touch_template[ $cart_id ] ) ) {
				continue; // No reminder on record for this cart; nothing to attribute.
			}
			$template_id = $last_touch_template[ $cart_id ];

			$order_id = (int) $cart->recovered_cart;
			if ( ! isset( $orders_by_id[ $order_id ] ) ) {
				continue;
			}
			$order = $orders_by_id[ $order_id ];

			if ( ! isset( $by_template[ $template_id ] ) ) {
				$by_template[ $template_id ] = array(
					'revenue' => 0.0,
					'count'   => 0,
				);
			}
			$by_template[ $template_id ]['revenue'] += (float) $order->get_total();
			++$by_template[ $template_id ]['count'];
		}

		if ( empty( $by_template ) ) {
			WCAP_Ability_Utils::log_execution( self::ID, $input );
			return array();
		}

		// Not wcap_get_email_templates() -- that drives a WP_List_Table admin
		// screen, unsuited to an execute_callback.
		$template_ids            = array_keys( $by_template );
		$template_id_placeholders = implode( ', ', array_fill( 0, count( $template_ids ), '%d' ) );
		$template_rows            = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare(
				"SELECT id, template_name, notification_type FROM `{$template_table}` WHERE id IN ( {$template_id_placeholders} )", // phpcs:ignore
				array_map( 'absint', $template_ids )
			)
		);

		$template_meta = array();
		if ( is_array( $template_rows ) ) {
			foreach ( $template_rows as $row ) {
				$template_meta[ (int) $row->id ] = array(
					'name' => $row->template_name,
					'type' => $row->notification_type,
				);
			}
		}

		$result = array();
		foreach ( $by_template as $template_id => $data ) {
			$tid  = (int) $template_id;
			$meta = isset( $template_meta[ $tid ] ) ? $template_meta[ $tid ] : null;

			$result[] = array(
				'template_id'           => $tid,
				// Deleted template: degrade to the raw id instead of dropping the row.
				'template_name'         => $meta ? $meta['name'] : (string) $tid,
				'notification_type'     => $meta ? $meta['type'] : '',
				'recovered_revenue'     => round( $data['revenue'], $decimals ),
				'recovered_order_count' => $data['count'],
			);
		}

		usort(
			$result,
			function ( $a, $b ) {
				return $b['recovered_revenue'] <=> $a['recovered_revenue'];
			}
		);

		WCAP_Ability_Utils::log_execution( self::ID, $input );

		return $result;
	}
}
