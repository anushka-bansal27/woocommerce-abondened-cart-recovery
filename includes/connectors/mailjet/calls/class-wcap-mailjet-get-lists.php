<?php
/**
 * Insert/Update Contact in MC.
 *
 * @package Connectors/Mailjet/Actions
 */

/**
 * Get ContactsLists in MAiljet.
 */
class Wcap_Mailjet_Get_Lists extends Wcap_Call {
	/**
	 * Class slug.
	 *
	 * @var $call_slug - string
	 */
	public $call_slug = 'wcap_mailjet_get_lists';
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

		$this->required_fields = array( 'api_user', 'api_key' );
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
	 * Process API request to get List.
	 */
	public function process() {
		$is_required_fields_present = $this->check_fields( $this->data, $this->required_fields );
		if ( false === $is_required_fields_present ) {
			return $this->show_fields_error();
		}

		Wcap_Mailjet::set_headers( $this->data['api_user'], $this->data['api_key'] );

		$params = array();
		if ( isset( $this->data['offset'] ) && 0 < absint( $this->data['offset'] ) ) {
			$params['offset'] = $this->data['offset'];
		}
		if ( isset( $this->data['limit'] ) && 0 < absint( $this->data['limit'] ) ) {
			$params['limit'] = $this->data['limit'];
		}

		return $this->make_wp_requests( $this->get_endpoint(), $params, Wcap_Mailjet::get_headers(), Wcap_Call::$GET ); // phpcs:ignore
	}

	/**
	 * Return the endpoint.
	 *
	 * @return string
	 */
	public function get_endpoint() {
		return $this->url . '/contactslist';
	}

}

return 'Wcap_Mailjet_Get_Lists';
