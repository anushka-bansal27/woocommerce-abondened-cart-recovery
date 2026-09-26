<?php

class Wcap_Mailchimp_Create_Product extends Wcap_Call {

	public $call_slug = 'wcap_mailchimp_create_product';
	private static $ins = null;

	public function __construct() {

		$this->required_fields = array( 'api_key', 'product_name', 'product_id', 'store_id' );
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

		Wcap_Mailchimp::set_headers( $this->data['api_key'] );
		$params = array(
			'id'       => 'wcap_product_' . $this->data['product_id'],
			'store_id' => $this->data['store_id'],
			'title'    => $this->data['product_name'],
			'variants' => array(
				array(
					'id'    => 'wcap_product_' . $this->data['product_id'],
					'title' => $this->data['product_name'],
					'image_url' => $this->data['image_url'],
					'price' => $this->data['price']
				)
			),
			'image_url' => $this->data['image_url']
		);

		return $this->make_wp_requests( $this->get_endpoint(), wp_json_encode( $params ), Wcap_Mailchimp::get_headers(), Wcap_Call::$POST );
	}

	/**
	 * Return the endpoint.
	 *
	 * @return string
	 */
	public function get_endpoint() {
		$data_center = Wcap_Mailchimp::get_data_center( $this->data['api_key'] );

		return Wcap_Mailchimp::get_endpoint( $data_center ) . 'ecommerce/stores/' . $this->data['store_id'] . '/products';
	}

}

return 'Wcap_Mailchimp_Create_Product';
