<?php
/**
 * Bootstrap for the Abandoned Cart Pro REST API.
 *
 * Loads the API classes (the plugin autoloader does not scan includes/api/),
 * registers every route under the existing wcap/v1 namespace on
 * rest_api_init, and ensures the covering index exists on upgrade. (BRD 2.4.)
 *
 * @package WooCommerce Abandoned Cart Pro
 * @since   10.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCAP_REST_API class.
 */
class WCAP_REST_API {

	/**
	 * Index schema version. Bump to trigger a re-check on upgrade.
	 *
	 * @var string
	 */
	const INDEX_VERSION = '1';

	/**
	 * Registered controller instances.
	 *
	 * @var WCAP_REST_Controller_Base[]
	 */
	protected $controllers = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->load_dependencies();
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'plugins_loaded', array( $this, 'maybe_add_index' ), 20 );
	}

	/**
	 * Require the API classes in dependency order.
	 *
	 * @return void
	 */
	protected function load_dependencies() {

		$dir = trailingslashit( __DIR__ );

		require_once $dir . 'class-wcap-rest-status-helper.php';
		require_once $dir . 'class-wcap-cart-schema.php';
		require_once $dir . 'class-wcap-rate-limiter.php';
		require_once $dir . 'class-wcap-rest-controller-base.php';
		require_once $dir . 'class-wcap-rest-abandoned-carts-controller.php';
		require_once $dir . 'class-wcap-rest-recovered-carts-controller.php';
		require_once $dir . 'class-wcap-rest-reminders-controller.php';
		require_once $dir . 'class-wcap-rest-product-report-controller.php';
	}

	/**
	 * Instantiate the controllers and register their routes.
	 *
	 * @return void
	 */
	public function register_routes() {

		// Guard: WooCommerce must be present (these endpoints expose WC data
		// and authenticate via WC's REST key mechanism).
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$this->controllers = array(
			new WCAP_REST_Abandoned_Carts_Controller(),
			new WCAP_REST_Recovered_Carts_Controller(),
			new WCAP_REST_Reminders_Controller(),
			new WCAP_REST_Product_Report_Controller(),
		);

		foreach ( $this->controllers as $controller ) {
			$controller->register_routes();
		}

		/**
		 * Fires after the WCAP REST routes are registered.
		 *
		 * @param WCAP_REST_API $this Bootstrap instance.
		 * @since 10.5.0
		 */
		do_action( 'wcap_rest_routes_registered', $this );
	}

	/**
	 * Add the covering index used by the list queries, once per index version.
	 *
	 * Index: (cart_ignored, recovered_cart, abandoned_cart_time) on the
	 * history table. Existing installs pick this up on upgrade; new installs
	 * get it through the activation routine as well. (BRD 2.4.3.)
	 *
	 * @return void
	 */
	public function maybe_add_index() {

		if ( get_option( 'wcap_rest_index_version' ) === self::INDEX_VERSION ) {
			return;
		}

		self::add_covering_index();

		update_option( 'wcap_rest_index_version', self::INDEX_VERSION, false );
	}

	/**
	 * Create the covering index if it does not already exist.
	 *
	 * Safe to call repeatedly: checks information_schema first.
	 *
	 * @return void
	 */
	public static function add_covering_index() {

		global $wpdb;

		if ( ! defined( 'WCAP_ABANDONED_CART_HISTORY_TABLE' ) ) {
			return;
		}

		$table      = WCAP_ABANDONED_CART_HISTORY_TABLE;
		$index_name = 'wcap_status_time_idx';

		$exists = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare(
				'SELECT COUNT(1) FROM information_schema.statistics WHERE table_schema = %s AND table_name = %s AND index_name = %s',
				DB_NAME,
				$table,
				$index_name
			)
		);

		if ( $exists ) {
			return;
		}

		$wpdb->query( "ALTER TABLE `{$table}` ADD INDEX `{$index_name}` ( cart_ignored, recovered_cart, abandoned_cart_time )" ); //phpcs:ignore
	}
}

new WCAP_REST_API();
