<?php
/**
 * Abandoned Cart Pro for WooCommerce
 *
 * It will send custom email to selected abandoned cart's users. The admin of the store will be able to send the abandoned cart reminder to specific abandoned cart(s). Also, the admin can edit the existing email template for sending the email.
 *
 * @author   LittleCodr
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Classes
 * @category Classes
 * @since    5.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require_once WCAP_PLUGIN_PATH . '/cron/class-wcap-send-email-using-cron.php';
/**
 * This class is used to start Initiate Recovery.
 *
 * @since 4.2
 */
class Wcap_Send_Manual_Email {

	/**
	 * It will replace the merge tags and send the email to customer(s).
	 *
	 * @global mixed $wpdb
	 * @global mixed $woocommerce
	 */
	public static function wcap_create_and_send_manual_email() {

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( -1 );
		}

		check_ajax_referer( 'wcap_settings_nonce', 'security' );

		global $wpdb;
		global $woocommerce;

		$abandoned_cart_ids           = isset( $_POST ['abandoned_cart_id'] ) ? explode( ',', sanitize_text_field( wp_unslash( $_POST['abandoned_cart_id'] ) ) ) : 0; // phpcs:ignore
		$selected_template_id         = isset( $_POST ['wcap_manual_template_name'] ) ? sanitize_text_field( wp_unslash( $_POST ['wcap_manual_template_name'] ) ) : 0; // phpcs:ignore

		$results_of_get_template_data = $wpdb->get_results( // phpcs:ignore
			$wpdb->prepare(
				'SELECT default_template, discount FROM ' . WCAP_NOTIFICATION_TEMPLATES_TABLE . ' WHERE id = %s', // phpcs:ignore
				$selected_template_id
			)
		);
		$default_template             = $results_of_get_template_data[0]->default_template;
		$discount_amount              = $results_of_get_template_data[0]->discount;
		$wcap_current_time            = current_time( 'timestamp' ); // phpcs:ignore

		$email_body_template    = isset( $_POST['woocommerce_ac_email_body'] ) ? wp_unslash( $_POST['woocommerce_ac_email_body'] ) : ''; // phpcs:ignore
		$email_body_template    = stripslashes( html_entity_decode( $email_body_template, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) );
		$template_email_subject = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : ''; // phpcs:ignore
		$wcap_from_name         = get_option( 'wcap_from_name' );
		$wcap_from_email        = get_option( 'wcap_from_email' );
		$wcap_reply_email       = get_option( 'wcap_reply_email' );

		$headers  = 'From: ' . $wcap_from_name . ' <' . $wcap_from_email . '>' . "\r\n";
		$headers .= 'Content-Type: text/html' . "\r\n";
		$headers .= 'Reply-To:  ' . $wcap_reply_email . ' ' . "\r\n";

		$coupon_id = isset( $_POST['coupon_ids_new'][0] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_ids_new'][0] ) ) : ''; // phpcs:ignore

		if ( $woocommerce->version < '2.3' ) {
			$checkout_page_link = $woocommerce->cart->get_checkout_url();
		} else {
			$checkout_page_id   = wc_get_page_id( 'checkout' );
			$checkout_page_link = '';
			if ( $checkout_page_id ) {
				// Get the checkout URL.
				$checkout_page_link = get_permalink( $checkout_page_id );
			}
		}
		// Force SSL if needed.
		if ( is_ssl() || 'yes' === get_option( 'woocommerce_force_ssl_checkout' ) ) {
			$checkout_page_link = str_ireplace( 'http:', 'https:', $checkout_page_link );
		}

		if ( $woocommerce->version < '2.3' ) {
			$cart_page_link = $woocommerce->cart->get_cart_url();
		} else {
			$cart_page_id   = wc_get_page_id( 'cart' );
			$cart_page_link = $cart_page_id ? get_permalink( $cart_page_id ) : '';
		}

		$utm = get_option( 'wcap_add_utm_to_links', '' );
		// Setup product image size.
		$wcap_product_image_height = get_option( 'wcap_product_image_height' );
		$wcap_product_image_width  = get_option( 'wcap_product_image_width' );

		$blogname = get_option( 'blogname' );
		$site_url = get_option( 'siteurl' );

		foreach ( $abandoned_cart_ids as $abandoned_cart_ids_key => $abandoned_cart_ids_value ) {

			$coupon_code = '';
			if ( '' !== $coupon_id ) {
				$coupon_to_apply = get_post( $coupon_id, ARRAY_A );
				$coupon_code     = $coupon_to_apply['post_title'];
			}

			$generate_unique_code = isset( $_POST['generate_unique_coupon_code'] ) ? sanitize_text_field( wp_unslash( $_POST['generate_unique_coupon_code'] ) ) : ''; // phpcs:ignore
			$is_wc_template       = isset( $_POST['is_wc_template'] ) ? sanitize_text_field( wp_unslash( $_POST['is_wc_template'] ) ) : ''; // phpcs:ignore
			$wc_template_header_t = '' !== $_POST['wcap_wc_email_header'] ? sanitize_text_field( wp_unslash( $_POST['wcap_wc_email_header'] ) ) : __( 'Abandoned cart reminder', 'woocommerce-ac' ); // phpcs:ignore
			$coupon_code_to_apply = '';
			$email_subject        = '';

			$results_of_abandoned_cart = $wpdb->get_results( // phpcs:ignore
				$wpdb->prepare(
					'SELECT * FROM ' . WCAP_ABANDONED_CART_HISTORY_TABLE . ' WHERE id = %s', // phpcs:ignore
					$abandoned_cart_ids_value
				)
			);

			$value                      = new stdClass();
			$value->user_type           = isset( $results_of_abandoned_cart[0]->user_type ) ? $results_of_abandoned_cart[0]->user_type : '';
			$value->user_id             = isset( $results_of_abandoned_cart[0]->user_id ) ? $results_of_abandoned_cart[0]->user_id : '';
			$value->abandoned_cart_info = isset( $results_of_abandoned_cart[0]->abandoned_cart_info ) ? $results_of_abandoned_cart[0]->abandoned_cart_info : '';
			$value->abandoned_cart_time = isset( $results_of_abandoned_cart[0]->abandoned_cart_time ) ? $results_of_abandoned_cart[0]->abandoned_cart_time : '';
			$value->language            = isset( $results_of_abandoned_cart[0]->language ) ? $results_of_abandoned_cart[0]->language : '';
			$wcap_guest_session_id      = isset( $results_of_abandoned_cart[0]->session_id ) ? $results_of_abandoned_cart[0]->session_id : 0;

			$value->ac_id     = isset( $results_of_abandoned_cart[0]->id ) ? $results_of_abandoned_cart[0]->id : '';
			$wcap_used_coupon = '';

			$selected_lanaguage = '';
			if ( 'GUEST' === $value->user_type && $value->user_id > 0 ) {
				$value->user_login = '';

				$results_guest = $wpdb->get_var( // phpcs:ignore
					$wpdb->prepare(
						'SELECT email_id FROM `' . WCAP_GUEST_CART_HISTORY_TABLE . '` WHERE id = %d', // phpcs:ignore
						$value->user_id
					)
				);
				if ( '' !== $results_guest ) {
					$user_email = $results_guest;
				}

				$results_guest_session = $wpdb->get_results( // phpcs:ignore
					$wpdb->prepare(
						'SELECT session_value FROM `' . $wpdb->prefix . 'woocommerce_sessions` WHERE session_key = %s', // phpcs:ignore
						$wcap_guest_session_id
					)
				);

				if ( count( $results_guest_session ) > 0 ) {
					$wcap_result_session  = unserialize( $results_guest_session[0]->session_value ); // phpcs:ignore
					$wcap_coupon_sesson   = unserialize( $wcap_result_session['applied_coupons'] ); // phpcs:ignore
					$wcap_used_coupon     = '';
					$coupon_code_to_apply = '';
					if ( is_array( $wcap_coupon_sesson ) && count( $wcap_coupon_sesson ) > 0 && isset( $wcap_coupon_sesson[0] ) ) {
						$wcap_used_coupon     = $wcap_coupon_sesson[0];
						$coupon_code_to_apply = $wcap_used_coupon;
					}
				}
			} else {
				$user_id            = $value->user_id;
				$user_email_biiling = get_user_meta( $user_id, 'billing_email', true );

				if ( isset( $user_email_biiling ) && '' == $user_email_biiling ) { // phpcs:ignore
					$user_data = get_userdata( $user_id );

					if ( isset( $user_data->user_email ) && '' != $user_data->user_email ) { // phpcs:ignore
						$user_email = $user_data->user_email;
					}
				} elseif ( '' != $user_email_biiling ) { // phpcs:ignore
					$user_email = $user_email_biiling;
				}

				$coupons_list = Wcap_Common::wcap_get_coupon_post_meta( $abandoned_cart_ids_value );
				if ( count( $coupons_list ) > 0 ) {
					$wcap_used_coupon = '';
					foreach ( $coupons_list as $coupon ) {
						$wcap_used_coupon .= $coupon . '<br>';
					}
					$coupon_code_to_apply = $wcap_used_coupon;
				}
			}
			$cart               = new stdClass();
			$cart_info_db_field = json_decode( stripslashes( $value->abandoned_cart_info ) );
			if ( ! empty( $cart_info_db_field->cart ) ) {
				$cart = $cart_info_db_field->cart;
			}

			// Currency selected.
			$currency = isset( $cart_info_db_field->currency ) ? $cart_info_db_field->currency : '';

			$validate_email_format = wcap_validate_email_format( $user_email );

			if ( count( get_object_vars( $cart ) ) > 0 && $value->user_id > 0 && 1 === $validate_email_format ) {
				$cart_update_time   = $value->abandoned_cart_time;
				$selected_lanaguage = $value->language;
				$cart_info_db       = $value->abandoned_cart_info;

				// Translate email content based on cart language.
				$name_msg                = 'wcap_template_' . $selected_template_id . '_message';
				$email_body_template     = wcap_get_translated_texts( $name_msg, $email_body_template, $selected_lanaguage );
				$name_sub                = 'wcap_template_' . $selected_template_id . '_subject';
				$template_email_subject  = wcap_get_translated_texts( $name_sub, $template_email_subject, $selected_lanaguage );
				$wc_template_header_text = 'wcap_template_' . $selected_template_id . '_wc_email_header';
				$wc_template_header      = wcap_get_translated_texts( $wc_template_header_text, $wc_template_header_t, $selected_lanaguage );

				$email_body_template    = convert_smilies( $email_body_template );
				$template_email_subject = convert_smilies( $template_email_subject );
				$email_body             = $email_body_template;
				$email_body            .= '{{email_open_tracker}}';

				if ( function_exists( 'icl_register_string' ) ) {
					$checkout_page_link = apply_filters( 'wpml_permalink', $checkout_page_link, $selected_lanaguage );
					$cart_page_link     = apply_filters( 'wpml_permalink', $cart_page_link, $selected_lanaguage );
				}
				// Force SSL if needed.
				if ( is_ssl() || 'yes' === get_option( 'woocommerce_force_ssl_checkout' ) ) {
					$checkout_page_link = str_ireplace( 'http:', 'https:', $checkout_page_link );
				}
				if ( is_ssl() ) {
					$cart_page_link = str_ireplace( 'http:', 'https:', $cart_page_link );
				}

				$customer_details = wcap_get_customer_names( $value->user_type, $value->user_id );

				$merge_tag_values['customer.firstname'] = isset( $customer_details['first_name'] ) ? $customer_details['first_name'] : '';
				$merge_tag_values['customer.lastname']  = isset( $customer_details['last_name'] ) ? $customer_details['last_name'] : '';
				$merge_tag_values['customer.fullname']  = isset( $customer_details['full_name'] ) ? $customer_details['full_name'] : '';
				$merge_tag_values['customer.email']     = wcap_get_customers_email( $value->user_id, $value->user_type );
				$merge_tag_values['customer.phone']     = wcap_get_customers_phone( $value->user_id, $value->user_type );

				$order_date = '';
				if ( '' != $cart_update_time && 0 != $cart_update_time ) { // phpcs:ignore
					$date_format = date_i18n( get_option( 'date_format' ), $cart_update_time );
					$time_format = date_i18n( get_option( 'time_format' ), $cart_update_time );
					$order_date  = $date_format . ' ' . $time_format;
				}

				if ( preg_match( '{{coupon.code}}', $email_body, $matched ) ) {

					// Calculating the expiry time of the coupon.
					$discount_expiry = '';
					if ( isset( $_POST['wcac_coupon_expiry'] ) && '' !== $_POST['wcac_coupon_expiry'] && '0' !== $_POST['wcac_coupon_expiry'] ) { // phpcs:ignore

						$wcac_coupon_expiry = isset( $_POST['wcac_coupon_expiry'] ) ? sanitize_text_field( wp_unslash( $_POST['wcac_coupon_expiry'] ) ) : '7'; // phpcs:ignore
						$expiry_day_or_hour = isset( $_POST['expiry_day_or_hour'] ) ? sanitize_text_field( wp_unslash( $_POST['expiry_day_or_hour'] ) ) : 'days'; // phpcs:ignore
						$discount_expiry    = ' -' . $wcac_coupon_expiry . ' ' . $expiry_day_or_hour;
						$expiry_date_extend = strtotime( $discount_expiry );
					}

					$coupon_post_meta = '';

					$discount_details['discount_expiry']      = $discount_expiry;
					$discount_details['discount_type']        = isset( $_POST['discount_type'] ) ? sanitize_text_field( wp_unslash( $_POST['discount_type'] ) ) : 'percent'; // phpcs:ignore
					$discount_details['discount_shipping']    = isset( $_POST['discount_shipping'] ) ? 'yes' : 'no'; // phpcs:ignore
					$discount_details['individual_use']       = empty( $_POST['individual_use'] ) ? '0' : '1'; // phpcs:ignore
					$discount_details['discount_amount']      = isset( $_POST['discount'] ) ? sanitize_text_field( wp_unslash( $_POST['discount'] ) ) : '0'; // phpcs:ignore
					$discount_details['generate_unique_code'] = $generate_unique_code;

					$coupon_code_to_apply            = wcap_get_coupon_email( $discount_details, $coupon_code, $default_template );
					$merge_tag_values['coupon.code'] = $coupon_code_to_apply;
				}

				$merge_tag_values['cart.abandoned_date'] = $order_date;

				$crypt_key = wcap_get_crypt_key( $user_email );
				$wpdb->insert( // phpcs:ignore
					WCAP_EMAIL_SENT_HISTORY_TABLE,
					array(
						'template_id'               => $selected_template_id,
						'cart_id'                   => $value->ac_id,
						'sent_time'                 => current_time( 'mysql' ),
						'sent_notification_contact' => addslashes( $user_email ),
						'notification_type'         => 'email',
						'encrypt_key'               => $crypt_key,
					)
				);
				$email_sent_id = $wpdb->insert_id;

				// Generate the nonce for the recovery link.
				$wcap_recovery_nonce = hash_hmac( 'sha256', 'wcap_recover_' . $value->ac_id . '_' . $value->user_id, wp_salt( 'auth' ) );

				$encoding_checkout = $email_sent_id . '&url=' . $checkout_page_link . $utm;
				$validate_checkout = Wcap_Common::encrypt_validate( $encoding_checkout, $crypt_key );

				if ( isset( $coupon_code_to_apply ) && '' !== $coupon_code_to_apply ) {
					$encypted_coupon_code = Wcap_Common::encrypt_validate( $coupon_code_to_apply, $crypt_key );
					$checkout_link_track  = $site_url . '/?wacp_action=track_links&user_email=' . $user_email . '&validate=' . $validate_checkout . '&c=' . $encypted_coupon_code . '&wcap_nonce=' . $wcap_recovery_nonce;
				} else {
					$checkout_link_track = $site_url . '/?wacp_action=track_links&user_email=' . $user_email . '&validate=' . $validate_checkout . '&wcap_nonce=' . $wcap_recovery_nonce;
				}

				// Populate the product name if its present in the email subject line.
				$sub_line_prod_name = '';
				$cart_details       = $cart_info_db_field->cart;
				$cart_totals        = isset( $cart_info_db_field->cart_totals ) ? (array) $cart_info_db_field->cart_totals : array();
				foreach ( $cart_details as $k => $v ) {
					$sub_line_prod_name = get_the_title( $v->product_id );
					break;
				}
				$merge_tag_values['product.name'] = $sub_line_prod_name;

				$encoding_cart = $email_sent_id . '&url=' . $cart_page_link;
				$validate_cart = Wcap_Common::encrypt_validate( $encoding_cart, $crypt_key );

				if ( isset( $coupon_code_to_apply ) && '' !== $coupon_code_to_apply ) {
					$encypted_coupon_code = Wcap_Common::encrypt_validate( $coupon_code_to_apply, $crypt_key );
					$cart_link_track      = $site_url . '/?wacp_action=track_links&user_email=' . $user_email . '&validate=' . $validate_cart . '&c=' . $encypted_coupon_code . '&wcap_nonce=' . $wcap_recovery_nonce;
				} else {
					$cart_link_track = $site_url . '/?wacp_action=track_links&user_email=' . $user_email . '&validate=' . $validate_cart . '&wcap_nonce=' . $wcap_recovery_nonce;
				}

				// Up Sell and Cross Sell Data.
				$email_settings = array(
					'image_height'  => $wcap_product_image_height,
					'image_width'   => $wcap_product_image_width,
					'site_url'      => $site_url,
					'email_sent_id' => $email_sent_id,
					'checkout_link' => $checkout_link_track,
					'coupon_used'   => $wcap_used_coupon,
					'currency'      => $currency,
					'abandoned_id'  => $value->ac_id,
					'blog_name'     => $blogname,
					'utm_params'    => $utm,
					'cart_lang'     => $selected_lanaguage,
				);

				// Populate the products.cart shortcode if it exists.
				if ( stripos( $email_body, '{{item.image}}' ) ||
					stripos( $email_body, '{{item.name}}' ) ||
					stripos( $email_body, '{{item.price}}' ) ||
					stripos( $email_body, '{{item.quantity}}' ) ||
					stripos( $email_body, '{{item.subtotal}}' ) ||
					stripos( $email_body, '{{cart.total}}' ) ) {
						$email_body = wcap_replace_product_cart( $email_body, $cart_details, $email_settings, $crypt_key, $user_email, $cart_totals );
				}
				$email_body = wcap_replace_upsell_data( $email_body, $cart_details, $email_settings, $crypt_key, $user_email );
				$email_body = wcap_replace_crosssell_data( $email_body, $cart_details, $email_settings, $crypt_key, $user_email );

				$merge_tag_values['cart.link']     = $cart_link_track;
				$merge_tag_values['checkout.link'] = $checkout_link_track;

				$validate_unsubscribe  = Wcap_Common::encrypt_validate( $email_sent_id, $crypt_key );
				$email_sent_id_address = $user_email;

				$encrypt_email_sent_id_address        = hash( 'sha256', $email_sent_id_address );
				$merge_tag_values['cart.unsubscribe'] = $site_url . '/?wcap_track_unsubscribe=wcap_unsubscribe&user_email=' . $user_email . '&validate=' . $validate_unsubscribe . '&track_email_id=' . $encrypt_email_sent_id_address;

				$email_body    = wcap_replace_email_merge_tags_body( $email_body, $merge_tag_values );
				$email_subject = wcap_replace_email_merge_tags_subject( $template_email_subject, $merge_tag_values );

				$plugins_url_track_image = $site_url . '/?wcap_track_email_opens=wcap_email_open&email_id=';
				$hidden_image            = '<img style="border:0px; height: 1px; width:1px; position:absolute; visibility:hidden;" alt="" src="' . $plugins_url_track_image . $email_sent_id . '" >';
				$email_body              = str_ireplace( '{{email_open_tracker}}', $hidden_image, $email_body );
				$email_body              = str_ireplace( 'My document title', '', $email_body );
				$email_body              = Wcap_Common::wcap_inline_email_css( $email_body );

				if ( isset( $is_wc_template ) && 'on' === $is_wc_template ) {

					ob_start();

					wc_get_template( 'emails/email-header.php', array( 'email_heading' => $wc_template_header ) );
					$email_body_template_header = ob_get_clean();

					ob_start();

					wc_get_template( 'emails/email-footer.php' );
					$email_body_template_footer = ob_get_clean();
					$email_body_template_footer = str_ireplace( '{site_title}', $blogname, $email_body_template_footer );
					$final_email_body           = $email_body_template_header . $email_body . $email_body_template_footer;

					Wcap_Common::wcap_add_wc_mail_header();
					wc_mail( $user_email, stripslashes( $email_subject ), stripslashes( $final_email_body ), $headers );
					Wcap_Common::wcap_remove_wc_mail_header();
				} else {
					Wcap_Common::wcap_add_wp_mail_header();
					wp_mail( $user_email, stripslashes( $email_subject ), stripslashes( $email_body ), $headers );
					Wcap_Common::wcap_remove_wc_mail_header();
				}

				$wpdb->update( // phpcs:ignore
					WCAP_ABANDONED_CART_HISTORY_TABLE,
					array(
						'email_reminder_status' => 'manual',
					),
					array(
						'id' => $abandoned_cart_ids_value,
					)
				);
			}
		}

		wp_send_json(
			array(
				'success' => __( 'Email Sent Successfully.', 'woocommerce-ac' ),
			)
		);
	}

}
