<?php

class Wcap_Mailchimp_Create_Store extends Wcap_Call {

	public $call_slug = 'wcap_mailchimp_create_store';
	private static $ins = null;

	public function __construct() {

		$this->required_fields = array( 'api_key', 'store_name', 'store_id', 'list_id', 'currency_code' );
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
			'id'            => $this->data['store_id'],
			'list_id'       => $this->data['list_id'],
			'name'          => $this->data['store_name'],
			'currency_code' => $this->data['currency_code']
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

		return Wcap_Mailchimp::get_endpoint( $data_center ) . 'ecommerce/stores';
	}

}

return 'Wcap_Mailchimp_Create_Store';
