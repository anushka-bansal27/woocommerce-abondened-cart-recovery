<?php
/**
 * AC insert and update abandon cart
 *
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Connectors/ActiveCampaign
 */

/**
 * Insert and Update an Abandon cart to a contact.
 */
class Wcap_Activecampaign_Upsert_Cart extends Wcap_Call {

	/**
	 * Slug name of the class
	 *
	 * @var string $call_slug.
	 */
	public $call_slug = 'wcap_activecampaign_upsert_cart';
	/**
	 * Ecom order id
	 *
	 * @var int $ecom_order_id.
	 */
	public $ecom_order_id = 0;
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
		$this->required_fields = array( 'api_key', 'api_url', 'totalPrice' );
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

		if ( isset( $this->data['ecom_order_id'] ) ) {
			$this->ecom_order_id = $this->data['ecom_order_id'];
			unset( $this->data['ecom_order_id'] );
		}
		if ( empty( $this->ecom_order_id ) ) {
			$this->required_fields[] = 'abandonedDate';
			$this->required_fields[] = 'customerid';
			$this->required_fields[] = 'connectionid';
			$this->required_fields[] = 'currency';
			$this->required_fields[] = 'email';
			$this->required_fields[] = 'externalcheckoutid';
			$this->required_fields[] = 'externalCreatedDate';
			$this->required_fields[] = 'source';
		}
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

		if ( ! is_email( $this->data['email'] ) ) {
			return array(
				'response' => 502,
				'body'     => array( 'Email is not valid' ),
			);
		}

		Wcap_Activecampaign::set_headers( $this->data['api_key'] );
		$endpoint = $this->get_endpoint();
		unset( $this->data['api_key'] );
		unset( $this->data['api_url'] );
		$params['ecomOrder'] = $this->data;

		$body = wp_json_encode( $params );
		if ( ! empty( $this->ecom_order_id ) ) {
			return $this->make_wp_requests( $endpoint, $body, Wcap_Activecampaign::get_headers(), Wcap_Call::$PUT  );
		}

		return $this->make_wp_requests( $endpoint, $body, Wcap_Activecampaign::get_headers(), Wcap_Call::$POST  );
	}

	/**
	 * Return the endpoint.
	 *
	 * @return string
	 */
	public function get_endpoint() {
		if ( ! empty( $this->ecom_order_id ) ) {
			return $this->data['api_url'] . '/api/3/ecomOrders/' . $this->ecom_order_id;
		}
		return $this->data['api_url'] . '/api/3/ecomOrders';
	}
	/**
	 * Check if an connection-id and externalcheckoutid( cart id exists)
	 *
	 * @param string $connectionid - connection id.
	 * @param string $externalcheckoutid - externalcheckoutid, which is the abandoned cart id.
	 */
	public function check_if_order_already_updated( $connectionid, $externalcheckoutid ) {
		$endpoint = $this->get_endpoint() . '/?filters[connectionid]=' . $connectionid . '&filters[externalcheckoutid]=' . $externalcheckoutid;
		$result   = $this->make_wp_requests( $endpoint, array(), Wcap_Activecampaign::get_headers(), Wcap_Call::$GET );
		return $result;
	}

}

return 'Wcap_Activecampaign_Upsert_Cart';
