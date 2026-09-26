<?php
/**
 * AC insert and update file
 *
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Connectors/ActiveCampaign
 */

/**
 * Delete a cart on ActiveCampaign
 */
class Wcap_Activecampaign_Delete_Cart extends Wcap_Call {
	/**
	 * Slug name of the class
	 *
	 * @var string $call_slug.
	 */
	public $call_slug = 'wcap_activecampaign_delete_cart';
	/**
	 * Single instance of the class.
	 *
	 * @var object $ins.
	 */
	private static $ins = null;
	/**
	 * Ecom_order_id
	 *
	 * @var ecom_order_id.
	 */
	public $ecom_order_id = '';

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->ecom_order_id   = wcap_get_cart_session( 'ecom_order_id' );
		$this->required_fields = array( 'api_url', 'api_key', 'ecom_order_id' );
	}

	/**
	 * Get Single instance of the class
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Process the REST call to a url/endpoint to delete a cart
	 */
	public function process() {
		$is_required_fields_present = $this->check_fields( $this->data, $this->required_fields );
		if ( false === $is_required_fields_present ) {
			return $this->show_fields_error();
		}

		if ( ! $this->data['ecom_order_id'] ) {
			return array(
				'response' => 502,
				'body'     => array( 'Cart ID is not valid' ),
			);
		}

		$params = array();

		Wcap_Activecampaign::set_headers( $this->data['api_key'] );

		return $this->make_wp_requests( $this->get_endpoint(), wp_json_encode( $params ), Wcap_Activecampaign::get_headers(), Wcap_Call::$DELETE );
	}

	/**
	 * Return the endpoint.
	 *
	 * @return string
	 */
	public function get_endpoint() {
		if ( ! empty( $this->ecom_order_id ) ) {
			return $this->data['api_url'] . '/api/3/ecomOrders/' . $this->ecom_order_id;
		}
		return $this->data['api_url'] . '/api/3/ecomOrders';
	}

}

return 'Wcap_Activecampaign_Delete_Cart';
