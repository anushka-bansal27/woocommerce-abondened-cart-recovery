<?php
/**
 * Prepare and place API call to Drip to unenroll contacts from workflows.
 *
 * @package Connectors/Drip/Action
 */

/**
 * Unenroll Contact Class.
 */
class Wcap_Drip_Unenroll_Contact_Action {

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
		// Unenroll in Drip once recovered.
		add_action( 'wcap_cart_recovered', array( &$this, 'wcap_unenroll_contact' ), 11, 1 );
		// Unenroll the email address if the order comes through.
		add_action( 'wcap_cart_cycle_completed', array( &$this, 'wcap_unenroll_contact' ), 11, 1 );
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
	 * Unenroll contact in Drip for recovered carts.
	 *
	 * @param int $abandoned_id - Cart ID.
	 */
	public function wcap_unenroll_contact( $abandoned_id ) {
		if ( (int) $abandoned_id && (int) $abandoned_id > 0 ) {
			global $wpdb;
			$connector_dp = Wcap_Drip::get_instance();
			$call         = $connector_dp->registered_calls['wcap_drip_unenroll_contact'];
			// Fetch the connector data.
			$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( 'wcap_drip' );
			$api_token          = isset( $connector_settings['api_token'] ) ? $connector_settings['api_token'] : '';
			$account_id         = isset( $connector_settings['account_id'] ) ? $connector_settings['account_id'] : '';
			$status             = isset( $connector_settings['status'] ) ? $connector_settings['status'] : '';
			$workflow_id        = isset( $connector_settings['workflow_id'] ) ? $connector_settings['workflow_id'] : '';

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
						'workflow_id' => $workflow_id,
					);

					$call->set_data( $params );
					$result = $call->process();
				}
			}
		}
	}
}
new Wcap_Drip_Unenroll_Contact_Action();
