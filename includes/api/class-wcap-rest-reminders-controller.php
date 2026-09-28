<?php
/**
 * Controller for /reminders-sent.
 *
 * Returns a paginated list of every reminder send recorded in
 * {prefix}ac_sent_history, with per-row opened / clicked flags joined from
 * the opened / clicked notification tables. (BRD 2.3, API Reference.)
 *
 * @package WooCommerce Abandoned Cart Pro
 * @since   10.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCAP_REST_Reminders_Controller class.
 */
class WCAP_REST_Reminders_Controller extends WCAP_REST_Controller_Base {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'reminders-sent';

	/**
	 * Per-request cache: cart_id => resolved email.
	 *
	 * @var array
	 */
	private $email_cache = array();

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
			'start_date'    => array(
				'description'       => __( 'Inclusive lower bound on reminder sent timestamp.', 'woocommerce-ac' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => array( $this, 'validate_date_param' ),
			),
			'end_date'      => array(
				'description'       => __( 'Inclusive upper bound on reminder sent timestamp.', 'woocommerce-ac' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => array( $this, 'validate_date_param' ),
			),
			'email'         => array(
				'description'       => __( 'Filter by customer email address.', 'woocommerce-ac' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => array( $this, 'validate_email_param' ),
			),
			'page'          => array(
				'description'       => __( 'Page number.', 'woocommerce-ac' ),
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => array( $this, 'validate_int_param' ),
			),
			'per_page'      => array(
				'description'       => __( 'Items per page (1-100).', 'woocommerce-ac' ),
				'type'              => 'integer',
				'default'           => 20,
				'sanitize_callback' => 'absint',
				'validate_callback' => array( $this, 'validate_int_param' ),
			),
			'order'         => array(
				'description'       => __( 'Sort direction.', 'woocommerce-ac' ),
				'type'              => 'string',
				'default'           => 'desc',
				'enum'              => array( 'asc', 'desc' ),
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => array( $this, 'validate_order_param' ),
			),
			'reminder_type' => array(
				'description'       => __( 'Filter by Reminder Type.', 'woocommerce-ac' ),
				'type'              => 'string',
				'enum'              => WCAP_REST_Status_Helper::ALLOWED_REMINDER_TYPES,
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => array( $this, 'validate_reminder_type_param' ),
			),
		);
	}

	/**
	 * GET /reminders-sent.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {

		$unknown = $this->reject_unknown_params( $request, array_keys( $this->get_collection_params() ) );
		if ( is_wp_error( $unknown ) ) {
			return $unknown;
		}

		global $wpdb;

		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = $this->clamp_per_page( $request->get_param( 'per_page' ) );
		$offset   = ( $page - 1 ) * $per_page;
		$order    = $this->safe_order( $request->get_param( 'order' ) );

		$clauses = array( '1 = 1' );
		$args    = array();

		// Date range on sent_time (stored as datetime).
		$start = $request->get_param( 'start_date' );
		if ( ! empty( $start ) ) {
			$clauses[] = 's.sent_time >= %s';
			$args[]    = gmdate( 'Y-m-d H:i:s', self::parse_date( $start ) );
		}
		$end = $request->get_param( 'end_date' );
		if ( ! empty( $end ) ) {
			$clauses[] = 's.sent_time <= %s';
			$args[]    = gmdate( 'Y-m-d H:i:s', self::parse_date( $end ) );
		}

		// Email filter -> resolve to cart_ids.
		$email = $request->get_param( 'email' );
		if ( ! empty( $email ) ) {
			$cart_ids = $this->resolve_email_to_cart_ids( $email );
			if ( empty( $cart_ids ) ) {
				return $this->envelope( array(), $this->build_meta( 0, $page, $per_page ) );
			}
			$placeholders = implode( ', ', array_fill( 0, count( $cart_ids ), '%d' ) );
			$clauses[]    = "s.cart_id IN ( {$placeholders} )";
			foreach ( $cart_ids as $cid ) {
				$args[] = (int) $cid;
			}
		}

		// Reminder Type filter -> resolve to cart_ids.
		$reminder_type = $request->get_param( 'reminder_type' );
		if ( ! empty( $reminder_type ) ) {
			$clauses[] = 's.notification_type Like %s';
			$args[]    = $reminder_type;
		}

		$where_sql = implode( ' AND ', $clauses );
		$sent      = WCAP_EMAIL_SENT_HISTORY_TABLE;
		$opened    = WCAP_NOTIFICATION_OPENED_TABLE;
		$clicked   = WCAP_LINK_CLICKED_TABLE;

		// Page of rows with opened/clicked flags computed via EXISTS.
		$data_sql = "SELECT s.id, s.cart_id, s.notification_type, s.sent_time,
				EXISTS ( SELECT 1 FROM `{$opened}` o WHERE o.notification_sent_id = s.id ) AS opened,
				EXISTS ( SELECT 1 FROM `{$clicked}` c WHERE c.notification_sent_id = s.id ) AS clicked
			FROM `{$sent}` AS s
			WHERE {$where_sql}
			ORDER BY s.sent_time {$order}, s.id {$order}
			LIMIT %d OFFSET %d";

		$data_args = array_merge( $args, array( $per_page, $offset ) );
		$rows      = $wpdb->get_results( $wpdb->prepare( $data_sql, $data_args ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared

		$count_sql = "SELECT COUNT(*) FROM `{$sent}` AS s WHERE {$where_sql}";
		$total     = $this->get_count( $count_sql, $args );

		$data = array();
		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$sent_ts = strtotime( $row->sent_time . ' UTC' );
				$data[]  = array(
					'id'            => (int) $row->id,
					'cart_id'       => (int) $row->cart_id,
					'email_id'      => $this->email_for_cart( (int) $row->cart_id ),
					'reminder_type' => (string) $row->notification_type,
					'sent_at'       => $sent_ts ? gmdate( 'Y-m-d\TH:i:s\Z', $sent_ts ) : '',
					'opened'        => (bool) $row->opened,
					'clicked'       => (bool) $row->clicked,
				);
			}
		}

		return $this->envelope( $data, $this->build_meta( $total, $page, $per_page ) );
	}

	/**
	 * Resolve an email to the cart_ids belonging to that customer.
	 *
	 * @param string $email Email (validated).
	 * @return int[]
	 */
	private function resolve_email_to_cart_ids( $email ) {

		global $wpdb;

		$user_ids = $this->resolve_email_to_user_ids( $email );
		if ( empty( $user_ids ) ) {
			return array();
		}

		$placeholders = implode( ', ', array_fill( 0, count( $user_ids ), '%d' ) );
		$cart_ids     = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare(
				'SELECT id FROM ' . WCAP_ABANDONED_CART_HISTORY_TABLE . " WHERE user_id IN ( {$placeholders} )", // phpcs:ignore
				$user_ids
			)
		);

		return is_array( $cart_ids ) ? array_map( 'intval', $cart_ids ) : array();
	}

	/**
	 * Resolve (and cache) the email address for a cart id.
	 *
	 * @param int $cart_id Cart id.
	 * @return string
	 */
	private function email_for_cart( $cart_id ) {

		if ( isset( $this->email_cache[ $cart_id ] ) ) {
			return $this->email_cache[ $cart_id ];
		}

		$email   = '';
		$history = function_exists( 'wcap_get_data_cart_history' ) ? wcap_get_data_cart_history( $cart_id ) : false;

		if ( $history ) {
			$user_id   = isset( $history->user_id ) ? (int) $history->user_id : 0;
			$user_type = isset( $history->user_type ) ? (string) $history->user_type : '';

			if ( $user_id >= self::GUEST_ID_THRESHOLD && 'REGISTERED' !== $user_type ) {
				$guest = wcap_get_data_guest_history( $user_id );
				$email = $guest && isset( $guest->email_id ) ? (string) $guest->email_id : '';
			} elseif ( $user_id > 0 ) {
				$email = (string) get_user_meta( $user_id, 'billing_email', true );
			}
		}

		$this->email_cache[ $cart_id ] = $email;
		return $email;
	}
}
