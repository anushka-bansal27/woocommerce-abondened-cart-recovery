<?php

class Wcap_Drip extends Wcap_Connector {

	public $connector_name       = 'drip';
    public $slug                 = 'wcap_drip';
    public $name                 = 'Drip';
    public $desc                 = 'Send emails and abandoned carts collected from the plugin to Drip.';
	private static $ins          = null;
	public static $api_end_point = 'https://api.getdrip.com/';
	public static $headers       = null;
	
	/** @var array All calls with object */
	public $registered_calls = array();

    public function __construct() {
        $this->wcap_define_plugin_properties();
        $this->connector_url = WCAP_DRIP_PLUGIN_URL;
        add_filter( 'wcap_connectors_loaded', array( $this, 'add_card' ) );
		add_action( 'wp_ajax_wcap_get_drip_workflows', array( $this, 'wcap_get_drip_workflows' ) );
		add_action( 'wp_ajax_wcap_get_existing_settings', array( &$this, 'wcap_get_existing_settings' ) );
		add_action( 'wp_loaded', array( &$this, 'wcap_drip_include_files' ), 9 );
		add_filter( 'wcap_before_connector_save_settings', array( &$this, 'wcap_verify_dp_settings' ), 10, 3 );
		// Add cart status in the Abandoned Orders tab.
		add_filter( 'wcap_abandoned_orders_table_data', array( &$this, 'wcap_add_dp_cart_status' ), 12, 1 );
		$this->wcap_register_calls();
    }

	public function wcap_define_plugin_properties() {
		if ( ! defined( 'WCAP_DRIP_VERSION' ) ) {
        	define( 'WCAP_DRIP_VERSION', '1.0.0' );
		}
		if ( ! defined( 'WCAP_DRIP_FULL_NAME' ) ) {
			define( 'WCAP_DRIP_FULL_NAME', 'Abandoned Carts Automations Connectors: DRIP' );
		}
		if ( ! defined( 'WCAP_DRIP_PLUGIN_FILE' ) ) {
			define( 'WCAP_DRIP_PLUGIN_FILE', __FILE__ );
		}
		if ( ! defined( 'WCAP_DRIP_PLUGIN_DIR' ) ) {
			define( 'WCAP_DRIP_PLUGIN_DIR', __DIR__ );
		}
		if ( ! defined( 'WCAP_DRIP_PLUGIN_URL' ) ) {
			define( 'WCAP_DRIP_PLUGIN_URL', untrailingslashit( plugin_dir_url( WCAP_DRIP_PLUGIN_FILE ) ) );
		}
		if ( ! defined( 'WCAP_DRIP_PLUGIN_BASENAME' ) ) { 
			define( 'WCAP_DRIP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		}
    }
    public function add_card( $available_connectors ) {
		$available_connectors['wcap']['connectors']['wcap_drip'] = array(
			'name'            => $this->name,
			'desc'            => __( $this->desc, 'woocommerce-ac' ),//phpcs:ignore
			'connector_class' => 'Wcap_Drip',
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
		$resource_dir = WCAP_DRIP_PLUGIN_DIR . '/calls';
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

	public static function set_headers( $api_token ) {
		$headers = array(
			'User-Agent'    => 'Abandoned Cart Pro',
			'Content-Type'  => 'application/json',
			'Authorization' => 'Basic ' . base64_encode( $api_token )
		);

		self::$headers = $headers;
	}

	public static function get_endpoint( $version = 'v2' ) {
		return self::$api_end_point . $version . '/';
	}

	public function wcap_drip_include_files() {
		include_once WCAP_DRIP_PLUGIN_DIR . '/includes/wcap-upsert-contact.php';
		include_once WCAP_DRIP_PLUGIN_DIR . '/includes/wcap-upsert-cart.php';
		include_once WCAP_DRIP_PLUGIN_DIR . '/includes/wcap-unenroll-contact.php';
		include_once WCAP_DRIP_PLUGIN_DIR . '/includes/wcap-create-order.php';
	}

	public function wcap_get_drip_workflows() {

		$api_token  = isset( $_POST['api_token'] ) ? sanitize_text_field( wp_unslash( $_POST['api_token' ] ) ) : '';//phpcs:ignore
		$account_id = isset( $_POST['account_id'] ) ? sanitize_text_field( wp_unslash( $_POST['account_id'] ) ) : '';//phpcs:ignore

		if ( empty( $api_token ) || empty( $account_id ) ) {
			wp_send_json( array(
				'response' => __( 'API Token/Account ID is not provided', 'woocommerce-ac' )
			) );
		}

		$workflows_result = $this->fetch_workflows( array( 'api_token' => $api_token, 'account_id' => $account_id ) );
		if ( ! is_array( $workflows_result ) || ! count( $workflows_result ) > 0 ) {
			wp_send_json( array( 'status' => false ) );
		}

		wp_send_json( $workflows_result );

		die();
	}

	public function fetch_workflows( $params, $captured_items = [] ) {
		$call = $this->registered_calls['wcap_drip_get_workflows'];
		$call->set_data( $params );
		$result = $call->process();

		if ( 200 !== absint( $result['response'] ) ) {
			$error = __( 'Error Response Code: ', 'woocommerce-ac' ) . $result['response'] . __( '. Drip Error: ', 'woocommerce-ac' );
			$error .= is_array( $result['body'] ) && isset( $result['body']['detail'] ) ? $result['body']['detail'] : __( 'No Response from Drip. ', 'woocommerce-ac' );
			$error .= ( 502 === absint( $result['response'] ) ) ? __( 'Abandoned Cart Error: ', 'woocommerce-ac' ) . $result['body'][0] : '';

			wp_send_json( array(
				'status'  => 'failed',
				'message' => $error,
			) );
		}

		$total_items_count = isset( $result['body']['total_items'] ) ? absint( $result['body']['total_items'] ) : 0;
		$data              = $result['body']['workflows'];
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

			return $this->fetch_workflows( $params, $captured_items );
		}

		return $captured_items;
	}

	/**
	 * Sync Carts manually.
	 */
	public function wcap_sync_manually() {

		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( 'wcap_drip' );
		$activated_time     = isset( $connector_settings['activated'] ) ? $connector_settings['activated'] : '';
		$connector_status   = isset( $connector_settings['status'] ) ? $connector_settings['status'] : '';

		if ( 'active' === $connector_status && '' != $activated_time ) {
			global $wpdb;
			// Get the list of carts which have not yet been synced.
			$cart_list = $wpdb->get_col( // phpcs:disable
				'SELECT wcap.id FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` as wcap LEFT JOIN `' . $wpdb->prefix . 'ac_connector_sync` as csync ON wcap.id=csync.cart_id where wcap.user_id > 0 AND wcap.abandoned_cart_time > "' . $activated_time . '" AND ( csync.cart_id IS NULL OR csync.status = "failed" ) AND connector_name = "drip"'
			);
			if ( is_array( $cart_list ) && count( $cart_list ) > 0 ) { // Start the process one at a time.
				$sync_count = 0;// phpcs:enable
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
						/* translators: %1d,%2d are replaced with number of carts synced and number of total carts respectively */
						__( '%1$d of %2$d carts were synced successfully.', 'woocommerce-ac' ),
						esc_html( $sync_count ),
						esc_html( $cart_count )
					);
				} else {
					$status = 'error';
					if ( 0 == $sync_count ) {
						$message = sprintf(
							// translators: %1d,%2d are replaced with number of carts synced and number of total carts respectively.
							__( '%1$d of %2$d carts were synced. Please check for network connectivity issues and try again.', 'woocommerce-ac' ),
							esc_html( $sync_count ),
							esc_html( $cart_count )
						);
					} else {
						$message = sprintf(
							// translators: %1d,%2d are replaced with number of carts synced and number of total carts respectively.
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
			$wcap_add_cart = Wcap_Drip_Upsert_Cart_Action::get_instance();
			$res           = $wcap_add_cart->wcap_prepare_cart_details( $cart_id, true );
			if ( 'complete' === $res ) { // failed, catch the data and move on.
				return true;
			}
		}
		return false;
	}

	/**
	 * Get existing Drip settings.
	 */
	public function wcap_get_existing_settings() {
		if ( isset( $_POST['name'] ) && 'wcap_drip' !== sanitize_text_field( wp_unslash( $_POST['name'] ) ) ) {//phpcs:ignore
			return;
		}
		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( 'wcap_drip' );
		$api_token          = isset( $connector_settings['api_token'] ) ? $connector_settings['api_token'] : '';
		$account_id         = isset( $connector_settings['account_id'] ) ? $connector_settings['account_id'] : '';
		$workflow_id        = isset( $connector_settings['workflow_id'] ) ? $connector_settings['workflow_id'] : '';

		if ( empty( $api_token ) || empty( $account_id ) ) {
			wp_send_json( array(
				'response' => __( 'API Token/Account ID is not provided', 'woocommerce-ac' )
			) );
		}

		$workflows_result = $this->fetch_workflows( array( 'api_token' => $api_token, 'account_id' => $account_id ) );

		if ( is_array( $workflows_result ) && count( $workflows_result ) > 0 ) {
			wp_send_json(
				array(
					'status'    => 'success',
					'workflows' => $workflows_result,
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
	 * Verify settings for Drip before saving.
	 *
	 * @param array $result - Verification status.
	 * @param array $settings - Drip Settings.
	 * @param str   $connector - Connector name.
	 */
	public function wcap_verify_dp_settings( $result, $settings, $connector ) {
		if ( '' !== $connector && 'drip' === $connector ) {
			$api_token   = isset( $settings['api_token'] ) && '' !== $settings['api_token'] ? true : false;
			$account_id  = isset( $settings['account_id'] ) && '' !== $settings['account_id' ] ? true : false;
			$workflow_id = isset( $settings['workflow_id'] ) && '' !== $settings['workflow_id' ] ? true : false;

			if ( $api_token && $account_id && $workflow_id ) {
				$result['drip'] = array(
					'status' => 'success',
					'message' => __( 'Connected successfully!', 'woocommerce-ac' ),
				);
			} else {
				$result['drip'] = array(
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
	 * @param object $wcap_abandoned_orders - Abandoned Orders data.
	 */
	public function wcap_add_dp_cart_status( $wcap_abandoned_orders ) {
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
							$wcap_abandoned_orders[ $k ]->connector_status .= "<span><img src='" . WCAP_DRIP_PLUGIN_URL . "/views/icon.png' class='wcap-wpf-success wcap-success wcap-connector-status' /></span>";
						} else {
							$wcap_abandoned_orders[ $k ]->connector_status .= "<span><img src='" . WCAP_DRIP_PLUGIN_URL . "/views/icon.png' class='wcap-wpf-failed wcap-failed wcap-connector-status' /></span>";
						}
					}
					break;
				}
			}
		}
		return $wcap_abandoned_orders;
	}
}
