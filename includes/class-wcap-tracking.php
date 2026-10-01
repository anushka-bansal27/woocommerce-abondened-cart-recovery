<?php
/**
 * Class for handling tracking functionalities.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Wcap_Tracking' ) ) {

	class Wcap_Tracking {

		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'wcap_after_load_files', array( $this, 'wcap_load_tracking' ) );
		}

		public function wcap_load_tracking() {
			/**
			* Files needed for Components folder
			*/
			require_once WCAP_PLUGIN_PATH . '/LittleCodr/component/license-active-notice/ts-active-license-notice.php';
			require_once WCAP_PLUGIN_PATH . '/LittleCodr/component/plugin-deactivation/class-LittleCodr-plugin-deactivation.php';
			new LittleCodr_Plugin_Deactivation(
				array(
					'plugin_name'       => 'Abandoned Cart Pro for WooCommerce',
					'plugin_base'       => 'woocommerce-abandon-cart-pro/woocommerce-ac.php',
					'script_file'       => WCAP_PLUGIN_URL . '/LittleCodr/assets/plugin-deactivation.js',
					'plugin_short_name' => 'acp_pro',
					'version'           => WCAP_PLUGIN_VERSION,
					'plugin_locale'     => 'woocommerce-ac',
				)
			);
			require_once WCAP_PLUGIN_PATH . '/LittleCodr/component/plugin-tracking/class-LittleCodr-plugin-tracking.php';

			new LittleCodr_Plugin_Tracking(
				array(
					'plugin_name'       => 'Abandoned Cart Pro for WooCommerce',
					'plugin_locale'     => 'woocommerce-ac',
					'plugin_short_name' => 'wcap',
					'version'           => WCAP_PLUGIN_VERSION,
					'blog_link'         => 'https://github.com/LittleCodr',
				)
			);
			require_once WCAP_PLUGIN_PATH . '/LittleCodr/component/plugin-tracking/class-wcap-data-tracking.php';

			if ( is_admin() ) {
				$wcap_plugin_name        = 'Abandoned Cart Pro for WooCommerce';
				$wcap_edd_license_option = 'edd_sample_license_status_ac_woo';
				$wcap_license_path       = 'admin.php?page=woocommerce_ac_page&action=emailsettings#/license';
				$wcap_locale             = 'woocommerce-ac';
				$wcap_edd_license_expires = 'edd_sample_license_expires';

				new wcap_active_license_notice( $wcap_plugin_name, $wcap_edd_license_option, $wcap_license_path, $wcap_locale, $wcap_edd_license_expires );

			}
		}
	}

	new Wcap_Tracking();
}