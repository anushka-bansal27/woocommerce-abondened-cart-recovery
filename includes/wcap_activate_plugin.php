<?php
/**
 *
 * It will call the function when we activate the plugin.
 *
 * @author  Tyche Softwares
 * @package Abandoned-Cart-Pro-for-WooCommerce/Admin/Activate-plugin
 * @since   5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( ! class_exists( 'Wcap_Activate_Plugin' ) ) {
	/**
	 * It will add the cron job, and create the tables and the options needed for plugin.
	 *
	 * @since 5.0
	 */
	class Wcap_Activate_Plugin {
		/**
		 * It will create the cron job needed for the abandoned cart reminder emails.
		 *
		 * @since 5.0
		 */
		public static function wcap_create_cron_job() {
			add_filter( 'cron_schedules', array( __CLASS__, 'wcap_add_cron_schedule' ) );
			self::wcap_schedule_cron_job();
		}
		/**
		 * It will create the cron job interval.
		 * Default value will be 15 minutes.
		 * If customer has changed the cron job interval time from the settings then it will be considered.
		 *
		 * @param array $schedules Array of all schedule events.
		 * @return array $schedule Array of new added schedule event.
		 * @since 5.0
		 */
		public static function wcap_add_cron_schedule( $schedules ) {
			$duration = get_option( 'wcap_cron_time_duration' );
		    if ( isset( $duration ) && $duration > 0 ) {
		        $duration_in_seconds = $duration * 60;
		    } else {
		        $duration_in_seconds = 900;
		    }
		    $schedules['15_minutes'] = array(
		               'interval'    => $duration_in_seconds,  // 15 minutes in seconds
		               'display'     => __( 'Once Every Fifteen Minutes', 'woocommerce-ac' ),
		    );

		    $schedules['wcap_15_days'] = array(
		               'interval'    => 1296000, // 15 days in seconds
		               'display'     => __( 'Once Every Fifteen Days','woocommerce-ac' ),
		    );
		    return $schedules;
		}
		/**
		 * It will check if the next cron job has been scheduled or not. It will be recurring event that will check 
		 * that next cron job has been set or not. 
		 * If it is not set then it will set it.
		 * @since 5.0
		 */
		public static function wcap_schedule_cron_job() {
			// cron job for deleting carts after X days.
			if ( ! wp_next_scheduled( 'wcap_clear_carts' ) ) {
				wp_schedule_event( time(), 'daily', 'wcap_clear_carts' );
			}
		}

	    /** 
	     * This function will load default settings when plugin is activated.
	     * @globals mixed $wpdb
	     * @globals mixed $woocommerce
	     * @since: 2.3.5
	     */
	    public static function wcap_activate() {

	    	// check whether its a multi site install or a single site install.
	    	if ( is_multisite() ) {
        		
        		$blog_list = get_sites();
        		foreach ( $blog_list as $blog_list_key => $blog_list_value ) {
             		if ( $blog_list_value->blog_id > 1 ) { // child sites.
                		$blog_id = $blog_list_value->blog_id;
                		self::wcap_process_activate( $blog_id );
            		} else { // parent site.
            			self::wcap_process_activate();
            		}
            	}
            } else { // single site.
            	self::wcap_process_activate();
            }
        }

        /**
         * Activation code for the site
         */
		static function wcap_process_activate( $blog_id = 0 ) {
			global $woocommerce, $wpdb;

			$db_prefix = ( $blog_id === 0 ) ? $wpdb->prefix : $wpdb->prefix . $blog_id . "_";

			// Create the DB version record.
			if ( $blog_id === 0 ) {
				add_option( 'woocommerce_ac_db_version', WCAP_PLUGIN_VERSION );
			} else {
				add_blog_option( $blog_id, 'woocommerce_ac_db_version', WCAP_PLUGIN_VERSION );
			}
		    $wcap_collate = '';
		    if ( $wpdb->has_cap( 'collation' ) ) {
		        $wcap_collate = $wpdb->get_charset_collate();//phpcs:disable
		    }
		    $sql = "CREATE TABLE IF NOT EXISTS ". $db_prefix . "ac_notification_templates" ." (
		            `id` int(11) NOT NULL AUTO_INCREMENT,
					`notification_type` VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL,
					`email_type` VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL,
		            `subject` VARCHAR(255) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
		            `body` mediumtext CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
		            `is_active` enum('0','1') COLLATE utf8_unicode_ci NOT NULL,
		            `frequency` int(11) NOT NULL,
		            `day_or_hour` enum('Minutes','Days','Hours') COLLATE utf8_unicode_ci NOT NULL,
		            `coupon_code` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
		            `template_name` text COLLATE utf8_unicode_ci NOT NULL,
		            `default_template` int(11) COLLATE utf8_unicode_ci NOT NULL,
		            `discount` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
		            `discount_type` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
		            `discount_shipping` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
					`discount_expiry` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
					`individual_use` enum('0','1') COLLATE utf8_unicode_ci NOT NULL,
		            `generate_unique_coupon_code` enum('0','1') COLLATE utf8_unicode_ci NOT NULL,
		            `is_wc_template` enum('0','1') COLLATE utf8_unicode_ci NOT NULL,
					`wc_email_header` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
					`match_rules` VARCHAR(50) COLLATE utf8_unicode_ci DEFAULT 'all' NOT NULL,
					`rules` VARCHAR(500) COLLATE utf8_unicode_ci NOT NULL,
		            `activated_time` int(11) NOT NULL,
		            PRIMARY KEY (`id`)
		            ) $wcap_collate AUTO_INCREMENT=1 ";
					 require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
					 $wpdb->query( $sql );

		    $sql_query = "CREATE TABLE IF NOT EXISTS ". $db_prefix . "ac_sent_history" ." (
		                `id` int(11) NOT NULL auto_increment,
						`notification_type` VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL,
		                `template_id` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
		                `cart_id` int(11) NOT NULL,
		                `sent_time` datetime NOT NULL,
		                `sent_notification_contact` text COLLATE utf8_unicode_ci NOT NULL,
		                `recovered_order` enum('0','1') COLLATE utf8_unicode_ci NOT NULL,
						`wc_order_id` bigint(20) NOT NULL,
						`encrypt_key` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
		                 PRIMARY KEY  (`id`),
		                 INDEX order_id (cart_id)
		                ) $wcap_collate AUTO_INCREMENT=1 ";
		    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		    $wpdb->query( $sql_query );

		    $opened_query = "CREATE TABLE IF NOT EXISTS " . $db_prefix . "ac_opened_notifications" . " (
		                    `id` int(11) NOT NULL AUTO_INCREMENT,
		                    `notification_sent_id` int(11) NOT NULL,
		                    `time_opened` datetime NOT NULL,
		                    PRIMARY KEY (`id`)
		                    ) $wcap_collate COMMENT='store the primary key id of opened email template' AUTO_INCREMENT=1 ";
		    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		    $wpdb->query( $opened_query );

		    $clicked_query = "CREATE TABLE IF NOT EXISTS " . $db_prefix . "ac_link_clicked_notifications" . " (
		                    `id` int(11) NOT NULL AUTO_INCREMENT,
		                    `notification_sent_id` int(11) NOT NULL,
		                    `link_clicked` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
		                    `time_clicked` datetime NOT NULL,
		                    PRIMARY KEY (`id`)
		                    ) $wcap_collate COMMENT='store the link clicked in sent email template' AUTO_INCREMENT=1 ";
		    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		    $wpdb->query( $clicked_query );

		    $history_query = "CREATE TABLE IF NOT EXISTS " . $db_prefix . "ac_abandoned_cart_history" . " (
		                    `id` int(11) NOT NULL AUTO_INCREMENT,
		                    `user_id` int(11) NOT NULL,
		                    `abandoned_cart_info` text COLLATE utf8_unicode_ci NOT NULL,
		                    `abandoned_cart_time` int(11) NOT NULL,
		                    `cart_ignored` enum('0','1','2','3','4') COLLATE utf8_unicode_ci NOT NULL,
		                    `recovered_cart` bigint(20) NOT NULL,
		                    `unsubscribe_link` enum('0','1') COLLATE utf8_unicode_ci NOT NULL,
		                    `user_type` text,
		                    `language` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
		                    `session_id` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
		                    `ip_address` longtext COLLATE utf8_unicode_ci NOT NULL,
		                    `email_reminder_status` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
							`wcap_trash` varchar(1) COLLATE utf8_unicode_ci NOT NULL,
							`checkout_link` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
							`sms_reminder_status` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
							`fb_reminder_status` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
							`whatsapp_reminder_status` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
		                    PRIMARY KEY (`id`),
		                    INDEX id (id)
		                    ) $wcap_collate";
		    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		    $wpdb->query( $history_query );

		    $ac_guest_history_query      = "CREATE TABLE IF NOT EXISTS " . $db_prefix . "ac_guest_abandoned_cart_history" . " (
		                                    `id` int(15) NOT NULL AUTO_INCREMENT,
		                                    `billing_first_name` text,
		                                    `billing_last_name` text,
		                                    `billing_country` text,
		                                    `billing_company_name` text,
		                                    `billing_address_1` text,
		                                    `billing_address_2` text,
		                                    `billing_city` text,
		                                    `billing_county` text,
		                                    `billing_zipcode` text,
		                                    `email_id` text,
		                                    `phone` text,
		                                    `ship_to_billing` text,
		                                    `order_notes` text,
		                                    `shipping_first_name` text,
		                                    `shipping_last_name` text,
		                                    `shipping_company_name` text,
		                                    `shipping_address_1` text,
		                                    `shipping_address_2` text,
		                                    `shipping_city` text,
		                                    `shipping_county` text,
		                                    `shipping_zipcode` text,
		                                    `shipping_charges` double,
		                                    PRIMARY KEY (`id`),
		                                    INDEX id (id)
		                                    ) $wcap_collate AUTO_INCREMENT=63000000";
		    require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
		    $wpdb->query( $ac_guest_history_query );
		    if( $blog_id === 0 ) {
		    	update_option( 'wcap_alter_guest_columns', '1', 'no' );
		    } else {
		    	update_blog_option( $blog_id, 'wcap_alter_guest_columns', '1' );
			}
		    /**
		     * @since 7.11.0
		     * Integration with Aelia Currency Switcher
		     */
		    $aelia_table = $db_prefix . "abandoned_cart_aelia_currency";

            $aelia_sql = "CREATE TABLE IF NOT EXISTS $aelia_table (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `abandoned_cart_id` int(11) COLLATE utf8_unicode_ci NOT NULL,
                    `acfac_currency` text COLLATE utf8_unicode_ci NOT NULL,
                    `date_time` TIMESTAMP on update CURRENT_TIMESTAMP COLLATE utf8_unicode_ci NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`)
                    ) $wcap_collate AUTO_INCREMENT=1 ";           
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            $wpdb->query( $aelia_sql );

            $default_template = new Wcap_Default_Settings;
            
            // Default templates:  function call to create default templates.
		    $check_table_empty  = $wpdb->get_var( "SELECT COUNT(*) FROM `" . $db_prefix . "ac_notification_templates" . "`" );
	            if ( 0 == $check_table_empty ) {
	                $default_template->wcap_create_default_email_templates( $db_prefix, $blog_id );
	                if( $blog_id === 0 ) {
	                	update_option( "wcap_ac_default_templates_installed", "yes" );
	                } else {
	                	update_blog_option( $blog_id, "wcap_ac_default_templates_installed", "yes" );
	                }
	            }
	        
	        if( $blog_id === 0 ) {
	        	$default_settings_created = get_option( 'ac_enable_cart_emails' );
	        } else {
	        	$default_settings_created = get_blog_option( $blog_id, 'ac_enable_cart_emails' );
	        }
		    //Default settings, if option table do not have any entry.
		    if ( ! $default_settings_created ) {
		        // function call to create default settings.
		        $default_template->wcap_create_default_settings( $blog_id );
		    }

		    /**
		     * This is added for those user who Install the plguin first time.
		     * So for them this option will be enabled.
		     */
		    if( $blog_id === 0 ) {
	        	if( ! get_option( 'ac_track_guest_cart_from_cart_page' ) ) {
	        		add_option( 'ac_track_guest_cart_from_cart_page', '' );
	        	}
	        } else {
	        	if( ! get_blog_option( $blog_id, 'ac_track_guest_cart_from_cart_page' ) ) {
	        		add_blog_option( $blog_id, 'ac_track_guest_cart_from_cart_page', '' );
	        	}
	        }
			
			$sql_tinyurls = "CREATE TABLE IF NOT EXISTS " . $db_prefix . "ac_tiny_urls" . " (
                    			`id` int(11) NOT NULL AUTO_INCREMENT,
                    			`cart_id` int(11) NOT NULL,
                    			`template_id` int(11) NOT NULL,
                    			`long_url` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                    			`short_code` VARCHAR(10) COLLATE utf8mb4_unicode_ci NOT NULL,
                    			`date_created` int(11) NOT NULL,
                    			`counter` int(11) NOT NULL DEFAULT '0',
                    			`notification_data` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                    			PRIMARY KEY (`id`),
                    			KEY short_code (`short_code`)
                    			) $wcap_collate AUTO_INCREMENT=1000000";

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
			$wpdb->query( $sql_tinyurls );

			// Call functions to create default SMS & FB templates.
			$default_template->wcap_create_default_sms_templates( $db_prefix, $blog_id );
			$default_template->wcap_create_default_fb_templates( $db_prefix, $blog_id );

			// 8.10.0 - ATC Rules and Statistics.
			$atc_rules_table  = $db_prefix . 'ac_atc_rules';
			$create_atc_table = "CREATE TABLE IF NOT EXISTS $atc_rules_table (
				`id` int(11) NOT NULL auto_increment,
				`name` varchar(100) collate utf8_unicode_ci NOT NULL,
				`match_rules` VARCHAR(50) COLLATE utf8_unicode_ci DEFAULT 'all' NOT NULL,
				`rules` VARCHAR(500) COLLATE utf8_unicode_ci NOT NULL,
				`popup_type` VARCHAR(500) COLLATE utf8_unicode_ci DEFAULT 'atc' NOT NULL,
				`is_active` enum('0','1') COLLATE utf8_unicode_ci NOT NULL,
				`frontend_settings` LONGTEXT COLLATE utf8_unicode_ci NOT NULL,
				`coupon_settings` LONGTEXT COLLATE utf8_unicode_ci NOT NULL,
				`quick_checkout_settings` LONGTEXT COLLATE utf8_unicode_ci NOT NULL,
				PRIMARY KEY  (`id`),
				INDEX id (id)
				) $wcap_collate AUTO_INCREMENT=1";
			$wpdb->query( $create_atc_table );

			$atc_stats_table    = $db_prefix . 'ac_statistics';
			$create_stats_table = "CREATE TABLE IF NOT EXISTS $atc_stats_table (
				`id` int(11) NOT NULL auto_increment,
				`template_id` int(11) collate utf8_unicode_ci NOT NULL,
				`template_type` VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL,
				`event` enum('0','1','2','3','4','5') COLLATE utf8_unicode_ci NOT NULL,
				`timestamp` int(11) COLLATE utf8_unicode_ci NOT NULL,
				PRIMARY KEY  (`id`),
				INDEX id (id)
				) $wcap_collate AUTO_INCREMENT=1";
			$wpdb->query( $create_stats_table );

			$get_atc_rules_count = $wpdb->get_var(
                'SELECT count(id) FROM ' . $atc_rules_table
            );

			if ( isset( $get_atc_rules_count ) && 0 === (int) $get_atc_rules_count ) {
				// Front end Settings.
				$frontend_settings = wp_json_encode(
					array(
						'wcap_heading_section_text_email' => 'Please enter your email',
						'wcap_text_section_text'          => 'To add this item to your cart, please enter your email address.',
						'wcap_email_placeholder_section_input_text' => 'Email address',
						'wcap_button_section_input_text'  => 'Add to Cart',
						'wcap_atc_mandatory_email'        => 'on',
						'wcap_popup_heading_color_picker' => '#737f97',
						'wcap_popup_text_color_picker'    => '#bbc9d2',
						'wcap_button_color_picker'        => '#0085ba',
						'wcap_button_text_color_picker'   => '#ffffff',
						'wcap_non_mandatory_text'         => 'No thanks',
						'wcap_atc_capture_phone'          => 'off',
						'wcap_atc_phone_placeholder'      => 'Phone number (e.g. +19876543210)',
					)
				);

				// Coupon Settings. 
				$coupon_settings = wp_json_encode(
					array(
						'wcap_atc_auto_apply_coupon_enabled' => '',
						'wcap_atc_coupon_type'               => '',
						'wcap_atc_popup_coupon'              => '',
						'wcap_atc_discount_type'             => '',
						'wcap_atc_discount_amount'           => '',
						'wcap_atc_coupon_free_shipping'      => '',
						'wcap_atc_popup_coupon_validity'     => '',
						'wcap_countdown_timer_msg'           => 'Coupon <coupon_code> expires in <hh:mm:ss>. Avail it now.',
						'wcap_countdown_msg_expired'         => '',
						'wcap_countdown_cart'                => 'on',
					)
				);

				$rules = wp_json_encode( array() );

				// Add default template.
				$wpdb->insert( // phpcs:ignore
					$atc_rules_table,
					array(
						'name'                    => __( 'Show on standard WooCommerce pages', 'woocommerce-ac' ),
						'match_rules'             => 'all',
						'rules'                   => $rules,
						'popup_type'              => 'atc',
						'is_active'               => 0,
						'frontend_settings'       => $frontend_settings,
						'coupon_settings'         => $coupon_settings,
						'quick_checkout_settings' => wp_json_encode( array() ),
					)
				);

				// Exit Intent Popup
				// Front end Settings.
				$frontend_settings = wp_json_encode(
					array(
						'wcap_heading_section_text_email' => 'Please enter your email',
						'wcap_text_section_text'          => 'We are sad to see you go but you can enter your email below and we will save the cart for you.',
						'wcap_email_placeholder_section_input_text' => 'Email address',
						'wcap_button_section_input_text'  => 'Complete my order!',
						'wcap_atc_mandatory_email'        => 'on',
						'wcap_popup_heading_color_picker' => '#737f97',
						'wcap_popup_text_color_picker'    => '#bbc9d2',
						'wcap_button_color_picker'        => '#0085ba',
						'wcap_button_text_color_picker'   => '#ffffff',
						'wcap_non_mandatory_text'         => 'No thanks',
						'wcap_atc_capture_phone'          => 'off',
						'wcap_atc_phone_placeholder'      => 'Phone number (e.g. +19876543210)',
					)
				);

				// Coupon Settings. 
				$coupon_settings = wp_json_encode(
					array(
						'wcap_atc_auto_apply_coupon_enabled' => '',
						'wcap_atc_coupon_type'               => '',
						'wcap_atc_popup_coupon'              => '',
						'wcap_atc_discount_type'             => '',
						'wcap_atc_discount_amount'           => '',
						'wcap_atc_coupon_free_shipping'      => '',
						'wcap_atc_popup_coupon_validity'     => '',
						'wcap_countdown_timer_msg'           => 'Coupon <coupon_code> expires in <hh:mm:ss>. Avail it now.',
						'wcap_countdown_msg_expired'         => '',
						'wcap_countdown_cart'                => 'on',
					)
				);

				$quick_ck = wp_json_encode(
					array(
						'wcap_enable_ei_for_registered_users' => 'on',
						'wcap_quick_ck_force_user_to_checkout' => 'off',
						'wcap_quick_ck_modal_heading'     => 'We are sad to see you leave',
						'wcap_quick_ck_modal_heading_color' => '#737f97',
						'wcap_quick_ck_modal_text'        => 'There are some items in your cart. These will not last long. Please proceed to checkout to complete the purchase.',
						'wcap_quick_ck_modal_text_color'  => '#bbc9d2',
						'wcap_quick_ck_link_text'         => 'Complete my order!',
						'wcap_quick_ck_link_button_color' => '#0085ba',
						'wcap_quick_ck_link_text_color'   => '#ffffff',
						'wcap_quick_ck_redirect_to'       => wc_get_checkout_url(),
					)
				);
				$rules = wp_json_encode( array() );

				// Add default template.
				$wpdb->insert( // phpcs:ignore
					$atc_rules_table,
					array(
						'name'                    => __( 'Exit Intent for all pages', 'woocommerce-ac' ),
						'match_rules'             => 'all',
						'rules'                   => $rules,
						'popup_type'              => 'exit_intent',
						'is_active'               => 0,
						'frontend_settings'       => $frontend_settings,
						'coupon_settings'         => $coupon_settings,
						'quick_checkout_settings' => $quick_ck,
					)
				);
			}

			// 8.15.0 - Connector table.
			$wcap_connector_table   = $db_prefix . 'ac_connector_sync';
			$create_connector_table = "CREATE TABLE IF NOT EXISTS $wcap_connector_table (
				`id` int(11) NOT NULL auto_increment,
				`cart_id` int(11) NOT NULL,
				`connector_cart_id` VARCHAR(500) COLLATE utf8_unicode_ci NOT NULL,
				`connector_name` VARCHAR(100) COLLATE utf8_unicode_ci NOT NULL,
				`sync_date` int(11) NOT NULL,
				`status` VARCHAR(100) COLLATE utf8_unicode_ci NOT NULL,
				`sync_data` LONGTEXT COLLATE utf8_unicode_ci NOT NULL,
				`message` LONGTEXT COLLATE utf8_unicode_ci NOT NULL,
				PRIMARY KEY  (`id`),
				INDEX id (id)
				) $wcap_collate AUTO_INCREMENT=1";
			$wpdb->query( $create_connector_table );

			// Issue #3744.
			$table_name  = $db_prefix . 'ac_notification_templates';
			$column_info = $wpdb->get_row(
				$wpdb->prepare(
					"SHOW COLUMNS FROM $table_name LIKE %s",
					'rules'
				)
			);
			if ( $column_info && strpos( $column_info->Type, 'varchar(500)' ) !== false ) {
			// Change rules column to varchar(1500)
				$wpdb->query(
					"ALTER TABLE $table_name MODIFY rules VARCHAR(1500)"
				);
			}

			do_action( 'wcap_after_activate_plugin' );
			
		}
	}

	//$enable_email = get_option( 'ac_enable_cart_emails' );
	//if( 'on' == $enable_email ) {
		Wcap_Activate_Plugin::wcap_create_cron_job();
	//}
}
