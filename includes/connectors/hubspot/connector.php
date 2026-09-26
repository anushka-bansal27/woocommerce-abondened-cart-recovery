<?php

class Wcap_Hubspot extends Wcap_Connector {

	public $connector_name       = 'hubspot';
    public $slug                 = 'wcap_hubspot';
    public $name                 = 'HubSpot';
    public $desc                 = 'Send emails and abandoned carts collected from the plugin to HubSpot.';
	private static $ins          = null;
	public static $api_end_point = 'https://api.hubapi.com/';
	public static $headers       = null;
	public $property_grp_id      = null;
	public $properties           = null;
	public $lists                = null;
	public $workflows            = null;

	/** @var array All calls with object */
	public $registered_calls = array();

	/**
	 * Construct.
	 */
	public function __construct() {
		$this->wcap_define_plugin_properties();
		$this->connector_url = WCAP_HUBSPOT_PLUGIN_URL;
		add_filter( 'wcap_connectors_loaded', array( $this, 'add_card' ) );
		add_filter( 'wcap_before_connector_save_settings', array( &$this, 'wcap_hubspot_connect_api' ), 10, 3 );
		add_action( 'wcap_after_connector_save_settings', array( $this, 'wcap_hubspot_add_defaults' ), 10, 2 );
		add_action( 'wp_loaded', array( &$this, 'wcap_hubspot_include_files' ), 10 ); 
		// Add cart status in the Abandoned Orders tab.
		add_filter( 'wcap_abandoned_orders_table_data', array( &$this, 'wcap_add_hb_cart_status' ), 11, 1 );
		$this->wcap_register_calls();
	}

	public function wcap_define_plugin_properties() {
		if ( ! defined( 'WCAP_HUBSPOT_VERSION' ) ) {
			define( 'WCAP_HUBSPOT_VERSION', '1.0.0' );
		}
		if ( ! defined( 'WCAP_HUBSPOT_FULL_NAME' ) ) {
			define( 'WCAP_HUBSPOT_FULL_NAME', 'Abandoned Carts Automations Connectors: Hubspot' );
		}
		if ( ! defined( 'WCAP_HUBSPOT_PLUGIN_FILE' ) ) {
			define( 'WCAP_HUBSPOT_PLUGIN_FILE', __FILE__ );
		}
		if ( ! defined( 'WCAP_HUBSPOT_PLUGIN_DIR' ) ) {
			define( 'WCAP_HUBSPOT_PLUGIN_DIR', __DIR__ );
		}
		if ( ! defined( 'WCAP_HUBSPOT_PLUGIN_URL' ) ) {
			define( 'WCAP_HUBSPOT_PLUGIN_URL', untrailingslashit( plugin_dir_url( WCAP_HUBSPOT_PLUGIN_FILE ) ) );
		}
		if ( ! defined( 'WCAP_HUBSPOT_PLUGIN_BASENAME' ) ) { 
			define( 'WCAP_HUBSPOT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		}
	}

	public function add_card( $available_connectors ) {
		$available_connectors['wcap']['connectors']['wcap_hubspot'] = array(
			'name'            => $this->name,
			'desc'            => __( $this->desc, 'woocommerce-ac' ),//phpcs:ignore
			'connector_class' => 'Wcap_Hubspot',
			'image'           => $this->get_image(),
			'source'          => '',
			'file'            => '',
		);

		return $available_connectors;
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function wcap_register_calls() {
		$resource_dir = WCAP_HUBSPOT_PLUGIN_DIR . '/calls';
		if ( @file_exists( $resource_dir ) ) {
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

	public static function get_headers() {
		return self::$headers;
	}

	public static function set_headers( $api_key ) {
		$headers = array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $api_key,
		);

		self::$headers = $headers;
	}

	public static function get_endpoint( $object_type, $version = '1.0' ) {
		return self::$api_end_point . $object_type . '/' . $version . '/';
	}

	public function wcap_hubspot_include_files() {
		include_once WCAP_HUBSPOT_PLUGIN_DIR . '/includes/wcap-create-defaults.php';
		include_once WCAP_HUBSPOT_PLUGIN_DIR . '/includes/wcap-upsert-contact.php';
		include_once WCAP_HUBSPOT_PLUGIN_DIR . '/includes/wcap-unenroll-contact.php';
	}

	public function wcap_hubspot_connect_api( $result, $settings, $connector ) {
		if ( '' !== $connector && 'hubspot' === $connector ) {
			$create_new = false;
			// If the settings are already present, please return.
			$old_settings = get_option( 'wcap_' . $connector . '_connector', false );
			if ( ! $old_settings ) { // No settings.
				$create_new = true;
			} else {
				$old_data = json_decode( $old_settings, true );
				if ( isset( $old_data['api_key'] ) && $old_data['api_key'] != $settings['api_key'] ) {
					$create_new = true;
				}
			}
			if ( $create_new ) {
				$defaults = Wcap_Hubspot_Create_Default_Setup_Action::get_instance();
				$response = $defaults->wcap_create_default_setup( $settings );

				$count_success = 0;
				foreach ( $response as $type => $details ) {

					switch ( $type ) {
						case 'property_grp':
							$property_grp_id = $details['grp_id'];
							break;
						case 'properties':
							$properties = 'success' === $details['status'] ? true : false;
							break;
						case 'list':
							$lists = $details['list_id'];
							break;
						case 'workflow':
							$workflows = $details['workflow_id'];
							break;
					}
					if ( 'success' === $details['status'] ) {
						$count_success++;
					}
				}

				$this->property_grp_id = isset( $property_grp_id ) ? $property_grp_id : 0;
				$this->properties      = isset( $properties ) ? $properties : '';
				$this->lists           = isset( $lists ) ? $lists : '';
				$this->workflows       = isset( $workflows ) ? $workflows : 0;

				if ( 4 == $count_success ) {
					$result['hubspot'] = array(
						'status'  => 'success',
						'message' => __( 'Groups, properties, lists & workflows have been created successfully.', 'woocommerce-ac' )
					);
				} else {
					$result['hubspot'] = array(
						'status'  => 'error',
						'message' => __( 'We were unable to create the default setup. Please try again.', 'woocommerce-ac' )
					);
				}
			} else {
				$this->property_grp_id = isset( $old_data['prp_grp_created'] ) ? $old_data['prp_grp_created'] : '';
				$this->properties      = isset( $old_data['prp_created'] ) ? $old_data['prp_created'] : '';
				$this->lists           = isset( $old_data['list_created'] ) ? $old_data['list_created'] : '';
				$this->workflows       = isset( $old_data['workflow_id'] ) ? $old_data['workflow_id'] : 0;
				$result['hubspot']     = array(
					'status' => 'success',
					'message' => __( 'Groups, properties, lists & workflows are already present in Hubspot.', 'woocommerce-ac' )
				);
			}
		}
		return $result;
	}

	public function wcap_hubspot_add_defaults( $settings, $connector ) {
		if ( '' !== $connector && 'hubspot' === $connector ) {

			$settings_dec = json_decode( get_option( 'wcap_' . $connector . '_connector' ), true );
			$settings_dec['prp_grp_created'] = $this->property_grp_id;
			$settings_dec['prp_created'] = $this->properties;
			$settings_dec['list_created'] = $this->lists;
			$settings_dec['workflow_id'] = $this->workflows;
			update_option( 'wcap_' . $connector . '_connector', wp_json_encode( $settings_dec ) );
		}
	}

	/**
	 * Sync Carts manually.
	 */
	public function wcap_sync_manually() {

		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( 'wcap_hubspot' );
		$activated_time     = isset( $connector_settings['activated'] ) ? $connector_settings['activated'] : '';
		$connector_status   = isset( $connector_settings['status'] ) ? $connector_settings['status'] : '';

		if ( 'active' === $connector_status && '' != $activated_time ) {
			global $wpdb;
			// Get the list of carts which have not yet been synced.
			$cart_list = $wpdb->get_col( // phpcs:disable
				'SELECT wcap.id FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` as wcap LEFT JOIN `' . $wpdb->prefix . 'ac_connector_sync` as csync ON wcap.id=csync.cart_id where wcap.user_id > 0 AND wcap.abandoned_cart_time > "' . $activated_time . '" AND ( csync.cart_id IS NULL OR csync.status = "failed" ) AND connector_name = "hubspot"'
			);// phpcs:enable
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
						/* translators: %1$s,%2$s are replaced with number of carts synced and number of total carts respectively*/
						__( '%1$d of %2$d carts were synced successfully.', 'woocommerce-ac' ),
						esc_html( $sync_count ),
						esc_html( $cart_count )
					);
				} else {
					$status = 'error';
					if ( 0 == $sync_count ) {
						$message = sprintf(
							/* translators: %1$s,%2$s are replaced with number of carts synced and number of total carts respectively*/
							__( '%1$d of %2$d carts were synced. Please check for network connectivity issues and try again.', 'woocommerce-ac' ),
							esc_html( $sync_count ),
							esc_html( $cart_count )
						);
					} else {
						$message = sprintf(
							/* translators: %1$s,%2$s are replaced with number of carts synced and number of total carts respectively*/
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
			$wcap_add_cart = Wcap_Hubspot_Upsert_Contact_Action::get_instance();
			$res           = $wcap_add_cart->wcap_prepare_contact( $cart_id, true );
			if ( 'complete' === $res ) { // failed, catch the data and move on.
				return true;
			}
		}
		return false;
	}

	/**
	 * Display Cart Sync status.
	 *
	 * @param object $wcap_abandoned_orders - Abandoned Orders data.
	 */
	public function wcap_add_hb_cart_status( $wcap_abandoned_orders ) {
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
							$wcap_abandoned_orders[ $k ]->connector_status .= "<span><img src='" . WCAP_HUBSPOT_PLUGIN_URL . "/views/icon.png' class='wcap-wpf-success wcap-success wcap-connector-status' /></span>";
						} else {
							$wcap_abandoned_orders[ $k ]->connector_status .= "<span><img src='" . WCAP_HUBSPOT_PLUGIN_URL . "/views/icon.png' class='wcap-wpf-failed wcap-failed wcap-connector-status' /></span>";
						}
					}
					break;
				}
			}
		}
		return $wcap_abandoned_orders;
	}
}
