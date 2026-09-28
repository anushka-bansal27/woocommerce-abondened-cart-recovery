<?php
/**
 * Centralised cart-status / cart-source resolution.
 *
 * Single source of truth for the mapping between the public `status`
 * enum and the underlying `cart_ignored` / `recovered_cart` columns of
 * the {prefix}ac_abandoned_cart_history table.
 *
 * The REST API, the CSV export and the admin list table must all derive
 * status from this class so the surfaces never drift. (BRD 2.4.1 / 2.4.3.)
 *
 * @package WooCommerce Abandoned Cart Pro
 * @since   10.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCAP_REST_Status_Helper class.
 */
class WCAP_REST_Status_Helper {

	/**
	 * Allowed values for the `status` query parameter.
	 *
	 * @var string[]
	 */
	const ALLOWED_STATUSES = array( 'abandoned', 'recovered', 'unpaid', 'received', 'cancelled', 'all' );

	/**
	 * Allowed values for the `cart_source` query parameter.
	 *
	 * @var string[]
	 */
	const ALLOWED_SOURCES = array( 'all', 'checkout_page', 'product_page', 'atc', 'exit_intent', 'custom_form', 'url' );

	/**
	 * Allowed values for the `user_type` query parameter.
	 *
	 * @var string[]
	 */
	const ALLOWED_USER_TYPES = array( 'REGISTERED', 'GUEST' );

	/**
	 * Allowed values for the `reminder_type` query parameter.
	 *
	 * @var string[]
	 */
	const ALLOWED_REMINDER_TYPES = array( 'email', 'sms', 'whatsapp', 'fb' );

	/**
	 * Build the SQL WHERE fragment for a status value.
	 *
	 * The status value is mapped to fixed integer `cart_ignored` values in
	 * PHP via a hard-coded switch — the caller-supplied string is NEVER
	 * interpolated into SQL. Only literal, code-defined fragments are
	 * returned. (BRD 2.4.1: "Filter values from the status enum must be
	 * mapped to the integer cart_ignored values in PHP — never interpolated
	 * into SQL.")
	 *
	 * @param string $status Status enum value. Assumed pre-validated.
	 * @param string $alias  Table alias used in the query (e.g. 'wpac').
	 * @return string SQL fragment, already wrapped in parentheses.
	 */
	public static function status_sql( $status, $alias = 'wpac' ) {

		// Sanitise the alias to a bare identifier so it can never carry SQL.
		$alias = preg_replace( '/[^A-Za-z0-9_]/', '', (string) $alias );
		$alias = '' === $alias ? 'wpac' : $alias;

		switch ( $status ) {
			case 'abandoned':
				return "( {$alias}.cart_ignored = '0' AND {$alias}.recovered_cart = 0 )";
			case 'recovered':
				return "( {$alias}.cart_ignored = '1' AND {$alias}.recovered_cart > 0 )";
			case 'unpaid':
				return "( {$alias}.cart_ignored = '2' AND {$alias}.recovered_cart = 0 )";
			case 'received':
				return "( {$alias}.cart_ignored = '3' AND {$alias}.recovered_cart = 0 )";
			case 'cancelled':
				return "( {$alias}.cart_ignored = '4' AND {$alias}.recovered_cart = 0 )";
			case 'all':
			default:
				return "( ( {$alias}.cart_ignored <> '1' AND {$alias}.recovered_cart = 0 ) OR ( {$alias}.cart_ignored = '1' AND {$alias}.recovered_cart > 0 ) )";
		}
	}

	/**
	 * Build the SQL WHERE fragment for a cart-source value.
	 *
	 * Mirrors the source filter used by the CSV export. Returns a fragment
	 * that begins with `AND ` (or an empty string for `all`). All fragments
	 * are literal and code-defined.
	 *
	 * @param string $source Source enum value. Assumed pre-validated.
	 * @param string $alias  Table alias used in the query (e.g. 'wpac').
	 * @return string SQL fragment.
	 */
	public static function source_sql( $source, $alias = 'wpac' ) {

		$alias = preg_replace( '/[^A-Za-z0-9_]/', '', (string) $alias );
		$alias = '' === $alias ? 'wpac' : $alias;

		switch ( $source ) {
			case 'checkout_page':
				return "AND {$alias}.abandoned_cart_info LIKE '%checkout%' AND {$alias}.user_id >= 63000000";
			// In this plugin, user_id < 63000000 represents registered users and user_id >= 63000000 represents guest users, so the product_page case uses user_id to identify carts captured from the product page for registered users instead of filtering by captured_by.
			case 'product_page':
				return "AND {$alias}.user_id > 0 AND {$alias}.user_id < 63000000";
			case 'atc':
				return "AND {$alias}.abandoned_cart_info LIKE '%atc%' AND {$alias}.user_id >= 63000000";
			case 'exit_intent':
				return "AND {$alias}.abandoned_cart_info LIKE '%exit_intent%'";
			case 'custom_form':
				return "AND {$alias}.abandoned_cart_info LIKE '%custom_form%'";
			case 'url':
				return "AND {$alias}.abandoned_cart_info LIKE '%url%'";
			case 'all':
			default:
				return '';
		}
	}

	/**
	 * Derive the public status string from the raw DB columns.
	 *
	 * Inverse of status_sql(). Used when shaping a response row so the
	 * returned `status` field always matches the filter semantics.
	 *
	 * @param mixed $cart_ignored   Raw cart_ignored column value.
	 * @param mixed $recovered_cart Raw recovered_cart column value.
	 * @return string One of the ALLOWED_STATUSES (never 'all').
	 */
	public static function derive_status( $cart_ignored, $recovered_cart ) {

		$cart_ignored   = (string) $cart_ignored;
		$recovered_cart = (int) $recovered_cart;

		if ( '1' === $cart_ignored && $recovered_cart > 0 ) {
			return 'recovered';
		}
		if ( '0' === $cart_ignored && 0 === $recovered_cart ) {
			return 'abandoned';
		}
		if ( '2' === $cart_ignored && 0 === $recovered_cart ) {
			return 'unpaid';
		}
		if ( '3' === $cart_ignored && 0 === $recovered_cart ) {
			return 'received';
		}
		if ( '4' === $cart_ignored && 0 === $recovered_cart ) {
			return 'cancelled';
		}

		// Anything that does not match a defined state is reported as abandoned.
		return 'abandoned';
	}

	/**
	 * Whether a status value is part of the allowed enum.
	 *
	 * @param string $status Status value.
	 * @return bool
	 */
	public static function is_valid_status( $status ) {
		return in_array( $status, self::ALLOWED_STATUSES, true );
	}

	/**
	 * Whether a type value is part of the allowed enum.
	 *
	 * @param string $status Status value.
	 * @return bool
	 */
	public static function is_valid_reminder_types( $status ) {
		return in_array( $status, self::ALLOWED_REMINDER_TYPES, true );
	}

	/**
	 * Whether a cart-source value is part of the allowed enum.
	 *
	 * @param string $source Source value.
	 * @return bool
	 */
	public static function is_valid_source( $source ) {
		return in_array( $source, self::ALLOWED_SOURCES, true );
	}

	/**
	 * Whether a user-type value is part of the allowed enum.
	 *
	 * @param string $user_type User type value.
	 * @return bool
	 */
	public static function is_valid_user_type( $user_type ) {
		return in_array( $user_type, self::ALLOWED_USER_TYPES, true );
	}
}
