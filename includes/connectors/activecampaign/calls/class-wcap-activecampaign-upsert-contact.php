<?php
/**
 * AC insert and update file
 *
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Connectors/ActiveCampaign
 */

/**
 * Insert and Update an contact on ActiveCampaign
 */
class Wcap_Activecampaign_Upsert_Contact extends Wcap_Call {
	/**
	 * Slug name of the class
	 *
	 * @var string $call_slug.
	 */
	public $call_slug = 'wcap_activecampaign_upsert_contact';
	/**
	 * Single instance of the class.
	 *
	 * @var object $ins.
	 */
	private static $ins = null;

	/**
	 * Skip sending Merge Fields validation, if merge fields are not being updated 
	 *
	 * @var bool $skip_merge_validation - to skip or not to skip validations
	 */
	private $skip_merge_validation = true;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->required_fields = array( 'api_key', 'api_url', 'email', 'connectionid' );
	}

	/**
	 * Get Single instance of the class
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Preprocess before processing by adding, unsetting new fields.
	 */
	private function preprocess() {

		if ( isset( $this->data['customer_id'] ) ) {
			$this->customer_id = $this->data['customer_id'];
			unset( $this->data['customer_id'] );
		}
	}

	/**
	 * Process the REST call to a url/endpoint to insert/update a contact
	 */
	public function process() {
		$this->preprocess();
		$is_required_fields_present = $this->check_fields( $this->data, $this->required_fields );
		if ( false === $is_required_fields_present ) {
			return $this->show_fields_error();
		}
		Wcap_Activecampaign::set_headers( $this->data['api_key'] );
		$check_if_contact_exists = $this->check_if_contact_exists( $this->data['email'] );

		$params['contact'] = array( 'email' => $this->data['email'] );
		$update            = false;
		$update_array      = array( 'firstName', 'lastName', 'phone' );
		foreach ( $update_array as $key ) {
			if ( isset( $this->data[ $key ] ) ) {
				$params['contact'][ $key ] = $this->data[ $key ];
				$update                    = true;
			}
		}

		if ( ! $check_if_contact_exists ) {
			$check_if_contact_exists = $this->make_wp_requests( $this->get_endpoint(), wp_json_encode( $params ), Wcap_Activecampaign::get_headers(), Wcap_Call::$POST ); // phpcs:ignore
		} elseif ( $update ) {
			$this->contact_id = $check_if_contact_exists['body']['contact']['id'];
			$update_contact = $this->make_wp_requests( $this->get_endpoint(), wp_json_encode( $params ), Wcap_Activecampaign::get_headers(), Wcap_Call::$PUT ); // phpcs:ignore
		}

		$check_if_customer_exists = $this->check_if_customer_exists( $this->data['email'], $this->data['connectionid'] );
		if ( ! $check_if_customer_exists ) {

			$params['ecomCustomer']['email']           = $this->data['email'];
			$params['ecomCustomer']['connectionid']    = $this->data['connectionid'];
			$res                                       = $this->make_wp_requests( $this->get_customr_endpoint(), wp_json_encode( $params ), Wcap_Activecampaign::get_headers(), Wcap_Call::$POST ); // phpcs:ignore
			if ( 200 !== absint( $res['response'] ) ) {
				return false;
			}
			$res['body']['ecomCustomer']['contact_id'] = $check_if_contact_exists['body']['contact']['id'];
			return $res;

		} else {
			$check_if_customer_exists['body']['ecomCustomer']['contact_id'] = $check_if_contact_exists['body']['contact']['id'];
			return $check_if_customer_exists;
		}

	}

	/**
	 * Check if contact exists
	 *
	 * @param string $email - Email id to check connection.
	 * @param int    $connectionid - connection id to check.
	 */
	private function check_if_contact_exists( $email = '' ) {

		$activecampaign_customer_details = wcap_get_cart_session( 'activecampaign_customer_details' );
		if ( isset( $activecampaign_customer_details['email'] ) && $activecampaign_customer_details['email'] === $email ) {
			$res['body']['contact']       = $activecampaign_customer_details;
			$res['body']['contact']['id'] = $activecampaign_customer_details['contact_id'];
			return $res;
		}
		$endpoint = $this->get_endpoint() . '?email=' . rawurlencode( $email ); // . '&filters[connectionid]=' . $connectionid;

		$response = $this->make_wp_requests( $endpoint, '', Wcap_Activecampaign::get_headers(), Wcap_Call::$GET ); //phpcs:ignore
		if ( isset( $response['body']['contacts'][0] ) ) {
			$res['body']['contact'] = $response['body']['contacts'][0];
			return $res;
		}
		return false;
	}

	/**
	 * Return the endpoint.
	 *
	 * @return string
	 */
	public function get_endpoint() {
		if ( ! empty( $this->contact_id ) ) {
				return $this->data['api_url'] . '/api/3/contacts/' . $this->contact_id;
		}
		return $this->data['api_url'] . '/api/3/contacts';
	}

	/**
	 * Check if Customer exists
	 *
	 * @param string $email - Email id to check connection.
	 * @param int    $connectionid - connection id to check.
	 */
	private function check_if_customer_exists( $email = '', $connectionid = '' ) {

		$activecampaign_customer_details = wcap_get_cart_session( 'activecampaign_customer_details' );
		if ( isset( $activecampaign_customer_details['email'] ) && $activecampaign_customer_details['email'] === $email ) {
			$res['body']['ecomCustomer'] = $activecampaign_customer_details;
			return $res;
		}
		$endpoint = $this->get_customr_endpoint() . '?filters[email]=' . rawurlencode( $email ) . '&filters[connectionid]=' . $connectionid;

		$response = $this->make_wp_requests( $endpoint, '', Wcap_Activecampaign::get_headers(), Wcap_Call::$GET ); //phpcs:ignore
		if ( 200 !== absint( $response['response'] ) ) {
			return false;
		}
		if ( isset( $response['body']['ecomCustomers'][0] ) ) {
			$res['body']['ecomCustomer'] = $response['body']['ecomCustomers'][0];
			return $res;
		}
		return false;
	}
	
	/**
	 * Return the endpoint.
	 *
	 * @return string
	 */
	public function get_customr_endpoint() {
		if ( ! empty( $this->customer_id ) ) {
				return $this->data['api_url'] . '/api/3/ecomCustomers/' . $this->customer_id;
		}
		return $this->data['api_url'] . '/api/3/ecomCustomers';
	}

}

return 'Wcap_Activecampaign_Upsert_Contact';
