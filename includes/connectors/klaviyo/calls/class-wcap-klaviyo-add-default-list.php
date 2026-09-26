<?php
/**
 * AC insert and update file
 *
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Connectors/klaviyo
 */

/**
 * Get lists from klaviyo
 */
class Wcap_Klaviyo_Add_Default_List extends Wcap_Call {

	/**
	 * Slug name of the class
	 *
	 * @var string $call_slug.
	 */
	public $call_slug = 'wcap_klaviyo_add_default_list';
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
	 * Preprocess get lists from klaviyo.
	 */
	public function process() {
		$is_required_fields_present = $this->check_fields( $this->data, $this->required_fields );
		if ( false === $is_required_fields_present ) {
			return $this->show_fields_error();
		}
		$body = wp_json_encode(
			array(
				'data' => array(
					'type'       => 'list',
					'attributes' => array(
						'name' => 'Abandoned Cart',
					),
				),
			)
		);

		Wcap_Klaviyo::set_headers();

		return $this->make_wp_requests( $this->get_endpoint(), $body, Wcap_Klaviyo::get_headers(), Wcap_Call::$POST ); // phpcs:ignore

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

return 'Wcap_Klaviyo_Add_Default_List';
