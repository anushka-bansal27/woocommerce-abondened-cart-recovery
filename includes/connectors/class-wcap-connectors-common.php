<?php
/**
 * Class for common functions for connectors.
 *
 * @package Includes/Connectors
 */

/**
 * Connectors common class.
 */
class Wcap_Connectors_Common {

	/**
	 * Class instance.
	 *
	 * @var $ins
	 */
	public static $ins = null;

	/**
	 * Connectors saved settings.
	 *
	 * @var $connectors_saved_data
	 */
	public static $connectors_saved_data = array();

	/**
	 * Saved Data.
	 *
	 * @var $saved_data
	 */
	public static $saved_data = false;

	/**
	 * Connectors List.
	 *
	 * @var array $connectors_list
	 */
	public static $connectors_list = array();

	/**
	 * Active connectors count.
	 *
	 * @var int $active_count
	 */
	public static $active_count = false;

	/**
	 * Inactive connectors count.
	 *
	 * @var int $inactive_count
	 */
	public static $inactive_count = false;

	/**
	 * Construct.
	 */
	public function __construct() {
		add_action( 'wp_loaded', array( __CLASS__, 'wcap_get_connectors_data' ) );
		add_action( 'wp_ajax_wcap_disconnect_connector', array( &$this, 'wcap_disconnect_connector' ) );
		add_action( 'wp_ajax_wcap_sync_connector', array( &$this, 'wcap_sync_connector' ) );
		add_action( 'wp_ajax_wcap_save_connector_settings', array( &$this, 'wcap_save_connector_settings' ) );
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
	 * Get connector settings data from DB.
	 *
	 * @param string $connector_name - Connector slug.
	 * @return array containing the data. By default all connector data is sent.
	 *
	 * @since 8.15.0
	 */
	public static function wcap_get_connectors_data( $connector_name = '' ) {

		$temp_arr      = array();
		$fetch_from_db = false;
		$slug          = '' !== $connector_name && 'wcap' !== substr( $connector_name, 0, 4 ) ? "wcap_$connector_name" : $connector_name;
		if ( false === self::$saved_data ) {
			$fetch_from_db = true;
		} else {
			if ( '' !== $slug && array_key_exists( $slug, self::$saved_data ) ) {
				$temp_arr[ $slug ] = self::$saved_data[ $slug ];
			} else {
				// get the single record from wp_options & add it to the saved_data.
				$fetch_from_db = true;
			}
		}

		if ( $fetch_from_db ) {
			if ( '' === $slug ) {
				// get all the records from wp_options.
				foreach ( self::$connectors_list as $slug_name => $class ) {
					$details = json_decode( get_option( $slug_name . '_connector', '' ), true );
					if ( is_array( $details ) && count( $details ) > 0 ) {
						$temp_arr[ $slug_name ] = $details;
					}
				}
			} else {
				// get the single record from wp_options & add it to the saved_data.
				$details = json_decode( get_option( $slug . '_connector', '' ), true );
				if ( is_array( $details ) && count( $details ) > 0 ) {
					$temp_arr[ $slug ] = $details;
				}
			}
			self::$saved_data = $temp_arr;
		}
		self::$connectors_saved_data = $temp_arr;

		if ( $connector_name !=='' ) { //phpcs:ignore
			return isset( self::$connectors_saved_data[ $slug ] ) ? self::$connectors_saved_data[ $slug ] : false;
		}

		return self::$connectors_saved_data;
	}

	/**
	 * Get list of connectors.
	 *
	 * @param string $type - Connector type - active|inactive.
	 * @return array - connector list.
	 *
	 * @since 8.15.0
	 */
	public static function wcap_get_connectors( $type = '' ) {
		if ( empty( self::$connectors_list ) ) {
			$resource_dir = WCAP_PLUGIN_PATH . '/includes/connectors';
			foreach ( glob( $resource_dir . '/*' ) as $connector ) {

				if ( strpos( $connector, 'index.php' ) !== false ) {
					continue;
				}

				$_field_filename = $connector;
				// If file does not end in .php, then it is a folder.
				$is_folder = substr( $connector, -4 ) !== '.php';
				// Append connector.php if it is a folder.
				if ( $is_folder ) {
					$_field_filename = $connector . '/connector.php';
					if ( file_exists( $_field_filename ) ) {
						require_once $_field_filename;
						// Load class if file checked is a folder.
						$path         = explode( '/', $connector );
						$folder_name  = array_pop( $path );
						$class_name   = 'Wcap_' . ucwords( $folder_name );
						$class_object = $class_name::get_instance();
						$slug         = $class_object->slug;
						$connector_list[ $slug ] = $class_object;
					}
				}
			}

			$connector_list        = apply_filters( 'wcap_basic_connectors_loaded', $connector_list );
			self::$connectors_list = $connector_list;
		}
		if ( '' === $type ) {
			return self::$connectors_list;
		} else {
			$return_list = array();
			// Identify the type i.e. active/inactive and only return those.
			foreach ( self::$connectors_list as $slug => $c_class ) {
				$details = self::wcap_get_connectors_data( $slug );
				switch ( $type ) {
					case 'active':
						if ( isset( $details['status'] ) && $type === $details['status'] ) {
							$return_list[ $slug ] = $c_class;
						}
						break;
					case 'inactive':
						if ( ( isset( $details['status'] ) && 'active' !== $details['status'] ) || ! isset( $details['status'] ) ) {
							$return_list[ $slug ] = $c_class;
						}
						break;
				}
			}
			return $return_list;
		}
	}

	/**
	 * Get count of active connectors.
	 */
	public static function wcap_get_active_connectors_count() {

		if ( false === self::$active_count ) {
			$total_active   = 0;
			$get_connectors = self::wcap_get_connectors();
			foreach ( $get_connectors as $connector_name => $connector_obj ) {
				$connector_settings = json_decode( get_option( $connector_name . '_connector', '' ), true );
				if ( is_array( $connector_settings ) && count( $connector_settings ) > 0 ) {
					$status = isset( $connector_settings['status'] ) ? $connector_settings['status'] : '';
					if ( 'active' === $status ) {
						$total_active++;
					}
				}
			}
			self::$active_count = $total_active;
		}
		return self::$active_count;
	}

	/**
	 * Get inactive connectors count.
	 */
	public static function wcap_get_inactive_connectors_count() {

		if ( false === self::$inactive_count ) {
			$total_inactive = 0;
			$get_connectors = self::wcap_get_connectors();
			foreach ( $get_connectors as $connector_name => $connector_obj ) {
				$connector_settings = json_decode( get_option( $connector_name . '_connector', '' ), true );
				if ( is_array( $connector_settings ) && count( $connector_settings ) > 0 ) {
					$status = isset( $connector_settings['status'] ) ? $connector_settings['status'] : '';
					if ( 'active' !== $status ) {
						$total_inactive++;
					}
				} else { // Settings have not yet been saved.
					$total_inactive++;
				}
			}
			self::$inactive_count = $total_inactive;
		}
		return self::$inactive_count;
	}

	/**
	 * Disconnect the connector.
	 */
	public function wcap_disconnect_connector() {
		$connector = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( '' === $connector ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => __( 'Something went wrong. Please try again.', 'woocommerce-ac' ),
				)
			);
		}
		// Get the list of active connectors.
		$active_connectors = json_decode( get_option( 'wcap_active_connectors', '' ), true );
		unset( $active_connectors[ $connector ] );
		update_option( 'wcap_active_connectors', wp_json_encode( $active_connectors ) );
		if ( 'salesforce_crm' === $connector ) {
			delete_option( 'wcap_salesforce_code_verifier' );
			delete_option( 'wcap_salesforce_access_token' );
			delete_option( 'wcap_salesforce_instance_url' );
			delete_option( 'wcap_salesforce_refresh_token' );
		}

		// Update the connector status.
		$connector_settings     = get_option( 'wcap_' . $connector . '_connector' );
		$settings_dec           = json_decode( $connector_settings, true );
		$settings_dec['status'] = 'inactive';
		update_option( 'wcap_' . $connector . '_connector', wp_json_encode( $settings_dec ) );

		wp_send_json(
			array(
				'status'  => 'success',
				'message' => __( 'Disconnected Successfully!', 'woocommerce-ac' ),
			)
		);
	}

	/**
	 * Manually sync the carts.
	 */
	public function wcap_sync_connector() {
		$connector = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( '' === $connector ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => __( 'Something went wrong. Please try again.', 'woocommerce-ac' ),
				)
			);
		}
		$class_name = 'Wcap_' . ucwords( $connector );
		if ( 'Wcap_Salesforce_crm' === $class_name || 'Wcap_Salesforce_Crm' === $class_name ) {
			$class_name = 'Wcap_Salesforce_Crm';
		}
		$class_object = $class_name::get_instance();
		$results      = $class_object->wcap_sync_manually();
		// Call the function to send the data to the connector.
		wp_send_json(
			$results
		);
	}

	/**
	 * Save connector settings.
	 */
	public function wcap_save_connector_settings() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => __( 'Permission denied.', 'woocommerce-ac' ),
				)
			);
		}

		check_ajax_referer( 'wcap_settings_nonce', 'security' );

		$settings  = isset( $_POST['settings'] ) ? $_POST['settings'] : ''; // phpcs:ignore
		$connector = isset( $_POST['connector'] ) ? sanitize_text_field( wp_unslash( $_POST['connector'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( '' === $connector ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => __( 'Please try again', 'woocommerce-ac' ),
				)
			);
		}
		if ( '' === $settings ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => __( 'Blank settings found. Please fill all the data.', 'woocommerce-ac' ),
				)
			);
		}

		$result          = array();
		$result          = apply_filters( 'wcap_before_connector_save_settings', $result, $settings, $connector );
		$connect_to_tool = isset( $result[ $connector ] ) ? $result[ $connector ] : array( 'status' => 'success' );
		if ( isset( $connect_to_tool['status'] ) && 'success' === $connect_to_tool['status'] ) {
			if ( ! in_array( $connector, array( 'google_sheets', 'salesforce_crm' ), true ) ) { // Google Sheets connector becomes active when the Connect button inside the settings is clicked.
				$settings['status']    = 'active';
				$settings['activated'] = current_time( 'timestamp' ); // phpcs:ignore
			} elseif ( 'google_sheets' === $connector ) { // Scenario: When the user modifies the setting while it is connected.
				// Retain the status, refresh token etc. if it is already saved in the DB record.
				$old_settings = json_decode( get_option( 'wcap_google_sheets_connector' ), true );
				if ( ! empty( $old_settings ) && ( isset( $old_settings['status'] ) && 'active' === $old_settings['status'] ) && ( isset( $old_settings['wcap_gsheets_refresh_token'] ) && '' !== $old_settings['wcap_gsheets_refresh_token'] && null !== $old_settings['wcap_gsheets_refresh_token'] ) ) {
					if ( $old_settings['client_id'] === $settings['client_id'] && $old_settings['secret_key'] === $settings['secret_key'] ) { // Refresh token is valid only if these have not changed.
						$settings['status']                     = $old_settings['status'];
						$settings['wcap_gsheets_refresh_token'] = $old_settings['wcap_gsheets_refresh_token'];
						$settings['activated']                  = $old_settings['activated'];
					}
				}
			}
			$settings_str = wp_json_encode( $settings );
			update_option( 'wcap_' . $connector . '_connector', $settings_str );

			$active_connectors = json_decode( get_option( 'wcap_active_connectors', '' ), true );
			if ( ! is_array( $active_connectors ) ) {
				$active_connectors = array();
			}
			$active_connectors[ $connector ] = $settings;

			update_option( 'wcap_active_connectors', wp_json_encode( $active_connectors ) );
			do_action( 'wcap_after_connector_save_settings', $settings, $connector );
			wp_send_json(
				array(
					'status'  => 'success',
					'message' => __( 'Connected successfully!', 'woocommerce-ac' ),
				)
			);
		} else {
			$settings['status'] = 'inactive';
			$settings_str       = wp_json_encode( $settings );
			update_option( 'wcap_' . $connector . '_connector', $settings_str );
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => $connect_to_tool['message'],
				)
			);
		}
		die();
	}

	/**
	 * Get the contact data using abandoned ID.
	 *
	 * @param int $abandoned_id - Abandoned Cart ID.
	 * @return string $user_details - User Details.
	 */
	public function wcap_get_contact_data( $abandoned_id ) {
		// Fetch the contact data.
		$cart_history  = wcap_get_data_cart_history( $abandoned_id );
		$customer_data = array();
		if ( $cart_history ) {
			// Defaults.
			$email     = '';
			$firstname = '';
			$lastname  = '';
			$user_id   = $cart_history->user_id;
			$user_type = $cart_history->user_type;
			$phone     = '';
			$country   = '';
			$company   = '';
			$state     = '';
			$address   = '';
			$city      = '';
			if ( $user_id >= 63000000 && 'GUEST' === $user_type ) {
				$guest_data = wcap_get_data_guest_history( $user_id );
				if ( isset( $cart_history ) && isset( $guest_data ) ) {
					$email     = isset( $guest_data->email_id ) ? $guest_data->email_id : '';
					$firstname = isset( $guest_data->billing_first_name ) && null !== $guest_data->billing_first_name ? $guest_data->billing_first_name : '';
					$lastname  = isset( $guest_data->billing_last_name ) && null !== $guest_data->billing_last_name ? $guest_data->billing_last_name : '';
					$phone     = isset( $guest_data->phone ) && null !== $guest_data->phone ? $guest_data->phone : '';
					$country   = isset( $guest_data->billing_country ) && null !== $guest_data->billing_country ? $guest_data->billing_country : '';
					$company   = isset( $guest_data->billing_company_name ) && null !== $guest_data->billing_company_name ? $guest_data->billing_company_name : 'LittleCodr';
					$state     = isset( $guest_data->billing_state ) && null !== $guest_data->billing_state ? $guest_data->billing_state : '';
					$address   = isset( $guest_data->billing_address_1 ) && null !== $guest_data->billing_address_1 ? $guest_data->billing_address_1 : '';
					$city      = isset( $guest_data->billing_city ) && null !== $guest_data->billing_city ? $guest_data->billing_city : '';
				}
			} elseif ( $user_id > 0 ) {
				// Get the first & last name from the user data.
				$user_info = new WP_User( $user_id );
				$firstname = $user_info->first_name;
				$lastname  = $user_info->last_name;
				$email     = $user_info->user_email;
				$phone     = isset( $user_info->billing_phone ) ? $user_info->billing_phone : '';
				$country   = isset( $user_info->billing_country ) ? $user_info->billing_country : '';
				$company   = isset( $user_info->billing_company ) ? $user_info->billing_company : '';
				$state     = isset( $user_info->billing_state ) ? $user_info->billing_state : '';
				$address   = isset( $user_info->billing_address_1 ) ? $user_info->billing_address_1 : '';
				$city      = isset( $user_info->billing_city ) ? $user_info->billing_city : '';
			}
			$customer_data['firstname'] = $firstname;
			$customer_data['lastname']  = $lastname;
			$customer_data['email']     = $email;
			$customer_data['phone']     = $phone;
			$customer_data['country']   = $country;
			$customer_data['user_id']   = $user_id;
			$customer_data['company']   = $company;
			$customer_data['state']     = $state;
			$customer_data['address']   = $address;
			$customer_data['city']      = $city;
		}
		return $customer_data;
	}

	/**
	 * Get Cart status using abandoned ID.
	 *
	 * @param int    $abandoned_id - Abandoned ID.
	 * @param string $connector - Name of the Connector hubspot|mailchimp etc.
	 * @return boolean true - cart synced | false - pending.
	 */
	public function wcap_get_cart_status( $abandoned_id, $connector = '' ) {
		$sent = 0;
		global $wpdb;

		if ( '' !== $connector ) {
			$id = $wpdb->get_var( // phpcs:ignore
				$wpdb->prepare(
					'SELECT id FROM `' . $wpdb->prefix . 'ac_connector_sync` WHERE cart_id = %s AND connector_name = %s',
					$abandoned_id,
					$connector
				)
			);
			if ( isset( $id ) && $id > 0 ) {
				$sent = $id;
			}
		}
		return $sent;
	}

	/**
	 * Get cart details to be exported.
	 *
	 * @param int $abandoned_id - Abandoned ID.
	 * @return array $cart_details - Cart Details.
	 */
	public function wcap_get_cart_details( $abandoned_id ) {

		global $wpdb;

		$cart_row = $wpdb->get_row( // phpcs:ignore
			$wpdb->prepare(
				'SELECT * FROM ' . WCAP_ABANDONED_CART_HISTORY_TABLE . ' WHERE id = %s', // phpcs:ignore
				absint( $abandoned_id )
			)
		);

		if ( isset( $cart_row ) ) {
			$abandoned_cart_timestamp = isset( $cart_row->abandoned_cart_time ) ? $cart_row->abandoned_cart_time : '';
			if ( '' !== $abandoned_cart_timestamp && isset( $cart_row->checkout_link ) ) {
				$abandoned_cart_date = date( 'Y-m-d', $abandoned_cart_timestamp ); // phpcs:ignore

				$cart_data = $this->wcap_create_cart_html( json_decode( $cart_row->abandoned_cart_info, true ), $cart_row->checkout_link );

				$cart_details['wcap_abandoned_date'] = $abandoned_cart_date;
				$cart_details['wcap_cart_products']  = $cart_data['products_list'];
				$cart_details['wcap_products_html']  = $cart_data['html'];
				$cart_details['wcap_products_sku']   = $cart_data['sku'];
				$cart_details['wcap_cart_subtotal']  = $cart_data['subtotal'];
				$cart_details['wcap_cart_tax']       = $cart_data['tax'];
				$cart_details['wcap_cart_total']     = $cart_data['total'];
				$cart_details['checkout_url']        = $cart_row->checkout_link;
				$cart_details['cart_status']         = $cart_row->cart_ignored;
				$cart_details['wc_order_id']         = $cart_row->recovered_cart;
				$cart_details['wcap_timestamp']      = $abandoned_cart_timestamp;
				$cart_details['products_with_qty']   = $cart_data['products_qty'];
				$cart_details['unsubscribe']         = $cart_row->unsubscribe_link;
				return $cart_details;
			}
		}
		return false;
	}

	/**
	 * Return Cart HTML Table.
	 *
	 * @param array  $cart_details - Cart Details captured by the plugin.
	 * @param string $checkout_url - Cart Checkout URL.
	 */
	public function wcap_create_cart_html( $cart_details, $checkout_url ) {

		$cart_details = $cart_details['cart'];

		$cart_total        = 0;
		$cart_tax          = 0;
		$subtotal          = 0;
		$sku               = '';
		$products_list     = '';
		$products_and_qty  = '';
		$image_col_heading = __( 'Image', 'woocommerce-ac' );
		$item_col_heading  = __( 'Name', 'woocommerce-ac' );
		$qty_col_heading   = __( 'Quantity', 'woocommerce-ac' );
		$price_col_heading = __( 'Price', 'woocommerce-ac' );
		$total_col_heading = __( 'Total', 'woocommerce-ac' );
		$cart_table_html   = '<div><hr></div><table style="font-size: 14px; font-family: Arial, sans-serif; line-height: 20px; text-align: left; table-layout: fixed;" width="100%"><thead><tr><th style="text-align: center;word-wrap: unset;">' . $image_col_heading . '</th><th style="text-align: center;word-wrap: unset;">' . $item_col_heading . '</th><th style="text-align: center;word-wrap: unset;">' . $qty_col_heading . '</th><th style="text-align: center;word-wrap: unset;">' . $price_col_heading . '</th><th style="text-align: center;word-wrap: unset;">' . $total_col_heading . '</th></tr></thead><tbody>';
		foreach ( $cart_details as $key => $details ) {
			$product_id   = $details['product_id'];
			$_product     = wc_get_product( $product_id ); // Product Object.
			$image_url    = wp_get_attachment_url( $_product->get_image_id() ); // Image URL.
			$product_name = $_product->get_title(); // Product Name.
			$sku_product  = $_product->get_sku();

			$cart_table_html .= '<tr><td width="20" style="max-width: 100%; text-align: center;">';
			$cart_table_html .= '<img height="50" width="50" src="' . $image_url . '">'; // Image URL.
			$cart_table_html .= '</td>';

			$cart_table_html .= '<td width="50" style="max-width: 100%; text-align: center; font-weight: normal;font-size: 10px;word-wrap: unset;">';
			$cart_table_html .= '<a style="display: inline-block;" target="_blank" href="' . $checkout_url . '">' . $product_name . '</a>'; // Product Name.
			$cart_table_html .= '</td>';

			$cart_table_html .= '<td width="10" style="max-width: 100%;text-align: center;">' . $details['quantity'] . '</td>'; // Quantity.

			$cart_table_html .= '<td width="10" style="max-width: 100%;text-align: center; font-size: 10px;">' . wc_price( $_product->get_price() ) . '</td>'; // price per product.

			$cart_table_html .= '<td width="10" style="max-width: 100%;text-align: center; font-size: 10px;">' . wc_price( $details['line_subtotal'] ) . '</td>'; // Line subtotal.

			$cart_table_html  .= '</tr>';
			$cart_tax         += isset( $details['line_subtotal_tax'] ) ? $details['line_subtotal_tax'] : 0;
			$cart_total       += isset( $details['line_subtotal'] ) ? $details['line_subtotal'] : 0;
			$subtotal         += isset( $details['line_total'] ) ? $details['line_total'] : 0;
			$sku              .= "$sku_product,";
			$products_list    .= "$product_name-$product_id,";
			$products_and_qty .= "$product_name x " . $details['quantity'] . ',';
		}
		$sku              = rtrim( $sku, ',' );
		$products_list    = rtrim( $products_list, ',' );
		$products_and_qty = rtrim( $products_and_qty, ',' );
		$cart_table_html .= '</tbody></table><div><hr></div>';

		return apply_filters(
			'wcap_abandoned_cart_html_and_other_data',
			array(
				'products_list' => $products_list,
				'sku'           => $sku,
				'subtotal'      => $subtotal,
				'html'          => $cart_table_html,
				'total'         => $cart_total,
				'tax'           => $cart_tax,
				'products_qty'  => $products_and_qty,
			),
			$cart_details,
			$checkout_url
		);
	}

	/**
	 * Get active connectors list.
	 *
	 * @since 8.16.0
	 */
	public function wcap_get_active_connectors_list() {
		$connectors_list = self::wcap_get_connectors_data();
		$active_list     = array();
		if ( is_array( $connectors_list ) && count( $connectors_list ) > 0 ) {
			foreach ( $connectors_list as $slug => $settings ) {
				$connector_status = isset( $settings['status'] ) ? $settings['status'] : '';
				if ( 'active' === $connector_status ) {
					array_push( $active_list, $slug );
				}
			}
			return $active_list;
		}
	}

	/**
	 * Return cart sync status.
	 *
	 * @param int $cart_id - Cart ID.
	 * @since 8.16.0
	 */
	public function wcap_needs_manual_sync( $cart_id = 0 ) {
		$needs_sync = false;
		if ( $cart_id > 0 ) {

			$active_connectors = $this->wcap_get_active_connectors_list();
			$sync_list         = array();
			if ( is_array( $active_connectors ) && count( $active_connectors ) > 0 ) {
				foreach ( $active_connectors as $slug ) {
					if ( stripos( $slug, 'custom_smtp' ) > 0 ) {
						continue;
					} else {
						$name               = str_ireplace( 'wcap_', '', $slug );
						$sync_status        = $this->wcap_get_cart_sync_status( $cart_id, $name );
						$sync_list[ $slug ] = $sync_status;
					}
				}
			}
			if ( count( $sync_list ) > 0 && in_array( false, $sync_list ) ) {
				$needs_sync = true;
			}
		}
		return $needs_sync;
	}

	/**
	 * Get Cart sync status.
	 *
	 * @param int    $cart_id - Cart ID.
	 * @param string $connector_name - Connector Name.
	 * @since 8.16.0
	 */
	public function wcap_get_cart_sync_status( $cart_id = 0, $connector_name = '' ) {

		if ( $cart_id > 0 && '' !== $connector_name ) {

			global $wpdb;
			$cart_status = $wpdb->get_var( // phpcs:ignore
				$wpdb->prepare(
					'SELECT status FROM `' . $wpdb->prefix . 'ac_connector_sync` WHERE cart_id = %d AND connector_name = %s',
					$cart_id,
					$connector_name
				)
			);
			if ( 'complete' === $cart_status ) {
				return true;
			} else {
				return false;
			}
		}
		return false;
	}

	/**
	 * Sync carts using Bulk Actions.
	 *
	 * @since 8.16.0
	 */
	public function wcap_bulk_sync_manually() {

		$ids = Wcap_Common::wcap_get_abandoned_cart_ids_from_get();
		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}
		$wcap_sync_manually_count = count( $ids );

		foreach ( $ids as $id ) {
			$this->wcap_sync_cart( $id );
		}
	}

	/**
	 * Sync the cart for active connectors.
	 *
	 * @param int $cart_id - Cart ID.
	 * @since 8.16.0
	 */
	public function wcap_sync_cart( $cart_id ) {

		if ( $cart_id > 0 ) {

			$active_connectors = $this->wcap_get_active_connectors_list();
			if ( is_array( $active_connectors ) && count( $active_connectors ) > 0 ) {
				foreach ( $active_connectors as $slug ) {
					$connector_name   = str_ireplace( 'wcap_', '', $slug );
					$cart_sync_status = $this->wcap_get_cart_sync_status( $cart_id, $connector_name );
					if ( ! $cart_sync_status ) {
						if ( stripos( $slug, 'custom_smtp' ) > 0 ) {
							continue;
						} else {
							$class_name   = ucwords( $slug );
							if ( 'Wcap_salesforce_crm' === $class_name || 'Wcap_Salesforce_Crm' === $class_name ) {
								$class_name = 'Wcap_Salesforce_Crm';
							}
							$class_object = $class_name::get_instance();
							$results      = $class_object->wcap_sync_single_cart( $cart_id );
						}
					}
				}
			}
		}
	}
}
$wcap_connectors_common = new Wcap_Connectors_Common();
