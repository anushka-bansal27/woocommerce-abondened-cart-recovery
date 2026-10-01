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

<?php $display_links = apply_filters( 'wcap_show_LittleCodr_exclusive_tabs', false ); ?>
<template id="facebook_messenger">
        <!-- Content Area -->
        <div class="ac-content-area" id="fb_cover" >
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

            <div class="container ">
               <div class="row">
                   <div class="col-md-12">
                       <div class="ac-page-head phw-btn">
                           <div class="col-left">
                               <h1><?php esc_html_e( 'Facebook Messenger', 'woocommerce-ac' ) ; ?></h1>
                               <p>
							   <?php 
                               $fb_doc_links = apply_filters(
                                    'wcap_facebook_messenger_doc_links',
                                    array(
                                        'desc' => __( 'Configure the plugin to send notifications to Facebook Messenger using the settings below.', 'woocommerce-ac' ),
                                        'link' => '', // translators: Documentation Link.
                                    )
                                );

                               printf(
										// translators: Documentation Link.
                                        //phpcs:disable
											$fb_doc_links['desc'],
											$fb_doc_links['link'],		
                                            $fb_doc_links['link'] ? '</a>' : ''					
										); ?>
							   
                           </div>
                           <div class="col-right" style="margin-left: 350px;">
                               <button type="button"  @click="save_fb_settings" ><?php echo esc_html_e( 'Save Settings', 'woocommerce-ac' ); ?></button>
                           </div>
                       </div>

                       <div class="wbc-accordion">
                           <div class="panel-group ac-accordian" id="wbc-accordion">
                               <!-- First Panel -->
                               <div class="panel panel-default">
                                   <div id="collapseOne" class="panel-collapse collapse show">
                                       <div class="panel-body">
                                           <div class="tbl-mod-1">
                                               <div class="tm1-row">
                                                   <div class="col-left">
                                                       <label><?php echo esc_html_e( 'Enable Facebook Messenger Reminders', 'woocommerce-ac' ); ?>:</label>
                                                   </div>
                                                   <div class="col-right">
                                                       <div class="rc-flx-wrap flx-aln-center">
                                                           <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php echo esc_html_e( 'By enabling this a check box will be shown after the Add to Cart button to get the user’s concern about connecting their Facebook account. Note: Enabling or disabling this option won’t affect the cart abandonment tracking.', 'woocommerce-ac' ); ?>">
                                                           <label class="el-switch el-switch-green">
                                                               <input type="checkbox" id="wcap_enable_fb_reminders" name="wcap_enable_fb_reminders"  v-model="settings.wcap_enable_fb_reminders" true-value="on" false-value=""  >
                                                               <span class="el-switch-style"></span>
                                                           </label>
                                                       </div>
                                                   </div>
                                               </div>

                                               <div class="tm1-row">
                                                   <div class="col-left">
                                                    <label><?php echo esc_html_e( 'Facebook Messenger On Add To Cart Pop-Up Modal', 'woocommerce-ac' ); ?>:</label>
                                                   </div>
                                                   <div class="col-right">
                                                    <div class="rc-flx-wrap flx-aln-center">
                                                        <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php echo esc_html_e( 'This option will display a checkbox on the pop-up modal to connect with Facebook.', 'woocommerce-ac' ); ?>">
                                                        <label class="el-switch el-switch-green">
                                                            <input type="checkbox"  id="wcap_enable_fb_reminders_popup" name="wcap_enable_fb_reminders_popup"  v-model="settings.wcap_enable_fb_reminders_popup" true-value="on" false-value=""  >
                                                            <span class="el-switch-style"></span>
                                                        </label>
                                                    </div>
                                                    </div>
                                                </div>

                                               <div class="tm1-row flx-center">
                                                   <div class="col-left">
                                                       <label><?php esc_html_e( 'Icon Size Of User', 'woocommerce-ac' ) ; ?>:</label>
                                                   </div>
                                                   <div class="col-right">
                                                       <div class="rc-flx-wrap flx-aln-center">
                                                           <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'Select the size of the user icon which shall be displayed below the checkbox in case the user is logged in to their Facebook account.', 'woocommerce-ac' ); ?>">
                                                           <select class="ib-md"  id="wcap_fb_user_icon" name="wcap_fb_user_icon"  v-model="settings.wcap_fb_user_icon" >
                                                               <option value="small"><?php echo esc_html_e('Small', 'woocommerce-ac'); ?></option>
                                                               <option value="medium"><?php echo esc_html_e('Medium', 'woocommerce-ac'); ?></option>
                                                               <option value="large"><?php echo esc_html_e('Large', 'woocommerce-ac'); ?></option>
                                                               <option value="standard"><?php echo esc_html_e('Standard', 'woocommerce-ac'); ?></option>
                                                               <option value="xlarge"><?php echo esc_html_e('Extra Large', 'woocommerce-ac'); ?></option>
                                                           </select>
                                                       </div>
                                                   </div>
                                               </div>

                                               <div class="tm1-row">
                                                   <div class="col-left">
                                                       <label><?php esc_html_e( 'Consent Text', 'woocommerce-ac' ) ; ?>:</label>
                                                   </div>
                                                   <div class="col-right">
                                                       <div class="rc-flx-wrap flx-aln-center">
                                                           <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'Text that will appear above the consent checkbox. HTML tags are also allowed.', 'woocommerce-ac' ) ; ?>">
                                                           <input class="ib-md" type="text"  id="wcap_fb_consent_text" name="wcap_fb_consent_text"  v-model="settings.wcap_fb_consent_text"  placeholder="<?php esc_html_e( 'Allow Order Status to be sent to Facebook Messanger', 'woocommerce-ac' ) ; ?>"/>
                                                       </div>
                                                   </div>
                                               </div>

                                               <div class="tm1-row">
                                                <div class="col-left">
                                                    <label><?php esc_html_e( 'Facebook Page ID', 'woocommerce-ac' ) ; ?>:</label>
                                                </div>
                                                <div class="col-right">
                                                    <div class="rc-flx-wrap flx-aln-center">
                                                        <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title='<?php esc_html_e( 'Facebook Page ID in numberic format.', 'woocommerce-ac' ); ?>'>
                                                        <input class="ib-md" type="text" placeholder="<?php esc_html_e( 'Enter Your Facebook ID', 'woocommerce-ac' ) ; ?>"  id="wcap_fb_page_id" name="wcap_fb_page_id"  v-model="settings.wcap_fb_page_id" />
                                                    </div>
                                                    <?php if( $display_links ): ?>
                                                        <?php echo __('You can find your page ID from <a href="https://github.com/LittleCodr" target="_blank">here.</a>', 'woocommerce-ac');//phpcs:ignore ?>
                                                    <?php endif; ?>
                                                </div>
                                                </div>

                                                <div class="tm1-row">
                                                   <div class="col-left">
                                                       <label><?php esc_html_e( 'Messenger App ID', 'woocommerce-ac' ) ; ?>:</label>
                                                   </div>
                                                   <div class="col-right">
                                                       <div class="rc-flx-wrap flx-aln-center">
                                                           <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'Enter your Messenger App ID', 'woocommerce-ac' ) ; ?>">
                                                           <input class="ib-md" type="text"  placeholder="<?php esc_html_e( 'Enter your Messanger App ID','woocommerce-ac' );?>"  id="wcap_fb_app_id" name="wcap_fb_app_id"  v-model="settings.wcap_fb_app_id" />
                                                       </div>
                                                   </div>
                                                </div>

                                                <div class="tm1-row">
                                                  <div class="col-left">
                                                    <label><?php esc_html_e( 'Facebook Page Token', 'woocommerce-ac' ) ; ?>:</label>
                                                  </div>
                                                 <div class="col-right">
                                                    <div class="rc-flx-wrap flx-aln-center">
                                                        <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'Enter your Facebook Page Token', 'woocommerce-ac' ) ; ?>">
                                                        <input class="ib-md" type="text" placeholder="<?php esc_html_e( 'Enter Your Facebook Token','woocommerce-ac' );?>"  id="wcap_fb_page_token" name="wcap_fb_page_token"  v-model="settings.wcap_fb_page_token" />
                                                    </div>
                                                 </div>
                                                </div>

                                                <div class="tm1-row">
                                                   <div class="col-left">
                                                     <label><?php esc_html_e( 'Verify Token', 'woocommerce-ac' ) ; ?>:</label>
                                                   </div>
                                                  <div class="col-right">
                                                     <div class="rc-flx-wrap flx-aln-center">
                                                         <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'Enter your Verify Token', 'woocommerce-ac' ) ; ?>">
                                                         <input class="ib-md" type="text" name="timefrom" placeholder="<?php esc_html_e( 'Enter your verify token here.','woocommerce-ac' );?>"  id="wcap_fb_verify_token" name="wcap_fb_verify_token"  v-model="settings.wcap_fb_verify_token" />
                                                     </div>
                                                  </div>
                                                 </div>
                                           </div>
                                       </div>
                                   </div>
                               </div>
                              <!-- Secondry Are  -->
                           </div>    
                       </div>

                       <div class="ss-foot">
                           <button type="button" @click="save_fb_settings"  ><?php esc_html_e( 'Save Settings','woocommerce-ac' );?></button>
                       </div>
                   </div>
               </div>
               <h2><?php esc_html_e( 'Domains Whitelisted for the page mentioned above', 'woocommerce-ac' ) ; ?>:</h2>
               <div class="alert alert-dark alert-dismissible fade show" role="alert">
                  <img class="msg-icon" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info-grey.svg" alt="Logo" />
				  <?php printf(
							// translators: FB restrictions doc link.
							__("The current domain shall be listed in the below list. Please note the domain will not get listed if it is not over https due to %s Facebook restrictions. %s", "woocommerce-ac"),
							'<a href="https://developers.facebook.com/docs/messenger-platform/reference/messenger-profile-api/domain-whitelisting#requirements" target="_blank">',								
							'</a>'
						); ?>
				 
              </div>
              <h2><?php esc_html_e( 'Webhook callback URL', 'woocommerce-ac' ) ; ?>:</h2>
              <p><?php esc_html_e( 'Your webhook callback URL is: ', 'woocommerce-ac' ); ?>
                    <u><?php echo get_home_url() . '/acpro-callback-webhook/';?></u>
                </p>
              <div class="alert alert-dark alert-dismissible fade show" role="alert">
                 <img class="msg-icon" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info-grey.svg" alt="Logo" />
                <?php if( $display_links ): ?>
                    <?php printf(
							// translators: Documentation Link.
							__('This webhook needs to added to your %s Facebook Developer App %s for the checkbox to appear on site.', "woocommerce-ac"),
							'<a href="https://github.com/LittleCodr" target="_blank">',								
							'</a>'
					); ?>	
                <?php else: ?>	
                    <?php printf(
							// translators: Documentation Link.
							__('This webhook needs to added to your Facebook Developer App for the checkbox to appear on site.', "woocommerce-ac"),
							'',								
							''
						); ?>		
                <?php endif; ?>			 
             </div>
           </div>
        <!-- Content Area End -->

    </div>
	</template>
