<?php

class Wcap_Hubspot_Upsert_Contact_Action {

	private static $ins = null;
    public function __construct() {
		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( 'wcap_hubspot' );
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
		if ( isset( $customer_details['email'] ) && '' === $customer_details['email'] ) {
			return;
		}

		if ( is_array( $customer_details ) && count( $customer_details ) > 0 ) {
			$merge_fields = $this->wcap_hubspot_user_details( $customer_details );
			$cart_sync_id = $common_inst->wcap_get_cart_status( $abandoned_id, 'hubspot' );
			$action       = $cart_sync_id > 0 ? 'update' : 'insert';

			$cart_details = $this->wcap_hubspot_cart_details( $abandoned_id, $action );
			$email        = isset( $customer_details['email'] ) ? $customer_details['email'] : '';

			if ( is_array( $cart_details ) && count( $cart_details ) > 0 && isset( $cart_details['wcap_cart_url'] ) && '' !== $cart_details['wcap_cart_url' ] ) {
				$status = $this->wcap_create_contact( $abandoned_id, $email, $cart_sync_id, $merge_fields, $cart_details, $action );
				if ( $return_status ) {
					return $status;
				}
			}
		}
	}

	public function wcap_create_contact( $abandoned_id, $email, $cart_sync_id = 0, $merge_fields = array(), $interests = array(), $action = 'insert' ) {
		$connector_hb = Wcap_Hubspot::get_instance();
		if ( 'update' === $action ) {
			$call = $connector_hb->registered_calls['wcap_hubspot_update_contact'];
		} else {
			$call = $connector_hb->registered_calls['wcap_hubspot_create_contact'];
		}
		// Fetch the connector data.
		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( 'wcap_hubspot' );
		$api_key            = isset( $connector_settings['api_key'] ) ? $connector_settings['api_key'] : '';
		$status             = isset( $connector_settings['status'] ) ? $connector_settings['status'] : '';

		if ( 'active' === $status && '' !== $email && '' !== $interests['wcap_cart_url'] ) {
			$params = array(
				'api_key' => $api_key,
				'email'   => $email,
			);

			if ( is_array( $merge_fields ) && ! empty( $merge_fields ) ) {
				$params['merge_fields'] = $merge_fields;
			}

			if ( is_array( $interests ) && ! empty( $interests ) && 'insert' === $action ) {
				$params['interests'] = $interests;
			} elseif ( is_array( $interests ) && ! empty( $interests ) && 'update' === $action ) {
				$params['update_properties'] = $interests;
			}

			$call->set_data( $params );
			$result = $call->process();

			$resp = wp_remote_retrieve_body( $result );
			if ( 409 === absint( $result['response'] ) && 'insert' === $action ) {
				$call = $connector_hb->registered_calls['wcap_hubspot_update_contact'];

				// Adjust the parameters for update.
				$params['update_properties'] = $interests;
				unset( $params['interests'] ); // Remove interests for update.
				if ( isset( $params['update_properties']['wcap_abandoned_date'] ) && ! empty( $params['update_properties']['wcap_abandoned_date'] ) ) {
					$params['update_properties']['wcap_abandoned_date'] = current_time( 'timestamp' ) * 1000;
				}

				$call->set_data( $params );
				$result = $call->process();
				$resp   = wp_remote_retrieve_body( $result );
			}

			// Update the status of the integration for the cart in the table.
			$message = $result['response'];
			if ( 200 !== absint( $result['response'] ) ) {
				$error = __( 'Error Response Code: ', 'woocommerce-ac' ) . $result['response'] . __( '. Hubspot Error: ', 'woocommerce-ac' );
				$error .= isset( $resp['message'] ) ? $resp['message'] : __( 'No Response from Hubspot. ', 'woocommerce-ac' );
				$error .= ( 502 === absint( $result['response'] ) ) ? __( 'Wcap Error: ', 'woocommerce-ac' ) . $resp['category'] : '';

				$connector_cart_id = '';
				$status            = 'failed';
				$message          .= ': ' . $resp['message'];

			} else {
				// Update the details in the plugin table.
				$status = 'complete';
			}

			global $wpdb;//phpcs:disable
			if ( 'update' === $action ) {
				$connector_cart_id = absint( $resp['vid'] );
				$sql               = $wpdb->prepare(
					"
				    UPDATE {$wpdb->prefix}ac_connector_sync
				    SET sync_date = %s, status = %s, sync_data = %s, message = %s
				    WHERE id = %d
				    ",
					current_time( 'timestamp' ),
					$status,
					wp_json_encode( $params ),
					$message,
					$cart_sync_id
				);
				$wpdb->query( $sql );
			} else {
				$connector_cart_id = isset( $resp['id'] ) ? $resp['id'] : '';
				$connector_cart_id = '' === $connector_cart_id && isset( $resp['vid'] ) ? absint( $resp['vid'] ) : $connector_cart_id;
				$sql               = $wpdb->prepare(
					"
				    INSERT INTO {$wpdb->prefix}ac_connector_sync 
				    (cart_id, connector_cart_id, connector_name, sync_date, status, sync_data, message)
				    VALUES (%d, %s, %s, %s, %s, %s, %s)
				    ",
					absint( $abandoned_id ),
					$connector_cart_id,
					'hubspot',
					current_time( 'timestamp' ),
					$status,
					wp_json_encode( $params ),
					$message
				);
				$wpdb->query( $sql );
			}
			return $status;//phpcs:enable
		}
	}

	public function wcap_hubspot_user_details( $customer_details ) {

		$merge_fields = array();
		$firstname    = $customer_details['firstname'];
		$lastname     = $customer_details['lastname'];
		if ( isset( $firstname ) && '' !== $firstname ) {
			$merge_fields['firstname'] = $firstname;
		}
		if ( isset( $lastname ) && '' !== $lastname ) {
			$merge_fields['lastname'] = $lastname;
		}

		return $merge_fields;
	}

    public function wcap_hubspot_cart_details( $abandoned_id, $action = 'insert' ) {

		$cart_details = array();
		$common_class = Wcap_Connectors_Common::get_instance();
		$cart         = $common_class->wcap_get_cart_details( $abandoned_id );

		if ( is_array( $cart ) && count( $cart ) > 1 ) {
			$cart_details['wcap_cart_products'] = $cart['wcap_cart_products'];
			$cart_details['wcap_products_html'] = $cart['wcap_products_html'];
			$cart_details['wcap_products_sku'] = $cart['wcap_products_sku'];
			$cart_details['wcap_cart_subtotal'] = $cart['wcap_cart_subtotal'];
			$cart_details['wcap_cart_tax'] = $cart['wcap_cart_tax'];
			$cart_details['wcap_cart_total'] = $cart['wcap_cart_total'];
			$cart_details['wcap_abandoned_cart'] = 'yes';
			$cart_details['wcap_cart_url'] = $cart['checkout_url'];
			if ( 'insert' === $action ) {
				$cart_details['wcap_abandoned_date'] = $cart['wcap_abandoned_date'];
				$cart_details['wcap_cart_counter'] = 1;
			}
		}
		return $cart_details;
    }
}
new Wcap_Hubspot_Upsert_Contact_Action();
