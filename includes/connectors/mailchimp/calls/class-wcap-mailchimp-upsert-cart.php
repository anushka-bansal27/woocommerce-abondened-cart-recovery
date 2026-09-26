<?php

class Wcap_Mailchimp_Upsert_Cart extends Wcap_Call {

	public $call_slug   = 'wcap_mailchimp_upsert_cart';
	private static $ins = null;

	public function __construct() {
		$this->required_fields = array( 'api_key', 'email', 'wcap_ab_id', 'store_id', 'abandoned_date', 'cart_url', 'cart_items', 'customer_data', 'cart_total' );
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function process() {
		$is_required_fields_present = $this->check_fields( $this->data, $this->required_fields );
		if ( false === $is_required_fields_present ) {
			return $this->show_fields_error();
		}

		if ( ! is_email( $this->data['email'] ) ) {
			return array(
				'response' => 502,
				'body'     => array( 'Email is not valid' ),
			);
		}

		if ( 'update' === $this->data['action'] ) {
			$params = array(
				'cart_id'         => 'wcap_cart_' . $this->data['wcap_ab_id'],
				'customer'        => $this->data['customer_data'],
				'lines'           => $this->get_product_order_line_items( $this->data['cart_items'] ),
				'currency_code'   => get_woocommerce_currency(),
				'order_total'     => $this->data['cart_total'],
				'checkout_url'    => $this->data['cart_url']
	//			'billing_address' => $customer_details['address']
			);
			$action = Wcap_Call::$PATCH;
		} else {
			$params = array(
				'id'              => 'wcap_cart_' . $this->data['wcap_ab_id'],
				'customer'        => $this->data['customer_data'],
				'lines'           => $this->get_product_order_line_items( $this->data['cart_items'] ),
				'currency_code'   => get_woocommerce_currency(),
				'order_total'     => $this->data['cart_total'],
				'checkout_url'    => $this->data['cart_url']
	//			'billing_address' => $customer_details['address']
			);
			$action = Wcap_Call::$POST;
		}

		Wcap_Mailchimp::set_headers( $this->data['api_key'] );

		return $this->make_wp_requests( $this->get_endpoint(), wp_json_encode( $params ), Wcap_Mailchimp::get_headers(), $action );
	}

	/**
	 * @param array $cart_items
	 *
	 * @return array
	 */
	public function get_product_order_line_items( $cart_items ) {
		$order_items = array();
		foreach ( $cart_items as $key => $item_data ) {
			$product_id = ( isset( $item_data['product_id'] ) ) ? $item_data['product_id'] : 0;
			$quantity   = ( isset( $item_data['quantity'] ) ) ? $item_data['quantity'] : 0;
			$product    = wc_get_product( $product_id );
			if ( ! $product instanceof WC_Product ) {
				continue;
			}

			$mailchimp_product_id = $this->get_product_id_by_wc_item( $product );

			/** if error: */
			if ( is_array( $mailchimp_product_id ) || empty( $mailchimp_product_id ) ) {
				continue;
			}

			$item_data = array(
				'id'                 => 'wcap_order_line_' . $key,
				'product_id'         => $mailchimp_product_id,
				'product_variant_id' => $mailchimp_product_id,
				'quantity'           => $quantity,
				'price'              => $product->get_price(),
			);

			$order_items[] = $item_data;
		}

		return $order_items;
	}

	/**
	 * @param WC_Product $product
	 *
	 * @return array|int
	 */
	public function get_product_id_by_wc_item( $product ) {
		$mailchimp_product_id = get_post_meta( $product->get_id(), 'wcap_mailchimp_product_id', true );
		if ( ! empty( $mailchimp_product_id ) ) {
			return $mailchimp_product_id;
		}

		$product_id           = $product->get_id();
		$name                 = str_replace( ' &ndash;', ': ', $product->get_name() );
		$image_url            = wp_get_attachment_url( $product->get_image_id() );
		$price                = $product->get_price();
		$mailchimp_product_id = $this->get_mailchimp_product_id( $name, $product_id, $image_url, $price );
		if ( is_array( $mailchimp_product_id ) ) {
			return $mailchimp_product_id;
		}

		update_post_meta( $product_id, 'wcap_mailchimp_product_id', $mailchimp_product_id );

		return $mailchimp_product_id;
	}

	public function get_mailchimp_product_id( $name, $wc_product_id, $image_url, $price ) {
		$connector_mc = Wcap_Mailchimp::get_instance();
		$call         = $connector_mc->registered_calls['wcap_mailchimp_create_product'];
		$call->set_data( array(
			'product_id'   => $wc_product_id,
			'product_name' => $name,
			'api_key'      => $this->data['api_key'],
			'store_id'     => $this->data['store_id'],
			'image_url'    => $image_url,
			'price'        => $price,
		) );
		$result = $call->process();

		if ( 200 !== $result['response'] || ! isset( $result['body']['id'] ) ) {
			$error = __( 'Error Response Code: ', 'woocommerce-ac' ) . $result['response'] . __( '. Mailchimp Error: ', 'woocommerce-ac' );
			$error .= is_array( $result['body'] ) && isset( $result['body']['detail'] ) ? $result['body']['detail'] : __( 'No Response from Mailchimp. ', 'woocommerce-ac' );
			$error .= ( 502 === absint( $result['response'] ) ) ? __( 'Wcap Error: ', 'woocommerce-ac' ) . $result['body'][0] : '';

			return array(
				'status'  => 'failed',
				'message' => $error,
			);
		}

		return $result['body']['id'];
	}

	/**
	 * Return the endpoint.
	 *
	 * @return string
	 */
	public function get_endpoint() {
		$data_center = Wcap_Mailchimp::get_data_center( $this->data['api_key'] );

		if ( 'update' === $this->data['action'] ) {
			return Wcap_Mailchimp::get_endpoint( $data_center ) . '/ecommerce/stores/' . $this->data['store_id'] . '/carts/wcap_cart_' . $this->data['wcap_ab_id'];
		} else {
			return Wcap_Mailchimp::get_endpoint( $data_center ) . '/ecommerce/stores/' . $this->data['store_id'] . '/carts';
		}
	}

}

return 'Wcap_Mailchimp_Upsert_Cart';
