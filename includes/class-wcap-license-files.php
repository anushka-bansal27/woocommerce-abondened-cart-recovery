<?php
/**
 * Class for handling license-related functionalities.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Wcap_License_Files' ) ) {

	class Wcap_License_Files {

		/**
		 * Constructor
		 */
		public function __construct() {
			require_once WCAP_PLUGIN_PATH . '/tyche/component/plugin-license/wcap_edd.php';
			require_once WCAP_PLUGIN_PATH . '/tyche/component/plugin-license/class-wcap-license.php';

			if ( class_exists( 'Wcap_EDD' ) ) {
				add_action( 'admin_init', array( 'Wcap_EDD', 'wcap_edd_ac_register_option' ) );
				add_action( 'admin_init', array( 'Wcap_EDD', 'wcap_edd_ac_deactivate_license' ) );
				add_action( 'admin_init', array( 'Wcap_EDD', 'wcap_edd_ac_activate_license' ) );
			}
		}
	}

	new Wcap_License_Files();
}
