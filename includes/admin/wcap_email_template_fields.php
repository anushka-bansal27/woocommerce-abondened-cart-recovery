<?php
/**
 * It will display the add/edit fields of the email template.
 *
 * @author   LittleCodr
 * @package Abandoned-Cart-Pro-for-WooCommerce/Admin/Template
 * @since 5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wcap_Email_Template_Fields' ) ) {
	/**
	 * It will display the add/edit fields of the email template.
	 */
	class Wcap_Email_Template_Fields {
		/**
		 * It will display the add/edit fields of the email template.
		 *
		 * @globals mixed $wpdb
		 * @globals mixed $woocommerce
		 * @since 1.0
		 * @todo Remove inline Javascript.
		 */
		public static function wcap_get_email_template_fields() {
			$template_settings                 = array();
			$template_id                       = isset( $_GET['id'] ) && '' !== $_GET['id'] ? sanitize_text_field( wp_unslash( $_GET['id'] ) ) : 0; // phpcs:ignore
			$template_settings['mode']         = Wcap_Common::wcap_get_mode();
			$template_settings['rules']        = isset( $results[0]->rules ) ? json_decode( $results[0]->rules ) : array();
			$template_settings['initial_data'] = '';

			$rule_type_options = apply_filters(
				'wcap_rules_engine_rule_type_values',
				array(
					array(
						'key'      => '',
						'value'    => __( 'Select Rule Type', 'woocommerce-ac' ),
						'disabled' => true,
					),
					array(
						'key'   => 'coupons',
						'value' => __( 'Coupons', 'woocommerce-ac' ),
					),
					array(
						'key'   => 'send_to',
						'value' => __( 'Send Emails to', 'woocommerce-ac' ),
					),
					array(
						'key'      => 'order_disabled',
						'value'    => __( 'Order', 'woocommerce-ac' ),
						'disabled' => true,
					),
					array(
						'key'   => 'payment_gateways',
						'value' => __( 'Payment Gateways', 'woocommerce-ac' ),
						'extra' => '→',
					),
					array(
						'key'   => 'order_status',
						'value' => __( 'Order Status', 'woocommerce-ac' ),
						'extra' => '→',
					),
					array(
						'key'      => 'cart_disabled',
						'value'    => __( 'Cart', 'woocommerce-ac' ),
						'disabled' => true,
					),
					array(
						'key'   => 'cart_status',
						'value' => __( 'Cart Status', 'woocommerce-ac' ),
						'extra' => '→',
					),
					array(
						'key'   => 'product_cat',
						'value' => __( 'Product Categories', 'woocommerce-ac' ),
						'extra' => '→',
					),
					array(
						'key'   => 'product_tag',
						'value' => __( 'Product Tags', 'woocommerce-ac' ),
						'extra' => '→',
					),
					array(
						'key'   => 'cart_items',
						'value' => __( 'Cart Items', 'woocommerce-ac' ),
						'extra' => '→',
					),
					array(
						'key'   => 'cart_items_count',
						'value' => __( 'Number of Cart Items', 'woocommerce-ac' ),
						'extra' => '→',
					),
					array(
						'key'   => 'cart_total',
						'value' => __( 'Cart Total', 'woocommerce-ac' ),
						'extra' => '→',
					),
					array(
						'key'      => 'location_disabled',
						'value'    => __( 'Location', 'woocommerce-ac' ),
						'disabled' => true,
					),
					array(
						'key'   => 'countries',
						'value' => __( 'Countries', 'woocommerce-ac' ),
						'extra' => '→',
					),
				)
			);

			$rule_condition_options                = apply_filters(
				'wcap_rules_engine_rule_condition_values',
				array(
					''                      => __( 'Select Condition', 'woocommerce-ac' ),
					'includes'              => __( 'Includes any of', 'woocommerce-ac' ),
					'excludes'              => __( 'Excludes any of', 'woocommerce-ac' ),
					'greater_than_equal_to' => __( 'Greater than or equal to', 'woocommerce-ac' ),
					'equal_to'              => __( 'Equal to', 'woocommerce-ac' ),
					'less_than_equal_to'    => __( 'Less than or equal to', 'woocommerce-ac' ),
				)
			);
			$template_settings['rule_conditions']  = $rule_condition_options;
			$template_settings['rule_types']       = $rule_type_options;
			$template_settings['wcap_match_rules'] = '';
			$template_settings['email_frequency']  = 1;
			$template_settings['day_or_hour']      = 'Minutes';
			$template_settings['coupon_ids']       = array();

			if ( 'edittemplate' === $template_settings['mode'] ) {
				$template_settings['ac_settings_frm'] = 'update';
			} else {
				$template_settings['ac_settings_frm'] = 'save';
			}

			if ( ! $template_id ) {
				return $template_settings;
			}
			global $wpdb;

			if ( 'edittemplate' === $template_settings['mode'] ) {
				$edit_id = $_GET['id']; // phpcs:ignore
				$query   = 'SELECT wpet . *  FROM `' . WCAP_NOTIFICATION_TEMPLATES_TABLE . '` AS wpet WHERE id= %d';
				$results = $wpdb->get_results( $wpdb->prepare( $query, $edit_id ) ); // phpcs:ignore
			}
			if ( 'copytemplate' === $template_settings['mode'] ) {
				$copy_id    = $_GET['id']; // phpcs:ignore
				$query_copy = 'SELECT wpet . *  FROM `' . WCAP_NOTIFICATION_TEMPLATES_TABLE . '` AS wpet WHERE id= %d';
				$results    = $wpdb->get_results( $wpdb->prepare( $query_copy, $copy_id ) ); // phpcs:ignore
			}

			$template_settings['rules']                        = isset( $results[0]->rules ) ? json_decode( $results[0]->rules ) : array();
			$template_settings['wcap_match_rules']             = isset( $results[0]->match_rules ) ? $results[0]->match_rules : '';
			$template_settings['woocommerce_ac_template_name'] = $results[0]->template_name;

			if ( 'copytemplate' === $template_settings['mode'] ) {
				$template_settings['woocommerce_ac_template_name'] = 'Copy of ' . $results[0]->template_name;
			}

			$template_settings['id']                           = $results[0]->id;
			$template_settings['email_frequency']              = $results[0]->frequency;
			$template_settings['day_or_hour']                  = $results[0]->day_or_hour;
			$template_settings['woocommerce_ac_email_subject'] = $results[0]->subject;
			$template_settings['initial_data']                 = $results[0]->body;
			$template_settings['is_wc_template']               = $results[0]->is_wc_template;
			$template_settings['wcap_wc_email_header']         = $results[0]->wc_email_header;
			$template_settings['unique_coupon']                = $results[0]->generate_unique_coupon_code;
			$template_settings['wcap_discount_type']           = isset( $results[0]->discount_type ) ? $results[0]->discount_type : '';
			$template_settings['wcap_coupon_amount']           = $results[0]->discount;
			$template_settings['wcap_allow_free_shipping']     = $results[0]->discount_shipping;
			$wcac_coupon_expiry                                = $results[0]->discount_expiry;
			$wcac_coupon_expiry_explode                        = explode( '-', $wcac_coupon_expiry );
			$template_settings['wcac_coupon_expiry']           = isset( $wcac_coupon_expiry_explode[0] ) ? $wcac_coupon_expiry_explode[0] : 0;
			$template_settings['expiry_day_or_hour']           = isset( $wcac_coupon_expiry_explode[1] ) ? $wcac_coupon_expiry_explode[1] : 'hours';
			$template_settings['individual_use']               = $results[0]->individual_use;
			$coupon_ids                                        = explode( ',', $results[0]->coupon_code );

			if ( ! empty( $coupon_ids ) && ! empty( $coupon_ids[0] ) ) {
				foreach ( $coupon_ids as $coupon_id ) {
					$template_settings['coupon_ids'][ $coupon_id ] = get_the_title( $coupon_id );
				}
			}

			if ( is_array( $template_settings['rules'] ) && count( $template_settings['rules'] ) > 0 ) {
				foreach ( $template_settings['rules'] as &$rule ) {

					$rule_value_array = array();
					switch ( $rule->rule_type ) {
						case 'payment_gateways':
							$wc_payment_gateways = new WC_Payment_Gateways();
							$payment_gateways    = $wc_payment_gateways->payment_gateways();
							foreach ( $payment_gateways as $slug => $gateways ) {
								if ( 'yes' === $gateways->enabled ) {
									$rule_value_array[ $slug ] = $gateways->title;
								}
							}
							break;
						case 'cart_status':
							$rule_value_array = array(
								'abandoned'           => __( 'Abandoned', 'woocommerce-ac' ),
								'abandoned-pending'   => __( 'Abandoned - Pending Payment', 'woocommerce-ac' ),
								'abandoned-cancelled' => __( 'Abandoned - Order Cancelled', 'woocommerce-ac' ),
							);
							break;
						case 'send_to':
							$rule_value_array = array(
								'all'                 => __( 'All', 'woocommerce-ac' ),
								'registered_users'    => __( 'Registered Users', 'woocommerce-ac' ),
								'guest_users'         => __( 'Guest Users', 'woocommerce-ac' ),
								'wcap_email_customer' => __( 'Customers', 'woocommerce-ac' ),
								'wcap_email_admin'    => __( 'Admin', 'woocommerce-ac' ),
								'wcap_email_customer_admin' => __( 'Customers & Admin', 'woocommerce-ac' ),
								'email_addresses'     => __( 'Email Addresses', 'woocommerce-ac' ),
							);
						case 'countries':
							$wc_countries_object  = new WC_Countries();
							$all_countries_list   = $wc_countries_object->get_countries();
							$rule_value_array[''] = __( 'Select countries', 'woocommerce-ac' );
							foreach ( $all_countries_list as $code => $name ) {
								$rule_value_array[ $code ] = $name;
							}
							break;
						case 'order_status':
							$wc_order_statuses = wc_get_order_statuses();
							foreach ( $wc_order_statuses as $slug => $name ) {
								$rule_value_array[ $slug ] = $name;
							}
							break;
					}
					$rule_value_array = apply_filters( 'wcap_rules_engine_rule_option_values', $rule_value_array );
					$rule->rule_value = (array) $rule->rule_value;

					foreach ( $rule->rule_value as $rule_key => $rule_value ) {
						$title = '';
						if ( 'send_to' === $rule->rule_type || 'payment_gateways' === $rule->rule_type || 'cart_status' === $rule->rule_type || 'order_status' === $rule->rule_type ) {
							$title = $rule_value_array[ $rule_value ];
						} elseif ( 'cart_items_count' === $rule->rule_type || 'cart_total' === $rule->rule_type ) {
							$title = $rule_value;
						} elseif ( 'countries' === $rule->rule_type ) {
							$title = WC()->countries->countries[ $rule_value ];
						} elseif ( 'custom_pages' === $rule->rule_type || 'products' === $rule->rule_type || 'cart_items' === $rule->rule_type ) {
							$title = get_the_title( $rule_value );
						} elseif ( 'product_cat' === $rule->rule_type || 'product_tag' === $rule->rule_type ) {
							$term_object = get_term( $rule_value );
							if ( isset( $term_object->name ) ) {
								$title = wp_kses_post( $term_object->name );
							}
						} else {
							$title = get_the_title( $rule_value );
						}

						$rule->rule_text[ $rule_key ] = $title;
					}

					$rule->rule_selText = isset( $rule->rule_text ) && is_array( $rule->rule_text ) ? implode( ', ', $rule->rule_text ) : ''; // phpcs:ignore
				}
			}

			return $template_settings;

		}
	}
}
