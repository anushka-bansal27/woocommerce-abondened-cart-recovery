<?php
/**
 * AC insert and update file
 *
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Connectors/sendinblue
 */

/**
 * Insert and Update an contact on sendinblue
 */
class Wcap_Sendinblue_Upsert_Contact extends Wcap_Call {
	/**
	 * Slug name of the class
	 *
	 * @var string $call_slug.
	 */
	public $call_slug = 'wcap_sendinblue_upsert_contact';

	/**
	 * Base url of the API
	 *
	 * @var string $api_url.
	 */

	public $api_url = 'https://a.sendinblue.com/api/v3/';
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
		$this->required_fields = array();
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

		$update = false;

		Wcap_Sendinblue::set_headers( $this->data['api_key'] );

		$check_if_contact_exists = $this->check_if_contact_exists( $this->data['email'] );

		$update_array = array( 'FIRSTNAME', 'LASTNAME', 'SMS' );
		foreach ( $update_array as $key ) {
			if ( isset( $this->data[ $key ] ) ) {
				$params[ $key ] = $this->data[ $key ];
				$update = true;
			}
		}
		$add_to_list = true;

		if ( isset( $check_if_contact_exists['listIds'] ) && in_array( $this->data['list_id'], $check_if_contact_exists['listIds'], true ) ) {
			$add_to_list = false;
		}

		$endpoint = $this->get_endpoint();

		if ( isset( $check_if_contact_exists['id'] ) ) {
			if ( $update ) {
				$method   = Wcap_Call::$PUT; // phpcs:ignore
				$endpoint = $endpoint . rawurlencode( $this->data['email'] );

				if ( ! empty( $params ) ) {
					$params['attributes'] = $params;
				}
				$data                 = wp_json_encode( $params );
				$updated              = $this->make_wp_requests( $endpoint, $data, Wcap_Sendinblue::get_headers(), $method );
			}
		} else {
			$method               = Wcap_Call::$POST; // phpcs:ignore
			if ( ! empty( $params ) ) {
				$params['attributes'] = $params;
			}
			$params['email']      = $this->data['email'];
			$params['listIds'][]  = (int) $this->data['list_id'];
			$data = wp_json_encode( $params );

			$result          = $this->make_wp_requests( $endpoint, $data, Wcap_Sendinblue::get_headers(), $method );// phpcs:ignore
			$check_if_contact_exists['email'] = $this->data['email'];
			$check_if_contact_exists['id']    = $result['body']['id'];
		}

		if ( ! $add_to_list ) {
			return $check_if_contact_exists;
		}

		$data       = wp_json_encode( array( 'emails' => array( $this->data['email'] ) ) );
		$endpoint   = $this->get_list_endpoint( $this->data['list_id'] );
		$list_added = $this->make_wp_requests( $endpoint, $data, Wcap_Sendinblue::get_headers(), Wcap_Call::$POST ); // phpcs:ignore

		return $check_if_contact_exists;
	}

	/**
	 * Check if contact exists
	 *
	 * @param string $email - Email id to check connection.
	 */
	private function check_if_contact_exists( $email = '' ) {

		$sendinblue_customer_details = wcap_get_cart_session( 'sendinblue_customer_details' );
		if ( isset( $sendinblue_customer_details['email'] ) && $sendinblue_customer_details['email'] === $email ) {
			return $sendinblue_customer_details;
		}
		$endpoint = $this->get_endpoint() . rawurlencode( $email );

		$response = $this->make_wp_requests( $endpoint, '', Wcap_Sendinblue::get_headers(), Wcap_Call::$GET ); //phpcs:ignore
		if ( ! empty( $response['body'] ) ) {
			return $response['body'];
		}
		return false;
	}

	/**
	 * Return the endpoint.
	 *
	 * @return string
	 */
	public function get_endpoint() {
		return 'https://api.sendinblue.com/v3/contacts/';
	}

	/**
	 * Check if contact exists
	 *
	 * @param string $list_id - List id to add contact to.
	 */
	public function get_list_endpoint( $list_id ) {
		return 'https://api.sendinblue.com/v3/contacts/lists/' . $list_id . '/contacts/add ';
	}

}
return 'Wcap_Sendinblue_Upsert_Contact';
