<?php
/**
 * It will fetch the Add to cart data, generate and populate data in the modal.
 *
 * @author  LittleCodr
 * @package Abandoned-Cart-Pro-for-WooCommerce/Admin/Settings
 * @since 6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( ! class_exists( 'Wcap_Add_Cart_Popup_Modal' ) ) {

	/**
	 * It will fetch the Add to cart data, generate and populate data in the modal.
	 *
	 * @since 6.0
	 */
	class Wcap_Add_Cart_Popup_Modal {

		/**
		 * It will Save the setting on the add to cart modal settings page.
		 *
		 * @since 6.0
		 */
		public static function wcap_add_to_cart_popup_save_settings() {
	        check_ajax_referer( 'wcap_settings_nonce', 'security' );
	        if ( ! current_user_can( 'manage_options' ) ) {
	            wp_send_json_error( 'Unauthorized', 403 );
	            wp_die();
	        }

			// Rules.
			$rules = array();
			$message = array();
			$rule_value = array();
			$rules_list = json_decode(stripslashes($_POST['wcap_rule_type_']));//phpcs:disable
			foreach ( $rules_list as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification
					// Get the id.
					$id          = $key;
					$rule_type   = $value->rule_type;
					$rule_cond   = $value->rule_condition;
					$rule_value = $value->rule_value;

					if ( is_array( $rule_value ) && count( $rule_value ) > 0 ) {
						$rules[] = array(
							'rule_type'      => $rule_type,
							'rule_condition' => $rule_cond,
							'rule_value'     => $rule_value,
						);
					}
			}
			// Front end Settings.
			$frontend_settings = array(
				'wcap_heading_section_text_email'    => isset( $_POST['wcap_heading_section_text_email'] ) ? stripslashes( sanitize_text_field( wp_unslash( $_POST['wcap_heading_section_text_email'] ) ) ) : 'Please enter your email', // phpcs:ignore WordPress.Security.NonceVerification
				'wcap_heading_section_text_image'    => isset( $_POST['wcap_heading_section_text_image'] ) ? stripslashes( sanitize_text_field( wp_unslash( $_POST['wcap_heading_section_text_image'] ) ) ) : 'popup-icon.svg', // phpcs:ignore WordPress.Security.NonceVerification
				'wcap_heading_section_text_image_ei' => isset( $_POST['wcap_heading_section_text_image_ei'] ) ? stripslashes( sanitize_text_field( wp_unslash( $_POST['wcap_heading_section_text_image_ei'] ) ) ) : 'popup-icon-1.svg', // phpcs:ignore WordPress.Security.NonceVerification
				'wcap_popup_heading_color_picker'    => isset( $_POST['wcap_popup_heading_color_picker'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_popup_heading_color_picker'] ) ) : '#737f97', // phpcs:ignore WordPress.Security.NonceVerification
				'wcap_text_section_text'             => isset( $_POST['wcap_text_section_text'] ) ? stripslashes( sanitize_text_field( wp_unslash( $_POST['wcap_text_section_text'] ) ) ) : 'To add this item to your cart, please enter your email address.', // phpcs:ignore WordPress.Security.NonceVerification
				'wcap_popup_text_color_picker'       => isset( $_POST['wcap_popup_text_color_picker'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_popup_text_color_picker'] ) ) : '#bbc9d2', // phpcs:ignore WordPress.Security.NonceVerification
				'wcap_email_placeholder_section_input_text' => isset( $_POST['wcap_email_placeholder_section_input_text'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_email_placeholder_section_input_text'] ) ) : 'Email Address', // phpcs:ignore WordPress.Security.NonceVerification
				'wcap_button_section_input_text'     => isset( $_POST['wcap_button_section_input_text'] ) ? stripslashes( sanitize_text_field( wp_unslash( $_POST['wcap_button_section_input_text'] ) ) ) : 'Add to Cart', // phpcs:ignore WordPress.Security.NonceVerification
				'wcap_button_color_picker'           => isset( $_POST['wcap_button_color_picker'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_button_color_picker'] ) ) : '#0085ba', // phpcs:ignore WordPress.Security.NonceVerification
				'wcap_button_text_color_picker'      => isset( $_POST['wcap_button_text_color_picker'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_button_text_color_picker'] ) ) : '#ffffff', // phpcs:ignore WordPress.Security.NonceVerification
				'wcap_non_mandatory_text'            => isset( $_POST['wcap_non_mandatory_text'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_non_mandatory_text'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification
				'wcap_atc_mandatory_email'           => isset( $_POST['wcap_atc_mandatory_email'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_atc_mandatory_email'] ) ) : 'off', // phpcs:ignore WordPress.Security.NonceVerification
				'wcap_atc_capture_phone'             => isset( $_POST['wcap_atc_capture_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_atc_capture_phone'] ) ) : 'off', // phpcs:ignore WordPress.Security.NonceVerification
				'wcap_atc_phone_placeholder'         => isset( $_POST['wcap_atc_phone_placeholder'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_atc_phone_placeholder'] ) ) : 'Phone number (e.g. +19876543210)', // phpcs:ignore WordPress.Security.NonceVerification
				'wcap_switch_atc_phone_mandatory'    => isset( $_POST['wcap_switch_atc_phone_mandatory'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_switch_atc_phone_mandatory'] ) ) : 'off', // phpcs:ignore WordPress.Security.NonceVerification
			);
			// Coupon Settings.
			$coupon_settings = array(
				'wcap_atc_auto_apply_coupon_enabled' => isset( $_POST['wcap_auto_apply_coupons_atc'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_auto_apply_coupons_atc'] ) ) : 'off', // phpcs:ignore WordPress.Security.NonceVerification
				'wcap_atc_coupon_type'               => isset( $_POST['wcap_atc_coupon_type'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_atc_coupon_type'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification
				'wcap_atc_popup_coupon'              => isset( $_POST['coupon_ids_new'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_ids_new'] ) ) : 0, // phpcs:ignore WordPress.Security.NonceVerification
				'wcap_atc_discount_type'             => isset( $_POST['wcap_atc_discount_type'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_atc_discount_type'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification
				'wcap_atc_discount_amount'           => isset( $_POST['wcap_atc_discount_amount'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_atc_discount_amount'] ) ) : 0, // phpcs:ignore WordPress.Security.NonceVerification
				'wcap_atc_coupon_free_shipping'      => isset( $_POST['wcap_atc_coupon_free_shipping'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_atc_coupon_free_shipping'] ) ) : 'off', // phpcs:ignore WordPress.Security.NonceVerification
				'wcap_atc_popup_coupon_validity'     => isset( $_POST['wcap_atc_coupon_validity'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_atc_coupon_validity'] ) ) : 0, // phpcs:ignore WordPress.Security.NonceVerification
				'wcap_countdown_cart'                => isset( $_POST['wcap_countdown_timer_cart'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_countdown_timer_cart'] ) ) : 'on', // phpcs:ignore WordPress.Security.NonceVerification
				'wcap_countdown_timer_msg'           => isset( $_POST['wcap_countdown_msg'] ) ? $_POST['wcap_countdown_msg'] : '', // phpcs:ignore
				'wcap_countdown_msg_expired'         => isset( $_POST['wcap_countdown_msg_expired'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_countdown_msg_expired'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification
			);

			// Quick Checkout Settings.
			$quick_checkout_settings = array(
				'wcap_quick_ck_force_user_to_checkout'     => isset( $_POST['wcap_quick_ck_force_user_to_checkout'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_quick_ck_force_user_to_checkout'] ) ) : 'off', // phpcs:ignore WordPress.Security.NonceVerification
				'wcap_enable_ei_for_registered_users'      => isset( $_POST['wcap_enable_ei_for_registered_users'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_enable_ei_for_registered_users'] ) ) : 'on', // phpcs:ignore WordPress.Security.NonceVerification
				'wcap_quick_ck_modal_heading'              => isset( $_POST['wcap_quick_ck_heading_section_text_email'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_quick_ck_heading_section_text_email'] ) ) : __( 'We are sad to see you leave', 'woocommerce-ac' ), // phpcs:ignore WordPress.Security.NonceVerification
				'wcap_quick_ck_popup_heading_color_picker' => isset( $_POST['wcap_quick_ck_popup_heading_color_picker'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_quick_ck_popup_heading_color_picker'] ) ) : '#737f97', // phpcs:ignore WordPress.Security.NonceVerification
				'wcap_quick_ck_modal_text'                 => isset( $_POST['wcap_quick_ck_text_section_text'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_quick_ck_text_section_text'] ) ) : __( 'There are some items in your cart. These will not last long. Please proceed to checkout to complete the purchase.', 'woocommerce-ac' ), // phpcs:ignore WordPress.Security.NonceVerification
				'wcap_quick_ck_popup_text_color_picker'    => isset( $_POST['wcap_quick_ck_popup_text_color_picker'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_quick_ck_popup_text_color_picker'] ) ) : '#bbc9d2', // phpcs:ignore WordPress.Security.NonceVerification
				'wcap_quick_ck_link_text'                  => isset( $_POST['wcap_quick_ck_button_section_input_text'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_quick_ck_button_section_input_text'] ) ) : __( 'Complete my order!', 'woocommerce-ac' ), // phpcs:ignore WordPress.Security.NonceVerification
				'wcap_quick_ck_button_color_picker'        => isset( $_POST['wcap_quick_ck_button_color_picker'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_quick_ck_button_color_picker'] ) ) : '#0085ba', // phpcs:ignore WordPress.Security.NonceVerification
				'wcap_quick_ck_button_text_color_picker'   => isset( $_POST['wcap_quick_ck_button_text_color_picker'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_quick_ck_button_text_color_picker'] ) ) : '#ffffff', // phpcs:ignore WordPress.Security.NonceVerification
				'wcap_quick_ck_redirect_to'                => isset( $_POST['wcap_quick_ck_redirect_to'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_quick_ck_redirect_to'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification
			);

			$template_name = isset( $_POST['wcap_template_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_template_name'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			$match_rules   = isset( $_POST['wcap_match_rules'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_match_rules'] ) ) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification
			$template_type = isset( $_POST['wcap_template_type'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_template_type'] ) ) : 'atc'; // phpcs:ignore WordPress.Security.NonceVerification

			$content = array(
				'name'                    => $template_name,
				'match_rules'             => $match_rules,
				'popup_type'              => $template_type,
				'rules'                   => wp_json_encode( $rules ),
				'frontend_settings'       => wp_json_encode( $frontend_settings ),
				'coupon_settings'         => wp_json_encode( $coupon_settings ),
				'quick_checkout_settings' => wp_json_encode( $quick_checkout_settings ),
			);

			if ( isset( $_POST['template_id'], $_POST['save_mode'] ) && 0 < $_POST['template_id'] && 'update' === $_POST['save_mode'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				self::wcap_update_atc_template( sanitize_text_field( wp_unslash( $_POST['template_id'] ) ), $content ); // phpcs:ignore WordPress.Security.NonceVerification
			} else {
				self::wcap_insert_atc_template( $content );
			}
			$message['success'] = 'The popup template has been successfully saved.';
			// Delete the popup template cache for the front end.
			if ( 'atc' === $template_type ) {
				delete_option( 'wcap_atc_templates' );
			} else if ( 'exit_intent' === $template_type ) {
				delete_option( 'wcap_ei_templates' );
			}
			do_action( 'wcap_save_atc_settings' );

			wp_send_json($message);
		}

		/**
		 * Update ATC template.
		 *
		 * @param int   $id - ATC Template ID.
		 * @param array $content - Template Content.
		 */
		public static function wcap_update_atc_template( $id, $content ) {
			global $wpdb;

			$wpdb->update( // phpcs:ignore
				WCAP_ATC_RULES_TABLE,
				$content,
				array(
					'id' => $id,
				)
			);
			return $id;
		}

		/**
		 * Insert new ATC Template.
		 *
		 * @param array $content - Template content.
		 */
		public static function wcap_insert_atc_template( $content ) {
			global $wpdb;

			$wpdb->insert( // phpcs:ignore
				WCAP_ATC_RULES_TABLE,
				$content
			);
			return $wpdb->insert_id;
		}
		/**
		 * Insert new Image in ATC Template.
		 */
		public static function wcap_add_to_cart_popup_upload_files() {
			if ( ! isset( $_POST['atc_add_edit_nonce'] ) || ! wp_verify_nonce( $_POST['atc_add_edit_nonce'], 'atc_upload_image' ) ) {
				wp_send_json( 'Invalid nonce' );
				wp_die();
			}
			if ( isset( $_FILES['wcap_file'] ) && isset( $_FILES['wcap_file']['tmp_name'] ) && isset( $_FILES['wcap_file']['name'] ) ) {
				$file_name     = basename( $_FILES['wcap_file']['name'] );
				$filetype      = wp_check_filetype( $file_name );
				$allowed_types = array( 'jpg', 'jpeg', 'png', 'gif' );

				if ( ! in_array( strtolower( $filetype['ext'] ), $allowed_types, true ) ) {
					wp_send_json( 'Invalid file type' );
					wp_die();
				}
				if ( move_uploaded_file( sanitize_text_field( $_FILES['wcap_file']['tmp_name'] ), WCAP_WP_UPLOADS_PATH . sanitize_text_field( $_FILES['wcap_file']['name'] ) ) ) {// phpcs:ignore WordPress.Security.NonceVerification
					wp_send_json( 'success' );
				} else {
					wp_send_json( 'fail' );
				}
			} else {
				wp_send_json( 'fail' );
			}
		}
		/**
		 * Delete Image From ATC Template.
		 */
		public static function wcap_add_to_cart_popup_delete_files() {
			if ( ! isset( $_POST['atc_add_edit_nonce'] ) || ! wp_verify_nonce( $_POST['atc_add_edit_nonce'], 'atc_upload_image' ) ) {
				wp_send_json( 'Invalid nonce' );
				wp_die();
			}
			$file_name = sanitize_file_name( $_POST['wcap_image_path'] );// phpcs:ignore WordPress.Security.NonceVerification
    		$path      = WCAP_WP_UPLOADS_PATH . $file_name;
			if ( file_exists( $path ) && strpos( realpath( $path ), WCAP_WP_UPLOADS_PATH ) === 0 ) {
        		unlink( $path );
        		wp_send_json_success( 'File deleted successfully' );
    		} else {
        		wp_send_json_error( 'File not found or invalid path' );
    		}
		}
	}
}
