<?php
/**
 * Insert/Update Cart in MC.
 *
 * @package Connectors/Activecampaign/Actions
 */

/**
 * Insert/Update Carts in MC.
 */
class Wcap_Activecampaign_Upsert_Cart_Action {
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
		if ( empty( $connector_settings ) || ! isset( $connector_settings['default_connection'] ) ) {
			return false;
		}
		if ( $connector_settings['status'] !=='active' ) {
			return false;
		}
		add_action( 'wcap_after_update_cart_history', array( &$this, 'wcap_cart_updated' ), 99, 2 );
		add_action( 'wcap_cart_history_after_insert', array( &$this, 'wcap_cart_inserted' ), 99, 1 );
		add_action( 'wcap_before_update_guest_cart_history', array( &$this, 'wcap_before_guest_update' ), 99, 2 );
		add_action( 'wcap_after_update_guest_cart_history', array( &$this, 'wcap_guest_updated' ), 99, 2 );

		// add_action( 'wcap_guest_cart_history_after_insert', array( &$this, 'wcap_guest_insert' ), 99, 1 );
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
	 * After Guest insert without any Popus
	 * 
	 * @param string $user_id - user_id of the huest row inserted
	 */
	public function wcap_guest_insert( $user_id ){
		$user_id;
		$abandoned_id  = wcap_get_abandoned_id_from_user_id( $user_id );

	}

	/**
	 * Before Guest record update. Check email is changed, if so delete old cart from old email.
	 *
	 * @param array $value - Updated columns and their values.
	 * @param array $where - Where condition for update.
	 */
	public function wcap_before_guest_update( $value = array(), $where = array() ) {
		$where;
		if ( ( is_array( $value ) && array_key_exists( 'email_id', $value ) && array_key_exists( 'id', $where ) ) ) {
			global $wpdb;
			$old_email_id = '';
			$result = $wpdb->get_results( 'select * from ' . $wpdb->prefix . 'ac_guest_abandoned_cart_history  where id = ' . $where['id'] ); // phpcs:ignore
			if ( ! empty( $result[0] ) && isset( $result[0]->email_id ) ) {
				$old_email_id = $result[0]->email_id;
			}
			$this->cart_deleted = false;
			if ( $old_email_id !== $value['email_id']  ) {
				$abandoned_id  = wcap_get_abandoned_id_from_user_id( $where['id'] );
				$ecom_order_id = $this->get_ecom_order_id( $abandoned_id );
				$this->wcap_delete_cart_details( $ecom_order_id );
				$this->cart_deleted = true;

				$wpdb->update( // phpcs:ignore
					$wpdb->prefix . 'ac_connector_sync',
					array(
						'connector_cart_id' => '',
						'sync_date'         => current_time( 'timestamp' ), // phpcs:ignore
						'status'            => '',
						'sync_data'         => '',
					),
					array(
						'cart_id'        => $abandoned_id,
						'connector_name' => 'activecampaign'
					)
				);
			}
		}
	}

	/**
	 * After Guest record update.
	 *
	 * @param array $value - Updated columns and their values.
	 * @param array $where - Where condition for update.
	 */
	public function wcap_guest_updated( $value = array(), $where = array() ) {
		$where;
		$abandoned_id  = wcap_get_abandoned_id_from_user_id( $where['id'] );
		$ecom_order_id = $this->get_ecom_order_id( $abandoned_id );	
		if ( is_array( $value ) && array_key_exists( 'email_id', $value ) && array_key_exists( 'id', $where ) && ( $this->cart_deleted || ( empty( $ecom_order_id ) && isset( $_POST['wcap_save_guest_data'] ) ) ) ) {//phpcs:ignore
			$abandoned_id = wcap_get_abandoned_id_from_user_id( $where['id'] );
			if ( $abandoned_id > 0 ) {
					$this->wcap_prepare_cart_details( $abandoned_id, false, $value );
			}
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
		} elseif ( is_array( $where ) && array_key_exists( 'user_id', $where ) ) {
			$abandoned_id = wcap_get_abandoned_id_from_user_id( $where['user_id'] );
		} elseif ( is_array( $value ) && array_key_exists( 'id', $value ) ) {
			$abandoned_id = $value['id'];
		}

		$ecom_order_id = $this->get_ecom_order_id( $abandoned_id );

		if ( is_array( $value ) && ( array_key_exists( 'cart_ignored', $value ) && ! array_key_exists( 'recovered_cart', $value ) ) ) {
			$this->wcap_delete_cart_details( $ecom_order_id );
			return;
		}

		if ( $abandoned_id > 0 ) {
			$this->wcap_prepare_cart_details( $abandoned_id, false, $value );
		}
	}

	/**
	 * Get and set E-com order id
	 *
	 * @param int $abandoned_id - Id of the row for abandoned cart.
	 */
	private function get_ecom_order_id( $abandoned_id ) {
		global $wpdb;
		$ecom_order_id = wcap_get_cart_session( 'ecom_order_id' );
		if ( empty( $ecom_order_id ) ) {
			$result = $wpdb->get_results( 'select * from ' . $wpdb->prefix . 'ac_connector_sync  where connector_name ="activecampaign" AND cart_id = ' . $abandoned_id ); //phpcs:ignore
			if ( ! empty( $result[0] ) && isset( $result[0]->connector_cart_id ) ) {
				$ecom_order_id = $result[0]->connector_cart_id;
				wcap_set_cart_session( 'ecom_order_id', $ecom_order_id );
			}
		}
		return $ecom_order_id;
	}

	/**
	 * Delete cart details for MC.
	 *
	 * @param int $ecom_order_id - Abandoned Cart ID.
	 */
	public function wcap_delete_cart_details( $ecom_order_id ) {

		$connector_mc = Wcap_Activecampaign::get_instance();
		$call         = $connector_mc->registered_calls['wcap_activecampaign_delete_cart'];
		// Fetch the connector data.
		$connector_settings      = Wcap_Connectors_Common::wcap_get_connectors_data( 'wcap_activecampaign' );
		$params['api_url']       = isset( $connector_settings['api_url'] ) ? $connector_settings['api_url'] : '';
		$params['api_key']       = isset( $connector_settings['api_key'] ) ? $connector_settings['api_key'] : '';
		$params['ecom_order_id'] = $ecom_order_id;
		$call->set_data( $params );
		$result = $call->process();
		wcap_set_cart_session( 'ecom_order_id', '' );

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
		$connector_sync_id = $common_inst->wcap_get_cart_status( $abandoned_id );
		$cart_details      = $this->wcap_get_cart( $abandoned_id, $customer_details );

		if ( is_array( $customer_details ) && count( $customer_details ) > 0 && is_array( $cart_details ) && count( $cart_details ) > 0 ) {
			$email = isset( $value['email_id'] ) ? $value['email_id'] : '';
			if ( ! $email ) {
				$email = isset( $customer_details['email'] ) ? $customer_details['email'] : '';
			}
			if ( empty( $email ) ) {
				return;
			}
			$action = ! $connector_sync_id ? 'insert' : 'update';
			$status = $this->wcap_create_cart_in_ac( $abandoned_id, $email, $cart_details, $action );
			if ( $return_status ) {
				return $status;
			}
		}
	}

	/**
	 * Raise the request for API call to AC.
	 *
	 * @param int    $abandoned_id - Abandoned ID.
	 * @param string $email - Email Address.
	 * @param array  $cart_details - Cart Details, URL, products etc.
	 * @param string $action - Insert/Update cart in AC.
	 * @param array  $merge_fields - Merge fields.
	 * @param array  $interests - Interests in AC.
	 */
	public function wcap_create_cart_in_ac( $abandoned_id, $email, $cart_details, $action = 'insert', $merge_fields = array(), $interests = array() ) {
		$connector_mc = Wcap_Activecampaign::get_instance();
		$call         = $connector_mc->registered_calls['wcap_activecampaign_upsert_cart'];
		// Fetch the connector data.
		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( $this->slug );
		if ( empty( $connector_settings ) ) {
			return false;
		}
		$api_url            = isset( $connector_settings['api_url'] ) ? $connector_settings['api_url'] : '';
		$api_key            = isset( $connector_settings['api_key'] ) ? $connector_settings['api_key'] : '';
		$connection_id      = isset( $connector_settings['default_connection'] ) ? $connector_settings['default_connection'] : '';
		$status             = isset( $connector_settings['status'] ) ? $connector_settings['status'] : '';
		
		if ( 'active' === $status && '' !== $email && $abandoned_id > 0 && '' !== $connection_id && ( '' !== $cart_details['checkout_link'] || is_user_logged_in() ) ) {

			$params = array(
				'api_url'       => $api_url,
				'api_key'       => $api_key,
				'email'         => $email,
				'totalProducts' => $cart_details['totalProducts'],
				'orderProducts' => $cart_details['cart_items'],
				'totalPrice'    => $cart_details['cart_total'] * 100,
				'action'        => $action,
			);

			$ecom_order_id = wcap_get_cart_session( 'ecom_order_id' );
			if ( empty( $ecom_order_id ) ) {
				$params['abandonedDate']       = $cart_details['abandoned_date'];
				$params['customerid']          = $cart_details['customer_id'];
				$params['connectionid']        = $connection_id;
				$params['currency']            = get_woocommerce_currency();
				$params['externalcheckoutid']  = $cart_details['id'];
				$params['externalCreatedDate'] = $cart_details['abandoned_date'];
				$params['orderUrl']            = $cart_details['checkout_link'];
				$params['source']              = 1;
			} else {
				$action                  = 'update';
				$params['ecom_order_id'] = $ecom_order_id;
			}

			if ( ! empty( $cart_details['recovered_cart'] ) ) {

				$params['externalid'] = $cart_details['recovered_cart'];

			}

			$call->set_data( $params );
			$check_if_order_already_updated = false;
			if ( empty( $ecom_order_id ) ) {
				$check_if_order_already_updated = $call->check_if_order_already_updated( $connection_id, $cart_details['id'] );
			}

			if ( isset( $check_if_order_already_updated['body']['ecomOrders'][0] ) ) {
				$result['body']['ecomOrder'] = $check_if_order_already_updated['body']['ecomOrders'][0];
				$result['response']          = $check_if_order_already_updated['response'];
			} else {
				$result = $call->process();
			}
			$message = $result['response'];
			if ( isset( $result['body']['ecomOrder'] ) ) {
				$ecom_order = $result['body']['ecomOrder'];
				wcap_set_cart_session( 'ecom_order_id', $ecom_order['id'] );
			}
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
				$connector_cart_id = $result['body']['ecomOrder']['id'];
				$status            = 'complete';
			}

			global $wpdb;

			$result = $wpdb->get_results( 'select * from ' . $wpdb->prefix . 'ac_connector_sync  where cart_id = ' . $abandoned_id . " AND connector_name='activecampaign'" ); // phpcs:ignore
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
						'connector_name' => 'activecampaign'
					)
				);
			} else {
				$wpdb->insert( // phpcs:ignore
					$wpdb->prefix . 'ac_connector_sync',
					array(
						'cart_id'           => $abandoned_id,
						'connector_cart_id' => $connector_cart_id,
						'connector_name'    => 'activecampaign',
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
	 * Get Cart Data.
	 *
	 * @param int   $abandoned_id - Abandoned Cart ID.
	 * @param array $customer_details - Customer Details.
	 * @return array $cart_details - Cart Details.
	 */
	public function wcap_get_cart( $abandoned_id, $customer_details ) {
		$cart_history = wcap_get_data_cart_history( $abandoned_id );
		$cart_details = array();
		if ( $cart_history ) {
			$cart_details['id']              = $cart_history->id;
			$cart_details['user_id']         = $cart_history->user_id;
			$cart_details['abandoned_date']  = date( 'Y-m-d', $cart_history->abandoned_cart_time ); // phpcs:ignore
			$cart_details['abandoned_date'] .= 'T'.date( 'H:i:s', $cart_history->abandoned_cart_time ); // phpcs:ignore
			$dt                              = new DateTime( 'now', new DateTimeZone( wp_timezone_string() ) );
			$dt->setTimestamp( $cart_history->abandoned_cart_time );
			$cart_details['abandoned_date'] .= $dt->format( 'P' );
			$cart_details['checkout_link']    = $cart_history->checkout_link;
			$cart_details['customer_id']     = $this->get_customer_id( $customer_details['email'] );
			$cart_details['recovered_cart']  = $cart_history->recovered_cart;
			list( $cart_details['cart_items'], $cart_details['cart_total'], $cart_details['totalProducts'] ) = $this->get_cart_and_totals( $cart_history );

		}
		return $cart_details;
	}

	/**
	 * Get customer id from Email from session
	 *
	 * @param string $email Email id - to check in session variable.
	 */
	private function get_customer_id( $email ) {
		$customer_details = wcap_get_cart_session( 'activecampaign_customer_details' );

		if ( isset( $customer_details['email'] ) && $customer_details['email'] === $email ) {
			return $customer_details['id'];
		}
	}

	/**
	 * Get cart totals
	 *
	 * @param object $cart_history - Row from cart history table.
	 */
	private function get_cart_and_totals( $cart_history ) {
		$abandoned_cart_info = json_decode( $cart_history->abandoned_cart_info, true );
		$cart_items          = array();
		$cart_total          = 0;
		$cart_quantity       = 0;

		foreach ( $abandoned_cart_info['cart'] as $c_key => $c_value ) {
			$line_total = $c_value['line_total'];
			$pid        = $c_value['product_id'];
			$plink      = get_permalink( $pid );
			if ( isset( $c_value['variation_id'] ) && $c_value['variation_id'] ) {
				$pid = $c_value['variation_id'];
			}
			$product   = wc_get_product( $pid );
			$image_id  = $product->get_image_id();
			$image_url = wp_get_attachment_image_url( $image_id );

			$cart_items[] = array(
				'name'       => $product->get_formatted_name(),
				'externalid' => $c_value['product_id'],
				'imageUrl'   => $image_url,
				'productUrl' => $plink,
				'quantity'   => $c_value['quantity'],
				'price'      => ( $line_total / $c_value['quantity'] ) * 100,
			);
			$cart_total    += $line_total;
			$cart_quantity += $c_value['quantity'];
		}

		return array( $cart_items, $cart_total, $cart_quantity );
	}
}
new Wcap_Activecampaign_Upsert_Cart_Action();
