<?php


class Wcap_Drip_Create_Order extends Wcap_Call {

	public $call_slug = 'wcap_drip_create_order';
	private static $ins = null;

	public function __construct() {
		$this->required_fields = array( 'api_token', 'account_id', 'email', 'order_id' );
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

		$order_id = $this->data['order_id'];
		if ( is_hpos_enabled() ) { // HPOS usage is enabled.
			$order_url = admin_url( "admin.php?page=wc-orders&id=$order_id&action=edit" );
		} else { // Traditional CPT-based orders are in use.
			$order_url = admin_url( "post.php?post=$order_id&action=edit" );
		}

		$params = array(
			'provider'    => get_option( 'blogname' ),
			'email'       => $this->data['email'],
			'action'      => 'placed',
			'occurred_at' => strval( $this->data['placed_at']->format('c') ),
			'order_id'    => strval( $order_id ),
			'grand_total' => intval( $this->data['grand_total'] ),
			'currency'    => $this->data['currency'],
			'order_url'   => $order_url,
			'items'       => $this->wcap_get_items( $this->data['items'] )
		);
        Wcap_Drip::set_headers( $this->data['api_token'] );

        $res = $this->make_wp_requests( $this->get_endpoint(), wp_json_encode( $params ), Wcap_Drip::get_headers(), Wcap_Call::$POST );

        return $res;
	}

	/**
	 * Return the endpoint.
	 *
	 * @return string
	 */
	public function get_endpoint() {
		return Wcap_Drip::get_endpoint( 'v3' ) . $this->data['account_id'] . '/shopper_activity/order';
	}

	public function wcap_get_items( $items ) {
		$order_items = array();
		foreach ( $items as $id => $item_data ) {

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
				'total'              => intval( $item_data['line_total'] ),
				'product_url'        => $product_url,
				'image_url'          => wp_get_attachment_url( $product->get_image_id() ),
			);

			$order_items[] = $item_data;
		}
		return $order_items;
	}
}

return 'Wcap_Drip_Create_Order';
