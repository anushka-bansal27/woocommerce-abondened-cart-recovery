<?php
/**
 * CustomSMTP Connector file *
 * Icon from https://www.iconpacks.net/icons/1/free-mail-icon-142-thumb.png.
 *
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Connectors/CustomSMTP
 */

/**
 * Class for CustomSMTP Connector
 */
class Wcap_Custom_SMTP extends Wcap_Connector {

	/**
	 * Connector Name
	 *
	 * @var $connector_name
	 */
	public $connector_name = 'Custom SMTP';
	/**
	 * Slug Name
	 *
	 * @var $slug
	 */
	public $slug = 'wcap_custom_smtp';
	/**
	 * Name
	 *
	 * @var $name
	 */
	public $name = 'Custom SMTP server';
	/**
	 * Description
	 *
	 * @var $desc
	 */
	public $desc = 'Use a custom SMTP server to send emails to recover abandoned carts.';
	/**
	 * Sync Disabled
	 *
	 * @var $sync_disabled
	 */
	public $disable_sync = true;
	/**
	 * Signle instance of the class
	 *
	 * @var $ins
	 */
	private static $ins = null;
	/**
	 * Custom SMTP Connection.
	 *
	 * @var $wcap_custom_smtp_settings.
	 */
	public static $wcap_custom_smtp_settings = false;

	/**
	 * @var $is_send_grid.
	 */
	public static $is_send_grid = false;
	/**
	 * Construct. Add hooks and filters.
	 *
	 * @var array All calls with object.
	 */
	public function __construct() {
		$this->wcap_define_plugin_properties();
		$this->connector_url = WCAP_CUSTOMSMTP_PLUGIN_URL;
		add_action( 'wp_ajax_wcap_save_connector_settings', array( &$this, 'wcap_save_connector_settings' ), 9 );
		add_action( 'wp_ajax_wcap_debug_smtp_settings', array( &$this, 'wcap_debug_smtp_settings' ), 9 );
		add_filter( 'wcap_basic_connectors_loaded', array( &$this, 'wcap_basic_connectors_loaded' ), 9, 1 );
		add_filter( 'wcap_send_reminder_emails', array( &$this, 'wcap_send_reminder_emails' ), 9, 1 );
		if ( self::wcap_is_set_custom_smtp() ) {
			if ( strpos( self::$wcap_custom_smtp_settings->smtp_host, 'sendgrid' ) ) {
				self::$is_send_grid = true;
				add_filter( 'pre_wp_mail', array( &$this, 'send_mail_from_sendgrid' ), -1, 2 );
			}
		}
	}
	/**
	 * Send emails from sendgrid.
	 *
	 * @param bool  $return - Whether to send emails or not.
	 * @param array $atts -   smtp array.
	 */
	public function send_mail_from_sendgrid( $return, $atts ) {
		$params   = apply_filters( 'wcap_custom_smtp_sendgrid', array() );
		$sendfrom = self::$wcap_custom_smtp_settings->smtp_username;
		$api      = self::decrypt( self::$wcap_custom_smtp_settings->smtp_password );

		if ( is_array( $params ) && count( $params ) > 0 ) {
			foreach ( $params as $k => $v ) {
				$atts[$k] = $v;
			}
		}
		include_once( 'sendgrid-php/senmail.php' ); // phpcs:ignore
		return false;
	}
	/**
	 * Send emails from site if this is enabled.
	 *
	 * @param bool $send_emails - Whether to send emails or not.
	 */
	public function wcap_send_reminder_emails( $send_emails ) {
		if ( Wcap_Custom_SMTP::wcap_is_set_custom_smtp() ) {
			return true;
		}
		return $send_emails;
	}
	/**
	 * Function to re-arrange connectors list
	 *
	 * @param array $connector_list - list of connectors.
	 */
	public function wcap_basic_connectors_loaded( $connector_list ) {
		$custom_smtp = $connector_list['wcap_custom_smtp'];
		unset( $connector_list['wcap_custom_smtp'] );
		$connector_list = array_merge( array( 'wcap_custom_smtp' => $custom_smtp ), $connector_list );
		return $connector_list;
	}
	/**
	 * Function to debug smtp connection.
	 */
	public function wcap_debug_smtp_settings() {
		if ( strpos( sanitize_text_field( $_POST['settings']['smtp_host'] ), 'sendgrid' ) ) { //phpcs:disable
			$_POST['settings']['to']      = sanitize_text_field( $_POST['settings']['test_email'] );
			$_POST['settings']['subject'] = 'Testing SMTP connection';
			$_POST['settings']['message'] = 'Test email to check SMTP connection';
			$this->send_mail_from_sendgrid( false, sanitize_text_field( $_POST['settings'] ) );
			exit;
		}
		require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
		require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
		require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';

		$phpmailer = new PHPMailer\PHPMailer\PHPMailer( true );
		$phpmailer->SMTPDebug = PHPMailer\PHPMailer\SMTP::DEBUG_CONNECTION;
		$email = $_POST['settings']['test_email'];
		$phpmailer->addAddress( $_POST['settings']['test_email'] ); 
		$this->wcap_set_custom_smtp( $phpmailer );
		// self::wcap_set_custom_smtp( $phpmailer );
		$phpmailer->isHTML( true );
		$phpmailer->Subject = esc_html__( 'Testing SMTP connection', 'woocommerce-ac' );
		$phpmailer->Body    = esc_html__( 'Test email to check SMTP connection', 'woocommerce-ac' );
		$phpmailer->AltBody = esc_html__( 'This is the body in plain text for non-HTML mail clients', 'woocommerce-ac' );
		$phpmailer->FromName = get_bloginfo( 'name' );
		// phpcs:enable
		ob_start();
		try {
			$result = $phpmailer->send();
			if ( $result ) {
				$result = esc_html__( 'Email sent', 'woocommerce-ac' );
			}
		}
		catch ( Exception $e ) {
			$result = $phpmailer->ErrorInfo; // phpcs:ignore
		}
		$buffer = ob_get_clean();
		wp_send_json(
			array(
				'debug'  => $buffer,
				'result' => $result,
			)
		);
		exit;
	}
	/**
	 * Function to encrypt password before saving it
	 */
	public function wcap_save_connector_settings() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		check_ajax_referer( 'wcap_settings_nonce', 'security' );

		if ( isset( $_POST['settings'] ) && isset( $_POST['settings']['smtp_password'] ) ) { // phpcs:ignore
			$_POST['settings']['smtp_password'] = $this->encrypt( $_POST['settings']['smtp_password'] ); //phpcs:ignore
		}
	}
	/**
	 * Function to encrypt password before saving it
	 *
	 * @param string $password - password for encrypting.
	 */
	public static function encrypt( $password ) {
		$crypt_key = get_option( 'wcap_random_security_key' );
		return Wcap_Aes_Ctr::encrypt( $password, $crypt_key, 256 );
	}
	/**
	 * Function to encrypt password before saving it
	 *
	 * @param string $password - password for encrypting.
	 */
	public static function decrypt( $password ) {
		$crypt_key = get_option( 'wcap_random_security_key' );
		return Wcap_Aes_Ctr::decrypt( $password, $crypt_key, 256 );
	}
	/**
	 * Function to define constans
	 */
	public function wcap_define_plugin_properties() {
		if ( ! defined( 'WCAP_CUSTOMSMTP_VERSION' ) ) {
			define( 'WCAP_CUSTOMSMTP_VERSION', '1.0.0' );
		}
		if ( ! defined( 'WCAP_CUSTOMSMTP_FULL_NAME' ) ) {
			define( 'WCAP_CUSTOMSMTP_FULL_NAME', 'Abandoned Carts Automations Connectors: CustomSMTP' );
		}
		if ( ! defined( 'WCAP_CUSTOMSMTP_PLUGIN_FILE' ) ) {
			define( 'WCAP_CUSTOMSMTP_PLUGIN_FILE', __FILE__ );
		}
		if ( ! defined( 'WCAP_CUSTOMSMTP_PLUGIN_DIR' ) ) {
			define( 'WCAP_CUSTOMSMTP_PLUGIN_DIR', __DIR__ );
		}
		if ( ! defined( 'WCAP_CUSTOMSMTP_PLUGIN_URL' ) ) {
			define( 'WCAP_CUSTOMSMTP_PLUGIN_URL', untrailingslashit( plugin_dir_url( WCAP_CUSTOMSMTP_PLUGIN_FILE ) ) );
		}
		if ( ! defined( 'WCAP_CUSTOMSMTP_PLUGIN_BASENAME' ) ) {
			define( 'WCAP_CUSTOMSMTP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		}
	}
	/**
	 * Function to Add card in connector's main page.
	 *
	 * @param array $available_connectors - Avaialble connector for display in main connector page.
	 */
	public function add_card( $available_connectors ) {
		$available_connectors['wcap']['connectors'][ $this->slug ] = array(
			'name'            => $this->name,
			'desc'            => __( $this->desc, 'woocommerce-ac' ),  //phpcs:ignore
			'connector_class' => 'Wcap_CustomSMTP',
			'image'           => $this->get_image(),
			'source'          => '',
			'file'            => '',
		);

		return $available_connectors;
	}

	/**
	 * Function to get instance.
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}
		return self::$ins;
	}

	/** Check if custom smtp is set up @since 8.15 */
	public static function wcap_is_set_custom_smtp() {
		$wcap_custom_smtp_settings = get_option( 'wcap_custom_smtp_connector' );
		if ( ! empty( $wcap_custom_smtp_settings ) ) {
			self::$wcap_custom_smtp_settings = json_decode( $wcap_custom_smtp_settings );
			if ( isset( self::$wcap_custom_smtp_settings->status ) && 'active' === self::$wcap_custom_smtp_settings->status ) {
				return true;
			}
		}
		return false;
	}

	/** Unset hooks from other plugins and add hooks for custom  */
	public static function wcap_add_custom_mail_hooks() {
		if ( self::$is_send_grid ) {
			return;
		}
		global $wp_filter;
		if ( isset( $wp_filter['pre_wp_mail'] ) ) {
			$wp_filter['pre_wp_mail_old'] = $wp_filter['pre_wp_mail']; //phpcs:ignore
			unset( $wp_filter['pre_wp_mail'] );
		}

		global $wp_action;
		if ( isset( $wp_action['phpmailer_init'] ) ) {
			$wp_action['phpmailer_init_old'] = $wp_filter['phpmailer_init'];
			unset( $wp_action['phpmailer_init'] );
		}

		add_action( 'phpmailer_init', array( 'Wcap_Common', 'wcap_set_plaintext_body' ) );
		add_action( 'phpmailer_init', array( 'Wcap_Custom_SMTP', 'wcap_set_custom_smtp' ), 9999999 );
	}

	/** Remove our hooks and add the other hooks removed */
	public static function wcap_remove_custom_mail_hooks() {
		if ( self::$is_send_grid ) {
			return;
		}
		global $wp_filter;
		global $wp_action;
		if ( isset( $wp_filter['pre_wp_mail_old'] ) ) {
			$wp_filter['pre_wp_mail'] = $wp_filter['pre_wp_mail_old']; //phpcs:ignore
		}
		if ( isset( $wp_action['phpmailer_init_old'] ) ) {
			$wp_action['phpmailer_init'] = $wp_action['phpmailer_init_old'];
		}

		remove_action( 'phpmailer_init', array( 'Wcap_Custom_SMTP', 'wcap_set_custom_smtp' ) );

	}

	/**
	 * Function to set credentials for Custom SMTP server
	 *
	 * @param object $phpmailer - phpmailer object.
	 */
	public static function wcap_set_custom_smtp( $phpmailer ) {
		// phpcs:disable
		$wcap_custom_smtp_settings = self::wcap_get_smtp_settings( );
		$phpmailer->isSMTP();
		$phpmailer->SMTPAuth   = $wcap_custom_smtp_settings->smtp_authentication === 'yes' ? true : false;
		$phpmailer->Host       = $wcap_custom_smtp_settings->smtp_host;
		$phpmailer->Port       = $wcap_custom_smtp_settings->smtp_port;
		$phpmailer->SMTPSecure = $wcap_custom_smtp_settings->smtp_encryption !== 'none' ? $wcap_custom_smtp_settings->smtp_encryption : false;
		if ( $phpmailer->SMTPSecure != 'tls' ) {
			$phpmailer->SMTPAutoTLS = $wcap_custom_smtp_settings->smtp_autotls === 'yes' ? true : false;
		}
		$phpmailer->Username   = $wcap_custom_smtp_settings->smtp_username;
		$phpmailer->Password   = $wcap_custom_smtp_settings->smtp_password;
		if ( !$phpmailer->SMTPDebug ) {
			$phpmailer->Password   =  Wcap_Custom_SMTP::decrypt( $phpmailer->Password );
		}
		$phpmailer->From       = $wcap_custom_smtp_settings->smtp_username;
		if ( strpos( $wcap_custom_smtp_settings->smtp_host, 'postmarkapp' ) ) {
			$phpmailer->From = '' !== $wcap_custom_smtp_settings->smtp_from_email ? $wcap_custom_smtp_settings->smtp_from_email : $wcap_custom_smtp_settings->smtp_username;
		}
		$phpmailer->clearReplyTos();
		// phpcs:enable
	}

	/**
	 * Function to get credentials for Custom SMTP server
	 */
	public static function wcap_get_smtp_settings() {
		// phpcs:disable
		if ( isset( $_POST['settings'] ) ) {	
			$settings  = json_decode(json_encode( $_POST['settings'] ) );		
			// $phpmailer->isHTML(true); 
			return $settings; 
		}
		// phpcs:enable

		return self::$wcap_custom_smtp_settings;
	}
	/**
	 * Sync individual cart.
	 *
	 * @param int $cart_id - Cart ID.
	 * @return bool true | false - success | failure.
	 * @since 8.16.0
	 */
	public function wcap_sync_single_cart( $cart_id = 0 ) {
		return ;
	}
}
