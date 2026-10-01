<?php
/**
 * It will display the email template listing.
 *
 * @author   LittleCodr
 * @package Abandoned-Cart-Pro-for-WooCommerce/Admin/Template
 * @since 5.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wcap_Email_Template_List' ) ) {
	/**
	 * It will display the email template listing, also it will add, update & delete the email template in the database.
	 *
	 * @since 5.0
	 */
	class Wcap_Email_Template_List {

		/**
		 * Email Type.
		 * @var string
		 * @since 8.21.0
		 */
		public static $email_type = 'abandoned_cart_email';
		/**
		 * It will save the new created email templates.
		 *
		 * @return true | false $insert_template_successfuly_pro If template inserted successfully
		 * @since 5.0
		 */
		public static function wcap_save_email_template() {
	        check_ajax_referer( 'wcap_settings_nonce', 'security' );
	        if ( ! current_user_can( 'manage_options' ) ) {
	            wp_send_json_error( 'Unauthorized', 403 );
	            wp_die();
	        }
			$message             = array();
			$rules               = self::wcap_rules();
			$coupon_code_options = self::wcap_coupon_options();
			$is_wc_template      = ( empty( $_POST['is_wc_template'] ) ) ? '0' : '1'; // phpcs:ignore WordPress.Security.NonceVerification
			$unique_coupon       = ( empty( $_POST['unique_coupon'] ) ) ? '0' : '1'; // phpcs:ignore WordPress.Security.NonceVerification

			$coupon_code_id = isset( $_POST['coupon_ids_new'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_ids_new'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

			$subject       = isset( $_POST['woocommerce_ac_email_subject'] ) ? stripslashes( sanitize_text_field( wp_unslash( $_POST['woocommerce_ac_email_subject'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			$body          = isset( $_POST['initial_data_body'] ) ? stripslashes( $_POST['initial_data_body'] ) : ''; // phpcs:ignore
			$body          = html_entity_decode( $body, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 );
			$email_freq    = isset( $_POST['email_frequency'] ) ? sanitize_text_field( wp_unslash( $_POST['email_frequency'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			$day_hour      = isset( $_POST['day_or_hour'] ) ? sanitize_text_field( wp_unslash( $_POST['day_or_hour'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			$template_name = isset( $_POST['woocommerce_ac_template_name'] ) ? sanitize_text_field( wp_unslash( $_POST['woocommerce_ac_template_name'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			$email_header  = isset( $_POST['wcap_wc_email_header'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_wc_email_header'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			$match_rules   = isset( $_POST['wcap_match_rules'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_match_rules'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

			// If the link merge tags are prefixed with http://|https:// please remove it.
			$rectify_links = array(
				'http://{{cart.link}}',
				'https://{{cart.link}}',
				'http://{{checkout.link}}',
				'https://{{checkout.link}}',
				'http://{{cart.unsubscribe}}',
				'https://{{cart.unsubscribe}}',
				'http://{{shop.url}}',
				'https://{{shop.url}}',
			);
			foreach ( $rectify_links as $merge_tag ) {
				$start_tag = stripos( $merge_tag, '{{' );
				$new_tag   = substr( $merge_tag, $start_tag );
				$body      = str_ireplace( $merge_tag, $new_tag, $body );
			}
			// Create the data array.
			$content = array(
				'notification_type'           => 'email',
				'email_type'                  => self::$email_type,
				'subject'                     => $subject,
				'body'                        => $body,
				'frequency'                   => $email_freq,
				'day_or_hour'                 => $day_hour,
				'coupon_code'                 => $coupon_code_id,
				'template_name'               => $template_name,
				'discount'                    => $coupon_code_options['coupon_amount'],
				'discount_type'               => $coupon_code_options['discount_type'],
				'discount_shipping'           => $coupon_code_options['discount_shipping'],
				'discount_expiry'             => $coupon_code_options['coupon_expiry'],
				'individual_use'              => $coupon_code_options['individual_use'],
				'generate_unique_coupon_code' => $unique_coupon,
				'is_wc_template'              => $is_wc_template,
				'wc_email_header'             => $email_header,
				'match_rules'                 => $match_rules,
				'rules'                       => $rules,
			);

			$id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
			$content = apply_filters( 'wcap_save_email_template_data', $content, $id );
			$check = 0;
			if ( isset( $_POST['ac_settings_frm'] ) && 'save' === sanitize_text_field( wp_unslash( $_POST['ac_settings_frm'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$content['default_template'] = '0';
				$content['is_active']        = '0';
				$content['activated_time']   = current_time( 'timestamp' ); //phpcs:ignore
				$check                       = self::wcap_insert_template( $content );
			} elseif ( isset( $_POST['ac_settings_frm'] ) && 'update' === sanitize_text_field( wp_unslash( $_POST['ac_settings_frm'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$check = self::wcap_update_template( $content );
			}
			$message['success'] = 'The reminder email template has been successfully saved.';
			return wp_send_json($message);
		}

		/**
		 * It will insert the new email template data into the database.
		 *
		 * @param array $content - Array of column names & their values to be inserted in the DB.
		 * @return int | false Insert ID | false - error.
		 * @since 5.0
		 */
		public static function wcap_insert_template( $content ) {

			$insert_id = WCAP_NOTIFICATION_TEMPLATES_MODEL::insert(
				$content
			);
			return $insert_id;

		}

		/**
		 * Return the coupon settings in the template.
		 *
		 * @return $coupon_code_options - Coupon code settings.
		 */
		public static function wcap_coupon_options() {

			$coupon_expiry      = '';
			$expiry_day_or_hour = '';
			if ( isset( $_POST['wcac_coupon_expiry'] ) && '' !== $_POST['wcac_coupon_expiry'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				$coupon_expiry = sanitize_text_field( wp_unslash( $_POST['wcac_coupon_expiry'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			}

			if ( isset( $_POST['expiry_day_or_hour'] ) && '' !== $_POST['expiry_day_or_hour'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				$expiry_day_or_hour = sanitize_text_field( wp_unslash( $_POST['expiry_day_or_hour'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			}
			$coupon_expiry = $coupon_expiry . '-' . $expiry_day_or_hour;

			$discount_shipping = isset( $_POST['wcap_allow_free_shipping'] ) && '' !== $_POST['wcap_allow_free_shipping'] ? 'yes' : 'off'; // phpcs:ignore WordPress.Security.NonceVerification
			$individual_use    = empty( $_POST['individual_use'] ) ? '0' : '1'; // phpcs:ignore WordPress.Security.NonceVerification
			$discount_type     = isset( $_POST['wcap_discount_type'] ) && '' !== $_POST['wcap_discount_type'] ? sanitize_text_field( wp_unslash( $_POST['wcap_discount_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			$coupon_amount     = isset( $_POST['wcap_coupon_amount'] ) && '' !== $_POST['wcap_coupon_amount'] ? sanitize_text_field( wp_unslash( $_POST['wcap_coupon_amount'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

			$coupon_code_options = array(
				'discount_type'     => $discount_type,
				'coupon_amount'     => $coupon_amount,
				'coupon_expiry'     => $coupon_expiry,
				'discount_shipping' => $discount_shipping,
				'individual_use'    => $individual_use,
			);

			return $coupon_code_options;
		}

		/**
		 * Return a json encoded array of rules for the template.
		 *
		 * @return string $rules - JSON encode $rules array.
		 * @since 8.9.0
		 */
		public static function wcap_rules() {

			$rules = array();
			$rule_value = array();
			$rules_list = json_decode(stripslashes($_POST['wcap_rule_type_']));// phpcs:ignore

			if ( ! empty( $rules_list ) && ( is_array( $rules_list ) || is_object( $rules_list ) ) ) {
				foreach ( $rules_list as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification
					// Get the id.
					$id         = $key;
					$rule_type  = $value->rule_type;
					$rule_cond  = $value->rule_condition;
					$rule_value = $value->rule_value;

					if ( 'order_status' === $rule_type ) {
						self::$email_type = 'follow-up';
					}
					if ( 'send_to' === $rule_type && is_array( $rule_value ) && in_array( 'email_addresses', $rule_value, true ) ) {
						$rule_email_list = '';
						if ( isset( $value->wcap_rules_email_addresses ) ) {
							$rule_email_list = sanitize_text_field( wp_unslash( $value->wcap_rules_email_addresses ) ); // phpcs:ignore WordPress.Security.NonceVerification
						} elseif ( isset( $value->emails ) ) {
							$rule_email_list = sanitize_text_field( wp_unslash( $value->emails ) ); // phpcs:ignore WordPress.Security.NonceVerification
						}
						$rules[] = array(
							'rule_type'      => $rule_type,
							'rule_condition' => $rule_cond,
							'rule_value'     => $rule_value,
							'emails'         => $rule_email_list,
						);
					} else {
						$rules[] = array(
							'rule_type'      => $rule_type,
							'rule_condition' => $rule_cond,
							'rule_value'     => $rule_value,
						);
					}
				}
			}

			return json_encode( $rules ); //phpcs:ignore
		}

		/**
		 * It will update email template data into the database.
		 * It will insert the post meta for the email action, it will decide who will recive this email template.
		 *
		 * @param array $content - Array of fields & their values to be updated in the DB.
		 * @return int | false Number of rows updated | false for error.
		 * @since 5.0
		 */
		public static function wcap_update_template( $content ) {

			$id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification

			if ( $id > 0 ) {
				$update_count = WCAP_NOTIFICATION_TEMPLATES_MODEL::update(
					$content,
					array(
						'id' => $id,
					)
				);

				return $update_count;
			}

		}
	}
}
