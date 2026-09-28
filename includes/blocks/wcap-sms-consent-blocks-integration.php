<?php
/**
 * WC Checkout Blocks Integration - SMS Consent.
 *
 * @package WooCommerce Abandon Cart Pro
 */

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

define( 'WCAP_SMS_CONSENT_VERSION', '0.1.0' );

/**
 * Class for integrating with WooCommerce Blocks
 */
class Wcap_SMS_Consent_Blocks_Integration implements IntegrationInterface {

	/**
	 * The name of the integration.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'wcap_sms_consent';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {
		$this->register_wcap_block_frontend_scripts();
		$this->register_wcap_block_editor_scripts();
		$this->register_wcap_block_editor_styles();
		$this->register_main_integration();
	}

	/**
	 * Registers the main JS file required to add filters and Slot/Fills.
	 */
	public function register_main_integration() {

		$script_path = '/build/index.js';

		$script_url = esc_url( WCAP_PLUGIN_URL ) . $script_path;

		$script_asset_path = WCAP_PLUGIN_PATH . '/build/index.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => $this->get_file_version( $script_path ),
			);

		wp_register_script(
			'wcap-blocks-integration',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
		wp_set_script_translations(
			'wcap-blocks-integration',
			'wcap_params',
			WCAP_PLUGIN_PATH . '/languages'
		);

	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return array( 'wcap-blocks-integration', 'wcap-sms-consent-block-frontend' );
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array();
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data() {
		$wcap_sms_consent_msg = get_option( 'wcap_sms_consent_msg', '' );
		$wcap_sms_consent_msg = '' !== $wcap_sms_consent_msg ? $wcap_sms_consent_msg : __( 'Saving your phone and cart details helps us keep you up to date with this order.', 'woocommerce-ac' );
		$wcap_sms_consent_msg = apply_filters( 'wcap_sms_consent_text', $wcap_sms_consent_msg );
		$display_consent_box  = apply_filters( 'wcap_display_sms_consent_box', true );

		$data = array(
			'wcap_sms_consent' => $display_consent_box,
			'optInDefaultText' => $wcap_sms_consent_msg,
		);

		return $data;

	}

	/**
	 * Register style files for editor block - admin.
	 */
	public function register_wcap_block_editor_styles() {
	}

	/**
	 * Register scripts for block editing - admin.
	 */
	public function register_wcap_block_editor_scripts() {
	}

	/**
	 * Register frontend scripts.
	 */
	public function register_wcap_block_frontend_scripts() {

		$script_path       = '/build/blocks-sms-consent-frontend.js';
		$script_url        = esc_url( WCAP_PLUGIN_URL ) . $script_path;
		$script_asset_path = WCAP_PLUGIN_PATH . '/build/blocks-sms-consent-frontend.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => $this->get_file_version( $script_asset_path ),
			);

		wp_register_script(
			'wcap-sms-consent-block-frontend',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
		wp_set_script_translations(
			'wcap-sms-consent-block-frontend', // script handle.
			'woocommerce-ac', // text domain.
			WCAP_PLUGIN_PATH . '/languages'
		);
	}

	/**
	 * Get the file modified time as a cache buster if we're in dev mode.
	 *
	 * @param string $file Local path to the file.
	 * @return string The cache buster value to use for the given file.
	 */
	protected function get_file_version( $file ) {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $file ) ) {
			return filemtime( $file );
		}
		return WCAP_SMS_CONSENT_VERSION;
	}
}
