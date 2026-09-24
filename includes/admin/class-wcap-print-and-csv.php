<?php
/**
 * It will generate and display the data for the print and csv.
 *
 * @author   Tyche Softwares
 * @package Abandoned-Cart-Pro-for-WooCommerce/Admin/Report
 * @since 5.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'Wcap_Print_And_CSV' ) ) {
	/**
	 * It will generate and display the data for the print and csv.
	 */
	class Wcap_Print_And_CSV {

		/**
		 * Export Abandoned Cart Data in batches.
		 *
		 * @since 8.13.0
		 */
		public static function wcap_do_export_data() {
			// 1. Nonce verification
		    check_ajax_referer( 'wcap_settings_nonce', 'security' );

		    // 2. Capability check
		    if ( ! current_user_can( 'manage_woocommerce' ) ) {
		        wp_send_json_error( 'Unauthorized', 403 );
		        wp_die();
		    }
			$csv_print = isset( $_POST['csv_print'] ) ? sanitize_text_field( wp_unslash( $_POST['csv_print'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			if ( 'csv' === $csv_print ) {
				$upload_dir = wp_upload_dir();
				$filename   = 'wcap-csv.csv';
				$file       = trailingslashit( $upload_dir['basedir'] ) . $filename;

				if ( ! is_writeable( $upload_dir['basedir'] ) ) {//phpcs:ignore
					wp_send_json(
						array(
							'error'   => true,
							'message' => __(
								'Export location or file not writable',
								'woocommerce-ac'
							),
						)
					);
					wp_die();
				}
			}

			$step                  = isset( $_POST['step'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['step'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
			$total_abandoned_carts = isset( $_POST['total_items'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['total_items'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
			$done_items            = isset( $_POST['done_items'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['done_items'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification

			$report = self::wcap_generate_data( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification

			if ( ! empty( $report ) ) {

				$exported_item = $done_items + count( $report );
				$saved_data    = $done_items + count( $report );
				$percentage    = round( ( $saved_data / $total_abandoned_carts ) * 100 );

				if ( 'csv' === $csv_print ) {
					$rows = self::wcap_generate_csv( $report, $column = false );

					if ( $step < 2 ) {
						$done = false;
						// Make sure we start with a fresh file on step 1.
						@unlink( $file ); // phpcs:ignore
						self::wcap_print_csv_cols( $file );
					}

					$row = self::wcap_stash_step_data( $file, $rows );

					$json_data = array(
						'added'      => $exported_item,
						'percentage' => $percentage,
					);
				} else {
					$html_data = '';
					if ( $step < 2 ) {
						$html_data .= self::wcap_download_print_file( $report, true );
					} else {
						$html_data .= self::wcap_download_print_file( $report, false, false, true );
					}
					$json_data = array(
						'added'      => $exported_item,
						'percentage' => $percentage,
						'html_data'  => $html_data,
					);
				}
				$step++;
				$json_data['step'] = $step;
				wp_send_json( $json_data );
				wp_die();
			} elseif ( 1 === $step && empty( $report ) ) {
				wp_send_json(
					array(
						'error'   => true,
						'message' => __( 'No data found for export parameters.', 'woocommerce-ac' ),
					)
				);
				wp_die();
			} else {

				if ( 'csv' === $csv_print ) {
					$args = array(
						'page'        => 'woocommerce_ac_page',
						'action'      => 'listcart',
						'step'        => $step,
						'nonce'       => wp_create_nonce( 'wcap-batch-export-csv' ),
						'wcap_action' => 'wcap_download_csv',
					);

					$download_url = add_query_arg( $args, admin_url( 'admin.php' ) );
					$json_data    = array(
						'step' => 'done',
						'url'  => $download_url,
					);
				} else {
					$json_data = array( 'step' => 'done' );
				}
				wp_send_json( $json_data );
				wp_die();
			}
		}

		/**
		 * It will generate the abandoned cart data for print and csv.
		 *
		 * @param array $export_params - Paramaters for batching.
		 * @return object | array $return_abandoned_orders - Return Data.
		 * @since  3.8
		 */
		public static function wcap_generate_data( $export_params ) {
			global $wpdb;
			$return_abandoned_orders = array();
			$per_page                = 30;
			$results                 = array();
			$blank_cart_info         = '{"cart":[]}';
			$blank_cart_info_guest   = '[]';
			$ac_cutoff_time          = is_numeric( get_option( 'ac_cart_abandoned_time', 10 ) ) ? get_option( 'ac_cart_abandoned_time', 10 ) : 10;
			$cut_off_time            = $ac_cutoff_time * 60;
			$current_time            = current_time( 'timestamp' ); // phpcs:ignore
			$compare_time            = $current_time - $cut_off_time;
			$ac_cutoff_time_guest    = is_numeric( get_option( 'ac_cart_abandoned_time_guest', 10 ) ) ? get_option( 'ac_cart_abandoned_time_guest', 10 ) : 10;
			$cut_off_time_guest      = $ac_cutoff_time_guest * 60;
			$compare_time_guest      = $current_time - $cut_off_time_guest;
			$get_section_of_page     = Wcap_Common::wcap_get_current_section();
			$cart_status_field       = isset( $export_params['filter_status'] ) && '' !== $export_params['filter_status'] ? $export_params['filter_status'] : 'all';
			$cart_section            = isset( $export_params['wcap_section'] ) && '' !== $export_params['wcap_section'] ? $export_params['wcap_section'] : 'wcap_all_abandoned';
			$hidden_start            = isset( $export_params['start_date'] ) && '' !== $export_params['start_date'] ? $export_params['start_date'] : date( 'Y-m-d', strtotime( '-7 days' ) ); // phpcs:ignore
			$hidden_end              = isset( $export_params['end_date'] ) && '' !== $export_params['end_date'] ? $export_params['end_date'] : date( 'Y-m-d', current_time( 'timestamp' ) ); // phpcs:ignore
			$total_items             = isset( $export_params['total_items'] ) && $export_params['total_items'] > 0 ? $export_params['total_items'] : 0;
			$done_items              = isset( $export_params['done_items'] ) && is_numeric( $export_params['done_items'] ) ? $export_params['done_items'] : 0;
			$step                    = isset( $export_params['step'] ) && $export_params['step'] > 0 ? $export_params['step'] : 1;
			$start_date              = strtotime( "$hidden_start 00:01:01" );
			$end_date                = strtotime( "$hidden_end 23:59:59" );
			$cart_source_field       = isset( $export_params['filter_source'] ) && '' !== $export_params['filter_source'] ? $export_params['filter_source'] : 'all';

			$per_step = 1000;
			if ( $step > 1 ) {
				--$step;
				$start_limit = ( $per_step * $step );
				$limit       = "limit $start_limit, $per_step";
			} else {
				$start_limit = 0;
				$limit       = "limit $start_limit, $per_step";
			}

			switch ( $cart_status_field ) {
				case 'abandoned':
					$status_filter = "( wpac.cart_ignored = '0' AND wpac.recovered_cart = 0 ) ";
					break;
				case 'recovered':
					$status_filter = "( wpac.cart_ignored = '1' AND wpac.recovered_cart > 0 ) ";
					break;
				case 'unpaid':
					$status_filter = "( wpac.cart_ignored = '2' AND wpac.recovered_cart = 0 ) ";
					break;
				case 'received':
					$status_filter = "( wpac.cart_ignored = '3' AND wpac.recovered_cart = 0 ) ";
					break;
				case 'cancelled':
					$status_filter = "(wpac.cart_ignored = '4' AND wpac.recovered_cart = 0 ) ";
					break;
				case 'all':
				default:
					$status_filter = "( ( wpac.cart_ignored <> '1' AND wpac.recovered_cart = 0) OR ( wpac.cart_ignored = '1' AND wpac.recovered_cart > 0 ) ) ";
					break;
			}
			switch ( $cart_source_field ) {
				case 'all':
				default:
					$source_filter = '';
					break;
				case 'checkout_page':
					$source_filter = "AND wpac.abandoned_cart_info like '%checkout%' AND wpac.user_id >= 63000000";
					break;
				case 'product_page':
					$source_filter = 'AND wpac.user_id > 0 AND wpac.user_id < 63000000';
					break;
				case 'atc':
					$source_filter = "AND wpac.abandoned_cart_info like '%atc%' AND wpac.user_id >= 63000000";
					break;
				case 'exit_intent':
					$source_filter = "AND wpac.abandoned_cart_info like '%exit_intent%'";
					break;
				case 'custom_form':
					$source_filter = "AND wpac.abandoned_cart_info like '%custom_form%'";
					break;
				case 'url':
					$source_filter = "AND wpac.abandoned_cart_info LIKE '%url%'";
					break;
			}
			switch ( $cart_section ) {
				case 'wcap_all_abandoned':
				default:
					if ( is_multisite() ) {
						$main_prefix = $wpdb->get_blog_prefix( 1 );
						$results     = $wpdb->get_results( // phpcs:disable
							$wpdb->prepare(
								'SELECT wpac.* , wpu.user_login, wpu.user_email FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` AS wpac LEFT JOIN ' . $main_prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE ( wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_time <= %d ) AND wpac.abandoned_cart_time >=  %d AND wpac.abandoned_cart_time <= %d AND wpac.abandoned_cart_info NOT LIKE %s AND wcap_trash = '' AND $status_filter $source_filter ORDER BY wpac.abandoned_cart_time DESC $limit", // phpcs:ignore
								$blank_cart_info_guest,
								$compare_time_guest,
								$start_date,
								$end_date,
								"%$blank_cart_info%"
							)
						);
					} else {
						$query   = $wpdb->prepare(
							'SELECT wpac.* , wpu.user_login, wpu.user_email FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` AS wpac LEFT JOIN ' . $wpdb->prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE ( wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_time <= %d ) AND wpac.abandoned_cart_time >= %d AND wpac.abandoned_cart_time <= %d AND wpac.abandoned_cart_info NOT LIKE %s AND wcap_trash = '' AND $status_filter $source_filter ORDER BY wpac.abandoned_cart_time DESC $limit", // phpcs:ignore
							$blank_cart_info_guest,
							$compare_time_guest,
							$start_date,
							$end_date,
							"%$blank_cart_info%"
						);
						$results = $wpdb->get_results( // phpcs:ignore
							$query
						);
					}
					break;

				case 'wcap_all_registered':
					if ( is_multisite() ) {
						$main_prefix = $wpdb->get_blog_prefix( 1 );
						$results     = $wpdb->get_results( // phpcs:ignore
							$wpdb->prepare(
								'SELECT wpac.* , wpu.user_login, wpu.user_email FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` AS wpac LEFT JOIN ' . $main_prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE user_type = 'REGISTERED' AND wpac.abandoned_cart_time <= %d AND wpac.abandoned_cart_time >= %d AND wpac.abandoned_cart_time <= %d AND wpac.abandoned_cart_info NOT LIKE %s AND wcap_trash = '' AND $status_filter $source_filter ORDER BY wpac.abandoned_cart_time DESC $limit", // phpcs:ignore
								$compare_time,
								$start_date,
								$end_date,
								"%$blank_cart_info%"
							)
						);
					} else {
						$results = $wpdb->get_results( // phpcs:ignore
							$wpdb->prepare(
								'SELECT wpac.* , wpu.user_login, wpu.user_email FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` AS wpac LEFT JOIN ' . $wpdb->prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE user_type = 'REGISTERED' AND wpac.abandoned_cart_time >= %d AND wpac.abandoned_cart_time <= %d AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_time <= %d AND wcap_trash = '' AND $status_filter $source_filter ORDER BY wpac.abandoned_cart_time DESC $limit", // phpcs:ignore
								$start_date,
								$end_date,
								"%$blank_cart_info%",
								$compare_time
							)
						);
					}
					break;
				case 'wcap_all_guest':
					if ( is_multisite() ) {
						$main_prefix = $wpdb->get_blog_prefix( 1 );
						$results     = $wpdb->get_results( // phpcs:ignore
							$wpdb->prepare(
								'SELECT wpac.* , wpu.user_login, wpu.user_email FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` AS wpac LEFT JOIN ' . $main_prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE user_type = 'GUEST' AND user_id >= '63000000' AND wpac.abandoned_cart_time >= %d AND wpac.abandoned_cart_time <= %d AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_time <= %d AND wcap_trash = '' AND $status_filter $source_filter ORDER BY wpac.abandoned_cart_time DESC $limit", // phpcs:ignore
								$start_date,
								$end_date,
								$blank_cart_info_guest,
								"%$blank_cart_info%",
								$compare_time_guest
							)
						);
					} else {
						$results = $wpdb->get_results( // phpcs:ignore
							$wpdb->prepare(
								'SELECT wpac.* , wpu.user_login, wpu.user_email FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` AS wpac LEFT JOIN ' . $wpdb->prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE user_type = 'GUEST' AND user_id >= '63000000' AND wpac.abandoned_cart_time >= %d AND wpac.abandoned_cart_time <= %d AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_time <= %d AND wcap_trash = '' AND $status_filter $source_filter ORDER BY wpac.abandoned_cart_time DESC $limit", // phpcs:ignore
								$start_date,
								$end_date,
								$blank_cart_info_guest,
								"%$blank_cart_info%",
								$compare_time_guest
							)
						);
					}
					break;
				case 'wcap_all_visitor':
					if ( is_multisite() ) {
						$main_prefix = $wpdb->get_blog_prefix( 1 );
						$results     = $wpdb->get_results( // phpcs:ignore
							$wpdb->prepare(
								'SELECT wpac.* , wpu.user_login, wpu.user_email FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` AS wpac LEFT JOIN ' . $main_prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE user_type = 'GUEST' AND user_id = '0' AND wpac.abandoned_cart_time >= %d AND wpac.abandoned_cart_time <= %d AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_time <= %d AND wcap_trash = '' AND $status_filter $source_filter ORDER BY wpac.abandoned_cart_time DESC $limit", // phpcs:ignore
								$start_date,
								$end_date,
								$blank_cart_info_guest,
								"%$blank_cart_info%",
								$compare_time_guest
							)
						);
					} else {
						$results = $wpdb->get_results( // phpcs:ignore
							$wpdb->prepare(
								'SELECT wpac.* , wpu.user_login, wpu.user_email FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` AS wpac LEFT JOIN ' . $wpdb->prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE user_type = 'GUEST' AND user_id = '0' AND wpac.abandoned_cart_time >= %d AND wpac.abandoned_cart_time <= %d AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_time <= %d AND wcap_trash = '' AND $status_filter $source_filter ORDER BY wpac.abandoned_cart_time DESC $limit", // phpcs:ignore
								$start_date,
								$end_date,
								$blank_cart_info_guest,
								"%$blank_cart_info%",
								$compare_time_guest
							)
						);
					}
					break;

				case 'wcap_trash_abandoned':
					if ( is_multisite() ) {
						$main_prefix = $wpdb->get_blog_prefix( 1 );
						$results     = $wpdb->get_results( // phpcs:ignore
							$wpdb->prepare(
								'SELECT wpac.* , wpu.user_login, wpu.user_email FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` AS wpac LEFT JOIN ' . $main_prefix . "users AS wpu ON wpac.user_id = wpu.id  (  wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_time <= %d ) AND wpac.abandoned_cart_time >= %d AND wpac.abandoned_cart_time <= %d AND wpac.abandoned_cart_info NOT LIKE %s AND wcap_trash = '1' AND $status_filter $source_filter ORDER BY wpac.abandoned_cart_time DESC $limit", // phpcs:ignore
								$blank_cart_info_guest,
								$compare_time_guest,
								$start_date,
								$end_date,
								"%$blank_cart_info%"
							)
						);
					} else {
						$query   = $wpdb->prepare(
							'SELECT wpac.* , wpu.user_login, wpu.user_email FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` AS wpac LEFT JOIN ' . $wpdb->prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE ( wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_time <= %d ) AND wpac.abandoned_cart_time >= %d AND wpac.abandoned_cart_time <= %d AND wpac.abandoned_cart_info NOT LIKE %s AND wcap_trash = '1' AND $status_filter $source_filter ORDER BY wpac.abandoned_cart_time DESC $limit", // phpcs:ignore
							$blank_cart_info_guest,
							$compare_time_guest,
							$start_date,
							$end_date,
							"%$blank_cart_info%"
						);
						$results = $wpdb->get_results( // phpcs:ignore
							$query
						);
					}
					break;
				case 'wcap_all_unsubscribe_carts':
					if ( is_multisite() ) {
						$main_prefix = $wpdb->get_blog_prefix( 1 );
						$results     = $wpdb->get_results( // phpcs:ignore
							$wpdb->prepare(
								'SELECT wpac.* , wpu.user_login, wpu.user_email FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` AS wpac LEFT JOIN ' . $main_prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE ( wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_time <= %d ) AND wpac.abandoned_cart_time >= %d AND wpac.abandoned_cart_time <= %d AND wpac.abandoned_cart_info NOT LIKE %s AND unsubscribe_link = '1' AND $status_filter $source_filter ORDER BY wpac.abandoned_cart_time DESC $limit", // phpcs:ignore
								$blank_cart_info_guest,
								$compare_time_guest,
								$start_date,
								$end_date,
								"%$blank_cart_info%"
							)
						);
					} else {
						$results = $wpdb->get_results( // phpcs:ignore
							$wpdb->prepare(
								'SELECT wpac.* , wpu.user_login, wpu.user_email FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` AS wpac LEFT JOIN ' . $wpdb->prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE ( wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_time <= %d ) AND wpac.abandoned_cart_time >= %d AND wpac.abandoned_cart_time <= %d AND wpac.abandoned_cart_info NOT LIKE %s AND unsubscribe_link = '1' AND $status_filter $source_filter ORDER BY wpac.abandoned_cart_time DESC $limit", // phpcs:ignore
								$blank_cart_info_guest,
								$compare_time_guest,
								$start_date,
								$end_date,
								"%$blank_cart_info%"
							)
						);
					}
					break;
			}

			$i                        = 0;
			$display_tracked_coupons  = get_option( 'ac_track_coupons' );
			$wp_date_format           = get_option( 'date_format' );
			$wp_time_format           = get_option( 'time_format' );
			$ac_cutoff_time           = is_numeric( get_option( 'ac_cart_abandoned_time', 10 ) ) ? get_option( 'ac_cart_abandoned_time', 10 ) : 10;
			$current_time             = current_time( 'timestamp' ); // phpcs:ignore
			$wcap_include_tax         = get_option( 'woocommerce_prices_include_tax' );
			$wcap_include_tax_setting = get_option( 'woocommerce_calc_taxes' );

			foreach ( $results as $key => $value ) {
				if ( 'GUEST' === $value->user_type ) {
					$results_guest = $wpdb->get_results( // phpcs:ignore
						$wpdb->prepare(
							'SELECT * from `' . WCAP_GUEST_CART_HISTORY_TABLE . '` WHERE id = %d', // phpcs:ignore
							$value->user_id
						)
					);
				}
				$abandoned_order_id = $value->id;
				$user_id            = (int) $value->user_id;
				$user_login         = $value->user_login;
				if ( 'GUEST' === $value->user_type ) {

					$user_email = '';
					if ( isset( $results_guest[0]->email_id ) ) {
						$user_email = $results_guest[0]->email_id;
					}

					if ( isset( $results_guest[0]->billing_first_name ) ) {
						$user_first_name = $results_guest[0]->billing_first_name;
					} elseif ( '0' === (string) $value->user_id ) {
						$user_first_name = 'Visitor';
					} else {
						$user_first_name = '';
					}

					$user_last_name = '';
					if ( isset( $results_guest[0]->billing_last_name ) ) {
						$user_last_name = $results_guest[0]->billing_last_name;
					}

					$phone = '';
					if ( isset( $results_guest[0]->phone ) ) {
						$phone = $results_guest[0]->phone;
					}
				} else {
					$user_email_biiling = get_user_meta( $user_id, 'billing_email', true );
					$user_email         = __( 'User Deleted', 'woocommerce-ac' );
					if ( isset( $user_email_biiling ) && '' === $user_email_biiling ) {
						$user_data = get_userdata( $user_id );
						if ( isset( $user_data->user_email ) && '' !== $user_data->user_email ) {
							$user_email = $user_data->user_email;
						}
					} elseif ( '' !== $user_email_biiling ) {
						$user_email = $user_email_biiling;
					}
					$user_first_name_temp = get_user_meta( $user_id, 'billing_first_name', true );
					if ( isset( $user_first_name_temp ) && '' === $user_first_name_temp ) {
						$user_first_name = '';
						if ( isset( $user_data->first_name ) && '' === $user_data->first_name ) {
							$user_first_name = $user_data->first_name;
						}
					} else {
						$user_first_name = $user_first_name_temp;
					}
					$user_last_name_temp = get_user_meta( $user_id, 'billing_last_name', true );
					if ( isset( $user_last_name_temp ) && '' === $user_last_name_temp ) {

						$user_last_name = '';
						if ( isset( $user_data->last_name ) && '' === $user_data->last_name ) {
							$user_last_name = $user_data->last_name;
						}
					} else {
						$user_last_name = $user_last_name_temp;
					}
					$user_phone_number = get_user_meta( $value->user_id, 'billing_phone' );
					if ( isset( $user_phone_number[0] ) ) {
						$phone = $user_phone_number[0];
					} else {
						$phone = '';
					}
				}
				$cart_info        = json_decode( stripslashes( $value->abandoned_cart_info ) );
				$order_date       = '';
				$cart_update_time = $value->abandoned_cart_time;
				if ( '' !== $cart_update_time && $cart_update_time > 0 ) {
					$date_format = date_i18n( $wp_date_format, $cart_update_time );
					$time_format = date_i18n( $wp_time_format, $cart_update_time );
					$order_date  = $date_format . ' ' . $time_format;
				}
				$cut_off_time                = $ac_cutoff_time * 60;
				$compare_time                = $current_time - $cart_update_time;
				$cart_details                = new stdClass();
				$line_total                  = 0;
				$cart_total                  = 0;
				$item_subtotal               = 0;
				$item_total                  = 0;
				$line_subtotal_tax_display   = 0;
				$after_item_subtotal         = 0;
				$after_item_subtotal_display = 0;
				$line_subtotal_tax           = 0;

				if ( isset( $cart_info->cart ) ) {
					$cart_details = $cart_info->cart;
				}

				$captured_by = isset( $cart_info->captured_by ) && '' !== $cart_info->captured_by ? $cart_info->captured_by : '';
				switch ( $captured_by ) {
					default:
					case '':
					case 'user_profile':
						$captured_text = __( 'User Profile', 'woocommerce-ac' );
						break;
					case 'exit_intent':
						$captured_text = __( 'Exit Intent Popup', 'woocommerce-ac' );
						break;
					case 'atc':
						$captured_text = __( 'Add to Cart Popup', 'woocommerce-ac' );
						break;
					case 'checkout':
						$captured_text = __( 'Checkout page', 'woocommerce-ac' );
						break;
					case 'custom_form':
						$captured_text = __( 'Custom Form', 'woocommerce-ac' );
						break;
					case 'url':
						$captured_text = __( 'URL', 'woocommerce-ac' );
						break;
				}
				// Currency selected.
				$currency = isset( $cart_info->currency ) ? $cart_info->currency : '';

				$prod_name = '';
				if ( isset( $cart_details ) && is_object( $cart_details ) && count( get_object_vars( $cart_details ) ) > 0 ) {
					foreach ( $cart_details as $k => $v ) {
						$prod_name        .= '<br>' . apply_filters( 'wcap_product_name', get_the_title( $v->product_id ), $v->product_id ) . '</br>';
						$wcap_product      = wc_get_product( $v->product_id );
						$wcap_product_type = '';
						$wcap_sku          = '';
						if ( false !== $wcap_product ) {
							$wcap_product_type = $wcap_product->get_type();
							if ( 'simple' === $wcap_product_type && '' !== $wcap_product->get_sku() ) {
								$wcap_sku = '<br> SKU: ' . apply_filters( 'wcap_product_sku', $wcap_product->get_sku(), $v->product_id );
							}
						}

						$prod_name = $prod_name . $wcap_sku;
						$prod_name = apply_filters( 'wcap_after_product_name', $prod_name, $v->product_id );

						if ( isset( $v->variation_id ) && $v->variation_id > 0 ) {
							$variation_id = $v->variation_id;
							$variation    = wc_get_product( $variation_id );
							if ( false !== $variation ) {
								$name        = $variation->get_formatted_name();
								$explode_all = explode( '&ndash;', $name );

								if ( version_compare( WC()->version, '3.0.0', '>=' ) ) {
									$wcap_sku = '';
									if ( $variation->get_sku() ) {
										$wcap_sku = 'SKU: ' . $variation->get_sku() . '<br>';
									}
									$wcap_get_formatted_variation = wc_get_formatted_variation( $variation, true );

									$add_product_name = $prod_name . ' - ' . $wcap_sku . $wcap_get_formatted_variation;

									$pro_name_variation = (array) $add_product_name;
								} else {
									$pro_name_variation = array_slice( $explode_all, 1, -1 );
								}
								$product_name_with_variable = '';
								$explode_many_varaition     = array();
								foreach ( $pro_name_variation as $pro_name_variation_key => $pro_name_variation_value ) {
									$explode_many_varaition = explode( ',', $pro_name_variation_value );
									if ( ! empty( $explode_many_varaition ) ) {
										foreach ( $explode_many_varaition as $explode_many_varaition_key => $explode_many_varaition_value ) {
											$product_name_with_variable = $product_name_with_variable . html_entity_decode( $explode_many_varaition_value ) . '<br>';
										}
									} else {
										$product_name_with_variable = $product_name_with_variable . html_entity_decode( $explode_many_varaition_value ) . '<br>';
									}
								}
								$prod_name = apply_filters( 'wcap_after_variable_product_name', $product_name_with_variable, $v->product_id );
							}
						}

						if ( isset( $wcap_include_tax ) && 'no' === $wcap_include_tax && isset( $wcap_include_tax_setting ) && 'yes' === $wcap_include_tax_setting ) {
							$line_total         = $line_total + $v->line_total;
							$line_subtotal_tax += $v->line_tax; // This is fix.
						} elseif ( isset( $wcap_include_tax ) && 'yes' === $wcap_include_tax && isset( $wcap_include_tax_setting ) && 'yes' === $wcap_include_tax_setting ) {
							// Item subtotal is calculated as product total including taxes.
							if ( isset( $v->line_tax ) && $v->line_tax > 0 ) {
								$line_subtotal_tax_display += $v->line_tax;
								// After copon code price.
								$after_item_subtotal = $item_subtotal + $v->line_total + $v->line_tax;
								// Calculate the product price.
								$item_subtotal = $item_subtotal + $v->line_subtotal + $v->line_subtotal_tax;
								$line_total    = $line_total + $v->line_subtotal + $v->line_subtotal_tax;
							} else {
								$item_subtotal              = $item_subtotal + $v->line_total;
								$line_total                 = $line_total + $v->line_total;
								$line_subtotal_tax_display += $v->line_tax;
							}
						} else {
							$line_total = $line_total + $v->line_total;
						}
					}
				}
				$line_total = $line_total;

				if ( isset( $wcap_include_tax ) && 'no' === $wcap_include_tax && isset( $wcap_include_tax_setting ) && 'yes' === $wcap_include_tax_setting ) {

					$line_subtotal_tax = $line_subtotal_tax;
				} elseif ( isset( $wcap_include_tax ) && 'yes' === $wcap_include_tax ) {
					$line_subtotal_tax = $line_subtotal_tax_display;
				}

				$quantity_total = 0;
				if ( isset( $cart_details ) && is_object( $cart_details ) && count( get_object_vars( $cart_details ) ) > 0 ) {
					foreach ( $cart_details as $k => $v ) {
						$quantity_total = $quantity_total + $v->quantity;
					}
				}
				if ( 1 === (int) $quantity_total ) {
					$item_disp = __( 'item', 'woocommerce-ac' );
				} else {
					$item_disp = __( 'items', 'woocommerce-ac' );
				}
				$coupon_details          = get_user_meta( $value->user_id, '_woocommerce_ac_coupon', true );
				$coupon_detail_post_meta = Wcap_Common::wcap_get_coupon_post_meta( $value->id );

				$ac_status = '';
				if ( '1' === $value->unsubscribe_link ) {
					$ac_status = __( 'Unsubscribed', 'woocommerce-ac' );
				} elseif ( '0' === $value->cart_ignored && 0 === (int) $value->recovered_cart ) {
					$ac_status = __( 'Abandoned', 'woocommerce-ac' );
				} elseif ( '1' === $value->cart_ignored && 0 === (int) $value->recovered_cart ) {
					$ac_status = __( 'Abandoned but new cart created after this', 'woocommerce-ac' );
				} elseif ( '2' === $value->cart_ignored && 0 === (int) $value->recovered_cart ) {
					$ac_status = __( 'Abandoned - Order Unpaid', 'woocommerce-ac' );
				} elseif ( '3' === $value->cart_ignored && 0 === (int) $value->recovered_cart ) {
					$ac_status = __( 'Abandoned - Order Received', 'woocommerce-ac' );
				} elseif ( '1' === $value->cart_ignored && $value->recovered_cart > 0 ) {
					$ac_status = __( 'Recovered', 'woocommerce-ac' );
				}

				$ip_address = '';
				if ( isset( $value->ip_address ) ) {
					$ip_address = $value->ip_address;
				}

				$coupon_code_used    = '';
				$coupon_code_message = '';
				if ( $compare_time > $cut_off_time && '' !== $ac_status ) {
					$return_abandoned_orders[ $i ] = new stdClass();
					if ( $quantity_total > 0 ) {
						$user_role = '';
						if ( isset( $user_id ) ) {
							if ( 0 === $user_id ) {
								$user_role = 'Guest';
							} elseif ( $user_id >= 63000000 ) {
								$user_role = 'Guest';
							} else {
								$user_role = Wcap_Common::wcap_get_user_role( $user_id );
							}
						}
						$abandoned_order_id                   = $abandoned_order_id;
						$customer_information                 = $user_first_name . ' ' . $user_last_name;
						$return_abandoned_orders[ $i ]->id    = $abandoned_order_id;
						$return_abandoned_orders[ $i ]->email = $user_email;
						if ( '' === $phone ) {
							$return_abandoned_orders[ $i ]->customer = $customer_information . '<br>' . $user_role;
						} else {
							$return_abandoned_orders[ $i ]->customer = $customer_information . '<br>' . $phone . '<br>' . $user_role;
						}
						$return_abandoned_orders[ $i ]->order_total   = $line_total;
						$return_abandoned_orders[ $i ]->quantity      = $quantity_total . ' ' . $item_disp;
						$return_abandoned_orders[ $i ]->date          = $order_date;
						$return_abandoned_orders[ $i ]->status        = $ac_status;
						$return_abandoned_orders[ $i ]->user_id       = $user_id;
						$return_abandoned_orders[ $i ]->product_names = $prod_name;
						$return_abandoned_orders[ $i ]->tax_type      = 'yes' === $wcap_include_tax ? 'inc' : 'exc';

						$return_abandoned_orders[ $i ]->tax_setting     = $wcap_include_tax_setting;
						$return_abandoned_orders[ $i ]->tax_amount      = $line_subtotal_tax;
						$return_abandoned_orders[ $i ]->user_ip_address = $ip_address;
						if ( '' !== $currency ) {
							$return_abandoned_orders[ $i ]->currency = $currency;
						}
						$return_abandoned_orders[ $i ]->captured_by = $captured_text;

						// Recovered Order ID.
						if ( $value->recovered_cart > 0 ) {
							$return_abandoned_orders[ $i ]->recovered_id = $value->recovered_cart;
						}
						if ( 'on' === $display_tracked_coupons ) {
							if ( '' !== $coupon_detail_post_meta ) {
								foreach ( $coupon_detail_post_meta as $key => $value ) {
									if ( '' !== $key ) {
										$coupon_code_used    .= $key . '</br>';
										$coupon_code_message .= $value . '</br>';
									}
								}
								$return_abandoned_orders[ $i ]->coupon_code_used   = $coupon_code_used;
								$return_abandoned_orders[ $i ]->coupon_code_status = $coupon_code_message;
							}
						}
					} else {
						$abandoned_order_id                    = $abandoned_order_id;
						$return_abandoned_orders[ $i ]->id     = $abandoned_order_id;
						$return_abandoned_orders[ $i ]->date   = $order_date;
						$return_abandoned_orders[ $i ]->status = $ac_status;
					}
					$i++;
				}
			}
			return $return_abandoned_orders;
		}

		/**
		 * It will prepare the data for the csv.
		 *
		 * @param array $report All abandoned cart information.
		 * @param bool  $column - Display Column Names.
		 * @return string $csv Prepared csv format data.
		 */
		public static function wcap_generate_csv( $report, $column = false ) {

			$csv = '';
			// Tracking coupons.
			$display_tracked_coupons = get_option( 'ac_track_coupons' );
			// Column Names.
			if ( $column ) {
				if ( 'on' === $display_tracked_coupons ) {
					$csv = 'ID, Email Address, Customer, Products, Order Total, Quantity, Abandoned Date, Coupon Code Used, Coupon Status, Email Captured By, Status of cart, Order ID (Recovered), IP Address';
				} else {
					$csv = 'ID, Email Address, Customer, Products, Order Total, Quantity, Abandoned Date, Email Captured By, Status of cart, Order ID (Recovered), IP Address';
				}
				$csv .= "\n";
			}

			foreach ( $report as $key => $value ) {
				$woocommerce_currency = isset( $value->currency ) ? $value->currency : get_woocommerce_currency();

				$currencey       = apply_filters( 'acfac_get_cart_currency', $woocommerce_currency, $value->id );
				$currency_symbol = get_woocommerce_currency_symbol( $currencey );

				// Order ID.
				$order_id = '';
				if ( isset( $value->id ) ) {
					$order_id = $value->id;
				}

				$email_id = '';
				if ( isset( $value->email ) ) {
					$email_id = $value->email;
				}

				$name = '';
				if ( isset( $value->customer ) ) {
					$name = $value->customer;
					$name = str_replace( '<br>', "\n", $name );
				}

				$product_name = '';
				if ( isset( $value->product_names ) ) {
					$product_name = wp_strip_all_tags( $value->product_names );
				}

				$product_name = str_replace( '</br>', "\n", $product_name );

				$order_total = '';
				if ( isset( $value->order_total ) ) {
					$order_total = $value->order_total;
				}

				$final_order_total = wp_strip_all_tags( html_entity_decode( wc_price( $order_total, array( 'currency' => $woocommerce_currency ) ) ) );

				if ( isset( $value->tax_setting ) && 'yes' === $value->tax_setting && isset( $value->tax_type ) && 'inc' === $value->tax_type ) {

					$final_subtotal_tax = wp_strip_all_tags( html_entity_decode( wc_price( $value->tax_amount, array( 'currency' => $woocommerce_currency ) ) ) );
					$final_order_total  = $final_order_total . ' (includes Tax: ' . $final_subtotal_tax . ')';
				} elseif ( isset( $value->tax_setting ) && 'yes' === $value->tax_setting && isset( $value->tax_type ) && 'exc' === $value->tax_type ) {

					$final_subtotal_tax = wp_strip_all_tags( html_entity_decode( wc_price( $value->tax_amount, array( 'currency' => $woocommerce_currency ) ) ) );
					$final_order_total  = $final_order_total . "\n" . 'Tax: ' . $final_subtotal_tax;
				}

				$quantity = '';
				if ( isset( $value->quantity ) ) {
					$quantity = $value->quantity;
				}

				$abandoned_date = '';
				if ( isset( $value->date ) ) {
					$abandoned_date = $value->date;
				}

				$abandoned_status = '';
				if ( isset( $value->status ) ) {
					$abandoned_status = $value->status;
				}

				$wcap_email_captured_by = $value->captured_by;

				$user_ip_address = '';
				if ( isset( $value->user_ip_address ) ) {
					$user_ip_address = $value->user_ip_address;
				}

				$recovered_order_id = isset( $value->recovered_id ) && $value->recovered_id > 0 ? $value->recovered_id : '';

				if ( 'on' === $display_tracked_coupons ) {
					if ( isset( $value->coupon_code_used ) ) {
						$coupon_used = $value->coupon_code_used;
					} else {
						$coupon_used = '';
					}
					$coupon_used   = str_replace( '</br>', "\n", $coupon_used );
					$coupon_status = '';
					if ( isset( $value->coupon_code_status ) && '' !== $value->coupon_code_status ) {
						$coupon_status = $value->coupon_code_status;
						$coupon_status = str_replace( '</br>', "\n", $coupon_status );
					}
					// Create the data row - Commas present in strings need to be escaped for CSV files. Hence wrap the content in double quotes.
					$row_data = $order_id . ',' . $email_id . ',' . "\" $name \"" . ',' . "\"  $product_name \"" . ',' . "\" $final_order_total \"" . ',' . $quantity . ',' . "\" $abandoned_date\"" . ',' . "\" $coupon_used \"" . ',' . "\" $coupon_status \"" . ',' . "\" $wcap_email_captured_by \"" . ',' . $abandoned_status . ',' . $recovered_order_id . ',' . $user_ip_address;
					$row_data = apply_filters( 'wcap_abandoned_carts_csv_row_data', $row_data, $value );

					// Append the data.
					$csv .= $row_data;
					$csv .= "\n";
				} else {
					// Create the data row.
					$row_data = $order_id . ',' . $email_id . ',' . "\" $name \"" . ',' . "\"  $product_name \"" . ',' . "\" $final_order_total \"" . ',' . $quantity . ',' . "\" $abandoned_date\"" . ',' . "\" $wcap_email_captured_by\"" . ',' . $abandoned_status . ',' . $recovered_order_id . ',' . $user_ip_address;
					$row_data = apply_filters( 'wcap_abandoned_carts_csv_row_data', $row_data, $value );
					// Append the data.
					$csv .= $row_data;
					$csv .= "\n";
				}
			}
			$csv = apply_filters( 'wcap_abandoned_carts_csv_data', $csv, $report );
			return $csv;
		}

		/**
		 * It will prepare the data for the print.
		 *
		 * @param array $report All abandoned cart information.
		 * @param bool  $table - Table HTML should be included or no.
		 * @param bool  $col_data - Column Names should be included or no.
		 * @param bool  $row_data - Include row data or no.
		 * @return string $print_data Prepared print format data
		 */
		public static function wcap_download_print_file( $report, $table = false, $col_data = false, $row_data = false ) {
			// Tracking coupons.
			$display_tracked_coupons = get_option( 'ac_track_coupons' );
			$columns                 = array(
				'id'             => __( 'ID', 'woocommerce-ac' ),
				'email'          => __( 'Email Address', 'woocommerce-ac' ),
				'cust_details'   => __( 'Customer Details', 'woocommerce-ac' ),
				'products'       => __( 'Products', 'woocommerce-ac' ),
				'order_total'    => __( 'Order Total', 'woocommerce-ac' ),
				'quantity'       => __( 'Quantity', 'woocommerce-ac' ),
				'abandoned_date' => __( 'Abandoned Date', 'woocommerce-ac' ),
			);

			if ( 'on' === $display_tracked_coupons ) {
				$columns['coupon_used']   = __( 'Coupon Code Used', 'woocommerce-ac' );
				$columns['coupon_status'] = __( 'Coupon Status', 'woocommerce-ac' );
			}

			$columns['captured_by']  = __( 'Email Captured By', 'woocommerce-ac' );
			$columns['cart_status']  = __( 'Status of cart', 'woocommerce-ac' );
			$columns['recovered_id'] = __( 'Order ID (Recovered)', 'woocommerce-ac' );
			$columns['ip_address']   = __( 'IP Address', 'woocommerce-ac' );

			$columns = apply_filters( 'wcap_abandoned_carts_print_columns', $columns );

			$print_data_columns = '<tr>';
			foreach ( $columns as $column_names ) {
				$print_data_columns .= "<th style='border:1px solid black;padding:5px;'>" . $column_names . '</th>';
			}
			$print_data_columns .= '</tr>';

			if ( $col_data ) {
				return $print_data_columns;
			}
			$print_data_row_data = '';
			foreach ( $report as $key => $value ) {

				$woocommerce_currency = isset( $value->currency ) ? $value->currency : get_woocommerce_currency();

				$wcap_currency = apply_filters( 'acfac_get_cart_currency', $woocommerce_currency, $value->id );
				$currency      = get_woocommerce_currency_symbol( $wcap_currency );

				$abandoned_id = '';
				if ( isset( $value->id ) ) {
					$abandoned_id = $value->id;
				}

				$customer_email = '';
				if ( isset( $value->email ) ) {
					$customer_email = $value->email;
				}

				$customer_name = '';
				if ( isset( $value->customer ) ) {
					$customer_name = $value->customer;
				}

				$product_names = '';
				if ( isset( $value->product_names ) ) {
					$product_names = $value->product_names;
				}

				if ( isset( $value->order_total ) ) {
					$order_total = $value->order_total;
				} else {
					$order_total = '';
					$currency    = '';
				}

				$final_order_total = wp_strip_all_tags( html_entity_decode( wc_price( $order_total, array( 'currency' => $woocommerce_currency ) ) ) );

				if ( isset( $value->tax_setting ) && 'yes' === $value->tax_setting && isset( $value->tax_type ) && 'inc' === $value->tax_type ) {

					$final_subtotal_tax = wp_strip_all_tags( html_entity_decode( wc_price( $value->tax_amount, array( 'currency' => $woocommerce_currency ) ) ) );
					$final_order_total  = $final_order_total . ' (includes Tax: ' . $final_subtotal_tax . ')';
				} elseif ( isset( $value->tax_setting ) && 'yes' === $value->tax_setting && isset( $value->tax_type ) && 'exc' === $value->tax_type ) {

					$final_subtotal_tax = wp_strip_all_tags( html_entity_decode( wc_price( $value->tax_amount, array( 'currency' => $woocommerce_currency ) ) ) );
					$final_order_total  = $final_order_total . '<br> Tax: ' . $final_subtotal_tax;
				}

				$order_quantity = '';
				if ( isset( $value->quantity ) ) {
					$order_quantity = $value->quantity;
				}

				$coupon_code_used = '';
				if ( isset( $value->coupon_code_used ) ) {
					$coupon_code_used = $value->coupon_code_used;
				}

				$coupon_code_status = '';
				if ( isset( $value->coupon_code_status ) ) {
					$coupon_code_status = $value->coupon_code_status;
				}

				$abandoned_date = '';
				if ( isset( $value->date ) ) {
					$abandoned_date = $value->date;
				}

				$abandoned_status = '';
				if ( isset( $value->status ) ) {
					$abandoned_status = $value->status;
				}
				$user_ip_address = '';
				if ( isset( $value->user_ip_address ) ) {
					$user_ip_address = $value->user_ip_address;
				}

				$wcap_email_captured_by = isset( $value->captured_by ) ? $value->captured_by : '';

				$recovered_order_id = ( isset( $value->recovered_id ) && $value->recovered_id > 0 ) ? $value->recovered_id : '';

				if ( 'on' === $display_tracked_coupons ) {
					$print_this_row = "<tr>
                                        <td style='border:1px solid black;padding:5px;'>" . $abandoned_id . "</td>
                                        <td style='border:1px solid black;padding:5px;'>" . $customer_email . "</td>
                                        <td style='border:1px solid black;padding:5px;'>" . $customer_name . "</td>
                                        <td style='border:1px solid black;padding:5px;'>" . $product_names . "</td>
                                        <td style='border:1px solid black;padding:5px;'>" . $final_order_total . "</td>
                                        <td style='border:1px solid black;padding:5px;'>" . $order_quantity . "</td>
                                        <td style='border:1px solid black;padding:5px;'>" . $abandoned_date . "</td>
                                        <td style='border:1px solid black;padding:5px;'>" . $coupon_code_used . "</td>
                                        <td style='border:1px solid black;padding:5px;'>" . $coupon_code_status . "</td>
                                        <td style='border:1px solid black;padding:5px;'>" . $wcap_email_captured_by . "</td>
                                        <td style='border:1px solid black;padding:5px;'>" . $abandoned_status . "</td>
                                        <td style='border:1px solid black;padding:5px;'>" . $recovered_order_id . "</td>
                                        <td style='border:1px solid black;padding:5px;'>" . $user_ip_address . '</td>
                                        </tr>';
				} else {
					$print_this_row = "<tr>
                                        <td style='border:1px solid black;padding:5px;'>" . $abandoned_id . "</td>
                                        <td style='border:1px solid black;padding:5px;'>" . $customer_email . "</td>
                                        <td style='border:1px solid black;padding:5px;'>" . $customer_name . "</td>
                                        <td style='border:1px solid black;padding:5px;'>" . $product_names . "</td>
                                        <td style='border:1px solid black;padding:5px;'>" . $final_order_total . "</td>
                                        <td style='border:1px solid black;padding:5px;'>" . $order_quantity . "</td>
                                        <td style='border:1px solid black;padding:5px;'>" . $abandoned_date . "</td>
                                        <td style='border:1px solid black;padding:5px;'>" . $wcap_email_captured_by . "</td>
                                        <td style='border:1px solid black;padding:5px;'>" . $abandoned_status . "</td>
                                        <td style='border:1px solid black;padding:5px;'>" . $recovered_order_id . "</td>
                                        <td style='border:1px solid black;padding:5px;'>" . $user_ip_address . '</td>
                                        </tr>';
				}
				$print_this_row       = apply_filters( 'wcap_abandoned_carts_print_row_data', $print_this_row, $value );
				$print_data_row_data .= $print_this_row;
			}
			$print_data_row_data = apply_filters( 'wcap_abandoned_carts_print_rows', $print_data_row_data, $report );
			if ( $row_data ) {
				return $print_data_row_data;
			}

			$print_data_title = apply_filters( 'wcap_abandoned_carts_print_title', __( 'Print Abandoned Carts', 'woocommerce-ac' ) );
			if ( $table ) {
				$print_data = "<table id='wcap_print_data' style='border:1px solid black;border-collapse:collapse;'>" . $print_data_columns . $print_data_row_data . '</table>';
				return $print_data; // phpcs:ignore
			} else {
				$print_data = '<html><head><title>' . $print_data_title . "</title><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body><table style='border:1px solid black;border-collapse:collapse;'>" . $print_data_columns . $print_data_row_data . '</table></body></html>';
				echo $print_data; // phpcs:ignore
				exit;
			}
		}

		/**
		 * This function will write the Abandoned Cart Column Data to CSV File.
		 *
		 * @param string $file Path of CSV File.
		 *
		 * @since 8.13.0
		 */
		public static function wcap_print_csv_cols( $file ) {

			$cols      = self::wcap_get_csv_cols();
			$col_data  = implode( ',', $cols );
			$col_data .= "\r\n";

			self::wcap_stash_step_data( $file, $col_data );

			return $col_data;
		}

		/**
		 * Function will return the Columns of Abandoned Cart Data.
		 *
		 * @since 8.13.0
		 */
		public static function wcap_get_csv_cols() {

			// Tracking coupons.
			$display_tracked_coupons = get_option( 'ac_track_coupons' );

			// Column Names.
			$cols = array(
				'id'          => __( 'ID', 'woocommerce-ac' ),
				'email'       => __( 'Email Address', 'woocommerce-ac' ),
				'customer'    => __( 'Customer', 'woocommerce-ac' ),
				'products'    => __( 'Products', 'woocommerce-ac' ),
				'order_total' => __( 'Order Total', 'woocommerce-ac' ),
				'quantity'    => __( 'Quantity', 'woocommerce-ac' ),
				'date'        => __( 'Abandoned Date', 'woocommerce-ac' ),
			);
			if ( $display_tracked_coupons ) {
				$cols['coupon_code_used']   = __( 'Coupon Used', 'woocommerce-ac' );
				$cols['coupon_code_status'] = __( 'Coupon Status', 'woocommerce-ac' );
			}
			$cols['email_captured_by'] = __( 'Email Captured By', 'woocommerce-ac' );
			$cols['status']            = __( 'Status of Cart', 'woocommerce-ac' );
			$cols['order_id']          = __( 'Order ID (Recovered)', 'woocommerce-ac' );
			$cols['ip']                = __( 'IP', 'woocommerce-ac' );

			return apply_filters( 'wcap_abandoned_carts_csv_columns', $cols );
		}

		/**
		 * Function to write data to CSV file.
		 *
		 * @param string $file Path of the CSV file.
		 * @param string $data Data to be added to CSV file.
		 * @since 8.13.0
		 */
		public static function wcap_stash_step_data( $file, $data ) {
			$file_content  = self::wcap_get_file( $file );
			$file_content .= $data;
			@file_put_contents( $file, $file_content ); // phpcs:ignore
		}

		/**
		 * Function to create CSV file OR get its content.
		 *
		 * @param string $file Path of the CSV file.
		 * @since 8.13.0
		 */
		public static function wcap_get_file( $file ) {

			$f = '';
			if ( @file_exists( $file ) ) { // phpcs:ignore
				if ( ! is_writeable( $file ) ) {
					$is_writable = false;
				}

				$f = @file_get_contents( $file ); // phpcs:ignore

			} else {
				@file_put_contents( $file, '' ); // phpcs:ignore
				@chmod( $file, 0664 ); // phpcs:ignore
			}

			return $f;
		}

		/**
		 * Function to download the CSV for Abandoned Carts.
		 *
		 * @since 8.13.0
		 */
		public static function wcap_download_csv() {

			if ( isset( $_GET['wcap_action'] ) && 'wcap_download_csv' === $_GET['wcap_action'] ) {

				if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ), 'wcap-batch-export-csv' ) ) {
					wp_die( esc_html__( 'Nonce verification failed', 'woocommerce-ac' ), esc_html__( 'Error', 'woocommerce-ac' ), array( 'response' => 403 ) );
				}

				$upload_dir = wp_upload_dir();
				$filename   = 'wcap-csv.csv';
				$file       = trailingslashit( $upload_dir['basedir'] ) . $filename;

				header( 'Content-Type: text/csv; charset=utf-8' );
				header( 'Content-Disposition: attachment; filename=' . apply_filters( 'wcap_csv_file_name', 'Abandon-Cart-Data-' . date( 'Y-m-d', current_time( 'timestamp' ) ) . '.csv' ) ); // phpcs:ignore
				header( 'Expires: 0' );
				echo "\xEF\xBB\xBF";
				readfile( $file ); // phpcS:ignore
				unlink( $file );
				die();
			}
		}
	}
}
