<?php

class Wcap_Hubspot_Create_List extends Wcap_Call {

	public $call_slug = 'wcap_hubspot_create_list';
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

		$params['name'] = $this->data['name'];
		$params['dynamic'] = true;
		$params['filters'] = array(
			array(
				array(
					'operator' => 'NEQ',
					'value' => '',
					'property' => 'email',
					'type' => 'string'
				),
				array(
					'operator' => 'EQ',
					'value' => 'yes',
					'property' => 'wcap_abandoned_cart',
					'type' =>'string'
				)
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
		return Wcap_Hubspot::get_endpoint( 'contacts', 'v1' ) . 'lists';
	}

}

return 'Wcap_Hubspot_Create_List';
