<?php
/**
 * It will display the email template listing.
 * @author   Tyche Softwares
 * @package Abandoned-Cart-Pro-for-WooCommerce/Admin/SMS Reminders Class
 * @since 7.9
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( !class_exists('Wcap_WhatsApp' ) ) {

	/**
	 * It will display the SMS template listing, also it will add, update & delete the SMS template in the database.
	 * @since 7.9
	 */
	class Wcap_WhatsApp {

		/**
		 * Saves the WhatsApp Templates
		 * Called via AJAX
		 */
		static function wcap_save_whatsapp_template() {
			check_ajax_referer( 'wcap_settings_nonce', 'security' );
		    if ( ! current_user_can( 'manage_options' ) ) {
		        wp_send_json_error( 'Unauthorized', 403 );
		        wp_die();
		    }
			global $wpdb;

			$id               = isset( $_REQUEST['template_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['template_id'] ) ) : 0; // phpcs:ignore
			$template_details = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM " . WCAP_NOTIFICATION_TEMPLATES_TABLE . " WHERE ID = %d", $id ) );//phpcs:ignore
			$template_present = ( isset( $template_details[0]->ID ) ) ? true : false;

			$whatsapp_body = isset( $_POST['body'] ) ? sanitize_text_field( wp_unslash($_POST['body'] ) ) : '';
			$frequency     = isset( $_POST['frequency'] ) ? sanitize_text_field( wp_unslash($_POST['frequency'] ) ) : '';
			$active        = isset( $_POST['is_active'] ) ? sanitize_text_field( wp_unslash($_POST['is_active'] ) ) : '';
			$coupon_code   = isset( $_POST['coupon_code'] ) ? sanitize_text_field( wp_unslash($_POST['coupon_code'] ) ) : '';
			$template_name = isset( $_POST['template_name'] ) ? sanitize_text_field( wp_unslash($_POST['template_name'] ) ) : '';
			$data          = array();

			// phpcs:enable
			// if yes, update the data.
					if ( $template_present ) {
						$temp_id = wcap_update_notifications(
							$id,
							$whatsapp_body,
							$frequency,
							$active,
							$coupon_code,
							$subject = '',
							$template_name
						);
					} else { // else add a new record.

						$temp_id = wcap_insert_notifications(
							$whatsapp_body,
							'whatsapp',
							$active,
							$frequency,
							$coupon_code,
							$default_template = '',
							$subject = '',
							$template_name
						);
						$insert  = true;						
					}			

			$data = Wcap_Common::wcap_get_whatsapp_templates( true );
			delete_option( 'wcap_whatsapp_token_notice' );

			if ( $temp_id ) {
				$data['status'] = 'success';
			}
			wp_send_json( $data );

		}
	} // end of class
	$wcap_whatsapp = new Wcap_WhatsApp();
}
