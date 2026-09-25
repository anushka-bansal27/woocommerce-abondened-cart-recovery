<?php
/**
 * It will capture the logged-in and visitor and guest users cart.
 * @author   Tyche Softwares
 * @package Abandoned-Cart-Pro-for-WooCommerce/Frontend/Cart-Capture
 * @since 5.0
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if ( !class_exists('Wcap_Cart_Updated' ) ) {
    /**
     * It will capture the logged-in and visitor and guest users cart.
     */
    class Wcap_Cart_Updated {

        private static $current_time;
        private static $current_user_lang;
        /**
         * It will capture the logged-in and visitor and guest users cart.
         * @hook woocommerce_cart_updated
         * @since 5.0
         */
        public static function wcap_store_cart_timestamp() {
            $block_crawlers = apply_filters( 'wcap_block_crawlers', false );

			if ( $block_crawlers ) {
				return;
			}
            if ( isset( $_POST['country'] ) && '' !== $_POST['country'] ) {//phpcs:disable
                $wcap_is_country_restricted = Wcap_Common::wcap_is_country_restricted( $_POST['country'] );
                if ( $wcap_is_country_restricted ) {
                    return;
                }
            }
            $atc_active = wcap_get_popup_active_status( 'atc' );
            
            if ( is_user_logged_in() || wcap_get_cart_session( 'wcap_abandoned_id' ) != '' ||
                wcap_get_cart_session( 'wcap_email_sent_id' ) != '' ||
                ( $atc_active && ! wcap_get_atc_email_mandatory_status() ) ||
                ! $atc_active ) {

            // @TODO manage in populate cart file
            /*if ( isset( $_SESSION['email_sent_id'] ) && $_SESSION['email_sent_id'] != '' && WC()->session->get( 'email_sent_id' ) == '' ) {
              WC()->session->set( 'email_sent_id' , $_SESSION['email_sent_id'] );
            }*/

            if ( get_transient( 'wcap_selected_language' ) !== false ) {
              wcap_set_cart_session( 'wcap_selected_language', get_transient( 'wcap_selected_language' ) );
              delete_transient( 'wcap_selected_language' );
            }
            if ( get_transient( 'wcap_c' ) !== false ) {
              wcap_set_cart_session( 'wcap_c', get_transient( 'wcap_c' ) );
              delete_transient( 'wcap_c' );
            }
            if ( get_transient( 'wcap_email_sent_id' ) !== false ) {
              wcap_set_cart_session( 'wcap_email_sent_id', get_transient( 'wcap_email_sent_id' ) );
              delete_transient( 'wcap_email_sent_id' );
            }

            self::$current_time       = current_time( 'timestamp' );
            self::$current_user_lang  = Wcap_Common::wcap_get_language();
            $user_id                  = get_current_user_id();
            $wcap_is_user_restricted  = false;
            $wcap_get_is_user_blocked = array();
            $wcap_get_is_user_blocked = get_user_meta( $user_id, 'wcap_restrict_user' );
            
            if ( isset( $wcap_get_is_user_blocked[0] ) && count( $wcap_get_is_user_blocked ) > 0 && "on" == $wcap_get_is_user_blocked[0] ) {
                $wcap_is_user_restricted = true;
            }

            $enable_tracking = get_option( 'wcap_enable_tracking', '' );
            if ( is_user_logged_in() ) {
                if ( false == $wcap_is_user_restricted ) {
                    Wcap_Cart_Updated::wcap_capture_logged_in_cart( $user_id );
                }
            } else {
                Wcap_Cart_Updated::wcap_capture_guest_and_visitor_cart();
            }
          }
        }

        /**
		 * Detect Crawlers
		 *
		 * @param boolean $ignore - Ignore.
		 * @return boolean $ignore - Ignore.
		 */
		public static function wcap_detect_crawlers( $ignore ) {
			$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

			if ( '' === $user_agent ) {
				return $ignore;
			}

			// Current list of bots being blocked:
			// 1. Googlebot, BingBot, DuckDuckBot, YandexBot, Exabot.
			// 2. cURL.
			// 3. wget.
			// 4. Yahoo/Slurp.
			// 5. Baiduspider.
			// 6. Sogou.
			// 7. Alexa.
			$bot_agents = array(
				'curl',
				'wget',
				'bot',
				'bots',
				'slurp',
				'baiduspider',
				'sogou',
				'ia_archiver',
			);

			foreach ( $bot_agents as $url ) {
				if ( false !== stripos( $user_agent, $url ) ) {
					return true;
				}
			}

			return $ignore;
		}

        /**
         * It will capture the logged in users cart.
         * @param int | string $user_id User Id
         * @globals mixed $wpdb
         * @since 5.0
         */
        public static function wcap_capture_logged_in_cart( $user_id ) {

			if ( '' !== wcap_get_cart_session( 'wcap_user_id' ) &&
				 '' !== wcap_get_cart_session( 'wcap_guest_email' ) &&
				 null !== wcap_get_cart_session( 'wcap_abandoned_id' ) ) {
				return;
			}

            global $wpdb;
            $disable_logged_in_cart = get_option( 'ac_disable_logged_in_cart_email' );
            $cut_off                = is_numeric( get_option( 'ac_cart_abandoned_time', 10 ) ) ? get_option( 'ac_cart_abandoned_time', 10 ) : 10;
            $cart_cut_off_time      = intval( $cut_off ) * 60;
            $compare_time           = self::$current_time - $cart_cut_off_time;
            $logged_in_cart         = "";
            if ( isset( $disable_logged_in_cart ) ) {
                $logged_in_cart = $disable_logged_in_cart;
            }

            $loggedin_user_ip_address = Wcap_Common::wcap_get_client_ip();
            $user_email_biiling       = get_user_meta( $user_id, 'billing_email', true );
            $current_user_email       = '';
            if (  "" == $user_email_biiling && isset( $user_email_biiling )  ) {
                $current_user_data   = get_userdata( $user_id );
                $current_user_email  = $current_user_data->user_email;
            } else {
                $current_user_email  = $user_email_biiling;
            }

            $wcap_is_ip_restricted            = Wcap_Common::wcap_is_ip_restricted            ( $loggedin_user_ip_address );
            $wcap_is_email_address_restricted = Wcap_Common::wcap_is_email_address_restricted ( $current_user_email );
            $wcap_is_domain_restricted        = Wcap_Common::wcap_is_domain_restricted        ( $current_user_email );

            if ( $logged_in_cart != "on" && ( false == $wcap_is_ip_restricted && false == $wcap_is_email_address_restricted && false == $wcap_is_domain_restricted ) ) {

                $query   = "SELECT * FROM `" . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE user_id = %d AND cart_ignored IN ('0','2') AND recovered_cart = '0' ";
                $results = $wpdb->get_results( $wpdb->prepare( $query, $user_id ) );
                if ( count( $results ) == 0 ) {
                    Wcap_Cart_Updated::wcap_insert_new_entry_of_loggedin_user( $user_id, self::$current_user_lang, $loggedin_user_ip_address, $results );
                } elseif ( $compare_time > $results[0]->abandoned_cart_time ) {
                    Wcap_Cart_Updated::wcap_capture_cart_after_cutoff_loggedin_user ( $user_id, $results, self::$current_user_lang, $loggedin_user_ip_address );
                } else {
                    Wcap_Cart_Updated::wcap_cart_capture_under_cart_cutoff_loggedin( $user_id, $results, self::$current_user_lang, $loggedin_user_ip_address );
				}

				if ( '' !== wcap_get_cart_session( 'wcap_guest_email' ) ) {
					wcap_set_cart_session( 'wcap_guest_email', '' );
				}
            }
        }
        /**
         * It will insert the new logged-in users cart into the database.
         * It will insert the data immediatly after user add the product to the cart.
         * If user has recovered last cart then it will create the new record for the user.
         * @param int | string $user_id User Id
         * @param string $current_user_lang User Selected language
         * @param string $loggedin_user_ip_address Ip address of the user
         * @param array $results Old record of user
         * @globals mixed $wpdb
         * @globals mixed $woocommerce
         * @since 5.0
         * 
         * @since 7.7 WCAP_CART_HISTORY_MODEL::insert() function used to insert data
         */
        public static function wcap_insert_new_entry_of_loggedin_user( $user_id, $current_user_lang, $loggedin_user_ip_address, $results ) {

          global $wpdb, $woocommerce;
          //$wcal_woocommerce_persistent_cart = version_compare( $woocommerce->version, '3.1.0', ">=" ) ? '_woocommerce_persistent_cart_' . get_current_blog_id() : '_woocommerce_persistent_cart' ;
          //$updated_cart_info = json_encode( get_user_meta( $user_id, $wcal_woocommerce_persistent_cart , true ) );

			$updated_cart_info                = array();
			$updated_cart_info['cart']        = WC()->session->cart;
			$updated_cart_info['cart_totals'] = WC()->session->cart_totals;

          // capturing shipping charges
          $updated_cart_info = self::wcap_add_shipping_charges($updated_cart_info);

          $cart_info         = json_encode( $updated_cart_info );
          $cart_info         = str_replace( ' \"%title%\" ', '', $cart_info );
          $cart_info         = wp_strip_all_tags( $cart_info );
			if ( function_exists( 'icl_object_id' ) ) {
				$cart_info = WCAP_CART_HISTORY_MODEL::add_wcml_currency( $cart_info );
			}
			if ( defined( 'WOOCOMMERCE_MULTICURRENCY_VERSION' ) ) {
				$cart_info = WCAP_CART_HISTORY_MODEL::add_wc_multicurrency( $cart_info );
			}
            if ( class_exists( 'WOOCS' ) ) {
                $cart_info = WCAP_CART_HISTORY_MODEL::add_woocs_currency( $cart_info );
            }

          $blank_cart_info = '{"cart":[]}';
           if ( $blank_cart_info != $updated_cart_info &&  '""' != $cart_info && '""' != $updated_cart_info ) {
              
              $wcap_query   = "SELECT * FROM `" . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE user_id = %d AND cart_ignored = '1' AND recovered_cart = '0' ORDER BY id DESC LIMIT 1 ";
              $wcap_results = $wpdb->get_results( $wpdb->prepare( $wcap_query, $user_id ) );
                if ( count( $wcap_results ) > 0  ) {

                    $wcap_is_cart_updated = Wcap_Cart_Updated::wcap_compare_all_users_carts( $cart_info, $wcap_results[0]->abandoned_cart_info );

                    if ( ( $wcap_is_cart_updated != '' && '""' != $updated_cart_info ) || ( true === $wcap_is_cart_updated && '""' != $updated_cart_info ) ) {

                        $data = array(
                        'user_id'               => $user_id,
                        'abandoned_cart_info'   => $cart_info,
                        'abandoned_cart_time'   => self::$current_time,
                        'cart_ignored'          => 0,
                        'recovered_cart'        => 0,
                        'user_type'             => 'REGISTERED',
                        'language'              => $current_user_lang,
                        'session_id'            => '',
                        'ip_address'            => $loggedin_user_ip_address,
                        'email_reminder_status' => '',
                        'wcap_trash'            => '',
                        'unsubscribe_link'           => ''                        
                        );
                        
                        $abandoned_cart_id = WCAP_CART_HISTORY_MODEL::insert( $data );

                        wcap_set_cart_session( 'wcap_abandoned_id', $abandoned_cart_id );
                    }

                } else if ( count( $results) == 0 ) {

                    $data = array(
                        'user_id'               => $user_id,
                        'abandoned_cart_info'   => $cart_info,
                        'abandoned_cart_time'   => self::$current_time,
                        'cart_ignored'          => 0,
                        'recovered_cart'        => 0,
                        'user_type'             => 'REGISTERED',
                        'language'              => $current_user_lang,
                        'session_id'            => '',
                        'ip_address'            => $loggedin_user_ip_address,
                        'email_reminder_status' => '',
                        'wcap_trash'            => '',
                        'unsubscribe_link'           => ''                        
                        );
                    $abandoned_cart_id = WCAP_CART_HISTORY_MODEL::insert( $data );

                    wcap_set_cart_session( 'wcap_abandoned_id', $abandoned_cart_id );
                }

                if ( wcap_get_cart_session( 'wcap_abandoned_id' ) != '' ) {
                    $abandoned_cart_id_hook = wcap_get_cart_session( 'wcap_abandoned_id' );
                	do_action ('acfac_add_data', $abandoned_cart_id_hook );
                	Wcap_Common::wcap_add_checkout_link( $abandoned_cart_id_hook );
                	Wcap_Common::wcap_run_webhook_after_cutoff( $abandoned_cart_id_hook );

                }
            }else if ( $blank_cart_info == $updated_cart_info && '""' == $updated_cart_info ){
                
                WCAP_CART_HISTORY_MODEL::update( array( 'cart_ignored' => 1 ), array( 'user_id' => $user_id ) );
            }
        }
        /**
         * It will capture the logged-in users cart after the cutoff time has been passed.
         * It will update the old cart of the user and insert new entry in database.
         * @param int | string $user_id User Id
         * @param string $current_user_lang User Selected language
         * @param string $loggedin_user_ip_address Ip address of the user
         * @param array $results Old record of user
         * @globals mixed $wpdb
         * @globals mixed $woocommerce
         * @since 5.0
         * 
         * @since 7.7 WCAP_CART_HISTORY_MODEL::insert() function used to insert data
         */
        public static function wcap_capture_cart_after_cutoff_loggedin_user( $user_id, $results, $current_user_lang, $loggedin_user_ip_address ){

            global $wpdb, $woocommerce;

            //$wcal_woocommerce_persistent_cart =version_compare( $woocommerce->version, '3.1.0', ">=" ) ? '_woocommerce_persistent_cart_' . get_current_blog_id() : '_woocommerce_persistent_cart' ;

            //$cart_data = get_user_meta( $user_id, $wcal_woocommerce_persistent_cart , true );

			$cart_data                = array();
			$cart_data['cart']        = WC()->session->cart;
			$cart_data['cart_totals'] = WC()->session->cart_totals;

            // adding shipping charges.
            $cart_data = self::wcap_add_shipping_charges($cart_data);

            $updated_cart_info = json_encode( $cart_data );
            $updated_cart_info = str_replace( ' \"%title%\" ', '', $updated_cart_info );
			if ( function_exists( 'icl_object_id' ) ) {
				$updated_cart_info = WCAP_CART_HISTORY_MODEL::add_wcml_currency( $updated_cart_info );
			}
			if ( defined( 'WOOCOMMERCE_MULTICURRENCY_VERSION' ) ) {
				$updated_cart_info = WCAP_CART_HISTORY_MODEL::add_wc_multicurrency( $updated_cart_info );
			}
            if ( class_exists( 'WOOCS' ) ) {
                $updated_cart_info = WCAP_CART_HISTORY_MODEL::add_woocs_currency( $updated_cart_info );
            }
            $blank_cart_info   = array( '{"cart":[],"shipping_charges":0}', '{"cart":[]}' );

            if ( /*( $results[0]->language == $current_user_lang || $results[0]->language == '' ) &&*/ ! in_array( $updated_cart_info, $blank_cart_info ) ) {

                $shipping_charge_changes = Wcap_Cart_Updated::wcap_check_shipping_charges( $updated_cart_info, $results[0]->abandoned_cart_info );
              
                if ( Wcap_Cart_Updated::wcap_compare_all_users_carts( $updated_cart_info, $results[0]->abandoned_cart_info ) && '""' !== $updated_cart_info  ) {

                    if( wcap_get_cart_session( 'wcap_email_sent_id' ) != '' ) {
						
                     WCAP_CART_HISTORY_MODEL::update( array( 'abandoned_cart_info' => $updated_cart_info ), array( 'user_id' => $user_id, 'cart_ignored' => 0 ) );
					 
                    }else {
                    
					WCAP_CART_HISTORY_MODEL::update( array( 'cart_ignored' => 1 ), array( 'user_id' => $user_id ) ) ;
                    
                    $data = array(
                        'user_id'               => $user_id,
                        'abandoned_cart_info'   => $updated_cart_info,
                        'abandoned_cart_time'   => self::$current_time,
                        'cart_ignored'          => 0,
                        'recovered_cart'        => 0,
                        'user_type'             => 'REGISTERED',
                        'language'              => $current_user_lang,
                        'session_id'            => '',
                        'ip_address'            => $loggedin_user_ip_address,
                        'email_reminder_status' => '',
                        'wcap_trash'            => '',
                        'unsubscribe_link'           => ''                        
                        );
                    $insert_id = WCAP_CART_HISTORY_MODEL::insert( $data );

                      wcap_set_cart_session( 'wcap_abandoned_id', $insert_id );

                      if ( '' !== $insert_id ) {
                        do_action ('acfac_add_data', $insert_id );
                        Wcap_Common::wcap_add_checkout_link( $insert_id );
                        Wcap_Common::wcap_run_webhook_after_cutoff( $insert_id );
                      }
                    }
                } else if( $shipping_charge_changes ) {

                    if ( function_exists( 'icl_object_id' ) ) {
                      $updated_cart_info = WCAP_CART_HISTORY_MODEL::add_wcml_currency( $updated_cart_info );
                    }
                    if ( defined ( 'WOOCOMMERCE_MULTICURRENCY_VERSION' ) ) {
                        $updated_cart_info = WCAP_CART_HISTORY_MODEL::add_wc_multicurrency( $updated_cart_info );
                    }
                    if ( class_exists( 'WOOCS' ) ) {
                        $updated_cart_info = WCAP_CART_HISTORY_MODEL::add_woocs_currency( $updated_cart_info );
                    }
                    
                    $update_data = array( 'abandoned_cart_info' => $updated_cart_info,
                                          'language'            => $current_user_lang,
                                          'ip_address'          => $loggedin_user_ip_address,
                    );
                    
                    WCAP_CART_HISTORY_MODEL::update( $update_data, array( 'id' => $results[0]->id ) );

                    wcap_set_cart_session( 'wcap_abandoned_id', $results[0]->id );
                    if ( '' !== $results[0]->id ) {
                        do_action ('acfac_add_data', $results[0]->id );
                    }
                }
            }else if ( in_array( $updated_cart_info, $blank_cart_info ) && isset( $results[0]->id ) ) {
                $email_sent_id = wcap_get_cart_session( 'wcap_email_sent_id' );
                if ( false === $email_sent_id || is_null( $email_sent_id ) ) { // delete the record if the user has not come in via a reminder link
                  WCAP_CART_HISTORY_MODEL::delete( array( 'user_id' => $user_id, 'id' => $results[0]->id ) );
                } else if( $email_sent_id > 0 ) {
                	// we want to retain the old record in a scenario where the user has come in via a link so statistics are correct.
                						
					WCAP_CART_HISTORY_MODEL::update( array( 'abandoned_cart_info' => $updated_cart_info ), array( 'user_id' => $user_id, 'cart_ignored' => 0 ) ) ;
				               
                }
            }
        }
        /**
         * It will update the logged-in users cart under the cutoff time.
         * @param int | string $user_id User Id
         * @param string $current_user_lang User Selected language
         * @param string $loggedin_user_ip_address Ip address of the user
         * @param array $results Old record of user
         * @globals mixed $wpdb
         * @globals mixed $woocommerce
         * @since 5.0
         */
        public static function wcap_cart_capture_under_cart_cutoff_loggedin ( $user_id, $results, $current_user_lang, $loggedin_user_ip_address ){

            global $wpdb, $woocommerce;
            $blank_cart_info   = array( '{"cart":[],"shipping_charges":0}', '{"cart":[]}' );

            //$wcal_woocommerce_persistent_cart = version_compare( $woocommerce->version, '3.1.0', ">=" ) ? '_woocommerce_persistent_cart_' . get_current_blog_id() : '_woocommerce_persistent_cart' ;
            
            //$cart_data = get_user_meta( $user_id, $wcal_woocommerce_persistent_cart , true );

			$cart_data                = array();
			$cart_data['cart']        = WC()->session->cart;
			$cart_data['cart_totals'] = WC()->session->cart_totals;

            //adding shipping charges
            $cart_data = self::wcap_add_shipping_charges($cart_data);

            $wc_shipping_charges = WC()->cart->get_shipping_total();
            // Extract the shipping amount
            $wc_shipping_charges = strip_tags( html_entity_decode( $wc_shipping_charges ) );
            $wc_shipping_charges = (float) filter_var( $wc_shipping_charges, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
            
            $cart_data[ 'shipping_charges' ] = $wc_shipping_charges;
            
            $updated_cart_info = json_encode( $cart_data );
            $updated_cart_info = str_replace( ' \"%title%\" ', '', $updated_cart_info );

            if ( ( $results[0]->language == '' || $results[0]->language == $current_user_lang ) && ! in_array( $updated_cart_info, $blank_cart_info ) ) {
              
                $shipping_charge_changes = Wcap_Cart_Updated::wcap_check_shipping_charges( $updated_cart_info, $results[0]->abandoned_cart_info );

                if ( function_exists( 'icl_object_id' ) ) {
                  $updated_cart_info = WCAP_CART_HISTORY_MODEL::add_wcml_currency( $updated_cart_info );
                }
                if ( defined ( 'WOOCOMMERCE_MULTICURRENCY_VERSION' ) ) {
                    $updated_cart_info = WCAP_CART_HISTORY_MODEL::add_wc_multicurrency( $updated_cart_info );
                }
                if ( class_exists( 'WOOCS' ) ) {
                    $updated_cart_info = WCAP_CART_HISTORY_MODEL::add_woocs_currency( $updated_cart_info );
                }

                if ( ( Wcap_Cart_Updated::wcap_compare_all_users_carts( $updated_cart_info, $results[0]->abandoned_cart_info ) && '""' !== $updated_cart_info ) || $shipping_charge_changes ) {

				
					WCAP_CART_HISTORY_MODEL::update( array( 'abandoned_cart_info' => $updated_cart_info, 'abandoned_cart_time' => self::$current_time, 'language' => $current_user_lang, 'ip_address' => $loggedin_user_ip_address ), array( 'id' => $results[0]->id ) );

                    wcap_set_cart_session( 'wcap_abandoned_id', $results[0]->id );
                    if ( '' !== $results[0]->id ){
                        do_action ('acfac_add_data', $results[0]->id );
                    }
                }
            }else if ( in_array( $updated_cart_info, $blank_cart_info ) && isset( $results[0]->id ) ) {
                WCAP_CART_HISTORY_MODEL::delete( array( 'user_id' => $user_id, 'id' => $results[0]->id ) );
            }
        }
        /**
         * It will captures the visitors and guest cart.
         * @globals mixed $wpdb
         * @globals mixed $woocommerce
         * @since 5.0
         */
        public static function wcap_capture_guest_and_visitor_cart(){

            global $wpdb, $woocommerce;

            $disable_guest_cart                 = get_option( 'ac_disable_guest_cart_email' );

            $track_guest_cart_from_cart_page    = get_option( 'ac_track_guest_cart_from_cart_page' );
            $cut_off                            = get_option( 'ac_cart_abandoned_time_guest' );
            $cart_cut_off_time                  = intval( $cut_off ) * 60;
            $compare_time                       = self::$current_time - $cart_cut_off_time;
            $guest_cart                         = "";
            if ( isset( $disable_guest_cart ) ) {
                $guest_cart = $disable_guest_cart;
            }
            $guest_user_ip_address   = Wcap_Common::wcap_get_client_ip();

            $user_id = wcap_get_cart_session( 'wcap_user_id' );

            $cart    = array();
            $results = array();
          
            if ( $user_id > 0 ){
                $query   = "SELECT * FROM `" . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE user_id = %d AND cart_ignored IN ('0','2') AND recovered_cart = '0' AND user_id != '0'";
                $results = $wpdb->get_results( $wpdb->prepare( $query, $user_id ) );
            }

            if ( function_exists('WC') ) {
				$cart['cart']        = WC()->session->cart;
				$cart['cart_totals'] = WC()->session->cart_totals;
            } else {
                $cart['cart'] = $woocommerce->session->cart;
            }

            //adding shipping charges
            $cart = self::wcap_add_shipping_charges($cart);

            $updated_cart_info = json_encode( $cart );
            $updated_cart_info = str_replace( ' \"%title%\" ', '', $updated_cart_info );
            $updated_cart_info = wp_strip_all_tags( $updated_cart_info );
			if ( function_exists( 'icl_object_id' ) ) {
				$updated_cart_info = WCAP_CART_HISTORY_MODEL::add_wcml_currency( $updated_cart_info );
			}
			if ( defined( 'WOOCOMMERCE_MULTICURRENCY_VERSION' ) ) {
				$updated_cart_info = WCAP_CART_HISTORY_MODEL::add_wc_multicurrency( $updated_cart_info );
			}
            if ( class_exists( 'WOOCS' ) ) {
                $updated_cart_info = WCAP_CART_HISTORY_MODEL::add_woocs_currency( $updated_cart_info );
            }

            $guest_blank_cart_info  = '[]';
            if ( count($results) > 0 ) {
                if ( $guest_cart != "on" ) {
					$existing_cart_info = json_decode( $results[0]->abandoned_cart_info );
                    $updated_cart_info_dec = json_decode( $updated_cart_info );
                    $updated_cart_info_dec->captured_by = isset( $existing_cart_info->captured_by ) ? $existing_cart_info->captured_by : '';
                    $updated_cart_info = json_encode( $updated_cart_info_dec );
                    if ( $compare_time > $results[0]->abandoned_cart_time ) {
                        if (  '' != $updated_cart_info &&
                            $updated_cart_info != $guest_blank_cart_info &&
                            Wcap_Cart_Updated::wcap_compare_all_users_carts( $updated_cart_info, $results[0]->abandoned_cart_info ) ) {

                            Wcap_Cart_Updated::wcap_update_guest_cart_after_cutoff_time( $user_id, $updated_cart_info, self::$current_user_lang, $guest_user_ip_address );
                        }
                    } else {
                        if (  '' != $updated_cart_info &&
                            $updated_cart_info != $guest_blank_cart_info &&
                                Wcap_Cart_Updated::wcap_compare_all_users_carts( $updated_cart_info, $results[0]->abandoned_cart_info ) ) {

                                Wcap_Cart_Updated::wcap_update_guest_cart_within_cutoff_time( $user_id, $updated_cart_info, $guest_user_ip_address, $results[0]->id );
                        }
                    }
                }
            } else {

                /***
                 * @Since: 2.7
                 * Here we capture the guest cart from the cart page.
                 */
                $wcap_is_ip_restricted = Wcap_Common::wcap_is_ip_restricted( $guest_user_ip_address );
                if ( isset( $disable_guest_cart ) ) {
                    $guest_cart = $disable_guest_cart;
                }

                $track_guest_user_cart_from_cart     = "";
                if ( isset( $track_guest_cart_from_cart_page ) ) {
                    $track_guest_user_cart_from_cart = $track_guest_cart_from_cart_page;
                }

                $wcap_guest_cart_key = Wcap_Common::wcap_get_guest_session_key();

                $atc_active = wcap_get_popup_active_status('atc');
                (string) $user_email = wcap_get_cart_session( 'wcap_guest_email' );
                $cart_id             = wcap_get_cart_session( 'wcap_abandoned_id' );

                if ( ! $cart_id && '' != $user_email && $user_id >= 63000000 ) { // This happens when the old cart is being marked as ignored and user is still in the same session.
                    // Insert a new record.
                    Wcap_Cart_Updated::wcap_insert_new_guest_cart( $updated_cart_info, self::$current_user_lang, $guest_user_ip_address, $wcap_guest_cart_key );
                } else if ( $wcap_guest_cart_key !== '' &&
                    $track_guest_user_cart_from_cart == "on" &&
                    false == $wcap_is_ip_restricted && 
                    ( wcap_get_cart_session( 'wcap_abandoned_id' ) != '' ||
                    wcap_get_cart_session( 'wcap_email_sent_id' ) != '' || 
                    ! $atc_active ) ) {
                        Wcap_Cart_Updated::wcap_capture_visitors_cart( $compare_time, $updated_cart_info, self::$current_user_lang, $guest_user_ip_address, $wcap_guest_cart_key );
                }
            }
        }

		/**
		 * Insert a new guest record if the old one is marked as ignored.
		 *
		 * @param string $updated_cart_info - Cart Info JSON encoded.
		 * @param string $current_user_lang - User Language.
		 * @param string $guest_user_ip_address - IP Address.
		 * @param string $wcap_guest_cart_key - WC Session Key.
		 * @since 8.14.0
		 */
		public static function wcap_insert_new_guest_cart( $updated_cart_info, $current_user_lang, $guest_user_ip_address, $wcap_guest_cart_key ) {
            (string) $user_email = wcap_get_cart_session( 'wcap_guest_email' );
            $cart_id             = wcap_get_cart_session( 'wcap_abandoned_id' );
            (int) $user_id       = wcap_get_cart_session( 'wcap_user_id' );
            
			global $wpdb;

			$abandoned_id = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT ID FROM ' . WCAP_ABANDONED_CART_HISTORY_TABLE . ' WHERE user_id = %d AND cart_ignored = %s AND recovered_cart = %s ORDER BY id DESC LIMIT 1',
					$user_id,
					'1',
					'0'
				)
			);

			if ( $abandoned_id > 0 ) {
                $data = array(
                    'user_id'               => $user_id,
                    'abandoned_cart_info'   => $updated_cart_info,
                    'abandoned_cart_time'   => self::$current_time,
                    'cart_ignored'          => 0,
                    'recovered_cart'        => 0,
                    'user_type'             => 'GUEST',
                    'language'              => $current_user_lang,
                    'session_id'            => $wcap_guest_cart_key,
                    'ip_address'            => $guest_user_ip_address,
                    'email_reminder_status' => '',
                    'wcap_trash'            => '',
                    'unsubscribe_link'           => ''                        
                    );

				$insert_id = WCAP_CART_HISTORY_MODEL::insert( $data );
				wcap_set_cart_session( 'wcap_abandoned_id', $insert_id );
			}
        }

        public static function wcap_update_session_id() {
            if ( null === wcap_get_cart_session( 'wcap_update_visitor_cart' ) && 
                null !== wcap_get_cart_session( 'wcap_abandoned_id' ) && 
                0 !== Wcap_Common::wcap_get_guest_session_key() ) {
                global $wpdb;

                $wpdb->update(
					WCAP_ABANDONED_CART_HISTORY_TABLE,
					array( 
						'session_id' => esc_attr( Wcap_Common::wcap_get_guest_session_key() ),
					),
					array( 'id' => esc_attr( wcap_get_cart_session( 'wcap_abandoned_id' ) ) )
				);
                wcap_set_cart_session( 'wcap_update_visitor_cart', 'updated' );
            }
        }

        /**
         * It will captures the visitors cart from the cart page.
         * @param timestamp $compare_time Time after cutoff time passed
         * @param json_encode $updated_cart_info Updated cart of the visitor
         * @param string $current_user_lang User Selected language
         * @param string $visitor_user_ip_address Ip address of user
         * @param string $wcap_guest_cart_key WooCommerce guest session key
         * @globals mixed $wpdb
         * @since 5.0
         */
        public static function wcap_capture_visitors_cart ( $compare_time, $updated_cart_info, $current_user_lang, $visitor_user_ip_address, $wcap_guest_cart_key ) {

            global $wpdb;

            $query     = "SELECT * FROM `" . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE session_id LIKE %s AND cart_ignored = '0' AND recovered_cart = '0' ";
            $results   = $wpdb->get_results( $wpdb->prepare( $query, $wcap_guest_cart_key ) );

            $cart_info = $updated_cart_info;
            if ( count( $results ) == 0 ) {
                Wcap_Cart_Updated::wcap_capture_new_visitor_cart ( $cart_info, $current_user_lang, $visitor_user_ip_address, $wcap_guest_cart_key );
            } elseif ( $compare_time > $results[0]->abandoned_cart_time ) {
                Wcap_Cart_Updated::wcap_capture_after_cutofftime_visitor_cart ( $results, $current_user_lang, $visitor_user_ip_address, $updated_cart_info, $wcap_guest_cart_key );
            } else {
                Wcap_Cart_Updated::wcap_capture_within_cutofftime_visitor_cart ( $results, $current_user_lang, $visitor_user_ip_address, $updated_cart_info, $wcap_guest_cart_key );
            }
        }

        /**
         * It will insert the visitors cart to the database.
         * @param json_encode $cart_info Updated cart of the visitor
         * @param string $current_user_lang User Selected language
         * @param string $visitor_user_ip_address Ip address of user
         * @param string $wcap_guest_cart_key WooCommerce guest session key
         * @globals mixed $wpdb
         * @since 5.0
         * 
         * @since 7.7 WCAP_CART_HISTORY_MODEL::insert() function used to insert data
         */
        public static function wcap_capture_new_visitor_cart ( $cart_info, $current_user_lang, $visitor_user_ip_address, $wcap_guest_cart_key ){

            global $wpdb;
            $blank_cart_info  = '[]';
            if ( '' != $cart_info && $blank_cart_info != $cart_info ) {

                $user_id = wcap_get_cart_session( 'wcap_user_id' )!=''?wcap_get_cart_session( 'wcap_user_id' ):0;
                
                $data = array(
                    'user_id'               => $user_id,
                    'abandoned_cart_info'   => $cart_info,
                    'abandoned_cart_time'   => self::$current_time,
                    'cart_ignored'          => 0,
                    'recovered_cart'        => 0,
                    'user_type'             => 'GUEST',
                    'language'              => $current_user_lang,
                    'session_id'            => $wcap_guest_cart_key,
                    'ip_address'            => $visitor_user_ip_address,
                    'email_reminder_status' => '',
                    'wcap_trash'            => '',
                    'unsubscribe_link'           => ''                        
                    );

                $insert_id = WCAP_CART_HISTORY_MODEL::insert( $data );

                if( $user_id > 0 ) {
                    Wcap_Common::wcap_add_checkout_link( $insert_id );
                }

                wcap_set_cart_session( 'wcap_abandoned_id', $insert_id );

                if ( $insert_id != '' ) {

                    $should_trigger = apply_filters( 'wcap_run_manually_webhook_initiated', false );
                    if ( $should_trigger ) {
                        do_action( 'wcap_webhook_initiated', $insert_id );
                    }
                    do_action ('acfac_add_data', $insert_id );
                }
            }
        }
        /**
         * It will capture the visitors cart after cutoff time has been reached.
         * It will update the old cart and insert the new data in the database.
         * @param json_encode $updated_cart_info Updated cart of the visitor
         * @param string $current_user_lang User Selected language
         * @param string $visitor_user_ip_address Ip address of user
         * @param string $wcap_guest_cart_key WooCommerce guest session key
         * @param array $results Old record of the user
         * @globals mixed $wpdb
         * @since 5.0
         * 
         * @since 7.7 WCAP_CART_HISTORY_MODEL::insert() function used to insert data
         */
        public static function wcap_capture_after_cutofftime_visitor_cart ( $results, $current_user_lang, $visitor_user_ip_address, $updated_cart_info, $wcap_guest_cart_key ) {

            global $wpdb;
            $blank_cart_info  = '[]';

            if (  '' != $updated_cart_info  &&
                ( $results[0]->language == $current_user_lang || $results[0]->language == '' ) &&
                $blank_cart_info != $updated_cart_info ) {

                    if ( Wcap_Cart_Updated::wcap_compare_all_users_carts( $updated_cart_info, $results[0]->abandoned_cart_info ) ) {

					
						WCAP_CART_HISTORY_MODEL::update( array( 'cart_ignored' => 1, 'ip_address'=> $visitor_user_ip_address ), array( 'session_id' => $wcap_guest_cart_key ) ) ;

                        
                        $data = array(
                            'user_id'               => '',
                            'abandoned_cart_info'   => $updated_cart_info,
                            'abandoned_cart_time'   => self::$current_time,
                            'cart_ignored'          => 0,
                            'recovered_cart'        => 0,
                            'user_type'             => 'GUEST',
                            'language'              => $current_user_lang,
                            'session_id'            => $wcap_guest_cart_key,
                            'ip_address'            => $visitor_user_ip_address,
                            'email_reminder_status' => '',
                            'wcap_trash'            => '',
                            'unsubscribe_link'           => ''                        
                            );

                        $insert_id = WCAP_CART_HISTORY_MODEL::insert( $data );

                        wcap_set_cart_session( 'wcap_abandoned_id', $insert_id );

                        if ( '' !== $insert_id ) {
                            do_action ('acfac_add_data', $insert_id );
                        }
                    }
            }
        }
        /**
         * It will capture the visitors cart within cutoff time and it will update the cart in the database.
         * @param json_encode $updated_cart_info Updated cart of the visitor
         * @param string $current_user_lang User Selected language
         * @param string $visitor_user_ip_address Ip address of user
         * @param string $wcap_guest_cart_key WooCommerce guest session key
         * @param array $results Old record of the user
         * @globals mixed $wpdb
         * @since 5.0
         */
        public static function wcap_capture_within_cutofftime_visitor_cart ( $results, $current_user_lang, $visitor_user_ip_address, $updated_cart_info, $wcap_guest_cart_key ) {
            global $wpdb;
            $blank_cart_info = '[]';

            if ( '' != $updated_cart_info &&
               ( $results[0]->language == $current_user_lang ||  $results[0]->language == '' ) &&
               $blank_cart_info != $updated_cart_info ) {

                if ( Wcap_Cart_Updated::wcap_compare_all_users_carts( $updated_cart_info, $results[0]->abandoned_cart_info ) ) {

                    if ( function_exists( 'icl_object_id' ) ) {
                      $updated_cart_info = WCAP_CART_HISTORY_MODEL::add_wcml_currency( $updated_cart_info );
                    }
                    if ( defined ( 'WOOCOMMERCE_MULTICURRENCY_VERSION' ) ) {
                        $updated_cart_info = WCAP_CART_HISTORY_MODEL::add_wc_multicurrency( $updated_cart_info );
                    }
                    if ( class_exists( 'WOOCS' ) ) {
                        $updated_cart_info = WCAP_CART_HISTORY_MODEL::add_woocs_currency( $updated_cart_info );
                    }

			
					WCAP_CART_HISTORY_MODEL::update( array( 'abandoned_cart_info' => $updated_cart_info, 'abandoned_cart_time' => self::$current_time, 'language' => $current_user_lang, 'ip_address' =>$visitor_user_ip_address   ), array( 'session_id' => $wcap_guest_cart_key, 'cart_ignored' => 0 ) ) ;

                    if ( '' !== $results[0]->id ) {
                        do_action ('acfac_add_data', $results[0]->id );
                    }
                }
            }
        }

        /**
         * It will capture the guest cart after cutoff time has been reached.
         * It will update the old cart and insert the new data in the database.
         * @param int | string $user_id User id
         * @param json_encode $updated_cart_info Updated cart of the visitor
         * @param string $current_user_lang User Selected language
         * @param string $guest_user_ip_address Ip address of user
         * @globals mixed $wpdb
         * @since 5.0
         * 
         * @since 7.7 WCAP_CART_HISTORY_MODEL::insert() function used to insert data
         */
        public static function wcap_update_guest_cart_after_cutoff_time ( $user_id, $updated_cart_info, $current_user_lang, $guest_user_ip_address ) {

            global $wpdb;

            if( wcap_get_cart_session( 'wcap_email_sent_id' ) != '' ){
        				$updated_cart_decoded = json_decode( stripslashes( $updated_cart_info ) );
                
                if ( ! isset( $updated_cart_decoded->wcap_user_ref ) ) {
                    
                    // Get the old cart data.
                    $get_cart_existing = $wpdb->get_var( $wpdb->prepare( 'SELECT abandoned_cart_info FROM ' . WCAP_ABANDONED_CART_HISTORY_TABLE . ' WHERE user_id = %d', $user_id ) );
                    $existing_cart_decoded = json_decode( stripslashes( $get_cart_existing ) );
                    if ( isset( $existing_cart_decoded->wcap_user_ref ) ) {
                        $updated_cart_decoded->wcap_user_ref = $existing_cart_decoded->wcap_user_ref;
                        $updated_cart_info = json_encode( $updated_cart_decoded );
                    }
                }

 		
				WCAP_CART_HISTORY_MODEL::update( array( 'abandoned_cart_info' => $updated_cart_info ), array( 'user_id' => $user_id, 'cart_ignored' => 0) ) ;
				
            } else {
                $wcap_guest_cart_key = Wcap_Common::wcap_get_guest_session_key();
                $existing_record     = $wpdb->get_results(
                    $wpdb->prepare(
                        'SELECT id, abandoned_cart_info from ' . WCAP_ABANDONED_CART_HISTORY_TABLE . ' WHERE session_id = %s AND recovered_cart = %s AND email_reminder_status <> %s',
                        $wcap_guest_cart_key,
						            '0',
						            'complete'
                    )
                );
                $abandoned_cart_id   = isset( $existing_record[0]->id ) ? $existing_record[0]->id : 0;

                // Check if any emails have been sent at all.
                $cnt_sent_emails = $wpdb->get_var(
                    $wpdb->prepare(
                        'SELECT count(id) FROM ' . WCAP_EMAIL_SENT_HISTORY_TABLE . ' WHERE cart_id = %d',
                        (int) $abandoned_cart_id
                    )
                );
                if ( $abandoned_cart_id > 0 && 0 === (int) $cnt_sent_emails ) { // Old record is present for the same session & no reminder emails have been sent, update the existign record.
                    $existing_cart_dec = isset( $existing_record[0]->abandoned_cart_info ) ? json_decode( stripslashes( $existing_record[0]->abandoned_cart_info ) ) : '';
                    
                    $updated_cart_decoded = json_decode( stripslashes( $updated_cart_info ) );
                    if ( ! isset( $updated_cart_decoded->wcap_user_ref ) && isset( $existing_cart_decoded->wcap_user_ref ) ) {
                        $updated_cart_decoded->wcap_user_ref = $existing_cart_decoded->wcap_user_ref;
                    }
                    $cart_info = json_encode( $updated_cart_decoded );
                
                    $wpdb->update(
						WCAP_ABANDONED_CART_HISTORY_TABLE,
						array(
							'cart_ignored' => '0',
							'abandoned_cart_info' => $cart_info,
                            'abandoned_cart_time' => self::$current_time
						),
						array(
							'id' => $abandoned_cart_id
						)
                    );
                    // Update the persistent data.
                    $main_prefix = is_multisite() ? $wpdb->get_blog_prefix(1) : $wpdb->prefix;
                    $wpdb->update(
						$main_prefix . 'usermeta',
						array(
							'meta_value' => $cart_info
						),
						array(
							'user_id'  => $user_id,
							'meta_key' => '_woocommerce_persistent_cart'
						)
                    );
					
                } else { // Mark the old record as ignored & create a new one.
										
					WCAP_CART_HISTORY_MODEL::update( array( 'cart_ignored' => 1 ), array( 'user_id' => $user_id ) ) ;
					
					$data = array(
                        'user_id'               => $user_id,
                        'abandoned_cart_info'   => $updated_cart_info,
                        'abandoned_cart_time'   => self::$current_time,
                        'cart_ignored'          => 0,
                        'recovered_cart'        => 0,
                        'user_type'             => 'GUEST',
                        'language'              => $current_user_lang,
                        'session_id'            => $wcap_guest_cart_key,
                        'ip_address'            => $guest_user_ip_address,
                        'email_reminder_status' => '',
                        'wcap_trash'            => '',
                        'unsubscribe_link'           => ''                        
                        );
                    
                    $insert_id = WCAP_CART_HISTORY_MODEL::insert( $data );
					wcap_set_cart_session( 'wcap_abandoned_id', $insert_id );
					$abandoned_cart_id = $insert_id;
                }

                if ( '' !== $abandoned_cart_id ) {
                  do_action ('acfac_add_data', $abandoned_cart_id );
                  Wcap_Common::wcap_add_checkout_link( $abandoned_cart_id );
                  Wcap_Common::wcap_run_webhook_after_cutoff( $abandoned_cart_id );
                }	
            }
        }

        /**
         * It will update the guest cart withing cutoff time.
         * @param int | string $user_id User id
         * @param json_encode $updated_cart_info Updated cart of the visitor
         * @param string $current_user_lang User Selected language
         * @param string $guest_user_ip_address Ip address of user
         * @param int | string $abandoned_cart_id  Abandoned cart id
         * @globals mixed $wpdb
         * @since 5.0
         */
        public static function wcap_update_guest_cart_within_cutoff_time ( $user_id, $updated_cart_info, $guest_user_ip_address, $abandoned_cart_id ) {

            global $wpdb;

            if( is_multisite() ) {
                $main_prefix = $wpdb->get_blog_prefix(1);
            }else {
                $main_prefix = $wpdb->prefix;
            }

            if ( function_exists( 'icl_object_id' ) ) {
              $updated_cart_info = WCAP_CART_HISTORY_MODEL::add_wcml_currency( $updated_cart_info );
            }
            if ( defined ( 'WOOCOMMERCE_MULTICURRENCY_VERSION' ) ) {
                $updated_cart_info = WCAP_CART_HISTORY_MODEL::add_wc_multicurrency( $updated_cart_info );
            }
            if ( class_exists( 'WOOCS' ) ) {
                $updated_cart_info = WCAP_CART_HISTORY_MODEL::add_woocs_currency( $updated_cart_info );
            }

			WCAP_CART_HISTORY_MODEL::update( array( 'abandoned_cart_info' => $updated_cart_info, 'abandoned_cart_time' => self::$current_time, 'ip_address'  => $guest_user_ip_address ), array( 'user_id' => $user_id, 'cart_ignored' => 0 ) );

            // update the persistent data
            $wpdb->query( "UPDATE `" .  $main_prefix . "usermeta` 
              SET meta_value = '" . $updated_cart_info . "'
              WHERE user_id = '" . $user_id . "'
              AND meta_key = '_woocommerce_persistent_cart'" );
            

            if ( '' !== $abandoned_cart_id ) {
                do_action ('acfac_add_data', $abandoned_cart_id );
            }
        }

        /**
         * It will compare old and new cart for the logged-in, visitors & guest users.
         * @param json_encode $new_cart New cart of user
         * @param json_encode $last_abandoned_cart old cart of user 
         * @return true | false 
         */
        public static function wcap_compare_all_users_carts( $new_cart, $last_abandoned_cart) {

            $current_woo_cart   = $abandoned_cart_arr = array();

            $current_woo_cart   = json_decode( stripslashes( $new_cart ), true );
            $abandoned_cart_arr = json_decode( stripslashes( $last_abandoned_cart ), true );

            /**
            * When we delete products from the cart it will return true as whole cart has been updated
            * When we add the new products to the cart it will return the true as whole cart has been updated
            */
            if( isset( $current_woo_cart['cart'], $abandoned_cart_arr['cart'] ) ) {
              if ( ( is_array( $current_woo_cart ) && is_array( $abandoned_cart_arr ) ) &&
                  ( count( $current_woo_cart['cart'] ) <  count( $abandoned_cart_arr['cart'] ) || 
                 count( $current_woo_cart['cart'] ) >  count( $abandoned_cart_arr['cart'] ) ) ) {
                  return true;
              }

              $wcap_check_cart_diff = Wcap_Cart_Updated::wcap_array_diff_recursive( $current_woo_cart['cart'], $abandoned_cart_arr['cart'] );

              if ( $wcap_check_cart_diff != 0 ){
                return true;
              }
            }
            if( isset( $current_woo_cart['shipping_method'] ) &&   ! isset( $abandoned_cart_arr['shipping_method'] ) ){

                return true;
            }
            if( isset( $current_woo_cart['shipping_charges'] ) &&   ! isset( $abandoned_cart_arr['shipping_charges'] ) ){

                return true;
            }
            if( ( isset( $current_woo_cart['shipping_charges'] ) && $current_woo_cart['shipping_charges'] != $abandoned_cart_arr['shipping_charges'] ) || ( isset( $current_woo_cart['shipping_method'] ) &&  $current_woo_cart['shipping_method'] != $abandoned_cart_arr['shipping_method'] ) ) {

                return true;
            }

          return false;
        }
        /**
         * It will compare cart values.
         * As we have the recursive array, we need to check all values of the cart array.
         * @return array | 0 $difference Array of diffrence between old and new cart
         */
        public static function wcap_array_diff_recursive( $array1, $array2 ) {
            $difference = array();
            $new_diff   = array();
            if( is_array( $array1 ) && count( $array1 ) > 0 ) {
                foreach( $array1 as $key => $value ) {
                    if( is_array( $value ) ) {
                        if( !isset( $array2[$key] ) ) {
                            $difference[$key] = $value;
                        } elseif( !is_array( $array2[$key] ) ) {
                            $difference[$key] = $value;
                        } else {
                            $new_diff = Wcap_Cart_Updated::wcap_array_diff_recursive( $value, $array2[$key] );
                            if( $new_diff != FALSE ) {
                                $difference[$key] = $new_diff;
                            }
                        }
                    } elseif( !isset( $array2[$key] ) || $array2[$key] != $value ) {
                        $difference[$key] = $value;
                    }
                }
                $blank_difference = array_filter( $difference );
              
                if ( count( $difference ) > 0  && !empty( $blank_difference ) ) {
                    return $difference ;
                } else {
                    return 0;
                }
            } else {
                return 0;
            }
        }
        
        /**
         * Checks if Shipping charges have been modified for the new and old cart.
         * Returns true when it has been changed, else false.
         * 
         * @param string $updated_cart - Updated Cart Details
         * @para string $existing_cart - Existing Cart Details
         * @since 7.7
         */
        static function wcap_check_shipping_charges( $updated_cart, $existing_cart ) {
        
            $updated_cart_decoded = json_decode( $updated_cart );
            $existing_cart_decoded = json_decode( $existing_cart );

            if( isset( $updated_cart_decoded->shipping_charges ) && isset( $existing_cart_decoded->shipping_charges ) ) {
                if( $updated_cart_decoded->shipping_charges != $existing_cart_decoded->shipping_charges ) {
                    return true;
                }
            }
            return false;
        }

        static function wcap_delete_non_logged_in_cart( $refer, $user ){

          $abandoned_id = wcap_get_cart_session( 'wcap_abandoned_id' );

          if ( $abandoned_id != '' ) {
            WCAP_CART_HISTORY_MODEL::delete( array( 'id' => $abandoned_id ) );

            wcap_unset_cart_session( 'wcap_abandoned_id' );
          }

          return $refer;
        }

        /**
         * Display Coupon validity banner.
         *
         * @since 8.5.0
         */
        public static function wcap_add_banner() {

//            if ( ! is_user_logged_in() ) {
                // check if the script needs to be loaded on the cart page
                $atc_template_id   = wcap_get_cart_session( 'wcap_atc_template_id' );
                $exit_intent_template_id = wcap_get_cart_session( 'wcap_exit_intent_template_id' );
                if ( $atc_template_id > 0 || $exit_intent_template_id > 0 ) {
                    $template_settings = wcap_get_atc_template( $atc_template_id );
                    if ( ! $template_settings ) {
                        $template_settings = wcap_get_atc_template( $exit_intent_template_id );
                    }
                    if ( $template_settings ) {
                        $coupon_settings   = json_decode( $template_settings->coupon_settings );
                        $display_msg = htmlspecialchars_decode( $coupon_settings->wcap_countdown_timer_msg );
                        // check if atc coupon has been applied & display msg is available.
                        $abandoned_id   = wcap_get_cart_session( 'wcap_abandoned_id' );
                        $coupons_post_meta = get_post_meta( $abandoned_id, '_woocommerce_ac_coupon', true );

                        if ( is_array( $coupons_post_meta ) && count ( $coupons_post_meta ) > 0 ) {
                            foreach ( $coupons_post_meta as $k => $details ) {
                                if ( ( isset( $details['atc_coupon_code'] ) && '' !== $details ) || ( isset( $details['exit_intent_coupon_code'] ) && '' !== $details ) ) {
                                    $coupon_details = $details;
                                    break;
                                }
                            }
                        }
                        $display_dismissed = isset( $coupon_details['countdown_display_dismissed'] ) && $coupon_details['countdown_display_dismissed'] ? true : false;
                        $coupon_removed = isset( $coupon_details['coupon_removed'] ) && $coupon_details['coupon_removed'] ? true : false;

                        if( ! $display_dismissed && ! $coupon_removed ) {

                            $coupon_expiry  = isset( $coupon_details['time_expires'] ) && '' !== $coupon_details['time_expires'] ? $coupon_details['time_expires'] : '';
                            
                            $coupon_code        = isset( $coupon_details['atc_coupon_code'] ) ? $coupon_details['atc_coupon_code'] : '';
                            if ( '' === $coupon_code ) {
                                $coupon_code = isset( $coupon_details['exit_intent_coupon_code'] ) ? $coupon_details['exit_intent_coupon_code'] : '';
                            }                            
                            if ( '' !== $coupon_code ) {
                                    
                                if ( ( '' !== $coupon_expiry && $coupon_expiry > current_time( 'timestamp' ) ) || ( $coupon_expiry === '' && '' !== $coupon_code ) ) {    
                                    if ( ! WC()->cart->has_discount( $coupon_code ) && count( WC()->cart->get_applied_coupons() ) == 0 ) {
                                        WC()->cart->add_discount( $coupon_code );
                                    }
                                } else if ( '' !== $coupon_expiry && $coupon_expiry < current_time( 'timestamp' ) ) { // Coupon has already been applied, but is no longer valid.
                                    if( WC()->cart->has_discount( $coupon_code ) ) {
                                        WC()->cart->remove_coupon( $coupon_code ); 
                                    }   
                                }
                            }
                            
                            if ( '' !== $display_msg && '' !== $coupon_expiry ) {
                                if( $coupon_expiry > current_time( 'timestamp' ) ) {
                                    if ( false !== strpos( $display_msg, '<coupon_code>' ) ) {
                                        $coupon_name = isset( $coupon_details['atc_coupon_code'] ) ? $coupon_details['atc_coupon_code'] : '';
                                        if ( '' === $coupon_name ) {
                                            $coupon_name = $coupon_details['exit_intent_coupon_code'];
                                        }
                                        $display_msg = str_ireplace( "<coupon_code>", $coupon_name, $display_msg );
                                    }
                                    if ( false !== strpos( $display_msg, '<hh:mm:ss>' ) ) {
                                        $display_msg = str_ireplace( '<hh:mm:ss>', "<span id='wcap_timer'></span>", $display_msg );
                                    }
                                } else {
                                    $display_msg = isset( $coupon_settings->wcap_countdown_msg_expired ) && '' !== $coupon_settings->wcap_countdown_msg_expired ? __( $coupon_settings->wcap_countdown_msg_expired, 'woocommerce-ac' ) : __( 'The offer is no longer valid.', 'woocommerce-ac' );
                                }
                                
                                $banner_content = "<div id='wcap_primary' class='woocommerce-info'><div id='wcap_float'>$display_msg<i class='fa fa-close' id='wcap_countdown_dismiss'></i></div></div>";
                                echo $banner_content;
                            }
                        }
                    }
                }
//            }
            
        }

        /**
         * ATC coupon is removed by the user. Create a record for the same.
         *
         * @since 8.6
         */
        public static function wcap_update_coupon_details( $coupon_code ) {

            // Only for guests.
            if ( ! is_user_logged_in() ) {

                // Get the AC ID.
                $abandoned_id   = wcap_get_cart_session( 'wcap_abandoned_id' );

                // Coupon Details.
                $coupons_meta   = get_post_meta( $abandoned_id, '_woocommerce_ac_coupon', true );
                $update_details = $coupons_meta;
                if ( is_array( $coupons_meta ) && count( $coupons_meta ) > 0 ) {
                    foreach ( $coupons_meta as $key => $coupon_details ) {
						$coupon_applied  = isset( $coupon_details['time_applied'] ) && '' !== $coupon_details['time_applied'] ? $coupon_details['time_applied'] : '';

						$atc_coupon_code = isset( $coupon_details['atc_coupon_code'] ) ? $coupon_details['atc_coupon_code'] : '';
                        if ( '' === $atc_coupon_code ) {
                            $atc_coupon_code = isset( $coupon_details['exit_intent_coupon_code'] ) ? $coupon_details['exit_intent_coupon_details'] : '';
                        }
						if ( '' !== $coupon_applied && $coupon_code === $atc_coupon_code ) {
							$update_details[$key]['coupon_removed'] = true;
							update_post_meta( $abandoned_id, '_woocommerce_ac_coupon', $update_details );
							break;
						}
					}
				}
            }
        }

                /**
         * Adding shipping charges to cart info
         *
         * @since 8.15
         */

        public static function wcap_add_shipping_charges( $cart_data ) {
            $wc_shipping_charges = WC()->cart->get_shipping_total();
            // Extract the shipping amount
            $wc_shipping_charges = strip_tags( html_entity_decode( $wc_shipping_charges ) );
            $wc_shipping_charges = (float) filter_var( $wc_shipping_charges, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
            if ( isset( $cart_data ) && is_array( $cart_data ) ) {
                // Check if cart is empty AND quote plugin is active.
                if ( ( empty( $cart_data['cart'] ) || ! is_array( $cart_data['cart'] ) ) && class_exists( 'Addify_Request_For_Quote' ) ) {
                    // Try getting quote items from session.
                    $quote_items = WC()->session->get( 'quotes' );

                    if ( ! empty( $quote_items ) && is_array( $quote_items ) ) {
                        $cart_data['cart'] = $quote_items;
                    }
                }
                // REMOVE WAPF DATA FROM CART ITEMS.
                if ( ! empty( $cart_data['cart'] ) && is_array( $cart_data['cart'] ) ) {
                    foreach ( $cart_data['cart'] as $cart_key => $cart_item ) {
                        unset(
                            $cart_data['cart'][ $cart_key ]['wapf'],
                            $cart_data['cart'][ $cart_key ]['wapf_key'],
                            $cart_data['cart'][ $cart_key ]['wapf_field_groups'],
                            $cart_data['cart'][ $cart_key ]['wapf_item_price']
                        );
                        // REMOVE QUOTES FROM ATTRIBUTES
                        if ( isset( $cart_item['variation'] ) && is_array( $cart_item['variation'] ) ) {
                            foreach ( $cart_item['variation'] as $attr_key => $attr_value ) {
                                $cart_data['cart'][ $cart_key ]['variation'][ $attr_key ] = str_replace('"', '', $attr_value);
                            }
                        }
                        if ( isset( $cart_item['data'] ) && is_object( $cart_item['data'] ) ) {
                            $product_data = $cart_item['data']->get_data();

                            // Remove unnecessary fields
                            unset( $product_data['description'] );
                            unset( $product_data['short_description'] );

                            // REMOVE meta_data from product data
                            unset( $product_data['meta_data'] );

                            // Clean attribute_summary inside product data
                            if ( isset( $product_data['attribute_summary'] ) ) {
                                $product_data['attribute_summary'] = str_replace('"', '', $product_data['attribute_summary']);
                            }

                            if ( isset( $product_data['name'] ) ) {
                                $product_data['name'] = str_replace('"', '', $product_data['name']);
                            }

                            // Fix WC_DateTime objects
                            if ( isset( $product_data['date_created'] ) && is_object( $product_data['date_created'] ) ) {
                                $product_data['date_created'] = $product_data['date_created']->date('Y-m-d H:i:s');
                            }

                            if ( isset( $product_data['date_modified'] ) && is_object( $product_data['date_modified'] ) ) {
                                $product_data['date_modified'] = $product_data['date_modified']->date('Y-m-d H:i:s');
                            }

                            // Clean attributes inside product data
                            if ( isset( $product_data['attributes'] ) && is_array( $product_data['attributes'] ) ) {
                                foreach ( $product_data['attributes'] as $attr_key => $attr_value ) {
                                    if ( is_string( $attr_value ) ) {
                                        $product_data['attributes'][ $attr_key ] = str_replace( '"', '', $attr_value );
                                    }
                                }
                            }

                            $cart_data['cart'][ $cart_key ]['data'] = $product_data;
                        }
                    }
                }
                $cart_data[ 'shipping_charges' ] = $wc_shipping_charges;
                $cart_data['shipping_method']    = self::wcap_get_shipping_method($cart_data);
				$cart_data['sms_consent']        = wcap_get_cart_session( 'wcap_sms_consent' ) ? wcap_get_cart_session( 'wcap_sms_consent' ) : true;
                $cart_data['whatsapp_consent']        = wcap_get_cart_session( 'wcap_whatsapp_consent' ) ? wcap_get_cart_session( 'wcap_whatsapp_consent' ) : false;
				// Shipping & Tax totals.
				$cart_data['cart_totals']['shipping_total'] = $wc_shipping_charges;
				$cart_data['cart_totals']['total_tax']      = WC()->cart->get_taxes_total();

                $user_id = wcap_get_cart_session( 'wcap_user_id' ) ? wcap_get_cart_session( 'wcap_user_id' ) : 0;
                if ( 0 == $user_id ) {
                    $cart_data['captured_by'] = 'visitor';
                }
            }            

            return $cart_data;
        }

          /** get shipping method  */

        public static function wcap_get_shipping_method( $cart_data ){

            $current_methods =  WC()->session->get( 'chosen_shipping_methods' );

            if ( empty( $current_methods ) ) {
                return '';
            }

            if ( ! isset( $current_methods[0] ) || empty( $current_methods[0] ) || is_null( $current_methods[0] ) ) {
                return '';
            }


            $shipping_methods = WC()->shipping->get_shipping_methods();
            $methods          = array();
            
            foreach ( $shipping_methods as $shipping_method ) {           
                $methods[ $shipping_method->id ] = $shipping_method->method_title;
            }
            $chosen_method = strstr( $current_methods[0], ':', true) ;
            
			if ( isset( $methods[ $chosen_method ] ) ) {
				return $methods[ $chosen_method ];
			} else {
				return '';
			}
        }
    }


}
