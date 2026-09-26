<?php
/**
 * AC insert and update file
 *
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Connectors/klaviyo
 */

/**
 * Get lists from klaviyo
 */
class Wcap_Klaviyo_Get_Lists extends Wcap_Call {

	/**
	 * Slug name of the class
	 *
	 * @var string $call_slug.
	 */
	public $call_slug = 'wcap_klaviyo_get_lists';
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
		$this->required_fields = array( 'private_key' );
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
	 * Preprocess get lists from klaviyo.
	 */
	public function process() {
		$is_required_fields_present = $this->check_fields( $this->data, $this->required_fields );
		if ( false === $is_required_fields_present ) {
			return $this->show_fields_error();
		}
		$private_key   = $this->data['private_key'];
		$authorization = 'Klaviyo-API-Key ' . $private_key;
		$headers       = array(
			'Authorization' => $authorization,
			'accept'        => 'application/json',
			'content-type'  => 'application/json',
			'revision'      => '2024-02-15',
		);

		$response = $this->make_wp_requests( $this->get_endpoint(), array(), $headers, Wcap_Call::$GET ); //phpcs:ignore
		return $response;
	}

	/**
	 * Return the endpoint.
	 *
	 * @return string
	 */
	public function get_endpoint() {
		return 'https://a.klaviyo.com/api/lists/';
	}

}

return 'Wcap_Klaviyo_Get_Lists';
