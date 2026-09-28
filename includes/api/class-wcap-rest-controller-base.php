<?php
/**
 * Abstract base controller for the Abandoned Cart Pro REST API.
 *
 * Houses everything the concrete controllers share: the permission
 * callback, the plugin-level rate limiter, parameter validation /
 * sanitisation callbacks, date parsing, the email -> user_id resolver,
 * unknown-parameter rejection, the shared WHERE builder, and pagination.
 *
 * @package WooCommerce Abandoned Cart Pro
 * @since   10.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCAP_REST_Controller_Base class.
 */
abstract class WCAP_REST_Controller_Base extends WP_REST_Controller {

	/**
	 * The REST namespace shared by every route.
	 *
	 * @var string
	 */
	protected $namespace = 'wcap/v1';

	/**
	 * Guest id threshold.
	 *
	 * @var int
	 */
	const GUEST_ID_THRESHOLD = 63000000;

	/**
	 * Per-request cache of COUNT(*) results, keyed by the parameter set.
	 *
	 * Avoids running the count twice when both `data` and `meta` are built
	 * in the same request. (BRD 2.4.1.)
	 *
	 * @var array
	 */
	protected $count_cache = array();

	/**
	 * Permission callback applied to every route.
	 *
	 * Accepts either (a) a WooCommerce REST API key (read scope is enough)
	 * whose underlying user has `manage_woocommerce`, or (b) a logged-in
	 * admin with `manage_woocommerce` and a valid REST nonce (cookie auth).
	 * Authentication itself is performed upstream by WP/WooCommerce; here we
	 * only assert the capability and enforce the rate limit. (BRD 2.3 / 2.4.1.)
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return true|WP_Error
	 */
	public function get_permission( $request ) {

		// Denied outright, never falling through to the rate limiter.
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_woocommerce' ) ) {
			return new WP_Error(
				'wcap_rest_forbidden',
				__( 'You do not have permission to access this endpoint.', 'woocommerce-ac' ),
				array( 'status' => 403 )
			);
		}

		// Enforce the plugin-level rate limit per API key / user.
		$limited = $this->check_rate_limit();
		if ( is_wp_error( $limited ) ) {
			return $limited;
		}

		return true;
	}

	/**
	 * Plugin-level rate limiter, shared with the AI Reporting Abilities via
	 * WCAP_Rate_Limiter — one bucket per identity across both surfaces.
	 *
	 * @return true|WP_Error
	 */
	protected function check_rate_limit() {
		return WCAP_Rate_Limiter::check( WCAP_Rate_Limiter::DEFAULT_LIMIT, WCAP_Rate_Limiter::DEFAULT_WINDOW, 'wcap_rate_limited' );
	}

	/**
	 * Shared collection params for the list endpoints, each with
	 * sanitize_callback and validate_callback. (BRD 2.4.1.)
	 *
	 * @param string $default_status Default status for this endpoint.
	 * @return array
	 */
	public function get_list_collection_params( $default_status = 'abandoned' ) {

		return array(
			'status'        => array(
				'description'       => __( 'Filter by cart status.', 'woocommerce-ac' ),
				'type'              => 'string',
				'default'           => $default_status,
				'enum'              => WCAP_REST_Status_Helper::ALLOWED_STATUSES,
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => array( $this, 'validate_status_param' ),
			),
			'start_date'    => array(
				'description'       => __( 'Inclusive lower bound on abandoned timestamp (ISO 8601 or Unix).', 'woocommerce-ac' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => array( $this, 'validate_date_param' ),
			),
			'end_date'      => array(
				'description'       => __( 'Inclusive upper bound on abandoned timestamp (ISO 8601 or Unix).', 'woocommerce-ac' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => array( $this, 'validate_date_param' ),
			),
			'email'         => array(
				'description'       => __( 'Exact customer email address.', 'woocommerce-ac' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => array( $this, 'validate_email_param' ),
			),
			'cart_source'   => array(
				'description'       => __( 'Filter by cart source.', 'woocommerce-ac' ),
				'type'              => 'string',
				'default'           => 'all',
				'enum'              => WCAP_REST_Status_Helper::ALLOWED_SOURCES,
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => array( $this, 'validate_source_param' ),
			),
			'user_type'     => array(
				'description'       => __( 'Filter by REGISTERED or GUEST.', 'woocommerce-ac' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => array( $this, 'validate_user_type_param' ),
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
			'orderby'       => array(
				'description'       => __( 'Sort field.', 'woocommerce-ac' ),
				'type'              => 'string',
				'default'           => 'abandoned_cart_time',
				'enum'              => array( 'abandoned_cart_time', 'id', 'email_id' ),
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => array( $this, 'validate_orderby_param' ),
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
	 * Validate an integer parameter (rejected if non-numeric).
	 *
	 * @param mixed           $value Raw value.
	 * @param WP_REST_Request $request Request.
	 * @param string          $param Parameter name.
	 * @return true|WP_Error
	 */
	public function validate_int_param( $value, $request, $param ) {
		if ( ! is_numeric( $value ) || (int) $value != $value ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			return $this->invalid( $param, sprintf( /* translators: %s parameter name */ __( 'Parameter %s must be an integer.', 'woocommerce-ac' ), $param ) );
		}
		if ( (int) $value < 1 ) {
			return $this->invalid( $param, sprintf( /* translators: %s parameter name */ __( 'Parameter %s must be 1 or greater.', 'woocommerce-ac' ), $param ) );
		}
		return true;
	}

	/**
	 * Validate a date parameter (ISO 8601 or Unix timestamp).
	 *
	 * @param mixed  $value Raw value.
	 * @param mixed  $request Request.
	 * @param string $param Parameter name.
	 * @return true|WP_Error
	 */
	public function validate_date_param( $value, $request, $param ) {
		if ( '' === $value || null === $value ) {
			return true;
		}
		if ( false === self::parse_date( $value ) ) {
			return $this->invalid( $param, sprintf( /* translators: %s parameter name */ __( 'Parameter %s must be ISO 8601 (YYYY-MM-DD or YYYY-MM-DDTHH:MM:SS) or a Unix timestamp.', 'woocommerce-ac' ), $param ) );
		}
		return true;
	}

	/**
	 * Validate an email parameter (RFC 5322 via is_email()).
	 *
	 * @param mixed  $value Raw value.
	 * @param mixed  $request Request.
	 * @param string $param Parameter name.
	 * @return true|WP_Error
	 */
	public function validate_email_param( $value, $request, $param ) {
		if ( '' === $value || null === $value ) {
			return true;
		}
		if ( ! is_email( $value ) ) {
			return $this->invalid( $param, __( 'Invalid email address.', 'woocommerce-ac' ) );
		}
		return true;
	}

	/**
	 * Validate the status parameter against the allowed enum.
	 *
	 * @param mixed  $value Raw value.
	 * @param mixed  $request Request.
	 * @param string $param Parameter name.
	 * @return true|WP_Error
	 */
	public function validate_status_param( $value, $request, $param ) {
		if ( ! WCAP_REST_Status_Helper::is_valid_status( $value ) ) {
			return $this->invalid(
				$param,
				__( 'Invalid status value. Allowed: abandoned, recovered, unpaid, received, cancelled, all.', 'woocommerce-ac' )
			);
		}
		return true;
	}

	/**
	 * Validate the status parameter against the allowed enum.
	 *
	 * @param mixed  $value Raw value.
	 * @param mixed  $request Request.
	 * @param string $param Parameter name.
	 * @return true|WP_Error
	 */
	public function validate_reminder_type_param( $value, $request, $param ) {
		if ( ! WCAP_REST_Status_Helper::is_valid_reminder_types( $value ) ) {
			return $this->invalid(
				$param,
				__( 'Invalid reminder type value. Allowed: email, sms, wattsap.', 'woocommerce-ac' )
			);
		}
		return true;
	}

	/**
	 * Validate the cart_source parameter.
	 *
	 * @param mixed  $value Raw value.
	 * @param mixed  $request Request.
	 * @param string $param Parameter name.
	 * @return true|WP_Error
	 */
	public function validate_source_param( $value, $request, $param ) {
		if ( ! WCAP_REST_Status_Helper::is_valid_source( $value ) ) {
			return $this->invalid(
				$param,
				__( 'Invalid cart_source value. Allowed: all, checkout_page, product_page, atc, exit_intent, custom_form, url.', 'woocommerce-ac' )
			);
		}
		return true;
	}

	/**
	 * Validate the user_type parameter.
	 *
	 * @param mixed  $value Raw value.
	 * @param mixed  $request Request.
	 * @param string $param Parameter name.
	 * @return true|WP_Error
	 */
	public function validate_user_type_param( $value, $request, $param ) {
		if ( '' === $value || null === $value ) {
			return true;
		}
		if ( ! WCAP_REST_Status_Helper::is_valid_user_type( $value ) ) {
			return $this->invalid( $param, __( 'Invalid user_type. Allowed: REGISTERED, GUEST.', 'woocommerce-ac' ) );
		}
		return true;
	}

	/**
	 * Validate the orderby parameter.
	 *
	 * @param mixed  $value Raw value.
	 * @param mixed  $request Request.
	 * @param string $param Parameter name.
	 * @return true|WP_Error
	 */
	public function validate_orderby_param( $value, $request, $param ) {
		if ( ! in_array( $value, array( 'abandoned_cart_time', 'id', 'email_id' ), true ) ) {
			return $this->invalid( $param, __( 'Invalid orderby. Allowed: abandoned_cart_time, id, email_id.', 'woocommerce-ac' ) );
		}
		return true;
	}

	/**
	 * Validate the order direction parameter.
	 *
	 * @param mixed  $value Raw value.
	 * @param mixed  $request Request.
	 * @param string $param Parameter name.
	 * @return true|WP_Error
	 */
	public function validate_order_param( $value, $request, $param ) {
		if ( ! in_array( strtolower( (string) $value ), array( 'asc', 'desc' ), true ) ) {
			return $this->invalid( $param, __( 'Invalid order. Allowed: asc, desc.', 'woocommerce-ac' ) );
		}
		return true;
	}

	/**
	 * Reject any request carrying parameters not declared for the route.
	 *
	 * Keeps the v1 surface explicit. (BRD 2.4.1.)
	 *
	 * @param WP_REST_Request $request Request object.
	 * @param string[]        $allowed Allowed parameter names for the route.
	 * @return true|WP_Error
	 */
	protected function reject_unknown_params( $request, $allowed ) {

		// Standard WP params that are always permitted on a GET request.
		$always  = array( '_locale', '_envelope', '_method', '_fields', 'context', 'rest_route', '_wpnonce' );
		$allowed = array_merge( $allowed, $always );

		$supplied = array_keys( $request->get_query_params() );

		foreach ( $supplied as $name ) {
			if ( ! in_array( $name, $allowed, true ) ) {
				return new WP_Error(
					'wcap_invalid_parameter',
					sprintf(
						/* translators: %s: parameter name */
						__( 'Unrecognised parameter: %s. The v1 API rejects unknown parameters.', 'woocommerce-ac' ),
						$name
					),
					array( 'status' => 400 )
				);
			}
		}

		return true;
	}

	/**
	 * Parse a date value into a Unix timestamp.
	 *
	 * Accepts a Unix timestamp (all-digits) or ISO 8601
	 * (YYYY-MM-DD or YYYY-MM-DDTHH:MM:SS, optionally with a trailing Z).
	 *
	 * @param string $value Raw value.
	 * @return int|false Unix timestamp, or false if unparseable.
	 */
	public static function parse_date( $value ) {

		$value = trim( (string) $value );
		if ( '' === $value ) {
			return false;
		}

		// An 8-digit string reads as a compact YYYYMMDD date, not a timestamp —
		// a real Unix timestamp for any date past 2001 is 10 digits.
		if ( 8 === strlen( $value ) && ctype_digit( $value ) ) {
			$ts = strtotime( substr( $value, 0, 4 ) . '-' . substr( $value, 4, 2 ) . '-' . substr( $value, 6, 2 ) . ' 00:00:00 UTC' );
			return false !== $ts ? $ts : false;
		}

		// Unix timestamp (digits only).
		if ( ctype_digit( $value ) ) {
			return (int) $value;
		}

		// ISO 8601 date or date-time.
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}:\d{2})?Z?$/', $value ) ) {
			$ts = strtotime( $value . ( false === strpos( $value, 'T' ) ? ' 00:00:00 UTC' : ' UTC' ) );
			return false !== $ts ? $ts : false;
		}

		return false;
	}

	/**
	 * Build the shared WHERE clause + prepare args for the list endpoints.
	 *
	 * Returns [ $where_sql, $args ] where $where_sql contains %d / %s
	 * placeholders only for caller-supplied values; status and source
	 * fragments are code-defined literals.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @param string          $alias   Table alias.
	 * @return array{0:string,1:array}
	 */
	protected function build_where( $request, $alias = 'wpac' ) {

		$alias = preg_replace( '/[^A-Za-z0-9_]/', '', (string) $alias );
		$alias = '' === $alias ? 'wpac' : $alias;

		$clauses = array();
		$args    = array();

		// Never expose trashed carts through the API.
		$clauses[] = "{$alias}.wcap_trash = ''";

		// Status (literal fragment, value mapped to ints in PHP).
		$status    = $request->get_param( 'status' );
		$clauses[] = WCAP_REST_Status_Helper::status_sql( $status, $alias );

		// Source (literal fragment).
		$source = $request->get_param( 'cart_source' );
		$src    = WCAP_REST_Status_Helper::source_sql( $source, $alias );
		if ( '' !== $src ) {
			// source_sql already begins with "AND ".
			$clauses[] = ltrim( substr( $src, 3 ) );
		}

		// Date range on abandoned_cart_time.
		$start = $request->get_param( 'start_date' );
		if ( ! empty( $start ) ) {
			$ts        = self::parse_date( $start );
			$clauses[] = "{$alias}.abandoned_cart_time >= %d";
			$args[]    = (int) $ts;
		}
		$end = $request->get_param( 'end_date' );
		if ( ! empty( $end ) ) {
			$ts        = self::parse_date( $end );
			$clauses[] = "{$alias}.abandoned_cart_time <= %d";
			$args[]    = (int) $ts;
		}

		// user_type.
		$user_type = $request->get_param( 'user_type' );
		if ( ! empty( $user_type ) ) {
			if ( 'GUEST' === $user_type ) {
				$clauses[] = "{$alias}.user_id >= %d";
				$args[]    = self::GUEST_ID_THRESHOLD;
			} elseif ( 'REGISTERED' === $user_type ) {
				$clauses[] = "{$alias}.user_id > %d AND {$alias}.user_id < %d";
				$args[]    = 0;
				$args[]    = self::GUEST_ID_THRESHOLD;
			}
		}

		// Email — resolve to a set of user_ids across guest + registered.
		$email = $request->get_param( 'email' );
		if ( ! empty( $email ) ) {
			$ids = $this->resolve_email_to_user_ids( $email );
			if ( empty( $ids ) ) {
				// No match — force an empty result deterministically.
				$clauses[] = '1 = 0';
			} else {
				$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );
				$clauses[]    = "{$alias}.user_id IN ( {$placeholders} )";
				foreach ( $ids as $id ) {
					$args[] = (int) $id;
				}
			}
		}

		$where_sql = implode( ' AND ', $clauses );

		return array( $where_sql, $args );
	}

	/**
	 * Resolve an email address to the set of matching user_ids.
	 *
	 * Searches guest records (guest table id == history user_id for guests)
	 * and registered users (usermeta billing_email). (BRD: "Searches both
	 * registered users and guest records.")
	 *
	 * @param string $email Email address (already validated).
	 * @return int[] List of user_ids (may be empty).
	 */
	protected function resolve_email_to_user_ids( $email ) {

		global $wpdb;

		$email = sanitize_email( $email );
		$ids   = array();

		// Guest records.
		$guest_ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare(
				'SELECT id FROM ' . WCAP_GUEST_CART_HISTORY_TABLE . ' WHERE email_id = %s', // phpcs:ignore
				$email
			)
		);
		if ( is_array( $guest_ids ) ) {
			$ids = array_merge( $ids, array_map( 'intval', $guest_ids ) );
		}

		// Registered users via billing_email.
		$user = get_user_by( 'email', $email );
		if ( $user ) {
			$ids[] = (int) $user->ID;
		}
		$meta_ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'billing_email' AND meta_value = %s",
				$email
			)
		);
		if ( is_array( $meta_ids ) ) {
			$ids = array_merge( $ids, array_map( 'intval', $meta_ids ) );
		}

		return array_values( array_unique( array_filter( $ids ) ) );
	}

	/**
	 * Clamp the per_page value to the documented 1-100 range.
	 *
	 * @param int $per_page Raw per_page.
	 * @return int
	 */
	protected function clamp_per_page( $per_page ) {
		$per_page = (int) $per_page;
		if ( $per_page < 1 ) {
			$per_page = 1;
		}
		if ( $per_page > 100 ) {
			$per_page = 100;
		}
		return $per_page;
	}

	/**
	 * Run (and cache) a COUNT(*) for a given prepared statement.
	 *
	 * @param string $sql  SQL with placeholders (already includes COUNT(*)).
	 * @param array  $args Prepare args.
	 * @return int
	 */
	protected function get_count( $sql, $args ) {

		global $wpdb;

		$cache_key = md5( $sql . wp_json_encode( $args ) );
		if ( isset( $this->count_cache[ $cache_key ] ) ) {
			return $this->count_cache[ $cache_key ];
		}

		if ( ! empty( $args ) ) {
			$prepared = $wpdb->prepare( $sql, $args ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		} else {
			$prepared = $sql;
		}

		$count                           = (int) $wpdb->get_var( $prepared ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$this->count_cache[ $cache_key ] = $count;

		return $count;
	}

	/**
	 * Build the pagination meta block.
	 *
	 * @param int $total    Total matching records.
	 * @param int $page     Current page.
	 * @param int $per_page Items per page.
	 * @return array
	 */
	protected function build_meta( $total, $page, $per_page ) {
		$per_page = max( 1, (int) $per_page );
		return array(
			'total'    => (int) $total,
			'pages'    => (int) ceil( $total / $per_page ),
			'page'     => (int) $page,
			'per_page' => (int) $per_page,
		);
	}

	/**
	 * Wrap a payload in the standard { data, meta } envelope and add
	 * pagination + rate-limit headers.
	 *
	 * @param mixed $data Data array (list) or object (single).
	 * @param array $meta Meta block (omit for single-record responses).
	 * @return WP_REST_Response
	 */
	protected function envelope( $data, $meta = null ) {

		$body = array( 'data' => $data );
		if ( null !== $meta ) {
			$body['meta'] = $meta;
		}

		$response = rest_ensure_response( $body );

		if ( is_array( $meta ) ) {
			$response->header( 'X-WP-Total', (int) $meta['total'] );
			$response->header( 'X-WP-TotalPages', (int) $meta['pages'] );
		}

		return $response;
	}

	/**
	 * Build a 400 validation WP_Error with a wcap_invalid_<param> code.
	 *
	 * @param string $param   Parameter name.
	 * @param string $message Human-readable message.
	 * @return WP_Error
	 */
	protected function invalid( $param, $message ) {
		return new WP_Error(
			'wcap_invalid_' . sanitize_key( $param ),
			$message,
			array( 'status' => 400 )
		);
	}

	/**
	 * Order-by column allow-list -> real column, with a safe alias prefix.
	 *
	 * @param string $orderby Requested orderby.
	 * @param string $alias   Table alias.
	 * @return string Safe "alias.column" string.
	 */
	protected function safe_orderby( $orderby, $alias = 'wpac' ) {
		$alias = preg_replace( '/[^A-Za-z0-9_]/', '', (string) $alias );
		$map   = array(
			'abandoned_cart_time' => 'abandoned_cart_time',
			'id'                  => 'id',
			'email_id'            => 'id', // email_id is not on the history table; fall back to id for a stable sort.
		);
		$col   = isset( $map[ $orderby ] ) ? $map[ $orderby ] : 'abandoned_cart_time';
		return $alias . '.' . $col;
	}

	/**
	 * Normalise an order direction to ASC/DESC.
	 *
	 * @param string $order Requested order.
	 * @return string
	 */
	protected function safe_order( $order ) {
		return 'asc' === strtolower( (string) $order ) ? 'ASC' : 'DESC';
	}
}
