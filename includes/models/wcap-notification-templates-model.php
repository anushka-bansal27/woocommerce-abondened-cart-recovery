<?php
/**
 * Notification Templates Model class 
 *
 * @author   LittleCodr
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Database-Layer
 * @since 8.15
 */
//namespace AC\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCAP_NOTIFICATION_TEMPLATES_MODEL' ) ) {

	/**
	 * Database access layer for performing DB related activities
	 */
	class WCAP_NOTIFICATION_TEMPLATES_MODEL extends WCAP_BASE_MODEL {


		/**
		 * Short name of the tablename, used for hooks.
		 *
		 * @var string $table_short.
		 */
		public static $table_short = 'notification_templates';

		/**
		 * Table Constant name.
		 *
		 * @var instance single instance.
		 */
		public const TABLE_COSNT = WCAP_NOTIFICATION_TEMPLATES_TABLE;


		/**
		 * Function to insert notification templates in DB.
		 *
		 * @param array $data - Data for insert.
		 * @return int Insert ID.
		 * @globals mixed $wpdb Global Variable
		 *
		 * @since 8.19.0
		 *
		 */
		public static function insert(  $data = array()  ) {

			if ( ! isset( $data['notification_type'] ) ) {
				return false;
			}

			global $wpdb;

			$data = apply_filters( 'wcap_notification_templates_before_insert', $data );

			$insert_id = parent::insert( $data );

			do_action( 'wcap_notification_templates_after_insert', $insert_id );

			return $insert_id;
		}
	
	}
}
