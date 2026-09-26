<?php
/**
 * FluentCRM add custom fields for contact file
 *
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Connectors/fluentcrm
 */

/**
 * Insert and Update an Abandon cart to a contact.
 */
class Wcap_Fluentcrm_ADD_Custom_Fields extends Wcap_Call {

	/**
	 * Slug name of the class
	 *
	 * @var string $call_slug.
	 */
	public $call_slug = 'wcap_fluentcrm_add_custom_fields';
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
		$this->required_fields = array( 'api_name', 'api_key' );
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

		$params = array();

		Wcap_Fluentcrm::set_headers( $this->data['api_name'], $this->data['api_key'] );

		$endpoint = $this->get_endpoint();
		$params   = $this->data;
		unset( $params['api_name'] );
		unset( $params['api_key'] );

		$data                    = wp_json_encode( $params );
		$check_if_contact_exists = $this->make_wp_requests( $endpoint, $data, Wcap_Fluentcrm::get_headers(), Wcap_Call::$PUT ); // phpcs:ignore

		return $check_if_contact_exists['body'];
	}
	/**
	 * Return the endpoint.
	 *
	 * @return string
	 */
	public function get_endpoint() {
		return home_url() . '/wp-json/fluent-crm/v2/custom-fields/contacts'; //phpcs:ignore
	}
}

return 'Wcap_Fluentcrm_ADD_Custom_Fields';
