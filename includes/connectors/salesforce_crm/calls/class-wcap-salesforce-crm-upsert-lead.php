<?php
/**
 * AC insert and update file
 *
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Connectors/Salesforce
 */

/**
 * Class for Salesforce CRM upsert lead
 */
class Wcap_Salesforce_Crm_Upsert_Lead extends Wcap_Call {

	/**
	 * Signle call slug
	 *
	 * @var $ins
	 */
	public $call_slug = 'wcap_salesforce_crm_upsert_lead';
	/**
	 * Signle instance of the class
	 *
	 * @var $ins
	 */
	private static $ins = null;

	/**
	 * Construct. Add hooks and filters.
	 *
	 * @var array All calls with object.
	 */
	public function __construct() {
		$this->required_fields = array( 'api_key', 'email' );
	}

	/**
	 * Function to get instance.
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}
	/**
	 * Return the Response from request.
	 *
	 * @return string
	 */
	public function process() {

		Wcap_Salesforce_Crm::set_headers( $this->get_accesstoken() );
		$params = $this->data;

		$res = $this->make_wp_requests( $this->get_endpoint(), wp_json_encode( $params ), Wcap_Salesforce_Crm::get_headers(), Wcap_Call::$POST );//phpcs:ignore

		if ( 401 === absint( $res['response'] ) ) {
			// Token expired, refresh it.
			$connection = Wcap_Salesforce_Crm_Connection::get_instance();
			$connection->refresh_salesforce_access_token();
			Wcap_Salesforce_Crm::set_headers( $this->get_accesstoken() );
			$res = $this->make_wp_requests( $this->get_endpoint(), wp_json_encode( $params ), Wcap_Salesforce_Crm::get_headers(), Wcap_Call::$POST );//phpcs:ignore
		}

		return $res;
	}

	/**
	 * Return the Accesstoken.
	 *
	 * @return string
	 */
	public function get_accesstoken() {
		$access_token = '';
		$access_token = get_option( 'wcap_salesforce_access_token' );
		return $access_token;
	}

	/**
	 * Return the endpoint.
	 *
	 * @return string
	 */
	public function get_endpoint() {
		$instance_url = get_option( 'wcap_salesforce_instance_url' );
		$data_center  = Wcap_Salesforce_Crm::get_data_center( $this->get_accesstoken() );
		$url          = $instance_url . '/services/data/v50.0/sobjects/Lead/';

		return $url;
	}

}

return 'Wcap_Salesforce_Crm_Upsert_Lead';
