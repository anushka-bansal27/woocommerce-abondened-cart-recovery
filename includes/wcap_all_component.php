<?php
/**
 * It will Add all the Boilerplate component when we activate the plugin.
 *
 * @author  Tyche Softwares
 * @package Abandoned-Cart-Pro-for-WooCommerce/Admin/Component
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( ! class_exists( 'Wcap_All_Component' ) ) {
	/**
	 * It will Add all the Boilerplate component when we activate the plugin.
	 */
	class Wcap_All_Component {

		/**
		 * It will Add all the Boilerplate component when we activate the plugin.
		 */
		public function __construct() {

			$is_admin = is_admin();

			if ( true === $is_admin ) {

				$wcap_plugin_name        = 'Abandoned Cart Pro for WooCommerce';
				$wcap_license_path       = 'admin.php?page=woocommerce_ac_page&action=emailsettings#/license';
				$wcap_locale             = 'woocommerce-ac';
				$wcap_file_name          = 'woocommerce-abandon-cart-pro/woocommerce-ac.php';
				$wcap_plugin_prefix      = 'wcap';
				$wcap_lite_plugin_prefix = 'wcal';
				$wcap_plugin_folder_name = 'woocommerce-abandon-cart-pro/';
				$wcap_plugin_dir_name    = WCAP_PLUGIN_PATH . '/woocommerce-ac.php';

				$wcap_get_previous_version = get_option( 'woocommerce_ac_db_version' );

				$wcap_plugins_page = 'admin.php?page=woocommerce_ac_page';
				$wcap_plugin_slug  = 'woocommerce_ac_page';

				$wcap_settings_page    = 'admin.php?page=woocommerce_ac_page&action=emailsettings';
				$wcap_setting_add_on   = 'woocommerce_ac_page';
				$wcap_setting_section  = 'ac_general_settings_section';
				$wcap_register_setting = 'woocommerce_ac_settings';

				new Wcap_TS_Woo_Active( $wcap_plugin_name, $wcap_file_name, $wcap_locale );

				$ts_pro_faq = self::wcap_get_faq();
				new Wcap_TS_Faq_Support( $wcap_plugin_name, $wcap_plugin_prefix, $wcap_plugins_page, $wcap_locale, $wcap_plugin_folder_name, $wcap_plugin_slug, $ts_pro_faq );

			}
		}

		/**
		 * It will contain all the FAQ which need to be display on the FAQ page.
		 *
		 * @return array $ts_faq All questions and answers.
		 */
		public static function wcap_get_faq() {

			$ts_faq = array();
			$display_links = apply_filters( 'wcap_show_tyche_exclusive_tabs', false );

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
						Our plugin relies on the action scheduler library which is present in the WooCommerce to send reminder emails. This action scheduler library needs the WP-Cron enabled. If the WP-Cron is disabled, the scheduler actions will run only when an admin page request occurs. If the WP-Cron is enabled but still, not working then it could be possible that your server has some restrictions which do not allow WP-Cron to run.',
				),
				3  => array(
					'question' => 'Reminder emails are shown as sent in the "Reminders Sent" tab but why I or my customers are not receiving them?',
					'answer'   => 'If you see the emails under the "Reminders Sent" tab but if they are still not being received to your mail Inbox then you can check once the Spam folder of your email account. If the email is not in the Spam then it’s likely a problem with your email server. You can contact your web host regarding this.',
				),
				4  => array(
					'question' => 'How can I offer discount codes to customers in the abandoned cart reminder emails?',
					'answer'   => 'When you add or edit an email template, it has a setting "Enter coupon code to add into email" where you can enter a coupon code that you have created from WooCommerce > Coupons page. You would also need to add the {{coupon.code}} merge tag in the Body section of the template. This will send the same coupon code in the reminder emails.
						<br/><br/>
						If you want to send a unique coupon code to each customer, then you need to enable the "Generate unique coupon codes" setting. You can set up the corresponding settings for the unique coupon code there.',
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
						If you want, you can change this interval from the "Run Automated Scheduler after X minutes" under the "Setting for sending Emails & SMS using Action Scheduler" section in the Settings tab.',
				),
				9  => array(
					'question' => 'Why I am receiving only the manual reminder emails, not the automatic reminder emails?',
					'answer'   => 'The setting "Send Abandoned cart reminders automatically using Action Scheduler" located at Abandoned Carts > Settings > General needs to be enabled to send the reminder emails automatically as per the set time.',
				),
				10 => array(
					'question' => 'Can I capture the customer’s email from the custom email field and send them a cart reminder email?',
					'answer'   => 'Yes, this is possible to achieve with our Pro plugin. We have a setting "Capture email address from custom fields" which you can enable and add the class of the custom email field in the option "Class names of the form fields" located at Abandoned Carts’ General Settings page. This will capture the user’s email entered into the custom email field and then send a cart reminder email.',
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
						The email field in the ATC popup can be set as optional that means the popup to ask for an email address will be displayed, but the customer can simply click on the "No thanks" link & proceed to add the item to the cart.',
				),
				14 => array(
					'question' => 'Why does the Add to Cart popup modal not appear when I click on Add to Cart? I have all the settings set up correctly.',
					'answer'   => 'If you are using custom buttons for Add to Cart, then the popup won’t appear. The Add to Cart popup appears with the default Add to Cart buttons which must have standard WC classes. 
						<br/><br/>
						Another reason could be that you have setup Rules in the Popup template thereby preventing the popup from appearing on all the default WooCommerce pages.
						<br/><br/>
						It is also possible that you are using the Add to Cart button on a custom page & not one of the default WooCommerce pages. If you wish for the popup template to appear on a custom page, please setup the same in the Rules section of the template.',
				),
				15 => array(
					'question' => 'For multi-lingual websites, will your plugin send reminder emails to customers in the language in which the cart was abandoned when browsing the website?',
					'answer'   => 'Yes, our plugin is compatible with the WPML and Polylang plugins. Using any of these plugins, you can send the abandoned cart reminder emails in different languages in which the customer has abandoned the cart.',
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

			return apply_filters( 'wcap_faqs_support', $ts_faq );
		}
	}
	$wcap_all_component = new Wcap_All_Component();
}
