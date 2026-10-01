<?php
/**
 * This class is responsible for sending the abandoned cart reminder emails to the customers.
 * Also it is generating the coupon code if needed in the template.
 * This if condition is used to identify that the wcap_send_mail.php file has been called directly from the cron job.
 * So it will prevent the below code when this file is called from the required_once.
 *
 * @author   LittleCodr
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Cron
 * @since    5.0
 */

/**
 * It will work when admin has added the cron job from the cPanel and disabled the auto cron setting in our plugin.
 * It will load the wp-load.php file. It will allow to access all WordPress functions.
 */
$file_base = basename( sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_FILENAME'] ) ) ); // phpcs:ignore
if ( basename( __FILE__ ) === $file_base ) {
	static $wp_load; // Since this will be called twice, hold onto it.
	$wp_load = ABSPATH . '/wp-load.php';
	if ( ! file_exists( $wp_load ) ) {
		$wp_load = false;
		$dir     = __FILE__;
		while ( '/' != ( $dir = dirname( $dir ) ) ) { // phpcs:ignore
			$wp_load = "{$dir}/wp-load.php";
			if ( file_exists( $wp_load ) ) {
				break;
			}
		}
	}
	$wcap_root = dirname( dirname( __FILE__ ) ); // Go two level up for directory from this file.
	require_once $wp_load;
	$wcap_auto_cron = get_option( 'wcap_use_auto_cron ' );
	if ( isset( $wcap_auto_cron ) && ( false === $wcap_auto_cron || '' === $wcap_auto_cron ) ) {
		require_once $wcap_root . '/includes/classes/class_wcap_aes.php';
		require_once $wcap_root . '/includes/classes/class_wcap_aes_ctr.php';
		Wcap_Send_Email_Using_Cron::wcap_abandoned_cart_send_email_notification();
		Wcap_Send_Email_Using_Cron::wcap_send_sms_notifications();
		Wcap_Send_Email_Using_Cron::wcap_send_whatsapp_reminders();
		include_once WCAP_PLUGIN_PATH . '/includes/fb-recovery/fb-recovery.php';
		WCAP_FB_Recovery::wcap_fb_cron();
	}
}

// Files for Twilio SMS.
if ( ! class_exists( 'SplClassLoader' ) ) {
	require_once WCAP_PLUGIN_PATH . '/includes/vendor/libraries/twilio-php/Twilio/autoload.php'; // Loads the library.
}
use Twilio\Rest\Client;

/**
 * It will send the abandoed cart reminder email the customers.
 * It will also update the abandoned cart status if the customer had placed the order before any reminder email is sent.
 */
class Wcap_Send_Email_Using_Cron {
	/**
	 * It will send the abandoed cart reminder email the customers.
	 * It will also update the abandoned cart status if the customer had placed the order before any reminder email is sent.
	 *
	 * @hook woocommerce_ac_send_email_action
	 * @globals mixed $wpdb
	 * @globals mixed $woocommerce
	 * @since 5.0
	 */
	public static function wcap_abandoned_cart_send_email_notification() {
		global $wpdb, $woocommerce;
		global $sitepress;
		global $polylang;

		$enable_email = get_option( 'ac_enable_cart_emails', '' );

		if ( 'on' === $enable_email ) {

			// Grab the cart abandoned cut-off time from database.
			$cut_off_time              = is_numeric( get_option( 'ac_cart_abandoned_time', 10 ) ) ? get_option( 'ac_cart_abandoned_time', 10 ) : 10;
			$cart_abandon_cut_off_time = $cut_off_time * 60;
			$ac_cutoff_time_guest      = is_numeric( get_option( 'ac_cart_abandoned_time_guest', 10 ) ) ? get_option( 'ac_cart_abandoned_time_guest', 10 ) : 10;
			$cut_off_time_guest        = $ac_cutoff_time_guest * 60;

			// Fetch all active templates present in the system.
			$results_template      = wcap_get_active_email_templates( 'abandoned_cart_email' );
			$minute_seconds        = 60;
			$hour_seconds          = 3600; // 60 * 60
			$day_seconds           = 86400; // 24 * 60 * 60
			$admin_abandoned_email = '';
			$wcap_from_name        = get_option( 'wcap_from_name' );
			$wcap_from_email       = get_option( 'wcap_from_email' );
			$wcap_reply_email      = get_option( 'wcap_reply_email' );

			$headers  = 'From: ' . $wcap_from_name . ' <' . $wcap_from_email . '>' . "\r\n";
			$headers .= 'Content-Type: text/html' . "\r\n";
			$headers .= 'Reply-To:  ' . $wcap_reply_email . ' ' . "\r\n";

			$go_date_format = get_option( 'date_format' );
			$go_time_format = get_option( 'time_format' );
			$go_blogname    = get_option( 'blogname' );
			$go_siteurl     = get_option( 'siteurl' );

			$go_product_image_height = get_option( 'wcap_product_image_height' );
			$go_product_image_width  = get_option( 'wcap_product_image_width' );

			$wcap_admin_email = get_option( 'admin_email' );

			// Check if WPML is active.
			$icl_register_function_exists = false;
			if ( function_exists( 'icl_register_string' ) ) {
				$icl_register_function_exists = true;
			}

			// Fetch checkout page settings & create link.
			if ( version_compare( WOOCOMMERCE_VERSION, '2.3' ) < 0 ) {
				$checkout_page_link = $woocommerce->cart->get_checkout_url();
			} else {
				$checkout_page_id   = wc_get_page_id( 'checkout' );
				$checkout_page_link = $checkout_page_id ? get_permalink( $checkout_page_id ) : '';
			}
			// Force SSL if needed.
			$ssl_is_used = false;
			if ( is_ssl() ) {
				$ssl_is_used = true;
			}
			if ( true === $ssl_is_used || 'yes' === get_option( 'woocommerce_force_ssl_checkout' ) ) {
				$checkout_page_https = true;
				$checkout_page_link  = str_ireplace( 'http:', 'https:', $checkout_page_link );
			}

			// Fetch cart page settings & create link.
			if ( version_compare( WOOCOMMERCE_VERSION, '2.3' ) < 0 ) {
				$cart_page_link = $woocommerce->cart->get_cart_url();
			} else {
				$cart_page_id   = wc_get_page_id( 'cart' );
				$cart_page_link = $cart_page_id ? get_permalink( $cart_page_id ) : '';
			}

			// Fetch woocommerce template header & footer.
			ob_start();
			wc_get_template( 'emails/email-header.php', array( 'email_heading' => '{{wc_template_header}}' ) );
			$email_body_template_header = ob_get_clean();

			if ( ! $icl_register_function_exists ) {
				ob_start();
				wc_get_template( 'emails/email-footer.php' );
				$email_body_template_footer = ob_get_clean();
				$email_body_template_footer = str_ireplace( '{site_title}', get_option( 'blogname' ), $email_body_template_footer );
			}

			// Check if it's a multisite.
			if ( is_multisite() ) {
				$main_prefix = $wpdb->get_blog_prefix( 1 );
			} else {
				$main_prefix = $wpdb->prefix;
			}
			$utm = get_option( 'wcap_add_utm_to_links', '' );
			if ( '' !== $utm && strlen( $utm ) > 0 && '?' !== substr( $utm, 0, 1 ) ) {
				$utm = "?$utm";
			}
			$wcap_current_time = current_time( 'timestamp' ); // phpcs:ignore

			// Find the template which is last in the sequence.
			$last_email_template = wcap_get_last_template_in_reminder_cycle( 'email', 'abandoned_cart_email' );
			if ( is_array( $last_email_template ) && count( $last_email_template ) > 0 ) {
				reset( $last_email_template );
				$last_template_id = key( $last_email_template );
			} else {
				$last_template_id = 0;
			}

			foreach ( $results_template as $results_template_key => $results_template_value ) {
				switch ( $results_template_value->day_or_hour ) {
					case 'Minutes':
						$time_to_send_template_after = $results_template_value->frequency * $minute_seconds;
						break;
					case 'Days':
						$time_to_send_template_after = $results_template_value->frequency * $day_seconds;
						break;
					case 'Hours':
						$time_to_send_template_after = $results_template_value->frequency * $hour_seconds;
						break;
				}

				$template_id = $results_template_value->id;

				$cart_time       = $wcap_current_time - $time_to_send_template_after - $cart_abandon_cut_off_time;
				$cart_time_guest = $wcap_current_time - $time_to_send_template_after - $cut_off_time_guest;

				$template_time = isset( $results_template_value->activated_time ) ? $results_template_value->activated_time : current_time( 'timestamp' ); // phpcs:ignore

				$carts = self::wcap_get_carts( $cart_time, $cart_time_guest, $template_id, $main_prefix, $template_time, 'email' );

				$email_frequency        = $results_template_value->frequency;
				$email_body_template    = convert_smilies( $results_template_value->body );
				$template_email_subject = convert_smilies( $results_template_value->subject );

				$coupon_id   = isset( $results_template_value->coupon_code ) ? $results_template_value->coupon_code : '';
				$coupon_code = '';
				if ( '' !== $coupon_id ) {
					$coupon_to_apply = get_post( $coupon_id, ARRAY_A );
					$coupon_code     = $coupon_to_apply['post_title'];
				}

				$default_template     = $results_template_value->default_template;
				$is_wc_template       = $results_template_value->is_wc_template;
				$wc_template_header_t = '' !== $results_template_value->wc_email_header ? $results_template_value->wc_email_header : __( 'Abandoned cart reminder', 'woocommerce-ac' );
				$coupon_code_to_apply = '';
				$email_subject        = '';

				$rules                 = isset( $results_template_value->rules ) ? json_decode( $results_template_value->rules ) : array();
				$rules_match_condition = isset( $results_template_value->match_rules ) ? $results_template_value->match_rules : '';

				$sent_carts_list = wcap_check_sent_history( $template_id );

				foreach ( $carts as $key => $value ) {
					$merge_tag_values    = array();
					$email_subject       = '';
					$webhook_links       = array();
					$cart_id             = $value->id;
					$abandoned_user_id   = isset( $value->user_id ) ? absint( $value->user_id ) : 0;
					$abandoned_user_type = isset( $value->user_type ) ? $value->user_type : '';
					$reminder_status     = $value->email_reminder_status;
					if ( false === stripos( $reminder_status, $template_id ) ) { // Rule check has not failed before.

						// Check if the user id for guests is >= 63000000, If not, no emails will be sent. @since 7.0.
						$wcap_is_guest_id_valid = self::wcap_get_is_guest_valid( $abandoned_user_id, $abandoned_user_type );

						if ( true === $wcap_is_guest_id_valid ) {

							$guest_email       = '';
							$selected_language = '';
							if ( 'GUEST' === $abandoned_user_type && 0 !== $abandoned_user_id ) {
								$results_guest = $wpdb->get_var( // phpcs:ignore
									$wpdb->prepare(
										'SELECT email_id FROM `' . WCAP_GUEST_CART_HISTORY_TABLE . '` WHERE id = %d', // phpcs:ignore
										$abandoned_user_id
									)
								);
								$guest_email   = isset( $results_guest ) ? $results_guest : '';
							}

							// Retrive the email address needed for the template.
							$abandoned_user_email = self::wcap_get_email_for_template( $template_id, $abandoned_user_type, $abandoned_user_id, $wcap_admin_email, $guest_email, $rules );

							$cart               = new stdClass();
							$cart_info_db_field = json_decode( stripslashes( $value->abandoned_cart_info ) );
							if ( ! empty( $cart_info_db_field->cart ) ) {
								$cart = $cart_info_db_field->cart;
							}

							// Currency selected.
							$currency = isset( $cart_info_db_field->currency ) ? $cart_info_db_field->currency : '';

							if ( isset( $cart_id ) && isset( $abandoned_user_email ) && '' !== $abandoned_user_email && 0 !== $abandoned_user_id ) {
								$cart_update_time = $value->abandoned_cart_time;
								$new_user         = in_array( $cart_id, $sent_carts_list, true ) ? false : true;

								$selected_language = $value->language;

								if ( function_exists( 'icl_object_id' ) ) {
									if ( isset( $polylang ) ) {
										$current_lang = pll_current_language();
									} else {
										$current_lang = $sitepress->get_current_language();
										if ( $current_lang !== $selected_language ) {
											$sitepress->switch_lang( $selected_language, true );
										}
									}
								}

								// Check if any further orders have come from the user. If yes and the order status is Pending or Failed, email will be sent.
								$wcap_check_cart_staus_need_to_update = self::wcap_get_cart_status( $time_to_send_template_after, $cart_update_time, $abandoned_user_id, $abandoned_user_type, $cart_id, $abandoned_user_email );

								if ( true === $new_user && count( get_object_vars( $cart ) ) > 0 ) {

									$rules_match = is_array( $rules ) && count( $rules ) > 0 ? false : true;
									if ( '' !== $rules_match_condition && is_array( $rules ) && count( $rules ) > 0 ) {
										$cart_details = array(
											'cart_id'     => $cart_id,
											'user_id'     => $abandoned_user_id,
											'cart_status' => $value->cart_ignored,
											'cart'        => $cart,
										);

										$rules_match = wcap_cart_rules_match( $rules, $rules_match_condition, $cart_details );
										if ( false === $rules_match ) {
											// Insert the template ID in the cart history table to ensure its not re-processed.
											$reminder_status .= '' === $reminder_status ? $template_id : ",$template_id";
											WCAP_CART_HISTORY_MODEL::update( array( 'email_reminder_status' => $reminder_status ), array( 'id' => $cart_id ) );
										}
									}

									// Only 1 template will be sent in each cycle. So if the previous template was held up due to some reason, send that first.
									$wcap_check_cart_needed_for_multiple_template = self::wcap_remove_cart_for_mutiple_templates( $cart_id, $time_to_send_template_after, $template_id );
									if ( false === $wcap_check_cart_needed_for_multiple_template && (int) $template_id === (int) $last_template_id ) {
										WCAP_CART_HISTORY_MODEL::update( array( 'email_reminder_status' => 'complete' ), array( 'id' => $cart_id ) );
									}
									if ( false === $wcap_check_cart_needed_for_multiple_template &&
										false === $wcap_check_cart_staus_need_to_update &&
										true === $rules_match ) {

										$wcap_used_coupon      = '';
										$wcap_check_cart_total = self::wcap_check_cart_total( $cart, $cart_id );

										if ( true === $wcap_check_cart_total ) {

											$wcap_explode_emails = explode( ',', $abandoned_user_email );

											$merge_tag_values['cart.id'] = $cart_id;
											if ( stripos( $email_body_template, '{{coupon.code}}' ) ) {
												$discount_details['discount_expiry']      = $results_template_value->discount_expiry;
												$discount_details['discount_type']        = $results_template_value->discount_type;
												$discount_details['discount_shipping']    = $results_template_value->discount_shipping;
												$discount_details['individual_use']       = $results_template_value->individual_use;
												$discount_details['discount_amount']      = $results_template_value->discount;
												$discount_details['generate_unique_code'] = $results_template_value->generate_unique_coupon_code;

												$coupon_code_to_apply            = wcap_get_coupon_email( $discount_details, $coupon_code, $default_template );
												$merge_tag_values['coupon.code'] = $coupon_code_to_apply;
											}

											// Translate email content based on cart language.
											$selected_language       = $value->language;
											$name_msg                = 'wcap_template_' . $template_id . '_message';
											$email_body_template     = wcap_get_translated_texts( $name_msg, $results_template_value->body, $selected_language );
											$name_sub                = 'wcap_template_' . $template_id . '_subject';
											$template_email_subject  = wcap_get_translated_texts( $name_sub, $results_template_value->subject, $selected_language );
											$wc_template_header_text = 'wcap_template_' . $template_id . '_wc_email_header';
											$wc_template_header      = wcap_get_translated_texts( $wc_template_header_text, $wc_template_header_t, $selected_language );

											if ( $icl_register_function_exists ) {
												ob_start();
												wc_get_template(
													'emails/email-footer.php',
													array(
														'cart_id'   => $cart_id,
														'cart_lang' => $selected_language,
													)
												);
												$email_body_template_footer = ob_get_clean();
												$email_body_template_footer = str_ireplace( '{site_title}', get_option( 'blogname' ), $email_body_template_footer );
											}
											$email_body = convert_smilies( $email_body_template );
											// Add the open tracker.
											$email_body .= '{{email_open_tracker}}';

											$cart_info_db = $value->abandoned_cart_info;
											if ( 'GUEST' === $abandoned_user_type ) {
												$wcap_guest_session_id = isset( $value->session_id ) ? $value->session_id : 0;

												$results_guest_session = $wpdb->get_results( // phpcs:ignore
													$wpdb->prepare(
														'SELECT session_value FROM `' . $wpdb->prefix . 'woocommerce_sessions` WHERE session_key = %s', // phpcs:ignore
														$wcap_guest_session_id
													)
												);

												if ( count( $results_guest_session ) > 0 ) {
													$wcap_result_session = unserialize( $results_guest_session[0]->session_value ); // phpcs:ignore
													if ( isset( $wcap_result_session['applied_coupons'] ) ) {
														$wcap_coupon_sesson  = unserialize( $wcap_result_session['applied_coupons'] ); // phpcs:ignore
														if ( is_array( $wcap_coupon_sesson ) && isset( $wcap_coupon_sesson[0] ) && count( $wcap_coupon_sesson ) > 0 ) {
															$wcap_used_coupon = $wcap_coupon_sesson[0];
														}
													}
												}
											} else {
												$coupons_list = Wcap_Common::wcap_get_coupon_post_meta( $cart_id );
												if ( count( $coupons_list ) > 0 ) {
													$wcap_used_coupon = '';
													foreach ( $coupons_list as $coupon ) {
														$wcap_used_coupon .= $coupon . '<br>';
													}
												}
											}
											// Proceed only if any of the merge tags are present in the template.
											if ( stripos( $email_body, '{{customer.firstname}}' ) !== false ||
												stripos( $email_body, '{{customer.lastname}}' ) !== false ||
												stripos( $email_body, '{{customer.fullname}}' ) !== false ||
												stripos( $template_email_subject, '{{customer.firstname}}' ) !== false ||
												stripos( $template_email_subject, '{{customer.lastname}}' ) !== false ||
												stripos( $template_email_subject, '{{customer.fullname}}' ) !== false ) {

												$customer_details = wcap_get_customer_names( $abandoned_user_type, $abandoned_user_id );

												$merge_tag_values['customer.firstname'] = isset( $customer_details['first_name'] ) ? $customer_details['first_name'] : '';
												$merge_tag_values['customer.lastname']  = isset( $customer_details['last_name'] ) ? $customer_details['last_name'] : '';
												$merge_tag_values['customer.fullname']  = isset( $customer_details['full_name'] ) ? $customer_details['full_name'] : '';
											}

											if ( isset( $email_subject ) && '' === $email_subject ) {
												$email_subject = $template_email_subject;
											}

											$merge_tag_values['customer.email'] = wcap_get_customers_email( $abandoned_user_id, $abandoned_user_type );
											if ( stripos( $email_body, '{{customer.phone}}' ) ) {
												$merge_tag_values['customer.phone'] = wcap_get_customers_phone( $abandoned_user_id, $abandoned_user_type );
											}

											$order_date = '';
											if ( '' != $cart_update_time && 0 != $cart_update_time ) { // phpcs:ignore
												$date_format = date_i18n( $go_date_format, $cart_update_time );
												$time_format = date_i18n( $go_time_format, $cart_update_time );
												$order_date  = $date_format . ' ' . $time_format;
											}
											$merge_tag_values['cart.abandoned_date'] = $order_date;

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

											foreach ( $wcap_explode_emails as $emails ) {
												$user_email                               = $emails;
												$crypt_key                                = wcap_get_crypt_key( $user_email );
												$unsubscribed_email_address_for_all_carts = get_option( 'wcap_unsubscribed_emails_for_all_carts' ) ? get_option( 'wcap_unsubscribed_emails_for_all_carts' ) : array();
												if ( in_array( $user_email, $unsubscribed_email_address_for_all_carts, true ) ) {
													continue;
												}

												$wpdb->insert( // phpcs:ignore
													WCAP_EMAIL_SENT_HISTORY_TABLE,
													array(
														'template_id' => $template_id,
														'cart_id' => $cart_id,
														'sent_time' => current_time( 'mysql' ),
														'sent_notification_contact' => addslashes( $user_email ),
														'notification_type' => 'email',
														'encrypt_key' => $crypt_key,
													)
												);
												$email_sent_id      = $wpdb->insert_id;
												$checkout_page_link = apply_filters( 'wcap_checkout_link_email_before_encoding', $checkout_page_link, $cart_id, $selected_language );
												$encoding_checkout  = $email_sent_id . '&url=' . $checkout_page_link . $utm;
												$validate_checkout  = Wcap_Common::encrypt_validate( $encoding_checkout, $crypt_key );

												// Populate the product name if it's present in the email subject line.
												$sub_line_prod_name = '';
												$cart_details       = $cart_info_db_field->cart;
												foreach ( $cart_details as $k => $v ) {
													$sub_line_prod_name = html_entity_decode( get_the_title( $v->product_id ), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 );
													break;
												}
												$merge_tag_values['product.name'] = $sub_line_prod_name;

												$encoding_cart = $email_sent_id . '&url=' . $cart_page_link . $utm;
												$validate_cart = Wcap_Common::encrypt_validate( $encoding_cart, $crypt_key );

												if ( isset( $coupon_code_to_apply ) && '' !== $coupon_code_to_apply ) {
													$encypted_coupon_code                    = Wcap_Common::encrypt_validate( $coupon_code_to_apply, $crypt_key );
													$email_settings['encrypted_coupon_code'] = $encypted_coupon_code;

													// Fix 2: Generate nonce tied to cart + user for this recovery link.
													$wcap_recovery_nonce = hash_hmac( 'sha256', 'wcap_recover_' . $cart_id . '_' . $abandoned_user_id, wp_salt( 'auth' ) );
													// Cart Link.
													$cart_link_track = $go_siteurl . '/?wacp_action=track_links&user_email=' . $user_email . '&validate=' . $validate_cart . '&c=' . $encypted_coupon_code . '&wcap_nonce=' . $wcap_recovery_nonce;
													// Checkout Link.
													$checkout_link_track = $go_siteurl . '/?wacp_action=track_links&user_email=' . $user_email . '&validate=' . $validate_checkout . '&c=' . $encypted_coupon_code . '&wcap_nonce=' . $wcap_recovery_nonce;
												} else {
													// Fix 2: Generate nonce tied to cart + user for this recovery link.
													$wcap_recovery_nonce = hash_hmac( 'sha256', 'wcap_recover_' . $cart_id . '_' . $abandoned_user_id, wp_salt( 'auth' ) );
													// Cart Link.
													$cart_link_track = $go_siteurl . '/?wacp_action=track_links&user_email=' . $user_email . '&validate=' . $validate_cart . '&wcap_nonce=' . $wcap_recovery_nonce;
													// Checkout Link.
													$checkout_link_track = $go_siteurl . '/?wacp_action=track_links&user_email=' . $user_email . '&validate=' . $validate_checkout . '&wcap_nonce=' . $wcap_recovery_nonce;
												}
												$merge_tag_values['cart.link']     = $cart_link_track;
												$merge_tag_values['checkout.link'] = $checkout_link_track;

												$webhook_links['cart_link']     = $cart_link_track;
												$webhook_links['checkout_link'] = $checkout_link_track;

												$wcap_product_image_height = $go_product_image_height;
												$wcap_product_image_width  = $go_product_image_width;

												$email_settings['image_height']  = $wcap_product_image_height;
												$email_settings['image_width']   = $wcap_product_image_width;
												$email_settings['checkout_link'] = $checkout_link_track;
												$email_settings['coupon_used']   = $wcap_used_coupon;
												$email_settings['currency']      = $currency;
												$email_settings['abandoned_id']  = $cart_id;
												$email_settings['blog_name']     = $go_blogname;
												$email_settings['site_url']      = $go_siteurl;
												$email_settings['email_sent_id'] = $email_sent_id;
												$email_settings['utm_params']    = $utm;
												$email_settings['cart_lang']     = $selected_language;

												$abandoned_products = array();
												foreach ( $cart_details as $k => $v ) { // list of abandoned products.
													array_push( $abandoned_products, $v->product_id );
												}
												$email_body = wcap_replace_upsell_data( $email_body, $abandoned_products, $email_settings, $crypt_key, $user_email );
												$email_body = wcap_replace_crosssell_data( $email_body, $abandoned_products, $email_settings, $crypt_key, $user_email );

												// Populate the products.cart shortcode if it exists.
												if ( stripos( $email_body, '{{item.image}}' ) ||
													stripos( $email_body, '{{item.name}}' ) ||
													stripos( $email_body, '{{item.price}}' ) ||
													stripos( $email_body, '{{item.quantity}}' ) ||
													stripos( $email_body, '{{item.subtotal}}' ) ||
													stripos( $email_body, '{{cart.total}}' ) ) {

													$cart_details = $cart_info_db_field->cart;
													$cart_totals  = isset( $cart_info_db_field->cart_totals ) ? (array) $cart_info_db_field->cart_totals : array();
													$email_body   = wcap_replace_product_cart( $email_body, $cart_details, $email_settings, $crypt_key, $user_email, $cart_totals );
												}

												$validate_unsubscribe                 = Wcap_Common::encrypt_validate( $email_sent_id, $crypt_key );
												$encrypt_email_sent_id_address        = hash( 'sha256', $user_email );
												$merge_tag_values['cart.unsubscribe'] = $go_siteurl . '/?wcap_track_unsubscribe=wcap_unsubscribe&user_email=' . $user_email . '&validate=' . $validate_unsubscribe . '&track_email_id=' . $encrypt_email_sent_id_address;

												$plugins_url_track_image = $go_siteurl . '/?wcap_track_email_opens=wcap_email_open&email_id=';
												$hidden_image            = '<img style="border:0px; height: 1px; width:1px; position:absolute; visibility:hidden;" alt="" src="' . $plugins_url_track_image . $email_sent_id . '" >';
												$email_body              = str_ireplace( '{{email_open_tracker}}', $hidden_image, $email_body );
												$email_body              = wcap_replace_email_merge_tags_body( $email_body, $merge_tag_values );
												$email_body              = Wcap_Common::wcap_inline_email_css( $email_body );

												$email_subject = wcap_replace_email_merge_tags_subject( $email_subject, $merge_tag_values );

												$webhook_links['open_link'] = $plugins_url_track_image . $email_sent_id;

												if ( isset( $is_wc_template ) && '1' === $is_wc_template ) {
													$email_body_template_header = str_ireplace( '{{wc_template_header}}', $wc_template_header, $email_body_template_header );
													$final_email_body           = $email_body_template_header . $email_body . $email_body_template_footer;
													Wcap_Common::wcap_add_wc_mail_header();
													wc_mail( $user_email, stripslashes( $email_subject ), stripslashes( $final_email_body ), $headers );
													Wcap_Common::wcap_remove_wc_mail_header();
												} else {
													Wcap_Common::wcap_add_wp_mail_header();
													wp_mail( $user_email, stripslashes( $email_subject ), stripslashes( $email_body ), $headers );
													Wcap_Common::wcap_remove_wc_mail_header();
												}
												do_action( 'wcap_reminder_email_sent', $cart_id, $email_sent_id, $webhook_links );
											}
										}
									}
								}

								if ( function_exists( 'icl_object_id' ) ) {
									if ( $current_lang !== $selected_language ) {
										if ( isset( $sitepress ) ) {
											$sitepress->switch_lang( $current_lang, true );
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}


	/**
	 * Checks if the order was recovered but not updated in the cart history table.
	 *
	 * @param string  $time_to_send_template_after - Frequency at which the reminder email should be sent.
	 * @param string  $cart_abandoned_time - Time at which the cart was abandoned.
	 * @param integer $abandoned_user_id - User ID by which the cart was abandoned.
	 * @param string  $abandoned_user_type - User Type (Guest, Registered).
	 * @param integer $abandoned_id - Abndoned Cart ID.
	 * @param string  $abandoned_user_email - User email for which the cart was abandoned.
	 * @return boolean $wcap_check_cart_status_need_to_update - False (reminder should be sent).
	 *
	 * @since 7.10.0
	 */
	public static function wcap_get_cart_status( $time_to_send_template_after, $cart_abandoned_time, $abandoned_user_id, $abandoned_user_type, $abandoned_id, $abandoned_user_email ) {

		global $wpdb;

		$order_id                              = 0;
		$wcap_check_cart_status_need_to_update = false;

		if ( is_hpos_enabled() ) { // HPOS usage is enabled.
			$get_order = wc_get_orders(
				array(
					'limit'      => 1,
					'meta_query' => array(//phpcs:ignore
						array(
							'key'     => 'wcap_abandoned_cart_id',
							'value'   => $abandoned_id,
							'compare' => 'EQUAL',
						),
					),
				)
			);
			if ( ! empty( $get_order ) ) {
				$order = $get_order[0];
			}
		} else { // Traditional CPT-based orders are in use.
			$results_wcap_check_if_cart_is_present_in_post_meta = $wpdb->get_results( // phpcS:ignore
				$wpdb->prepare(
					"SELECT post_id FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key = 'wcap_abandoned_cart_id' AND meta_value = %d LIMIT 1", // phpcs:ignore
					$abandoned_id
				)
			);

			if ( is_array( $results_wcap_check_if_cart_is_present_in_post_meta ) && count( $results_wcap_check_if_cart_is_present_in_post_meta ) > 0 ) {

				if ( isset( $results_wcap_check_if_cart_is_present_in_post_meta[0]->post_id ) && $results_wcap_check_if_cart_is_present_in_post_meta[0]->post_id > 0 ) {
					$order_id = $results_wcap_check_if_cart_is_present_in_post_meta[0]->post_id;

					$order = wc_get_order( $order_id );

				}
			}
		}

		if ( ! isset( $order ) ) { // check for an order for the same date & email address.
			$args      = array(
				'customer' => $abandoned_user_email,
				'limit'    => 1,
			);
			$order_obj = wc_get_orders( $args );
			if ( ! empty( $order_obj ) ) {
				$order = $order_obj[0];
			}
		}

		if ( isset( $order ) && is_object( $order ) ) {

			$order_data = $order->get_data();

			$order_status = $order_data['status'];
			$order_id     = $order_data['id'];

			if ( 'cancelled' !== $order_status && 'failed' !== $order_status && 'pending' !== $order_status && 'checkout-draft' !== $order_status ) { // checkout-draft - WC Blocks.

				$order_date      = $order_data['date_created']->date( 'Y-m-d' );
				$order_date_time = $order_data['date_created']->date( 'Y-m-d H:i:s' );

				$order_details = array(
					'id'                => $order_id,
					'status'            => $order_status,
					'date_created'      => $order_date,
					'date_time_created' => $order_date_time,
				);

				$wcap_check_cart_status_need_to_update = self::wcap_update_abandoned_cart_status_for_placed_orders( $time_to_send_template_after, $cart_abandoned_time, $abandoned_user_id, $abandoned_user_type, $abandoned_id, $abandoned_user_email, $order_details );
			}
		}
		return $wcap_check_cart_status_need_to_update;
	}

	/**
	 * This function will check if the user type is Guest and the id is greater than 63000000.
	 * Then conider that as a correct guest user, if is not then do not send the emails.
	 *
	 * @param int | string $wcap_user_id - User ID.
	 * @param string       $wcap_user_type - User Type.
	 * @return true | false
	 * @since 7.1
	 */
	public static function wcap_get_is_guest_valid( $wcap_user_id, $wcap_user_type ) {

		if ( 'REGISTERED' === $wcap_user_type ) {
			return true;
		}

		if ( 'GUEST' === $wcap_user_type && $wcap_user_id >= 63000000 ) {
			return true;
		}

		// It indicates that the user type is guest but the id for them is wrong.
		return false;
	}

	/**
	 * It will check the cart total. If the cart total is 0 then email will not be sent.
	 *
	 * @param array $cart Cart detail.
	 * @param int   $cart_id - Cart ID.
	 * @return true | false
	 * @since 4.7
	 */
	public static function wcap_check_cart_total( $cart, $cart_id ) {

		$prd_value_greater_than_zero = false;
		foreach ( $cart as $k => $v ) {
			if ( isset( $v->line_total ) && 0 !== $v->line_total && 0 < $v->line_total ) {
				$prd_value_greater_than_zero = true;
				break;
			}
		}
		return apply_filters( 'wcap_cart_total', $prd_value_greater_than_zero, $cart_id, $cart );

	}

	/**
	 * Get all carts which have the creation time earlier than the one that is passed.
	 *
	 * @param timestamp    $cart_time Cutoff time for loggedin user.
	 * @param timestamo    $cart_time_guest Cutoff time for Guest user.
	 * @param int | string $template_id Template id.
	 * @param string       $main_prefix Multisite main site prefix.
	 * @param int          $template_time - Time at which template was activated.
	 * @param string       $template_type = Reminder template type i.e. email | sms | fb.
	 * @globals mixed $wpdb
	 * @return array | object $results All carts.
	 */
	public static function wcap_get_carts( $cart_time, $cart_time_guest, $template_id, $main_prefix, $template_time, $template_type = 'email' ) {
		global $wpdb;

		switch ( $template_type ) {
			case 'email':
			default:
				$reminder_status = 'email_reminder_status';
				break;
			case 'sms':
				$reminder_status = 'sms_reminder_status';
				break;
			case 'whatsapp':
				$reminder_status = 'whatsapp_reminder_status';
				break;
			case 'fb':
				$reminder_status = 'fb_reminder_status';
				break;
		}
		$wcap_add_template_condition = ' AND abandoned_cart_time > ' . $template_time;

		// Return carts with statuses 'Abandoned - cart_ignored = 0' and 'Abandoned - Order Unpaid - cart_ignored = 2'.
		$results = $wpdb->get_results( // phpcs:disable
			'SELECT wpac.id, wpac.cart_ignored, wpac.user_id, wpac.abandoned_cart_info, wpac.abandoned_cart_time, wpac.' . $reminder_status . ', wpac.user_type, wpac.language, wpac.session_id FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "` AS wpac
				WHERE (
					( user_type = 'REGISTERED' AND abandoned_cart_time < '" . $cart_time . "' )
					OR 
					( user_type = 'GUEST' AND abandoned_cart_time < '" . $cart_time_guest . "' )
				)
				AND wpac.user_id != 0
				AND wpac.recovered_cart = '0'
				AND wpac.cart_ignored IN ('0','2','4')
				AND wpac.unsubscribe_link <> '1'
				AND wcap_trash = ''
				AND " . $reminder_status . " NOT IN ('complete','manual')
				AND wpac.id NOT IN ( SELECT cart_id FROM " . WCAP_EMAIL_SENT_HISTORY_TABLE . " WHERE template_id = $template_id ) $wcap_add_template_condition" // phpcs:ignore
		);

		return $results;
	}

	/**
	 * When all email templates are activated after a period - let's say 1 month, then the emails shouldn't be sent together for all templates
	 * Instead, the emails should be sent with earliest template first & then subsequently after the appropriate interval for 2nd template
	 * & so on.
	 *
	 * @param int | string $wcap_cart_id Abandoned cart id.
	 * @param timestamp    $time_to_send_template_after Email template time.
	 * @param int          $template_id Template id.
	 * @return boolean true Send email | false Dont send email.
	 * @globals mixed $wpdb
	 * @since 3.7
	 */
	public static function wcap_remove_cart_for_mutiple_templates( $wcap_cart_id, $time_to_send_template_after, $template_id ) {
		global $wpdb;

		$wcap_get_last_email_sent_time_results_list = $wpdb->get_results( // phpcs:ignore
			"SELECT `sent_time` FROM `" . WCAP_EMAIL_SENT_HISTORY_TABLE . "` WHERE cart_id = $wcap_cart_id ORDER BY `sent_time` DESC LIMIT 1" // phpcs:ignore
		);

		if ( count( $wcap_get_last_email_sent_time_results_list ) > 0 ) {
			$last_template_send_time   = strtotime( $wcap_get_last_email_sent_time_results_list[0]->sent_time );
			$second_template_send_time = $last_template_send_time + $time_to_send_template_after;
			$current_time_test         = current_time( 'timestamp' ); // phpcs:ignore
			if ( $second_template_send_time > $current_time_test ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * It will update the abandoned cart status if the customer has placed the order before the reminder email is sent.
	 *
	 * @param timestamp    $time_to_send_template_after Template time.
	 * @param timestamp    $wcap_cart_time Abadoned cart time.
	 * @param int | string $wcap_user_id User id.
	 * @param string       $wcap_user_type User type.
	 * @param int | string $wcap_cart_id Abandoned cart id.
	 * @param string       $wcap_user_email Email address of user.
	 * @param array        $order_details - Order Details.
	 * @global mixed $wpdb
	 * @return boolean true Cart updated | false Cart not updated.
	 * @since 5.0
	 */
	public static function wcap_update_abandoned_cart_status_for_placed_orders( $time_to_send_template_after, $wcap_cart_time, $wcap_user_id, $wcap_user_type, $wcap_cart_id, $wcap_user_email, $order_details ) {

		$updated_value = self::wcap_update_cart_status( $wcap_cart_id, $wcap_cart_time, $time_to_send_template_after, $wcap_user_email, $order_details );

		if ( 1 === $updated_value ) {
				return true;
		}

		return false;

	}

	/**
	 * Updates the abandoned cart status
	 *
	 * @param int | string $cart_id Abandoned cart id.
	 * @param timestamp    $abandoned_cart_time Abadoned cart time.
	 * @param timestamp    $time_to_send_template_after Template time.
	 * @param string       $user_billing_email Email address of user.
	 * @param array        $order_details - Order Details such as created date etc.
	 * @global mixed $wpdb
	 * @return boolean true Cart updated | false Cart not updated.
	 * @since 5.0
	 */
	public static function wcap_update_cart_status( $cart_id, $abandoned_cart_time, $time_to_send_template_after, $user_billing_email, $order_details ) {

		global $wpdb;

		$current_time    = current_time( 'timestamp' ); // phpcs:ignore
		$todays_date     = date( 'Y-m-d', $current_time ); // phpcs:ignore
		$order_date      = $order_details['date_created'];
		$order_date_time = $order_details['date_time_created'];
		$order_status    = $order_details['status'];
		$days            = get_option( 'ac_cart_abandoned_after_x_days_order_placed' );
		if ( '' !== $days ) {
			$days = ' +' . $days . ' days';
		}

		$order_date_str = strtotime( $order_date . $days );

		// Retreive the cart status.
		$cart_ignored_status = $wpdb->get_col( // phpcs:ignore
			$wpdb->prepare(
				'SELECT cart_ignored FROM ' . WCAP_ABANDONED_CART_HISTORY_TABLE . ' WHERE id = %d', // phpcs:ignore
				$cart_id
			)
		);

		if ( $order_date_str > $current_time ) {

			// In some case the cart is recovered but it is not marked as the recovered. So here we check if any record is found for that cart id if yes then update the record respectively.
			$wcap_check_email_sent_to_cart = self::wcap_get_cart_sent_data( $cart_id );

			if ( 0 !== $wcap_check_email_sent_to_cart && '2' !== $cart_ignored_status[0] ) {

				$wcap_results = $wpdb->get_results( // phpcs:ignore
					$wpdb->prepare(
						'SELECT `post_id` FROM `' . $wpdb->prefix . 'postmeta` WHERE meta_value = %s AND meta_key = %s ',
						$cart_id,
						'wcap_recover_order_placed'
					)
				);

				if ( count( $wcap_results ) > 0 ) {

					$order_id = $wcap_results[0]->post_id;
					try {
						if ( 'wc-cancelled' !== $order_status && 'wc-refunded' !== $order_status && 'wc-trash' !== $order_status ) {
							$order = new WC_Order( $order_id );
							wcap_common::wcap_updated_recovered_cart( $cart_id, $order_id, $wcap_check_email_sent_to_cart, $order );

						}
					} catch ( Exception $e ) { // phpcs:ignore
					}
				} else { // Since there's an order placed today for the same user, mark this cart as ignored.
					WCAP_CART_HISTORY_MODEL::update( array( 'cart_ignored' => 3 ), array( 'id' => $cart_id ) );
				}
			} elseif ( '2' === $cart_ignored_status[0] ) {
				return 0; // Return 0 as we want to send reminders for unpaid order status.
			} elseif ( '2' !== $cart_ignored_status[0] ) {
				WCAP_CART_HISTORY_MODEL::update( array( 'cart_ignored' => 3 ), array( 'id' => $cart_id ) );
			}
			return 1;
		} elseif ( strtotime( $order_date_time ) > $abandoned_cart_time ) {
			// Mark the cart as ignored.
			WCAP_CART_HISTORY_MODEL::update( array( 'cart_ignored' => 1 ), array( 'id' => $cart_id ) );
			return 1;
		} elseif ( 'wc-pending' === $order_status || 'wc-failed' === $order_status ) { // Send the reminders.
			return 0;
		}

		return 0;
	}

	/**
	 * It will give the email sent id of the cart id.
	 *
	 * @param int | string $wcap_cart_id Abadoned cart id.
	 * @globals mixed $wpdb
	 * @return int $wcap_sent_id|0 Email sent id | No email sent.
	 * @since 5.0
	 */
	public static function wcap_get_cart_sent_data( $wcap_cart_id ) {
		global $wpdb;

		$wcap_results = $wpdb->get_results( // phpcs:ignore
			$wpdb->prepare(
				"SELECT id FROM `" . WCAP_EMAIL_SENT_HISTORY_TABLE . "` WHERE cart_id = %d  AND recovered_order = '0' ORDER BY 'id' DESC LIMIT 1 ", // phpcs:ignore
				$wcap_cart_id
			)
		);

		if ( count( $wcap_results ) > 0 ) {
			$wcap_sent_id = $wcap_results[0]->id;
			return (int) $wcap_sent_id;
		}
		return 0;
	}

	/**
	 * This function will return the email address needed for the selected template. As we have given the choice to the admin that
	 * he can choose who will recive the template.
	 *
	 * @param int | string $wcap_template_id Template Id.
	 * @param string       $wcap_user_type User type.
	 * @param int | string $wcap_user_id User id.
	 * @param string       $wcap_admin_email Admin Email address.
	 * @param string       $wcap_email_address - Guest Email address.
	 * @param array        $rules - Rules setup for the email template.
	 * @globals mixed $wpdb
	 * @return string $wcap_email_address Email ids on reminder need to send.
	 * @since: 7.0
	 */
	public static function wcap_get_email_for_template( $wcap_template_id, $wcap_user_type, $wcap_user_id, $wcap_admin_email, $wcap_email_address = '', $rules = array() ) {

		global $wpdb;

		$wcap_email_action = array();
		if ( is_array( $rules ) && count( $rules ) > 0 ) {
			foreach ( $rules as $rule_data ) {
				$rule_type  = $rule_data->rule_type;
				$rule_cond  = $rule_data->rule_condition;
				$rule_value = $rule_data->rule_value;
				if ( 'send_to' === $rule_type && 'includes' === $rule_cond ) {
					foreach ( $rule_value as $email_send_key ) {
						if ( in_array( $email_send_key, array( 'wcap_email_customer', 'wcap_email_admin', 'wcap_email_customer_admin', 'email_addresses' ), true ) ) {
							$wcap_email_action[] = $email_send_key;
							if ( 'email_addresses' === $email_send_key ) {
								$emails = '';
								if ( '' !== $rule_data->emails ) {
									if ( stripos( $rule_data->emails, ',' ) > 0 ) {
										$wcap_explode_emails = explode( ',', $rule_data->emails );
									} elseif ( stripos( $rule_data->emails, ' ' ) > 0 ) {
										$wcap_explode_emails = explode( ' ', $rule_data->emails );
									}
									if ( isset( $wcap_explode_emails ) && count( $wcap_explode_emails ) > 0 ) {
										foreach ( $wcap_explode_emails as $emails_list ) {
											if ( '' !== trim( $emails_list ) ) {
												$emails .= $emails_list . ',';
											}
										}
										$emails = rtrim( $emails, ',' );
									} else {
										$emails = $rule_data->emails;
									}
								}
							}
						}
					}
				}
			}
		}

		// If no rules for send to are found, default to customer.
		if ( 0 === count( $wcap_email_action ) ) {
			$wcap_email_action = array( 'wcap_email_customer' );
		}

		if ( 'GUEST' !== $wcap_user_type && 0 !== $wcap_user_id ) {
			$user_email         = '';
			$key                = 'billing_email';
			$single             = true;
			$user_billing_email = get_user_meta( $wcap_user_id, $key, $single );
			if ( isset( $user_billing_email ) && '' !== $user_billing_email ) {
				$user_email = $user_billing_email;
			} else {
				$user_data = get_userdata( $wcap_user_id );
				if ( isset( $user_data->user_email ) && '' !== $user_data->user_email ) {
					$user_email = $user_data->user_email;
				}
			}
			$wcap_email_address = sanitize_email( $user_email );
		}

		$reminder_email = '';
		foreach ( $wcap_email_action as $action ) {
			switch ( $action ) {
				case 'wcap_email_customer':
					$reminder_email .= $wcap_email_address . ',';
					break;
				case 'wcap_email_customer_admin':
					$reminder_email .= $wcap_admin_email . ',' . $wcap_email_address . ',';
					break;
				case 'wcap_email_admin':
					$reminder_email .= $wcap_admin_email . ',';
					break;
				case 'email_addresses':
					$reminder_email .= $emails . ',';
					break;
			}
		}
		$wcap_email_address = stripslashes( rtrim( $reminder_email, ',' ) );

		if ( strpos( $wcap_email_address, ',' ) !== false ) {
			$wcap_explode_emails = explode( ',', $wcap_email_address );
			foreach ( $wcap_explode_emails as $email_address ) {
				$validate = wcap_validate_email_format( sanitize_email( $email_address ) );
				if ( 1 === $validate ) {
					break;
				}
			}
		} else {
			$validate = wcap_validate_email_format( sanitize_email( $wcap_email_address ) );
		}

		if ( 1 === $validate ) {
			return sanitize_text_field( $wcap_email_address );
		} else {
			return '';
		}

	}

	/**
	 * Send SMS Reminders if enabled.
	 *
	 * @hook woocommerce_ac_send_email_action
	 * @since 7.9
	 */
	public static function wcap_send_sms_notifications() {
		// Send SMS only during working hours i.e. 09:00 to 17:00 hrs.
		$sms_hrs      = array(
			'start' => '09:00',
			'end'   => '17:00',
		);
		$sms_hrs      = apply_filters( 'wcap_send_sms_hours', $sms_hrs );
		$current_time = gmdate( 'H:i', current_time( 'timestamp' ) );
		if ( $current_time < $sms_hrs['start'] || $current_time > $sms_hrs['end'] ) {
			return;
		}
		$enable_sms = get_option( 'wcap_enable_sms_reminders', '' );

		if ( isset( $enable_sms ) && 'on' === $enable_sms ) {
			self::wcap_send_sms_reminders();

		}
	}

	/**
	 * Sends the reminder emails for all SMS templates
	 * and abandoned carts.
	 *
	 * @since 7.9
	 */
	public static function wcap_send_sms_reminders() {

		// Check if all the details are correctly filled.
		$sid                 = get_option( 'wcap_sms_account_sid', '' );
		$token               = get_option( 'wcap_sms_auth_token', '' );
		$from_phone          = get_option( 'wcap_sms_from_phone', '' );
		$sms_consent_enabled = get_option( 'wcap_enable_sms_consent', '' );
		if ( '' === $sid || '' === $token || '' === $from_phone ) {
			return;
		} else {
			$twilio_details = array(
				'sid'        => $sid,
				'token'      => $token,
				'from_phone' => $from_phone,
			);
		}

		// Require the common functions file.
		require_once WCAP_PLUGIN_PATH . '/includes/wcap_functions.php';
		require_once WCAP_PLUGIN_PATH . '/includes/wcap_tiny_url.php';

		// Get the SMS Templates.
		$sms_templates = wcap_get_notification_templates( 'sms' );

		if ( is_array( $sms_templates ) && count( $sms_templates ) > 0 ) {
			global $wpdb;
			$current_time        = current_time( 'timestamp' ); // phpcs:ignore
			$registered_cut_off  = is_numeric( get_option( 'ac_cart_abandoned_time', 10 ) ) ? get_option( 'ac_cart_abandoned_time', 10 ) * 60 : 10 * 60;
			$guest_cut_off       = is_numeric( get_option( 'ac_cart_abandoned_time_guest', 10 ) ) ? get_option( 'ac_cart_abandoned_time_guest', 10 ) * 60 : 10 * 60;
			$main_prefix         = is_multisite() ? $wpdb->get_blog_prefix( 1 ) : $wpdb->prefix; // Check if it's a multisite.
			$last_email_template = wcap_get_last_template_in_reminder_cycle( 'sms' );
			if ( is_array( $last_email_template ) && count( $last_email_template ) > 0 ) {
				reset( $last_email_template );
				$last_template_id = key( $last_email_template );
			} else {
				$last_template_id = 0;
			}
			foreach ( $sms_templates as $frequency => $template_data ) {

				// Template ID.
				$template_id = $template_data['id'];

				$time_check_registered = $current_time - $frequency - $registered_cut_off;
				$time_check_guest      = $current_time - $frequency - $guest_cut_off;
				// Get abandoned carts.
				$carts = self::wcap_get_carts( $time_check_registered, $time_check_guest, $template_id, $main_prefix, $template_data['activated_time'], 'sms' );

				if ( is_array( $carts ) && count( $carts ) > 0 ) {
					foreach ( $carts as $cart_data ) {
						$cart_id      = $cart_data->id;
						$cart_details = json_decode( $cart_data->abandoned_cart_info );
						// Mark complete if it's the last SMS to be sent for the cart.
						if ( (int) $template_id === (int) $last_template_id ) {
							WCAP_CART_HISTORY_MODEL::update( array( 'sms_reminder_status' => 'complete' ), array( 'id' => $cart_id ) );
						}
						if ( 'on' === $sms_consent_enabled ) {
							$sms_consent = isset( $cart_details->sms_consent ) && $cart_details->sms_consent ? $cart_details->sms_consent : false;
							$sms_consent = apply_filters( 'wcap_sms_consent_status', $sms_consent, $cart_id, $cart_data );
						} else {
							$sms_consent = true;
						}
						if ( $cart_data->user_id > 0 && $sms_consent ) {
							// SMS Reminders.
							self::wcap_send_sms( $cart_data, $template_data, $twilio_details );
						}
					}
				}
			}
		}
	}

	/**
	 * Sends the SMS Reminder for the abandoned cart.
	 *
	 * @param object $cart_data - Data from the Cart History Table.
	 * @param object $template_data - SMS Template Data.
	 * @param array  $twilio_details - Twilio Connection Details.
	 * @since 7.9
	 */
	public static function wcap_send_sms( $cart_data, $template_data, $twilio_details ) {

		// Get the phone number to send the SMS to.
		$to_phone = self::get_phone( $cart_data->user_id );

		$template_id = $template_data['id'];
		$cart_id     = $cart_data->id;

		// Send the message.
		if ( $to_phone ) {

			global $wpdb;
			// cart Data - cart ID, cart info, user ID, cart abandoned time.
			// template data - body, coupon code.
			$message_temp = $template_data['body'];

			$coupon_code = $template_data['coupon_code'];

			$crypt_key         = wcap_get_crypt_key( $to_phone );
			$encryption        = array(
				'crypt_key' => $crypt_key,
				'contact'   => $to_phone,
			);
			$selected_language = $cart_data->language;
			$name_msg          = 'wcap_sms_' . $template_data['id'] . '_body';
			$message_temp      = wcap_get_translated_texts( $name_msg, $message_temp, $selected_language );
			$msg_body          = self::wcap_replace_sms_tags( $message_temp, $cart_data, $template_data, $encryption, 'sms_link' );

			$from_phone = $twilio_details['from_phone'];
			$sid        = $twilio_details['sid'];
			$token      = $twilio_details['token'];
			// Insert a record in the sent history table.
			$wpdb->insert( // phpcs:ignore
				WCAP_EMAIL_SENT_HISTORY_TABLE,
				array(
					'template_id'               => $template_id,
					'cart_id'                   => $cart_id,
					'sent_time'                 => current_time( 'mysql' ),
					'sent_notification_contact' => $to_phone,
					'notification_type'         => 'sms',
					'encrypt_key'               => $crypt_key,
				)
			);
			try {
				$client = new Client( $sid, $token );

				$message = $client->messages->create(
					$to_phone,
					array(
						'from' => $from_phone,
						'body' => $msg_body,
					)
				);

				if ( $message->sid ) {
					$message_sid = $message->sid;

					$message_details = $client->messages( $message_sid )->fetch();

					$status = $message_details->status;

					// Update the details in the tiny urls.
					self::wcap_update_sms_details( $template_id, $cart_id, $to_phone, $status, $message_sid );

					// Update the count.
					self::wcap_update_sms_count( $template_id, 1 );
					// Hook for further action.
					do_action( 'wcap_reminder_sms_sent', $cart_id, $template_id );
				}
			} catch ( Exception $e ) {
				if ( defined( WP_DEBUG ) && true === WP_DEBUG ) {
					error_log( $e->getMessage() );//phpcs:ignore
				}
			}
		}

	}

	/**
	 * Returns the Phone number of the user.
	 *
	 * @param integer $user_id - User ID.
	 * @return string`|boolean - Phone Number.
	 *
	 * @since 7.9
	 */
	public static function get_phone( $user_id ) {

		global $wpdb;

		$country_map = Wcap_Common::wcap_country_code_map();

		$to_phone = '';
		// User Name.
		if ( $user_id >= 63000000 ) {

			$phone = $wpdb->get_results( // phpcs:ignore
				$wpdb->prepare(
					'SELECT phone, billing_country FROM `' . WCAP_GUEST_CART_HISTORY_TABLE . '` WHERE id = %d', // phpcs:ignore
					$user_id
				)
			);

			if ( is_array( $phone ) && count( $phone ) > 0 ) {
				$billing_country = $phone[0]->billing_country;
				$to_phone        = $phone[0]->phone;
			}
		} else {
			$user = get_user_by( 'id', $user_id );

			$billing_country = $user->billing_country;

			$to_phone = $user->billing_phone;
		}
		$billing_country = apply_filters( 'wcap_default_sms_country_code', $billing_country, $user_id );
		$dial_code       = isset( $country_map[ $billing_country ] ) ? $country_map[ $billing_country ]['dial_code'] : '';
		$to_phone        = str_ireplace( '-', '', $to_phone );

		// Verify the Phone number.
		if ( is_numeric( $to_phone ) && strlen( $to_phone ) > 9 ) { // Check if the number is valid.
			// If first character is 0, remove it.
			$to_phone = ltrim( $to_phone, '0' );
			// If first character is not a +, add it.
			if ( '+' !== substr( $to_phone, 0, 1 ) ) {
				if ( '' !== $dial_code ) {
					$to_phone = $dial_code . $to_phone;
				} else {
					$to_phone = '+' . $to_phone;
				}
			}
			return $to_phone;
		} else {
			return false;
		}

	}

	/**
	 * Replace the merge tags with cart data.
	 *
	 * @param string $body - SMS text.
	 * @param object $cart_data - Cart Data.
	 * @param array  $template_data - SMS Template Data.
	 * @param string $encryption - Encryption key.
	 *
	 * @since 7.9
	 */
	public static function wcap_replace_sms_tags( $body, $cart_data, $template_data, $encryption, $link_type, $checkout_short_url = false ) {

		global $wpdb;

		$user_id = $cart_data->user_id;
		// User Name.
		if ( $user_id >= 63000000 ) {
			$name = $wpdb->get_col( // phpcs:ignore
				$wpdb->prepare(
					'SELECT billing_first_name FROM `' . WCAP_GUEST_CART_HISTORY_TABLE . '` WHERE id = %d', // phpcS:ignore
					$user_id
				)
			);

			if ( is_array( $name ) && count( $name ) > 0 ) {
				$replace_tags['{{user.name}}'] = $name[0];
			}
		} else {

			$user                          = get_user_by( 'id', $user_id );
			$replace_tags['{{user.name}}'] = $user->first_name;
		}

		$abandoned_id = $cart_data->id;
		$template_id  = $template_data['id'];

		// Date Abandoned.
		$replace_tags['{{date.abandoned}}'] = date( 'Y-m-d', $cart_data->abandoned_cart_time ); // phpcs:ignore
		// Shop Name.
		$replace_tags['{{shop.name}}'] = get_option( 'blogname' );
		// Shop Link.
		if ( stripos( $body, '{{shop.link}}' ) !== false ) {

			$shop_link = wc_get_page_permalink( 'shop' );

			// Shorten it.
			$shortened_shop_link = WCAP_Tiny_Url::get_short_url( $shop_link );

			$wpdb->insert( // phpcs:ignore
				WCAP_TINY_URLS,
				array(
					'cart_id'           => $abandoned_id,
					'template_id'       => $template_id,
					'long_url'          => $shop_link,
					'short_code'        => $shortened_shop_link,
					'date_created'      => current_time( 'timestamp' ), // phpcs:ignore
					'counter'           => 0,
					'notification_data' => wp_json_encode( array( 'link_clicked' => 'Shop Page' ) ),
				)
			);
			$insert_id = $wpdb->insert_id;

			// Add the website url to the short url.
			$shop_link = get_option( 'siteurl' ) . "/$shortened_shop_link";

			$replace_tags['{{shop.link}}'] = $shop_link;

		} else {
			$replace_tags['{{shop.link}}'] = '';
		}

		if ( stripos( $body, '{{checkout.link}}' ) !== false ) {

			// Checkout Link.

			// Generate the long url.
			$db_id = generate_checkout_url( $cart_data, $template_data, $link_type, $encryption );

			// Get the long url.
			$long_url = WCAP_Tiny_Url::get_long_url_from_id( $db_id );
			// Shorten it.
			$short_url = WCAP_Tiny_Url::get_short_url( $long_url );

			// Update the DB.
			WCAP_Tiny_Url::update_short_url( $db_id, $short_url );

			// Add the website url to the short url.
			$short_url = $checkout_short_url ? $short_url : get_option( 'siteurl' ) . "/$short_url";

			$replace_tags['{{checkout.link}}'] = $short_url;

		} else {
			$replace_tags['{{checkout.link}}'] = '';
		}

		// Admin Phone Number.
		$user_admin = get_user_by( 'email', get_option( 'admin_email' ) );
		if ( $user_admin ) {
			$admin_id                         = $user_admin->ID;
			$replace_tags['{{phone.number}}'] = get_user_meta( $admin_id, 'billing_phone', true );
		} else {
			$replace_tags['{{phone.number}}'] = '';
		}

		// Coupon code.
		$coupon_id                       = $template_data['coupon_code'];
		$coupon_to_apply                 = get_post( $coupon_id, ARRAY_A );
		$coupon_code                     = isset( $coupon_to_apply['post_title'] ) ? $coupon_to_apply['post_title'] : '';
		$replace_tags['{{coupon.code}}'] = $coupon_code;

		// Replace the merge tags with data.
		$msg = $body;
		foreach ( $replace_tags as $key => $value ) {
			$msg = str_replace( $key, $value, $msg );
		}

		return $msg;
	}

	/**
	 * Update the SMS Sent count.
	 *
	 * @param integer $template_id - SMS Template ID.
	 * @param integer $update_by - Number by which to update the count.
	 *
	 * @since 7.9
	 */
	public static function wcap_update_sms_count( $template_id, $update_by = 1 ) {

		// Get the existing count.
		$count = wcap_get_notification_meta( $template_id, 'sent_count' );

		if ( ! $count ) {
			$count = 0;
		}
		// Update the count in the DB.
		$count += $update_by;
		wcap_update_notification_meta( $template_id, 'sent_count', $count );
	}

	/**
	 * Update SMS details in the Tiny URLs table.
	 *
	 * @param integer $template_id - SMS Template ID.
	 * @param integer $cart_id - Abandoned Cart ID.
	 * @param string  $to_phone - Phone Number to which SMS has been sent E.164 format.
	 * @param string  $sms_status - SMS Status.
	 * @param string  $message_sid - Message ID (received from Twilio).
	 * @since 7.10.0
	 */
	public static function wcap_update_sms_details( $template_id, $cart_id, $to_phone, $sms_status, $message_sid ) {

		global $wpdb;

		// Get the record from tiny urls table.
		$record_id = $wpdb->get_results( // phpcS:ignore
			$wpdb->prepare(
				'SELECT id, notification_data FROM ' . WCAP_TINY_URLS . ' WHERE cart_id = %d AND template_id = %d', // phpcs:ignore
				$cart_id,
				$template_id
			)
		);

		if ( is_array( $record_id ) && count( $record_id ) > 0 ) {
			foreach ( $record_id as $r_id ) {
				$notification_data = json_decode( $r_id->notification_data );

				// Prepare the data to be inserted.
				$notification_data->phone_number = $to_phone;
				$notification_data->sent_time    = current_time( 'timestamp' ); // phpcs:ignore
				$notification_data->sms_status   = $sms_status;
				$notification_data->msg_id       = $message_sid;
				$id                              = $r_id->id;

				// Update the record.
				$wpdb->update( WCAP_TINY_URLS, array( 'notification_data' => json_encode( $notification_data ) ), array( 'id' => $r_id->id ) ); // phpcS:ignore

			}
		}

	}

	/**
	 * Sends the reminder emails for all SMS templates
	 * and abandoned carts.
	 *
	 * @since 7.9
	 */
	public static function wcap_send_whatsapp_reminders() {

		// Check if all the details are correctly filled.
		$token               = get_option( 'wcap_whatsapp_access_token', '' );
		$phone_id            = get_option( 'wcap_whatsapp_phone_id', '' );

		if ( '' === $token || '' === $phone_id ) {
			return;
		}

		// Require the common functions file.
		require_once WCAP_PLUGIN_PATH . '/includes/wcap_functions.php';
		require_once WCAP_PLUGIN_PATH . '/includes/wcap_tiny_url.php';

		// Get the SMS Templates.
		$whatsapp_templates = wcap_get_notification_templates( 'whatsapp' );

		if ( is_array( $whatsapp_templates ) && count( $whatsapp_templates ) > 0 ) {
			global $wpdb;
			$current_time        = current_time( 'timestamp' ); // phpcs:ignore
			$registered_cut_off  = is_numeric( get_option( 'ac_cart_abandoned_time', 10 ) ) ? get_option( 'ac_cart_abandoned_time', 10 ) * 60 : 10 * 60;
			$guest_cut_off       = is_numeric( get_option( 'ac_cart_abandoned_time_guest', 10 ) ) ? get_option( 'ac_cart_abandoned_time_guest', 10 ) * 60 : 10 * 60;
			$main_prefix         = is_multisite() ? $wpdb->get_blog_prefix( 1 ) : $wpdb->prefix; // Check if it's a multisite.
			$last_email_template = wcap_get_last_template_in_reminder_cycle( 'whatsapp' );
			if ( is_array( $last_email_template ) && count( $last_email_template ) > 0 ) {
				reset( $last_email_template );
				$last_template_id = key( $last_email_template );
			} else {
				$last_template_id = 0;
			}

			foreach ( $whatsapp_templates as $frequency => $template_data ) {
				// Template ID.
				$template_id = $template_data['id'];

				$time_check_registered = $current_time - $frequency - $registered_cut_off;
				$time_check_guest      = $current_time - $frequency - $guest_cut_off;
				// Get abandoned carts.
				$carts = self::wcap_get_carts( $time_check_registered, $time_check_guest, $template_id, $main_prefix, $template_data['activated_time'], 'whatsapp' );

				if ( is_array( $carts ) && count( $carts ) > 0 ) {
					foreach ( $carts as $cart_data ) {
						$cart_id      = $cart_data->id;
						$cart_details = json_decode( $cart_data->abandoned_cart_info );
						// Mark complete if it's the last SMS to be sent for the cart.
						if ( (int) $template_id === (int) $last_template_id ) {
							WCAP_CART_HISTORY_MODEL::update( array( 'whatsapp_reminder_status' => 'complete' ), array( 'id' => $cart_id ) );
						}
						
						$whatsapp_consent = isset( $cart_details->whatsapp_consent ) && $cart_details->whatsapp_consent ? $cart_details->whatsapp_consent : false;
						if ( $cart_data->user_id > 0 && $whatsapp_consent ) {
							// SMS Reminders.
							self::wcap_send_whastapp_message( $cart_data, $template_data );
						}
					}
				}
			}
		}
	}
	/**
	 * Check if the placeholders are numbered.
	 *
	 * @param array $body_vars - Body variables.
	 * @return boolean
	 * @since 9.19.0
	 */
	public static function wcap_check_placeholder_type( $body_vars ) {
		$type = '';
	
		foreach ( $body_vars as $key => $val ) {
			if ( preg_match('/^\{\{\d+\}\}$/', $key ) ) {
				$type = 'number';
				break;
			} elseif ( preg_match('/^\{\{[a-zA-Z_][a-zA-Z0-9_.]*\}\}$/', $key ) ) {
				$type = 'name';
				break;
			}
		}
	
		return $type;
	}

	public static function wcap_get_placeholder_text( $string ) {
		if ( preg_match( '/\{\{([^}]+)\}\}/', $string, $matches ) ) {
			return $matches[1]; // text inside {{ }}
		}
		return null;
	}

	/**
	 * Sends the SMS Reminder for the abandoned cart.
	 *
	 * @param object $cart_data - Data from the Cart History Table.
	 * @param object $template_data - SMS Template Data.
	 * @param array  $twilio_details - Twilio Connection Details.
	 * @since 7.9
	 */
	public static function wcap_send_whastapp_message( $cart_data, $template_data ) {

		// Get the phone number to send the SMS to.
		$to_phone    = self::get_phone( $cart_data->user_id );
		$token       = get_option( 'wcap_whatsapp_access_token', '' );
		$phone_id    = get_option( 'wcap_whatsapp_phone_id', '' );
		$template_id = $template_data['id'];
		$cart_id     = $cart_data->id;

		// Send the message.
		if ( $to_phone ) {

			global $wpdb;
			// cart Data - cart ID, cart info, user ID, cart abandoned time.
			// template data - body, coupon code.
			$message_variables = json_decode( $template_data['body'], true );
			$template_language = $message_variables['template_language'];
			$body_variables    = $message_variables['body_variables'];
			$button_variables  = $message_variables['button_variables'];
			$header_variables  = $message_variables['header_variables'];
			
			//$coupon_code = $template_data['coupon_code'];

			$crypt_key         = wcap_get_crypt_key( $to_phone );
			$encryption        = array(
				'crypt_key' => $crypt_key,
				'contact'   => $to_phone,
			);

			// Step 2: Parse body_variables into key => value pairs
			$body_vars = [];
			if ( !empty( $body_variables ) ) {
				$pairs = explode( ',', $body_variables );
				foreach ( $pairs as $pair ) {
					list( $key, $value ) = explode( ':', $pair, 2 );	
					$body_vars[ trim($key) ] = trim($value);
				}

				foreach( $body_vars as $key => $value ) {
					$body_vars[ $key ] = self::wcap_replace_sms_tags( $value, $cart_data, $template_data, $encryption, 'whatsapp_link' );
				}
			}

			$variable_type = self::wcap_check_placeholder_type( $body_vars );

			$header_vars = [];
			if ( !empty( $header_variables ) ) {
				$pairs = explode( ',', $header_variables );
				foreach ( $pairs as $pair ) {
					list( $key, $value ) = explode( ':', $pair, 2 );
					$header_vars[ trim($key) ] = trim($value);
				}

				foreach( $header_vars as $key => $value ) {
					$header_vars[ $key ] = self::wcap_replace_sms_tags( $value, $cart_data, $template_data, $encryption, 'whatsapp_link' );
				}
			}

			$button_vars = [];
			if ( !empty( $button_variables ) ) {
				$pairs = explode( ',', $button_variables );
				foreach ( $pairs as $pair ) {
					list( $key, $value ) = explode( ':', $pair, 2 );
					$button_vars[ trim($key) ] = trim($value);
				}

				foreach( $button_vars as $key => $value ) {
					$button_vars[ $key ] = self::wcap_replace_sms_tags( $value, $cart_data, $template_data, $encryption, 'whatsapp_link', $short_url = true );
				}
			}

			// Insert a record in the sent history table.
			$wpdb->insert( // phpcs:ignore
				WCAP_EMAIL_SENT_HISTORY_TABLE,
				array(
					'template_id'               => $template_id,
					'cart_id'                   => $cart_id,
					'sent_time'                 => current_time( 'mysql' ),
					'sent_notification_contact' => $to_phone,
					'notification_type'         => 'whatsapp',
					'encrypt_key'               => $crypt_key,
				)
			);
			try {

				if ( 'name' === $variable_type ) {
					$payload = array(
						'messaging_product' => 'whatsapp',
						'to' => $to_phone,
						'type' => 'template',
						'template' => array(
							'name' => $template_data['template_name'],
							'language' => array( 'code' => $template_language ),
							'components' => array(
								array(
									'type' => 'header',
									'parameters' =>  array_map(
										function( $key, $var ) {
											return [
												'type' => 'text',
												'parameter_name' => self::wcap_get_placeholder_text( $key ),
												'text' => trim( $var ),
											];
										},
										array_keys( $header_vars ),
										$header_vars
									),
								),
								array(
									'type' => 'body',
									'parameters' =>  array_map(
										function( $key, $var ) {
											return [
												'type' => 'text',
												'parameter_name' => self::wcap_get_placeholder_text( $key ),
												'text' => trim( $var ),
											];
										},
										array_keys( $body_vars ),
										$body_vars
									),
								),
								array(
									'type' => 'button',
									'sub_type' => 'url',
									'index' => 0, // button index (0 = first button)
									'parameters' => array_values(
										array_map(
											function( $var ) {
												return [
													'type' => 'text',
													'text' => trim( $var ),
												];
											},
											$button_vars
										)
									),
								)
							),
						)
					);
				} else {
					$payload = array(
						'messaging_product' => 'whatsapp',
						'to' => $to_phone,
						'type' => 'template',
						'template' => array(
							'name' => $template_data['template_name'],
							'language' => array( 'code' => $template_language ),
							'components' => array(
								array(
									'type' => 'header',
									'parameters' =>  array_values(
										array_map(
											function( $var ) {
												return [
													'type' => 'text',
													'text' => trim( $var ),
												];
											},
											$header_vars
										)
									),
								),
								array(
									'type' => 'body',
									'parameters' =>  array_values(
										array_map(
											function( $var ) {
												return [
													'type' => 'text',
													'text' => trim( $var ),
												];
											},
											$body_vars
										)
									),
								),
								array(
									'type' => 'button',
									'sub_type' => 'url',
									'index' => 0, // button index (0 = first button)
									'parameters' => array_values(
										array_map(
											function( $var ) {
												return [
													'type' => 'text',
													'text' => trim( $var ),
												];
											},
											$button_vars
										)
									),
								)
							),
						)
					);
				}
				
				$response = wp_remote_post( "https://graph.facebook.com/v22.0/{$phone_id}/messages", [
					'headers' => [
						'Authorization' => "Bearer $token",
						'Content-Type'  => 'application/json',
					],
					'body' => json_encode( $payload ),
				]);

				$message = json_decode( wp_remote_retrieve_body( $response ), true );
				if ( isset( $message['error'] ) ) {
					$error_code    = $message['error']['code'];
					$error_message = $message['error']['message'];
					// Log or handle token issues
					Wcap_Common::wcap_handle_whatsapp_token_error( $error_code, $error_message );
				} else {
					delete_option( 'wcap_whatsapp_token_notice' );
				}

				
				// Update the details in the tiny urls.
				//self::wcap_update_sms_details( $template_id, $cart_id, $to_phone, $status, $message_sid );

				// Update the count.
				//self::wcap_update_sms_count( $template_id, 1 );
				// Hook for further action.
				do_action( 'wcap_reminder_whatsapp_sent', $cart_id, $template_id );
			} catch ( Exception $e ) {
				if ( defined( WP_DEBUG ) && true === WP_DEBUG ) {
					error_log( $e->getMessage() );//phpcs:ignore
				}
			}
		}

	}

}
