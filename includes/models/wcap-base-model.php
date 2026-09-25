<?php
/**
 * Base Model class for database manipulation
 *
 * @author   Tyche Softwares
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Database-Layer
 * @since 8.15
 */

//namespace AC\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCAP_BASE_MODEL' ) ) {

	/**
	 * Database access layer for performing DB related activities
	 */
	class WCAP_BASE_MODEL {

		/**
		 * Single Instance of the class.
		 *
		 * @var instance single instance.
		 */
		public static $instance = null;

		/**
		 * Short name of the tablename, used for hooks.
		 *
		 * @var string $table_short.
		 */
		public static $table_short = '';

		/**
		 * Table Constant name.
		 *
		 * @var TABLE_COSNT table constant name.
		 */
		public const TABLE_COSNT = '';

		/**
		 * Class Construct
		 */
		public function __construct() {

		}

		/**
		 * Insert record
		 *
		 * @param array $data values to be inserted.
		 */
		public static function insert( $data = array() ) {
			global $wpdb;

			if ( ! static::validate_data( $data ) ) {
				return false;
			}

			$data = apply_filters( 'wcap_' . static::$table_short . '_before_insert', $data );

			$wpdb->insert(  // phpcs:ignore
				static::TABLE_COSNT,
				$data
			);

			$insert_id = $wpdb->insert_id;

			do_action( 'wcap_' . static::$table_short . '_after_insert', $insert_id, $data );

			return $insert_id;
		}

		/**
		 * Delete Abandoned Cart Record
		 *
		 * @param array $where Key => Value pair to be deleted.
		 */
		public static function delete( $where = array() ) {
			// return without updating if no condition provided.
			if ( empty( $where ) ) {
				return false;
			}
			global $wpdb;

			do_action( 'wcap_before_delete_' . static::$table_short, $where );
			$return_val = $wpdb->delete( static::TABLE_COSNT, $where ); // phpcs:ignore
			do_action( 'wcap_after_delete_' . static::$table_short, $where );

			return $return_val;

		}

		/**
		 * Update  Record
		 *
		 * @param array $value Key => Value pair to be updated.
		 *
		 * @param array $where key => value condition.
		 */
		public static function update( $value = array(), $where = array() ) {
			// return without updating if no condition provided.
			if ( empty( $where ) ) {
				return false;
			}

			global $wpdb;

			do_action( 'wcap_before_update_' . static::$table_short, $value, $where );
			$return_val = $wpdb->update( static::TABLE_COSNT, $value , $where); // phpcs:ignore
			do_action( 'wcap_after_update_' . static::$table_short, $value, $where );

			return $return_val;

		}

		/**
		 * Validate record
		 *
		 * @param array $data data to be validated.
		 */
		public static function validate_data( $data = array() ) {
			return true;
		}
	}

}


