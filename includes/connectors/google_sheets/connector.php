<?php
/**
 * Google Sheets Integration.
 *
 * @package Abandon Cart Pro/Connectors
 */

/**
 * Google Sheets Class.
 */
class Wcap_Google_Sheets extends Wcap_Connector {

	/**
	 * Connector name.
	 *
	 * @var string $connector_name
	 */
	public $connector_name = 'google_sheets';
	/**
	 * Connector Slug Name.
	 *
	 * @var string $slug
	 */
	public $slug = 'wcap_google_sheets';
	/**
	 * Connector Name.
	 *
	 * @var string $name
	 */
	public $name = 'Google Sheets';
	/**
	 * Connector Description.
	 *
	 * @var string $desc
	 */
	public $desc = 'Export emails and abandoned carts collected from the plugin to Google Sheets.';
	/**
	 * Class Instance.
	 *
	 * @var object $ins
	 */
	private static $ins = null;
	/**
	 * Calls list.
	 *
	 * @var array $registered_calls
	 */
	public $registered_calls = array();

	/**
	 * Construct.
	 */
	public function __construct() {
		$this->wcap_define_plugin_properties();
		$this->connector_url = WCAP_GOOGLE_SHEETS_PLUGIN_URL;
		add_filter( 'wcap_connectors_loaded', array( $this, 'add_card' ) );
		add_action( 'admin_init', array( &$this, 'wcap_connection_status_check' ) );
		add_action( 'wcap_single_connector_box_before_buttons', array( $this, 'wcap_add_logout_url' ) );
		add_filter( 'wcap_send_reminder_emails', array( &$this, 'wcap_send_reminder_emails' ), 10, 1 );
		add_action( 'wp_loaded', array( &$this, 'wcap_google_sheets_include_files' ), 9 );
		add_filter( 'wcap_before_connector_save_settings', array( &$this, 'wcap_before_save_settings' ), 10, 3 );
		add_filter( 'wcap_connect_to_google', array( &$this, 'wcap_get_connection_link' ), 10, 1 );
		add_filter( 'wcap_logout_of_google', array( &$this, 'wcap_get_logout_link' ), 10, 1 );
		// Add cart status in the Abandoned Orders tab.
		add_filter( 'wcap_abandoned_orders_table_data', array( &$this, 'wcap_add_gs_cart_status' ), 12, 1 );

	}

	/**
	 * Define constants.
	 */
	public function wcap_define_plugin_properties() {
		if ( ! defined( 'WCAP_GOOGLE_SHEETS_VERSION' ) ) {
			define( 'WCAP_GOOGLE_SHEETS_VERSION', '1.0.0' );
		}
		if ( ! defined( 'WCAP_GOOGLE_SHEETS_FULL_NAME' ) ) {
			define( 'WCAP_GOOGLE_SHEETS_FULL_NAME', 'Abandoned Carts Automations Connectors: Google Sheets' );
		}
		if ( ! defined( 'WCAP_GOOGLE_SHEETS_PLUGIN_FILE' ) ) {
			define( 'WCAP_GOOGLE_SHEETS_PLUGIN_FILE', __FILE__ );
		}
		if ( ! defined( 'WCAP_GOOGLE_SHEETS_PLUGIN_DIR' ) ) {
			define( 'WCAP_GOOGLE_SHEETS_PLUGIN_DIR', __DIR__ );
		}
		if ( ! defined( 'WCAP_GOOGLE_SHEETS_PLUGIN_URL' ) ) {
			define( 'WCAP_GOOGLE_SHEETS_PLUGIN_URL', untrailingslashit( plugin_dir_url( WCAP_GOOGLE_SHEETS_PLUGIN_FILE ) ) );
		}
		if ( ! defined( 'WCAP_GOOGLE_SHEETS_PLUGIN_BASENAME' ) ) {
			define( 'WCAP_GOOGLE_SHEETS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		}
	}

	/**
	 * Adds connector card.
	 *
	 * @param array $available_connectors - List of Connectors.
	 */
	public function add_card( $available_connectors ) {
		$available_connectors['wcap']['connectors']['wcap_google_sheets'] = array(
			'name'            => $this->name,
			'desc'            => __( $this->desc, 'woocommerce-ac' ), // phpcs:ignore
			'connector_class' => 'Wcap_Google_Sheets',
			'image'           => $this->get_image(),
			'source'          => '',
			'file'            => '',
		);

		return $available_connectors;
	}

	/**
	 * Returns class instance.
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Redirect to the correct page once Google redirects back after authentication or logout.
	 */
	public static function wcap_connection_status_check() {
		$_page   = isset( $_GET['page'] ) && '' !== $_GET['page'] ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore
		$_auth   = isset( $_GET['wcap_google_auth'] ) && '' !== $_GET['wcap_google_auth'] ? sanitize_text_field( wp_unslash( $_GET['wcap_google_auth'] ) ) : ''; // phpcs:ignore
		$_logout = isset( $_GET['wcap_google_logout'] ) && '' !== $_GET['wcap_google_logout'] ? sanitize_text_field( wp_unslash( $_GET['wcap_google_logout'] ) ) : ''; // phpcs:ignore
		if ( isset( $_page, $_auth ) && 'woocommerce_ac_page' === $_page && '1' === $_auth ) {

			// Found when user connects to Google and the connection is successful.
			if ( isset( $_GET['code'] ) && '' !== $_GET['code'] ) { //phpcs:ignore
				$wcap_oauth_gcal = new Wcap_Google_Oauth();
				$wcap_oauth_gcal->wcap_oauth_redirect();
			}
		} elseif ( isset( $_page, $_logout ) && 'woocommerce_ac_page' === $_page && '1' === $_logout ) {
			$wcap_oauth_gcal = new Wcap_Google_Oauth();
			$wcap_oauth_gcal->oauth_logout();
		}
	}

	/**
	 * Include files.
	 */
	public function wcap_google_sheets_include_files() {
		include_once WCAP_GOOGLE_SHEETS_PLUGIN_DIR . '/includes/class-wcap-google-oauth.php';
		include_once WCAP_GOOGLE_SHEETS_PLUGIN_DIR . '/includes/class-wcap-create-spreadsheet.php';
		include_once WCAP_GOOGLE_SHEETS_PLUGIN_DIR . '/includes/class-wcap-upsert-cart.php';
		include_once WCAP_GOOGLE_SHEETS_PLUGIN_DIR . '/includes/class-wcap-delete-cart.php';
	}

	/**
	 * Ensure reminder emails are sent with the connector active.
	 *
	 * @param bool $send_emails - Send emails or no.
	 * @return bool $send_emails - Send emails or no.
	 */
	public static function wcap_send_reminder_emails( $send_emails ) {

		$common_inst       = Wcap_Connectors_Common::get_instance();
		$active_connectors = $common_inst->wcap_get_active_connectors_list();
		// Reminders should be sent if google sheets is enabled.
		if ( is_array( $active_connectors ) && count( $active_connectors ) > 0 && in_array( 'wcap_google_sheets', $active_connectors, true ) ) {
			return apply_filters( 'wcap_override_reminder_emails_for_sheets', true );
		}
		return $send_emails;

	}

	/**
	 * Saves logout link as a hidden field.
	 */
	public function wcap_add_logout_url() {
		$logout_url = self::wcap_get_logout_link( '' );
		?>
		<input type="hidden" name="wcap_logout_url" id="wcap_logout_url" value="<?php echo esc_url( $logout_url ); ?>" />
		<?php
	}

	/**
	 * Verify settings for Google Sheets before saving.
	 *
	 * @param array $result - Verification status.
	 * @param array $settings - GS Settings.
	 * @param str   $connector - Connector name.
	 */
	public function wcap_before_save_settings( $result, $settings, $connector ) {

		if ( '' !== $connector && 'google_sheets' === $connector ) {
			$client_id    = isset( $settings['client_id'] ) && '' !== $settings['client_id'] ? sanitize_text_field( wp_unslash( $settings['client_id'] ) ) : '';
			$secret_key   = isset( $settings['secret_key'] ) && '' !== $settings['secret_key'] ? sanitize_text_field( wp_unslash( $settings['secret_key'] ) ) : '';
			$redirect_uri = isset( $settings['redirect_uri'] ) && '' !== $settings['redirect_uri'] ? sanitize_text_field( wp_unslash( $settings['redirect_uri'] ) ) : '';
			$sheet_title  = isset( $settings['sheet_title'] ) && '' !== $settings['sheet_title'] ? sanitize_text_field( wp_unslash( $settings['sheet_title'] ) ) : 'Abandoned Carts Pro - Cart Data';

			if ( '' !== $client_id && '' !== $secret_key && '' !== $redirect_uri ) {
				$result['google_sheets'] = array(
					'status'  => 'success',
					'message' => __( 'Connected successfully!', 'woocommerce-ac' ),
				);

				// Check if the sheet name has been modified.
				$old_settings = json_decode( get_option( 'wcap_google_sheets_connector' ), true );
				if ( ! empty( $old_settings ) && ( isset( $old_settings['status'] ) && 'active' === $old_settings['status'] ) && ( isset( $old_settings['wcap_gsheets_refresh_token'] ) && '' !== $old_settings['wcap_gsheets_refresh_token'] && null !== $old_settings['wcap_gsheets_refresh_token'] ) ) {
					if ( $old_settings['sheet_title'] !== $sheet_title ) { // It was changed. Update it in Drive.
						$spreadsheet_obj = Wcap_Create_Spreadsheet::get_instance();
						$edit_status     = $spreadsheet_obj->wcap_edit_sheet_title( $sheet_title );
						if ( ! is_bool( $edit_status ) && '' !== $edit_status ) {
							$sheet_title = $old_settings['sheet_title']; // Revert to the old title if we were unable to update it in the Drive.
						}
					}
				}
			} else {
				$result['google_sheets'] = array(
					'status'  => 'error',
					'message' => __( 'Some settings are blanks. Please fill all the fields.', 'woocommerce-ac' ),
				);
			}
		}
		return $result;
	}

	/**
	 * Return Connection Auth Link.
	 *
	 * @param string $url - Any existing URL that may be saved.
	 */
	public static function wcap_get_connection_link( $url ) {

		$wcap_oauth_gcal = new Wcap_Google_Oauth();
		return $wcap_oauth_gcal->wcap_get_google_auth_url();
	}

	/**
	 * Returns Google Logout.
	 *
	 * @param string $url - default value.
	 * @return string $url - Logout URL.
	 */
	public static function wcap_get_logout_link( $url ) {

		$redirect_args = array(
			'page'               => 'woocommerce_ac_page',
			'wcap_google_logout' => '1',
		);
		return add_query_arg( $redirect_args, admin_url( '/admin.php' ) );
	}

	/**
	 * Display Cart Sync status.
	 *
	 * @param object $wcap_abandoned_orders - Abandoned Orders data.
	 */
	public function wcap_add_gs_cart_status( $wcap_abandoned_orders ) {

		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( $this->slug );
		$connector_status   = isset( $connector_settings['status'] ) ? $connector_settings['status'] : '';
		if ( 'active' === $connector_status ) {
			foreach ( $wcap_abandoned_orders as $k => $col_data ) {
				$cart_id = 0;
				foreach ( $col_data as $col_name => $col_value ) {
					$cart_id = 'id' === $col_name ? $col_value : $cart_id;
					if ( (int) $cart_id > 0 ) {
						$connector_common = Wcap_Connectors_Common::get_instance();
						$cart_status      = $connector_common->wcap_get_cart_sync_status( $cart_id, $this->connector_name );

						if ( $cart_status ) {
							$wcap_abandoned_orders[ $k ]->connector_status .= "<span><img src='" . WCAP_GOOGLE_SHEETS_PLUGIN_URL . "/views/icon.png' class='wcap-wpf-success wcap-success wcap-connector-status' /></span>";
						} else {
							$wcap_abandoned_orders[ $k ]->connector_status .= "<span><img src='" . WCAP_GOOGLE_SHEETS_PLUGIN_URL . "/views/icon.png' class='wcap-wpf-failed wcap-failed wcap-connector-status' /></span>";
						}
					}
					break;
				}
			}
		}
		return $wcap_abandoned_orders;
	}

	/**
	 * Sync Cart Manually.
	 */
	public function wcap_sync_manually() {

		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( 'wcap_google_sheets' );
		$activated_time     = isset( $connector_settings['activated'] ) ? $connector_settings['activated'] : '';
		$connector_status   = isset( $connector_settings['status'] ) ? $connector_settings['status'] : '';
		$refresh_token      = isset( $connector_settings['wcap_gsheets_refresh_token'] ) ? $connector_settings['wcap_gsheets_refresh_token'] : '';

		if ( 'active' === $connector_status && '' != $activated_time && '' !== $refresh_token ) { // phpcs:ignore

			global $wpdb;
			// Get the list of carts which have not yet been synced.
			$cart_list = $wpdb->get_col( // phpcs:ignore
				'SELECT wcap.id FROM `' . WCAP_ABANDONED_CART_HISTORY_TABLE . '` as wcap LEFT JOIN `' . $wpdb->prefix . 'ac_connector_sync` as csync ON wcap.id=csync.cart_id where wcap.user_id > 0 AND wcap.abandoned_cart_time > "' . $activated_time . '" AND ( csync.cart_id IS NULL OR csync.status = "failed" ) AND connector_name = "google_sheets"' // phpcs:ignore
			);
			if ( is_array( $cart_list ) && count( $cart_list ) > 0 ) { // Start the process one at a time.
				$sync_count = 0;
				$cart_count = count( $cart_list );
				foreach ( $cart_list as $cart_id ) {
					$sync_status = $this->wcap_sync_single_cart( $cart_id );
					if ( $sync_status ) { // failed, catch the data and move on.
						$sync_count++;
					}
				}

				if ( $sync_count === $cart_count ) {
					$status  = 'success';
					$message = sprintf(
						/* translators: %1$s,%2$s are replaced with number of carts synced and number of total carts respectively */
						__( '%1$d of %2$d carts were synced successfully.', 'woocommerce-ac' ),
						esc_html( $sync_count ),
						esc_html( $cart_count )
					);
				} else {
					$status = 'error';
					if ( 0 == $sync_count ) { // phpcs:ignore
						$message = sprintf(
							/* translators: %1$s,%2$s are replaced with number of carts synced and number of total carts respectively */
							__( '%1$d of %2$d carts were synced. Please check for network connectivity issues and try again.', 'woocommerce-ac' ),
							esc_html( $sync_count ),
							esc_html( $cart_count )
						);
					} else {
						$message = sprintf(
							/* translators: %1$s,%2$s are replaced with number of carts synced and number of total carts respectively */
							__( '%1$d of %2$d carts were synced successfully. Please try again to sync the remaining carts.', 'woocommerce-ac' ),
							esc_html( $sync_count ),
							esc_html( $cart_count )
						);
					}
				}
				wp_send_json(
					array(
						'status'  => $status,
						'message' => $message,
					)
				);

			} else { // If none found, send a message saying data has already been synced.
				wp_send_json(
					array(
						'status'  => 'success',
						'message' => __( 'All carts have been synced.', 'woocommerce-ac' ),
					)
				);
			}
		}
		wp_send_json(
			array(
				'status'  => 'error',
				'message' => __( 'We were unable to connect. Please try again later.', 'woocommerce-ac' ),
			)
		);
	}

	/**
	 * Sync individual cart with Google Sheets.
	 *
	 * @param int $cart_id - Cart ID.
	 * @return bool Status.
	 */
	public function wcap_sync_single_cart( $cart_id = 0 ) {
		if ( $cart_id > 0 ) {
			$wcap_upsert_cart = Wcap_Google_Sheets_Upsert_Cart::get_instance();
			$res              = $wcap_upsert_cart->wcap_prepare_cart_details( $cart_id, true );
			if ( 'complete' === $res ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Return the connection status.
	 */
	public function wcap_return_google_sheet_connection_status() {
		$connector_settings = Wcap_Connectors_Common::wcap_get_connectors_data( 'google_sheets' );

		if ( ! empty( $connector_settings ) && ( isset( $connector_settings['status'] ) && 'active' === $connector_settings['status'] ) && ( isset( $connector_settings['wcap_gsheets_refresh_token'] ) && '' !== $connector_settings['wcap_gsheets_refresh_token'] && null !== $connector_settings['wcap_gsheets_refresh_token'] ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Return the Google Sheet row ID for the cart.
	 *
	 * @param int $cart_id - Cart ID.
	 * @return int $row_id - Row ID.
	 */
	public function wcap_get_row_id( $cart_id ) {

		global $wpdb;

		$results = $wpdb->get_results( // phpcs:ignore
			$wpdb->prepare(
				'SELECT * FROM `' . $wpdb->prefix . 'ac_connector_sync` WHERE cart_id = %d AND connector_name = %s',
				$cart_id,
				'google_sheets'
			)
		);

		if ( isset( $results[0]->connector_cart_id ) && '' !== $results[0]->connector_cart_id ) {
			return $results[0]->connector_cart_id;
		} else {
			return false;
		}

	}
}
