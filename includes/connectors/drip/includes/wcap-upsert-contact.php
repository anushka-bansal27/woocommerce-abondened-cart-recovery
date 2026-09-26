<?php

class Wcap_Drip_Upsert_Contact_Action {

	private static $ins = null;
	public function __construct() {
		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( 'wcap_drip' );
		if ( empty( $connector_settings ) ) {
			return false;
		}

		// Guest email address captured.
		add_action( 'wcap_guest_cart_history_after_insert', array( &$this, 'wcap_contact_inserted' ), 100, 1 );
		add_action( 'wcap_after_update_guest_cart_history', array( &$this, 'wcap_contact_updated' ), 100, 2 );
		// Cart Updated.
		add_action( 'wcap_after_update_cart_history', array( &$this, 'wcap_cart_history_updated' ), 100, 2 );
		// Cart Inserted.
		add_action( 'wcap_cart_history_after_insert', array( &$this, 'wcap_cart_history_inserted' ), 100, 1 );
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function wcap_contact_inserted( $user_id = 0 ) {

		if ( $user_id > 0 ) {

			$abandoned_id = wcap_get_abandoned_id_from_user_id( $user_id );
			if ( $abandoned_id > 0 ) {
				$this->wcap_prepare_contact( $abandoned_id );
			}
		}
	}

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

	public function wcap_cart_history_inserted( $abandoned_id = 0 ) {

		if ( $abandoned_id > 0 ) {
			$this->wcap_prepare_contact( $abandoned_id );
		}
	}

	public function wcap_cart_history_updated( $value = array(), $where = array() ) {
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

	public function wcap_prepare_contact( $abandoned_id, $return_status = false ) {
		// Fetch the contact data.
		$common_inst      = Wcap_Connectors_Common::get_instance();
		$customer_details = $common_inst->wcap_get_contact_data( $abandoned_id );

		if ( is_array( $customer_details ) && count( $customer_details ) > 0 ) {
			$merge_fields = $this->wcap_drip_user_details( $customer_details );
			$cart_sync_id = $common_inst->wcap_get_cart_status( $abandoned_id, 'drip' );
			$action       = $cart_sync_id > 0 ? 'update' : 'insert';
			$email        = isset( $customer_details['email'] ) ? $customer_details['email'] : '';

			if ( '' !== $email ) {
				$status = $this->wcap_create_contact( $abandoned_id, $email, $action, $cart_sync_id, $merge_fields );
				if ( $return_status ) {
					return $status;
				}
			}
		}
	}

	public function wcap_create_contact( $abandoned_id, $email, $action = 'insert', $cart_sync_id = 0, $merge_fields = array(), $interests = array() ) {

		$connector_dp = Wcap_Drip::get_instance();
		$call         = $connector_dp->registered_calls['wcap_drip_upsert_contact'];

		// Fetch the connector data.
		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( 'wcap_drip' );
		$api_token          = isset( $connector_settings['api_token'] ) ? $connector_settings['api_token'] : '';
		$account_id         = isset( $connector_settings['account_id'] ) ? $connector_settings['account_id'] : '';
		$status             = isset( $connector_settings['status'] ) ? $connector_settings['status'] : '';

		if ( 'active' === $status && '' !== $email ) {

			$params = array(
				'api_token'  => $api_token,
				'account_id' => $account_id,
				'email'      => $email,
			);

			if ( 'update' === $action ) {
				$drip_details = json_decode( wcap_get_cart_session( 'drip_customer_details' ), true );
				if ( $drip_details['email'] !== $email ) {
					$params['new_email'] = $email;
					$params['email']     = $drip_details['email'];
				}
			}
			if ( is_array( $merge_fields ) && ! empty( $merge_fields ) ) {
				$params['merge_fields'] = $merge_fields;
			}

			$call->set_data( $params );
			$result = $call->process();

			$resp = wp_remote_retrieve_body( $result );

			// Update the status of the integration for the cart in the table.
			$message = $result['response'];
			if ( 200 !== absint( $result['response'] ) ) {
				$error = __( 'Error Response Code: ', 'woocommerce-ac' ) . $result['response'] . __( '. Drip Error: ', 'woocommerce-ac' );
				$error .= isset( $resp['message'] ) ? $resp['message'] : __( 'No Response from Drip. ', 'woocommerce-ac' );
				$error .= ( 502 === absint( $result['response'] ) ) ? __( 'Wcap Error: ', 'woocommerce-ac' ) . $resp['category'] : '';

				$connector_cart_id = '';
				$status            = 'failed';
				$message          .= ': ' . $resp['message'];

			} else {
				// Update the details in the plugin table.
				$status       = 'complete';
				$drip_details = array(
					'id'    => $resp['subscribers'][0]['id'],
					'email' => $resp['subscribers'][0]['email'],
				);
				wcap_set_cart_session(
					'drip_customer_details',
					wp_json_encode( $drip_details )
				);
			}

			return $status;
		}
	}

	public function wcap_drip_user_details( $customer_details ) {

		$merge_fields = array();
		$firstname    = $customer_details['firstname'];
		$lastname     = $customer_details['lastname'];
		if ( isset( $firstname ) && '' !== $firstname ) {
			$merge_fields['first_name'] = $firstname;
		}
		if ( isset( $lastname ) && '' !== $lastname ) {
			$merge_fields['last_name'] = $lastname;
		}

		return $merge_fields;
	}
}
new Wcap_Drip_Upsert_Contact_Action();
