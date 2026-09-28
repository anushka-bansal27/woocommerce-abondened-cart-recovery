<?php
/**
 * Controller for /product-report.
 *
 * Aggregates, per product, how many times it was abandoned and how many
 * times it was recovered, with a derived recovery rate. The source data is
 * the JSON line-item blob in {prefix}ac_abandoned_cart_history, so the
 * aggregation is performed in PHP over a chunked scan and cached briefly to
 * keep repeated polls cheap.
 *
 * @package WooCommerce Abandoned Cart Pro
 * @since   10.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCAP_REST_Product_Report_Controller class.
 */
class WCAP_REST_Product_Report_Controller extends WCAP_REST_Controller_Base {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'product-report';

	/**
	 * Register the route.
	 *
	 * @return void
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_permission' ),
					'args'                => $this->get_collection_params(),
				),
			)
		);
	}

	/**
	 * Collection params for this endpoint.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'start_date' => array(
				'description'       => __( 'Filter by cart abandoned timestamp lower bound.', 'woocommerce-ac' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => array( $this, 'validate_date_param' ),
			),
			'end_date'   => array(
				'description'       => __( 'Filter by cart abandoned timestamp upper bound.', 'woocommerce-ac' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => array( $this, 'validate_date_param' ),
			),
			'page'       => array(
				'description'       => __( 'Page number.', 'woocommerce-ac' ),
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => array( $this, 'validate_int_param' ),
			),
			'per_page'   => array(
				'description'       => __( 'Items per page (1-100).', 'woocommerce-ac' ),
				'type'              => 'integer',
				'default'           => 20,
				'sanitize_callback' => 'absint',
				'validate_callback' => array( $this, 'validate_int_param' ),
			),
			'order'      => array(
				'description'       => __( 'Sort direction (by times_abandoned).', 'woocommerce-ac' ),
				'type'              => 'string',
				'default'           => 'desc',
				'enum'              => array( 'asc', 'desc' ),
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => array( $this, 'validate_order_param' ),
			),
		);
	}

	/**
	 * GET /product-report.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {

		$unknown = $this->reject_unknown_params( $request, array_keys( $this->get_collection_params() ) );
		if ( is_wp_error( $unknown ) ) {
			return $unknown;
		}

		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = $this->clamp_per_page( $request->get_param( 'per_page' ) );
		$order    = $this->safe_order( $request->get_param( 'order' ) );

		$start = $request->get_param( 'start_date' );
		$end   = $request->get_param( 'end_date' );
		$start = ! empty( $start ) ? (int) self::parse_date( $start ) : 0;
		$end   = ! empty( $end ) ? (int) self::parse_date( $end ) : 0;

		$report = $this->build_report( $start, $end );

		// Sort by times_abandoned, then product_id for a stable order.
		usort(
			$report,
			function ( $a, $b ) use ( $order ) {
				if ( $a['times_abandoned'] === $b['times_abandoned'] ) {
					return $a['product_id'] <=> $b['product_id'];
				}
				$cmp = $a['times_abandoned'] <=> $b['times_abandoned'];
				return 'ASC' === $order ? $cmp : -$cmp;
			}
		);

		$total  = count( $report );
		$offset = ( $page - 1 ) * $per_page;
		$slice  = array_slice( $report, $offset, $per_page );

		return $this->envelope( $slice, $this->build_meta( $total, $page, $per_page ) );
	}

	/**
	 * Build (or fetch from cache) the per-product aggregate.
	 *
	 * Delegates to WCAP_Product_Aggregate::build_report(), shared with the
	 * AI Reporting Abilities (top-abandoned-products, recovery-rate-ranking)
	 * so the chunked scan of ac_abandoned_cart_history exists once. (10.6.0.)
	 *
	 * @param int $start Start Unix timestamp (0 = no lower bound).
	 * @param int $end   End Unix timestamp (0 = no upper bound).
	 * @return array
	 */
	protected function build_report( $start, $end ) {
		require_once __DIR__ . '/class-wcap-product-aggregate.php';
		return WCAP_Product_Aggregate::build_report( $start, $end );
	}
}
