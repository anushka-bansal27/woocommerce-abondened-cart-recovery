<?php

class Wcap_Hubspot_Create_Property extends Wcap_Call {

	public $call_slug = 'wcap_hubspot_create_property';
	private static $ins = null;

	public function __construct() {
		$this->required_fields = array( 'api_key', 'name', 'label', 'groupName', 'type', 'fieldType' );
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

        $params = array(
            'name' => $this->data['name'],
            'label' => $this->data['label'],
            'description' => $this->data['description'],
            'groupName' => $this->data['groupName'],
            'type' => $this->data['type'],
            'fieldType' => $this->data['fieldType'],
            'formField' => $this->data['formField'],
            'displayOrder' => $this->data['displayOrder'],
            'options' => $this->data['options']
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
		return Wcap_Hubspot::get_endpoint( 'properties', 'v1' ) . 'contacts/properties/';
	}

}

return 'Wcap_Hubspot_Create_Property';
