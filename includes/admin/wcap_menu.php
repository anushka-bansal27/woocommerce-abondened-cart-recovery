<?php
/**
 * It will display the menu of the Abadoned cart.
 *
 * @author   LittleCodr
 * @package Abandoned-Cart-Pro-for-WooCommerce/Admin/Menu
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Wcap_Menu' ) ) {
	/**
	 * It will display the menu of the Abadoned cart.
	 */
	class Wcap_Menu {

		/**
		 * It will add the 'Abandoned Carts' as the sub menu under the WooCommerce menu.
		 *
		 * @hook admin_menu
		 * @since 1.0
		 */
		public static function wcap_admin_menu() {

			$page = add_submenu_page(
				'woocommerce',
				__( 'Abandoned Carts', 'woocommerce-ac' ),
				__( 'Abandoned Carts', 'woocommerce-ac' ),
				'manage_woocommerce', // phpcs:ignore
				'woocommerce_ac_page',
				array( 'Wcap_Menu', 'wcap_menu_page' )
			);
		}

		/**
		 * It is the call back for the Abandon Cart Page.
		 * It will display all the tabs of the plugin.
		 *
		 * @since 1.0
		 */
		public static function wcap_menu_page() {

			do_action( 'wcap_admin_menu_page' );

			if ( is_user_logged_in() ) {
				Wcap_Common::wcap_check_user_can_manage_woocommerce();
				?>
				<div class="wrap">
				<h2>
					<?php esc_attr_e( 'Abandoned Cart', 'woocommerce-ac' ); ?>
				</h2>
				<?php

				$action     = Wcap_Common::wcap_get_action();
				$mode       = Wcap_Common::wcap_get_mode();
				$action_two = Wcap_Common::wcap_get_action_two();
				$section    = Wcap_Common::wcap_get_section();

				$wcap_action  = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
				$wcap_section = isset( $_GET['wcap_section'] ) ? sanitize_text_field( wp_unslash( $_GET['wcap_section'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

				do_action( 'wcap_display_message' );

				self::wcap_display_tabs();

				do_action( 'wcap_crm_data' );
				do_action( 'wcap_add_tab_content' );

				if ( 'wcap_atc_settings' === $section && ( 'copytemplate' === $mode || 'edittemplate' === $mode || 'addnewtemplate' === $mode ) ) {

					$id                = isset( $_GET['id'] ) ? sanitize_text_field( wp_unslash( $_GET['id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
					$get_template      = array();
					$frontend_settings = new stdClass();
					$coupon_settings   = new stdClass();
					$quick_ck_settings = new stdClass();
					$rules             = array();
					$save_mode         = 'save';
					$mode              = isset( $_GET['mode'] ) ? sanitize_text_field( wp_unslash( $_GET['mode'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
					if ( $id > 0 ) {
						$get_template = wcap_get_atc_template( sanitize_text_field( wp_unslash( $_GET['id'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
						if ( false !== $get_template ) {
							$frontend_settings = json_decode( $get_template->frontend_settings );
							$coupon_settings   = json_decode( $get_template->coupon_settings );
							$rules             = json_decode( $get_template->rules );
							$quick_ck_settings = isset( $get_template->quick_checkout_settings ) ? json_decode( $get_template->quick_checkout_settings ) : $quick_ck_settings;
						}
						$save_mode = 'edittemplate' === $mode ? 'update' : $save_mode;
					}
					$template_settings = wcap_get_atc_template_preview( $id );

					ob_start();
					wc_get_template(
						'add_edit.php',
						array(
							'template_settings' => $template_settings,
						),
						'woocommerce-abandon-cart-pro/',
						WCAP_PLUGIN_PATH . '/includes/admin/views/settings/popup_templates/'
					);
					echo ob_get_clean(); // phpcs:ignore
					return;
				}

				Wcap_Actions::wcap_perform_action( $action, $action_two );

				if ( 'emailsettings' === $action ) {
					include_once 'views/settings/index.php';
					return;
				} elseif ( 'wcap_dashboard_advanced' === $action || '' === $action ) {
					include_once 'views/dashboard/index.php';
					return;
				} elseif ( 'listcart' === $action && ( ! isset( $_GET['action_details'] ) ) // phpcs:ignore
					&& ( ! isset( $_GET['wcap_download'] ) || 'wcap.csv' != $_GET['wcap_download'] ) // phpcs:ignore
					&& ( ! isset( $_GET['wcap_download'] ) || 'wcap.print' != $_GET['wcap_download'] ) // phpcs:ignore
					) {
					include_once 'views/abandoned_orders/index.php';
					return;
				} elseif ( 'cart_recovery' === $action && ( 'edittemplate' !== $mode && 'addnewtemplate' !== $mode && 'copytemplate' !== $mode && 'wcap_manual_email' !== $mode ) ) {
					include_once 'views/templates/index.php';
					return;
				} elseif ( 'emailstats' === $action ) {
					include_once 'views/reminders/index.php';
					return;
				}
				if ( 'cart_recovery' === $action && ( 'emailtemplates' === $section ) && ( 'addnewtemplate' === $mode || 'edittemplate' === $mode || 'copytemplate' === $mode ) ) {

					$template_settings = Wcap_Email_Template_Fields::wcap_get_email_template_fields();
					ob_start();
					wc_get_template(
						'add_edit_email_template.php',
						array(
							'template_settings' => $template_settings,
						),
						'woocommerce-abandon-cart-pro/',
						WCAP_PLUGIN_PATH . '/includes/admin/views/templates/'
					);
					echo ob_get_clean(); // phpcs:ignore
					return;
				} elseif ( 'cart_recovery' === $action && 'emailtemplates' === $section && 'wcap_manual_email' === $mode ) {

					$template_settings = Wcap_Email_Template_Fields::wcap_get_email_template_fields();
					ob_start();
					wc_get_template(
						'send_email.php',
						array(
							'template_settings' => $template_settings,
						),
						'woocommerce-abandon-cart-pro/',
						WCAP_PLUGIN_PATH . '/includes/admin/views/abandoned_orders/'
					);
					echo ob_get_clean(); // phpcs:ignore
					return;
				} elseif ( 'report' === $action ) {
					include_once 'views/reports/index.php';
					return;
				}
				echo '</table>';
			}
		}
		/**
		 * It will display all the tabs of the plugin.
		 *
		 * @since 1.0
		 */
		public static function wcap_display_tabs() {
			$action = Wcap_Common::wcap_get_action();

			$active_wcap_dashboard = '';
			$active_dashboard_adv  = '';
			$active_listcart       = '';
			$active_cart_recovery  = '';
			$active_settings       = '';
			$active_stats          = '';

			switch ( $action ) {
				case 'wcap_dashboard':
					$active_wcap_dashboard = 'active';
					break;
				case 'wcap_dashboard_advanced':
				case '':
					$active_dashboard_adv = 'active';
					break;
				case 'listcart':
					$active_listcart = 'active';
					break;
				case 'cart_recovery':
					$active_cart_recovery = 'active';
					break;
				case 'sms':
					$active_sms = 'active';
					break;
				case 'emailsettings':
					$active_settings = 'active';
					break;
				case 'emailstats':
					$active_emailstats = 'active';
					break;
				case 'report':
					$active_report = 'active';
					break;
			}
			?>

			<div style="background-image: url( '<?php echo plugins_url(); // phpcs:ignore ?>/woocommerce-abandon-cart-pro/assets/images/ac_tab_icon.png' ) !important;" class="icon32">
			<br>
			</div>
		
			</h2>
			
			<?php
			include_once 'views/ac-header.php';
		}
	}
}
