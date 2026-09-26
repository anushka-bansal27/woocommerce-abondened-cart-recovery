<?php
/**
 * Insert/Update Contact in Salesforce CRM.
 *
 * @package Connectors/Salesforce/Actions
 */

/**
 * Insert/Update Contact in MC.
 */
class Wcap_Salesforce_Upsert_Lead_Action {

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
		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( 'wcap_salesforce_crm' );
		if ( empty( $connector_settings ) || ( isset( $connector_settings['status'] ) && 'active' !== $connector_settings['status'] ) ) {
			return false;
		}
		// Guest email address captured.
		add_action( 'wcap_guest_cart_history_after_insert', array( &$this, 'wcap_guest_inserted' ), 99, 1 );
		// Guest Details updated.
		add_action( 'wcap_after_update_guest_cart_history', array( &$this, 'wcap_guest_updated' ), 99, 2 );
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
	public function wcap_guest_inserted( $user_id = 0 ) {

		if ( $user_id > 0 ) {

			$abandoned_id = wcap_get_abandoned_id_from_user_id( $user_id );
			if ( $abandoned_id > 0 ) {
				$this->wcap_prepare_cart_details( $abandoned_id );
			}
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
			$this->wcap_prepare_cart_details( $abandoned_id );
		}
	}

	/**
	 * Abandoned Cart record inserted.
	 *
	 * @param int $abandoned_id - Abandoned Cart ID.
	 */
	public function wcap_cart_inserted( $abandoned_id = 0 ) {

		if ( $abandoned_id > 0 ) {
			$this->wcap_prepare_cart_details( $abandoned_id );
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
			$this->wcap_prepare_cart_details( $abandoned_id, false, $value );
		}
	}

	/**
	 * Prepare cart details for MC.
	 *
	 * @param int   $abandoned_id - Abandoned Cart ID.
	 * @param bool  $return_status - true for manual sync.
	 * @param array $value - Columns Updated.
	 */
	public function wcap_prepare_cart_details( $abandoned_id, $return_status = false, $value = array() ) {
		// Fetch the contact data.
		$common_inst      = Wcap_Connectors_Common::get_instance();
		$customer_details = $common_inst->wcap_get_contact_data( $abandoned_id );
		if ( isset( $customer_details['email'] ) && '' === $customer_details['email'] ) {
			return;
		}

		// Depending on the cart status, the record in MC will be inserted or updated.
		$cart_sync_id = $common_inst->wcap_get_cart_status( $abandoned_id, 'salesforce_crm' );
		$cart_details = $this->wcap_get_cart( $abandoned_id, $customer_details );

		if ( is_array( $customer_details ) && count( $customer_details ) > 0 && is_array( $cart_details ) && count( $cart_details ) > 0 ) {
			$email  = isset( $customer_details['email'] ) ? $customer_details['email'] : '';
			$action = $cart_sync_id > 0 ? 'update' : 'insert';

			$status = $this->wcap_create_cart_in_sc( $abandoned_id, $customer_details, $cart_details, $action, $cart_sync_id );
			if ( $return_status ) {
				return $status;
			}
		}
	}

	/**
	 * Raise the request for API call to SC.
	 *
	 * @param int    $abandoned_id      - Abandoned ID.
	 * @param array  $customer_details  - Customer details.
	 * @param array  $cart_details      - Cart details.
	 * @param string $action            - Insert/Update cart in MC.
	 * @param int    $cart_sync_id      - Cart Sync ID in Connectors Sync Table.
	 * @param array  $merge_fields      - Merge fields.
	 * @param array  $interests         - Interests in MC.
	 * @return string $status           - Status of the sync.
	 */
	public function wcap_create_cart_in_sc( $abandoned_id, $customer_details, $cart_details, $action = 'insert', $cart_sync_id = 0, $merge_fields = array(), $interests = array() ) {
		$connector_mc = Wcap_Salesforce_Crm::get_instance();
		$call         = $connector_mc->registered_calls['wcap_salesforce_crm_upsert_lead'];
		// Fetch the connector data.
		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( 'wcap_salesforce_crm' );
		$status             = isset( $connector_settings['status'] ) ? $connector_settings['status'] : '';

		// fetch customer details.
		$email     = isset( $customer_details['email'] ) ? $customer_details['email'] : '';
		$firstname = isset( $customer_details['firstname'] ) ? $customer_details['firstname'] : '';
		$lastname  = isset( $customer_details['lastname'] ) ? $customer_details['lastname'] : '';
		$phone     = isset( $customer_details['phone'] ) ? $customer_details['phone'] : '';
		$country   = isset( $customer_details['country'] ) ? $customer_details['country'] : '';
		$company   = isset( $customer_details['company'] ) ? $customer_details['company'] : 'Tyche';
		$address   = isset( $customer_details['address'] ) ? $customer_details['address'] : '';
		$state     = isset( $customer_details['state'] ) ? $customer_details['state'] : '';
		$city      = isset( $customer_details['city'] ) ? $customer_details['city'] : '';
		$company   = $company ? $company : 'Tyche Softwares';

		$product_name         = '';
		$wcap_product_details = '';
		foreach ( $cart_details['cart_items'] as $cart_details_key => $cart_details_value ) {
			$quantity_total = $cart_details_value['quantity'];
			$product_id     = $cart_details_value['product_id'];
			$prod_name      = get_post( $product_id );
			if ( $prod_name ) {
				$product_name = $prod_name->post_title;
				if ( isset( $cart_details_value['variation_id'] ) && '' !== $cart_details_value['variation_id'] && 0 !== $cart_details_value['variation_id'] ) {
					$variation_id                 = $cart_details_value['variation_id'];
					$variation                    = wc_get_product( $variation_id );
					$wcap_get_formatted_variation = wc_get_formatted_variation( $variation, true );
					$prod_name                    = $variation->get_title();
					$pro_name_variation           = (array) "$prod_name $wcap_get_formatted_variation";

					$product_name_with_variable = '';
					$explode_many_varaition     = array();

					foreach ( $pro_name_variation as $pro_name_variation_key => $pro_name_variation_value ) {
						$explode_many_varaition = explode( ',', $pro_name_variation_value );
						if ( ! empty( $explode_many_varaition ) ) {
							foreach ( $explode_many_varaition as $explode_many_varaition_key => $explode_many_varaition_value ) {
								$product_name_with_variable = $product_name_with_variable . " " . html_entity_decode( $explode_many_varaition_value );//phpcs:ignore
							}
						} else {
							$product_name_with_variable = $product_name_with_variable . " " . html_entity_decode( $explode_many_varaition_value );//phpcs:ignore
						}
					}
					$product_name = $product_name_with_variable;
				}
				$wcap_product_details .= html_entity_decode ( 'Product Name: ' . $product_name . ' , Quantity: ' . $quantity_total ) . "\n";//phpcs:ignore
			}
		}

		if ( 'active' === $status && '' !== $email && $abandoned_id > 0 && '' !== $cart_details['checkout_url'] ) {
			$params = array(
				'firstname'   => $firstname,
				'lastname'    => $lastname,
				'email'       => $email,
				'phone'       => $phone,
				'street'      => $address,
				'city'        => $city,
				'state'       => $state,
				'country'     => $country,
				'Company'     => $company,
				'description' => $wcap_product_details,
			);

			// Search for the lead by email to check for duplicates.
			$instance_url = get_option( 'wcap_salesforce_instance_url' );
			$access_token = get_option( 'wcap_salesforce_access_token' );
			$soql         = urlencode( "SELECT Id FROM Lead WHERE Email = '$email'" );//phpcs:ignore
			$query_url    = "$instance_url/services/data/v54.0/query/?q=$soql";
			$response     = wp_remote_get( $query_url, ['headers' => [ 'Authorization' => 'Bearer ' . $access_token, ],] );//phpcs:ignore

			if ( is_wp_error( $response ) ) {
				$status  = 'failed';
				$message = 'Failed to search for the lead';
			}

			$response_body = wp_remote_retrieve_body( $response );
			$response_data = json_decode( $response_body, true );

			if ( ! isset( $response_data['records'] ) || empty( $response_data['records'] ) ) {

				if ( is_array( $merge_fields ) && ! empty( $merge_fields ) ) {
					$params['merge_fields'] = $merge_fields;
				}

				if ( is_array( $interests ) && ! empty( $interests ) ) {
					$params['interests'] = $interests;
				}

				$call->set_data( $params );
				$result = $call->process();

				// Update the status of the integration for the cart in the table.
				$message = '';
				if ( 200 !== absint( $result['response'] ) ) {
					$error  = __( 'Error Response Code: ', 'woocommerce-ac' ) . $result['response'] . __( '. Salesforce Error: ', 'woocommerce-ac' );
					$error .= is_array( $result['body'] ) && isset( $result['body']['errors'] ) ? $result['body']['errors'] : __( 'No Response from Salesforce. ', 'woocommerce-ac' );

					$connector_cart_id = '';
					$status            = 'failed';
					if ( isset( $result['body'][0] ) ) {
						$message .= ': ' . $result['body'][0]['message'];
					}
				} if ( 400 === absint( $result['response'] ) ) {
					// Lead found, update it.
					$lead_id           = isset( $result['body'][0]['duplicateResult']['matchResults'][0]['matchRecords'][0]['record']['Id'] ) ? $result['body'][0]['duplicateResult']['matchResults'][0]['matchRecords'][0]['record']['Id'] : '';
					$response_data     = $this->wcap_update_lead_details( $lead_id, $params );
					$status            = $response_data['status'];
					$message           = $response_data['message'];
					$connector_cart_id = $response_data['connector_cart_id'];
				} else {
					// Update the details in the plugin table.
					$connector_cart_id = $result['body']['id'];
					$status            = 'complete';
				}
			} elseif ( ! empty( $response_data['records'] ) ) {
				// Lead found, update it.
				$lead_id           = $response_data['records'][0]['Id'];
				$res               = $this->wcap_update_lead_details( $lead_id, $params );
				$status            = $res['status'];
				$message           = $res['message'];
				$connector_cart_id = $res['connector_cart_id'];
			} else {
				// Lead not found, handle accordingly.
				$status  = 'not_found';
				$message = __( 'Lead not found', 'woocommerce-ac' );
			}
			global $wpdb;
			if ( 'update' === $action ) {
				$wpdb->update( // phpcs:ignore
					$wpdb->prefix . 'ac_connector_sync',
					array(
						'sync_date' => current_time( 'timestamp' ), // phpcs:ignore
						'status'    => $status,
						'sync_data' => wp_json_encode( $params ),
						'message'   => $message,
					),
					array(
						'id' => $cart_sync_id,
					)
				);
			} else {
				$wpdb->insert( // phpcs:ignore
					$wpdb->prefix . 'ac_connector_sync',
					array(
						'cart_id'           => $abandoned_id,
						'connector_cart_id' => $connector_cart_id,
						'connector_name'    => 'salesforce_crm',
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
	 * Get Cart Details.
	 *
	 * @param int   $abandoned_id      - Abandoned ID.
	 * @param array $customer_details  - Customer details.
	 * @return array $cart_details     - Cart details.
	 */
	public function wcap_get_cart( $abandoned_id, $customer_details ) {
		$cart_history = wcap_get_data_cart_history( $abandoned_id );
		$cart_details = array();
		if ( $cart_history ) {

			$cart_details['abandoned_date'] = get_date_from_gmt( date( 'Y-m-d H:i:s', $cart_history->abandoned_cart_time ) ); // phpcs:ignore
			$cart_details['checkout_url']   = $cart_history->checkout_link;

			$abandoned_cart_info = json_decode( $cart_history->abandoned_cart_info, true );
			$cart_items          = array();
			$cart_total          = 0;
			foreach ( $abandoned_cart_info['cart'] as $c_key => $c_value ) {
				$line_total           = isset( $c_value['line_total'] ) ? $c_value['line_total'] : 0;
				$cart_items[ $c_key ] = array(
					'product_id'   => $c_value['product_id'],
					'variation_id' => $c_value['variation_id'],
					'quantity'     => $c_value['quantity'],
					'price'        => $line_total,
				);
				$cart_total          += $line_total;
			}
			$cart_details['cart_items'] = $cart_items;
			$cart_details['cart_total'] = $cart_total;
		}
		return $cart_details;
	}

	/**
	 * Update saleforce lead details when duplicate lead found.
	 *
	 * @param int   $lead_id - Lead ID.
	 * @param array $params - Customer details.
	 */
	public function wcap_update_lead_details( $lead_id, $params ) {
			$instance_url = get_option( 'wcap_salesforce_instance_url' );
			$access_token = get_option( 'wcap_salesforce_access_token' );
			// Data to update (example: updating first name and last name).
			$update_data     = wp_json_encode( $params );
			$update_url      = "$instance_url/services/data/v54.0/sobjects/Lead/$lead_id";
			$update_response = wp_remote_request(
				$update_url,
				[
					'method'  => 'PATCH',
					'headers' => [
						'Authorization' => 'Bearer ' . $access_token,
						'Content-Type'  => 'application/json',
					],
					'body'    => $update_data,
				]
			);
		if ( 204 !== $update_response['response']['code'] ) {
			$connector_cart_id = '';
			$status            = 'failed';
			$message           = __( 'Failed to update the lead', 'woocommerce-ac' );
		} else {
			$connector_cart_id = $lead_id;
			$status            = 'complete';
			$message           = __( 'Lead updated successfully', 'woocommerce-ac' );
		}
		return array(
			'status'            => $status,
			'connector_cart_id' => $connector_cart_id,
			'message'           => $message,
		);

	}

}
new Wcap_Salesforce_Upsert_Lead_Action();
