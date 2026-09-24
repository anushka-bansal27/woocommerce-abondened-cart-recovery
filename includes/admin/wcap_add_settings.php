<?php
/**
 * It will add all the settings needed for the plugin.
 *
 * @author  Tyche Softwares
 *
 * @since 2.3.8
 *
 * @package woocommerce-ac
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wcap_Add_Settings' ) ) {
	/**
	 * It will add all the settings needed for the plugin.
	 *
	 * @since 5.0
	 */
	class Wcap_Add_Settings {

		/**
		 * Mark old carts complete.
		 *
		 * @param string $old_value - Old Value of the setting.
		 * @param string $new_value - New Value of the setting.
		 *
		 * @since 8.11.0
		 */
		public static function wcap_update_ac_enable_cart_emails( $old_value, $new_value ) {
			if ( 'on' === $new_value && 'on' !== $old_value ) {
				$old_carts_time = current_time( 'timestamp' ) - 7 * 86400; // (older than 1 week).
				// Mark carts older than 7 days as abandoned.
				global $wpdb;
				$wpdb->query( // phpcs:ignore
					'UPDATE ' . $wpdb->prefix . "ac_abandoned_cart_history SET email_reminder_status = 'complete' WHERE abandoned_cart_time < $old_carts_time AND email_reminder_status = ''" // phpcs:ignore
				);
			}
		}
		/**
		 * Ajax function which will save the notice state in database.
		 *
		 * @param str $new_value - new value.
		 * @param str $old_value - old value.
		 * @since 9.3.0
		 */
		public static function wcap_update_admin_notice_value( $new_value, $old_value ) {
			// Check test mode.
			if ( $new_value !== $old_value && ! empty( $new_value ) && 'on' === $new_value ) {
				update_option( 'wcap_auto_login_notice_dismiss', false );
			}
			return $new_value;
		}
		/**
		 * Enabling this setting will allow users registered on the website to access it via reminder email links without needing to login
		 *
		 * @since 9.3.0
		 *
		 * @hook admin_notices
		 */
		public static function wcap_show_auto_loggin_notice() {
			if ( isset( $_GET['page'] ) && 'woocommerce_ac_page' === $_GET['page'] && isset( $_GET['action'] ) && 'emailsettings' === $_GET['action'] && ! get_option( 'wcap_auto_login_notice_dismiss', false ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				if ( 'on' === get_option( 'wcap_auto_login_users', 'on' ) ) {
					?>
					<div id='wcap_auto_login_notice' class='is-dismissible notice notice-info wcal-cron-notice'>
						<p>
							<?php
							printf(
								// Translators: Plugin Name and URL.
								esc_html__( 'Enabling this setting will allow users registered on the website to access it via reminder email links without needing to login, which may be a security vulnerability.', 'woocommerce-ac' ),
								wp_kses_post( '<b>Abandoned Cart Pro for WooCommerce</b>' )
							);
							?>
						</p>
					</div>
					<?php
				}
			}

			if ( isset( $_GET['page'] ) && 'woocommerce_ac_page' === $_GET['page'] && isset( $_GET['action'] ) && 'emailsettings' === $_GET['action'] && ! get_option( 'wcap_lite_data_imported_notice_dismiss', false ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				if ( 'yes' === get_option( 'wcap_lite_data_imported' ) ) {
					?>
					<div id='wcap_auto_login_notice' class='is-dismissible notice notice-success wcal-cron-notice'>
						<p>
							<?php
							printf(
								// Translators: Plugin Name and URL.
								esc_html__( 'Your Abandoned Cart data has been successfully migrated from the lite to the pro version.', 'woocommerce-ac' ),
								wp_kses_post( '<b>Abandoned Cart Pro for WooCommerce</b>' )
							);
							?>
						</p>
					</div>
					<?php
					update_option( 'wcap_lite_data_imported_notice_dismiss', true );
				}
			}
		}

		/**
		 * Introduces the AI Reporting Abilities feature (off by default) the
		 * first time an admin visits the plugin's screens after it ships,
		 * until dismissed or the feature is turned on.
		 *
		 * @since 10.6.0
		 *
		 * @hook admin_notices
		 */
		public static function wcap_show_ai_abilities_intro_notice() {

			if ( ! isset( $_GET['page'] ) || 'woocommerce_ac_page' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				return;
			}

			if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_woocommerce' ) ) {
				return;
			}

			if ( 'on' === get_option( 'wcap_enable_ai_abilities', '' ) ) {
				return;
			}

			if ( get_user_meta( get_current_user_id(), 'wcap_ai_abilities_notice_dismissed', true ) ) {
				return;
			}

			$settings_url = admin_url( 'admin.php?page=woocommerce_ac_page&action=emailsettings' );
			?>
			<div id="wcap_ai_abilities_intro_notice" class="is-dismissible notice notice-info wcal-cron-notice">
				<p>
					<?php
					printf(
						/* translators: %1$s: link to the plugin's Settings screen, %2$s: link to feature documentation */
						esc_html__( 'New in Abandoned Cart Pro: AI assistants (like Claude, connected via MCP) can answer questions about your abandoned-cart data — top abandoned products, recovered revenue, recovery rates, and template performance. This is off by default. %1$s or %2$s.', 'woocommerce-ac' ),
						'<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Turn it on in Settings', 'woocommerce-ac' ) . '</a>',
						'<a href="https://www.tychesoftwares.com/docs/docs/abandoned-cart-pro-for-woocommerce-new/connect-abandoned-cart-pro-to-ai-assistants-using-mcp/" target="_blank">' . esc_html__( 'learn more', 'woocommerce-ac' ) . '</a>'
					);
					?>
				</p>
			</div>
			<?php
		}

		/**
		 * Ajax handler: mark the AI Reporting Abilities intro notice as
		 * dismissed for the current user.
		 *
		 * @since 10.6.0
		 */
		public static function wcap_dismiss_ai_abilities_notice() {

			check_ajax_referer( 'wcap_settings_nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_woocommerce' ) ) {
				wp_send_json_error( 'Unauthorized', 403 );
				wp_die();
			}

			update_user_meta( get_current_user_id(), 'wcap_ai_abilities_notice_dismissed', 'yes' );
			wp_send_json_success();
			wp_die();
		}

		/**
		 * Gets all settings
		 *
		 * @param bool $return - Return values.
		 *
		 * @since 8.21.0
		 */
		public static function wcap_get_settings( $return = false ) {
			if ( ! $return ) {
		        check_ajax_referer( 'wcap_settings_nonce', 'security' );
		        if ( ! current_user_can( 'manage_options' ) ) {
		            wp_send_json_error( 'Unauthorized', 403 );
		            wp_die();
		        }
    		}
			global $wpdb;

			$results  = $wpdb->get_results( // phpcs:ignore
				'SELECT * FROM `' . $wpdb->prefix . "options` WHERE option_name LIKE 'ac_%' OR option_name LIKE 'wcap_%' OR option_name LIKE 'wcac_%'"
			);
			$settings = array();
			foreach ( $results as $value ) {
				$key              = $value->option_name;
				$settings[ $key ] = isset( $value->option_value ) && maybe_unserialize( $value->option_value ) !== false ? maybe_unserialize( $value->option_value ) : $value->option_value;
			}

			if ( '' === $settings['ac_delete_abandoned_order_days'] || ! isset( $settings['ac_delete_abandoned_order_days'] ) ) {
				$settings['ac_delete_abandoned_order_days'] = '365';
			}
			$settings_to_check = array(
				'ac_cart_abandoned_after_x_days_order_placed',
				'ac_email_admin_on_recovery',
				'ac_disable_guest_cart_email',
				'ac_disable_logged_in_cart_email',
				'ac_capture_email_from_forms',
				'wcac_delete_plugin_data',
				'wcap_atc_close_icon_add_product_to_cart',
				'wcap_delete_coupon_data',
				'wcap_use_auto_cron',
			);

			foreach ( $settings_to_check as $key ) {
				if ( isset( $settings[ $key ] ) && '' === $settings[ $key ] ) {
					$settings[ $key ] = 'off';
				}
			}

			$wcap_enable_debounce             = isset( $settings['wcap_enable_debounce'] ) ? $settings['wcap_enable_debounce'] : '';
			$settings['wcap_enable_debounce'] = 'on' === $wcap_enable_debounce ? 'on' : '';

			$settings['wcap_enable_ai_abilities'] = isset( $settings['wcap_enable_ai_abilities'] ) ? $settings['wcap_enable_ai_abilities'] : '';

			$settings['wcap_enable_gdpr_consent'] = isset( $settings['wcap_enable_gdpr_consent'] ) ? $settings['wcap_enable_gdpr_consent'] : '';
			if ( '' === $settings['wcap_enable_gdpr_consent'] ) {
				$wcap_guest_cart_capture_msg  = isset( $settings['wcap_guest_cart_capture_msg'] ) ? $settings['wcap_guest_cart_capture_msg'] : '';
				$wcap_logged_cart_capture_msg = isset( $settings['wcap_logged_cart_capture_msg'] ) ? $settings['wcap_logged_cart_capture_msg'] : '';
				if ( '' !== $wcap_guest_cart_capture_msg || '' !== $wcap_logged_cart_capture_msg ) {
					$settings['wcap_enable_gdpr_consent'] = '';
				}
			}

			$settings['wcap_product_name_redirect']      = isset( $settings['wcap_product_name_redirect'] ) && '' !== $settings['wcap_product_name_redirect'] ? $settings['wcap_product_name_redirect'] : 'checkout';
			$settings['wcap_add_utm_to_links']           = isset( $settings['wcap_add_utm_to_links'] ) ? $settings['wcap_add_utm_to_links'] : '';
			$settings['wcap_unsubscribe_landing_page']   = isset( $settings['wcap_unsubscribe_landing_page'] ) && '' !== $settings['wcap_unsubscribe_landing_page'] ? $settings['wcap_unsubscribe_landing_page'] : 'default_page';
			$settings['wcap_unsubscribe_custom_content'] = isset( $settings['wcap_unsubscribe_custom_content'] ) && '' !== $settings['wcap_unsubscribe_custom_content'] ? $settings['wcap_unsubscribe_custom_content'] : __( 'You have successfully unsubscribed and will not receive any further reminders for abandoned carts.', 'woocommerce-ac' );
			$settings['wcap_unsubscribe_custom_wp_page'] = isset( $settings['wcap_unsubscribe_custom_wp_page'] ) && '' !== $settings['wcap_unsubscribe_custom_wp_page'] ? $settings['wcap_unsubscribe_custom_wp_page'] : '';
			$settings['wcap_enable_sms_consent']         = isset( $settings['wcap_enable_sms_consent'] ) && '' !== $settings['wcap_enable_sms_consent'] ? $settings['wcap_enable_sms_consent'] : '';
			$settings['wcap_select_guest_cart_tracking'] = isset( $settings['wcap_select_guest_cart_tracking'] ) && '' !== $settings['wcap_select_guest_cart_tracking'] ? $settings['wcap_select_guest_cart_tracking'] : 'opt-out';
			$settings['wcap_gdpr_allow_opt_in']          = isset( $settings['wcap_gdpr_allow_opt_in'] ) && '' !== $settings['wcap_gdpr_allow_opt_in'] ? $settings['wcap_gdpr_allow_opt_in'] : __( 'I agree to receive abandoned cart reminder emails.', 'woocommerce-ac' );
			$settings['wcap_restrict_countries']         = isset( $settings['wcap_restrict_countries'] ) && '' !== $settings['wcap_restrict_countries'] ? $settings['wcap_restrict_countries'] : '';
			$cart_source                                 = array(
				'all'         => 'on',
				'checkout'    => 'on',
				'profile'     => 'on',
				'atc'         => 'on',
				'exit_intent' => 'on',
				'url'         => 'on',
				'custom_form' => 'on',
			);
			$settings['wcap_email_admin_cart_source']    = isset( $settings['wcap_email_admin_cart_source'] ) && '' !== $settings['wcap_email_admin_cart_source'] ? $settings['wcap_email_admin_cart_source'] : $cart_source;


			$settings = apply_filters( 'wcap_settings', $settings );

			if ( $return ) {
				return $settings;
			}

			wp_send_json( $settings );
		}

		/**
		 * Save all settings.
		 *
		 * @param bool $return - Return values.
		 *
		 * @since 8.21.0
		 */
		public static function wcap_save_settings( $return = false ) {

		    // 1. Nonce verification.
		    check_ajax_referer( 'wcap_settings_nonce', 'security' );

		    // 2. Capability check.
		    if ( ! current_user_can( 'manage_options' ) ) {
		        wp_send_json_error( 'Unauthorized', 403 );
		    }

		    // 3. Allowed prefixes — only plugin-owned option names.
		    $allowed_prefixes = array( 'wcap_', 'ac_' );
		    $settings         = (array) self::wcap_get_settings( true );

		    foreach ( $_POST as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification
		        $key        = sanitize_key( $key );
		        $is_allowed = false;
		        foreach ( $allowed_prefixes as $prefix ) {
		            if ( substr( $key, 0, strlen( $prefix ) ) === $prefix ) {
					    $is_allowed = true;
					    break;
					}
		        }

		        if ( ! $is_allowed ) {
		            continue;
		        }

		        $val = wp_unslash( $value );
		        if ( 'wcap_email_admin_cart_source' === $key ) {
		            $decoded = json_decode( stripslashes( $val ), true );
		            $val     = is_array( $decoded ) ? array_map( 'sanitize_text_field', $decoded ) : sanitize_text_field( $val );
		        } else {
		            $val = is_array( $val ) ? array_map( 'sanitize_text_field', wp_unslash( $val ) ) : sanitize_text_field( $val );
		        }

		        if ( 'wcap_whatsapp_access_token' === $key ) {
		            delete_option( 'wcap_whatsapp_token_notice' );
		        }
		        update_option( $key, $val );// nosemgrep:scanner.php.wp.security.options.user-controlled
		    }

		    wp_send_json( 'success' );
		}

		/**
		 * Ensure setting is not left blanks.
		 *
		 * @param string $new_value - New Value of the setting.
		 * @param string $old_value - Old Value of the setting.
		 * @return string $new_value - New Value.
		 *
		 * @since 9.3.0
		 */
		public static function wcap_update_email_admin_custom_addresses( $new_value, $old_value ) {

			if ( '' === $new_value ) {
				$new_value = get_option( 'admin_email' );
			}
			return $new_value;
		}
	}
}
