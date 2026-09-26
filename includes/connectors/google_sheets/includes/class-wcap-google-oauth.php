<?php
/**
 * Wcap Google OAuth Operations File.
 *
 * @author Tyche Softwares
 * @since 9.4.0
 * @package Abandon Cart Pro for WooCommerce.
 */

if ( ! class_exists( 'Wcap_Google_Oauth' ) ) {

	/**
	 * Google OAuth Class.
	 */
	class Wcap_Google_Oauth {

		/**
		 * Class instance.
		 *
		 * @var $ins
		 */
		public static $ins = null;

		/**
		 * Construct.
		 */
		public function __construct() {
			add_action( 'wcap_update_google_client_id_secret', array( &$this, 'wcap_update_google_client_callback' ), 10, 1 );
			self::wcap_load_google_oauth_files();
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
		 * This will return the uri to which the user will be redirected after consent screen.
		 *
		 * @since 9.4.0
		 */
		public function wcap_get_redirect_uri() {

			$redirect_uri = '';

			$query_args   = array(
				'page'             => 'woocommerce_ac_page',
				'wcap_google_auth' => '1',
			);
			$redirect_uri = add_query_arg( $query_args, admin_url( 'admin.php' ) );

			return $redirect_uri;
		}

		/**
		 * This will set the Access Token information in transient.
		 *
		 * @param string $access_token - Access Token to connect to Google.
		 * @since 9.4.0
		 */
		public function wcap_set_access_token( $access_token ) {
			set_transient( 'wcap_gsheets_access_token', $access_token, 3600 );
		}

		/**
		 * This will return the the Access Token information.
		 *
		 * @since 9.4.0
		 */
		public function wcap_get_access_token() {

			$access_token = get_transient( 'wcap_gsheets_access_token' );

			return $access_token;
		}

		/**
		 * This will return the Refresh Token information.
		 *
		 * @since 9.4.0
		 */
		public function wcap_get_refresh_token() {

			$connector_settings = json_decode( get_option( 'wcap_google_sheets_connector' ), true );

			$refresh_token = '';
			if ( isset( $connector_settings ) && is_array( $connector_settings ) && count( $connector_settings ) > 0 ) {
				$refresh_token = isset( $connector_settings['wcap_gsheets_refresh_token'] ) ? $connector_settings['wcap_gsheets_refresh_token'] : false;
			}

			return $refresh_token;
		}

		/**
		 * Renew access token with refresh token.
		 *
		 * @param string        $refresh_token Refresh Token.
		 * @param Google_Client $client Google Client Object.
		 *
		 * @return array
		 */
		private function wcap_renew_access_token( $refresh_token, $client ) {

			if ( $client->getClientId() ) {
				return $client->fetchAccessTokenWithRefreshToken( $refresh_token );
			}
		}

		/**
		 * Return valid Google Client Object.
		 */
		public function get_client() {

			if ( ! $this->wcap_check_google_vendor_files() ) {
				return;
			}

			$client = new Google_Client();
			$client->addScope( Google\Service\Sheets::SPREADSHEETS );
			$client->setAccessType( 'offline' );
			$client->setRedirectUri( $this->wcap_get_redirect_uri() );

			do_action( 'wcap_update_google_client_id_secret', $client ); // Adding client and secret data to $client.

			$access_token  = $this->wcap_get_access_token();
			$refresh_token = $this->wcap_get_refresh_token();

			// https://github.com/googleapis/google-api-php-client/issues/1475#issuecomment-492378984 .
			$client->setApprovalPrompt( 'force' );
			$client->setIncludeGrantedScopes( true );

			if ( $refresh_token && empty( $access_token ) ) {

				$access_token = $this->wcap_renew_access_token( $refresh_token, $client );

				if ( $access_token && isset( $access_token['access_token'] ) ) {

					unset( $access_token['refresh_token'] ); // unset this since we store it in an option.
					$this->wcap_set_access_token( $access_token );
				} else {
					$unable_to_fetch = __( 'Unable to fetch access token with refresh token. Google sync disabled until re-authenticated. ', 'woocommerce-ac' );

					$error            = isset( $access_token['error'] ) ? $access_token['error'] . ' - ' : '';
					$error           .= isset( $access_token['error_description'] ) ? $access_token['error_description'] : '';
					$error            = ( '' !== $error ) ? 'Error : ' . $error : '';
					$unable_to_fetch .= $error;

					$client->setClientId( null );
					$client->setClientSecret( null );

					$oauth_settings = json_decode( get_option( 'wcap_google_sheets_connector' ), true );
					if ( isset( $oauth_settings['wcap_gsheets_refresh_token'] ) ) {
						$oauth_settings['wcap_gsheets_refresh_token'] = '';
						update_option( 'wcap_google_sheets_connector', wp_json_encode( $oauth_settings ) );
					}
					delete_transient( 'wcap_gsheets_access_token' );
				}
			}
			// It may be empty, e.g. in case refresh token is empty.
			if ( ! empty( $access_token ) ) {
				$access_token['refresh_token'] = $refresh_token;
				try {
					$client->setAccessToken( $access_token );
				} catch ( InvalidArgumentException $e ) {
					return false;
				}
			}

			return $client;
		}

		/**
		 * Get google login url from connect.woocommerce.com.
		 *
		 * @return string
		 */
		public function wcap_get_google_auth_url() {

			$client = $this->get_client();
			if ( $client->getClientId() ) {
				return $client->createAuthUrl();
			}

			return add_query_arg(
				array(
					'redirect' => $this->wcap_get_redirect_uri(),
				),
				$client->createAuthUrl()
			);
		}

		/**
		 * Process the oauth redirect.
		 */
		public function wcap_oauth_redirect() {

			$client = $this->get_client();
			$client->authenticate( $_GET['code'] ); // phpcs:ignore
			$access_token = $client->getAccessToken();

			try {
				$client->setAccessToken( $access_token ); // $access_token is an array of access_token, expires_in, scope, token_type, created, refresh_token.

				$user_id = get_current_user_id();

				set_transient( 'wcap_gsheets_access_token', $access_token, 3500 );
				$refresh = $client->getRefreshToken();

				$oauth_settings = json_decode( get_option( 'wcap_google_sheets_connector' ), true );
				if ( is_array( $oauth_settings ) && count( $oauth_settings ) > 0 && ! isset( $oauth_settings['wcap_gsheets_refresh_token'] ) ) {
					$oauth_settings['wcap_gsheets_refresh_token'] = $refresh;
					$oauth_settings['status']                     = 'active'; // Mark the connector as active.
					$oauth_settings['activated']                  = current_time( 'timestamp' ); // phpcs:ignore
					update_option( 'wcap_google_sheets_connector', wp_json_encode( $oauth_settings ) );

					// Create spreadsheet to write data to.
					$create_sheet_obj = new Wcap_Create_Spreadsheet();
					$create_sheet_obj->wcap_create_spreadsheet();

				}
			} catch ( Exception $e ) {
				return false;
			}

			if ( ! empty( $access_token ) ) {
				$status = 'success';

				// Redirecting user to appropriate page.
				$redirect_args = array(
					'page'   => 'woocommerce_ac_page',
					'action' => 'emailsettings#/connectors',
				);
				$url           = add_query_arg( $redirect_args, admin_url( '/admin.php?' ) );

				wp_safe_redirect( $url );
				exit;
			} else {
				$status        = 'fail';
				// translators: %1$s, %2$s will be replaced with error code and description respectively.
				$error_message = sprintf( __( 'Google OAuth failed with "%1$s", "%2$s"', 'woocommerce-ac' ), isset( $_GET['error'] ) ? $_GET['error'] : '', isset( $_GET['error_description'] ) ? $_GET['error_description'] : '' ); // phpcs:ignore
			}

		}

		/**
		 * OAuth Logout.
		 *
		 * @since 9.4.0
		 */
		public function oauth_logout() {

			$client       = $this->get_client();
			$access_token = $client->getAccessToken();

			if ( ! empty( $access_token['access_token'] ) ) {
				if ( $client->getClientId() ) {
					$body = $client->revokeToken( $access_token );
				}
			}

			$oauth_settings = json_decode( get_option( 'wcap_google_sheets_connector' ), true );
			if ( isset( $oauth_settings['wcap_gsheets_refresh_token'] ) ) {
				unset( $oauth_settings['wcap_gsheets_refresh_token'] );
				update_option( 'wcap_google_sheets_connector', wp_json_encode( $oauth_settings ) );
			}
			delete_transient( 'wcap_gsheets_access_token' );

			// Redirecting user to appropriate page.
			$redirect_args = array(
				'page'   => 'woocommerce_ac_page',
				'action' => 'emailsettings#/connectors',
			);
			$url           = add_query_arg( $redirect_args, admin_url( '/admin.php?' ) );
			wp_safe_redirect( $url );
			exit;

		}

		/**
		 * This function is responsible for the connecting to the google and it returns an authorized API client.
		 *
		 * @param obj $client Client Object.
		 * @since 9.4.0
		 */
		public function wcap_update_google_client_callback( $client ) {

			$oauth_settings = json_decode( get_option( 'wcap_google_sheets_connector', '{}' ), true );

			if ( $oauth_settings && is_array( $oauth_settings ) && count( $oauth_settings ) > 0 ) {
				if ( isset( $oauth_settings['client_id'], $oauth_settings['secret_key'] ) && '' !== $oauth_settings['client_id'] && '' !== $oauth_settings['secret_key'] ) {
					$client->setClientId( $oauth_settings['client_id'] );
					$client->setClientSecret( $oauth_settings['secret_key'] );
				}
			}
		}

		/**
		 * Function to check if Google Sheets, Drive and Google Vendor Files are loaded.
		 *
		 * @since 9.4.0
		 */
		public function wcap_check_google_vendor_files() {
			return class_exists( Google_Client::class );
		}

		/**
		 * Function to load Google Calendar Vendor Files from Composer.
		 *
		 * @since 9.4.0
		 */
		public static function wcap_load_google_oauth_files() {
			$autoloader = WCAP_PLUGIN_PATH . '/includes/vendor/libraries/oauth-google-sheets/vendor/autoload.php';
			if ( file_exists( $autoloader ) ) {
				require $autoloader;
			}
		}
	}
}
