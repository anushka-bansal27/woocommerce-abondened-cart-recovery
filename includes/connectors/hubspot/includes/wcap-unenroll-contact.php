<?php

class Wcap_Hubspot_Unenroll_Contact_Action {

	private static $ins = null;
    public function __construct() {
		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( 'wcap_hubspot' );
		if ( empty( $connector_settings ) ) {
			return false;
		}
		if ( 'active' !== $connector_settings['status'] ) {
			return false;
		}
		add_action( 'wcap_cart_recovered', array( &$this, 'wcap_unenroll_contact' ), 11, 1 );
		// Update contact properties & unenroll in Hubspot once recovered.
		add_action( 'wcap_cart_recovered', array( &$this, 'wcap_update_cart' ), 11, 1 );
		// Update contact properties & unenroll the email address if the order comes through.
		add_action( 'wcap_cart_cycle_completed', array( &$this, 'wcap_update_cart' ), 11, 1 );
		add_action( 'wcap_cart_cycle_completed', array( &$this, 'wcap_unenroll_contact' ), 11, 1 );
    }

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function wcap_update_cart( $abandoned_id ) {

        global $wpdb;

		$connector_hb = Wcap_Hubspot::get_instance();
		$call         = $connector_hb->registered_calls['wcap_hubspot_update_contact'];
		// Fetch the connector data.
		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( 'wcap_hubspot' );
		$api_key            = isset( $connector_settings['api_key'] ) ? $connector_settings['api_key'] : '';
		$status             = isset( $connector_settings['status'] ) ? $connector_settings['status'] : '';

		$wc_order_id = $wpdb->get_var(// phpcs:ignore
			$wpdb->prepare(
				'SELECT post_id FROM `' . $wpdb->prefix . 'postmeta` WHERE meta_key = %s AND meta_value = %s',
				'wcap_abandoned_cart_id',
				absint( $abandoned_id )
			)
		);
		$order = wc_get_order( $wc_order_id );
		$email = $order ? $order->get_billing_email() : '';

		if ( 'active' === $status && '' !== $email ) {
			$params = array(
				'api_key'  => $api_key,
				'email' => $email,
                'update_properties' => array(
                    'wcap_abandoned_cart' => 'no'
                )
			);

			$call->set_data( $params );
			$result = $call->process();

		}
	}

	public function wcap_unenroll_contact( $abandoned_id ) {
		global $wpdb;
		$connector_hb = Wcap_Hubspot::get_instance();
		$call         = $connector_hb->registered_calls['wcap_hubspot_unenroll_contact'];
		// Fetch the connector data.
		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( 'wcap_hubspot' );
		$api_key            = isset( $connector_settings['wcap_hubspot']['api_key'] ) ? $connector_settings['wcap_hubspot']['api_key'] : '';
		$status             = isset( $connector_settings['wcap_hubspot']['status'] ) ? $connector_settings['wcap_hubspot']['status'] : '';
		$workflow_id        = isset( $connector_settings['wcap_hubspot']['workflow_id'] ) ? $connector_settings['wcap_hubspot']['workflow_id'] : '';

		$wc_order_id = $wpdb->get_var(// phpcs:ignore
			$wpdb->prepare(
				'SELECT post_id FROM `' . $wpdb->prefix . 'postmeta` WHERE meta_key = %s AND meta_value = %s',
				'wcap_abandoned_cart_id',
				absint( $abandoned_id )
			)
		);
		$order = wc_get_order( $wc_order_id );
		$email = $order ? $order->get_billing_email() : '';

		if ( 'active' === $status && '' !== $email ) {
			$params = array(
				'api_key'     => $api_key,
				'email'       => $email,
				'workflow_id' => $workflow_id
			);

			$call->set_data( $params );
			$result = $call->process();
		}
	}
}
new Wcap_Hubspot_Unenroll_Contact_Action();
