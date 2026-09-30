<?php
/**
 * 
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Tyche Init
 *
 * @package Tyche
 * @since 1.0.0
 */

class Tyche_WCAP_Init {

	/**
	 * Constructor
	 */
	public function __construct() {
		require_once 'component/plugin-license/class-wcap-license.php';

		add_filter( 'wcap_admin_menu_items', array( $this, 'wcap_admin_menu_items' ) );
		add_filter( 'wcap_show_tyche_exclusive_tabs', '__return_true' );
		add_filter( 'wcap_tyche_support_link', array( $this, 'wcap_tyche_support_link' ) );
		add_filter( 'wcap_connectors_page_description', array( $this, 'wcap_connectors_page_description' ) );
		add_filter( 'wcap_facebook_messenger_doc_links', array( $this, 'wcap_facebook_messenger_doc_links' ) );

		add_filter( 'woocommerce_abandoned_cart_docs_url', array( $this, 'wcap_abandoned_cart_docs_url' ) );
		add_filter( 'woocommerce_abandoned_cart_support_url', array( $this, 'wcap_tyche_support_link' ) );
		add_filter( 'wcap_settings_based_on_license', array( $this, 'wcap_settings_based_on_license' ), 1 );
		add_filter( 'wcap_include_additional_files', array( $this, 'wcap_include_additional_files' ), 1 );
		add_filter( 'wcap_settings', array( $this, 'wcap_add_settings' ) );
		add_action( 'wcap_admin_menu_page', array( $this, 'wcap_admin_menu_page' ) );
		add_filter( 'wcap_fb_page_id_desc', array( $this, 'wcap_fb_page_id_desc' ) );
		add_filter( 'wcap_fb_settings_desc', array( $this, 'wcap_fb_settings_desc' ) );
		add_filter( 'wcap_fb_webhook_desc', array( $this, 'wcap_fb_webhook_desc' ) );

		add_filter( 'wcap_basic_connectors_loaded', array( $this, 'wcap_connectors_based_on_license' ) );
		add_filter( 'wcap_license_valid', array( $this, 'wcap_license_valid' ) );
		add_filter( 'wcap_show_only_license_page', array( $this, 'wcap_show_only_license_page' ) );
		add_action( 'wcap_fb_settings_after', array( $this, 'wcap_display_fb_settings' ) );
		add_action( 'wcap_fb_templates_after', array( $this, 'wcap_display_fb_settings' ) );

		add_action( 'wcap_after_activate_plugin', array( $this, 'wcap_after_activate_plugin' ) );
		add_action( 'wcap_after_update_plugin', array( $this, 'wcap_after_update_plugin' ) );
		add_filter( 'wcap_faqs_support', array( $this, 'wcap_faqs_support' ) );
	}

	public function wcap_admin_menu_items( $menu_items ) {
		$license_page_only = get_option( 'wcap_show_only_license_page', '' );
		if ( 'yes' === $license_page_only ) {
			return array();
		}

		return $menu_items;
	}

	public function wcap_show_only_license_page( $show_only_license_page ) {
		$license_key = trim( get_option( 'wcap_show_only_license_page', '' ) );
		if ( 'yes' === $license_key ) {
			return true;
		}

		return $show_only_license_page;
	}

	public function wcap_tyche_support_link( $support_link ) {
		return 'https://support.tychesoftwares.com/help/2285384554';
	}

	public function wcap_abandoned_cart_docs_url( $link ) {
		return 'https://www.tychesoftwares.com/docs/docs/abandoned-cart-pro-for-woocommerce/';
	}

	public function wcap_connectors_page_description( $desc ) {
		return array(
			'desc' => __( 'Please note that the plugin will no longer send reminder emails to abandoned carts if integration is enabled with a third party CRM/email marketing tool. If you still wish to send reminder emails with the integration enabled, please get in touch with %s.', 'woocommerce-ac' ),
			'link' => '<a href="https://support.tychesoftwares.com/help/2285384554" target="_blank">support at Tyche Softwares</a>',
		);
	}

	public function wcap_facebook_messenger_doc_links( $links ) {
		return array(
			'desc' => __("Configure the plugin to send notifications to Facebook Messenger using the settings below. Please refer the %s following documentation %s to complete the setup.", "woocommerce-ac"),
			'link' => '<a href="https://www.tychesoftwares.com/docs/docs/abandoned-cart-pro-for-woocommerce/send-abandoned-cart-reminder-notifications-using-facebook-messenger" target="_blank">', // translators: Documentation Link.
		);
	}

	public function wcap_fb_page_id_desc( $desc ) {
		return __( 'Facebook Page ID in numberic format. You can find your page ID from <a href="https://www.tychesoftwares.com/docs/docs/abandoned-cart-pro-for-woocommerce/send-abandoned-cart-reminder-notifications-using-facebook-messenger#fbpageid" target="_blank">here</a>', 'woocommerce-ac' );
	}
	
	public function wcap_fb_settings_desc( $desc ) {
		return ____( 'Configure the plugin to send notifications to Facebook Messenger using the settings below. Please refer the <a href="https://www.tychesoftwares.com/docs/docs/abandoned-cart-pro-for-woocommerce/send-abandoned-cart-reminder-notifications-using-facebook-messenger" target="_blank">following documentation</a> to complete the setup.', 'woocommerce-ac' );
	}

	public function wcap_fb_webhook_desc( $desc ) {
		return __( 'This webhook needs to added to your <a href="https://www.tychesoftwares.com/docs/docs/abandoned-cart-pro-for-woocommerce/send-abandoned-cart-reminder-notifications-using-facebook-messenger#attachment_2719" target="_blank">Facebook Developer App</a> for the checkbox to appear on site.', 'woocommerce-ac' );
	}

	public function wcap_settings_based_on_license( $settings ) {
		$license_type = get_option( 'wcap_edd_license_type' );

		if ( ! in_array( $license_type, array( 'business', 'enterprise' ), true ) ) {
			unset( $settings['general'] );
			unset( $settings['unsubscribe_emails'] );
		}

		return $settings;
	}

	public function wcap_include_additional_files( $additional_files ) {
		array_push( $additional_files, plugin_dir_path( __FILE__ ) . 'templates/license.php' );
		return $additional_files;
	}

	public function wcap_add_settings( $settings ) {
		$settings['edd_sample_license_status_ac_woo'] = get_option( 'edd_sample_license_status_ac_woo', '' );
		$settings['edd_sample_license_key_ac_woo']    = get_option( 'edd_sample_license_key_ac_woo', '' );

		return $settings;
	}

	public function wcap_admin_menu_page() {
		if ( 'yes' === get_option( 'wcap_show_only_license_page', '' ) ) {
			if ( ! ( isset( $_GET['action'] ) && 'emailsettings' === $_GET['action'] ) ) { // phpcs:ignore
				wp_safe_redirect( admin_url( '/admin.php?page=woocommerce_ac_page&action=emailsettings#/license' ) );
				exit;
			}
		}
	}

	public function wcap_connectors_based_on_license( $connectors ) {
		$license_type = get_option( 'wcap_edd_license_type' );
		switch ( $license_type ) {
			case '':
			case 'starter':
				unset( $connectors['wcap_mailchimp'] );
				unset( $connectors['wcap_activecampaign'] );
				unset( $connectors['wcap_mailjet'] );
				unset( $connectors['wcap_sendinblue'] );
				unset( $connectors['wcap_wp_fusion'] );
				unset( $connectors['wcap_salesforce_crm'] );
				unset( $connectors['wcap_klaviyo'] );
				unset( $connectors['wcap_hubspot'] );
				unset( $connectors['wcap_fluentcrm'] );
				unset( $connectors['wcap_drip'] );
				break;

			case 'business':
				unset( $connectors['wcap_wp_fusion'] );
				unset( $connectors['wcap_klaviyo'] );
				unset( $connectors['wcap_hubspot'] );
				unset( $connectors['wcap_fluentcrm'] );
				unset( $connectors['wcap_salesforce_crm'] );
				break;
		}
		return $connectors;
	}

	public function wcap_license_valid( $is_valid ) {
		if ( get_option( 'edd_sample_license_status_ac_woo' ) === 'valid' ) {
			return true;
		}

		return false;
	}

	public function wcap_display_fb_settings() {
		if ( get_option( 'edd_sample_license_status_ac_woo' ) !== 'valid' ) {
			wc_get_template( 
                'license_missing.php', 
                '', 
                'woocommerce-abandon-cart-pro/',
                WCAP_PLUGIN_PATH . '/tyche/templates/license_missing/' );
		}
	}

	public function wcap_after_activate_plugin() {
		$license_key = trim( get_option( 'edd_sample_license_key_ac_woo', '' ) );
		if ( '' === $license_key ) {
			set_transient( 'wcap_redirect_to_license_page', true, 5 * MINUTE_IN_SECONDS );
		}
	}

	public function wcap_after_update_plugin() {
		// Each time the update code is run, we will check the license and resave the type. Needed to ensure anyone who upgrades licenses continues to see the correct set of features.
		$license = trim( get_option( 'edd_sample_license_key_ac_woo' ) );
		if ( '' !== $license ) {
			// data to send in our API request
			$api_params = array(
					'edd_action'=> 'check_license',
					'license'   => $license,
					'item_name' => urlencode( EDD_SL_ITEM_NAME_AC_WOO ) // the name of our product in EDD
			);
			// Call the custom API.
			$response = wp_remote_get( add_query_arg( $api_params, EDD_SL_STORE_URL_AC_WOO ), array( 'timeout' => 15, 'sslverify' => false ) );
			// make sure the response came back okay
			if ( is_wp_error( $response ) )
				return false;
			// decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
			if ( isset( $license_data->date_created ) && strtotime( $license_data->date_created ) <= strtotime( '2022-04-01' ) ) { // Plans were introduced on Apr 1, 2022.
				update_option( 'wcap_edd_license_type', 'enterprise' );
			} else if ( isset( $license_data->price_id ) && ! empty( $license_data->price_id ) ) {
				switch ( $license_data->price_id ) {
				case '0':
						update_option( 'wcap_edd_license_type', 'starter' );
						break;
					case '1':
						update_option( 'wcap_edd_license_type', 'business' );
						break;
					case '2':
					default:
						update_option( 'wcap_edd_license_type', 'enterprise' );
						break;
				}
			} else {
				update_option( 'wcap_edd_license_type', 'enterprise' );
			}
		}

		// 9.7.0 - Enable tracking for updating users.
		if ( 'yes' !== get_option( 'wcap_allow_tracking', '' ) && ! get_option( 'wcap_tracking_enabled_by_user', false ) ) {
			update_option( 'wcap_allow_tracking', 'yes' );
			update_option( 'wcap_tracking_enabled_by_user', 'no' );
		}
		if ( ! get_option( 'wcap_add_v2_tracker_event', false ) ) {
			// Remove the tracker event, so the new v2 event can be added correctly.
			$next_scheduled = wp_next_scheduled( 'wcap_ts_tracker_send_event' );
			if ( $next_scheduled ) {
				wp_unschedule_event( $next_scheduled, 'wcap_ts_tracker_send_event' );
			}
			update_option( 'wcap_add_v2_tracker_event', 'yes' );
		}
	}

	public function wcap_faqs_support( $faq ) {
		$ts_faq = array(
			1  => array(
				'question' => 'When would a customer’s cart be considered as abandoned?',
				'answer'   => 'Our plugin considers the cart as abandoned when the user adds a product to the shopping cart but leaves the website without completing the purchase. The cart is considered as abandoned as soon as the cut-off time has passed since adding the product to the cart. 
					<br><br>
					For guest users, the user’s email will be captured once the email address is entered in the Add to cart popup modal (if the Add to cart popup modal is enabled). If the Add to cart popup modal is disabled then the user’s email and other details like first name, last name & phone number will be captured only when the guest user reaches the checkout page and enters it.',
			),
			2  => array(
				'question' => 'I have a few abandoned carts. But why is the abandoned cart email not sent to those customers?',
				'answer'   => 'There are several reasons abandoned cart reminder emails not being sent from your website. First, make sure the setting "Enable abandoned cart emails" located at Abandoned Carts > Settings and the email templates of our plugin are enabled. Also, the setting "Send Abandoned cart reminders automatically using Action Scheduler" needs to be enabled to send the reminder emails automatically using the action scheduler.
					<br/><br/>
					Our plugin relies on the action scheduler library which is present in the WooCommerce to send reminder emails. This action scheduler library needs the WP-Cron enabled. If the WP-Cron is disabled, the scheduler actions will run only when an admin page request occurs. If the WP-Cron is enabled but still, not working then it could be possible that your server has some restrictions which do not allow WP-Cron to run. You can refer to <a href="https://www.tychesoftwares.com/docs/docs/abandoned-cart-pro-for-woocommerce/wp_alternate_cron/?utm_source=userwebsite&utm_medium=link&utm_campaign=AbandonedCartProFAQTab" target="_blank">this post</a> for fixing the WP-Cron issue.',
			),
			3  => array(
				'question' => 'Reminder emails are shown as sent in the "Reminders Sent" tab but why I or my customers are not receiving them?',
				'answer'   => 'If you see the emails under the "Reminders Sent" tab but if they are still not being received to your mail Inbox then you can check once the Spam folder of your email account. If the email is not in the Spam then it’s likely a problem with your email server. You can contact your web host regarding this.',
			),
			4  => array(
				'question' => 'How can I offer discount codes to customers in the abandoned cart reminder emails?',
				'answer'   => 'When you add or edit an email template, it has a setting "Enter coupon code to add into email" where you can enter a coupon code that you have created from WooCommerce > Coupons page. You would also need to add the {{coupon.code}} merge tag in the Body section of the template. This will send the same coupon code in the reminder emails.
					<br/><br/>
					If you want to send a unique coupon code to each customer, then you need to enable the "Generate unique coupon codes" setting. You can set up the corresponding settings for the unique coupon code there. You can learn more about the coupon code settings <a href="https://www.tychesoftwares.com/docs/docs/abandoned-cart-pro-for-woocommerce/understanding-coupon-codes/?utm_source=userwebsite&utm_medium=link&utm_campaign=AbandonedCartProFAQTab" target="_blank">here</a>.',
			),
			5  => array(
				'question' => 'When the abandoned cart is considered as recovered?',
				'answer'   => 'Our plugin considers the abandoned cart as recovered when the customer comes to the site via accessing the cart/checkout link provided in the abandoned cart reminders (emails, SMS & FB) and purchases the product.',
			),
			6  => array(
				'question' => 'What would happen when an abandoned order is recovered?',
				'answer'   => 'When an order is recovered, we do change the order status from Abandoned to Recovered and the Recovered order status is showing in green on the Abandoned Orders tab. Our plugin does not send further abandoned cart reminder emails to the users who recovered their carts.',
			),
			7  => array(
				'question' => 'Can I know which customers have received the email or which email has been sent?',
				'answer'   => 'Yes, you can see which email templates are sent and to whom it has sent in the "Reminders Sent" tab of our plugin. You can view which customers have opened the email and clicked on the cart/checkout link.
					<br/><br/>
					Also, our plugin shows the Abandoned Cart details popup modal on the Abandoned Orders tab where you can see whether the email template is sent and the customer has opened/clicked the email link or not.',
			),
			8  => array(
				'question' => 'How does the Abandoned cart plugin send out the cart recovery emails? Do I need a special setup?',
				'answer'   => 'Our plugin uses the Action Scheduler Library to send the abandoned cart reminder emails automatically. This will reduce the dependency on the WP Cron and this does not require a special setup. The email sending is done every 15 minutes.
					<br/><br/>
					If you want, you can change this interval from the "Run Automated Scheduler after X minutes" under the "Setting for sending Emails & SMS using Action Scheduler" section in the Settings tab. You can check out more about the Action Scheduler <a href="https://www.tychesoftwares.com/moving-to-the-action-scheduler-library/?utm_source=userwebsite&utm_medium=link&utm_campaign=AbandonedCartProFAQTab" target="_blank">here</a>.',
			),
			9  => array(
				'question' => 'Why I am receiving only the manual reminder emails, not the automatic reminder emails?',
				'answer'   => 'The setting "Send Abandoned cart reminders automatically using Action Scheduler" located at Abandoned Carts > Settings > General needs to be enabled to send the reminder emails automatically as per the set time.',
			),
			10 => array(
				'question' => 'Can I capture the customer’s email from the custom email field and send them a cart reminder email?',
				'answer'   => 'Yes, this is possible to achieve with our Pro plugin. We have a setting "Capture email address from custom fields" which you can enable and add the class of the custom email field in the option "Class names of the form fields" located at Abandoned Carts’ General Settings page. This will capture the user’s email entered into the custom email field and then send a cart reminder email.
					<br/><br/>
					You can learn more about this feature <a href="https://www.tychesoftwares.com/docs/docs/abandoned-cart-pro-for-woocommerce/capture-email-address-from-custom-fields/?utm_source=userwebsite&utm_medium=link&utm_campaign=AbandonedCartProFAQTab" target="_blank">here</a>.',
			),
			11 => array(
				'question' => 'Can I send abandoned cart reminder emails to the store admin as well?',
				'answer'   => 'Yes, you can send the reminder email to customers, admin, both - customers and admin, and others using the Rules engine we provide in the email template.',
			),
			12 => array(
				'question' => 'The test emails have wrong or dummy data. Is it supposed to work this way?',
				'answer'   => 'Yes, we send the dummy data in our test email. We don’t have actual data to add in the test email hence we display the customer’s name as John, two dummy products, coupon code as TESTCOUPON etc. However, when the actual abandon cart recovery emails are sent out, these merge tags will be replaced with the customer’s cart data.',
			),
			13 => array(
				'question' => 'How does the Add To Cart Popup Modal work?',
				'answer'   => 'The Add to cart popup modal appears on the home, shop, product, product category, and the custom page once the customer clicks on the add to cart button. The popup modal asks guest users to enter their email with their consent.
					<br/><br/>
					The email field in the ATC popup can be set as optional that means the popup to ask for an email address will be displayed, but the customer can simply click on the "No thanks" link & proceed to add the item to the cart.
					<br/><br/>
					You can learn more about the Add to cart popup modal <a href="https://www.tychesoftwares.com/docs/docs/abandoned-cart-pro-for-woocommerce/capture-visitor-email-address-before-checkout-page/?utm_source=userwebsite&utm_medium=link&utm_campaign=AbandonedCartProFAQTab" target="_blank">here</a>.',
			),
			14 => array(
				'question' => 'Why does the Add to Cart popup modal not appear when I click on Add to Cart? I have all the settings set up correctly.',
				'answer'   => 'If you are using custom buttons for Add to Cart, then the popup won’t appear. The Add to Cart popup appears with the default Add to Cart buttons which must have standard WC classes. 
					<br/><br/>
					Another reason could be that you have setup Rules in the Popup template thereby preventing the popup from appearing on all the default WooCommerce pages.
					<br/><br/>
					It is also possible that you are using the Add to Cart button on a custom page & not one of the default WooCommerce pages. If you wish for the popup template to appear on a custom page, please setup the same in the Rules section of the template.
					<br/><br/>
					You can <a href="https://support.tychesoftwares.com/help/2285384554/?utm_source=userwebsite&utm_medium=link&utm_campaign=AbandonedCartProFAQTab" target="_blank">contact</a> our support team to get this resolved as each custom button needs a different solution.',
			),
			15 => array(
				'question' => 'For multi-lingual websites, will your plugin send reminder emails to customers in the language in which the cart was abandoned when browsing the website?',
				'answer'   => 'Yes, our plugin is compatible with the WPML and Polylang plugins. Using any of these plugins, you can send the abandoned cart reminder emails in different languages in which the customer has abandoned the cart.
					You can learn more about it <a href="https://www.tychesoftwares.com/docs/docs/abandoned-cart-pro-for-woocommerce/multilingual/?utm_source=userwebsite&utm_medium=link&utm_campaign=AbandonedCartProFAQTab" target="_blank">here</a>.',
			),
			16 => array(
				'question' => 'What does the "Abandoned - Order Received" status mean?',
				'answer'   => 'If the user has placed an order recently on the site and if he has abandoned another cart. Then in such a case, if the store owner does not want to send the reminder email immediately then our plugin has a setting "Send reminder emails for newly abandoned carts after X days of order placement". It allows the site admin to set the number of days after which a reminder emails should be sent for a newly abandoned cart since the last order placed by the same user.
					<br/><br/>
					Alternatively, the Store Owner or Shop Manager can also unsubscribe the user from the Abandoned Orders listing page to avoid sending the reminder email.',
			),
			17 => array(
				'question' => 'Can we send the abandoned cart reminder emails based on the rules like cart total or product categories?',
				'answer'   => 'Yes, you can send the reminder emails based on the different conditions like depending on the cart total, cart items, cart status, payment gateways, and product categories, etc. You can set one or many conditions as per your requirements on the Edit Template page.',
			),
		);

		return $ts_faq;
	}
}

// Initialize the Tyche plugin.
$tyche_wcap_init = new Tyche_WCAP_Init();
