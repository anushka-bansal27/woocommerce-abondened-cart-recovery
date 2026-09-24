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
<template id="sms">
        <!-- Content Area -->
        <div class="ac-content-area" id="sms_cover" >
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
                                           <?php esc_html_e( 'SMS Reminders', 'woocommerce-ac' ) ; ?>
                                        </h2>
                                        <p><?php esc_html_e( 'Twillio: Configure your Twillio account settings below.
                                                    Please note that due to some restrictions from Twillio, customers may sometimes receive delayed messages', 'woocommerce-ac' ) ; ?></p>
                                    </div>
                                    <div id="collapseOne" class="panel-collapse collapse show">
                                        <div class="panel-body">
                                            <div class="tbl-mod-1">
                                                <div class="tm1-row">
                                                    <div class="col-left">
                                                        <label><?php esc_html_e( 'Enable SMS', 'woocommerce-ac' ) ; ?>:</label>
                                                    </div>
                                                    <div class="col-right">
                                                        <div class="rc-flx-wrap flx-aln-center">
                                                            <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'SMS notifications of the abandoned cart will be sent to the users. Note: Enabling or disabling this option will not affect the cart abandonment tracking. ', 'woocommerce-ac' ) ; ?>">
                                                            <label class="el-switch el-switch-green">
                                                                <input type="checkbox" id="wcap_enable_sms_reminders" name="wcap_enable_sms_reminders"  v-model="settings.wcap_enable_sms_reminders" true-value="on" false-value="" >
                                                                <span class="el-switch-style"></span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="tm1-row">
                                                    <div class="col-left">
                                                        <label style="padding-top: 10px ;"><?php esc_html_e( 'From', 'woocommerce-ac' ) ; ?>:</label>
                                                    </div>
                                                    <div class="col-right">
                                                        <div class="rc-flx-wrap flx-aln-center">
                                                            <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'Must be a Twilio phone number (in E.164 format) or alphanumeric sender ID.', 'woocommerce-ac' ) ; ?>">
                                                            <input class="ib-md" type="text"  id="wcap_sms_from_phone" name="wcap_sms_from_phone"  v-model="settings.wcap_sms_from_phone" />
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="tm1-row flx-center">
                                                    <div class="col-left">
                                                        <label><?php esc_html_e( 'Account SID', 'woocommerce-ac' ) ; ?>:</label>
                                                    </div>
                                                    <div class="col-right">
                                                        <div class="rc-flx-wrap flx-aln-center">
                                                            <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'Please enter your Twilio Account SID. This unique identifier is required for authentication and API access.', 'woocommerce-ac' ) ; ?>">
                                                            <input class="ib-xl" type="text" placeholder="<?php esc_html_e( 'Enter your Twilio Account SID', 'woocommerce-ac' ) ; ?>" id="wcap_sms_account_sid" name="wcap_sms_account_sid"  v-model="settings.wcap_sms_account_sid" >
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="tm1-row flx-center">
                                                    <div class="col-left">
                                                        <label><?php esc_html_e( 'Auth Token', 'woocommerce-ac' ) ; ?>:</label>
                                                    </div>
                                                    <div class="col-right">
                                                        <div class="rc-flx-wrap flx-aln-center">
                                                            <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'Please enter your Twilio Auth Token. This is required for secure API authentication. Keep it private and do not share it publicly.', 'woocommerce-ac' ) ; ?>">
                                                            <input class="ib-xl" type="text" placeholder="<?php esc_html_e( 'Enter your Twilio Auth Token', 'woocommerce-ac' ) ; ?>" id="wcap_sms_auth_token" name="wcap_sms_auth_token"  v-model="settings.wcap_sms_auth_token" >
                                                        </div>
                                                    </div>
                                                </div>
												
												<div class="ss-foot">
                                <button type="button" @click="save_sms_settings" ><?php esc_html_e( 'Save Settings', 'woocommerce-ac' ) ; ?></button>
                            </div> 
							
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Second Panel -->
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h2 class="panel-title" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false">
                                           <?php esc_html_e( 'Send Test SMS', 'woocommerce-ac' ) ; ?>
                                        </h2>
                                    </div>
                                    <div id="collapseTwo" class="panel-collapse collapse show">
                                        <div class="panel-body">
                                            <div class="tbl-mod-1">
                                                <div class="alert alert-dark alert-dismissible fade show" role="alert" v-show="show_sms_alert" v-html="sms_alert" >
                                                    <img class="msg-icon"   src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info-grey.svg" alt="Logo" />{{sms_alert}}
                                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                        <span aria-hidden="true" style="color:red">&times;</span>
                                                    </button>
                                                </div>
                                                
                                                <div class="tm1-row">
                                                    <div class="col-left">
                                                        <label><?php esc_html_e( 'Recipient', 'woocommerce-ac' ) ; ?>:</label>
                                                    </div>
                                                    <div class="col-right">
                                                        <div class="rc-flx-wrap flx-aln-center">
                                                            <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'Enter a valid phone number in E.164 format, e.g., +1234567890', 'woocommerce-ac' ) ; ?>">
                                                            <input class="ib-md" type="text" id="test_number" name="test_number"  v-model="settings.test_number" placeholder="<?php esc_html_e( 'Enter phone number', 'woocommerce-ac' ) ; ?>" />
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="tm1-row">
                                                    <div class="col-left">
                                                        <label><?php esc_html_e( 'Message', 'woocommerce-ac' ) ; ?>:</label>
                                                    </div>
                                                    <div class="col-right">
                                                        <div class="rc-flx-wrap">
                                                            <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'Enter the SMS content.', 'woocommerce-ac' ) ; ?>">
                                                            <textarea class="ta-sm" id="test_msg" name="test_msg"  v-model="settings.test_msg" ><?php esc_html_e( 'Hello World!', 'woocommerce-ac' );?></textarea>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="tm1-row bdr-0 pt-0">
                                                    <div class="col-left"></div>
                                                    <div class="col-right sb-wrap">
                                                        <input class="secondary-btn" type="submit" name="" @click="send_test_sms" value="<?php esc_html_e( 'Send', 'woocommerce-ac' ) ; ?>">
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
            </div>
        <!-- Content Area End -->

    </div>
	</template>
