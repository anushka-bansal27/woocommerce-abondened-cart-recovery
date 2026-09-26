<?php
/**
 * Insert/Update Cart in MC.
 *
 * @package Connectors/Klaviyo/Actions
 */

/**
 * Insert/Update Carts in MC.
 */
class Wcap_WP_Fusion_Upsert_Cart_Action {
	/**
	 * Slug Name
	 *
	 * @var $slug
	 */
	public $slug = 'wcap_wp_fusion';
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
		if ( 'active' !== $connector_settings['status'] || ! ( class_exists( 'WP_Fusion' ) || class_exists( 'WP_Fusion_Lite' ) ) ) {
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
				wcap_set_cart_session( 'wcap_wpfusion_sync_cart_id', '' );
				$this->cart_moved   = true;
				$this->old_email_id = $old_email_id;

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
						'connector_name' => 'wp_fusion',
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
		$abandoned_id               = wcap_get_abandoned_id_from_user_id( $where['id'] );
		$wcap_wpfusion_sync_cart_id = $this->get_wcap_wpfusion_sync_cart_id( $abandoned_id );
		if ( is_array( $value ) && array_key_exists( 'email_id', $value ) && array_key_exists( 'id', $where ) && ( $this->cart_moved || ( empty( $wcap_wpfusion_sync_cart_id ) && isset( $_POST['wcap_save_guest_data'] ) ) ) ) { //phpcs:ignore
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
		$this->cart_ignored = false;
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
	private function get_wcap_wpfusion_sync_cart_id( $abandoned_id ) {
		global $wpdb;
		$wcap_wpfusion_sync_cart_id = wcap_get_cart_session( 'wcap_wpfusion_sync_cart_id' );
		if ( empty( $wcap_wpfusion_sync_cart_id ) ) {
			$result = $wpdb->get_results( 'select * from ' . $wpdb->prefix . 'ac_connector_sync  where connector_name ="wp_fusion" AND cart_id = ' . $abandoned_id ); //phpcs:ignore
			if ( ! empty( $result[0] ) && isset( $result[0]->connector_cart_id ) ) {
				$wcap_wpfusion_sync_cart_id = $result[0]->cart_id;
				wcap_set_cart_session( 'wcap_wpfusion_sync_cart_id', $wcap_wpfusion_sync_cart_id );
			}
		}
		return $wcap_wpfusion_sync_cart_id;
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
		$connector_sync_id = $common_inst->wcap_get_cart_status( $abandoned_id, 'wp_fusion' );
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
				$this->wcap_create_cart_in_wp_fusion( $abandoned_id, $this->old_email_id, $cart_details, $action );
			}
			$status = $this->wcap_create_cart_in_wp_fusion( $abandoned_id, $email, $cart_details, $action );

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
	public function wcap_create_cart_in_wp_fusion( $abandoned_id, $email, $cart_details, $action = 'insert', $merge_fields = array(), $interests = array() ) {

		$wp_fusion_customer_details = wcap_get_cart_session( 'wp_fusion_customer_details' );
		if ( empty( $wp_fusion_customer_details['id'] ) ) {
			return false;
		}
		$connector_mc = Wcap_WP_Fusion::get_instance();
		if ( '' !== $email && $abandoned_id > 0 && ( '' !== $cart_details['checkout_link'] || is_user_logged_in() ) ) {
			$update     = false;
			$contact_id = $wp_fusion_customer_details['id'];

			$wcap_wpfusion_sync_cart_id = $this->get_wcap_wpfusion_sync_cart_id( $abandoned_id );
			if ( ! empty( $wcap_wpfusion_sync_cart_id ) ) {
				$update = true;
			}

			if ( empty( $wcap_wpfusion_sync_cart_id ) ) {
				$tags['event'] = $connector_mc->events[0];
			} else {
				$tags['event'] = $connector_mc->events[1];
			}

			if ( ! empty( $cart_details['cart_ignored'] ) ) {

				$tags['event'] = $connector_mc->events[2];

				switch ( $cart_details['cart_ignored'] ) {
					case 2:
						$tags['reason'] = 'Old cart ignored and New cart created';
						break;
					case 2:
						$tags['reason'] = 'Cancelled Order';
						break;
					case 3:
						$tags['reason'] = 'Received Order';
						break;
					case 4:
						$tags['reason'] = 'Pending Payment';
						break;
				}
			}
			if ( ! empty( $cart_details['recovered_cart'] ) ) {
				$tags['event']                  = $connector_mc->events[3];
				$tags['properties']['order_id'] = $cart_details['recovered_cart'];
			}
			if ( isset( $this->cart_moved ) && $this->cart_moved ) {
				$tags['event']        = $connector_mc->events[2];
				$tags['reason']       = 'Email changed';
				$tags['new_email_id'] = $this->current_email_id;
				$tags['old_email_id'] = $this->old_email_id;
				$action               = 'insert';

				if ( $email !== $this->old_email_id ) {
					$tags['event'] = 'Cart Added';
				} else {
					$contact_id = wp_fusion()->crm->get_contact_id( $email );
				}
			}
			if ( 'delete' === $action ) {
				$tags['event'] = $connector_mc->events[4];
			}

			$result = wp_fusion()->crm->apply_tags( $tags, $contact_id );

			if ( isset( $this->old_email_id ) && $email === $this->old_email_id ) {
				$update = false;
			}

			$args = array(
				'cart_id'      => $abandoned_id . '-' . $email, // email id added to make it unique in case of  change in email at checkout.
				'recovery_url' => $cart_details['checkout_link'],
				'items'        => $cart_details['cart_items'],
				'discounts'    => array(
					array(
						'name'   => 'Discount',
						'amount' => $cart_details['discounts'],
					),
				),
				'user_email'   => $email,
				'provider'     => EDD_SL_ITEM_NAME_AC_WOO,
				'update'       => $update,
				'currency'     => get_woocommerce_currency(),
			);
			global $wpdb;

			if ( 'delete' === $action ) {
				$query              = $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'postmeta' . " WHERE meta_key ='wcap_abandoned_cart_id' AND meta_value=%d", $abandoned_id );
				$get_cart_data      = $wpdb->get_results( $query ); //phpcs:ignore
				$args['externalid'] = $get_cart_data[0]->post_id;
			}

			do_action( 'wpf_abandoned_cart_created', $wp_fusion_customer_details['id'], $args );

			// Update the status of the integration for the cart in the table.
			if ( $result && ! ( $result instanceof WP_Error ) ) {
				$connector_cart_id = '';
				$status            = 'complete';
				$message           = 'Tags Applied';
			} else {
				// Update the details in the plugin table.
				$connector_cart_id = '';
				$status            = 'failed';
				$message           = $result::get_error_message();
				wcap_set_cart_session( 'wcap_wpfusion_sync_cart_id', $abandoned_id );
			}

			if ( 'delete' === $action ) {

				$wpdb->delete( // phpcs:ignore
					$wpdb->prefix . 'ac_connector_sync',
					array(
						'cart_id'        => $abandoned_id,
						'connector_name' => 'wp_fusion',
					)
				);
				return;
			}

			if ( 'update' === $action ) {
				$wpdb->update( // phpcs:ignore
					$wpdb->prefix . 'ac_connector_sync',
					array(
						'connector_cart_id' => $connector_cart_id,
						'sync_date'         => current_time( 'timestamp' ), // phpcs:ignore
						'status'            => $status,
						'sync_data'         => wp_json_encode( $tags ),
						'message'           => $message,
					),
					array(
						'cart_id'        => $abandoned_id,
						'connector_name' => 'wp_fusion',
					)
				);
			} else {
				$wpdb->insert( // phpcs:ignore
					$wpdb->prefix . 'ac_connector_sync',
					array(
						'cart_id'           => $abandoned_id,
						'connector_cart_id' => $connector_cart_id,
						'connector_name'    => 'wp_fusion',
						'sync_date'         => current_time( 'timestamp' ), // phpcs:ignore
						'status'            => $status,
						'sync_data'         => wp_json_encode( $tags ),
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
			$cart_details['checkout_link']   = $cart_history->checkout_link;
			$cart_details['recovered_cart']  = $cart_history->recovered_cart;
			list( $cart_details['cart_items'], $cart_details['cart_total'], $cart_details['totalProducts'], $cart_details['captured_by'], $cart_details['sub_total'], $cart_details['tax'], $cart_details['discounts'] ) = $this->get_cart_and_totals( $cart_history );
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
		$discount            = 0;
		$sub_total           = 0;
		$tax                 = 0;

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
			
			if ( ! $product ) {
				continue;
			}

			$cart_items[]   = array(
				'product_id'  => $pid,
				'name'        => $product->get_formatted_name(),
				'image_url'   => $image_url,
				'product_url' => $plink,
				'quantity'    => (string) $c_value['quantity'],
				'description' => get_the_excerpt( $pid ),
				'total'       => round( $line_total, 2 ),
				'price'       => (string) ( $line_total / $c_value['quantity'] ),
				'sku'         => $product->get_sku(),
			);
			$cart_total    += $line_total;
			$cart_quantity += $c_value['quantity'];
			$sub_total     += $c_value['line_subtotal'];
			$tax           += $c_value['line_tax'];
			$discount      += ( $c_value['line_subtotal'] - $c_value['line_total'] );
		}
		$captured_by = isset( $abandoned_cart_info['captured_by'] ) ? $abandoned_cart_info['captured_by'] : '';

		return array( $cart_items, $cart_total, $cart_quantity, $captured_by, $sub_total, $tax, $discount );
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
			$this->wcap_delete_cart_on_wp_fusion( $abandoned_id );
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

		$this->wcap_delete_cart_on_wp_fusion( $abandoned_id );
	}
	/**
	 *  Delete cart on wp_fusion
	 *
	 * @param int $abandoned_id - abandoned id.
	 */
	public function wcap_delete_cart_on_wp_fusion( $abandoned_id = 0 ) {

		$common_inst      = Wcap_Connectors_Common::get_instance();
		$customer_details = $common_inst->wcap_get_contact_data( $abandoned_id );
		$cart_details     = $this->wcap_get_cart( $abandoned_id, $customer_details );

		if ( empty( $customer_details['email'] ) ) {
			return;
		}

		$this->wcap_create_cart_in_wp_fusion( $abandoned_id, $customer_details['email'], $cart_details, 'delete' );

	}
}
new Wcap_WP_Fusion_Upsert_Cart_Action();
