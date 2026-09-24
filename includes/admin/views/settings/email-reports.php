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
<template id="email_reports">
        <!-- Content Area -->
        <div class="ac-content-area " id="email_reports_cover" >
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
	<div class="container pl-page-wrap">
             <div class="row">
                 <div class="col-md-12">
                     <div class="wbc-box">
                         <div class="wbc-head">
                             <h2><?php esc_html_e( 'Reports', 'woocommerce-ac' ) ; ?></h2>
                         </div>
                         <div class="wbc-content">
                             <div class="tbl-mod-1">
                                 <div class="tm1-row  flx-center">
                                     <div class="col-left">
                                         <label><?php esc_html_e( 'Frequency with which report should be sent', 'woocommerce-ac' ) ; ?>:</label>
                                     </div>
                                     <div class="col-right">
                                         <div class="row-box-1">
                                             <div class="rb1-right">
                                                 <div class="rb1-row flx-center">
                                                   <div class="rb-flx-style">
                                                      <div >
                                                         <img id="frequency-info" class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'Send emails on weekly/monthly basis.', 'woocommerce-ac' ) ; ?>">
                                                          <input type="radio" id="wcap_email_reports_frequency" name="wcap_email_reports_frequency"  v-model="settings.wcap_email_reports_frequency" value="weekly" >
                                                          
                                                      </div>
                                                      <label for="7_2"><?php esc_html_e( 'Weekly', 'woocommerce-ac' ) ; ?></label>
                                                  </div>
                                                  <div class="rb-flx-style">
                                                   <div >
                                                       <input type="radio" id="wcap_email_reports_frequency" name="wcap_email_reports_frequency"  v-model="settings.wcap_email_reports_frequency" value="monthly">
                                                       
                                                   </div>
                                                   <label for="7_2"><?php esc_html_e( 'Monthly', 'woocommerce-ac' ) ; ?></label>
                                                  </div>
                                                </div>
                                             </div>
                                         </div>
                                     </div>
                                 </div>
                                 <div class="tm1-row flx-center">
                                    <div class="col-left">
                                        <label><?php esc_html_e( 'Send report emails to', 'woocommerce-ac' ) ; ?>:</label>
                                    </div>
                                    <div class="col-right">
                                        <div class="rc-flx-wrap flx-aln-center">
                                            <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'Enter a list of email addresses. Each email address should be on a new line.', 'woocommerce-ac' ) ; ?>">
                                            <textarea class="ta-sm"   id="wcap_email_reports_emails_list" name="wcap_email_reports_emails_list" v-model="settings.wcap_email_reports_emails_list" placeholder=""></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="ss-foot">
                                    
                                    <div class="col-right sb-wrap">
                                        <button @click="save_email_settings" type="submit" value="<?php esc_html_e( 'Save Settings', 'woocommerce-ac' ) ; ?>"><?php esc_html_e( 'Save Settings', 'woocommerce-ac' ) ; ?></button>
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
