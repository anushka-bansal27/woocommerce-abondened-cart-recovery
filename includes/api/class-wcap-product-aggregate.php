<?php
/**
 * Per-product abandon/recover aggregate, shared by the REST product-report
 * endpoint and the AI Reporting Abilities (top-abandoned-products,
 * recovery-rate-ranking).
 *
 * Extracted from WCAP_REST_Product_Report_Controller::build_report() so the
 * chunked scan of ac_abandoned_cart_history exists in exactly one place.
 *
 * @package WooCommerce Abandoned Cart Pro
 * @since   10.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCAP_Product_Aggregate class.
 */
class WCAP_Product_Aggregate {

	/**
	 * Rows read per chunk while scanning the history table.
	 *
	 * @var int
	 */
	const SCAN_CHUNK = 2000;

	/**
	 * Aggregate cache TTL in seconds.
	 *
	 * @var int
	 */
	const CACHE_TTL = 300;

	/**
	 * Build (or fetch from cache) the per-product aggregate.
	 *
	 * @param int $start Start Unix timestamp (0 = no lower bound).
	 * @param int $end   End Unix timestamp (0 = no upper bound).
	 * @return array Each entry: product_id, variation_id, name, times_abandoned, times_recovered, recovery_rate_pct.
	 * @since 10.6.0
	 */
	public static function build_report( $start, $end ) {

		$cache_key = 'wcap_prodreport_' . md5( $start . ':' . $end );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		global $wpdb;
		$table = WCAP_ABANDONED_CART_HISTORY_TABLE;

		$where = array( "wcap_trash = ''" );
		$args  = array();
		if ( $start > 0 ) {
			$where[] = 'abandoned_cart_time >= %d';
			$args[]  = $start;
		}
		if ( $end > 0 ) {
			$where[] = 'abandoned_cart_time <= %d';
			$args[]  = $end;
		}
		$where_sql = implode( ' AND ', $where );

		$tally  = array(); // key => [product_id, variation_id, name, times_abandoned, times_recovered].
		$offset = 0;

		do {
			$sql        = "SELECT id, abandoned_cart_info, cart_ignored, recovered_cart FROM `{$table}` WHERE {$where_sql} ORDER BY id ASC LIMIT %d OFFSET %d";
			$chunk_args = array_merge( $args, array( self::SCAN_CHUNK, $offset ) );
			$rows       = $wpdb->get_results( $wpdb->prepare( $sql, $chunk_args ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared

			if ( ! is_array( $rows ) || 0 === count( $rows ) ) {
				break;
			}

			foreach ( $rows as $row ) {
				$is_recovered = ( '1' === (string) $row->cart_ignored && (int) $row->recovered_cart > 0 );
				$products     = self::parse_products( $row->abandoned_cart_info, (int) $row->id );

				$seen = array();
				foreach ( $products as $p ) {
					$key = $p['product_id'] . ':' . $p['variation_id'];

					// Count each distinct product once per cart.
					if ( isset( $seen[ $key ] ) ) {
						continue;
					}
					$seen[ $key ] = true;

					if ( ! isset( $tally[ $key ] ) ) {
						$tally[ $key ] = array(
							'product_id'      => $p['product_id'],
							'variation_id'    => $p['variation_id'],
							'name'            => $p['name'],
							'times_abandoned' => 0,
							'times_recovered' => 0,
						);
					}

					++$tally[ $key ]['times_abandoned'];
					if ( $is_recovered ) {
						++$tally[ $key ]['times_recovered'];
					}
				}
			}

			$offset += self::SCAN_CHUNK;
		} while ( count( $rows ) === self::SCAN_CHUNK );//phpcs:ignore

		// Finalise: add recovery_rate_pct.
		$report = array();
		foreach ( $tally as $entry ) {
			$rate                       = $entry['times_abandoned'] > 0 ? round( ( $entry['times_recovered'] / $entry['times_abandoned'] ) * 100, 2 ) : 0;
			$entry['recovery_rate_pct'] = $rate;
			$report[]                   = $entry;
		}

		set_transient( $cache_key, $report, self::CACHE_TTL );

		return $report;
	}

	/**
	 * Parse the product line items from a cart JSON blob, guarding malformed data.
	 *
	 * @param string $cart_info Raw abandoned_cart_info.
	 * @param int    $cart_id   Cart id (for logging).
	 * @return array
	 * @since 10.6.0
	 */
	protected static function parse_products( $cart_info, $cart_id ) {

		if ( '' === trim( (string) $cart_info ) ) {
			return array();
		}

		$decoded = json_decode( stripslashes( (string) $cart_info ) );
		if ( null === $decoded && JSON_ERROR_NONE !== json_last_error() ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->warning(
					sprintf( 'product-aggregate: malformed cart #%d', $cart_id ),
					array( 'source' => 'abandoned-cart-pro' )
				);
			}
			return array();
		}

		$out = array();
		if ( isset( $decoded->cart ) && is_object( $decoded->cart ) ) {
			foreach ( $decoded->cart as $item ) {
				$product_id   = isset( $item->product_id ) ? absint( $item->product_id ) : 0;
				$variation_id = isset( $item->variation_id ) ? absint( $item->variation_id ) : 0;
				if ( 0 === $product_id ) {
					continue;
				}
				$lookup_id = $variation_id > 0 ? $variation_id : $product_id;
				$out[]     = array(
					'product_id'   => $product_id,
					'variation_id' => $variation_id,
					'name'         => wp_strip_all_tags( (string) get_the_title( $lookup_id ) ),
				);
			}
		}

		return $out;
	}
}
