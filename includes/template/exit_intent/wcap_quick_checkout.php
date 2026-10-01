<?php

/**
 * Add to Cart popup modal template, it wll be displayed on shop, category, and products pages. 
 * @author   LittleCodr
 * @package Abandoned-Cart-Pro-for-WooCommerce/Frontend/ATC-Template
 * @since 6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
$page_id = get_the_ID();
$template_settings = wcap_get_popup_template_for_page( $page_id, 'exit_intent' );

?>
<div class = "wcap_container" id = "wcap_popup_main_div">
            <div id="popup_model_4" class="modal">
                <div class="popup-model-content align-center"><center>
                    <div v-if="wcap_image_file_name_ei !== ''">
                        <img v-bind:src="wcap_image_path + wcap_image_file_name_ei" width="100" height="104" style="border-radius:50%;" />
                    </div>
                    
                    <h1 v-model = "wcap_quick_ck_heading" v-bind:style = "wcap_ei_popup_heading">{{wcap_quick_ck_heading}}</h1>
                    <p v-bind:style = "wcap_ei_popup_text" v-model = "wcap_quick_ck_text">{{wcap_quick_ck_text}}</p>

                    <form class="popup-form complete-my-order-form" action = "" name = "wcap_modal_form">
                        <button name="add-to-cart" class="wcap_popup_button" v-bind:style = "wcap_ei_button" v-model = "wcap_quick_ck_button" id="wcap_ei_quick_ck_button">{{wcap_quick_ck_button}}</button>
                        <?php if ( 'on' === $template_settings['wcap_atc_auto_apply_coupon_enabled'] && '' !== $template_settings['wcap_atc_coupon_type'] ) { ?>
                        <div id="wcap_coupon_auto_applied" class="woocommerce-info">
                            <p class="wcap_atc_coupon_auto_applied_msg" > {{wcap_atc_coupon_applied_msg}} </p>
                        </div>
                    <?php } ?>
                    </form>
                    </center>
                </div>
                <div class ="wcap_popup_close" id="wcap_ei_popup_close" ></div>
            </div>
        </div>
<!-- jQuery Modal -->
<?php // phpcs:disable ?>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.0.0/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.js"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.css" />