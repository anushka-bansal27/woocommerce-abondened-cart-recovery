<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Active License Notice class
 *
 * @class wcap_active_license_notice
 * @version 1.0
 */
class wcap_active_license_notice {

	/**
	 * The name of the plugin
	 *
	 * @var string
	 * @access public
	 */
	public $plugin_name = '';

	/**
	 * The option name where the license key is stored
	 *
	 * @var string
	 * @access public
	 */
	public $plugin_license_option = '';

	/**
	 * Store the path of the license page.
	 *
	 * @var string Path of the license page.
	 * @access public
	 */
	public $ts_license_page_url = '';

	/**
	 * Store the plguin locale.
	 *
	 * @var string Used Plugin locale.
	 * @access public
	 */
	public $ts_locale = '';

	/**
	 * Check if license expires.
	 *
	 * @var bool if license expires.
	 * @access public
	 */
	public $plugin_license_expires = '';

	/**
	 * Default Constructor
	 *
	 * @access public
	 * @since  7.7
	 */
	public function __construct( $ts_plugin_name = '', $ts_license_option_name = '', $ts_license_page_url = '', $ts_locale = '', $ts_license_expires = '') {
		$this->plugin_name           = $ts_plugin_name;
		$this->plugin_license_option = $ts_license_option_name;
		$this->ts_license_page_url   = $ts_license_page_url;
		$this->ts_locale             = $ts_locale;

		$this->plugin_license_expires = $ts_license_expires;

		if ( '' !== $this->plugin_license_option ) {
			add_action( 'admin_init', array( &$this, 'ts_check_if_license_active' ) );
			add_action( 'admin_init', array( &$this, 'ts_check_if_license_expired' ) );
		}

		add_action( 'admin_enqueue_scripts', array( &$this, 'ts_custom_notice_style' ) );
	}

	/**
	 * Check if the license key is active for the plugin. If not active a notice will be displayed.
	 *
	 * @access public
	 * @since 7.7
	 */
	public function ts_check_if_license_active() {
		if ( ! $this->ts_check_active_license() && ! $this->ts_check_license_expire() ) {
			add_action( 'admin_notices', array( &$this, 'ts_license_active_notice' ) );
		}
	}

	/**
	 * Returns the result of the license key.
	 *
	 * @access public
	 * @return bool
	 * @since  7.7
	 */
	public function ts_check_active_license() {
		$status = get_option( $this->plugin_license_option );

		switch ( $status ) {
			case 'valid':
				return true;
			case 'invalid':
			case 'expired':
				return false;
			default:
				return false;
		}
		if ( false !== $status && 'valid' === $status ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 *  Display the notice if the license key is not active
	 *
	 * @access public
	 * @since 7.7
	 */
	public function ts_license_active_notice() {

		global $current_screen;

		$current_screen = get_current_screen(); //phpcs:ignore
		if ( ( method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() )
			|| ( function_exists( 'is_gutenberg_page' ) && is_gutenberg_page() ) ) {
			return;
		}

		$show = ( isset( $_GET['action'] ) && 'upload-plugin' === $_GET['action'] ) ? false : true; // phpcs:ignore.
		if ( get_post_type() !== 'page' && get_post_type() !== 'post' &&  ! ( isset( $_GET['action'] ) && 'onboarding' === $_GET['action'] ) && $show ) { // phpcs:ignore
			// $message = __( 'We have noticed that the license for <b>' . $this->plugin_name . '</b> plugin is not active. To receive automatic updates & support, please activate the license <a href= "'. $this->ts_license_page_url .'">here</a>.', "'. $this->ts_locale .'"  );
			
			if ( ! isset( $_GET['action'] ) && ! ( isset( $_GET['action'] ) && 'emailsettings' === $_GET['action'] ) ) { // phpcs:ignore
				/* translators: 1%s: Plugin name, 2%s: License Page URL */
				$message = sprintf( __( 'Your WooCommerce store is detected with a critical error! You missed activating the license for %1$s. <a href="%2$s" class="button button-secondary">Activate License</a>', 'order-delivery-date' ), $this->plugin_name, $this->ts_license_page_url );
				?>
				<div class="wcap-message notice">
					<div class="wcap-content">
						<img class="wcap-site-logo" src="<?php echo esc_url( plugins_url( '/woocommerce-abandon-cart-pro/assets/images/tyche-logo.png' ) ); ?> ">
						<p><?php echo $message; //phpcs:ignore ?></p>
					</div>
				</div>
				<?php
			}
		}
	}

	/**
	 * Check if the license has expired
	 *
	 * @access public
	 * @since  7.7
	 */
	public function ts_check_if_license_expired() {

		if ( $this->ts_check_license_expire() ) {
			add_action( 'admin_notices', array( &$this, 'ts_license_expiry_notice' ) );
		}
	}

	/**
	 * Returns the result of the license expiry
	 *
	 * @access public
	 * @return bool
	 * @since  7.7
	 */
	public function ts_check_license_expire() {
		$status = get_option( $this->plugin_license_option );

		switch ( $status ) {
			case 'invalid':
			case 'expired':
				return true;
			default:
				return false;
		}
	}

	/**
	 * Returns the result of the license expiry
	 *
	 * @access public
	 * @return bool
	 * @since  7.7
	 */
	public function ts_check_license_expired() {
		$plugin_license_expires = get_option( $this->plugin_license_expires );

		if ( 'lifetime' === $plugin_license_expires ) {
			return false;
		}

		$expiry_date = new DateTime( $plugin_license_expires );
		$today       = new DateTime( gmdate( 'Y-m-d' ) );

		if ( $today > $expiry_date ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Display the notice if the license key is expired.
	 *
	 * @access public
	 * @since 7.7
	 */
	public function ts_license_expiry_notice() {



		$current_screen = get_current_screen();
		if ( ( method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() )
			|| ( function_exists( 'is_gutenberg_page' ) && is_gutenberg_page() ) ) {
			return;
		}

		$show        = ( isset( $_GET['action'] ) && 'upload-plugin' === $_GET['action'] ) ? false : true; // phpcs:ignore.
		$license_key = trim( get_option( 'edd_sample_license_key_ac_woo', '' ) );
		if ( '' !== $license_key ) {
			$item_id    = EDD_SL_STORE_AC_TRIAL_DOWNLOAD_ID;
			$renew_link = add_query_arg(
				array(
					'edd_license_key' => $license_key,
					'download_id'     => $item_id,
				),
				EDD_SL_STORE_URL_AC_WOO . 'checkout'
			);

			if ( $show ) {

				// $message = __( 'We have noticed that the license for <b>' . $this->plugin_name . '</b> plugin is expired. To receive automatic updates & support, please renew the license  <a href="https://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro/" target="_blank">here</a> and update the license <a href= "'. $this->ts_license_page_url .'">here</a>.', "'. $this->ts_locale .'"  );
				/* translators: %s: Renew Plugin Link */
				$message = sprintf( __( 'Your Woo store is losing its WOW factor. Your Abandoned Cart Pro for WooCommerce license has expired. <a href="%s" class="button" target="_blank">Renew Now</a>', 'order-delivery-date' ), $renew_link );
				?>
				<div class="wcap-message notice">
					<div class="wcap-content">
						<img class="wcap-site-logo" src="<?php echo esc_url( plugins_url( '/woocommerce-abandon-cart-pro/assets/images/tyche-logo.png' ) ); ?> ">
						<p><?php echo $message; //phpcs:ignore ?></p>
					</div>
				</div>
				<?php
			}
		}
	}

	/**
	 * Function to add the css file for styling the notice from plugin.
	 *
	 * @access public
	 * @since  7.7
	 * @return void
	 */
	public function ts_custom_notice_style() {

		wp_enqueue_style(
			'wcap-custom-notice',
			plugins_url( '/woocommerce-abandon-cart-pro/assets/css/admin/wcap_notices.css' ),
			'',
			WCAP_PLUGIN_VERSION
		);
	}
}
