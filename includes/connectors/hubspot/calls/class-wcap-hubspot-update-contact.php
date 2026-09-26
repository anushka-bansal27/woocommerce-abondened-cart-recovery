<?php

class Wcap_Hubspot_Update_Contact extends Wcap_Call {

	public $call_slug = 'wcap_hubspot_update_contact';
	private static $ins = null;

	public function __construct() {
		$this->required_fields = array( 'api_key', 'email' );
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

        if ( count( $this->data['update_properties'] ) > 0 ) {
			$params = array(
				'properties' => array(
				)
			);

			foreach ( $this->data['update_properties'] as $k => $v ) {
				array_push(
					$params['properties'],
					array(
						'property' => $k,
						'value' => $v
					)
				);
			}

			Wcap_Hubspot::set_headers( $this->data['api_key'] );

			$res = $this->make_wp_requests( $this->get_endpoint(), wp_json_encode( $params ), Wcap_Hubspot::get_headers(), Wcap_Call::$POST );

			return $res;
		}
	}

	/**
	 * Return the endpoint.
	 *
	 * @return string
	 */
	public function get_endpoint() {
		return Wcap_Hubspot::get_endpoint( 'contacts', 'v1' ) . 'contact/createOrUpdate/email/' . $this->data['email'];
	}

}

return 'Wcap_Hubspot_Update_Contact';
