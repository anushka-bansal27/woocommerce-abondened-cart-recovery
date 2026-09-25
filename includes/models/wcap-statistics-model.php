<?php
/**
 * cart Statistics Model class 
 *
 * @author   Tyche Softwares
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Database-Layer
 * @since 8.15
 */
//namespace AC\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCAP_STATISTICS_MODEL' ) ) {

	/**
	 * Database access layer for performing DB related activities
	 */
	class WCAP_STATISTICS_MODEL extends WCAP_BASE_MODEL {


		/**
		 * Short name of the tablename, used for hooks.
		 *
		 * @var string $table_short.
		 */
		public static $table_short = 'cart_statistics';

		/**
		 * Table Constant name.
		 *
		 * @var instance single instance.
		 */
		public const TABLE_COSNT = WCAP_AC_STATS;

		/**
		 * Insert event record in wp_ac_statistics.
		 *
		 * @param int    $template_id - Popup Template ID.
		 * @param string $template_type - Popup Template Type.
		 * @param string $event - Event (0...5).
		 *
		 * @since 8.14.0
		 */
		public static function insert( $data = array() ) {
			
			$data['timestamp'] = current_time( 'timestamp' );
			$insert_id = parent::insert( $data );//phpcs:disable
			return $insert_id;			
		}

		/**
		 * Return count of events for given template ID across time.
		 *
		 * @param int    $template_id - Popup Template ID.
		 * @param string $event - Event ID (o... 5).
		 * @return int   $count - Count of the event records present.
		 *
		 * @since 8.14.0
		 */
		public static function wcap_get_stats_for_event( $template_id, $event ) {
			global $wpdb;
			$cnt = $wpdb->get_var( // phpcs:ignore
				$wpdb->prepare(
					'SELECT count(id) FROM ' . static::TABLE_COSNT . ' WHERE event=%s AND template_id = %d',
					$event,
					$template_id
				)
			);
			return $cnt;
		}

		/**
		 * Fetch count of event across all popup templates in a time range.
		 *
		 * @param int    $template_id - Popup Template ID.
		 * @param string $start - Start timestamp.
		 * @param string $end - End timestamp.
		 * @return int   $count - Count of events.
		 *
		 * @since 8.14.0
		 */
		public static function wcap_get_stats_for_daterange( $event, $start, $end ) {
			global $wpdb;
			$count = $wpdb->get_var( // phpcs:ignore
				$wpdb->prepare(
					'SELECT count(id) FROM ' . static::TABLE_COSNT . ' WHERE event = %s AND timestamp >= %d AND timestamp <= %d',
					$event,
					$start,
					$end
				)
			);
			return $count;
		}
	}
}
