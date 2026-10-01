<?php
/**
 * Abandoned Cart Pro for WooCommerce Uninstall
 *
 * Uninstalling Abandoned Cart Pro for WooCommerce deletes tables, and options.
 *
 * @author      LittleCodr
 * @package     Abandoned-Cart-Pro-for-WooCommerce/Uninstaller
 * @version     5.0
 * @todo in v9.0 - remove the delete queries for post meta
 *                  1. wcap_email_action
 *                  2. wcap_other_emails
 *                  3. wcap_template_time
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

wp_clear_scheduled_hook( 'woocommerce_ac_send_email_action' );

$ac_delete = get_option( 'wcac_delete_plugin_data' );

if ( 'on' === $ac_delete ) {

    /**
     * Delete the data for the WordPress Multisite.
     */
    if ( is_multisite() ) {
        
        $blog_list = get_sites();
        foreach ( $blog_list as $blog_list_key => $blog_list_value ) {
            $blog_id = $blog_list_value->blog_id; 
            if ( $blog_id > 1 ) {
                $sub_site_prefix = $wpdb->prefix . $blog_id . '_';//phpcs:disable

                $table_name_ac_abandoned_cart_history = $sub_site_prefix . "ac_abandoned_cart_history";
                $sql_ac_abandoned_cart_history = "DROP TABLE IF EXISTS " . $table_name_ac_abandoned_cart_history ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_ac_abandoned_cart_history );//phpcs:ignore

                $table_name_ac_email_templates = $sub_site_prefix . "ac_notification_templates";
                $sql_ac_email_templates = "DROP TABLE IF EXISTS " . $table_name_ac_email_templates ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );//phpcs:ignore
                $wpdb->get_results( $sql_ac_email_templates );

                $table_name_ac_sent_history = $sub_site_prefix . "ac_sent_history";
                $sql_ac_sent_history = "DROP TABLE IF EXISTS " . $table_name_ac_sent_history ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );//phpcs:ignore
                $wpdb->get_results( $sql_ac_sent_history );//phpcs:ignore

                $table_name_ac_opened_emails = $sub_site_prefix . "ac_opened_notifications";
                $sql_ac_opened_emails = "DROP TABLE IF EXISTS " . $table_name_ac_opened_emails ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );//phpcs:ignore
                $wpdb->get_results( $sql_ac_opened_emails );//phpcs:ignore

                $table_name_ac_link_clicked_email = $sub_site_prefix . "ac_link_clicked_notifications";
                $sql_ac_link_clicked_email = "DROP TABLE IF EXISTS " . $table_name_ac_link_clicked_email ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_ac_link_clicked_email );//phpcs:ignore

                $table_name_ac_guest_abandoned_cart_history = $sub_site_prefix . "ac_guest_abandoned_cart_history";
                $sql_ac_abandoned_cart_history = "DROP TABLE IF EXISTS " . $table_name_ac_guest_abandoned_cart_history ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_ac_abandoned_cart_history );//phpcs:ignore

                $table_name_tiny_urls = $sub_site_prefix . "ac_tiny_urls";
                $sql_ac_tiny_urls = "DROP TABLE IF EXISTS " . $table_name_tiny_urls ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_ac_tiny_urls );//phpcs:ignore
                
                $table_name_aelia = $sub_site_prefix . "abandoned_cart_aelia_currency";
                $sql_aelia = "DROP TABLE IF EXISTS " . $table_name_aelia ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_aelia );//phpcs:ignore

                $table_name_atc_templates = $sub_site_prefix . "ac_atc_rules";
                $sql_atc_templates = "DROP TABLE IF EXISTS " . $table_name_atc_templates;
                $wpdb->get_results( $sql_atc_templates );//phpcs:ignore

                $table_name_ac_stats = $sub_site_prefix . "ac_statistics";
                $sql_ac_stats = "DROP TABLE IF EXISTS " . $table_name_ac_stats;
                $wpdb->get_results( $sql_ac_stats );//phpcs:ignore

                $wpdb->get_results( 'DROP TABLE IF EXISTS ' . $sub_site_prefix . 'ac_notifications' );

                $wpdb->get_results( 'DROP TABLE IF EXISTS ' . $sub_site_prefix . 'ac_notifications_meta' );

                $sql_table_post_meta = "DELETE FROM `" . $sub_site_prefix . "postmeta` WHERE meta_key = '_woocommerce_ac_coupon'";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_table_post_meta );//phpcs:ignore

                $option_name = $wpdb->prefix . $blog_id . "_wcap_ac_default_templates_installed";

                $sql_table_option_data = "DELETE FROM `" . $sub_site_prefix . "options` WHERE option_name = '" . $option_name . "' ";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_table_option_data );//phpcs:ignore

                $option_name = $wpdb->prefix . $blog_id . '_wcap_move_templates_data';

                $sql_table_option_data = "DELETE FROM `" . $sub_site_prefix . "options` WHERE option_name = '" . $option_name . "' ";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_table_option_data );//phpcs:ignore

                 /**
                 * ATC meta key for the reports
                 */
                $wcap_atc_post_meta = "DELETE FROM `" . $sub_site_prefix . "postmeta` WHERE meta_key = 'wcap_atc_report'";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $wcap_atc_post_meta );//phpcs:ignore

                /**
                 * Post meta for recovred email sent
                 */
                $wcap_recovered_email_post_meta = "DELETE FROM `" . $sub_site_prefix . "postmeta` WHERE meta_key = 'wcap_recovered_email_sent'";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $wcap_recovered_email_post_meta );//phpcs:ignore

                /**
                 * Post meta for the email templates action
                 */
                $wcap_email_templates_action_post_meta = "DELETE FROM `" . $sub_site_prefix . "postmeta` WHERE meta_key = 'wcap_email_action'";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $wcap_email_templates_action_post_meta );//phpcs:ignore
                
                $wcap_email_templates_action_other_post_meta = "DELETE FROM `" . $sub_site_prefix . "postmeta` WHERE meta_key = 'wcap_other_emails'";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $wcap_email_templates_action_other_post_meta );//phpcs:ignore

                /**
                 * New created email template time
                 */
                $wcap_new_email_templates_time_post_meta = "DELETE FROM `" . $sub_site_prefix . "postmeta` WHERE meta_key = 'wcap_template_time'";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $wcap_new_email_templates_time_post_meta );//phpcs:ignore

                delete_blog_option( $blog_id, 'ac_enable_cart_emails' );
                delete_blog_option( $blog_id, 'ac_cart_abandoned_time' );
                delete_blog_option( $blog_id, 'ac_cart_abandoned_time_guest' );
                delete_blog_option( $blog_id, 'ac_delete_abandoned_order_days' );
                delete_blog_option( $blog_id, 'ac_email_admin_on_recovery' );
                delete_blog_option( $blog_id, 'ac_track_coupons' );
                delete_blog_option( $blog_id, 'ac_disable_guest_cart_email' );
                delete_blog_option( $blog_id, 'ac_disable_logged_in_cart_email' );
                delete_blog_option( $blog_id, 'ac_track_guest_cart_from_cart_page' );
                delete_blog_option( $blog_id, 'ac_settings_status' );
                delete_blog_option( $blog_id, 'woocommerce_ac_db_version' );
                delete_blog_option( $blog_id, 'wcap_cron_time_duration' );
                delete_blog_option( $blog_id, 'ac_email_admin_on_abandoned' );
                delete_blog_option( $blog_id, 'wcap_restrict_ip_address' );
                delete_blog_option( $blog_id, 'wcap_restrict_email_address' );
                delete_blog_option( $blog_id, 'wcap_restrict_domain_address' );
                delete_blog_option( $blog_id, 'wcap_restrict_countries' );
                delete_blog_option( $blog_id, 'wcap_use_auto_cron' );
                delete_blog_option( $blog_id, 'wcap_from_name' );
                delete_blog_option( $blog_id, 'wcap_from_email' );
                delete_blog_option( $blog_id, 'wcap_reply_email' );
                delete_blog_option( $blog_id, 'wcap_product_image_height' );
                delete_blog_option( $blog_id, 'wcap_product_image_width' );
                delete_blog_option( $blog_id, 'wcap_guest_user_id_altered' );
                delete_blog_option( $blog_id, 'wcap_guest_last_id_checked' );
                delete_blog_option( $blog_id, 'wcap_guest_cart_capture_msg' );
                delete_blog_option( $blog_id, 'wcap_logged_cart_capture_msg' );
                delete_blog_option( $blog_id, 'wcap_gdpr_allow_opt_out' );
                delete_blog_option( $blog_id, 'wcap_gdpr_opt_out_message' );
                delete_blog_option( $blog_id, 'wcap_delete_coupon_data' );
                delete_blog_option( $blog_id, 'ac_cart_abandoned_after_x_days_order_placed' );
				delete_blog_option( $blog_id, 'wcap_auto_login_users' );
                /**
                 * ATC DATA 
                 */
                delete_blog_option( $blog_id, 'wcap_heading_section_text_email' );
                delete_blog_option( $blog_id, 'wcap_popup_heading_color_picker' );
                delete_blog_option( $blog_id, 'wcap_text_section_text' );
                delete_blog_option( $blog_id, 'wcap_popup_text_color_picker' );
                delete_blog_option( $blog_id, 'wcap_email_placeholder_section_input_text' );
                delete_blog_option( $blog_id, 'wcap_button_section_input_text' );
                delete_blog_option( $blog_id, 'wcap_button_color_picker' );
                delete_blog_option( $blog_id, 'wcap_button_text_color_picker' );
                delete_blog_option( $blog_id, 'wcap_non_mandatory_text' );
                delete_blog_option( $blog_id, 'wcap_atc_enable_modal' );
                delete_blog_option( $blog_id, 'wcap_atc_mandatory_email' );
                delete_blog_option( $blog_id, 'wcap_custom_pages_list' );
                delete_blog_option( $blog_id, 'wcap_atc_auto_apply_coupon_enabled' );
                delete_blog_option( $blog_id, 'wcap_atc_popup_coupon' );
                delete_blog_option( $blog_id, 'wcap_atc_popup_coupon_validity' );
                delete_blog_option( $blog_id, 'wcap_countdown_timer_msg' );
                delete_blog_option( $blog_id, 'wcap_countdown_msg_expired' );
                delete_blog_option( $blog_id, 'wcap_countdown_cart' );
                delete_blog_option( $blog_id, 'wcap_atc_coupon_validity_minutes' );
                delete_blog_option( $blog_id, 'wcap_atc_coupon_type' );
                delete_blog_option( $blog_id, 'wcap_atc_discount_type' );
                delete_blog_option( $blog_id, 'wcap_atc_discount_amount' );
                delete_blog_option( $blog_id, 'wcap_atc_coupon_free_shipping' );
                
                delete_blog_option( $blog_id, 'wcap_import_page_displayed' );
                delete_blog_option( $blog_id, 'wcap_lite_data_imported' );
                delete_blog_option( $blog_id, 'wcap_alter_tables_ran' );
                delete_blog_option( $blog_id, 'wcap_alter_guest_columns' );
                delete_blog_option( $blog_id, 'wcap_update_options_ran' );
                delete_blog_option( $blog_id, 'wcap_template_customer_added' );
                delete_blog_option( $blog_id, 'wcap_cleanup_sent_history' );
                delete_blog_option( $blog_id, 'wcap_scheduler_update_dismiss' );

                delete_blog_option( $blog_id, 'ac_capture_email_address_from_url' );

                delete_blog_option( $blog_id, 'edd_sample_license_key_ac_woo' );
                delete_blog_option( $blog_id, 'edd_sample_license_status_ac_woo' );
                delete_blog_option( $blog_id, 'wcap_edd_license_type' );
                delete_blog_option( $blog_id, 'edd_sample_license_expires_ac_woo' );
                
                /**
                 * Unsubscribe settings.
                 * @since 8.11.0
                 */
                delete_blog_option( $blog_id, 'wcap_unsubscribe_landing_page' );
                delete_blog_option( $blog_id, 'wcap_unsubscribe_custom_content' );
                delete_blog_option( $blog_id, 'wcap_unsubscribe_custom_wp_page' );
                /**
                 * SMS Settings Data
                 * @since 7.9
                 */
                delete_blog_option( $blog_id, 'wcap_enable_sms_reminders' );
                delete_blog_option( $blog_id, 'wcap_sms_from_phone' );
                delete_blog_option( $blog_id, 'wcap_sms_account_sid' );
                delete_blog_option( $blog_id, 'wcap_sms_auth_token' );

                /**
                 * Notice option
                 * @since 7.10.0
                 */
                delete_blog_option( $blog_id, 'wcap_notice_dissmissed' );
                delete_blog_option( $blog_id, 'wcap_pro_welcome_page_shown' );
                delete_blog_option( $blog_id, 'wcap_pro_welcome_page_shown_time' );
                delete_blog_option( $blog_id, 'wcap_installation_wizard_license_key' );
                delete_blog_option( $blog_id, 'wcap_allow_tracking' );
                delete_blog_option( $blog_id, 'wcap_edd_license_download_type' );
                delete_blog_option( $blog_id, 'wcap_manually_deactivated' );
                delete_blog_option( $blog_id, 'wcap_migrated_from_lite' );
                
                /**
                 * Facebook Settings
                 */
                delete_blog_option( $blog_id, 'wcap_enable_fb_reminders' );
                delete_blog_option( $blog_id, 'wcap_enable_fb_reminders_popup' );
                delete_blog_option( $blog_id, 'wcap_fb_consent_text' );
                delete_blog_option( $blog_id, 'wcap_fb_page_id' );
                delete_blog_option( $blog_id, 'wcap_fb_user_icon' );
                delete_blog_option( $blog_id, 'wcap_fb_app_id' );
                delete_blog_option( $blog_id, 'wcap_fb_page_token' );
                delete_blog_option( $blog_id, 'wcap_fb_verify_token' );

                /**
                 * For new email templates
                 */
                delete_blog_option( $blog_id, 'wcap_new_default_templates' );

                delete_blog_option( $blog_id, 'wcac_delete_plugin_data' );

                delete_blog_option( $blog_id, 'wcap_update_coupon_code_meta' );
                delete_blog_option( $blog_id, 'wcap_rules_engine_edit_email_templates_table' );
                delete_blog_option( $blog_id, 'wcap_manual_email_col_update' );
                delete_blog_option( $blog_id, 'wcap_atc_rules_table_update' );
                delete_blog_option( $blog_id, 'wcap_atc_templates' );
                // Connector Sync Settings.
                delete_blog_option( $blog_id, 'wcap_mailchimp_connector' );
                delete_blog_option( $blog_id, 'wcap_hubspot_connector' );
                delete_blog_option( $blog_id, 'wcap_mailjet_connector' );
                delete_blog_option( $blog_id, 'wcap_activecampaign_connector' );
                delete_blog_option( $blog_id, 'wcap_active_connectors' );
                delete_blog_option( $blog_id, 'wcap_custom_smtp_connector' );
                delete_blog_option( $blog_id, 'wcap_drip_connector' );
                delete_blog_option( $blog_id, 'wcap_fluentcrm_connector' );
                delete_blog_option( $blog_id, 'wcap_klaviyo_connector' );
                delete_blog_option( $blog_id, 'wcap_sendinblue_connector' );
                delete_blog_option( $blog_id, 'wcap_wp_fusion_connector' );
				delete_blog_option( $blog_id, 'wcap_google_sheets_connector' );
				delete_blog_option( $blog_id, 'wcap_google_sheet_id' );
				delete_blog_option( $blog_id, 'wcap_google_sheet_name' );

                delete_blog_option( $blog_id, 'wcap_enable_gdpr_consent' );
                delete_blog_option( $blog_id, 'wcap_gdpr_consent_migrated' );
                delete_blog_option( $blog_id, 'wcap_enable_sms_consent' );
                delete_blog_option( $blog_id, 'wcap_sms_consent_msg' );
                delete_blog_option( $blog_id, 'wcap_coupons_data_updated' );
                delete_blog_option( $blog_id, 'wcap_product_name_redirect' );
                delete_blog_option( $blog_id, 'wcap_add_utm_to_links' );
                delete_blog_option( $blog_id, 'wcap_enable_debounce' );
                delete_blog_option( $blog_id, 'ac_debounce_api' );
                delete_blog_option( $blog_id, 'wcap_atc_rule_notice_dismiss' );
                delete_blog_option( $blog_id, 'wcap_atc_close_icon_add_product_to_cart' );
                delete_blog_option( $blog_id, 'wcap_ei_templates' );
                delete_blog_option( $blog_id, 'wcap_migrate_notification_templates' );
                delete_blog_option( $blog_id, 'wcap_email_reports_frequency' );
                delete_blog_option( $blog_id, 'wcap_email_reports_emails_list' );
                delete_blog_option( $blog_id, 'wcap_last_email_report_data' );
                delete_blog_option( $blog_id, 'wcap_new_default_followup_templates' );
                delete_blog_option( $blog_id, 'wcap_random_security_key' );
				delete_blog_option( $blog_id, 'wcap_email_admin_on_abandonment' );
				delete_blog_option( $blog_id, 'wcap_email_admin_custom_addresses' );
				delete_blog_option( $blog_id, 'wcap_email_admin_cart_source' );
				delete_blog_option( $blog_id, 'wcap_enable_tracking_by_default' );
				delete_blog_option( $blog_id, 'wcap_tracking_enabled_by_user' );
				delete_blog_option( $blog_id, 'ts_tracker_last_send' );
				delete_blog_option( $blog_id, 'wcap_add_v2_tracker_event' );
                delete_blog_option( $blog_id, 'wcap_show_only_license_page' );
                delete_blog_option( $blog_id, 'wcap_lite_data_imported_notice_dismiss' );
                delete_blog_option( $blog_id, 'wcap_enable_ai_abilities' );
             } else {
                $option_name = $wpdb->prefix . "wcap_ac_default_templates_installed";
                delete_option( $option_name );

                $option_name = $wpdb->prefix . "wcap_move_templates_data";
                delete_option( $option_name );

                $table_name_ac_abandoned_cart_history = $wpdb->prefix . 'ac_abandoned_cart_history';
                $sql_ac_abandoned_cart_history = "DROP TABLE IF EXISTS " . $table_name_ac_abandoned_cart_history ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_ac_abandoned_cart_history );//phpcs:ignore

                $table_name_ac_email_templates = $wpdb->prefix . 'ac_notification_templates';
                $sql_ac_email_templates = "DROP TABLE IF EXISTS " . $table_name_ac_email_templates ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_ac_email_templates );//phpcs:ignore

                $table_name_ac_sent_history = $wpdb->prefix . 'ac_sent_history';
                $sql_ac_sent_history = "DROP TABLE IF EXISTS " . $table_name_ac_sent_history ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_ac_sent_history );//phpcs:ignore

                $table_name_ac_opened_emails = $wpdb->prefix . 'ac_opened_notifications';
                $sql_ac_opened_emails = "DROP TABLE IF EXISTS " . $table_name_ac_opened_emails ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_ac_opened_emails );//phpcs:ignore

                $table_name_ac_link_clicked_email = $wpdb->prefix . 'ac_link_clicked_notifications';
                $sql_ac_link_clicked_email = "DROP TABLE IF EXISTS " . $table_name_ac_link_clicked_email ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_ac_link_clicked_email );//phpcs:ignore

                $table_name_ac_guest_abandoned_cart_history = $wpdb->prefix . 'ac_guest_abandoned_cart_history';
                $sql_ac_abandoned_cart_history = "DROP TABLE IF EXISTS " . $table_name_ac_guest_abandoned_cart_history ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_ac_abandoned_cart_history );//phpcs:ignore

                $sql_table_user_meta = "DELETE FROM `" . $wpdb->prefix . "usermeta` WHERE meta_key = '_woocommerce_ac_coupon'";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_table_user_meta );//phpcs:ignore

                $sql_table_post_meta = "DELETE FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key = '_woocommerce_ac_coupon'";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_table_post_meta );//phpcs:ignore

                $sql_table_user_meta_cart = "DELETE FROM `" . $wpdb->prefix . "usermeta` WHERE meta_key = '_woocommerce_persistent_cart'";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_table_user_meta_cart );//phpcs:ignore
                
                $table_name_ac_tiny_urls = $wpdb->prefix . 'ac_tiny_urls';
                $sql_ac_tiny_urls = "DROP TABLE IF EXISTS " . $table_name_ac_tiny_urls ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_ac_tiny_urls );//phpcs:ignore
                
                $table_name_aelia = $wpdb->prefix . 'abandoned_cart_aelia_currency';
                $sql_aelia = "DROP TABLE IF EXISTS " . $table_name_aelia ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_aelia );//phpcs:ignore

                $table_name_atc_templates = $wpdb->prefix . 'ac_atc_rules';
                $sql_atc_templates = "DROP TABLE IF EXISTS " . $table_name_atc_templates;
                $wpdb->get_results( $sql_atc_templates );//phpcs:ignore

                $table_name_ac_stats = $wpdb->prefix . 'ac_statistics';
                $sql_ac_stats = "DROP TABLE IF EXISTS " . $table_name_ac_stats;
                $wpdb->get_results( $sql_ac_stats );//phpcs:ignore
                
                $table_name_ac_cn_sync = $wpdb->prefix . 'ac_connector_sync';
                $sql_ac_cn_sync = "DROP TABLE IF EXISTS " . $table_name_ac_cn_sync;
                $wpdb->get_results( $sql_ac_cn_sync );//phpcs:ignore

                $wpdb->get_results( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'ac_notifications' );

                $wpdb->get_results( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'ac_notifications_meta' );

                 /**
                 * ATC meta key for the reports
                 */
                $wcap_atc_post_meta = "DELETE FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key = 'wcap_atc_report'";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $wcap_atc_post_meta );//phpcs:ignore

                /**
                 * Post meta for recovred email sent
                 */
                $wcap_recovered_email_post_meta = "DELETE FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key = 'wcap_recovered_email_sent'";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $wcap_recovered_email_post_meta );//phpcs:ignore

                /**
                 * Post meta for the email templates action
                 */
                $wcap_email_templates_action_post_meta = "DELETE FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key = 'wcap_email_action'";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $wcap_email_templates_action_post_meta );//phpcs:ignore
                
                $wcap_email_templates_action_other_post_meta = "DELETE FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key = 'wcap_other_emails'";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $wcap_email_templates_action_other_post_meta );//phpcs:ignore

                /**
                 * New created email template time
                 */
                $wcap_new_email_templates_time_post_meta = "DELETE FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key = 'wcap_template_time'";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $wcap_new_email_templates_time_post_meta );//phpcs:ignore
            }
        }
    } else {
        /**
         * Delete the data for the single website ( Non-Multisite )
         */
        delete_option( 'wcap_ac_default_templates_installed' );
        delete_option( 'wcap_move_templates_data' );

        $table_name_ac_abandoned_cart_history = $wpdb->prefix . 'ac_abandoned_cart_history';
        $sql_ac_abandoned_cart_history = "DROP TABLE IF EXISTS " . $table_name_ac_abandoned_cart_history ;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $sql_ac_abandoned_cart_history );//phpcs:ignore

        $table_name_ac_email_templates = $wpdb->prefix . 'ac_notification_templates';
        $sql_ac_email_templates = "DROP TABLE IF EXISTS " . $table_name_ac_email_templates ;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $sql_ac_email_templates );//phpcs:ignore

        $table_name_ac_sent_history = $wpdb->prefix . 'ac_sent_history';
        $sql_ac_sent_history = "DROP TABLE IF EXISTS " . $table_name_ac_sent_history ;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $sql_ac_sent_history );

        $table_name_ac_opened_emails = $wpdb->prefix . 'ac_opened_notifications';
        $sql_ac_opened_emails = "DROP TABLE IF EXISTS " . $table_name_ac_opened_emails ;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $sql_ac_opened_emails );

        $table_name_ac_link_clicked_email = $wpdb->prefix . 'ac_link_clicked_notifications';
        $sql_ac_link_clicked_email = "DROP TABLE IF EXISTS " . $table_name_ac_link_clicked_email ;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $sql_ac_link_clicked_email );

        $table_name_ac_guest_abandoned_cart_history = $wpdb->prefix . 'ac_guest_abandoned_cart_history';
        $sql_ac_abandoned_cart_history = "DROP TABLE IF EXISTS " . $table_name_ac_guest_abandoned_cart_history ;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $sql_ac_abandoned_cart_history );

        $table_name_ac_tiny_urls = $wpdb->prefix . 'ac_tiny_urls';
        $sql_ac_tiny_urls = "DROP TABLE IF EXISTS " . $table_name_ac_tiny_urls ;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $sql_ac_tiny_urls );

        $table_name_aelia = $wpdb->prefix . 'abandoned_cart_aelia_currency';
        $sql_aelia = "DROP TABLE IF EXISTS " . $table_name_aelia ;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $sql_aelia );
                
        $table_name_atc_templates = $wpdb->prefix . 'ac_atc_rules';
        $sql_atc_templates = "DROP TABLE IF EXISTS " . $table_name_atc_templates;
        $wpdb->get_results( $sql_atc_templates );

        $table_name_ac_stats = $wpdb->prefix . 'ac_statistics';
        $sql_ac_stats = "DROP TABLE IF EXISTS " . $table_name_ac_stats;
        $wpdb->get_results( $sql_ac_stats );

        $table_name_ac_cn_sync = $wpdb->prefix . 'ac_connector_sync';
        $sql_ac_cn_sync = "DROP TABLE IF EXISTS " . $table_name_ac_cn_sync;
        $wpdb->get_results( $sql_ac_cn_sync );

        $wpdb->get_results( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'ac_notifications' );

        $wpdb->get_results( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'ac_notifications_meta' );

        $sql_table_user_meta = "DELETE FROM `" . $wpdb->prefix . "usermeta` WHERE meta_key = '_woocommerce_ac_coupon'";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $sql_table_user_meta );

        $sql_table_post_meta = "DELETE FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key = '_woocommerce_ac_coupon'";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $sql_table_post_meta );

        $sql_table_user_meta_cart = "DELETE FROM `" . $wpdb->prefix . "usermeta` WHERE meta_key = '_woocommerce_persistent_cart'";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $sql_table_user_meta_cart );
        /**
         * ATC meta key for the reports
         */
        $wcap_atc_post_meta = "DELETE FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key = 'wcap_atc_report'";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $wcap_atc_post_meta );

        /**
         * Post meta for recovred email sent
         */
        $wcap_recovered_email_post_meta = "DELETE FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key = 'wcap_recovered_email_sent'";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $wcap_recovered_email_post_meta );
        
        /**
         * Post meta for the email templates action
         */
        $wcap_email_templates_action_post_meta = "DELETE FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key = 'wcap_email_action'";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $wcap_email_templates_action_post_meta );
        
        $wcap_email_templates_action_other_post_meta = "DELETE FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key = 'wcap_other_emails'";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $wcap_email_templates_action_other_post_meta );

        /**
         * New created email template time
         */
        $wcap_new_email_templates_time_post_meta = "DELETE FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key = 'wcap_template_time'";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $wcap_new_email_templates_time_post_meta );
    }

    /**
     * Delete the settings option records.
     */
    delete_option( 'ac_enable_cart_emails' );
    delete_option( 'ac_cart_abandoned_time' );
    delete_option( 'ac_cart_abandoned_time_guest' );
    delete_option( 'ac_delete_abandoned_order_days' );
    delete_option( 'ac_email_admin_on_recovery' );
    delete_option( 'ac_track_coupons' );
    delete_option( 'ac_disable_guest_cart_email' );
    delete_option( 'ac_disable_logged_in_cart_email' );
    delete_option( 'ac_track_guest_cart_from_cart_page' );
    delete_option( 'ac_settings_status' );
    delete_option( 'woocommerce_ac_db_version' );
    delete_option( 'wcap_cron_time_duration' );
    delete_option( 'ac_email_admin_on_abandoned' );
    delete_option( 'wcap_restrict_ip_address' );
    delete_option( 'wcap_restrict_email_address' );
    delete_option( 'wcap_restrict_domain_address' );
    delete_option( 'wcap_restrict_countries' );
    delete_option( 'wcap_use_auto_cron' );
    delete_option( 'wcap_from_name' );
    delete_option( 'wcap_from_email' );
    delete_option( 'wcap_reply_email' );
    delete_option( 'wcap_product_image_height' );
    delete_option( 'wcap_product_image_width' );
    delete_option( 'wcap_guest_user_id_altered' );
    delete_option( 'wcap_guest_last_id_checked' );
    delete_option( 'wcap_guest_cart_capture_msg' );
    delete_option( 'wcap_logged_cart_capture_msg' );
    delete_option( 'wcap_gdpr_allow_opt_out' );
    delete_option( 'wcap_gdpr_opt_out_message' );
    delete_option( 'wcap_delete_coupon_data' );
    delete_option( 'ac_cart_abandoned_after_x_days_order_placed' );
	delete_option( 'wcap_auto_login_users' );

    /**
     * ATC DATA 
     */
    delete_option( 'wcap_heading_section_text_email' );
    delete_option( 'wcap_popup_heading_color_picker' );
    delete_option( 'wcap_text_section_text' );
    delete_option( 'wcap_popup_text_color_picker' );
    delete_option( 'wcap_email_placeholder_section_input_text' );
    delete_option( 'wcap_button_section_input_text' );
    delete_option( 'wcap_button_color_picker' );
    delete_option( 'wcap_button_text_color_picker' );
    delete_option( 'wcap_non_mandatory_text' );
    delete_option( 'wcap_atc_enable_modal' );
    delete_option( 'wcap_atc_mandatory_email' );
    delete_option( 'wcap_custom_pages_list' );
    delete_option( 'wcap_atc_auto_apply_coupon_enabled' );
    delete_option( 'wcap_atc_popup_coupon' );
    delete_option( 'wcap_atc_popup_coupon_validity' );
    delete_option( 'wcap_countdown_timer_msg' );
    delete_option( 'wcap_countdown_msg_expired' );
    delete_option( 'wcap_countdown_cart' );
    delete_option( 'wcap_atc_coupon_validity_minutes' );
    delete_option( 'wcap_atc_coupon_type' );
    delete_option( 'wcap_atc_discount_type' );
    delete_option( 'wcap_atc_discount_amount' );
    delete_option( 'wcap_atc_coupon_free_shipping' );

    delete_option( 'wcap_import_page_displayed' );
    delete_option( 'wcap_lite_data_imported' );
    delete_option( 'wcap_alter_tables_ran' );
    delete_option( 'wcap_alter_guest_columns' );
    delete_option( 'wcap_update_options_ran' );
    delete_option( 'wcap_template_customer_added' );
    delete_option( 'wcap_cleanup_sent_history' );
    delete_option( 'wcap_scheduler_update_dismiss' );

    delete_option( 'ac_capture_email_address_from_url' );

    delete_option( 'edd_sample_license_key_ac_woo' );
    delete_option( 'edd_sample_license_status_ac_woo' );
    delete_option( 'wcap_edd_license_type' );
    delete_option( 'wcap_show_only_license_page' );
    delete_option( 'wcap_edd_license_download_type' );
    delete_option( 'wcap_manually_deactivated' );
    
    /**
     * Unsubscribe settings.
     * @since 8.11.0
     */
    delete_option( 'wcap_unsubscribe_landing_page' );
    delete_option( 'wcap_unsubscribe_custom_content' );
    delete_option( 'wcap_unsubscribe_custom_wp_page' );
    /**
     * SMS Settings Data
     * @since 7.9
     */
    delete_option( 'wcap_enable_sms_reminders' );
    delete_option( 'wcap_sms_from_phone' );
    delete_option( 'wcap_sms_account_sid' );
    delete_option( 'wcap_sms_auth_token' );

    /**
     * Notice option
     * @since 7.10.0
     */
    delete_option( 'wcap_notice_dissmissed' );
    delete_option( 'wcap_pro_welcome_page_shown' );
    delete_option( 'wcap_pro_welcome_page_shown_time' );
    delete_option( 'wcap_installation_wizard_license_key' );
    delete_option( 'wcap_allow_tracking' );
    
    /**
     * Facebook Settings
     */
    delete_option( 'wcap_enable_fb_reminders' );
    delete_option( 'wcap_enable_fb_reminders_popup' );
    delete_option( 'wcap_fb_consent_text' );
    delete_option( 'wcap_fb_page_id' );
    delete_option( 'wcap_fb_user_icon' );
    delete_option( 'wcap_fb_app_id' );
    delete_option( 'wcap_fb_page_token' );
    delete_option( 'wcap_fb_verify_token' );

    /**
     * For new email templates
     */
    delete_option( 'wcap_new_default_templates' );

    delete_option( 'wcac_delete_plugin_data' );

    delete_option( 'wcap_update_coupon_code_meta' );
    delete_option( 'wcap_rules_engine_edit_email_templates_table' );
    delete_option( 'wcap_manual_email_col_update' );
    delete_option( 'wcap_atc_rules_table_update' );
    delete_option( 'wcap_atc_templates' );

    // Connector Sync Settings.
    delete_option( 'wcap_mailchimp_connector' );
    delete_option( 'wcap_hubspot_connector' );
    delete_option( 'wcap_mailjet_connector' );
    delete_option( 'wcap_activecampaign_connector' );
    delete_option( 'wcap_active_connectors' );
    delete_option( 'wcap_custom_smtp_connector' );
    delete_option( 'wcap_drip_connector' );
    delete_option( 'wcap_fluentcrm_connector' );
    delete_option( 'wcap_klaviyo_connector' );
    delete_option( 'wcap_sendinblue_connector' );
    delete_option( 'wcap_wp_fusion_connector' );
	delete_option( 'wcap_google_sheets_connector' );
	delete_option( 'wcap_google_sheet_id' );
	delete_option( 'wcap_google_sheet_name' );

    delete_option( 'wcap_enable_gdpr_consent' );
    delete_option( 'wcap_gdpr_consent_migrated' );
    delete_option( 'wcap_enable_sms_consent' );
    delete_option( 'wcap_sms_consent_msg' );
    delete_option( 'wcap_coupons_data_updated' );
    delete_option( 'wcap_product_name_redirect' );
    delete_option( 'wcap_add_utm_to_links' );
    delete_option( 'wcap_enable_debounce' );
    delete_option( 'ac_debounce_api' );
    delete_option( 'wcap_atc_rule_notice_dismiss' );
    delete_option( 'wcap_atc_close_icon_add_product_to_cart' );
    delete_option( 'wcap_ei_templates' );
    delete_option( 'wcap_migrate_notification_templates' );
    delete_option( 'wcap_email_reports_frequency' );
    delete_option( 'wcap_email_reports_emails_list' );
    delete_option( 'wcap_last_email_report_data' );
    delete_option( 'wcap_new_default_followup_templates' );
    delete_option( 'wcap_random_security_key' );
	delete_option( 'wcap_email_admin_on_abandonment' );
	delete_option( 'wcap_email_admin_custom_addresses' );
	delete_option( 'wcap_email_admin_cart_source' );
	delete_option( 'wcap_enable_tracking_by_default' );
	delete_option( 'wcap_tracking_enabled_by_user' );
	delete_option( 'ts_tracker_last_send' );
	delete_option( 'wcap_add_v2_tracker_event' );
    delete_option( 'wcap_migrated_from_lite' );
    delete_option( 'edd_sample_license_expires_ac_woo' );
    delete_option( 'wcap_lite_data_imported_notice_dismiss' );

    /**
     * AI Reporting Abilities.
     * @since 10.6.0
     */
    delete_option( 'wcap_enable_ai_abilities' );
    delete_metadata( 'user', 0, 'wcap_ai_abilities_notice_dismissed', '', true );

    wp_cache_flush();
}