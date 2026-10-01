<?php
/**
 * It will add the default setting and the email templates.
 * @author   LittleCodr
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Setting
 * @since 2.3.5
 * 
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if ( !class_exists('Wcap_Default_Settings' ) ) {
	/**
	 * It will add the default setting and the email templates.
	 */
	class Wcap_Default_Settings {

		/** 
		 * This function will load default settings.
		 * @since 2.3.5
		 */
		function wcap_create_default_settings( $blog_id ) {
			$cart_source = array(
				'all'         => 'on',
				'checkout'    => 'on',
				'profile'     => 'on',
				'atc'         => 'on',
				'exit_intent' => 'on',
				'url'         => 'on',
				'custom_form' => 'on',
			);
			if( $blog_id === 0 ) {
				add_option( 'ac_enable_cart_emails'         , 'on' );
				add_option( 'ac_cart_abandoned_time'        , '10' );
				add_option( 'ac_cart_abandoned_time_guest'  , '10' );
				add_option( 'ac_delete_abandoned_order_days', '365' );
				add_option( 'ac_email_admin_on_recovery'    , 'on' );
				add_option( 'ac_track_coupons'              , '' );
				add_option( 'ac_disable_guest_cart_email'   , '' );
				add_option( 'wcap_use_auto_cron'            , 'on' );
				add_option( 'wcap_delete_coupon_data'       , 'on' );
				add_option( 'wcap_cron_time_duration'       , '15' );
				update_option( 'ac_settings_status'         , 'INDIVIDUAL' );
				add_option( 'wcap_from_name'                , 'Admin' );
				$wcap_get_admin_email = get_option( 'admin_email' );
				add_option( 'wcap_from_email'               , $wcap_get_admin_email );
				add_option( 'wcap_reply_email'              , $wcap_get_admin_email );
				add_option( 'wcap_product_image_height'     , '125' );
				add_option( 'wcap_product_image_width'      , '125' );
				add_option( 'wcap_email_reports_frequency', 'weekly' );
				add_option( 'wcap_email_reports_emails_list', $wcap_get_admin_email );
				add_option( 'wcap_product_name_redirect', 'checkout' );
				add_option( 'wcap_unsubscribe_landing_page', 'default_page' );
				add_option( 'wcap_auto_login_users', '' );
				add_option( 'wcap_email_admin_on_abandonment', 'on' );
				add_option( 'wcap_email_admin_custom_addresses', $wcap_get_admin_email );
				add_option( 'wcap_email_admin_cart_source', $cart_source );
				add_option( 'wcap_allow_tracking', 'yes' );
				add_option( 'wcap_enable_tracking_by_default', 'yes' );
				if ( ! get_option( 'wcap_gdpr_allow_opt_in' ) ) {
					add_option( 'wcap_gdpr_allow_opt_in', __( 'I agree to receive abandoned cart reminder emails.', 'woocommerce-ac' ) );
				}
			} else {
				add_blog_option( $blog_id, 'ac_enable_cart_emails'         , 'on' );
				add_blog_option( $blog_id, 'ac_cart_abandoned_time'        , '10' );
				add_blog_option( $blog_id, 'ac_cart_abandoned_time_guest'  , '10' );
				add_blog_option( $blog_id, 'ac_delete_abandoned_order_days', '365' );
				add_blog_option( $blog_id, 'ac_email_admin_on_recovery'    , '' );
				add_blog_option( $blog_id, 'ac_track_coupons'              , '' );
				add_blog_option( $blog_id, 'ac_disable_guest_cart_email'   , '' );
				add_blog_option( $blog_id, 'wcap_use_auto_cron'            , 'on' );
				add_blog_option( $blog_id, 'wcap_cron_time_duration'       , '15' );
				update_blog_option( $blog_id, 'ac_settings_status'         , 'INDIVIDUAL' );
				add_blog_option( $blog_id, 'wcap_from_name'                , 'Admin' );
				$wcap_get_admin_email = get_blog_option( $blog_id, 'admin_email' );
				add_blog_option( $blog_id, 'wcap_from_email'               , $wcap_get_admin_email );
				add_blog_option( $blog_id, 'wcap_reply_email'              , $wcap_get_admin_email );
				add_blog_option( $blog_id, 'wcap_product_image_height'     , '125' );
				add_blog_option( $blog_id, 'wcap_product_image_width'      , '125' );
				add_blog_option( $blog_id, 'wcap_email_reports_frequency', 'weekly' );
				add_blog_option( $blog_id, 'wcap_email_reports_emails_list', $wcap_get_admin_email );
				add_blog_option( $blog_id, 'wcap_product_name_redirect', 'checkout' );
				add_blog_option( $blog_id, 'wcap_unsubscribe_landing_page', 'default_page' );
				add_blog_option( $blog_id, 'wcap_auto_login_users', '' );
				add_blog_option( $blog_id, 'wcap_email_admin_on_abandonment', 'on' );
				add_blog_option( $blog_id, 'wcap_email_admin_custom_addresses', $wcap_get_admin_email );
				add_blog_option( $blog_id, 'wcap_email_admin_cart_source', $cart_source );
				add_blog_option( $blog_id, 'wcap_allow_tracking', 'yes' );
				add_blog_option( $blog_id, 'wcap_enable_tracking_by_default', 'yes' );
				if ( ! get_blog_option( $blog_id, 'wcap_gdpr_allow_opt_in' ) ) {
					add_blog_option( $blog_id, 'wcap_gdpr_allow_opt_in', __( 'I agree to receive abandoned cart reminder emails.', 'woocommerce-ac' ) );
				}
			}
			self::wcap_email_reports_frequency_scheduled_action( '', 'weekly' );
		}

		/**
		 * Add/Remove the scheduled action based on the setting.
		 *
		 * @param string $old_value - Old Value of the setting.
		 * @param string $new_value - New Value of the setting.
		 *
		 * @since 8.20.0
		 */
		public static function wcap_email_reports_frequency_scheduled_action( $old_value, $new_value ) {
			if ( '' !== $new_value ) {
				as_unschedule_action( 'wcap_email_reports_frequency_action' );
				if ( false !== as_next_scheduled_action( 'wcap_email_reports_frequency_action' ) ) {
					as_unschedule_action( 'wcap_email_reports_frequency_action' );
				}
				if ( 'weekly' === $new_value ) {
					$cron_interval = strtotime( 'next monday' );
				} elseif ( 'monthly' === $new_value ) {
					$cron_interval = strtotime( 'first day of next month' );
				}
				$cron_interval = $cron_interval - strtotime( 'today' );
				as_schedule_recurring_action( time(), $cron_interval, 'wcap_email_reports_frequency_action' );
			}
		}

		/** 
		 * This function will load default template while activating the plugin.
		 * @globals mixed $wpdb
		 * @since 2.3.5
		 */
		function wcap_create_default_email_templates( $db_prefix = '', $blog_id = 0 ) {
			if( $db_prefix === '' ) {
				return;
			}

			global $wpdb;

			$template_name_array    = array ( 'Initial', 'Interim', 'Final' );
			$site_title             = get_bloginfo( 'name' );
			$template_subject_array = array ( "Hey {{customer.firstname}}!! You left something in your cart", "Still Interested?", "10% off | We miss you…and so does your cart" );
			$active_post_array      = array ( 0, 0, 0 );
			$email_frequency_array  = array ( 15, 1, 24 );
			$day_or_hour_array      = array ( 'Minutes', 'Hours', 'Hours' );
			$discount_type          = "percent";
			$discount_shipping      = "no";
			$discount_expiry        = "7-days";

			$content = array();

			for ( $temp_num=1; $temp_num < 4; $temp_num++ ) { 
				ob_start();
				include( WCAP_PLUGIN_PATH . '/assets/html/templates/default_' . $temp_num . '.html' );
				$content[$temp_num] = ob_get_clean();
			}

			$body_content_array     = array ( 
				addslashes ( $content[1] ),
				addslashes ( $content[2] ),
				addslashes ( $content[3] ) 
			);

			$header_text = array(
				addslashes('You left Something in Your Cart!'),
				addslashes('We saved your cart.'),
				addslashes('It\'s not too late!')
			);

			$coupon_code_id   = '';
			$default_template = 1;
			$discount_array   = array( '0', '0', '10' );
			$is_wc_template   = 0 ;

			for ( $insert_count = 0 ; $insert_count < 3 ; $insert_count++ ) {

				$query = "INSERT INTO `" . $db_prefix . "ac_notification_templates" . "`
				( notification_type, email_type, subject, body, is_active, frequency, day_or_hour, coupon_code, template_name, default_template, discount, discount_type, discount_shipping, discount_expiry, is_wc_template, wc_email_header )
				VALUES (
							'email',
							'abandoned_cart_email',
							'" . $template_subject_array [ $insert_count ] . "',
							'" . $body_content_array [ $insert_count ] . "',
							'" . $active_post_array [ $insert_count ] . "',
							'" . $email_frequency_array [ $insert_count ] . "',
							'" . $day_or_hour_array [ $insert_count ] . "',
							'" . $coupon_code_id . "',
							'" . $template_name_array [ $insert_count ] . "',
							'" . $default_template . "',
							'" . $discount_array [ $insert_count ] . "',
							'" . $discount_type . "',
							'" . $discount_shipping . "',
							'" . $discount_expiry . "',
							'" . $is_wc_template . "',
							'" . $header_text [ $insert_count ] . "' )";

				$wpdb->query( $query );//phpcs:disable

			}

			if( $blog_id === 0 ) {
				add_option( 'wcap_new_default_templates', 1 );
			} else {
				add_blog_option( $blog_id, 'wcap_new_default_templates', 1 );
			}
			$this->wcap_create_default_email_followup_templates( $db_prefix, $blog_id );
		}

		/**
		 * Create default follow-up Templates.
		 *
		 * @param string $db_prefix - DB prefix.
		 * @param int    $blog_id - Blog ID.
		 * @since 8.21.0
		 */
		public function wcap_create_default_email_followup_templates( $db_prefix = '', $blog_id = 0 ) {
			if ( $db_prefix === '' ) {
				return;
			}

			global $wpdb;

			$template_name_array    = array( 'Review Reminder Template', 'Coupon for Next Purchase' );
			$site_title             = get_bloginfo( 'name' );
			$template_subject_array = array( 'Your feedback matters a lot to us :)', 'Flat 10% off on your next order!!' );
			$active_post_array      = array( 0, 0 );
			$email_frequency_array  = array( 7, 30 );
			$day_or_hour_array      = array( 'Days', 'Days' );
			$discount_type          = 'percent';
			$discount_shipping      = 'no';
			$discount_expiry        = '1-days';
			$generate_unique_code   = array( '0', '1' );

			$content = array();
			$i       = 0;
			for ( $temp_num = 4; $temp_num < 6; $temp_num++ ) {
				ob_start();
				include( WCAP_PLUGIN_PATH . '/assets/html/templates/default_' . $temp_num . '.html' );
				$content[$i] = ob_get_clean();
				$i++;
			}

			$body_content_array = array(
				addslashes( $content[0] ),
				addslashes( $content[1] ),
			);

			$header_text = array(
				addslashes( "We'd love your feedback" ),
				addslashes( 'Save 10% on your next order' ),
			);

			$rules_array      = array();
			$rules_array[]    = array(
				'rule_type'      => 'order_status',
				'rule_condition' => 'includes',
				'rule_value'     => array(
					'wc-completed',
				),
			);
			$rules            = wp_json_encode( $rules_array );
			$coupon_code_id   = '';
			$default_template = 1;
			$discount_array   = array( '0', '10' );
			$is_wc_template   = 0;

			for ( $insert_count = 0; $insert_count < 2; $insert_count++ ) {

				$query = "INSERT INTO `" . $db_prefix . "ac_notification_templates`
				( notification_type, email_type, rules, match_rules, subject, body, is_active, frequency, day_or_hour, coupon_code, template_name, default_template, discount, discount_type, discount_shipping, discount_expiry, generate_unique_coupon_code, is_wc_template, wc_email_header )
				VALUES (
							'email',
							'follow-up',
							'" . $rules . "',
							'all',
							'" . $template_subject_array[ $insert_count ] . "',
							'" . $body_content_array[ $insert_count ] . "',
							'" . $active_post_array[ $insert_count ] . "',
							'" . $email_frequency_array[ $insert_count ] . "',
							'" . $day_or_hour_array[ $insert_count ] . "',
							'" . $coupon_code_id . "',
							'" . $template_name_array[ $insert_count ] . "',
							'" . $default_template . "',
							'" . $discount_array[ $insert_count ] . "',
							'" . $discount_type . "',
							'" . $discount_shipping . "',
							'" . $discount_expiry . "',
							'" . $generate_unique_code[ $insert_count ] . "',
							'" . $is_wc_template . "',
							'" . $header_text[ $insert_count ] . "' )";

				$wpdb->query( $query ); // phpcs:ignore

			}
			if( $blog_id === 0 ) {
				add_option( 'wcap_new_default_followup_templates', 1 );
			} else {
				add_blog_option( $blog_id, 'wcap_new_default_followup_templates', 1 );
			}
		}

		/**
		 * Default SMS templates.
		 *
		 * @param string $db_prefix - DB Prefix.
		 * @param int    $blog_id - Blog ID.
		 */
		public function wcap_create_default_sms_templates( $db_prefix = '', $blog_id = 0 ) {

			global $wpdb;

			$get_count = $wpdb->get_var(
				"SELECT COUNT(id) FROM " . $db_prefix . "ac_notification_templates" . " WHERE `default_template` = '1' AND `notification_type` = 'sms'"
			);

			if ( isset( $get_count ) && $get_count == 0 ) {

				$wpdb->query(
					"INSERT INTO " . $db_prefix . "ac_notification_templates" . " 
					( notification_type, email_type, subject, body, is_active, frequency, day_or_hour, coupon_code, template_name, default_template, discount, discount_type, discount_shipping, discount_expiry, is_wc_template, wc_email_header, rules, activated_time )
							VALUES
							( 
								'sms',
								'',
								'',
								'Hey {{user.name}}, I noticed you left some products in your cart at {{shop.link}}. If you have any queries, please get in touch with me on {{phone.number}}. - {{shop.name}}',
								'0', 
								'30',
								'minutes',
								'', 
								'Initial',
								'1',
								'',
								'',
								'',
								'',
								'0',
								'' ,
								'',
								0
							),
							( 
								'sms',
								'',
								'',
								'Hey {{user.name}}, we have saved your cart at {{shop.name}}. Complete your purchase using {{checkout.link}} now!',  
								'0', 
								'1',
								'days', 
								'', 
								'Final',
								'1',
								'',
								'',
								'',
								'',
								'0',
								'',
								'',
								0
							)"
				);

			}
		}

		/**
		 * Default FB templates.
		 *
		 * @param string $db_prefix - DB Prefix.
		 * @param int    $blog_id - Blog ID.
		 */
		public function wcap_create_default_fb_templates( $db_prefix = '', $blog_id = 0 ) {
			global $wpdb;

			$get_count = $wpdb->get_var(
				"SELECT COUNT(id) FROM " . $db_prefix . "ac_notification_templates" . " WHERE `default_template` = '1' AND `notification_type` = 'fb'"
			);

			$default_fb_body = array( 
				'{"header":"We saved your cart","subheader":"Purchase now before they are out of stock","header_image":"' . esc_url( WCAP_PLUGIN_URL ) . '/includes/fb-recovery/assets/css/images/carts_div.png","checkout_text":"Checkout Now!","unsubscribe_text":"Unsubscribe"}',
				'{"header":"You left some items in your cart","subheader":"We have saved some items in your cart","header_image":"' . esc_url( WCAP_PLUGIN_URL ) . '/includes/fb-recovery/assets/css/images/carts_div.png","checkout_text":"Checkout","unsubscribe_text":"Unsubscribe"}'
			);
			
			if( isset( $get_count ) && $get_count == 0 ) {

				// add 2 default sms templates and 2 FB templates
				$wpdb->query(
					"INSERT INTO " . $db_prefix . "ac_notification_templates" . " 
					( notification_type, email_type, subject, body, is_active, frequency, day_or_hour, coupon_code, template_name, default_template, discount, discount_type, discount_shipping, discount_expiry, is_wc_template, wc_email_header, rules, activated_time )
						VALUES
						( 
							'fb',
							'',
							'Hey there, We noticed that you left some great products in your cart at " . get_bloginfo( 'name' ) . ". Do not worry we saved them for you:',
							'" . $default_fb_body[0] . "',  
							'0', 
							'30',
							'minutes', 
							'', 
							'',
							'1',
							'',
							'',
							'',
							'',
							'0',
							'' ,
							'',
							0
						),
						( 
							'fb',
							'',
							'Hey there, There are some great products in your cart you left behind at " . get_bloginfo( 'name' ) . ". Here is a list of items you left behind:',
							'" . $default_fb_body[1] . "', 
							'0', 
							'6',
							'hours', 
							'', 
							'',
							'1',
							'',
							'',
							'',
							'',
							'0',
							'' ,
							'',
							0
						)"
				);

			}

			// 7.10.0 added default setting for consent text.
			//phpcs:enable
			if ( $blog_id === 0 ) {
				if( ! get_option( 'wcap_fb_consent_text' ) ) {
					add_option( 'wcap_fb_consent_text', 'Allow order status to be sent to Facebook Messenger' );
				}
			} else {
				if( ! get_blog_option( $blog_id, 'wcap_fb_consent_text' ) ) {
					add_blog_option( $blog_id, 'wcap_fb_consent_text', 'Allow order status to be sent to Facebook Messenger' );
				}
			}

		}
	}

}
