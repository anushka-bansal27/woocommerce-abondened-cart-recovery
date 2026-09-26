<?php
/**
 * AC insert and update abandon cart
 *
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Connectors/sendinblue
 */

/**
 * Insert and Update an Abandon cart to a contact.
 */
class Wcap_Sendinblue_Upsert_Cart extends Wcap_Call {

	/**
	 * Slug name of the class
	 *
	 * @var string $call_slug.
	 */
	public $call_slug = 'wcap_sendinblue_upsert_cart';
	/**
	 * Single instance of the class.
	 *
	 * @var object $ins.
	 */
	public $api_url = 'https://in-automate.sendinblue.com/api/v2/trackEvent';
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
		$this->required_fields = array( 'ma-key' );
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
		$email = rawurldecode( $this->data['email'] );
		if ( ! is_email( $email ) ) {
			return array(
				'response' => 502,
				'body'     => array( 'Email is not valid' ),
			);
		}

		$headers  = array(
			'Accept'       => 'application/json',
			'Content-Type' => 'application/json',
			'api-key'      => $this->data['ma-key'],
			'ma-key'       => $this->data['ma-key'],
		);
		$endpoint = $this->api_url;
		$body     = wp_json_encode( $this->data );

		return $this->make_wp_requests( $endpoint, $body, $headers, Wcap_Call::$POST ); // phpcs:ignore
	}
}

return 'Wcap_Sendinblue_Upsert_Cart';
