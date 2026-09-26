<?php
/**
 * AC insert and update abandon cart
 *
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Connectors/klaviyo
 */

/**
 * Insert and Update an Abandon cart to a contact.
 */
class Wcap_Klaviyo_Upsert_Cart extends Wcap_Call {

	/**
	 * Slug name of the class
	 *
	 * @var string $call_slug.
	 */
	public $call_slug = 'wcap_klaviyo_upsert_cart';
	/**
	 * Single instance of the class.
	 *
	 * @var object $ins.
	 */
	public $api_url = 'https://a.klaviyo.com/api/events';
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

	}
	/**
	 * Process the REST call to a url/endpoint to insert/update a cart.
	 */
	public function process() {
		$this->preprocess();
		$is_required_fields_present = $this->check_fields( $this->data, $this->required_fields );
		if ( false === $is_required_fields_present ) {
			return $this->show_fields_error();
		}
		$email = $this->data['attributes']['profile']['data']['attributes']['email'];
		if ( ! is_email( $email ) ) {
			return array(
				'response' => 502,
				'body'     => array( 'Email is not valid' ),
			);
		}
		$body = wp_json_encode( array( 'data' => $this->data ) );
		Wcap_Klaviyo::set_headers();

		return $this->make_wp_requests( $this->api_url, $body, Wcap_Klaviyo::get_headers(), Wcap_Call::$POST ); // phpcs:ignore
	}
}

return 'Wcap_Klaviyo_Upsert_Cart';
