<?php
/**
 * Include header for Admin pages
 *
 * @since 8.23.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<?php

$settings_based_on_license = apply_filters(
    'wcap_settings_based_on_license',
    array(
        'general' => array(
            'ac_capture_email_from_forms',
            'ac_email_forms_classes',
        ),
        'unsubscribe_emails' => array(),
    )
);

?>
<template id="general_settings">
        <!-- Content Area -->
        <div class="ac-content-area" id="general_settings_cover" >
			<div class="container-fluid pl-info-wrap" ref="save_message" id="save_message" v-show="saved_message">
					<div class="row">
						<div class="col-md-12">
							<div class="alert alert-success alert-dismissible fade show" role="alert">
									{{message_saved}}
									<button type="button" class="close" data-dismiss="alert" aria-label="Close">
										<span aria-hidden="true">&times;</span>
									</button>
							</div>
						</div> 
					</div>
				</div>
				<div id="ac_events_loader" v-show="saving">
					<div class="ac_events_loader_wrapper">
						{{message}}...<img src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/ajax-loader.gif">
					</div>
				</div>

            <div class="container fas-page-wrap" >
                <div class="row">
                    <div class="col-md-12">
                        <div class="ac-page-head phw-btn">
                            <div class="col-left">
                                <!-- <h1><?php //esc_html_e( 'General', 'woocommerce-ac' ); ?></h1> -->
<p><?php esc_html_e( 'Change settings for sending email notifications to Customers, to Admin, Tracking Coupons etc.', 'woocommerce-ac' ); ?></p>								
                            </div>
                           
                        </div>

                        <div class="wbc-accordion">
                            <div class="panel-group ac-accordian" id="wbc-accordion">
                                <!-- First Panel -->
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h2 class="panel-title" data-toggle="collapse" data-target="#collapseOne" aria-expanded="false">
                                           <?php esc_html_e( 'Email Settings','woocommerce-ac' );?>
                                        </h2>
                                    </div>
                                    <div id="collapseOne" class="panel-collapse collapse">
                                        <div class="panel-body">
                                            <div class="tbl-mod-1">
                                                <div class="tm1-row flx-center">
                                                    <div class="col-left">
                                                        <label><?php esc_html_e( "Enable abandoned cart reminder emails:", 'woocommerce-ac' ); ?></label>
                                                    </div>
                                                    <div class="col-right">
                                                        <div class="rc-flx-wrap flx-aln-center">
                                                            <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "Email notifications of the abandoned carts will be sent to the users. Note: Enabling or disabling this option won’t affect the cart abandonment tracking.", 'woocommerce-ac' ); ?>">
                                                            <label class="el-switch el-switch-green">
                                                                <input type="checkbox" value="on"  id="ac_enable_cart_emails" name="ac_enable_cart_emails"  v-model="settings.ac_enable_cart_emails" true-value="on" false-value="" >
                                                                <span class="el-switch-style"></span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="tm1-row flx-center">
                                                    <div class="col-left">
                                                        <label><?php esc_html_e( "Email admin On Order Recovery:", 'woocommerce-ac' ); ?></label>
                                                    </div>
                                                    <div class="col-right">
                                                        <div class="rc-flx-wrap flx-aln-center">
                                                            <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "Sends email to Admin if an Abandoned Cart Order is recovered.", 'woocommerce-ac' ); ?>">
                                                            <label class="el-switch el-switch-green">
                                                                <input type="checkbox"  id="ac_email_admin_on_recovery" name="ac_email_admin_on_recovery"  v-model="settings.ac_email_admin_on_recovery" true-value="on" false-value="" >
                                                                <span class="el-switch-style"></span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
												<div class="tm1-row flx-center">
													<div class="col-left">
														<label><?php esc_html_e( 'Email admin on cart abandonment:', 'woocommerce-ac' ); ?></label>
													</div>
													<div class="col-right">
														<div class="rc-flx-wrap flx-aln-center">
															<img class="tt-info" src="<?php echo esc_url( esc_url( WCAP_PLUGIN_URL ) ); ?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( 'Sends email to the admin if a cart is marked as abandoned by the plugin.', 'woocommerce-ac' ); ?>">
															<label class="el-switch el-switch-green">
																<input type="checkbox"  id="wcap_email_admin_on_abandonment" name="wcap_email_admin_on_abandonment"  v-model="settings.wcap_email_admin_on_abandonment" true-value="on" false-value="" >
																<span class="el-switch-style"></span>
															</label>
														</div>
													</div>
												</div>
												<div class="tm1-row flx-center" v-show="settings.wcap_email_admin_on_abandonment">
													<div class="col-left">
														<label><?php esc_html_e( 'Email address to send abandonment notifications:', 'woocommerce-ac' ); ?></label>
													</div>
													<div class="col-right">
														<div class="rc-flx-wrap flx-aln-center">
															<img class="tt-info" src="<?php echo esc_url( esc_url( WCAP_PLUGIN_URL ) ); ?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( 'You can enter multiple email addresses to which abandoned cart notifications will be sent. Enter email addresses comma separated or one per line.', 'woocommerce-ac' ); ?>">
															<textarea  id="wcap_email_admin_custom_addresses" name="wcap_email_admin_custom_addresses"  v-model="settings.wcap_email_admin_custom_addresses" >{{settings.wcap_email_admin_custom_addresses}}</textarea>
															<br />
														</div>
														<p class='help-text'><?php echo esc_html_e( 'Enter email addresses comma separated or one per line', 'woocommerce-ac' ); ?></p>
													</div>
												</div>
												<div class="tm1-row flx-center wcap-cart-source" v-show="settings.wcap_email_admin_on_abandonment">
													<div class="col-left">
														<label><?php esc_html_e( 'Select cart sources to send abandonment notifications for:', 'woocommerce-ac' ); ?></label>
													</div>
													<div class="col-right">
														<div class="rc-flx-wrap flx-aln-center wcap-cart-source">
															<img class="tt-info" src="<?php echo esc_url( esc_url( WCAP_PLUGIN_URL ) ); ?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( 'Select the cart sources for which the notifications should be sent for.', 'woocommerce-ac' ); ?>">
															<div class='wcap-left'>
																<label class="el-switch el-switch-green">
																	<input type="checkbox"  id="wcap_email_admin_cart_source_all" name="wcap_email_admin_cart_source_all"  v-model="settings.wcap_email_admin_cart_source.all" true-value="on" false-value="" v-on:change="process_source_all()" >
																	<span class="el-switch-style"></span><label class='wcap-source-names'><?php esc_html_e( 'All', 'woocommerce-ac' ); ?></label>
																</label><br>
																<label class="el-switch el-switch-green">
																	<input type="checkbox"  id="wcap_email_admin_cart_source_checkout" name="wcap_email_admin_cart_source_checkout"  v-model="settings.wcap_email_admin_cart_source.checkout" true-value="on" false-value="" v-on:change="process_source()" >
																	<span class="el-switch-style"></span><label class='wcap-source-names'><?php esc_html_e( 'Checkout Page', 'woocommerce-ac' ); ?></label>
																</label><br>
																<label class="el-switch el-switch-green">
																	<input type="checkbox"  id="wcap_email_admin_cart_source_profile" name="wcap_email_admin_cart_source_profile"  v-model="settings.wcap_email_admin_cart_source.profile" true-value="on" false-value="" v-on:change="process_source()" >
																	<span class="el-switch-style"></span><label class='wcap-source-names'><?php esc_html_e( 'Product Page', 'woocommerce-ac' ); ?></label>
																</label><br>
																<label class="el-switch el-switch-green">
																	<input type="checkbox" id="wcap_email_admin_cart_source_atc" name="wcap_email_admin_cart_source_atc"  v-model="settings.wcap_email_admin_cart_source.atc" true-value="on" false-value="" v-on:change="process_source()" >
																	<span class="el-switch-style"></span><label class='wcap-source-names'><?php esc_html_e( 'Add to Cart Popup', 'woocommerce-ac' ); ?></label>
																</label>
															</div>
															<div class='wcap-right'>
																<label class="el-switch el-switch-green">
																	<input type="checkbox" id="wcap_email_admin_cart_source_exit_intent" name="wcap_email_admin_cart_source_exit_intent"  v-model="settings.wcap_email_admin_cart_source.exit_intent" true-value="on" false-value="" v-on:change="process_source()" >
																	<span class="el-switch-style"></span><label class='wcap-source-names'><?php esc_html_e( 'Exit Intent Popup', 'woocommerce-ac' ); ?></label>
																</label><br>
																<label class="el-switch el-switch-green">
																	<input type="checkbox" id="wcap_email_admin_cart_source_url" name="wcap_email_admin_cart_source_url" v-model="settings.wcap_email_admin_cart_source.url" true-value="on" false-value="" v-on:change="process_source()" >
																	<span class="el-switch-style"></span><label class='wcap-source-names'><?php esc_html_e( 'URLs', 'woocommerce-ac' ); ?></label>
																</label><br>
																<label class="el-switch el-switch-green">
																	<input type="checkbox" id="wcap_email_admin_cart_source_form" name="wcap_email_admin_cart_source_form" v-model="settings.wcap_email_admin_cart_source.custom_form" true-value="on" false-value="" v-on:change="process_source()" >
																	<span class="el-switch-style"></span><label class='wcap-source-names'><?php esc_html_e( 'Custom Forms', 'woocommerce-ac' ); ?></label>
																</label>
															</div>
														</div>
													</div>
												</div>
                                                <div class="tm1-row flx-center">
                                                    <div class="col-left">
                                                        <label><?php esc_html_e( "Send reminder emails for newly abandoned carts after X days of order placement:", 'woocommerce-ac' ); ?></label>
                                                    </div>
                                                    <div class="col-right">
                                                        <div class="rc-flx-wrap flx-aln-center">
                                                            <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "Reminder emails will be sent for newly abandoned carts only after X days of a previously placed order for a user with the same email address as that of the abandoned cart", 'woocommerce-ac' ); ?>">
                                                            <input class="ib-md" type="text" placeholder="10" name="ac_cart_abandoned_after_x_days_order_placed" id="ac_cart_abandoned_after_x_days_order_placed" maxlength="40" v-model="settings.ac_cart_abandoned_after_x_days_order_placed"/>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php if ( isset( $settings_based_on_license['general'] ) && in_array( 'ac_capture_email_from_forms', $settings_based_on_license['general'], true ) ) : ?>
                                                <div class="tm1-row flx-center">
                                                    <div class="col-left">
                                                        <label>
                                                            <?php esc_html_e( 'Capture email address from custom fields', 'woocommerce-ac' ); ?>:
                                                            </label>
                                                    </div>
                                                    <div class="col-right">
                                                        <div class="rc-flx-wrap flx-aln-center">
                                                            <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "Enable this setting to capture email address from other form fields.", 'woocommerce-ac' ); ?>">
                                                            <label class="el-switch el-switch-green">
                                                                <input type="checkbox" value="on" id="ac_capture_email_from_forms" name="ac_capture_email_from_forms" v-model="settings.ac_capture_email_from_forms" true-value="on" false-value="">
                                                                <span class="el-switch-style"></span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endif; ?>

                                                <?php if ( isset( $settings_based_on_license['general'] ) && in_array( 'ac_email_forms_classes', $settings_based_on_license['general'], true ) ) : ?>
                                                <div class="tm1-row flx-center flx-center">
                                                    <div class="col-left">
                                                        <label><?php esc_html_e( "Class Names Of The Form Fields:", 'woocommerce-ac' ); ?></label>
                                                    </div>
                                                    <div class="col-right">
                                                        <div class="rc-flx-wrap flx-aln-center">
                                                            <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "Enter class names of fields separated by commas from where email needs to be captured.", 'woocommerce-ac' ); ?>">
                                                            <input class="ib-xl" type="text"  id="ac_email_forms_classes" name="ac_email_forms_classes"  v-model="settings.ac_email_forms_classes"  placeholder="Class Name 1,Class Name 2,Class Name 3">
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <div class="tm1-row flx-center">
                                                    <div class="col-left">
                                                        <label><?php esc_html_e( " Capture Email address from URL:", 'woocommerce-ac' ); ?></label>
                                                    </div>
                                                    <div class="col-right">
                                                        <div class="rc-flx-wrap flx-aln-center">
                                                            <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "If your site URL contains the same key, the plugin will capture the value as an email address of the customer.", 'woocommerce-ac' ); ?>">
                                                            <input class="ib-md" type="text"  id="ac_capture_email_address_from_url" name="ac_capture_email_address_from_url"  v-model="settings.ac_capture_email_address_from_url"  placeholder="" />
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="tm1-row flx-center">
                                                    <div class="col-left">
                                                        <label>
                                                            <?php esc_html_e( 'Enable Email Verification', 'woocommerce-ac' ); ?>:
                                                            </label>
                                                    </div>
                                                    <div class="col-right">
                                                        <div class="rc-flx-wrap flx-aln-center">
                                                            <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "Enable this checkbox to allow email verification to be done via DeBounce API services.", 'woocommerce-ac' ); ?>">
                                                            <label class="el-switch el-switch-green">
                                                                <input type="checkbox" id="wcap_enable_debounce" name="wcap_enable_debounce"  v-model="settings.wcap_enable_debounce" true-value="on" false-value="">
                                                                <span class="el-switch-style"></span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="tm1-row flx-center">
                                                    <div class="col-left">
                                                        <label><?php esc_html_e( "Enter DeBounce API Key:", 'woocommerce-ac' ); ?></label>
                                                    </div>
                                                    <div class="col-right">
                                                        <div class="rc-flx-wrap flx-aln-center">
                                                            <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "Enter DeBounce JS API Key.", 'woocommerce-ac' ); ?>">
                                                            <input class="ib-md" type="text"  id="ac_debounce_api" name="ac_debounce_api"  v-model="settings.ac_debounce_api" placeholder="Enter DeBounce JS API Key" />
                                                        </div>
                                                    </div>
                                                </div>
												
												<div class="ss-foot">
                                                    <button type="button"  @click="save_general_settings( 'collapseOne' )" ><?php esc_html_e( 'Save', 'woocommerce-ac' ); ?></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Second Panel -->
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h2 class="panel-title" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false">
                                       <?php esc_html_e( 'Cart Settings', 'woocommerce-ac' ); ?>
                                    </h2>
                                </div>
                                <div id="collapseTwo" class="panel-collapse collapse">
                                    <div class="panel-body">
                                        <div class="tbl-mod-1">
                                            <div class="tm1-row flx-center">
                                                <div class="col-left">
                                                    <label><?php esc_html_e( "Cart abandoned cut-off time for logged-in users", 'woocommerce-ac' ); ?></label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap flx-aln-center">
                                                        <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "For logged-in users consider cart abandoned after X minutes of item being added to cart & order not placed.", 'woocommerce-ac' ); ?>">
                                                        <input class="ib-md" type="text"  id="ac_cart_abandoned_time" name="ac_cart_abandoned_time"  v-model="settings.ac_cart_abandoned_time" placeholder="10" />
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="tm1-row flx-center">
                                                <div class="col-left">
                                                    <label><?php esc_html_e( "Cart abandoned cut-off time for guest users", 'woocommerce-ac' ); ?></label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap flx-aln-center">
                                                        <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "For guest users & visitors consider cart abandoned after X minutes of item being added to cart & order not placed.", 'woocommerce-ac' ); ?>">
                                                        <input class="ib-md" type="text" id="ac_cart_abandoned_time_guest" name="ac_cart_abandoned_time_guest"  v-model="settings.ac_cart_abandoned_time_guest" placeholder="20" />
                                                    </div>
                                                </div>
                                            </div>                                            

                                            <div class="tm1-row flx-center">
                                                <div class="col-left">
                                                    <label>
                                                        <?php esc_html_e( 'Do not track carts of guest users', 'woocommerce-ac' ); ?>:</label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap flx-aln-center">
                                                        <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "Abandoned carts of guest users will not be tracked.", 'woocommerce-ac' ); ?>">
                                                        <label class="el-switch el-switch-green">
                                                            <input type="checkbox" id="ac_disable_guest_cart_email" name="ac_disable_guest_cart_email"  v-model="settings.ac_disable_guest_cart_email" true-value="on" false-value="" @click="disable_atc_popup('event')" >
                                                            <span class="el-switch-style"></span>
                                                        </label>
                                                    </div>
                                                    <p id ="wcap_atc_disable_msg" class="wcap_atc_disable_msg"></p>
                                                </div>
                                            </div> 
											
											 <div class="tm1-row flx-center" v-show="settings.ac_disable_guest_cart_email !== 'on'">
                                                <div class="col-left">
                                                    <label>
                                                        <?php esc_html_e( 'Start tracking from Cart Page', 'woocommerce-ac' ); ?>:</label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap flx-aln-center">
                                                        <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "Enable tracking of abandoned products & carts even if customer does not visit the checkout page or does not enter any details on the checkout page like Name or Email. Tracking will begin as soon as a visitor adds a product to their cart and visits the cart page.", 'woocommerce-ac' ); ?>">
                                                        <label class="el-switch el-switch-green">
                                                            <input type="checkbox" id="ac_track_guest_cart_from_cart_page" name="ac_track_guest_cart_from_cart_page"  v-model="settings.ac_track_guest_cart_from_cart_page" true-value="on" false-value="" >
                                                            <span class="el-switch-style"></span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div> 

                                            
                                            <div class="tm1-row flx-center">
                                                <div class="col-left">
                                                    <label>
                                                        <?php esc_html_e( 'Do not track carts of logged-in users', 'woocommerce-ac' ); ?>:</label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap flx-aln-center">
                                                        <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "Abandoned carts of logged-in users will not be tracked.", 'woocommerce-ac' ); ?>">
                                                        <label class="el-switch el-switch-green">
                                                            <input type="checkbox" id="ac_disable_logged_in_cart_email" name="ac_disable_logged_in_cart_email"  v-model="settings.ac_disable_logged_in_cart_email" true-value="on" false-value=""  >
                                                            <span class="el-switch-style"></span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>  

                                            <div class="tm1-row flx-center">
                                                <div class="col-left">
                                                    <label>
                                                    <?php esc_html_e( 'Add product to cart when close icon is clicked in the popup modal?', 'woocommerce-ac' ); ?>:</label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap flx-aln-center">
                                                        <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "Enable this setting if you want the product to the added to cart when the user clicks on the Close Icon in the Add to Cart Popup Window.", 'woocommerce-ac' ); ?>">
                                                        <label class="el-switch el-switch-green">
                                                            <input type="checkbox" id="wcap_atc_close_icon_add_product_to_cart" name="wcap_atc_close_icon_add_product_to_cart"  v-model="settings.wcap_atc_close_icon_add_product_to_cart" true-value="on" false-value="" >
                                                            <span class="el-switch-style"></span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div> 
											
											<div class="ss-foot">
                                                    <button type="button"  @click="save_general_settings( 'collapseTwo' )" ><?php esc_html_e( 'Save', 'woocommerce-ac' ); ?></button>
                                                </div>

											
                                        </div>
                                    </div>
                                </div>
                            </div>

                                <!-- Third Panel -->
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h2 class="panel-title" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false">
                                        <?php esc_html_e( 'Settings', 'woocommerce-ac' ); ?>
                                    </h2>
                                </div>
                                <div id="collapseThree" class="panel-collapse collapse">
                                    <div class="panel-body">
                                        <div class="tbl-mod-1">
                                            <div class="tm1-row flx-center">
                                                <div class="col-left">
                                                    <label><?php esc_html_e( "Automatically Delete Abandoned Orders after X days", 'woocommerce-ac' ); ?></label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap flx-aln-center">
                                                        <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "Automatically delete abandoned cart orders after X days.", 'woocommerce-ac' ); ?>">
                                                        <input class="ib-md" type="text"  id="ac_delete_abandoned_order_days" name="ac_delete_abandoned_order_days"  v-model="settings.ac_delete_abandoned_order_days" placeholder="365" />
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="tm1-row flx-center">
                                                <div class="col-left">
                                                    <label>
                                                        <?php esc_html_e( 'Remove Data on Uninstall?', 'woocommerce-ac' ); ?>:</label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap flx-aln-center">
                                                        <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "Enable this setting if you want to completely remove Abandoned Cart data when plugin is deleted.", 'woocommerce-ac' ); ?>">
                                                        <label class="el-switch el-switch-green">
                                                            <input type="checkbox"  id="wcac_delete_plugin_data" name="wcac_delete_plugin_data"  v-model="settings.wcac_delete_plugin_data" true-value="on" false-value="" >
                                                            <span class="el-switch-style"></span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="tm1-row flx-center">
                                                <div class="col-left">
                                                    <label><?php esc_html_e( 'Enable AI Reporting Abilities (lets AI assistants query your abandoned-cart reports):', 'woocommerce-ac' ); ?></label>
                                                    <div>
                                                        <a href="https://github.com/LittleCodr" target="_blank" style="font-size: 14px;"><?php esc_html_e( 'View Documentation', 'woocommerce-ac' ); ?></a>
                                                    </div>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap flx-aln-center">
                                                        <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ); ?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_attr_e( 'Lets AI assistants (e.g. Claude, connected via MCP) query abandoned-cart reporting data such as top abandoned products, recovered revenue, and template performance. Read-only, requires the same admin capability as this settings screen, and exposes no customer data.', 'woocommerce-ac' ); ?>">
                                                        <label class="el-switch el-switch-green">
                                                            <input type="checkbox" id="wcap_enable_ai_abilities" name="wcap_enable_ai_abilities" v-model="settings.wcap_enable_ai_abilities" true-value="on" false-value="" >
                                                            <span class="el-switch-style"></span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="tm1-row flx-center flx-center">
                                                <div class="col-left">
                                                    <label><?php esc_html_e( "Reset Usage Tracking", 'woocommerce-ac' ); ?></label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="row-box-1 flx-center">
                                                        <div class="rb1-left">
                                                            <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" style="position: relative; top: -4px;"  title="<?php esc_html_e( "This will reset your usage tracking settings, causing it to show the opt-in banner and not send any data.", 'woocommerce-ac' ); ?>">
                                                        </div>
                                                        <div class="rb1-right">
                                                            <div class="rb1-row">
                                                                <input class="trietary-btn reverse" type="reset" name="" value="Reset"  @click="reset_usage_tracking">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
											
											<div class="ss-foot">
                                                    <button type="button"  @click="save_general_settings( 'collapseThree' )" ><?php esc_html_e( 'Save', 'woocommerce-ac' ); ?></button>
                                                </div>
												
												
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!--Four Panel -->
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h2 class="panel-title" data-toggle="collapse" data-target="#collapseFour" aria-expanded="false">
                                       <?php esc_html_e( 'GDPR Consent', 'woocommerce-ac' ); ?> 
                                    </h2>
                                </div>
                                <div id="collapseFour" class="panel-collapse collapse">
                                    <div class="panel-body">
                                        <div class="tbl-mod-1">
                                            <div class="tm1-row flx-center">
                                                <div class="col-left">
                                                    <label>
                                                        <?php esc_html_e( 'Enable GDPR Notice', 'woocommerce-ac' ); ?>:</label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap flx-aln-center">
                                                        <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="In compliance with GDPR, add a message on the Checkout page and Email Address Capture pop-up to inform Guest users of how their data is being used.
For example: Your email address will help us support your shopping experience throughout the site. Please check our Privacy Policy to see how we use your personal data.">
                                                        <label class="el-switch el-switch-green">
                                                            <input type="checkbox" id="wcap_enable_gdpr_consent" name="wcap_enable_gdpr_consent"  v-model="settings.wcap_enable_gdpr_consent" true-value="on" false-value="" >
                                                            <span class="el-switch-style"></span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>  

                                            <div class="tm1-row flx-center">
                                                <div class="col-left">
                                                    <label><?php esc_html_e( "Message to be displayed for Guest users when tracking their carts:", 'woocommerce-ac' ); ?></label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap">
                                                        <img class="tt-info aw-text" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "In compliance with GDPR, add a message on the Shop & Product pages to inform Guest users of how their data is being used. For example: Please check our Privacy Policy to see how we use your personal data.", 'woocommerce-ac' ); ?>">
                                                        <textarea class="ta-sm"  placeholder="Your email address will help us support your shopping experience throughout the site. Please check our Privacy Policy to see how we use your personal data. "  id="wcap_guest_cart_capture_msg" name="wcap_guest_cart_capture_msg"  v-model="settings.wcap_guest_cart_capture_msg"></textarea>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="tm1-row flx-center">
                                                <div class="col-left">
                                                    <label><?php esc_html_e( "Message to be displayed for registered users when tracking their carts:", 'woocommerce-ac' ); ?></label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap">
                                                        <img class="tt-info aw-text" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "In compliance with GDPR, add a message on the Shop & Product pages to inform Registered users of how their data is being used. For example: Please check our Privacy Policy to see how we use your personal data.", 'woocommerce-ac' ); ?>">
                                                        <textarea class="ta-sm" placeholder="Please check our Privacy Policy to see how we use your personal data. " id="wcap_logged_cart_capture_msg" name="wcap_logged_cart_capture_msg"  v-model="settings.wcap_logged_cart_capture_msg"></textarea>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="tm1-row flx-center">
                                                <div class="col-left">
                                                    <label><?php esc_html_e( "GDPR Compliance Type:", 'woocommerce-ac' ); ?></label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap flx-aln-center">
                                                        <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "In compliance with GDPR, allow site visitors (guests) to opt in to cart tracking. This message will be displayed alongside the GDPR message above.", 'woocommerce-ac' ); ?>">
                                                        <select class="ib-md"  id="wcap_select_guest_cart_tracking" name="wcap_select_guest_cart_tracking"  v-model="settings.wcap_select_guest_cart_tracking">
                                                            <option value="opt-out" ><?php  esc_html_e( 'Opt Out of Cart Tracking', 'woocommerce-ac' ) ?></option>
                                                            <option value="opt-in" ><?php  esc_html_e( 'Opt In to Cart Tracking', 'woocommerce-ac' ) ?></option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="tm1-row flx-center" v-show="'opt-out' === settings.wcap_select_guest_cart_tracking">
                                                <div class="col-left">
                                                    <label><?php esc_html_e( "Allow the visitor to opt out of cart tracking:", 'woocommerce-ac' ); ?></label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap flx-aln-center">
                                                        <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "In compliance with GDPR, allow the site visitor (guests & registered users) to opt out from cart tracking. This message will be displayed in conjunction with the GDPR message above.", 'woocommerce-ac' ); ?>">
                                                        <input class="ib-md" type="text" placeholder=""  id="wcap_gdpr_allow_opt_out" name="wcap_gdpr_allow_opt_out"  v-model="settings.wcap_gdpr_allow_opt_out" />
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="tm1-row flx-center" v-show="'opt-out' === settings.wcap_select_guest_cart_tracking">
                                                <div class="col-left">
                                                    <label><?php esc_html_e( "Message to be displayed when the user chooses to opt out of cart tracking:", 'woocommerce-ac' ); ?></label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap">
                                                        <img class="tt-info aw-text" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info"  data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "Message to be displayed when the user chooses to opt out of cart tracking.", 'woocommerce-ac' ); ?>">
                                                        <textarea class="ta-sm" placeholder="Message to be displayed when the user chooses to opt out of cart tracking. " id="wcap_gdpr_opt_out_message" name="wcap_gdpr_opt_out_message"  v-model="settings.wcap_gdpr_opt_out_message" ></textarea>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="tm1-row flx-center" v-show="'opt-in' === settings.wcap_select_guest_cart_tracking" >
                                                <div class="col-left">
                                                    <label><?php esc_html_e( "Allow the visitor to opt in to cart tracking:", 'woocommerce-ac' ); ?></label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap flx-aln-center">
                                                        <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "In compliance with GDPR, allow site visitors (guests) to opt in to cart tracking. This message will be displayed in conjunction with the GDPR message above.", 'woocommerce-ac' ); ?>">
                                                        <input class="ib-md" type="text" placeholder=""  id="wcap_gdpr_allow_opt_in" name="wcap_gdpr_allow_opt_in"  v-model="settings.wcap_gdpr_allow_opt_in" />
                                                    </div>
                                                </div>
                                            </div>

											<div class="tm1-row flx-center">
												<div class="col-left">
													<label>
														<?php esc_html_e( 'Enable SMS Consent', 'woocommerce-ac' ); ?>:</label>
												</div>
												<div class="col-right">
													<div class="rc-flx-wrap flx-aln-center">
														<img class="tt-info" src="<?php echo esc_url( esc_url( WCAP_PLUGIN_URL ) ); ?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="Enable this setting to display a notice informing customers that their phone number and cart data are saved to send abandonment reminders.">
														<label class="el-switch el-switch-green">
															<input type="checkbox" id="wcap_enable_sms_consent" name="wcap_enable_sms_consent"  v-model="settings.wcap_enable_sms_consent" true-value="on" false-value="" >
															<span class="el-switch-style"></span>
														</label>
													</div>
												</div>
											</div>

											<div class="tm1-row flx-center">
												<div class="col-left">
													<label><?php esc_html_e( 'SMS Consent Message:', 'woocommerce-ac' ); ?></label>
												</div>
												<div class="col-right">
													<div class="rc-flx-wrap">
														<img class="tt-info aw-text" src="<?php echo esc_url( esc_url( WCAP_PLUGIN_URL ) ); ?>/assets/images/icon-info.svg" alt="Info"  data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( 'Message to be displayed below the Phone field at Checkout informing users that their phone number and cart data are saved to send abandonment reminders.', 'woocommerce-ac' ); ?>">
														<textarea class="ta-sm" placeholder="Message to be displayed along with the SMS consent checkbox. " id="wcap_sms_consent_msg" name="wcap_sms_consent_msg"  v-model="settings.wcap_sms_consent_msg" ></textarea>
													</div>
												</div>
											</div>
											<div class="ss-foot">
                                                    <button type="button" @click="save_general_settings( 'collapseFour' )"  ><?php esc_html_e( 'Save', 'woocommerce-ac' ); ?></button>
                                                </div>
												
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!--Five Panel -->
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h2 class="panel-title" data-toggle="collapse" data-target="#collapseFive" aria-expanded="false">
                                       <?php esc_html_e( 'Coupon Settings', 'woocommerce-ac' ); ?> 
                                    </h2>
                                </div>
                                <div id="collapseFive" class="panel-collapse collapse">
                                    <div class="panel-body">
                                        <div class="tbl-mod-1">
                                            <div class="tm1-row flx-center">
                                                <div class="col-left">
                                                    <label><?php esc_html_e( " Delete Coupons Automatically:", 'woocommerce-ac' ); ?></label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap flx-aln-center">
                                                        <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "Enable this setting if you want to completely remove the expired and used coupon codes created by the plugin automatically every 15 days.", 'woocommerce-ac' ); ?>">
                                                        <label class="el-switch el-switch-green">
                                                            <input type="checkbox" id="wcap_delete_coupon_data" name="wcap_delete_coupon_data"  v-model="settings.wcap_delete_coupon_data" true-value="on" false-value="">
                                                            <span class="el-switch-style"></span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>  

                                            <div class="tm1-row flx-center flx-center">
                                                <div class="col-left">
                                                    <label><?php esc_html_e( "Delete Coupons Manually", 'woocommerce-ac' ); ?></label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="row-box-1 flx-center">
                                                        <div class="rb1-left">
                                                            <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" style="position: relative; top: -4px;" data-toggle="tooltip" data-placement="top"  title='<?php esc_html_e( 'If you want to completely remove the expired and used coupon code now then click on "Delete" button.', 'woocommerce-ac' ); ?>'>
                                                        </div>
                                                        <div class="rb1-right">
                                                            <div class="rb1-row">
                                                                <input class="trietary-btn reverse" type="reset" name="" value="Delete" @click="delete_coupons_manually" >
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
											
											<div class="ss-foot">
                                                    <button type="button" @click="save_general_settings( 'collapseFive' )"  ><?php esc_html_e( 'Save', 'woocommerce-ac' ); ?></button>
                                                </div>
												
                                        </div>
                                    </div>
                                </div> 
                            </div>
                            <!--Six Panel -->
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h2 class="panel-title" data-toggle="collapse" data-target="#collapseSix" aria-expanded="false">
                                        <?php esc_html_e( 'Setting for sending Emails & SMS using Action Scheduler', 'woocommerce-ac' ); ?>
                                    </h2>
                                </div>
                                <div id="collapseSix" class="panel-collapse collapse">
                                    <div class="panel-body">
                                        <div class="tbl-mod-1">
                                            <div class="tm1-row flx-center">
                                                <div class="col-left">
                                                    <label><?php esc_html_e( " Send Abandoned cart reminders automatically using Action Scheduler:", 'woocommerce-ac' ); ?></label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap flx-aln-center">
                                                        <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "Enabling this setting will send the abandoned cart reminders to the customer after the set time. If disabled, automated abandoned cart reminders will not be sent by the plugin. Carts will be tracked based on the settings, but no reminders will be sent.", 'woocommerce-ac' ); ?>">
                                                        <label class="el-switch el-switch-green">
                                                            <input type="checkbox" id="wcap_use_auto_cron" name="wcap_use_auto_cron"  v-model="settings.wcap_use_auto_cron" true-value="on" false-value="">
                                                            <span class="el-switch-style"></span>
                                                        </label>
                                                    </div>
                                                    <?php echo wp_kses_post( "Please visit <a href='https://github.com/LittleCodr' target='_blank'>here</a> for more information on the Action Scheduler.", 'woocommerce-ac' ); ?>
                                                </div>
                                            </div>  

                                            <div class="tm1-row flx-center">
                                                <div class="col-left">
                                                    <label><?php esc_html_e( "Run automated scheduler action after X minutes.", 'woocommerce-ac' ); ?></label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap flx-aln-center">
                                                        <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "The duration in minutes after which an action should be automatically scheduled to send email, SMS & FB reminders to customers.", 'woocommerce-ac' ); ?>">
                                                        <input class="ib-md" type="text" placeholder="2"  id="wcap_cron_time_duration" name="wcap_cron_time_duration"  v-model="settings.wcap_cron_time_duration"/>
                                                    </div>
                                                </div>
                                            </div>
											
											<div class="ss-foot">
                                                    <button type="button"  @click="save_general_settings( 'collapseSix' )" ><?php esc_html_e( 'Save', 'woocommerce-ac' ); ?></button>
                                                </div>
												
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!--Seven Panel -->
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h2 class="panel-title" data-toggle="collapse" data-target="#collapseSeven" aria-expanded="false">
                                        <?php esc_html_e( 'Rules to exclude capturing abandoned carts', 'woocommerce-ac' ); ?>
                                    </h2>
                                </div>
                                <div id="collapseSeven" class="panel-collapse collapse">
                                    <div class="panel-body">
                                        <div class="tbl-mod-1">
                                            <div class="tm1-row flx-center">
                                                <div class="col-left">
                                                    <label>
                                                        <?php esc_html_e( 'Do not capture abandoned carts for these IP addresses', 'woocommerce-ac' ); ?>:</label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap">
                                                        <img class="tt-info aw-text" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title='The carts abandoned from these IP addresses will not be tracked by the plugin. Accepts wildcards, e.g 192.168.* will block all IP addresses which starts from "192.168". Separate IP addresses with commas.'>
                                                        <textarea class="ta-sm" placeholder="Add an IP address "  id="wcap_restrict_ip_address" name="wcap_restrict_ip_address"  v-model="settings.wcap_restrict_ip_address"></textarea>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="tm1-row flx-center">
                                                <div class="col-left">
                                                    <label><?php esc_html_e( "Do not capture abandoned carts for these email addresses:", 'woocommerce-ac' ); ?></label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap">
                                                        <img class="tt-info aw-text" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"   title="<?php esc_html_e( "Carts that are abandoned using these email addresses will not be tracked by the plugin. *Separate email addresses with commas.", 'woocommerce-ac' ); ?>">
                                                        <textarea class="ta-sm" placeholder=" Add an email address  "  id="wcap_restrict_email_address" name="wcap_restrict_email_address"  v-model="settings.wcap_restrict_email_address"></textarea>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="tm1-row flx-center">
                                                <div class="col-left">
                                                    <label><?php esc_html_e( "Do not capture abandoned carts for email addresses from these domains:", 'woocommerce-ac' ); ?></label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap">
                                                        <img class="tt-info aw-text" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"   title="<?php esc_html_e( "The carts abandoned from email addresses with these domains will not be tracked by the plugin.", 'woocommerce-ac' ); ?>" >
                                                        <textarea class="ta-sm" placeholder="Add an email domain name"  id="wcap_restrict_domain_address" name="wcap_restrict_domain_address"  v-model="settings.wcap_restrict_domain_address"></textarea>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="tm1-row flx-center">
                                                <div class="col-left">
                                                    <label><?php esc_html_e( "Do not capture carts from countries:", 'woocommerce-ac' ); ?></label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap">
                                                        <img class="tt-info aw-text" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info"  data-toggle="tooltip" data-placement="top"    title="<?php esc_html_e( "The carts abandoned from these countries will not be tracked by the plugin.", 'woocommerce-ac' ); ?>">
                                                       
                                                  <?php $wc_countries_object = new WC_Countries();
														$all_countries_list  = $wc_countries_object->get_countries();
														// Next, we update the name attribute to access this element's ID in the context of the display options array.
														// We also access the show_header element of the options collection in the call to the checked() helper function.
														?>
														<select class="select2 wcap_restrict_countries" id="wcap_restrict_countries" name="wcap_restrict_countries[]" v-model="restriced_countries" multiple data-placeholder="<?php echo esc_attr_e( 'Select countries', 'woocommerce-ac' ); ?>">
															<?php
															foreach ( $all_countries_list as $code => $name ) {
																?>
																<option name = "<?php echo esc_attr( $code ); ?>" value ="<?php echo esc_attr( $code ); ?>"><?php echo esc_attr__( $name, 'woocommerce-ac' );//phpcs:ignore ?></option>
															<?php }
															?>
														</select>

												   </div>
                                                </div>
                                            </div>
												
                                        </div>
										
										<div class="ss-foot">
                                                    <button type="button" @click="save_general_settings( 'collapseSeven' )"  ><?php esc_html_e( 'Save', 'woocommerce-ac' ); ?></button>
                                                </div>
                                    </div>
									
									
                                </div>
                            </div>
                            <!--Seven Panel -->
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h2 class="panel-title" data-toggle="collapse" data-target="#collapseEight" aria-expanded="false">
                                        <?php esc_html_e( 'Settings for abandoned cart recovery emails', 'woocommerce-ac' ); ?>
                                    </h2>
                                </div>
                                <div id="collapseEight" class="panel-collapse collapse">
                                    <div class="panel-body">
                                        <div class="tbl-mod-1">
                                            <div class="tm1-row flx-center">
                                                <div class="col-left">
                                                    <label><?php esc_html_e( '"From" Name:', 'woocommerce-ac' ); ?></label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap flx-aln-center">
                                                        <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "Enter the name that should appear in the email sent.", 'woocommerce-ac' ); ?>">
                                                        <input class="ib-md" type="text" placeholder="Admin"  id="wcap_from_name" name="wcap_from_name"  v-model="settings.wcap_from_name" />
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="tm1-row flx-center">
                                                <div class="col-left">
                                                    <label><?php esc_html_e( '"From" Address:', 'woocommerce-ac' ); ?></label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap flx-aln-center">
                                                        <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "Email address from which the reminder emails should be sent. Note: This setting shall be applicable only when PHP mail function is used by your Hosting Provider. If SMTP mail plugins are used or if mail configuration is based on SMTP then this setting wont be applicable.", 'woocommerce-ac' ); ?>">
                                                        <input class="ib-md" type="text" placeholder="john@example.com"  id="wcap_from_email" name="wcap_from_email"  v-model="settings.wcap_from_email" />
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="tm1-row flx-center">
                                                <div class="col-left">
                                                    <label><?php esc_html_e( "Send Reply Emails to:", 'woocommerce-ac' ); ?></label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap flx-aln-center">
                                                        <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "When a contact receives your email and clicks reply, which email address should that reply be sent to?", 'woocommerce-ac' ); ?>">
                                                        <input class="ib-md" type="text" placeholder="john@example.com"  id="wcap_reply_email" name="wcap_reply_email"  v-model="settings.wcap_reply_email" />
                                                    </div>
                                                </div>
                                            </div>

                                            
                                            <div class="tm1-row flx-center">
                                                <div class="col-left">
                                                    <label><?php esc_html_e( "Product Image:", 'woocommerce-ac' ); ?></label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap flx-aln-center">
                                                        <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "This setting affects the dimension of the product image in the abandoned cart reminder email.", 'woocommerce-ac' ); ?>">
                                                        <input class="ib-sm ib-small" type="text" style="width:70px" name="" placeholder="2"   id="wcap_product_image_height" name="wcap_product_image_height"  v-model="settings.wcap_product_image_height" />
                                                        <p style="margin-bottom: 0px;"> &nbsp; X &nbsp;</p>
                                                        <input class="ib-sm ib-small" type="text" style="width:70px" name="" placeholder="2"  id="wcap_product_image_width" name="wcap_product_image_width"  v-model="settings.wcap_product_image_width" />
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="tm1-row flx-center">
                                                <div class="col-left">
                                                    <label><?php esc_html_e( "Product Name Redirects to:", 'woocommerce-ac' ); ?></label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap flx-aln-center">
                                                        <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "Select the page where product name in reminder emails should redirect to.", 'woocommerce-ac' ); ?>">
                                                        <select class="ib-md"  id="wcap_product_name_redirect" name="wcap_product_name_redirect"  v-model="settings.wcap_product_name_redirect">
                                                            <option value="checkout" ><?php  esc_html_e( 'Checkout Page', 'woocommerce-ac' ) ?></option>
                                                            <option value="product" ><?php  esc_html_e( 'Product Page', 'woocommerce-ac' ) ?></option>
                                                            <option value="none" ><?php  esc_html_e( 'No linkback', 'woocommerce-ac' ) ?></option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                           
                                            <div class="tm1-row flx-center">
                                                <div class="col-left">
                                                    <label><?php esc_html_e( "UTM parameters to be added to all the links in reminder emails", 'woocommerce-ac' ); ?></label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap flx-aln-center">
                                                        <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "UTM parameters that should be added to all the links in reminder emails.", 'woocommerce-ac' ); ?>">
                                                        <input class="ib-md" type="text"  placeholder="UTM parameters"  id="wcap_add_utm_to_links" name="wcap_add_utm_to_links"  v-model="settings.wcap_add_utm_to_links"/>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="tm1-row flx-center">
                                                <div class="col-left">
                                                    <label><?php esc_html_e( "Auto login WordPress users coming to the site using reminder email links", 'woocommerce-ac' ); ?></label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap flx-aln-center">
                                                        <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "Should users registered on the site be auto logged in when they click a link in the reminder emails?", 'woocommerce-ac' ); ?>">
                                                        <label class="el-switch el-switch-green">
                                                            <input type="checkbox" id="wcap_auto_login_users" name="wcap_auto_login_users" v-model="settings.wcap_auto_login_users" true-value="on" false-value=""/>
                                                            <span class="el-switch-style"></span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
											
											<div class="ss-foot">
                                                    <button type="button" @click="save_general_settings( 'collapseEight' )" ><?php esc_html_e( 'Save', 'woocommerce-ac' ); ?></button>
                                                </div>
												
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php if ( isset( $settings_based_on_license['unsubscribe_emails']) ): ?> 
                            <!-- Eight Panel -->
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h2 class="panel-title" data-toggle="collapse" data-target="#collapseNine" aria-expanded="false">
                                        <?php esc_html_e( 'Unsubscribe Emails Settings', 'woocommerce-ac' ); ?>
                                    </h2>
                                </div>
								<?php
													
								$unsubscribe_options = array(
									'default_page'   => __( 'Default Unsubscribe Page', 'woocommerce-ac' ),
									'custom_text'    => __( 'Custom Text', 'woocommerce-ac' ),
									'custom_wp_page' => __( 'Custom WordPress page', 'woocommerce-ac' )
								);
								
								?>
                                <div id="collapseNine" class="panel-collapse collapse">
                                    <div class="panel-body">
                                        <div class="tbl-mod-1">
                                            <div class="tm1-row flx-center">
                                                <div class="col-left">
                                                    <label><?php esc_html_e( "Unsubscribe Landing Page:", 'woocommerce-ac' ); ?></label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap flx-aln-center">
                                                        <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "Select a source where the user must be redirected when an Unsubscribe link is clicked from reminders sent. For details, please check the documentation.", 'woocommerce-ac' ); ?>">
                                                        <select class="ib-md" type="text"  id="wcap_unsubscribe_landing_page" name="wcap_unsubscribe_landing_page"  v-model="settings.wcap_unsubscribe_landing_page">
														<?php
															foreach ( $unsubscribe_options as $u_key => $u_value ) { ?>
																<option name = "<?php echo esc_attr( $u_key ); ?>" value ="<?php echo esc_attr( $u_key ); ?>"><?php echo esc_attr__( $u_value, 'woocommerce-ac' );//phpcs:ignore ?></option>
															<?php }
														?>
														</select>
                                                    </div>
                                                </div>
                                            </div>
											
											 <div class="tm1-row flx-center" v-show="settings.wcap_unsubscribe_landing_page == 'custom_text'">
                                                <div class="col-left">
                                                    <label>
                                                        <?php esc_html_e( 'Custom Content', 'woocommerce-ac' ); ?>:</label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap flx-aln-center">
                                                        <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "Enable tracking of abandoned products & carts even if customer does not visit the checkout page or does not enter any details on the checkout page like Name or Email. Tracking will begin as soon as a visitor adds a product to their cart and visits the cart page.", 'woocommerce-ac' ); ?>">
                                                       <?php  $initial_data = get_option( 'wcap_unsubscribe_custom_content', '' );

															wp_editor(
																__( $initial_data, 'woocommerce-ac' ),//phpcs:ignore
																'wcap_unsubscribe_custom_content',
																array(
																'media_buttons' => true,
																'textarea_rows' => 15,
																'tabindex' => 4,
																'tinymce' => array(
																	'theme_advanced_buttons1' => 'bold,italic,underline,|,bullist,numlist,blockquote,|,link,unlink,|,spellchecker,fullscreen,|,formatselect,styleselect',
																),
																)
															);
															?>
                                                    </div>
                                                </div>
                                            </div>

											 <div class="tm1-row flx-center" v-show="settings.wcap_unsubscribe_landing_page == 'custom_wp_page'">
                                                <div class="col-left">
                                                    <label>
                                                        <?php esc_html_e( 'Select Custom WordPress page', 'woocommerce-ac' ); ?>:</label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap flx-aln-center">
                                                        <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "Select a WordPress page to redirect to.", 'woocommerce-ac' ); ?>">
                                                			<select class="ib-md wcap_unsubscribe_custom_wp_page wc-page-search" id="wcap_unsubscribe_custom_wp_page" name="wcap_unsubscribe_custom_wp_page" data-placeholder='<?php esc_attr__( 'Search for a Page&hellip;', 'woocommerce-ac' )?>' >
															<?php 
															$custom_pages = get_option('wcap_unsubscribe_custom_wp_page');
																if( $custom_pages > 0 ) {
																	$post_title = get_the_title( $custom_pages );
																	printf( "<option value='%s' selected>%s</option>\n", esc_html( $custom_pages ), esc_html( $post_title ) );
																}
															?> 
															</select> 
                                                    </div>
                                                </div>
                                            </div> 

                                            <div class="tm1-row flx-center">
                                                <div class="col-left">
                                                    <label><?php esc_html_e( "Allow customers to unsubscribe from all future Abandoned Cart Reminders:", 'woocommerce-ac' ); ?></label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap flx-aln-center">
                                                        <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top"  title="<?php esc_html_e( "This will give an option to the customers whether they want to unsubscribe from the current abandoned cart reminders or from all future abandoned cart reminders also when they click on the unsubscribe link in the email.", 'woocommerce-ac' ); ?>">
                                                        <label class="el-switch el-switch-green">
                                                            <input type="checkbox" id="wcap_unsubscribe_from_all_carts" name="wcap_unsubscribe_from_all_carts"  v-model="settings.wcap_unsubscribe_from_all_carts" true-value="on" false-value="">
                                                            <span class="el-switch-style"></span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
											
											<div class="ss-foot">
                                                <button type="button" @click="save_general_settings( 'collapseNine' )" ><?php esc_html_e( 'Save', 'woocommerce-ac' ); ?></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="ss-foot">
                            <button type="button" @click="save_general_settings('')" ><?php esc_html_e( 'Save All', 'woocommerce-ac' ); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Content Area End -->

    </div>
	</template>
