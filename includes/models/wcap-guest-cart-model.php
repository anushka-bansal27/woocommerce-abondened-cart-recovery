<?php
/**
 * Guest cart Model class 
 *
 * @author   LittleCodr
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Database-Layer
 * @since 8.15
 */
//namespace AC\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCAP_GUEST_CART_MODEL' ) ) {

	/**
	 * Database access layer for performing DB related activities
	 */
	class WCAP_GUEST_CART_MODEL extends WCAP_BASE_MODEL {


		/**
		 * Short name of the tablename, used for hooks.
		 *
		 * @var string $table_short.
		 */
		public static $table_short = 'guest_cart_history';

		/**
		 * Table Constant name.
		 *
		 * @var instance single instance.
		 */
		public const TABLE_COSNT = WCAP_GUEST_CART_HISTORY_TABLE;

	}
}
