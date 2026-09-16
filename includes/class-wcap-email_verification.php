<?php
/**
 * This file will add functions related to verifying email present on ATC field.
 *
 * @author  Tyche Softwares
 * @package Abandoned-Cart-Pro-for-WooCommerce/Admin/ATC
 * @since 8.8.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wcap_Email_Verification' ) ) {

	/**
	 * Email Verification Class
	 */
	class Wcap_Email_Verification {

		/**
		 * Contructor.
		 */
		public function __construct() {

			add_filter( 'wcap_popup_params', array( &$this, 'wcap_add_api_param' ) );
		}


		/**
		 * Add Localize param to ATC
		 *
		 * @param array $localize_params Localize Params.
		 * @return array
		 */
		public function wcap_add_api_param( $localize_params ) {

			if ( 'on' === get_option( 'wcap_enable_debounce', '' ) && '' !== get_option( 'ac_debounce_api' ) ) {
				$localize_params['wcap_debounce_key'] = get_option( 'ac_debounce_api' );
			}
			return $localize_params;
		}
	}
}

return new Wcap_Email_Verification();
