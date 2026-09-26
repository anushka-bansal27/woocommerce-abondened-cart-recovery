<?php

class Wcap_Mailchimp_Delete_Cart extends Wcap_Call {

	public $call_slug   = 'wcap_mailchimp_delete_cart';
	private static $ins = null;

	public function __construct() {
		$this->required_fields = array( 'api_key', 'store_id', 'cart_id' );
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function process() {
		$is_required_fields_present = $this->check_fields( $this->data, $this->required_fields );
		if ( false === $is_required_fields_present ) {
			return $this->show_fields_error();
		}

		if ( ! $this->data['cart_id'] ) {
			return array(
				'response' => 502,
				'body'     => array( 'Cart ID is not valid' ),
			);
		}

		$params = array();

		Wcap_Mailchimp::set_headers( $this->data['api_key'] );

		return $this->make_wp_requests( $this->get_endpoint(), wp_json_encode( $params ), Wcap_Mailchimp::get_headers(), Wcap_Call::$DELETE );
	}

	/**
	 * Return the endpoint.
	 *
	 * @return string
	 */
	public function get_endpoint() {
		$data_center = Wcap_Mailchimp::get_data_center( $this->data['api_key'] );

		return Wcap_Mailchimp::get_endpoint( $data_center ) . '/ecommerce/stores/' . $this->data['store_id'] . '/carts/' . $this->data['cart_id'];
	}

}

return 'Wcap_Mailchimp_Delete_Cart';
