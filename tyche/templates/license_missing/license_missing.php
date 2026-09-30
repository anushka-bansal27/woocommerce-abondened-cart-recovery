<?php

/**
 * License missing template. To be displayed on pages where license is mandatory 
 * @author   Tyche Softwares
 * @package Abandoned-Cart-Pro-for-WooCommerce/License
 * @since 6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

?>

<div class="wcap_license_invalid" style="text-align: center;">
    <img src="https://www.tychesoftwares.com/wp-content/themes/tyche-softwares/assets/images/icons/Tyche-plugins-SHOPPINGCART.png" height="150px" width="150px"> 
    <p style='font-size:12pt; color:#b00606;'>
        <?php
            printf(
                /* translators: %1$s and %2$sare replaced with license settings link and string here */
                __( 'Please activate your license to use this feature. License can be activated from the Settings tab above or by clicking %s Then navigate to <strong>Plugin License Options.</strong>', 'woocommerce-ac' ),
                '<a href="admin.php?page=woocommerce_ac_page&action=emailsettings&wcap_section=wcap_license_settings#/license">' . __( 'here' ) . '</a>'
            );
        ?>
    </p>
    <p style='font-size:12pt; color:#b00606;'>
        <?php
            printf(
                /* translators: %1$s is replaced with support link */
                __( 'If you facing any issues in activating the license then do %s and we shall be more than happy to help you.', 'woocommerce-ac' ),
                '<a href="https://support.tychesoftwares.com/help/2285384554" target="_blank">contact us</a>'
            )
        ?>
    </p>
    <p style='font-size:12pt; color:#b00606;'>
        <?php
            printf(
                /* translators: %1$s is replaced with plugin name */
                __( 'If your license key is expired or if you don\'t have a valid license key, you will need to purchase a new license of the plugin. Head over to our site to purchase a license of %s', 'woocommerce-ac' ),
                '<strong>Abandoned Cart Pro for WooCommerce</strong>'
            );
        ?>
    </p>
    <a href="https://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro/" class="button button-primary" target="_blank">
        <?php _e( 'Buy Abandoned Cart Pro', 'woocommerce-ac' ); ?>
    </a>
</div>