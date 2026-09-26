<?php
/**
 * Insert/Update Contact in MC.
 *
 * @package Connectors/Mailjet/Actions
 */

/**
 * Insert/Update contact in MAiljet.
 */
class Wcap_Mailjet_Upsert_Contact extends Wcap_Call {
	/**
	 * Class slug.
	 *
	 * @var $call_slug - string
	 */
	public $call_slug = 'wcap_mailjet_upsert_contact';
	/**
	 * Class Instance.
	 *
	 * @var $ins
	 */
	private static $ins = null;
	/**
	 * API url.
	 *
	 * @var $url
	 */
	public $url = 'https://api.mailjet.com/v3/REST/';

	/**
	 * Construct.
	 */
	public function __construct() {
		$this->required_fields = array( 'api_key', 'api_user', 'Email', 'ListID' );
	}

	/**
	 * Get class instance.
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Preprocess API request to get List.
	 */
	private function preprocess() {

	}

	/**
	 * Process API request to get Add/update.
	 */
	public function process() {
		$this->preprocess();
		$is_required_fields_present = $this->check_fields( $this->data, $this->required_fields );
		if ( false === $is_required_fields_present ) {
			return $this->show_fields_error();
		}
		Wcap_Mailjet::set_headers( $this->data['api_user'], $this->data['api_key'] );
		$contact = $contact_exists = $this->check_if_contact_exists( $this->data['Email'] );

		if ( ! $contact_exists ) {
			$params['Email'] = $this->data['Email'];
			$params['Name']  = isset( $this->data['Name'] ) ? $this->data['Name'] : '';
			$response        = $this->make_wp_requests( $this->get_endpoint(), wp_json_encode( $params ), Wcap_Mailjet::get_headers(), Wcap_Call::$POST ); //phpcs:ignore
			if ( 200 !== absint( $response['response'] ) ) {
				return $response;
			}			
			$contact             = $response['body']['Data'][0];
			$contact['response'] = $response['response'];
		}
		$data = array(
			'Data' => array(),
		);
		$update = false;
		if ( isset( $this->data['Name'] ) ) {
			$data['Data'][] = array(
				'Name'  => 'name',
				'Value' => $this->data['Name'],
			);
			$update         = true;
		}
		if ( isset( $this->data['phone'] ) ) {
			$data['Data'][] = array(
				'Name'  => 'phone',
				'Value' => $this->data['phone'],
			);
			$update         = true;
		}
		if ( isset( $this->data['FirstName'] ) ) {
			$data['Data'][] = array(
				'Name'  => 'firstname',
				'Value' => $this->data['FirstName'],
			);
			$update         = true;
		}
		if ( isset( $this->data['LastName'] ) ) {
			$data['Data'][] = array(
				'Name'  => 'lastname',
				'Value' => $this->data['LastName'],
			);
			$update         = true;
		}
		if ( isset( $this->data['country'] ) ) {
			$data['Data'][] = array(
				'Name'  => 'country',
				'Value' => $this->data['country'],
			);
			$update         = true;
		}
		if ( $update ) {
			$response = $this->make_wp_requests( $this->url . '/contactdata/'.$contact['ID'], wp_json_encode( $data ), Wcap_Mailjet::get_headers(), Wcap_Call::$PUT ); //phpcs:ignore
		} elseif ( $contact_exists ) {
				return $contact;
		}
		if ( ! isset( $this->data['ListID'] ) ) {
			return $contact;
		}
		$result = $this->add_contact_to_list( $contact, $this->data['ListID'] );
		return $contact;
	}

	/**
	 * Check if contact already exists.
	 * 
	 * @param string $email - email id.
	 */
	private function check_if_contact_exists( $email = '' ) {

		$mailjet_customer_details = wcap_get_cart_session( 'mailjet_customer_details' );
		if ( isset( $mailjet_customer_details['Email'] ) && $mailjet_customer_details['Email'] === $email ) {
			$mailjet_customer_details['response'] = 200;
			return $mailjet_customer_details;
		}
		$endpoint = $this->get_endpoint() . $email;

		$response  = $this->make_wp_requests( $endpoint, '', Wcap_Mailjet::get_headers(), Wcap_Call::$GET ); //phpcs:ignore
		if ( isset( $response['body']['Data'][0] ) ) {
			$response['body']['Data'][0]['response'] = $response['response'];
			return $response['body']['Data'][0];
		}
		return false;
	}

	/**
	 * Add contact to list after creating it or finding it.
	 * 
	 * @param array  $contact - Contact data in array.
	 * @param string $list_id - list_id to add the contact to.
	 */
	private function add_contact_to_list( $contact, $list_id ) {

		$mailjet_customer_details = wcap_get_cart_session( 'mailjet_customer_details' );
		if ( isset( $mailjet_customer_details['Email'] ) && $mailjet_customer_details['Email'] === $contact['Email']
		&& isset( $mailjet_customer_details['ListID'] ) && $mailjet_customer_details['Email'] === $list_id ) {
			return $mailjet_customer_details;
		}

		$endpoint                  = $this->url . 'contact/' . $contact['ID'] . '/managecontactslists';
		$params['ContactsLists'][] = array(
			'Action' => 'addforce',
			'ListID' => $list_id,
		);
		$response  = $this->make_wp_requests( $endpoint, wp_json_encode( $params ), Wcap_Mailjet::get_headers(), Wcap_Call::$POST ); //phpcs:ignore
		if ( 200 !== absint( $response['response'] ) ) {
			return $response;
		}
		if ( isset( $response['body']['Data'][0] ) ) {
			$contact_list        = $response['body']['Data'][0];
			$contact['ListID']   = $contact_list['ContactsLists'][0]['ListID'];
			$contact['response'] = $response['response'];
			return $contact;
		}

		return $contact;

	}

	/**
	 * Return the endpoint.
	 *
	 * @return string
	 */
	public function get_endpoint() {
		return $this->url . '/contact/';
	}

}

return 'Wcap_Mailjet_Upsert_Contact';
