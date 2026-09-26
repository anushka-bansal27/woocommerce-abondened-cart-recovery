<?php //phpcs:ignore
/**
 * AC insert and update file
 *
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Connectors/Salesforce
 */

/**
 * Class for Salesforce Connector
 */
class Wcap_Salesforce_Crm extends Wcap_Connector {

	/**
	 * Connector Name
	 *
	 * @var $connector_name
	 */
	public $connector_name = 'salesforce_crm';
	/**
	 * Slug Name
	 *
	 * @var $slug
	 */
	public $slug = 'wcap_salesforce_crm';
	/**
	 * Name
	 *
	 * @var $name
	 */
	public $name = 'Salesforce CRM';
	/**
	 * Description
	 *
	 * @var $desc
	 */
	public $desc = 'Exports emails and abandoned carts collected from the plugin to Salesforce CRM.';
	/**
	 * Signle instance of the class
	 *
	 * @var $ins
	 */
	private static $ins = null;
	/**
	 * API endpoint
	 *
	 * @var $api_end_point
	 */
	public static $api_end_point = 'https://<instance>.salesforce.com/services/data/vXX.X/sobjects/';
	/**
	 * Headers for Curl calls
	 *
	 * @var $headers
	 */
	public static $headers = null;

	/**
	 * Array of registered calls for function
	 *
	 *  @var array All calls with object
	 */
	public $registered_calls = array();

	/**
	 * Construct. Add hooks and filters.
	 *
	 * @var array All calls with object.
	 */
	public function __construct() {
		$this->wcap_define_plugin_properties();
		$this->connector_url = WCAP_SALESFORCE_PLUGIN_URL;
		add_filter( 'wcap_connectors_loaded', array( $this, 'add_card' ) );
		add_action( 'wp_loaded', array( &$this, 'wcap_salesforce_include_files' ), 9 );
		add_filter( 'wcap_before_connector_save_settings', array( &$this, 'wcap_verify_mc_settings' ), 10, 3 );
		// Add cart status in the Abandoned Orders tab.
		add_filter( 'wcap_abandoned_orders_table_data', array( &$this, 'wcap_add_mc_cart_status' ), 12, 1 );
		$this->wcap_register_calls();
	}

	/**
	 * Function to define constans
	 */
	public function wcap_define_plugin_properties() {
		if ( ! defined( 'WCAP_SALESFORCE_VERSION' ) ) {
			define( 'WCAP_SALESFORCE_VERSION', '1.0.0' );
		}
		if ( ! defined( 'WCAP_SALESFORCE_FULL_NAME' ) ) {
			define( 'WCAP_SALESFORCE_FULL_NAME', 'Abandoned Carts Automations Connectors: Salesforce CRM' );
		}
		if ( ! defined( 'WCAP_SALESFORCE_PLUGIN_FILE' ) ) {
			define( 'WCAP_SALESFORCE_PLUGIN_FILE', __FILE__ );
		}
		if ( ! defined( 'WCAP_SALESFORCE_PLUGIN_DIR' ) ) {
			define( 'WCAP_SALESFORCE_PLUGIN_DIR', __DIR__ );
		}
		if ( ! defined( 'WCAP_SALESFORCE_PLUGIN_URL' ) ) {
			define( 'WCAP_SALESFORCE_PLUGIN_URL', untrailingslashit( plugin_dir_url( WCAP_SALESFORCE_PLUGIN_FILE ) ) );
		}
		if ( ! defined( 'WCAP_SALESFORCE_PLUGIN_BASENAME' ) ) {
			define( 'WCAP_SALESFORCE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		}
	}

	/**
	 * Function to Add card in connector's main page.
	 *
	 * @param Array $available_connectors avilable connectors.
	 * @var array $available_connectors - Avaialble connector for display in main connector page.
	 */
	public function add_card( $available_connectors ) {
		$available_connectors['wcap']['connectors']['wcap_salesforce_crm'] = array(
			'name'            => $this->name,
			'desc'            => __( $this->desc, 'woocommerce-ac' ),//phpcs:ignore
			'connector_class' => 'Wcap_Salesforce_Crm',
			'image'           => $this->get_image(),
			'source'          => '',
			'file'            => '',
		);

		return $available_connectors;
	}
	/**
	 * Function to get instance.
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Function to register connector calls by pulling class files from the directory.
	 */
	public function wcap_register_calls() {
		$resource_dir = WCAP_SALESFORCE_PLUGIN_DIR . '/calls';
		if ( @file_exists( $resource_dir ) ) {//phpcs:ignore
			foreach ( glob( $resource_dir . '/class-*.php' ) as $filename ) {
				$call_class = require_once $filename;
				if ( ( is_object( $call_class ) || is_string( $call_class ) ) && method_exists( $call_class, 'get_instance' ) ) {
					$call_obj                                       = $call_class::get_instance();
					$this->registered_calls[ $call_obj->call_slug ] = $call_obj;
				}
			}
		}
		do_action( 'wcap_' . $this->get_slug() . '_actions_loaded' );

	}

	/**
	 * Function to get headers for curl/http calls.
	 */
	public static function get_headers() {
		return self::$headers;
	}

	/**
	 * Function to set headers for curl/http calls.
	 *
	 * @param array $access_token - Accesstoken created by the connection, integration at Salesforce.
	 */
	public static function set_headers( $access_token ) {
		$headers = array(
			'Authorization' => 'Bearer ' . $access_token,
			'Content-Type'  => 'application/json',
		);

		self::$headers = $headers;
	}

	/**
	 * Function to get endpoints of the url.
	 *
	 * @param array $data_center - Replaced tag value.
	 * @param array $version - Version number.
	 */
	public static function get_endpoint( $data_center, $version = '3.0' ) {
		return str_replace( '<instance>', $data_center, self::$api_end_point );
	}

	/**
	 * Function to set headers for curl/http calls.
	 *
	 * @param array $api_key - Api key.
	 */
	public static function get_data_center( $api_key ) {
		if ( empty( $api_key ) || false === strpos( $api_key, 'salesforce' ) ) {
			return false;
		}

		return $api_key;
	}
	/**
	 * Function to include files.
	 */
	public function wcap_salesforce_include_files() {
		include_once WCAP_SALESFORCE_PLUGIN_DIR . '/includes/wcap-upsert-lead.php';
		include_once WCAP_SALESFORCE_PLUGIN_DIR . '/includes/wcap-salesforce-crm-connection.php';
	}

	/**
	 * Sync Carts manually.
	 */
	public function wcap_sync_manually() {

		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( $this->slug );
		$activated_time     = isset( $connector_settings['activated'] ) ? $connector_settings['activated'] : '';
		$connector_status   = isset( $connector_settings['status'] ) ? $connector_settings['status'] : '';

		if ( 'active' === $connector_status && '' !== $activated_time ) {
			global $wpdb;
			// Get the list of carts which have not yet been synced.
			$cart_list = $wpdb->get_col( // phpcs:ignore
				'SELECT wcap.id FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` as wcap LEFT JOIN `' . $wpdb->prefix . 'ac_connector_sync` as csync ON wcap.id=csync.cart_id where wcap.user_id > 0 AND wcap.abandoned_cart_time > "' . $activated_time . '" AND ( csync.cart_id IS NULL OR csync.status = "failed" ) AND connector_name = "salesforce_crm"' );// phpcs:ignore
			if ( is_array( $cart_list ) && count( $cart_list ) > 0 ) { // Start the process one at a time.
				$sync_count = 0;
				$cart_count = count( $cart_list );
				foreach ( $cart_list as $cart_id ) {
					$sync_status = $this->wcap_sync_single_cart( $cart_id );
					if ( $sync_status ) { // failed, catch the data and move on.
						$sync_count++;
					}
				}

				if ( $sync_count === $cart_count ) {
					$status  = 'success';
					$message = sprintf(
						/* translators: %1$s,%2$s are replaced with number of carts synced and number of total carts respectively */
						__( '%1$d of %2$d carts were synced successfully.', 'woocommerce-ac' ),
						esc_html( $sync_count ),
						esc_html( $cart_count )
					);
				} else {
					$status = 'error';
					if ( 0 === $sync_count ) {
						$message = sprintf(
							/* translators: %1$s,%2$s are replaced with number of carts synced and number of total carts respectively */
							__( '%1$d of %2$d carts were synced. Please check for network connectivity issues and try again.', 'woocommerce-ac' ),
							esc_html( $sync_count ),
							esc_html( $cart_count )
						);
					} else {
						$message = sprintf(
							/* translators: %1$s,%2$s are replaced with number of carts synced and number of total carts respectively */
							__( '%1$d of %2$d carts were synced successfully. Please try again to sync the remaining carts.', 'woocommerce-ac' ),
							esc_html( $sync_count ),
							esc_html( $cart_count )
						);
					}
				}
				wp_send_json(
					array(
						'status'  => $status,
						'message' => $message,
					)
				);

			} else { // If none found, send a message saying data has already been synced.
				wp_send_json(
					array(
						'status'  => 'success',
						'message' => __( 'All carts have been synced.', 'woocommerce-ac' ),
					)
				);
			}
		}
		wp_send_json(
			array(
				'status'  => 'error',
				'message' => __( 'We were unable to connect. Please try again later.', 'woocommerce-ac' ),
			)
		);
	}

	/**
	 * Sync individual cart.
	 *
	 * @param int $cart_id - Cart ID.
	 * @return bool true | false - success | failure.
	 * @since 8.16.0
	 */
	public function wcap_sync_single_cart( $cart_id = 0 ) {
		if ( $cart_id > 0 ) {
			$wcap_add_cart = Wcap_Salesforce_Upsert_Lead_Action::get_instance();
			$res           = $wcap_add_cart->wcap_prepare_cart_details( $cart_id, true );
			if ( 'complete' === $res ) { // failed, catch the data and move on.
				return true;
			}
		}
		return false;
	}

	/**
	 * Verify settings for salesforce before saving.
	 *
	 * @param array $result - Verification status.
	 * @param array $settings - MC Settings.
	 * @param str   $connector - Connector name.
	 */
	public function wcap_verify_mc_settings( $result, $settings, $connector ) {
		if ( '' !== $connector && 'salesforce_crm' === $connector ) {
			$client_id     = isset( $settings['client_id'] ) && '' !== $settings['client_id'] ? true : false;
			$client_secret = isset( $settings['client_secret'] ) && '' !== $settings['client_secret'] ? true : false;
			$redirect_uri  = isset( $settings['redirect_uri'] ) && '' !== $settings['redirect_uri'] ? true : false;

			if ( $client_id && $client_secret && $redirect_uri ) {
				$result['salesforce'] = array(
					'status'  => 'error',
					'message' => __( 'Connected successfully!', 'woocommerce-ac' ),
				);
			} else {
				$result['salesforce'] = array(
					'status'  => 'error',
					'message' => __( 'Some settings are blanks. Please fill all the fields.', 'woocommerce-ac' ),
				);
			}
		}
		return $result;
	}

	/**
	 * Display Cart Sync status.
	 *
	 * @param object $wcap_abandoned_orders - Abandoned Orders data.
	 */
	public function wcap_add_mc_cart_status( $wcap_abandoned_orders ) {
		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( $this->slug );
		$connector_status   = isset( $connector_settings['status'] ) ? $connector_settings['status'] : '';
		if ( 'active' === $connector_status ) {
			foreach ( $wcap_abandoned_orders as $k => $col_data ) {
				$cart_id = 0;
				foreach ( $col_data as $col_name => $col_value ) {
					$cart_id = 'id' === $col_name ? $col_value : $cart_id;
					if ( (int) $cart_id > 0 ) {
						$connector_common = Wcap_Connectors_Common::get_instance();
						$cart_status      = $connector_common->wcap_get_cart_sync_status( $cart_id, $this->connector_name );

						if ( $cart_status ) {
							$wcap_abandoned_orders[ $k ]->connector_status .= "<span><img src='" . WCAP_SALESFORCE_PLUGIN_URL . "/views/icon.png' class='wcap-wpf-success wcap-success wcap-connector-status' /></span>";
						} else {
							$wcap_abandoned_orders[ $k ]->connector_status .= "<span><img src='" . WCAP_SALESFORCE_PLUGIN_URL . "/views/icon.png' class='wcap-wpf-failed wcap-failed wcap-connector-status' /></span>";
						}
					}
					break;
				}
			}
		}
		return $wcap_abandoned_orders;
	}
}
