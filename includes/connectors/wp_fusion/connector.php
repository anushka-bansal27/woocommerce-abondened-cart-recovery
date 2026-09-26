<?php
/**
 * AC insert and update file
 *
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Connectors/wp_fusion
 */

/**
 * Class for ActiveCapmaign Connector
 */
class Wcap_WP_Fusion extends Wcap_Connector {
	/**
	 * Connector Name
	 *
	 * @var $connector_name
	 */
	public $connector_name = 'wp_fusion';
	/**
	 * Slug Name
	 *
	 * @var $slug
	 */
	public $slug = 'wcap_wp_fusion';
	/**
	 * Name
	 *
	 * @var $name
	 */
	public $name = 'WP Fusion';
	/**
	 * Description
	 *
	 * @var $desc
	 */
	public $desc = 'Send emails and abandoned carts collected from the plugin to WP Fusion.';
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
	public $events = array( 'Created Cart', 'Modifed Cart', 'Ignored Cart', 'Recovered Cart', 'Deleted Cart' );
	/**
	 * Construct. Add hooks and filters.
	 *
	 * @var array All calls with object.
	 */
	public function __construct() {
		$this->wcap_define_plugin_properties();
		$this->connector_url = WCAP_WP_FUSION_PLUGIN_URL;
		add_filter( 'wcap_connectors_loaded', array( $this, 'add_card' ) );
		add_action( 'init', array( &$this, 'wcap_wp_fusion_include_files' ) );
		// Add cart status in the Abandoned Orders tab.
		add_filter( 'wcap_abandoned_orders_table_data', array( &$this, 'wcap_add_wp_fusion_cart_status' ), 10, 1 );
	}

	/**
	 * Function to define constans
	 */
	public function wcap_define_plugin_properties() {
		if ( ! defined( 'WCAP_WP_FUSION_VERSION' ) ) {
			define( 'WCAP_WP_FUSION_VERSION', '1.0.0' );
		}
		if ( ! defined( 'WCAP_WP_FUSION_FULL_NAME' ) ) {
			define( 'WCAP_WP_FUSION_FULL_NAME', 'Abandoned Carts Automations Connectors: WP_FUSION' );
		}
		if ( ! defined( 'WCAP_WP_FUSION_PLUGIN_FILE' ) ) {
			define( 'WCAP_WP_FUSION_PLUGIN_FILE', __FILE__ );
		}
		if ( ! defined( 'WCAP_WP_FUSION_PLUGIN_DIR' ) ) {
			define( 'WCAP_WP_FUSION_PLUGIN_DIR', __DIR__ );
		}
		if ( ! defined( 'WCAP_WP_FUSION_PLUGIN_URL' ) ) {
			define( 'WCAP_WP_FUSION_PLUGIN_URL', untrailingslashit( plugin_dir_url( WCAP_WP_FUSION_PLUGIN_FILE ) ) );
		}
		if ( ! defined( 'WCAP_WP_FUSION_PLUGIN_BASENAME' ) ) {
			define( 'WCAP_WP_FUSION_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		}
	}

	/**
	 * Function to Add card in connector's main page.
	 *
	 * @param array $available_connectors - Avaialble connector for display in main connector page.
	 */
	public function add_card( $available_connectors ) {
		$available_connectors['wcap']['connectors']['wcap_wp_fusion'] = array(
			'name'            => $this->name,
			'desc'            => __( $this->desc, 'woocommerce-ac' ), //phpcs:ignore
			'connector_class' => 'Wcap_WP_Fusion',
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
	 * Function to include files
	 */
	public function wcap_wp_fusion_include_files() {
		include_once WCAP_WP_FUSION_PLUGIN_DIR . '/includes/wcap-upsert-contact.php';
		include_once WCAP_WP_FUSION_PLUGIN_DIR . '/includes/wcap-upsert-cart.php';
	}

	/**
	 * Function to include files.
	 */
	public function wcap_get_wp_fusion_lists() {
		$private_key = isset( $_POST['private_key'] ) ? sanitize_text_field( wp_unslash( $_POST['private_key' ] ) ) : ''; // phpcs:ignore

		if ( empty( $private_key ) ) {
			wp_send_json(
				array(
					'response' => __( 'Private Key is not provided', 'woocommerce-ac' ),
				)
			);
		}

		$lists_result = $this->fetch_lists(
			array(
				'private_key' => $private_key,
			)
		);
		if ( ! is_array( $lists_result ) || ! count( $lists_result ) > 0 ) {
			wp_send_json( array( 'status' => false ) );
		}

		wp_send_json( $lists_result );

		die();
	}

	/**
	 * Sync Carts manually.
	 */
	public function wcap_sync_manually() {

		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( 'wcap_wp_fusion' );
		$activated_time     = isset( $connector_settings['activated'] ) ? $connector_settings['activated'] : '';
		$status             = isset( $connector_settings['status'] ) ? $connector_settings['status'] : '';

		global $wpdb;
		// Get the list of carts which have not yet been synced.
		$cart_list = $wpdb->get_col( // phpcs:ignore
			$wpdb->prepare( 'SELECT wcap.id FROM ' . WCAP_ABANDONED_CART_HISTORY_TABLE . ' as wcap LEFT JOIN ' . $wpdb->prefix . 'ac_connector_sync as csync ON (wcap.id=csync.cart_id AND connector_name="wp_fusion" ) where ( connector_cart_id IS NULL OR connector_cart_id =""  ) AND wcap.user_id > 0 AND wcap.abandoned_cart_time > %d', $activated_time ) // phpcs:ignore 
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
			wcap_set_cart_session( 'wcap_wpfusion_sync_cart_id', '' );
			wcap_set_cart_session( 'wp_fusion_customer_details', '' );
			$wcap_ct_instance = Wcap_WP_Fusion_Upsert_Contact_Action::get_instance();
			$wcap_add_contact = $wcap_ct_instance->wcap_prepare_contact( $cart_id );
			$wcap_add_cart    = Wcap_WP_Fusion_Upsert_Cart_Action::get_instance();
			$res              = $wcap_add_cart->wcap_prepare_cart_details( $cart_id, true );
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
	public function wcap_add_wp_fusion_cart_status( $wcap_abandoned_orders ) {

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
							$wcap_abandoned_orders[ $k ]->connector_status .= "<span><img src='" . WCAP_WP_FUSION_PLUGIN_URL . "/views/icon.png' class='wcap-wpf-success wcap-success wcap-connector-status' /></span>";
						} else {
							$wcap_abandoned_orders[ $k ]->connector_status .= "<span><img src='" . WCAP_WP_FUSION_PLUGIN_URL . "/views/icon.png' class='wcap-wpf-failed wcap-failed wcap-connector-status' /></span>";
						}
					}
					break;
				}
			}
		}
		return $wcap_abandoned_orders;
	}
}
