<?php
/**
 * Shared rate limiter used by both the wcap/v1 REST controllers and the AI Reporting Abilities.
 *
 * @package WooCommerce Abandoned Cart Pro
 * @since   10.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCAP_Rate_Limiter class.
 */
class WCAP_Rate_Limiter {

	/**
	 * Default requests allowed inside the window.
	 *
	 * @var int
	 */
	const DEFAULT_LIMIT = 60;

	/**
	 * Default window in seconds.
	 *
	 * @var int
	 */
	const DEFAULT_WINDOW = 60;

	/**
	 * Seconds to wait for the MySQL named lock before failing open.
	 *
	 * @var int
	 */
	const LOCK_TIMEOUT = 2;

	/**
	 * Retry-After value for the most recent breach, read by add_retry_after_header().
	 *
	 * @var int|null
	 */
	private static $last_retry_after = null;

	/**
	 * Check (and record) a request against the shared bucket for the current caller.
	 *
	 * One bucket per identity, shared by abilities and REST endpoints.
	 * Uses a MySQL named lock so concurrent requests can't undercount.
	 *
	 * @param int    $limit      Requests allowed per window.
	 * @param int    $window     Window length in seconds.
	 * @param string $error_code Error code to return on breach (callers use their own convention).
	 * @return true|WP_Error
	 * @since 10.6.0
	 */
	public static function check( $limit = self::DEFAULT_LIMIT, $window = self::DEFAULT_WINDOW, $error_code = 'wcap_rate_limited' ) {

		$key = self::get_identity_key();
		if ( '' === $key ) {
			// Could not identify a caller; let the capability check govern.
			return true;
		}

		global $wpdb;
		$lock_name = 'wcap_rl_' . md5( $key );

		$acquired = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', $lock_name, self::LOCK_TIMEOUT ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( '1' !== $acquired ) {
			// Lock not acquired in time; fail open.
			return true;
		}

		try {
			return self::check_locked( $lock_name, $limit, $window, $error_code );
		} finally {
			$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}
	}

	/**
	 * The actual bucket check, run only while the named lock is held.
	 *
	 * @param string $lock_name  Same key used for the transient bucket.
	 * @param int    $limit      Requests allowed per window.
	 * @param int    $window     Window length in seconds.
	 * @param string $error_code Error code to return on breach.
	 * @return true|WP_Error
	 * @since 10.6.0
	 */
	private static function check_locked( $lock_name, $limit, $window, $error_code ) {

		$bucket = get_transient( $lock_name );
		$now    = time();

		if ( ! is_array( $bucket ) || ! isset( $bucket['reset'] ) || $bucket['reset'] <= $now ) {
			// Fresh window.
			set_transient(
				$lock_name,
				array(
					'count' => 1,
					'reset' => $now + $window,
				),
				$window
			);
			return true;
		}

		if ( $bucket['count'] >= $limit ) {
			$retry_after = max( 1, $bucket['reset'] - $now );

			if ( ! headers_sent() ) {
				header( 'Retry-After: ' . $retry_after );
			}
			self::$last_retry_after = $retry_after;
			// Static callback so add_filter dedupes repeat breaches in one request.
			add_filter( 'rest_post_dispatch', array( __CLASS__, 'add_retry_after_header' ) );

			return new WP_Error(
				$error_code,
				__( 'Rate limit exceeded. See retry_after_seconds for when the window resets.', 'woocommerce-ac' ),
				array(
					'status'              => 429,
					'retry_after_seconds' => $retry_after,
				)
			);
		}

		++$bucket['count'];
		set_transient( $lock_name, $bucket, max( 1, $bucket['reset'] - $now ) );

		return true;
	}

	/**
	 * rest_post_dispatch callback: attaches the most recent breach's Retry-After.
	 *
	 * @param mixed $response Response being filtered.
	 * @return mixed
	 * @since 10.6.0
	 */
	public static function add_retry_after_header( $response ) {
		if ( $response instanceof WP_REST_Response && null !== self::$last_retry_after ) {
			$response->header( 'Retry-After', self::$last_retry_after );
		}
		return $response;
	}

	/**
	 * Resolve the identity used for rate limiting.
	 *
	 * Prefers the logged-in user id; PHP_AUTH_USER is untrusted (any
	 * Basic-Auth header populates it) so it's only a fallback.
	 *
	 * @return string
	 * @since 10.6.0
	 */
	public static function get_identity_key() {

		$uid = get_current_user_id();
		if ( $uid > 0 ) {
			return 'uid_' . $uid;
		}

		if ( isset( $_SERVER['PHP_AUTH_USER'] ) && '' !== $_SERVER['PHP_AUTH_USER'] ) {
			return 'ck_' . sanitize_text_field( wp_unslash( $_SERVER['PHP_AUTH_USER'] ) );
		}

		return '';
	}
}
