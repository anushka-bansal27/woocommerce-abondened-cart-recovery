<?php

class Wcap_Mailchimp_Upsert_Contact extends Wcap_Call {

	public $call_slug = 'wcap_mailchimp_upsert_contact';
	private static $ins = null;

	/** Skip sending Merge Fields validation, if merge fields are not being updated */
	private $skip_merge_validation = true;

	public function __construct() {
		$this->required_fields = array( 'api_key', 'email', 'list_id' );
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

		$params = $this->add_merge_fields( array() );
		$params = $this->add_interests( $params );

		$params['email_address'] = $this->data['email'];
		$params['status_if_new'] = 'subscribed';
		$params['source']        = 'Abandon Cart API';

		Wcap_Mailchimp::set_headers( $this->data['api_key'] );

		$res = $this->make_wp_requests( $this->get_endpoint(), wp_json_encode( $params ), Wcap_Mailchimp::get_headers(), Wcap_Call::$PUT );

		return $res;
	}

	public function add_merge_fields( $params ) {
		if ( isset( $this->data['merge_fields'] ) && is_array( $this->data['merge_fields'] ) ) {
			$params['merge_fields']      = $this->data['merge_fields'];
			$this->skip_merge_validation = false;
		}

		return $params;
	}

	public function add_interests( $params ) {
		return $params;
	}

	/**
	 * Return the endpoint.
	 *
	 * @return string
	 */
	public function get_endpoint() {
		$data_center           = Wcap_Mailchimp::get_data_center( $this->data['api_key'] );
		$skip_merge_validation = ( true === $this->skip_merge_validation ) ? '?skip_merge_validation=true' : '';

		return Wcap_Mailchimp::get_endpoint( $data_center ) . 'lists/' . $this->data['list_id'] . '/members/' . md5( $this->data['email'] ) . $skip_merge_validation;
	}

}

return 'Wcap_Mailchimp_Upsert_Contact';
