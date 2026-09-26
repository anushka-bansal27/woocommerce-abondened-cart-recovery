<?php

class Wcap_Hubspot_Get_Property_Group extends Wcap_Call {

	public $call_slug = 'wcap_hubspot_get_property_group';
	private static $ins = null;

	public function __construct() {
		$this->required_fields = array( 'api_key', 'name' );
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
		return Wcap_Hubspot::get_endpoint( 'properties', 'v2' ) . 'contacts/groups/named/' . $this->data['name'];
	}

}

return 'Wcap_Hubspot_Get_Property_Group';
