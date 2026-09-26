<?php
/**
 * Cart Data Export to Google Sheets.
 *
 * @package Abandon Cart Pro for WooCommerce
 * @since 9.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Wcap_Google_Sheets_Upsert_Cart' ) ) {

	/**
	 * Cart Insert/Update Class
	 */
	class Wcap_Google_Sheets_Upsert_Cart {

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

			$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( 'google_sheets' );
			if ( empty( $connector_settings ) || ( isset( $connector_settings['status'] ) && 'active' !== $connector_settings['status'] ) || ( isset( $connector_settings['wcap_gsheets_refresh_token'] ) && ( '' === $connector_settings['wcap_gsheets_refresh_token'] || null === $connector_settings['wcap_gsheets_refresh_token'] ) ) ) {
				return false;
			}

			// Guest email address captured.
			add_action( 'wcap_guest_cart_history_after_insert', array( &$this, 'wcap_guest_inserted' ), 99, 1 );
			// Guest Details updated.
			add_action( 'wcap_after_update_guest_cart_history', array( &$this, 'wcap_guest_updated' ), 99, 2 );
			// Cart Updated.
			add_action( 'wcap_after_update_cart_history', array( &$this, 'wcap_cart_updated' ), 99, 2 );
			// Cart Inserted.
			add_action( 'wcap_cart_history_after_insert', array( &$this, 'wcap_cart_inserted' ), 99, 1 );
			// Cart Recovered.
			add_action( 'wcap_cart_recovered', array( &$this, 'wcap_update_cart_status_in_sheet' ), 99, 2 );
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
		 * Guest record inserted.
		 *
		 * @param int $user_id - User ID.
		 */
		public function wcap_guest_inserted( $user_id = 0 ) {

			if ( $user_id > 0 ) {

				$abandoned_id = wcap_get_abandoned_id_from_user_id( $user_id );

				if ( $abandoned_id > 0 ) {
					$this->wcap_prepare_cart_details( $abandoned_id );
				}
			}
		}

		/**
		 * Guest record updated.
		 *
		 * @param array $value - Updated columns and their values.
		 * @param array $where - Where condition for update.
		 */
		public function wcap_guest_updated( $value = array(), $where = array() ) {
			$user_id = 0;
			if ( is_array( $where ) && array_key_exists( 'id', $where ) ) {
				$user_id = $where['id'];
			} elseif ( is_array( $value ) && array_key_exists( 'id', $value ) ) {
				$user_id = $value['id'];
			}

			$abandoned_id = wcap_get_abandoned_id_from_user_id( $user_id );
			if ( $abandoned_id > 0 ) {
				$this->wcap_prepare_cart_details( $abandoned_id );
			}
		}

		/**
		 * Abandoned Cart record inserted.
		 *
		 * @param int $abandoned_id - Abandoned Cart ID.
		 */
		public function wcap_cart_inserted( $abandoned_id = 0 ) {

			if ( $abandoned_id > 0 ) {
				$this->wcap_prepare_cart_details( $abandoned_id );
			}
		}

		/**
		 * Abandoned Cart record updated.
		 *
		 * @param array $value - Updated columns and their values.
		 * @param array $where - Where condition for update.
		 */
		public function wcap_cart_updated( $value = array(), $where = array() ) {

			if ( wcap_get_cart_session( 'wcap_email_sent_id' ) ) { // User has come from a reminder link.
				return;
			}
			$abandoned_id = 0;
			if ( is_array( $where ) && array_key_exists( 'id', $where ) ) {
				$abandoned_id = $where['id'];
			} elseif ( is_array( $value ) && array_key_exists( 'id', $value ) ) {
				$abandoned_id = $value['id'];
			}

			if ( 0 === $abandoned_id ) {
				$user_id = isset( $where['user_id'] ) ? $where['user_id'] : 0;
				if ( $user_id > 0 ) {
					$abandoned_id = wcap_get_abandoned_id_from_user_id( $user_id );
				}
			}
			if ( $abandoned_id > 0 ) {
				$this->wcap_prepare_cart_details( $abandoned_id, false );
			}
		}

		/**
		 * Update Cart Status when it is recovered.
		 *
		 * @param int $cart_id - Abandoned Cart ID.
		 * @param int $order_id - WC Order ID.
		 */
		public static function wcap_update_cart_status_in_sheet( $cart_id = 0, $order_id = 0 ) {

			if ( (int) $cart_id > 0 && (int) $order_id > 0 ) {

				// Get the row ID for Google Sheet.
				$connector_inst           = Wcap_Google_Sheets::get_instance();
				$get_existing_row_from_db = $connector_inst->wcap_get_row_id( $cart_id );

				// Update only if the cart abandoned record is found.
				if ( '' !== $get_existing_row_from_db ) { // 1st row is the header text, hence.

					$spreadsheet_id = get_option( 'wcap_google_sheet_id' );
					$explode_row    = explode( '-', $get_existing_row_from_db );
					if ( isset( $explode_row ) && 2 === count( $explode_row ) ) {
						$sheet_name = isset( $explode_row[0] ) && '' !== $explode_row[0] ? $explode_row[0] : '';
						$row_number = isset( $explode_row[1] ) && $explode_row[1] > 1 ? $explode_row[1] : 0;
						if ( '' === $sheet_name || $row_number < 1 ) {
							return;
						}
					} else {
						return;
					}

					// Create a new Google client.
					$wcap_oauth = Wcap_Google_Oauth::get_instance();
					$client     = $wcap_oauth->get_client();

					// Create a new Google Sheets service.
					$service = new Google_Service_Sheets( $client );

					// Order URL.
					if ( is_hpos_enabled() ) { // HPOS usage is enabled.
						$order_url = admin_url( "admin.php?page=wc-orders&action=edit&id=$order_id" );
					} else {
						$order_url = admin_url( "post.php?post=$order_id&action=edit" );
					}
					// Define the values you want to append.
					$values       = array();
					$values[0]    = array();
					$values[0][5] = __( 'Recovered', 'woocommerce-ac' ); // 5 refers to the Cart Status column in the sheet.
					$values[0][6] = $order_id; // 6 refers to the WC Order ID column in the sheet.

					$sheet_data = array(
						'client'         => $client,
						'service'        => $service,
						'spreadsheet_id' => $spreadsheet_id,
						'sheet_name'     => $sheet_name,
					);

					$sheet_data['row_number'] = $row_number;

					$self_inst = self::get_instance();
					$self_inst->wcap_update_existing_row_in_sheet( $sheet_data, $cart_id, $values, 'update', $order_url );
				}
			}
		}

		/**
		 * Prepare cart details for Google Sheets Export.
		 *
		 * @param int  $abandoned_id - Abandoned Cart ID.
		 * @param bool $return_status - true for manual sync.
		 */
		public function wcap_prepare_cart_details( $abandoned_id, $return_status = false ) {

			// Fetch the contact data.
			$common_inst      = Wcap_Connectors_Common::get_instance();
			$customer_details = $common_inst->wcap_get_contact_data( $abandoned_id );

			$customer_email = isset( $customer_details['email'] ) && '' !== $customer_details['email'] ? $customer_details['email'] : '';
			$customer_phone = isset( $customer_details['phone'] ) && '' !== $customer_details['phone'] ? $customer_details['phone'] : '';

			if ( '' === $customer_email && '' === $customer_phone ) {
				return;
			}

			$valid_email = filter_var( $customer_email, FILTER_VALIDATE_EMAIL );
			$valid_phone = filter_var( $customer_phone, FILTER_SANITIZE_NUMBER_INT );

			if ( ! $valid_email && ! $valid_phone ) {
				return;
			}

			// Depending on the cart status, the record in Google Sheets will be inserted or updated.
			$cart_sync_id = $common_inst->wcap_get_cart_status( $abandoned_id, 'google_sheets' );
			$cart_details = $this->wcap_get_cart( $abandoned_id, $customer_details );

			if ( is_array( $customer_details ) && count( $customer_details ) > 0 && is_array( $cart_details ) && count( $cart_details ) > 0 ) {

				$action = $cart_sync_id > 0 ? 'update' : 'insert';

				$status = $this->wcap_upsert_cart_in_sheet( $abandoned_id, $cart_details, $action, $cart_sync_id );
				if ( $return_status ) {
					return $status;
				}
			}
		}

		/**
		 * Prepare Cart Data to be inserted in Google Sheets.
		 *
		 * @param int   $cart_id - Abandoned Cart ID.
		 * @param array $customer_details - Cart User Details.
		 * @return array $cart_data - Cart Data.
		 */
		public function wcap_get_cart( $cart_id, $customer_details ) {

			$cart_data    = array();
			$common_inst  = Wcap_Connectors_Common::get_instance();
			$cart_details = $common_inst->wcap_get_cart_details( $cart_id );

			$wc_order_id = isset( $cart_details['wc_order_id'] ) ? (int) $cart_details['wc_order_id'] : 0;
			$cart_status = isset( $cart_details['cart_status'] ) ? $cart_details['cart_status'] : '';
			// Cart is either abandoned, pending payment, cancelled, order-received.
			if ( 0 === $wc_order_id && in_array( $cart_status, array( '0', '2', '4', '3' ) ) && '' !== $cart_details['wcap_timestamp'] ) { // phpcs:ignore

				// Cart Status.
				if ( isset( $cart_details['unsubscribe'] ) && '1' === $cart_details['unsubscribe'] ) {
					$cart_status_desc = __( 'Unsubscribed', 'woocommerce-ac' );
				} else {
					switch ( $cart_status ) {
						case '0':
						default:
							$cart_status_desc = __( 'Abandoned', 'woocommerce-ac' );
							break;
						case '2':
							$cart_status_desc = __( 'Abandoned - Order Cancelled', 'woocommerce-ac' );
							break;
						case '4':
							$cart_status_desc = __( 'Abandoned - Pending Payment', 'woocommerce-ac' );
							break;
						case '3':
							$cart_status_desc = __( 'Abandoned - Order Recieved', 'woocommerce-ac' );
							break;
					}
				}

				$date_format = get_option( 'date_format' );
				$time_format = get_option( 'time_format' );
				// Cart Time.
				$abandoned_date = date( $date_format, $cart_details['wcap_timestamp'] ); // phpcS:ignore
				$abandoned_time = date( $time_format, $cart_details['wcap_timestamp'] ); // phpcs:ignore

				// User Type.
				if ( $customer_details['user_id'] > 0 && $customer_details['user_id'] < 63000000 ) {

					global $wp_roles;

					$user       = get_userdata( $customer_details['user_id'] );
					$user_roles = $user->roles[0]; // Get all the user roles for this user as an array.
					$role_name  = $wp_roles->roles[ $user_roles ]['name'];

					// translators: User role name.
					$user_type = sprintf( __( 'Registered, %s', 'woocommerce-ac' ), $role_name );
				} else {
					$user_type = __( 'Guest', 'woocommerce-ac' );
				}
				// Product Details.
				$product_list = $cart_details['products_with_qty'];
				$product_list = str_ireplace( ',', "\r\n", $product_list );
				// Create the array to return.
				$cart_data['cart_id']         = $cart_id;
				$cart_data['user_id']         = $customer_details['user_id'];
				$cart_data['user_type']       = $user_type;
				$cart_data['cart_details']    = $product_list;
				$cart_data['cart_total']      = $cart_details['wcap_cart_total'];
				$cart_data['cart_status']     = $cart_status_desc;
				$cart_data['wc_order_id']     = $wc_order_id;
				$cart_data['cart_date']       = $abandoned_date;
				$cart_data['cart_time']       = $abandoned_time;
				$cart_data['user_first_name'] = $customer_details['firstname'];
				$cart_data['user_last_name']  = $customer_details['lastname'];
				$cart_data['user_email']      = $customer_details['email'];
				$cart_data['user_phone']      = $customer_details['phone'];
				$cart_data['user_country']    = $customer_details['country'];
				$cart_data['checkout_link']   = $cart_details['checkout_url'];
			}
			return $cart_data;
		}

		/**
		 * Insert/Update Cart Record in Sheets.
		 *
		 * @param int    $cart_id - Abandoned Cart ID.
		 * @param array  $cart_details - Abandoned Cart Details.
		 * @param string $action - Insert|Update action.
		 * @param int    $cart_sync_id - Sync ID for update.
		 */
		public function wcap_upsert_cart_in_sheet( $cart_id, $cart_details, $action, $cart_sync_id ) {

			$spreadsheet_id = get_option( 'wcap_google_sheet_id' );

			// Create a new Google client.
			$wcap_oauth = Wcap_Google_Oauth::get_instance();
			$client     = $wcap_oauth->get_client();

			// Create a new Google Sheets service.
			$service = new Google_Service_Sheets( $client );

			// Define the values you want to append.
			$values    = array();
			$values[0] = array();
			foreach ( $cart_details as $column_val ) {
				$values[0][] = $column_val;
			}

			$sheet_data = array(
				'client'         => $client,
				'service'        => $service,
				'spreadsheet_id' => $spreadsheet_id,
			);

			$connector_inst           = Wcap_Google_Sheets::get_instance();
			$get_existing_row_from_db = $connector_inst->wcap_get_row_id( $cart_id );

			if ( 'insert' === $action || ( 'update' === $action && ! $get_existing_row_from_db ) ) {

				$create_sheet_obj = Wcap_Create_Spreadsheet::get_instance();
				$sheet_name       = $create_sheet_obj->wcap_check_sheet_record_limit( $client, $service, $spreadsheet_id );

				$sheet_data['sheet_name'] = $sheet_name;
				$sheet_data['row_number'] = $this->wcap_get_new_row_number( $client, $service, $spreadsheet_id, $sheet_name );

				$this->wcap_insert_new_row_in_sheet( $sheet_data, $cart_id, $values, $action );

			} elseif ( 'update' === $action && '' !== $get_existing_row_from_db ) {
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
				$sheet_data['sheet_name'] = $sheet_name;
				$sheet_data['row_number'] = $row_number;

				$this->wcap_update_existing_row_in_sheet( $sheet_data, $cart_id, $values, $action, false );
			}

		}

		/**
		 * Get New row number for Insert.
		 *
		 * @param obj    $client - Google Client.
		 * @param obj    $service - Google Client Service.
		 * @param int    $spreadsheet_id - Google Sheet ID.
		 * @param string $sheet_name - Sheet name.
		 * @return int $row_number - Row Number.
		 */
		public function wcap_get_new_row_number( $client, $service, $spreadsheet_id, $sheet_name ) {

			// Get the last populated row number.
			$range = $sheet_name . '!A:A';

			try {
				$response = $service->spreadsheets_values->get( $spreadsheet_id, $range );
				$values   = $response->getValues();

				$row_number = empty( $values ) ? 1 : count( $values ) + 1;

				return $row_number;
			} catch ( Exception $e ) {
				return false;
			}
		}

		/**
		 * Insert a new row in Google Sheets.
		 *
		 * @param array  $sheet_data - Sheet Data & Google Client Object.
		 * @param int    $cart_id - Abandoned Cart ID.
		 * @param array  $values - Data to insert.
		 * @param string $action - Insert|Update action.
		 */
		public function wcap_insert_new_row_in_sheet( $sheet_data, $cart_id, $values, $action ) {

			$client         = isset( $sheet_data['client'] ) ? $sheet_data['client'] : null;
			$service        = isset( $sheet_data['service'] ) ? $sheet_data['service'] : null;
			$spreadsheet_id = isset( $sheet_data['spreadsheet_id'] ) ? $sheet_data['spreadsheet_id'] : '';
			$row_number     = isset( $sheet_data['row_number'] ) ? $sheet_data['row_number'] : 0;
			$sheet_name     = isset( $sheet_data['sheet_name'] ) ? $sheet_data['sheet_name'] : get_option( 'wcap_google_sheet_name' );

			if ( $client && $service && '' !== $spreadsheet_id && $row_number > 0 ) {
				$range = $sheet_name . '!A' . $row_number; // A indicates a fresh row insert.

				// Prepare the request to append the data.
				$request_body = new Google_Service_Sheets_ValueRange(
					array(
						'values' => $values,
					)
				);

				// Make the request to append the data.
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
						$this->wcap_update_sync_status( $cart_id, "$sheet_name-$row_number", 'complete', '200', $values, $action );
					} else {
						$this->wcap_update_sync_status( $cart_id, '', 'failed', '', $values, $action );
					}
				} catch ( Exception $e ) {
					$message = $e->getCode() . ': ' . $e->getMessage();
					$this->wcap_update_sync_status( $cart_id, '', 'failed', $message, $values, $action );
				}
			}
		}

		/**
		 * Update existing row.
		 *
		 * @param array  $sheet_data - Sheet Data & Google Client Object.
		 * @param int    $cart_id - Abandoned Cart ID.
		 * @param array  $values - Data to insert.
		 * @param string $action - Insert|Update action.
		 * @param string $order_link - WC Order link for recovered Carts | false.
		 */
		public function wcap_update_existing_row_in_sheet( $sheet_data, $cart_id, $values, $action, $order_link = false ) {

			$client         = isset( $sheet_data['client'] ) ? $sheet_data['client'] : null;
			$service        = isset( $sheet_data['service'] ) ? $sheet_data['service'] : null;
			$spreadsheet_id = isset( $sheet_data['spreadsheet_id'] ) ? $sheet_data['spreadsheet_id'] : '';
			$sheet_name     = isset( $sheet_data['sheet_name'] ) ? $sheet_data['sheet_name'] : 'Sheet1';
			$row_number     = isset( $sheet_data['row_number'] ) ? $sheet_data['row_number'] : 0;
			if ( $client && $service && '' !== $spreadsheet_id && $row_number > 0 ) {

				$range = $sheet_name . '!A' . $row_number . ':O' . $row_number; // We always fetch from the entire row. A through O columns.

				try {
					$response = $service->spreadsheets_values->get( $spreadsheet_id, $range );
					$old_val  = $response->getValues();

					if ( ! empty( $old_val ) ) {
						if ( (int) $old_val[0][0] === (int) $cart_id ) { // We have the correct record.
							// Update the values.
							foreach ( $values as $k => $v ) {
								foreach ( $v as $a => $b ) {
									$old_val[0][ $a ] = $b;
								}
							}
							// Prepare the request to update the row.
							$request_body = new Google_Service_Sheets_ValueRange(
								array(
									'values' => $old_val,
								)
							);

							// Specify the range for the update.
							$update_range = $sheet_name . '!A' . $row_number . ':O' . $row_number; // Columns A to O, change if format is modified.
							// Make the request to update the row.
							$result = $service->spreadsheets_values->update(
								$spreadsheet_id,
								$update_range,
								$request_body,
								array(
									'valueInputOption' => 'RAW',
								)
							);

							// Check the response for success.
							if ( $result->updatedCells > 0 ) { // phpcs:ignore
								$this->wcap_update_sync_status( $cart_id, "$sheet_name-$row_number", 'complete', '200', $old_val, $action );
								// If order link has been sent, it needs to be added to the WooCommerce Order ID Cell.
								if ( $order_link ) {
									$this->wcap_add_link_to_cell( $sheet_data, $order_link, "G$row_number", $values[0][6] );
								}
							} else {
								$this->wcap_update_sync_status( $cart_id, '', 'failed', '', $old_val, $action );
							}
						}
					}
				} catch ( Exception $e ) {
					$message = $e->getCode() . ': ' . $e->getMessage();
					$this->wcap_update_sync_status( $cart_id, '', 'failed', $message, $old_val, $action );
				}
			}
		}

		/**
		 * Cart Sync Status in connector_sync table.
		 *
		 * @param int    $cart_id - Cart ID.
		 * @param string $row_number - Sheet and row number.
		 * @param string $status - success|failed.
		 * @param string $message - Status code.
		 * @param array  $params - Parameters passed in API call.
		 * @param string $action - Insert|Update.
		 */
		public function wcap_update_sync_status( $cart_id, $row_number, $status, $message, $params, $action ) {

			global $wpdb;

			if ( 'insert' === $action ) {

				$wpdb->insert( // phpcs:ignore
					$wpdb->prefix . 'ac_connector_sync',
					array(
						'cart_id'           => $cart_id,
						'connector_cart_id' => $row_number,
						'connector_name'    => 'google_sheets',
						'sync_date'         => current_time( 'timestamp' ), // phpcs:ignore
						'status'            => $status,
						'sync_data'         => wp_json_encode( $params ),
						'message'           => $message,
					)
				);
			} elseif ( 'update' === $action ) {
				$wpdb->update( // phpcs:ignore
					$wpdb->prefix . 'ac_connector_sync',
					array(
						'connector_cart_id' => $row_number,
						'sync_date'         => current_time( 'timestamp' ), // phpcs:ignore
						'status'            => $status,
						'sync_data'         => wp_json_encode( $params ),
						'message'           => $message,
					),
					array(
						'cart_id'        => $cart_id,
						'connector_name' => 'google_sheets',
					)
				);
			}
		}

		/**
		 * Add Order Link to recovered order ID in Google Sheets.
		 *
		 * @param array  $sheet_data - Spreadsheet & Google Client Details.
		 * @param string $order_url - Order URL.
		 * @param string $cell - Cell Name and number.
		 * @param string $display_text - Text to display for the link.
		 */
		public function wcap_add_link_to_cell( $sheet_data, $order_url, $cell, $display_text ) {

			$client         = isset( $sheet_data['client'] ) ? $sheet_data['client'] : null;
			$service        = isset( $sheet_data['service'] ) ? $sheet_data['service'] : null;
			$spreadsheet_id = isset( $sheet_data['spreadsheet_id'] ) ? $sheet_data['spreadsheet_id'] : '';
			$sheet_name     = isset( $sheet_data['sheet_name'] ) ? $sheet_data['sheet_name'] : 'Sheet1';

			if ( $client && $service && '' !== $spreadsheet_id && '' !== $cell ) {

				// Prepare the formula to create a hyperlink using the HYPERLINK function.
				$formula = '=HYPERLINK("' . $order_url . '","' . $display_text . '")';
				$range   = $sheet_name . '!' . $cell;

				// Prepare the request to update the cell with the hyperlink formula.
				$update_request = new Google_Service_Sheets_ValueRange(
					array(
						'values' => array(
							array(
								$formula,
							),
						),
						'range'  => $range,
					)
				);

				try {
					// Make a request to update the cell with the hyperlink formula.
					$service->spreadsheets_values->update( $spreadsheet_id, $range, $update_request, array( 'valueInputOption' => 'USER_ENTERED' ) );
				} catch ( Exception $e ) {
					return false;
				}
			}
		}
	}
}
new Wcap_Google_Sheets_Upsert_Cart();
