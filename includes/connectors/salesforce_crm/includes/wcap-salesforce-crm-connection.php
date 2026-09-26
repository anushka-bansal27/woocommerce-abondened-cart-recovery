<?php //phpcs:ignore
/**
 * Insert/Update Contact in Salesforce CRM.
 *
 * @package Connectors/Salesforce/Actions
 */

/**
 * Insert/Update Contact in MC.
 */
class Wcap_Salesforce_Crm_Connection {

	/**
	 * Class Instance.
	 *
	 * @var Wcap_Salesforce_Crm_Connection|null
	 */
	private static $ins = null;

	/**
	 * Construct.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'wcap_connection_initiate_or_logout' ) );
	}

	/**
	 * Get instance.
	 *
	 * @return Wcap_Salesforce_Crm_Connection
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Guest record inserted.
	 */
	public function wcap_connection_initiate_or_logout() {
		if ( isset( $_GET['code'] ) && isset( $_GET['action'] ) && 'emailsettings' === $_GET['action'] ) {//phpcs:ignore
			$code = sanitize_text_field( $_GET['code'] );//phpcs:ignore

			$result = $this->salesforce_init_connection( $code );
			if ( ! is_array( $result ) || ! count( $result ) > 0 ) {
				return wp_send_json(array( 'status' => false, 'message' => 'Client ID is not provided' ) );//phpcs:ignore
			}

			if ( is_array( $result['response'] ) || count( $result['response'] ) > 0 ) {
				if ( 200 === $result['response']['code'] ) {
					$connector           = 'salesforce_crm';
					$settings            = json_decode( get_option( 'wcap_' . $connector . '_connector' ) );
					$settings->status    = 'active';
					$settings->activated = current_time( 'mysql' );
					$settings_str        = wp_json_encode( $settings );
					update_option( 'wcap_' . $connector . '_connector', $settings_str );

					$active_connectors = json_decode( get_option( 'wcap_active_connectors', '' ), true );
					if ( ! is_array( $active_connectors ) ) {
						$active_connectors = array();
					}
					$active_connectors[ $connector ] = $settings;

					update_option( 'wcap_active_connectors', wp_json_encode( $active_connectors ) );
					wp_redirect( admin_url( 'admin.php?page=woocommerce_ac_page&action=emailsettings#/connectors' ) );//phpcs:ignore
					exit;
				}
			}
		}
	}

	/**
	 * Initialize Salesforce connection.
	 *
	 * @param string|null $code Authorization code.
	 * @return array|WP_Error
	 */
	public function salesforce_init_connection( $code = null ) {
		$saved_data    = Wcap_Connectors_Common::wcap_get_connectors_data( 'wcap_salesforce_crm' );
		$old_data      = ( isset( $saved_data ) && is_array( $saved_data ) && count( $saved_data ) > 0 ) ? $saved_data : array();
		$client_id     = isset( $old_data['client_id'] ) ? $old_data['client_id'] : '';
		$client_secret = isset( $old_data['client_secret'] ) ? $old_data['client_secret'] : '';
		$redirect_uri  = isset( $old_data['redirect_uri'] ) ? $old_data['redirect_uri'] : '';

		if ( ! $client_id || ! $client_secret ) {
			return new WP_Error( 'missing_credentials', 'Missing Salesforce credentials' );
		}

		if ( '' === $code ) {
			// Step 1: Generate code verifier and code challenge.
			$code_verifier  = rtrim( strtr( base64_encode( random_bytes( 32 ) ), '+/', '-_' ), '=' );//phpcs:ignore
			$code_challenge = rtrim( strtr( base64_encode( hash( 'sha256', $code_verifier, true ) ), '+/', '-_' ), '=' );//phpcs:ignore
			update_option( 'wcap_salesforce_code_verifier', $code_verifier );

			// Step 1: Redirect user to Salesforce authorization endpoint.
			$authorization_url = 'https://login.salesforce.com/services/oauth2/authorize';

			$params = array(
				'response_type'         => 'code',
				'client_id'             => $client_id,
				'redirect_uri'          => $redirect_uri,
				'scope'                 => 'refresh_token offline_access full',
				'code_challenge_method' => 'S256', // SHA-256 code challenge method.
				'code_challenge'        => $code_challenge,
			);

			$authorization_url = $authorization_url . '?' . http_build_query( $params );

			wp_redirect( $authorization_url );//phpcs:ignore
			exit;
		} else {
			// Step 2: Exchange authorization code for access token.
			$token_url = 'https://login.salesforce.com/services/oauth2/token';

			$params = array(
				'grant_type'    => 'authorization_code',
				'client_id'     => $client_id,
				'client_secret' => $client_secret,
				'redirect_uri'  => $redirect_uri,
				'code_verifier' => get_option( 'wcap_salesforce_code_verifier' ),
				'code'          => $code,
			);

			$response = wp_remote_post( $token_url, array( 'body' => $params, ) );//phpcs:ignore

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );

			// Store access token securely (e.g., in options or database).
			update_option( 'wcap_salesforce_access_token', $data['access_token'] );
			update_option( 'wcap_salesforce_instance_url', $data['instance_url'] );
			update_option( 'wcap_salesforce_refresh_token', $data['refresh_token'] );

			return $response;
		}
	}

	/**
	 * Refresh Salesforce access token.
	 */
	public function refresh_salesforce_access_token() {
		$saved_data    = Wcap_Connectors_Common::wcap_get_connectors_data( 'wcap_salesforce_crm' );
		$old_data      = ( isset( $saved_data ) && is_array( $saved_data ) && count( $saved_data ) > 0 ) ? $saved_data : array();
		$client_id     = isset( $old_data['client_id'] ) ? $old_data['client_id'] : '';
		$client_secret = isset( $old_data['client_secret'] ) ? $old_data['client_secret'] : '';
		$refresh_token = get_option( 'wcap_salesforce_refresh_token' );
		$token_url     = 'https://login.salesforce.com/services/oauth2/token';

		$response = wp_remote_post( $token_url, array(//phpcs:ignore
			'body' => array(
				'grant_type'    => 'refresh_token',
				'client_id'     => $client_id,
				'client_secret' => $client_secret,
				'refresh_token' => $refresh_token,
			),
		) );//phpcs:ignore

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data['access_token'] ) ) {
			// Update the access token.
			update_option( 'wcap_salesforce_access_token', $data['access_token'] );
		}
	}
}

new Wcap_Salesforce_Crm_Connection();
