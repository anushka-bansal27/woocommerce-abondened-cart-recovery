<?php

class Wcap_Hubspot_Create_Contact extends Wcap_Call {

	public $call_slug = 'wcap_hubspot_create_contact';
	private static $ins = null;

	public function __construct() {
		$this->required_fields = array( 'api_key', 'email', 'interests' );
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

		$first_name = isset( $this->data['firstname'] ) ? $this->data['firstname'] : '';
		$last_name = isset( $this->data['lastname'] ) ? $this->data['lastname'] : '';
		$params = array(
			'properties' => array(
				'email' => $this->data['email'],
				'firstname' => $first_name,
				'lastname' => $last_name,
				'wcap_cart_counter' => $this->data['interests']['wcap_cart_counter'],
				'wcap_abandoned_date' => $this->data['interests']['wcap_abandoned_date'],
				'wcap_cart_products' => $this->data['interests']['wcap_cart_products'],
				'wcap_products_html' => $this->data['interests']['wcap_products_html'],
				'wcap_products_sku' => $this->data['interests']['wcap_products_sku'],
				'wcap_cart_subtotal' => $this->data['interests']['wcap_cart_subtotal'],
				'wcap_cart_tax' => $this->data['interests']['wcap_cart_tax'],
				'wcap_cart_total' => $this->data['interests']['wcap_cart_total'],
				'wcap_cart_url' => $this->data['interests']['wcap_cart_url'],
				'wcap_abandoned_cart' => $this->data['interests']['wcap_abandoned_cart'],
			)
		);

		Wcap_Hubspot::set_headers( $this->data['api_key'] );

		$res = $this->make_wp_requests( $this->get_endpoint(), wp_json_encode( $params ), Wcap_Hubspot::get_headers(), Wcap_Call::$POST );

		return $res;
	}

	/**
	 * Return the endpoint.
	 *
	 * @return string
	 */
	public function get_endpoint() {
		return Wcap_Hubspot::get_endpoint( 'crm', 'v3' ) . 'objects/contacts';
	}

}

return 'Wcap_Hubspot_Create_Contact';
