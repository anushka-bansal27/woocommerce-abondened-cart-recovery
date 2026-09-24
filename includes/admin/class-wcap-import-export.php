<?php
/**
 * Import Export REST API
 *
 * @package WooCommerce Abandoned Cart Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCAP_Import_Export_API class.
 */
class WCAP_Import_Export_API {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'wcap_register_routes' ) );
	}

	/**
	 * Register REST Routes
	 */
	public function wcap_register_routes() {

		register_rest_route(
			'wcap/v1',
			'/export-settings',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'wcap_export_settings' ),
				'permission_callback' => array( $this, 'wcap_get_permission' ),
			)
		);

		register_rest_route(
			'wcap/v1',
			'/import-settings',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'wcap_import_settings' ),
				'permission_callback' => array( $this, 'wcap_get_permission' ),
			)
		);
	}

	/**
	 * Permission callback for API endpoints
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return bool True if the user has permission, false otherwise.
	 */
	public static function wcap_get_permission( $request ) {
		if ( current_user_can( 'manage_options' ) || current_user_can( 'manage_woocommerce' ) ) {
			return true;
		}

		return new WP_Error(
			'orddd_rest_forbidden',
			__( 'You are not allowed to access this resource.', 'order-delivery-date' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Export Settings
	 *
	 * @param WP_REST_Request $request The REST request object containing the export parameters.
	 * @return WP_REST_Response A response object containing the exported settings data.
	 */
	public function wcap_export_settings( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Permission denied.', 'woocommerce-ac' ),
				),
				403
			);
		}
		$params      = $request->get_json_params();
		$export_type = isset( $params['export_type'] ) ? sanitize_text_field( $params['export_type'] ) : 'all';
		$settings    = isset( $params['settings'] ) ? $params['settings'] : array();

		$data = array();
		// All available option groups.
		$allowed_settings = $this->wcap_allowed_settings();

		// Export all settings.
		if ( 'all' === $export_type ) {
			foreach ( $allowed_settings as $section => $option_keys ) {
				$data[ $section ] = $this->wcap_get_general_settings( $option_keys );
			}

			$data['email_templates']    = $this->wcap_get_email_templates( 'email' );
			$data['sms_templates']      = $this->wcap_get_email_templates( 'sms' );
			$data['popup_templates']    = $this->wcap_get_email_templates( 'popup' );
			$data['whatsapp_templates'] = $this->wcap_get_email_templates( 'whatsapp' );
			$data['fb_templates']       = $this->wcap_get_email_templates( 'fb' );
		} else {

			foreach ( $settings as $setting_key ) {
				// General settings sections.
				if ( isset( $allowed_settings[ $setting_key ] ) ) {
					$data[ $setting_key ] = $this->wcap_get_general_settings(
						$allowed_settings[ $setting_key ]
					);

					// Template sections.
				} elseif ( str_contains( $setting_key, '_templates' ) ) {
					$type                 = str_replace( '_templates', '', $setting_key );
					$data[ $setting_key ] = $this->wcap_get_email_templates( $type );
				}
			}
		}
		$data['plugin_version'] = WCAP_PLUGIN_VERSION;
		return rest_ensure_response(
			array(
				'data' => $data,
			)
		);
	}

	/**
	 * Fetch all templates data.
	 *
	 * @param string $type The type of templates to fetch (e.g., 'email', 'sms', 'popup', 'whatsapp', 'fb').
	 * @return array List of templates for the specified type.
	 */
	public function wcap_get_email_templates( $type = 'email' ) {

		global $wpdb;

		// Popup templates stored in ATC rules table.
		if ( 'popup' === $type ) {
			$results = $wpdb->get_results(
				'SELECT * FROM `' . esc_sql( WCAP_ATC_RULES_TABLE ) . '` ORDER BY id ASC', //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				ARRAY_A
			);
			// rules only belong to email templates.
			if ( ! empty( $results ) ) {
				foreach ( $results as &$row ) {
					$row['rules']     = '[]';
					$row['is_active'] = '0';
					// Remove existing coupon ID from coupon_settings.
					if ( ! empty( $row['coupon_settings'] ) ) {
						$coupon_settings = json_decode( $row['coupon_settings'], true );
						if ( is_array( $coupon_settings ) ) {
							$coupon_settings['wcap_atc_popup_coupon'] = '';
						}
						$row['coupon_settings'] = wp_json_encode( $coupon_settings );
					}
				}
			}
		} else {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM `' . WCAP_NOTIFICATION_TEMPLATES_TABLE . '` WHERE notification_type = %s', //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$type
				),
				ARRAY_A
			);
			// rules only belong to email templates.
			if ( ! empty( $results ) ) {
				foreach ( $results as &$row ) {
					$row['rules']       = '[]';
					$row['coupon_code'] = '';
					$row['is_active']   = '0';
				}
			}
		}

		return $results;
	}
	/**
	 * List of allowed settings for export/import.
	 */
	public function wcap_allowed_settings() {
		$allowed_settings = array(
			'general_settings'         => array(
				'ac_cart_abandoned_after_x_days_order_placed',
				'ac_cart_abandoned_time',
				'ac_cart_abandoned_time_guest',
				'ac_delete_abandoned_order_days',
				'ac_disable_guest_cart_email',
				'ac_enable_cart_emails',
				'ac_email_admin_on_recovery',
				'wcap_email_admin_on_abandonment',
				'wcap_email_admin_cart_source',
				'wcap_email_admin_custom_addresses',
				'ac_capture_email_from_forms',
				'ac_email_forms_classes',
				'ac_capture_email_address_from_url',
				'wcap_enable_debounce',
				'ac_disable_logged_in_cart_email',
				'wcap_atc_close_icon_add_product_to_cart',
				'wcac_delete_plugin_data',
				'wcap_delete_coupon_data',
				'wcap_unsubscribe_landing_page',
				'wcap_unsubscribe_from_all_carts',
				'wcap_unsubscribe_custom_content',
				'ac_track_guest_cart_from_cart_page',
				'wcap_cron_time_duration',
				'wcap_use_auto_cron',
				'wcap_add_utm_to_links',
				'wcap_auto_login_users',
				'wcap_email_reports_frequency',
				'wcap_email_reports_emails_list',
				'wcap_enable_fb_reminders',
				'wcap_enable_fb_reminders_popup',
				'wcap_fb_user_icon',
				'wcap_fb_consent_text',
			),
			'gdpr_settings'            => array(
				'wcap_enable_gdpr_consent',
				'wcap_guest_cart_capture_msg',
				'wcap_logged_cart_capture_msg',
				'wcap_select_guest_cart_tracking',
				'wcap_gdpr_allow_opt_in',
				'wcap_gdpr_allow_opt_out',
				'wcap_gdpr_opt_out_message',
				'wcap_enable_sms_consent',
				'wcap_sms_consent_msg',
			),
			'restriction_settings'     => array(
				'wcap_restrict_ip_address',
				'wcap_restrict_email_address',
				'wcap_restrict_domain_address',
				'wcap_restrict_countries',
			),
			'recovery_emails_settings' => array(
				'wcap_product_name_redirect',
				'wcap_add_utm_to_links',
				'wcap_auto_login_users',
				'wcap_from_email',
				'wcap_from_name',
				'wcap_reply_email',
				'wcap_product_image_height',
				'wcap_product_image_width',
			),
			'sms_settings'             => array(
				'wcap_enable_sms_reminders',
				'wcap_sms_from_phone',
				'wcap_sms_account_sid',
				'wcap_sms_auth_token',
			),
			'whatsapp_settings'        => array(
				'wcap_enable_whatsapp_reminders',
				'wcap_whatsapp_access_token',
				'wcap_whatsapp_phone_id',
				'wcap_whatsapp_consent_text',
			),
			'smtp_connector'           => array(
				'wcap_custom_smtp_connector',
			),
		);
		return $allowed_settings;
	}
	/**
	 * Fetch settings values based on provided option keys.
	 *
	 * @param array $option_keys List of option keys to fetch values for.
	 * @return array Associative array of option keys and their corresponding values.
	 */
	public function wcap_get_general_settings( $option_keys ) {
		$settings = array();
		foreach ( $option_keys as $option_key ) {
			$settings[ $option_key ] = get_option( $option_key );
		}

		/*
		 * Prepare SMTP connector settings
		 */
		if ( isset( $settings['wcap_custom_smtp_connector'] ) ) {
			$settings['wcap_custom_smtp_connector'] = $this->wcap_remove_sensitive_smtp_data( $settings['wcap_custom_smtp_connector'] );
		}
		return $settings;
	}

	/**
	 * Remove sensitive SMTP data from settings.
	 *
	 * @param string $smtp_json The SMTP settings as JSON string.
	 * @return string The SMTP settings without password.
	 */
	public function wcap_remove_sensitive_smtp_data( $smtp_json ) {
		$smtp = json_decode( $smtp_json, true );
		if ( is_array( $smtp ) ) {
			unset( $smtp['smtp_password'] );
		}
		return wp_json_encode( $smtp );
	}

	/**
	 * Import Settings
	 *
	 * @param WP_REST_Request $request The REST request object containing the import data.
	 * @return WP_REST_Response A response object indicating the success or failure of the import process.
	 */
	public function wcap_import_settings( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Permission denied.', 'woocommerce-ac' ),
				),
				403
			);
		}

		$params   = $request->get_json_params();
		$sections = $params['sections'] ?? array();
		$mode     = $params['import_mode'] ?? 'merge';
		$settings = $params['settings'] ?? array();

		if ( empty( $settings ) || ! is_array( $settings ) ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid JSON file.', 'woocommerce-ac' ),
			);
		}

		foreach ( $sections as $section ) {
			$data = $settings[ $section ] ?? array();

			switch ( $section ) {
				case 'general_settings':
				case 'gdpr_settings':
				case 'restriction_settings':
				case 'recovery_emails_settings':
				case 'sms_settings':
				case 'whatsapp_settings':
				case 'smtp_connector':
					$this->wcap_import_option_settings( $data );
					break;

				case 'sms_templates':
				case 'whatsapp_templates':
				case 'facebook_templates':
				case 'popup_templates':
				case 'email_templates':
					$this->wcap_process_import_section( $section, $data, $mode );
					break;
			}
		}

		return array(
			'success' => true,
			'message' => __( 'Settings imported successfully.', 'woocommerce-ac' ),
		);
	}
	/**
	 * Process import for template sections.
	 *
	 * @param string $section The section being imported (e.g., 'sms_templates').
	 * @param array  $rows    The rows of data to be imported for the section.
	 * @param string $mode    The import mode ('replace' or 'merge').
	 */
	public function wcap_process_import_section( $section, $rows, $mode ) {

		global $wpdb;
		if ( 'popup_templates' === $section ) {
			$table = WCAP_ATC_RULES_TABLE;
			if ( 'replace' === $mode ) {
				$wpdb->query( 'DELETE FROM `' . esc_sql( $table ) . '`' ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
			foreach ( $rows as $row ) {
				unset( $row['id'] );
				$wpdb->insert( $table, $row );
			}
		} else {
			$table    = WCAP_NOTIFICATION_TEMPLATES_TABLE;
			$type_map = array(
				'sms_templates'      => 'sms',
				'whatsapp_templates' => 'whatsapp',
				'fb_templates'       => 'fb',
				'email_templates'    => 'email',
			);
			if ( ! isset( $type_map[ $section ] ) ) {
				return;
			}
			$type = $type_map[ $section ];
			if ( 'replace' === $mode ) {
				$wpdb->delete(
					$table,
					array( 'notification_type' => $type )
				);
			}
			foreach ( $rows as $row ) {
				unset( $row['id'] );
				$wpdb->insert( $table, $row );
			}
		}
	}

	/**
	 * Import option settings from data array.
	 *
	 * @param array $settings The settings array to import.
	 * @return void
	 */
	public function wcap_import_option_settings( $settings ) {

		if ( ! is_array( $settings ) ) {
			return;
		}

		$allowed      = $this->wcap_allowed_settings();
		$flat_allowed = array_merge( ...array_values( $allowed ) );

		foreach ( $settings as $option_key => $value ) {
			if ( ! in_array( $option_key, $flat_allowed, true ) ) {
				continue;
			}
			if ( is_string( $value ) ) {
				$value = sanitize_text_field( $value );
			}
			// Update each option.
			update_option( $option_key, $value );
		}
	}
}

new WCAP_Import_Export_API();
