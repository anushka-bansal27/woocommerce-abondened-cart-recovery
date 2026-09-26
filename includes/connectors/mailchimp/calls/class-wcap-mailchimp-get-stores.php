<?php

class Wcap_Mailchimp_Get_Stores extends Wcap_Call {

	public $call_slug   = 'wcap_mailchimp_get_stores';
	private static $ins = null;

	public function __construct() {

		$this->required_fields = array( 'api_key' );
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

		$params = array();
		if ( isset( $this->data['offset'] ) && 0 < absint( $this->data['offset'] ) ) {
			$params['offset'] = $this->data['offset'];
		}
		if ( isset( $this->data['limit'] ) && 0 < absint( $this->data['limit'] ) ) {
			$params['limit'] = $this->data['limit'];
		}

		return $this->make_wp_requests( $this->get_endpoint(), $params, Wcap_Mailchimp::get_headers(), Wcap_Call::$GET );
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

return 'Wcap_Mailchimp_Get_Stores';
