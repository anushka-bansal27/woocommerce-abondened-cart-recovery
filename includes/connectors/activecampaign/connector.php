<?php
/**
 * AC insert and update file
 *
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Connectors/ActiveCampaign
 */

/**
 * Class for ActiveCapmaign Connector
 */
class Wcap_Activecampaign extends Wcap_Connector {
	/**
	 * Connector Name
	 *
	 * @var $connector_name
	 */
	public $connector_name = 'activecampaign';
	/**
	 * Slug Name
	 *
	 * @var $slug
	 */
	public $slug = 'wcap_activecampaign';
	/**
	 * Name
	 *
	 * @var $name
	 */
	public $name = 'ActiveCampaign';
	/**
	 * Description
	 *
	 * @var $desc
	 */
	public $desc = 'Send emails and abandoned carts collected from the plugin to ActiveCampaign.';
	/**
	 * Signle instance of the class
	 *
	 * @var $ins
	 */
	private static $ins = null;
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
	 * Array containing values for default connection
	 *
	 *  @var array All calls with object
	 */
	public $default_connection = array(
		'name'    => 'Abandoned Cart Pro',
		'service' => 'Abandoned Cart Pro',
		'logoUrl' => 'https://live.tychesoftwares.com/wp-content/uploads/2020/05/mascot-cart-1.png',
	);
	/**
	 * Construct. Add hooks and filters.
	 *
	 * @var array All calls with object.
	 */
	public function __construct() {
		$this->wcap_define_plugin_properties();
		$this->connector_url = WCAP_ACTIVECAMPAIGN_PLUGIN_URL;
		add_filter( 'wcap_connectors_loaded', array( $this, 'add_card' ) );
		add_action( 'wp_ajax_wcap_get_activecampaign_connections', array( $this, 'wcap_get_activecampaign_connections' ) );
		add_action( 'wp_ajax_wcap_get_existing_settings_activecampaign', array( &$this, 'wcap_get_existing_settings' ) );
		add_action( 'init', array( &$this, 'wcap_activecampaign_include_files' ) );
		// Add cart status in the Abandoned Orders tab.
		add_filter( 'wcap_abandoned_orders_table_data', array( &$this, 'wcap_add_ac_cart_status' ), 10, 1 );
		$this->wcap_register_calls();
		$this->default_connection['externalid'] = home_url();
		$this->default_connection['linkUrl']    = admin_url() . '/admin.php?page=woocommerce_ac_page&action=emailsettings&wcap_section=wcap_connectors';
	}

	/**
	 * Function to define constans
	 */
	public function wcap_define_plugin_properties() {
		if ( ! defined( 'WCAP_ACTIVECAMPAIGN_VERSION' ) ) {
			define( 'WCAP_ACTIVECAMPAIGN_VERSION', '1.0.0' );
		}
		if ( ! defined( 'WCAP_ACTIVECAMPAIGN_FULL_NAME' ) ) {
			define( 'WCAP_ACTIVECAMPAIGN_FULL_NAME', 'Abandoned Carts Automations Connectors: Activecampaign' );
		}
		if ( ! defined( 'WCAP_ACTIVECAMPAIGN_PLUGIN_FILE' ) ) {
			define( 'WCAP_ACTIVECAMPAIGN_PLUGIN_FILE', __FILE__ );
		}
		if ( ! defined( 'WCAP_ACTIVECAMPAIGN_PLUGIN_DIR' ) ) {
			define( 'WCAP_ACTIVECAMPAIGN_PLUGIN_DIR', __DIR__ );
		}
		if ( ! defined( 'WCAP_ACTIVECAMPAIGN_PLUGIN_URL' ) ) {
			define( 'WCAP_ACTIVECAMPAIGN_PLUGIN_URL', untrailingslashit( plugin_dir_url( WCAP_ACTIVECAMPAIGN_PLUGIN_FILE ) ) );
		}
		if ( ! defined( 'WCAP_ACTIVECAMPAIGN_PLUGIN_BASENAME' ) ) { 
			define( 'WCAP_ACTIVECAMPAIGN_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		}
	}

	/**
	 * Function to Add card in connector's main page.
	 *
	 * @var array $available_connectors - Avaialble connector for display in main connector page.
	 */
	public function add_card( $available_connectors ) {
		$available_connectors['wcap']['connectors']['wcap_activecampaign'] = array(
			'name'            => $this->name,
			'desc'            => __( $this->desc, 'woocommerce-ac' ), //phpcs:ignore
			'connector_class' => 'Wcap_Activecampaign',
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
		$resource_dir = WCAP_ACTIVECAMPAIGN_PLUGIN_DIR . '/calls';
		if ( file_exists( $resource_dir ) ) {
			foreach ( glob( $resource_dir . '/class-*.php' ) as $filename ) {
				$call_class = require_once( $filename );
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
	 * @var array $api_key - API key created in the settings, integration at ActiveCampaign.
	 */
	public static function set_headers( $api_key ) {
		$api_key;
		$headers = array(
			'Content-Type' => 'application/json',
			'Api-Token'    => $api_key,
		);

		self::$headers = $headers;
	}

	/**
	 * Function to include files
	 */
	public function wcap_activecampaign_include_files() {
		include_once WCAP_ACTIVECAMPAIGN_PLUGIN_DIR . '/includes/wcap-upsert-contact.php';
		include_once WCAP_ACTIVECAMPAIGN_PLUGIN_DIR . '/includes/wcap-upsert-cart.php';
	}

	/**
	 * Function to include files.
	 */
	public function wcap_get_activecampaign_connections() {
		$api_url = isset( $_POST['api_url'] ) ? sanitize_text_field( wp_unslash( $_POST['api_url' ] ) ) : ''; // phpcs:ignore
		$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key' ] ) ) : ''; // phpcs:ignore

		if ( empty( $api_url ) ) {
			wp_send_json(
				array(
					'response' => __( 'API Url is not provided', 'woocommerce-ac' ),
				)
			);
		}

		if ( empty( $api_key ) ) {
			wp_send_json(
				array(
					'response' => __( 'API Key is not provided', 'woocommerce-ac' ),
				)
			);
		}

		$connections_result = $this->fetch_connections(
			array(
				'api_url' => $api_url,
				'api_key' => $api_key,
			)
		);
		if ( ! is_array( $connections_result ) || ! count( $connections_result ) > 0 ) {
			wp_send_json( array( 'status' => false ) );
		}

		wp_send_json( $connections_result );

		die();
	}

	/**
	 * Function to fetch connections available in ActiveCampaign.
	 *
	 * @var array $params - API key created in the settings, integration at ActiveCampaign.
	 * @var array $captured_items - Array of connections.
	 */
	public function fetch_connections( $params, $captured_items = array() ) {
		$call = $this->registered_calls['wcap_activecampaign_get_connections'];
		$call->set_data( $params );
		$result = $call->process();

		if ( 200 !== absint( $result['response'] ) ) {
			$error  = __( 'Error Response Code: ', 'woocommerce-ac' ) . $result['response'] . __( '. Activecampaign Error: ', 'woocommerce-ac' );
			$error .= is_array( $result['body'] ) && isset( $result['body']['detail'] ) ? $result['body']['detail'] : __( 'No Response from Activecampaign. ', 'woocommerce-ac' );
			$error .= ( 502 === absint( $result['response'] ) ) ? __( 'Autonami Error: ', 'woocommerce-ac' ) . $result['body'][0] : '';

			wp_send_json(
				array(
					'status'  => 'failed',
					'message' => $error,
				)
			);
		}

		$total_items_count = count( $result['body']['connections'] );
		if ( ! $total_items_count ) {
			$params = array_merge( $params, $this->default_connection );
			$call   = $this->registered_calls['wcap_activecampaign_add_default_connection'];
			$call->set_data( $params );
			$result  = $call->process();
			$data[0] = $result['body']['connection'];
		} else {
			$data = $result['body']['connections'];
		}
		foreach ( $data as $row ) {
			$id                    = $row['id'];
			$captured_items[ $id ] = $row['externalid'] . ' ( ' . $row['service'] . ' ) ';
		}

		$offset = '';
		if ( $total_items_count > count( $captured_items ) ) {
			$offset = count( $captured_items );
		}
		if ( ! empty( $offset ) ) {
			$params['offset'] = $offset;

			return $this->fetch_connections( $params, $captured_items );
		}

		return $captured_items;
	}


	/**
	 * Sync Carts manually.
	 */
	public function wcap_sync_manually() {

		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( 'wcap_activecampaign' );
		$activated_time     = isset( $connector_settings['activated'] ) ? $connector_settings['activated'] : '';
		$status             = isset( $connector_settings['status'] ) ? $connector_settings['status'] : '';

		global $wpdb;
		// Get the list of carts which have not yet been synced.
		$sync_query = 'SELECT wcap.id FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` as wcap LEFT JOIN `' . $wpdb->prefix . 'ac_connector_sync` as csync ON (wcap.id=csync.cart_id AND connector_name="activecampaign" ) where ( connector_cart_id IS NULL OR connector_cart_id =""  ) 
		 AND wcap.user_id > 0 AND wcap.abandoned_cart_time > "' . $activated_time . '"';

		$cart_list = $wpdb->get_col( // phpcs:ignore
			$sync_query //phpcs:ignore
		);
		if ( is_array( $cart_list ) && count( $cart_list ) > 0 ) { // Start the process one at a time.
			$sync_count = 0;
			$cart_count = count( $cart_list );
			foreach ( $cart_list as $cart_id ) {				
				$cart_sync_status = $this->wcap_sync_single_cart( $cart_id );
				if ( $cart_sync_status ) { // failed, catch the data and move on.
					$sync_count++;
				}
			}

			if ( $sync_count === $cart_count ) {
				$status  = 'success';
				$message = sprintf(
					/* translators: %1$s,%2$s are replaced with number of carts synced and number of total carts respectively */
					__( '%1$d of %2$d carts were synced successfully.', 'woocommerce-ac' ), //phpcs:ignore
					esc_html( $sync_count ),
					esc_html( $cart_count )
				);
			} else {
				$status = 'error';
				if ( 0 === $sync_count ) {
					$message = sprintf(
						// translators: %1$s,%2$s are replaced with number of carts synced and number of total carts respectively.
						__( '%1$d of %2$d carts were synced. Please check for network connectivity issues and try again.', 'woocommerce-ac' ), //phpcs:ignore
						esc_html( $sync_count ),
						esc_html( $cart_count )
					);
				} else {
					$message = sprintf(
						// translators: %1$s,%2$s are replaced with number of carts synced and number of total carts respectively.
						__( '%1$d of %2$d carts were synced successfully. Please try again to sync the remaining carts.', 'woocommerce-ac' ), //phpcs:ignore
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

	/**
	 * Sync individual cart.
	 *
	 * @param int $cart_id - Cart ID.
	 * @return bool true | false - success | failure.
	 * @since 8.16.0
	 */
	public function wcap_sync_single_cart( $cart_id = 0 ) {
		if ( $cart_id > 0 ) {
			if ( ! WC()->session ) {
				WC()->initialize_session();
			}
			wcap_set_cart_session( 'ecom_order_id', '' );
			wcap_set_cart_session( 'activecampaign_customer_details', '' );
			$wcap_ct_instance = Wcap_Activecampaign_Upsert_Contact_Action::get_instance();
			$wcap_add_contact = $wcap_ct_instance->wcap_prepare_contact( $cart_id );
			$wcap_add_cart    = Wcap_Activecampaign_Upsert_Cart_Action::get_instance();
			$res              = $wcap_add_cart->wcap_prepare_cart_details( $cart_id, true );
			if ( 'complete' === $res ) { // failed, catch the data and move on.
				return true;
			}
		}
		return false;
	}

	/**
	 * Get existing Activecampaign settings.
	 */
	public function wcap_get_existing_settings() {
		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( 'wcap_activecampaign' );
		$api_url            = isset( $connector_settings['api_url'] ) ? $connector_settings['api_url'] : '';
		$api_key            = isset( $connector_settings['api_key'] ) ? $connector_settings['api_key'] : '';
		$connection_id      = isset( $connector_settings['default_connection'] ) ? $connector_settings['default_connection'] : '';
		if ( empty( $api_key ) ) {
			wp_send_json(
				array(
					'response' => __( 'API Key is not provided', 'woocommerce-ac' )
				)
			);
		}
		if ( empty( $api_url ) ) {
			wp_send_json(
				array(
					'response' => __( 'API URL is not provided', 'woocommerce-ac' )
				)
			);
		}
		$connections_result = $this->fetch_connections(
			array(
				'api_url' => $api_url,
				'api_key' => $api_key,
			)
		);

		if ( is_array( $connections_result ) && count( $connections_result ) > 0 ) {
			wp_send_json(
				array(
					'status'        => 'success',
					'connections'   => $connections_result,
					'connection_id' => $connection_id,
				)
			);
		} else {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => __( 'We were unable to connect to your store. Please disconnect and try connecting again.', 'woocommerce-ac' ),
				)
			);
		}
		die();
	}

	/**
	 * Display Cart Sync status.
	 *
	 * @param object $wcap_abandoned_order - Abandoned Orders data.
	 */
	public function wcap_add_ac_cart_status( $wcap_abandoned_orders ) {

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
							$wcap_abandoned_orders[ $k ]->connector_status .= "<span><img src='" . WCAP_ACTIVECAMPAIGN_PLUGIN_URL . "/views/icon.png' class='wcap-wpf-success wcap-success wcap-connector-status' /></span>";
						} else {
							$wcap_abandoned_orders[ $k ]->connector_status .= "<span><img src='" . WCAP_ACTIVECAMPAIGN_PLUGIN_URL . "/views/icon.png' class='wcap-wpf-failed wcap-failed wcap-connector-status' /></span>";
						}
					}
					break;
				}
			}
		}
		return $wcap_abandoned_orders;
	}
}
