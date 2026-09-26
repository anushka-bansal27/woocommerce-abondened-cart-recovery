<?php
/**
 * Create a new Spreadsheet in Google Sheets once connection is made.
 *
 * @package Abandon Cart Pro for WooCommerce.
 * @since 9.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Wcap_Create_Spreadsheet' ) ) {

	/**
	 * Create a new spreadsheet in Google Sheets.
	 */
	class Wcap_Create_Spreadsheet {

		/**
		 * Class instance.
		 *
		 * @var $ins
		 */
		public static $ins = null;

		/**
		 * Construct
		 */
		public function __construct() {
		}

		/**
		 * Get instance of the class.
		 */
		public static function get_instance() {
			if ( null === self::$ins ) {
				self::$ins = new self; // phpcs:ignore
			}

			return self::$ins;
		}

		/**
		 * Create a New Spreadsheet.
		 */
		public function wcap_create_spreadsheet() {

			// Check if a spreadsheet is already present.
			if ( ! get_option( 'wcap_google_sheet_id' ) ) {
				// Create a new Google client.
				$wcap_oauth = Wcap_Google_Oauth::get_instance();
				$client     = $wcap_oauth->get_client();

				// Create a new Google Sheets service.
				$service = new Google_Service_Sheets( $client );

				$oauth_settings = json_decode( get_option( 'wcap_google_sheets_connector' ), true );
				$sheet_title    = isset( $oauth_settings['sheet_title'] ) && '' !== $oauth_settings['sheet_title'] ? $oauth_settings['sheet_title'] : 'Abandoned Carts Pro - Cart Data';
				$sheet_title    = apply_filters( 'wcap_google_sheet_title', $sheet_title );
				// Define the properties of the new spreadsheet.
				$spreadsheet = new Google_Service_Sheets_Spreadsheet(
					array(
						'properties' => array(
							'title' => $sheet_title, // Replace with your desired title.
						),
					)
				);

				// Attempt to create the new spreadsheet.
				try {
					$spreadsheet    = $service->spreadsheets->create( $spreadsheet );
					$spreadsheet_id = $spreadsheet->spreadsheetId; // phpcs:ignore
					update_option( 'wcap_google_sheet_id', $spreadsheet_id );
				} catch ( Exception $e ) {
					return $e->getMessage();
				}

				// Add the header row if creation was a success.
				if ( isset( $spreadsheet_id ) && $spreadsheet_id ) {
					$this->wcap_insert_header( $spreadsheet_id, $client, $service, 'Sheet1' );
					return true;
				}
			}
		}

		/**
		 * Insert a header in the new sheet.
		 *
		 * @param int    $spreadsheet_id - Spread Sheet ID.
		 * @param obj    $client - Google Client.
		 * @param obj    $service - Google Client Service.
		 * @param string $sheet_name - Sheet Name.
		 */
		public function wcap_insert_header( $spreadsheet_id, $client, $service, $sheet_name ) {

			$store_currency = get_woocommerce_currency();
			// Define the values you want to insert.
			$values = array(
				array( 'Cart ID', 'User ID', 'User Type', 'Cart Details', 'Cart Total (' . $store_currency . ')', 'Cart Status', 'WooCommerce Order ID', 'Abandoned Date', 'Abandoned Time', 'Abandoned Cart Shopper First Name', 'Abandoned Cart Shopper Last Name', "Abandoned Cart Shopper's Email", "Abandoned Cart Shopper's Phone", "Abandoned Cart Shopper's Country", 'Checkout Link' ), // Modify when the columns are changed.
			);

			// Define the range where you want to insert the data.
			$range = $sheet_name . '!A1:O1'; // Update the sheet name and range as needed.
			// Prepare the request to insert the data.
			$request_body = new Google_Service_Sheets_ValueRange(
				array(
					'values' => $values,
				)
			);

			// Make the request to insert the data.
			try {
				$result = $service->spreadsheets_values->append(
					$spreadsheet_id,
					$range,
					$request_body,
					array(
						'valueInputOption' => 'RAW',
					)
				);

				// Check the response for success.
				if ( $result->updates->updatedCells > 0 ) {
					update_option( 'wcap_google_sheet_name', $sheet_name );
					$header_inserted = true;
				} else {
					update_option( 'wcap_google_sheet_name', '' );
					$header_inserted = false;
				}
			} catch ( Exception $e ) {
				update_option( 'wcap_google_sheet_name', '' );
				$header_inserted = false;
			}

			if ( $header_inserted ) {
				$this->wcap_mark_header_as_bold( $client, $service, $spreadsheet_id, $sheet_name );
			}
		}

		/**
		 * Mark the header as bold.
		 *
		 * @param obj    $client - Google Client.
		 * @param obj    $service - Google Client Service.
		 * @param int    $spreadsheet_id - Google Sheet ID.
		 * @param string $sheet_name - Sheet name.
		 */
		public function wcap_mark_header_as_bold( $client, $service, $spreadsheet_id, $sheet_name ) {
			$sheet_index = filter_var( $sheet_name, FILTER_SANITIZE_NUMBER_INT ) - 1;
			// Set the row data as bold for the header.
			$bold_format = new Google_Service_Sheets_TextFormat(
				array(
					'bold' => true,
				)
			);

			// Specify the range of the first row.
			$header_range = $sheet_name . '!1:1'; // Assuming the first row.

			// Prepare the request to update the first row as bold.
			$update_request = new Google_Service_Sheets_Request(
				array(
					'repeatCell' => array(
						'range'  => array(
							'sheetId'       => $service->spreadsheets->get( $spreadsheet_id )->sheets[ $sheet_index ]->properties->sheetId,
							'startRowIndex' => 0,
							'endRowIndex'   => 1,
						),
						'cell'   => array(
							'userEnteredFormat' => array(
								'textFormat' => $bold_format,
							),
						),
						'fields' => 'userEnteredFormat.textFormat.bold',
					),
				)
			);

			// Create a BatchUpdateSpreadsheetRequest with the update request.
			$batch_update_request = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(
				array(
					'requests' => array(
						$update_request,
					),
				)
			);

			// Make the request to mark the first row as bold.
			try {
				$service->spreadsheets->batchUpdate( $spreadsheet_id, $batch_update_request );
			} catch ( Exception $e ) {
				return false;
			}
		}

		/**
		 * Check whether the 5k rows limit is reached or no. If yes, create a new sheet.
		 *
		 * @param obj $client - Google Client.
		 * @param obj $service - Google Client Service.
		 * @param int $spreadsheet_id - Google Sheet ID.
		 */
		public function wcap_check_sheet_record_limit( $client, $service, $spreadsheet_id ) {

			// Specify the threshold for the number of rows in a sheet.
			$row_threshold = 5001; // 1 for the header record.

			$sheet_name = get_option( 'wcap_google_sheet_name' );
			// Specify the range for the entire sheet (assuming columns A to N, change if needed).
			$range = $sheet_name . '!A:O';

			// Make the request to get the values from the entire sheet.
			try {
				$response = $service->spreadsheets_values->get( $spreadsheet_id, $range );
				$values   = $response->getValues();

				// Check if there are any values.
				if ( empty( $values ) ) {
					return $sheet_name;
				} else {
					// Get the last populated row number.
					$last_row_number = count( $values );

					// Compare with the threshold and create a new sheet if needed.
					if ( $last_row_number >= $row_threshold ) {
						// Generate a new sheet name.
						$new_sheet_name = 'Sheet' . ( count( $service->spreadsheets->get( $spreadsheet_id )->getSheets() ) + 1 );

						// Create a new sheet.
						$requests = array(
							new Google_Service_Sheets_Request(
								array(
									'addSheet' => array(
										'properties' => array(
											'title' => $new_sheet_name,
										),
									),
								)
							),
						);

						$batch_update_request = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(
							array(
								'requests' => $requests,
							)
						);
						$service->spreadsheets->batchUpdate( $spreadsheet_id, $batch_update_request );
						$this->wcap_insert_header( $spreadsheet_id, $client, $service, $new_sheet_name );
						return $new_sheet_name;

					} else {
						return $sheet_name;
					}
				}
			} catch ( Exception $e ) {
				return false;
			}
		}

		/**
		 * Edit the Sheet title.
		 *
		 * @param string $new_title - New Title.
		 */
		public function wcap_edit_sheet_title( $new_title ) {

			$spreadsheet_id = get_option( 'wcap_google_sheet_id', '' );
			// A spreadsheet ID is mandatory to run this.
			if ( '' !== $spreadsheet_id && '' !== $new_title ) {
				// Create a new Google client.
				$wcap_oauth = Wcap_Google_Oauth::get_instance();
				$client     = $wcap_oauth->get_client();

				// Create a new Google Sheets service.
				$service = new Google_Service_Sheets( $client );

				$update_spreadsheet_request = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(
					array(
						'requests' => array(
							'updateSpreadsheetProperties' => array(
								'properties' => array(
									'title' => $new_title,
								),
								'fields'     => 'title',
							),
						),
					)
				);

				// Make the request to update the spreadsheet title.
				try {
					$service->spreadsheets->batchUpdate( $spreadsheet_id, $update_spreadsheet_request );
					return true;
				} catch ( Exception $e ) {
					return $e->getMessage();
				}
			}
		}
	}
}
