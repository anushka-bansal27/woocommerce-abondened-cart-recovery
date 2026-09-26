<?php
/**
 * Contact Insert Update file
 *
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Connectors/ActiveCampaign
 */

/**
 * Insert/Update Contact in AC.
 *
 * @package Abandoned-Cart-Pro-for-WooCommerce/Connectors/ActiveCampaign
 */
class Wcap_Activecampaign_Upsert_Contact_Action {
	/**
	 * Slug Name
	 *
	 * @var $slug
	 */
	public $slug = 'wcap_activecampaign';
	/**
	 * Class Instance.
	 *
	 * @var $ins
	 */
	private static $ins = null;

	/**
	 * Construct.
	 */
	public function __construct() {
		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( $this->slug );
		if ( empty( $connector_settings ) ) {
			return false;
		}
		if ( $connector_settings['status'] !=='active' ) {
			return false;
		}
		// Guest email address captured.
		add_action( 'wcap_cart_history_after_insert', array( &$this, 'wcap_cart_inserted' ), 98, 2 );
		add_action( 'wcap_after_update_cart_history', array( &$this, 'wcap_cart_updated' ), 98, 2 );
		add_action( 'wcap_after_update_guest_cart_history', array( &$this, 'wcap_guest_updated' ), 98, 2 );
	}

	/**
	 * Get instance.
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Guest record inserted.
	 *
	 * @param int $abandoned_id - Abandoned ID.
	 */
	public function wcap_cart_inserted( $abandoned_id = 0 ) {

		if ( $abandoned_id > 0 ) {
			$this->wcap_prepare_contact( $abandoned_id );
		}
	}

	/**
	 * Cart record updated.
	 *
	 * @param array $value - Updated columns and their values.
	 * @param array $where - Where condition for update.
	 */
	public function wcap_cart_updated( $value = array(), $where = array() ) {
		$abandoned_id = 0;
		if ( is_array( $where ) && array_key_exists( 'id', $where ) ) {
			$abandoned_id = $where['id'];
		}
		if ( $abandoned_id > 0 ) {
			$this->wcap_prepare_contact( $abandoned_id );
		}
	}
	/**
	 * Guest record updated.
	 *
	 * @param array $value - Updated columns and their values.
	 * @param array $where - Where condition for update.
	 */
	public function wcap_guest_updated( $value = array(), $where = array() ) {
		$user_id = 0;
		if ( is_array( $where ) && array_key_exists( 'id', $where ) ) {
			$user_id = $where['id'];
		} elseif ( is_array( $value ) && array_key_exists( 'id', $value ) ) {
			$user_id = $value['id'];
		}

		$abandoned_id = wcap_get_abandoned_id_from_user_id( $user_id );
		if ( $abandoned_id > 0 ) {
			$this->wcap_prepare_contact( $abandoned_id, $value['email_id'] );
		}
	}
	/**
	 * Prepare contact details for AC.
	 *
	 * @param int    $abandoned_id - Abandoned Cart ID.
	 * @param string $email - Email of the contact.
	 */
	public function wcap_prepare_contact( $abandoned_id, $email = '' ) {
		// Fetch the contact data.
		$common_inst      = Wcap_Connectors_Common::get_instance();
		$customer_details = $common_inst->wcap_get_contact_data( $abandoned_id );

		if ( is_array( $customer_details ) && count( $customer_details ) > 0 ) {
			$merge_fields = $this->wcap_activecampaign_user_details( $customer_details );
			if ( $email === '' ) { //phpcs:ignore
				$email = isset( $customer_details['email'] ) ? $customer_details['email'] : '';
			}
			if ( empty( $email ) ) {
				return;
			}
			$this->wcap_create_contact( $email, $merge_fields );
		}
	}

	/**
	 * Create contact in Activecampaign.
	 *
	 * @param string $email - Email Address.
	 * @param array  $merge_fields - Merge fields.
	 */
	public function wcap_create_contact( $email, $merge_fields = array() ) {
		$connector_mc = Wcap_Activecampaign::get_instance();
		$call         = $connector_mc->registered_calls['wcap_activecampaign_upsert_contact'];
		// Fetch the connector data.
		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( $this->slug );
		$api_url            = isset( $connector_settings['api_url'] ) ? $connector_settings['api_url'] : '';
		$api_key            = isset( $connector_settings['api_key'] ) ? $connector_settings['api_key'] : '';
		$connection_id      = isset( $connector_settings['default_connection'] ) ? $connector_settings['default_connection'] : '';
		$status             = isset( $connector_settings['status'] ) ? $connector_settings['status'] : '';

		if ( 'active' === $status && '' !== $email ) {
			$params = array(
				'api_url'      => $api_url,
				'api_key'      => $api_key,
				'email'        => $email,
				'connectionid' => $connection_id,
			);

			if ( is_array( $merge_fields ) && ! empty( $merge_fields ) ) {
				foreach ( $merge_fields as $field => $value ) {
					$params[ $field ] = $value;
				}
			}
			$activecampaign_customer_details = wcap_get_cart_session( 'activecampaign_customer_details' );
			if ( isset( $activecampaign_customer_details['id'] ) && $email === $activecampaign_customer_details['email'] ) {
				$params['customer_id'] = $activecampaign_customer_details['id'];
			}

			$call->set_data( $params );
			$result = $call->process();
			if ( isset( $result['body']['ecomCustomer'] ) ) {
				$ecom_customer = $result['body']['ecomCustomer'];
				$id            = $ecom_customer['id'];
				wcap_set_cart_session(
					'activecampaign_customer_details',
					array(
						'connectionid' => $ecom_customer['connectionid'],
						'id'           => $ecom_customer['id'],
						'contact_id'   => $ecom_customer['contact_id'],
						'email'        => $ecom_customer['email'],
					)
				);
			}
			// Update the status of the integration for the cart in the table.
		}
	}

	/**
	 * Setup Activecampaign user details.
	 *
	 * @param array $customer_details - Customer Details.
	 * @return array $merge_fields - Merge fields.
	 */
	public function wcap_activecampaign_user_details( $customer_details ) {

		$firstname    = isset( $customer_details['firstname'] ) ? $customer_details['firstname'] : '';
		$lastname     = isset( $customer_details['lastname'] ) ? $customer_details['lastname'] : '';
		$phone        = isset( $customer_details['phone'] ) ? $customer_details['phone'] : '';
		$merge_fields = array();
		if ( isset( $firstname ) && '' !== $firstname ) {
			$merge_fields['firstName'] = $firstname;
		}
		if ( isset( $lastname ) && '' !== $lastname ) {
			$merge_fields['lastName'] = $lastname;
		}
		if ( isset( $phone ) && '' !== $phone ) {
			$merge_fields['phone'] = $phone;
		}

		return $merge_fields;
	}

}
new Wcap_Activecampaign_Upsert_Contact_Action();
