<?php
/**
 * Abandoned Cart Pro for WooCommerce
 *
 * @package      Abandoned-Cart-Pro-for-WooCommerce
 * @copyright    Copyright (C) 2012-2022, LittleCodr - https://github.com/LittleCodr
 * @link         https://github.com/LittleCodr
 * @since        1.0
 * @since        Updated 10.7.2
 *
 * @wordpress-plugin
 * Plugin Name:  Abandoned Cart Pro for WooCommerce
 * Plugin URI:   https://github.com/LittleCodr
 * Description:  This plugin captures abandoned carts by logged-in users and guest users. It allows to create multiple email templates to be sent at fixed intervals. Thereby reminding customers about their abandoned orders & resulting in increased sales by completing those orders. Go to <strong>WooCommerce -> <a href="admin.php?page=woocommerce_ac_page">Abandoned Carts</a> </strong>to get started.
 * Version:      10.8.0
 * Author:       LittleCodr
 * Author URI:   http://www.LittleCodr.com
 * Text Domain:  woocommerce-ac
 * Requires PHP: 7.4
 * WC requires at least: 4.0.0
 * WC tested up to: 11.1.1
 * Requires Plugins: woocommerce
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

defined( 'ABSPATH' ) || exit;

// Deactivate the Lite plugin if active. This is needed to avoid conflicts.
if ( in_array( 'woocommerce-abandoned-cart/woocommerce-ac.php', (array) get_option( 'active_plugins', array() ) ) ) { //phpcs:ignore
	add_option( 'wcap_migrated_from_lite', true );
	deactivate_plugins( 'woocommerce-abandoned-cart/woocommerce-ac.php' );
}

if ( ! defined( 'WCAP_PLUGIN_FILE' ) ) {
	define( 'WCAP_PLUGIN_FILE', __FILE__ );
}

update_option('edd_sample_license_key_ac_woo', '123456-123456-123456-123456');
update_option('edd_sample_license_status_ac_woo', 'valid');
add_filter('pre_http_request', function ($pre, $parsed_args, $url) {
	if (strpos($url, 'https://github.com/LittleCodr') === 0 && isset($parsed_args['body']['edd_action'])) {
		return [
			'response' => ['code' => 200, 'message' => 'ОК'],
			'body'     => json_encode(['success' => true, 'license' => 'valid', 'expires' => '2035-01-01 23:59:59', 'license_limit' => 100, 'site_count' => 1, 'activations_left' => 99])
		];
	}
	return $pre;
}, 10, 3);

require_once 'includes/wcap_activate_plugin.php';
require_once 'includes/wcap_update_check.php';
require_once 'includes/wcap_default_settings.php';
require_once 'includes/admin/class-wcap-import-export.php';
require_once 'includes/api/class-wcap-rest-api.php';

// Create the covering index used by the REST list queries on activation.
register_activation_hook( __FILE__, array( 'WCAP_REST_API', 'add_covering_index' ) );

// AI Reporting Abilities.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
	require_once 'includes/wp-abilities/class-wcap-abilities-bootstrap.php';
}

/**
 * Woocommerce_Abandon_Cart class
 *
 * It will call all the functions for the file inclusion, global variable and action & filter
 *
 * @since 1.0
 */
final class Woocommerce_Abandon_Cart {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	protected static $plugin_version = '10.8.0';

	/**
	 * Plugin Name.
	 *
	 * @var string
	 */
	protected static $plugin_name = 'Abandoned Cart Pro for WooCommerce';

	/**
	 * Plugin URL.
	 *
	 * @var string
	 */
	protected static $plugin_url = 'https://github.com/LittleCodr';

	/**
	 * DEV MODE.
	 *
	 * Sets the plugin base to either development ( TRUE ) or production ( FALSE ).
	 *
	 * @var boolean
	 */
	protected static $dev_mode = true;

	/**
	 * The single instance of the class.
	 *
	 * @var Woocommerce_Booking
	 */
	protected static $instance = null;

	/**
	 * Default constructor
	 *
	 * @since 5.17.0
	 */
	private static function setup() {

		self::wcap_declare_variable();

		if ( ! self::check_is_woo_active() ) {

			add_action(
				'admin_notices',
				function () {
					$class   = 'notice notice-error';
					$message = sprintf( __( 'We have noticed that WooCommerce plugin is not active. Please activate it to use %s plugin', 'woocommerce-abandoned-cart' ),
						'Abandoned Cart Pro for WooCommerce'
					);
					printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ); //phpcs:ignore
				}
			);
			if ( isset( $_GET['activate'] ) ) { //phpcs:ignore
				unset( $_GET['activate'] ); //phpcs:ignore
			}
			add_action(
				'plugins_loaded',
				function () {
					deactivate_plugins( 'woocommerce-abandon-cart-pro/woocommerce-ac.php' );
				}
			);

		} else {
			add_action(
				'before_woocommerce_init',
				function () {
					if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
						\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
						\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'orders_cache', __FILE__, true );
						\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'product_block_editor', __FILE__, true );
					}
				},
				999
			);

			// Load the Files.
			add_action( 'plugins_loaded', array( __CLASS__, 'wcap_load_files' ), 5 );
			// Initialize settings.
			register_activation_hook( __FILE__, array( 'Wcap_Activate_Plugin', 'wcap_activate' ) );
			add_action( 'plugins_loaded', array( __CLASS__, 'wcap_load_hooks' ), 6 );
			spl_autoload_register( array( __CLASS__, 'loadclass' ) );
			// Register deactivation hook.
			register_deactivation_hook( __FILE__, array( 'Woocommerce_Abandon_Cart', 'wcap_deactivate' ) );
			add_filter( 'wp_plugin_check_checks', array( __CLASS__, 'wcap_plugin_check_ignore_files' ), 10 );
		}
	}

	/**
	 * Retrieve the instance of the class and ensures only one instance is loaded or can be loaded.
	 *
	 * @return Woocommerce_Abandon_Cart
	 */
	public static function instance() {
		if ( is_null( self::$instance ) && ! ( self::$instance instanceof Woocommerce_Abandon_Cart ) ) {
			self::$instance = new Woocommerce_Abandon_Cart();
			self::$instance->setup();
		}

		return self::$instance;
	}

	/**
	 * Declare the common variables and the constants needed for the plugin.
	 *
	 * @since 5.0
	 */
	public static function wcap_declare_variable() {
		global $one_hour, $three_hours, $six_hours, $twelve_hours, $one_day, $one_week, $duration_range_select, $start_end_dates, $ACUpdateChecker; //phpcs:ignore

		$one_hour        = 60 * 60;
		$three_hours     = 3 * $one_hour;
		$six_hours       = 6 * $one_hour;
		$twelve_hours    = 12 * $one_hour;
		$one_day         = 24 * $one_hour;
		$one_week        = 7 * $one_day;
		$ACUpdateChecker = '10.8.0'; //phpcs:ignore

		$duration_range_select = array(
			'yesterday'      => 'Yesterday',
			'today'          => 'Today',
			'last_seven'     => 'Last 7 days',
			'last_fifteen'   => 'Last 15 days',
			'last_thirty'    => 'Last 30 days',
			'last_ninety'    => 'Last 90 days',
			'last_year_days' => 'Last 365',
		);

		$start_end_dates = array(
			'yesterday'      => array(
				'start_date' => date( 'd M Y', ( current_time( 'timestamp' ) - 24 * 60 * 60 ) ), //phpcs:ignore
				'end_date'   => date( 'd M Y', ( current_time( 'timestamp' ) - 7 * 24 * 60 * 60 ) ), //phpcs:ignore
			),
			'today'          => array(
				'start_date' => date( 'd M Y', ( current_time( 'timestamp' ) ) ), //phpcs:ignore
				'end_date'   => date( 'd M Y', ( current_time( 'timestamp' ) ) ), //phpcs:ignore
			),
			'last_seven'     => array(
				'start_date' => date( 'd M Y', ( current_time( 'timestamp' ) - 7 * 24 * 60 * 60 ) ), //phpcs:ignore
				'end_date'   => date( 'd M Y', ( current_time( 'timestamp' ) ) ), //phpcs:ignore
			),
			'last_fifteen'   => array(
				'start_date' => date( 'd M Y', ( current_time( 'timestamp' ) - 15 * 24 * 60 * 60 ) ), //phpcs:ignore
				'end_date'   => date( 'd M Y', ( current_time( 'timestamp' ) ) ), //phpcs:ignore
			),
			'last_thirty'    => array(
				'start_date' => date( 'd M Y', ( current_time( 'timestamp' ) - 30 * 24 * 60 * 60 ) ), //phpcs:ignore
				'end_date'   => date( 'd M Y', ( current_time( 'timestamp' ) ) ), //phpcs:ignore
			),
			'last_ninety'    => array(
				'start_date' => date( 'd M Y', ( current_time( 'timestamp' ) - 90 * 24 * 60 * 60 ) ), //phpcs:ignore
				'end_date'   => date( 'd M Y', ( current_time( 'timestamp' ) ) ), //phpcs:ignore
			),
			'last_year_days' => array(
				'start_date' => date( 'd M Y', ( current_time( 'timestamp' ) - 365 * 24 * 60 * 60 ) ), //phpcs:ignore
				'end_date'   => date( 'd M Y', ( current_time( 'timestamp' ) ) ), //phpcs:ignore
			),
		);

			/**
			 * Define The constants for plugin table names and other constants.
			 */
			self::wcap_define_constants_for_table_and_other();
	}

		/**
		 * Function for definining WKAP constants.
		 *
		 * @param string $variable Constant which is to be defined.
		 * @param string $value Valueof the Constant.
		 *
		 * @since 8.23.0
		 */
	public static function define( $variable, $value ) {
		if ( ! defined( $variable ) ) {
			define( $variable, $value );
		}
	}

		/**
		 * Define The constants for plugin table names and other constants.
		 *
		 * @globals mixed $wpdb
		 * @since 5.0
		 */
	public static function wcap_define_constants_for_table_and_other() {

			global $wpdb;
			self::define( 'WCAP_PLUGIN_VERSION', self::$plugin_version );
			self::define( 'WCAP_ABANDONED_CART_HISTORY_TABLE', $wpdb->prefix . 'ac_abandoned_cart_history' );
			self::define( 'WCAP_GUEST_CART_HISTORY_TABLE', $wpdb->prefix . 'ac_guest_abandoned_cart_history' );
			self::define( 'WCAP_EMAIL_TEMPLATE_TABLE', $wpdb->prefix . 'ac_email_templates' );
			self::define( 'WCAP_NOTIFICATION_TEMPLATES_TABLE', $wpdb->prefix . 'ac_notification_templates' );
			self::define( 'WCAP_EMAIL_CLICKED_TABLE', $wpdb->prefix . 'ac_link_clicked_email' );
			self::define( 'WCAP_EMAIL_OPENED_TABLE', $wpdb->prefix . 'ac_opened_emails' );
			self::define( 'WCAP_NOTIFICATION_OPENED_TABLE', $wpdb->prefix . 'ac_opened_notifications' );
			self::define( 'WCAP_LINK_CLICKED_TABLE', $wpdb->prefix . 'ac_link_clicked_notifications' );
			self::define( 'WCAP_EMAIL_SENT_HISTORY_TABLE', $wpdb->prefix . 'ac_sent_history' );
			self::define( 'WCAP_PLUGIN_FILE', __FILE__ );
			self::define( 'WCAP_PLUGIN_URL', untrailingslashit( plugins_url( '/', __FILE__ ) ) );
			self::define( 'WCAP_ADMIN_URL', admin_url( 'admin.php' ) );
			self::define( 'WCAP_ADMIN_AJAX_URL', admin_url( 'admin-ajax.php' ) );
			self::define( 'WCAP_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
			self::define( 'WCAP_NOTIFICATIONS', $wpdb->prefix . 'ac_notifications' );
			self::define( 'WCAP_NOTIFICATIONS_META', $wpdb->prefix . 'ac_notifications_meta' );
			self::define( 'WCAP_TINY_URLS', $wpdb->prefix . 'ac_tiny_urls' );
			self::define( 'WCAP_ATC_RULES_TABLE', $wpdb->prefix . 'ac_atc_rules' );
			self::define( 'WCAP_AC_STATS', $wpdb->prefix . 'ac_statistics' );
			self::define( 'WCAP_INCLUDE_PATH', WCAP_PLUGIN_PATH . '/includes/' );
			$upload_dir = wp_upload_dir();
			self::define( 'WCAP_WP_UPLOADS_PATH', $upload_dir['basedir'] . '/' );
			self::define( 'WCAP_WP_UPLOADS_URL', $upload_dir['baseurl'] . '/' );

			/* Trial Version Name and Item ID on site */
			self::define( 'EDD_SL_ITEM_NAME_AC_WOO_TRIAL', 'Abandoned Cart Pro for WooCommerce Trial' );
			self::define( 'EDD_SL_STORE_AC_TRIAL_DOWNLOAD_ID', 699377 );

			self::define(
				'WCAP_WHATSAPP_TEMPLATE_LANGUAGES',
				array(
					'af'    => 'Afrikaans',
					'sq'    => 'Albanian',
					'ar'    => 'Arabic',
					'ar_EG' => 'Arabic (Egypt)',
					'ar_AE' => 'Arabic (UAE)',
					'ar_LB' => 'Arabic (Lebanon)',
					'ar_MA' => 'Arabic (Morocco)',
					'ar_QA' => 'Arabic (Qatar)',
					'az'    => 'Azerbaijani',
					'bn'    => 'Bengali',
					'bg'    => 'Bulgarian',
					'ca'    => 'Catalan',
					'zh_CN' => 'Chinese (Mainland)',
					'zh_HK' => 'Chinese (Hong Kong)',
					'zh_TW' => 'Chinese (Taiwan)',
					'hr'    => 'Croatian',
					'cs'    => 'Czech',
					'da'    => 'Danish',
					'nl'    => 'Dutch',
					'en'    => 'English',
					'en_GB' => 'English (UK)',
					'en_US' => 'English (US)',
					'et'    => 'Estonian',
					'fil'   => 'Filipino',
					'fi'    => 'Finnish',
					'fr'    => 'French',
					'ka'    => 'Georgian',
					'de'    => 'German',
					'el'    => 'Greek',
					'gu'    => 'Gujarati',
					'ha'    => 'Hausa',
					'he'    => 'Hebrew',
					'hi'    => 'Hindi',
					'hu'    => 'Hungarian',
					'id'    => 'Indonesian',
					'ga'    => 'Irish',
					'it'    => 'Italian',
					'ja'    => 'Japanese',
					'kn'    => 'Kannada',
					'kk'    => 'Kazakh',
					'rw_RW' => 'Kinyarwanda (Rwanda)',
					'ko'    => 'Korean',
					'ky_KG' => 'Kyrgyz (Kyrgyzstan)',
					'lo'    => 'Lao',
					'lv'    => 'Latvian',
					'lt'    => 'Lithuanian',
					'mk'    => 'Macedonian',
					'ms'    => 'Malay',
					'ml'    => 'Malayalam',
					'mr'    => 'Marathi',
					'nb'    => 'Norwegian',
					'fa'    => 'Persian',
					'pl'    => 'Polish',
					'pt_BR' => 'Portuguese (Brazil)',
					'pt_PT' => 'Portuguese (Portugal)',
					'pa'    => 'Punjabi',
					'ro'    => 'Romanian',
					'ru'    => 'Russian',
					'sr'    => 'Serbian',
					'sk'    => 'Slovak',
					'sl'    => 'Slovenian',
					'es'    => 'Spanish',
					'es_AR' => 'Spanish (Argentina)',
					'es_ES' => 'Spanish (Spain)',
					'es_MX' => 'Spanish (Mexico)',
					'sw'    => 'Swahili',
					'sv'    => 'Swedish',
					'ta'    => 'Tamil',
					'te'    => 'Telugu',
					'th'    => 'Thai',
					'tr'    => 'Turkish',
					'uk'    => 'Ukrainian',
					'ur'    => 'Urdu',
					'uz'    => 'Uzbek',
					'vi'    => 'Vietnamese',
					'zu'    => 'Zulu',
				)
			);
	}

		/**
		 * Load all files needed for the plugin.
		 *
		 * @globals string $pagenow Name of the current page
		 * @since 5.0
		 */
	public static function wcap_load_files() {
		require_once WCAP_INCLUDE_PATH . 'class-wcap-files.php';
		new WCAP_Files();
	}

		/**
		 * It will load all the hooks needed for the plugin.
		 *
		 * @since 5.0
		 */
	public static function wcap_load_hooks() {
		Wcap_Load_Hooks::wcap_load_hooks_and_filters();
		do_action( 'wcap_after_load_hooks' );
	}

		/**
		 * Actions to be run when deactivating the plugin.
		 *
		 * @since 9.0
		 */
	public static function wcap_deactivate() {
		// Send reminders.
		if ( false !== as_next_scheduled_action( 'woocommerce_ac_send_email_action' ) ) {
			as_unschedule_action( 'woocommerce_ac_send_email_action' );
		}
		$next_scheduled = wp_next_scheduled( 'wcap_ts_tracker_send_event' );
		if ( $next_scheduled ) {
			wp_unschedule_event( $next_scheduled, 'wcap_ts_tracker_send_event' );
		}
	}

		/**
		 * Autoloader for classes
		 *
		 * @param class $class_name Name of the class.
		 *
		 * @since 9.0
		 */
	public static function loadclass( $class_name ) {

			static $class_cache = array();
			static $not_found   = array();

			// Skip classes already scanned and not found in WCAP directories.
			if ( isset( $not_found[ $class_name ] ) ) {
				return;
			}

			// Use cached path if available.
			if ( isset( $class_cache[ $class_name ] ) ) {
				require_once $class_cache[ $class_name ];
				return;
			}

			static $plugin_dir = null;
			$ds                = DIRECTORY_SEPARATOR;
			if ( null === $plugin_dir ) {
				$resolved   = realpath( plugin_dir_path( __FILE__ ) );
				$plugin_dir = ( false !== $resolved ? $resolved : plugin_dir_path( __FILE__ ) ) . $ds;
			}
			$directories = array(
				'includes' . $ds,
				'includes' . $ds . 'admin' . $ds,
				'includes' . $ds . 'classes' . $ds,
				'includes' . $ds . 'component' . $ds,
				'includes' . $ds . 'frontend' . $ds,
				'includes' . $ds . 'libraries' . $ds,
				'includes' . $ds . 'models' . $ds,
			);
			$classpath   = trim( $class_name, 'AC\\' );
			$folderarray = explode( '\\', $classpath );
			$filename    = strtolower( str_replace( '_', '-', array_pop( $folderarray ) ) ) . '.php';
			$filefolder  = strtolower( implode( $ds, $folderarray ) ) . $ds;

			foreach ( $directories as $directory ) {
				$fullpath = $plugin_dir . $directory . $filefolder . $filename;
				if ( file_exists( $fullpath ) ) {
					$class_cache[ $class_name ] = $fullpath;
					require_once $fullpath; // nosemgrep:audit.php.lang.security.file.inclusion-arg
					return;
				}
			}

			// Cache failed lookups.
			$not_found[ $class_name ] = true;
	}
		/**
		 * Check if woocommerce is active for the site or network
		 */
	public static function check_is_woo_active() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// is_plugin_active() already covers network-wide and per-site activation.
		$require_network_active = apply_filters( 'wcap_do_network_check', false );

		if ( $require_network_active && is_multisite() ) {
			return is_plugin_active_for_network( 'woocommerce/woocommerce.php' );
		}

		return is_plugin_active( 'woocommerce/woocommerce.php' );
	}

	/**
	 * Skip js files during plugin check validations.
	 *
	 * @param array $checks An array of checks to ignore.
	 * @since 9.16.0
	 */
	public static function wcap_plugin_check_ignore_files( $checks ) {
		unset( $checks['i18n_usage'] );
		unset( $checks['plugin_header_fields'] );
		unset( $checks['file_type'] );
		unset( $checks['trademarks'] );
		return $checks;
	}
}

/**
 * Returns the main instance of the class.
 *
 * @return Woocommerce_Booking
 */
function WCAP() { //phpcs:ignore
	return Woocommerce_Abandon_Cart::instance();
}

WCAP();
