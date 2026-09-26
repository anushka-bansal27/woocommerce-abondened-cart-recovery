<?php
/**
 * Contact Insert Update file
 *
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Connectors/sendinblue
 */

/**
 * Insert/Update Contact in AC.
 *
 * @package Abandoned-Cart-Pro-for-WooCommerce/Connectors/sendinblue
 */
class Wcap_Sendinblue_Upsert_Contact_Action {
	/**
	 * Slug Name
	 *
	 * @var $slug
	 */
	public $slug = 'wcap_sendinblue';
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
		if ( 'active' !== $connector_settings['status'] ) {
			return false;
		}
		// Guest email address captured.
		add_action( 'wcap_cart_history_after_insert', array( &$this, 'wcap_cart_inserted' ), 98, 2 );
		add_action( 'wcap_after_update_cart_history', array( &$this, 'wcap_cart_updated' ), 98, 2 );
		add_action( 'wcap_after_update_guest_cart_history', array( &$this, 'wcap_guest_updated' ), 98, 2 );
		if ( ! WC()->session ) {
			WC()->initialize_session();
		}
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
			$merge_fields = $this->wcap_sendinblue_user_details( $customer_details );
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
	 * Create contact in Sendinblue.
	 *
	 * @param string $email - Email Address.
	 * @param array  $merge_fields - Merge fields.
	 */
	public function wcap_create_contact( $email, $merge_fields = array() ) {
		$connector_mc = Wcap_Sendinblue::get_instance();
		$call         = $connector_mc->registered_calls['wcap_sendinblue_upsert_contact'];
		// Fetch the connector data.
		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( $this->slug );
		$api_key            = isset( $connector_settings['api_key'] ) ? $connector_settings['api_key'] : '';
		$list_id            = isset( $connector_settings['default_list'] ) ? $connector_settings['default_list'] : '';
		$status             = isset( $connector_settings['status'] ) ? $connector_settings['status'] : '';

		if ( 'active' === $status && '' !== $email ) {
			$params = array(
				'api_key' => $api_key,
				'email'   => $email,
				'list_id' => $list_id,
			);

			if ( is_array( $merge_fields ) && ! empty( $merge_fields ) ) {
				foreach ( $merge_fields as $field => $value ) {
					$params[ $field ] = $value;
				}
			}
			$sendinblue_customer_details = wcap_get_cart_session( 'sendinblue_customer_details' );
			if ( isset( $sendinblue_customer_details['id'] ) && $email === $sendinblue_customer_details['email'] ) {
				$params['id'] = $sendinblue_customer_details['id'];
			}

			$call->set_data( $params );
			$result = $call->process();
			if ( ! empty( $result['id'] ) ) {
				wcap_set_cart_session(
					'sendinblue_customer_details',
					$result
				);
			}
			// Update the status of the integration for the cart in the table.
		}
	}

	/**
	 * Setup Sendinblue user details.
	 *
	 * @param array $customer_details - Customer Details.
	 * @return array $merge_fields - Merge fields.
	 */
	public function wcap_sendinblue_user_details( $customer_details ) {
  
		$firstname = isset( $customer_details['firstname'] ) ? $customer_details['firstname'] : '';
		$lastname  = isset( $customer_details['lastname'] ) ? $customer_details['lastname'] : '';
		$phone     = isset( $customer_details['phone'] ) ? $customer_details['phone'] : '';
		$country   = isset( $customer_details['country'] ) ? $customer_details['country'] : '';
		$user_id   = isset( $customer_details['user_id'] ) ? $customer_details['user_id'] : 0;
		if ( $phone ) {
			$phone = $this->wcap_sendinblue_add_country_code( $phone, $country, $user_id );
		}
		$merge_fields = array();
		if ( isset( $firstname ) && '' !== $firstname ) {
			$merge_fields['FIRSTNAME'] = $firstname;
		}
		if ( isset( $lastname ) && '' !== $lastname ) {
			$merge_fields['LASTNAME'] = $lastname;
		}
		if ( isset( $phone ) && '' != $phone ) { // phpcs:ignore
			$merge_fields['SMS'] = $phone;
		}

		return $merge_fields;
	}

	/**
	 * Add country code to phone number before sending it to Sendinblue.
	 *
	 * @param string     $phone - Phone number.
	 * @param string     $billing_country - Billing Country code.
	 * @param int|string $user_id - User ID.
	 * @return string    $phone - Phone number with Dial Code.
	 */
	public function wcap_sendinblue_add_country_code( $phone, $billing_country, $user_id ) {
		global $wpdb;
		$country_map     = Wcap_Common::wcap_country_code_map();
		$billing_country = apply_filters( 'wcap_default_sms_country_code', $billing_country, $user_id );
		$dial_code       = isset( $country_map[ $billing_country ] ) ? $country_map[ $billing_country ]['dial_code'] : '';
		$phone           = str_replace( array( '-', '(', ')', ' ' ), '', $phone );
		if ( is_numeric( $phone ) && strlen( $phone ) > 9 ) { // Check if the number is valid.
			// If first character is 0, remove it.
			$phone = ltrim( $phone, '0' );
			// If first character is not a +, add it.
			if ( '+' !== substr( $phone, 0, 1 ) ) {
				if ( '' !== $dial_code && strlen( $phone ) < 11 ) {
					$phone = $dial_code . $phone;
				} else {
					$phone = '+' . $phone;
				}
			}
		} else {
			$phone = '';
		}

		return $phone;
	}

}
new Wcap_Sendinblue_Upsert_Contact_Action();
