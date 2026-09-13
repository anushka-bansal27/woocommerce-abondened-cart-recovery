<?php
/**
 * It will have all the common function needed all over the plugin.
 *
 * @author   Tyche Softwares
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Common-Functions
 * @since 5.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use Automattic\WooCommerce\Vendor\Pelago\Emogrifier\CssInliner;

if ( ! class_exists( 'Wcap_Common' ) ) {
	/**
	 * It will have all the common function needed all over the plugin.
	 */
	class Wcap_Common {

		/**
		 * Show action links on the plugin screen.
		 *
		 * @param mixed $links Plugin Action links.
		 * @return array $action_links
		 * @since 5.0
		 */
		public static function wcap_plugin_action_links( $links ) {
			$action_links = array(
				'settings' => '<a href="' . admin_url( 'admin.php?page=woocommerce_ac_page&action=emailsettings' ) . '" title="' . esc_attr( __( 'View WooCommerce abandoned Cart Settings', 'woocommerce-ac' ) ) . '">' . __( 'Settings', 'woocommerce-ac' ) . '</a>',
			);
			return array_merge( $action_links, $links );
		}
		/**
		 * Show row meta on the plugin screen.
		 *
		 * @param mixed  $links Plugin Action links
		 * @param string $file Plugin path
		 * @return array $links Plugin Action links
		 * @since 5.0
		 */
		public static function wcap_plugin_row_meta( $links, $file ) {
			$plugin_base_name  = dirname( dirname( plugin_basename( __FILE__ ) ) );
			$plugin_base_name .= '/woocommerce-ac.php';

			if ( $file == $plugin_base_name ) {
				$row_meta = array(
					'docs'    => '<a href="' . esc_url( apply_filters( 'woocommerce_abandoned_cart_docs_url', 'https://woocommerce.com/document/woocommerce-abandon-cart-pro/' ) ) . '" title="' . esc_attr( __( 'View WooCommerce abandoned Cart Documentation', 'woocommerce-ac' ) ) . '">' . __( 'Docs', 'woocommerce-ac' ) . '</a>',
					'support' => '<a href="' . esc_url( apply_filters( 'woocommerce_abandoned_cart_support_url', 'https://woocommerce.com/my-account/contact-support/' ) ) . '" taregt="_blank" title="' . esc_attr( __( 'Submit Ticket', 'woocommerce-ac' ) ) . '">' . __( 'Submit Ticket', 'woocommerce-ac' ) . '</a>',
				);
				return array_merge( $links, $row_meta );
			}
			return (array) $links;
		}

		/**
		 * Check if user have the permission to access the WooCommerce pages.
		 *
		 * @since 5.0
		 */
		public static function wcap_check_user_can_manage_woocommerce() {
			// Check the user capabilities
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( esc_html( __( 'You do not have sufficient permissions to access this page.', 'woocommerce-ac' ) ) );
			}
		}

		/**
		 * It will return the current action.
		 *
		 * @return string $wcap_action Action name
		 * @since 5.0
		 */
		public static function wcap_get_action() {
			$wcap_action = '';
			if ( isset( $_GET['action'] ) ) {// phpcs:ignore
				$wcap_action = sanitize_text_field( wp_unslash( $_GET['action'] ) );// phpcs:ignore
			}

			/**
			 * @since : 4.2
			 * This is done as we are sending the manaul email with bulk action.
			 * Bulk action do not allow to give the multiple parameter, so in single parameter we are giving the long
			 * string with all needed data.
			 * So for that we need to break the string.
			 */
			if ( isset( $_GET['action2'] ) ) {// phpcs:ignore
				if ( '-1' == $_GET['action'] ) {// phpcs:ignore
					$wcap_action = sanitize_text_field( wp_unslash( $_GET['action2'] ) );// phpcs:ignore
					if ( strpos( $wcap_action, 'wcap_manual_email' ) !== false ) {
						$explode_action = explode( '&', sanitize_text_field( wp_unslash( $_GET['action2'] ) ) );// phpcs:ignore
						$wcap_action    = $explode_action [0];
						$_GET['mode']   = 'wcap_manual_email';
					}
				} else {
					$wcap_action = sanitize_text_field( wp_unslash( $_GET['action'] ) );// phpcs:ignore
					if ( strpos( $wcap_action, 'wcap_manual_email' ) !== false ) {
						$explode_action = explode( '&', sanitize_text_field( wp_unslash( $_GET['action'] ) ) );// phpcs:ignore
						$wcap_action    = $explode_action [0];
						$_GET['mode']   = 'wcap_manual_email';
					}
				}
			}

			if ( isset( $_GET['action2'] ) ) {// phpcs:ignore
				if ( '-1' == $_GET['action'] ) {// phpcs:ignore
					$wcap_action = sanitize_text_field( wp_unslash( $_GET['action2'] ) );// phpcs:ignore
					if ( strpos( $wcap_action, 'wcap_add_agile' ) !== false ) {
						$explode_action = explode( '&', sanitize_text_field( wp_unslash( $_GET['action2'] ) ) );// phpcs:ignore
						$wcap_action    = $explode_action [0];
						$_GET['mode']   = 'wcap_add_agile';
					}
				} else {
					$wcap_action = sanitize_text_field( wp_unslash( $_GET['action'] ) );// phpcs:ignore
					if ( strpos( $wcap_action, 'wcap_add_agile' ) !== false ) {
						$explode_action = explode( '&', sanitize_text_field( wp_unslash( $_GET['action'] ) ) );// phpcs:ignore
						$wcap_action    = $explode_action [0];
						$_GET['mode']   = 'wcap_add_agile';
					}
				}
			}
			if ( '-1' == $wcap_action && isset( $_GET['wcap_action'] ) ) {// phpcs:ignore

				$wcap_action = sanitize_text_field( wp_unslash( $_GET['wcap_action'] ) );// phpcs:ignore
			}
			return $wcap_action;
		}

		/**
		 * It will return the mode of the plugin.
		 *
		 * @return $wcap_mode Mode name
		 * @since 5.0
		 */
		public static function wcap_get_mode() {

			$wcap_mode = '';
			if ( isset( $_GET['mode'] ) ) {// phpcs:ignore
				$wcap_mode = sanitize_text_field( wp_unslash( $_GET['mode'] ) );// phpcs:ignore
			}
			return $wcap_mode;
		}

		/**
		 * Returns the section set for the admin page
		 *
		 * @since 7.9
		 */
		public static function wcap_get_section() {
			return ( isset( $_GET['section'] ) ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : 'emailtemplates';// phpcs:ignore
		}
		/**
		 * It will retunrn the user selectd action from the below bulk action editor.
		 *
		 * @return string $wcap_action_two Action name
		 * @since 5.0
		 */
		public static function wcap_get_action_two() {
			$wcap_action_two = '';

			if ( isset( $_GET['action2'] ) ) {//phpcs:ignore
				$wcap_action_two = sanitize_text_field( wp_unslash( $_GET['action2'] ) );// phpcs:disable
			}
			return $wcap_action_two;
		}

		/**
		 * It will return the abandoned cart ids from the url.
		 *
		 * @return string $wcap_ac_ids Abandoned cart ids
		 * @since 5.0
		 */
		public static function wcap_get_abandoned_cart_ids_from_get() {

			$wcap_ac_ids = isset( $_GET['abandoned_order_id'] ) ? wp_unslash( $_GET['abandoned_order_id'] ) : false;
			return $wcap_ac_ids;
		}

		/**
		 * It will return the template id from the url.
		 *
		 * @return string $wcap_template_ids Template ids
		 * @since 5.0
		 */
		public static function wcap_get_template_ids_from_get() {

			$wcap_template_ids = isset( $_GET['template_id'] ) ? sanitize_text_field( wp_unslash( $_GET['template_id'] ) ) : false;
			return $wcap_template_ids;
		}

		/**
		 * It will return the email sent id from the url.
		 *
		 * @return string $wcap_email_sent_ids Email sent ids
		 * @since 5.0
		 */
		public static function wcap_get_email_sent_ids_from_get() {

			$wcap_email_sent_ids = isset( $_GET['wcap_email_sent_id'] ) ? sanitize_text_field( wp_unslash( $_GET['wcap_email_sent_id'] ) ) : false;
			return $wcap_email_sent_ids;
		}

		/**
		 * It will return the selected action by the admin based on the url.
		 *
		 * @return string $wcap_notice_action Notice action
		 * @since 5.0
		 */
		public static function wcap_get_notice_action() {

			$wcap_notice_action = '';

			if ( isset( $_GET ['wcap_deleted'] ) && 'YES' == $_GET['wcap_deleted'] ) {
				$wcap_notice_action = 'wcap_deleted';
			}

			if ( isset( $_GET ['wcap_rec_deleted'] ) && 'YES' == $_GET['wcap_rec_deleted'] ) {
				$wcap_notice_action = 'wcap_rec_deleted';
			}

			if ( isset( $_GET ['wcap_abandoned_trash'] ) && 'YES' == $_GET['wcap_abandoned_trash'] ) {
				$wcap_notice_action = 'wcap_abandoned_trash';
			}

			if ( isset( $_GET ['wcap_abandoned_restore'] ) && 'YES' == $_GET['wcap_abandoned_restore'] ) {
				$wcap_notice_action = 'wcap_abandoned_restore';
			}

			if ( isset( $_GET ['wcap_rec_trash'] ) && 'YES' == $_GET['wcap_rec_trash'] ) {
				$wcap_notice_action = 'wcap_rec_trash';
			}

			if ( isset( $_GET ['wcap_rec_restore'] ) && 'YES' == $_GET['wcap_rec_restore'] ) {
				$wcap_notice_action = 'wcap_rec_restore';
			}

			if ( isset( $_GET ['wcap_sent_email_restore'] ) && 'YES' == $_GET['wcap_sent_email_restore'] ) {
				$wcap_notice_action = 'wcap_sent_email_restore';
			}

			if ( isset( $_GET ['wcap_template_deleted'] ) && 'YES' == $_GET['wcap_template_deleted'] ) {
				$wcap_notice_action = 'wcap_template_deleted';
			}

			if ( isset( $_GET ['wcap_manual_email_sent'] ) && 'YES' == $_GET['wcap_manual_email_sent'] ) {
				$wcap_notice_action = 'wcap_manual_email_sent';
			}
			// phpcs:enable

			return $wcap_notice_action;
		}

		/**
		 * It will return the WC order id.
		 *
		 * @param object|array $wcap_order WooCommerce order
		 * @globals mixed $woocommerce
		 * @return int|string $wcap_order_id Order Id
		 * @since 5.0
		 * @todo Change the function name
		 */
		public static function wcap_get_ordrer_id( $wcap_order ) {
			global $woocommerce;
			$wcap_order_id = '';
			if ( version_compare( $woocommerce->version, '3.0.0', '>=' ) ) {
				$wcap_order_id = $wcap_order->get_id();
			} else {
				$wcap_order_id = $wcap_order->id;
			}

			return $wcap_order_id;
		}

		/**
		 * It will check if the abandoned cart email is sent to the cart id or not.
		 *
		 * @param int|string $abandoned_order_id Abandoned cart id
		 * @globals mixed $wpdb
		 * @return true Email sent
		 * @return false Email not sent
		 * @since 5.0
		 */
		public static function wcap_check_email_sent_for_order( $abandoned_order_id ) {
			global $wpdb;
			$query   = 'SELECT id FROM `' . WCAP_EMAIL_SENT_HISTORY_TABLE . '` WHERE cart_id = %d';
			$results = $wpdb->get_results( $wpdb->prepare( $query, $abandoned_order_id ) );//phpcs:disable
			if ( count( $results ) > 0 ) {
				return true;
			}
			return false;
		}

		/**
		 * This function is used to encode the string.
		 *
		 * @param string $validate String need to encrypt.
		 * @param string $crypt_key - Key to be used for encryption.
		 * @return string $validate_encoded Encrypted string
		 * @since 5.0
		 */
		public static function encrypt_validate( $validate, $crypt_key ) {
			// Encrypt.
			if ( '' !== $crypt_key ) {
				$validate_encoded = Wcap_Aes_Ctr::encrypt( $validate, $crypt_key, 256 );
				return( $validate_encoded );
			}
			return false;
		}

		/**
		 * It will return the user selected language.
		 *
		 * @return string $wcap_current_user_lang User selected language
		 * @since 5.0
		 */

		public static function wcap_get_language() {

			$wcap_current_user_lang = 'en';
			if ( function_exists( 'icl_register_string' ) ) {
				$wcap_current_user_lang = defined( 'ICL_LANGUAGE_CODE' ) ? ICL_LANGUAGE_CODE : '';
			}

			return $wcap_current_user_lang;
		}

		/**
		 * When cron job time changed this function will be called.
		 * It is used to reset the cron time again.
		 *
		 * @since 5.0
		 */
		public static function wcap_cron_time_duration() {
			wp_clear_scheduled_hook( 'woocommerce_ac_send_email_action' );
		}

		/**
		 * We have changed the WooCommerce session expiration date.
		 *
		 * @param int $seconds
		 * @return int $days_7 7 days in seconds
		 * @since 5.0
		 */
		public static function wcap_set_session_expiring( $seconds ) {
			$hours_23 = 60 * 60 * 23;
			$days_7   = $hours_23 * 7;
			return $days_7;
		}

		/**
		 * We have changed the WooCommerce session expiration date.
		 *
		 * @param int $seconds
		 * @return int $days_7 7 days in seconds
		 * @since 5.0
		 */
		public static function wcap_set_session_expired( $seconds ) {
			$hours_24 = 60 * 60 * 24;
			$days_7   = $hours_24 * 7;
			return $days_7;
		}

		/**
		 * It will remove the cart updated hook from our plugin.
		 *
		 * @since 5.0
		 */
		public static function wcap_remove_action_hook() {
			if ( class_exists( 'Wcap_Cart_Updated' ) ) {
				remove_action( 'woocommerce_cart_updated', array( 'Wcap_Cart_Updated', 'wcap_store_cart_timestamp' ) );
			}
		}

		/**
		 * To output the print and preview email template we need it.
		 *
		 * @since 5.0
		 */
		public static function wcap_output_buffer() {
			ob_start();
		}

		/**
		 * We will return the customers IP address. We are using the WooCommerce geoloctaion.
		 *
		 * @return string User IP address
		 * @since 5.0
		 */
		public static function wcap_get_client_ip() {
			$ipaddress = WC_Geolocation::get_ip_address();
			return $ipaddress;
		}

		/**
		 * We will return the user role on the user id.
		 *
		 * @param int|string $uid User Id
		 * @globals mixed $wpdb
		 * @return string $roles User role
		 * @since 5.0
		 */
		public static function wcap_get_user_role( $uid ) {
			global $wpdb;
			$role = $wpdb->get_var( "SELECT meta_value FROM {$wpdb->usermeta} WHERE meta_key = 'wp_capabilities' AND user_id = {$uid}" );

			if ( ! $role ) {
				return '';
			}
			$rarr = unserialize( $role );

			$roles = is_array( $rarr ) ? array_keys( $rarr ) : array( 'non-user' );

			/**
			 * When store have the wpml it have so many user roles to fix the user role for admin we have applied this fix.
			 */
			if ( in_array( 'administrator', $roles ) ) {

				$roles[0] = 'administrator';
			}

			return ucfirst( $roles[0] );
		}

		/**
		 * We are checking if the customer IP addres is blocked by the admin.
		 *
		 * @param string $wcap_user_ip_address User IP address
		 * @return true|false $wcap_restricted_ip_data_exists IP address restricted | IP address not restricted
		 * @since 5.0
		 */
		public static function wcap_is_ip_restricted( $wcap_user_ip_address ) {

			$wcap_restricted_ip_data_exists = false;
			$wcap_restricted_ip_records     = get_option( 'wcap_restrict_ip_address' );
			if ( false != $wcap_restricted_ip_records ) {
				$explode_on_new_line_data_ip_records = explode( PHP_EOL, $wcap_restricted_ip_records );

				$implode_ip_address = '';
				$explode_ip_address = array();

				if ( count( $explode_on_new_line_data_ip_records ) > 1 ) {
					$implode_ip_address = implode( ',', $explode_on_new_line_data_ip_records );
					$explode_ip_address = explode( ',', $implode_ip_address );
				} else {
					$explode_ip_address = explode( ',', $wcap_restricted_ip_records );
				}

				$trimmed_explode_ip_address = array_map(
					function( $v ) {
						return trim( str_replace( array( '*.', '*' ), '', $v ) );
					},
					$explode_ip_address
				);

				foreach ( $trimmed_explode_ip_address as $restricted_ip ) {
					if ( $restricted_ip === $wcap_user_ip_address || strpos( $wcap_user_ip_address, $restricted_ip ) !== false ) {
						$wcap_restricted_ip_data_exists = true;
					}
				}

				$block_ip_address = self::block_users( $trimmed_explode_ip_address, $wcap_user_ip_address );

				if ( $block_ip_address == 1 ) {
					$wcap_restricted_ip_data_exists = true;
				}
			}

			return $wcap_restricted_ip_data_exists;
		}

		/**
		 * We are checking if the customer Email addres is blocked by the admin.
		 *
		 * @param string $current_user_email_address User Email address
		 * @return true|false $wcap_restricted_email_data_exists Email address restricted | Email address not restricted
		 * @since 5.0
		 */
		public static function wcap_is_email_address_restricted( $current_user_email_address ) {

			$wcap_restricted_email_data_exists = false;

			$wcap_restricted_email_records = get_option( 'wcap_restrict_email_address' );
			if ( false != $wcap_restricted_email_records ) {
				$explode_on_new_line_data_email_records = explode( PHP_EOL, $wcap_restricted_email_records );

				$implode_email_address = '';
				$explode_email_address = array();

				if ( count( $explode_on_new_line_data_email_records ) > 1 ) {
					$implode_email_address = implode( ',', $explode_on_new_line_data_email_records );
					$explode_email_address = explode( ',', $implode_email_address );
				} else {
					$explode_email_address = explode( ',', $wcap_restricted_email_records );
				}

				$trimmed_explode_email_address = array_map( 'trim', $explode_email_address );
				$trimmed_explode_email_address = array_map( 'strtolower', $explode_email_address );

				$current_user_email_address = strtolower( $current_user_email_address );

				if ( in_array( $current_user_email_address, $trimmed_explode_email_address ) && '' != $current_user_email_address ) {
					$wcap_restricted_email_data_exists = true;
				}
			}
			return $wcap_restricted_email_data_exists;
		}

		/**
		 * We are checking if the customer Domain name is blocked by the admin.
		 *
		 * @param string $current_user_email_address User Email address
		 * @return true|false $wcap_restricted_domain_data_exists Domain restricted | Domain not restricted
		 * @since 5.0
		 */
		public static function wcap_is_domain_restricted( $current_user_email_address ) {

			$wcap_restricted_domain_data_exists = false;

			$wcap_restricted_domain_records = get_option( 'wcap_restrict_domain_address' );
			if ( false != $wcap_restricted_domain_records ) {
				$explode_on_new_line_data_domain_records = explode( ' ', $wcap_restricted_domain_records );

				$implode_domain_address = '';
				$explode_domain_address = array();

				if ( count( $explode_on_new_line_data_domain_records ) > 1 ) {
					$implode_domain_address = implode( ',', $explode_on_new_line_data_domain_records );
					$explode_domain_address = explode( ',', $implode_domain_address );
				} else {
					$explode_domain_address = explode( ',', $wcap_restricted_domain_records );
				}
				$get_domain                      = '';
				$explode_user_email_addresson_at = array();

				$explode_user_email_addresson_at = explode( '@', $current_user_email_address );

				if ( isset( $explode_user_email_addresson_at [1] ) && '' != $explode_user_email_addresson_at [1] ) {
					$get_domain = $explode_user_email_addresson_at [1];
				}

				$trimmed_explode_domain_address = array_map( 'trim', $explode_domain_address );
				$trimmed_explode_domain_address = array_map( 'strtolower', $explode_domain_address );
				$get_domain                     = strtolower( $get_domain );

				if ( in_array( $get_domain, $trimmed_explode_domain_address ) ) {
					$wcap_restricted_domain_data_exists = true;
				}
			}
			return $wcap_restricted_domain_data_exists;
		}

		/**
		 * We are checking if the customer country is blocked by the admin.
		 *
		 * @param string $country User Country
		 * @return true|false $wcap_restricted_country_exists Country restricted | Country not restricted
		 * @since 8.20.0
		 */
		public static function wcap_is_country_restricted( $country ) {

			$wcap_restricted_country_exists = false;
			$wcap_restrict_countries        = get_option( 'wcap_restrict_countries' );
			$wcap_restrict_countries        = explode( ',', $wcap_restrict_countries );
			if ( ! empty( $wcap_restrict_countries ) && is_array( $wcap_restrict_countries ) ) {
				if ( ! empty( $country ) && in_array( $country, $wcap_restrict_countries ) ) {
					$wcap_restricted_country_exists = true;
				}
			}
			return apply_filters( 'wcap_restricted_country_exists', $wcap_restricted_country_exists, $country );
		}

		/**
		 * It will break the bulk of the IP address and verify each email IP is blocked or not.
		 *
		 * @param array  $user_inputs All blocked IP
		 * @param string $customer_ip_address User IP address
		 * @return true|false $block IP blocked | IP not blocked
		 */
		public static function block_users( $user_inputs, $customer_ip_address ) {

			$userOctets      = explode( '.', $customer_ip_address ); // get the client's IP address and split it by the period character
			$userOctetsCount = count( $userOctets );  // Number of octets we found, should always be four

			$block = false; // boolean that says whether or not we should block this user

			foreach ( $user_inputs as $ipAddress ) { // iterate through the list of IP addresses
				$octets = explode( '.', $ipAddress );
				if ( count( $octets ) != $userOctetsCount ) {
					continue;
				}

				for ( $i = 0; $i < $userOctetsCount; $i++ ) {
					if ( $userOctets[ $i ] == $octets[ $i ] || $octets[ $i ] == '*' ) {
						continue;
					} else {
						break;
					}
				}

				if ( $i == $userOctetsCount ) { // if we looked at every single octet and there is a match, we should block the user
					$block = true;
					break;
				}
			}

			return $block;
		}

		/**
		 * It will give you the total abandoned carts.
		 *
		 * @param string $get_section_result Section name
		 * @globals mixed $wpdb
		 * @return int $return_abandoned_count Count of abandoned order
		 * @since 5.0
		 */
		public static function wcap_get_abandoned_order_count( $get_section_result ) {
			global $wpdb, $start_end_dates;
			$duration_range = '';
			if ( '' == $duration_range ) {
				if ( isset( $_GET['duration_select'] ) && '' != $_GET['duration_select'] ) {
					$duration_range = $_GET['duration_select'];
				}
			}
			if ( isset( $_SESSION ['duration'] ) && '' != $_SESSION ['duration'] ) {
				$duration_range = $_SESSION ['duration'];
			}

			if ( isset( $_POST['duration_select'] ) ) {
				$duration_range = $_POST['duration_select'];
			}

			if ( '' == $duration_range ) {
				$duration_range = 'last_seven';
			}
			$start_date_range = '';

			if ( isset( $_SESSION ['hidden_start'] ) && '' != $_SESSION ['hidden_start'] ) {
				$start_date_range = $_SESSION ['hidden_start'];
			}

			if ( isset( $_POST['hidden_start'] ) && '' != $_POST['hidden_start'] ) {
				$start_date_range = $_POST['hidden_start'];
			}

			if ( '' == $start_date_range ) {
				$start_date_range = $start_end_dates[ $duration_range ]['start_date'];
			}
			$end_date_range = '';

			if ( isset( $_SESSION ['hidden_end'] ) && '' != $_SESSION ['hidden_end'] ) {
				$end_date_range = $_SESSION ['hidden_end'];
			}

			if ( isset( $_POST['hidden_end'] ) && '' != $_POST['hidden_end'] ) {
				$end_date_range = $_POST['hidden_end'];
			}

			if ( '' == $end_date_range ) {
				$end_date_range = $start_end_dates[ $duration_range ]['end_date'];
			}

			$start_date = strtotime( $start_date_range . ' 00:01:01' );
			$end_date   = strtotime( $end_date_range . ' 23:59:59' );

			$return_abandoned_count = 0;

			$blank_cart_info       = '{"cart":[]}';
			$blank_cart_info_guest = '[]';

			$ac_cutoff_time = is_numeric( get_option( 'ac_cart_abandoned_time', 10 ) ) ? get_option( 'ac_cart_abandoned_time', 10 ) : 10;
			$cut_off_time   = $ac_cutoff_time * 60;
			$current_time   = current_time( 'timestamp' );
			$compare_time   = $current_time - $cut_off_time;

			$ac_cutoff_time_guest = is_numeric( get_option( 'ac_cart_abandoned_time_guest', 10 ) ) ? get_option( 'ac_cart_abandoned_time_guest', 10 ) : 10;
			$cut_off_time_guest   = $ac_cutoff_time_guest * 60;
			$current_time         = current_time( 'timestamp' );
			$compare_time_guest   = $current_time - $cut_off_time_guest;
			// nosemgrep:audit.php.wp.security.sqli.input-in-sinks
			switch ( $get_section_result ) {
				case 'wcap_all_abandoned_lifetime':
					$query_ac               = 'SELECT * FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE ( user_type = 'REGISTERED' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_time <= '$compare_time' AND recovered_cart = 0 AND wcap_trash = '' AND cart_ignored <> '1' ) OR ( user_type = 'GUEST' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_time <= '$compare_time_guest' AND recovered_cart = 0 AND wcap_trash = '' AND cart_ignored <> '1' ) ORDER BY recovered_cart desc ";
					$ac_results             = $wpdb->get_results( $query_ac );
					$return_abandoned_count = count( $ac_results );
					break;

				case 'wcap_all_abandoned':
					// 7.14.0 - @todo - remove this case
					$query_ac               = 'SELECT * FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE ( user_type = 'REGISTERED' AND abandoned_cart_time >=  $start_date AND abandoned_cart_time <= $end_date AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_time <= '$compare_time' AND recovered_cart = 0 AND wcap_trash = '' AND cart_ignored <> '1' ) OR ( user_type = 'GUEST' AND abandoned_cart_time >=  $start_date AND abandoned_cart_time <= $end_date AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_time <= '$compare_time_guest' AND recovered_cart = 0 AND wcap_trash = '' AND cart_ignored <> '1' ) ORDER BY recovered_cart desc ";
					$ac_results             = $wpdb->get_results( $query_ac );// nosemgrep:audit.php.wp.security.sqli.input-in-sinks
					$return_abandoned_count = count( $ac_results );
					break;

				case 'wcap_trash_abandoned':
					$query_ac = 'SELECT * FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE ( abandoned_cart_time >=  $start_date AND abandoned_cart_time <= $end_date AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_time <= '$compare_time'  AND wcap_trash = '1' AND ( ( cart_ignored <> '1' AND recovered_cart = 0) OR ( cart_ignored = '1' AND recovered_cart > 0 ) ) ) OR ( user_type = 'GUEST' AND abandoned_cart_time >=  $start_date AND abandoned_cart_time <= $end_date AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_time <= '$compare_time_guest' AND recovered_cart = 0 AND wcap_trash = '1' AND cart_ignored <> '1' ) ORDER BY recovered_cart desc ";
					// $query_ac        = "SELECT * FROM `".WCAP_ABANDONED_CART_HISTORY_TABLE."` WHERE ( user_type = 'REGISTERED' AND abandoned_cart_time >=  $start_date AND abandoned_cart_time <= $end_date AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_time <= '$compare_time' AND wcap_trash = '1' ) OR ( user_type = 'GUEST' AND abandoned_cart_time >=  $start_date AND abandoned_cart_time <= $end_date AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_time <= '$compare_time_guest' AND recovered_cart = 0 AND wcap_trash = '1' AND cart_ignored <> '1' ) ORDER BY recovered_cart desc ";

					$ac_results             = $wpdb->get_results( $query_ac );// nosemgrep:audit.php.wp.security.sqli.input-in-sinks
					$return_abandoned_count = count( $ac_results );
					break;

				case 'wcap_all_registered':
					// 7.14.0 - @todo - remove this case
					$query_ac               = 'SELECT * FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE ( user_type = 'REGISTERED' AND abandoned_cart_time >=  $start_date AND abandoned_cart_time <= $end_date AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_time <= '$compare_time' AND recovered_cart = 0 AND wcap_trash = '' AND cart_ignored <> '1' ) ORDER BY recovered_cart desc ";
					$ac_results             = $wpdb->get_results( $query_ac );// nosemgrep:audit.php.wp.security.sqli.input-in-sinks
					$return_abandoned_count = count( $ac_results );
					break;

				case 'wcap_all_guest':
					// 7.14.0 - @todo - remove this case
					$query_ac               = 'SELECT * FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE ( user_type = 'GUEST' AND abandoned_cart_time >=  $start_date AND abandoned_cart_time <= $end_date AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_time <= '$compare_time_guest' AND recovered_cart = 0 AND wcap_trash = '' AND user_id >= 63000000 AND cart_ignored <> '1' ) ORDER BY recovered_cart desc ";
					$ac_results             = $wpdb->get_results( $query_ac );// nosemgrep:audit.php.wp.security.sqli.input-in-sinks
					$return_abandoned_count = count( $ac_results );
					break;

				case 'wcap_all_visitor':
					// 7.14.0 - @todo - remove this case
					$query_ac               = 'SELECT * FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE ( user_type = 'GUEST' AND abandoned_cart_time >=  $start_date AND abandoned_cart_time <= $end_date AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_time <= '$compare_time_guest' AND recovered_cart = 0 AND wcap_trash = '' AND user_id = 0 AND cart_ignored <> '1' ) ORDER BY recovered_cart desc ";
					$ac_results             = $wpdb->get_results( $query_ac );// nosemgrep:audit.php.wp.security.sqli.input-in-sinks
					$return_abandoned_count = count( $ac_results );
					break;

				case 'wcap_all_unsubscribe_carts':
					$query_ac               = 'SELECT * FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "` WHERE abandoned_cart_time >=  $start_date AND abandoned_cart_time <= $end_date AND wcap_trash = '' AND unsubscribe_link = '1' ORDER BY abandoned_cart_time DESC";
					$ac_results             = $wpdb->get_results( $query_ac );// nosemgrep:audit.php.wp.security.sqli.input-in-sinks
					$return_abandoned_count = count( $ac_results );
					break;

				default:
					break;
			}

			return $return_abandoned_count;
		}

		/**
		 * It will get the total amount of email sent for the time period.
		 *
		 * @param date   $start_date_range Admin selected start date
		 * @param date   $end_date_range Admin selected end date
		 * @param string $get_section_result Section name
		 * @globals mixed $wpdb
		 * @return int $return_sent_email_count Count of email sent
		 * @since 5.0
		 */
		public static function wcap_get_sent_emails_count( $start_date_range, $end_date_range, $get_section_result ) {
			global $wpdb;
			$return_sent_email_count = 0;

			$start_date    = strtotime( $start_date_range . ' 00:01:01' );
			$end_date      = strtotime( $end_date_range . ' 23:59:59' );
			$start_date_db = date( 'Y-m-d H:i:s', $start_date );
			$end_date_db   = date( 'Y-m-d H:i:s', $end_date );

			$blank_cart_info       = '{"cart":[]}';
			$blank_cart_info_guest = '[]';

			$ac_cutoff_time = is_numeric( get_option( 'ac_cart_abandoned_time', 10 ) ) ? get_option( 'ac_cart_abandoned_time', 10 ) : 10;
			$cut_off_time   = $ac_cutoff_time * 60;
			$current_time   = current_time( 'timestamp' );
			$compare_time   = $current_time - $cut_off_time;

			$ac_cutoff_time_guest = is_numeric( get_option( 'ac_cart_abandoned_time_guest', 10 ) ) ? get_option( 'ac_cart_abandoned_time_guest', 10 ) : 10;
			$cut_off_time_guest   = $ac_cutoff_time_guest * 60;
			$current_time         = current_time( 'timestamp' );
			$compare_time_guest   = $current_time - $cut_off_time_guest;

			switch ( $get_section_result ) {
				case 'wcap_all_sent':
					$query_ac_sent           = 'SELECT wpsh.* FROM ' . WCAP_EMAIL_SENT_HISTORY_TABLE . ' as wpsh WHERE wpsh.sent_time >= %s AND wpsh.sent_time <= %s ORDER BY wpsh.id DESC';
					$ac_results_sent         = $wpdb->get_results( $wpdb->prepare( $query_ac_sent, $start_date_db, $end_date_db ) );
					$return_sent_email_count = count( $ac_results_sent );
					break;

				default:
					// code...
					break;
			}

			return $return_sent_email_count;
		}

		/**
		 * It will get the session key for the gusst users.
		 *
		 * @return string $wcap_session_id Session key
		 * @since 5.0
		 */
		public static function wcap_get_guest_session_key() {

			if ( ! WC()->session || ! method_exists( WC()->session, 'get_session_cookie' ) ) {
				return 0;
			}
			$wcap_get_cookie = WC()->session->get_session_cookie();
			if ( $wcap_get_cookie ) {
				return $wcap_get_cookie[0];
			}
			return 0;
		}

		/**
		 * It will automatically populate data of the guest user when user comes from the abandoned cart reminder email.
		 *
		 * @param array $fields List of fields
		 * @return array $fields List of fields
		 * @since 5.0
		 */
		public static function guest_checkout_fields( $fields ) {

			if ( ! wp_doing_ajax() && is_checkout() ) {

				if ( wcap_get_cart_session( 'wcap_guest_first_name' ) != '' ) {
					$_POST['billing_first_name'] = wcap_get_cart_session( 'wcap_guest_first_name' );
				}
				if ( wcap_get_cart_session( 'wcap_guest_last_name' ) != '' ) {
					$_POST['billing_last_name'] = wcap_get_cart_session( 'wcap_guest_last_name' );
				}
				if ( wcap_get_cart_session( 'wcap_populate_email' ) != '' ) {
					$_POST['billing_email'] = wcap_get_cart_session( 'wcap_populate_email' );
				} elseif ( wcap_get_cart_session( 'wcap_guest_email' ) != '' ) {
					$_POST['billing_email'] = wcap_get_cart_session( 'wcap_guest_email' );
				}
				if ( wcap_get_cart_session( 'wcap_guest_phone' ) != '' ) {
					$_POST['billing_phone'] = wcap_get_cart_session( 'wcap_guest_phone' );
				}
			}
			return $fields;
		}

		public static function wfacp_guest_checkout_fields( $field_value, $key, $fields ) {

			if ( $key === 'billing_email' ) {
				if ( wcap_get_cart_session( 'wcap_populate_email' ) != '' ) {
					$field_value = $_POST['billing_email'] = wcap_get_cart_session( 'wcap_populate_email' );
				} elseif ( wcap_get_cart_session( 'wcap_guest_email' ) != '' ) {
					$field_value = $_POST['billing_email'] = wcap_get_cart_session( 'wcap_guest_email' );
				}
			}
			return $field_value;
		}

		/**
		 * It will replace the email body merge codes with content.
		 *
		 * @param string $body_email_preview Email body
		 * @globals mixed $wpdb
		 * @return strinig $body_email_preview Email body
		 * @since 7.0
		 */
		public static function wcap_replace_email_body_merge_code( $body_email_preview ) {
			global $wpdb;

			$wcap_product_image_height = get_option( 'wcap_product_image_height' );
			$wcap_product_image_width  = get_option( 'wcap_product_image_width' );

			$customer_details                       = wcap_get_customer_names( 'REGISTERED', get_current_user_id() );
			$merge_tag_values['customer.firstname'] = isset( $customer_details['first_name'] ) ? $customer_details['first_name'] : '';
			$merge_tag_values['customer.lastname']  = isset( $customer_details['last_name'] ) ? $customer_details['last_name'] : '';
			$merge_tag_values['customer.fullname']  = isset( $customer_details['full_name'] ) ? $customer_details['full_name'] : '';

			$wcap_product_query = 'SELECT wpost.id, wpost.post_title from ' . $wpdb->prefix . 'posts as wpost 
                                    LEFT JOIN ' . $wpdb->prefix . "postmeta as wpm ON wpost.id = wpm.post_id 
                                    WHERE 
                                    wpost.post_type = 'product' 
                                    AND 
                                    wpost.post_status= 'publish' 
                                    AND 
                                    wpm.meta_key = '_regular_price' 
                                    AND wpm.meta_value > '0' 
                                    ORDER BY id DESC LIMIT 1";
			$wcap_get_products  = $wpdb->get_results( $wcap_product_query );

			$email_settings['image_height']  = $wcap_product_image_height;
			$email_settings['image_width']   = $wcap_product_image_width;
			$email_settings['checkout_link'] = wc_get_page_permalink( 'checkout' );
			$email_settings['coupon_used']   = 'TESTCOUPON';
			$email_settings['currency']      = get_woocommerce_currency();
			$email_settings['abandoned_id']  = 0;
			$email_settings['blog_name']     = get_option( 'blogname', '' );
			$email_settings['site_url']      = get_option( 'siteurl', '' );
			$email_settings['email_sent_id'] = 0;
			$email_settings['utm_params']    = get_option( 'wcap_add_utm_to_links', '' );
			$email_settings['cart_lang']     = ''; // Blank as preview will not have cart language.
			$wcap_products                   = array();

			$i = 1;
			foreach ( $wcap_get_products as $k => $v ) {
				$product                                = wc_get_product( $v->id );
				$wcap_products[ $i ]                    = new stdClass();
				$wcap_products[ $i ]->product_id        = $v->id;
				$wcap_products[ $i ]->line_total        = get_post_meta( $v->id, '_regular_price', true );
				$wcap_products[ $i ]->line_subtotal     = $wcap_products[ $i ]->line_total;
				$wcap_products[ $i ]->line_tax          = 7;
				$wcap_products[ $i ]->line_subtotal_tax = 7;
				$wcap_products[ $i ]->quantity          = 1;
			}

			$wcap_products = (object) $wcap_products;

			$body_email_preview = wcap_replace_product_cart( $body_email_preview, $wcap_products, $email_settings );
			$body_email_preview = wcap_replace_upsell_data( $body_email_preview, $wcap_products, $email_settings );
			$body_email_preview = wcap_replace_crosssell_data( $body_email_preview, $wcap_products, $email_settings );

			$current_time_stamp = current_time( 'timestamp' );
			$date_format        = date_i18n( get_option( 'date_format' ), $current_time_stamp );
			$time_format        = date_i18n( get_option( 'time_format' ), $current_time_stamp );
			$test_date          = $date_format . ' ' . $time_format;
			$site_url           = get_option( 'siteurl' );

			$merge_tag_values['customer.email']      = get_option( 'admin_email' );
			$merge_tag_values['customer.phone']      = get_user_meta( get_current_user_id(), 'billing_phone', true );
			$merge_tag_values['coupon.code']         = 'TESTCOUPON';
			$merge_tag_values['cart.abandoned_date'] = $test_date;
			$merge_tag_values['checkout.link']       = wc_get_page_permalink( 'checkout' );
			$merge_tag_values['cart.link']           = wc_get_page_permalink( 'cart' );
			$merge_tag_values['cart.unsubscribe']    = $site_url . '/?wcap_test_track_unsubscribe=wcap_test_unsubscribe';

			$body_email_preview = wcap_replace_email_merge_tags_body( $body_email_preview, $merge_tag_values );

			return $body_email_preview;
		}

		/**
		 * It will handle the email open and unsubscribe the user from the cart.
		 *
		 * @hook template_include
		 * @param string $template The path of the template to include.
		 * @return string $template The path of the template to include.
		 * @globals mixed $wpdb
		 * @globals mixed $woocommerce
		 * @since 9.2.0
		 */
		public static function wcap_test_email_track_open_and_unsubscribe( $args ) {
			global $wpdb;
			$unsubscribe = '';

			if ( isset( $_GET['wcap_test_track_unsubscribe'] ) && $_GET['wcap_test_track_unsubscribe'] == 'wcap_test_unsubscribe' ) {
				$unsubscribe = true;
			} else {
				return $args;
			}
			if ( $unsubscribe ) {

				$unsubscribe_choice = get_option( 'wcap_unsubscribe_landing_page', 'default_page' );
				$redirect           = false;
				switch ( $unsubscribe_choice ) {
					case 'default_page':
					case 'default':
						wc_get_template(
							'wcap_default_landing.php',
							array(
								'content' => apply_filters( 'wcap_unsubscribe_page_content', __( 'You have successfully unsubscribed and will not receive any further reminders for abandoned carts.', 'woocommerce-ac' ) ),
							),
							'woocommerce-abandon-cart-pro/',
							WCAP_PLUGIN_PATH . '/includes/template/unsubscribe/'
						);
						break;

					case 'custom_text':
						$content = get_option( 'wcap_unsubscribe_custom_content', 'You have successfully unsubscribed and will not receive any further reminders for abandoned carts.' );

						wc_get_template(
							'wcap_default_landing.php',
							array(
								'content' => __( $content, 'woocommerce-ac' ),
							),
							'woocommerce-abandon-cart-pro/',
							WCAP_PLUGIN_PATH . '/includes/template/unsubscribe/'
						);
						break;

					case 'custom_wp_page':
						$redirect       = true;
						$custom_page_id = get_option( 'wcap_unsubscribe_custom_wp_page' );
						$url            = $custom_page_id > 0 ? get_permalink( $custom_page_id ) : get_option( 'siteurl' );
						break;
				}

				if ( $redirect ) {
					sleep( 2 );
					wp_safe_redirect( $url );
					exit;
				}
			} else {
				return $args;
			}
		}

		/**
		 * It will return the current section.
		 *
		 * @return string $section Section name
		 */
		public static function wcap_get_current_section() {
			$section = 'wcap_all_abandoned';
			if ( isset( $_GET['wcap_section'] ) ) {
				$section = $_GET['wcap_section'];
			}
			return $section;
		}

		/**
		 * Get the image to be attached to the emails
		 *
		 * @param string|int          $id Product ID or the variation ID
		 * @param string              $size (default: 'shop_thumbnail')
		 * @param array               $attr
		 * @param bool True to return $placeholder if no image is found, or false to return an empty string.
		 * @return string
		 *
		 * @since 7.6.0
		 */
		public static function wcap_get_product_image( $id, $size = 'shop_thumbnail', $attr = array(), $placeholder = true ) {

			if ( has_post_thumbnail( $id ) ) {
				$image = self::wcap_get_product_image_thumbnail( $id, $size, $attr );
				$image = apply_filters( 'wcac_product_img_html', $image, $id, $size, $attr );
			} elseif ( ( $parent_id = wp_get_post_parent_id( $id ) ) && has_post_thumbnail( $parent_id ) ) {
				$image = self::wcap_get_product_image_thumbnail( $parent_id, $size, $attr );
			} elseif ( $placeholder ) {
				$image = wc_placeholder_img( $size );
			} else {
				$image = '';
			}
			return $image;
		}

		/**
		 * Get the image to be attached to the emails
		 *
		 * @param string|int          $id Product ID or the variation ID
		 * @param string              $size (default: 'shop_thumbnail')
		 * @param array               $attr
		 * @param bool True to return $placeholder if no image is found, or false to return an empty string.
		 * @return string
		 *
		 * @since 7.6.0
		 */
		public static function wcap_get_product_image_thumbnail( $id, $custom_size = 'thumbnail', $attr = array() ) {

			$full_image_url   = get_the_post_thumbnail_url( $id, 'full' );
			$custom_image_url = get_the_post_thumbnail_url( $id, $custom_size );

			if ( $custom_image_url === $full_image_url ) {
				return get_the_post_thumbnail( $id, 'thumbnail', $attr );
			}
			return get_the_post_thumbnail( $id, $custom_size, $attr );
		}

		/**
		 * Add the From Name for WooCommerce Template Emails via Filters
		 *
		 * @param string $from_name From name
		 * @return string
		 * @since 7.6.0
		 */
		public static function wcap_from_name( $from_name ) {
			return get_option( 'wcap_from_name' );
		}

		/**
		 * Add the From Emails for WooCommerce Template Emails via Filters
		 *
		 * @param string $from_address From address
		 * @return string
		 * @since 7.6.0
		 */
		public static function wcap_from_address( $from_address ) {
			return get_option( 'wcap_from_email' );
		}

		/**
		 * Add the From Name and Emails for WooCommerce Template Emails via Filters
		 *
		 * @since 7.6.0
		 */
		public static function wcap_add_wc_mail_header() {

			// adding custom smtp credentials, if set, return. @since 8.15

			if ( Wcap_Custom_SMTP::wcap_is_set_custom_smtp() ) {
				Wcap_Custom_SMTP::wcap_add_custom_mail_hooks();
				return;
			}

			add_filter( 'woocommerce_email_from_name', array( 'Wcap_Common', 'wcap_from_name' ) );
			add_filter( 'woocommerce_email_from_address', array( 'Wcap_Common', 'wcap_from_address' ) );
			add_action( 'phpmailer_init', array( 'Wcap_Common', 'wcap_set_plaintext_body' ) );

		}

		/**
		 * Remove the From Name and Emails for WooCommerce Template Emails via Filters.
		 * This will be called after Abandoned Cart Emails are sent
		 *
		 * @since 7.6.0
		 */
		public static function wcap_remove_wc_mail_header() {

			remove_filter( 'woocommerce_email_from_name', array( 'Wcap_Common', 'wcap_from_name' ) );
			remove_filter( 'woocommerce_email_from_address', array( 'Wcap_Common', 'wcap_from_address' ) );

			remove_action( 'phpmailer_init', array( 'Wcap_Common', 'wcap_set_plaintext_body' ) );

			// remove mail hooks attached to send mail from custom smtp server. @since 8.15
			if ( Wcap_Custom_SMTP::wcap_is_set_custom_smtp() ) {
				Wcap_Custom_SMTP::wcap_remove_custom_mail_hooks();
			}
		}

		/**
		 * Add the From Name and Emails for WordPress Template Emails via Filters
		 *
		 * @since 7.6.0
		 */
		public static function wcap_add_wp_mail_header() {

			// adding custom smtp credentials, if set, return. @since 8.15
			if ( Wcap_Custom_SMTP::wcap_is_set_custom_smtp() ) {
				Wcap_Custom_SMTP::wcap_add_custom_mail_hooks();
				return;
			}

			add_filter( 'wp_mail_from_name', array( 'Wcap_Common', 'wcap_from_name' ) );
			add_filter( 'wp_mail_from', array( 'Wcap_Common', 'wcap_from_address' ) );
			add_action( 'phpmailer_init', array( 'Wcap_Common', 'wcap_set_plaintext_body' ) );
		}

		/**
		 * Remove the From Name and Emails for WordPress Template Emails via Filters.
		 * This will be called after Abandoned Cart Emails are sent
		 *
		 * @since 7.6.0
		 */
		public static function wcap_remove_wp_mail_header() {

			remove_filter( 'wp_mail_from_name', array( 'Wcap_Common', 'wcap_from_name' ) );
			remove_filter( 'wp_mail_from', array( 'Wcap_Common', 'wcap_from_address' ) );
			remove_action( 'phpmailer_init', array( 'Wcap_Common', 'wcap_set_plaintext_body' ) );

			// remove mail hooks attached to send mail from custom smtp server. @since 8.15
			if ( Wcap_Custom_SMTP::wcap_is_set_custom_smtp() ) {
				Wcap_Custom_SMTP::wcap_remove_custom_mail_hooks();
				return;
			}

		}

		/**
		 * It will restrict the user and do not capture the cart.
		 *
		 * @param object $user User data
		 * @since 7.6
		 */
		public static function wcap_add_restrict_user_meta_field( $user ) {
			echo '<h3 class="heading">Restrict user for capturing the abandoned cart.</h3>';

			$wcap_is_user_blocked = '';
			if ( isset( $user->ID ) && '' != $user->ID ) {
				$wcap_get_is_user_blocked = get_user_meta( $user->ID, 'wcap_restrict_user' );
				if ( count( $wcap_get_is_user_blocked ) > 0 && isset( $wcap_get_is_user_blocked[0] ) && 'on' == $wcap_get_is_user_blocked[0] ) {
					$wcap_is_user_blocked = 'checked';
				}
			}?>
			<table class="form-table">
				<tr>
					<th>
						<label for="wcap_restrict_user">Do not capture abandoned cart of this user</label>
					</th>
		  
					<td>
						<input type="checkbox" id="wcap_restrict_user" name="wcap_restrict_user" value="on" <?php echo $wcap_is_user_blocked; ?> />
					</td>
				</tr>
			</table>
			<?php
		}

		/**
		 * Save the setting for the user restriction setting.
		 *
		 * @param int|string wcap_user_id User Id
		 * @since 7.6
		 */
		public static function wcap_save_restrict_user_meta_field( $wcap_user_id ) {
			if ( isset( $_POST['wcap_restrict_user'] ) && '' != $_POST['wcap_restrict_user'] ) {
				$wcap_restrict_user = $_POST['wcap_restrict_user'];
				update_user_meta( $wcap_user_id, 'wcap_restrict_user', $wcap_restrict_user );
			}
		}

		/**
		 * It will  add the plain text in the Abanodned cart reminder emails.
		 *
		 * @param PHPMailer $phpmailer
		 * @since  7.6
		 */
		public static function wcap_set_plaintext_body( $phpmailer ) {

			$previous_altbody = '';

			// don't run if sending plain text email already
			if ( $phpmailer->ContentType === 'text/plain' ) {
				return;
			}

			// don't run if altbody is set (by other plugin)
			if ( ! empty( $phpmailer->AltBody ) && $phpmailer->AltBody !== $previous_altbody ) {
				return;
			}

			// set AltBody
			$text_message       = self::wcap_strip_html_tags( $phpmailer->Body );
			$phpmailer->AltBody = wordwrap( $text_message );
			$previous_altbody   = $text_message;
		}

		/**
		 * Remove HTML tags, including invisible text such as style and
		 * script code, and embedded objects.  Add line breaks around
		 * block-level tags to prevent word joining after tag removal.
		 *
		 * @param string $text Texts with html
		 * @return string $text Texts without html
		 */
		private static function wcap_strip_html_tags( $text ) {
			$text = preg_replace(
				array(
					// Remove invisible content
					  '@<head[^>]*?>.*?</head>@siu',
					'@<style[^>]*?>.*?</style>@siu',
					'@<script[^>]*?.*?</script>@siu',
					'@<object[^>]*?.*?</object>@siu',
					'@<embed[^>]*?.*?</embed>@siu',
					'@<noscript[^>]*?.*?</noscript>@siu',
					'@<noembed[^>]*?.*?</noembed>@siu',
					'@\t+@siu',
					'@\n+@siu',
				),
				'',
				$text
			);

			// replace certain elements with a line-break
			$text = preg_replace(
				array(
					'@</?((div)|(h[1-9])|(/tr)|(p)|(pre))@iu',
				),
				"\n\$0",
				$text
			);

			// replace other elements with a space
			$text = preg_replace(
				array(
					'@</((td)|(th))@iu',
				),
				"\n\$0",
				$text
			);

			$plain_replace = array(
				'',                                             // Non-legal carriage return
				' ',                                            // Non-breaking space
				'"',                                            // Double quotes
				"'",                                            // Single quotes
				'>',                                            // Greater-than
				'<',                                            // Less-than
				'&',                                            // Ampersand
				'&',                                            // Ampersand
				'&',                                            // Ampersand
				'(c)',                                          // Copyright
				'(tm)',                                         // Trademark
				'(R)',                                          // Registered
				'--',                                           // mdash
				'-',                                            // ndash
				'*',                                            // Bullet
				'£',                                            // Pound sign
				'EUR',                                          // Euro sign. € ?
				'$',                                            // Dollar sign
				'',                                             // Unknown/unhandled entities
				' ',                                             // Runs of spaces, post-handling
			);

			$plain_search = array(
				"/\r/",                                          // Non-legal carriage return
				'/&(nbsp|#160);/i',                              // Non-breaking space
				'/&(quot|rdquo|ldquo|#8220|#8221|#147|#148);/i', // Double quotes
				'/&(apos|rsquo|lsquo|#8216|#8217);/i',           // Single quotes
				'/&gt;/i',                                       // Greater-than
				'/&lt;/i',                                       // Less-than
				'/&#38;/i',                                      // Ampersand
				'/&#038;/i',                                     // Ampersand
				'/&amp;/i',                                      // Ampersand
				'/&(copy|#169);/i',                              // Copyright
				'/&(trade|#8482|#153);/i',                       // Trademark
				'/&(reg|#174);/i',                               // Registered
				'/&(mdash|#151|#8212);/i',                       // mdash
				'/&(ndash|minus|#8211|#8722);/i',                // ndash
				'/&(bull|#149|#8226);/i',                        // Bullet
				'/&(pound|#163);/i',                             // Pound sign
				'/&(euro|#8364);/i',                             // Euro sign
				'/&#36;/',                                       // Dollar sign
				'/&[^&\s;]+;/i',                                 // Unknown/unhandled entities
				'/[ ]{2,}/',                                      // Runs of spaces, post-handling
			);

			$text = preg_replace( $plain_search, $plain_replace, $text );
			// strip all remaining HTML tags
			$text = strip_tags( $text );

			// trim text
			$text = trim( $text );

			return $text;
		}

		/**
		 * Updates the Abandoned Cart History table as well as the
		 * Email Sent History table to indicate the order has been
		 * recovered
		 *
		 * @param integer  $cart_id - ID of the Abandoned Cart
		 * @param integer  $order_id - Recovered Order ID
		 * @param integer  $wcap_check_email_sent_to_cart - ID of the record in the Email Sent History table.
		 * @param WC_Order $order - Order Details
		 *
		 * @since 7.7
		 */
		static function wcap_updated_recovered_cart( $cart_id, $order_id, $wcap_check_email_sent_to_cart, $order ) {

			global $wpdb;
			$recovered = wcap_get_cart_session( 'wcap_recovered_cart' );
			// check & make sure that the recovered cart details are not already updated
			$query_status = 'SELECT user_id, recovered_cart FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` WHERE id = %d';

			$get_status = $wpdb->get_row( $wpdb->prepare( $query_status, $cart_id ) );

			$recovered_status = isset( $get_status->recovered_cart ) ? $get_status->recovered_cart : '';
			$user_id          = isset( $get_status->user_id ) ? $get_status->user_id : 0;

			if ( $recovered_status == 0 ) {

				// Update the cart history table
				$update_details = array(
					'recovered_cart' => $order_id,
					'cart_ignored'   => '1',
					'language'       => '',
				);

				$get_old_cart_id = 0;
				// check if more than one reminder email has been sent.
				if ( $wcap_check_email_sent_to_cart > 0 ) {
					$get_old_cart_id = $wpdb->get_var(
						$wpdb->prepare(
							'SELECT cart_id FROM ' . WCAP_EMAIL_SENT_HISTORY_TABLE . ' WHERE id = %d',
							$wcap_check_email_sent_to_cart
						)
					);
				}

				$update_sent_history = array(
					'recovered_order' => '1',
				);

				if ( $user_id == 0 && $get_old_cart_id > 0 ) { // We can't set a visitor cart as recovered. Update the cart ID to mark the old cart as recovered.
					$cart_id = $get_old_cart_id;
				}

				WCAP_CART_HISTORY_MODEL::update( $update_details, array( 'id' => $cart_id ) );

				// update the email sent history table
				if ( $wcap_check_email_sent_to_cart > 0 || $recovered ) {
					$wpdb->update(
						WCAP_EMAIL_SENT_HISTORY_TABLE,
						$update_sent_history,
						array( 'id' => $wcap_check_email_sent_to_cart )
					);
				}

				// Add Order Note
				$order->add_order_note( __( 'This order was abandoned & subsequently recovered.', 'woocommerce-ac' ) );

				// delete the cart from the sms notifications list.
				self::wcap_delete_cart_notification( $cart_id );
				do_action( 'wcap_cart_recovered', $cart_id, $order_id );
			}
		}

		/**
		 * Display Prices as per the currency selected during cart creation
		 *
		 * @param string $price Price to be displayed
		 * @param string $currency Currency in which price needs to be displayed
		 *
		 * @return string modified price with currency symbol
		 *
		 * @since 7.7
		 */
		public static function wcap_get_price( $price, $currency ) {
		    if ( ! isset( $currency ) || '' === $currency ) {
		        return wc_price( $price );
		    }
		    global $WOOCS;
		    $removed = is_object( $WOOCS )
		        ? remove_filter( 'woocommerce_currency_symbol', array( $WOOCS, 'woocommerce_currency_symbol' ), 9999 )
		        : false;

		    $formatted = wc_price( $price, array( 'currency' => $currency ) );
		    if ( $removed ) {
		        add_filter( 'woocommerce_currency_symbol', array( $WOOCS, 'woocommerce_currency_symbol' ), 9999 );
		    }
		    return $formatted;
		}

		/**
		 * Validate Cart and check if its not empty
		 *
		 * @param mixed $cart_info Cart Info Object
		 * @return bool true if valid else false
		 *
		 * @since 7.7.0
		 */
		public static function wcap_validate_cart( $cart_info ) {

			$cart_info = json_decode( stripslashes( $cart_info ), true );

			if ( ! empty( $cart_info ) && isset( $cart_info['cart'] ) &&
				 ! empty( $cart_info['cart'] ) && count( $cart_info ) > 0 ) {

				return true;
			} else {
				return false;
			}
		}

		/**
		 * Removes the Cart ID from the list of carts for which
		 * SMS reminders need to be sent.
		 *
		 * @param integer $cart_id - Abandoned Cart ID
		 * @since 7.9
		 */
		static function wcap_delete_cart_notification( $cart_id ) {

			global $wpdb;
			// check if templates are present
			$sms_query = 'SELECT id from `' . WCAP_NOTIFICATION_TEMPLATES_TABLE . "`
                            WHERE notification_type = 'sms' AND is_active = '1'";

			$sms_list = $wpdb->get_results( $sms_query );

			// check for active SMS templates
			if ( false != $sms_list && is_array( $sms_list ) && count( $sms_list ) > 0 ) {
				foreach ( $sms_list as $sms_data ) {
					$template_id = $sms_data->id;

					// check if template is active
					if ( $template_id > 0 ) {

						$cart_list = wcap_get_notification_meta( $template_id, 'to_be_sent_cart_ids' );

						if ( $cart_list ) {

							// check if the ID is already present
							$explode_list = explode( ',', $cart_list );

							if ( in_array( $cart_id, $explode_list ) ) {
								$key = array_search( $cart_id, $explode_list );
								unset( $explode_list[ $key ] );
							}

							$carts_str = implode( ',', $explode_list );

							// update the record
							wcap_update_notification_meta( $template_id, 'to_be_sent_cart_ids', $carts_str );

						}
					}
				}
			}

		}

		/**
		 * Returns the User ID for a given abandoned
		 * cart ID
		 *
		 * @param integer $cart_id - Abandoned Cart ID
		 * @return integer $user_id - User ID
		 *
		 * @since 7.9
		 */
		static function get_user_id_from_cart( $cart_id ) {

			global $wpdb;

			$user_id = 0;

			if ( $cart_id > 0 ) {
				$user_query = 'SELECT user_id FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '`
                                WHERE id = %d';

				$user_results = $wpdb->get_results( $wpdb->prepare( $user_query, $cart_id ) );

				if ( is_array( $user_results ) && count( $user_results ) > 0 ) {
					$user_id = isset( $user_results[0]->user_id ) ? $user_results[0]->user_id : 0;
				}
			}

			return $user_id;
		}

		/**
		 * Returns the Guest user data for a given user ID from
		 * the Guest Table
		 *
		 * @param integer $user_id - User ID
		 * @return array $guest - Array containing guest user details array
		 *
		 * @since 7.9
		 */
		static function get_guest_data( $user_id ) {

			global $wpdb;

			$guest = false;
			if ( $user_id >= '63000000' ) {

				$guest_query   = 'SELECT billing_first_name, billing_last_name, email_id, phone FROM `' . WCAP_GUEST_CART_HISTORY_TABLE . '`
                                WHERE id = %d';
				$guest_results = $wpdb->get_results( $wpdb->prepare( $guest_query, $user_id ) );

				if ( is_array( $guest_results ) && count( $guest_results ) > 0 ) {

					$guest = array(
						'first_name' => $guest_results[0]->billing_first_name,
						'last_name'  => $guest_results[0]->billing_last_name,
						'email_id'   => $guest_results[0]->email_id,
						'phone'      => $guest_results[0]->phone,
					);
				}
			}

			return $guest;
		}

		/**
		 * Returns the recovered cart ID for an abadoned cart record
		 * for which the user ID is passed
		 *
		 * @param integer $user_id - User ID (Guest & Registered)
		 * @return integer $recovered_order - Recovered Order ID
		 * @since 7.9
		 */
		static function get_recovered_id_for_user( $user_id ) {

			global $wpdb;

			$recovered_order = 0;
			if ( $user_id > 0 ) {

				$cart_query   = 'SELECT recovered_cart FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . "`
                                WHERE user_id = %d
                                AND cart_ignored IN ( '0','2' )";
				$cart_results = $wpdb->get_results( $wpdb->prepare( $cart_query, $user_id ) );

				if ( is_array( $cart_results ) && count( $cart_results ) > 0 ) {

					$recovered_order = isset( $cart_results[0]->recovered_cart ) ? $cart_results[0]->recovered_cart : 0;
				}
			}

			return $recovered_order;
		}

		/**
		 * Returns an array with mapped Country codes with ISD codes
		 *
		 * @return array Mapped Array
		 *
		 * @since 7.9
		 */
		public static function wcap_country_code_map() {

			return array(
				'IL' => array(
					'name'      => 'Israel',
					'dial_code' => '+972',
				),
				'AF' => array(
					'name'      => 'Afghanistan',
					'dial_code' => '+93',
				),
				'AL' => array(
					'name'      => 'Albania',
					'dial_code' => '+355',
				),
				'DZ' => array(
					'name'      => 'Algeria',
					'dial_code' => '+213',
				),
				'AS' => array(
					'name'      => 'AmericanSamoa',
					'dial_code' => '+1684',
				),
				'AD' => array(
					'name'      => 'Andorra',
					'dial_code' => '+376',
				),
				'AO' => array(
					'name'      => 'Angola',
					'dial_code' => '+244',
				),
				'AI' => array(
					'name'      => 'Anguilla',
					'dial_code' => '+1264',
				),
				'AG' => array(
					'name'      => 'Antigua and Barbuda',
					'dial_code' => '+1268',
				),
				'AR' => array(
					'name'      => 'Argentina',
					'dial_code' => '+54',
				),
				'AM' => array(
					'name'      => 'Armenia',
					'dial_code' => '+374',
				),
				'AW' => array(
					'name'      => 'Aruba',
					'dial_code' => '+297',
				),
				'AU' => array(
					'name'      => 'Australia',
					'dial_code' => '+61',
				),
				'AT' => array(
					'name'      => 'Austria',
					'dial_code' => '+43',
				),
				'AZ' => array(
					'name'      => 'Azerbaijan',
					'dial_code' => '+994',
				),
				'BS' => array(
					'name'      => 'Bahamas',
					'dial_code' => '+1 242',
				),
				'BH' => array(
					'name'      => 'Bahrain',
					'dial_code' => '+973',
				),
				'BD' => array(
					'name'      => 'Bangladesh',
					'dial_code' => '+880',
				),
				'BB' => array(
					'name'      => 'Barbados',
					'dial_code' => '+1 246',
				),
				'BY' => array(
					'name'      => 'Belarus',
					'dial_code' => '+375',
				),
				'BE' => array(
					'name'      => 'Belgium',
					'dial_code' => '+32',
				),
				'BZ' => array(
					'name'      => 'Belize',
					'dial_code' => '+501',
				),
				'BJ' => array(
					'name'      => 'Benin',
					'dial_code' => '+229',
				),
				'BM' => array(
					'name'      => 'Bermuda',
					'dial_code' => '+1 441',
				),
				'BT' => array(
					'name'      => 'Bhutan',
					'dial_code' => '+975',
				),
				'BA' => array(
					'name'      => 'Bosnia and Herzegovina',
					'dial_code' => '+387',
				),
				'BW' => array(
					'name'      => 'Botswana',
					'dial_code' => '+267',
				),
				'BR' => array(
					'name'      => 'Brazil',
					'dial_code' => '+55',
				),
				'IO' => array(
					'name'      => 'British Indian Ocean Territory',
					'dial_code' => '+246',
				),
				'BG' => array(
					'name'      => 'Bulgaria',
					'dial_code' => '+359',
				),
				'BF' => array(
					'name'      => 'Burkina Faso',
					'dial_code' => '+226',
				),
				'BI' => array(
					'name'      => 'Burundi',
					'dial_code' => '+257',
				),
				'KH' => array(
					'name'      => 'Cambodia',
					'dial_code' => '+855',
				),
				'CM' => array(
					'name'      => 'Cameroon',
					'dial_code' => '+237',
				),
				'CA' => array(
					'name'      => 'Canada',
					'dial_code' => '+1',
				),
				'CV' => array(
					'name'      => 'Cape Verde',
					'dial_code' => '+238',
				),
				'KY' => array(
					'name'      => 'Cayman Islands',
					'dial_code' => '+ 345',
				),
				'CF' => array(
					'name'      => 'Central African Republic',
					'dial_code' => '+236',
				),
				'TD' => array(
					'name'      => 'Chad',
					'dial_code' => '+235',
				),
				'CL' => array(
					'name'      => 'Chile',
					'dial_code' => '+56',
				),
				'CN' => array(
					'name'      => 'China',
					'dial_code' => '+86',
				),
				'CX' => array(
					'name'      => 'Christmas Island',
					'dial_code' => '+61',
				),
				'CO' => array(
					'name'      => 'Colombia',
					'dial_code' => '+57',
				),
				'KM' => array(
					'name'      => 'Comoros',
					'dial_code' => '+269',
				),
				'CG' => array(
					'name'      => 'Congo',
					'dial_code' => '+242',
				),
				'CK' => array(
					'name'      => 'Cook Islands',
					'dial_code' => '+682',
				),
				'CR' => array(
					'name'      => 'Costa Rica',
					'dial_code' => '+506',
				),
				'HR' => array(
					'name'      => 'Croatia',
					'dial_code' => '+385',
				),
				'CU' => array(
					'name'      => 'Cuba',
					'dial_code' => '+53',
				),
				'CY' => array(
					'name'      => 'Cyprus',
					'dial_code' => '+537',
				),
				'CZ' => array(
					'name'      => 'Czech Republic',
					'dial_code' => '+420',
				),
				'DK' => array(
					'name'      => 'Denmark',
					'dial_code' => '+45',
				),
				'DJ' => array(
					'name'      => 'Djibouti',
					'dial_code' => '+253',
				),
				'DM' => array(
					'name'      => 'Dominica',
					'dial_code' => '+1 767',
				),
				'DO' => array(
					'name'      => 'Dominican Republic',
					'dial_code' => '+1849',
				),
				'EC' => array(
					'name'      => 'Ecuador',
					'dial_code' => '+593',
				),
				'EG' => array(
					'name'      => 'Egypt',
					'dial_code' => '+20',
				),
				'SV' => array(
					'name'      => 'El Salvador',
					'dial_code' => '+503',
				),
				'GQ' => array(
					'name'      => 'Equatorial Guinea',
					'dial_code' => '+240',
				),
				'ER' => array(
					'name'      => 'Eritrea',
					'dial_code' => '+291',
				),
				'EE' => array(
					'name'      => 'Estonia',
					'dial_code' => '+372',
				),
				'ET' => array(
					'name'      => 'Ethiopia',
					'dial_code' => '+251',
				),
				'FO' => array(
					'name'      => 'Faroe Islands',
					'dial_code' => '+298',
				),
				'FJ' => array(
					'name'      => 'Fiji',
					'dial_code' => '+679',
				),
				'FI' => array(
					'name'      => 'Finland',
					'dial_code' => '+358',
				),
				'FR' => array(
					'name'      => 'France',
					'dial_code' => '+33',
				),
				'GF' => array(
					'name'      => 'French Guiana',
					'dial_code' => '+594',
				),
				'PF' => array(
					'name'      => 'French Polynesia',
					'dial_code' => '+689',
				),
				'GA' => array(
					'name'      => 'Gabon',
					'dial_code' => '+241',
				),
				'GM' => array(
					'name'      => 'Gambia',
					'dial_code' => '+220',
				),
				'GE' => array(
					'name'      => 'Georgia',
					'dial_code' => '+995',
				),
				'DE' => array(
					'name'      => 'Germany',
					'dial_code' => '+49',
				),
				'GH' => array(
					'name'      => 'Ghana',
					'dial_code' => '+233',
				),
				'GI' => array(
					'name'      => 'Gibraltar',
					'dial_code' => '+350',
				),
				'GR' => array(
					'name'      => 'Greece',
					'dial_code' => '+30',
				),
				'GL' => array(
					'name'      => 'Greenland',
					'dial_code' => '+299',
				),
				'GD' => array(
					'name'      => 'Grenada',
					'dial_code' => '+1 473',
				),
				'GP' => array(
					'name'      => 'Guadeloupe',
					'dial_code' => '+590',
				),
				'GU' => array(
					'name'      => 'Guam',
					'dial_code' => '+1 671',
				),
				'GT' => array(
					'name'      => 'Guatemala',
					'dial_code' => '+502',
				),
				'GN' => array(
					'name'      => 'Guinea',
					'dial_code' => '+224',
				),
				'GW' => array(
					'name'      => 'Guinea-Bissau',
					'dial_code' => '+245',
				),
				'GY' => array(
					'name'      => 'Guyana',
					'dial_code' => '+595',
				),
				'HT' => array(
					'name'      => 'Haiti',
					'dial_code' => '+509',
				),
				'HN' => array(
					'name'      => 'Honduras',
					'dial_code' => '+504',
				),
				'HU' => array(
					'name'      => 'Hungary',
					'dial_code' => '+36',
				),
				'IS' => array(
					'name'      => 'Iceland',
					'dial_code' => '+354',
				),
				'IN' => array(
					'name'      => 'India',
					'dial_code' => '+91',
				),
				'ID' => array(
					'name'      => 'Indonesia',
					'dial_code' => '+62',
				),
				'IQ' => array(
					'name'      => 'Iraq',
					'dial_code' => '+964',
				),
				'IE' => array(
					'name'      => 'Ireland',
					'dial_code' => '+353',
				),
				'IL' => array(
					'name'      => 'Israel',
					'dial_code' => '+972',
				),
				'IT' => array(
					'name'      => 'Italy',
					'dial_code' => '+39',
				),
				'JM' => array(
					'name'      => 'Jamaica',
					'dial_code' => '+1876',
				),
				'JP' => array(
					'name'      => 'Japan',
					'dial_code' => '+81',
				),
				'JO' => array(
					'name'      => 'Jordan',
					'dial_code' => '+962',
				),
				'KZ' => array(
					'name'      => 'Kazakhstan',
					'dial_code' => '+77',
				),
				'KE' => array(
					'name'      => 'Kenya',
					'dial_code' => '+254',
				),
				'KI' => array(
					'name'      => 'Kiribati',
					'dial_code' => '+686',
				),
				'KW' => array(
					'name'      => 'Kuwait',
					'dial_code' => '+965',
				),
				'KG' => array(
					'name'      => 'Kyrgyzstan',
					'dial_code' => '+996',
				),
				'LV' => array(
					'name'      => 'Latvia',
					'dial_code' => '+371',
				),
				'LB' => array(
					'name'      => 'Lebanon',
					'dial_code' => '+961',
				),
				'LS' => array(
					'name'      => 'Lesotho',
					'dial_code' => '+266',
				),
				'LR' => array(
					'name'      => 'Liberia',
					'dial_code' => '+231',
				),
				'LI' => array(
					'name'      => 'Liechtenstein',
					'dial_code' => '+423',
				),
				'LT' => array(
					'name'      => 'Lithuania',
					'dial_code' => '+370',
				),
				'LU' => array(
					'name'      => 'Luxembourg',
					'dial_code' => '+352',
				),
				'MG' => array(
					'name'      => 'Madagascar',
					'dial_code' => '+261',
				),
				'MW' => array(
					'name'      => 'Malawi',
					'dial_code' => '+265',
				),
				'MY' => array(
					'name'      => 'Malaysia',
					'dial_code' => '+60',
				),
				'MV' => array(
					'name'      => 'Maldives',
					'dial_code' => '+960',
				),
				'ML' => array(
					'name'      => 'Mali',
					'dial_code' => '+223',
				),
				'MT' => array(
					'name'      => 'Malta',
					'dial_code' => '+356',
				),
				'MH' => array(
					'name'      => 'Marshall Islands',
					'dial_code' => '+692',
				),
				'MQ' => array(
					'name'      => 'Martinique',
					'dial_code' => '+596',
				),
				'MR' => array(
					'name'      => 'Mauritania',
					'dial_code' => '+222',
				),
				'MU' => array(
					'name'      => 'Mauritius',
					'dial_code' => '+230',
				),
				'YT' => array(
					'name'      => 'Mayotte',
					'dial_code' => '+262',
				),
				'MX' => array(
					'name'      => 'Mexico',
					'dial_code' => '+52',
				),
				'MC' => array(
					'name'      => 'Monaco',
					'dial_code' => '+377',
				),
				'MN' => array(
					'name'      => 'Mongolia',
					'dial_code' => '+976',
				),
				'ME' => array(
					'name'      => 'Montenegro',
					'dial_code' => '+382',
				),
				'MS' => array(
					'name'      => 'Montserrat',
					'dial_code' => '+1664',
				),
				'MA' => array(
					'name'      => 'Morocco',
					'dial_code' => '+212',
				),
				'MM' => array(
					'name'      => 'Myanmar',
					'dial_code' => '+95',
				),
				'NA' => array(
					'name'      => 'Namibia',
					'dial_code' => '+264',
				),
				'NR' => array(
					'name'      => 'Nauru',
					'dial_code' => '+674',
				),
				'NP' => array(
					'name'      => 'Nepal',
					'dial_code' => '+977',
				),
				'NL' => array(
					'name'      => 'Netherlands',
					'dial_code' => '+31',
				),
				'AN' => array(
					'name'      => 'Netherlands Antilles',
					'dial_code' => '+599',
				),
				'NC' => array(
					'name'      => 'New Caledonia',
					'dial_code' => '+687',
				),
				'NZ' => array(
					'name'      => 'New Zealand',
					'dial_code' => '+64',
				),
				'NI' => array(
					'name'      => 'Nicaragua',
					'dial_code' => '+505',
				),
				'NE' => array(
					'name'      => 'Niger',
					'dial_code' => '+227',
				),
				'NG' => array(
					'name'      => 'Nigeria',
					'dial_code' => '+234',
				),
				'NU' => array(
					'name'      => 'Niue',
					'dial_code' => '+683',
				),
				'NF' => array(
					'name'      => 'Norfolk Island',
					'dial_code' => '+672',
				),
				'MP' => array(
					'name'      => 'Northern Mariana Islands',
					'dial_code' => '+1670',
				),
				'NO' => array(
					'name'      => 'Norway',
					'dial_code' => '+47',
				),
				'OM' => array(
					'name'      => 'Oman',
					'dial_code' => '+968',
				),
				'PK' => array(
					'name'      => 'Pakistan',
					'dial_code' => '+92',
				),
				'PW' => array(
					'name'      => 'Palau',
					'dial_code' => '+680',
				),
				'PA' => array(
					'name'      => 'Panama',
					'dial_code' => '+507',
				),
				'PG' => array(
					'name'      => 'Papua New Guinea',
					'dial_code' => '+675',
				),
				'PY' => array(
					'name'      => 'Paraguay',
					'dial_code' => '+595',
				),
				'PE' => array(
					'name'      => 'Peru',
					'dial_code' => '+51',
				),
				'PH' => array(
					'name'      => 'Philippines',
					'dial_code' => '+63',
				),
				'PL' => array(
					'name'      => 'Poland',
					'dial_code' => '+48',
				),
				'PT' => array(
					'name'      => 'Portugal',
					'dial_code' => '+351',
				),
				'PR' => array(
					'name'      => 'Puerto Rico',
					'dial_code' => '+1939',
				),
				'QA' => array(
					'name'      => 'Qatar',
					'dial_code' => '+974',
				),
				'RO' => array(
					'name'      => 'Romania',
					'dial_code' => '+40',
				),
				'RW' => array(
					'name'      => 'Rwanda',
					'dial_code' => '+250',
				),
				'WS' => array(
					'name'      => 'Samoa',
					'dial_code' => '+685',
				),
				'SM' => array(
					'name'      => 'San Marino',
					'dial_code' => '+378',
				),
				'SA' => array(
					'name'      => 'Saudi Arabia',
					'dial_code' => '+966',
				),
				'SN' => array(
					'name'      => 'Senegal',
					'dial_code' => '+221',
				),
				'RS' => array(
					'name'      => 'Serbia',
					'dial_code' => '+381',
				),
				'SC' => array(
					'name'      => 'Seychelles',
					'dial_code' => '+248',
				),
				'SL' => array(
					'name'      => 'Sierra Leone',
					'dial_code' => '+232',
				),
				'SG' => array(
					'name'      => 'Singapore',
					'dial_code' => '+65',
				),
				'SK' => array(
					'name'      => 'Slovakia',
					'dial_code' => '+421',
				),
				'SI' => array(
					'name'      => 'Slovenia',
					'dial_code' => '+386',
				),
				'SB' => array(
					'name'      => 'Solomon Islands',
					'dial_code' => '+677',
				),
				'ZA' => array(
					'name'      => 'South Africa',
					'dial_code' => '+27',
				),
				'GS' => array(
					'name'      => 'South Georgia and the South Sandwich Islands',
					'dial_code' => '+500',
				),
				'ES' => array(
					'name'      => 'Spain',
					'dial_code' => '+34',
				),
				'LK' => array(
					'name'      => 'Sri Lanka',
					'dial_code' => '+94',
				),
				'SD' => array(
					'name'      => 'Sudan',
					'dial_code' => '+249',
				),
				'SR' => array(
					'name'      => 'Suriname',
					'dial_code' => '+597',
				),
				'SZ' => array(
					'name'      => 'Swaziland',
					'dial_code' => '+268',
				),
				'SE' => array(
					'name'      => 'Sweden',
					'dial_code' => '+46',
				),
				'CH' => array(
					'name'      => 'Switzerland',
					'dial_code' => '+41',
				),
				'TJ' => array(
					'name'      => 'Tajikistan',
					'dial_code' => '+992',
				),
				'TH' => array(
					'name'      => 'Thailand',
					'dial_code' => '+66',
				),
				'TG' => array(
					'name'      => 'Togo',
					'dial_code' => '+228',
				),
				'TK' => array(
					'name'      => 'Tokelau',
					'dial_code' => '+690',
				),
				'TO' => array(
					'name'      => 'Tonga',
					'dial_code' => '+676',
				),
				'TT' => array(
					'name'      => 'Trinidad and Tobago',
					'dial_code' => '+1868',
				),
				'TN' => array(
					'name'      => 'Tunisia',
					'dial_code' => '+216',
				),
				'TR' => array(
					'name'      => 'Turkey',
					'dial_code' => '+90',
				),
				'TM' => array(
					'name'      => 'Turkmenistan',
					'dial_code' => '+993',
				),
				'TC' => array(
					'name'      => 'Turks and Caicos Islands',
					'dial_code' => '+1649',
				),
				'TV' => array(
					'name'      => 'Tuvalu',
					'dial_code' => '+688',
				),
				'UG' => array(
					'name'      => 'Uganda',
					'dial_code' => '+256',
				),
				'UA' => array(
					'name'      => 'Ukraine',
					'dial_code' => '+380',
				),
				'AE' => array(
					'name'      => 'United Arab Emirates',
					'dial_code' => '+971',
				),
				'GB' => array(
					'name'      => 'United Kingdom',
					'dial_code' => '+44',
				),
				'US' => array(
					'name'      => 'United States',
					'dial_code' => '+1',
				),
				'UY' => array(
					'name'      => 'Uruguay',
					'dial_code' => '+598',
				),
				'UZ' => array(
					'name'      => 'Uzbekistan',
					'dial_code' => '+998',
				),
				'VU' => array(
					'name'      => 'Vanuatu',
					'dial_code' => '+678',
				),
				'WF' => array(
					'name'      => 'Wallis and Futuna',
					'dial_code' => '+681',
				),
				'YE' => array(
					'name'      => 'Yemen',
					'dial_code' => '+967',
				),
				'ZM' => array(
					'name'      => 'Zambia',
					'dial_code' => '+260',
				),
				'ZW' => array(
					'name'      => 'Zimbabwe',
					'dial_code' => '+263',
				),
				'BO' => array(
					'name'      => 'Bolivia, Plurinational State of',
					'dial_code' => '+591',
				),
				'BN' => array(
					'name'      => 'Brunei Darussalam',
					'dial_code' => '+673',
				),
				'CC' => array(
					'name'      => 'Cocos (Keeling) Islands',
					'dial_code' => '+61',
				),
				'CD' => array(
					'name'      => 'Congo, The Democratic Republic of the',
					'dial_code' => '+243',
				),
				'CI' => array(
					'name'      => 'Cote dIvoire',
					'dial_code' => '+225',
				),
				'FK' => array(
					'name'      => 'Falkland Islands (Malvinas)',
					'dial_code' => '+500',
				),
				'GG' => array(
					'name'      => 'Guernsey',
					'dial_code' => '+44',
				),
				'VA' => array(
					'name'      => 'Holy See (Vatican City State)',
					'dial_code' => '+379',
				),
				'HK' => array(
					'name'      => 'Hong Kong',
					'dial_code' => '+852',
				),
				'IR' => array(
					'name'      => 'Iran, Islamic Republic of',
					'dial_code' => '+98',
				),
				'IM' => array(
					'name'      => 'Isle of Man',
					'dial_code' => '+44',
				),
				'JE' => array(
					'name'      => 'Jersey',
					'dial_code' => '+44',
				),
				'KP' => array(
					'name'      => 'Korea, Democratic Peoples Republic of',
					'dial_code' => '+850',
				),
				'KR' => array(
					'name'      => 'Korea, Republic of',
					'dial_code' => '+82',
				),
				'LA' => array(
					'name'      => 'Lao Peoples Democratic Republic',
					'dial_code' => '+856',
				),
				'LY' => array(
					'name'      => 'Libyan Arab Jamahiriya',
					'dial_code' => '+218',
				),
				'MO' => array(
					'name'      => 'Macao',
					'dial_code' => '+853',
				),
				'MK' => array(
					'name'      => 'Macedonia, The Former Yugoslav Republic of',
					'dial_code' => '+389',
				),
				'FM' => array(
					'name'      => 'Micronesia, Federated States of',
					'dial_code' => '+691',
				),
				'MD' => array(
					'name'      => 'Moldova, Republic of',
					'dial_code' => '+373',
				),
				'MZ' => array(
					'name'      => 'Mozambique',
					'dial_code' => '+258',
				),
				'PS' => array(
					'name'      => 'Palestinian Territory, Occupied',
					'dial_code' => '+970',
				),
				'PN' => array(
					'name'      => 'Pitcairn',
					'dial_code' => '+872',
				),
				'RE' => array(
					'name'      => 'Réunion',
					'dial_code' => '+262',
				),
				'RU' => array(
					'name'      => 'Russia',
					'dial_code' => '+7',
				),
				'BL' => array(
					'name'      => 'Saint Barthélemy',
					'dial_code' => '+590',
				),
				'SH' => array(
					'name'      => 'Saint Helena, Ascension and Tristan Da Cunha',
					'dial_code' => '+290',
				),
				'KN' => array(
					'name'      => 'Saint Kitts and Nevis',
					'dial_code' => '+1 869',
				),
				'LC' => array(
					'name'      => 'Saint Lucia',
					'dial_code' => '+1758',
				),
				'MF' => array(
					'name'      => 'Saint Martin',
					'dial_code' => '+590',
				),
				'PM' => array(
					'name'      => 'Saint Pierre and Miquelon',
					'dial_code' => '+508',
				),
				'VC' => array(
					'name'      => 'Saint Vincent and the Grenadines',
					'dial_code' => '+1784',
				),
				'ST' => array(
					'name'      => 'Sao Tome and Principe',
					'dial_code' => '+239',
				),
				'SO' => array(
					'name'      => 'Somalia',
					'dial_code' => '+252',
				),
				'SJ' => array(
					'name'      => 'Svalbard and Jan Mayen',
					'dial_code' => '+47',
				),
				'SY' => array(
					'name'      => 'Syrian Arab Republic',
					'dial_code' => '+963',
				),
				'TW' => array(
					'name'      => 'Taiwan, Province of China',
					'dial_code' => '+886',
				),
				'TZ' => array(
					'name'      => 'Tanzania, United Republic of',
					'dial_code' => '+255',
				),
				'TL' => array(
					'name'      => 'Timor-Leste',
					'dial_code' => '+670',
				),
				'VE' => array(
					'name'      => 'Venezuela, Bolivarian Republic of',
					'dial_code' => '+58',
				),
				'VN' => array(
					'name'      => 'Viet Nam',
					'dial_code' => '+84',
				),
				'VG' => array(
					'name'      => 'Virgin Islands, British',
					'dial_code' => '+1284',
				),
				'VI' => array(
					'name'      => 'Virgin Islands, U.S.',
					'dial_code' => '+1340',
				),
			);
		}

		/**
		 * Display notices in Admin related to new features released along with appropriate links
		 *
		 * @since 7.10.0
		 */
		public static function wcap_admin_promotions() {

			if ( isset( $_GET['page'] ) && 'woocommerce_ac_page' === $_GET['page'] ) {

				if ( ! get_option( 'wcap_atc_rule_notice_dismiss', false ) ) {
					$post_link  = '<a href="https://www.tychesoftwares.com/add-to-cart-popup-templates/?utm_source=AcProNotice&utm_medium=link&utm_campaign=AbandonCartPro" target="_blank">here</a>';
					$setup_link = '<a href="admin.php?page=woocommerce_ac_page&action=emailsettings&wcap_section=wcap_atc_settings" target="_blank">setup</a>';
					?>
						<div id='wcap_atc_rule_notice' class='is-dismissible notice notice-info wcap-cron-notice'>
							<p><?php _e( "The <b>Abandoned Cart Pro for WooCommerce</b> now allows you to $setup_link multiple Add to cart Popup Templates. For further details, please visit $post_link.", 'woocommerce-ac' ); ?></p>
						</div>
					<?php
				}
			}
		}

		/**
		 * Mark the admin notice as dismissed.
		 *
		 * @since 8.6
		 */
		public static function wcap_dismiss_admin_notice() {

		    check_ajax_referer( 'wcap_settings_nonce', 'security' );

		    if ( ! current_user_can( 'manage_options' ) ) {
		        wp_send_json_error( 'Unauthorized', 403 );
		        wp_die();
		    }

		    $option_key = isset( $_POST['notice'] ) ? sanitize_key( wp_unslash( $_POST['notice'] ) ) : '';

		    if ( 'wcap_auto_login_notice_dismiss' !== $option_key ) {
		        wp_send_json_error( 'Invalid notice key', 400 );
		        wp_die();
		    }

		    update_option( 'wcap_auto_login_notice_dismiss', 'yes' );
		    wp_send_json_success();
		    wp_die();
		}

		public static function wcap_hide_notices() {
			// Nonce + capability check
		    check_ajax_referer( 'wcap_settings_nonce', 'security' );
		    if ( ! current_user_can( 'manage_options' ) ) {
		        wp_send_json_error( 'Unauthorized', 403 );
		        wp_die();
		    }

			if ( isset( $_POST['wcap_notice_dissmissed'] ) && $_POST['wcap_notice_dissmissed'] == true ) {
				update_option( 'wcap_notice_dissmissed', 'yes' );
				wp_send_json_success();
			}
			wp_die();
		}

		public static function wcap_get_wc_address() {

			$countries = new WC_Countries();

			$address         = $countries->get_base_address();
			$address_2       = $countries->get_base_address_2();
			$city            = $countries->get_base_city();
			$state           = $countries->get_base_state();
			$country         = $countries->get_base_country();
			$postcode        = $countries->get_base_postcode();
			$country_display = '';
			$state_display   = '';

			// Get all countries key/names in an array:
			$countries_array = $countries->get_countries();

			// Get all country states key/names in a multilevel array:
			$country_states_array = $countries->get_states();

			if ( isset( $country ) && $country != '' &&
				 isset( $countries_array[ $country ] ) && $countries_array[ $country ] != '' ) {

				$country_display = $countries_array[ $country ];
			}

			if ( isset( $state ) && $state != '' &&
				 isset( $country_states_array[ $country ] ) && isset( $country_states_array[ $country ][ $state ] ) ) {

				$country_display = $country_states_array[ $country ][ $state ];
			}

			return $address . ' ' . $address_2 . ' ' . $city . ' ' . $state . ' ' . $postcode;
		}

		/**
		 * Display Date Filter
		 *
		 * @since 8.4.0
		 */
		public static function wcap_display_date_filter( $name ) {
			global $start_end_dates;
			$duration_range   = '';
			$input_name       = "duration_select_$name";
			$input_start_date = "start_date_$name";
			$input_end_date   = "end_date_$name";
			$duration_range_select = array(
				'yesterday'      => __( 'Yesterday', 'woocommerce-ac' ),
				'today'          => __( 'Today', 'woocommerce-ac' ),
				'last_seven'     => __( 'Last 7 days', 'woocommerce-ac' ),
				'last_fifteen'   => __( 'Last 15 days', 'woocommerce-ac' ),
				'last_thirty'    => __( 'Last 30 days', 'woocommerce-ac' ),
				'last_ninety'    => __( 'Last 90 days', 'woocommerce-ac' ),
				'last_year_days' => __( 'Last 365', 'woocommerce-ac' ),
			);

			if ( isset( $_POST[ $input_name ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$duration_range        = sanitize_text_field( wp_unslash( $_POST[ $input_name ] ) ); // phpcs:ignore WordPress.Security.NonceVerification
				$_SESSION ['duration'] = $duration_range;
			}

			if ( '' === $duration_range ) {
				if ( isset( $_GET[ $input_name ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					$duration_range        = sanitize_text_field( wp_unslash( $_GET[ $input_name ] ) ); // phpcs:ignore WordPress.Security.NonceVerification
					$_SESSION ['duration'] = $duration_range;
				}
			}

			if ( isset( $_SESSION['duration'] ) ) {
				$duration_range = sanitize_text_field( wp_unslash( $_SESSION['duration'] ) );
			}

			if ( '' === $duration_range ) {
				$duration_range       = 'last_seven';
				$_SESSION['duration'] = $duration_range;
			}

			?>

			<div id="<?php echo esc_attr( $name ); ?>" class="postbox" style="display:block">
				<div class="inside">
					<form method="post" action="admin.php?page=woocommerce_ac_page&action=<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $name ); ?>">
						<select id="<?php echo esc_attr( $input_name ); ?>" name="<?php echo esc_attr( $input_name ); ?>" >
							<?php
							foreach ( $duration_range_select as $key => $value ) {
								$sel = '';
								if ( $key == $duration_range ) { //phpcs:ignore
									$sel = __( ' selected ', 'woocommerce-ac' );
								}
								echo sprintf( "<option value='%s' %s>%s</option>", esc_attr( $key ), esc_attr( $sel ), esc_attr( __( $value, 'woocommerce-ac' ) ) ); //phpcs:ignore
							}

							$date_sett = $start_end_dates[ $duration_range ];
							?>
						</select>

						<?php
						$start_date_range = '';
						if ( isset( $_POST[ $input_start_date ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
							$start_date_range        = sanitize_text_field( wp_unslash( $_POST[ $input_start_date ] ) ); // phpcs:ignore WordPress.Security.NonceVerification
							$_SESSION ['start_date'] = $start_date_range;
						}
						if ( isset( $_SESSION ['start_date'] ) ) {
							$start_date_range = sanitize_text_field( wp_unslash( $_SESSION ['start_date'] ) );
						}
						if ( '' === $start_date_range ) {
							$start_date_range        = $date_sett['start_date'];
							$_SESSION ['start_date'] = $start_date_range;
						}

						$end_date_range = '';
						if ( isset( $_POST[ $input_end_date ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
							$end_date_range        = sanitize_text_field( wp_unslash( $_POST[ $input_end_date ] ) ); // phpcs:ignore WordPress.Security.NonceVerification
							$_SESSION ['end_date'] = $end_date_range;
						}
						if ( isset( $_SESSION['end_date'] ) ) {
							$end_date_range = sanitize_text_field( wp_unslash( $_SESSION ['end_date'] ) );
						}
						if ( '' === $end_date_range ) {
							$end_date_range       = $date_sett['end_date'];
							$_SESSION['end_date'] = $end_date_range;
						}
						?>
						<label class="start_label" for="start_day">
							<?php esc_html_e( 'Start Date:', 'woocommerce-ac' ); ?>
						</label>
						<input type="text" id="<?php echo esc_attr( $input_start_date ); ?>" name="<?php echo esc_attr( $input_start_date ); ?>" readonly="readonly" value="<?php echo esc_attr( $start_date_range ); ?>" />
						<label class="end_label" for="end_day"> <?php esc_html_e( 'End Date:', 'woocommerce-ac' ); ?> </label>
						<input type="text" id="<?php echo esc_attr( $input_end_date ); ?>" name="<?php echo esc_attr( $input_end_date ); ?>" readonly="readonly" value="<?php echo esc_attr( $end_date_range ); ?>" />
						<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e( 'Go', 'woocommerce-ac' ); ?>"  />
					</form>
				</div>
			</div>

			<?php

		}

		/**
		 * Add a scheduled action for the webhok to be delivered once the cart cut off is reached.
		 *
		 * @param int $cart_id - Abandoned Cart ID.
		 * @since 8.7.0
		 */
		public static function wcap_run_webhook_after_cutoff( $cart_id ) {
			// check if the Webhook is present & active.
			global $wpdb;

			$get_webhook_status = $wpdb->get_var( $wpdb->prepare( 'SELECT status FROM `' . $wpdb->prefix . 'wc_webhooks` WHERE topic = %s', 'wcap_cart.cutoff' ) );

			if ( isset( $get_webhook_status ) && 'active' == $get_webhook_status ) {
				// Reconfirm that the cart is either a registered user cart or a guest cart. The webhook will not be run for visitor carts.
				$cart_data = $wpdb->get_results( $wpdb->prepare( 'SELECT user_id, user_type, cart_ignored, recovered_cart FROM ' . WCAP_ABANDONED_CART_HISTORY_TABLE . ' WHERE id = %d', $cart_id ) );

				$user_id   = isset( $cart_data[0]->user_id ) ? $cart_data[0]->user_id : 0;
				$user_type = isset( $cart_data[0]->user_type ) ? $cart_data[0]->user_type : '';

				if ( $user_id > 0 && '' != $user_type && '0' == $cart_data[0]->cart_ignored && $cart_data[0]->recovered_cart <= 0 ) {

					$cut_off = 0;
					if ( 'REGISTERED' == $user_type ) {
						$cut_off = is_numeric( get_option( 'ac_cart_abandoned_time', 10 ) ) ? get_option( 'ac_cart_abandoned_time', 10 ) * 60 : 10 * 60;
					} elseif ( 'GUEST' == $user_type ) {
						$cut_off = is_numeric( get_option( 'ac_cart_abandoned_time_guest', 10 ) ) ? get_option( 'ac_cart_abandoned_time_guest', 10 ) * 60 : 10 * 60;
					}

					if ( $cut_off > 0 ) {
						// run the hook
						as_schedule_single_action( time() + $cut_off, 'wcap_webhook_after_cutoff', array( 'id' => $cart_id ) );
					}
				}
			}
			do_action( 'wcap_webhook_initiated', $cart_id );
		}

		/**
		 * Update Checkout Link in cart history table.
		 *
		 * @param int $cart_id - Cart ID.
		 * @since 8.7.0
		 */
		public static function wcap_add_checkout_link( $cart_id ) {

			if ( $cart_id > 0 ) {

				global $wpdb;
				if ( version_compare( WOOCOMMERCE_VERSION, '2.3' ) < 0 ) {
					global $woocommerce;
					$checkout_page_link = $woocommerce->cart->get_checkout_url();
				} else {
					$checkout_page_id   = wc_get_page_id( 'checkout' );
					$checkout_page_link = $checkout_page_id ? get_permalink( $checkout_page_id ) : '';
				}

				if ( function_exists( 'icl_register_string' ) ) {
					$cart_language      = $wpdb->get_var( $wpdb->prepare( 'SELECT language FROM ' . WCAP_ABANDONED_CART_HISTORY_TABLE . ' WHERE id = %d', $cart_id ) );
					$checkout_page_link = apply_filters( 'wpml_permalink', $checkout_page_link, $cart_language );
				}
				// Force SSL if needed.
				$ssl_is_used = is_ssl() ? true : false;

				if ( true === $ssl_is_used || 'yes' === get_option( 'woocommerce_force_ssl_checkout' ) ) {
					$checkout_page_link = str_ireplace( 'http:', 'https:', $checkout_page_link );
				}

				$common_inst  = Wcap_Connectors_Common::get_instance();
				$user_details = $common_inst->wcap_get_contact_data( $cart_id );
				$user_email   = is_array( $user_details ) && count( $user_details ) > 0 && isset( $user_details['email'] );

				if ( '' !== $user_email ) {
					$crypt_key         = wcap_get_crypt_key( $user_email, true, $cart_id );
					$encoding_checkout = $cart_id . '&url=' . $checkout_page_link;
					$validate_checkout = self::encrypt_validate( $encoding_checkout, $crypt_key );

					$checkout_link = get_option( 'siteurl' ) . '/?wacp_action=checkout_link&user_email=' . $user_email . '&validate=' . $validate_checkout;

					WCAP_CART_HISTORY_MODEL::update( array( 'checkout_link' => $checkout_link ), array( 'id' => $cart_id ) );
				}
			}

		}

		/**
		 * Get the post meta data for AC Coupons.
		 *
		 * @param int $cart_id - Abandoned Cart ID.
		 * @return array $return_coupons Return Coupon data.
		 * @since 8.8.1
		 */
		public static function wcap_get_coupon_post_meta( $cart_id ) {

			// Fetch the record from the DB.
			$get_coupons = get_post_meta( $cart_id, '_woocommerce_ac_coupon', true );

			// Create a return array.
			$return_coupons = array();

			// If any coupon have been applied, populate them in the return array.
			if ( is_array( $get_coupons ) && count( $get_coupons ) > 0 ) {
				foreach ( $get_coupons as $coupon_data ) {
					$coupon_msg  = '';
					$coupon_code = '';
					if ( isset( $coupon_data['coupon_code'] ) && is_string( $coupon_data['coupon_code'] ) && '' !== $coupon_data['coupon_code'] ) {
						$coupon_code = $coupon_data['coupon_code'];
					}
					if ( isset( $coupon_data['coupon_message'] ) && is_string( $coupon_data['coupon_message'] ) && '' !== $coupon_data['coupon_message'] ) {
						$coupon_msg = $coupon_data['coupon_message'];
					}

					if ( '' !== $coupon_code && ! in_array( $coupon_code, $return_coupons, true ) ) {
						$return_coupons[ $coupon_code ] = $coupon_msg;
					}
				}
			}
			return $return_coupons;
		}

		/**
		 * Update the Coupon data in post meta table.
		 *
		 * @param int    $cart_id - Abandoned Cart ID.
		 * @param string $coupon_code - Coupon code to be updated.
		 * @param string $msg - Msg to be added for the coupon.
		 * @since 8.8.1
		 */
		public static function wcap_update_coupon_post_meta( $cart_id, $coupon_code, $msg = '' ) {

			// Set default.
			$msg = '' !== $msg ? $msg : __( 'Discount code applied successfully.', 'woocommerce-ac' );
			// Fetch the record from the DB.
			$get_coupons = get_post_meta( $cart_id, '_woocommerce_ac_coupon', true );

			// Create a return array.
			$return_coupons = array();

			// If any coupon have been applied, populate them in the return array.
			if ( is_array( $get_coupons ) && count( $get_coupons ) > 0 ) {
				$exists = false;
				foreach ( $get_coupons as $coupon_data ) {
					if ( isset( $coupon_data['coupon_code'] ) && $coupon_code === $coupon_data['coupon_code'] ) {
						$exists = true;
					}
				}

				if ( ! $exists ) {
					$get_coupons[] = array(
						'coupon_code'    => $coupon_code,
						'coupon_message' => $msg,
					);
					update_post_meta( $cart_id, '_woocommerce_ac_coupon', $get_coupons );
					return true;
				}
			} else {
				$get_coupons   = array();
				$get_coupons[] = array(
					'coupon_code'    => $coupon_code,
					'coupon_message' => $msg,
				);
				update_post_meta( $cart_id, '_woocommerce_ac_coupon', $get_coupons );
				return true;
			}
			return false;
		}

		/**
		 * Return filter start & end dares based on duration in d-m-Y format.
		 *
		 * @param string $duration_range - Duration Range.
		 * @return array - Start & End Dates.
		 *
		 * @since 8.13.0
		 */
		public static function wcap_get_dates_from_range( $duration_range = '' ) {
			$duration_range = '' === $duration_range ? 'last_seven' : $duration_range;
			switch ( $duration_range ) {
				case 'yesterday':
					$dates = array(
						'start_date' => date( 'd-m-Y', ( current_time( 'timestamp' ) - 24 * 60 * 60 ) ),
						'end_date'   => date( 'd-m-Y', ( current_time( 'timestamp' ) - 7 * 24 * 60 * 60 ) ),
					);
					break;
				case 'today':
					$dates = array(
						'start_date' => date( 'd-m-Y', current_time( 'timestamp' ) ),
						'end_date'   => date( 'd-m-Y', current_time( 'timestamp' ) ),
					);
					break;
				case 'last_seven':
				default:
					$dates = array(
						'start_date' => date( 'd-m-Y', ( current_time( 'timestamp' ) - 7 * 24 * 60 * 60 ) ),
						'end_date'   => date( 'd-m-Y', current_time( 'timestamp' ) ),
					);
					break;
				case 'last_fifteen':
					$dates = array(
						'start_date' => date( 'd-m-Y', ( current_time( 'timestamp' ) - 15 * 24 * 60 * 60 ) ),
						'end_date'   => date( 'd-m-Y', current_time( 'timestamp' ) ),
					);
					break;
				case 'last_thirty':
					$dates = array(
						'start_date' => date( 'd-m-Y', ( current_time( 'timestamp' ) - 30 * 24 * 60 * 60 ) ),
						'end_date'   => date( 'd-m-Y', current_time( 'timestamp' ) ),
					);
					break;
				case 'last_ninety':
					$dates = array(
						'start_date' => date( 'd-m-Y', ( current_time( 'timestamp' ) - 90 * 24 * 60 * 60 ) ),
						'end_date'   => date( 'd-m-Y', current_time( 'timestamp' ) ),
					);
					break;
				case 'last_year_days':
					$dates = array(
						'start_date' => date( 'd-m-Y', ( current_time( 'timestamp' ) - 365 * 24 * 60 * 60 ) ),
						'end_date'   => date( 'd-m-Y', current_time( 'timestamp' ) ),
					);
					break;
			}
			return $dates;
		}

		/**
		 * Create WC order for cart.
		 *
		 * @param int $cart_id - Abandoned Cart ID.
		 * @return int $order_id - WC Order ID.
		 *
		 * @since 8.19.0
		 */
		public static function wcap_create_wc_order_for_cart( $cart_id ) {
			if ( $cart_id > 0 ) {
				// Fetch the cart content.
				$cart_data = wcap_get_data_cart_history( $cart_id );
				// Collect user information.
				if ( $cart_data ) {
					$product_details = wcap_get_product_details( $cart_data->abandoned_cart_info );
					if ( count( $product_details ) > 0 ) { // Not an empty cart.
						$user_details = array();
						$user_id      = $cart_data->user_id;
						if ( $user_id >= '63000000' ) { // Guest cart.
							$guest_details = wcap_get_data_guest_history( $user_id );
							if ( $guest_details ) {
								$user_details = array(
									'email'      => $guest_details->email_id,
									'first_name' => $guest_details->billing_first_name,
									'last_name'  => $guest_details->billing_last_name,
									'phone'      => $guest_details->phone,
									'country'    => $guest_details->billing_country,
								);
							}
						} elseif ( $user_id > 0 ) { // Registered User.
							$user_details = array(
								'email'      => get_user_meta( $user_id, 'billing_email', true ),
								'first_name' => get_user_meta( $user_id, 'billing_first_name', true ),
								'last_name'  => get_user_meta( $user_id, 'billing_last_name', true ),
								'phone'      => get_user_meta( $user_id, 'billing_phone', true ),
								'country'    => get_user_meta( $user_id, 'billing_country', true ),
							);
						}
						// Create WC Order.
						$order = wc_create_order();
						// Add Products.
						foreach ( $product_details as $products ) {
							$order->add_product( wc_get_product( $products->product_id, $products->quantity ) );
						}
						// Add User Details.
						if ( count( $user_details ) > 0 ) {
							$order->set_address( $user_details, 'billing' );
						}
						// Calculate Order Totals.
						$order->calculate_totals();
						// Fetch the order ID.
						$order_id = $order->get_id();
						return $order_id;
					}
				}
				return false;
			}
		}

		/**
		 * Gets country code from the user IP address.
		 *
		 * @param string $ip - user IP address.
		 * @param string $purpose - purpose of what value should function return.
		 * @return int $order_id - WC Order ID.
		 *
		 * @since 8.20.0
		 */
		public static function wcap_get_ip_info( $ip = null, $purpose = 'country' ) {
			$output = null;
			if ( false === filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				return;
			}

			$purpose = str_replace( array( 'name', "\n", "\t", ' ', '-', '_' ), '', strtolower( trim( $purpose ) ) );
			$support = array( 'country', 'countrycode', 'state', 'region', 'city' );

			if ( filter_var( $ip, FILTER_VALIDATE_IP ) && in_array( $purpose, $support ) ) {
				$ipdat = @json_decode( file_get_contents( 'http://www.geoplugin.net/json.gp?ip=' . $ip ) );
				if ( 2 === @strlen( trim( $ipdat->geoplugin_countryCode ) ) ) {
					switch ( $purpose ) {
						case 'city':
							$output = @$ipdat->geoplugin_city;
							break;
						case 'state':
							$output = @$ipdat->geoplugin_regionName;
							break;
						case 'region':
							$output = @$ipdat->geoplugin_regionName;
							break;
						case 'country':
							$output = @$ipdat->geoplugin_countryName;
							break;
						case 'countrycode':
							$output = @$ipdat->geoplugin_countryCode;
							break;
					}
				}
			}
			return $output;
		}

		/**
		 * Option for deleting the plugin data upon uninstall.
		 *
		 * @param array $args Argument for adding field details.
		 * @since 8.3
		 */
		public static function wcap_deleting_coupon_data( $args ) {
			$wcap_delete_coupon_data = get_option( 'wcap_delete_coupon_data', '' );
			if ( isset( $wcap_delete_coupon_data ) && '' === $wcap_delete_coupon_data ) {
				$wcap_delete_coupon_data = 'off';
				wp_clear_scheduled_hook( 'woocommerce_ac_delete_coupon_action' );
			} else {
				if ( ! wp_next_scheduled( 'woocommerce_ac_delete_coupon_action' ) ) {
					wp_schedule_event( time(), 'wcap_15_days', 'woocommerce_ac_delete_coupon_action' );
				}
			}
		}

		/**
		 * Add/Remove the scheduled action based on the setting.
		 *
		 * @param $old_value - Old Value of the setting.
		 * @param $new_value - New Value of the setting.
		 *
		 * @since 8.7.0
		 */
		public static function wcap_use_auto_cron( $old_value, $new_value ) {

			// Now if there's an action scheduled, it needs to be updated with the new frequency
			if ( false !== as_next_scheduled_action( 'woocommerce_ac_send_email_action' ) && '' == $new_value ) {
				as_unschedule_action( 'woocommerce_ac_send_email_action' );
			} elseif ( false === as_next_scheduled_action( 'woocommerce_ac_send_email_action' ) && 'on' == $new_value ) {
				$cron_interval = intval( get_option( 'wcap_cron_time_duration', 15 ) ) * 60;
				as_schedule_recurring_action( time(), $cron_interval, 'woocommerce_ac_send_email_action' );
			}
		}

		/**
		 * Update the Schedule Action frequency when the same is updated in the settings.
		 *
		 * @since 8.6
		 */
		public static function wcap_update_cron_interval( $old_value, $new_value ) {

			// Now if there's an action scheduled, it needs to be updated with the new frequency
			if ( false !== as_next_scheduled_action( 'woocommerce_ac_send_email_action' ) ) {
				as_unschedule_action( 'woocommerce_ac_send_email_action' );

				$new_value = $new_value > 0 ? intval( $new_value ) : 0;
				if ( is_integer( $new_value ) && 0 < $new_value ) {
					$new_interval = $new_value * 60;
					as_schedule_recurring_action( time(), $new_interval, 'woocommerce_ac_send_email_action' );
				}
			}
		}



		/**
		 * Get all popup Templates
		 *
		 * @return array $data - data of popup templates.
		 *
		 * @since 8.21.0
		 */
		public static function wcap_get_popup_templates( $return = false ) {
			if ( ! $return ) {
		        check_ajax_referer( 'wcap_settings_nonce', 'security' );
		        if ( ! current_user_can( 'manage_options' ) ) {
		            wp_send_json_error( 'Unauthorized', 403 );
		            wp_die();
		        }
    		}
			require_once 'classes/class-wcap-atc-templates-table.php';
			$wcap_atc_template_list = new Wcap_ATC_Templates_Table();
			$data                   = $wcap_atc_template_list->wcap_templates_prepare_items();
			if ( $return ) {
				return $data;
			}
			wp_send_json( $data );
		}

		/**
		 * Get all email Templates
		 *
		 * @return array $data - data of email templates.
		 *
		 * @since 8.21.0
		 */
		public static function wcap_get_email_templates( $return = false ) {
			if ( ! $return ) {
		        check_ajax_referer( 'wcap_settings_nonce', 'security' );
		        if ( ! current_user_can( 'manage_options' ) ) {
		            wp_send_json_error( 'Unauthorized', 403 );
		            wp_die();
		        }
		    }

			require_once 'classes/class_wcap_templates_table.php';
			$wcap_template_list = new Wcap_Templates_Table();
			$data               = $wcap_template_list->wcap_templates_prepare_items();
			if ( $return ) {
				return $data;
			}
			wp_send_json( $data );
		}

		/**
		 * Get all SMS Templates
		 *
		 * @return array $data - data of sms templates.
		 *
		 * @since 8.21.0
		 */
		public static function wcap_get_sms_templates( $return = false ) {
			if ( ! $return ) {
		        check_ajax_referer( 'wcap_settings_nonce', 'security' );
		        if ( ! current_user_can( 'manage_options' ) ) {
		            wp_send_json_error( 'Unauthorized', 403 );
		            wp_die();
		        }
		    }

			require_once 'classes/class_wcap_sms_templates_table.php';
			$wcap_template_list = new Wcap_SMS_Templates();
			$data               = $wcap_template_list->wcap_sms_templates_prepare_items();
			if ( $return ) {
				return $data;
			}
			wp_send_json( $data );
		}

		/**
		 * Get all SMS Templates
		 *
		 * @return array $data - data of sms templates.
		 *
		 * @since 8.21.0
		 */
		public static function wcap_get_whatsapp_templates( $return = false ) {
			if ( ! $return ) {
		        check_ajax_referer( 'wcap_settings_nonce', 'security' );
		        if ( ! current_user_can( 'manage_options' ) ) {
		            wp_send_json_error( 'Unauthorized', 403 );
		            wp_die();
		        }
		    }

			require_once 'classes/class_wcap_whatsapp_templates_table.php';
			$wcap_template_list = new Wcap_WhatsApp_Templates();
			$data               = $wcap_template_list->wcap_whatsapp_templates_prepare_items();
			if ( $return ) {
				return $data;
			}
			wp_send_json( $data );
		}

		/**
		 * Get all FB Templates
		 *
		 * @return array $data - data of Fb templates.
		 *
		 * @since 8.21.0
		 */
		public static function wcap_get_fb_templates( $return = false ) {
			if ( ! $return ) {
		        check_ajax_referer( 'wcap_settings_nonce', 'security' );
		        if ( ! current_user_can( 'manage_options' ) ) {
		            wp_send_json_error( 'Unauthorized', 403 );
		            wp_die();
		        }
		    }

			require_once 'fb-recovery/admin/wcap_fb_templates_list.php';
			$fb_list = new WCAP_FB_Templates_List();
			$data    = $fb_list->wcap_fb_templates_prepare_items();
			if ( $return ) {
				return $data;
			}
			wp_send_json( $data );
		}

		/**
		 * Get all Abandoned Orders
		 *
		 * @return array $data - data of Abandoned Orders.
		 *
		 * @since 8.21.0
		 */
		public static function wcap_get_abandoned_orders( $return = false ) {
			if ( ! $return ) {
		        check_ajax_referer( 'wcap_settings_nonce', 'security' );
		        if ( ! current_user_can( 'manage_options' ) ) {
		            wp_send_json_error( 'Unauthorized', 403 );
		            wp_die();
		        }
		    }

			self::wcap_set_filter_data();
			if ( isset( $_POST['wcap_section'] ) ) {
				$_GET['wcap_section'] = sanitize_text_field( wp_unslash( $_POST['wcap_section'] ) );
			}
			if ( isset( $_POST['paged'] ) ) {
				$_GET['paged'] = sanitize_text_field( wp_unslash( $_POST['paged'] ) );
			}

			if ( ( isset( $_GET['wcap_section'] ) && $_GET['wcap_section'] !== 'wcap_trash_abandoned' ) || ! isset( $_GET['wcap_section'] ) ) {
				require_once 'classes/class_wcap_abandoned_orders_table.php';
				$wcap_abandoned_order_list = new Wcap_Abandoned_Orders_Table();
				$data                      = $wcap_abandoned_order_list->wcap_abandoned_order_prepare_items();
			} else {
				require_once 'classes/class_wcap_abandoned_trash_orders_table.php';
				$wcap_abandoned_order_list = new Wcap_Abandoned_Trash_Orders_Table();
				$data                      = $wcap_abandoned_order_list->wcap_abandoned_order_prepare_items();
			}

			if ( $return ) {
				return $data;
			}
			wp_send_json( $data );
		}
		/**
		 * set filter data for Abandoned Orders
		 *
		 * @since 8.21.0
		 */
		public static function wcap_set_filter_data() {

			if ( session_id() === '' ) {
				// session has not started
				session_start();
			}

			if ( isset( $_POST['start_date'] ) && '' !== $_POST['start_date'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				$start_date_range        = sanitize_text_field( wp_unslash( $_POST['start_date'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
				$_SESSION ['start_date'] = $start_date_range;
			}

			if ( isset( $_POST['end_date'] ) && '' !== $_POST['end_date'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				$end_date_range        = sanitize_text_field( wp_unslash( $_POST['end_date'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
				$_SESSION ['end_date'] = $end_date_range;
			}
			if ( isset( $_POST['cart_status'] ) && '' !== $_POST['cart_status'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				$filtered_status         = sanitize_text_field( wp_unslash( $_POST['cart_status'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
				$_SESSION['cart_status'] = $filtered_status;
			}
			if ( isset( $_POST['cart_source'] ) && '' !== $_POST['cart_source'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				$filtered_source         = sanitize_text_field( wp_unslash( $_POST['cart_source'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
				$_SESSION['cart_source'] = $filtered_source;
			}
			if ( isset( $_POST['duration_select'] ) && '' !== $_POST['duration_select'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				$duration_range              = sanitize_text_field( wp_unslash( $_POST['duration_select'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
				$_SESSION['duration_select'] = $duration_range;
			}

			if ( isset( $_POST['hidden_start'] ) && '' !== $_POST['hidden_start'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				$hidden_start             = sanitize_text_field( wp_unslash( $_POST['hidden_start'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
				$_SESSION['hidden_start'] = $hidden_start;
			}
			if ( isset( $_POST['hidden_end'] ) && '' !== $_POST['hidden_end'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				$hidden_end             = sanitize_text_field( wp_unslash( $_POST['hidden_end'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
				$_SESSION['hidden_end'] = $hidden_end;
			}

		}
		/**
		 * get filter data for Abandoned Orders
		 *
		 * @since 8.21.0
		 */
		public static function wcap_get_filter_data() {

			self::wcap_clear_filter_session_on_listcart();

			$start_date      = '';
			$end_date        = '';
			$cart_status     = 'all';
			$cart_source     = 'all';
			$duration_select = 'last_seven';
			$hidden_start    = '';
			$hidden_end      = '';
			if ( isset( $_SESSION['start_date'] ) && '' !== $_SESSION['start_date'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				$start_date = sanitize_text_field( wp_unslash( $_SESSION['start_date'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			}

			if ( isset( $_SESSION['end_date'] ) && '' !== $_SESSION['end_date'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				$end_date = sanitize_text_field( wp_unslash( $_SESSION['end_date'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			}
			if ( isset( $_SESSION['cart_status'] ) && '' !== $_SESSION['cart_status'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				$cart_status = sanitize_text_field( wp_unslash( $_SESSION['cart_status'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			}
			if ( isset( $_SESSION['cart_source'] ) && '' !== $_SESSION['cart_source'] ) {
				$cart_source = sanitize_text_field( wp_unslash( $_SESSION['cart_source'] ) );
			}
			if ( isset( $_SESSION['duration_select'] ) && '' !== $_SESSION['duration_select'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				$duration_select = sanitize_text_field( wp_unslash( $_SESSION['duration_select'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			}
			if ( isset( $_SESSION['hidden_start'] ) && '' !== $_SESSION['hidden_start'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				$hidden_start = sanitize_text_field( wp_unslash( $_SESSION['hidden_start'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			}
			if ( isset( $_SESSION['hidden_end'] ) && '' !== $_SESSION['hidden_end'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				$hidden_end = sanitize_text_field( wp_unslash( $_SESSION['hidden_end'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			}
			return array(
				'hidden_start'    => $hidden_start,
				'hidden_end'      => $hidden_end,
				'start_date'      => $start_date,
				'end_date'        => $end_date,
				'cart_status'     => $cart_status,
				'cart_source'     => $cart_source,
				'duration_select' => $duration_select,
			);
		}
		/**
		 * Get all Email reminderss
		 *
		 * @return array $data - data of Abandoned Orders.
		 *
		 * @since 8.21.0
		 */
		public static function wcap_get_email_reminders( $return = false ) {
			if ( ! $return ) {
		        check_ajax_referer( 'wcap_settings_nonce', 'security' );
		        if ( ! current_user_can( 'manage_options' ) ) {
		            wp_send_json_error( 'Unauthorized', 403 );
		            wp_die();
		        }
    		}

			self::wcap_set_filter_data();
			if ( isset( $_POST['paged'] ) ) {
				$_GET['paged'] = sanitize_text_field( wp_unslash( $_POST['paged'] ) );
			}

			require_once 'classes/class_wcap_sent_emails_table.php';
			$wcap_sent_emails_list = new Wcap_Sent_Emails_Table();
			$data                  = $wcap_sent_emails_list->wcap_sent_emails_prepare_items();
			if ( $return ) {
				return $data;
			}
			wp_send_json( $data );
		}

		/**
		 * Get all Abandoned Orders
		 *
		 * @return array $data - data of Abandoned Orders.
		 *
		 * @since 8.21.0
		 */

		public static function wcap_get_sms_reminders( $return = false ) {
			if ( ! $return ) {
		        check_ajax_referer( 'wcap_settings_nonce', 'security' );
		        if ( ! current_user_can( 'manage_options' ) ) {
		            wp_send_json_error( 'Unauthorized', 403 );
		            wp_die();
		        }
    		}
			self::wcap_set_filter_data();
			if ( isset( $_POST['paged'] ) ) {
				$_GET['paged'] = sanitize_text_field( wp_unslash( $_POST['paged'] ) );
			}
			require_once 'classes/class_wcap_sent_sms_table.php';
			$wcap_sent_sms_list = new Wcap_Sent_SMS_Table();
			$data               = $wcap_sent_sms_list->wcap_sent_sms_prepare_items();
			if ( $return ) {
				return $data;
			}
			wp_send_json( $data );
		}

		/**
		 * Get all Abandoned Orders
		 *
		 * @return array $data - data of Abandoned Orders.
		 *
		 * @since 9.19.0
		 */

		 public static function wcap_get_whatsapp_reminders( $return = false ) {
		 	if ( ! $return ) {
		        check_ajax_referer( 'wcap_settings_nonce', 'security' );
		        if ( ! current_user_can( 'manage_options' ) ) {
		            wp_send_json_error( 'Unauthorized', 403 );
		            wp_die();
		        }
    		}
			self::wcap_set_filter_data();
			if ( isset( $_POST['paged'] ) ) {
				$_GET['paged'] = sanitize_text_field( wp_unslash( $_POST['paged'] ) );
			}
			require_once 'classes/class_wcap_sent_whatsapp_table.php';
			$wcap_sent_whatsapp_list = new Wcap_Sent_WhatsApp_Table();
			$data               = $wcap_sent_whatsapp_list->wcap_sent_whatsapp_prepare_items();
			if ( $return ) {
				return $data;
			}
			wp_send_json( $data );
		}

		/**
		 * Get all Abandoned Orders
		 *
		 * @return array $data - data of Abandoned Orders.
		 *
		 * @since 8.21.0
		 */

		public static function wcap_get_product_reports( $return = false ) {
			if ( ! $return ) {
		        check_ajax_referer( 'wcap_settings_nonce', 'security' );
		        if ( ! current_user_can( 'manage_options' ) ) {
		            wp_send_json_error( 'Unauthorized', 403 );
		            wp_die();
		        }
    		}
			require_once 'classes/class_wcap_product_report_table.php';
			$wcap_product_report_list = new Wcap_Product_Report_Table();
			$data                     = $wcap_product_report_list->wcap_product_report_prepare_items();
			if ( $return ) {
				return $data;
			}
			wp_send_json( $data );
		}


		/**
		 * Get all Abandoned Orders
		 *
		 * @return array $data - data of Abandoned Orders.
		 *
		 * @since 8.21.0
		 */

		public static function wcap_delete_template() {
			if ( ! $return ) {
		        check_ajax_referer( 'wcap_settings_nonce', 'security' );
		        if ( ! current_user_can( 'manage_options' ) ) {
		            wp_send_json_error( 'Unauthorized', 403 );
		            wp_die();
		        }
    		}

			$template_type = sanitize_text_field( wp_unslash( $_POST['template_type'] ) );
			$template_ids  = $_POST['template_id'];

			global $wpdb;

			foreach ( $template_ids as $template_id ) {
				$template_id  = sanitize_text_field( wp_unslash( $template_id ) );
				$query_remove = 'DELETE FROM `' . WCAP_NOTIFICATION_TEMPLATES_TABLE . '` WHERE id = %d ';
				$wpdb->query( $wpdb->prepare( $query_remove, $template_id ) );
			}

			switch ( $template_type ) {
				case 'email':
					self::wcap_get_email_templates();
					break;
				case 'sms':
					self::wcap_get_sms_templates();
					break;
				case 'whatsapp':
					self::wcap_get_whatsapp_templates();
					break;
				case 'fb':
					self::wcap_get_fb_templates();
					break;
			}

		}

		/**
		 * Get all Abandoned Orders
		 *
		 * @return array $data - data of Abandoned Orders.
		 *
		 * @since 8.21.0
		 */
		public static function wcap_cart_bulk_action( $return = false ) {
			if ( ! $return ) {
		        check_ajax_referer( 'wcap_settings_nonce', 'security' );
		        if ( ! current_user_can( 'manage_options' ) ) {
		            wp_send_json_error( 'Unauthorized', 403 );
		            wp_die();
		        }
		    }

			$action     = '';
			$action_two = '';
			if ( isset( $_POST['bulk_action'] ) ) {
				$action = sanitize_text_field( wp_unslash( $_POST['bulk_action'] ) );
			}
			require_once 'admin/wcap_actions.php';
			require_once 'admin/wcap_actions_handler.php';

			if ( 'wcap_sync_manually' === $action || 'wcap_abandoned_trash' === $action || 'wcap_abandoned_restore' === $action || 'wcap_abandoned_delete' === $action ) {
				$_GET['abandoned_order_id'] = $_POST['abandoned_order_id'];
			}

			$updated = Wcap_Actions::wcap_perform_action( $action, $action_two );

			if ( $updated ) {

				if ( ! ( 'wcap_sync_manually' === $action || 'wcap_abandoned_restore' === $action || 'wcap_abandoned_delete' === $action ) ) {
					require_once 'classes/class_wcap_abandoned_orders_table.php';
					$wcap_abandoned_order_list = new Wcap_Abandoned_Orders_Table();
					$data                      = $wcap_abandoned_order_list->wcap_abandoned_order_prepare_items();
				} else {
					require_once 'classes/class_wcap_abandoned_trash_orders_table.php';
					$wcap_abandoned_order_list = new Wcap_Abandoned_Trash_Orders_Table();
					$data                      = $wcap_abandoned_order_list->wcap_abandoned_order_prepare_items();
				}
				wp_send_json( $data );
			}

		}

		/**
		 * Get all Abandoned Orders
		 *
		 * @return array $data - data of Abandoned Orders.
		 *
		 * @since 8.21.0
		 */
		public static function wcap_cart_row_action( $return = false ) {

			if ( ! $return ) {
		        check_ajax_referer( 'wcap_settings_nonce', 'security' );
		        if ( ! current_user_can( 'manage_options' ) ) {
		            wp_send_json_error( 'Unauthorized', 403 );
		            wp_die();
		        }
		    }

			$action     = '';
			$action_two = '';
			if ( isset( $_POST['row_action'] ) ) {
				$action = sanitize_text_field( wp_unslash( $_POST['row_action'] ) );
			}
			require_once 'admin/wcap_actions.php';
			require_once 'admin/wcap_actions_handler.php';
			require_once 'classes/class_wcap_abandoned_orders_table.php';

			if ( 'unsubscribe' === $action ) {
				$wcap_cart_id = $_POST['abandoned_order_id'];
				$updated      = WCAP_CART_HISTORY_MODEL::wcap_unsubscribe_cart( $wcap_cart_id );
			}
			// Sync Manually.
			if ( 'sync_manually' === $action ) {
				$wcap_cart_id = $_POST['abandoned_order_id'];
				if ( $wcap_cart_id > 0 ) {
					$connectors_common = Wcap_Connectors_Common::get_instance();
					$connectors_common->wcap_sync_cart( $wcap_cart_id );
				}
				$updated = true;
			}

			if ( $updated ) {

				if ( ! ( 'wcap_abandoned_restore' === $action || 'wcap_abandoned_delete' === $action ) ) {
					require_once 'classes/class_wcap_abandoned_orders_table.php';
					$wcap_abandoned_order_list = new Wcap_Abandoned_Orders_Table();
					$data                      = $wcap_abandoned_order_list->wcap_abandoned_order_prepare_items();
				} else {
					require_once 'classes/class_wcap_abandoned_trash_orders_table.php';
					$wcap_abandoned_order_list = new Wcap_Abandoned_Trash_Orders_Table();
					$data                      = $wcap_abandoned_order_list->wcap_abandoned_order_prepare_items();
				}
				wp_send_json( $data );
			}

		}

		public static function wcap_get_active_connector_list() {

			$connector_message = false;
			$active_count      = Wcap_Connectors_Common::wcap_get_active_connectors_count();
			if ( $active_count > 0 ) {
				$connector_name = '';
				$active_list    = json_decode( get_option( 'wcap_active_connectors' ), true );
				// Unset the connectors for which the plugin will continue to send reminder emails.
				if ( ! empty( $active_list ) && array_key_exists( 'custom_smtp', $active_list ) ) {
					unset( $active_list['custom_smtp'] );
				}
				if ( ! empty( $active_list ) && array_key_exists( 'google_sheets', $active_list ) ) {
					unset( $active_list['google_sheets'] );
				}
				if ( ! empty( $active_list ) ) {
					foreach ( $active_list as $c_name => $c_details ) {
						$connector_name .= '' === $connector_name ? ucwords( $c_name ) : ', ' . ucwords( $c_name );
					}
					$connector_message = '<strong>' . sprintf(
						// translators: Names of active connectors.
						__( 'Please note: No emails are currently being sent from the plugin as connection with %s is active.', 'woocommerce-ac' ),
						$connector_name
					) . '</strong>';
				}
			}

			return $connector_message;
		}

		public static function wcap_delete_atc_template() {
		    check_ajax_referer( 'wcap_settings_nonce', 'security' );
		    if ( ! current_user_can( 'manage_options' ) ) {
		        wp_send_json_error( 'Unauthorized', 403 );
		        wp_die();
		    }
			global $wpdb;
			$message = array();
			$id      = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification

			if ( '' != $id  ) { // phpcs:ignore
				$wpdb->delete( //phpcs:ignore
					WCAP_ATC_RULES_TABLE,
					array(
						'id' => $id,
					)
				);
				$message['success'] = __( 'The template has been successfully deleted.', 'woocommerce-ac' );
			} else {
				$message['error'] = __( 'There was an error. Please try again.', 'woocommerce-ac' );
			}

			wp_send_json( $message );
		}

		/**
		 * Inline CSS for emails.
		 *
		 * @param string $email_content - Email content.
		 *
		 * @since 9.8.0
		 */
		public static function wcap_inline_email_css( $email_content ) {

			$wc_version = defined( 'WC_VERSION' ) ? WC_VERSION : '10.3.0';
			$wc_version = preg_replace( '/-.*/', '', $wc_version );
			$is_old_wc  = version_compare( $wc_version, '10.4.0', '<' );

			$class = $is_old_wc ? '\\WCAP\\Emogrifier\\CssInliner' : CssInliner::class;

			if ( ! class_exists( $class ) ) {
				include_once WCAP_PLUGIN_PATH . '/includes/vendor/libraries/emogrifier/src/CssInliner.php';
			}

			try {
				$inliner = $class::fromHtml( $email_content )->inlineCss()->render();
				return $inliner;
			} catch ( Exception $e ) {
			}

			return $email_content;
		}

		/**
		 * Handle WhatsApp token errors and store notices.
		 *
		 * @param int    $code - Error code from WhatsApp API.
		 * @param string $message - Error message from WhatsApp API.
		 *
		 * @since 9.19.0
		 */
		public static function wcap_handle_whatsapp_token_error( $code, $message ) {
			$notice = '';
		
			switch ( $code ) {
				case 190: // Invalid/Expired token
					$notice = "Your WhatsApp access token has expired or is invalid. Please regenerate a System User token from Business Manager.";
					break;
				case 102: // User session expired
					$notice = "Your WhatsApp User token session has expired. Switch to a System User token for production use.";
					break;
				case 10: // Permission denied
					$notice = "Missing permissions on your WhatsApp token. Ensure it has both 'whatsapp_business_messaging' and 'whatsapp_business_management'.";
					break;
				case 200: // Permission error on asset
					$notice = "The token does not have access to this WhatsApp Business Account or phone number. Please assign the System User to the WhatsApp asset.";
					break;
				default:
					$notice = "WhatsApp API error ({$code}): {$message}";
					break;
			}
		
			if ( $notice ) {
				update_option( 'wcap_whatsapp_token_notice', $notice );
			}
		}		

		public static function wcap_show_whatsapp_error_notice() {
			$notice = get_option( 'wcap_whatsapp_token_notice' );
			if ( $notice ) {
				echo '<div class="notice notice-error"><p>' . esc_html( $notice ) . '</p></div>';
			}
		}
		/**
		 * Clear filter session on Abandoned orders page.
		 *
		 * @since 10.2.0
		 */
		public static function wcap_clear_filter_session_on_listcart() {
			if ( is_admin() && isset( $_GET['page'], $_GET['action'] ) && 'woocommerce_ac_page' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) && 'listcart' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) {

				unset(
					$_SESSION['start_date'],
					$_SESSION['end_date'],
					$_SESSION['cart_status'],
					$_SESSION['cart_source'],
					$_SESSION['duration_select'],
					$_SESSION['hidden_start'],
					$_SESSION['hidden_end']
				);
			}
		}

	}
}
