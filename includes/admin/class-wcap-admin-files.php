<?php
/**
 * Abandoned Cart Pro for WooCommerce.
 *
 * Class for including WCAP files for the Admin.
 *
 * @author      Tyche Softwares
 * @package     WCAP/Admin/Files
 * @category    Classes
 * @since       5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCAP Admin Files.
 *
 * @since 5.0
 */
class WCAP_Admin_Files {

	/**
	 * Construct
	 *
	 * @since 5.0
	 */
	public function __construct() {
		$this->include_files();
	}

	/**
	 * Include files.
	 *
	 * @since 5.0
	 */
	public function include_files() {

		global $pagenow;
		$is_admin = is_admin();

		if ( true === $is_admin ) {

			WCAP_Files::include_file( WCAP_PLUGIN_PATH . '/includes/admin/wcap_menu.php' );

			WCAP_Files::include_file( WCAP_PLUGIN_PATH . '/includes/admin/class-wcap-abandoned-cart-details.php' );

			WCAP_Files::include_file( WCAP_PLUGIN_PATH . '/includes/admin/wcap_add_cart_popup_modal.php' );

			WCAP_Files::include_file( WCAP_PLUGIN_PATH . '/includes/admin/wcap_privacy_export.php' );

			WCAP_Files::include_file( WCAP_PLUGIN_PATH . '/includes/admin/wcap_privacy_erase.php' );
		}

		if ( true === $is_admin && (//phpcs:disable
			( isset( $_GET['page'] ) && 'woocommerce_ac_page' === $_GET['page'] ) ||
			( isset( $_POST['option_page'] ) &&
			( 'woocommerce_ac_settings' === $_POST['option_page'] ||
			'woocommerce_sms_settings' === $_POST['option_page'] ||
			'woocommerce_ac_email_reports_frequency_settings' === $_POST['option_page'] )
			)
			)
			) {//phpcs:enable
			self::wcap_load_admin_side_files();

			/**
			 * Load class files.
			 */
			self::wcap_load_support_class_files();
		} elseif ( true === $is_admin && ( 'index.php' === $pagenow || 'admin-ajax.php' === $pagenow ) ) {
			self::wcap_load_dashboard_widget_files();
		}

		self::include_api_files();
	}

	/**
	 * Include API files.
	 *
	 * @since 5.0
	 */
	public function include_api_files() {
	}

	/**
	 * Loads an Admin View File.
	 *
	 * @param string $filename View File to be loaded.
	 * @since 5.0
	 */
	public static function load_view_file( $filename ) {
		WCAP_Files::include_file( WCAP_PLUGIN_PATH . '/includes/admin/views/' . $filename . '.php' );
	}

	/**
	 * Loads an Admin Section File.
	 *
	 * @param string $section Section Directory.
	 * @param string $filename File in the section Directory to be loaded.
	 * @since 5.19.0
	 */
	public static function load_section_file( $section, $filename = '' ) {

		if ( '' === $section ) {
			return;
		}

		$section_dir = WCAP_PLUGIN_PATH . '/includes/admin/views/' . $section;
		$file        = $section_dir . '/' . ( '' === $filename ? 'index.php' : self::do_file_check( $filename ) );

		WCAP_Files::include_file( $file );
	}

	/**
	 * Loads an Admin Page.
	 *
	 * @param string $section Section Directory.
	 * @param array  $pages Admin Pagesto be loaded.
	 * @param bool   $load_sub_navigation Whether to load the sub-navigation bar.
	 * @since 5.0.0
	 */
	public static function load_admin_pages( $section, $pages, $load_sub_navigation = true ) {
		self::load_view_file( 'ac-header' );

		foreach ( $pages as $page ) {
			self::load_section_file( $section, $page );
		}

		if ( $load_sub_navigation ) {
			self::load_view_file( 'main' );
		}

		self::load_view_file( 'ac-footer' );
	}

	/**
	 * Load files which needed in the admin side.
	 *
	 * @since 5.0
	 */
	public static function wcap_load_admin_side_files() {
		WCAP_Files::include_file( WCAP_PLUGIN_PATH . '/includes/admin/wcap_actions.php' );
		WCAP_Files::include_file( WCAP_PLUGIN_PATH . '/includes/admin/wcap_email_template_fields.php' );
		WCAP_Files::include_file( WCAP_PLUGIN_PATH . '/includes/admin/wcap_tiny_mce.php' );
		WCAP_Files::include_file( WCAP_PLUGIN_PATH . '/includes/admin/wcap_localization.php' );
	}

	/**
	 * Load the supporting class files.
	 *
	 * @since 5.0
	 */
	public static function wcap_load_support_class_files() {

		WCAP_Files::include_file( WCAP_PLUGIN_PATH . '/includes/classes/class_wcap_manual_email.php' );
		WCAP_Files::include_file( WCAP_PLUGIN_PATH . '/includes/classes/class-wcap-send-manual-email.php' );
		WCAP_Files::include_file( WCAP_PLUGIN_PATH . '/includes/classes/class_wcap_abandoned_orders_table.php' );
		WCAP_Files::include_file( WCAP_PLUGIN_PATH . '/includes/classes/class_wcap_abandoned_trash_orders_table.php' );
		WCAP_Files::include_file( WCAP_PLUGIN_PATH . '/includes/classes/class_wcap_templates_table.php' );
		WCAP_Files::include_file( WCAP_PLUGIN_PATH . '/includes/classes/class_wcap_sent_emails_table.php' );
		WCAP_Files::include_file( WCAP_PLUGIN_PATH . '/includes/classes/class_wcap_product_report_table.php' );
		WCAP_Files::include_file( WCAP_PLUGIN_PATH . '/includes/classes/class_wcap_atc_dashboard.php' );
		WCAP_Files::include_file( WCAP_PLUGIN_PATH . '/includes/classes/class_wcap_sms_templates_table.php' );
		WCAP_Files::include_file( WCAP_PLUGIN_PATH . '/includes/classes/class_wcap_sent_sms_table.php' );
		WCAP_Files::include_file( WCAP_PLUGIN_PATH . '/includes/classes/class-wcap-atc-templates-table.php' );
	}

	/**
	 * Loads the dashboard widget files.
	 *
	 * @since 5.0
	 */
	public static function wcap_load_dashboard_widget_files() {
		WCAP_Files::include_file( WCAP_PLUGIN_PATH . '/includes/admin/wcap_dashboard_widget.php' );
		WCAP_Files::include_file( WCAP_PLUGIN_PATH . '/includes/classes/class_wcap_dashboard_widget_report.php' );
		WCAP_Files::include_file( WCAP_PLUGIN_PATH . '/includes/classes/class_wcap_dashboard_widget_heartbeat.php' );
	}
}
