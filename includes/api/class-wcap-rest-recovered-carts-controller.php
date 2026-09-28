<?php
/**
 * Controller for /recovered-carts.
 *
 * Shares all query/format helpers with the abandoned-carts controller via
 * WCAP_REST_Controller_Base. Differs only in its default status
 * (`recovered`) and the per-row shaping, which surfaces the associated
 * WooCommerce order id. (BRD 2.3 / 2.4.1.)
 *
 * @package WooCommerce Abandoned Cart Pro
 * @since   10.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCAP_REST_Recovered_Carts_Controller class.
 */
class WCAP_REST_Recovered_Carts_Controller extends WCAP_REST_Controller_Base {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'recovered-carts';

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
					'args'                => $this->get_list_collection_params( 'recovered' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * GET /recovered-carts — paginated list of recovered carts.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {

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

		$data_sql  = "SELECT wpac.* FROM `{$table}` AS wpac WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$data_args = array_merge( $where_args, array( $per_page, $offset ) );

		$rows = $wpdb->get_results( $wpdb->prepare( $data_sql, $data_args ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared

		$count_sql = "SELECT COUNT(*) FROM `{$table}` AS wpac WHERE {$where_sql}";
		$total     = $this->get_count( $count_sql, $where_args );

		$data = array();
		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$data[] = $this->shape_recovered( $row );
			}
		}

		return $this->envelope( $data, $this->build_meta( $total, $page, $per_page ) );
	}

	/**
	 * Shape a recovered-cart row.
	 *
	 * Reuses the canonical formatter, then trims to the recovered-cart view
	 * documented in the API reference (no billing/shipping/session block,
	 * recovered_order_id promoted).
	 *
	 * @param object $row Raw DB row.
	 * @return array
	 */
	protected function shape_recovered( $row ) {

		$full = WCAP_Cart_Schema::format_cart_row( $row, false );

		return array(
			'id'                      => $full['id'],
			'status'                  => $full['status'],
			'user_id'                 => $full['user_id'],
			'user_type'               => $full['user_type'],
			'email_id'                => $full['email_id'],
			'abandoned_cart_time'     => $full['abandoned_cart_time'],
			'abandoned_cart_time_iso' => $full['abandoned_cart_time_iso'],
			'recovered_order_id'      => $full['recovered_order_id'],
			'products'                => $full['products'],
			'totals'                  => $full['totals'],
			'reminder_activity'       => $full['reminder_activity'],
		);
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
