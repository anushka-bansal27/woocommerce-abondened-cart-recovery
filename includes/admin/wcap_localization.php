<?php
/**
 * It will add the template string to the WPML.
 * Like : Body, subject, Wc header text
 * @author   Tyche Softwares
 * @package Abandoned-Cart-Pro-for-WooCommerce/Admin/I18N
 * @since 5.0
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( !class_exists('Wcap_Localization' ) ) {
    /**
     * It will add the template string to the WPML.
     * Like : Body, subject, Wc header text
     *
     */
    class Wcap_Localization{
        /** 
         * This function used to register template string to WPML.
         * Like : Body, subject, Wc header text
         * @hook admin_init
         * @globals mixed $wpdb
         * @since 2.7
         */
        public static function wcap_register_template_string_for_wpml() {
            if ( function_exists('icl_register_string') ) {
                global $wpdb;
                $context = 'WCAP';
                
                $result = $wpdb->get_results( "SELECT * FROM " . WCAP_NOTIFICATION_TEMPLATES_TABLE . " WHERE notification_type = 'email'" );// phpcs:ignore
                foreach ( $result as $each_template ) {
                    $name_msg = 'wcap_template_' . $each_template->id . '_message';
                    $value_msg = $each_template->body;
                    icl_register_string($context, $name_msg, $value_msg); //for registering message

                    $name_sub = 'wcap_template_' . $each_template->id . '_subject';
                    $value_sub = $each_template->subject;
                    icl_register_string($context, $name_sub, $value_sub); //for registering subject

                    $template_name = 'wcap_template_' . $each_template->id . '_template_name';
                    $getvalue_template_name = $each_template->template_name;
                    icl_register_string($context, $template_name, $getvalue_template_name);

                    $wc_email_header = 'wcap_template_' . $each_template->id . '_wc_email_header';
                    $getvalue_wc_email_header = $each_template->wc_email_header;
                    icl_register_string($context, $wc_email_header, $getvalue_wc_email_header);
                }

                // SMS & FB
                $short_messages = $wpdb->get_results( "SELECT id, subject, body, notification_type FROM " . WCAP_NOTIFICATION_TEMPLATES_TABLE . " WHERE notification_type IN ('sms','fb')" );// phpcs:ignore
                if( is_array( $short_messages ) && count( $short_messages ) > 0 ) {
                    foreach( $short_messages as $message_data ) {
                        $subject      = $message_data->subject;
                        $body         = $message_data->body;
                        $subject_name = 'wcap_' . $message_data->notification_type . '_' . $message_data->id . '_subject';
                        $body_name    = 'wcap_' . $message_data->notification_type . '_' . $message_data->id . '_body';

                        icl_register_string( $context, $subject_name, $subject );
                        icl_register_string( $context, $body_name, $body );
                    }
                }

                //ATC &EI popup
                $table = esc_sql( $wpdb->prefix . 'ac_atc_rules' );
                $query = "SELECT id, frontend_settings, quick_checkout_settings FROM $table";  //phpcs:ignore
                $rules = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore

                if ( is_array( $rules ) && count( $rules ) > 0 ) {
                    foreach ( $rules as $rule ) {
                        $rule_id = $rule['id'];

                        // --- Frontend Popup Settings ---
                        $frontend = json_decode( $rule['frontend_settings'], true );
                        if ( ! empty( $frontend ) ) {
                            $frontend_keys = [
                                'wcap_heading_section_text_email',
                                'wcap_text_section_text',
                                'wcap_email_placeholder_section_input_text',
                                'wcap_button_section_input_text',
                                'wcap_non_mandatory_text',
                                'wcap_atc_phone_placeholder',
                            ];

                            foreach ( $frontend_keys as $key ) {
                                if ( ! empty( $frontend[$key] ) ) {
                                    $name = $key;
                                    icl_register_string( $context, $name, $frontend[$key] );
                                }
                            }
                        }

                        // --- Quick Checkout Popup Settings ---
                        $quick_ck = json_decode( $rule['quick_checkout_settings'], true );
                        if ( ! empty( $quick_ck ) ) {
                            $quick_ck_keys = [
                                'wcap_quick_ck_modal_heading',
                                'wcap_quick_ck_modal_text',
                                'wcap_quick_ck_link_text',
                            ];

                            foreach ( $quick_ck_keys as $key ) {
                                if ( ! empty( $quick_ck[$key] ) ) {
                                    $name = $key;
                                    icl_register_string( $context, $name, $quick_ck[$key] );
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
