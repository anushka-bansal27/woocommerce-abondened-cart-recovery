<?php
/**
 * Ability: abandoned-cart-pro/recovered-revenue.
 *
 * Returns total revenue recovered via abandoned-cart reminders over an
 * explicit date range. Ports the query/loop from
 * Wcap_Dashboard_Report::get_current_month_recovered_amount()
 * (includes/classes/class_wcap_dashboard_report.php), parameterized by
 * explicit dates instead of the dashboard's month-name logic.
 *
 * @package WooCommerce Abandoned Cart Pro
 * @since   10.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCAP_Ability_Recovered_Revenue class.
 */
class WCAP_Ability_Recovered_Revenue {

	/**
	 * Ability id.
	 *
	 * @var string
	 */
	const ID = 'abandoned-cart-pro/recovered-revenue';

	/**
	 * Rows read per chunk while scanning the history table.
	 *
	 * @var int
	 */
	const SCAN_CHUNK = 2000;

	/**
	 * Order IDs per wc_get_orders() bulk-fetch call.
	 *
	 * @var int
	 */
	const ORDER_FETCH_CHUNK = 200;

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
				'label'               => __( 'Recovered Revenue', 'woocommerce-ac' ),
				'description'         => __( 'Returns total revenue recovered via abandoned-cart reminders over an explicit date range (ISO 8601, e.g. 2026-06-01). Both start_date and end_date are required.', 'woocommerce-ac' ),
				'category'            => WCAP_Abilities_Bootstrap::CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'start_date' => array(
							'type'        => 'string',
							'description' => __( 'Inclusive lower bound (ISO 8601 date, e.g. 2026-06-01). Required.', 'woocommerce-ac' ),
						),
						'end_date'   => array(
							'type'        => 'string',
							'description' => __( 'Inclusive upper bound (ISO 8601 date, e.g. 2026-06-30). Required.', 'woocommerce-ac' ),
						),
					),
					'required'   => array( 'start_date', 'end_date' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'recovered_revenue'     => array( 'type' => 'number' ),
						'recovered_order_count' => array( 'type' => 'integer' ),
						'period'                => array(
							'type'       => 'object',
							'properties' => array(
								'start' => array( 'type' => 'string' ),
								'end'   => array( 'type' => 'string' ),
							),
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

		$range = WCAP_Ability_Utils::resolve_date_range( $input, true );
		if ( is_wp_error( $range ) ) {
			return $range;
		}
		list( $start, $end ) = $range;

		$window_check = WCAP_Ability_Utils::check_max_window( $start, $end );
		if ( is_wp_error( $window_check ) ) {
			return $window_check;
		}

		$start_raw = $input['start_date'];
		$end_raw   = $input['end_date'];

		global $wpdb;
		$table = WCAP_ABANDONED_CART_HISTORY_TABLE;

		// Chunked scan, same shape as WCAP_Product_Aggregate::build_report().
		$order_ids = array();
		$offset    = 0;
		do {
			// Blank-cart exclusion matches Wcap_Dashboard_Report::get_current_month_recovered_amount()
			// so this agrees with the Dashboard's "Recovered Revenue".
			$chunk = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
				$wpdb->prepare(
					"SELECT recovered_cart FROM `{$table}` WHERE abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s AND recovered_cart > 0 AND abandoned_cart_time >= %d AND abandoned_cart_time <= %d AND wcap_trash = '' ORDER BY id ASC LIMIT %d OFFSET %d", // phpcs:ignore
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

			foreach ( $chunk as $row ) {
				$order_ids[] = (int) $row->recovered_cart;
			}

			$offset += self::SCAN_CHUNK;
		} while ( count( $chunk ) === self::SCAN_CHUNK );

		$decimals = function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 2;
		$revenue  = 0.0;
		$count    = 0;

		// Bulk-fetch instead of one wc_get_order() per row.
		foreach ( array_chunk( $order_ids, self::ORDER_FETCH_CHUNK ) as $id_chunk ) {
			foreach ( wc_get_orders( array( 'post__in' => $id_chunk, 'limit' => -1 ) ) as $order ) {
				$revenue += (float) $order->get_total();
				++$count;
			}
		}

		$result = array(
			'recovered_revenue'     => round( $revenue, $decimals ),
			'recovered_order_count' => $count,
			'period'                => array(
				'start' => $start_raw,
				'end'   => $end_raw,
			),
		);

		WCAP_Ability_Utils::log_execution( self::ID, $input );

		return $result;
	}
}
