<?php
/**
 * AC insert and update file
 *
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Connectors/ActiveCampaign
 */

/**
 * Get connections from ActiveCampaign
 */
class Wcap_Activecampaign_Add_Default_Connection extends Wcap_Call {

	/**
	 * Slug name of the class
	 *
	 * @var string $call_slug.
	 */
	public $call_slug = 'wcap_activecampaign_add_default_connection';
	/**
	 * Single instance of the class.
	 *
	 * @var object $ins.
	 */
	private static $ins = null;

	/**
	 * Class constructor.
	 */
	public function __construct() {

		$this->required_fields = array( 'api_url', 'api_key' );
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
	 * Preprocess get connections from ActiveCampaign.
	 */
	public function process() {
		$is_required_fields_present = $this->check_fields( $this->data, $this->required_fields );
		if ( false === $is_required_fields_present ) {
			return $this->show_fields_error();
		}

		Wcap_Activecampaign::set_headers( $this->data['api_key'] );

		$params = array();
		if ( isset( $this->data['offset'] ) && 0 < absint( $this->data['offset'] ) ) {
			$params['offset'] = $this->data['offset'];
		}
		if ( isset( $this->data['limit'] ) && 0 < absint( $this->data['limit'] ) ) {
			$params['limit'] = $this->data['limit'];
		}	
		$params['connection'] = $this->data;

		$body = wp_json_encode( $params );

		return $this->make_wp_requests( $this->get_endpoint(), $body, Wcap_Activecampaign::get_headers(), Wcap_Call::$POST );
	}

	/**
	 * Return the endpoint.
	 *
	 * @return string
	 */
	public function get_endpoint() {
		return $this->data['api_url'] . '/api/3/connections';
	}

}

return 'Wcap_Activecampaign_Add_Default_Connection';
