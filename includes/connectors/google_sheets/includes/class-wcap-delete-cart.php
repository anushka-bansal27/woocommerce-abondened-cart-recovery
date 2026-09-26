<?php
/**
 * Delete cart record in Google Sheets.
 *
 * @package Abandon Cart for WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Wcap_Google_Sheets_Delete_Cart' ) ) {

	/**
	 * Delete Cart Action.
	 */
	class Wcap_Google_Sheets_Delete_Cart {

		/**
		 * Class instance.
		 *
		 * @var $ins
		 */
		private $ins = null;

		/**
		 * Construct.
		 */
		public function __construct() {
			$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( 'google_sheets' );
			if ( empty( $connector_settings ) || ( isset( $connector_settings['status'] ) && 'active' !== $connector_settings['status'] ) || ( isset( $connector_settings['wcap_gsheets_refresh_token'] ) && ( '' === $connector_settings['wcap_gsheets_refresh_token'] || null === $connector_settings['wcap_gsheets_refresh_token'] ) ) ) {
				return false;
			}
			add_action( 'wcap_cart_cycle_completed', array( &$this, 'wcap_delete_cart' ), 10, 1 );
		}

		/**
		 * Get Instance.
		 */
		public static function get_instance() {
			if ( null === self::$ins ) {
				self::$ins = new self();
			}

			return self::$ins;
		}

		/**
		 * Delete Cart.
		 *
		 * @param int $cart_id - Abandoned ID.
		 */
		public function wcap_delete_cart( $cart_id ) {

			if ( $cart_id > 0 ) {

				// Get the row ID for Google Sheet.
				$connector_inst           = Wcap_Google_Sheets::get_instance();
				$get_existing_row_from_db = $connector_inst->wcap_get_row_id( $cart_id );

				// Update only if the cart abandoned record is found.
				if ( $get_existing_row_from_db ) {

					$spreadsheet_id = get_option( 'wcap_google_sheet_id' );

					$explode_row = explode( '-', $get_existing_row_from_db );
					if ( isset( $explode_row ) && 2 === count( $explode_row ) ) {
						$sheet_name = isset( $explode_row[0] ) && '' !== $explode_row[0] ? $explode_row[0] : '';
						$row_number = isset( $explode_row[1] ) && $explode_row[1] > 1 ? $explode_row[1] : 0;
						if ( '' === $sheet_name || $row_number < 1 ) {
							return;
						}
					} else {
						return;
					}

					$sheet_data['row_number'] = $row_number;
					// Create a new Google client.
					$wcap_oauth = Wcap_Google_Oauth::get_instance();
					$client     = $wcap_oauth->get_client();

					// Create a new Google Sheets service.
					$service = new Google_Service_Sheets( $client );

					// Specify the range for the row you want to delete.
					$range = 'Sheet1!A' . $row_number . ':O' . $row_number; // A through O in v9.4.0, change if needed.

					// Make the request to delete the row.
					try {
						$service->spreadsheets_values->clear( $spreadsheet_id, $range, new Google_Service_Sheets_ClearValuesRequest() );
					} catch ( Exception $e ) {
						return false;
					}
				}
			}
		}
	}
}
new Wcap_Google_Sheets_Delete_Cart();
