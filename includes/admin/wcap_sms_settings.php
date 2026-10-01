<?php
/**
 * It will display the email template listing.
 * @author   LittleCodr
 * @package Abandoned-Cart-Pro-for-WooCommerce/Admin/Settings
 * @since 7.9
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Get the PHP helper library from twilio.com/docs/php/install
if( ! class_exists( 'SplClassLoader' ) ) { 
    require_once( WCAP_PLUGIN_PATH . '/includes/vendor/libraries/twilio-php/Twilio/autoload.php' ); // Loads the library
}
use Twilio\Rest\Client;

if ( !class_exists('Wcap_SMS_settings' ) ) {
    /**
     * It will display the SMS settings for the plugin.
     * @since 7.9
     */
    class Wcap_SMS_settings{

        /**
         * Construct
         */
        public function __construct() {
            
        }

        /**
         * Sends a Test SMS
         * Called via AJAX
         * 
         * @since 7.9
         */
        static function wcap_send_test_sms() {
            
            $msg_array = '';
             
            $phone_number = ( isset( $_POST[ 'number' ] ) ) ? sanitize_text_field( $_POST[ 'number' ] ) : 0;// phpcs:disable
            
            $msg = ( isset( $_POST[ 'msg' ] ) && $_POST[ 'msg' ] != '') ? sanitize_text_field( $_POST[ 'msg' ] ) : '';
            // phpcs:enable
            if( $phone_number != '' && $msg != '' ) {
                
                // Verify the Phone number
                if( is_numeric( $phone_number ) ) {
                
                    // if first character is not a +, add it
                    if( substr( $phone_number, 0, 1 ) != '+' ) {
                        $phone_number = '+' . $phone_number;
                    }
                    
                    $sid = get_option( 'wcap_sms_account_sid' );
                    $token = get_option( 'wcap_sms_auth_token' );
                    
                    if( $sid != '' && $token != '' ) {
                        
                        try {
                            $client = new Client($sid, $token);
                            
                            $message = $client->messages->create(
                                $phone_number,
                                array(
                                    'from' => get_option( 'wcap_sms_from_phone' ),
                                    'body' => $msg,
                                )
                            );
                            
                            if( $message->sid ) {
                                $message_sid = $message->sid;
    
                                $message_details = $client->messages( $message_sid )->fetch();
                                
                                $status = $message_details->status;
                                $error_msg = $message_details->errorMessage;
                                
                                $msg_array .= __( "Message Status: $status", 'woocommerce-ac' ) . "<br/>";//phpcs:ignore
                            }
                        } catch( Exception $e ) {
                            $msg_array .= $e->getMessage() . "<br/>";;
                        } 
                    } else { // Account Information is incomplete
                        $msg_array .= __( 'Incomplete Twilio Account Details. Please provide an Account SID and Auth Token to send a test message.', 'woocommerce-ac' ) . "<br/>";;
                    }
                } else {
                    $msg_array .= __( 'Please enter the phone number in E.164 format', 'woocommerce-ac' ) . "<br/>";;
                } 
            } else { // Phone number/Msg has not been provided
                $msg_array .= __( 'Please make sure the Recipient Number and Message field are populated with valid details.', 'woocommerce-ac' ) . "<br/>";;
            }
            
            echo json_encode( $msg_array );
            die();
        }
    } // end of class
    $wcap_SMS_settings = new Wcap_SMS_settings();
}
