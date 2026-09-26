<?php
/**
 * Insert/Update Contact in Mailjet.
 *
 * @package Connectors/Mailjet/Actions
 */

/**
 * Insert/Update Contact in Mailjet.
 */
class Wcap_Mailjet_Upsert_Contact_Action {
	/**
	 * Slug Name
	 *
	 * @var $slug
	 */
	public $slug = 'wcap_mailjet';
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
	 * @param int $abandoned_id - Cart history row ID.
	 */
	public function wcap_cart_inserted( $abandoned_id = 0 ) {
		$abandoned_id;
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
	 * @param int $abandoned_id - Abandoned Cart ID.
	 */
	public function wcap_prepare_contact( $abandoned_id, $email = '',  $return_status = false ) {
		// Fetch the contact data.
		$common_inst      = Wcap_Connectors_Common::get_instance();
		$customer_details = $common_inst->wcap_get_contact_data( $abandoned_id );

		if ( is_array( $customer_details ) && count( $customer_details ) > 0 ) {
			$merge_fields = $this->wcap_mailjet_user_details( $customer_details );
			if ( $email === '' ) { // phpcs:ignore
				$email = isset( $customer_details['email'] ) ? $customer_details['email'] : '';
			}
			if ( empty( $email ) ) {
				return;
			}
			$status = $this->wcap_create_contact( $email, $abandoned_id, $merge_fields );
			if ( $return_status ) {
				return $status;
			}
		}
	}

	/**
	 * Create contact in Mailjet.
	 *
	 * @param string $email - Email Address.
	 * @param array  $merge_fields - Merge fields.
	 * @param string  $abandoned_id - abandoned id.
	 */
	public function wcap_create_contact( $email, $abandoned_id, $merge_fields = array() ) {
		$connector_mc = Wcap_Mailjet::get_instance();
		$call         = $connector_mc->registered_calls['wcap_mailjet_upsert_contact'];
		// Fetch the connector data.
		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( $this->slug );
		$api_user           = isset( $connector_settings['api_user'] ) ? $connector_settings['api_user'] : '';
		$api_key            = isset( $connector_settings['api_key'] ) ? $connector_settings['api_key'] : '';
		$list_id            = isset( $connector_settings['default_list'] ) ? $connector_settings['default_list'] : '';
		$status             = isset( $connector_settings['status'] ) ? $connector_settings['status'] : '';

		if ( 'active' === $status && '' !== $email ) {
			$params = array(
				'api_user' => $api_user,
				'api_key'  => $api_key,
				'Email'    => $email,
				'ListID'   => $list_id,
			);

			if ( is_array( $merge_fields ) && ! empty( $merge_fields ) ) {
				foreach ( $merge_fields as $field => $value ) {
					$params[ $field ] = $value;
				}
			}
			$action                   = 'insert';
			$mailjet_customer_details = wcap_get_cart_session( 'mailjet_customer_details' );
			if ( isset( $mailjet_customer_details['ID'] ) && $email === $mailjet_customer_details['Email'] ) {
				$params['ID'] = $mailjet_customer_details['ID'];
				$action       = 'update';
			}

			$call->set_data( $params );
			$result = $call->process();
			if ( isset( $result['Email'] ) ) {
				wcap_set_cart_session( 'mailjet_customer_details', array( 'ID' => $result['ID'], 'Email' => $result['Email'] ) );
			}
			
			$message = $result['response'];

			// Update the status of the integration for the cart in the table.
			if ( 200 !== absint( $result['response'] ) ) {
				$error  = __( 'Error Response Code: ', 'woocommerce-ac' ) . $result['response'] . __( '. Activecampaign Error: ', 'woocommerce-ac' );
				$error .= is_array( $result['body'] ) && isset( $result['body']['detail'] ) ? $result['body']['detail'] : __( 'No Response from Activecampaign. ', 'woocommerce-ac' );
				$error .= ( 502 === absint( $result['response'] ) ) ? __( 'Wcap Error: ', 'woocommerce-ac' ) . $result['body'][0] : '';

				$connector_cart_id = '';
				$status            = 'failed';

				if ( isset( $result['body']['detail'] ) ) {
					$message .= ': ' . $result['body']['detail'];
				} elseif( isset( $result['body']['errors'] ) ) {
					$message .= ': ' . wp_json_encode( $result['body']['errors'] );
				} elseif ( isset( $result['body'][0] ) ) {
					$message .= ': ' . $result['body'][0];
				}

			} else {
				// Update the details in the plugin table.
				$connector_cart_id = $result['ID'];
				$status            = 'complete';
			}
			global $wpdb;

			$result = $wpdb->get_results( 'select * from ' . $wpdb->prefix . 'ac_connector_sync  where cart_id = ' . $abandoned_id . " AND connector_name='mailjet'" ); // phpcs:ignore
			if ( ! empty( $result[0] ) ) {
				$action = 'update';
			}

			if ( 'update' === $action ) {
				$wpdb->update( // phpcs:ignore
					$wpdb->prefix . 'ac_connector_sync',
					array(
						'connector_cart_id' => $connector_cart_id,
						'sync_date'         => current_time( 'timestamp' ), // phpcs:ignore
						'status'            => $status,
						'sync_data'         => wp_json_encode( $params ),
						'message'           => $message,
					),
					array(
						'cart_id'        => $abandoned_id,
						'connector_name' => 'mailjet'
					)
				);
			} else {
				$wpdb->insert( // phpcs:ignore
					$wpdb->prefix . 'ac_connector_sync',
					array(
						'cart_id'           => $abandoned_id,
						'connector_cart_id' => $connector_cart_id,
						'connector_name'    => 'mailjet',
						'sync_date'         => current_time( 'timestamp' ), // phpcs:ignore
						'status'            => $status,
						'sync_data'         => wp_json_encode( $params ),
						'message'           => $message,
					)
				);
			}
			return $status;
		}
	}

	/**
	 * Setup Mailjet user details.
	 *
	 * @param array $customer_details - Customer Details.
	 * @return array $merge_fields - Merge fields.
	 */
	public function wcap_mailjet_user_details( $customer_details ) {

		$firstname    = isset( $customer_details['firstname'] ) ? $customer_details['firstname'] : '';
		$lastname     = isset( $customer_details['lastname'] ) ? $customer_details['lastname'] : '';
		$phone        = isset( $customer_details['phone'] ) ? $customer_details['phone'] : '';
		$country      = isset( $customer_details['country'] ) ? $customer_details['country'] : '';
		$merge_fields = array();
		if ( isset( $firstname ) && '' !== $firstname ) {
			$merge_fields['Name'] = $firstname;
		}
		if ( isset( $firstname ) && '' !== $firstname ) {
			$merge_fields['FirstName'] = $firstname;
		}
		if ( isset( $lastname ) && '' !== $lastname ) {
			$merge_fields['LastName'] = $lastname;
		}
		if ( isset( $country ) && '' !== $country ) {
			$merge_fields['country'] = $country;
		}
		if ( isset( $firstname ) && '' !== $firstname && isset( $lastname ) && '' !== $lastname ) {
			$merge_fields['Name'] = $firstname . ' ' . $lastname;
		}
		if ( isset( $phone ) && '' !== $phone ) {
			$merge_fields['phone'] = $phone;
		}

		return $merge_fields;
	}

}
new Wcap_Mailjet_Upsert_Contact_Action();
