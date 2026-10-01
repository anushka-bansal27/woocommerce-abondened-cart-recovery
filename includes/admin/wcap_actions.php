<?php
/**
 * Handle all the delete, restore and trash actions.
 *
 * @author  LittleCodr
 * @package Abandoned-Cart-Pro-for-WooCommerce/Admin/Action
 * @since 5.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wcap_Actions' ) ) {
	/**
	 * Handle all the delete, restore and trash actions.
	 *
	 * @since 5.0
	 */
	class Wcap_Actions {
		/**
		 * This function will handle all the delete, restore and trash actions
		 * @param string $wcap_action First action the above drop down on list page
		 * @param string $wcap_action_two Second action the below drop down on list page
		 * @since 5.0
		 */
		public static function wcap_perform_action( $wcap_action, $wcap_action_two ) {

			$updated = false;
			$class = new Wcap_Actions_Handler();
			// Detect when a bulk action is being triggered on abandoned orders page.
			if ( 'wcap_abandoned_delete' === $wcap_action || 'wcap_abandoned_delete' === $wcap_action_two ) {
				$ids = Wcap_Common::wcap_get_abandoned_cart_ids_from_get();
				if ( ! is_array( $ids ) ){
					$ids = array( $ids );
				}
				$wcap_abandoned_selected_order_count = count( $ids );
				foreach ( $ids as $id ) {

					$updated = $class->wcap_delete_bulk_action_handler_function( $id, $wcap_abandoned_selected_order_count );
				}
			}

			// Detect when a bulk action is being triggered on abandoned orders page.
			if ( 'wcap_abandoned_trash' === $wcap_action || 'wcap_abandoned_trash' === $wcap_action_two ) {
				$ids = Wcap_Common::wcap_get_abandoned_cart_ids_from_get();
				if ( ! is_array( $ids ) ){
					$ids = array( $ids );
				}
				$wcap_abandoned_selected_order_count = count ( $ids );
				foreach ( $ids as $id ) {
					$updated = $class->wcap_abandoned_trash_bulk_action_handler( $id, $wcap_abandoned_selected_order_count );
				}
			}

			if ( 'wcap_abandoned_trash_visitor' === $wcap_action || 'wcap_abandoned_trash_visitor' === $wcap_action_two ) {
				$updated = $class->wcap_abandoned_trash_visitor_bulk_action_handler();
			}

			if ( 'wcap_abandoned_trash_guest' === $wcap_action || 'wcap_abandoned_trash_guest' === $wcap_action_two ) {
				$updated = $class->wcap_abandoned_trash_guest_bulk_action_handler();
			}

			if ( 'wcap_abandoned_trash_registered' === $wcap_action || 'wcap_abandoned_trash_registered' === $wcap_action_two ) {
				$updated = $class->wcap_abandoned_trash_registered_bulk_action_handler();
			}

			if ( 'wcap_abandoned_trash_all' === $wcap_action || 'wcap_abandoned_trash_all' === $wcap_action_two ) {
				$updated = $class->wcap_abandoned_trash_all_bulk_action_handler();				
			}

			if ( 'wcap_sync_manually' === $wcap_action || 'wcap_sync_manually' === $wcap_action_two ) {
				$updated = $class->wcap_sync_carts_manually_bulk_action_handler();
			}
			// Detect when a bulk action is being triggered on recovered orders page.
			if ( 'wcap_abandoned_restore' === $wcap_action || 'wcap_abandoned_restore' === $wcap_action_two ) {

				$ids = Wcap_Common::wcap_get_abandoned_cart_ids_from_get();
				if ( ! is_array( $ids ) ){
					$ids = array( $ids );
				}
				$wcap_restore_selected_order_count = count( $ids );

				foreach ( $ids as $id ) {
					$updated = $class->wcap_abandoned_restore_bulk_action_handler( $id, $wcap_restore_selected_order_count );
				}
			}

			// Detect when a bulk action is being triggered on recovered orders page.
			if ( 'wcap_rec_delete' === $wcap_action || 'wcap_rec_delete' === $wcap_action_two ) {
				$ids = Wcap_Common::wcap_get_abandoned_cart_ids_from_get();
				if ( ! is_array( $ids ) ){
					$ids = array( $ids );
				}
				foreach ( $ids as $id ) {

					$class->wcap_recovered_delete_bulk_action_handler( $id );
				}
			}

			// Detect when a bulk action is being triggered on recovered orders page.
			if ( 'wcap_rec_restore' === $wcap_action || 'wcap_rec_restore' === $wcap_action_two ) {
				$ids = Wcap_Common::wcap_get_abandoned_cart_ids_from_get();
				if ( ! is_array( $ids ) ) {
					$ids = array( $ids );
				}
				$wcap_restore_selected_order_count = count( $ids );
				foreach ( $ids as $id ) {

					$updated = $class->wcap_recovered_restore_bulk_action_handler( $id, $wcap_restore_selected_order_count );
				}
			}

			// Detect when a bulk action is being triggered on abandoned orders page.
			$wcap_trash_selected_order_count = 0;
			if ( 'wcap_rec_trash' === $wcap_action || 'wcap_rec_trash' === $wcap_action_two ) {
				$ids = Wcap_Common::wcap_get_abandoned_cart_ids_from_get();
				if ( ! is_array( $ids ) ) {
					$ids = array( $ids );
				}
				$wcap_trash_selected_order_count = count( $ids );
				foreach ( $ids as $id ) {

					$updated = $class->wcap_recovered_trash_bulk_action_handler( $id, $wcap_trash_selected_order_count );
				}
			}

			// Detect when a bulk action is being triggered on templates page.
			if ( 'wcap_delete_template' === $wcap_action || 'wcap_delete_template' === $wcap_action_two ) {
				$ids = Wcap_Common::wcap_get_template_ids_from_get();
				if ( ! is_array( $ids ) ) {
					$ids = array( $ids );
				}
				foreach ( $ids as $id ) {

					$class->wcap_delete_template_bulk_action_handler_function( $id );
				}
			}

			// Delete SMS Template - Bulk Action
			if ( 'wcap_delete_sms_template' === $wcap_action || 'wcap_delete_sms_template' === $wcap_action_two || 
				'wcap_delete_fb_template' === $wcap_action || 'wcap_delete_fb_template' === $wcap_action_two ||
				'wcap_delete_whatsapp_template' === $wcap_action || 'wcap_delete_whatsapp_template' === $wcap_action_two ) {

				$ids = Wcap_Common::wcap_get_template_ids_from_get();
				if ( ! is_array( $ids ) ) {
					$ids = array( $ids );
				}
				foreach ( $ids as $id ) {
					Wcap_SMS::wcap_delete_template_data( $id );
				}

				if ( 'wcap_delete_sms_template' === $wcap_action || 'wcap_delete_sms_template' === $wcap_action_two ) {
					wp_safe_redirect( admin_url( '/admin.php?page=woocommerce_ac_page&action=cart_recovery&section=sms&wcap_sms_template_deleted=YES' ) );
				} elseif ( 'wcap_delete_fb_template' === $wcap_action || 'wcap_delete_fb_template' === $wcap_action_two ) {
					wp_safe_redirect( admin_url( '/admin.php?page=woocommerce_ac_page&action=cart_recovery&section=fb_templates&wcap_fb_template_deleted=YES' ) );
				}
			}

			// Send Bulk Manual Emails.
			if ( 'emailtemplates&mode=wcap_manual_email' === $wcap_action ) {

				$ids = isset( $_GET['abandoned_order_id'] ) ? sanitize_text_field( $_GET['abandoned_order_id'] ) : false;// phpcs:disable

				if ( ! is_array( $ids ) ) {
					$ids = array( $ids );
				}
				$ids_list = implode( ',', $ids );

				wp_safe_redirect( admin_url( "/admin.php?page=woocommerce_ac_page&action=cart_recovery&section=emailtemplates&mode=wcap_manual_email&abandoned_order_id=$ids_list" ) );
			}

			$mode = isset( $_GET['mode'] ) ? sanitize_text_field( $_GET['mode'] ) : '';
			// Unsubscribe.
			if ( 'listcart' === $wcap_action && 'unsubscribe' === $mode ) {
				$wcap_cart_id = sanitize_text_field( $_GET['abandoned_order_id'] );
				$updated = WCAP_CART_HISTORY_MODEL::wcap_unsubscribe_cart( $wcap_cart_id );				
			}
			// Sync Manually.
			if ( 'listcart' === $wcap_action && 'sync_manually' === $mode ) {
				$wcap_cart_id = sanitize_text_field( $_GET['abandoned_order_id'] );
				if ( $wcap_cart_id > 0 ) {
					$connectors_common = Wcap_Connectors_Common::get_instance();
					$connectors_common->wcap_sync_cart( $wcap_cart_id );
				}
				$updated= true;// phpcs:enable
			}
			
			if ( 'wcap_empty_trash' === $wcap_action || 'wcap_empty_trash' === $wcap_action_two ) {
				global $wpdb;
				$updated = $wpdb->delete( // phpcs:ignore
					WCAP_ABANDONED_CART_HISTORY_TABLE,
					array(
						'wcap_trash' => '1',
					)
				);
				$updated = true;
			}
			
			return $updated;
		}
	}
}
