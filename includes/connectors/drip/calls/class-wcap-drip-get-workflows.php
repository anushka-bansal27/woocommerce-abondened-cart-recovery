<?php

class Wcap_Drip_Get_Workflows extends Wcap_Call {

	public $call_slug = 'wcap_drip_get_workflows';
	private static $ins = null;

	public function __construct() {

		$this->required_fields = array( 'api_token', 'account_id' );
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

		Wcap_Drip::set_headers( $this->data['api_token'] );

		$params = array();
		if ( isset( $this->data['offset'] ) && 0 < absint( $this->data['offset'] ) ) {
			$params['offset'] = $this->data['offset'];
		}
		if ( isset( $this->data['limit'] ) && 0 < absint( $this->data['limit'] ) ) {
			$params['limit'] = $this->data['limit'];
		}

		$res = $this->make_wp_requests( $this->get_endpoint(), $params, Wcap_Drip::get_headers(), Wcap_Call::$GET );

		return $res;
	}

	/**
	 * Return the endpoint.
	 *
	 * @return string
	 */
	public function get_endpoint() {

		return Wcap_Drip::get_endpoint( 'v2' ) . $this->data['account_id'] . '/workflows';
	}

}

return 'Wcap_Drip_Get_Workflows';
