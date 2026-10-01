<?php
/**
 * It will handle the license activate & deactivate funtionality.
 *
 * @author   LittleCodr
 * @package Abandoned-Cart-Pro-for-WooCommerce/Admin/License
 * @since   5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wcap_EDD' ) ) {
	/**
	 * It will handle the license activate & deactivate funtionality.
	 */
	class Wcap_EDD {
		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'wp_ajax_ac_deactivate_license', array( &$this, 'wcap_edd_ac_deactivate_license' ) );
			add_action( 'wp_ajax_ac_activate_license', array( &$this, 'wcap_edd_ac_activate_license' ) );
		}

		/**
		 * It will register the license setting.
		 *
		 * @since 2.3.1
		 */
		public static function wcap_edd_ac_register_option() {
			// creates our settings in the options table.
			register_setting( 'edd_sample_license', 'edd_sample_license_key_ac_woo', array( 'Wcap_EDD', 'wcap_edd_sanitize_license' ) );
		}

		/**
		 * It will sanitize the license key entred by the admin.
		 *
		 * @param string $new_license Licese key.
		 * @return string $new_license Licese key.
		 * @since 2.3.1
		 */
		public static function wcap_edd_sanitize_license( $new_license ) {
			$old = get_option( 'edd_sample_license_key_ac_woo' );
			if ( $old && $old != $new_license ) {
				delete_option( 'edd_sample_license_status_ac_woo' ); // new license has been entered, so must reactivate.
			}
			return $new_license;
		}

		/**
		 * It will activate the licese key on our server.
		 *
		 * @since 2.3.1
		 */
		public static function wcap_edd_ac_activate_license() {
			// listen for our activate button to be clicked.
			if ( isset( $_POST['edd_ac_license_activate'] ) ) {

				if ( isset( $_POST['action'] ) && 'ac_activate_license' === $_POST['action'] ) {
					// run a quick security check.
					if ( ( ! check_ajax_referer( 'edd_license_nonce', 'edd_license_nonce' ) ) ) {
						return; // get out if we didn't click the Activate button.
					}

					if ( isset( $_POST['edd_sample_license_key_ac_woo'] ) ) {
						update_option( 'edd_sample_license_key_ac_woo', sanitize_text_field( wp_unslash( $_POST['edd_sample_license_key_ac_woo'] ) ) );
					}
				} elseif ( ( ! check_ajax_referer( 'edd_sample_nonce', 'edd_sample_nonce' ) ) ) {
					return; // get out if we didn't click the Activate button.
				}

				$license = trim( get_option( 'edd_sample_license_key_ac_woo' ) );

				// data to send in our API request.
				$api_params = array(
					'edd_action' => 'activate_license',
					'license'    => $license,
					'item_name'  => urlencode( EDD_SL_ITEM_NAME_AC_WOO ) // the name of our product in EDD.
				);

				// Call the custom API.
				$response = wp_remote_get( add_query_arg( $api_params, EDD_SL_STORE_URL_AC_WOO ), array( 'timeout' => 15, 'sslverify' => false ) );

				// make sure the response came back okay.
				if ( is_wp_error( $response ) ) {
					return false;
				}

				// decode the license data.
				$license_data = json_decode( wp_remote_retrieve_body( $response ) );

				if ( isset( $license_data->error ) ) {
					update_option( 'wcap_edd_license_error', $license_data->error );
				}

				// Check if using trial version download license or not.
				if ( isset( $license_data->success ) && ! $license_data->success && ( 'invalid' === $license_data->license && in_array( $license_data->error, array( 'missing', 'item_name_mismatch' ), true ) ) ) {

					$api_params = array(
						'edd_action' => 'activate_license',
						'license'    => $license,
						'item_name'  => rawurlencode( EDD_SL_ITEM_NAME_AC_WOO_TRIAL ),
					);

					// Call the custom API.
					$response = wp_remote_get(
						esc_url_raw( add_query_arg( $api_params, EDD_SL_STORE_URL_AC_WOO ) ),
						array(
							'timeout'   => 15,
							'sslverify' => false,
						)
					);

					// make sure the response came back okay.
					if ( is_wp_error( $response ) ) {
						return false;
					}

					$license_data = json_decode( wp_remote_retrieve_body( $response ) );

					switch ( $license_data->license ) {
						case 'valid':
							update_option( 'wcap_edd_license_download_type', 'trial' );
							delete_option( 'wcap_edd_license_error' );
							delete_option( 'wcap_show_only_license_page' );
							break;
						case 'invalid':
							update_option( 'wcap_edd_license_error', $license_data->error );
							if ( 'expired' === $license_data->error ) {
								update_option( 'wcap_edd_license_download_type', 'trial' );
							}
							break;
						case 'expired':
							update_option( 'wcap_edd_license_download_type', 'trial' );
							update_option( 'wcap_edd_license_error', 'expired' );
					}
				} elseif ( 'valid' === $license_data->license ) {
					delete_option( 'wcap_show_only_license_page' );
					delete_option( 'wcap_edd_license_error' );
				} elseif ( 'invalid' === $license_data->license ) {
					update_option( 'wcap_edd_license_error', $license_data->error );
				}

				// $license_data->license will be either "active" or "inactive"
				update_option( 'edd_sample_license_status_ac_woo', $license_data->license );
				if ( isset( $license_data->expires ) ) {
					update_option( 'edd_sample_license_expires', $license_data->expires );
					update_option( 'edd_sample_license_expires_ac_woo', $license_data->expires );
				}

				if ( 'valid' === $license_data->license ) {
					if ( isset( $license_data->date_created ) && strtotime( $license_data->date_created ) <= strtotime( '2022-04-01' ) ) { // Plans were introduced on Apr 1, 2022.
						update_option( 'wcap_edd_license_type', 'enterprise' );
					} elseif ( isset( $license_data->price_id ) && '' !== $license_data->price_id ) {
						switch ( $license_data->price_id ) {
							case '0':
								update_option( 'wcap_edd_license_type', 'starter' );
								break;
							case '1':
								update_option( 'wcap_edd_license_type', 'business' );
								break;
							case '2':
							default:
								update_option( 'wcap_edd_license_type', 'enterprise' );
								break;
						}
					} else {
						update_option( 'wcap_edd_license_type', 'enterprise' );
					}
				}

				if ( isset( $_POST['action'] ) && $_POST['action'] === 'ac_activate_license' ) {
					delete_option( 'wcap_manually_deactivated' );
					wp_send_json( $license_data->license );
				}
			}
		}

		/**
		 * It will deactivate a license key. This will descrease the site count.
		 *
		 * @since 2.3.1
		 */
		public static function wcap_edd_ac_deactivate_license() {
			// listen for our activate button to be clicked.
			if ( isset( $_POST['edd_ac_license_deactivate'] ) ) {
				// run a quick security check.
				if ( ( ! check_ajax_referer( 'edd_license_nonce', 'edd_license_nonce' ) ) ) {
					return; // get out if we didn't click the Activate button.
				}

				// retrieve the license from the database.
				$license = trim( get_option( 'edd_sample_license_key_ac_woo' ) );

				// data to send in our API request.
				$api_params = array(
					'edd_action' => 'deactivate_license',
					'license'    => $license,
					'item_name'  => urlencode( EDD_SL_ITEM_NAME_AC_WOO ), // the name of our product in EDD.
				);

				// Call the custom API.
				$response = wp_remote_get( add_query_arg( $api_params, EDD_SL_STORE_URL_AC_WOO ), array( 'timeout' => 15, 'sslverify' => false ) );

				// make sure the response came back okay.
				if ( is_wp_error( $response ) ) {
					return false;
				}

				// decode the license data.
				$license_data = json_decode( wp_remote_retrieve_body( $response ) );
				if ( 'failed' === $license_data->license ) {
					$api_params = array(
						'edd_action' => 'deactivate_license',
						'license'    => $license,
						'item_name'  => rawurlencode( EDD_SL_ITEM_NAME_AC_WOO_TRIAL ), // the name of our product in EDD.
					);

					// Call the custom API.
					$response = wp_remote_get(
						esc_url_raw( add_query_arg( $api_params, EDD_SL_STORE_URL_AC_WOO ) ),
						array(
							'timeout'   => 15,
							'sslverify' => false,
						)
					);

					// make sure the response came back okay.
					if ( is_wp_error( $response ) ) {
						return false;
					}

					// decode the license data.
					$license_data = json_decode( wp_remote_retrieve_body( $response ) );
				}

				// $license_data->license will be either "deactivated" or "failed".
				if ( 'deactivated' === $license_data->license ) {
					delete_option( 'edd_sample_license_status_ac_woo' );
					delete_option( 'wcap_edd_license_error' );
					update_option( 'wcap_manually_deactivated', 'yes' );
					update_option( 'wcap_show_only_license_page', 'yes' );
					echo 'invalid';
				}
			}
		}

		/**
		 * This illustrates how to check if a license key is still valid the updater does this for you, so this is only needed if you want to do something custom.
		 *
		 * @since 2.3.1
		 */
		public static function edd_sample_check_license() {

			global $wp_version;

			$license    = trim( get_option( 'edd_sample_license_key_ac_woo' ) );
			$api_params = array(
				'edd_action' => 'check_license',
				'license'    => $license,
				'item_name'  => urlencode( EDD_SL_ITEM_NAME_AC_WOO ),
			);
			// Call the custom API.
			$response = wp_remote_get( add_query_arg( $api_params, EDD_SL_STORE_URL_AC_WOO ), array( 'timeout' => 15, 'sslverify' => false ) );
			if ( is_wp_error( $response ) ) {
				return false;
			}

			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
			if ( 'valid' === $license_data->license ) {
				echo 'valid';
				exit;
			} else {
				echo 'invalid';
				exit;
			}
		}

		/**
		 * This will fetch the current license status.
		 *
		 * @since 7.10.0
		 */
		public static function wcap_edd_get_license_status() {
			return get_option( 'edd_sample_license_status_ac_woo' );
		}
	}

	new Wcap_EDD();
}

