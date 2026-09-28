<?php
/**
 * Shared support code for the AI Reporting Abilities: rate limiting,
 * the wcap_ability_* structured error builder, and audit logging. The
 * capability check itself is inlined in each ability's permission_callback
 * (not shared here) so QIT's security scanner can see the return type
 * directly, without tracing through a helper method call.
 *
 * @package WooCommerce Abandoned Cart Pro
 * @since   10.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCAP_Ability_Utils class.
 */
class WCAP_Ability_Utils {

	/**
	 * Rate-limit check for the execute path. Returns a WP_Error so a breach
	 * can't be mistaken for real data (WP_Error skips output-schema
	 * validation entirely). Retry-after is folded into the message text,
	 * since the MCP execute-ability wrapper drops WP_Error's error data.
	 *
	 * @param string $ability_id Ability id, for breach logging.
	 * @return true|WP_Error
	 * @since 10.6.0
	 */
	public static function check_rate_limit( $ability_id ) {

		$result = WCAP_Rate_Limiter::check( WCAP_Rate_Limiter::DEFAULT_LIMIT, WCAP_Rate_Limiter::DEFAULT_WINDOW, 'wcap_ability_rate_limited' );
		if ( ! is_wp_error( $result ) ) {
			return true;
		}

		self::log_rate_limit_breach( $ability_id );

		$data        = $result->get_error_data();
		$retry_after = isset( $data['retry_after_seconds'] ) ? (int) $data['retry_after_seconds'] : null;

		return new WP_Error(
			$result->get_error_code(),
			null !== $retry_after
				? sprintf(
					/* translators: %d: seconds until the rate-limit window resets. */
					__( 'Rate limit exceeded. Retry after %d seconds.', 'woocommerce-ac' ),
					$retry_after
				)
				: $result->get_error_message(),
			$data
		);
	}

	/**
	 * Resolve start_date/end_date input into Unix timestamps.
	 *
	 * @param array      $input    Raw ability input.
	 * @param bool       $required Whether both dates must be supplied.
	 * @param array|null $default  [start, end] timestamps to use when a date is omitted; null means "no bound" (0).
	 * @return array|WP_Error [ $start, $end ], or a WP_Error.
	 * @since 10.6.0
	 */
	public static function resolve_date_range( $input, $required = false, $default = null ) {

		$start_raw = isset( $input['start_date'] ) ? (string) $input['start_date'] : '';
		$end_raw   = isset( $input['end_date'] ) ? (string) $input['end_date'] : '';

		if ( $required && ( '' === $start_raw || '' === $end_raw ) ) {
			return self::invalid_input_error( __( 'start_date and end_date are both required.', 'woocommerce-ac' ) );
		}

		$start = null !== $default ? (int) $default[0] : 0;
		if ( '' !== $start_raw ) {
			$parsed = self::parse_date( $start_raw );
			if ( false === $parsed ) {
				return self::invalid_input_error( __( 'start_date must be an ISO 8601 date or Unix timestamp.', 'woocommerce-ac' ) );
			}
			$start = (int) $parsed;
		}

		$end = null !== $default ? (int) $default[1] : 0;
		if ( '' !== $end_raw ) {
			$parsed = self::parse_date( $end_raw );
			if ( false === $parsed ) {
				return self::invalid_input_error( __( 'end_date must be an ISO 8601 date or Unix timestamp.', 'woocommerce-ac' ) );
			}
			$end = (int) $parsed;
		}

		if ( $start > 0 && $end > 0 && $start > $end ) {
			return self::invalid_date_range_error();
		}

		return array( $start, $end );
	}

	/**
	 * Largest allowed span between start_date and end_date, for abilities that
	 * run a single unbounded query rather than a chunked scan.
	 *
	 * @var int
	 */
	const MAX_WINDOW_SECONDS = 366 * DAY_IN_SECONDS;

	/**
	 * Reject date ranges wider than MAX_WINDOW_SECONDS. Only applies once
	 * both bounds are set.
	 *
	 * @param int $start Start Unix timestamp.
	 * @param int $end   End Unix timestamp.
	 * @return true|WP_Error
	 * @since 10.6.0
	 */
	public static function check_max_window( $start, $end ) {

		if ( $start > 0 && $end > 0 && ( $end - $start ) > self::MAX_WINDOW_SECONDS ) {
			return self::invalid_input_error(
				sprintf(
					/* translators: %d: maximum number of days allowed between start_date and end_date. */
					__( 'start_date and end_date must not span more than %d days.', 'woocommerce-ac' ),
					366
				)
			);
		}

		return true;
	}

	/**
	 * Build a wcap_ability_invalid_date_range error.
	 *
	 * @return WP_Error
	 * @since 10.6.0
	 */
	public static function invalid_date_range_error() {
		return new WP_Error(
			'wcap_ability_invalid_date_range',
			__( 'start_date must not be after end_date.', 'woocommerce-ac' ),
			array( 'status' => 400 )
		);
	}

	/**
	 * Build a wcap_ability_invalid_input error.
	 *
	 * @param string $message Human-readable message.
	 * @return WP_Error
	 * @since 10.6.0
	 */
	public static function invalid_input_error( $message ) {
		return new WP_Error( 'wcap_ability_invalid_input', $message, array( 'status' => 400 ) );
	}

	/**
	 * Log an ability execution. Never logs the result payload, only
	 * ability_id, user_id, timestamp, and input. (BRD 2.4.3.)
	 *
	 * @param string $ability_id Ability id, e.g. abandoned-cart-pro/top-abandoned-products.
	 * @param array  $input      Validated input parameters.
	 * @return void
	 * @since 10.6.0
	 */
	public static function log_execution( $ability_id, $input ) {

		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		wc_get_logger()->info(
			wp_json_encode(
				array(
					'ability_id' => $ability_id,
					'user_id'    => get_current_user_id(),
					'timestamp'  => gmdate( 'Y-m-d\TH:i:s\Z' ),
					'input'      => $input,
				)
			),
			array( 'source' => 'abandoned-cart-pro-abilities' )
		);
	}

	/**
	 * Log a rate-limit breach distinctly from normal executions.
	 *
	 * @param string $ability_id Ability id.
	 * @return void
	 * @since 10.6.0
	 */
	public static function log_rate_limit_breach( $ability_id ) {

		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		wc_get_logger()->warning(
			wp_json_encode(
				array(
					'ability_id' => $ability_id,
					'user_id'    => get_current_user_id(),
					'timestamp'  => gmdate( 'Y-m-d\TH:i:s\Z' ),
					'event'      => 'rate_limit_breach',
				)
			),
			array( 'source' => 'abandoned-cart-pro-abilities' )
		);
	}

	/**
	 * Parse a date value (ISO 8601 or Unix timestamp) into a Unix timestamp.
	 *
	 * @param string $value Raw value.
	 * @return int|false
	 * @since 10.6.0
	 */
	public static function parse_date( $value ) {
		return WCAP_REST_Controller_Base::parse_date( $value );
	}
}
