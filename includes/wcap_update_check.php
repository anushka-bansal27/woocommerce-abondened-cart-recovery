<?php
/**
 * It will update the tables, options and any other changes when we update the plugin.
 * @author   LittleCodr
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Update
 * @since 5.0
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if ( !class_exists('Wcap_Update_Check' ) ) {
    /**
     * It will update the tables, options and any other changes when we update the plugin.
     */
    class Wcap_Update_Check {

        /**
         * sha256 of a legacy hardcoded default that shipped as ac_security_key on
         * old installs -- every site with an unmigrated key shared this same value.
         * Hashed (not stored in plaintext) so it isn't a live, copy-pasteable secret;
         * comparing hashes here detects the same legacy value just as reliably.
         */
        const LEGACY_DEFAULT_KEY_HASH = 'df1d175cfdcf447f62d6af03417c79737da973d958fdff4a81b77b70ae55164b'; //gitleaks:allow -- this is a one-way sha256 hash, not a usable secret.

        /**
         * It will upadate the tables and other options for the plugin, it will be called when we upadate the plugin.
         * @globals mixed $wpdb
         * @globals int | string $woocommerce_ac_plugin_version Old version of plugin
         * @globals 
         * @since 5.0
         */
        public static function wcap_update_db_check() {

			global $wpdb, $woocommerce_ac_plugin_version;
			// NOTE: The version number for the default value should always be updated to the latest version.
            $woocommerce_ac_plugin_version = get_option( 'woocommerce_ac_db_version', WCAP_PLUGIN_VERSION );

            if ( $woocommerce_ac_plugin_version != WCAP_PLUGIN_VERSION ) {

                // check whether its a multi site install or a single site install
                if ( is_multisite() ) {
                    
                    // check if tables exist for the child sites, if not, create
                    if( 'yes' != get_blog_option( 1, 'wcap_update_multisite' ) ) {
                        // run the activate function
                        Wcap_Activate_Plugin::wcap_activate();
                        update_blog_option( 1, 'wcap_update_multisite', 'yes' );
                    }
                    $blog_list = get_sites();
                    foreach( $blog_list as $blog_list_key => $blog_list_value ) {
                        if( $blog_list_value->blog_id > 1 ){ // child sites
                            $blog_id = $blog_list_value->blog_id;
                            self::wcap_process_db_update( $blog_id );
                        } else { // parent site
                            self::wcap_process_db_update();
                        }
                    }
                } else { // single site
                    self::wcap_process_db_update();
                }
            }
        }

        static function wcap_process_db_update( $blog_id = 0 ) {
                
            global $woocommerce, $wpdb;

            $db_prefix = ( $blog_id === 0 ) ? $wpdb->prefix : $wpdb->prefix . $blog_id . "_";

            if( $blog_id === 0 ) {
                //get the option, if it is not set to individual then convert to individual records and delete the base record
                $ac_settings = get_option( 'ac_settings_status' );
                if ( $ac_settings != 'INDIVIDUAL' ) {

                    //fetch the existing settings and save them as inidividual to be used for the settings API
                    $woocommerce_ac_settings = json_decode( get_option( 'woocommerce_ac_settings' ) );
                    add_option( 'ac_enable_cart_emails',              $woocommerce_ac_settings[0]->enable_cart_notification );
                    add_option( 'ac_cart_abandoned_time',             $woocommerce_ac_settings[0]->cart_time );
                    add_option( 'ac_delete_abandoned_order_days',     $woocommerce_ac_settings[0]->delete_order_days );
                    add_option( 'ac_email_admin_on_recovery',         $woocommerce_ac_settings[0]->email_admin );
                    add_option( 'ac_track_coupons',                   $woocommerce_ac_settings[0]->track_coupons );
                    add_option( 'ac_disable_guest_cart_email',        $woocommerce_ac_settings[0]->disable_guest_cart );
                    add_option( 'ac_disable_logged_in_cart_email',    $woocommerce_ac_settings[0]->disable_logged_in_cart );
                    add_option( 'ac_track_guest_cart_from_cart_page', $woocommerce_ac_settings[0]->disable_guest_cart_from_cart_page );
                    update_option( 'ac_settings_status', 'INDIVIDUAL' );
                    //Delete the main settings record
                    delete_option( 'woocommerce_ac_settings' );
                }
                update_option( 'woocommerce_ac_db_version', WCAP_PLUGIN_VERSION );
            } else {
                //get the option, if it is not set to individual then convert to individual records and delete the base record
                $ac_settings = get_blog_option( $blog_id, 'ac_settings_status' );
                if ( $ac_settings != 'INDIVIDUAL' ) {

                    //fetch the existing settings and save them as inidividual to be used for the settings API
                    $woocommerce_ac_settings = json_decode( get_blog_option( $blog_id, 'woocommerce_ac_settings' ) );
                    if( ! isset( $woocommerce_ac_settings[0] ) && is_multisite() ) {
                        $woocommerce_ac_settings = json_decode( get_blog_option( 1, 'woocommerce_ac_settings' ) );
                    }
                    add_blog_option( $blog_id, 'ac_enable_cart_emails',              $woocommerce_ac_settings[0]->enable_cart_notification );
                    add_blog_option( $blog_id, 'ac_cart_abandoned_time',             $woocommerce_ac_settings[0]->cart_time );
                    add_blog_option( $blog_id, 'ac_delete_abandoned_order_days',     $woocommerce_ac_settings[0]->delete_order_days );
                    add_blog_option( $blog_id, 'ac_email_admin_on_recovery',         $woocommerce_ac_settings[0]->email_admin );
                    add_blog_option( $blog_id, 'ac_track_coupons',                   $woocommerce_ac_settings[0]->track_coupons );
                    add_blog_option( $blog_id, 'ac_disable_guest_cart_email',        $woocommerce_ac_settings[0]->disable_guest_cart );
                    add_blog_option( $blog_id, 'ac_disable_logged_in_cart_email',    $woocommerce_ac_settings[0]->disable_logged_in_cart );
                    add_blog_option( $blog_id, 'ac_track_guest_cart_from_cart_page', $woocommerce_ac_settings[0]->disable_guest_cart_from_cart_page );
                    update_blog_option( $blog_id, 'ac_settings_status', 'INDIVIDUAL' );
                    //Delete the main settings record
                    delete_blog_option( $blog_id, 'woocommerce_ac_settings' );
                }
                update_blog_option( $blog_id, 'woocommerce_ac_db_version', WCAP_PLUGIN_VERSION );
            }

            if ( $wpdb->get_var( "SHOW TABLES LIKE '". $db_prefix ."ac_link_clicked_email';" ) ) {//phpcs:disable
                $check_table_query = "SHOW COLUMNS FROM `" . $db_prefix . "ac_link_clicked_email` LIKE 'link_clicked'";
                $results = $wpdb->get_results( $check_table_query );
            }
            if ( isset( $results, $results[0]->Type ) && $results[0]->Type == 'varchar(60)' ) {
                $alter_table_query = "ALTER TABLE `". $db_prefix . "ac_link_clicked_email` MODIFY COLUMN link_clicked varchar (500)";
                $wpdb->get_results( $alter_table_query );
            }

            /**
             * As we do not use the trash feature in sent emails tab we dont need the trash coulmn in the database.
             * @since: 7.6
             */
            if ( $wpdb->get_var( "SHOW COLUMNS FROM `". $db_prefix ."ac_sent_history` LIKE 'wcap_sent_trash';" ) ) {
                $wpdb->query( "ALTER TABLE ".$db_prefix."ac_sent_history DROP COLUMN `wcap_sent_trash`;" );
            }
                
            // @since 7.7 - Add new cart status for cart_ignored column in cart history table.
            $check_table_query = "SHOW COLUMNS FROM " . $db_prefix . "ac_abandoned_cart_history LIKE 'cart_ignored'";
            $results = $wpdb->get_results( $check_table_query );
            if ( isset( $results, $results[0]->Type ) && ( $results[0]->Type == "enum('0','1')" OR $results[0]->Type == "enum('0','1','2')" ) ) {
                $alter_table_query = "ALTER TABLE " . $db_prefix . "ac_abandoned_cart_history MODIFY COLUMN cart_ignored enum('0','1','2','3')";
                $wpdb->query( $alter_table_query );
            }
                
            /**
             * Create 3 new tables
             * @since 7.9
             */
            $wcap_collate = '';
            if ( $wpdb->has_cap( 'collation' ) ) {
                $wcap_collate = $wpdb->get_charset_collate();
            }
                
            $sql_parent = "CREATE TABLE IF NOT EXISTS " . $db_prefix . "ac_notifications (
                                `id` int(11) NOT NULL AUTO_INCREMENT,
                                `subject` text COLLATE utf8mb4_unicode_ci,
                                `body` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
                                `type` text COLLATE utf8mb4_unicode_ci NOT NULL,
                                `is_active` enum('0','1') COLLATE utf8mb4_unicode_ci NOT NULL,
                                `frequency` text NOT NULL,
                                `coupon_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
                                `default_template` enum('0','1') COLLATE utf8mb4_unicode_ci NOT NULL,
                                PRIMARY KEY (`id`)
                                ) $wcap_collate AUTO_INCREMENT=1 ";
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
            $wpdb->query( $sql_parent );
                 
            $sql_meta = "CREATE TABLE IF NOT EXISTS " . $db_prefix . "ac_notifications_meta (
                                `meta_id` int(11) NOT NULL AUTO_INCREMENT,
                                `template_id` int(11) NOT NULL,
                                `meta_key` text COLLATE utf8mb4_unicode_ci NOT NULL,
                                `meta_value` text COLLATE utf8mb4_unicode_ci,
                                PRIMARY KEY(`meta_id`)
                                ) $wcap_collate AUTO_INCREMENT=1";
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
            $wpdb->query( $sql_meta );

            $sql_tinyurls = "CREATE TABLE IF NOT EXISTS " . $db_prefix . "ac_tiny_urls (
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
                
            $check_query = "SELECT COUNT(id) FROM " . $db_prefix . "ac_notifications WHERE `default_template` = '1'";
                 
            $get_count = $wpdb->get_var( $check_query );

            $default_fb_body = array( 
                    '{"header":"We saved your cart","subheader":"Purchase now before they are out of stock","header_image":"' . esc_url( WCAP_PLUGIN_URL ) . '/includes/fb-recovery/assets/css/images/carts_div.png","checkout_text":"Checkout Now!","unsubscribe_text":"Unsubscribe"}',
                    '{"header":"You left some items in your cart","subheader":"We have saved some items in your cart","header_image":"' . esc_url( WCAP_PLUGIN_URL ) . '/includes/fb-recovery/assets/css/images/carts_div.png","checkout_text":"Checkout","unsubscribe_text":"Unsubscribe"}'
                );

            if( isset( $get_count ) && $get_count == 0 ) {

                // add 2 default sms templates and 2 FB templates
                $insert_templates = "INSERT INTO " . $db_prefix . "ac_notifications 
                                (`subject`, `body`, `type`, `is_active`, `frequency`, `coupon_code`, `default_template`) 
                                VALUES
                                ( 
                                    NULL,
                                    'Hey {{user.name}}, I noticed you left some products in your cart at {{shop.link}}. If you have any queries, please get in touch with me on {{phone.number}}. - {{shop.name}}', 
                                    'sms', 
                                    '0', 
                                    '30 minutes',
                                    '', 
                                    '1' 
                                ),
                                ( 
                                    NULL,
                                    'Hey {{user.name}}, we have saved your cart at {{shop.name}}. Complete your purchase using {{checkout.link}} now!', 
                                    'sms', 
                                    '0', 
                                    '1 days', 
                                    '',
                                    '1' 
                                ),
                                ( 
                                    'Hey there, We noticed that you left some great products in your cart at " . get_bloginfo( 'name' ) . ". Do not worry we saved them for you:',
                                    '" . $default_fb_body[0] . "', 
                                    'fb', 
                                    '0', 
                                    '30 minutes', 
                                    '',
                                    '1' 
                                ),
                                ( 
                                    'Hey there, There are some great products in your cart you left behind at " . get_bloginfo( 'name' ) . ". Here is a list of items you left behind:',
                                    '" . $default_fb_body[1] . "', 
                                    'fb', 
                                    '0', 
                                    '6 hours', 
                                    '',
                                    '1' 
                                )";
                $wpdb->query( $insert_templates );
            } else if( isset( $get_count ) && $get_count == 2 ) {

                /** @since 7.10.0 - Addded default FB templates **/
                // add 2 default FB templates
                $insert_fb = "INSERT INTO " . $db_prefix . "ac_notifications 
                                (`subject`, `body`, `type`, `is_active`, `frequency`, `coupon_code`, `default_template`) 
                                VALUES
                                ( 
                                    'Hey there, We noticed that you left some great products in your cart at " . get_bloginfo( 'name' ) . ". Do not worry we saved them for you:',
                                    '" . $default_fb_body[0] . "', 
                                    'fb', 
                                    '0', 
                                    '30 minutes', 
                                    '',
                                    '1' 
                                ),
                                ( 
                                    'Hey there, There are some great products in your cart you left behind at " . get_bloginfo( 'name' ) . ". Here is a list of items you left behind:',
                                    '" . $default_fb_body[1] . "', 
                                    'fb', 
                                    '0', 
                                    '6 hours', 
                                    '',
                                    '1' 
                                )";
                $wpdb->query( $insert_fb );
            }
                
                /** @since 7.10.0 - Added a new colum in Tiny URls **/
            if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `". $db_prefix."ac_tiny_urls` LIKE 'notification_data';" ) ) {
                $wpdb->query( "ALTER TABLE ".$db_prefix."ac_tiny_urls ADD `notification_data` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL;" );
            }

            // check the auto increment of the tiny urls table and update if needed
            $get_auto = $wpdb->get_results( "SHOW TABLE STATUS FROM `" . DB_NAME . "` WHERE `name` LIKE '" . $db_prefix . "ac_tiny_urls'" );
            if( isset( $get_auto[0]->Auto_increment ) && $get_auto[0]->Auto_increment < 1000000 ) {
                $wpdb->query( "ALTER TABLE  ". $db_prefix . "ac_tiny_urls AUTO_INCREMENT = 1000000" );
            }

            // if the tables are not indexed, add them
            $get_index_cart = "SHOW INDEX FROM " . $db_prefix . "ac_abandoned_cart_history WHERE Key_name = 'id'";
            $check_index_cart = $wpdb->get_results( $get_index_cart );
            if( is_array( $check_index_cart ) && count( $check_index_cart ) == 0 ) {
                $add_index_cart = "CREATE UNIQUE INDEX id ON " . $db_prefix . "ac_abandoned_cart_history (id)";
                $wpdb->query( $add_index_cart );
            }

            $get_index_guest = "SHOW INDEX FROM " . $db_prefix . "ac_guest_abandoned_cart_history WHERE Key_name = 'id'";
            $check_index_guest = $wpdb->get_results( $get_index_guest );
            if( is_array( $check_index_guest ) && count( $check_index_guest ) == 0 ) {
                $add_index_guest = "CREATE UNIQUE INDEX id on " . $db_prefix . "ac_guest_abandoned_cart_history (id)";
                $wpdb->query( $add_index_guest );
            }

            $get_index_sent = "SHOW INDEX FROM " . $db_prefix . "ac_sent_history WHERE Key_name = 'order_id'";
            $check_index_sent = $wpdb->get_results( $get_index_sent );
            if( is_array( $check_index_sent ) && count( $check_index_sent ) == 0 ) {
                $add_index_sent = "CREATE INDEX order_id on " . $db_prefix . "ac_sent_history (abandoned_order_id)";
                $wpdb->query( $add_index_sent );
            }
                
            if( $blog_id === 0 ) {
                $cleanup_data               = get_option( 'ac_pro_user_cleanup', '' );
                $move_templates_data        = get_option( 'wcap_move_templates_data' );
                $wcap_get_admin_option      = get_option( 'ac_email_admin_on_abandoned' );
                $update_sent_history        = get_option( 'wcap_cleanup_sent_history' );
                $update_coupon_code_meta    = get_option( 'wcap_update_coupon_code_meta' );
                
                if ( !get_option( 'ac_cart_abandoned_time_guest' ) ) {
                    $cart_abandoned_time = get_option( 'ac_cart_abandoned_time' );
                    update_option( 'ac_cart_abandoned_time_guest', $cart_abandoned_time );
                }

                $wcap_check_option_available        = "SELECT `option_name` FROM {$db_prefix}options WHERE `option_name` LIKE 'wcap_use_auto_cron'";
                $result_wcap_check_option_available = $wpdb->get_results ($wcap_check_option_available);

                if ( count( $result_wcap_check_option_available ) == 0  ) {
                    $wcap_auto_cron = 'on';
                    update_option( 'wcap_use_auto_cron', $wcap_auto_cron );
                }

                if ( !get_option( 'wcap_cron_time_duration' ) ) {
                    $wcap_cron_duration_time_minutes = 15;
                    update_option( 'wcap_cron_time_duration', $wcap_cron_duration_time_minutes );
                }

                if ( !get_option( 'wcap_new_default_templates' ) ) {
                    $default_template = new Wcap_Default_Settings();
                    $default_template->wcap_create_default_email_templates( $db_prefix, $blog_id );
                }

            } else {
                $cleanup_data               = get_blog_option( $blog_id, 'ac_pro_user_cleanup' );
                $move_templates_data        = get_blog_option( $blog_id, 'wcap_move_templates_data' );
                $wcap_get_admin_option      = get_blog_option( $blog_id, 'ac_email_admin_on_abandoned' );
                $update_sent_history        = get_blog_option( $blog_id, 'wcap_cleanup_sent_history' );
                $update_coupon_code_meta    = get_blog_option( $blog_id, 'wcap_update_coupon_code_meta' );

                if ( !get_blog_option( $blog_id, 'ac_cart_abandoned_time_guest' ) ) {
                    $cart_abandoned_time = get_blog_option( $blog_id, 'ac_cart_abandoned_time' );
                    update_blog_option( $blog_id, 'ac_cart_abandoned_time_guest', $cart_abandoned_time );
                }

                $wcap_check_option_available        = "SELECT `option_name` FROM {$db_prefix}options WHERE `option_name` LIKE 'wcap_use_auto_cron'";
                $result_wcap_check_option_available = $wpdb->get_results ($wcap_check_option_available);

                if ( count( $result_wcap_check_option_available ) == 0  ) {
                    $wcap_auto_cron = 'on';
                    update_blog_option( $blog_id, 'wcap_use_auto_cron', $wcap_auto_cron );
                }

                if ( !get_blog_option( $blog_id, 'wcap_cron_time_duration' ) ) {
                    $wcap_cron_duration_time_minutes = 15;
                    update_blog_option( $blog_id, 'wcap_cron_time_duration', $wcap_cron_duration_time_minutes );
                }

                if ( !get_blog_option( $blog_id, 'wcap_new_default_templates' ) ) {
                    $default_template = new Wcap_Default_Settings();
                    $default_template->wcap_create_default_email_templates( $db_prefix, $blog_id );
                }
            }
            
            if ( 'yes' !== $cleanup_data ) {
                $query_cleanup = "UPDATE `" . $db_prefix . "ac_guest_abandoned_cart_history` SET 
                        billing_first_name = IF (billing_first_name LIKE '%<%', '', billing_first_name),
                        billing_last_name = IF (billing_last_name LIKE '%<%', '', billing_last_name),
                        billing_company_name = IF (billing_company_name LIKE '%<%', '', billing_company_name),
                        billing_address_1 = IF (billing_address_1 LIKE '%<%', '', billing_address_1),
                        billing_address_2 = IF (billing_address_2 LIKE '%<%', '', billing_address_2),
                        billing_city = IF (billing_city LIKE '%<%', '', billing_city),
                        billing_country = IF (billing_country LIKE '%<%', '', billing_country),
                        billing_zipcode = IF (billing_zipcode LIKE '%<%', '', billing_zipcode),
                        email_id = IF (email_id LIKE '%<%', '', email_id),
                        phone = IF (phone LIKE '%<%', '', phone),
                        ship_to_billing = IF (ship_to_billing LIKE '%<%', '', ship_to_billing),
                        order_notes = IF (order_notes LIKE '%<%', '', order_notes),
                        shipping_first_name = IF (shipping_first_name LIKE '%<%', '', shipping_first_name),
                        shipping_last_name = IF (shipping_last_name LIKE '%<%', '', shipping_last_name),
                        shipping_company_name = IF (shipping_company_name LIKE '%<%', '', shipping_company_name),
                        shipping_address_1 = IF (shipping_address_1 LIKE '%<%', '', shipping_address_1),
                        shipping_address_2 = IF (shipping_address_2 LIKE '%<%', '', shipping_address_2),
                        shipping_city = IF (shipping_city LIKE '%<%', '', shipping_city),
                        shipping_county = IF (shipping_county LIKE '%<%', '', shipping_county)";

                $wpdb->query( $query_cleanup );

                $email = 'woouser401a@mailinator.com';
                $exists = email_exists( $email );
                if ( $exists ) {
                    wp_delete_user( esc_html( $exists ) );
                }

                if( $blog_id === 0 ) {
                    update_option( 'ac_pro_user_cleanup', 'yes' );
                } else {
                    update_blog_option( $blog_id, 'ac_pro_user_cleanup', 'yes' );
                }
            }

            // @since 7.14.0
            if ( $wpdb->get_var( "SHOW TABLES LIKE '". $db_prefix ."ac_email_templates';" ) ) {
                if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `". $db_prefix."ac_email_templates` LIKE 'cart_rules';" ) && $wpdb->get_var( "SHOW COLUMNS FROM `" . $db_prefix ."ac_email_templates` LIKE 'wc_template_filter';" ) ) {
                    $wpdb->query( "ALTER TABLE ". $db_prefix."ac_email_templates ADD `cart_rules` varchar(50) COLLATE   utf8_unicode_ci NOT NULL AFTER `wc_template_filter`;" );
                }
            
                if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `". $db_prefix."ac_email_templates` LIKE 'product_ids';" ) && $wpdb->get_var( "SHOW COLUMNS FROM `" . $db_prefix ."ac_email_templates` LIKE 'cart_rules';" ) ) {
                $wpdb->query( "ALTER TABLE ".$db_prefix."ac_email_templates ADD `product_ids` varchar(100) COLLATE     utf8_unicode_ci NOT NULL AFTER `cart_rules`;" );
                }
            }


            // @since 8.15.0
            if ( $wpdb->get_var( "SHOW TABLES LIKE '". $db_prefix ."ac_email_templates';" ) ) {
                if ( $wpdb->get_var( "SHOW TABLES LIKE '". $db_prefix ."ac_email_templates';" ) ) {
                    $check_subject_query = "SHOW COLUMNS FROM " . $db_prefix . "ac_email_templates LIKE 'subject'";
                    $results = $wpdb->get_results( $check_subject_query );
                }
                
                if ( isset( $results, $results[0]->Type ) && ( $results[0]->Type != "varchar(255)" ) ) {
                    $alter_table_query = "ALTER TABLE " . $db_prefix . "ac_email_templates CHANGE `subject` `subject` VARCHAR(255) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL";
                    $wpdb->query( $alter_table_query );
                }
            }

            // Move all the email template data that is saved in post meta table to the email templates table.
            if ( $wpdb->get_var( "SHOW TABLES LIKE '". $db_prefix ."ac_email_templates';" ) ) { 
                if( 'yes' != $move_templates_data && $wpdb->get_var( "SHOW COLUMNS FROM `". $db_prefix ."ac_email_templates` LIKE 'product_ids'" ) ) {
                    $columns_added = false; 
                    if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `". $db_prefix ."ac_email_templates` LIKE 'send_emails_to'" ) ) {
                        $wpdb->query( "ALTER TABLE " . $db_prefix . "ac_email_templates ADD `send_emails_to` VARCHAR(300) NOT NULL AFTER `product_ids`" );
                        $columns_added = true;
                    }

                    if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `".$db_prefix."ac_email_templates` LIKE 'activated_time'" ) ) {
                        $wpdb->query( "ALTER TABLE " . $db_prefix . "ac_email_templates ADD `activated_time` int(11) NOT NULL AFTER `send_emails_to`" );
                        $columns_added = true;
                    }

                    //
                    $email_templates = $wpdb->get_col( "SELECT id FROM " . $db_prefix . "ac_email_templates" );
                    if( is_array( $email_templates ) && $columns_added ) {
                        
                        foreach( $email_templates as $id ) {
                            $activated_time = get_post_meta( $id, 'wcap_template_time', true );
                            $email_action = get_post_meta( $id, 'wcap_email_action', true );
                            $other_action = get_post_meta( $id, 'wcap_other_emails', true );

                            $send_emails_data = json_encode( array(  'action' => $email_action,
                                                        'others' => trim( implode( ',', explode( PHP_EOL, $other_action ) ) ) ) );
                            $update_array = [  'activated_time' => $activated_time,
                                            'send_emails_to' => $send_emails_data ];

                            $wpdb->update( $db_prefix . "ac_email_templates", $update_array, array( 'id' => $id ) );

                        }

                        // delete all the data from post meta
                        $wpdb->query( "DELETE FROM `" . $db_prefix . "postmeta` WHERE meta_key = 'wcap_template_time'" );
                        $wpdb->query( "DELETE FROM `" . $db_prefix . "postmeta` WHERE meta_key = 'wcap_email_action'" );
                        $wpdb->query( "DELETE FROM `" . $db_prefix . "postmeta` WHERE meta_key = 'wcap_other_emails'" ); 
        
                    }

                    if( $blog_id === 0 ) {
                        update_option( 'wcap_move_templates_data', 'yes' );
                    } else {
                        update_blog_option( $blog_id, 'wcap_move_templates_data', 'yes' );
                    }
                }
            }
            
            // since 8.4
            if ( '' == $update_sent_history ) {
                $last_365 = date( 'Y-m-d H:i:s', strtotime( '-365 days', current_time( 'timestamp' ) ) );
                $wpdb->query( "UPDATE " . WCAP_EMAIL_SENT_HISTORY_TABLE . " as sent LEFT JOIN " . WCAP_EMAIL_CLICKED_TABLE . " as link ON sent.id = link.email_sent_id SET sent.recovered_order = '0' where link.id IS NULL AND sent.recovered_order = '1' AND sent.sent_time > '$last_365'" );

                if ( 0 === $blog_id ) {
                    update_option( 'wcap_cleanup_sent_history', 'yes' );
                } else {
                    update_blog_option( $blog_id, 'wcap_cleanup_sent_history', 'yes' );
                }

            }

            /* Adding coupon meta to coupons which are generated by this plugin */
            if ( 'yes' !== $update_coupon_code_meta ) {

                $coupons = $wpdb->get_col( $wpdb->prepare( 'SELECT id from `' . $db_prefix . 'posts` WHERE post_type = %s AND post_status = %s AND post_content = %s', 'shop_coupon', 'publish', "This coupon provides 5% discount on cart price." ) );
                
		    foreach ( $coupons as $key => $coupon_id ) {
                    update_post_meta( $coupon_id, 'wcap_created_by', 'wcap' );
                }

                if ( $blog_id === 0 ) {
                    update_option( 'wcap_update_coupon_code_meta', 'yes' );
                    update_option( 'ac_cart_abandoned_after_x_days_order_placed', 7 );
                } else {
                    update_blog_option( $blog_id, 'wcap_update_coupon_code_meta', 'yes' );
                    update_blog_option( $blog_id, 'ac_cart_abandoned_after_x_days_order_placed', 7 );
                }
            }
            
            // @since 8.7.0 - Add Checkout Links - Currently used in Webhooks. These links can be used directly to recover the cart.
            if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `".$db_prefix."ac_abandoned_cart_history` LIKE 'checkout_link'" ) ) {
                $wpdb->query( "ALTER TABLE " . $db_prefix . "ac_abandoned_cart_history ADD `checkout_link` varchar(200) NOT NULL AFTER `wcap_trash`" );
            }

			// @since - Add individual use column in email templates.
            if ( $wpdb->get_var( "SHOW TABLES LIKE '". $db_prefix ."ac_email_templates';" ) ) {
    			if ( ! $wpdb->get_var( "SHOW COLUMNS FROM " . $db_prefix . "ac_email_templates LIKE 'individual_use'" ) ) { // phpcs:ignore
    				$wpdb->query( "ALTER TABLE `" . $db_prefix . "ac_email_templates` ADD `individual_use` ENUM('0','1') NOT NULL AFTER `discount_expiry`;" ); // phpcs:ignore
    				// loop through all the records in the table and set the individual use value to 1.
    				$get_ids = $wpdb->get_col( "SELECT id FROM `" . $db_prefix . "ac_email_templates` WHERE generate_unique_coupon_code = '1'" ); // phpcs:ignore

    				if ( is_array( $get_ids ) && count( $get_ids ) > 0 ) {
    					foreach ( $get_ids as $template_id ) {
    						$wpdb->update(
    							$db_prefix . 'ac_email_templates',
    							array( 'individual_use' => '1' ),
    							array( 'id' => $template_id )
    						);
    					}
    				}
    			}
            }

            // 8.10.0 - ATC Rules and Statistics.
            $atc_rules_table = $db_prefix . 'ac_atc_rules';
            $create_atc_table = "CREATE TABLE IF NOT EXISTS $atc_rules_table (
                `id` int(11) NOT NULL auto_increment,
                `name` varchar(100) collate utf8_unicode_ci NOT NULL,
                `match_rules` VARCHAR(50) COLLATE utf8_unicode_ci DEFAULT 'all' NOT NULL,
                `rules` VARCHAR(500) COLLATE utf8_unicode_ci NOT NULL,
                `is_active` enum('0','1') COLLATE utf8_unicode_ci NOT NULL,
                `frontend_settings` varchar(1000) COLLATE utf8_unicode_ci NOT NULL,
                `coupon_settings` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
                 PRIMARY KEY  (`id`),
                 INDEX id (id)
                ) $wcap_collate AUTO_INCREMENT=1";
            $wpdb->query( $create_atc_table );

            $atc_stats_table = $db_prefix . 'ac_statistics';
            $create_stats_table = "CREATE TABLE IF NOT EXISTS $atc_stats_table (
                `id` int(11) NOT NULL auto_increment,
                `template_id` int(11) collate utf8_unicode_ci NOT NULL,
                `template_type` VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL,
                `event` enum('0','1','2','3','4') COLLATE utf8_unicode_ci NOT NULL,
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
				$frontend_settings = json_encode(
						array(
						'wcap_heading_section_text_email' => get_option( 'wcap_heading_section_text_email' ),
						'wcap_text_section_text'          => get_option( 'wcap_text_section_text' ),
						'wcap_email_placeholder_section_input_text' => get_option( 'wcap_email_placeholder_section_input_text' ),
						'wcap_button_section_input_text'  => get_option( 'wcap_button_section_input_text' ),
						'wcap_atc_mandatory_email' => get_option( 'wcap_atc_mandatory_email' ),
						'wcap_popup_heading_color_picker' => get_option( 'wcap_popup_heading_color_picker' ),
						'wcap_popup_text_color_picker' => get_option( 'wcap_popup_text_color_picker' ),
						'wcap_button_color_picker' => get_option( 'wcap_button_color_picker' ),
						'wcap_button_text_color_picker' => get_option( 'wcap_button_text_color_picker' ),
						'wcap_non_mandatory_text' => get_option( 'wcap_non_mandatory_text' )
					)
				);

				// Coupon Settings. 
				$coupon_settings = json_encode(
						array(
						'wcap_atc_auto_apply_coupon_enabled' => get_option( 'wcap_atc_auto_apply_coupon_enabled' ),
						'wcap_atc_coupon_type' => get_option( 'wcap_atc_coupon_type' ),
						'wcap_atc_popup_coupon' => get_option( 'wcap_atc_popup_coupon' ),
						'wcap_atc_discount_type' => get_option( 'wcap_atc_discount_type' ),
						'wcap_atc_discount_amount' => get_option( 'wcap_atc_discount_amount' ),
						'wcap_atc_coupon_free_shipping' => get_option( 'wcap_atc_coupon_free_shipping' ),
						'wcap_atc_popup_coupon_validity' => get_option( 'wcap_atc_popup_coupon_validity' ),
						'wcap_countdown_timer_msg' => get_option( 'wcap_countdown_timer_msg' ),
						'wcap_countdown_msg_expired' => get_option( 'wcap_countdown_msg_expired' ),
						'wcap_countdown_cart' => get_option( 'wcap_countdown_cart' )
					)
				);

				$rules = json_encode( array() );
				if ( count( get_option( 'wcap_custom_pages_list', array() ) ) > 0 ) {
					$rules = json_encode(
                        array(
							array(
                                'rule_type' => 'custom_pages',
                                'rule_condition' => 'includes',
                                'rule_value' => get_option( 'wcap_custom_pages_list' )
                            )
						)
					);
				}
				$is_active = 'on' === get_option( 'wcap_atc_enable_modal', '' ) ? '1' : '0';
                // Add default template.
                $wpdb->insert(
                    $atc_rules_table,
                    array(
                        'name' => __( 'Show on standard WooCommerce pages', 'woocommerce-ac' ),
                        'match_rules' => 'all',
                        'rules' => $rules,
                        'is_active' => $is_active,
                        'frontend_settings' => $frontend_settings,
                        'coupon_settings' => $coupon_settings
                    )
                );
            }
            // Since 8.18.0
            if ( '' === get_option( 'wcap_gdpr_consent_migrated', '' ) ) {
                update_option( 'wcap_gdpr_consent_migrated', 'yes' );
                if ( '' !== get_option( 'wcap_guest_cart_capture_msg', '' ) || '' !== get_option( 'wcap_logged_cart_capture_msg', '' ) ) {
                    update_option( 'wcap_enable_gdpr_consent', 'on' );
                }
            }
            /**
             * Please change in this function if it requires any updation in tables or in options
             */
            Wcap_Update_Check::wcap_alter_tables_if_required( $db_prefix, $blog_id );
            Wcap_Update_Check::wcap_update_options_if_required( $blog_id );

            if ( ( $wcap_get_admin_option == 'on' || '' == $wcap_get_admin_option ) && false !== $wcap_get_admin_option ) {
                Wcap_Update_Check::wcap_add_customer_for_template( $db_prefix, $blog_id );
            }
            // Change rules column to varchar(1500) in notification templates table if it is varchar(500).
            $table_name  = $db_prefix . 'ac_notification_templates';
            $column_info = $wpdb->get_row(
                $wpdb->prepare(
                    "SHOW COLUMNS FROM $table_name LIKE %s", 'rules'
				)
			);
            if ( $column_info && strpos( $column_info->Type, 'varchar(500)' ) !== false ) {
                $wpdb->query(
                    "ALTER TABLE $table_name MODIFY rules VARCHAR(1500)"
				);
			}
        }

        /**
         * It will alter the tables if required.
         * @globals mixed $wpdb
         * @since 5.0
         */
        public static function wcap_alter_tables_if_required( $db_prefix = '', $blog_id = 0 ) {

            if( $db_prefix === '' ) {
                return;
            }

            if( $blog_id === 0 ) {
                $guest_user_id_altered = get_option ('wcap_guest_user_id_altered');
                $wcap_manual_reset_needed   = get_option( 'wcap_guest_users_manual_reset_needed' );
                $alter_tables_ran = get_option( 'wcap_alter_tables_ran' );
                $alter_guest_columns = get_option( 'wcap_alter_guest_columns' );
            } else {
                $guest_user_id_altered = get_blog_option( $blog_id, 'wcap_guest_user_id_altered');
                $wcap_manual_reset_needed   = get_blog_option( $blog_id, 'wcap_guest_users_manual_reset_needed' );
                $alter_tables_ran = get_blog_option( $blog_id, 'wcap_alter_tables_ran' );
                $alter_guest_columns = get_blog_option( $blog_id, 'wcap_alter_guest_columns' );
            }

            global $wpdb;
            if ( $wpdb->get_var( "SHOW TABLES LIKE '".$db_prefix."ac_guest_abandoned_cart_history' " )  && 'yes' != $guest_user_id_altered ) {
                $last_id = $wpdb->get_var( "SELECT max(id) FROM `".$db_prefix."ac_guest_abandoned_cart_history`;" );
                if ( NULL != $last_id && $last_id <= 63000000 ) {
                    $wpdb->query( "ALTER TABLE ".$db_prefix."ac_guest_abandoned_cart_history AUTO_INCREMENT = 63000000;" );
                    update_option ( 'wcap_guest_user_id_altered' , 'yes' );
                }
            }
            if ( '1' != $alter_tables_ran ) {

                if ( $wpdb->get_var( "SHOW TABLES LIKE '".$db_prefix."ac_email_templates' " ) ) {
                    
                    if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `".$db_prefix."ac_email_templates` LIKE 'wc_template_filter';" ) ) {
                        $wpdb->query( "ALTER TABLE ".$db_prefix."ac_email_templates ADD `wc_template_filter` varchar(500) COLLATE utf8_unicode_ci NOT NULL AFTER `wc_email_header`;" );
                    }
                }
                
                if ( $wpdb->get_var( "SHOW TABLES LIKE '".$db_prefix."ac_abandoned_cart_history' " ) ) {
                    if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `".$db_prefix."ac_abandoned_cart_history` LIKE 'language';" ) ) {
                        $wpdb->query( "ALTER TABLE ".$db_prefix."ac_abandoned_cart_history ADD `language` varchar(50) COLLATE utf8_unicode_ci NOT NULL AFTER `user_type`;" );
                    }
                    
                    if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `".$db_prefix."ac_abandoned_cart_history` LIKE 'session_id';" ) ) {
                        $wpdb->query( "ALTER TABLE ".$db_prefix."ac_abandoned_cart_history ADD `session_id` varchar(50) COLLATE utf8_unicode_ci NOT NULL AFTER `language`;" );
                    }
                    
                    if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `".$db_prefix."ac_abandoned_cart_history` LIKE 'ip_address';" ) ) {
                        $wpdb->query( "ALTER TABLE ".$db_prefix."ac_abandoned_cart_history ADD `ip_address` longtext COLLATE utf8_unicode_ci NOT NULL AFTER  `session_id`;" );
                    }
                    
                    if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `".$db_prefix."ac_abandoned_cart_history` LIKE 'manual_email';" ) ) {
                        $wpdb->query( "ALTER TABLE ".$db_prefix."ac_abandoned_cart_history ADD `manual_email` varchar(50) COLLATE utf8_unicode_ci NOT NULL AFTER `ip_address`;" );
                    }
                    
                    if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `".$db_prefix."ac_abandoned_cart_history` LIKE 'wcap_trash';" ) ) {
                        $wpdb->query( "ALTER TABLE ".$db_prefix."ac_abandoned_cart_history ADD `wcap_trash` varchar(1) COLLATE utf8_unicode_ci NOT NULL AFTER `manual_email`;" );
                    }
                }

                /**
                 * Since 4.3
                 * We have added Trash feature in the sent emails tab. It will add new coulmn in the sent history table
                 */
                if ( $wpdb->get_var( "SHOW TABLES LIKE '". $db_prefix ."ac_sent_history'" ) ) {
                    if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `".$db_prefix."ac_sent_history` LIKE 'wcap_sent_trash';" ) ) {
                        $wpdb->query( "ALTER TABLE ".$db_prefix."ac_sent_history ADD `wcap_sent_trash` varchar(1) COLLATE utf8_unicode_ci NOT NULL AFTER `recovered_order`;" );
                    }
                }
                /**
                 * This is used to prevent guest users wrong Id. If guest users id is less then 63000000 then this code will ensure that we will change the id of guest tables so it wont affect on the next guest users.
                 */
        /**        if ( $wpdb->get_var( "SHOW TABLES LIKE '".$db_prefix."ac_guest_abandoned_cart_history' " )  && 'yes' != get_option ('wcap_guest_user_id_altered') ) {
                    $last_id = $wpdb->get_var( "SELECT max(id) FROM `".$db_prefix."ac_guest_abandoned_cart_history`;" );
                    if ( NULL != $last_id && $last_id <= 63000000 ) {
                        $wpdb->query( "ALTER TABLE ".$db_prefix."ac_guest_abandoned_cart_history AUTO_INCREMENT = 63000000;" );
                        update_option ( 'wcap_guest_user_id_altered' , 'yes' );
                    }
                } **/

                /*
                 * Since 4.7
                 * We have moved email templates fields in the setings section. SO to remove that fields column fro the db we need it.
                 * For existing user we need to fill this setting with the first template.
                 */
                if ( $wpdb->get_var( "SHOW TABLES LIKE '".$db_prefix."ac_email_templates' " ) ) {

                    if ( $wpdb->get_var( "SHOW COLUMNS FROM `".$db_prefix."ac_email_templates` LIKE 'from_email';" ) ) {
                        $get_email_template_query  = "SELECT `from_email` FROM ".$db_prefix."ac_email_templates WHERE `is_active` = '1' ORDER BY `id` ASC LIMIT 1";
                        $get_email_template_result = $wpdb->get_results ($get_email_template_query);

                        $wcap_from_email = '';
                        if ( isset( $get_email_template_result ) && count ( $get_email_template_result ) > 0 ) {
                            $wcap_from_email =  $get_email_template_result[0]->from_email;

                            /* Store data in setings api*/
                            if( $blog_id === 0 ) {
                                update_option ( 'wcap_from_email', $wcap_from_email );
                            } else {
                                update_blog_option( $blog_id, 'wcap_from_email', $wcap_from_email );
                            }   

                            /* Delete table from the Db*/
                            $wpdb->query( "ALTER TABLE ".$db_prefix."ac_email_templates DROP COLUMN `from_email`;" );
                        }
                    }

                    if ( $wpdb->get_var( "SHOW COLUMNS FROM `".$db_prefix."ac_email_templates` LIKE 'from_name';" ) ) {
                        $get_email_template_from_name_query  = "SELECT `from_name` FROM ".$db_prefix."ac_email_templates WHERE `is_active` = '1' ORDER BY `id` ASC LIMIT 1";
                        $get_email_template_from_name_result = $wpdb->get_results ($get_email_template_from_name_query);

                        $wcap_from_name = '';
                        if ( isset( $get_email_template_from_name_result ) && count ( $get_email_template_from_name_result ) > 0 ){
                            $wcap_from_name =  $get_email_template_from_name_result[0]->from_name;

                            /* Store data in setings api*/
                            if( $blog_id === 0 ) {
                                update_option( 'wcap_from_name', $wcap_from_name );
                            } else {
                                update_blog_option( $blog_id, 'wcap_from_name', $wcap_from_name );
                            }

                            /* Delete table from the Db*/
                            $wpdb->query( "ALTER TABLE ".$db_prefix."ac_email_templates DROP COLUMN `from_name`;" );
                        }
                    }

                    if ( $wpdb->get_var( "SHOW COLUMNS FROM `".$db_prefix."ac_email_templates` LIKE 'reply_email';" ) ) {
                        $get_email_template_reply_email_query  = "SELECT `reply_email` FROM ".$db_prefix."ac_email_templates WHERE `is_active` = '1' ORDER BY `id` ASC LIMIT 1";
                        $get_email_template_reply_email_result = $wpdb->get_results ($get_email_template_reply_email_query);

                        $wcap_reply_email = '';
                        if ( isset( $get_email_template_reply_email_result ) && count ( $get_email_template_reply_email_result ) > 0 ){
                            $wcap_reply_email =  $get_email_template_reply_email_result[0]->reply_email;

                            /* Store data in setings api*/
                            if( $blog_id === 0 ) {
                                update_option( 'wcap_reply_email', $wcap_reply_email );
                            } else {
                                update_option( $blog_id, 'wcap_reply_email', $wcap_reply_email );
                            }

                            /* Delete table from the Db*/
                            $wpdb->query( "ALTER TABLE ".$db_prefix."ac_email_templates DROP COLUMN `reply_email`;" );
                        }
                    }
                }

                if( $blog_id === 0 ) {
                    update_option( 'wcap_alter_tables_ran', '1', 'no' );
                } else {
                    update_blog_option( $blog_id, 'wcap_alter_tables_ran', '1' );
                }
            }

            if ( '1' != $alter_guest_columns ) {
                $wpdb->query( "
                    ALTER TABLE " . $db_prefix . "ac_guest_abandoned_cart_history 
                    ADD billing_country TEXT AFTER billing_last_name" );
                if( $blog_id === 0 ) {
                    update_option( 'wcap_alter_guest_columns', '1', 'no' );
                } else {
                    update_blog_option( $blog_id, 'wcap_alter_guest_columns', '1' );
                }
            }

            /* Creating new columns for coupon code option available in the email template */
            if ( $wpdb->get_var( "SHOW TABLES LIKE '". $db_prefix ."ac_email_templates';" ) ) {
                if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `". $db_prefix."ac_email_templates` LIKE 'discount_type';" ) ) {                
                    $wpdb->query( "ALTER TABLE ".$db_prefix."ac_email_templates ADD `discount_type` VARCHAR(50) DEFAULT 'percent' AFTER `discount`, ADD `discount_shipping` VARCHAR(50) DEFAULT 'no' AFTER `discount_type`, ADD `discount_expiry` VARCHAR(50) DEFAULT '7-days' AFTER `discount_shipping`;" );
                }
            }

			// Update 8.9.0 - Email Templates Rules Engine.
			if ( 'yes' !== get_option( 'wcap_rules_engine_edit_email_templates_table', '' ) && ! $wpdb->get_var( "SHOW COLUMNS FROM `". $db_prefix."ac_email_templates` LIKE 'match_rules';" ) ) {

				// Add the new columns.
				if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `". $db_prefix."ac_email_templates` LIKE 'match_rules';" ) ) {                
					$wpdb->query( "ALTER TABLE ".$db_prefix."ac_email_templates ADD `match_rules` VARCHAR(50) DEFAULT 'all' AFTER `send_emails_to`, ADD `rules` VARCHAR(500) AFTER `match_rules`;" );
				}
				// Move all the data from the old columns to the new columns.
				$emails = $wpdb->get_results(
					'SELECT id, wc_template_filter, cart_rules, product_ids, send_emails_to FROM ' . $db_prefix . 'ac_email_templates'
				);

				$match_rules = 'all';
				// Loop through all the email templates.
				foreach ( $emails as $data ) {
                    $send_to = false;
					$rules = array();
					if ( '' !== $data->wc_template_filter ) {
						switch( $data->wc_template_filter ) {
							case 'all':
							default:
								$rule_type = 'send_to';
								$rule_condition = 'includes';
								$rule_value = array( 'all' );
                                $send_to = true;
							break;
							case 'Carts abandoned with one product':
								$rule_type = 'cart_items_count';
								$rule_condition = 'equal_to';
								$rule_value = 1;
							break;
							case 'Carts abandoned with more than one product':
								$rule_type = 'cart_items_count';
								$rule_condition = 'greater_than_equal_to';
								$rule_value = 1;
							break;
							case 'Registered Users':
								$rule_type = 'send_to';
								$rule_condition = 'includes';
								$rule_value = array( 'registered_users' );
                                $send_to = true;
							break;
							case 'Guest Users':
								$rule_type = 'send_to';
								$rule_condition = 'includes';
                                $rule_value = array( 'guest_users' );
                                $send_to = true;
							break;
						}
						$rules[] = array(
							'rule_type' => $rule_type,
							'rule_condition' => $rule_condition,
							'rule_value' => $rule_value,
						);
					}
					
					if ( '' !== $data->cart_rules && '' !== $data->product_ids ) {
						$product_list = strpos( $data->product_ids, ',' ) !== false ? explode( ',', $data->product_ids ) : array( $data->product_ids );
						$rules[] = array(
							'rule_type' => 'cart_items',
							'rule_condition' => $data->cart_rules,
							'rule_value' => $product_list,
						);
					}

					if ( '' !== $data->send_emails_to ) {
						$send_emails_to = json_decode( $data->send_emails_to );
						$action = $send_emails_to->action;
						$others = $send_emails_to->others;

                        if ( $send_to ) {
                            foreach ( $rules as $r_key => $rule_data ) {
                                if ( 'send_to' === $rule_data['rule_type'] ) {
                                    $existing_rule = $rule_data['rule_value'];
                                    if ( '' !== $others ) {  
                                        $action = 'email_addresses';
                                        $rule_data['emails'] = $others;
                                    }
                                    array_push( $existing_rule, $action );
                                    $rule_data['rule_value'] = $existing_rule;
                                    $rules[$r_key] = $rule_data;
                                }
                            }
                        } else {	
							if ( '' !== $others ) {
								$rules[] = array(
									'rule_type' => 'send_to',
									'rule_condition' => 'includes',
									'rule_value' => array( 'email_addresses' ),
									'emails' => $others,
								);
							} else {
								$rules[] = array(
									'rule_type' => 'send_to',
									'rule_condition' => 'includes',
									'rule_value' => array( $action ),
								);
							}
						}
					}

					$wpdb->update(
						$db_prefix . 'ac_email_templates',
						array(
							'match_rules' => $match_rules,
							'rules'       => json_encode( $rules )
						),
						array(
							'id' => $data->id
						)
					);

				}

				// Remove the old columns.
				$wpdb->query( 'ALTER TABLE ' . $db_prefix . 'ac_email_templates DROP wc_template_filter, DROP cart_rules, DROP product_ids, DROP send_emails_to' );
				update_option( 'wcap_rules_engine_edit_email_templates_table', 'yes' );
            }

            $check_table_query = "SHOW COLUMNS FROM " . $db_prefix . "ac_abandoned_cart_history LIKE 'cart_ignored'";
            $results = $wpdb->get_results( $check_table_query );
            if ( isset( $results, $results[0]->Type ) && ( $results[0]->Type == "enum('0','1')" OR $results[0]->Type == "enum('0','1','2')" OR $results[0]->Type == "enum('0','1','2','3')" ) ) {
                $alter_table_query = "ALTER TABLE " . $db_prefix . "ac_abandoned_cart_history MODIFY COLUMN cart_ignored enum('0','1','2','3','4')";
                $wpdb->query( $alter_table_query );
            }

            // 8.9.1 - Rename manual_email to email_reminder_status.
            if ( 'yes' !== get_option( 'wcap_manual_email_col_update', '' ) ) {
                add_option( 'wcap_manual_email_col_update', 'yes' );
                self::wcap_update_manual_emails_column( $db_prefix );
            }

            // 8.11.0 - Alter ATC rules table.
			if ( '' === get_option( 'wcap_atc_rules_table_update', '' ) ) {
				$check_table_query = "SHOW COLUMNS FROM " . $db_prefix . "ac_atc_rules LIKE 'frontend_settings'";
				$results = $wpdb->get_results( $check_table_query );
				if ( isset( $results, $results[0]->Type ) && $results[0]->Type == "varchar(1000)" ) {
					$alter_table_query = "ALTER TABLE `" . $db_prefix . "ac_atc_rules` CHANGE `frontend_settings` `frontend_settings` LONGTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL, CHANGE `coupon_settings` `coupon_settings` LONGTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;";
					$wpdb->query( $alter_table_query );
					update_option( 'wcap_atc_rules_table_update', 'yes' );
				}
			}
            // 8.14.0 - Alter ATC rules table.
            if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `". $db_prefix."ac_atc_rules` LIKE 'popup_type';" ) ) {                
                $alter_table_query = "ALTER TABLE `" . $db_prefix . "ac_atc_rules` ADD `popup_type` VARCHAR(500) COLLATE utf8_unicode_ci DEFAULT 'atc' NOT NULL AFTER `rules`, ADD `quick_checkout_settings` LONGTEXT COLLATE utf8_unicode_ci NOT NULL after `coupon_settings`;";
                $wpdb->query( $alter_table_query );
                // Add a default EI template as well.
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
					$db_prefix . 'ac_atc_rules',
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

            $results = $wpdb->get_results( "SHOW COLUMNS FROM " . $db_prefix . "ac_statistics LIKE 'event'" );
            if ( isset( $results, $results[0]->Type ) && $results[0]->Type == "enum('0','1','2','3','4')" ) {
                $alter_table_query = "ALTER TABLE " . $db_prefix . "ac_statistics MODIFY COLUMN event enum('0','1','2','3','4','5')";
                $wpdb->query( $alter_table_query );
            }

            // 8.15.0 - Connector table.
            $wcap_collate = '';
            if ( $wpdb->has_cap( 'collation' ) ) {
                $wcap_collate = $wpdb->get_charset_collate();
            }
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

            // Since 8.19.0
            $results = $wpdb->get_results( "SHOW COLUMNS FROM " . $db_prefix . "ac_abandoned_cart_history LIKE 'recovered_cart'" );
            if ( isset( $results, $results[0]->Type ) && $results[0]->Type == "int(11)" ) {
                $wpdb->query( 'ALTER TABLE `' . $db_prefix . 'ac_abandoned_cart_history` CHANGE `recovered_cart` `recovered_cart` BIGINT(20) NOT NULL;' ); 
            }

			// Migrate FB & SMS templates.
            if ( 'yes' !== get_option( 'wcap_migrate_notification_templates', '' ) ) {
                update_option( 'wcap_migrate_notification_templates', 'yes' );
                // Change the table names.
                $wpdb->query(
                    'RENAME TABLE `' . $db_prefix . 'ac_email_templates` TO `' . $db_prefix . 'ac_notification_templates`, `' . $db_prefix . 'ac_link_clicked_email` TO `' . $db_prefix . 'ac_link_clicked_notifications`, `' . $db_prefix . 'ac_opened_emails` TO `' . $db_prefix . 'ac_opened_notifications`'
                );
                // Add the new columns.
                $wpdb->query(
                    'ALTER TABLE ' . $db_prefix . 'ac_notification_templates ADD `notification_type` VARCHAR(50) NOT NULL AFTER `id`, ADD `email_type` VARCHAR(50) NOT NULL AFTER `notification_type`;'
                );
                $wpdb->query(
                    'ALTER TABLE ' . $db_prefix . 'ac_sent_history ADD `notification_type` VARCHAR(50) NOT NULL AFTER `id`;'
                );
                // Modify existing column names.
                $wpdb->query(
                    'ALTER TABLE `' . $db_prefix . 'ac_notification_templates` CHANGE `body` `body` mediumtext CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL'
                );
                $wpdb->query(
                    'ALTER TABLE `' . $db_prefix . 'ac_link_clicked_notifications` CHANGE email_sent_id notification_sent_id int(11)'
                );
                $wpdb->query(
                    'ALTER TABLE `' . $db_prefix . 'ac_link_clicked_notifications` CHANGE `link_clicked` `link_clicked` VARCHAR(500);'
                );
                $wpdb->query(
                    'ALTER TABLE `' . $db_prefix . 'ac_opened_notifications` CHANGE email_sent_id notification_sent_id int(11)'
                );
                if ( $wpdb->get_var( "SHOW COLUMNS FROM `". $db_prefix."ac_sent_history` LIKE 'abandoned_order_id';" ) ) {
                    $wpdb->query(
                        'ALTER TABLE `' . $db_prefix . 'ac_sent_history` CHANGE abandoned_order_id cart_id int(11), CHANGE sent_email_id sent_notification_contact text'
                    );
                }
                if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `". $db_prefix."ac_abandoned_cart_history` LIKE 'sms_reminder_status';" ) ) {
                    $wpdb->query(
                        'ALTER TABLE ' . $db_prefix . 'ac_abandoned_cart_history ADD `sms_reminder_status` varchar(50) COLLATE utf8_unicode_ci NOT NULL AFTER `checkout_link`, ADD `fb_reminder_status` varchar(50) COLLATE utf8_unicode_ci NOT NULL AFTER `sms_reminder_status`;'
                    );
                }
                // Add the notification type & email type before migration.
                $wpdb->query( 'UPDATE `' . $db_prefix . 'ac_notification_templates` SET notification_type = "email", email_type = "abandoned_cart_email"' );
                $wpdb->query( 'UPDATE `' . $db_prefix . 'ac_sent_history` SET notification_type = "email"' );
                // Migrate the templates.
                $templates = $wpdb->get_results(
                    'SELECT * FROM `' . $db_prefix . 'ac_notifications`'
                );
                if ( is_array( $templates ) && count( $templates ) > 0 ) {
                    $minute_seconds   = 60;
                    $hour_seconds     = 3600; // 60 * 60
                    $day_seconds      = 86400; // 24 * 60 * 60       
                    $week_in_seconds  = 7 * $day_seconds; // 1 week in seconds.
                    $list_frequencies = array();
					$insert_list = '';
                    foreach ( $templates as $template_data ) {
                        $frequency = explode( ' ', $template_data->frequency );
                        $frequency_numeric = trim( $frequency[0] );
                        $day_or_hour       = trim( $frequency[1] );
                        $activated_time    = current_time( 'timestamp' ) - $week_in_seconds; // activated 1 week ago.
                        $notification_type = $template_data->type;
                        switch ( $day_or_hour ) {
                            case 'minutes':
                                $freq = $frequency_numeric * $minute_seconds;
                                break;
                            case 'hours':
                                $freq = $frequency_numeric * $hour_seconds;
                                break;
                            case 'days':
                                $freq = $frequency_numeric * $day_seconds;
                                break;
                        }
                        if ( '1' == $template_data->is_active && $freq > $week_in_seconds ) {
                            $activated_time = current_time( 'timestamp' ) - $freq - $hour_seconds;
                        }
                        $list_frequencies[ $notification_type ][ $template_data->id ] = (int) $activated_time;
                        $subject = NULL == $template_data->subject ? '' : $template_data->subject;
                        $insert_id = $wpdb->insert(
                            $db_prefix . 'ac_notification_templates',
                            array(
                                'notification_type' => $notification_type,
                                'subject' => $subject,
                                'body' => $template_data->body,
                                'is_active' => $template_data->is_active,
                                'frequency' => $frequency_numeric,
                                'day_or_hour' => $day_or_hour,
                                'coupon_code' => $template_data->coupon_code,
                                'default_template' => $template_data->default_template,
                                'activated_time' => $activated_time
                            )
                        );
						$insert_list .= $insert_id . ' ';
                    }
                }
                // Update the cart completion status.
                foreach ( array( 'sms', 'fb' ) as $t_type ) {
                    $temp_list = $list_frequencies[ $t_type ];
                    arsort( $temp_list, SORT_NUMERIC );
                    reset( $temp_list );
                    $last_template_id = key( $temp_list );
                    $activated_time   = array_shift( $temp_list );

                    $sent_carts_list = wcap_get_notification_meta( $last_template_id, 'to_be_sent_cart_ids' );
                    $sent_carts_str  = '';
                    if ( $sent_carts_list ) {
                        $sent_carts = explode( ',', $sent_carts_list );
                        foreach ( $sent_carts as $cart_id ) {
                            if ( '' !== $sent_carts_str ) {
                                $sent_carts_str .= ( '' !== $cart_id ) ? ",'$cart_id'" : '';
                            } else {
                                $sent_carts_str = ( '' !== $cart_id ) ? "'$cart_id'" : '';
                            }
                        }
                    }

                    $column_name = 'sms' === $t_type ? 'sms_reminder_status' : 'fb_reminder_status';
                    $update_count1 = $wpdb->query( "UPDATE `" . $db_prefix. "ac_abandoned_cart_history` SET $column_name = 'complete' WHERE abandoned_cart_time <= $activated_time" ); // Mark all carts that were abandoned before the activation time as complete.
                    if ( '' !== $sent_carts_str ) {
                        $update_count2 = $wpdb->query( "UPDATE `" . $db_prefix. "ac_abandoned_cart_history` SET $column_name = 'complete' WHERE id NOT IN ( $sent_carts_str )" ); // If it's not in the to be sent list, mark it as complete.
                    } else {
                        $update_count2 = 0;
                    }
					if ( $update_count1 > 0 && $update_count2 > 0 ) {
						$wpdb->query( 'DROP TABLE IF EXISTS ' . $db_prefix . 'ac_notifications_meta' );
					}
                }
                // Drop the notifications & the notifications_meta table.
				if ( is_array( $templates ) && count( $templates ) > 0 && '' !== $insert_list ) {
					$wpdb->query( 'DROP TABLE IF EXISTS ' . $db_prefix . 'ac_notifications' );
				}
            }

            // 8.21.0
            if ( ! $wpdb->get_results( "SHOW COLUMNS FROM " . $db_prefix . "ac_sent_history LIKE 'wc_order_id'" ) ) {
                $wpdb->query( 'ALTER TABLE `' . $db_prefix . 'ac_sent_history` ADD `wc_order_id` BIGINT(20) NOT NULL AFTER `recovered_order`;' ); 
            }
            if ( ! get_option( 'wcap_new_default_followup_templates', false ) ) {
                $wpdb->query( 'UPDATE `' . $db_prefix . 'ac_notification_templates` SET email_type = "abandoned_cart_email" WHERE notification_type = "email" AND email_type="reminder"' );
                $default_template = new Wcap_Default_Settings;
                $default_template->wcap_create_default_email_followup_templates( $db_prefix, $blog_id );
            }
            
            // Delete the old cached record. - Fix #3097.
            if ( 0 === $blog_id ) {
                delete_option( 'wcap_atc_templates' );
                delete_option( 'wcap_ei_templates' );
            } else {
                delete_blog_option( $blog_id, 'wcap_atc_templates' );
                delete_blog_option( $blog_id, 'wcap_ei_templates' );
            }
            // 9.0 - Set the auto login feature to on by default for existing users.
            if ( 0 === $blog_id ) {
                update_option( 'wcap_auto_login_users', 'on' );
            } else {
                update_blog_option( $blog_id, 'wcap_auto_login_users', 'on' );
            }

            // 9.1.0
            if ( ! $wpdb->get_var( 'SHOW COLUMNS FROM ' . WCAP_EMAIL_SENT_HISTORY_TABLE . " LIKE 'encrypt_key';" ) ) { //phpcs:ignore
				$wpdb->query( 'ALTER TABLE ' . WCAP_EMAIL_SENT_HISTORY_TABLE . ' ADD `encrypt_key` VARCHAR(500) NOT NULL AFTER `wc_order_id`;' ); //phpcs:ignore
			}

            /* 9.2.0  Updating data type of wc_email_header column in ac_notification_templates */
            if ( $wpdb->get_var( "SHOW TABLES LIKE '". $db_prefix ."ac_notification_templates';" ) ) {
                $wpdb->query(
                    'ALTER TABLE `' . $db_prefix . 'ac_notification_templates` CHANGE `wc_email_header` `wc_email_header` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL AFTER `is_wc_template`;'
                );
            }

            // Add an option record for a new key.
            if ( '' === get_option( 'wcap_random_security_key', '' ) ) {
                $rand_key = wcap_generate_random_key();
                add_option( 'wcap_random_security_key', $rand_key );

                // Now decrypt and resave the pwd with the new encrypt_key.
                if ( '' !== get_option( 'wcap_custom_smtp_connector' ) ) {
                    $connector_settings = json_decode( get_option( 'wcap_custom_smtp_connector' ) );
                    if ( isset( $connector_settings->smtp_password ) && ! is_null( $connector_settings->smtp_password ) ) {
                        $smtp_password = Wcap_Aes_Ctr::decrypt( $connector_settings->smtp_password, get_option( 'ac_security_key' ), 256 );
                        $smtp_pwd_new  = Wcap_Aes_Ctr::encrypt( $smtp_password, $rand_key, 256 );
                        $connector_settings->smtp_password = $smtp_pwd_new;
                        update_option( 'wcap_custom_smtp_connector', wp_json_encode( $connector_settings ) );
                    }
                }
            }

            // 9.3.0
			if ( '' !== get_option( 'wcap_email_admin_on_abandonment', false ) && ! get_option( 'wcap_email_admin_on_abandonment', false ) ) {
                update_option( 'wcap_email_admin_on_abandonment', 'on' );
                update_option( 'wcap_email_admin_custom_addresses', get_option( 'admin_email' ) );
                $cart_source = array(
                    'all'         => 'on',
                    'checkout'    => 'on',
                    'profile'     => 'on',
                    'atc'         => 'on',
                    'exit_intent' => 'on',
                    'url'         => 'on',
                    'custom_form' => 'on',
                );
                update_option( 'wcap_email_admin_cart_source', $cart_source );
			}

            if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `". $db_prefix."ac_abandoned_cart_history` LIKE 'whatsapp_reminder_status';" ) ) {
                $wpdb->query(
                    'ALTER TABLE ' . $db_prefix . 'ac_abandoned_cart_history ADD `whatsapp_reminder_status` varchar(50) COLLATE utf8_unicode_ci NOT NULL AFTER `fb_reminder_status`;'
                );
            }

            do_action( 'wcap_after_update_plugin' );
        }

        /**
         * Change the manual_emails column to email_reminder_status column
         *
         * @param string $db_prefix - DB prefix.
         * @since 8.9.1
         */
        public static function wcap_update_manual_emails_column( $db_prefix ) {
            global $wpdb;

            $wpdb->update(
                $db_prefix . "ac_abandoned_cart_history",
                array(
                    'manual_email' => 'manual',
                ),
                array(
                    'manual_email' => 'yes',
                )
			);
			if ( $wpdb->get_var( "SHOW COLUMNS FROM `". $db_prefix ."ac_abandoned_cart_history` LIKE 'manual_email'" ) && ! $wpdb->get_var( "SHOW COLUMNS FROM `" . $db_prefix . "ac_abandoned_cart_history` LIKE 'email_reminder_status'" ) ) {
				$wpdb->query(
					"ALTER TABLE `" . $db_prefix . "ac_abandoned_cart_history` CHANGE `manual_email` `email_reminder_status` VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL;" 
				);
			}
            // Mark the old carts for whom email sequences have completed as 'complete'.
            $get_last_template = wcap_get_last_template_in_reminder_cycle( 'email', 'abandoned_cart_email' );
            $template_freq = is_array( $get_last_template ) && count( $get_last_template ) > 0 ? array_pop( $get_last_template ) : 0;
            $send_after_days = get_option( 'ac_cart_abandoned_after_x_days_order_placed', 7 ) * 86400;
            $cron_duration = get_option( 'wcap_cron_time_duration', 15 ) * 60;
            $leave_carts_abandoned_in = current_time( 'timestamp' ) - ( $template_freq + $send_after_days + $cron_duration );

            $wpdb->query(
                "UPDATE " . $db_prefix . "ac_abandoned_cart_history SET email_reminder_status = 'complete' WHERE abandoned_cart_time < $leave_carts_abandoned_in AND email_reminder_status = ''" 
            );
        
        }
        /**
         * It will alter the options if required.
         * @globals mixed $wpdb
         * @since 5.0
         */
        public static function wcap_update_options_if_required( $blog_id = 0 ) {

            $wcap_product_image_height_px = 125;
            $wcap_product_image_width_px = 125;
            
            if( $blog_id === 0 ) {
                if ( '1' != get_option( 'wcap_update_options_ran' ) ) {

                    $key = get_option( 'ac_security_key' );
                    if ( ! $key || hash( 'sha256', $key ) === self::LEGACY_DEFAULT_KEY_HASH ) {
                        $key = bin2hex( random_bytes( 32 ) ); // cryptographically random, unique per site
                        update_option( 'ac_security_key', $key );
                    }

                    if ( !get_option( 'wcap_product_image_height' ) ) {
                        update_option( 'wcap_product_image_height', $wcap_product_image_height_px);
                    }

                    if ( !get_option( 'wcap_product_image_width' ) ) {
                        update_option( 'wcap_product_image_width', $wcap_product_image_width_px );
                    }

                    update_option( 'wcap_update_options_ran', '1', 'no' );

                }

                if ( !get_option( 'wcap_fb_consent_text' ) ) {
                    add_option( 'wcap_fb_consent_text', 'Allow order status to be sent to Facebook Messenger' );
                }

            } else {

                if ( '1' != get_blog_option( $blog_id, 'wcap_update_options_ran' ) ) {

                    $key = get_blog_option( $blog_id, 'ac_security_key' );
                    if ( ! $key || hash( 'sha256', $key ) === self::LEGACY_DEFAULT_KEY_HASH ) {
                        $key = bin2hex( random_bytes( 32 ) ); // cryptographically random, unique per site
                        update_blog_option( $blog_id, 'ac_security_key', $key );
                    }

                    if ( !get_blog_option( $blog_id, 'wcap_product_image_height' ) ) {
                        update_blog_option( $blog_id, 'wcap_product_image_height', $wcap_product_image_height_px);
                    }

                    if ( !get_blog_option( $blog_id, 'wcap_product_image_width' ) ) {
                        update_blog_option( $blog_id, 'wcap_product_image_width', $wcap_product_image_width_px );
                    }

                    update_blog_option( $blog_id, 'wcap_update_options_ran', '1' );

                }

                if ( !get_blog_option( $blog_id, 'wcap_fb_consent_text' ) ) {
                    add_blog_option( $blog_id, 'wcap_fb_consent_text', 'Allow order status to be sent to Facebook Messenger' );
                }

            }
            
        }
        /**
         * When we update the plugin we need to add who will receive the email template.
         * @globals mixed $wpdb
         * @since 7.1
         */
        public static function wcap_add_customer_for_template( $db_prefix, $blog_id ) {
            global $wpdb;

            if ( $wpdb->get_var( "SHOW TABLES LIKE '". $db_prefix ."ac_email_templates';" ) ) {
                $wcap_get_all_template   = "SELECT id FROM ". $db_prefix . "ac_email_templates";
                $wcap_result_of_template = $wpdb->get_results ( $wcap_get_all_template );
            }

            if( $blog_id === 0 ) {
                $wcap_admin_setting = get_option ('ac_email_admin_on_abandoned');
            } else {
                $wcap_admin_setting = get_blog_option( $blog_id, 'ac_email_admin_on_abandoned');
            }

            if ( isset( $wcap_admin_setting ) && 'on' == $wcap_admin_setting ) {
                $wcap_customers_key = 'wcap_email_customer_admin';
            }else{
                $wcap_customers_key = 'wcap_email_customer';
            }
            if ( count( $wcap_result_of_template ) > 0 ) {

                foreach ($wcap_result_of_template as $wcap_result_of_template_key => $wcap_result_of_template_value ) {
                    add_post_meta( $wcap_result_of_template_value->id , 'wcap_email_action', $wcap_customers_key );
                }

                if( $blog_id === 0 ) {
                    delete_option ( 'ac_email_admin_on_abandoned' );
                } else {
                    delete_blog_option ( $blog_id, 'ac_email_admin_on_abandoned' );
                }
            
            }
        }

		/**
		 * Update the data format for _woocommerce_ac_coupons in post meta.
		 *
		 * @since 8.8.1
		 */
        public static function wcap_update_postmeta_coupons() {

			global $wpdb;
			// Get the count of abandoned carts.
			$cart_ids = $wpdb->get_results(
				"SELECT id from " . WCAP_ABANDONED_CART_HISTORY_TABLE . " AS cart_history INNER JOIN " . $wpdb->prefix . "postmeta as meta ON cart_history.id = meta.post_id where cart_history.cart_ignored = '0' and meta.meta_key = '_woocommerce_ac_coupon'"
			);
    
			// Create batches of 100.
			if ( is_array( $cart_ids ) && count( $cart_ids ) > 100 ) {
				$chunks = array_chunk( $cart_ids, 100 );
			} else {
				$chunks[0] = $cart_ids;
			}
            
            // loop through this in batches.
			foreach( $chunks as $coupon_meta ) {
				foreach( $coupon_meta as $coupon_details ) {
					$post_id = $coupon_details->id;
					$coupon_data = get_post_meta( $post_id, '_woocommerce_ac_coupon' );
                    update_post_meta( $post_id, '_woocommerce_ac_coupon', $coupon_data );
				}
			}
                    
        }
    }
}
