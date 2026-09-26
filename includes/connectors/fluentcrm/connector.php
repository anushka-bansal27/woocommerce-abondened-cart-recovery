<?php
/**
 * FluentCRM connector class
 *
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Connectors/fluentcrm
 */

/**
 * Class for FluenctCRM Connector
 */
class Wcap_Fluentcrm extends Wcap_Connector {
	/**
	 * Connector Name
	 *
	 * @var $connector_name
	 */
	public $connector_name = 'fluentcrm';
	/**
	 * Slug Name
	 *
	 * @var $slug
	 */
	public $slug = 'wcap_fluentcrm';
	/**
	 * Name
	 *
	 * @var $name
	 */
	public $name = 'Fluentcrm';
	/**
	 * Description
	 *
	 * @var $desc
	 */
	public $desc = 'Send emails and abandoned carts collected from the plugin to Fluentcrm.';
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
	 * Array of events
	 *
	 *  @var array All calls with object
	 */
	public $events = array( 'Created Cart', 'Modifed Cart', 'Ignored Cart', 'Recovered Cart', 'Order Placed' );
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

		if ( strstr( $_SERVER['REQUEST_URI'], 'wp-json' ) ) { //phpcs:ignore
			return;
		}
		$this->wcap_define_plugin_properties();
		$this->connector_url = WCAP_FLUENTCRM_PLUGIN_URL;
		add_filter( 'wcap_connectors_loaded', array( $this, 'add_card' ) );
		add_action( 'wp_ajax_wcap_get_fluentcrm_lists', array( $this, 'wcap_get_fluentcrm_lists' ) );
		add_action( 'wp_ajax_wcap_get_existing_settings_fluentcrm', array( &$this, 'wcap_get_existing_settings' ) );
		add_action( 'init', array( &$this, 'wcap_fluentcrm_include_files' ) );
		// Add cart status in the Abandoned Orders tab.
		add_filter( 'wcap_abandoned_orders_table_data', array( &$this, 'wcap_add_fluentcrm_cart_status' ), 10, 1 );
		$this->wcap_register_calls();
	}

	/**
	 * Function to define constans
	 */
	public function wcap_define_plugin_properties() {
		if ( ! defined( 'WCAP_FLUENTCRM_VERSION' ) ) {
			define( 'WCAP_FLUENTCRM_VERSION', '1.0.0' );
		}
		if ( ! defined( 'WCAP_FLUENTCRM_FULL_NAME' ) ) {
			define( 'WCAP_FLUENTCRM_FULL_NAME', 'Abandoned Carts Automations Connectors: FLUENTCRM' );
		}
		if ( ! defined( 'WCAP_FLUENTCRM_PLUGIN_FILE' ) ) {
			define( 'WCAP_FLUENTCRM_PLUGIN_FILE', __FILE__ );
		}
		if ( ! defined( 'WCAP_FLUENTCRM_PLUGIN_DIR' ) ) {
			define( 'WCAP_FLUENTCRM_PLUGIN_DIR', __DIR__ );
		}
		if ( ! defined( 'WCAP_FLUENTCRM_PLUGIN_URL' ) ) {
			define( 'WCAP_FLUENTCRM_PLUGIN_URL', untrailingslashit( plugin_dir_url( WCAP_FLUENTCRM_PLUGIN_FILE ) ) );
		}
		if ( ! defined( 'WCAP_FLUENTCRM_PLUGIN_BASENAME' ) ) {
			define( 'WCAP_FLUENTCRM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		}
	}

	/**
	 * Function to Add card in connector's main page.
	 *
	 * @param array $available_connectors - Avaialble connector for display in main connector page.
	 */
	public function add_card( $available_connectors ) {
		$available_connectors['wcap']['connectors']['wcap_fluentcrm'] = array(
			'name'            => $this->name,
			'desc'            => __( $this->desc, 'woocommerce-ac' ), //phpcs:ignore
			'connector_class' => 'Wcap_Fluentcrm',
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
		$resource_dir = WCAP_FLUENTCRM_PLUGIN_DIR . '/calls';
		if ( file_exists( $resource_dir ) ) {
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
	 * @param string $api_name - API NAme created in the settings, integration at fluentcrm.
	 * @param string $api_key  - API key created in the settings, integration at fluentcrm.
	 */
	public static function set_headers( $api_name, $api_key ) {
		$headers = array(
			'Content-Type'  => 'application/json',
			'Authorization' => ' Basic ' . base64_encode( $api_name . ':' . $api_key ), //phpcs:ignore
		);

		self::$headers = $headers;
	}

	/**
	 * Function to include files
	 */
	public function wcap_fluentcrm_include_files() {
		include_once WCAP_FLUENTCRM_PLUGIN_DIR . '/includes/wcap-upsert-contact.php';
		include_once WCAP_FLUENTCRM_PLUGIN_DIR . '/includes/wcap-upsert-cart.php';
	}

	/**
	 * Function to include files.
	 */
	public function wcap_get_fluentcrm_lists() {
		$api_name = isset( $_POST['api_name'] ) ? sanitize_text_field( wp_unslash( $_POST['api_name' ] ) ) : ''; // phpcs:ignore
		$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key' ] ) ) : ''; // phpcs:ignore

		if ( empty( $api_name ) ) {
			wp_send_json(
				array(
					'response' => __( 'API Name is not provided', 'woocommerce-ac' ),
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

		$lists_result = $this->fetch_lists(
			array(
				'api_name' => $api_name,
				'api_key'  => $api_key,
			)
		);

		$this->wcap_add_custom_fields( $api_name, $api_key );

		$this->wcap_add_default_events( $api_name, $api_key );

		if ( ! is_array( $lists_result ) || ! count( $lists_result ) > 0 ) {
			wp_send_json( array( 'status' => false ) );
		}

		wp_send_json( $lists_result );

		die();
	}

	/**
	 * Function to fetch lists available in fluentcrm.
	 *
	 * @param array $params - API key created in the settings, integration at fluentcrm.
	 * @param array $captured_items - Array of lists.
	 */
	public function fetch_lists( $params, $captured_items = array() ) {
		$call = $this->registered_calls['wcap_fluentcrm_get_lists'];
		$call->set_data( $params );
		$result = $call->process();

		if ( 200 !== absint( $result['response'] ) ) {
			$error  = __( 'Error Response Code: ', 'woocommerce-ac' ) . $result['response'] . __( '. Fluentcrm Error: ', 'woocommerce-ac' );
			$error .= is_array( $result['body'] ) && isset( $result['body']['detail'] ) ? $result['body']['detail'] : __( 'No Response from Fluentcrm. ', 'woocommerce-ac' );
			$error .= ( 502 === absint( $result['response'] ) ) ? __( 'WCAP Error: ', 'woocommerce-ac' ) . $result['body'][0] : '';

			wp_send_json(
				array(
					'status'  => 'failed',
					'message' => $error,
				)
			);
		}

		$total_items_count = count( $result['body']['lists'] );
		if ( ! $total_items_count ) {
			$call = $this->registered_calls['wcap_fluentcrm_add_default_list'];
			$call->set_data( $params );
			$result = $call->process();

			$call = $this->registered_calls['wcap_fluentcrm_get_lists'];
			$call->set_data( $params );
			$result = $call->process();
			$data   = $result['body'];

		} else {
			$data = $result['body'];
		}
		foreach ( $data['lists'] as $row ) {
			$id                    = $row['id'];
			$captured_items[ $id ] = $row['title'];
		}

		$captured_items;
		return $captured_items;
	}

	/**
	 * Sync Carts manually.
	 */
	public function wcap_sync_manually() {

		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( 'wcap_fluentcrm' );
		$activated_time     = isset( $connector_settings['activated'] ) ? $connector_settings['activated'] : '';

		global $wpdb;
		// Get the list of carts which have not yet been synced.
		$cart_list = $wpdb->get_col( // phpcs:ignore
			$wpdb->prepare( 'SELECT wcap.id FROM ' . WCAP_ABANDONED_CART_HISTORY_TABLE . ' as wcap LEFT JOIN ' . $wpdb->prefix . 'ac_connector_sync as csync ON (wcap.id=csync.cart_id AND connector_name="fluentcrm" ) where ( connector_cart_id IS NULL OR connector_cart_id =""  ) AND wcap.user_id > 0 AND wcap.abandoned_cart_time > %d', $activated_time ) // phpcs:ignore 
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
						/* translators: %1$s,%2$s are replaced with number of carts synced and number of total carts respectively */
						__( '%1$d of %2$d carts were synced. Please check for network connectivity issues and try again.', 'woocommerce-ac' ), //phpcs:ignore
						esc_html( $sync_count ),
						esc_html( $cart_count )
					);
				} else {
					$message = sprintf(
						/* translators: %1$s,%2$s,%3$s and %4$s is replaced with string */
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
			wcap_set_cart_session( 'wcap_fluentcrm_sync_cart_id', '' );
			wcap_set_cart_session( 'fluentcrm_customer_details', '' );
			$wcap_ct_instance = Wcap_Fluentcrm_Upsert_Contact_Action::get_instance();
			$wcap_add_contact = $wcap_ct_instance->wcap_prepare_contact( $cart_id );
			$wcap_add_cart    = Wcap_Fluentcrm_Upsert_Cart_Action::get_instance();
			$res              = $wcap_add_cart->wcap_prepare_cart_details( $cart_id, true );
			if ( 'complete' === $res ) { // failed, catch the data and move on.
				return true;
			}
		}
		return false;
	}

	/**
	 * Get existing Fluentcrm settings.
	 */
	public function wcap_get_existing_settings() {
		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( 'wcap_fluentcrm' );

		$api_name = isset( $connector_settings['api_name'] ) ? $connector_settings['api_name'] : '';
		$api_key  = isset( $connector_settings['api_key'] ) ? $connector_settings['api_key'] : '';
		$list_id  = isset( $connector_settings['list_id'] ) ? $connector_settings['list_id'] : '';

		if ( empty( $api_name ) ) {
			wp_send_json(
				array(
					'response' => __( 'API Name is not provided', 'woocommerce-ac' ),
				)
			);
		}
		if ( empty( $api_key ) ) {
			wp_send_json(
				array(
					'response' => __( 'API key is not provided', 'woocommerce-ac' ),
				)
			);
		}

		$lists_result = $this->fetch_lists(
			array(
				'api_name' => $api_name,
				'api_key'  => $api_key,
			)
		);

		if ( is_array( $lists_result ) && count( $lists_result ) > 0 ) {
			wp_send_json(
				array(
					'status'  => 'success',
					'lists'   => $lists_result,
					'list_id' => $list_id,
				)
			);
		} else {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => __( 'We were unable to fetch lists. Please disconnect and try connecting again.', 'woocommerce-ac' ),
				)
			);
		}
		die();
	}

	/**
	 * Display Cart Sync status.
	 *
	 * @param object $wcap_abandoned_orders - Abandoned Orders data.
	 */
	public function wcap_add_fluentcrm_cart_status( $wcap_abandoned_orders ) {

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
							$wcap_abandoned_orders[ $k ]->connector_status .= "<span><img src='" . WCAP_FLUENTCRM_PLUGIN_URL . "/views/icon.png' class='wcap-wpf-success wcap-success wcap-connector-status' /></span>";
						} else {
							$wcap_abandoned_orders[ $k ]->connector_status .= "<span><img src='" . WCAP_FLUENTCRM_PLUGIN_URL . "/views/icon.png' class='wcap-wpf-failed wcap-failed wcap-connector-status' /></span>";
						}
					}
					break;
				}
			}
		}
		return $wcap_abandoned_orders;
	}

	/**
	 * Function to add custom fields to fluentCRM.
	 *
	 * @param string $api_name - API Name.
	 * @param string $api_key  - API Key.
	 */
	public function wcap_add_custom_fields( $api_name, $api_key ) {

		$connector_mc = self::get_instance();
		$call         = $connector_mc->registered_calls['wcap_fluentcrm_add_custom_fields'];
		// Fetch the connector data.

		$params = array(
			'api_name' => $api_name,
			'api_key'  => $api_key,
		);

		$params['fields'] = array(
			array(
				'field_key' => 'text',
				'type'      => 'text',
				'label'     => 'Wcap Cart Id',
				'slug'      => 'wcap_cart_id',
			),
			array(
				'field_key' => 'text',
				'type'      => 'text',
				'label'     => 'Wcap CheckoutURL',
				'slug'      => 'wcap_checkouturl',
			),
			array(
				'field_key' => 'text',
				'type'      => 'text',
				'label'     => 'Wcap Currency',
				'slug'      => 'wcap_currency',
			),
			array(
				'field_key' => 'textarea',
				'type'      => 'textarea',
				'label'     => 'Wcap Cart Products',
				'slug'      => 'wcap_cart_products',
			),
			array(
				'field_key' => 'text',
				'type'      => 'text',
				'label'     => 'Wcap Cart Total',
				'slug'      => 'wcap_cart_total',
			),
			array(
				'field_key' => 'text',
				'type'      => 'text',
				'label'     => 'Wcap user Id',
				'slug'      => 'wcap_user_id',
			),
			array(
				'field_key' => 'text',
				'type'      => 'text',
				'label'     => 'Wcap Abandoned Date',
				'slug'      => 'wcap_abandoned_date',
			),
			array(
				'field_key' => 'text',
				'type'      => 'text',
				'label'     => 'Wcap Captured by',
				'slug'      => 'wcap_captured_by',
			),
			array(
				'field_key' => 'text',
				'type'      => 'text',
				'label'     => 'Wcap Cart Subtotal',
				'slug'      => 'wcap_cart_subtotal',
			),
			array(
				'field_key' => 'text',
				'type'      => 'text',
				'label'     => 'Wcap Cart Discount',
				'slug'      => 'wcap_cart_discount',
			),
			array(
				'field_key' => 'text',
				'type'      => 'text',
				'label'     => 'Wcap Cart Shipping',
				'slug'      => 'wcap_cart_shipping',
			),
			array(
				'field_key' => 'text',
				'type'      => 'text',
				'label'     => 'Wcap Cart Total Before Tax',
				'slug'      => 'wcap_cart_total_before_tax',
			),
			array(
				'field_key' => 'text',
				'type'      => 'text',
				'label'     => 'Wcap Cart Tax',
				'slug'      => 'wcap_cart_tax',
			),
			array(
				'field_key' => 'text',
				'type'      => 'text',
				'label'     => 'Wcap Cart Total Products',
				'slug'      => 'wcap_cart_totalProducts',
			),
			array(
				'field_key' => 'textarea',
				'type'      => 'textarea',
				'label'     => 'Wcap Abandoned Cart Html',
				'slug'      => 'wcap_abandoned_cart_html',
			),
			array(
				'field_key' => 'text',
				'type'      => 'text',
				'label'     => 'Wcap Reason',
				'slug'      => 'wcap_reason',
			),
			array(
				'field_key' => 'text',
				'type'      => 'text',
				'label'     => 'Wcap Order Id',
				'slug'      => 'wcap_order_id',
			),
			array(
				'field_key' => 'text',
				'type'      => 'text',
				'label'     => 'Wcap New Email Id',
				'slug'      => 'wcap_new_email_id',
			),
			array(
				'field_key' => 'text',
				'type'      => 'text',
				'label'     => 'Wcap Old Email Id',
				'slug'      => 'wcap_old_email_id',
			),
			array(
				'field_key' => 'text',
				'type'      => 'text',
				'label'     => 'Wcap Cart Abandoned',
				'slug'      => 'wcap_car_abandoned',
			),
		);

		$call->set_data( $params );
		$result = $call->process();

	}

	/**
	 * Function to add default events to FluentCRM.
	 *
	 * @param string $api_name - API Name.
	 * @param string $api_key  - API Key.
	 */
	public function wcap_add_default_events( $api_name, $api_key ) {
		$connector_mc = self::get_instance();
		$call         = $connector_mc->registered_calls['wcap_fluentcrm_add_tag'];
				
		$params = array(
			'api_name' => $api_name,
			'api_key'  => $api_key,
		);

		$events = $connector_mc->events;
		foreach ( $events as $event ) {
			$params['title'] = $event;
			$params['slug']  = $this->wcap_get_slug( $event );
			$call->set_data( $params );
			$result = $call->process();
		}

	}
	/**
	 * Get slug for an event
	 *
	 *  @param string $event - event.
	 */
	public function wcap_get_slug( $event ) {
		return strtolower( str_replace( ' ', '-', $event ) );
	}

}


