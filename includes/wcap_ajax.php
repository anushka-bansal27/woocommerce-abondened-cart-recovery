<?php
/**
 *
 * It contain all the functions for ajax call.
 *
 * @author  Tyche Softwares
 * @package Abandoned-Cart-Pro-for-WooCommerce/Ajax-Functions
 * @since   5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Wcap_Ajax' ) ) {

	/**
	 * This class contain all the ajax function used in the plugin.
	 */
	class Wcap_Ajax {

		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'wcap_import_lite_data' ), 10 );
		}

		/**
		 * This function will import the data of the lite version. Like abandoned carts, email templates, settings, sent history.
		 *
		 * @globals mixed $wpdb
		 * @since: 8.0
		 */
		public static function wcap_import_lite_data() {

			global $wpdb;

			$wcap_lite_data_imported     = get_option( 'wcap_lite_data_imported', '' );
			$ac_lite_cart_abandoned_time = get_option( 'ac_lite_cart_abandoned_time', 'not_available' );

			if ( 'yes' !== $wcap_lite_data_imported && 'not_available' !== $ac_lite_cart_abandoned_time ) {
				$import_cart_data       = true;
				$import_email_templates = true;
				$import_settings        = true;
				if ( $import_cart_data ) {//phpcs:disable
					$wcap_abandoned_cart_history_data_query = 'INSERT INTO `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` ( `id`,`user_id`, `abandoned_cart_info`, `abandoned_cart_time`, `cart_ignored`, `recovered_cart`, `user_type`, `unsubscribe_link`, `session_id` ) SELECT `id`,`user_id`, `abandoned_cart_info`, `abandoned_cart_time`, `cart_ignored`, `recovered_cart`, `user_type`, `unsubscribe_link`, `session_id` FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` ON DUPLICATE KEY UPDATE
						`id` = VALUES (`id`),  
						`user_id` = VALUES (`user_id`),
						`abandoned_cart_info` = VALUES (`abandoned_cart_info`),
						`abandoned_cart_time` = VALUES (`abandoned_cart_time`),
						`cart_ignored` = VALUES (`cart_ignored`),
						`recovered_cart` = VALUES (`recovered_cart`),
						`user_type` = VALUES (`user_type`),
						`unsubscribe_link` = VALUES (`unsubscribe_link`),
						`session_id` = VALUES (`session_id`)
						';
					$wpdb->query( $wcap_abandoned_cart_history_data_query ); // phpcs:ignore

					$wcap_abandoned_cart_guest_history_data_query = 'INSERT INTO `' . WCAP_GUEST_CART_HISTORY_TABLE . '` ( `id`,`billing_first_name`, `billing_last_name`, `billing_company_name`, `billing_address_1`, `billing_address_2`, `billing_city`, `billing_county`, `billing_zipcode`, `email_id`, `phone`, `ship_to_billing`, `order_notes`, `shipping_first_name`, `shipping_last_name`, `shipping_company_name`, `shipping_address_1`, `shipping_address_2`, `shipping_city`, `shipping_county`, `shipping_zipcode`, `shipping_charges` ) 
						SELECT `id`, `billing_first_name`, `billing_last_name`, `billing_company_name`, `billing_address_1`, `billing_address_2`, `billing_city`, `billing_county`, `billing_zipcode`, `email_id`, `phone`, `ship_to_billing`, `order_notes`, `shipping_first_name`, `shipping_last_name`, `shipping_company_name`, `shipping_address_1`, `shipping_address_2`, `shipping_city`, `shipping_county`, `shipping_zipcode`, `shipping_charges` FROM `' . $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite` ON DUPLICATE KEY UPDATE
							`id` = VALUES (`id`),
							`billing_first_name` = VALUES (`billing_first_name`), 
							`billing_last_name` = VALUES (`billing_last_name`), 
							`phone` = VALUES (`phone`), 
							`email_id` = VALUES (`email_id`)
						';
					$wpdb->query( $wcap_abandoned_cart_guest_history_data_query ); // phpcs:ignore
				}

				$wcap_is_settings_checked = 1;
				if ( $import_settings ) {

					$wcap_get_lite_cut_off_time = get_option( 'ac_lite_cart_abandoned_time' );
					if ( isset( $wcap_get_lite_cut_off_time ) && '' != $wcap_get_lite_cut_off_time ) {
						update_option( 'ac_cart_abandoned_time', $wcap_get_lite_cut_off_time );
						update_option( 'ac_cart_abandoned_time_guest', $wcap_get_lite_cut_off_time );
					}

					$wcap_get_lite_admin_recovery = get_option( 'ac_lite_email_admin_on_recovery' );
					if ( ( $wcap_get_lite_admin_recovery == 'on' || '' == $wcap_get_lite_admin_recovery ) && false !== $wcap_get_lite_admin_recovery ) {

						update_option( 'ac_email_admin_on_recovery', $wcap_get_lite_admin_recovery );
					}

					$wcap_get_lite_visitors = get_option( 'ac_lite_track_guest_cart_from_cart_page' );
					if ( isset( $wcap_get_lite_visitors ) && ( $wcap_get_lite_visitors == 'on' || '' == $wcap_get_lite_visitors )
						&& false !== $wcap_get_lite_admin_recovery ) {
						update_option( 'ac_track_guest_cart_from_cart_page', $wcap_get_lite_visitors );
					}

					$wcal_from_name = get_option( 'wcal_from_name' );
					if ( isset( $wcal_from_name ) && '' != $wcal_from_name ) {
						update_option( 'wcap_from_name', $wcal_from_name );
					}

					$wcal_from_email = get_option( 'wcal_from_email' );
					if ( isset( $wcal_from_email ) && '' != $wcal_from_email ) {
						update_option( 'wcap_from_email', $wcal_from_email );
					}

					$wcal_reply_email = get_option( 'wcal_reply_email' );
					if ( isset( $wcal_reply_email ) && '' != $wcal_reply_email ) {
						update_option( 'wcap_reply_email', $wcal_reply_email );
					}

					$delete_coupons = get_option( 'wcal_delete_coupon_data', '' );
					update_option( 'wcap_delete_coupon_data', $delete_coupons );
				}

				$wcap_get_all_templates = 'SELECT id from `' . $wpdb->prefix . "ac_email_templates` WHERE `default_template` = '1' ";
				if ( $import_email_templates ) {

					$wcap_replace_with_merge_code = addslashes(
					    '<table border="0" width="100%" cellspacing="0" cellpadding="0" style="border-collapse: collapse; font-family: Arial, sans-serif;">
					        <tbody>
					            <tr>
					                <td colspan="5" style="text-align:center; font-size:18px; font-weight:bold; padding:10px 0;">
					                    Cart Details
					                </td>
					            </tr>

					            <tr>
					                <td style="background-color:#666666; color:#ffffff; text-align:center; font-size:13px; padding:8px;">Item</td>
					                <td style="background-color:#666666; color:#ffffff; text-align:center; font-size:13px; padding:8px;">Product</td>
					                <td style="background-color:#666666; color:#ffffff; text-align:center; font-size:13px; padding:8px;">Price</td>
					                <td style="background-color:#666666; color:#ffffff; text-align:center; font-size:13px; padding:8px;">Qty</td>
					                <td style="background-color:#666666; color:#ffffff; text-align:center; font-size:13px; padding:8px;">Total</td>
					            </tr>
					            <tr style="background-color:#f4f5f4;">
					                <td align="center" style="padding:8px;">{{item.image}}</td>
					                <td align="center" style="padding:8px;">{{item.name}}</td>
					                <td align="center" style="padding:8px;">{{item.price}}</td>
					                <td align="center" style="padding:8px;">{{item.quantity}}</td>
					                <td align="center" style="padding:8px;">{{item.subtotal}}</td>
					            </tr>
					            <tr>
					                <td colspan="3">&nbsp;</td>
					                <td style="text-align:right; font-weight:bold; padding:8px;">Cart Total:</td>
					                <td style="text-align:center; padding:8px;">{{cart.total}}</td>
					            </tr>
					        </tbody>
					    </table>
					    <br><br>'
					);

					/*$wcap_update_templates_mergecode = 'UPDATE `' . $wpdb->prefix . 'ac_email_templates_lite`
						SET `body` = replace( `body`, "{{products.cart}}", "' . $wcap_replace_with_merge_code . '" ) ';
					$wpdb->query( $wcap_update_templates_mergecode );*/ // phpcs:ignore

					$rules   = array();
					$rules[] = array(
						'rule_type'      => 'send_to',
						'rule_condition' => 'includes',
						'rule_value'     => array( 'wcap_email_customer' ),
					);
					$rules   = json_encode( $rules );

					// Get all the Templates & Insert the data.
					$all_lite_templates = $wpdb->get_results( 'SELECT * FROM `' . $wpdb->prefix . 'ac_email_templates_lite`' );
					if ( is_array( $all_lite_templates ) && count( $all_lite_templates ) > 0 ) {
						foreach ( $all_lite_templates as $template_data ) {
							$generate_unique_coupons = isset( $template_data->generate_unique_coupon_code ) ? $template_data->generate_unique_coupon_code : '0';
							$discount_type           = isset( $template_data->discount_type ) ? $template_data->discount_type : 'percent';
							$discount                = isset( $template_data->discount ) ? $template_data->discount : 0;
							$discount_shipping       = isset( $template_data->discount_shipping ) ? $template_data->discount_shipping : 'off';
							$discount_expiry         = isset( $template_data->discount_expiry ) ? $template_data->discount_expiry : '7-days';
							$individual_use          = isset( $template_data->individual_use ) ? $template_data->individual_use : '1';
							$coupon_code             = isset( $template_data->coupon_code ) ? $template_data->coupon_code : '';
							$body_content = str_replace(
							    '{{products.cart}}',
							    stripslashes( $wcap_replace_with_merge_code ),
							    $template_data->body
							);
							$wpdb->insert(
								WCAP_NOTIFICATION_TEMPLATES_TABLE,
								array(
									'notification_type' => 'email',
									'email_type'        => 'reminder',
									'subject'           => stripslashes( $template_data->subject ),
									'body'              => stripslashes( $body_content ),
									'is_active'         => '0',
									'frequency'         => $template_data->frequency,
									'day_or_hour'       => $template_data->day_or_hour,
									'template_name'     => $template_data->template_name,
									'is_wc_template'    => $template_data->is_wc_template,
									'default_template'  => $template_data->default_template,
									'wc_email_header'   => $template_data->wc_email_header,
									'match_rules'       => 'all',
									'rules'             => $rules,
									'generate_unique_coupon_code' => $generate_unique_coupons,
									'discount'          => $discount,
									'discount_type'     => $discount_type,
									'discount_shipping' => $discount_shipping,
									'discount_expiry'   => $discount_expiry,
									'individual_use'    => $individual_use,
									'coupon_code'       => $coupon_code,
									'activated_time'    => '',
								)
							);
						}
					}
				}

				// Both cart & email template data is being imported, so pls import the sent history table data.
				if ( $import_cart_data && $import_email_templates ) {

					$wpdb->query(
						'INSERT INTO `' . WCAP_EMAIL_SENT_HISTORY_TABLE . '` ( `id`, `template_id`, `cart_id`, `sent_time`, `sent_notification_contact` ) SELECT `id`,`template_id`, `abandoned_order_id`, `sent_time`, `sent_email_id` AS `sent_notification_contact` FROM `' . $wpdb->prefix . 'ac_sent_history_lite` ON DUPLICATE KEY UPDATE
						`id` = VALUES (`id`),  
						`template_id` = VALUES (`template_id`),
						`cart_id` = VALUES (`cart_id`),
						`sent_time` = VALUES (`sent_time`),
						`sent_notification_contact` = VALUES (`sent_notification_contact`)'
					);
				}
				$wcap_lite_plugin_path = ( dirname( dirname( WCAP_PLUGIN_FILE ) ) ) . '/woocommerce-abandoned-cart/woocommerce-ac.php';

				deactivate_plugins( $wcap_lite_plugin_path );

				// Add option which button is clicked for the record.
				update_option( 'wcap_lite_data_imported', 'yes' );
			}
		}

		/**
		 * This ajax create the preview email content for the without WooCommerce setting.
		 *
		 * @hook wp_ajax_wcap_preview_email
		 * @since: 7.0
		 */
		public static function wcap_preview_email() {
			$wcap_email                    = convert_smilies( html_entity_decode( $_POST ['body_email_preview'] ) );
			$wcap_email_body_strip_slashes = stripslashes( $wcap_email );
			$body_email_preview            = Wcap_Common::wcap_replace_email_body_merge_code( $wcap_email_body_strip_slashes );
			$body_email_preview            = Wcap_Common::wcap_inline_email_css( $body_email_preview );
			$wcap_include_tax_setting      = get_option( 'woocommerce_calc_taxes' );
			$wcap_add_tax_note             = '';

			if ( isset( $wcap_include_tax_setting ) && 'yes' === $wcap_include_tax_setting ) {
				$wcap_add_tax_note = '<strong>Note</strong>: Tax amount is hardcoded in the preview. It will be replaced with real tax amount when reminder email will be sent to customers.';
			}

			$wcap_footer_fields = ' <div id = "wcap_tax_note_preview" class = "wcap_tax_note_preview"> ' . $wcap_add_tax_note . ' </div>
                                    <tr>
                                        <th>
                                            <label for="woocommerce_ac_email_preview">
                                                <b>Send a test Email to:</b>
                                            </label>
                                        </th>
                                        <td>
                                            <input type="text" id="send_test_email_preview" name="send_test_email_preview" class="regular-text 22 send_test_email_preview" @click="wcap_send_test_email( $event )" >
                                            <input type="button" value="Send a test Email" id="preview_test_email"  style="margin-left:9px;" class= "preview_test_email button-primary trietary-btn reverse" onclick="javascript:void(0);" data-wcap-email-type="normal_preview">
                                            <span id="preview_test_email_sent_msg" style="display:none;"></span>
                                        </td>
                                    </tr>';
			$wcap_email_body    = '
                                <div class="wcap-modal__body">
                                    <div class="wcap-modal__body-inner">' . $body_email_preview . ' </div>
                                </div>
                                <div class="wcap-modal__footer">' . $wcap_footer_fields . ' </div>';
			echo $wcap_email_body;
			wp_die();

		}

		/**
		 * This ajax create the preview email content for the WooCommerce setting.
		 *
		 * @hook wp_ajax_wcap_preview_wc_email
		 * @globals mixed $woocommerce
		 * @since: 7.0
		 */
		public static function wcap_preview_wc_email() {
			global $woocommerce;

			$wcap_email                    = convert_smilies( html_entity_decode( $_POST ['body_email_preview'] ) );
			$wcap_email_body_strip_slashes = stripslashes( $wcap_email );
			$body_email_preview            = Wcap_Common::wcap_replace_email_body_merge_code( $wcap_email_body_strip_slashes );
			$body_email_preview            = Wcap_Common::wcap_inline_email_css( $body_email_preview );
			$wcap_message                  = '';

			if ( version_compare( WC()->version, '2.3', '<' ) ) {
				global $email_heading;
				$wcap_mailer        = WC()->mailer();
				$wcap_email_heading = stripslashes( $_POST ['wc_template_header'] );
				$wcap_message       = $wcap_mailer->wrap_message( $wcap_email_heading, $body_email_preview );
			} else {

				$wcap_mailer        = WC()->mailer();
				$wcap_email_heading = stripslashes( $_POST ['wc_template_header'] );
				$wcap_email         = new WC_Email();
				//stripped media queries from the email body.
				$body_email_preview = preg_replace('#<style\b[^>]*>.*?</style>#is', '', $body_email_preview);
				$wcap_message       = $wcap_email->style_inline( $wcap_mailer->wrap_message( $wcap_email_heading, $body_email_preview ) );
			}
			$wcap_include_tax_setting = get_option( 'woocommerce_calc_taxes' );
			$wcap_add_tax_note        = '';
			if ( isset( $wcap_include_tax_setting ) && 'yes' == $wcap_include_tax_setting ) {
				$wcap_add_tax_note = '<strong>Note</strong>: Tax amount is hardcoded in the preview. It will be replaced with real tax amount when reminder email will be sent to customers.';
			}
			$wcap_footer_fields = ' <div id = "wcap_tax_note_preview" class = "wcap_tax_note_preview"> ' . $wcap_add_tax_note . ' </div>
                                    <tr>
                                        <th>
                                            <label for="woocommerce_ac_email_preview">
                                                <b>Send a test Email to:</b>
                                            </label>
                                        </th>
                                        <td>
                                            <input type="text" id="send_test_email_preview" name="send_test_email_preview" class="regular-text 22 send_test_email_preview"  @click="wcap_send_test_email( $event )" >
                                            <input type="button" value="Send a test Email" id="preview_test_email"   style="margin-left:9px;"  class= "preview_test_email button-primary trietary-btn reverse " onclick="javascript:void(0);" data-wcap-email-type="wc_preview">
                                            <span id="preview_test_email_sent_msg" style="display:none;"></span>
                                        </td>
                                    </tr>';
			$wcap_email_body    = '
                                <div class="wcap-modal__body">
                                    <div class="wcap-modal__body-inner">' . $wcap_message . ' </div>
                                </div>
                                <div class="wcap-modal__footer">' . $wcap_footer_fields . ' </div>';
			echo $wcap_email_body;
			wp_die();
		}

		/**
		 * This function check if the Add to Cart is enabled or not when we disabled the guest cart capturing.
		 *
		 * @hook wp_ajax_wcap_is_atc_enable
		 * @since: 7.0
		 */
		public static function wcap_is_atc_enable() {

			$wcap_get_atc_enabled = wcap_get_popup_active_status( 'atc' );

			// Delete the ATC cache for the front end.
			delete_option( 'wcap_atc_templates' );
			if ( ! $wcap_get_atc_enabled ) {
				echo 'off';
				wp_die();
			}

			if ( $wcap_get_atc_enabled ) {
				// Deactivate all ATC rules.
				global $wpdb;
				$wpdb->update(
					WCAP_ATC_RULES_TABLE,
					array(
						'is_active' => '0',
					),
					array(
						'is_active' => '1',
					)
				);

				wp_send_json(
					array(
						'error' => __( "The Enable Add to cart popup modal setting has been disabled. As the guest cart's are no longer captured !!", 'woocommerce-ac' ),
						'on'    => __( 'on', 'woocommerce-ac' ),
					)
				);
				wp_die();
			}
		}

		/**
		 * It will activate and deactivate the template from the template page.
		 *
		 * @hook wp_ajax_wcap_toggle_template_status
		 * @globals mixed $wpdb
		 * @since 4.8
		 */
		public static function wcap_toggle_template_status() {
			global $wpdb;
			$template_id             = $_POST['wcap_template_id'];
			$current_template_status = $_POST['current_state'];

			$template_type = isset( $_POST['template_type'] ) ? $_POST['template_type'] : 'emailtemplates';
			$active = ( 'on' == $current_template_status ) ? '1' : '0';
			// nosemgrep:audit.php.wp.security.sqli.input-in-sinks
			$wpdb->query( 'UPDATE `' . WCAP_NOTIFICATION_TEMPLATES_TABLE . "` SET is_active = '" . $active . "',activated_time = '" . current_time( 'timestamp' ) . "' WHERE id  = '" . $template_id . "'" );// nosemgrep:audit.php.wp.security.sqli.input-in-sinks
			wp_die();
		}

		/**
		 * It will reset all the default configuration of the Add To Cart modal.
		 *
		 * @hook wp_ajax_wcap_atc_reset_setting
		 * @since: 6.0
		 */
		public static function wcap_atc_reset_setting() {
			global $wpdb;

			$template_id = isset( $_POST['template_id'] ) ? sanitize_text_field( wp_unslash( $_POST['template_id'] ) ) : 0; // phpcs:ignore 

			if ( $template_id > 0 ) {
				$frontend_settings = array(
					'wcap_heading_section_text_email' => 'Please enter your email',
					'wcap_popup_heading_color_picker' => '#737f97',
					'wcap_text_section_text'          => 'To add this item to your cart, please enter your email address.',
					'wcap_popup_text_color_picker'    => '#bbc9d2',
					'wcap_email_placeholder_section_input_text' => 'Email address',
					'wcap_button_section_input_text'  => 'Add to Cart',
					'wcap_button_color_picker'        => '#0085ba',
					'wcap_button_text_color_picker'   => '#ffffff',
					'wcap_non_mandatory_text'         => 'No thanks',
					'wcap_atc_mandatory_email'        => 'on',
				);

				$is_active = 'off';

				// ATC Auto Apply Coupon settings.
				$coupon_settings = array(
					'wcap_atc_auto_apply_coupon_enabled' => 'off',
					'wcap_countdown_cart'                => 'on',
					'wcap_atc_popup_coupon'              => '',
					'wcap_countdown_timer_msg'           => 'Coupon <coupon_code> expires in <hh:mm:ss>. Avail it now.',
					'wcap_atc_popup_coupon_validity'     => '',
					'wcap_atc_coupon_type'               => '',
					'wcap_atc_discount_type'             => '',
					'wcap_atc_discount_amount'           => '',
					'wcap_atc_coupon_free_shipping'      => 'off',
				);

				$wpdb->update(
					WCAP_ATC_RULES_TABLE,
					array(
						'is_active'         => $is_active,
						'frontend_settings' => wp_json_encode( $frontend_settings ),
						'coupon_settings'   => wp_json_encode( $coupon_settings ),
					),
					array(
						'id' => $template_id,
					)
				);
				// Delete the ATC cache for the front end.
				delete_option( 'wcap_atc_templates' );
			}
		}

		/**
		 * We need to define the do_action for running the ajax on the shop page.
		 * Because the wp-ajax was not runing on the shop page.
		 * We have used the WC ajax so it can run on shop page.
		 *
		 * @hook init
		 * @since: 6.0
		 */
		public static function wcap_add_ajax_for_atc() {
			if ( isset( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], array( 'wcap_atc_store_guest_email', 'wcap_atc_stats_record' ) ) ) :
				do_action( 'wp_ajax_nopriv_' . $_REQUEST['action'] );
			endif;
		}

		/**
		 * Insert statistics record for ATC
		 *
		 * @since 8.10.0
		 */
		public static function wcap_atc_stats_record() {
			$action = isset( $_POST['stats_action'] ) ? sanitize_text_field( wp_unslash( $_POST['stats_action'] ) ) : '';

			if ( '' !== $action ) {
				global $wpdb;
				switch ( $action ) {
					case 'wcap_atc_opened':
						$insert_data = array(
							'event' => '0',
						);
						break;
					case 'wcap_atc_no_thanks':
						$insert_data = array(
							'event' => '2',
						);
						break;
					case 'wcap_atc_dismissed':
						$insert_data = array(
							'event' => '3',
						);
						break;
				}
				$insert_data['timestamp']     = current_time( 'timestamp' );
				$insert_data['template_type'] = 'atc';
				$insert_data['template_id']   = isset( $_POST['template_id'] ) ? sanitize_text_field( wp_unslash( $_POST['template_id'] ) ) : 0;
				$wcap_atc_dismissed           = apply_filters( 'wcap_atc_dismissed', false );

				$wpdb->insert(
					WCAP_AC_STATS,
					$insert_data
				);
				echo $wcap_atc_dismissed;
			}

			die();
		}

		/**
		 * We have used WC ajax because the wp-ajax was not runing on the shop page.
		 * When we run the wp-admin ajax, it was giving the 302 status of the ajax call.
		 *
		 * @hook wp_ajax_nopriv_wcap_atc_store_guest_email
		 * @globals mixed $wpdb
		 * @globals mixed $woocommerce
		 * @since: 6.0
		 */
		public static function wcap_atc_store_guest_email() {

			global $wpdb, $woocommerce;
			$wcap_guest_email        = sanitize_text_field( $_POST['wcap_atc_email'] );
			$current_user_ip_address = Wcap_Common::wcap_get_client_ip();

			$wcap_is_ip_restricted            = Wcap_Common::wcap_is_ip_restricted( $current_user_ip_address );
			$wcap_is_email_address_restricted = Wcap_Common::wcap_is_email_address_restricted( $wcap_guest_email );
			$wcap_is_domain_restricted        = Wcap_Common::wcap_is_domain_restricted( $wcap_guest_email );
			$wcap_current_user_country_code   = Wcap_Common::wcap_get_ip_info( $current_user_ip_address, 'countrycode' );
			$wcap_is_country_restricted       = Wcap_Common::wcap_is_country_restricted( $wcap_current_user_country_code );
			if ( false == $wcap_is_ip_restricted && false == $wcap_is_email_address_restricted && false == $wcap_is_domain_restricted && false == $wcap_is_country_restricted ) {
				$wcap_session_cookie = Wcap_Common::wcap_get_guest_session_key();
				$get_ac              = $wpdb->get_row( $wpdb->prepare( 'SELECT id, user_id, abandoned_cart_info FROM ' . WCAP_ABANDONED_CART_HISTORY_TABLE . ' WHERE session_id = %s', $wcap_session_cookie ) );
				if ( isset( $get_ac ) && count( get_object_vars( $get_ac ) ) > 0 && $get_ac->user_id > 0 ) { // If a record is present for the same session key, do not proceed further.
					return;
				}
				$wc_shipping_charges = '';
				if ( function_exists( 'WC' ) ) {
					$cart['cart']        = WC()->session->cart;
					$cart['cart_totals'] = WC()->session->cart_totals;
					if ( 'disabled' != get_option( 'woocommerce_ship_to_countries' ) ) {
						$wc_shipping_charges = WC()->cart->get_cart_shipping_total();
						// Extract the shipping amount
						$wc_shipping_charges = strip_tags( html_entity_decode( $wc_shipping_charges ) );
						$wc_shipping_charges = (float) filter_var( $wc_shipping_charges, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
					}
				} else {
					$cart['cart'] = $woocommerce->session->cart;
					if ( 'disabled' != get_option( 'woocommerce_ship_to_countries' ) ) {
						$wc_shipping_charges = $woocommerce->cart->get_cart_shipping_total();
					}
				}
				$default_sms_consent = 'on' === get_option( 'wcap_enable_sms_consent', '' ) && isset( $_POST['wcap_atc_phone'] ) ? false : true; // phpcs:ignore
				$consent             = isset( $_POST['wcap_sms_consent'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_sms_consent'] ) ) : $default_sms_consent; // phpcs:ignore
				$cart['sms_consent'] = $consent;
				wcap_set_cart_session( 'wcap_sms_consent', $consent );

				$whatsapp_consent = isset( $_POST['wcap_whatsapp_consent'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_whatsapp_consent'] ) ) : false; // phpcs:ignore
				$cart['whatsapp_consent'] = $whatsapp_consent;
				wcap_set_cart_session( 'wcap_whatsapp_consent', $whatsapp_consent );

				$cart_info = json_encode( $cart );
				$cart_info = str_replace( ' \"%title%\" ', '', $cart_info );

				$current_time = current_time( 'timestamp' );

				if ( function_exists( 'icl_register_string' ) ) {
					$current_user_lang = isset( $_SESSION['wpml_globalcart_language'] ) ? $_SESSION['wpml_globalcart_language'] : ICL_LANGUAGE_CODE;
				} else {
					$current_user_lang = 'en';
				}

				if ( function_exists( 'icl_object_id' ) ) {
					$cart_info = WCAP_CART_HISTORY_MODEL::add_wcml_currency( $cart_info );
				}
				if ( defined( 'WOOCOMMERCE_MULTICURRENCY_VERSION' ) ) {
					$cart_info = WCAP_CART_HISTORY_MODEL::add_wc_multicurrency( $cart_info );
				}

				if ( isset( $_POST['wcap_atc_user_action'] ) && 'yes' == $_POST['wcap_atc_user_action'] ) {
					$wcap_user_email_from_popup = sanitize_email( $_POST['wcap_atc_email'] );
					$wcap_user_phone_from_popup = isset( $_POST['wcap_atc_phone'] ) ? sanitize_text_field( $_POST['wcap_atc_phone'] ) : '';
					wcap_set_cart_session( 'wcap_guest_email', $wcap_user_email_from_popup );
					wcap_set_cart_session( 'wcap_guest_phone', $wcap_user_phone_from_popup );

					$wcap_insert_guest_email = array(
						'email_id'           => $wcap_user_email_from_popup,
						'billing_first_name' => '',
						'billing_last_name'  => '',
						'phone'              => $wcap_user_phone_from_popup,
						'shipping_charges'   => $wc_shipping_charges,
					);

					$wcap_guest_user_id = WCAP_GUEST_CART_MODEL::insert( $wcap_insert_guest_email );

					$cart_info_decoded              = json_decode( $cart_info );
					$cart_info_decoded->captured_by = isset( $_POST['wcap_captured_from'] ) && '' !== $_POST['wcap_captured_from'] ? sanitize_text_field( wp_unslash( $_POST['wcap_captured_from'] ) ) : 'atc';
					$cart_info                      = wp_json_encode( $cart_info_decoded );
					if ( function_exists( 'icl_object_id' ) ) {
						$cart_info = WCAP_CART_HISTORY_MODEL::add_wcml_currency( $cart_info );
					}
					if ( defined( 'WOOCOMMERCE_MULTICURRENCY_VERSION' ) ) {
						$cart_info = WCAP_CART_HISTORY_MODEL::add_wc_multicurrency( $cart_info );
					}
					wcap_set_cart_session( 'wcap_user_id', $wcap_guest_user_id );

					if ( isset( $_POST['wcap_captured_from'] ) && $_POST['wcap_captured_from'] == 'custom_form' ) {

						$wcap_abandoned_cart_id = wcap_get_cart_session( 'wcap_abandoned_id' );

						if ( $wcap_abandoned_cart_id ) {

							WCAP_CART_HISTORY_MODEL::update(
								array(
									'user_id'             => $wcap_guest_user_id,
									'abandoned_cart_info' => $cart_info,
								),
								array(
									'id' => $wcap_abandoned_cart_id,
								)
							);

							echo $wcap_abandoned_cart_id;
							wp_die();
							exit;
						}
					}

					self::wcap_add_guest_record_for_atc( $wcap_guest_user_id, $cart_info, $current_time, $current_user_lang, $current_user_ip_address );

					// Fetch now so it's available
					$wcap_abandoned_cart_id = wcap_get_cart_session( 'wcap_abandoned_id' );

					// ATC stats email data.
					$atc_template_id = isset( $_POST['wcap_atc_template_id'] ) ? sanitize_text_field( wp_unslash( $_POST['wcap_atc_template_id'] ) ) : 0;
					$insert_data     = array(
						'timestamp'     => current_time( 'timestamp' ),
						'template_type' => 'atc',
						'template_id'   => $atc_template_id,
						'event'         => '1',
					);

					$wpdb->insert(
						WCAP_AC_STATS,
						$insert_data
					);
					// atc coupon auto apply
					$atc_template_settings = wcap_get_atc_template( $atc_template_id );
					$coupon_settings_atc   = isset( $atc_template_settings->coupon_settings ) ? json_decode( $atc_template_settings->coupon_settings ) : '';
					if ( isset( $coupon_settings_atc->wcap_atc_auto_apply_coupon_enabled ) && 'on' === $coupon_settings_atc->wcap_atc_auto_apply_coupon_enabled ) {
						self::wcap_atc_auto_apply_coupon( $wcap_abandoned_cart_id, $coupon_settings_atc, $atc_template_id, 'atc' );
					}
					if ( isset( $wcap_abandoned_cart_id ) && '' != $wcap_abandoned_cart_id && $wcap_abandoned_cart_id > 0 ) {
						do_action( 'acfac_add_data', $wcap_abandoned_cart_id );
						do_action( 'wcap_atc_record_created', $wcap_abandoned_cart_id );
						Wcap_Common::wcap_add_checkout_link( $wcap_abandoned_cart_id );
						Wcap_Common::wcap_run_webhook_after_cutoff( $wcap_abandoned_cart_id );
					}
					echo $wcap_abandoned_cart_id;
				} elseif ( isset( $_POST['wcap_atc_user_action'] ) && 'no' == $_POST['wcap_atc_user_action'] ) {

					$wcap_guest_user_id = '0';

					// Fetch now so it might be available
					$wcap_abandoned_cart_id = wcap_get_cart_session( 'wcap_abandoned_id' );

					// if session not set insert record and set session

					$track_from_cart_page = get_option( 'ac_track_guest_cart_from_cart_page' ) ? get_option( 'ac_track_guest_cart_from_cart_page' ) : '';
					if ( $wcap_abandoned_cart_id == '' && 'on' === $track_from_cart_page ) {
						self::wcap_add_visitor_record_for_new_session( $wcap_guest_user_id, $cart_info, $current_time, $current_user_lang, $current_user_ip_address );
					}

					if ( isset( $wcap_abandoned_cart_id ) && '' != $wcap_abandoned_cart_id && $wcap_abandoned_cart_id > 0 ) {
						do_action( 'acfac_add_data', $wcap_abandoned_cart_id );
					}
					echo $wcap_abandoned_cart_id;
				}
			}
			wp_die();
		}

		/**
		 *
		 * This function will add the Guest user cart information in abandoned cart history table.
		 * It will check if any session reocrd is present and it is not updated cart then update / insert the record
		 * with GUEST Id.
		 *
		 * @globals mixed $wpdb
		 * @globals mixed $woocommerce
		 * @param int | string $user_id User id of the abandoned cart
		 * @param json_encode  $cart_info Cart information
		 * @param timestamp    $current_time Current Time
		 * @param string       $current_user_lang User selected language while abandoing the cart
		 * @param string       $current_user_ip_address Ip address of the user.
		 * @since: 6.0
		 */
		public static function wcap_add_guest_record_for_atc( $user_id, $cart_info, $current_time, $current_user_lang, $current_user_ip_address ) {

			global $wpdb, $woocommerce;

			if ( is_multisite() ) {
				$main_prefix = $wpdb->get_blog_prefix( 1 );
			} else {
				$main_prefix = $wpdb->prefix;
			}

			$abandoned_cart_id   = 0;
			$wcap_wc_session_key = Wcap_Common::wcap_get_guest_session_key();

			$wcap_check_session_key_data         = 'SELECT id FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE user_id = '0' AND session_id = %s AND cart_ignored = '0' ";
			$wcap_check_session_key_data_results = $wpdb->get_results( $wpdb->prepare( $wcap_check_session_key_data, $wcap_wc_session_key ) );

			if ( count( $wcap_check_session_key_data_results ) > 0 ) {

				$wcap_id                = $wcap_check_session_key_data_results[0]->id;
				$wcap_update_guest_data = array(
					'user_id'             => $user_id,
					'abandoned_cart_time' => $current_time,
					'abandoned_cart_info' => $cart_info,
				);

				$abandoned_cart_id = $wcap_id;
				WCAP_CART_HISTORY_MODEL::update( $wcap_update_guest_data, array( 'id' => $wcap_id ) );
			} else {

				$data              = array(
					'user_id'               => $user_id,
					'abandoned_cart_info'   => $cart_info,
					'abandoned_cart_time'   => $current_time,
					'cart_ignored'          => 0,
					'recovered_cart'        => 0,
					'user_type'             => 'GUEST',
					'language'              => $current_user_lang,
					'session_id'            => $wcap_wc_session_key,
					'ip_address'            => $current_user_ip_address,
					'email_reminder_status' => '',
					'wcap_trash'            => '',
					'unsubscribe_link'      => '',
				);
				$abandoned_cart_id = WCAP_CART_HISTORY_MODEL::insert( $data );
			}
			wcap_set_cart_session( 'wcap_abandoned_id', $abandoned_cart_id );

			$insert_persistent_cart = 'INSERT INTO `' . $main_prefix . "usermeta`( user_id, meta_key, meta_value )
                                      VALUES ( %d , '_woocommerce_persistent_cart', %s  )";
			$wpdb->query( $wpdb->prepare( $insert_persistent_cart, $user_id, $cart_info ) );
		}

		/**
		 *
		 * It will add the visitors cart when customer do not provide the email address in Add To Cart modal.
		 * To be deprecated by v8.0
		 *
		 * @globals mixed $wpdb
		 * @globals mixed $woocommerce
		 * @param int | string $wcap_guest_user_id User id of the abandoned cart
		 * @param json_encode  $wcap_cart_info Cart information
		 * @param timestamp    $current_time Current Time
		 * @param string       $current_user_lang User selected language while abandoing the cart
		 * @param string       $current_user_ip_address Ip address of the user.
		 * @since: 6.0
		 */
		public static function wcap_add_visitor_record_for_new_session( $wcap_guest_user_id, $wcap_cart_info, $current_time, $current_user_lang, $current_user_ip_address ) {

			global $wpdb, $woocommerce;
			$main_prefix = ( is_multisite() ) ? $wpdb->get_blog_prefix( 1 ) : $wpdb->prefix;

			$wcap_atc_email_mandatory = wcap_get_atc_email_mandatory_status() ? 'on' : 'off';
			$wcap_atc_enabled         = wcap_get_popup_active_status( 'atc' ) ? 'on' : 'off';

			if ( ( 'off' == $wcap_atc_email_mandatory && 'on' == $wcap_atc_enabled ) || ( 'on' == $wcap_atc_email_mandatory && 'on' == $wcap_atc_enabled ) ) {
				if ( function_exists( 'icl_object_id' ) ) {
					$wcap_cart_info = WCAP_CART_HISTORY_MODEL::add_wcml_currency( $wcap_cart_info );
				}
				if ( defined( 'WOOCOMMERCE_MULTICURRENCY_VERSION' ) ) {
					$wcap_cart_info = WCAP_CART_HISTORY_MODEL::add_wc_multicurrency( $wcap_cart_info );
				}
				$wcap_wc_session_key      = Wcap_Common::wcap_get_guest_session_key();
				$wcap_insert_visitor_cart = array(
					'user_id'             => $wcap_guest_user_id,
					'abandoned_cart_info' => $wcap_cart_info,
					'abandoned_cart_time' => $current_time,
					'cart_ignored'        => '0',
					'recovered_cart'      => '0',
					'user_type'           => 'GUEST',
					'language'            => $current_user_lang,
					'session_id'          => $wcap_wc_session_key,
					'ip_address'          => $current_user_ip_address,
				);

				$abandoned_cart_id = WCAP_CART_HISTORY_MODEL::insert( $wcap_insert_visitor_cart );
				wcap_set_cart_session( 'wcap_abandoned_id', $abandoned_cart_id );

				$insert_persistent_cart = 'INSERT INTO `' . $main_prefix . "usermeta`( user_id, meta_key, meta_value )
                                          VALUES ( %d , '_woocommerce_persistent_cart', %s  )";
				$wpdb->query( $wpdb->prepare( $insert_persistent_cart, $wcap_guest_user_id, $wcap_cart_info ) );
			}
		}

		/**
		 * It will change the status popup modal visibility on the front end.
		 *
		 * @hook wp_ajax_wcap_toggle_atc_enable_status
		 * @globals mixed $wpdb
		 * @since: 6.0
		 */
		public static function wcap_toggle_atc_enable_status() {
			global $wpdb;
			$current_atc_modal_status = isset( $_POST['new_state'] ) ? sanitize_text_field( wp_unslash( $_POST['new_state'] ) ) : '';
			$id                       = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : 0;

			$is_active = 'on' === $current_atc_modal_status ? '1' : '0';
			if ( 0 < $id ) {
				$wpdb->update(
					WCAP_ATC_RULES_TABLE,
					array(
						'is_active' => $is_active,
					),
					array(
						'id' => $id,
					)
				);
			}
			// Delete the ATC cache for the front end.
			delete_option( 'wcap_atc_templates' );
			wp_die();
		}

		/**
		 * It will populate the modal detail for the abandoned cart.
		 *
		 * @hook wp_ajax_wcap_abandoned_cart_info
		 * @since 4.8
		 */
		public static function wcap_abandoned_cart_info() {

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( -1 );
			}

			check_ajax_referer( 'wcap_settings_nonce', 'security' );

			$wcap_cart_id          = isset( $_POST ['wcap_cart_id'] ) ? $_POST ['wcap_cart_id'] : '';
			$wcap_email_address    = isset( $_POST ['wcap_email_address'] ) ? $_POST ['wcap_email_address'] : '';
			$wcap_customer_details = isset( $_POST ['wcap_customer_details'] ) ? $_POST ['wcap_customer_details'] : '';
			$wcap_cart_total       = isset( $_POST ['wcap_cart_total'] ) ? $_POST ['wcap_cart_total'] : '';
			$wcap_abandoned_date   = isset( $_POST ['wcap_abandoned_date'] ) ? $_POST ['wcap_abandoned_date'] : '';
			$wcap_abandoned_status = isset( $_POST ['wcap_abandoned_status'] ) ? $_POST ['wcap_abandoned_status'] : '';
			$wcap_current_page     = isset( $_POST ['wcap_current_page'] ) ? $_POST ['wcap_current_page'] : '';
			Wcap_Abandoned_Cart_Details::wcap_get_cart_detail_view( $wcap_email_address, $wcap_customer_details, $wcap_cart_total, $wcap_abandoned_date, $wcap_abandoned_status, $wcap_current_page, $wcap_cart_id );
			wp_die();
		}

		/**
		 * It will unsubscribe the cart from admin end
		 *
		 * @hook wp_ajax_wcap_admin_unsubscribe_cart
		 * @since 4.8
		 */
		public static function wcap_admin_unsubscribe_cart() {

			$wcap_cart_id = isset( $_POST ['wcap_cart_id'] ) ? $_POST ['wcap_cart_id'] : '';

			if ( '' !== $wcap_cart_id ) {
				WCAP_CART_HISTORY_MODEL::wcap_unsubscribe_cart( $wcap_cart_id );

				wp_send_json_success(
					__( 'Unsubscribed successfully', 'woocommerce-ac' )
				);
			}
		}

		/**
		 * It will send the test email from the template add / edit page.
		 *
		 * @hook wp_ajax_wcap_preview_email_sent
		 * @since 1.0
		 */
		public static function wcap_preview_email_sent() {
			$from_email_name       = get_option( 'wcap_from_name' );
			$from_email_preview    = get_option( 'wcap_from_email' );
			$reply_name_preview    = get_option( 'wcap_reply_email' );
			$subject_email_preview = convert_smilies( $_POST['subject_email_preview'] );
			$body_email_preview    = convert_smilies( stripslashes( html_entity_decode( $_POST['body_email_preview'] ) ) );
			$to_email_preview      = isset( $_POST['send_email_id'] ) ? $_POST['send_email_id'] : '';
			$is_wc_template        = $_POST['is_wc_template'];
			$wc_template_header    = $_POST['wc_template_header'];
			$headers               = 'From: ' . $from_email_name . ' <' . $from_email_preview . '>' . "\r\n" . 'Content-Type: text/html' . "\r\n" . 'Reply-To:  ' . $reply_name_preview . ' ' . "\r\n";

			$subject_email_preview = wcap_replace_email_merge_tags_subject(
				$subject_email_preview,
				array(
					'customer.firstname' => 'John',
					'product.name'       => 'Spectre',
				)
			);

			$body_email_preview = Wcap_Common::wcap_replace_email_body_merge_code( $body_email_preview );
			$body_email_preview = Wcap_Common::wcap_inline_email_css( $body_email_preview );

			if ( isset( $is_wc_template ) && ( 'true' === $is_wc_template || 1 == $is_wc_template ) ) {
				ob_start();
				wc_get_template( 'emails/email-header.php', array( 'email_heading' => $wc_template_header ) );
				$email_body_template_header = ob_get_clean();

				ob_start();
				wc_get_template( 'emails/email-footer.php' );
				$email_body_template_footer = ob_get_clean();

				$email_body_template_footer = str_ireplace( '{site_title}', get_option( 'blogname' ), $email_body_template_footer );
				$final_email_body           = $email_body_template_header . $body_email_preview . $email_body_template_footer;
				Wcap_Common::wcap_add_wc_mail_header();
				wc_mail( $to_email_preview, $subject_email_preview, $final_email_body, $headers );
				Wcap_Common::wcap_remove_wc_mail_header();
			} else {
				Wcap_Common::wcap_add_wp_mail_header();
				wp_mail( $to_email_preview, $subject_email_preview, $body_email_preview, $headers );
				Wcap_Common::wcap_remove_wc_mail_header();
			}

			echo 'email sent';
			die();
		}

		/**
		 * It will search for the coupon code. It is called on the add / edit template page.
		 *
		 * @hook wp_ajax_wcap_json_find_coupons
		 * @param string $x
		 * @param array  $post_types Post type which we want to search
		 * @since 1.0
		 */
		public static function wcap_json_find_coupons( $x = '', $post_types = array( 'shop_coupon' ) ) {
			$term = (string) urldecode( stripslashes( strip_tags( $_GET['term'] ) ) );
			if ( empty( $term ) ) {
				die();
			}
			if ( is_numeric( $term ) ) {
				$args  = array(
					'post_type'      => $post_types,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'post__in'       => array( 0, $term ),
					'fields'         => 'ids',
				);
				$args2 = array(
					'post_type'      => $post_types,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'post_parent'    => $term,
					'fields'         => 'ids',
				);
				$args3 = array(
					'post_type'      => $post_types,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'meta_query'     => array(
						array(
							'key'     => '_sku',
							'value'   => $term,
							'compare' => 'LIKE',
						),
					),
					'fields'         => 'ids',
				);
				$posts = array_unique( array_merge( get_posts( $args ), get_posts( $args2 ), get_posts( $args3 ) ) );
			} else {
				$args  = array(
					'post_type'      => $post_types,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					's'              => $term,
					'fields'         => 'ids',
				);
				$args2 = array(
					'post_type'      => $post_types,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'meta_query'     => array(
						array(
							'key'     => '_sku',
							'value'   => $term,
							'compare' => 'LIKE',
						),
					),
					'fields'         => 'ids',
				);
				$posts = array_unique( array_merge( get_posts( $args ), get_posts( $args2 ) ) );
			}
			$found_products = array();
			if ( $posts ) {
				foreach ( $posts as $post ) {
					$SKU              = get_post_meta( $post, '_sku', true );
					$wcap_product_sku = apply_filters( 'wcap_product_sku', $SKU );
					if ( false != $wcap_product_sku && '' != $wcap_product_sku ) {
						if ( isset( $SKU ) && $SKU ) {
							$SKU = ' ( SKU: ' . $SKU . ' )';
						}
						$found_products[ $post ] = get_the_title( $post ) . ' - #' . $post . $SKU;
					} else {
						$found_products[ $post ] = get_the_title( $post ) . ' - #' . $post;
					}
				}
			}
			echo json_encode( $found_products );
			die();
		}

		/**
		 * It will search for the Products. It is called on the add / edit template page.
		 *
		 * @hook wp_ajax_wcap_json_find_products
		 * @param string $x
		 * @param array  $post_types Post type which we want to search
		 * @since 7.14.0
		 */
		public static function wcap_json_find_products( $x = '', $post_types = array( 'product' ) ) {

			check_ajax_referer( 'search-products', 'security' );
			$term = (string) urldecode( stripslashes( strip_tags( $_GET['term'] ) ) );
			if ( empty( $term ) ) {
				die();
			}
			if ( is_numeric( $term ) ) {
				$args  = array(
					'post_type'      => $post_types,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'post__in'       => array( 0, $term ),
					'fields'         => 'ids',
				);
				$args2 = array(
					'post_type'      => $post_types,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'post_parent'    => $term,
					'fields'         => 'ids',
				);
				$args3 = array(
					'post_type'      => $post_types,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'meta_query'     => array(
						array(
							'key'     => '_sku',
							'value'   => $term,
							'compare' => 'LIKE',
						),
					),
					'fields'         => 'ids',
				);
				$posts = array_unique( array_merge( get_posts( $args ), get_posts( $args2 ), get_posts( $args3 ) ) );
			} else {
				$args  = array(
					'post_type'      => $post_types,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					's'              => $term,
					'fields'         => 'ids',
				);
				$args2 = array(
					'post_type'      => $post_types,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'meta_query'     => array(
						array(
							'key'     => '_sku',
							'value'   => $term,
							'compare' => 'LIKE',
						),
					),
					'fields'         => 'ids',
				);
				$posts = array_unique( array_merge( get_posts( $args ), get_posts( $args2 ) ) );
			}
			$found_products = array();
			if ( $posts ) {
				foreach ( $posts as $post ) {
					$SKU              = get_post_meta( $post, '_sku', true );
					$wcap_product_sku = apply_filters( 'wcap_product_sku', $SKU );
					if ( false != $wcap_product_sku && '' != $wcap_product_sku ) {
						if ( isset( $SKU ) && $SKU ) {
							$SKU = ' ( SKU: ' . $SKU . ' )';
						}
						$found_products[ $post ] = get_the_title( $post ) . ' - #' . $post . $SKU;
					} else {
						$found_products[ $post ] = get_the_title( $post ) . ' - #' . $post;
					}
				}
			}
			echo json_encode( $found_products );
			die();
		}

		/**
		 * Returns matching product categories.
		 *
		 * @hook wp_ajax_wcap_json_find_product_cat
		 * @param string $x
		 * @param array  $post_types Post type which we want to search.
		 * @since 8.9.0
		 */
		public static function wcap_json_find_product_cat( $x = '', $post_types = array( 'product_cat' ) ) {

			check_ajax_referer( 'search-products', 'security' );
			$term = (string) urldecode( stripslashes( strip_tags( $_GET['term'] ) ) );
			if ( empty( $term ) ) {
				die();
			}

			$taxonomy     = 'product_cat';
			$orderby      = 'name';
			$show_count   = 0;      // 1 for yes, 0 for no
			$pad_counts   = 0;      // 1 for yes, 0 for no
			$hierarchical = 1;      // 1 for yes, 0 for no
			$title        = '';
			$empty        = 0;

			$args           = array(
				'taxonomy'     => $taxonomy,
				'orderby'      => $orderby,
				'show_count'   => $show_count,
				'pad_counts'   => $pad_counts,
				'hierarchical' => $hierarchical,
				'title_li'     => $title,
				'hide_empty'   => $empty,
			);
			$all_categories = get_categories( $args );
			$found_cats     = array();

			foreach ( $all_categories as $cat ) {
				if ( stripos( $cat->name, $term ) !== false ) {
					$category_id                = $cat->term_id;
					$found_cats[ $category_id ] = $cat->name;
				}
			}
			echo json_encode( $found_cats );
			die();
		}

		/**
		 * Returns matching product tags.
		 *
		 * @hook wp_ajax_wcap_json_find_product_tag
		 * @param string $x
		 * @param array  $post_types Post type which we want to search.
		 * @since 8.9.0
		 */
		public static function wcap_json_find_product_tag( $x = '', $post_types = array( 'product_tag' ) ) {

			check_ajax_referer( 'search-products', 'security' );
			$term = (string) urldecode( stripslashes( strip_tags( $_GET['term'] ) ) );
			if ( empty( $term ) ) {
				die();
			}

			$taxonomy     = 'product_tag';
			$orderby      = 'name';
			$show_count   = 0;      // 1 for yes, 0 for no
			$pad_counts   = 0;      // 1 for yes, 0 for no
			$hierarchical = 1;      // 1 for yes, 0 for no
			$title        = '';
			$empty        = 0;

			$args       = array(
				'taxonomy'     => $taxonomy,
				'orderby'      => $orderby,
				'show_count'   => $show_count,
				'pad_counts'   => $pad_counts,
				'hierarchical' => $hierarchical,
				'title_li'     => $title,
				'hide_empty'   => $empty,
			);
			$all_tags   = get_tags( $args );
			$found_tags = array();

			foreach ( $all_tags as $tag ) {
				if ( $tag->parent == 0 ) {
					if ( stripos( $tag->name, $term ) !== false ) {
						$tag_id                = $tag->term_id;
						$found_tags[ $tag_id ] = $tag->name;
					}
				}
			}
			echo json_encode( $found_tags );
			die();
		}

		/**
		 * Returns matching cart statuses.
		 *
		 * @hook wp_ajax_wcap_json_find_cart_status
		 * @param string $x
		 * @param array  $post_types Post type which we want to search.
		 * @since 8.9.0
		 */
		public static function wcap_json_find_cart_status( $x = '', $post_types = array() ) {

			check_ajax_referer( 'search-products', 'security' );
			$term = (string) urldecode( stripslashes( strip_tags( $_GET['term'] ) ) );
			if ( empty( $term ) ) {
				die();
			}

			$wcap_cart_status = array(
				'abandoned'           => __( 'Abandoned', 'woocommerce-ac' ),
				'abandoned-pending'   => __( 'Abandoned - Pending Payment', 'woocommerce-ac' ),
				'abandoned-cancelled' => __( 'Abandoned - Order Cancelled', 'woocommerce-ac' ),
			);

			$found_matches = array();

			foreach ( $wcap_cart_status as $k => $v ) {
				$found_matches[ $k ] = $v;
			}
			echo json_encode( $found_matches );
			die();
		}

		/**
		 * Returns matching WC order statuses.
		 *
		 * @hook wp_ajax_wcap_json_find_order_status
		 * @param string $x
		 * @param array  $post_types Post type which we want to search.
		 * @since 8.9.0
		 */
		public static function wcap_json_find_order_status( $x = '', $post_types = array() ) {

			check_ajax_referer( 'search-products', 'security' );
			$term = (string) urldecode( stripslashes( strip_tags( $_GET['term'] ) ) );
			if ( empty( $term ) ) {
				die();
			}

			$wc_order_statuses = wc_get_order_statuses();

			$found_matches = array();

			foreach ( $wc_order_statuses as $k => $v ) {
				$found_matches[ $k ] = $v;
			}
			echo json_encode( $found_matches );
			die();
		}

		/**
		 * Returns matching send to options.
		 *
		 * @hook wp_ajax_wcap_json_find_send_to
		 * @param string $x
		 * @param array  $post_types Post type which we want to search.
		 * @since 8.9.0
		 */
		public static function wcap_json_find_send_to( $x = '', $post_types = array() ) {

			check_ajax_referer( 'search-products', 'security' );
			$term = (string) urldecode( stripslashes( strip_tags( $_GET['term'] ) ) );
			if ( empty( $term ) ) {
				die();
			}

			$wcap_send_to = array(
				'all'                       => __( 'All', 'woocommerce-ac' ),
				'registered_users'          => __( 'Registered Users', 'woocommerce-ac' ),
				'guest_users'               => __( 'Guest Users', 'woocommerce-ac' ),
				'wcap_email_customer'       => __( 'Customers', 'woocommerce-ac' ),
				'wcap_email_admin'          => __( 'Admin', 'woocommerce-ac' ),
				'wcap_email_customer_admin' => __( 'Customers & Admin', 'woocommerce-ac' ),
				'email_addresses'           => __( 'Email Addresses', 'woocommerce-ac' ),
			);

			/*
				   $found_matches = array();

			foreach ( $wcap_send_to as $k => $v ) {
				if ( stripos( $v, $term ) !== false ) {
					$found_matches[$k] = $v;
				}
			}*/
			echo json_encode( $wcap_send_to );
			die();
		}

		/**
		 * Searches for pages matching the term sent.
		 * Used to allow for Add to Cart Pop-up to be displayed
		 * on Custom pages
		 *
		 * @param string $x
		 * @param array  $post_types Post type which we want to search - Pages
		 * @return Matched pages
		 * @since 7.10.0
		 */
		public static function wcap_json_find_pages( $x = '', $post_types = array( 'page' ) ) {
			check_ajax_referer( 'search-products', 'security' );
			$term = (string) urldecode( stripslashes( strip_tags( $_GET['term'] ) ) );
			if ( empty( $term ) ) {
				die();
			}
			$args        = array(
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				's'              => $term,
				'fields'         => 'ids',
			);
			$page_list   = get_posts( $args );
			$found_pages = array();
			if ( $page_list ) {
				foreach ( $page_list as $page ) {
					$found_pages[ $page ] = get_the_title( $page );
				}
			}
			echo json_encode( $found_pages );
			die();
		}

		/**
		 * Search WC order to be linked to an AC cart for recovery.
		 *
		 * @since 8.11.0
		 */
		public static function wcap_json_search_wc_order() {
			$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;

			if ( $order_id > 0 ) {
				$order = wc_get_order( $order_id );
				if ( false !== $order ) {
					$customer_email    = $order->get_billing_email();
					$billing_firstname = $order->get_billing_first_name();
					$billing_lastname  = $order->get_billing_last_name();
					echo "Order #$order_id - $billing_firstname $billing_lastname <$customer_email>";
					die();
				}
			}
			echo 'failed';
			die();
		}

		/**
		 * Mark AC Cart as recovered by admin.
		 *
		 * @since 8.11.0
		 */
		public static function wcap_mark_order_recovered_admin() {

			$recovery_mode = isset( $_POST['order_type'] ) ? sanitize_text_field( wp_unslash( $_POST['order_type'] ) ) : '';
			$cart_id       = isset( $_POST['cart_id'] ) && 0 < $_POST['cart_id'] ? sanitize_text_field( $_POST['cart_id'] ) : 0;
			if ( 'wcap_create' === $recovery_mode && $cart_id > 0 ) { // Check the recovery type
				// Create the WC Order
				$order_id = Wcap_Common::wcap_create_wc_order_for_cart( $cart_id );
			} elseif ( 'wcap_existing' === $recovery_mode ) {
				$order_id = isset( $_POST['order_id'] ) && 0 < $_POST['order_id'] ? sanitize_text_field( $_POST['order_id'] ) : 0;
			}
			// Mark the order as recovered.
			if ( $cart_id > 0 && $order_id > 0 ) {
				$order = wc_get_order( $order_id );
				Wcap_Common::wcap_updated_recovered_cart( $cart_id, $order_id, 0, $order );
				require_once 'classes/class_wcap_abandoned_orders_table.php';
				$wcap_abandoned_order_list = new Wcap_Abandoned_Orders_Table();
				$data                      = $wcap_abandoned_order_list->wcap_abandoned_order_prepare_items();
				wp_send_json( $data );
			} else {
				wp_send_json(
					array(
						'status'  => 'error',
						'message' => __( 'Something went wrong. Please check whether the cart contains atleast 1 product and try again.', 'woocommerce-ac' ),
					)
				);
			}
			die();
		}

		/**
		 * It will generate the selected template data and return to the ajax.
		 *
		 * @hook wp_ajax_wcap_change_manual_email_data
		 * @since: 4.2
		 */
		public static function wcap_change_manual_email_data() {

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( -1 );
			}

			check_ajax_referer( 'wcap_settings_nonce', 'security' );

			$return_selected_template_data = array();
			if ( isset( $_POST['wcap_template_id'] ) ) {
				global $wpdb;
				global $woocommerce;

				$template_id = $_POST['wcap_template_id'];
				$query       = 'SELECT wpet . *  FROM `' . WCAP_NOTIFICATION_TEMPLATES_TABLE . '` AS wpet WHERE id= %d';
				$results     = $wpdb->get_results( $wpdb->prepare( $query, $template_id ) );

				$return_selected_template_data ['from_name']                   = get_option( 'wcap_from_name' );
				$return_selected_template_data ['from_email']                  = get_option( 'wcap_from_email' );
				$return_selected_template_data ['reply_email']                 = get_option( 'wcap_reply_email' );
				$return_selected_template_data ['subject']                     = $results[0]->subject;
				$return_selected_template_data ['body']                        = $results[0]->body;
				$return_selected_template_data ['is_wc_template']              = $results[0]->is_wc_template;
				$return_selected_template_data ['wc_email_header']             = $results[0]->wc_email_header;
				$return_selected_template_data ['coupon_code']                 = $results[0]->coupon_code;
				$return_selected_template_data ['generate_unique_coupon_code'] = $results[0]->generate_unique_coupon_code;
				$return_selected_template_data ['discount']                    = $results[0]->discount;
				$return_selected_template_data ['discount_type']               = $results[0]->discount_type;
				$return_selected_template_data ['discount_shipping']           = $results[0]->discount_shipping;
				$return_selected_template_data ['discount_expiry']             = $results[0]->discount_expiry;
				$return_selected_template_data ['coupon_code_name']            = '';

				if ( $results[0]->coupon_code > 0 ) {
					$coupon_to_apply                                    = get_post( $results[0]->coupon_code, ARRAY_A );
					$coupon_code_name                                   = $coupon_to_apply['post_title'];
					$return_selected_template_data ['coupon_code_name'] = $coupon_code_name;
				} else {
					$return_selected_template_data ['coupon_code_name'] = '';
				}
				$return_selected_template_data ['generate_unique_coupon_code'] = $results[0]->generate_unique_coupon_code;

				if ( function_exists( 'WC' ) ) {
					$return_selected_template_data ['wc_version'] = WC()->version;
				} else {
					$return_selected_template_data ['wc_version'] = $woocommerce->version;
				}
			}
			echo json_encode( $return_selected_template_data );
			die();
		}

		/**
		 * It will delete the expired and used coupon codes.
		 *
		 * @hook wp_ajax_wcap_change_manual_email_data
		 * @since: 4.2
		 */
		public static function wcap_delete_expired_used_coupon_code() {

			global $wpdb;

			$expired_coupons = self::wcap_fetch_expired_coupons();
			$used_coupons    = self::wcap_fetch_used_coupons();
			$coupons         = array_unique( array_merge( $expired_coupons, $used_coupons ) );
			$coupon_count    = count( $coupons );

			if ( $coupon_count ) {
				$coupons_ids = implode( ',', $coupons );
                $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE post_id IN(" . $coupons_ids . ')' );//phpcs:ignore
                $wpdb->query( "DELETE FROM {$wpdb->posts} WHERE ID IN(" . $coupons_ids . ')' );//phpcs:ignore
			}

			// translators: %1$s: Coupons Deleted, %2$s: Deleted coupons count'.
			wp_send_json_success( sprintf( __( '%1$s: %2$d', 'woo-cart-abandonment-recovery' ), 'Coupons Deleted', $coupon_count ) );
		}

		/**
		 * It will fetch all expired coupons
		 *
		 * @hook wp_ajax_wcap_change_manual_email_data
		 * @since: 4.2
		 */
		public static function wcap_fetch_expired_coupons() {

			$coupon_ids = array();
			$args       = array(
				'posts_per_page' => -1,
				'post_type'      => 'shop_coupon',
				'post_status'    => 'publish',
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => 'date_expires',
						'value'   => strtotime( 'today' ),
						'compare' => '<=',
					),
					array(
						'key'     => 'date_expires',
						'value'   => '',
						'compare' => '!=',
					),
					array(
						'key'     => 'wcap_created_by',
						'value'   => 'wcap',
						'compare' => '=',
					),
				),
			);

			$coupons = get_posts( $args );

			if ( ! empty( $coupons ) ) {
				$current_time = current_time( 'timestamp' );

				foreach ( $coupons as $coupon ) {
					array_push( $coupon_ids, $coupon->ID );
				}
			}

			return $coupon_ids;
		}

		/**
		 * It will fetch all used coupons.
		 *
		 * @hook wp_ajax_wcap_change_manual_email_data
		 * @since: 4.2
		 */
		public static function wcap_fetch_used_coupons() {

			$coupon_ids = array();
			$args       = array(
				'posts_per_page' => -1,
				'post_type'      => 'shop_coupon',
				'post_status'    => 'publish',
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => 'usage_count',
						'value'   => 0,
						'compare' => '>',
					),
					array(
						'key'     => 'wcap_created_by',
						'value'   => 'wcap',
						'compare' => '=',
					),
				),
			);

			$coupons = get_posts( $args );

			if ( ! empty( $coupons ) ) {
				foreach ( $coupons as $coupon ) {
					// if ( $coupon->get_usage_count() >= $coupon->get_usage_limit() ) {
						array_push( $coupon_ids, $coupon->ID );
					// }
				}
			}

			return $coupon_ids;
		}

		/**
		 * It will store the guest users data in the ac_guest_abandoned_cart_history & ac_abandoned_cart_history table.
		 * It is called on the checkout page on email field.
		 *
		 * @hook wp_ajax_nopriv_wcap_save_guest_data
		 * @globals mixed $wpdb
		 * @globals mixed $woocommerce
		 * @since 1.0
		 */
		public static function wcap_save_guest_data() {

			$current_user_ip_address = Wcap_Common::wcap_get_client_ip();
			$billing_email_post      = '';
			if ( isset( $_POST['billing_email'] ) && '' != $_POST['billing_email'] ) {
				$billing_email_post = sanitize_text_field( $_POST['billing_email'] );
			}
			if ( class_exists( 'Addify_Request_For_Quote' ) ) {
				if ( isset( $_POST['form_data'] ) ) {
					parse_str( sanitize_meta( '', wp_unslash( $_POST['form_data'] ), '' ), $form_data );
				}
				$quote_fields_obj = new AF_R_F_Q_Quote_Fields();
				$quote_fields     = (array) $quote_fields_obj->quote_fields;
				$fields_data      = array();
				foreach ( $quote_fields as $value ) {
					$field_id    = $value->ID;
					$field_label = strtolower( trim( $value->post_title ) );

					$afrfq_field_name = get_post_meta( $field_id, 'afrfq_field_name', true );

					if ( isset( $form_data[ $afrfq_field_name ] ) ) {
						$fields_data[ $field_label ] = $form_data[ $afrfq_field_name ];
					}
				}
				// Map RFQ → WooCommerce fields
				$mapping = [
					'first name' => 'billing_first_name',
					'last name'  => 'billing_last_name',
					'email'      => 'billing_email',
					'phone'      => 'billing_phone',
					'company'    => 'billing_company',
					'address'    => 'billing_address_1',
					'city'       => 'billing_city',
					'state'      => 'billing_state',
					'zip code'   => 'billing_postcode',
				];

				foreach ( $mapping as $label => $wc_key ) {
					if ( isset( $fields_data[ $label ] ) ) {
						$value = $fields_data[ $label ];
						// Apply proper sanitization
						if ( 'billing_email' === $wc_key ) {
							$value = sanitize_email( $value );
							$billing_email_post = $value;
						} else {
							$value = sanitize_text_field( $value );
						}
						$_POST[ $wc_key ] = $value;
					}
				}
			}

			// sanitize the input fields
			$billing_phone_post      = isset( $_POST['billing_phone'] ) ? sanitize_text_field( $_POST['billing_phone'] ) : '';
			$billing_first_name_post = isset( $_POST['billing_first_name'] ) ? sanitize_text_field( $_POST['billing_first_name'] ) : '';
			$billing_last_name_post  = isset( $_POST['billing_last_name'] ) ? sanitize_text_field( $_POST['billing_last_name'] ) : '';
			$billing_country_post    = isset( $_POST['billing_country'] ) ? sanitize_text_field( $_POST['billing_country'] ) : '';
			$billing_postcode        = isset( $_POST['billing_postcode'] ) ? sanitize_text_field( $_POST['billing_postcode'] ) : '';
			$shipping_postcode       = isset( $_POST['shipping_postcode'] ) ? sanitize_text_field( $_POST['shipping_postcode'] ) : '';
			$ei_update               = isset( $_POST['ei_update'] ) && 'yes' === sanitize_text_field( wp_unslash( $_POST['ei_update'] ) ) ? true : false;
			$captured_by             = $ei_update ? 'exit_intent' : 'checkout';
			$billing_address_post    = isset( $_POST['billing_address_1'] ) ? sanitize_text_field( $_POST['billing_address_1'] ) : '';
			$billing_address_post_2  = isset( $_POST['billing_address_2'] ) ? sanitize_text_field( $_POST['billing_address_2'] ) : '';
			$billing_city            = isset( $_POST['billing_city'] ) ? sanitize_text_field( $_POST['billing_city'] ) : '';

			// shipping details when ship to a different address clicked.
			$shipping_phone_post      = isset( $_POST['shipping_phone'] ) ? sanitize_text_field( $_POST['shipping_phone'] ) : '';
			$shipping_first_name_post = isset( $_POST['shipping_first_name'] ) ? sanitize_text_field( $_POST['shipping_first_name'] ) : '';
			$shipping_last_name_post  = isset( $_POST['shipping_last_name'] ) ? sanitize_text_field( $_POST['shipping_last_name'] ) : '';
			$shipping_country_post    = isset( $_POST['shipping_country'] ) ? sanitize_text_field( $_POST['shipping_country'] ) : '';
			$shipping_postcode        = isset( $_POST['shipping_postcode'] ) ? sanitize_text_field( $_POST['shipping_postcode'] ) : '';
			$shipping_address_post    = isset( $_POST['shipping_address_1'] ) ? sanitize_text_field( $_POST['shipping_address_1'] ) : '';
			$shipping_address_post_2  = isset( $_POST['shipping_address_2'] ) ? sanitize_text_field( $_POST['shipping_address_2'] ) : '';
			$shipping_city            = isset( $_POST['shipping_city'] ) ? sanitize_text_field( $_POST['shipping_city'] ) : '';

			$guest_details = array(
				'first_name'        => $billing_first_name_post,
				'last_name'         => $billing_last_name_post,
				'phone'             => $billing_phone_post,
				'email'             => $billing_email_post,
				'billing_postcode'  => $billing_postcode,
				'shipping_postcode' => $shipping_postcode,
			);

			$get_restricted_ip_address     = Wcap_Common::wcap_is_ip_restricted( $current_user_ip_address );
			$get_restricted_email_address  = Wcap_Common::wcap_is_email_address_restricted( $billing_email_post );
			$get_restricted_domain_address = Wcap_Common::wcap_is_domain_restricted( $billing_email_post );
			$wcap_is_country_restricted    = Wcap_Common::wcap_is_country_restricted( $billing_country_post );

			if ( ! is_user_logged_in() && ( false == $get_restricted_ip_address && false == $get_restricted_email_address && false == $get_restricted_domain_address && false === $wcap_is_country_restricted ) ) {

				global $wpdb, $woocommerce;

				$wcap_wc_session_key = Wcap_Common::wcap_get_guest_session_key();
				$wc_shipping_charges = '';
				if ( 'disabled' != get_option( 'woocommerce_ship_to_countries' ) ) {

					if ( function_exists( 'WC' ) ) {
						$wc_shipping_charges = WC()->cart->get_cart_shipping_total(); // returns the formatted shipping total in a <span> tag
						// Extract the shipping amount
						$wc_shipping_charges = strip_tags( html_entity_decode( $wc_shipping_charges ) );
						$wc_shipping_charges = (float) filter_var( $wc_shipping_charges, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );

					} else {
						$wc_shipping_charges = $woocommerce->cart->get_shipping_total();
					}
				}

				if ( isset( $billing_first_name_post ) && $billing_first_name_post != '' ) {
					wcap_set_cart_session( 'billing_first_name', $billing_first_name_post );
				}
				if ( isset( $billing_last_name_post ) && $billing_last_name_post != '' ) {
					wcap_set_cart_session( 'billing_last_name', $billing_last_name_post );
				}
				if ( isset( $billing_postcode ) && $billing_postcode != '' ) {
					wcap_set_cart_session( 'billing_postcode', $billing_postcode );
				}
				if ( isset( $billing_email_post ) && $billing_email_post != '' ) {
					wcap_set_cart_session( 'billing_email', $billing_email_post );
				} else {
					wcap_set_cart_session( 'billing_email', '' );
				}
				if ( isset( $billing_phone_post ) && $billing_phone_post != '' ) {
					wcap_set_cart_session( 'billing_phone', $billing_phone_post );
				}
				if ( isset( $shipping_postcode ) && $shipping_postcode != '' ) {
					wcap_set_cart_session( 'shipping_postcode', $shipping_postcode );
				}

				$session_billing_email = wcap_get_cart_session( 'billing_email' );

				$abandoned_order_id = wcap_get_cart_session( 'wcap_abandoned_id' );
				$wcap_user_id       = wcap_get_cart_session( 'wcap_user_id' );

				$email_sent_id = wcap_get_cart_session( 'wcap_email_sent_id' );

				if ( $email_sent_id > 0 && $abandoned_order_id > 0 ) {
					$get_ac = $wpdb->get_row( $wpdb->prepare( 'SELECT id, user_id, abandoned_cart_info FROM ' . WCAP_ABANDONED_CART_HISTORY_TABLE . ' WHERE id = %s', $abandoned_order_id ) );
				} else {
					// Check if a record is present for the session ID.
					$get_ac = $wpdb->get_row( $wpdb->prepare( 'SELECT id, user_id, abandoned_cart_info FROM ' . WCAP_ABANDONED_CART_HISTORY_TABLE . ' WHERE session_id = %s', $wcap_wc_session_key ) );
				}

				// If yes, check if guest details record is present.
				if ( isset( $get_ac ) && $get_ac->id > 0 ) {

					if ( isset( $get_ac ) && $get_ac->user_id >= 63000000 ) { // Guest record exists.

						// Check if we already have the email address saved.
						$email_address_db = $wpdb->get_var( $wpdb->prepare( 'SELECT email_id FROM ' . WCAP_GUEST_CART_HISTORY_TABLE . ' WHERE id = %d', $get_ac->user_id ) );
						$checkout_webhook = false;
						if ( filter_var( $billing_email_post, FILTER_VALIDATE_EMAIL ) && '' == $email_address_db && '' != $billing_email_post ) { // No email address saved in DB & current email address in the correct format.
							$checkout_webhook = true;
						} elseif ( '' != $email_address_db && ! filter_var( $email_address_db, FILTER_VALIDATE_EMAIL ) && '' != $billing_email_post && filter_var( $billing_email_post, FILTER_VALIDATE_EMAIL ) ) { // Email address in DB is in incorrect format & current address is in correct format.
							$checkout_webhook = true;
						}

						if ( ! filter_var( $billing_email_post, FILTER_VALIDATE_EMAIL ) ) {
							$billing_email_post = $email_address_db;
						}

						$update_details = array(
							'billing_first_name'  => $billing_first_name_post,
							'billing_last_name'   => $billing_last_name_post,
							'email_id'            => $billing_email_post,
							'phone'               => $billing_phone_post,
							'billing_zipcode'     => $billing_postcode,
							'billing_city'        => $billing_city,
							'billing_country'     => $billing_country_post,
							'billing_address_1'   => $billing_address_post,
							'billing_address_2'   => $billing_address_post_2,
							'shipping_zipcode'    => $shipping_postcode,
							'shipping_charges'    => $wc_shipping_charges,
							'shipping_first_name' => $shipping_first_name_post,
							'shipping_last_name'  => $shipping_last_name_post,
							'shipping_county'     => $shipping_country_post,
							'shipping_address_1'  => $shipping_address_post,
							'shipping_address_2'  => $shipping_address_post_2,
							'shipping_city'       => $shipping_city,
						);
						$update_details = apply_filters( 'wcap_update_guest_data_at_checkout', $update_details, $_POST );
						WCAP_GUEST_CART_MODEL::update(
							$update_details,
							array(
								'id' => $get_ac->user_id,
							)
						);

						// Check if wcap_user_ref is present in the Session, but is not present in our table records.
						$wcap_user_ref_session = wcap_get_cart_session( 'wcap_user_ref' );
						$cart_info_existing    = json_decode( stripslashes( $get_ac->abandoned_cart_info ) );

						if ( ! isset( $cart_info_existing->wcap_user_ref ) && '' !== $wcap_user_ref_session ) {
							$cart_info_existing->wcap_user_ref = $wcap_user_ref_session;

							WCAP_CART_HISTORY_MODEL::update( array( 'abandoned_cart_info' => wp_strip_all_tags( wp_json_encode( $cart_info_existing ) ) ), array( 'id' => $get_ac->id ) );
						}

						$user_id = $get_ac->user_id;
						if ( $checkout_webhook ) {
							$abandoned_cart_id_hook = wcap_get_cart_session( 'wcap_abandoned_id' );
							do_action( 'wcap_guest_created_at_checkout', $abandoned_cart_id_hook );
							Wcap_Common::wcap_add_checkout_link( $abandoned_cart_id_hook );
							Wcap_Common::wcap_run_webhook_after_cutoff( $abandoned_cart_id_hook );
						}
					} elseif ( isset( $get_ac ) && 0 == $get_ac->user_id ) { // Insert a guest record.

						/**
						 * If a record is present in the guest cart history table for the same email id, then update
						 * the previous records of the user
						 */
						$query_guest   = 'SELECT id FROM `' . WCAP_GUEST_CART_HISTORY_TABLE . '`  WHERE email_id = %s';
						$results_guest = $wpdb->get_results( $wpdb->prepare( $query_guest, $session_billing_email ) );

						if ( $results_guest ) {
							foreach ( $results_guest as $key => $value ) {
								$query  = 'SELECT id FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE user_id = %d AND recovered_cart = '0'";
								$result = $wpdb->get_results( $wpdb->prepare( $query, $value->id ) );

								if ( $result ) {
									WCAP_CART_HISTORY_MODEL::update( array( 'cart_ignored' => 1 ), array( 'user_id' => $value->id ) );
								}
							}
						}

						$billing_first_name = wcap_get_cart_session( 'billing_first_name' );
						$billing_last_name  = wcap_get_cart_session( 'billing_last_name' );
						$billing_phone      = wcap_get_cart_session( 'billing_phone' );

						$billing_email = $session_billing_email;

						$shipping_zipcode = $billing_zipcode = '';
						if ( wcap_get_cart_session( 'shipping_postcode' ) != '' ) {
							$shipping_zipcode = wcap_get_cart_session( 'shipping_postcode' );
						} elseif ( wcap_get_cart_session( 'billing_postcode' ) != '' ) {
							$shipping_zipcode = $billing_zipcode = wcap_get_cart_session( 'billing_postcode' );
						}

						/**
						 * Insert the guest record.
						 */

						$insert_data = array(
							'billing_first_name'  => $billing_first_name_post,
							'billing_last_name'   => $billing_last_name_post,
							'email_id'            => $billing_email_post,
							'phone'               => $billing_phone_post,
							'billing_zipcode'     => $billing_postcode,
							'billing_city'        => $billing_city,
							'billing_country'     => $billing_country_post,
							'billing_address_1'   => $billing_address_post,
							'billing_address_2'   => $billing_address_post_2,
							'shipping_zipcode'    => $shipping_postcode,
							'shipping_charges'    => $wc_shipping_charges,
							'shipping_first_name' => $shipping_first_name_post,
							'shipping_last_name'  => $shipping_last_name_post,
							'shipping_county'     => $shipping_country_post,
							'shipping_address_1'  => $shipping_address_post,
							'shipping_address_2'  => $shipping_address_post_2,
							'shipping_city'       => $shipping_city,
						);
						$insert_data = apply_filters( 'wcap_insert_guest_data_at_checkout', $insert_data, $_POST );
						$user_id     = WCAP_GUEST_CART_MODEL::insert( $insert_data );
						/**
						 * Insert record in abandoned cart table for the guest user.
						 */

						wcap_set_cart_session( 'wcap_user_id', $user_id );

						$default_sms_consent = 'on' === get_option( 'wcap_enable_sms_consent', '' ) ? false : true;  // By default it will be true if setting is disabled.
						$sms_consent         = wcap_get_cart_session( 'wcap_sms_consent' ) ? wcap_get_cart_session( 'wcap_sms_consent' ) : $default_sms_consent;

						$whatsapp_consent = wcap_get_cart_session( 'wcap_whatsapp_consent' ) ? wcap_get_cart_session( 'wcap_whatsapp_consent' ) : false;

						// Update the User ID in the cart history table.
						$cart_info_dec              = json_decode( $get_ac->abandoned_cart_info );
						$cart_info_dec->captured_by = $captured_by;
						$cart_info_dec->sms_consent = $sms_consent;
						$cart_info_dec->whatsapp_consent = $whatsapp_consent;
						$cart_info                  = wp_strip_all_tags( wp_json_encode( $cart_info_dec ) );
						WCAP_CART_HISTORY_MODEL::update(
							array(
								'user_id'             => $user_id,
								'abandoned_cart_info' => $cart_info,
							),
							array(
								'session_id' => $wcap_wc_session_key,
							)
						);
						// New Record has been created. If we have the email address, we need to run the webhook.
						if ( $user_id > 0 ) {
							$abandoned_cart_id_hook = wcap_get_cart_session( 'wcap_abandoned_id' );
							do_action( 'wcap_guest_created_at_checkout', $abandoned_cart_id_hook );
							Wcap_Common::wcap_add_checkout_link( $abandoned_cart_id_hook );
							Wcap_Common::wcap_run_webhook_after_cutoff( $abandoned_cart_id_hook );
						}
					}

					// Add/update the _woocommerce_persistent_cart record.
					self::update_guest_persistent_cart( $user_id, stripslashes( $get_ac->abandoned_cart_info ) );

				} else { // If no record is present for the session ID, insert.

					if ( isset( $billing_email_post ) && $billing_email_post != '' ||
						 isset( $billing_phone_post ) && $billing_phone_post != '' ||
						 isset( $billing_first_name_post ) && $billing_first_name_post != '' ||
						 isset( $billing_last_name_post ) && $billing_last_name_post != '' ) {

						/**
						 * If a record is present in the guest cart history table for the same email id, then update
						 * the previous records of the user
						 */
						$query_guest   = 'SELECT id FROM `' . WCAP_GUEST_CART_HISTORY_TABLE . '`  WHERE email_id = %s';
						$results_guest = $wpdb->get_results( $wpdb->prepare( $query_guest, $session_billing_email ) );

						if ( $results_guest ) {
							foreach ( $results_guest as $key => $value ) {
								$query  = 'SELECT id FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE user_id = %d AND recovered_cart = '0'";
								$result = $wpdb->get_results( $wpdb->prepare( $query, $value->id ) );

								if ( $result ) {
									WCAP_CART_HISTORY_MODEL::update( array( 'cart_ignored' => 1 ), array( 'user_id' => $value->id ) );
								}
							}
						}

						$billing_first_name = wcap_get_cart_session( 'billing_first_name' );
						$billing_last_name  = wcap_get_cart_session( 'billing_last_name' );
						$billing_phone      = wcap_get_cart_session( 'billing_phone' );

						$billing_email = $session_billing_email;

						$shipping_zipcode = $billing_zipcode = '';
						if ( wcap_get_cart_session( 'shipping_postcode' ) != '' ) {
							$shipping_zipcode = wcap_get_cart_session( 'shipping_postcode' );
						} elseif ( wcap_get_cart_session( 'billing_postcode' ) != '' ) {
							$shipping_zipcode = $billing_zipcode = wcap_get_cart_session( 'billing_postcode' );
						}

						/**
						 * Insert the guest record.
						 */

						$insert_data = array(
							'billing_first_name'  => $billing_first_name_post,
							'billing_last_name'   => $billing_last_name_post,
							'email_id'            => $billing_email_post,
							'phone'               => $billing_phone_post,
							'billing_zipcode'     => $billing_postcode,
							'billing_city'        => $billing_city,
							'billing_country'     => $billing_country_post,
							'billing_address_1'   => $billing_address_post,
							'billing_address_2'   => $billing_address_post_2,
							'shipping_zipcode'    => $shipping_postcode,
							'shipping_charges'    => $wc_shipping_charges,
							'shipping_first_name' => $shipping_first_name_post,
							'shipping_last_name'  => $shipping_last_name_post,
							'shipping_county'     => $shipping_country_post,
							'shipping_address_1'  => $shipping_address_post,
							'shipping_address_2'  => $shipping_address_post_2,
							'shipping_city'       => $shipping_city,
						);
						$user_id     = WCAP_GUEST_CART_MODEL::insert( $insert_data );

						/**
						 * Insert record in abandoned cart table for the guest user.
						 */

						wcap_set_cart_session( 'wcap_user_id', $user_id );
						$current_time      = current_time( 'timestamp' );
						$cut_off_time      = get_option( 'ac_cart_abandoned_time_guest' );
						$cart_cut_off_time = $cut_off_time * 60;
						$compare_time      = $current_time - $cart_cut_off_time;

						/**
						 * Check if the generated user id is present in the abandoned cart history table.
						 * If yes then we will update that abandoned cart history row.
						 * If not then create the new record in the abandoned cart history table.
						 */
						$query   = 'SELECT * FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE user_id = %d AND cart_ignored = '0' AND recovered_cart = '0' AND user_type = 'GUEST'";
						$results = $wpdb->get_results( $wpdb->prepare( $query, $user_id ) );
						$cart    = array();

						$cart['cart']        = function_exists( 'WC' ) ? WC()->session->cart : $woocommerce->session->cart;
						$cart['cart_totals'] = function_exists( 'WC' ) ? WC()->session->cart_totals : $woocommerce->session->cart_totals;
						$cart['captured_by'] = $captured_by;
						if ( wcap_get_cart_session( 'wcap_user_ref' ) != '' ) {
							$cart['wcap_user_ref'] = wcap_get_cart_session( 'wcap_user_ref' );
						}
						$default_sms_consent = 'on' === get_option( 'wcap_enable_sms_consent', '' ) ? false : true;  // By default it will be true if setting is disabled.
						$sms_consent         = wcap_get_cart_session( 'wcap_sms_consent' ) ? wcap_get_cart_session( 'wcap_sms_consent' ) : $default_sms_consent;
						$cart['sms_consent'] = $sms_consent;

						$whatsapp_consent         = wcap_get_cart_session( 'wcap_whatsapp_consent' ) ? wcap_get_cart_session( 'wcap_whatsapp_consent' ) : false;
						$cart['whatsapp_consent'] = $whatsapp_consent;
						
						/**
						 * Count 0 indicate that the guest user id is not exists in the abandoned history table.
						 */
						if ( count( $results ) == 0 ) {

							if ( function_exists( 'icl_register_string' ) ) {
								$current_user_lang = isset( $_SESSION['wpml_globalcart_language'] ) ? $_SESSION['wpml_globalcart_language'] : ICL_LANGUAGE_CODE;
							} else {
								$current_user_lang = 'en';
							}
							$cart_info     = wp_strip_all_tags( wp_json_encode( $cart ) );
							$cart_info     = str_replace( ' \"%title%\" ', '', $cart_info );
							$query         = 'SELECT COUNT(`id`) FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "` 
                                            WHERE 
                                            session_id LIKE '$wcap_wc_session_key' 
                                            AND 
                                            cart_ignored = '0' 
                                            AND 
                                            recovered_cart = '0' ";
							$results_count = $wpdb->get_var( $query );

							if ( $results_count == 0 ) {

								self::wcap_add_guest_record_for_new_session( $user_id, $cart_info, $current_time, $current_user_lang, $current_user_ip_address );
							} else {

								self::wcap_add_guest_record_for_same_session( $cart_info, $user_id, $current_time, $current_user_lang, $current_user_ip_address, $current_user_ip_address );
							}
						}
						// New Record has been created. If we have the email address, we need to run the webhook.
						if ( $user_id > 0 ) {
							$abandoned_cart_id_hook = wcap_get_cart_session( 'wcap_abandoned_id' );
							do_action( 'wcap_guest_created_at_checkout', $abandoned_cart_id_hook );
							Wcap_Common::wcap_add_checkout_link( $abandoned_cart_id_hook );
							Wcap_Common::wcap_run_webhook_after_cutoff( $abandoned_cart_id_hook );
						}
					}
				}
			} elseif ( ! is_user_logged_in() &&
					 ( true == $get_restricted_ip_address ||
					   true == $get_restricted_email_address ||
					   true == $get_restricted_domain_address ||
					   true === $wcap_is_country_restricted ) ) {
				global $wpdb, $woocommerce;

				$wcap_wc_session_key = Wcap_Common::wcap_get_guest_session_key();
				WCAP_CART_HISTORY_MODEL::delete( array( 'session_id' => $wcap_wc_session_key ) );
			}
			if ( $ei_update ) {
				echo wcap_get_cart_session( 'wcap_abandoned_id' );
			} else {
				wp_die();
			}
		}

		/**
		 * Add/Update _woocommerce_persistent_cart record in the user meta table for guests.
		 *
		 * @param int   $user_id - Guest User ID.
		 * @param array $cart_info - Cart Data.
		 */
		public static function update_guest_persistent_cart( $user_id = 0, $cart_info = '' ) {

			if ( $user_id >= 63000000 && '' !== $cart_info ) {

				global $wpdb;
				$main_prefix = is_multisite() ? $wpdb->get_blog_prefix( 1 ) : $wpdb->prefix;

				// Check if record exists.
				$get_cart = $wpdb->get_results( $wpdb->prepare( 'SELECT umeta_id FROM `' . $main_prefix . "usermeta` WHERE user_id = %d AND meta_key = '_woocommerce_persistent_cart' ORDER BY umeta_id DESC LIMIT 1", $user_id ) );
				if ( isset( $get_cart ) && is_array( $get_cart ) && 1 === count( $get_cart ) ) {
					$wpdb->query(
						'UPDATE `' . $main_prefix . "usermeta` 
                      SET meta_value = '" . $cart_info . "'
                      WHERE user_id = '" . $user_id . "'
                      AND meta_key = '_woocommerce_persistent_cart'"
					);

				} else {
					$wpdb->query( $wpdb->prepare( 'INSERT INTO `' . $main_prefix . "usermeta`( user_id, meta_key, meta_value ) VALUES ( %d , '_woocommerce_persistent_cart', %s  )", $user_id, $cart_info ) );
				}
			}
		}

		/**
		 * Update the Guest user record if we found the same last name for the same session.
		 *
		 * @globals mixed $wpdb
		 * @globals mixed $woocommerce
		 * @param string       $wcap_wc_session_key The session key of the guest user
		 * @param int | string $wc_shipping_charges The charges of the shipping
		 * @since: 7.6
		 */
		public static function wcap_update_guest_record_on_same_last_name( $wcap_wc_session_key, $wc_shipping_charges, $guest_details ) {

			global $wpdb, $woocommerce;

			$update_on_last_name_info =
				'UPDATE `' . WCAP_GUEST_CART_HISTORY_TABLE . '` AS wpag 
                LEFT JOIN `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` AS wpah 
                ON wpag.id = wpah.user_id 
                SET wpag.phone = %s , 
                    wpag.email_id = %s, 
                    wpag.billing_first_name = %s ,
                    wpag.billing_country = %s,
                    wpag.shipping_charges = %s 
                WHERE wpag.billing_first_name = %s 
                AND wpah.session_id = %s';

			$wpdb->query(
				$wpdb->prepare(
					$update_on_last_name_info,
					$guest_details['phone'],
					$guest_details['email'],
					$guest_details['first_name'],
					sanitize_text_field( $_POST['billing_country'] ),
					$wc_shipping_charges,
					$guest_details['last_name'],
					$wcap_wc_session_key
				)
			);

			wcap_set_cart_session( 'billing_last_name', $guest_details['last_name'] );
			$guest_id = wcap_get_cart_session( 'wcap_user_id' );

			$query_update_get     = 'SELECT * FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` WHERE user_id = %d';
			$get_abandoned_record = $wpdb->get_results( $wpdb->prepare( $query_update_get, $guest_id ) );

			$abandoned_cart_id = $get_abandoned_record[0]->id;
			wcap_set_cart_session( 'wcap_abandoned_id', $abandoned_cart_id );

			if ( '' !== $abandoned_cart_id ) {
				do_action( 'acfac_add_data', $abandoned_cart_id );
			}
		}

		/**
		 * Update the Guest user record if we found the same first name for the same session.
		 *
		 * @globals mixed $wpdb
		 * @globals mixed $woocommerce
		 * @param string       $wcap_wc_session_key The session key of the guest user
		 * @param int | string $wc_shipping_charges The charges of the shipping
		 * @since: 7.6
		 */
		public static function wcap_update_guest_record_on_same_first_name( $wcap_wc_session_key, $wc_shipping_charges, $guest_details ) {

			global $wpdb, $woocommerce;

			$update_on_first_name_info =
				'UPDATE `' . WCAP_GUEST_CART_HISTORY_TABLE . '` AS wpag 
                LEFT JOIN `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` AS wpah 
                ON wpag.id = wpah.user_id 
                SET wpag.phone = %s , 
                    wpag.email_id = %s, 
                    wpag.billing_last_name = %s , 
                    wpag.billing_country = %s,
                    wpag.shipping_charges = %s
                WHERE wpag.billing_first_name = %s 
                AND wpah.session_id = %s';

			$wpdb->query(
				$wpdb->prepare(
					$update_on_first_name_info,
					$guest_details['phone'],
					$guest_details['email'],
					$guest_details['last_name'],
					sanitize_text_field( $_POST['billing_country'] ),
					$wc_shipping_charges,
					$guest_details['first_name'],
					$wcap_wc_session_key
				)
			);

			wcap_set_cart_session( 'billing_first_name', $guest_details['first_name'] );
			$guest_id = wcap_get_cart_session( 'wcap_user_id' );

			$query_update_get     = 'SELECT * FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` WHERE user_id = %d';
			$get_abandoned_record = $wpdb->get_results( $wpdb->prepare( $query_update_get, $guest_id ) );

			$abandoned_cart_id = $get_abandoned_record[0]->id;
			wcap_set_cart_session( 'wcap_abandoned_id', $abandoned_cart_id );

			if ( '' !== $abandoned_cart_id ) {
				do_action( 'acfac_add_data', $abandoned_cart_id );
			}
		}

		/**
		 * Update the Guest user reocrd if we found the same email address for the same session.
		 *
		 * @globals mixed $wpdb
		 * @globals mixed $woocommerce
		 * @param string       $wcap_wc_session_key The session key of the guest user
		 * @param int | string $wc_shipping_charges The charges of the shipping
		 * @since: 7.6
		 */
		public static function wcap_update_guest_record_on_same_email( $wcap_wc_session_key, $wc_shipping_charges, $guest_details ) {

			global $wpdb, $woocommerce;
			// default the variable
			$abandoned_cart_id = 0;

			$update_mobile_info =
				'UPDATE `' . WCAP_GUEST_CART_HISTORY_TABLE . '` AS wpag 
                LEFT JOIN `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` AS wpah 
                ON wpag.id = wpah.user_id 
                SET wpag.phone = %s , 
                    wpag.billing_first_name = %s,  
                    wpag.billing_last_name = %s, 
                    wpag.billing_country = %s,
                    wpag.shipping_charges = %s, 
                wpag.email_id = %s 
                WHERE wpah.session_id = %s';
			$wpdb->query(
				$wpdb->prepare(
					$update_mobile_info,
					$guest_details['phone'],
					$guest_details['first_name'],
					$guest_details['last_name'],
					sanitize_text_field( $_POST['billing_country'] ),
					$wc_shipping_charges,
					$guest_details['email'],
					$wcap_wc_session_key
				)
			);

			wcap_set_cart_session( 'billing_phone', $guest_details['phone'] );
			wcap_set_cart_session( 'billing_email', $guest_details['email'] );

			if ( isset( $_POST['wcap_abandoned_id'] ) && $_POST['wcap_abandoned_id'] !== '' ) {
				$abandoned_cart_id = $_POST['wcap_abandoned_id'];
				wcap_set_cart_session( 'wcap_abandoned_id', $abandoned_cart_id );
			} else {
				$guest_id = wcap_get_cart_session( 'wcap_user_id' );

				$query_update_get = 'SELECT * FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` WHERE user_id = %d';

				$get_abandoned_record = $wpdb->get_results( $wpdb->prepare( $query_update_get, $guest_id ) );

				if ( is_array( $get_abandoned_record ) && count( $get_abandoned_record ) > 0 ) {
					$abandoned_cart_id = $get_abandoned_record[0]->id;
					wcap_set_cart_session( 'wcap_abandoned_id', $abandoned_cart_id );
				}
			}

			if ( '' !== $abandoned_cart_id ) {
				do_action( 'acfac_add_data', $abandoned_cart_id );
			}
		}

		/**
		 * Update the Guest user reocrd if we found the same Phone number for the same session.
		 *
		 * @globals mixed $wpdb
		 * @globals mixed $woocommerce
		 * @param string       $wcap_wc_session_key The session key of the guest user
		 * @param int | string $wc_shipping_charges The charges of the shipping
		 * @since: 7.6
		 */
		public static function wcap_update_guest_record_on_same_phone( $wcap_wc_session_key, $wc_shipping_charges, $guest_details ) {

			global $wpdb, $woocommerce;

			$update_mobile_info =
				'UPDATE `' . WCAP_GUEST_CART_HISTORY_TABLE . '` AS wpag 
                LEFT JOIN `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` AS wpah 
                ON wpag.id = wpah.user_id 
                SET wpag.phone = %s , 
                    wpag.billing_first_name = %s,  
                    wpag.billing_last_name = %s, 
                    wpag.billing_country = %s,
                    wpag.email_id = %s, 
                    wpag.shipping_charges = %s 
                WHERE wpag.phone = %s 
                AND wpah.session_id = %s';
			$wpdb->query(
				$wpdb->prepare(
					$update_mobile_info,
					$guest_details['phone'],
					$guest_details['first_name'],
					$guest_details['last_name'],
					sanitize_text_field( $_POST['billing_country'] ),
					$guest_details['email'],
					$wc_shipping_charges,
					$guest_details['phone'],
					$wcap_wc_session_key
				)
			);

			wcap_set_cart_session( 'billing_phone', $guest_details['phone'] );

			if ( isset( $_POST['wcap_abandoned_id'] ) && $_POST['wcap_abandoned_id'] !== '' ) {
				wcap_set_cart_session( 'wcap_abandoned_id', $_POST['wcap_abandoned_id'] );
			} else {
				$guest_id = wcap_get_cart_session( 'wcap_user_id' );

				$query_update_get     = 'SELECT * FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` WHERE user_id = %d ';
				$get_abandoned_record = $wpdb->get_results( $wpdb->prepare( $query_update_get, $guest_id ) );

				$abandoned_cart_id = $get_abandoned_record[0]->id;
				wcap_set_cart_session( 'wcap_abandoned_id', $abandoned_cart_id );
			}

			if ( '' !== $abandoned_cart_id ) {
				do_action( 'acfac_add_data', $abandoned_cart_id );
			}
		}

		/**
		 * Insert the new record for the guest user if we do not have any relevant record for the user.
		 *
		 * @globals mixed $wpdb
		 * @globals mixed $woocommerce
		 * @param int | string $user_id User id of the abandoned cart
		 * @param json_encode  $cart_info Cart information
		 * @param timestamp    $current_time Current Time
		 * @param string       $current_user_lang User selected language while abandoing the cart
		 * @param string       $current_user_ip_address Ip address of the user.
		 * @since 6.0
		 */
		public static function wcap_add_guest_record_for_new_session( $user_id, $cart_info, $current_time, $current_user_lang, $current_user_ip_address ) {

			global $wpdb, $woocommerce;
			if ( is_multisite() ) {
				$main_prefix = $wpdb->get_blog_prefix( 1 );
			} else {
				$main_prefix = $wpdb->prefix;
			}

			$wcap_wc_session_key = Wcap_Common::wcap_get_guest_session_key();
			if ( function_exists( 'icl_object_id' ) ) {
				$cart_info = WCAP_CART_HISTORY_MODEL::add_wcml_currency( $cart_info );
			}
			if ( defined( 'WOOCOMMERCE_MULTICURRENCY_VERSION' ) ) {
				$cart_info = WCAP_CART_HISTORY_MODEL::add_wc_multicurrency( $cart_info );
			}

			$data = array(
				'user_id'               => $user_id,
				'abandoned_cart_info'   => $cart_info,
				'abandoned_cart_time'   => $current_time,
				'cart_ignored'          => 0,
				'recovered_cart'        => 0,
				'user_type'             => 'GUEST',
				'language'              => $current_user_lang,
				'session_id'            => $wcap_wc_session_key,
				'ip_address'            => $current_user_ip_address,
				'email_reminder_status' => '',
				'wcap_trash'            => '',
				'unsubscribe_link'      => '',
			);

			$abandoned_cart_id = WCAP_CART_HISTORY_MODEL::insert( $data );

			wcap_set_cart_session( 'wcap_abandoned_id', $abandoned_cart_id );

			$insert_persistent_cart = 'INSERT INTO `' . $main_prefix . "usermeta`( user_id, meta_key, meta_value )
                                      VALUES ( %d , '_woocommerce_persistent_cart', %s  )";
			$wpdb->query( $wpdb->prepare( $insert_persistent_cart, $user_id, $cart_info ) );

			if ( '' !== $abandoned_cart_id ) {
				do_action( 'acfac_add_data', $abandoned_cart_id );
			}
		}

		/**
		 * It will update the reocrd of the same user when we found the user data in the database.
		 *
		 * @globals mixed $wpdb
		 * @globals mixed $woocommerce
		 * @param int | string $user_id User id of the abandoned cart
		 * @param json_encode  $cart_info Cart information
		 * @param timestamp    $current_time Current Time
		 * @param string       $current_user_lang User selected language while abandoing the cart
		 * @param string       $current_user_ip_address Ip address of the user.
		 * @since 6.0
		 */
		public static function wcap_add_guest_record_for_same_session( $cart_info, $user_id, $current_time, $current_user_lang, $current_user_ip_address ) {

			global $wpdb, $woocommerce;

			if ( is_multisite() ) {
				$main_prefix = $wpdb->get_blog_prefix( 1 );
			} else {
				$main_prefix = $wpdb->prefix;
			}

			$wcap_wc_session_key = Wcap_Common::wcap_get_guest_session_key();

			if ( function_exists( 'icl_object_id' ) ) {
				$cart_info = WCAP_CART_HISTORY_MODEL::add_wcml_currency( $cart_info );
			}
			if ( defined( 'WOOCOMMERCE_MULTICURRENCY_VERSION' ) ) {
				$cart_info = WCAP_CART_HISTORY_MODEL::add_wc_multicurrency( $cart_info );
			}

			WCAP_CART_HISTORY_MODEL::update(
				array(
					'abandoned_cart_info' => $cart_info,
					'user_id'             => $user_id,
					'abandoned_cart_time' => $current_time,
					'language'            => $current_user_lang,
					'ip_address'          => $current_user_ip_address,
				),
				array(
					'session_id'   => $wcap_wc_session_key,
					'cart_ignored' => 0,
				)
			);

			if ( isset( $_POST['wcap_abandoned_id'] ) && $_POST['wcap_abandoned_id'] !== '' ) {
				$abandoned_cart_id = $_POST['wcap_abandoned_id'];
				wcap_set_cart_session( 'wcap_abandoned_id', $abandoned_cart_id );
			} else {
				$query_update_get     = 'SELECT id FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE user_id = %d AND cart_ignored='0' AND session_id = %s ";
				$get_abandoned_record = $wpdb->get_results( $wpdb->prepare( $query_update_get, $user_id, $wcap_wc_session_key ) );

				$abandoned_cart_id = $get_abandoned_record[0]->id;
				wcap_set_cart_session( 'wcap_abandoned_id', $abandoned_cart_id );
			}

			$insert_persistent_cart =
				'INSERT INTO `' . $main_prefix . "usermeta` 
                    ( user_id, meta_key, meta_value )
                    VALUES ( %d , '_woocommerce_persistent_cart', %s )";
			$wpdb->query( $wpdb->prepare( $insert_persistent_cart, $user_id, $cart_info ) );

			if ( '' !== $abandoned_cart_id ) {
				do_action( 'acfac_add_data', $abandoned_cart_id );
			}
		}

		/**
		 * Delete cart records and update the DB to mark the
		 * user's choice.
		 *
		 * @since 8.3.0
		 */
		public static function wcap_gdpr_refused() {

			$abandoned_cart_id = wcap_get_cart_session( 'wcap_abandoned_id' );

			global $wpdb;

			if ( isset( $abandoned_cart_id ) && $abandoned_cart_id > 0 ) {
				// fetch the user ID - if greater than 0, we need to check & delete guest table record is applicable.
				$query_user = 'SELECT user_id FROM ' . WCAP_ABANDONED_CART_HISTORY_TABLE . ' WHERE id = %d';
				$user_id    = $wpdb->get_var( $wpdb->prepare( $query_user, $abandoned_cart_id ) );

				if ( $user_id >= 63000000 ) { // guest user
					// delete the guest record
					WCAP_GUEST_CART_MODEL::delete( array( 'id' => $user_id ) );
				} else { // registered cart
					// save the user choice of not being tracked
					update_user_meta( $user_id, 'wcap_gdpr_tracking_choice', 0 );
				}
				// add in the session, that the user has refused tracking
				wcap_set_cart_session( 'wcap_cart_tracking_refused', 'yes' );

				// finally delete the cart history record
				WCAP_CART_HISTORY_MODEL::delete( array( 'id' => $abandoned_cart_id ) );
			} elseif ( is_user_logged_in() ) { // User might've clicked on No Thanks before adding products to the cart. Save the choice if the user is a registered user.
				$user_id = get_current_user_id();
				// save the user choice of not being tracked
				update_user_meta( $user_id, 'wcap_gdpr_tracking_choice', 0 );
				// add in the session, that the user has refused tracking
				wcap_set_cart_session( 'wcap_cart_tracking_refused', 'yes' );
			}
		}

		/**
		 * Delete cart records and update the DB to mark the
		 * user's choice.
		 *
		 * @since 8.3.0
		 */
		public static function wcap_email_consent() {

			$abandoned_cart_id = wcap_get_cart_session( 'wcap_abandoned_id' );
			$consent = isset( $_POST['consent'] ) ? sanitize_text_field( wp_unslash( $_POST['consent'] ) ) : false;

			global $wpdb;

			if ( 'false' === $consent && isset( $abandoned_cart_id ) && $abandoned_cart_id > 0 ) {
				// fetch the user ID - if greater than 0, we need to check & delete guest table record is applicable.
				$query_user = 'SELECT user_id FROM ' . WCAP_ABANDONED_CART_HISTORY_TABLE . ' WHERE id = %d';
				$user_id    = $wpdb->get_var( $wpdb->prepare( $query_user, $abandoned_cart_id ) );

				if ( $user_id >= 63000000 ) { // guest user
					// delete the guest record
					WCAP_GUEST_CART_MODEL::delete( array( 'id' => $user_id ) );
				} else { // registered cart
					// save the user choice of not being tracked
					add_user_meta( $user_id, 'wcap_gdpr_tracking_choice', 0 );
				}
				// add in the session, that the user has refused tracking
				wcap_set_cart_session( 'wcap_cart_tracking_refused', 'yes' );

				// finally delete the cart history record
				WCAP_CART_HISTORY_MODEL::delete( array( 'id' => $abandoned_cart_id ) );
			}

			if ( 'true' === $consent ) {
				$email_address_arr = get_option( 'wcap_unsubscribed_emails_for_all_carts' ) ? get_option( 'wcap_unsubscribed_emails_for_all_carts' ) : array();
				$user_email_address = isset( $_POST['email'] ) ? sanitize_text_field( wp_unslash( $_POST['email'] ) ) : false;
				$pos = array_search( $user_email_address, $email_address_arr );
    			if ( $pos !== false ) {
    				unset( $email_address_arr[$pos] ) ;
    				update_option( 'wcap_unsubscribed_emails_for_all_carts', $email_address_arr );
				}
			}
		}

		/**
		 * Function for saving the email address to the abandoned cart from Form Plugins.
		 *
		 * @since 8.3.0
		 */
		public static function wcap_add_email_to_cart() {
			if ( null !== wcap_get_cart_session( 'wcap_abandoned_id' ) && null === wcap_get_cart_session( 'wcap_user_id' ) ) {
				global $wpdb;

				$wpdb->insert(
					WCAP_GUEST_CART_HISTORY_TABLE,
					array(
						'email_id' => esc_attr( $_POST['wcap_get_email_address'] ),
					),
					array( '%s' )
				);

				$user_id = $wpdb->insert_id;

				$abandoned_id = wcap_get_cart_session( 'wcap_abandoned_id' );
				$cart_info    = $wpdb->get_var(
					$wpdb->prepare(
						'SELECT abandoned_cart_info FROM ' . WCAP_ABANDONED_CART_HISTORY_TABLE . ' WHERE id = %d',
						$abandoned_id
					)
				);

				$cart_info_dec              = json_decode( stripslashes( $cart_info ) );
				$cart_info_dec->captured_by = 'custom_form';
				$updated_cart_info          = wp_json_encode( $cart_info_dec );
				WCAP_CART_HISTORY_MODEL::update(
					array(
						'user_id'             => esc_attr( $user_id ),
						'abandoned_cart_info' => $updated_cart_info,
					),
					array( 'id' => $abandoned_id )
				);
				// Webhook Trigger
				do_action( 'wcap_guest_using_forms', $abandoned_id );

				echo 'success';
				die();
			}
		}

		/**
		 * Apply Coupon to cart for ATC record.
		 *
		 * @param int $abandoned_id - Abandoned Order ID
		 * @since 8.5.0
		 */
		public static function wcap_atc_auto_apply_coupon( $abandoned_id, $coupon_settings, $template_id, $template_type ) {

			if ( 0 < $abandoned_id ) {
				$coupon_type = isset( $coupon_settings->wcap_atc_coupon_type ) ? $coupon_settings->wcap_atc_coupon_type : '';

				// If auto-apply coupons is enabled & coupon type is setup.
				if ( 'on' === $coupon_settings->wcap_atc_auto_apply_coupon_enabled && '' !== $coupon_type ) {

					$post_meta_coupon_array = get_post_meta( $abandoned_id, '_woocommerce_ac_coupon', true );
					if ( is_array( $post_meta_coupon_array ) && count( $post_meta_coupon_array ) > 0 ) {
						foreach ( $post_meta_coupon_array as $k => $coupon_details ) {
							if ( isset( $coupon_details['atc_coupon_code'] ) && '' != $coupon_details['atc_coupon_code'] ) { // Coupon was applied.
								return; // As coupon was applied as per the meta record details.
							}
						}
					} else {

						$current_time_gmt = time();
						$current_time_wp  = current_time( 'timestamp' );

						$coupon_validity      = isset( $coupon_settings->wcap_atc_popup_coupon_validity ) ? $coupon_settings->wcap_atc_popup_coupon_validity : '';
						$expiry_timestamp_gmt = 0;
						if ( is_numeric( $coupon_validity ) ) {
							$validity             = $coupon_validity * 60;
							$expiry_timestamp     = $current_time_wp + $validity;
							$expiry_timestamp_gmt = $current_time_gmt + $validity;
						}

						// If preselected.
						if ( 'pre-selected' === $coupon_type ) {

							$coupon_code_id = isset( $coupon_settings->wcap_atc_popup_coupon ) ? $coupon_settings->wcap_atc_popup_coupon : 0;
							if ( $coupon_code_id > 0 ) {
								$coupon_code = get_the_title( $coupon_code_id );
							}
						} elseif ( 'unique' === $coupon_type ) { // If unique.

							// Get the coupon details
							$wcap_atc_discount_type        = isset( $coupon_settings->wcap_atc_discount_type ) ? $coupon_settings->wcap_atc_discount_type : '';
							$wcap_atc_discount_amount      = isset( $coupon_settings->wcap_atc_discount_amount ) ? $coupon_settings->wcap_atc_discount_amount : '';
							$wcap_atc_coupon_free_shipping = isset( $coupon_settings->wcap_atc_coupon_free_shipping ) ? $coupon_settings->wcap_atc_coupon_free_shipping : 'no';

							// Generate the coupon.
							if ( in_array( $wcap_atc_discount_type, array( 'percent', 'fixed_cart' ), true ) && 0 < $wcap_atc_discount_amount ) {

								$wcap_atc_coupon_free_shipping               = 'on' === $wcap_atc_coupon_free_shipping ? 'yes' : 'no';
								$coupon_post_meta['usage_limit_per_user'][0] = 1;
								$coupon_post_meta['atc_unique_coupon'][0]    = true;
								$coupon_code                                 = wcap_wp_coupon_code( $wcap_atc_discount_amount, $wcap_atc_discount_type, $expiry_timestamp_gmt, $wcap_atc_coupon_free_shipping, $coupon_post_meta );
							}
						}

						if ( isset( $coupon_code ) ) {
							$message = WC()->cart->add_discount( $coupon_code );
							if ( ! $message ) {
								$the_coupon = new WC_Coupon( $coupon_code );
								if ( ! $the_coupon->is_valid() ) {
									$message = $the_coupon->get_error_message();
								}
								$coupon_details[] = array(
									'coupon_code'    => $coupon_code,
									'coupon_message' => $message,
								);
								update_post_meta( $abandoned_id, '_woocommerce_ac_coupon', $coupon_details );
							}

							// Statistics Update.
							if ( isset( $template_id ) && absint( $template_id ) > 0 ) {
								global $wpdb;
								$data = array(
									'template_id'   => $template_id,
									'template_type' => $template_type,
									'event'         => '4',
								);
								WCAP_STATISTICS_MODEL::insert( $data );
								wcap_set_cart_session( 'wcap_' . $template_type . '_template_id', $template_id );
							}
							if ( 'exit_intent' === $template_type ) {
								// If it's an EI popup, the coupon post meta needs to be updated manually.
								$applied = Wcap_Common::wcap_update_coupon_post_meta( $abandoned_id, $coupon_code );
							}
							// Re-fetch as the post meta record has now been created.
							$post_meta_coupon_array = get_post_meta( $abandoned_id, '_woocommerce_ac_coupon', true );
							if ( is_array( $post_meta_coupon_array ) && count( $post_meta_coupon_array ) > 0 ) {
								foreach ( $post_meta_coupon_array as $k => $coupon_details ) {
									if ( strtolower( $coupon_details['coupon_code'] ) === strtolower( $coupon_code ) ) {
										$post_meta_coupon_array[ $k ][ $template_type . '_coupon_code' ] = $coupon_code;

										// add the time at which coupon has been applied & its validity.
										$post_meta_coupon_array[ $k ]['time_applied'] = $current_time_wp;
										if ( is_numeric( $coupon_validity ) ) {
											$post_meta_coupon_array[ $k ]['time_expires'] = $expiry_timestamp;
										}
									}
								}
							}

							update_post_meta( $abandoned_id, '_woocommerce_ac_coupon', $post_meta_coupon_array );
						}
					}
				}
			}
		}

		/**
		 * Mark the countdown as dismissed by the user in the DB.
		 *
		 * @since 8.6.0
		 */
		public static function wcap_coupon_countdown_dismissed() {

			$abandoned_id = wcap_get_cart_session( 'wcap_abandoned_id' );

			if ( $abandoned_id > 0 ) {
				$post_meta_coupon_array = get_post_meta( $abandoned_id, '_woocommerce_ac_coupon', true );
				if ( is_array( $post_meta_coupon_array ) && count( $post_meta_coupon_array ) > 0 ) {
					foreach ( $post_meta_coupon_array as $k => $coupon_details ) {
						if ( isset( $coupon_details['atc_coupon_code'] ) && '' !== $coupon_details['atc_coupon_code'] ) {
							$post_meta_coupon_array[ $k ]['countdown_display_dismissed'] = true;
							update_post_meta( $abandoned_id, '_woocommerce_ac_coupon', $post_meta_coupon_array );
							break;
						}
					}
				}
			}
			die();
		}

		/**
		 * Update the Guest Email ID from admin.
		 *
		 * @since 8.8.0
		 */
		public static function wcap_edit_guest_email() {
			$wcap_user_id = isset( $_POST ['wcap_user_id'] ) ? $_POST ['wcap_user_id'] : '';
			$wcap_email   = isset( $_POST ['wcap_email'] ) ? $_POST ['wcap_email'] : '';
			$wcap_email   = filter_var( $wcap_email, FILTER_SANITIZE_EMAIL );
			WCAP_GUEST_CART_MODEL::update( array( 'email_id' => $wcap_email ), array( 'id' => $wcap_user_id ) );
			$cart_id = wcap_get_abandoned_id_from_user_id( $wcap_user_id );
			if ( $cart_id > 0 ) {
				Wcap_Common::wcap_add_checkout_link( $cart_id );
			}
			wp_send_json_success( __( 'Details Updated!', 'woocommerce-ac' ) );
		}

		/**
		 * Exit Intent Popup Stats update, apply coupon & create cart records.
		 *
		 * @since 8.14.0
		 */
		public static function wcap_ei_stats_update() {

			global $wpdb;

			// EI stats email data.
			$ei_template_id = isset( $_POST['template_id'] ) ? sanitize_text_field( wp_unslash( $_POST['template_id'] ) ) : 0;
			$action         = isset( $_POST['stats_action'] ) ? sanitize_text_field( wp_unslash( $_POST['stats_action'] ) ) : '';

			if ( '' !== $action ) {
				switch ( $action ) {
					default:
						$event = '';
						break;
					case 'wcap_ei_opened':
						$event = '0';
						wcap_set_cart_session( 'wcap_exit_intent_template_id', $ei_template_id );
						break;
					case 'wcap_email_capture':
						$event                       = '1';
						$_POST['billing_phone']      = '';
						$_POST['billing_first_name'] = '';
						$_POST['billing_last_name']  = '';
						$_POST['billing_country']    = '';
						$_POST['billing_postcode']   = '';
						$_POST['shipping_postcode']  = '';
						$abandoned_id                = self::wcap_save_guest_data();
						break;
					case 'wcap_ei_no_thanks':
						$event = '2';
						break;
					case 'wcap_ei_dismissed':
						$event = '3';
						break;
					case 'wcap_ei_button_click':
						$event = '5';
						// Auto apply EI coupons if applicable.
						$ei_template_settings   = wcap_get_atc_template( $ei_template_id );
						$wcap_abandoned_cart_id = wcap_get_cart_session( 'wcap_abandoned_id' );
						$coupon_settings_ei     = isset( $ei_template_settings->coupon_settings ) ? json_decode( $ei_template_settings->coupon_settings ) : '';
						if ( isset( $coupon_settings_ei->wcap_atc_auto_apply_coupon_enabled ) && 'on' === $coupon_settings_ei->wcap_atc_auto_apply_coupon_enabled && $wcap_abandoned_cart_id > 0 ) {
							self::wcap_atc_auto_apply_coupon( $wcap_abandoned_cart_id, $coupon_settings_ei, $ei_template_id, 'exit_intent' );
						}
						break;
				}

				if ( '' !== $event ) {
					$data = array(
						'template_id'   => $ei_template_id,
						'template_type' => 'exit_intent',
						'event'         => $event,
					);
					WCAP_STATISTICS_MODEL::insert( $data );
				}

				// Return the abandoned cart ID if email capture action has taken place.
				if ( 'wcap_email_capture' === $action ) {
					echo $abandoned_id;
				}
			}
			wp_die();
		}
		/**
		 * Capture SMS consent.
		 *
		 * @since 8.18.0
		 */
		public static function wcap_sms_consent() {
			$consent      = isset( $_POST['consent'] ) ? sanitize_text_field( wp_unslash( $_POST['consent'] ) ) : '';
			$abandoned_id = wcap_get_cart_session( 'wcap_abandoned_id' );
			if ( $abandoned_id > 0 ) {
				// $wcap_session_cookie = Wcap_Common::wcap_get_guest_session_key();
				global $wpdb;
				$cart                      = $wpdb->get_var(
					$wpdb->prepare(
						'SELECT abandoned_cart_info FROM ' . WCAP_ABANDONED_CART_HISTORY_TABLE . ' WHERE id = %d',
						absint( $abandoned_id )
					)
				);
				$cart_details              = json_decode( stripslashes( $cart ) );
				$cart_details->sms_consent = $consent;
				$cart_info                 = wp_json_encode( $cart_details );
				WCAP_CART_HISTORY_MODEL::update(
					array(
						'abandoned_cart_info' => $cart_info,
					),
					array(
						'id' => $abandoned_id,
					)
				);
			}
			wcap_set_cart_session( 'wcap_sms_consent', $consent );
			die();
		}

		/**
		 * Capture SMS consent.
		 *
		 * @since 8.18.0
		 */
		public static function wcap_whatsapp_consent() {
			$consent       = isset( $_POST['consent'] ) ? sanitize_text_field( wp_unslash( $_POST['consent'] ) ) : '';
			$is_logged_in  = is_user_logged_in() ? true : false;
			$abandoned_id = wcap_get_cart_session( 'wcap_abandoned_id' );
			if ( $abandoned_id > 0 ) {
				// $wcap_session_cookie = Wcap_Common::wcap_get_guest_session_key();
				global $wpdb;
				$cart                      = $wpdb->get_var(
					$wpdb->prepare(
						'SELECT abandoned_cart_info FROM ' . WCAP_ABANDONED_CART_HISTORY_TABLE . ' WHERE id = %d',
						absint( $abandoned_id )
					)
				);
				$cart_details              = json_decode( stripslashes( $cart ) );
				$cart_details->whatsapp_consent = $consent;
				$cart_info                 = wp_json_encode( $cart_details );
				WCAP_CART_HISTORY_MODEL::update(
					array(
						'abandoned_cart_info' => $cart_info,
					),
					array(
						'id' => $abandoned_id,
					)
				);

				
			}

			if ( $is_logged_in ) {
				$user_id = get_current_user_id();
				update_user_meta( $user_id, 'wcap_whatsapp_consent', 'true' === $consent ? true : false );
			}

			wcap_set_cart_session( 'wcap_whatsapp_consent', $consent );
			die();
		}

		/**
		 * Capture SMS consent.
		 *
		 * @since 8.18.0
		 */
		public static function wcap_verify_email_validity() {

			if ( isset( $_POST['wcap_atc_email'] ) ) {
				$email    = sanitize_text_field( wp_unslash( $_POST['wcap_atc_email'] ) );
				$response = apply_filters(
					'wcap_extra_email_validation',
					array(
						'status' => 'success',
						'msg'    => '',
					),
					$email
				);
			}
			 wp_send_json( $response );
		}
	}

	new Wcap_Ajax();
}
