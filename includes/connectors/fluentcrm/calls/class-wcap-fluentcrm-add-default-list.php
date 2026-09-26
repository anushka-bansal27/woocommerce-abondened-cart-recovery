<?php
/**
 * FluentCRM add default list file
 *
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Connectors/fluentcrm
 */

/**
 * Get lists from fluentcrm
 */
class Wcap_Fluentcrm_Add_Default_List extends Wcap_Call {

	/**
	 * Slug name of the class
	 *
	 * @var string $call_slug.
	 */
	public $call_slug = 'wcap_fluentcrm_add_default_list';
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
	 * Preprocess get lists from fluentcrm.
	 */
	public function process() {
		$is_required_fields_present = $this->check_fields( $this->data, $this->required_fields );
		if ( false === $is_required_fields_present ) {
			return $this->show_fields_error();
		}

		$body['title'] = 'Abandoned Cart';
		$body['slug']  = 'abandoned-cart';

		Wcap_Fluentcrm::set_headers( $this->data['api_name'], $this->data['api_key'] );

		$result = $this->make_wp_requests( $this->get_endpoint(), wp_json_encode( $body ), Wcap_Fluentcrm::get_headers(), Wcap_Call::$POST ); // phpcs:ignore
		return $result;
	}

	/**
	 * Return the endpoint.
	 *
	 * @return string
	 */
	public function get_endpoint() {
		return home_url() . '/wp-json/fluent-crm/v2/lists'; //phpcs:ignore
	}

}

return 'Wcap_Fluentcrm_Add_Default_List';
