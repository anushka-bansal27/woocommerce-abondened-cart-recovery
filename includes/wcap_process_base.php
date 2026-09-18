<?php
/**
 * Abandoned Cart Pro for WooCommerce
 *
 * This file will run background processes.
 *
 * @author   Tyche Softwares
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Processes
 * @category Processes
 * @since    8.6.0
 */

/**
 * Process Class.
 */
class Wcap_Process_Base {

	/**
	 * Construct.
	 */
	public function __construct() {

		$wcap_auto_cron = get_option( 'wcap_use_auto_cron' );
		if ( isset( $wcap_auto_cron ) && false !== $wcap_auto_cron && '' !== $wcap_auto_cron ) {
			// Hook into that action that'll fire based on the cron frequency.
			add_action( 'woocommerce_ac_send_email_action', array( &$this, 'wcap_process_handler' ), 11 );
		}

		$wcap_email_reports_frequency = get_option( 'wcap_email_reports_frequency' );
		if ( '' !== $wcap_email_reports_frequency ) {
			// Hook into that action that'll fire based on the cron frequency.
			add_action( 'wcap_email_reports_frequency_action', array( &$this, 'wcap_email_reports_frequency_action_handler' ), 11 );
		}

		// Initate a scheduled action for abandonment notification.
		add_action( 'wcap_webhook_initiated', array( &$this, 'wcap_schedule_email_notification' ), 10, 1 );
	}

	/**
	 * Execute functions to send reminders.
	 */
	public function wcap_process_handler() {

		// Check if reminders are enabled.
		$reminders_list = wcap_get_enabled_reminders();

		if ( is_array( $reminders_list ) && count( $reminders_list ) > 0 ) {
			foreach ( $reminders_list as $reminder_type ) {
				switch ( $reminder_type ) {
					case 'emails':
						$active_count = Wcap_Connectors_Common::wcap_get_active_connectors_count();
						$send_emails  = $active_count > 0 ? false : true;
						$send_emails  = apply_filters( 'wcap_send_reminder_emails', $send_emails );
						if ( $send_emails ) {
							Wcap_Send_Email_Using_Cron::wcap_abandoned_cart_send_email_notification();
						}
						break;
					case 'sms':
						Wcap_Send_Email_Using_Cron::wcap_send_sms_notifications();
						break;
					case 'whatsapp':
						Wcap_Send_Email_Using_Cron::wcap_send_whatsapp_reminders();
						break;
					case 'fb':
						WCAP_FB_Recovery::wcap_fb_cron();
						break;
				}
			}
		}

	}

	/**
	 * Execute functions to send reminders.
	 */
	public function wcap_email_reports_frequency_action_handler() {
		if ( ! class_exists( 'Wcap_Dashboard_Report' ) ) {
			include_once 'classes/class_wcap_dashboard_report.php';
		}

		$from_email = get_option( 'admin_email' );
		$blogname   = get_option( 'blogname' );
		$blogname   = str_replace( '&#039;', "'", $blogname );

		$wcap_email_reports_frequency = get_option( 'wcap_email_reports_frequency', 'weekly' );
		if ( 'weekly' === $wcap_email_reports_frequency ) {
			$start_date   = date( 'Y-m-d', strtotime( '-7 days' ) ); // phpcs:ignore
			$end_date     = date( 'Y-m-d', strtotime( 'Today' ) ); // phpcs:ignore
			$date_range   = 'other';
			$subject_line = 'Weekly';
			$summary_text = 'week';
		} elseif ( 'monthly' === $wcap_email_reports_frequency ) {
			$start_date   = date( 'Y-m-d', strtotime( 'first day of previous month' ) ); // phpcs:ignore
			$end_date     = date( 'Y-m-d', strtotime( 'last day of previous month' ) ); // phpcs:ignore
			$date_range   = 'other';
			$summary_text = 'month';
			$subject_line = 'Monthly';
		}
		$orders = new Wcap_Dashboard_Report();
		$stats  = $orders->get_adv_stats( $date_range, $start_date, $end_date );

		$wcap_recovered_amount = $stats['recovered_amount'];
		/* translators: %1$s is replaced recovered amount, %2$s: report frequency */
		$wcap_recovered_text = sprintf( __( 'You recovered', 'woocommerce-ac' ) . ' %1$s ' . __( 'in revenue this %2$s!', 'woocommerce-ac' ), wc_price( $wcap_recovered_amount ), $summary_text );
		$wcap_recovered_text = apply_filters( 'wcap_email_report_heading_text', $wcap_recovered_text, wc_price( $wcap_recovered_amount ) );

		$wcap_email_abandoned_cart_count = $stats['abandoned_count'];
		$wcap_email_abandoned_cart_text  = __( 'carts abandoned', 'woocommerce-ac' );
		$wcap_email_abandoned_cart_text  = apply_filters( 'wcap_email_report_abandoned_cart_text', $wcap_email_abandoned_cart_text );
		$wcap_email_recovered_cart_count = $stats['recovered_count'];
		$wcap_email_recovered_cart_text  = __( 'carts recovered', 'woocommerce-ac' );
		$wcap_email_recovered_cart_text  = apply_filters( 'wcap_email_report_recovered_cart_text', $wcap_email_recovered_cart_text );

		$wcap_sales_heading_text      = __( 'Sales figures', 'woocommerce-ac' );
		$wcap_sales_heading_text      = apply_filters( 'wcap_email_report_sales_heading_text', $wcap_sales_heading_text );
		$wcap_text_of_abandoned_cart  = __( 'worth carts abandoned', 'woocommerce-ac' );
		$wcap_text_of_abandoned_cart  = apply_filters( 'wcap_email_report_worth_abandoned_cart_text', $wcap_text_of_abandoned_cart );
		$wcap_worth_of_abandoned_cart = wc_price( $stats['abandoned_amount'] );
		$wcap_text_of_recovered_cart  = __( 'worth carts recovered', 'woocommerce-ac' );
		$wcap_text_of_recovered_cart  = apply_filters( 'wcap_email_report_worth_recovered_cart_text', $wcap_text_of_recovered_cart );
		$wcap_worth_of_recovered_cart = wc_price( $stats['recovered_amount'] );

		$wcap_campaign_title       = __( 'Campaign Engagement', 'woocommerce-ac' );
		$wcap_campaign_title       = apply_filters( 'wcap_email_report_campaign_title', $wcap_campaign_title );
		$wcap_remider_text         = __( 'Reminders Sent', 'woocommerce-ac' );
		$wcap_remider_text         = apply_filters( 'wcap_email_report_remider_sent_text', $wcap_remider_text );
		$wcap_email_sent_count     = $orders->wcap_get_email_report( 'total_sent', $date_range, $start_date, $end_date );
		$wcap_open_text            = __( 'Open', 'woocommerce-ac' );
		$wcap_open_text            = apply_filters( 'wcap_email_report_open_text', $wcap_open_text );
		$wcap_email_opened_count   = $orders->wcap_get_email_report( 'total_opened', $date_range, $start_date, $end_date );
		$wcap_clicks_text          = __( 'Clicks', 'woocommerce-ac' );
		$wcap_clicks_text          = apply_filters( 'wcap_email_report_clicks_text', $wcap_clicks_text );
		$wcap_email_clicked_count  = $orders->wcap_get_email_report( 'total_clicked', $date_range, $start_date, $end_date );
		$wcap_conversions_text     = __( 'Conversions', 'woocommerce-ac' );
		$wcap_conversions_text     = apply_filters( 'wcap_email_report_conversions_text', $wcap_conversions_text );
		$wcap_abandoned_cart_count = $stats['abandoned_count'];
		$wcap_recovered_cart_count = $stats['recovered_count'];

		$wcap_dashboard_text = __( 'View Dashboard', 'woocommerce-ac' );
		$wcap_dashboard_text = apply_filters( 'wcap_email_report_dashboard_button_text', $wcap_dashboard_text );

		$wcap_email_reports_emails_list = get_option( 'wcap_email_reports_emails_list' );
		$wcap_email_reports_emails_list = implode( ',', explode( ' ', $wcap_email_reports_emails_list ) );

		if ( '' === $wcap_email_reports_emails_list ) {
			$wcap_email_reports_emails_list = get_option( 'admin_email' );
		}

		$wcap_email_summary_title = sprintf( '%s ' . __( 'Account Summary for', 'woocommerce-ac' ) . ' %s', $subject_line, $blogname );
		$wcap_email_summary_title = apply_filters( 'wcap_email_report_summary_title', $wcap_email_summary_title, $subject_line, $blogname );
		/* translators: %1$s is replaced with type of frequency for reports, %2$s: report frequency, %3$s: report frequency */
		$wcap_email_summary_text   = sprintf( __( 'Below is the', 'woocommerce-ac' ) . ' %1$s ' . __( 'summary of the revenue recovered during this %2$s using Abandoned Cart Pro for WooCommerce.', 'woocommerce-ac' ), strtolower( $subject_line ), $summary_text );
		$wcap_email_summary_text   = apply_filters( 'wcap_email_report_summary_text', $wcap_email_summary_text, strtolower( $subject_line ) );
		$wcap_date_formatted_range = date( 'd F', strtotime( $start_date ) ) . ' - ' . date( 'd F Y', strtotime( $end_date ) ); // phpcs:ignore

		// translators: 4 digit current year.
		$wcap_footer_text = sprintf( __( '&copy; 2012-%1$s Tyche Softwares, All Rights Reserved.', 'woocommerce-ac' ), date( 'Y' ) ); // phpcs:ignore
		$wcap_footer_text = apply_filters( 'wcap_email_footer_text', $wcap_footer_text );

		$wcap_get_last_report_data = get_option( 'wcap_last_email_report_data' );

		$wcap_last_email_report_data_value = array(
			$wcap_email_reports_frequency => array(
				'abandoned_count' => $wcap_email_abandoned_cart_count,
				'recovered_count' => $wcap_email_recovered_cart_count,
			),
		);
		update_option( 'wcap_last_email_report_data', $wcap_last_email_report_data_value );

		$email_output = wc_get_template_html(
			'template-email-reports.php',
			array(
				'base_url'                => esc_url( WCAP_PLUGIN_URL ) . '/assets/images',
				'dashboard_url'           => admin_url( 'admin.php?page=woocommerce_ac_page' ),
				'recovered_amount_text'   => $wcap_recovered_text,
				'email_summary_title'     => $wcap_email_summary_title,
				'email_summary_text'      => $wcap_email_summary_text,
				'date_formatted_range'    => $wcap_date_formatted_range,
				'abandoned_cart_count'    => $wcap_email_abandoned_cart_count,
				'abandoned_cart_text'     => $wcap_email_abandoned_cart_text,
				'recovered_cart_count'    => $wcap_email_recovered_cart_count,
				'recovered_cart_text'     => $wcap_email_recovered_cart_text,
				'sales_heading_text'      => $wcap_sales_heading_text,
				'text_of_abandoned_cart'  => $wcap_text_of_abandoned_cart,
				'worth_of_abandoned_cart' => $wcap_worth_of_abandoned_cart,
				'text_of_recovered_cart'  => $wcap_text_of_recovered_cart,
				'worth_of_recovered_cart' => $wcap_worth_of_recovered_cart,
				'campaign_title'          => $wcap_campaign_title,
				'remider_text'            => $wcap_remider_text,
				'email_sent_count'        => $wcap_email_sent_count,
				'open_text'               => $wcap_open_text,
				'email_opened_count'      => $wcap_email_opened_count,
				'clicks_text'             => $wcap_clicks_text,
				'email_clicked_count'     => $wcap_email_clicked_count,
				'conversions_text'        => $wcap_conversions_text,
				'conversions_count'       => $wcap_recovered_cart_count,
				'dashboard_text'          => $wcap_dashboard_text,
				'footer_text'             => $wcap_footer_text,
			),
			'woocommerce-abandon-cart-pro/',
			WCAP_PLUGIN_PATH . '/includes/template/emails/'
		);

		$subject = sprintf( '%s ' . __( 'summary for Abandoned Carts on', 'woocommerce-ac' ) . ' %s', $subject_line, $blogname );
		$subject = apply_filters( 'wcap_email_report_subject', $subject, $subject_line, $blogname );

		$headers  = "From: " . $blogname . " <" . $from_email . ">" . "\r\n"; // phpcs:ignore
		$headers .= "Content-Type: text/html" . "\r\n"; // phpcs:ignore

		Wcap_Common::wcap_add_wp_mail_header();
		wp_mail( $wcap_email_reports_emails_list, $subject, stripslashes( $email_output ), $headers );
		Wcap_Common::wcap_remove_wc_mail_header();
	}

	/**
	 * Initiate scheduled action for abandonment notification.
	 *
	 * @param int $cart_id - Cart ID.
	 * @since 9.3.0
	 */
	public static function wcap_schedule_email_notification( $cart_id ) {

		$admin_notification_status = get_option( 'wcap_email_admin_on_abandonment', '' );
		// Check if feature is enabled.
		if ( 'on' === $admin_notification_status && $cart_id > 0 ) {

			$cart_history = wcap_get_data_cart_history( $cart_id );

			if ( $cart_history ) {
				$user_id   = $cart_history->user_id;
				$user_type = $cart_history->user_type;

				$billing_first_name = '';
				$billing_last_name  = '';
				$email_id           = '';
				$phone              = '';

				if ( $user_id >= 63000000 && 'GUEST' === $user_type ) {
					$guest_data = wcap_get_data_guest_history( $user_id );

					if ( $guest_data ) {
						$billing_first_name = $guest_data->billing_first_name;
						$billing_last_name  = $guest_data->billing_last_name;
						$email_id           = $guest_data->email_id;
						$phone              = $guest_data->phone;
					}
				} elseif ( 'REGISTERED' === $user_type ) {
					$billing_first_name = get_user_meta( $user_id, 'billing_first_name', true );
					$billing_last_name  = get_user_meta( $user_id, 'billing_last_name', true );
					$email_id           = get_user_meta( $user_id, 'billing_email', true );
					$phone              = get_user_meta( $user_id, 'billing_phone', true );
					if ( '' === $email_id || null === $email_id ) {
						$customer   = new WC_Customer( $user_id );
						$user_email = $customer->get_billing_email();
						$email_id   = '' === $user_email ? $customer->get_email() : $user_email;
					}
				}

				$email_id = apply_filters( 'wcap_cart_abandoned_alter_email_id', $email_id );

				// At the minimum a phone number or email address should've been captured.
				if ( ( '' !== $email_id && null !== $email_id ) || ( '' !== $phone && null !== $phone ) ) {

					$source_list = get_option( 'wcap_email_admin_cart_source', array() );

					$send_for_source = array();
					foreach ( $source_list as $s_key => $s_val ) {
						if ( 'on' === $s_val ) {
							array_push( $send_for_source, $s_key );
						}
					}
					$cart_details = json_decode( $cart_history->abandoned_cart_info );
					$cart_source  = isset( $cart_details->captured_by ) ? $cart_details->captured_by : '';

					if ( '' === $cart_source && isset( $user_id ) && $user_id > 0 && $user_id < 63000000 ) {
						$cart_source = 'profile';
					}
					// Compare the cart source rules.
					if ( in_array( 'all', $send_for_source, true ) || ( in_array( trim( $cart_source ), $send_for_source, true ) ) ) {
						// Initiate the action.
						$cut_off = 0;
						if ( 'REGISTERED' === $user_type ) {
							$cut_off = is_numeric( get_option( 'ac_cart_abandoned_time', 10 ) ) ? get_option( 'ac_cart_abandoned_time', 10 ) : 10;
						} elseif ( 'GUEST' === $user_type ) {
							$cut_off = is_numeric( get_option( 'ac_cart_abandoned_time_guest', 10 ) ) ? get_option( 'ac_cart_abandoned_time_guest', 10 ) : 10;
						}
						$notification_buffer = apply_filters( 'wcap_admin_notification_buffer', 0 );
						$cut_off            += (int) $notification_buffer;

						$cut_off   = $cut_off * 60; // convert to seconds.
						$cart_id   = (int) $cart_id;
						$scheduled = as_next_scheduled_action( 'wcap_send_admin_notification', array( 'id' => $cart_id ) );

						if ( $cut_off > 0 && ! $scheduled ) {
							// Run the hook.
							as_schedule_single_action( time() + $cut_off, 'wcap_send_admin_notification', array( 'id' => $cart_id ) );
						}
					}
				}
			}
		}
	}
}
new Wcap_Process_Base();
