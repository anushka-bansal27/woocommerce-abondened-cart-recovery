<?php

//uncomment this line for testing
//set_site_transient( 'update_plugins', null );

/**
 * Allows plugins to use their own update API.
 *
 * @author Ashok Rane
 * @version 8.22.1
 */
class EDD_AC_WOO_Plugin_Updater {
	private $api_url  = 'https://www.tychesoftwares.com/';
	private $api_data = array();
	private $name     = 'Abandoned Cart Pro for WooCommerce';
	private $slug     = 'woocommerce-ac';
	private $version  = '';

	/**
	 * Class constructor.
	 *
	 * @uses plugin_basename()
	 * @uses hook()
	 *
	 * @param string $_api_url The URL pointing to the custom API endpoint.
	 * @param string $_plugin_file Path to the plugin file.
	 * @param array $_api_data Optional data to send with API calls.
	 * @return void
	 */
	function __construct( $_api_url , $_plugin_file , $_api_data = null ) {
		$this->api_url  = trailingslashit( $_api_url );
		$this->api_data = urlencode_deep( $_api_data );
		$this->name     = plugin_basename( $_plugin_file );
		$this->slug     = basename( $_plugin_file, '.php');
		$this->version  = $_api_data['version'];

		// Set up hooks.
		$this->hook();
	}

	/**
	 * Set up Wordpress filters to hook into WP's update process.
	 *
	 * @uses add_filter()
	 *
	 * @return void
	 */
	private function hook() {
		add_filter( 'pre_set_site_transient_update_plugins' , array( $this , 'pre_set_site_transient_update_plugins_filter' ) );
		add_filter( 'plugins_api', array( $this , 'plugins_api_filter' ) , 10 , 3 );
	}

	/**
	 * Check for Updates at the defined API endpoint and modify the update array.
	 *
	 * This function dives into the update api just when Wordpress creates its update array,
	 * then adds a custom API call and injects the custom plugin data retrieved from the API.
	 * It is reassembled from parts of the native Wordpress plugin update code.
	 * See wp-includes/update.php line 121 for the original wp_update_plugins() function.
	 *
	 * @uses api_request()
	 *
	 * @param array $_transient_data Update array build by Wordpress.
	 * @return array Modified update array with custom plugin data.
	 */
	function pre_set_site_transient_update_plugins_filter( $_transient_data ) {

		$license_status = get_option ('edd_sample_license_status_ac_woo');
	    if ( isset ( $license_status) && 'valid' === $license_status ) {

			if( empty( $_transient_data ) ) {
				return $_transient_data;
			}

			$to_send		= array( 'slug' => $this->slug );
			// check the cache
			$run_api_call   = $this->get_cached_response( $this->slug );
			if ( ! $run_api_call ) {
				$api_response	= $this->api_request( 'plugin_latest_version', $to_send );
				if( false !== $api_response && is_object( $api_response ) && isset( $api_response->new_version ) ) {
					$api_response->plugin = $this->name; // REQUIRED: WP core's auto-updater (should_update()) keys off this property.
        			set_transient( $this->slug, $api_response, 3600 );
					if( version_compare( $this->version, $api_response->new_version, '<' ) ) {
						$_transient_data->response[$this->name] = $api_response;
					}
				}
			} else {
				if( version_compare( $this->version, $run_api_call->new_version, '<' ) ) {
					$_transient_data->response[$this->name] = $run_api_call;
				}
			}
		}
		return $_transient_data;

	}

	/**
	 * Return transient value.
	 *
	 * @param string $slug - Transient Name. Same as plugin slug.
	 * @return object - Transient Value | false if expired or not found.
	 */
	function get_cached_response( $slug ) {
		return get_transient( $slug );
	}

	/**
	 * Updates information on the "View version x.x details" page with custom data.
	 *
	 * @uses api_request()
	 *
	 * @param mixed $_data
	 * @param string $_action
	 * @param object $_args
	 * @return object $_data
	 */
	function plugins_api_filter( $_data , $_action = '' , $_args = null ) {
		if ( ( $_action != 'plugin_information' ) || !isset( $_args->slug ) || ( $_args->slug != $this->slug ) ) return $_data;

		$to_send = array( 'slug' => $this->slug );
		// check the cache
		$run_api_call = $this->get_cached_response( $this->slug );
		if ( ! $run_api_call ) {
			$api_response = $this->api_request( 'plugin_information', $to_send );
			if ( false !== $api_response ) {
				set_transient( $this->slug, $api_response, 3600 );
				$_data          = $api_response;
				$_data->banners = maybe_unserialize( $_data->banners );
			}
		} else {
			$_data          = $run_api_call;
			$_data->banners = maybe_unserialize( $_data->banners );
		}
		return $_data;
	}

	/**
	 * Calls the API and, if successfull, returns the object delivered by the API.
	 *
	 * @uses get_bloginfo()
	 * @uses wp_remote_post()
	 * @uses is_wp_error()
	 *
	 * @param string $_action The requested action.
	 * @param array $_data Parameters for the API action.
	 * @return false||object
	 */
	private function api_request( $_action, $_data ) {

		global $wp_version;

		$data = array_merge( $this->api_data, $_data );

		if( $data['slug'] != $this->slug )
			return;

		if( empty( $data['license'] ) )
			return;

		$license_type = get_option( 'wcap_edd_license_download_type', '' );
		if ( 'trial' === $license_type ) {
			$name = 'Abandoned+Cart+Pro+for+WooCommerce+Trial';
		} else {
			$name = isset( $data['item_name'] ) ? $data['item_name'] : false;
		}

		$api_params = array(
			'edd_action' 	=> 'get_version',
			'license' 		=> $data['license'],
			'name' 			=> $name,
			'slug' 			=> $this->slug,
			'author'		=> $data['author']
		);
		$request = wp_remote_post( $this->api_url , array( 'timeout' => 15 , 'sslverify' => false , 'body' => $api_params ) );

		if ( ! is_wp_error( $request ) ):
			$request = json_decode( wp_remote_retrieve_body( $request ) );
			if( $request && isset( $request->sections ) )
				$request->sections = maybe_unserialize( $request->sections );
			return $request;
		else:
			return false;
		endif;
	}
}
