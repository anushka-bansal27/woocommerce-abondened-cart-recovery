<?php
/**
 * This file is part of Tyche.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This is the URL our updater / license checker pings. This should be the URL of the site with EDD installed.
 * IMPORTANT: change the name of this constant to something unique to prevent conflicts with other plugins using this system.
 */
define( 'EDD_SL_STORE_URL_AC_WOO', 'https://www.tychesoftwares.com/' );

/**
 * The name of your product. This is the title of your product in EDD and should match the download title in EDD exactly.
 * IMPORTANT: change the name of this constant to something unique to prevent conflicts with other plugins using this system.
 */
define( 'EDD_SL_ITEM_NAME_AC_WOO', 'Abandoned Cart Pro for WooCommerce' );

if ( ! class_exists( 'EDD_AC_WOO_Plugin_Updater' ) ) {
	// load our custom updater if it doesn't already exist.
	include dirname( __DIR__ ) . '/plugin-updates/EDD_AC_WOO_Plugin_Updater.php';
}
/**
 * Retrieve our license key from the DB
 */
$license_key = trim( get_option( 'edd_sample_license_key_ac_woo' ) );
/**
 * Setup the updater
 */
$edd_updater = new EDD_AC_WOO_Plugin_Updater(
	EDD_SL_STORE_URL_AC_WOO,
	WCAP_PLUGIN_FILE,
	array(
		'version'   => '10.8.0',                    // current version number.
		'license'   => $license_key,                // license key (used get_option above to retrieve from DB).
		'item_name' => EDD_SL_ITEM_NAME_AC_WOO,     // name of this plugin.
		'author'    => 'Ashok Rane',                // author of this plugin.
	)
);

/**
 * Tyche License Class
 *
 * @package Tyche
 * @since 1.0.0
 */
class WCAP_License {

	/**
	 * Constructor
	 */
	public function __construct() {
		$show_features = true;

		// No license data is entered.
		$license_key = trim( get_option( 'edd_sample_license_key_ac_woo', '' ) );
		if ( '' === $license_key ) {
			add_action( 'after_plugin_row_woocommerce-abandon-cart-pro/woocommerce-ac.php', array( __CLASS__, 'wcap_plugin_update_section_notice_for_enter_license' ), 10, 2 );
			$show_features = false;
			update_option( 'wcap_show_only_license_page', 'yes' );
		}

		// Already entered data for trial version.
		$trial       = get_option( 'wcap_edd_license_download_type', '' );
		$expiry_date = get_option( 'edd_sample_license_expires_ac_woo', gmdate( 'Y-m-d', strtotime( 'yesterday' ) ) );
		if ( ! is_numeric( $expiry_date ) ) {
			$expiry_date = strtotime( $expiry_date );
		}
		$current_utc_time = strtotime( current_time( 'mysql', true ) );
		if ( '' !== $expiry_date && $expiry_date < $current_utc_time ) {
			if ( '' === get_option( 'wcap_manually_deactivated', '' ) && $show_features && 'trial' === $trial ) {
				$license_data = self::wcap_check_if_license_renewed();

				if ( false !== $license_data && isset( $license_data->license ) && 'invalid' !== $license_data->license ) {

					$show_notice = false;
					if ( isset( $license_data->success ) && $license_data->success ) {

						if ( isset( $license_data->license ) && 'expired' === $license_data->license ) {
							$show_notice = true;
							update_option( 'edd_sample_license_status_ac_woo', 'expired' );
							update_option( 'wcap_show_only_license_page', 'yes' );
						} else if ( isset( $license_data->expires ) && 'lifetime' !== $license_data->expires ) {
							$expiry_date = $license_data->expires;
							if ( ! is_numeric( $expiry_date ) ) {
								$expiry_date = strtotime( $expiry_date );
								if ( '' !== $expiry_date && $expiry_date < $current_utc_time ) {
									$show_notice = true;
									update_option( 'edd_sample_license_status_ac_woo', 'expired' );
									update_option( 'wcap_show_only_license_page', 'yes' );
								}
							}
						} else {
							$api_params = array(
								'edd_action' => 'activate_license',
								'license'    => $license_key,
								'item_name'  => rawurlencode( EDD_SL_ITEM_NAME_AC_WOO_TRIAL ),
							);

							// Call the custom API.
							wp_remote_get(
								esc_url_raw( add_query_arg( $api_params, EDD_SL_STORE_URL_AC_WOO ) ), // @todo - change url to live site url constant.
								array(
									'timeout'   => 15,
									'sslverify' => false,
								)
							);
							update_option( 'edd_sample_license_status_ac_woo', 'valid' );
							delete_option( 'wcap_show_only_license_page' );
							delete_option( 'wcap_edd_license_error' );
						}
					} else {
						$show_notice = true;
					}
					if ( $show_notice ) {
						add_action( 'after_plugin_row_woocommerce-abandon-cart-pro/woocommerce-ac.php', array( __CLASS__, 'wcap_plugin_update_section_notice_for_expired_license' ), 10, 2 );
						$show_features = false;
						update_option( 'wcap_show_only_license_page', 'yes' );
					}
				}
			} elseif ( isset( $_GET['page'] ) && 'woocommerce_ac_page' === $_GET['page']  ) { // phpcs:ignore.
				$license_data = self::wcap_check_if_license_renewed();
				if ( isset( $license_data->success ) && $license_data->success ) {

					if ( isset( $license_data->license ) && 'expired' === $license_data->license ) {
						update_option( 'edd_sample_license_status_ac_woo', 'expired' );
						update_option( 'wcap_show_only_license_page', 'yes' );
						update_option( 'wcap_edd_license_error', 'expired' );
					}
				}
			}
		}

		add_action( 'admin_init', array( __CLASS__, 'wcap_redirect_to_license_page_after_activation' ), 20 );
		add_action( 'wcap_after_load_hooks', array( $this, 'wcap_load_hooks_and_filters' ) );
	}

	/**
	 * This function redirect the user license page.
	 *
	 * @since 8.7
	 */
	public static function wcap_redirect_to_license_page_after_activation() {

		$license_key = trim( get_option( 'edd_sample_license_key_ac_woo', '' ) );
		if ( '' === $license_key || ( '' !== $license_key && 'valid' !== get_option( 'edd_sample_license_status_ac_woo', '' ) ) ) {
			if ( isset( $_GET['page'] ) && 'woocommerce_ac_page' === $_GET['page'] ) { // phpcs:ignore.
				if ( isset( $_GET['action'] ) && '' != $_GET['action'] ) { // phpcs:ignore.
					if ( 'onboarding' !== $_GET['action'] ) {  // phpcs:ignore
						// @todo: if onboarding added then add redirect url here.
					}
				}
			}

			if ( ! isset( $_GET['action'] ) || isset( $_GET['action'] ) && 'onboarding' !== $_GET['action'] ) {  // phpcs:ignore
				if ( get_transient( 'wcap_redirect_to_license_page' ) ) {
					delete_transient( 'wcap_redirect_to_license_page' );
					$license_page_url = admin_url( 'admin.php?page=woocommerce_ac_page&action=emailsettings#/license' );
					wp_safe_redirect( $license_page_url );
					exit();
				}
			}
		}
	}

	/**
	 * This function will check if the trial download license is renewed or not.
	 *
	 * @since 8.7
	 */
	public static function wcap_check_if_license_renewed() {

		$license = trim( get_option( 'edd_sample_license_key_ac_woo', '' ) );
		if ( '' !== $license ) {
			$item_id = EDD_SL_STORE_AC_TRIAL_DOWNLOAD_ID;

			// data to send in our API request.
			$api_params = array(
				'edd_action' => 'check_license',
				'license'    => $license,
				'item_id'    => $item_id, // the name of our product in EDD.
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
			if ( isset( $license_data->expires ) ) {
				update_option( 'edd_sample_license_expires_ac_woo', $license_data->expires );
			}

			return $license_data;
		}
		return false;
	}

	/**
	 * This function will add notice in the plugin update message section for the license expiry.
	 *
	 * @param string $file File.
	 * @param string $plugin Plugin.
	 * @since 8.7
	 */
	public static function wcap_plugin_update_section_notice_for_expired_license( $file, $plugin ) {

		$license_key    = trim( get_option( 'edd_sample_license_key_ac_woo' ) );
		$item_id        = EDD_SL_STORE_AC_TRIAL_DOWNLOAD_ID;
		$renew_link     = add_query_arg(
			array(
				'edd_license_key' => $license_key,
				'download_id'     => $item_id,
			),
			EDD_SL_STORE_URL_AC_WOO . 'checkout'
		);
		$message        = sprintf( __( 'Your License of Abandoned Cart Pro Trial is expired. Purchase or Renew the license from <a href="%s" target="_blank">here</a> to utilize the pro plugin features.', 'woocommerce-ac' ), $renew_link ); // phpcs:ignore
		$expiry_message = '<tr class="plugin-update-tr">
		<td colspan="3" class="plugin-update colspanchange">
			<div class="notice inline notice-error notice-alt">
				<p>' . $message . '</p>
			</div>
		</td>
		</tr>';

		echo $expiry_message; // phpcs:ignore
	}

	/**
	 * This function will add notice in plugin udpate notice section for entering the license data.
	 *
	 * @since 8.7
	 */
	public static function wcap_plugin_update_section_notice_for_enter_license() {

		$license_page_link     = admin_url( 'admin.php?page=woocommerce_ac_page&action=emailsettings#/license' );
		$message               = sprintf( __( 'You have not entered the license data. Kindly enter your license data <a href="%s">here</a> to use the plugin functionality.', 'woocommerce-ac' ), $license_page_link ); // phpcs:ignore
		$enter_license_message = '<tr class="plugin-update-tr">
		<td colspan="3" class="plugin-update colspanchange">
			<div class="notice inline notice-error notice-alt">
				<p>' . $message . '</p>
			</div>
		</td>
		</tr>';

		echo $enter_license_message; // phpcs:ignore
	}

	public function wcap_load_hooks_and_filters() {
		add_action( 'wp_ajax_ac_deactivate_license', array( 'Wcap_EDD', 'wcap_edd_ac_deactivate_license' ) );
		add_action( 'wp_ajax_ac_activate_license', array( 'Wcap_EDD', 'wcap_edd_ac_activate_license' ) );
	}
}

// Initialize the Tyche License class.
$wcap_license = new WCAP_License();
