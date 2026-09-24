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
<template id="whatsapp">
        <!-- Content Area -->
        <div class="ac-content-area" id="whatsapp_cover" >
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

          <div class="container ac-reminder-section ">
                <div class="row">
                    <div class="col-md-12">
                        <div class="ac-page-head">
                        </div>
                        <div class="wbc-accordion">
                            <div class="panel-group ac-accordian" id="wbc-accordion">
                                <!-- First Panel -->
                                <div class="panel panel-default" id="sms_cover" >
                                    <div class="panel-heading">
                                        <h2 class="panel-title" data-toggle="collapse" data-target="#collapseOne" aria-expanded="false">
                                           <?php esc_html_e( 'WhatsApp Reminders', 'woocommerce-ac' ) ; ?>
                                        </h2>
                                        <p><?php esc_html_e( 'Configure your WhatsApp Cloud API account by adding your Access Token and Phone Number ID below', 'woocommerce-ac' ) ; ?></p>
                                        <a href="<?php echo esc_url('https://developers.facebook.com/apps/') ?>"><?php echo esc_html_e( 'Create or manage your account' ); ?></a>
                                    </div>
                                    <div id="collapseOne" class="panel-collapse collapse show">
                                        <div class="panel-body">
                                            <div class="tbl-mod-1">
                                                <div class="tm1-row">
                                                    <div class="col-left">
                                                        <label><?php esc_html_e( 'Enable WhatsApp', 'woocommerce-ac' ) ; ?>:</label>
                                                    </div>
                                                    <div class="col-right">
                                                        <div class="rc-flx-wrap flx-aln-center">
                                                            <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'Whatsapp notifications of the abandoned cart will be sent to the users. Note: Enabling or disabling this option will not affect the cart abandonment tracking. ', 'woocommerce-ac' ) ; ?>">
                                                            <label class="el-switch el-switch-green">
                                                                <input type="checkbox" id="wcap_enable_whatsapp_reminders" name="wcap_enable_whatsapp_reminders"  v-model="settings.wcap_enable_whatsapp_reminders" true-value="on" false-value="" >
                                                                <span class="el-switch-style"></span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div> 

                                                <div class="tm1-row flx-center">
                                                    <div class="col-left">
                                                        <label><?php esc_html_e( 'Access Token', 'woocommerce-ac' ) ; ?>:</label>
                                                    </div>
                                                    <div class="col-right">
                                                        <div class="rc-flx-wrap flx-aln-center">
                                                            <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'Please enter your Access token. This is required for secure API authentication. Keep it private and do not share it publicly.', 'woocommerce-ac' ) ; ?>">
                                                            <input class="ib-xl" type="text" placeholder="<?php esc_html_e( 'Enter your WhatsApp Business account access token', 'woocommerce-ac' ) ; ?>" id="wcap_whatsapp_access_token" name="wcap_whatsapp_access_token"  v-model="settings.wcap_whatsapp_access_token" >
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="tm1-row flx-center">
                                                    <div class="col-left">
                                                        <label><?php esc_html_e( 'Phone number ID', 'woocommerce-ac' ) ; ?>:</label>
                                                    </div>
                                                    <div class="col-right">
                                                        <div class="rc-flx-wrap flx-aln-center">
                                                            <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'Please enter your Phone ID from WhatsApp cloud account. This unique identifier is required for authentication and API access.', 'woocommerce-ac' ) ; ?>">
                                                            <input class="ib-xl" type="text" placeholder="<?php esc_html_e( 'Enter your Phone Number ID', 'woocommerce-ac' ) ; ?>" id="wcap_whatsapp_phone_id" name="wcap_whatsapp_phone_id"  v-model="settings.wcap_whatsapp_phone_id" >
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="tm1-row flx-center">
                                                    <div class="col-left">
                                                        <label><?php esc_html_e( 'Consent Text', 'woocommerce-ac' ) ; ?>:</label>
                                                    </div>
                                                    <div class="col-right">
                                                        <div class="rc-flx-wrap flx-aln-center">
                                                            <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'WhatsApp requires explicit opt-in from the customers to send messages.', 'woocommerce-ac' ) ; ?>">
                                                            <input class="ib-xl" type="text" placeholder="<?php esc_html_e( 'Enter consent text to display on checkout', 'woocommerce-ac' ) ; ?>" id="wcap_whatsapp_consent_text" name="wcap_whatsapp_consent_text"  v-model="settings.wcap_whatsapp_consent_text" >
                                                        </div>
                                                    </div>
                                                </div>
												
												<div class="ss-foot">
                                                    <button type="button" @click="save_whatsapp_settings" ><?php esc_html_e( 'Save Settings', 'woocommerce-ac' ) ; ?></button>
                                                </div> 
							
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>  
                        </div>
                    </div>
                </div>
            </div>
        <!-- Content Area End -->

    </div>
	</template>
