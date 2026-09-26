<?php
/**
 * Insert/Update Cart in MC.
 *
 * @package Connectors/Fluentcrm/Actions
 */

/**
 * Insert/Update Carts in MC.
 */
class Wcap_Fluentcrm_Upsert_Cart_Action {
	/**
	 * Slug Name
	 *
	 * @var $slug
	 */
	public $slug = 'wcap_fluentcrm';
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
		if ( ! is_plugin_active( 'fluentcampaign-pro/fluentcampaign-pro.php' ) ) {
			return false;
		}
		add_action( 'wcap_after_update_cart_history', array( &$this, 'wcap_cart_updated' ), 99, 2 );
		add_action( 'wcap_cart_history_after_insert', array( &$this, 'wcap_cart_inserted' ), 99, 1 );
		add_action( 'wcap_before_update_guest_cart_history', array( &$this, 'wcap_before_guest_update' ), 99, 2 );
		add_action( 'wcap_after_update_guest_cart_history', array( &$this, 'wcap_guest_updated' ), 99, 2 );
		add_action( 'wcap_before_delete_guest_cart_history', array( &$this, 'wcap_before_delete_guest' ), 98, 2 );
		add_action( 'wcap_before_delete_cart_history', array( &$this, 'wcap_before_delete_history' ), 99, 1 );
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
	 * @param string $user_id - user_id of the huest row inserted.
	 */
	public function wcap_guest_insert( $user_id ) {
		$user_id;
		wcap_get_abandoned_id_from_user_id( $user_id );

	}

	/**
	 * Before Guest record update. Check email is changed, if so delete old cart from old email.
	 *
	 * @param array $value - Updated columns and their values.
	 * @param array $where - Where condition for update.
	 */
	public function wcap_before_guest_update( $value = array(), $where = array() ) {
		$this->old_email_id = '';
		$old_email_id       = '';
		global $wpdb;
		if ( ( is_array( $value ) && array_key_exists( 'email_id', $value ) && array_key_exists( 'id', $where ) ) ) {

			$result = $wpdb->get_results( 'select email_id from ' . $wpdb->prefix . 'ac_guest_abandoned_cart_history  where id = ' . $where['id'] ); // phpcs:ignore
			if ( ! empty( $result[0] ) && isset( $result[0]->email_id ) ) {
				$old_email_id = $result[0]->email_id;
			}
			$this->cart_moved = false;
			if ( $old_email_id !== $value['email_id'] && ! empty( $old_email_id ) ) {
				$abandoned_id = wcap_get_abandoned_id_from_user_id( $where['id'] );

				wcap_set_cart_session( 'wcap_fluentcrm_sync_cart_id', '' );
				$this->cart_moved   = true;
				$this->old_email_id = $old_email_id;

				$fluentcrm_customer_details = wcap_get_cart_session( 'fluentcrm_customer_details' );
				$this->contact_id           = $fluentcrm_customer_details['id'];
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
						'connector_name' => 'fluentcrm',
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
		$abandoned_id                = wcap_get_abandoned_id_from_user_id( $where['id'] );
		$wcap_fluentcrm_sync_cart_id = $this->get_wcap_fluentcrm_sync_cart_id( $abandoned_id );
		if ( is_array( $value ) && array_key_exists( 'email_id', $value ) && array_key_exists( 'id', $where ) && ( $this->cart_moved || ( empty( $wcap_fluentcrm_sync_cart_id ) && isset( $_POST['wcap_save_guest_data'] ) ) ) ) { //phpcs:ignore
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
		$this->cart_ignored         = false;
		$fluentcrm_customer_details = wcap_get_cart_session( 'fluentcrm_customer_details' );
		$this->contact_id           = $fluentcrm_customer_details['id'];
		if ( is_array( $value ) && ( array_key_exists( 'cart_ignored', $value ) && ! array_key_exists( 'recovered_cart', $value ) ) ) {
			$this->cart_ignored = true;
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
	private function get_wcap_fluentcrm_sync_cart_id( $abandoned_id ) {
		global $wpdb;
		$wcap_fluentcrm_sync_cart_id = wcap_get_cart_session( 'wcap_fluentcrm_sync_cart_id' );
		if ( empty( $wcap_fluentcrm_sync_cart_id ) ) {
			$result = $wpdb->get_results( 'select * from ' . $wpdb->prefix . 'ac_connector_sync  where connector_name ="fluentcrm" AND cart_id = ' . $abandoned_id ); //phpcs:ignore
			if ( ! empty( $result[0] ) && isset( $result[0]->connector_cart_id ) ) {
				$wcap_fluentcrm_sync_cart_id = $result[0]->cart_id;
				wcap_set_cart_session( 'wcap_fluentcrm_sync_cart_id', $wcap_fluentcrm_sync_cart_id );
			}
		}
		return $wcap_fluentcrm_sync_cart_id;
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
		$connector_sync_id = $common_inst->wcap_get_cart_status( $abandoned_id, 'fluentcrm' );
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

			$this->current_email_id = $email;
			if ( isset( $this->cart_moved ) && $this->cart_moved ) {
				$this->wcap_create_cart_in_fluentcrm( $abandoned_id, $this->old_email_id, $cart_details, $action );
			}
			$status = $this->wcap_create_cart_in_fluentcrm( $abandoned_id, $email, $cart_details, $action );

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
	public function wcap_create_cart_in_fluentcrm( $abandoned_id, $email, $cart_details, $action = 'insert', $merge_fields = array(), $interests = array() ) {
		$connector_mc = Wcap_Fluentcrm::get_instance();
		$call         = $connector_mc->registered_calls['wcap_fluentcrm_upsert_cart'];
		// Fetch the connector data.
		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( $this->slug );
		$api_name           = isset( $connector_settings['api_name'] ) ? $connector_settings['api_name'] : '';
		$api_key            = isset( $connector_settings['api_key'] ) ? $connector_settings['api_key'] : '';
		$status             = isset( $connector_settings['status'] ) ? $connector_settings['status'] : '';

		if ( 'active' === $status && '' !== $email && $abandoned_id > 0 && ( '' !== $cart_details['checkout_link'] || is_user_logged_in() ) ) {

			$params = array(
				'api_name' => $api_name,
				'api_key'  => $api_key,
				'email'    => $email,
			);

			$params['custom_values'] = array(
				'wcap_cart_id'               => $cart_details['id'],
				'wcap_checkouturl'           => $cart_details['checkout_link'],
				'wcap_currency'              => get_woocommerce_currency(),
				'wcap_cart_products'         => wp_json_encode( $cart_details['cart_items'] ),
				'wcap_cart_total'            => $cart_details['cart_total'],
				'wcap_user_id'               => $cart_details['user_id'],
				'wcap_abandoned_date'        => $cart_details['abandoned_date'],
				'wcap_captured_by'           => $cart_details['captured_by'],
				'wcap_cart_subtotal'         => $cart_details['sub_total'],
				'wcap_cart_discount'         => $cart_details['discount'],
				'wcap_cart_shipping'         => $cart_details['shipping'],
				'wcap_cart_total_before_tax' => $cart_details['sub_total'] - $cart_details['discount'] + $cart_details['shipping'],
				'wcap_cart_tax'              => $cart_details['tax'],
				'wcap_cart_totalProducts'    => (string) $cart_details['totalProducts'],
				'wcap_abandoned_cart_html'   => $cart_details['cart_html'],
			);

			$wcap_fluentcrm_sync_cart_id = $this->get_wcap_fluentcrm_sync_cart_id( $abandoned_id );

			$remove = false;
			$tags   = $this->get_wcap_fluentcrm_tags();
			if ( empty( $wcap_fluentcrm_sync_cart_id ) ) {
				$params['tags'][] = $tags[ $connector_mc->events[0] ];
			} else {
				$params['tags'][] = $tags[ $connector_mc->events[1] ];
			}

			if ( ! empty( $cart_details['cart_ignored'] ) ) {
				$params['tags'][] = $tags[ $connector_mc->events[2] ];
				switch ( $cart_details['cart_ignored'] ) {
					case 1:
						$params['custom_values']['wcap_reason'] = 'Old cart ignored and New cart created';
						break;
					case 2:
						$params['custom_values']['wcap_reason'] = 'Cancelled Order';
						break;
					case 3:
						$params['custom_values']['wcap_reason'] = 'Received Order';
						break;
					case 4:
						$params['custom_values']['wcap_reason'] = 'Pending Payment';
						break;
				}
				$remove = true;
			}
			if ( ! empty( $cart_details['recovered_cart'] ) ) {
				$params['tags'][]                         = $tags[ $connector_mc->events[3] ];
				$params['custom_values']['wcap_order_id'] = $cart_details['recovered_cart'];
				$params['custom_values']['wcap_reason']   = 'Recovered Cart';

				$remove = true;
			}
			if ( isset( $this->cart_moved ) && $this->cart_moved ) {
				$params['tags'][]                             = $tags[ $connector_mc->events[2] ];
				$params['custom_values']['wcap_new_email_id'] = $this->current_email_id;
				$params['custom_values']['wcap_old_email_id'] = $this->old_email_id;
				$params['custom_values']['wcap_reason']       = 'Email changed';
				$action                                       = 'insert';

				$remove = true;
				if ( $email !== $this->old_email_id ) {
					$params['tags'][] = $tags[ $connector_mc->events[0] ];
					$remove           = false;
				}
			}
			global $wpdb;
			if ( 'delete' === $action ) {
				$params['tags'][] = $tags[ $connector_mc->events[4] ];
				$query            = $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'postmeta' . " WHERE meta_key ='wcap_abandoned_cart_id' AND meta_value=%d", $abandoned_id );
				$get_cart_data    = $wpdb->get_results( $query ); //phpcs:ignore
				if ( isset( $get_cart_data[0]->post_id ) ) {
					$params['custom_values']['wcap_reason']   = 'Order Placed';
					$params['custom_values']['wcap_order_id'] = $get_cart_data[0]->post_id;
				}
				$remove = true;
			}

			if ( $remove ) {
				$list = $this->get_wcap_fluentcrm_default_list();

				$params['remove'] = array(
					'type'        => 'lists',
					'detach'      => array( $list['slug'] ),
					'subscribers' => array( $this->contact_id ),
				);
			}

			$call->set_data( $params );
			$result = $call->process();

			$message = $result['body']['message'];

			// Update the status of the integration for the cart in the table.
			if ( ! ( 200 === absint( $result['response'] ) || 204 === absint( $result['response'] ) ) ) {
				$error  = __( 'Error Response Code: ', 'woocommerce-ac' ) . $result['response'] . __( '. WCAP Error: ', 'woocommerce-ac' );
				$error .= is_array( $result['body'] ) && isset( $result['body']['detail'] ) ? $result['body']['detail'] : __( 'No Response from Fluentcrm. ', 'woocommerce-ac' );
				$error .= ( 502 === absint( $result['response'] ) ) ? __( 'Wcap Error: ', 'woocommerce-ac' ) . $result['body'][0] : '';

				$connector_cart_id = '';
				$status            = 'failed';

				if ( isset( $result['body']['detail'] ) ) {
					$message .= ': ' . $result['body']['detail'];
				} elseif ( isset( $result['body']['errors'] ) ) {
					$message .= ': ' . wp_json_encode( $result['body']['errors'] );
				}
			} else {
				// Update the details in the plugin table.
				$connector_cart_id = '';
				$status            = 'complete';
				wcap_set_cart_session( 'wcap_fluentcrm_sync_cart_id', $abandoned_id );
				if ( 'delete' === $action ) {

					$wpdb->delete( // phpcs:ignore
						$wpdb->prefix . 'ac_connector_sync',
						array(
							'cart_id'        => $abandoned_id,
							'connector_name' => 'fluentcrm',
						)
					);
				}
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
						'connector_name' => 'fluentcrm',
					)
				);
			} elseif ( 'delete' !== $action ) {
				$wpdb->insert( // phpcs:ignore
					$wpdb->prefix . 'ac_connector_sync',
					array(
						'cart_id'           => $abandoned_id,
						'connector_cart_id' => $connector_cart_id,
						'connector_name'    => 'fluentcrm',
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
			$common_class                    = Wcap_Connectors_Common::get_instance();
			$cart_details['id']              = $cart_history->id;
			$cart_details['user_id']         = $cart_history->user_id;
			$cart_details['abandoned_date']  = date( 'Y-m-d', $cart_history->abandoned_cart_time ); // phpcs:ignore
			$cart_details['abandoned_date'] .= 'T'.date( 'H:i:s', $cart_history->abandoned_cart_time ); // phpcs:ignore
			$dt                              = new DateTime( 'now', new DateTimeZone( wp_timezone_string() ) );
			$dt->setTimestamp( $cart_history->abandoned_cart_time );
			$cart_details['abandoned_date'] .= $dt->format( 'P' );
			$cart_details['checkout_link']   = $cart_history->checkout_link;
			$cart_details['recovered_cart']  = $cart_history->recovered_cart;

			list( $cart_details['cart_items'], $cart_details['cart_total'], $cart_details['totalProducts'], $cart_details['captured_by'], $cart_details['sub_total'],$cart_details['discount'], $cart_details['shipping'], $cart_details['tax'] ) = $this->get_cart_and_totals( $cart_history );

			$cart_html_array           = $common_class->wcap_create_cart_html( json_decode( $cart_history->abandoned_cart_info, true ), $cart_history->checkout_link );
			$cart_details['cart_html'] = $cart_html_array['html'];
		}
		return $cart_details;
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
		$sub_total           = 0;
		$tax                 = 0;
		$shipping            = 0;
		$discount            = 0;

		foreach ( $abandoned_cart_info['cart'] as $c_key => $c_value ) {
			$line_total   = $c_value['line_total'];
			$pid          = $c_value['product_id'];
			$product      = wc_get_product( $pid );
			if ( ! $product ) {
				continue;
			}
			$plink        = get_permalink( $pid );
			$variant_id   = '';
			$variant_name = '';
			$variant_sku  = '';
			if ( isset( $c_value['variation_id'] ) && $c_value['variation_id'] ) {
				$variant_id   = $c_value['variation_id'];
				$variant      = wc_get_product( $variant_id );
				$variant_name = $variant->get_formatted_name();
				$variant_sku  = $variant->get_sku();
			}	

			$image_id   = $product->get_image_id();
			$image_url  = wp_get_attachment_image_url( $image_id );
			$cats_array = wp_get_post_terms( $c_value['product_id'], 'product_cat', array( 'fields' => 'names' ) );

			$cart_items[]   = array(
				'category'     => is_array( $cats_array ) ? implode( ',', $cats_array ) : '',
				'id'           => $pid,
				'name'         => $product->get_formatted_name(),
				'image'        => $image_url,
				'url'          => $plink,
				'quantity'     => (string) $c_value['quantity'],
				'price'        => (string) ( $line_total / $c_value['quantity'] ),
				'sku'          => $product->get_sku(),
				'variant_id'   => $variant_id,
				'variant_name' => $variant_name,
				'variant_sku'  => $variant_sku,
			);
			$sub_total     += $c_value['line_subtotal'];
			$tax           += $c_value['line_tax'];
			$cart_quantity += $c_value['quantity'];
			$discount      += ( $c_value['line_subtotal'] - $c_value['line_total'] );
		}
		$captured_by = isset( $abandoned_cart_info['captured_by'] ) ? $abandoned_cart_info['captured_by'] : '';
		$shipping    = isset( $abandoned_cart_info['shipping_charges'] ) ? $abandoned_cart_info['shipping_charges'] : 0;
		$cart_total  = $sub_total + $shipping + $tax;
		return array( $cart_items, $cart_total, $cart_quantity, $captured_by, $sub_total, $discount, $shipping, $tax );
	}
	/**
	 * Abandoned Cart record updated.
	 *
	 * @param array $where - deleted row condition.
	 */
	public function wcap_before_delete_guest( $where = array() ) {
		$user_id = 0;
		if ( is_array( $where ) && array_key_exists( 'id', $where ) ) {
			$user_id = $where['id'];
		}
		$abandoned_id = wcap_get_abandoned_id_from_user_id( $user_id );
		if ( $abandoned_id > 0 ) {
			$this->wcap_delete_cart_on_fluentcrm( $abandoned_id );
		}
	}
	/**
	 * Abandoned Cart record updated.
	 *
	 * @param array $where - deleted row condition.
	 */
	public function wcap_before_delete_history( $where = array() ) {

		$abandoned_id = 0;

		if ( is_array( $where ) && array_key_exists( 'id', $where ) ) {
			$abandoned_id = $where['id'];
		} elseif ( is_array( $where ) && array_key_exists( 'user_id', $where ) ) {
			$abandoned_id = wcap_get_abandoned_id_from_user_id( $where['user_id'] );
		}
		if ( ! $abandoned_id ) {
			return;
		}

		$this->wcap_delete_cart_on_fluentcrm( $abandoned_id );
	}
	/**
	 *  Delete cart on Fluentcrm
	 *
	 * @param int $abandoned_id - abandoned id.
	 */
	public function wcap_delete_cart_on_fluentcrm( $abandoned_id = 0 ) {

		$common_inst      = Wcap_Connectors_Common::get_instance();
		$customer_details = $common_inst->wcap_get_contact_data( $abandoned_id );
		$cart_details     = $this->wcap_get_cart( $abandoned_id, $customer_details );

		if ( empty( $customer_details['email'] ) ) {
			return;
		}

		$fluentcrm_customer_details = wcap_get_cart_session( 'fluentcrm_customer_details' );
		$this->contact_id           = $fluentcrm_customer_details['id'];

		$this->wcap_create_cart_in_fluentcrm( $abandoned_id, $customer_details['email'], $cart_details, 'delete' );

	}
	/**
	 * Get tags from FluentCRM
	 */
	private function get_wcap_fluentcrm_tags() {
		$wcap_fluentcrm_tags = wcap_get_cart_session( 'wcap_fluentcrm_tags' );
		if ( empty( $wcap_fluentcrm_tags ) ) {
			$connector_mc = Wcap_Fluentcrm::get_instance();
			$call         = $connector_mc->registered_calls['wcap_fluentcrm_get_tags'];
			// Fetch the connector data.
			$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( $this->slug );
			$api_name           = isset( $connector_settings['api_name'] ) ? $connector_settings['api_name'] : '';
			$api_key            = isset( $connector_settings['api_key'] ) ? $connector_settings['api_key'] : '';
			$params             = array(
				'api_name' => $api_name,
				'api_key'  => $api_key,
			);
			$call->set_data( $params );
			$result = $call->process();
			if ( isset( $result['body']['tags']['data'] ) ) {
				foreach ( $connector_mc->events as $event ) {
					foreach ( $result['body']['tags']['data'] as $tag ) {
						if ( $tag['title'] === $event ) {
							$wcap_fluentcrm_tags[ $event ] = $tag['id'];
						}
					}
				}
				wcap_set_cart_session( 'wcap_fluentcrm_tags', $wcap_fluentcrm_tags );
			}
		}
		return $wcap_fluentcrm_tags;
	}

	/**
	 * Get tags from FluentCRM
	 */
	private function get_wcap_fluentcrm_default_list() {
		$wcap_fluentcrm_default_list = wcap_get_cart_session( 'wcap_fluentcrm_default_list' );
		if ( empty( $wcap_fluentcrm_default_list ) ) {
			$connector_mc = Wcap_Fluentcrm::get_instance();
			$call         = $connector_mc->registered_calls['wcap_fluentcrm_get_lists'];
			// Fetch the connector data.
			$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( $this->slug );
			$api_name           = isset( $connector_settings['api_name'] ) ? $connector_settings['api_name'] : '';
			$api_key            = isset( $connector_settings['api_key'] ) ? $connector_settings['api_key'] : '';
			$list_id            = isset( $connector_settings['default_list'] ) ? $connector_settings['default_list'] : '';

			$params = array(
				'api_name' => $api_name,
				'api_key'  => $api_key,
				'id'       => $list_id,
			);
			$call->set_data( $params );
			$result = $call->process();
			if ( ! empty( $result['body'] ) ) {
				$wcap_fluentcrm_default_list = $result['body'];
				wcap_set_cart_session( 'wcap_fluentcrm_default_list', $wcap_fluentcrm_default_list );
			}
		}
		return $wcap_fluentcrm_default_list;
	}
}
new Wcap_Fluentcrm_Upsert_Cart_Action();
