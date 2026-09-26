<?php
/**
 * Insert/Update Contact in MC.
 *
 * @package Connectors/Mailchimp/Actions
 */

/**
 * Insert/Update Contact in MC.
 */
class Wcap_Mailchimp_Upsert_Contact_Action {

	/**
	 * Class Instance.
	 *
	 * @var $ins
	 */
	private $ins = null;

	/**
	 * Construct.
	 */
	public function __construct() {
		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( 'wcap_mailchimp' );
		if ( empty( $connector_settings ) || 'active' !== $connector_settings['status'] ) {
			return false;
		}
		// Guest email address captured.
		add_action( 'wcap_guest_cart_history_after_insert', array( &$this, 'wcap_contact_inserted' ), 99, 1 );
		add_action( 'wcap_after_update_guest_cart_history', array( &$this, 'wcap_contact_updated' ), 99, 2 );
		// Cart Updated.
		add_action( 'wcap_after_update_cart_history', array( &$this, 'wcap_cart_updated' ), 99, 2 );
		// Cart Inserted.
		add_action( 'wcap_cart_history_after_insert', array( &$this, 'wcap_cart_inserted' ), 99, 1 );
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
	 * @param int $user_id - User ID.
	 */
	public function wcap_contact_inserted( $user_id = 0 ) {

		if ( $user_id > 0 ) {

			$abandoned_id = wcap_get_abandoned_id_from_user_id( $user_id );
			if ( $abandoned_id > 0 ) {
				$this->wcap_prepare_contact( $abandoned_id );
			}
		}
	}

	/**
	 * Guest record updated.
	 *
	 * @param array $value - Updated columns and their values.
	 * @param array $where - Where condition for update.
	 */
	public function wcap_contact_updated( $value = array(), $where = array() ) {
		$user_id = 0;
		if ( is_array( $where ) && array_key_exists( 'id', $where ) ) {
			$user_id = $where['id'];
		} elseif ( is_array( $value ) && array_key_exists( 'id', $value ) ) {
			$user_id = $value['id'];
		}
		$abandoned_id = wcap_get_abandoned_id_from_user_id( $user_id );
		if ( $abandoned_id > 0 ) {
			$this->wcap_prepare_contact( $abandoned_id );
		}
	}

	/**
	 * Abandoned Cart record inserted.
	 *
	 * @param int $abandoned_id - Abandoned Cart ID.
	 */
	public function wcap_cart_inserted( $abandoned_id = 0 ) {

		if ( $abandoned_id > 0 ) {
			$this->wcap_prepare_contact( $abandoned_id );
		}
	}

	/**
	 * Abandoned Cart record updated.
	 *
	 * @param array $value - Updated columns and their values.
	 * @param array $where - Where condition for update.
	 */
	public function wcap_cart_updated( $value = array(), $where = array() ) {
		$abandoned_id = 0;
		if ( is_array( $where ) && array_key_exists( 'id', $where ) ) {
			$abandoned_id = $where['id'];
		} elseif ( is_array( $value ) && array_key_exists( 'id', $value ) ) {
			$abandoned_id = $value['id'];
		}

		if ( $abandoned_id > 0 ) {
			$this->wcap_prepare_contact( $abandoned_id );
		}
	}

	/**
	 * Prepare contact details for MC.
	 *
	 * @param int $abandoned_id - Abandoned Cart ID.
	 */
	public function wcap_prepare_contact( $abandoned_id ) {
		// Fetch the contact data.
		$common_inst      = Wcap_Connectors_Common::get_instance();
		$customer_details = $common_inst->wcap_get_contact_data( $abandoned_id );

		if ( is_array( $customer_details ) && count( $customer_details ) > 0 ) {
			$merge_fields = $this->wcap_mailchimp_user_details( $customer_details );
			$email        = isset( $customer_details['email'] ) ? $customer_details['email'] : '';
			$this->wcap_create_contact( $email, $merge_fields );
		}
	}

	/**
	 * Create contact in Mailchimp.
	 *
	 * @param string $email - Email Address.
	 * @param array  $merge_fields - Merge fields.
	 * @param array  $interests - Interests.
	 */
	public function wcap_create_contact( $email, $merge_fields = array(), $interests = array() ) {
		$connector_mc = Wcap_Mailchimp::get_instance();
		$call         = $connector_mc->registered_calls['wcap_mailchimp_upsert_contact'];
		// Fetch the connector data.
		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( 'wcap_mailchimp' );
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
				$params['merge_fields'] = $merge_fields;
			}

			if ( is_array( $interests ) && ! empty( $interests ) ) {
				$params['interests'] = $interests;
			}
			$call->set_data( $params );
			$result = $call->process();
			// Update the status of the integration for the cart in the table.
		}
	}

	/**
	 * Setup Mailchimp user details.
	 *
	 * @param array $customer_details - Customer Details.
	 * @return array $merge_fields - Merge fields.
	 */
	public function wcap_mailchimp_user_details( $customer_details ) {
		$merge_fields = array();

		$firstname = $customer_details['firstname'];
		$lastname  = $customer_details['lastname'];
		if ( isset( $firstname ) && '' !== $firstname ) {
			$merge_fields['FNAME'] = $firstname;
		}
		if ( isset( $lastname ) && '' !== $lastname ) {
			$merge_fields['LNAME'] = $lastname;
		}

		return $merge_fields;
	}

}
new Wcap_Mailchimp_Upsert_Contact_Action();
