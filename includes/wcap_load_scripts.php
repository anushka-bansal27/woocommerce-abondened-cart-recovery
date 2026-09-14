<?php
/**
 * Abandoned Cart Pro for WooCommerce
 *
 * This files will load the JavaScript files at front end for Add To Cart Popup Modal and it will also load scripts for migrating the data of LITE version to PRO version at backend.
 *
 * @author   Tyche Softwares
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Classes
 * @category Classes
 * @since    5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Automattic\WooCommerce\StoreApi\StoreApi;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;

if ( ! class_exists( 'Wcap_Load_Scripts' ) ) {
	/**
	 * Load Scripts needed for Plugin.
	 *
	 * @since  5.0
	 */
	class Wcap_Load_Scripts {
		/**
		 * Enqueue Common JS Scripts to be included in Admin Side.
		 *
		 * @hook admin_enqueue_scripts
		 *
		 * @param string $hook Hook suffix for the current admin page.
		 * @globals $pagenow Current page
		 * @since 5.0
		 */
		public static function wcap_enqueue_scripts_js( $hook ) {
			global $pagenow, $woocommerce;

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wcap_copy_atc_popup_images_to_uploads_dir();
			$page   = isset( $_GET['page'] ) ? $_GET['page'] : ''; // phpcs:disable

			wp_enqueue_style( 'font-awesome-all', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.9.0/css/all.min.css' );

			$display_widget = apply_filters( 'wcap_show_admin_widget', true );
			if ( 'index.php' === $pagenow && $display_widget ) {
				wp_enqueue_script( 'wcap_dashboard_widget', esc_url( WCAP_PLUGIN_URL ) . '/assets/js/admin/wcap_dashboard_widget.js', array(), WCAP_PLUGIN_VERSION );
			}
			wp_enqueue_script(
				'wcap-tyche',
				esc_url( WCAP_PLUGIN_URL ) . '/assets/js/tyche.js',
				array(),
				WCAP_PLUGIN_VERSION,
				false
			);
		
			if ( $page === '' || $page !== 'woocommerce_ac_page' ) {
				return;
			} else {
				
				$wc_payment_gateways = new WC_Payment_Gateways();
				$payment_gateways    = $wc_payment_gateways->payment_gateways();
				$available_gateways  = array();
				foreach ( $payment_gateways as $slug => $gateways ) {
					if ( 'yes' === $gateways->enabled ) {
						$available_gateways[ $slug ] = $gateways->title;
					}
				}
				$wc_countries_object     = new WC_Countries();
				$all_countries_list      = $wc_countries_object->get_countries();
				$available_countries[''] = array( __( 'Select countries', 'woocommerce-ac' ) );
				foreach ( $all_countries_list as $code => $name ) {
					$available_countries[ $code ] = $name;
				}
					
				wp_enqueue_script( 'jquery' );
				wp_enqueue_script( 'jquery-ui-core' );

				// Scripts included for woocommerce auto-complete coupons.
				
				wp_enqueue_script( 'wcap_vue_js', esc_url( WCAP_PLUGIN_URL ) . '/assets/js/vue.min.js', '', '', false );
				
				wp_enqueue_script( 'wcap_vue_router', esc_url( WCAP_PLUGIN_URL ) . '/assets/js/vue-router.js',	array( 'jquery', 'jquery-ui-core', 'jquery-ui-datepicker' ));

				wp_enqueue_script( 'wcap_vue_axios', esc_url( WCAP_PLUGIN_URL ) . '/assets/js/axios.min.js' );				
				wp_register_script( 'enhanced', plugins_url() . '/woocommerce/assets/js/admin/wc-enhanced-select.js', array( 'jquery', 'select2' ) );
				
				wp_enqueue_script(
					'bootstrap_js',
					esc_url( WCAP_PLUGIN_URL ) . '/assets/js/admin/bootstrap.min.js',
					array(),
					WCAP_PLUGIN_VERSION,
					false
				);
				if ( 'woocommerce_ac_page' === $page && '' == isset( $_GET['action'] ) ) {
					wp_enqueue_script( 'wcap_acp_dashboard', esc_url( WCAP_PLUGIN_URL ) . '/assets/js/admin/wcap_acp_dashboard.js', array(), WCAP_PLUGIN_VERSION );
					wp_localize_script( 'wcap_acp_dashboard', 'wcap_strings', array( 'wcap_settings_nonce' => wp_create_nonce('wcap_settings_nonce'), ) );
				}
				wp_enqueue_script(
					'wcap-main',
					esc_url( WCAP_PLUGIN_URL ) . '/assets/js/admin/main.js',
					array( 'jquery', 'jquery-ui-core' ),
					WCAP_PLUGIN_VERSION,
					false
				);
				wp_localize_script( 'wcap-main', 'wcap_strings', array( 'wcap_settings_nonce' => wp_create_nonce('wcap_settings_nonce'), ) );
				/**
				 * It is used for the Search coupon new functionality.
				 *
				 * @since: 3.3
				 */
				wp_localize_script(
					'enhanced',
					'wc_enhanced_select_params',
					array(
						'i18n_matches_1'            => _x( 'One result is available, press enter to select it.', 'enhanced select', 'woocommerce-ac' ),
						'i18n_matches_n'            => _x( '%qty% results are available, use up and down arrow keys to navigate.', 'enhanced select', 'woocommerce-ac' ),
						'i18n_no_matches'           => _x( 'No matches found', 'enhanced select', 'woocommerce-ac' ),
						'i18n_ajax_error'           => _x( 'Loading failed', 'enhanced select', 'woocommerce-ac' ),
						'i18n_input_too_short_1'    => _x( 'Please enter 1 or more characters', 'enhanced select', 'woocommerce-ac' ),
						'i18n_input_too_short_n'    => _x( 'Please enter %qty% or more characters', 'enhanced select', 'woocommerce-ac' ),
						'i18n_input_too_long_1'     => _x( 'Please delete 1 character', 'enhanced select', 'woocommerce-ac' ),
						'i18n_input_too_long_n'     => _x( 'Please delete %qty% characters', 'enhanced select', 'woocommerce-ac' ),
						'i18n_selection_too_long_1' => _x( 'You can only select 1 item', 'enhanced select', 'woocommerce-ac' ),
						'i18n_selection_too_long_n' => _x( 'You can only select %qty% items', 'enhanced select', 'woocommerce-ac' ),
						'i18n_load_more'            => _x( 'Loading more results&hellip;', 'enhanced select', 'woocommerce-ac' ),
						'i18n_searching'            => _x( 'Searching&hellip;', 'enhanced select', 'woocommerce-ac' ),
						'ajax_url'                  => esc_url( WCAP_ADMIN_AJAX_URL ),
						'search_products_nonce'     => wp_create_nonce( 'search-products' ),
						'search_customers_nonce'    => wp_create_nonce( 'search-customers' )
					)
				);

				$wc_round_value       = wc_get_price_decimals();
				$wc_currency_position = get_option( 'woocommerce_currency_pos' );

				wp_enqueue_script( 'enhanced' );
				wp_dequeue_script( 'wc-enhanced-select' );

				if ( version_compare( $woocommerce->version, '3.2.0', '>=' ) ) {
					wp_register_script( 'selectWoo', plugins_url() . '/woocommerce/assets/js/selectWoo/selectWoo.full.min.js', array( 'jquery' ) );
					wp_enqueue_script( 'selectWoo' );
				}
				

				wp_register_script( 'select2', plugins_url() . '/woocommerce/assets/js/select2/select2.min.js', array( 'jquery', 'jquery-ui-widget', 'jquery-ui-core' ) );
				wp_enqueue_script( 'select2' );

				$js_src = includes_url( 'js/tinymce/' ) . 'tinymce.min.js';
				wp_enqueue_script( 'tinyMCE_ac', $js_src );
				/*
				 *   When Bulk action is selected without any proper action then this file will be called
				 */
				wp_register_script( 'wcap_choices',	esc_url( WCAP_PLUGIN_URL ) . '/assets/js/admin/choices.min.js', array(), WCAP_PLUGIN_VERSION, true );
				wp_register_script( 'wcap_custom', esc_url( WCAP_PLUGIN_URL ) . '/assets/js/admin/custom.js', array( 'jquery', 'jquery-ui-core' ), WCAP_PLUGIN_VERSION, true );
		

				$action = $action_down = '';
				if ( isset( $_GET['action'] ) ) {
					$action = $_GET['action'];
				}

				if ( isset( $_GET['action2'] ) ) {
					$action_down = $_GET['action2'];
				}

				if ( '-1' == $action && isset( $_GET['wcap_action'] ) ) {
					$action = $_GET['wcap_action'];
				}
				$section      = ( isset( $_GET['section'] ) ) ? $_GET['section'] : '';
				$wcap_section = isset( $_GET['wcap_section'] ) ? sanitize_text_field( wp_unslash( $_GET['wcap_section'] ) ) : '';
				$suffix       = '';
				$mode = Wcap_Common::wcap_get_mode();
				
				if ( 'emailsettings' === $action && ! isset($_GET[ 'mode' ] ) ) {
					wp_register_script( 'wcap_settings', esc_url( WCAP_PLUGIN_URL ) . '/assets/js/admin/wcap_settings' . $suffix . '.js', array(), WCAP_PLUGIN_VERSION, true );
					$js_args = array(
						'base_url'                  => home_url(),
						'admin_url'                 => admin_url(),
						'rest_url'                  => esc_url_raw( rest_url( 'wcap/v1/' ) ),
						'loading_message'           => __( 'Loading', 'woocommerce-ac' ),
						'saving_message'            => __( 'Saving Changes', 'woocommerce-ac' ),
						'message_saved'             => __( 'Settings saved', 'woocommerce-ac' ),
						'tracking_reset_warn'       => __( 'Resetting Usage Tracking', 'woocommerce-ac' ),
						'tracking_reset'            => __( 'Usage tracking reset', 'woocommerce-ac' ),
						'coupons_delete_message'    => __( 'Deleting Coupons', 'woocommerce-ac' ),
						'coupons_deleted'           => __( 'Coupons Deleted', 'woocommerce-ac' ),
						'coupon_delete_message'     => __( 'Are you sure you want delete the expired and used coupons created by Abandonment Cart Pro for WooCommerce Plugin?', 'woocommerce-ac' ),
						'sms_alert'                 => __( 'Please make sure the Recipient Number and Message field are populated with valid details.', 'woocommerce-ac' ),
						'send_test_sms'             => __( 'Sending test SMS', 'woocommerce-ac' ),
						'test_msg'                  => __( 'Hello World!', 'woocommerce-ac' ),
						'edd_license_nonce'         => wp_create_nonce( 'edd_license_nonce' ),
						'wcap_settings_nonce'       => wp_create_nonce( 'wcap_settings_nonce' ),
						'license_activated'         => __( 'License activated', 'woocommerce-ac' ),
						'license_deativated'        => __( 'License deactivated', 'woocommerce-ac' ),
						'wcap_custom_pages'         => __( 'Search for a Page&hellip;', 'woocommerce-ac' ),
						'cartSourceValidationError' => __( 'Atleast one source must be selected to receive the email notification.', 'woocommerce-ac' ),
						'oldCartsValidationError'   => __( 'Automatically Delete Abandoned Orders after X days has to be greater than 0.', 'woocommerce-ac' ),
						'wcap_show_only_license_page' => get_option( 'wcap_show_only_license_page', '' ),
						'import_export_nonce'         => wp_create_nonce( 'wp_rest' ),
						'exporting_message'           => __( 'Exporting...', 'woocommerce-ac' ),
						'export_setting_title'        => __( 'Export Settings', 'woocommerce-ac' ),
						'current_plugin_version'      => WCAP_PLUGIN_VERSION,

					);
					wp_localize_script( 'wcap_settings', 'wcap_strings', $js_args );
					wp_enqueue_script( 'wcap_settings' );
					wp_register_script( 'wcap-integrations-main', esc_url( WCAP_PLUGIN_URL ) . '/assets/js/admin/wcap_integrations_main' . $suffix . '.js', array(), WCAP_PLUGIN_VERSION, true );
					wp_localize_script(
						'wcap-integrations-main',
						'wcap_int_params',
						array(
							'ajax_url' => esc_url( WCAP_ADMIN_AJAX_URL )
						)
					);
					wp_enqueue_script( 'wcap-integrations-main' );
					wp_enqueue_style( 'wcap-integrations-list', esc_url( WCAP_PLUGIN_URL ) . '/assets/css/admin/wcap_integrations_main' . $suffix . '.css', array(), WCAP_PLUGIN_VERSION );
				}

				$mode = Wcap_Common::wcap_get_mode();
				
				if ( $section == 'wcap_atc_settings' && ( 'copytemplate' === $mode || 'edittemplate' === $mode || 'addnewtemplate' === $mode ) ) {
					wp_register_script( 'wcap_atc_add_edit', esc_url( WCAP_PLUGIN_URL ) . '/assets/js/admin/wcap_atc_add_edit' . $suffix . '.js', array(), WCAP_PLUGIN_VERSION, true );
					
					$id = isset( $_GET['id'] ) ? sanitize_text_field( wp_unslash( $_GET['id'] ) ) : 0;					
					$template_settings = wcap_get_atc_template_preview( $id );
					if ( isset( $_GET['message'] ) && 'template_updated' === $_GET['message'] ) { // phpcs:ignore WordPress.Security.NonceVerification
						$template_settings['saved_message'] = true;
						$template_settings['message_saved'] = __( 'Template saved', 'woocommerce-ac' );					
					}
					$template_settings['wcap_path']              = WCAP_WP_UPLOADS_URL;
					$template_settings['atc_add_edit_nonce']     = wp_create_nonce( 'atc_upload_image' );
					$template_settings['wcap_settings_nonce']    = wp_create_nonce( 'wcap_settings_nonce' );
					wp_localize_script(
						'wcap_atc_add_edit',
						'wcap_template_settings',
						$template_settings
					);
					wp_localize_script(
						'wcap_atc_add_edit',
						'wcap_atc_rules_params',
						array(
							'wcap_custom_pages'             => __( 'Search for a Page&hellip;', 'woocommerce-ac' ),
							'wcap_prod_cat_select'          => __( 'Search for a Product Category&hellip;', 'woocommerce-ac' ),
							'wcap_products_select'          => __( 'Search for a Product&hellip;', 'woocommerce-ac' ),
							'wcap_ei_popup_button_heading'  => __( 'Link Text', 'woocommerce-ac' ),
							'wcap_atc_popup_button_heading' => __( 'Add to cart button text', 'woocommerce-ac' ),
							'wcap_atc_button_text'          => __( 'Add to Cart', 'woocommerce-ac' ),
							'wcap_ei_button_text'           => __( 'Complete my order!', 'woocommerce-ac' ),
							'wcap_ei_modal_text'            => __( 'We are sad to see you go but you can enter your email below and we will save the cart for you.', 'woocommerce-ac' ),
							'wcap_atc_modal_text'           => __( 'To add this item to your cart, please enter your email address.', 'woocommerce-ac' ),
						)
					);
					wp_enqueue_script( 'wcap_atc_add_edit' );
				}
				
				if ( $action == 'cart_recovery'&& $section == 'emailtemplates' && ( 'copytemplate' === $mode || 'edittemplate' === $mode || 'addnewtemplate' === $mode ) ) {
					wp_register_script( 'add_edit_email_template', esc_url( WCAP_PLUGIN_URL ) . '/assets/js/admin/add_edit_email_template' . $suffix . '.js', array(), WCAP_PLUGIN_VERSION, true );
						

					$template_localized_params = self::wcap_template_params();
					wp_localize_script(
						'add_edit_email_template',
						'wcap_template_params',
						$template_localized_params
					);
					
					$id = isset( $_GET['id'] ) ? sanitize_text_field( wp_unslash( $_GET['id'] ) ) : 0;
					
					$template_settings = Wcap_Email_Template_Fields::wcap_get_email_template_fields();
					wp_localize_script(
						'add_edit_email_template',
						'wcap_template_settings',
						$template_settings
					);
					
					wp_localize_script(
						'add_edit_email_template',
						'wcap_email_params',
						array(
							'wcap_payment_gateways' => $available_gateways,
							'wcap_available_countries' => $available_countries,
							'wcap_cond_includes' => array(
								'includes' => __( 'Includes any of', 'woocommerce-ac' ),
								'excludes' => __( 'Excludes any of', 'woocommerce-ac' ),
							),
							'wcap_counts' => array(
								'greater_than_equal_to'  => __( 'Greater than or equal to', 'woocommerce-ac' ),
								'equal_to'               => __( 'Equal to', 'woocommerce-ac' ),
								'less_than_equal_to'     => __( 'Less than or equal to', 'woocommerce-ac' ),
							),
							'wcap_send_to_select'        => __( 'Search for options&hellip;', 'woocommerce-ac' ),
							'wcap_product_select'        => __( 'Search for a Product&hellip;', 'woocommerce-ac' ),
							'wcap_coupon_select'         => __( 'Search for a Coupon&hellip;', 'woocommerce-ac'),
							'wcap_prod_cat_select'       => __( 'Search for a Product Category&hellip;', 'woocommerce-ac' ),
							'wcap_prod_tag_select'       => __( 'Search for a Product Tag&hellip;', 'woocommerce-ac' ),
							'wcap_cart_status_select'    => __( 'Search for a Cart Status&hellip;', 'woocommerce-ac' ),
							'wcap_order_status_select'   => __( 'Search for a Order Status&hellip;', 'woocommerce-ac' ),
							'wcap_email_sent_image_path' => esc_url( WCAP_PLUGIN_URL ) . '/assets/images/wcap_email_sent.svg',
							'wcap_email_warn_message'    => __( 'Please enter a valid email id.', 'woocommerce-ac' ),
							'wcap_settings_nonce'        => wp_create_nonce( 'wcap_settings_nonce' ),
						)
					);
					wp_enqueue_script( 'add_edit_email_template' );
					
				}
				
				if ( $action == 'cart_recovery'&& $section == 'emailtemplates' && ( 'wcap_manual_email' === $mode ) ) {
					
					wp_register_script( 'wcap_send_manual_email', esc_url( WCAP_PLUGIN_URL ) . '/assets/js/admin/wcap_send_manual_email' . $suffix . '.js', array(), WCAP_PLUGIN_VERSION, true );
					
					wp_localize_script(
						'wcap_send_manual_email',
						'wcap_send_manual_email',
						array(							
							'wcap_email_sent_image_path' => esc_url( WCAP_PLUGIN_URL ) . '/assets/images/wcap_email_sent.svg',
							'wcap_email_warn_message' => __( 'Please enter a valid email id.', 'woocommerce-ac' ),
							'message_loading' => __( 'Loading', 'woocommerce-ac' ),
							'wcap_settings_nonce' => wp_create_nonce( 'wcap_settings_nonce' ),
						)
					);
					
					$template_localized_params = self::wcap_template_params();
					wp_localize_script(
						'wcap_send_manual_email',
						'wcap_template_params',
						$template_localized_params
					);
					
					wp_enqueue_script( 'wcap_send_manual_email' );
					
				}
				
				if ( $action == 'cart_recovery' && ! ( 'copytemplate' === $mode || 'edittemplate' === $mode || 'addnewtemplate' === $mode || 'wcap_manual_email' === $mode ) ) {
				
					wp_register_script( 'wcap_cart_recovery', esc_url( WCAP_PLUGIN_URL ) . '/assets/js/admin/wcap_cart_recovery' . $suffix . '.js', array(), WCAP_PLUGIN_VERSION, true );
										
					wp_localize_script(
						'wcap_cart_recovery',
						'wcap_strings',
						array(
							'wcap_payment_gateways' => $available_gateways,
							'wcap_show_only_license_page' => get_option( 'wcap_show_only_license_page', '' ),
							'wcap_available_countries' => $available_countries,
							'wcap_cond_includes' => array(
								'includes' => __( 'Includes any of', 'woocommerce-ac' ),
								'excludes' => __( 'Excludes any of', 'woocommerce-ac' ),
							),
							'wcap_counts' => array(
								'greater_than_equal_to' => __( 'Greater than or equal to', 'woocommerce-ac' ),
								'equal_to'              => __( 'Equal to', 'woocommerce-ac' ),
								'less_than_equal_to'    => __( 'Less than or equal to', 'woocommerce-ac' ),
							),
							'wcap_send_to_select'      => __( 'Search for options&hellip;', 'woocommerce-ac' ),
							'wcap_product_select'      => __( 'Search for a Product&hellip;', 'woocommerce-ac' ),
							'wcap_coupon_select'       => __( 'Search for a Coupon&hellip;', 'woocommerce-ac'),
							'wcap_prod_cat_select'     => __( 'Search for a Product Category&hellip;', 'woocommerce-ac' ),
							'wcap_prod_tag_select'     => __( 'Search for a Product Tag&hellip;', 'woocommerce-ac' ),
							'wcap_cart_status_select'  => __( 'Search for a Cart Status&hellip;', 'woocommerce-ac' ),
							'wcap_order_status_select' => __( 'Search for a Order Status&hellip;', 'woocommerce-ac' ),
							'base_url'          => home_url(),
							'admin_url'         => admin_url(),
							'loading_message'   => __( 'Loading', 'woocommerce-ac' ),									
							'message_saved'     => __( 'Settings saved', 'woocommerce-ac' ),
							'saving_template'   => __( 'Saving template', 'woocommerce-ac' ),
							'adding_template'   => __( 'Adding template', 'woocommerce-ac' ),
							'template_added'    => __( 'Template added', 'woocommerce-ac' ),
							'updating_template' => __( 'Updating template', 'woocommerce-ac' ),
							'template_updated'  => __( 'Template updated', 'woocommerce-ac' ),
							'deleting_template' => __( 'Deleting template', 'woocommerce-ac' ),
							'template_deleted'  => __( 'Template deleted', 'woocommerce-ac' ),
							'confirm_delete'    => __( 'Are you sure you want to delete', 'woocommerce-ac' ),
							'chose_action'      => __( 'Please chose an action', 'woocommerce-ac' ),
							'connector_message' => Wcap_Common::wcap_get_active_connector_list(),
							'wcap_settings_nonce' => wp_create_nonce( 'wcap_settings_nonce' ),
						)
					);
					
					wp_enqueue_script( 'wcap_cart_recovery' );					
				}
				


				if ( 'listcart' == $action && ( !isset( $_GET['action_details'] )  )
                    && ( !isset($_GET['wcap_download']) || 'wcap.csv' != $_GET['wcap_download'] )
                    && ( !isset($_GET['wcap_download']) || 'wcap.print' != $_GET['wcap_download'] )
                    ) {
						$base_url = admin_url( 'admin.php?page=woocommerce_ac_page' );

						$wcap_abandoned_bulk_actions = array(
							'wcap_manual_email'               => __( 'Send Custom Email', 'woocommerce-ac' ),
							'wcap_abandoned_trash'            => __( 'Move to Trash', 'woocommerce-ac' ),
							'wcap_abandoned_trash_visitor'    => __( 'Move all Visitor carts to Trash', 'woocommerce-ac' ),
							'wcap_abandoned_trash_guest'      => __( 'Move all Guest User carts to Trash', 'woocommerce-ac' ),
							'wcap_abandoned_trash_registered' => __( 'Move all Registered User carts to Trash', 'woocommerce-ac' ),							
							'wcap_abandoned_trash_all'        => __( 'Move all carts to Trash', 'woocommerce-ac' )							
						);
						$wcap_trash_bulk_actions = array(
							'wcap_abandoned_restore' => __( 'Restore', 'woocommerce-ac' ),
	        				'wcap_abandoned_delete'  => __( 'Delete Permanently', 'woocommerce-ac' ),
	        				'wcap_empty_trash'       => __( 'Empty Trash', 'woocommerce-ac' ),
						);
						
						$active_count = Wcap_Connectors_Common::wcap_get_active_connectors_count();
						if ( $active_count > 0 ) {
							$wcap_abandoned_bulk_actions['wcap_sync_manually'] = __( 'Sync Manually', 'woocommerce-ac' );
						}
						
							
							$duration_range_select = array(
								'yesterday'         => __( 'Yesterday',    'woocommerce-ac' ),
								'today'             => __( 'Today',        'woocommerce-ac' ),
								'last_seven'        => __( 'Last 7 days',  'woocommerce-ac' ),
								'last_fifteen'      => __( 'Last 15 days', 'woocommerce-ac' ),
								'last_thirty'       => __( 'Last 30 days', 'woocommerce-ac' ),
								'last_ninety'       => __( 'Last 90 days', 'woocommerce-ac' ),
								'last_year_days'    => __( 'Last 365',     'woocommerce-ac' )
							);
							
							$start_end_dates = array(
								'yesterday'     => array( 'start_date' => date( "d M Y", ( current_time('timestamp') - 24*60*60 ) ), 'end_date' => date( "d M Y", ( current_time( 'timestamp' ) - 24*60*60 ) ) ),

								'today'         => array( 'start_date' => date( "d M Y", ( current_time( 'timestamp' ) ) ), 'end_date' => date( "d M Y", ( current_time( 'timestamp' ) ) ) ),

								'last_seven'    => array( 'start_date' => date( "d M Y", ( current_time( 'timestamp' ) - 7*24*60*60 ) ), 'end_date' => date( "d M Y", ( current_time( 'timestamp' ) ) ) ),

								'last_fifteen'  => array( 'start_date' => date( "d M Y", ( current_time( 'timestamp' ) - 15*24*60*60 ) ), 'end_date' => date( "d M Y", ( current_time( 'timestamp' ) ) ) ),

								'last_thirty'   => array( 'start_date' => date( "d M Y", ( current_time( 'timestamp' ) - 30*24*60*60 ) ), 'end_date' => date( "d M Y", ( current_time( 'timestamp' ) ) ) ),

								'last_ninety'   => array( 'start_date' => date( "d M Y", ( current_time( 'timestamp' ) - 90*24*60*60 ) ), 'end_date' => date( "d M Y", ( current_time( 'timestamp' ) ) ) ),

								'last_year_days'=> array( 'start_date' => date( "d M Y", ( current_time( 'timestamp' ) - 365*24*60*60 ) ) , 'end_date' => date( "d M Y", ( current_time( 'timestamp' ) ) ) )
							);

							$valid_statuses = array(
								'all'       => __( 'All Statuses', 'woocommerce-ac' ),
								'abandoned' => __( 'Abandoned', 'woocommerce-ac' ),
								'recovered' => __( 'Recovered', 'woocommerce-ac' ),
								'received'  => __( 'Abandoned - Order Received', 'woocommerce-ac' ),
								'unpaid'    => __( 'Abandoned - Pending Payment', 'woocommerce-ac' ),
								'cancelled' => __( 'Abandoned - Order Cancelled', 'woocommerce-ac' ),
							);
							$valid_sources  = array(
								'all'           => __( 'All Sources', 'woocommerce-ac' ),
								'checkout_page' => __( 'Checkout Page', 'woocommerce-ac' ),
								'product_page'  => __( 'User Profile', 'woocommerce-ac' ),
								'atc'           => __( 'Add to Cart Popup', 'woocommerce-ac' ),
								'exit_intent'   => __( 'Exit Intent Popup', 'woocommerce-ac' ),
								'custom_form'   => __( 'Custom Forms', 'woocommerce-ac' ),
								'url'           => __( 'URL', 'woocommerce-ac' ),
							);
						$orders_link = '<a target="_blank" href="' . admin_url( 'edit.php?post_type=shop_order' ) . '">' . __( 'here', 'woocommerce-ac' ) . '</a>';
					wp_localize_script(
						'wcap_orders',
						'wcap_abandoned_cart_params',
						array(
							'order_id_not_found_msg'    => __( 'Order ID not found. Please try again.', 'woocommerce-ac' ),
							'mark_recovered_txt'        => __( 'Mark as Recovered', 'woocommerce-ac' ),
							'order_id_txt_placeholder'  => __( 'Search WooCommerce Orders', 'woocommerce-ac' ),
							'search_button_txt'         => __( 'Search', 'woocommerce-ac' ),
							'validation_error_order_id' => __( 'Please enter a valid Order ID', 'woocommerce-ac' ),
							'existing_display_text'     => __( 'Enter your WooCommerce Order ID against which you wish to link the cart and mark as Recovered.', 'woocommerce-ac' ),
							'create_order_display_text' => __( 'Create a WooCommerce Order against which the cart will be marked as recovered.', 'woocommerce-ac' ),
							'wc_order_link'             => __( ' WooCommerce orders can be found ', 'woocommerce-ac' ) . $orders_link . '.',
						)
					);
						
						wp_register_script( 'wcap_orders', esc_url( WCAP_PLUGIN_URL ) . '/assets/js/admin/wcap_orders' . $suffix . '.js', array(), WCAP_PLUGIN_VERSION, true );
						
						
						$wcap_manual_email_sent = false;
						$mail_sent_message = '';
						if ( isset( $_GET['wcap_manual_email_sent'] ) )
						{
							$wcap_manual_email_sent = true;
							$mail_sent_message = __('Mail sent.' );
						}
						$js_args = array(
							'wcap_abandoned_bulk_actions' => $wcap_abandoned_bulk_actions,
							'wcap_trash_bulk_actions'     => $wcap_trash_bulk_actions,
							'duration_range_select'       => $duration_range_select,
							'start_end_dates'             => $start_end_dates,
							'valid_statuses'              => $valid_statuses,
							'valid_sources'               => $valid_sources,
							'page_url'                    => admin_url( '/admin.php?page=woocommerce_ac_page&action=listcart' ),
							'wcap_filter_data'            => Wcap_Common::wcap_get_filter_data(),
							'confirm_bulk_action'         => __('Are you sure you want to' ),
							'please_chose_ids'            => __('Please select some carts' ),
							'confirm_trash'               => __('Are you sure you want to trash' ),
							'cannot_send_email'           => __('Send Custom Email cannot be applied.' ),
							'manual_email_url'            =>  admin_url( '/admin.php?page=woocommerce_ac_page&action=cart_recovery&section=emailtemplates&mode=wcap_manual_email&'),
							'order_id_not_found_msg'      => __( 'Order ID not found. Please try again.', 'woocommerce-ac' ),
							'loading_message'             => __( 'Loading', 'woocommerce-ac' ),
							'message_moving'              => __( 'Moving to trash', 'woocommerce-ac' ),
							'message_moved'               => __( 'Moved to trash', 'woocommerce-ac' ),
							'message_processing'          => __( 'Processing', 'woocommerce-ac' ),
							'message_unsubscribed'        => __( 'Cart Unsubscribed', 'woocommerce-ac' ),
							'message_synced'              => __( 'Cart Synced', 'woocommerce-ac' ),
							'message_recovered'           => __( 'Cart Recovered', 'woocommerce-ac' ),
							'order_id_not_found_msg'      => __( 'Order ID not found. Please try again.', 'woocommerce-ac' ),
							'message_restored'            => __( 'Cart Restored', 'woocommerce-ac' ),
							'message_deleted'             => __( 'Cart Deleted', 'woocommerce-ac' ),
							'message_trash_emptied'       => __( 'Trash Emptied', 'woocommerce-ac' ),
							'wcap_manual_email_sent'      => $wcap_manual_email_sent,
							'mail_sent_message'           => $mail_sent_message,
							'no_action_chosen'            => __( 'No action chosen', 'woocommerce-ac' ),
							'message_synced'              => __( 'Selected carts were synced successfully', 'woocommerce-ac' ),
							'wcap_settings_nonce'         => wp_create_nonce('wcap_settings_nonce'),
						);
						
						wp_localize_script( 'wcap_orders', 'wcap_strings', $js_args );						
						wp_enqueue_script( 'wcap_orders' );	
						
						wp_enqueue_script( 'wcap_abandoned_cart_details', esc_url( WCAP_PLUGIN_URL ) . '/assets/js/admin/wcap_abandoned_cart_detail_modal' . $suffix . '.js' );
					
				}
				
				if ( 'emailstats' == $action ) {
							
						wp_register_script( 'wcap_reminders', esc_url( WCAP_PLUGIN_URL ) . '/assets/js/admin/wcap_reminders' . $suffix . '.js', array(), WCAP_PLUGIN_VERSION, true );
						$duration_range_select = array(
							'yesterday'         => __( 'Yesterday',    'woocommerce-ac' ),
							'today'             => __( 'Today',        'woocommerce-ac' ),
							'last_seven'        => __( 'Last 7 days',  'woocommerce-ac' ),
							'last_fifteen'      => __( 'Last 15 days', 'woocommerce-ac' ),
							'last_thirty'       => __( 'Last 30 days', 'woocommerce-ac' ),
							'last_ninety'       => __( 'Last 90 days', 'woocommerce-ac' ),
							'last_year_days'    => __( 'Last 365',     'woocommerce-ac' )
						);
						$start_end_dates = array(
								'yesterday'     => array( 'start_date' => date( "d M Y", ( current_time('timestamp') - 24*60*60 ) ), 'end_date' => date( "d M Y", ( current_time( 'timestamp' ) - 24*60*60 ) ) ),

								'today'         => array( 'start_date' => date( "d M Y", ( current_time( 'timestamp' ) ) ), 'end_date' => date( "d M Y", ( current_time( 'timestamp' ) ) ) ),

								'last_seven'    => array( 'start_date' => date( "d M Y", ( current_time( 'timestamp' ) - 7*24*60*60 ) ), 'end_date' => date( "d M Y", ( current_time( 'timestamp' ) ) ) ),

								'last_fifteen'  => array( 'start_date' => date( "d M Y", ( current_time( 'timestamp' ) - 15*24*60*60 ) ), 'end_date' => date( "d M Y", ( current_time( 'timestamp' ) ) ) ),

								'last_thirty'   => array( 'start_date' => date( "d M Y", ( current_time( 'timestamp' ) - 30*24*60*60 ) ), 'end_date' => date( "d M Y", ( current_time( 'timestamp' ) ) ) ),

								'last_ninety'   => array( 'start_date' => date( "d M Y", ( current_time( 'timestamp' ) - 90*24*60*60 ) ), 'end_date' => date( "d M Y", ( current_time( 'timestamp' ) ) ) ),

								'last_year_days'=> array( 'start_date' => date( "d M Y", ( current_time( 'timestamp' ) - 365*24*60*60 ) ) , 'end_date' => date( "d M Y", ( current_time( 'timestamp' ) ) ) )
							);
						$js_args = array(
									'base_url'               => home_url(),
									'admin_url'              => admin_url(),
									'loading_message'        => __( 'Loading', 'woocommerce-ac' ),
									'saving_message'         => __( 'Saving Changes', 'woocommerce-ac' ),
									'duration_range_select'  => $duration_range_select,
									'wcap_filter_data'       => Wcap_Common::wcap_get_filter_data(),
									'start_end_dates'        => $start_end_dates,
									'wcap_settings_nonce'    => wp_create_nonce( 'wcap_settings_nonce' ),
							);
					wp_localize_script( 'wcap_reminders', 'wcap_strings', $js_args );
					wp_enqueue_script( 'wcap_reminders' );
					wp_enqueue_script( 'wcap_abandoned_cart_details', esc_url( WCAP_PLUGIN_URL ) . '/assets/js/admin/wcap_abandoned_cart_detail_modal' . $suffix . '.js', array(), WCAP_PLUGIN_VERSION );
											
				}
				
				if ( 'report' == $action ) {
							
						wp_register_script( 'wcap_product_reports', esc_url( WCAP_PLUGIN_URL ) . '/assets/js/admin/wcap_product_reports' . $suffix . '.js', array(), WCAP_PLUGIN_VERSION, true );
						
						$js_args = array(
									'base_url'               => home_url(),
									'admin_url'              => admin_url(),
									'loading_message'        => __( 'Loading', 'woocommerce-ac' ),
									'saving_message'         => __( 'Saving Changes', 'woocommerce-ac' ),
									'wcap_settings_nonce'	 => wp_create_nonce( 'wcap_settings_nonce' ),															
							);
					wp_localize_script( 'wcap_product_reports', 'wcap_strings', $js_args );
						wp_enqueue_script( 'wcap_product_reports' );	
				}
			}
			
		}

		/**
		 * Enqueue JS Scripts at front end for capturing the cart from checkout page.
		 *
		 * @hook woocommerce_after_checkout_billing_form
		 *
		 * @since 5.0
		 */
		public static function wcap_include_js_for_guest() {

            $execute = apply_filters( 'wcap_show_only_license_page', true );
			if ( ! $execute ) {
				return;
			}

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			$guest_cart = get_option( 'ac_disable_guest_cart_email' );

			// check if the script needs to be loaded on the cart page
			$load_cart      = wcap_get_atc_coupon_msg_cart() ? true : false;
			$cart_condition = $load_cart ? 'is_cart()' : '';
			$vars           = array();

			if ( ( $cart_condition || is_checkout() ) && $guest_cart != 'on' && ! is_user_logged_in() ) {
				wp_enqueue_script( 'jquery' );
				wp_enqueue_script( 'wcap_capture_guest_user', esc_url( WCAP_PLUGIN_URL ) . '/assets/js/frontend/wcap_guest_user' . $suffix . '.js', array(), WCAP_PLUGIN_VERSION );
				$enable_gdpr = get_option( 'wcap_enable_gdpr_consent', '' );
				$guest_msg   = get_option( 'wcap_guest_cart_capture_msg' );

				$session_gdpr = wcap_get_cart_session( 'wcap_cart_tracking_refused' );
				$show_gdpr    = isset( $session_gdpr ) && 'yes' == $session_gdpr ? false : true;

				if ( 'on' === $enable_gdpr ) {

					$display_msg = isset( $guest_msg ) && '' !== $guest_msg ? $guest_msg : __( 'Saving your email and cart details helps us keep you up to date with this order.', 'woocommerce-ac' );
					$display_msg = apply_filters( 'wcap_gdpr_email_consent_guest_users', $display_msg );

					$no_thanks              = get_option( 'wcap_gdpr_allow_opt_out', '' );
					$no_thanks              = apply_filters( 'wcap_gdpr_opt_out_text', $no_thanks );
					$opt_in_msg             = ! empty( get_option( 'wcap_gdpr_allow_opt_in', '' ) ) ? get_option( 'wcap_gdpr_allow_opt_in', '' ) : __( 'I agree to receive abandoned cart reminder emails.', 'woocommerce-ac' );
					$selected_cart_tracking = get_option( 'wcap_select_guest_cart_tracking', 'opt-out' );

					$opt_out_confirmation_msg = get_option( 'wcap_gdpr_opt_out_message', '' );
					$opt_out_confirmation_msg = apply_filters( 'wcap_gdpr_opt_out_confirmation_text', $opt_out_confirmation_msg );
					$vars = array(
						'_show_gdpr_message'        => $show_gdpr,
						'_gdpr_message'             => htmlspecialchars( $display_msg, ENT_QUOTES ),
						'_gdpr_nothanks_msg'        => htmlspecialchars( $no_thanks, ENT_QUOTES ),
						'_gdpr_optin_msg'           => htmlspecialchars( $opt_in_msg, ENT_QUOTES ),
						'_gdpr_after_no_thanks_msg' => htmlspecialchars( $opt_out_confirmation_msg, ENT_QUOTES ),
						'enable_ca_tracking'        => true,
						'selected_cart_tracking'    => $selected_cart_tracking,
					);
				}

				if ( 'on' === get_option('wcap_enable_whatsapp_reminders' ) ) {
					$wcap_wa_consent_msg = get_option( 'wcap_whatsapp_consent_text', '' );
					$wcap_wa_consent_msg = '' !== $wcap_wa_consent_msg ? $wcap_wa_consent_msg : __( 'Saving your phone number and cart details helps us keep you up to date with this order.', 'woocommerce-ac' );
					$vars['_whatsapp_consent_msg']     = $wcap_wa_consent_msg;
					$vars['_display_whatsapp_consent_box'] = true;
				}
				
				$vars['ajax_url']      = esc_url( WCAP_ADMIN_AJAX_URL );
				$vars['custom_fields'] = apply_filters( 'wcap_detect_more_fields_at_checkout', '' );
				wp_localize_script( 'wcap_capture_guest_user', 'wcap_capture_guest_user_params', $vars );
			}

			if ( ( $cart_condition || is_checkout() ) && $guest_cart != 'on' ) {
				wp_enqueue_script( 'jquery' );
				wp_enqueue_script( 'wcap_capture_guest_user', esc_url( WCAP_PLUGIN_URL ) . '/assets/js/frontend/wcap_guest_user' . $suffix . '.js', array(), WCAP_PLUGIN_VERSION );
				$enable_gdpr = get_option( 'wcap_enable_gdpr_consent', '' );
				$guest_msg   = get_option( 'wcap_guest_cart_capture_msg' );

				$session_gdpr = wcap_get_cart_session( 'wcap_cart_tracking_refused' );
				$show_gdpr    = isset( $session_gdpr ) && 'yes' == $session_gdpr ? false : true;

				if ( 'on' === $enable_gdpr ) {

					$display_msg = isset( $guest_msg ) && '' !== $guest_msg ? $guest_msg : __( 'Saving your email and cart details helps us keep you up to date with this order.', 'woocommerce-ac' );
					$display_msg = apply_filters( 'wcap_gdpr_email_consent_guest_users', $display_msg );

					$no_thanks              = get_option( 'wcap_gdpr_allow_opt_out', '' );
					$no_thanks              = apply_filters( 'wcap_gdpr_opt_out_text', $no_thanks );
					$opt_in_msg             = ! empty( get_option( 'wcap_gdpr_allow_opt_in', '' ) ) ? get_option( 'wcap_gdpr_allow_opt_in', '' ) : __( 'I agree to receive abandoned cart reminder emails.', 'woocommerce-ac' );
					$selected_cart_tracking = get_option( 'wcap_select_guest_cart_tracking', 'opt-out' );

					$opt_out_confirmation_msg = get_option( 'wcap_gdpr_opt_out_message', '' );
					$opt_out_confirmation_msg = apply_filters( 'wcap_gdpr_opt_out_confirmation_text', $opt_out_confirmation_msg );
					$vars = array(
						'_show_gdpr_message'        => $show_gdpr,
						'_gdpr_message'             => htmlspecialchars( $display_msg, ENT_QUOTES ),
						'_gdpr_nothanks_msg'        => htmlspecialchars( $no_thanks, ENT_QUOTES ),
						'_gdpr_optin_msg'           => htmlspecialchars( $opt_in_msg, ENT_QUOTES ),
						'_gdpr_after_no_thanks_msg' => htmlspecialchars( $opt_out_confirmation_msg, ENT_QUOTES ),
						'enable_ca_tracking'        => true,
						'selected_cart_tracking'    => $selected_cart_tracking,
					);
				}

				if ( 'on' === get_option( 'wcap_enable_sms_consent', '' ) ) {
					$wcap_sms_consent_msg         = get_option( 'wcap_sms_consent_msg', '' );
					$wcap_sms_consent_msg         = '' !== $wcap_sms_consent_msg ? $wcap_sms_consent_msg : __( 'Saving your phone and cart details helps us keep you up to date with this order.', 'woocommerce-ac' );
					$wcap_sms_consent_msg         = apply_filters( 'wcap_sms_consent_text', $wcap_sms_consent_msg );
					$display_consent_box          = apply_filters( 'wcap_display_sms_consent_box', true );
					$vars['_sms_consent_msg']     = $wcap_sms_consent_msg;
					$vars['_display_consent_box'] = $display_consent_box;
				}
				
				$vars['ajax_url']      = esc_url( WCAP_ADMIN_AJAX_URL );
				$vars['custom_fields'] = apply_filters( 'wcap_detect_more_fields_at_checkout', '' );
				wp_localize_script( 'wcap_capture_guest_user', 'wcap_capture_guest_user_params', $vars );
			}

			if ( ( $cart_condition || is_checkout() ) && is_user_logged_in() ) {
				if ( 'on' === get_option('wcap_enable_whatsapp_reminders' ) ) {
					wp_enqueue_script(
						'wcap_registered_capture',
						esc_url( WCAP_PLUGIN_URL ) . '/assets/js/frontend/wcap_registered_user_capture' . $suffix . '.js',
						'',
						WCAP_PLUGIN_VERSION,
						true
					);
					$wcap_wa_consent_msg = get_option( 'wcap_whatsapp_consent_text', '' );
					$wcap_wa_consent_msg = '' !== $wcap_wa_consent_msg ? $wcap_wa_consent_msg : __( 'Saving your phone number and cart details helps us keep you up to date with this order.', 'woocommerce-ac' );

					$user_id = get_current_user_id();
					$consent_meta = (bool) get_user_meta( $user_id, 'wcap_whatsapp_consent', true );

					$vars = array(
						'_whatsapp_consent_msg' => $wcap_wa_consent_msg,
						'_display_whatsapp_consent_box' => true,
						'is_user_logged_in'       => true,
						'consent_given'           => isset( $consent_meta ) && $consent_meta ? true : false,
						'ajax_url'                => esc_url( WCAP_ADMIN_AJAX_URL ),
					);

					wp_localize_script( 'wcap_registered_capture', 'wcap_registered_capture_params', $vars );

				}
			}
		}  

		public static function wcap_include_js_atc_coupon() {

            $execute = apply_filters( 'wcap_show_only_license_page', true );
			if ( ! $execute ) {
				return;
			}

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			$guest_cart = get_option( 'ac_disable_guest_cart_email' );

			// check if the script needs to be loaded on the cart page.
			$atc_template_id   = wcap_get_cart_session( 'wcap_atc_template_id' );
			$ei_template_id    = wcap_get_cart_session( 'wcap_exit_intent_template_id' );
			if ( $atc_template_id > 0 || $ei_template_id > 0 ) {
				$template_settings = wcap_get_atc_template( $atc_template_id );
				if ( false === $template_settings ) {
					$template_settings = wcap_get_atc_template( $ei_template_id );
				}
				if ( $template_settings ) {
					$coupon_settings   = json_decode( $template_settings->coupon_settings );
					if ( 'on' === $coupon_settings->wcap_atc_auto_apply_coupon_enabled ) { // Coupons are enabled.
						$load_cart         = 'on' === $coupon_settings->wcap_countdown_cart ? true : false;
						$cart_condition    = $load_cart ? 'is_cart()' : '';

						if ( ( $cart_condition || is_checkout() ) && $guest_cart != 'on' && ! is_user_logged_in() ) {
							wp_enqueue_script( 'wcap_atc_coupon_countdown', esc_url( WCAP_PLUGIN_URL ) . '/assets/js/frontend/wcap_atc_coupon_countdown' . $suffix . '.js', array(), WCAP_PLUGIN_VERSION );

							$vars = array();

							$vars['ajax_url'] = esc_url( WCAP_ADMIN_AJAX_URL );

							// ATC Coupons auto applied.
							$atc_coupon_applied        = 'on' === $coupon_settings->wcap_atc_auto_apply_coupon_enabled ? true : false;
							$vars['_wcap_coupons_atc'] = $atc_coupon_applied;

							// Coupon, validity & expiry message is setup?
							$countdown_msg = '' !== $coupon_settings->wcap_countdown_timer_msg ? htmlspecialchars_decode( $coupon_settings->wcap_countdown_timer_msg ) : 'Coupon <coupon_code> expires in <hh:mm:ss>. Avail it now.'; 
							if ( 'on' === $coupon_settings->wcap_atc_auto_apply_coupon_enabled && '' !== $countdown_msg && 0 < $coupon_settings->wcap_atc_popup_coupon_validity ) {
								$coupon_expiry = '';
								$abandoned_id = wcap_get_cart_session( 'wcap_abandoned_id' );
								$coupons_meta = get_post_meta( $abandoned_id, '_woocommerce_ac_coupon', true );
								if ( is_array( $coupons_meta ) && count( $coupons_meta ) > 0 ) {
									foreach ( $coupons_meta as $key => $coupon_details ) {
										if ( isset( $coupon_details['time_expires'] ) ) {
											$coupon_expiry = $coupon_details['time_expires'];
											break;
										}
									}
								}
								if ( '' !== $coupon_expiry ) {
									$coupon_expiry_date = date( 'Y/m/d, H:i:s', $coupon_expiry );
									$display_msg        = $countdown_msg;

									$vars['_wcap_coupon_msg']     = __( $display_msg, 'woocommerce-ac' );
									$vars['_wcap_coupon_expires'] = $coupon_expiry_date;
									$vars['_wcap_expiry_msg']     = '' !== $coupon_settings->wcap_countdown_msg_expired ? __( $coupon_settings->wcap_countdown_msg_expired, 'woocommerce-ac' ) : __( 'The offer is no longer valid.', 'woocommerce-ac' ); // phpcs:ignore
									$vars['_wcap_server_offset']  = get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
								}
							}
							wp_localize_script( 'wcap_atc_coupon_countdown', 'wcap_atc_coupon_countdown_params', $vars );
						}
					}
				}
			}
		}
		/**
		 * It will dequeue front end script for the Add To Cart Popup Modal on shop page.
		 *
		 * @hook plugins_loaded
		 *
		 * @since 8.0
		 */
		public static function wcap_dequeue_scripts_atc_modal() {

			$execute = apply_filters( 'wcap_show_only_license_page', true );
			if ( ! $execute ) {
				return;
			}

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_dequeue_script( 'wc-add-to-cart' );

			wp_register_script(
				'wc-add-to-cart',
				esc_url( WCAP_PLUGIN_URL ) . '/assets/js/frontend/wcap_atc_modal' . $suffix . '.js',
				array(),
				WCAP_PLUGIN_VERSION,
				true
			);
			wp_enqueue_script( 'wc-add-to-cart' );
		}

		/**
		 * It will load all the front end scripts for the Exit Intent Modal.
		 *
		 * @hook wp_enqueue_scripts
		 *
		 * @since 8.14.0
		 */
		public static function wcap_enqueue_ei_scripts() {

			$execute = apply_filters( 'wcap_show_only_license_page', true );
			if ( ! $execute ) {
				return;
			}

			$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			$ei_active = wcap_get_popup_active_status( 'exit_intent' );

			$ei_displayed  = wcap_get_cart_session( 'wcap_exit_intent_template_id' );

			// EI popup is displayed when template is active & cart contains atleast 1 product.
			if ( $ei_active && ! $ei_displayed ) {
				// Get the cache data.
				$wcap_get_ei_template_list = array();
				$wcap_get_ei_template_list = json_decode( get_option( 'wcap_ei_templates', '' ), true );
				// Identify the page.
				global $post;
				$page_id = 0;
				if ( is_shop() ) {
					$page_id = wc_get_page_id('shop');
				} else {
					$page_id = isset( $post->ID ) ? $post->ID : 0;
				}
				$wcap_ei_cache_check = false;
				$single              = false;
				if ( is_array( $wcap_get_ei_template_list ) && count( $wcap_get_ei_template_list ) > 0 ) {
					if ( array_key_exists( 'single_no_rules', $wcap_get_ei_template_list ) ) {
						$wcap_ei_cache_check = true; // should remain inside the condition, always.
						$single              = true;
					} elseif ( array_key_exists( $page_id, $wcap_get_ei_template_list ) ) {
						$wcap_ei_cache_check = true; // should remain inside the condition, always.
					}
				}
				// Identify which EI template will be displayed on the page.
				if ( $wcap_ei_cache_check ) {
					$template_to_use     = $single ? $wcap_get_ei_template_list['single_no_rules'] : $wcap_get_ei_template_list[ $page_id ];
					$template_settings   = $template_to_use['template_settings'];
					$custom_pages        = $template_to_use['custom_pages'];
					$allowed_products    = $template_to_use['allowed_products'];
					$disallowed_products = $template_to_use['disallowed_products'];
					$custom_pages_exc    = $template_to_use['custom_pages_exc'];
				} else {
					$parent_id = 0;
					if ( is_tax( 'product_cat' ) ) { // If its the product category page, we need the category term ID & parent ID.
						$cat       = get_queried_object();
						$page_id   = $cat->term_id;
						$parent_id = $cat->parent;
					}
					// Localize based on the template that should be displayed.
					$template_settings = wcap_get_popup_template_for_page( $page_id, 'exit_intent' );
					$cache_ei_data     = wcap_popup_display_list( $template_settings, $page_id, $parent_id );
					if ( $cache_ei_data ) {
						$custom_pages        = $cache_ei_data['custom_pages'];
						$allowed_products    = $cache_ei_data['allowed_products'];
						$disallowed_products = $cache_ei_data['disallowed_products'];
						$custom_pages_exc    = $cache_ei_data['custom_pages_exc'];

						if ( isset( $template_settings['single_no_rules'] ) && $template_settings['single_no_rules'] ) {
							$wcap_get_ei_template_list['single_no_rules'] = $cache_ei_data;
						} else {
							$wcap_get_ei_template_list[ $page_id ] = $cache_ei_data;
						}
						update_option( 'wcap_ei_templates', wp_json_encode( $wcap_get_ei_template_list ), false );
					} else {
						return;
					}
				}
				$active_theme      = wp_get_theme();
				$active_theme_name = array( "Skyline WP Child","AFGolf 2026 Block Theme", "Colibri WP", "EggShell", "Frontend", "Furnob Child", "Twenty Twenty-Five" );

				if ( in_array( $active_theme->name, $active_theme_name ) || in_array( $active_theme->parent_theme, $active_theme_name ) ) { // Issue #3509
					wp_enqueue_script( 'wc-cart-fragments' );
				}
				// if user is logged in and the popup is set to not display for logged in users, return.
				if ( is_user_logged_in() && ( isset( $template_settings['wcap_enable_ei_for_registered_users'] ) && 'on' !== $template_settings['wcap_enable_ei_for_registered_users'] ) ) {
					return;
				}
				if ( is_array( $disallowed_products ) && in_array( $page_id, $disallowed_products ) ) {
					return;
				}
				if ( is_array( $custom_pages_exc ) && count( $custom_pages_exc ) > 0 && in_array( $page_id, $custom_pages_exc ) ) {
					return;
				}
				if ( is_array( $custom_pages ) && count( $custom_pages ) > 0 && ! in_array( $page_id, $custom_pages ) ) {
					return;
				}
				$atc_coupon_code = '';
				if ( 'on' === $template_settings['wcap_atc_auto_apply_coupon_enabled'] && 'pre-selected' === $template_settings['wcap_atc_coupon_type'] && 0 < $template_settings['wcap_atc_popup_coupon'] ) {
					$atc_coupon_code = get_the_title( $template_settings['wcap_atc_popup_coupon'] );
				}
				$force_checkout_enabled = $template_settings['wcap_quick_ck_force_checkout'];
				$customer_email         = WC()->session->get( 'wcap_guest_email' );
				$billing_email          = WC()->session->get( 'billing_email' );
				$email_captured         = null === $customer_email && null === $billing_email ? false : true;
				$load_quick_ck          = false;

				// Load the Template.
				if ( 'on' === $force_checkout_enabled ) { // Load the Force Checkout Template
					$load_quick_ck = true;
				} else if ( ! is_user_logged_in() && $email_captured ) {
					$load_quick_ck = true;
				} else if ( is_user_logged_in() ) {
					$load_quick_ck = true;
				}

				$wcap_ei_modal = '';
				ob_start();
				if ( $load_quick_ck ) {
					include WCAP_PLUGIN_PATH . '/includes/template/exit_intent/wcap_quick_checkout.php';
					$wcap_ei_modal = ob_get_clean();
				} else {
					include WCAP_PLUGIN_PATH . '/includes/template/exit_intent/wcap_exit_with_email.php';
					$wcap_ei_modal = ob_get_clean();
				}

				wp_enqueue_script(
					'wcap_vue_js',
					esc_url( WCAP_PLUGIN_URL ) . '/assets/js/vue.min.js',
					array(),
					WCAP_PLUGIN_VERSION,
					true
				);
				$wcap_ei_modal = apply_filters( 'wcap_custom_ei_template', $wcap_ei_modal );

				ob_start();
				include WCAP_PLUGIN_PATH . '/includes/template/exit_intent/wcap_quick_checkout.php';
				$wcap_ei_no_email = ob_get_clean();
				$wcap_ei_no_email = apply_filters( 'wcap_custom_ei_template_without_email', $wcap_ei_no_email );

				// Load JS files.
				wp_enqueue_script( 'jquery' );
				wp_register_script( 'wcap-ei-popup', esc_url( WCAP_PLUGIN_URL ) . '/assets/js/frontend/wcap_exit_intent' . $suffix . '.js', array(), WCAP_PLUGIN_VERSION, true );
				$coupon_applied_msg = sprintf(
					// Translators: Coupon code name.
					__( 'Thank you. Coupon %s will be auto-applied to your cart.', 'woocommerce-ac' ),
					$atc_coupon_code
				);

				$redirect_link = $template_settings['wcap_quick_ck_link'];
				// If WPML is enabled, update the redirect link to the correct language.
				if ( function_exists( 'icl_register_string' ) ) {
					$curr_lang     = apply_filters( 'wpml_current_language', '' );
					$redirect_link = apply_filters( 'wpml_permalink', $template_settings['wcap_quick_ck_link'], $curr_lang ); 
				}
				$localize_params = array(
					'wcap_ei_modal_data'                => $wcap_ei_modal,
					'wcap_ei_modal_no_email_data'       => $wcap_ei_no_email,
					'wcap_ei_template_id'               => $template_settings['template_id'],
					'wcap_atc_head'                     => __( $template_settings['wcap_heading_section_text_email'], 'woocommerce-ac' ),
					'wcap_atc_email_place'              => __( $template_settings['wcap_email_placeholder_section_input_text'], 'woocommerce-ac' ),
					'wcap_atc_button_bg_color'          => $template_settings['wcap_button_color_picker'],
					'wcap_atc_button_text_color'        => $template_settings['wcap_button_text_color_picker'],
					'wcap_atc_popup_text_color'         => $template_settings['wcap_popup_text_color_picker'],
					'wcap_atc_popup_heading_color'      => $template_settings['wcap_popup_heading_color_picker'],
					'wcap_atc_non_mandatory_input_text' => __( $template_settings['wcap_non_mandatory_text'], 'woocommerce-ac' ),
					'wcap_quick_ck_heading'             => $template_settings['wcap_quick_ck_heading'],
					'wcap_ei_heading_text_color'        => $template_settings['wcap_quick_ck_heading_color'],
					'wcap_quick_ck_text'                => $template_settings['wcap_quick_ck_text'],
					'wcap_ei_text_color'                => $template_settings['wcap_quick_ck_text_color'],
					'wcap_ei_button_text'               => $template_settings['wcap_quick_ck_button_text'],
					'wcap_ei_button_bg_color'           => $template_settings['wcap_quick_ck_button_bg_color'],
					'wcap_ei_button_text_color'         => $template_settings['wcap_quick_ck_button_txt_color'],
					'wcap_ei_redirect_to_link'          => $redirect_link,
					'wcap_mandatory_text'               => __( 'Email address is mandatory to proceed.', 'woocommerce-ac' ),
					'wcap_mandatory_email_text'         => __( 'Please enter a valid email address.', 'woocommerce-ac' ),
					'wcap_ajax_add'                     => get_option( 'woocommerce_enable_ajax_add_to_cart' ),
					'wcap_ajax_url'                     => esc_url( WCAP_ADMIN_URL ),
					'wcap_image_file_name'              => $template_settings['wcap_heading_section_text_image'],
					'wcap_image_file_name_ei'           => $template_settings['wcap_heading_section_text_image_ei'],
					'wcap_image_path'                   => WCAP_WP_UPLOADS_URL,
					'wcap_atc_coupon_applied_msg'       => apply_filters( 'wcap_atc_coupon_applied_msg', $coupon_applied_msg, $template_settings['template_id'] ),
					'is_cart'                           => is_cart(),
					'wc_ajax_url'                       => WC()->ajax_url(),
					'wcap_coupon_msg_fadeout_timer'     => apply_filters( 'wcap_atc_coupon_applied_msg_fadeout_timer', 3000, $template_settings['template_id'] ),
					'wp_ajax_url'                       => esc_url( WCAP_ADMIN_AJAX_URL ),
				);
				if ( $template_settings['template_id'] > 0 ) {
					$localize_params['wcap_atc_text']   = __( $template_settings['wcap_text_section_text'], 'woocommerce-ac' );
					$localize_params['wcap_atc_button'] = __( $template_settings['wcap_button_section_input_text'], 'woocommerce-ac' );
				} else { // For the default template, we need to add the default texts for button & section text.
					$localize_params['wcap_atc_text']   = $template_settings['wcap_quick_ck_text'];
					$localize_params['wcap_atc_button'] = $template_settings['wcap_quick_ck_button_text'];
				}
				$localize_params = apply_filters( 'wcap_popup_params', $localize_params );
				wp_localize_script(
					'wcap-ei-popup',
					'wcap_ei_modal_param',
					$localize_params
				);
				wp_enqueue_script( 'wcap-ei-popup' );
			}
		}

		/**
		 * It will load all the front end scripts for the Add To Cart Popup Modal.
		 *
		 * @hook wp_enqueue_scripts
		 *
		 * @globals WP_Post $post
		 * @since 6.0
		 */
		public static function wcap_enqueue_scripts_atc_modal() {

			$execute = apply_filters( 'wcap_show_only_license_page', true );
			if ( ! $execute ) {
				return;
			}

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			$abandoned_id = wcap_get_cart_session( 'wcap_abandoned_id' );
			$atc_active   = wcap_get_popup_active_status( 'atc' );

			if ( wcap_get_cart_session( 'wcap_guest_email') == '' && wcap_get_cart_session( 'wcap_email_sent_id' ) == '' && ( 'on' === get_option( 'ac_capture_email_from_forms' ) || '' !== get_option( 'ac_capture_email_address_from_url' ) ) && ! is_user_logged_in() ) {
				wp_register_script( 'wcap_mailchimp_capture', esc_url( WCAP_PLUGIN_URL ) . '/assets/js/frontend/wcap_mailchimp_capture' . $suffix . '.js', array(), WCAP_PLUGIN_VERSION );
				wp_localize_script(
					'wcap_mailchimp_capture',
					'wcap_mailchimp_setting',
					array(
						'wcap_popup_setting' => $atc_active,
						'wcap_form_classes'  => str_replace( ' ', '', trim( get_option( 'ac_email_forms_classes' ) ) ),
						'wcap_ajax_url'      => esc_url( WCAP_ADMIN_AJAX_URL ),
						'wc_ajax_url'        => WC_AJAX::get_endpoint( '%%endpoint%%' ),
						'wcap_url_capture'   => get_option( 'ac_capture_email_address_from_url' ),
					)
				);
				wp_enqueue_script( 'wcap_mailchimp_capture' );
			}

			// The ATC scripts should be loaded only before the first product is added to the cart. Once a product has been added, AC id will be present, so return without loading.
			if ( $abandoned_id ) {
				return;
			}

			if ( wcap_get_cart_session( 'wcap_populate_email' ) != '' && ! $atc_active ) {
				$wcap_get_url_email_address = wcap_get_cart_session( 'wcap_populate_email' );
				$wcap_is_atc_enabled        = $atc_active;

				wp_enqueue_script( 'jquery' );
				wp_register_script( 'wcap-capture-url-email', esc_url( WCAP_PLUGIN_URL ) . '/assets/js/frontend/wcap_capture_url_email' . $suffix . '.js', array(), WCAP_PLUGIN_VERSION );
				wp_enqueue_script( 'wcap-capture-url-email' );
				wp_localize_script(
					'wcap-capture-url-email',
					'wcap_capture_url_email_param',
					array(
						'wcap_ajax_add'       => get_option( 'woocommerce_enable_ajax_add_to_cart' ),
						'wcap_populate_email' => $wcap_get_url_email_address,
						'wcap_ajax_url'       => esc_url( WCAP_ADMIN_URL ),
						'wc_ajax_url'         => WC_AJAX::get_endpoint( '%%endpoint%%' ),
						'wcap_is_atc_enabled' => $wcap_is_atc_enabled,
					)
				);
			}

			if ( $atc_active && wcap_get_cart_session( 'wcap_email_sent_id' ) == '' ) {
				global $post;

				if ( ! is_user_logged_in() ) {

					global $post;
					$page_id = 0;
					if ( is_shop() ) {
						$page_id = wc_get_page_id('shop');
					} else {
						$page_id = isset( $post->ID ) ? $post->ID : 0;
					}
					$wcap_atc_cache_check       = false;
					$single                     = false;
					$wcap_get_atc_template_list = array();
					$wcap_get_atc_template_list = json_decode( get_option( 'wcap_atc_templates', '' ), true );
					if ( is_array( $wcap_get_atc_template_list ) && count( $wcap_get_atc_template_list ) > 0 ) {
						if ( array_key_exists( 'single_no_rules', $wcap_get_atc_template_list ) ) {
							$wcap_atc_cache_check = true; // should remain inside the condition, always.
							$single               = true;
						} elseif ( array_key_exists( $page_id, $wcap_get_atc_template_list ) ) {
							$wcap_atc_cache_check = true; // should remain inside the condition, always.
						}
					}

					if ( $wcap_atc_cache_check ) {

						$template_to_use     = $single ? $wcap_get_atc_template_list['single_no_rules'] : $wcap_get_atc_template_list[ $page_id ];
						$template_settings   = $template_to_use['template_settings'];
						$custom_pages        = $template_to_use['custom_pages'];
						$allowed_products    = $template_to_use['allowed_products'];
						$disallowed_products = $template_to_use['disallowed_products'];
						$custom_pages_exc    = isset( $template_to_use['custom_pages_exc'] ) ? $template_to_use['custom_pages_exc'] : array();

					} else {
						$parent_id = 0;
						if ( is_tax( 'product_cat' ) ) { // If its the product category page, we need the category term ID & parent ID.
							$cat       = get_queried_object();
							$page_id   = $cat->term_id;
							$parent_id = $cat->parent;
						}
						// Localize based on the template that should be displayed.
						$template_settings = wcap_get_popup_template_for_page( $page_id, 'atc' );
						$cache_atc_data    = wcap_popup_display_list( $template_settings, $page_id, $parent_id );
						if ( $cache_atc_data ) {
							$custom_pages        = $cache_atc_data['custom_pages'];
							$allowed_products    = $cache_atc_data['allowed_products'];
							$disallowed_products = $cache_atc_data['disallowed_products'];
							$custom_pages_exc    = $cache_atc_data['custom_pages_exc'];

							if ( isset( $template_settings['single_no_rules'] ) && $template_settings['single_no_rules'] ) {
								$wcap_get_atc_template_list['single_no_rules'] = $cache_atc_data;
							} else {
								$wcap_get_atc_template_list[ $page_id ] = $cache_atc_data;
							}
							update_option( 'wcap_atc_templates', wp_json_encode( $wcap_get_atc_template_list ), false );
						} else {
							return;
						}
					}

					if ( ! ( is_cart() || is_checkout() ) ) {
						if ( is_array( $disallowed_products ) && in_array( $page_id, $disallowed_products ) ) {
							return;
						} elseif ( is_array( $allowed_products ) && count( $allowed_products ) > 0 && ! in_array( $page_id, $allowed_products ) ) {
							return;
						}
						if ( is_array( $custom_pages_exc ) && count( $custom_pages_exc ) > 0 && in_array( $page_id, $custom_pages_exc ) ) {
							return;
						}
					}

					$wcap_atc_modal = '';
					if ( ! is_cart() && ! is_checkout() ) {
						$page_id = get_the_ID();
						$template_settings = wcap_get_popup_template_for_page( $page_id, 'atc' );
						if (  $template_settings ) {

							wp_enqueue_script(
								'wcap_vue_js',
								esc_url( WCAP_PLUGIN_URL ) . '/assets/js/vue.min.js',
								array(),
								WCAP_PLUGIN_VERSION,
								true
							);
	
							ob_start();
							include WCAP_PLUGIN_PATH . '/includes/template/add_to_cart/wcap_add_to_cart.php';
							$wcap_atc_modal = ob_get_clean();
							
						}

						
					}

					$wcap_atc_modal = apply_filters( 'wcap_add_custom_atc_template', $wcap_atc_modal );

					if ( ( apply_filters('wcap_enable_custom_conditions_popup_modal', false) || is_shop() || is_home() || is_product_category() || is_front_page() || ( function_exists( 'is_demo' ) && is_demo() ) || in_array( $page_id, $custom_pages ) ) &&
					apply_filters( 'wcap_enable_pages_popup_modal', true ) ) {
						wp_dequeue_script( 'wc-add-to-cart' );
						wp_deregister_script( 'wc-add-to-cart' );
						wp_enqueue_script( 'jquery' );
						wp_register_script( 'wc-add-to-cart', esc_url( WCAP_PLUGIN_URL ) . '/assets/js/frontend/wcap_atc_modal' . $suffix . '.js', array(), WCAP_PLUGIN_VERSION, true );
						wp_enqueue_script( 'wc-add-to-cart' );

						$wcap_params                = self::wcap_atc_localize_params( $wcap_atc_modal, $template_settings );
						$wcap_params['enable_atc']  = $allowed_products;
						$wcap_params['disable_atc'] = $disallowed_products;
						wp_localize_script(
							'wc-add-to-cart',
							'wcap_atc_modal_param',
							$wcap_params
						);
					}
					$wcap_populate_email_address = null !== wcap_get_cart_session( 'wcap_populate_email' ) && '' != wcap_get_cart_session( 'wcap_populate_email' ) ? wcap_get_cart_session( 'wcap_populate_email' ) : '';
					
					if ( is_product() || ( function_exists( 'is_producto' ) && is_producto() ) ) {
						$wcap_product = wc_get_product( $post->ID );
						$active_theme = wp_get_theme();

						$theme_array = array( 'Divi', 'Flatsome', 'Avada','Furnob', 'Furnob Child', 'Twenty Twenty-Five', 'AFGolf 2026 Block Theme' ); // Issue #3259, #3452, #3468.

						if ( in_array( $active_theme->name, $theme_array, true ) || in_array( $active_theme->parent_theme, $theme_array, true ) ) {
							wp_enqueue_script( 'wc-cart-fragments' );
						}

						if ( $wcap_product->is_type( 'simple' ) || $wcap_product->is_type( 'course' ) || $wcap_product->is_type( 'subscription' ) || $wcap_product->is_type( 'composite' ) || $wcap_product->is_type( 'booking' ) || $wcap_product->is_type( 'appointment' ) || $wcap_product->is_type( 'bundle' ) ) {
							wp_dequeue_script( 'astra-single-product-ajax-cart' );
							wp_dequeue_script( 'wc-add-to-cart' );
							wp_deregister_script( 'wc-add-to-cart' );
							wp_enqueue_script( 'jquery' );
							wp_register_script( 'wcap_atc_single_simple_product', esc_url( WCAP_PLUGIN_URL ) . '/assets/js/frontend/wcap_atc_simple_single_page' . $suffix . '.js', array(), WCAP_PLUGIN_VERSION, array( 'in_footer' => true ) );
							wp_enqueue_script( 'wcap_atc_single_simple_product' );

							wp_localize_script(
								'wcap_atc_single_simple_product',
								'wcap_atc_modal_param',
								self::wcap_atc_localize_params( $wcap_atc_modal, $template_settings )
							);
						} elseif ( $wcap_product->is_type( 'variable' ) || $wcap_product->is_type( 'variable-subscription' ) ) {
							wp_dequeue_script( 'wc-add-to-cart' );
							wp_deregister_script( 'wc-add-to-cart' );
							// Variable Product
							if ( 'entrada' == get_option( 'template' ) ) {
								wp_register_script( 'wcap_entrada_atc_variable_page', esc_url( WCAP_PLUGIN_URL ) . '/assets/js/themes/wcap_entrada_atc_variable_page' . $suffix . '.js', array( 'jquery', 'wp-util' ), WCAP_PLUGIN_VERSION, array( 'in_footer' => true ) );
								wp_enqueue_script( 'wcap_entrada_atc_variable_page' );

								wp_localize_script(
									'wcap_entrada_atc_variable_page',
									'wcap_atc_modal_param',
									self::wcap_atc_localize_params( $wcap_atc_modal, $template_settings )
								);
							} elseif ( is_plugin_active( 'woo-variations-table-grid/woo-variations-table.php' ) && ! get_option( 'vartable_disabled' ) &&
								( get_post_meta( $wcap_product->get_id(), 'disable_variations_table', true ) == '' || get_post_meta( $wcap_product->get_id(), 'disable_variations_table', true ) != 1 ) ) {

								wp_dequeue_script( 'wc-add-to-cart-variation' );
								wp_deregister_script( 'wc-add-to-cart-variation' );

								wp_register_script( 'wc-add-to-cart', esc_url( WCAP_PLUGIN_URL ) . '/assets/js/frontend/wcap_atc_modal' . $suffix . '.js', array(), WCAP_PLUGIN_VERSION, array( 'in_footer' => true ) );
								wp_enqueue_script( 'wc-add-to-cart' );

								$wcap_populate_email_address = null !== wcap_get_cart_session( 'wcap_populate_email' ) && '' != wcap_get_cart_session( 'wcap_populate_email' ) ? wcap_get_cart_session( 'wcap_populate_email' ) : '';

								$wcap_params                = self::wcap_atc_localize_params( $wcap_atc_modal, $template_settings );
								$wcap_params['enable_atc']  = $allowed_products;
								$wcap_params['disable_atc'] = $disallowed_products;
								wp_localize_script(
									'wc-add-to-cart',
									'wcap_atc_modal_param',
									$wcap_params
								);
							} else {
								wp_dequeue_script( 'wc-add-to-cart-variation' );
								wp_deregister_script( 'wc-add-to-cart-variation' );
								do_action( 'wcap_before_atc_modal_single_product_scripts_loaded' );
								wp_register_script( 'wc-add-to-cart-variation', esc_url( WCAP_PLUGIN_URL ) . '/assets/js/frontend/wcap_atc_modal_single_product' . $suffix . '.js', array( 'jquery', 'wp-util' ), WCAP_PLUGIN_VERSION, array( 'in_footer' => true ) );

								wp_enqueue_script( 'wc-add-to-cart-variation' );

								wp_localize_script(
									'wc-add-to-cart-variation',
									'wcap_atc_modal_param_variation',
									self::wcap_atc_localize_params( $wcap_atc_modal, $template_settings )
								);
							}
						} elseif ( $wcap_product->is_type( 'grouped' ) ) {
							wp_enqueue_script( 'jquery' );
							wp_register_script( 'wcap_atc_group_product', esc_url( WCAP_PLUGIN_URL ) . '/assets/js/frontend/wcap_atc_group_page' . $suffix . '.js', array(), WCAP_PLUGIN_VERSION, array( 'in_footer' => true ) );
							wp_enqueue_script( 'wcap_atc_group_product' );

							wp_localize_script(
								'wcap_atc_group_product',
								'wcap_atc_modal_param',
								self::wcap_atc_localize_params( $wcap_atc_modal, $template_settings )
							);
						}
					} else if ( 'course' === get_post_type( $post ) ) {
						wp_dequeue_script( 'astra-single-product-ajax-cart' );
						wp_dequeue_script( 'wc-add-to-cart' );
						wp_deregister_script( 'wc-add-to-cart' );
						wp_enqueue_script( 'jquery' );
						wp_register_script( 'wcap_atc_single_simple_product', esc_url( WCAP_PLUGIN_URL ) . '/assets/js/frontend/wcap_atc_simple_single_page' . $suffix . '.js', array(), WCAP_PLUGIN_VERSION, array( 'in_footer' => true ) );
						wp_enqueue_script( 'wcap_atc_single_simple_product' );

						wp_localize_script(
							'wcap_atc_single_simple_product',
							'wcap_atc_modal_param',
							self::wcap_atc_localize_params( $wcap_atc_modal, $template_settings )
						);
					}

					if ( is_cart() && ( 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) || 'no' === get_option( 'woocommerce_cart_redirect_after_add' ) ) && !empty( $template_settings ) ) {
						wp_enqueue_script( 'jquery' );
						$active_theme = wp_get_theme();

						$theme_array  = array( 'Divi', 'Flatsome', 'Avada', 'EggShell', 'Chicken Coops','Furnob Child', 'Twenty Twenty-Five', 'AFGolf 2026 Block Theme' );
						if ( in_array ( $active_theme->parent_theme, $theme_array ) || in_array ( $active_theme->name, $theme_array ) ) { // Issue #3259, #3452, #3468.
							wp_enqueue_script( 'wc-cart-fragments' );
						}
						wp_register_script( 'wcap_atc_cart', esc_url( WCAP_PLUGIN_URL ) . '/assets/js/frontend/wcap_atc_cart_page' . $suffix . '.js', array(), WCAP_PLUGIN_VERSION, array( 'in_footer' => true ) );
						wp_enqueue_script( 'wcap_atc_cart' );
						wp_localize_script(
							'wcap_atc_cart',
							'wcap_atc_cart_param',
							array(
								'wcap_ajax_url' => esc_url( WCAP_ADMIN_URL ),
								'wcap_atc_template_id' => $template_settings['template_id'],
							)
						);
					}

					$atc_coupon_code = '';
					if ( isset( $template_settings['wcap_atc_auto_apply_coupon_enabled'] ) && 'on' === $template_settings['wcap_atc_auto_apply_coupon_enabled'] && 'pre-selected' === $template_settings['wcap_atc_coupon_type'] && 0 < $template_settings['wcap_atc_popup_coupon'] ) {
						$atc_coupon_code = get_the_title( $template_settings['wcap_atc_popup_coupon'] );
					}
					do_action( 'wcap_after_atc_scripts_loaded', $wcap_atc_modal, $wcap_populate_email_address, $atc_coupon_code );
				}
			}
			
		}

		/**
		 * Enqueue CSS file to be included at front end for Add To Cart Popup Modal.
		 *
		 * @hook wp_enqueue_scripts
		 *
		 * @since 6.0
		 */
		public static function wcap_enqueue_css_atc_modal() {

			$execute = apply_filters( 'wcap_show_only_license_page', true );
			if ( ! $execute ) {
				return;
			}
			// Check if count down timer needs to be included or no.
			$include_countdown = false;
			$atc_template_id   = wcap_get_cart_session( 'wcap_atc_template_id' );
			$ei_template_id    = wcap_get_cart_session( 'wcap_exit_intent_template_id' );
			if ( $atc_template_id > 0 || $ei_template_id > 0 ) {
				$template_settings = wcap_get_atc_template( $atc_template_id );
				if ( false === $template_settings ) {
					$template_settings = wcap_get_atc_template( $ei_template_id );
				}
				if ( $template_settings ) {
					$coupon_settings   = json_decode( $template_settings->coupon_settings );
					if ( 'on' === $coupon_settings->wcap_atc_auto_apply_coupon_enabled ) { // Coupons are enabled.
						$include_countdown = true;
					}
				}
			}
			$atc_active = wcap_get_popup_active_status( 'atc' );
			if ( $atc_active ) {
				$abandoned_id = wcap_get_cart_session( 'wcap_abandoned_id' );
				if ( ! is_cart() && ! is_checkout() && ! is_user_logged_in() && ! $abandoned_id ) {
					wp_enqueue_style( 'wcap_abandoned_details_modal', esc_url( WCAP_PLUGIN_URL ) . '/assets/css/frontend/wcap_atc_detail_modal.css', array(), WCAP_PLUGIN_VERSION );
				}
				if ( ( is_cart() || is_checkout() ) && ! is_user_logged_in() && $include_countdown ) {
					wp_enqueue_style( 'wcap_countdown_timer', esc_url( WCAP_PLUGIN_URL ) . '/assets/css/frontend/wcap_atc_countdown_timer.css', array(), WCAP_PLUGIN_VERSION );
					wp_enqueue_style( 'wcap-font-awesome', esc_url( WCAP_PLUGIN_URL ) . '/assets/css/admin/font-awesome.css', array(), WCAP_PLUGIN_VERSION );
					wp_enqueue_style( 'wcap-font-awesome-min', esc_url( WCAP_PLUGIN_URL ) . '/assets/css/admin/font-awesome.min.css', array(), WCAP_PLUGIN_VERSION );
				}
			}
			$ei_enabled   = wcap_get_popup_active_status( 'exit_intent' );
			$ei_displayed = wcap_get_cart_session( 'wcap_exit_intent_template_id' );
			if ( $ei_enabled && ! $ei_displayed ) {
				wp_enqueue_style( 'wcap_abandoned_details_modal', esc_url( WCAP_PLUGIN_URL ) . '/assets/css/frontend/wcap_atc_detail_modal.css', array(), WCAP_PLUGIN_VERSION );
			}
			if ( $ei_enabled && $ei_displayed > 0 && $include_countdown ) {
				wp_enqueue_style( 'wcap_countdown_timer', esc_url( WCAP_PLUGIN_URL ) . '/assets/css/frontend/wcap_atc_countdown_timer.css', array(), WCAP_PLUGIN_VERSION );
			}
		}

		/**
		 * Load CSS file to be included at WordPress Admin.
		 *
		 * @hook admin_enqueue_scripts
		 *
		 * @param   int $hook Hook suffix for the current admin page
		 * @globals mixed $pagenow
		 * @since   6.0
		 */
		public static function wcap_enqueue_scripts_css( $hook ) {
			global $pagenow;

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			$page = isset( $_GET['page'] ) ? $_GET['page'] : '';

			if ( $hook != 'woocommerce_page_woocommerce_ac_page' && 'index.php' === $pagenow ) {
				wp_enqueue_style( 'wcap-dashboard', esc_url( WCAP_PLUGIN_URL ) . '/assets/css/admin/wcap_style.min.css', array(), WCAP_PLUGIN_VERSION );
				return;
			} elseif ( $page === 'woocommerce_ac_page' ) {
				
				$action = '';
				if ( isset( $_GET['action'] ) ) {
					$action = Wcap_Common::wcap_get_action();
				}

				$section      = isset( $_GET['section'] ) ? $_GET['section'] : '';
				$wcap_section = isset( $_GET['wcap_section'] ) ? $_GET['wcap_section'] : 'wcap_general_settings';
				
				wp_enqueue_style( 'wcap-bootstrap', esc_url( WCAP_PLUGIN_URL ) . '/assets/css/admin/bootstrap.min.css', array(), WCAP_PLUGIN_VERSION	);
				wp_enqueue_style( 'wcap-bootstrap-tokenfield', esc_url( WCAP_PLUGIN_URL ) . '/assets/css/admin/bootstrap-tokenfield.css', array(), WCAP_PLUGIN_VERSION );
				wp_enqueue_style( 'wcap-admin-style', esc_url( WCAP_PLUGIN_URL ) . '/assets/css/admin/wcap-admin-style.css', array(), WCAP_PLUGIN_VERSION );
				wp_enqueue_style( 'wcap-checkbox', esc_url( WCAP_PLUGIN_URL ) . '/assets/css/admin/checkbox.css', array(), WCAP_PLUGIN_VERSION );
				
				if ( 'listcart' == $action || 'emailstats' == $action ) {
					wp_enqueue_style( 'wcap_abandoned_details_modal', esc_url( WCAP_PLUGIN_URL ) . '/assets/css/admin/wcap_abandoned_cart_detail_modal.min.css', array(), WCAP_PLUGIN_VERSION );
					wp_enqueue_style( 'wcap_abandoned_details', esc_url( WCAP_PLUGIN_URL ) . '/assets/css/admin/wcap_view_order_button.min.css', array(), WCAP_PLUGIN_VERSION );
				}
				
			}
		}

		/**
		 * Localize Params for ATC.
		 *
		 * @param string $wcap_atc_modal HTML string for ATC.
		 * @return array
		 */
		public static function wcap_atc_localize_params( $wcap_atc_modal, $template_settings ) {
			$wcap_populate_email_address = null !== wcap_get_cart_session( 'wcap_populate_email' ) && '' != wcap_get_cart_session( 'wcap_populate_email' ) ? wcap_get_cart_session( 'wcap_populate_email' ) : '';

			if ( empty( $template_settings ) ) {
				return;
			}
			$atc_coupon_code = '';
			if ( 'on' === $template_settings['wcap_atc_auto_apply_coupon_enabled'] && 'pre-selected' === $template_settings['wcap_atc_coupon_type'] && 0 < $template_settings['wcap_atc_popup_coupon'] ) {
				$atc_coupon_code = get_the_title( $template_settings['wcap_atc_popup_coupon'] );
			}
			$template_settings['wcap_phone_placeholder'] = ! isset( $template_settings['wcap_phone_placeholder'] ) ? 'Please enter your phone number in E.164 format': $template_settings['wcap_phone_placeholder']; 
			$coupon_applied_msg                          = sprintf(
				// Translators: Coupon code name.
				__( 'Thank you. Coupon %s will be auto-applied to your cart.', 'woocommerce-ac' ),
				$atc_coupon_code
			);
			$localize_params = array(
				'wcap_atc_modal_data'               => $wcap_atc_modal,
				'wcap_atc_template_id'              => $template_settings['template_id'],
				'wcap_atc_head'                     => __( $template_settings['wcap_heading_section_text_email'], 'woocommerce-ac' ),
				'wcap_atc_text'                     => __( $template_settings['wcap_text_section_text'], 'woocommerce-ac' ),
				'wcap_atc_email_place'              => __( $template_settings['wcap_email_placeholder_section_input_text'], 'woocommerce-ac' ),
				'wcap_atc_button'                   => __( $template_settings['wcap_button_section_input_text'], 'woocommerce-ac' ),
				'wcap_atc_button_bg_color'          => $template_settings['wcap_button_color_picker'],
				'wcap_atc_button_text_color'        => $template_settings['wcap_button_text_color_picker'],
				'wcap_atc_popup_text_color'         => $template_settings['wcap_popup_text_color_picker'],
				'wcap_atc_popup_heading_color'      => $template_settings['wcap_popup_heading_color_picker'],
				'wcap_atc_non_mandatory_input_text' => __( $template_settings['wcap_non_mandatory_text'], 'woocommerce-ac' ),
				'wcap_atc_mandatory_email'          => $template_settings['wcap_atc_mandatory_email'],
				'wcap_switch_atc_phone_mandatory'   => $template_settings['wcap_switch_atc_phone_mandatory'],
				'wcap_ajax_add'                     => get_option( 'woocommerce_enable_ajax_add_to_cart' ),
				'wcap_close_icon_add_to_cart'       => get_option( 'wcap_atc_close_icon_add_product_to_cart', 'off' ),
				'wcap_populate_email'               => $wcap_populate_email_address,
				'wcap_ajax_url'                     => esc_url( WCAP_ADMIN_URL ),
				'wcap_image_path'                   => WCAP_WP_UPLOADS_URL,
				'wcap_image_file_name'              => $template_settings['wcap_heading_section_text_image'],
				'wcap_mandatory_text'               => __( 'Email address is mandatory for adding product to the cart.', 'woocommerce-ac' ),
				'wcap_phone_mandatory_text'         => __( 'Please enter a valid phone number.', 'woocommerce-ac' ),
				'wcap_mandatory_email_text'         => __( 'Please enter a valid email address.', 'woocommerce-ac' ),
				'wcap_atc_coupon_applied_msg'       => apply_filters( 'wcap_atc_coupon_applied_msg', $coupon_applied_msg, $template_settings['template_id'] ),
				'is_cart'                           => is_cart(),
				'wc_ajax_url'                       => WC()->ajax_url(),
				'wcap_atc_phone_place'              => __( $template_settings['wcap_phone_placeholder'], 'woocommerce-ac' ),
				'wcap_coupon_msg_fadeout_timer'     => apply_filters( 'wcap_atc_coupon_applied_msg_fadeout_timer', 3000, $template_settings['template_id'] ),
			);

			$localize_params = apply_filters( 'wcap_popup_params', $localize_params );

			return $localize_params;
		}

		public static function wcap_template_params() {

			$localized_array = array();

			for ( $temp = 1; $temp < 13; $temp++ ) {
				$temp_obj       = new stdClass();
				$temp_obj->id   = $temp;
				$temp_obj->url  = esc_url( WCAP_PLUGIN_URL ) . '/assets/images/templates/template_' . $temp . '.png';
				$temp_obj->html = esc_url( WCAP_PLUGIN_URL ) . '/assets/html/templates/template_' . $temp . '.html';

				array_push( $localized_array, $temp_obj );
			}

			return $localized_array;
		}

		/**
		 * JS files to capture guest carts in WC Checkout blocks.
		 *
		 * @since 9.4.0
		 */
		public static function wcap_js_checkout_blocks() {

			$execute = apply_filters( 'wcap_show_only_license_page', true );
			if ( ! $execute ) {
				return;
			}

			$suffix     = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			$guest_cart = get_option( 'ac_disable_guest_cart_email' );

			$enable_gdpr = get_option( 'wcap_enable_gdpr_consent', '' );

			$session_gdpr = wcap_get_cart_session( 'wcap_cart_tracking_refused' );
			$show_gdpr    = isset( $session_gdpr ) && 'yes' === $session_gdpr ? false : true;

			if ( is_checkout() && 'on' !== $guest_cart && $show_gdpr ) {

				$script_path       = '/build/blocks-guest-capture.js';
				$script_asset_path = WCAP_PLUGIN_PATH . '/build/blocks-guest-capture.asset.php';
				$script_asset      = file_exists( $script_asset_path )
					? require $script_asset_path
					: array(
						'dependencies' => array(),
						'version'      => '1.0',
					);
				$script_url        = esc_url( WCAP_PLUGIN_URL ) . '/' . $script_path;

				wp_register_script(
					'wcap-guest-user-blocks',
					$script_url,
					$script_asset['dependencies'],
					$script_asset['version'],
					true
				);

				$vars = array();

				$vars['ajax_url']      = esc_url( WCAP_ADMIN_AJAX_URL );
				$vars['custom_fields'] = apply_filters( 'wcap_detect_more_fields_at_checkout', '' );
				$vars['user_id']       = 0;
				if ( wcap_get_cart_session( 'wcap_user_id' ) && wcap_get_cart_session( 'wcap_user_id' ) >= 63000000 ) {
					$user_id   = wcap_get_cart_session( 'wcap_user_id' );
					$firstname = wcap_get_cart_session( 'wcap_guest_first_name' ) ? wcap_get_cart_session( 'wcap_guest_first_name' ) : '';
					$lastname  = wcap_get_cart_session( 'wcap_guest_last_name' ) ? wcap_get_cart_session( 'wcap_guest_last_name' ) : '';
					$email     = wcap_get_cart_session( 'wcap_guest_email' ) ? wcap_get_cart_session( 'wcap_guest_email' ) : '';
					$phone     = wcap_get_cart_session( 'wcap_guest_phone' ) ? wcap_get_cart_session( 'wcap_guest_phone' ) : ''; 

					$vars['first_name'] = $firstname;
					$vars['last_name']  = $lastname;
					$vars['email']      = $email;
					$vars['phone']      = $phone;
					$vars['user_id']    = $user_id;
				}
				wp_localize_script( 'wcap-guest-user-blocks', 'wcap_guest_capture_blocks_params', $vars );
				wp_enqueue_script( 'wcap-guest-user-blocks' );

			}

		}
		/**
		 * Load Blocks JS files.
		 *
		 * @since 9.4.0
		 */
		public static function wcap_load_guest_blocks_scripts() {

			$execute = apply_filters( 'wcap_show_only_license_page', true );
			if ( ! $execute ) {
				return;
			}
			// SMS consent checkbox.
			if ( 'on' === get_option( 'wcap_enable_sms_consent' ) && 'on' !== get_option( 'ac_disable_guest_cart_email' ) ) {
				require_once WCAP_INCLUDE_PATH . 'blocks/wcap-sms-consent-blocks-integration.php';
				add_action(
					'woocommerce_blocks_checkout_block_registration',
					function ( $integration_registry ) {
						$integration_registry->register( new Wcap_SMS_Consent_Blocks_Integration() );
					}
				);
			}

			// GDPR Notice below the email field.
			if ( 'on' === get_option( 'wcap_enable_gdpr_consent' ) ) {
				require_once WCAP_INCLUDE_PATH . 'blocks/wcap-gdpr-emails-blocks-integration.php';
				add_action(
					'woocommerce_blocks_checkout_block_registration',
					function ( $integration_registry ) {
						$integration_registry->register( new Wcap_GDPR_Emails_Blocks_Integration() );
					}
				);
			}

			//WhatsApp integration.
			if ( 'on' === get_option( 'wcap_enable_whatsapp_reminders' ) && 'on' !== get_option( 'ac_disable_guest_cart_email' ) ) {
				require_once WCAP_INCLUDE_PATH . 'blocks/wcap-whatsapp-consent-blocks-integration.php';
				add_action(
					'woocommerce_blocks_checkout_block_registration',
					function ( $integration_registry ) {
						$integration_registry->register( new Wcap_WhatsApp_Consent_Blocks_Integration() );
					}
				);
			}
		}
	}
}
