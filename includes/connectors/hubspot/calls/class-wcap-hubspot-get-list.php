<?php

class Wcap_Hubspot_Get_List extends Wcap_Call {

	public $call_slug = 'wcap_hubspot_get_list';
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

		$params = array();
		
		Wcap_Hubspot::set_headers( $this->data['api_key'] );

		$res = $this->make_wp_requests( $this->get_endpoint(), $params, Wcap_Hubspot::get_headers(), Wcap_Call::$GET );

		return $res;
	}

	/**
	 * Return the endpoint.
	 *
	 * @return string
	 */
	public function get_endpoint() {
		if ( isset( $this->data['list_id'] ) && $this->data['list_id'] > 0 ) {
			return Wcap_Hubspot::get_endpoint( 'contacts', 'v1' ) . 'lists/' . $this->data['list_id'];	
		}
		return Wcap_Hubspot::get_endpoint( 'contacts', 'v1' ) . 'lists';
	}

}

return 'Wcap_Hubspot_Get_List';
