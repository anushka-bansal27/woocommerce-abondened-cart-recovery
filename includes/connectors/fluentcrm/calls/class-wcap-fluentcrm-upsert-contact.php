<?php
/**
 * FluentCRM insert and update contact file
 *
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Connectors/fluentcrm
 */

/**
 * Insert and Update an contact on fluentcrm
 */
class Wcap_Fluentcrm_Upsert_Contact extends Wcap_Call {
	/**
	 * Slug name of the class
	 *
	 * @var string $call_slug.
	 */
	public $call_slug = 'wcap_fluentcrm_upsert_contact';

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
	 * Process the REST call to a url/endpoint to insert/update a contact
	 */
	public function process() {
		$this->preprocess();
		$is_required_fields_present = $this->check_fields( $this->data, $this->required_fields );
		if ( false === $is_required_fields_present ) {
			return $this->show_fields_error();
		}

		$params = array();

		Wcap_Fluentcrm::set_headers( $this->data['api_name'], $this->data['api_key'] );

		$endpoint        = $this->get_endpoint();
		$params['email'] = $this->data['email'];
		$update_array    = array( 'first_name', 'last_name', 'phone' );
		foreach ( $update_array as $key ) {
			if ( isset( $this->data[ $key ] ) ) {
				$params[ $key ] = $this->data[ $key ];
			}
		}
		$params['status']         = 'subscribed';
		$params['__force_update'] = 'yes';
		$params['lists']          = array( $this->data['list_id'] );

		$data = wp_json_encode( $params );

		$check_if_contact_exists = $this->make_wp_requests( $endpoint, $data, Wcap_Fluentcrm::get_headers(), Wcap_Call::$POST ); // phpcs:ignore

		if ( isset( $check_if_contact_exists['body']['contact']['id'] ) && $this->data['remove_tags'] ) {
			$params                = $this->data['remove_tags'];
			$endpoint              = $this->get_remove_tags_endpoint();
			$params['subscribers'] = array( $check_if_contact_exists['body']['contact']['id'] );

			$deleted_tags          = $this->make_wp_requests( $endpoint, wp_json_encode( $params ), Wcap_Fluentcrm::get_headers(), Wcap_Call::$POST ); // phpcs:ignore
		}
		return $check_if_contact_exists['body'];
	}

	/**
	 * Return the endpoint.
	 *
	 * @return string
	 */
	public function get_endpoint() {
		return home_url() . '/wp-json/fluent-crm/v2/subscribers'; //phpcs:ignore
	}
	/**
	 * Return remove list endpoint.
	 *
	 * @return string
	 */
	public function get_remove_tags_endpoint() {
		return home_url() . '/wp-json/fluent-crm/v2/subscribers/sync-segments';  //phpcs:ignore
	}
}
return 'Wcap_Fluentcrm_Upsert_Contact';
