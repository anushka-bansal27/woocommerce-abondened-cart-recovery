<?php
/**
 * Prepare and place API call to Drip to create order.
 *
 * @package Connectors/Drip/Action
 */

/**
 * Create Order Class.
 */
class Wcap_Drip_Create_Order_Action {

	/**
	 * Class instance.
	 *
	 * @var obj $ins.
	 */
	private static $ins = null;

	/**
	 * Construct.
	 */
	public function __construct() {
		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( 'wcap_drip' );
		if ( empty( $connector_settings ) ) {
			return false;
		}
		if ( 'active' !== $connector_settings['status'] ) {
			return false;
		}
		// Create an order placed event in Drip once recovered.
		add_action( 'wcap_cart_recovered', array( &$this, 'wcap_mark_recovered' ), 12, 1 );
		// Create an order placed event in Drip once the order comes through.
		add_action( 'wcap_cart_cycle_completed', array( &$this, 'wcap_create_order' ), 12, 1 );
	}

	/**
	 * Return class instance.
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Create order in drip for recovered carts.
	 *
	 * @param int $abandoned_id - Cart ID.
	 */
	public function wcap_mark_recovered( $abandoned_id ) {
		if ( (int) $abandoned_id && (int) $abandoned_id > 0 ) {
			$this->wcap_create_order_drip( (int) $abandoned_id, true );
		}
	}

	/**
	 * Create Order in Drip for fresh carts.
	 *
	 * @param int $abandoned_id - Cart ID.
	 */
	public function wcap_create_order( $abandoned_id ) {
		if ( (int) $abandoned_id && (int) $abandoned_id > 0 ) {
			$this->wcap_create_order_drip( (int) $abandoned_id, false );
		}
	}

	/**
	 * Collect data and place order.
	 *
	 * @param int  $abandoned_id - Cart ID.
	 * @param bool $create_connector_record - Create Record.
	 */
	public function wcap_create_order_drip( $abandoned_id, $create_connector_record = false ) {
		if ( $abandoned_id > 0 ) {
			global $wpdb;
			$connector_dp = Wcap_Drip::get_instance();
			$call         = $connector_dp->registered_calls['wcap_drip_create_order'];
			// Fetch the connector data.
			$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( 'wcap_drip' );
			$api_token          = isset( $connector_settings['api_token'] ) ? $connector_settings['api_token'] : '';
			$account_id         = isset( $connector_settings['account_id'] ) ? $connector_settings['account_id'] : '';
			$status             = isset( $connector_settings['status'] ) ? $connector_settings['status'] : '';

			$wc_order_id = $wpdb->get_var( // phpcs:ignore
				$wpdb->prepare(
					'SELECT post_id FROM `' . $wpdb->prefix . 'postmeta` WHERE meta_key = %s AND meta_value = %s',
					'wcap_abandoned_cart_id',
					absint( $abandoned_id )
				)
			);
			$order       = wc_get_order( $wc_order_id );
			if ( $order ) {

				$email = $order->get_billing_email();

				if ( 'active' === $status && '' !== $email ) {
					$params = array(
						'api_token'   => $api_token,
						'account_id'  => $account_id,
						'email'       => $email,
						'order_id'    => $wc_order_id,
						'placed_at'   => $order->get_date_created(),
						'grand_total' => $order->get_total(),
						'items'       => $this->wcap_get_items( $order ),
						'currency'    => $order->get_currency(),
					);

					$call->set_data( $params );
					$result = $call->process();

					$message = $result['response'];
					if ( 200 != absint( $result['response'] ) ) { // phpcs:ignore
						$error  = __( 'Error Response Code: ', 'woocommerce-ac' ) . $result['response']['code'] . __( '. Drip Error: ', 'woocommerce-ac' );
						$error .= is_array( $result['response'] ) && isset( $result['response']['message'] ) ? $result['response']['message'] : __( 'No Response from Drip. ', 'woocommerce-ac' );
						$error .= ( 502 === absint( $result['response'] ) ) ? __( 'Wcap Error: ', 'woocommerce-ac' ) . $result['body'] : '';

						$status = 'failed';
						if ( isset( $result['response']['message'] ) ) {
							$message .= ': ' . $error;
						}
					} else {
						// Update the details in the plugin table.
						$status   = 'complete';
						$message .= ' Order placed.';
						global $wpdb;
						$wpdb->update( // phpcs:ignore
							$wpdb->prefix . 'ac_connector_sync',
							array(
								'sync_date' => current_time( 'timestamp' ), // phpcs:ignore
								'status'    => $status,
								'sync_data' => wp_json_encode( $params ),
								'message'   => $message,
							),
							array(
								'cart_id'        => $abandoned_id,
								'connector_name' => 'drip',
							)
						);

					}
				}
			}
		}
	}

	/**
	 * Get Order Items.
	 *
	 * @param obj $order - WC Order Obj.
	 */
	public function wcap_get_items( $order ) {

		$items = array();
		foreach ( $order->get_items() as $item_id => $item_data ) {
			$items[ $item_id ] = array(
				'product_id'   => $item_data->get_product_id(),
				'variation_id' => $item_data->get_variation_id(),
				'quantity'     => $item_data->get_quantity(),
				'line_total'   => $item_data->get_total(),
			);
		}
		return $items;
	}
}
new Wcap_Drip_Create_Order_Action();
