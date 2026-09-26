<?php

class Wcap_Drip_Upsert_Cart extends Wcap_Call {

	public $call_slug   = 'wcap_drip_upsert_cart';
	private static $ins = null;

	public function __construct() {
		$this->required_fields = array( 'api_token', 'account_id', 'email', 'cart_id', 'abandoned_time', 'cart_url', 'cart_items', 'cart_total' );
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

		$params = array(
			'provider'       => get_option( 'blogname' ),
			'email'          => $this->data['email'],
			'initial_status' => 'active',
			'action'         => $this->data['action'],
			'cart_id'        => strval( $this->data['cart_id'] ),
			'occurred_at'	 => gmdate( 'c', $this->data['abandoned_time'] ),
			'grand_total'    => $this->data['cart_total'],
			'currency'       => get_woocommerce_currency(),
			'cart_url'       => $this->data['cart_url'],
			'items'          => $this->wcap_dp_cart_items( $this->data['cart_items'] )
		);

		Wcap_Drip::set_headers( $this->data['api_token'] );

		return $this->make_wp_requests( $this->get_endpoint(), wp_json_encode( $params ), Wcap_Drip::get_headers(), Wcap_Call::$POST );
	}

	/**
	 * @param array $cart_items
	 *
	 * @return array
	 */
	public function wcap_dp_cart_items( $cart_items ) {
		$order_items = array();
		foreach ( $cart_items as $key => $item_data ) {
			$product_id = isset( $item_data['product_id'] ) ? $item_data['product_id'] : 0;
			$quantity   = isset( $item_data['quantity'] ) ? $item_data['quantity'] : 0;
			if ( isset( $item_data['variation_id'] ) && $item_data['variation_id'] > 0 ) {
				$variation_id = $item_data['variation_id'];
				$product      = wc_get_product( $variation_id );
				$product_url  = get_permalink( $variation_id );
			} else {
				$variation_id = $item_data['product_id'];
				$product      = wc_get_product( $product_id );
				$product_url  = get_permalink( $product_id );
			}
			if ( ! $product instanceof WC_Product ) {
				continue;
			}

			$item_data = array(
				'product_id'         => strval( $product_id ),
				'product_variant_id' => strval( $variation_id ),
				'name'               => str_replace( ' &ndash;', ': ', $product->get_name() ),
				'quantity'           => $quantity,
				'price'              => intval( $product->get_price() ),
				'total'              => $item_data['line_total'],
				'product_url'        => $product_url,
				'image_url'          => wp_get_attachment_url( $product->get_image_id() ),
			);

			$order_items[] = $item_data;
		}

		return $order_items;
	}

	/**
	 * Return the endpoint.
	 *
	 * @return string
	 */
	public function get_endpoint() {
		return Wcap_Drip::get_endpoint('v3') . $this->data['account_id'] . '/shopper_activity/cart';
	}

}

return 'Wcap_Drip_Upsert_Cart';
