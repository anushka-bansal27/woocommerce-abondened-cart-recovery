<?php
/**
 * It will display the email template listing.
 * @author   Tyche Softwares
 * @package Abandoned-Cart-Pro-for-WooCommerce/Admin/SMS Reminders Class
 * @since 7.9
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( !class_exists('Wcap_SMS' ) ) {

	/**
	 * It will display the SMS template listing, also it will add, update & delete the SMS template in the database.
	 * @since 7.9
	 */
	class Wcap_SMS{
	
		public function __construct() {
		}

		/**
		 * Returns the new ID for the SMS
		 * @since 7.9
		 */
		/*static function get_new_id() {
			
			global $wpdb;
			
			$max_id = $wpdb->get_results( "SELECT MAX(ID) as maxID FROM " . WCAP_NOTIFICATION_TEMPLATES_TABLE );
			$new_id = isset( $max_id[0]->maxID ) ? $max_id[0]->maxID + 1 : 1;
			
			return $new_id;
		}*/
		
		/**
		 * Deletes the SMS Template when delete action is executed for 
		 * a single SMS template.
		 * Called via AJAX
		 * @since 7.9
		 */
		public static function wcap_delete_sms() {

			$template_id = isset( $_POST[ 'template_id' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'template_id' ] ) ) : 0;// phpcs:disable

			if ( $template_id > 0 ) {
				self:: wcap_delete_template_data( $template_id );

				global $wpdb;
				$template_details = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM " . WCAP_NOTIFICATION_TEMPLATES_TABLE . " WHERE ID = %d", $template_id ) );//phpcs:ignore
				$template_present = ( isset( $template_details[0]->ID ) ) ? true : false;
				if ( ! $template_present ) {
					wp_send_json( array( 'status' => 'success' ) );
				}

			}
			die();
		}

		/**
		 * Delete the SMS Template from the DB & its meta data
		 * @since 7.9
		 */
		static function wcap_delete_template_data( $template_id ) {
			global $wpdb;
			// delete the template from the parent table.
			$wpdb->delete( WCAP_NOTIFICATION_TEMPLATES_TABLE, array( 'id' => $template_id ) );// phpcs:ignore
		}

		/**
		 * Saves the SMS Templates
		 * Called via AJAX
		 * @since 8.2
		 */
		static function wcap_save_sms_template() {
			check_ajax_referer( 'wcap_settings_nonce', 'security' );
		    if ( ! current_user_can( 'manage_options' ) ) {
		        wp_send_json_error( 'Unauthorized', 403 );
		        wp_die();
		    }
			global $wpdb;

			$id               = isset( $_REQUEST['template_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['template_id'] ) ) : 0; // phpcs:ignore
			$template_details = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM " . WCAP_NOTIFICATION_TEMPLATES_TABLE . " WHERE ID = %d", $id ) );//phpcs:ignore
			$template_present = ( isset( $template_details[0]->ID ) ) ? true : false;

			$sms_body      = isset( $_POST['body'] ) ? sanitize_text_field( wp_unslash($_POST['body'] ) ) : '';
			$frequency     = isset( $_POST['frequency'] ) ? sanitize_text_field( wp_unslash($_POST['frequency'] ) ) : '';
			$active        = isset( $_POST['is_active'] ) ? sanitize_text_field( wp_unslash($_POST['is_active'] ) ) : '';
			$coupon_code   = isset( $_POST['coupon_code'] ) ? sanitize_text_field( wp_unslash($_POST['coupon_code'] ) ) : '';
			$template_name = isset( $_POST['template_name'] ) ? sanitize_text_field( wp_unslash($_POST['template_name'] ) ) : '';
			$data = array();
			// phpcs:enable
			// if yes, update the data.
					if ( $template_present ) {
						$temp_id = wcap_update_notifications(
							$id,
							$sms_body,
							$frequency,
							$active,
							$coupon_code,
							$subject = '',
							$template_name
						);
					} else { // else add a new record.

						$temp_id = wcap_insert_notifications(
							$sms_body,
							'sms',
							$active,
							$frequency,
							$coupon_code,
							$default_template = '',
							$subject = '',
							$template_name
						);
						$insert  = true;						
					}			

			$data = Wcap_Common::wcap_get_sms_templates( true );
			if ( $temp_id ) {
				$data['status'] = 'success';
			}
			wp_send_json( $data );

		}
	} // end of class
	$wcap_sms = new WCap_SMS();
}
