<?php
/**
 * AC insert and update file
 *
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Connectors/sendinblue
 */

/**
 * Get lists from sendinblue
 */
class Wcap_Sendinblue_Get_Lists extends Wcap_Call {

	/**
	 * Slug name of the class
	 *
	 * @var string $call_slug.
	 */
	public $call_slug = 'wcap_sendinblue_get_lists';
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
		$this->required_fields = array( 'api_key' );
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
	 * Preprocess get lists from sendinblue.
	 */
	public function process() {
		$is_required_fields_present = $this->check_fields( $this->data, $this->required_fields );
		if ( false === $is_required_fields_present ) {
			return $this->show_fields_error();
		}

		Wcap_Sendinblue::set_headers( $this->data['api_key'] );

		return $this->make_wp_requests( $this->get_endpoint(),'', Wcap_Sendinblue::get_headers(), Wcap_Call::$GET ); //phpcs:ignore
	}

	/**
	 * Return the endpoint.
	 *
	 * @return string
	 */
	public function get_endpoint() {
		return 'https://api.sendinblue.com/v3/contacts/lists?limit=10&offset=0&sort=desc';
	}

}

return 'Wcap_Sendinblue_Get_Lists';
