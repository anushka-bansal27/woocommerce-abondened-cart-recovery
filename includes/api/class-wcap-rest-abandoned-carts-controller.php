<?php
/**
 * Controller for /abandoned-carts and /abandoned-carts/{id}.
 *
 * @package WooCommerce Abandoned Cart Pro
 * @since   10.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCAP_REST_Abandoned_Carts_Controller class.
 */
class WCAP_REST_Abandoned_Carts_Controller extends WCAP_REST_Controller_Base {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'abandoned-carts';

	/**
	 * Register the list and single-record routes.
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
					'args'                => $this->get_list_collection_params( 'abandoned' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_permission' ),
					'args'                => array(
						'id' => array(
							'description'       => __( 'Numeric cart record id.', 'woocommerce-ac' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
							'validate_callback' => array( $this, 'validate_int_param' ),
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * GET /abandoned-carts — paginated, filterable list.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {

		// Reject unknown parameters to keep the v1 surface explicit.
		$unknown = $this->reject_unknown_params( $request, array_keys( $this->get_list_collection_params() ) );
		if ( is_wp_error( $unknown ) ) {
			return $unknown;
		}

		global $wpdb;

		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = $this->clamp_per_page( $request->get_param( 'per_page' ) );
		$offset   = ( $page - 1 ) * $per_page;

		list( $where_sql, $where_args ) = $this->build_where( $request, 'wpac' );

		$orderby = $this->safe_orderby( $request->get_param( 'orderby' ), 'wpac' );
		$order   = $this->safe_order( $request->get_param( 'order' ) );

		$table = WCAP_ABANDONED_CART_HISTORY_TABLE;

		// Primary SELECT with LIMIT/OFFSET.
		$data_sql  = "SELECT wpac.* FROM `{$table}` AS wpac WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$data_args = array_merge( $where_args, array( $per_page, $offset ) );

		$rows = $wpdb->get_results( $wpdb->prepare( $data_sql, $data_args ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared

		// Separate COUNT(*) with the same WHERE (cached for the request).
		$count_sql = "SELECT COUNT(*) FROM `{$table}` AS wpac WHERE {$where_sql}";
		$total     = $this->get_count( $count_sql, $where_args );

		$data = array();
		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				// List responses omit opens/clicks to stay fast. (BRD 2.4.2.).
				$data[] = WCAP_Cart_Schema::format_cart_row( $row, false );
			}
		}

		return $this->envelope( $data, $this->build_meta( $total, $page, $per_page ) );
	}

	/**
	 * GET /abandoned-carts/{id} — single record with full details.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {

		global $wpdb;

		$id    = absint( $request->get_param( 'id' ) );
		$table = WCAP_ABANDONED_CART_HISTORY_TABLE;

		$row = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE id = %d AND wcap_trash = ''", // phpcs:ignore
				$id
			)
		);

		if ( ! $row ) {
			return new WP_Error(
				'wcap_not_found',
				sprintf(
					/* translators: %d cart id */
					__( 'No cart found with ID %d.', 'woocommerce-ac' ),
					$id
				),
				array( 'status' => 404 )
			);
		}

		// Single record includes opens/clicks. (BRD 2.4.2.).
		$data = WCAP_Cart_Schema::format_cart_row( $row, true );

		return $this->envelope( $data );
	}

	/**
	 * Public item schema for OPTIONS discovery.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		return WCAP_Cart_Schema::get_cart_schema();
	}
}
