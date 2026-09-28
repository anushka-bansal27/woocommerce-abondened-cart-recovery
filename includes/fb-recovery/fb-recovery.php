<?php
/**
 * Abandoned Cart Pro for WooCommerce
 *
 * Load FB Messenger related Files and Actions
 * 
 * @author   Tyche Softwares
 * @package  Abandoned-Cart-Pro-for-WooCommerce/FB-Messenger
 * @category Modules
 * @since    7.10.0
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;   //Exit if accessed directly.
}

require_once( WCAP_PLUGIN_PATH . '/includes/wcap_tiny_url.php' );
if ( !class_exists( 'WCAP_FB_Recovery' ) ) {

    /**
     * Class for performing FB Messenger related actions
     */
    class WCAP_FB_Recovery {

        function __construct() {

            self::wcap_fb_set_details();
            $is_valid = apply_filters( 'wcap_license_valid', true );
            if ( is_admin() ) {
                self::wcap_include_admin();

            } else if( get_option( 'wcap_enable_fb_reminders' ) && $is_valid ) {
                self::wcap_include_frontend();
            }
            
            self::wcap_include_library();

            self::wcap_fb_webhook_setup();

            add_filter( 'wcap_reminders_list', array( &$this, 'wcap_fb_reminder' ), 10, 1 );
        }

        public static function wcap_fb_webhook_setup() {
            if ( 'on' === get_option( 'wcap_enable_fb_reminders', '' ) ) {
                add_action( 'init',          array( 'WCAP_FB_Recovery', 'wcap_fb_webhook_endpoint' ) );
            }
            add_action( 'parse_request', array( 'WCAP_FB_Recovery', 'wcap_fb_webhook_parse_request' ) );
        }

        public static function wcap_fb_webhook_endpoint() {
            // access webhook at url such as http://[your site]/mailchimp/webhook
            add_rewrite_rule( 'acpro-callback-webhook' , 'index.php?acpro-callback-webhook=1', 'top' );
            add_rewrite_tag( '%acpro-callback-webhook%' , '([^&]+)' );

            flush_rewrite_rules( false );
        }

        public static function wcap_fb_webhook_parse_request( &$wp ) {
            if ( array_key_exists( 'acpro-callback-webhook', $wp->query_vars ) ) {
                do_action( 'wcap_fb_messenger_callback_webhook' );
                exit();
            }
        }

        public static function wcap_include_admin() {
            require_once 'admin/wcap_fb_admin_settings.php';
            require_once 'admin/wcap_fb_templates.php';
            require_once 'admin/wcap_fb_templates_list.php';
            require_once 'admin/wcap_fb_domain_whitelisting.php';
        }

        public static function wcap_include_library() {
            require_once 'wcap_fb_webhook.php';
            require_once __DIR__ . '/../vendor/libraries/fb-messenger-php/FbBotApp.php';
            require_once __DIR__ . '/../vendor/libraries/fb-messenger-php/Messages/Message.php';
            require_once __DIR__ . '/../vendor/libraries/fb-messenger-php/Messages/MessageButton.php';
            require_once __DIR__ . '/../vendor/libraries/fb-messenger-php/Messages/StructuredMessage.php';
            require_once __DIR__ . '/../vendor/libraries/fb-messenger-php/Messages/MessageElement.php';
            require_once __DIR__ . '/../vendor/libraries/fb-messenger-php/Messages/MessageReceiptElement.php';
            require_once __DIR__ . '/../vendor/libraries/fb-messenger-php/Messages/Address.php';
            require_once __DIR__ . '/../vendor/libraries/fb-messenger-php/Messages/Summary.php';
            require_once __DIR__ . '/../vendor/libraries/fb-messenger-php/Messages/Adjustment.php';
        }

        public static function wcap_include_frontend() {
            require_once 'frontend/wcap_fb_frontend_loader.php';
            require_once 'frontend/wcap_fb_process_abandoned_records.php';
        }

        private function wcap_fb_set_details() {
            //Define Messenger App Token
            define( "WCAP_FB_PAGE_TOKEN", get_option( 'wcap_fb_page_token' ) );

            //Define Webhook Verify Token
            define( "WCAP_FB_VERIFY_TOKEN", get_option( 'wcap_fb_verify_token' ) );

            //Define Messenger App ID
            define( "WCAP_FB_APP_ID", get_option( 'wcap_fb_app_id' ) );

            //Define Page ID
            define( "WCAP_FB_PAGE_ID", get_option( 'wcap_fb_page_id' ) );
        }

        public function wcap_fb_reminder( $reminder_list ) {

            if ( get_option( 'wcap_enable_fb_reminders' ) ) {
                array_push( $reminder_list, 'fb' );
            }

            return $reminder_list;
        }

        public static function wcap_fb_cron() {

            if ( get_option( 'wcap_enable_fb_reminders' ) ) {
                // get the SMS Templates
                $fb_templates = wcap_get_notification_templates( 'fb' );
            
                if( is_array( $fb_templates ) && count( $fb_templates ) > 0 ) {
                    global $wpdb;
                    $main_prefix         = is_multisite() ? $wpdb->get_blog_prefix( 1 ) : $wpdb->prefix;
                    $current_time        = current_time( 'timestamp' );
                    $registered_cut_off  = is_numeric( get_option( 'ac_cart_abandoned_time', 10 ) ) ? get_option( 'ac_cart_abandoned_time', 10 ) * 60 : 10 * 60;
                    $guest_cut_off       = is_numeric( get_option( 'ac_cart_abandoned_time_guest', 10 ) ) ? get_option( 'ac_cart_abandoned_time_guest', 10 ) * 60 : 10 * 60;
                    $last_email_template = wcap_get_last_template_in_reminder_cycle( 'fb' );
                    if ( is_array( $last_email_template ) && count( $last_email_template ) > 0 ) {
                        reset( $last_email_template );
                        $last_template_id = key( $last_email_template );
                    } else {
                        $last_template_id = 0;
                    }
                    foreach( $fb_templates as $frequency => $template_data ) {

                        // template ID
                        $template_id = $template_data[ 'id' ];

                        $time_registered = $current_time - $frequency - $registered_cut_off;
                        $time_guest = $current_time - $frequency - $guest_cut_off;
                        // get abandoned carts
                        $carts = Wcap_Send_Email_Using_Cron::wcap_get_carts( $time_registered, $time_guest, $template_id, $main_prefix, $template_data['activated_time'], 'fb' );
                        if( is_array( $carts ) && count( $carts ) > 0 ) {
                            foreach( $carts as $cart_data ) {
                                $cart_id = $cart_data->id;
                                // Mark complete if it's the last SMS to be sent for the cart.
                                if ( (int) $template_id === (int) $last_template_id ) {
                                    WCAP_CART_HISTORY_MODEL::update( array( 'fb_reminder_status' => 'complete' ), array( 'id' => $cart_id ) );
                                }
                                // FB Reminders
                                self::wcap_send_fb_message( $cart_data, $template_data );
                            }
                        }
                    }
                }
            }
        }

        public static function wcap_send_fb_message( $cart_data, $template_data ) {

            $cart_info = json_decode( stripslashes( $cart_data->abandoned_cart_info ) );

            if ( isset( $cart_info->wcap_user_ref ) && $cart_info->wcap_user_ref != '' ) {

                global $wpdb;
                $user_ref    = $cart_info->wcap_user_ref;
                $template_id = $template_data['id'];
                $cart_id     = $cart_data->id;
                // Insert a record in the sent history table.
                $wpdb->insert( // phpcs:ignore
                    WCAP_EMAIL_SENT_HISTORY_TABLE,
                    array(
                        'template_id' => $template_id,
                        'cart_id' => $cart_id,
                        'sent_time' => current_time( 'mysql' ),
                        'sent_notification_contact' => $user_ref,
                        'notification_type' => 'fb',
                    )
                );
                $selected_language = $cart_data->language;
                if ( isset( $template_data['subject'] ) && $template_data['subject'] != '' ) {

                    $name_msg          = 'wcap_fb_' . $template_data['id'] . '_subject';
                    $trans_subject     = wcap_get_translated_texts( $name_msg, $template_data['subject'], $selected_language );
        
                    $message = new stdClass();
                    $message->text = $trans_subject;

                    self::wcap_send_fb_api( $user_ref, $message );
                }

                $checkout_url    = self::wcap_fb_get_checkout_url( $cart_data, $template_data );
                $checkout_url    = apply_filters( 'wcap_checkout_link_fb_before_encoding', $checkout_url, $cart_id, $selected_language );
                $unsubscribe_url = self::wcap_fb_get_unsubscribe_url( $cart_data->id );
                $name_msg        = 'wcap_fb_' . $template_data['id'] . '_body';
                $trans_body      = wcap_get_translated_texts( $name_msg, $template_data['body'], $selected_language );
    
                $list_message = new stdClass();

                $list_message = self::wcap_create_list_body( 
                    $cart_info, 
                    json_decode( $trans_body ), 
                    $checkout_url,
                    $unsubscribe_url );

                $response = self::wcap_send_fb_api( $user_ref, $list_message );

                if ( is_wp_error( $response ) ) {
                    if ( defined( WP_DEBUG ) && true === WP_DEBUG ) {
                        error_log($response->get_error_message());//phpcs:ignore
                    }
                } else if ( isset( $response['body'] ) ) {

                    $response_body = json_decode( $response['body'] );

                    if ( isset( $response_body->error ) ) {
                        if ( defined( WP_DEBUG ) && true === WP_DEBUG ) {
                            error_log( print_r( $response_body->error, true ) );//phpcs:ignore
                        }
                    }else {
                        do_action( 'wcap_reminder_fb_sent', $cart_id, $template_id );
                    }
                }
            }
        }

        public static function wcap_send_fb_api( $user_ref, $message ) {

            $fb_api_url = 'https://graph.facebook.com/v12.0/me/messages?access_token=' . WCAP_FB_PAGE_TOKEN;

            $body = json_decode('{
                "messaging_type": "UPDATE",
                "recipient": {
                    "user_ref": "' . $user_ref . '"
                },
                "message": {
                }
            }');

            $body->message = $message;
            $body          = wp_json_encode( $body );

            $response = wp_remote_post( 
                $fb_api_url, 
                array(
                    'method' => 'POST',
                    'blocking' => true,
                    'headers' => array( 'Content-Type' => 'application/json' ),
                    'body' => $body,
                    'cookies' => array()
                )
            );

            return $response;
        }

        public static function wcap_create_list_body( $cart_info, $template_data, $checkout_url, $unsubscribe_url ) {

            $message        = new stdClass();
            $product_object = new stdClass();
            $products_array = array();

            $message = json_decode( '{
                "attachment":{
                    "type":"template",
                    "payload":{
                        "template_type":"generic",
                        "elements": []
                    }
                }
            }');

            if ( count( get_object_vars( $cart_info->cart ) ) > 0 ) {
                foreach ( $cart_info->cart as $items ) {
                    $id = ( $items->variation_id != 0 ) ? $items->variation_id : $items->product_id;

                    $image_url = wp_get_attachment_image_src( get_post_thumbnail_id( $id ), 'single-post-thumbnail' );

                    if ( $image_url == false && $items->variation_id != 0 ) {
                        $image_url = wp_get_attachment_image_src( get_post_thumbnail_id( $items->product_id ), 'single-post-thumbnail' );
                        if ( $image_url == false ) {
                            $image_url = wc_placeholder_img_src();
                        }else {
                            $image_url = $image_url[0];
                        }
                    }elseif ( $image_url == false && $items->variation_id == 0 ) {
                        $image_url = wc_placeholder_img_src();
                    }else {
                        $image_url = $image_url[0];
                    }

                    $product_object->title = html_entity_decode( get_the_title( $id ), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 );
                    $product_object->image_url = $image_url;
                    $product_object->subtitle = $items->quantity . ' x ' . $items->line_subtotal;

                    $product_object->default_action = (object) [
                        'type' => 'web_url',
                        'url' => $checkout_url,
                        'webview_height_ratio' => "tall"
                      ];

                    $checkout = (object) [
                        'type' => 'web_url',
                        'url' => $checkout_url,
                        'title' => $template_data->checkout_text
                      ];

                    $unsubscribe_url = '' === $unsubscribe_url ? get_option( 'siteurl' ) : $unsubscribe_url;
                    $unsubscribe = (object) [
                        'type' => 'web_url',
                        'url' => $unsubscribe_url,
                        'title' => $template_data->unsubscribe_text
                      ];

                    $product_object->buttons = array( $checkout, $unsubscribe );
                    array_push( $products_array, $product_object );
                }
            }

            $message->attachment->payload->elements = array_merge(
                $message->attachment->payload->elements,
                $products_array
            );

            return $message;
        }

        public static function wcap_fb_get_checkout_url( $cart_data, $template_data ) {

            // generate the long url
            $db_id = generate_checkout_url( $cart_data, $template_data, 'fb_link' );
        
            // get the long url
            $long_url = WCAP_Tiny_Url::get_long_url_from_id( $db_id );
            // shorten it
            $short_url = WCAP_Tiny_Url::get_short_url( $long_url );
    
            // update the DB
            WCAP_Tiny_Url::update_short_url( $db_id, $short_url );
    
            // add the website url to the short url
            $short_url = get_option( 'siteurl' ) . "/$short_url";

            return $short_url;
        }

        /**
         * Returns Unsubscribe URL
         *
         * @param int $cart_id - Cart ID.
         * @return string $unsubscribe_url - Unsubscribe URL.
         * @since 8.11.0
         */
        public static function wcap_fb_get_unsubscribe_url( $cart_id ) {
            // site url.
            $site_url = get_option( 'siteurl' );
            $validate_unsubscribe = Wcap_Common::encrypt_validate( $cart_id );
            $unsubscribe_url = "$site_url/?wcap_track_unsubscribe=wcap_fb_unsubscribe&validate=$validate_unsubscribe";
            return $unsubscribe_url;
        }

        /**
         * Updates the sent count for FB messages.
         *
         * @param int $template_id - Template ID.
         * @return string $update_by - Update count by value.
         * @since 8.16.0
         */
        public static function wcap_update_fb_count( $template_id, $update_by = 1 ) {

            // Get the existing count.
            $count = wcap_get_notification_meta( $template_id, 'sent_count' );
    
            if ( ! $count ) {
                $count = 0;
            }
            // Update the count in the DB.
            $count += $update_by;
            wcap_update_notification_meta( $template_id, 'sent_count', $count );
        }
    }
}

return new WCAP_FB_Recovery();
