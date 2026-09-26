<?php
/**
 * Delete cart & create Order in MC.
 *
 * @package Connectors/Mailchimp/Actions
 */

/**
 * Delete Cart Action.
 */
class Wcap_Mailchimp_Delete_Cart_Action {

	/**
	 * Class instance.
	 *
	 * @var $ins
	 */
	private $ins = null;

	/**
	 * Construct.
	 */
	public function __construct() {
		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( 'wcap_mailchimp' );
		if ( empty( $connector_settings ) || 'active' !== $connector_settings['status'] ) {
			return false;
		}
		add_action( 'wcap_cart_recovered', array( &$this, 'wcap_add_order' ), 9, 2 );
		// Delete cart in Mailchimp once recovered.
		add_action( 'wcap_cart_recovered', array( &$this, 'wcap_delete_cart' ), 10, 1 );
		// Delete cart in Mailchimp if the order comes through.
		add_action( 'wcap_cart_cycle_completed', array( &$this, 'wcap_delete_cart' ), 10, 1 );
		add_action( 'wcap_cart_cycle_completed', array( &$this, 'wcap_add_order' ), 9, 2 );
	}

	/**
	 * Get Instance.
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Delete Cart.
	 *
	 * @param int $abandoned_id - Abandoned ID.
	 */
	public function wcap_delete_cart( $abandoned_id ) {
		$connector_mc = Wcap_Mailchimp::get_instance();
		$call         = $connector_mc->registered_calls['wcap_mailchimp_delete_cart'];
		// Fetch the connector data.
		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( 'wcap_mailchimp' );
		$api_key            = isset( $connector_settings['api_key'] ) ? $connector_settings['api_key'] : '';
		$store_id           = isset( $connector_settings['default_store'] ) ? $connector_settings['default_store'] : '';
		$status             = isset( $connector_settings['status'] ) ? $connector_settings['status'] : '';

		if ( 'active' === $status && '' !== $abandoned_id ) {
			$params = array(
				'api_key'  => $api_key,
				'store_id' => $store_id,
				'cart_id'  => 'wcap_cart_' . $abandoned_id,
			);

			$call->set_data( $params );
			$result = $call->process();

			// Update the status of the integration for the cart in the table.
		}
	}

	/**
	 * Add Order in MC.
	 *
	 * @param int $abandoned_id - Abandoned ID.
	 */
	public function wcap_add_order( $abandoned_id, $order_id ) {
		global $wpdb;
		$connector_mc = Wcap_Mailchimp::get_instance();
		$call         = $connector_mc->registered_calls['wcap_mailchimp_create_order'];
		// Fetch the connector data.
		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( 'wcap_mailchimp' );
		$api_key            = isset( $connector_settings['api_key'] ) ? $connector_settings['api_key'] : '';
		$store_id           = isset( $connector_settings['default_store'] ) ? $connector_settings['default_store'] : '';
		$status             = isset( $connector_settings['status'] ) ? $connector_settings['status'] : '';
		$order              = wc_get_order( $order_id );

		$billing_email = $order->get_billing_email();

		if ( 'active' === $status && '' !== $abandoned_id ) {
			$params = array(
				'api_key'  => $api_key,
				'store_id' => $store_id,
				'cart_id'  => 'wcap_cart_' . $abandoned_id,
				'order_id' => $order_id,
				'email'    => $billing_email,
			);

			$call->set_data( $params );
			$result = $call->process();
		}
	}
}
new Wcap_Mailchimp_Delete_Cart_Action();
