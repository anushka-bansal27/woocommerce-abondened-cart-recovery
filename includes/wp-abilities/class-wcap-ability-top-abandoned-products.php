<?php
/**
 * Ability: abandoned-cart-pro/top-abandoned-products.
 *
 * Returns the products most frequently left in abandoned carts within an
 * optional date range.
 *
 * @package WooCommerce Abandoned Cart Pro
 * @since   10.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once dirname( __DIR__ ) . '/api/class-wcap-product-aggregate.php';

/**
 * WCAP_Ability_Top_Abandoned_Products class.
 */
class WCAP_Ability_Top_Abandoned_Products {

	/**
	 * Ability id.
	 *
	 * @var string
	 */
	const ID = 'abandoned-cart-pro/top-abandoned-products';

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
				'label'             => __( 'Top Abandoned Products', 'woocommerce-ac' ),
				'description'       => __( 'Returns the products most frequently left in abandoned carts within an optional date range (ISO 8601, e.g. 2026-06-01), ranked by how often they were abandoned.', 'woocommerce-ac' ),
				'category'          => WCAP_Abilities_Bootstrap::CATEGORY,
				'input_schema'      => array(
					// null allowed: mcp-adapter turns an empty {} into null
					// before validating, so a strict 'object' type rejects
					// the no-filters case.
					'type'       => array( 'object', 'null' ),
					'properties' => array(
						'start_date' => array(
							'type'        => 'string',
							'description' => __( 'Inclusive lower bound (ISO 8601 date, e.g. 2026-06-01). Optional, defaults to 366 days ago.', 'woocommerce-ac' ),
						),
						'end_date'   => array(
							'type'        => 'string',
							'description' => __( 'Inclusive upper bound (ISO 8601 date, e.g. 2026-06-30). Optional, defaults to today.', 'woocommerce-ac' ),
						),
						'limit'      => array(
							'type'        => 'integer',
							'description' => __( 'Number of products to return.', 'woocommerce-ac' ),
							'default'     => 5,
							'minimum'     => 1,
							'maximum'     => 50,
						),
					),
				),
				'output_schema'     => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'product_id'        => array( 'type' => 'integer' ),
							'variation_id'       => array( 'type' => 'integer' ),
							'name'               => array( 'type' => 'string' ),
							'times_abandoned'    => array( 'type' => 'integer' ),
							'times_recovered'    => array( 'type' => 'integer' ),
							'recovery_rate_pct'  => array( 'type' => 'number' ),
						),
					),
				),
				'execute_callback'  => array( __CLASS__, 'execute' ),
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
				'meta'              => array(
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

		$limit = isset( $input['limit'] ) ? max( 1, min( 50, (int) $input['limit'] ) ) : 5;

		// Defaults to the last 366 days rather than truly unbounded, so an
		// omitted date range can't force a full-table scan on a large store.
		$default_start = time() - WCAP_Ability_Utils::MAX_WINDOW_SECONDS;
		$range         = WCAP_Ability_Utils::resolve_date_range( $input, false, array( $default_start, time() ) );
		if ( is_wp_error( $range ) ) {
			return $range;
		}
		list( $start, $end ) = $range;

		$window_check = WCAP_Ability_Utils::check_max_window( $start, $end );
		if ( is_wp_error( $window_check ) ) {
			return $window_check;
		}

		$report = WCAP_Product_Aggregate::build_report( $start, $end );

		usort(
			$report,
			function ( $a, $b ) {
				if ( $a['times_abandoned'] === $b['times_abandoned'] ) {
					return $a['product_id'] <=> $b['product_id'];
				}
				return $b['times_abandoned'] <=> $a['times_abandoned'];
			}
		);

		$result = array_slice( $report, 0, $limit );

		WCAP_Ability_Utils::log_execution( self::ID, $input );

		return $result;
	}
}
