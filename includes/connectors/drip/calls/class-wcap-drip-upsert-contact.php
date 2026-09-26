<?php

class Wcap_Drip_Upsert_Contact extends Wcap_Call {

	public $call_slug = 'wcap_drip_upsert_contact';
	private static $ins = null;

	public function __construct() {
		$this->required_fields = array( 'api_token', 'email', 'account_id' );
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

		$first_name = isset( $this->data['merge_fields']['first_name'] ) ? $this->data['merge_fields']['first_name'] : '';
		$last_name  = isset( $this->data['merge_fields']['last_name'] ) ? $this->data['merge_fields']['last_name'] : '';

		if ( isset( $this->data['new_email'] ) ) {
			$params = array(
				'subscribers' => array(
					array(
						'email'      => $this->data['email'],
						'first_name' => $first_name,
						'last_name'  => $last_name,
						'new_email'  => $this->data['new_email'],
					)
				)
			);
		} else {
			$params = array(
				'subscribers' => array(
					array(
						'email'      => $this->data['email'],
						'first_name' => $first_name,
						'last_name'  => $last_name,
					)
				)
			);
		}

		Wcap_Drip::set_headers( $this->data['api_token'] );
		$res = $this->make_wp_requests( $this->get_endpoint(), wp_json_encode( $params ), Wcap_Drip::get_headers(), Wcap_Call::$POST );

		return $res;
	}

	/**
	 * Return the endpoint.
	 *
	 * @return string
	 */
	public function get_endpoint() {
		return Wcap_Drip::get_endpoint( 'v2' ) . $this->data['account_id'] . '/subscribers';
	}

}

return 'Wcap_Drip_Upsert_Contact';
