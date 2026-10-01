<?php
/**
 * This file will trigger and send follow-up emails.
 *
 * @author  LittleCodr
 * @package Abandoned-Cart-Pro-for-WooCommerce/FollowUp
 * @since 8.21.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wcap_Follow_Up_Emails' ) ) {

	/**
	 * Email Verification Class
	 */
	class Wcap_Follow_Up_Emails {

		/**
		 * Contructor.
		 */
		public function __construct() {
			add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'wcap_trigger_scheduled_actions' ), 20, 3 );
			add_action( 'wcap_send_follow_up_email', array( __CLASS__, 'wcap_send_follow_up_email' ), 10, 3 );
		}

		/**
		 * Schedule one time action on WC order status change, if needed.
		 *
		 * @param int    $order_id - WC Order ID.
		 * @param string $old_status - Old WC status.
		 * @param string $new_status - New WC status.
		 */
		public static function wcap_trigger_scheduled_actions( $order_id, $old_status, $new_status ) {
			$follow_up_emails = wcap_get_active_email_templates( 'follow-up' );

			if ( count( $follow_up_emails ) > 0 ) {
				$minute_seconds = 60;
				$hour_seconds   = 3600; // 60 * 60
				$day_seconds    = 86400; // 24 * 60 * 60

				// Loop through the rules and find a match.
				foreach ( $follow_up_emails as $template_data ) {
					$template_id = (int) $template_data->id;
					switch ( $template_data->day_or_hour ) {
						case 'Minutes':
							$timestamp = $template_data->frequency * $minute_seconds;
							break;
						case 'Days':
							$timestamp = $template_data->frequency * $day_seconds;
							break;
						case 'Hours':
							$timestamp = $template_data->frequency * $hour_seconds;
							break;
					}
					$rules = isset( $template_data->rules ) ? json_decode( $template_data->rules ) : array();

					if ( count( $rules ) > 0 ) {
						foreach ( $rules as $rule_data ) {
							if ( 'order_status' === $rule_data->rule_type && in_array( "wc-$new_status", $rule_data->rule_value, true ) && 'includes' === $rule_data->rule_condition ) {
								// Check if email is already sent or scheduled to be sent.
								$order       = wc_get_order( $order_id );
								$sent_status = $order->get_meta( "_wcap_status_$new_status" . "_$template_id", true ); // phpcs:ignore
								if ( '' === $sent_status ) {
									as_schedule_single_action(
										time() + $timestamp,
										'wcap_send_follow_up_email',
										array(
											'order_id' => $order_id,
											'template' => $template_id,
											'status'   => $new_status,
										)
									);
									$order->update_meta_data( "_wcap_status_$new_status" . "_$template_id", 'scheduled' ); // phpcs:ignore
									$order->save();
									break;
								}
							}
						}
					}
				}
			}
		}

		/**
		 * Send follow-up reminder emails.
		 *
		 * @param int    $order_id - WC Order ID.
		 * @param int    $template_id - Email Template ID.
		 * @param string $order_status - WC Order Status.
		 * @since 8.21.0
		 */
		public static function wcap_send_follow_up_email( $order_id, $template_id, $order_status ) {
			// Fetch the email template.
			$template_data = wcap_get_template_data_by_id( $template_id );
			if ( '1' === $template_data->is_active && $order_id > 0 ) {
				$template_name = $template_data->template_name;
				// Fetch the WC Order.
				$order = wc_get_order( $order_id );
				if ( $order ) {
					global $wpdb;

					// Check if email has been sent for the order, if yes, return.
					$sent_list = wcap_check_sent_history( $template_id, 'follow-up' );
					if ( in_array( $order_id, $sent_list, true ) ) {
						return;
					}

					// Retrive the email address needed for the template.
					$user_email = $order->get_billing_email();
					// Get the order details as needed for further processing.
					$order_details = array(
						'order_id'     => $order_id,
						'user_id'      => $order->get_user_id(),
						'order_status' => $order->get_status(),
						'order'        => $order,
					);
					// Run a rule check to make sure everything matches.
					$rules                 = isset( $template_data->rules ) ? json_decode( $template_data->rules ) : array();
					$rules_match_condition = isset( $template_data->match_rules ) ? $template_data->match_rules : '';
					if ( '' !== $rules_match_condition && count( $rules ) > 0 ) {
						$rules_match = wcap_cart_rules_match( $rules, $rules_match_condition, array(), $order_details );
						// If rule check fails, return.
						if ( ! $rules_match ) {
							// Add an order note perhaps?
							$order->add_order_note(
								sprintf(
									// translators: Order Note - email template and user email address.
									__( 'Follow up template %1$s was not sent to %2$s due to rule match failure.', 'woocommerce-ac' ),
									esc_attr( $template_name ),
									$user_email
								)
							);
							return;
						}
					}

					$blogname = get_option( 'blogname' );

					// Fetch woocommerce template header & footer.
					ob_start();
					wc_get_template( 'emails/email-header.php', array( 'email_heading' => '{{wc_template_header}}' ) );
					$email_body_template_header = ob_get_clean();

					// Check if WPML is active.
					$icl_register_function_exists = function_exists( 'icl_register_string' ) ? true : false;
					ob_start();
					wc_get_template( 'emails/email-footer.php' );
					$email_body_template_footer = ob_get_clean();
					$email_body_template_footer = str_ireplace( '{site_title}', $blogname, $email_body_template_footer );

					// UTM settings.
					$utm = get_option( 'wcap_add_utm_to_links', '' );
					if ( '' !== $utm && strlen( $utm ) > 0 && '?' !== substr( $utm, 0, 1 ) ) {
						$utm = "?$utm";
					}

					$default_template     = $template_data->default_template;
					$is_wc_template       = $template_data->is_wc_template;
					$wc_template_header_t = '' !== $template_data->wc_email_header ? $template_data->wc_email_header : __( "We'd love to hear back from you", 'woocommerce-ac' );

					// Prepare the email headers.
					$wcap_from_name   = get_option( 'wcap_from_name' );
					$wcap_from_email  = get_option( 'wcap_from_email' );
					$wcap_reply_email = get_option( 'wcap_reply_email' );
					$headers          = 'From: ' . $wcap_from_name . ' <' . $wcap_from_email . '>' . "\r\n";
					$headers         .= 'Content-Type: text/html' . "\r\n";
					$headers         .= 'Reply-To:  ' . $wcap_reply_email . ' ' . "\r\n";

					// Fetch the email subject & email body.
					$email_subject = convert_smilies( $template_data->subject );
					$email_body    = convert_smilies( $template_data->body );
					$email_body   .= '{{email_open_tracker}}'; // Add the open tracker.

					// Translations?? Find the order language?
					// WPML has a field custom_language, probably saved as order meta. Confirm the same and figure it out.
					// When do we start using the WC custom tables. Some of them are already present in the DB.
					// Research and find the versions they were introduced in and discuss when to move the data fetch.
					// Order Currency.
					$order_currency = $order->get_currency();
					$order_items    = $order->get_items();
					$shipping_total = $order->get_shipping_total();
					$total_tax      = $order->get_total_tax();

					// Prepare the defaults.
					$merge_tag_values    = array();
					$discount_details    = array();
					$selected_language   = '';
					$siteurl             = get_option( 'siteurl' );
					$add_cart_merge_tags = false;
					$date_format         = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
					// Proceed only if any of the merge tags are present in the template.
					// Customer first name, last name and full name.
					if ( stripos( $email_body, '{{customer.firstname}}' ) !== false ||
						stripos( $email_body, '{{customer.lastname}}' ) !== false ||
						stripos( $email_body, '{{customer.fullname}}' ) !== false ||
						stripos( $email_subject, '{{customer.firstname}}' ) !== false ||
						stripos( $email_subject, '{{customer.lastname}}' ) !== false ||
						stripos( $email_subject, '{{customer.fullname}}' ) !== false ) {

						$customer_first_name = $order->get_billing_first_name();
						$customer_last_name  = $order->get_billing_last_name();

						$merge_tag_values['customer.firstname'] = $customer_first_name;
						$merge_tag_values['customer.lastname']  = $customer_last_name;
						$merge_tag_values['customer.fullname']  = "$customer_first_name $customer_last_name";
					}

					// Customer Email Address. Customer phone number.
					$merge_tag_values['customer.email'] = $user_email;
					if ( stripos( $email_body, '{{customer.phone}}' ) ) {
						$merge_tag_values['customer.phone'] = $order->get_billing_phone();
					}

					// Coupon code.
					$coupon_id   = isset( $template_data->coupon_code ) ? $template_data->coupon_code : '';
					$coupon_code = '';
					if ( '' !== $coupon_id ) {
						$coupon_to_apply = get_post( $coupon_id, ARRAY_A );
						$coupon_code     = $coupon_to_apply['post_title'];
					}
					if ( stripos( $email_body, '{{coupon.code}}' ) ) {
						$discount_details['discount_expiry']      = $template_data->discount_expiry;
						$discount_details['discount_type']        = $template_data->discount_type;
						$discount_details['discount_shipping']    = $template_data->discount_shipping;
						$discount_details['individual_use']       = $template_data->individual_use;
						$discount_details['discount_amount']      = $template_data->discount;
						$discount_details['generate_unique_code'] = $template_data->generate_unique_coupon_code;

						$coupon_code_to_apply            = wcap_get_coupon_email( $discount_details, $coupon_code, $default_template );
						$merge_tag_values['coupon.code'] = $coupon_code_to_apply;
					}

					// Fetch the corresponding AC cart if any.
					$cart_id = $order->get_meta( 'wcap_abandoned_cart_id', true );
					if ( '' == $cart_id || false == $cart_id ) { // phpcs:ignore
						$cart_id = 0;
					}
					if ( $cart_id > 0 ) {
						// Check if the cart is recovered.
						$cart_data = $wpdb->get_results( // phpcs:ignore
							$wpdb->prepare(
								'SELECT abandoned_cart_info, recovered_cart, language FROM ' . WCAP_ABANDONED_CART_HISTORY_TABLE . ' WHERE id = %d', // phpcs:ignore
								(int) $cart_id
							)
						);
						if ( isset( $cart_data ) && count( $cart_data ) > 0 ) {
							$recovered_order = isset( $cart_data[0]->recovered_cart ) ? $cart_data[0]->recovered_cart : 0;
							// Unrecovered cart.
							if ( 0 == $recovered_order ) { // phpcs:ignore
								$cart_info_db_field  = json_decode( stripslashes( $cart_data[0]->abandoned_cart_info ) );
								$cart_details        = $cart_info_db_field->cart;
								$selected_language   = $cart_data[0]->language;
								$add_cart_merge_tags = true;
							}
						}
					}

					$crypt_key = wcap_get_crypt_key( $user_email );
					// Insert a record in the sent history table.
					$wpdb->insert( // phpcs:ignore
						WCAP_EMAIL_SENT_HISTORY_TABLE,
						array(
							'notification_type'         => 'email',
							'template_id'               => $template_id,
							'cart_id'                   => $cart_id,
							'sent_time'                 => current_time( 'mysql' ),
							'sent_notification_contact' => addslashes( $user_email ),
							'wc_order_id'               => $order_id,
							'encrypt_key'               => $crypt_key,
						)
					);
					$email_sent_id = $wpdb->insert_id;

					// Create order items list.
					$items_id   = array();
					$items_list = array();
					$i          = 1;
					foreach ( $order_items as $item ) { // list of abandoned products.
						$first_product = ! isset( $first_product ) ? $item->get_name() : $first_product;
						array_push( $items_id, $item->get_product_id() );

						// Create the object to be sent for items table.
						$_product = $item->get_product();

						$items_list[ $i ]                    = new stdClass();
						$items_list[ $i ]->product_id        = $item->get_product_id();
						$items_list[ $i ]->variation_id      = $item->get_variation_id();
						$items_list[ $i ]->line_total        = $item->get_total();
						$items_list[ $i ]->line_subtotal     = $item->get_subtotal();
						$items_list[ $i ]->line_tax          = $item->get_subtotal_tax();
						$items_list[ $i ]->line_subtotal_tax = $item->get_subtotal_tax();
						$items_list[ $i ]->quantity          = $item->get_quantity();
						$i++;
					}
					$items_list = (object) $items_list;

					// Totals.
					$order_totals                   = array();
					$order_totals['total_tax']      = $total_tax;
					$order_totals['shipping_total'] = $shipping_total;
					// Prepare the data.
					$email_settings['image_height']  = get_option( 'wcap_product_image_height' );
					$email_settings['image_width']   = get_option( 'wcap_product_image_width' );
					$email_settings['currency']      = $order_currency;
					$email_settings['abandoned_id']  = $cart_id;
					$email_settings['blog_name']     = $blogname;
					$email_settings['site_url']      = $siteurl;
					$email_settings['email_sent_id'] = $email_sent_id;
					$email_settings['utm_params']    = $utm;
					$email_settings['coupon_used']   = count( $order->get_coupon_codes() ) > 0 ? rtrim( implode( ',', $order->get_coupon_codes() ), ',' ) : '';
					$email_settings['checkout_link'] = wc_get_page_id( 'checkout' ) ? get_permalink( wc_get_page_id( 'checkout' ) ) : '';
					// Unsubscribe Link.
					$validate_unsubscribe                 = Wcap_Common::encrypt_validate( $email_sent_id, $crypt_key );
					$encrypt_email_sent_id_address        = hash( 'sha256', $user_email );
					$merge_tag_values['cart.unsubscribe'] = $siteurl . '/?wcap_track_unsubscribe=wcap_unsubscribe&user_email=' . $user_email . '&validate=' . $validate_unsubscribe . '&track_email_id=' . $encrypt_email_sent_id_address;

					// Email Tracker.
					$plugins_url_track_image = $siteurl . '/?wcap_track_email_opens=wcap_email_open&email_id=';
					$hidden_image            = '<img style="border:0px; height: 1px; width:1px; position:absolute; visibility:hidden;" alt="" src="' . $plugins_url_track_image . $email_sent_id . '" >';
					$email_body              = str_ireplace( '{{email_open_tracker}}', $hidden_image, $email_body );

					if ( $add_cart_merge_tags ) {
						$checkout_page_link = wc_get_page_id( 'checkout' ) ? get_permalink( wc_get_page_id( 'checkout' ) ) : '';

						// Force SSL if needed.
						$ssl_is_used = is_ssl() ? true : false;

						if ( true === $ssl_is_used || 'yes' === get_option( 'woocommerce_force_ssl_checkout' ) ) {
							$checkout_page_https = true;
							$checkout_page_link  = str_ireplace( 'http:', 'https:', $checkout_page_link );
						}

						// Fetch cart page settings & create link.
						$cart_page_link = wc_get_page_id( 'cart' ) ? get_permalink( wc_get_page_id( 'cart' ) ) : '';

						if ( true === $icl_register_function_exists ) {
							$checkout_page_link = apply_filters( 'wpml_permalink', $checkout_page_link, $selected_language );
							$cart_page_link     = apply_filters( 'wpml_permalink', $cart_page_link, $selected_language );
						}

						// If ssl is enabled.
						if ( isset( $checkout_page_https ) && true === $checkout_page_https ) {
							$checkout_page_link = str_ireplace( 'http:', 'https:', $checkout_page_link );
						}

						// If ssl is enabled.
						if ( true === $ssl_is_used ) {
							$cart_page_link = str_ireplace( 'http:', 'https:', $cart_page_link );
						}

						$checkout_page_link = apply_filters( 'wcap_checkout_link_email_before_encoding', $checkout_page_link, $cart_id, $selected_language );
						$encoding_checkout  = $email_sent_id . '&url=' . $checkout_page_link . $utm;
						$validate_checkout  = Wcap_Common::encrypt_validate( $encoding_checkout, $crypt_key );

						$encoding_cart = $email_sent_id . '&url=' . $cart_page_link . $utm;
						$validate_cart = Wcap_Common::encrypt_validate( $encoding_cart, $crypt_key );
						if ( isset( $coupon_code_to_apply ) && '' !== $coupon_code_to_apply ) {
							$encypted_coupon_code                    = Wcap_Common::encrypt_validate( $coupon_code_to_apply, $crypt_key );
							$email_settings['encrypted_coupon_code'] = $encypted_coupon_code;

							// Cart Link.
							$cart_link_track = $siteurl . '/?wacp_action=track_links&user_email=' . $user_email . '&validate=' . $validate_cart . '&c=' . $encypted_coupon_code;
							// Checkout Link.
							$checkout_link_track = $siteurl . '/?wacp_action=track_links&user_email=' . $user_email . '&validate=' . $validate_checkout . '&c=' . $encypted_coupon_code;
						} else {
							// Cart Link.
							$cart_link_track = $siteurl . '/?wacp_action=track_links&user_email=' . $user_email . '&validate=' . $validate_cart;
							// Checkout Link.
							$checkout_link_track = $siteurl . '/?wacp_action=track_links&user_email=' . $user_email . '&validate=' . $validate_checkout;
						}

						$email_settings['checkout_link'] = $checkout_link_track;
						$email_settings['cart_lang']     = $selected_language;
						// Cart Link, Checkout Link.
						$merge_tag_values['cart.link']     = $cart_link_track;
						$merge_tag_values['checkout.link'] = $checkout_link_track;
					}

					$merge_tag_values['product.name'] = $first_product;
					// Prepare the email subject.
					$email_subject = wcap_replace_email_merge_tags_subject( $email_subject, $merge_tag_values );

					// Product Image, Name, Price, Qty, Subtotal, Cart, Cart Total.
					$email_body = wcap_replace_product_cart( $email_body, $items_list, $email_settings, $crypt_key, $user_email, $order_totals );

					// Date when cart was abandoned - replace with order placed data?
					$merge_tag_values['cart.abandoned_date'] = date( $date_format, strtotime( $order->get_date_created() ) ); // phpcs:ignore
					// Cross sells & Upsells.
					$email_body = wcap_replace_upsell_data( $email_body, $items_id, $email_settings, $crypt_key, $user_email );
					$email_body = wcap_replace_crosssell_data( $email_body, $items_id, $email_settings, $crypt_key, $user_email );

					// Prepare the email body.
					$email_body = wcap_replace_email_merge_tags_body( $email_body, $merge_tag_values );

					// Send the email.
					if ( isset( $is_wc_template ) && '1' === $is_wc_template ) {
						$email_body_template_header = str_ireplace( '{{wc_template_header}}', $wc_template_header, $email_body_template_header );
						$email_body                 = $email_body_template_header . $email_body . $email_body_template_footer;
					}
					Wcap_Common::wcap_add_wp_mail_header();
					wp_mail( $user_email, stripslashes( $email_subject ), stripslashes( $email_body ), $headers );
					Wcap_Common::wcap_remove_wc_mail_header();

					// Update the record in the respective files.
					$order->update_meta_data( "_wcap_status_$order_status" . "_$template_id", 'sent' ); // phpcs:ignore
					$order->save();
					$timestamp   = date( $date_format , current_time( 'timestamp' ) ); // phpcs:ignore
					// Add an order note.
					$order->add_order_note(
						sprintf(
							// translators: template name, user email address & timestamp.
							__( 'Follow up template %1$s sent to %2$s at %3$s', 'woocommerce-ac' ),
							$template_name,
							$user_email,
							$timestamp
						)
					);
				}
			}
		}

	}
}

return new Wcap_Follow_Up_Emails();
