<?php

/**
 * Add to Cart popup modal template, it wll be displayed on shop, category, and products pages. 
 * @author   Tyche Softwares
 * @package Abandoned-Cart-Pro-for-WooCommerce/Frontend/ATC-Template
 * @since 6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}


?>
<!-- Modal content 3 -->
<div class = "wcap_container" id = "wcap_popup_main_div">
            <div id="popup_model_3" class="modal">
                <div class="popup-model-content align-center">
                    <center>
                    <div v-if="wcap_image_file_name !== ''">
                        <img v-bind:src="wcap_image_path + wcap_image_file_name" width="100" height="104" style="border-radius:50%;" />
                    </div>
                    <?php do_action( 'wcap_atc_after_heading_texts' ); ?> 
                    <h1 v-model = "wcap_heading_section_text_email" v-bind:style = "wcap_atc_popup_heading">{{wcap_heading_section_text_email}}</h1>
                    <p v-bind:style = "wcap_atc_popup_text" v-model = "wcap_text_section_text_field">{{wcap_text_section_text_field}}</p>

                    <form action = "" name = "wcap_modal_form" id="wcap_modal_form" >
                        <?php
                        // check if any message is present in the settings
                        $guest_msg = get_option( 'wcap_guest_cart_capture_msg' );
                        
                        if( isset( $guest_msg ) && '' != $guest_msg ) {
                            ?>
                            <p><small><?php esc_html_e( $guest_msg, 'woocommerce-ac' );//phpcs:ignore ?></small></p>
                            <?php 
                        } 
                        do_action( 'wcap_atc_before_form_fields' ); ?>
                        
                        <div class="email_address" id="">
                            <input class = "wcap_popup_input" id = "wcap_popup_input" type = "email" name = "wcap_email" v-bind:placeholder = wcap_email_placeholder_section_input_text>
                        </div>
                        <?php if ( 'on' === $template_settings['wcap_atc_capture_phone'] ) :?>
                        <div class="phone_number" id="">
                            <input id="wcap_atc_phone" class="wcap_popup_input" type="text" name="wcap_atc_phone" v-bind:placeholder="wcap_phone_placeholder_section_input_text" />
							<?php if ( 'on' === $template_settings['wcap_sms_consent_checkbox'] ) : ?>
							<p>
							<input type='checkbox' id='wcap_sms_consent' /><span id='wcap_sms_consent_block'> <span style='font-size: small'> <?php echo esc_html( $template_settings['wcap_sms_consent_message'] ); ?> </span></span>
							</p>
							<?php endif; ?>

                            <?php if ( 'on' === $template_settings['wcap_wa_consent_checkbox'] ) : ?>
							<p>
							<input type='checkbox' id='wcap_whatsapp_consent' /><span id='wcap_whatsapp_consent_block'> <span style='font-size: small'> <?php echo esc_html( $template_settings['wcap_wa_consent_message'] ); ?> </span></span>
							</p>
							<?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <?php if ( 'on' === $template_settings['wcap_email_consent_checkbox'] ) : ?>
                            <p>
                            <input type='checkbox' id='wcap_email_consent' /><span id='wcap_email_consent_block'> <span style='font-size: small'> <?php echo esc_html( $template_settings['wcap_email_consent_message'] ); ?> </span></span>
                            </p>
                        <?php endif; ?>
                        <span id = "wcap_placeholder_validated_msg" class = "wcap_placeholder_validated_msg" > Please enter a valid email address.</span>

                        <?php do_action( 'wcap_atc_after_email_field' ); ?>

                        <button v-bind:style = "wcap_atc_button" v-model = "wcap_button_section_input_text" class="wcap_popup_button">{{wcap_button_section_input_text}}</button>
                        <?php if ( 'on' !== $template_settings['wcap_atc_mandatory_email'] && 'on' !== $template_settings['wcap_switch_atc_phone_mandatory']  ) : ?>
                        <div id ="wcap_non_mandatory_text_wrapper" class = "wcap_non_mandatory_text_wrapper">
                            <a class = "wcap_popup_non_mandatory_button" href = "" v-model = "wcap_non_mandatory_modal_input_text" > {{wcap_non_mandatory_modal_input_text}}
                            </a>
                        </div>
                    <?php endif; ?>
                    <?php if ( 'on' === $template_settings['wcap_atc_auto_apply_coupon_enabled'] && '' !== $template_settings['wcap_atc_coupon_type'] ) { ?>
                        <div id="wcap_coupon_auto_applied" class="woocommerce-info">
                            <p class="wcap_atc_coupon_auto_applied_msg" > {{wcap_atc_coupon_applied_msg}} </p>
                        </div>
                    <?php } ?>
                    </form>
                </center>
                </div>
                <div class = "wcap_popup_close" ></div>
            </div>
    </div>
<!-- jQuery Modal -->
<?php // phpcs:disable ?>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.0.0/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.js"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.css" />