<?php

class Wcap_Mailchimp extends Wcap_Connector {

	public $connector_name       = 'mailchimp';
    public $slug                 = 'wcap_mailchimp';
    public $name                 = 'Mailchimp';
    public $desc                 = 'Send emails and abandoned carts collected from the plugin to Mailchimp.';
	private static $ins          = null;
	public static $api_end_point = 'https://<dc>.api.mailchimp.com/';
	public static $headers       = null;
	
	/** @var array All calls with object */
	public $registered_calls = array();

    public function __construct() {
        $this->wcap_define_plugin_properties();
        $this->connector_url = WCAP_MAILCHIMP_PLUGIN_URL;
        add_filter( 'wcap_connectors_loaded', array( $this, 'add_card' ) );
//		add_action( 'admin_init', array( &$this, 'wcap_mc_ajax_loads' ) );
		add_action( 'wp_ajax_wcap_get_mailchimp_lists', array( $this, 'wcap_get_mailchimp_lists' ) );
		add_action( 'wp_ajax_wcap_get_mailchimp_stores', array( $this, 'wcap_get_mailchimp_stores' ) );
		add_action( 'wp_ajax_wcap_get_existing_settings', array( &$this, 'wcap_get_existing_settings' ) );
		add_action( 'wp_loaded', array( &$this, 'wcap_mailchimp_include_files' ), 9 );
		add_filter( 'wcap_before_connector_save_settings', array( &$this, 'wcap_verify_mc_settings' ), 10, 3 );
		// Add cart status in the Abandoned Orders tab.
		add_filter( 'wcap_abandoned_orders_table_data', array( &$this, 'wcap_add_mc_cart_status' ), 12, 1 );
		$this->wcap_register_calls();
    }

	public function wcap_define_plugin_properties() {
		if ( ! defined( 'WCAP_MAILCHIMP_VERSION' ) ) {
        	define( 'WCAP_MAILCHIMP_VERSION', '1.0.0' );
		}
		if ( ! defined( 'WCAP_MAILCHIMP_FULL_NAME' ) ) {
			define( 'WCAP_MAILCHIMP_FULL_NAME', 'Abandoned Carts Automations Connectors: Mailchimp' );
		}
		if ( ! defined( 'WCAP_MAILCHIMP_PLUGIN_FILE' ) ) {
			define( 'WCAP_MAILCHIMP_PLUGIN_FILE', __FILE__ );
		}
		if ( ! defined( 'WCAP_MAILCHIMP_PLUGIN_DIR' ) ) {
			define( 'WCAP_MAILCHIMP_PLUGIN_DIR', __DIR__ );
		}
		if ( ! defined( 'WCAP_MAILCHIMP_PLUGIN_URL' ) ) {
			define( 'WCAP_MAILCHIMP_PLUGIN_URL', untrailingslashit( plugin_dir_url( WCAP_MAILCHIMP_PLUGIN_FILE ) ) );
		}
		if ( ! defined( 'WCAP_MAILCHIMP_PLUGIN_BASENAME' ) ) { 
			define( 'WCAP_MAILCHIMP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		}
//		define( 'WFCO_MAILCHIMP_MAIN', 'woocommerce-ac' );
//		define( 'WFCO_MAILCHIMP_ENCODE', sha1( WFCO_MAILCHIMP_PLUGIN_BASENAME ) );
    }
    public function add_card( $available_connectors ) {
		$available_connectors['wcap']['connectors']['wcap_mailchimp'] = array(
			'name'            => $this->name,
			'desc'            => __( $this->desc, 'woocommerce-ac' ),//phpcs:ignore
			'connector_class' => 'Wcap_Mailchimp',
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
		$resource_dir = WCAP_MAILCHIMP_PLUGIN_DIR . '/calls';
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
			'Authorization' => 'Basic ' . base64_encode( 'woocommerce-ac:' . $api_key )
		);

		self::$headers = $headers;
	}

	public static function get_endpoint( $data_center, $version = '3.0' ) {
		return str_replace( '<dc>', $data_center, self::$api_end_point . $version . '/' );
	}

	public static function get_data_center( $api_key ) {
		if ( empty( $api_key ) || false === strpos( $api_key, '-' ) ) {
			return false;
		}

		return explode( '-', $api_key )[1];
	}

	public function wcap_mailchimp_include_files() {
		include_once WCAP_MAILCHIMP_PLUGIN_DIR . '/includes/wcap-upsert-contact.php';
		include_once WCAP_MAILCHIMP_PLUGIN_DIR . '/includes/wcap-upsert-cart.php';
		include_once WCAP_MAILCHIMP_PLUGIN_DIR . '/includes/wcap-delete-cart.php';
	}

	public function wcap_get_mailchimp_lists() {
		
		$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key' ] ) ) : '';//phpcs:ignore

		if ( empty( $api_key ) ) {
			wp_send_json( array(
				'response' => __( 'API Key is not provided', 'woocommerce-ac' )
			) );
		}

		$lists_result = $this->fetch_lists( array( 'api_key' => $api_key ) );
		if ( ! is_array( $lists_result ) || ! count( $lists_result ) > 0 ) {
			wp_send_json( array( 'status' => false ) );
		}

		wp_send_json( $lists_result );

		die();
	}

	public function fetch_lists( $params, $captured_items = [] ) {
		$call = $this->registered_calls['wcap_mailchimp_get_lists'];
		$call->set_data( $params );
		$result = $call->process();

		if ( 200 !== absint( $result['response'] ) ) {
			$error = __( 'Error Response Code: ', 'woocommerce-ac' ) . $result['response'] . __( '. Mailchimp Error: ', 'woocommerce-ac' );
			$error .= is_array( $result['body'] ) && isset( $result['body']['detail'] ) ? $result['body']['detail'] : __( 'No Response from Mailchimp. ', 'woocommerce-ac' );
			$error .= ( 502 === absint( $result['response'] ) ) ? __( 'Autonami Error: ', 'woocommerce-ac' ) . $result['body'][0] : '';

			wp_send_json( array(
				'status'  => 'failed',
				'message' => $error,
			) );
		}

		$total_items_count = absint( $result['body']['total_items'] );
		$data              = $result['body']['lists'];
		foreach ( $data as $row ) {
			$id                    = $row['id'];
			$captured_items[ $id ] = $row['name'];
		}

		$offset = '';
		if ( $total_items_count > count( $captured_items ) ) {
			$offset = count( $captured_items );
		}
		if ( ! empty( $offset ) ) {
			$params['offset'] = $offset;

			return $this->fetch_lists( $params, $captured_items );
		}

		return $captured_items;
	}

	public function wcap_get_mailchimp_stores() {

		$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';//phpcs:ignore
		if ( empty( $api_key ) ) {
			wp_send_json( array(
				'response' => __( 'API Key is not provided', 'woocommerce-ac' )
			) );
		}

		$list_id = isset( $_POST['list_id'] ) ? sanitize_text_field( wp_unslash( $_POST['list_id'] ) ) : '';//phpcs:ignore
		if ( empty( $list_id ) ) {
			wp_send_json( array(
				'response' => __( 'List ID is not provided', 'woocommerce-ac' )
			) );
		}

		/** Fetch E-Commerce Stores */
		$stores_result = $this->fetch_stores( array( 'api_key' => $api_key ) );
		if ( ! is_array( $stores_result ) || ! count( $stores_result ) > 0 ) {
			$stores_result = $this->create_store( array( 'api_key' => $api_key, 'list_id' => $list_id ) );
		}

		wp_send_json( $stores_result );
	}

	public function fetch_stores( $params, $captured_items = [] ) {
		$call = $this->registered_calls['wcap_mailchimp_get_stores'];
		$call->set_data( $params );
		$result = $call->process();

		if ( 200 !== absint( $result['response'] ) ) {
			$error = __( 'Error Response Code: ', 'woocommerce-ac' ) . $result['response'] . __( '. Mailchimp Error: ', 'woocommerce-ac' );
			$error .= is_array( $result['body'] ) && isset( $result['body']['detail'] ) ? $result['body']['detail'] : __( 'No Response from Mailchimp. ', 'woocommerce-ac' );
			$error .= ( 502 === absint( $result['response'] ) ) ? __( 'Wcap Error: ', 'woocommerce-ac' ) . $result['body'][0] : '';

			wp_send_json( array(
				'status'  => 'failed',
				'message' => $error,
			) );
		}

		$total_items_count = absint( $result['body']['total_items'] );
		$data              = $result['body']['stores'];
		foreach ( $data as $row ) {
			$id                    = $row['id'];
			$captured_items[ $id ] = $row['name'];
		}

		$offset = '';
		if ( $total_items_count > count( $captured_items ) ) {
			$offset = count( $captured_items );
		}
		if ( ! empty( $offset ) ) {
			$params['offset'] = $offset;

			return $this->fetch_stores( $params, $captured_items );
		}

		return $captured_items;
	}

	public function create_store( $params ) {
		$params['store_id']      = 'abandon_cart_pro_store';
		$params['store_name']    = get_bloginfo( 'name' );
		$currency                = get_woocommerce_currency();
		$params['currency_code'] = ! empty( $currency ) ? $currency : 'USD';

		/** @var Wcap_Mailchimp_Create_Store $call */
		$call = $this->registered_calls[ 'wcap_mailchimp_create_store' ];
		$call->set_data( $params );
		$result = $call->process();

		if ( 200 !== $result['response'] || ! isset( $result['body']['id'] ) ) {
			$error = __( 'Error Response Code: ', 'woocommerce-ac' ) . $result['response'] . __( '. Mailchimp Error: ', 'woocommerce-ac' );
			$error .= is_array( $result['body'] ) && isset( $result['body']['detail'] ) ? $result['body']['detail'] : __( 'No Response from Mailchimp. ', 'woocommerce-ac' );
			$error .= ( 502 === absint( $result['response'] ) ) ? __( 'Wcap Error: ', 'woocommerce-ac' ) . $result['body'][0] : '';

			wp_send_json( array(
				'status'  => 'failed',
				'message' => $error,
			) );
		}

		return array( $result['body']['id'] => $result['body']['name'] );
	}

	/**
	 * Sync Carts manually.
	 */
	public function wcap_sync_manually() {

		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( 'wcap_mailchimp' );
		$activated_time     = isset( $connector_settings['activated'] ) ? $connector_settings['activated'] : '';
		$connector_status   = isset( $connector_settings['status'] ) ? $connector_settings['status'] : '';

		if ( 'active' === $connector_status && '' != $activated_time ) {
			global $wpdb;
			// Get the list of carts which have not yet been synced.
			$cart_list = $wpdb->get_col( // phpcs:disable
				'SELECT wcap.id FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` as wcap LEFT JOIN `' . $wpdb->prefix . 'ac_connector_sync` as csync ON wcap.id=csync.cart_id where wcap.user_id > 0 AND wcap.abandoned_cart_time > "' . $activated_time . '" AND ( csync.cart_id IS NULL OR csync.status = "failed" ) AND connector_name = "mailchimp"'
			);
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
					if ( 0 == $sync_count ) {
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
			$wcap_add_cart = Wcap_Mailchimp_Upsert_Cart_Action::get_instance();
			$res           = $wcap_add_cart->wcap_prepare_cart_details( $cart_id, true );
			if ( 'complete' === $res ) { // failed, catch the data and move on.
				return true;
			}
		}
		return false;
	}

	/**
	 * Get existing Mailchimp settings.
	 */
	public function wcap_get_existing_settings() {
		if ( isset( $_POST['name'] ) && 'wcap_mailchimp' !== sanitize_text_field( wp_unslash( $_POST['name'] ) ) ) {
			return;
		}
		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( 'wcap_mailchimp' );
		$api_key            = isset( $connector_settings['api_key'] ) ? $connector_settings['api_key'] : '';
		$list_id            = isset( $connector_settings['default_list'] ) ? $connector_settings['default_list'] : '';
		$store_id           = isset( $connector_settings['default_store'] ) ? $connector_settings['default_store'] : '';

		if ( empty( $api_key ) ) {
			wp_send_json( array(
				'response' => __( 'API Key is not provided', 'woocommerce-ac' )
			) );
		}

		$lists_result = $this->fetch_lists( array( 'api_key' => $api_key ) );

		$stores_result = $this->fetch_stores( array( 'api_key' => $api_key ) );

		if ( is_array( $lists_result ) && is_array( $stores_result ) && count( $lists_result ) > 0 && count( $stores_result ) > 0 ) {
			wp_send_json(
				array(
					'status' => 'success',
					'lists'  => $lists_result,
					'stores' => $stores_result,
				)
			);
		} else {
			wp_send_json(
				array(
					'status' => 'error',
					'message' => __( 'We were unable to connect to your store. Please disconnect and try connecting again.', 'woocommerce-ac' ),
				)
			);
		}
		die();
	}

	/**
	 * Verify settings for Mailchimp before saving.
	 *
	 * @param array $result - Verification status.
	 * @param array $settings - MC Settings.
	 * @param str   $connector - Connector name.
	 */
	public function wcap_verify_mc_settings( $result, $settings, $connector ) {
		if ( '' !== $connector && 'mailchimp' === $connector ) {
			$api_key  = isset( $settings['api_key'] ) && '' !== $settings['api_key'] ? true : false;
			$list_id  = isset( $settings['default_list'] ) && '' !== $settings['default_list' ] ? true : false;
			$store_id = isset( $settings['default_store'] ) && '' !== $settings['default_store' ] ? true : false;

			if ( $api_key && $store_id && $list_id ) {
				$result['mailchimp'] = array(
					'status' => 'success',
					'message' => __( 'Connected successfully!', 'woocommerce-ac' ),
				);
			} else {
				$result['mailchimp'] = array(
					'status' => 'error',
					'message' => __( 'Some settings are blanks. Please fill all the fields.', 'woocommerce-ac' ),
				);
			}
		}
		return $result;
	}

	/**
	 * Display Cart Sync status.
	 *
	 * @param object $wcap_abandoned_order - Abandoned Orders data.
	 */
	public function wcap_add_mc_cart_status( $wcap_abandoned_orders ) {
		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( $this->slug );
		$connector_status = isset( $connector_settings['status'] ) ? $connector_settings['status'] : '';
		if ( 'active' === $connector_status ) {
			foreach ( $wcap_abandoned_orders as $k => $col_data ) {
				$cart_id = 0;
				foreach ( $col_data as $col_name => $col_value ) {
					$cart_id = 'id' === $col_name ? $col_value : $cart_id;
					if ( (int) $cart_id > 0 ) {
						$connector_common = Wcap_Connectors_Common::get_instance();
						$cart_status      = $connector_common->wcap_get_cart_sync_status( $cart_id, $this->connector_name );

						if ( $cart_status ) {
							$wcap_abandoned_orders[ $k ]->connector_status .= "<span><img src='" . WCAP_MAILCHIMP_PLUGIN_URL . "/views/icon.png' class='wcap-wpf-success wcap-success wcap-connector-status' /></span>";
						} else {
							$wcap_abandoned_orders[ $k ]->connector_status .= "<span><img src='" . WCAP_MAILCHIMP_PLUGIN_URL . "/views/icon.png' class='wcap-wpf-failed wcap-failed wcap-connector-status' /></span>";
						}
					}
					break;
				}
			}
		}
		return $wcap_abandoned_orders;
	}
}
