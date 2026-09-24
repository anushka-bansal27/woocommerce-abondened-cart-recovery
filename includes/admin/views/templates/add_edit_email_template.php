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
include_once( dirname( __FILE__ ) . '/' .'../ac-header.php' );

?>
<!-- Content Area -->
<div class="ac-content-area" id="secondary-nav-wrap"  ref="foo" >
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
			<div class="container cw-full secondary-nav">
				<div class="row">
					<div class="col-md-12">
						<!-- Secondary Navigation -->
						<div class="secondary-nav-wrap">
							<ul>
								<li v-for="tab in settings_tabs"
									v-bind:key="tab.id"
									v-bind:class="{ 'current-menu-item': currentSettingsTab === tab.id }"
									v-on:click="currentSettingsTab = tab.id"> 
									<a href="<?php echo esc_url( admin_url('admin.php?page=woocommerce_ac_page&action=cart_recovery') ); ?>" :link="tab.link" @click = " change_link( tab.link, $event )">{{ tab.text }}</a>
								</li>
							</ul>
						</div>
						<!-- Secondary Navigation - End -->
					</div>
				</div>
			</div>
			<form method="post" >
			<input type="hidden" name="mode" :value="settings.mode" />
         <input type="hidden" name="id" :value="settings.id" />
         <input type="hidden" name="ac_settings_frm" :value="settings.ac_settings_frm"/>
			   <div class="ordd-content-area">
            <div class="container fas-page-wrap ac-page-head pb-0">
               <div class="row">
                  <div class="col-md-12">
                     <div class="tm1-row bdr-0 pt-0 delvry-sch-bottom mb-4">
                        <div class="abulk-box d-flex pt-0">
                        </div>
                        <div class="action-url" style="padding-bottom:15px ;">
                           <button type="button" class="top-back" @click="back_to_templates_lists()"><?php esc_html_e( 'Back', 'woocommerce-ac' ); ?></button>
                           <button type="button" @click="wcap_emailtemplates_save_settings()"><?php esc_html_e( 'Save Settings', 'woocommerce-ac' ); ?></button>
                        </div>
                     </div>
                     <div class="tbl-mod-1">
                        <div class="custom-integrations">
                           <div class="bts-content">
                              <div class="tbl-mod-2 flx-100">
                                 <div class="tm2-inner-wrap tbl-responsive">
                                    <div class="obp-content">
                                       <div class="wbc-box">                                           
                                           <div class="wbc-content">
                                               <div class="tbl-mod-1">
                                                   <div class="tm1-row">
                                                      <div class="col-left">
                                                         <label><?php esc_html_e( 'Match Rules', 'woocommerce-ac' ); ?></label>
                                                      </div>
                                                      <div class="col-right">
                                                         <div class="rc-flx-wrap flx-aln-center">
                                                            <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'Email template will be sent depending on whether all the rule matches are met or any rule matches.', 'woocommerce-ac' ); ?>">
                                                            <select class="ib-md"  id="wcap_match_rules" name="wcap_match_rules"  v-model="settings.wcap_match_rules">
                                                                  <option value=""><?php esc_html_e( 'Select a Value', 'woocommerce-ac' ) ; ?></option>
																  <option value="all" ><?php esc_html_e( 'Match all rules', 'woocommerce-ac' ) ; ?></option>
                                                                  <option value="any" ><?php esc_html_e( 'Match any rules', 'woocommerce-ac' ) ; ?></option>
                                                            </select>
                                                         </div>
                                                      </div>
                                                   </div>

                                                   <div class="tm1-row">
                                                      <div class="col-left">
                                                         <label><?php esc_html_e( 'Rules', 'woocommerce-ac' ) ; ?></label>
                                                      </div>
                                                   </div>
                                                   <table class="table">
                                                      <thead>
                                                         <tr>
                                                            <th class="rule_td" ><?php esc_html_e( 'Rule Type', 'woocommerce-ac' ) ; ?></th>
                                                            <th  class="rule_td" ><?php esc_html_e( 'Conditions', 'woocommerce-ac' ) ; ?></th>
                                                            <th  class="rule_td" ><?php esc_html_e( 'Values', 'woocommerce-ac' ) ; ?></th>
                                                            <th  class="rule_td" ><?php esc_html_e( 'Actions', 'woocommerce-ac' ) ; ?></th>
                                                         </tr>
                                                      </thead>
                                                      <tbody>
													     <tr class="cloned-row" v-for="( row, index ) in settings.rules" :key="index" :id="index">
                                                            <td  v-for="( row2 ) in settings.rule_types" v-if = "row2.key == row.rule_type" v-show="!row.edit">{{row2.value}}</td>
                                                            <td  v-show="!row.edit">{{settings.rule_conditions[row.rule_condition]}}</td>
                                                            <td v-show="!row.edit" >{{row.rule_selText}}<br><br>
                                                               <textarea v-if="row.emails" v-show="!row.edit" rows="3" cols="35" name="wcap_rules_email_addresses" id="wcap_rules_email_addresses" disabled>{{row.emails}}</textarea>
                                                            </td>
															
															<td v-show="row.edit" class="rule_td">		
																<select class='wcap_rule_type' :id="'wcap_rule_type_'+index" :name="'wcap_rule_type_'+index" @change='wcap_rule_values( $event )' v-model ="row.rule_type" >
																	<option v-for="( row ) in settings.rule_types" :value="row.key" :disabled="row.disabled" :class=" row.child ? 'wcap_rule_subcategory' : 'wcap_rule_parent_category' " >{{row.extra}} {{row.value}}</option>
																</select>
																</td>
																
																<td class='rule_td wcap_rule_condition_col' v-show="row.edit" >	
																	<select class='wcap_rule_condition' :id="'wcap_rule_condition_'+index" :name="'wcap_rule_condition_'+index"  v-model ="row.rule_condition" >
																		<option v-for="( value, key ) in settings.rule_conditions" :value="key"  >{{value}}</option>
																	</select>
																</td>
																
																<td class="rule_td" v-show="row.edit">
																	<select class='wcap_rule_value' :id="'wcap_rule_value_'+index" :name="'wcap_rule_value_'+index" style='width: 90%;'  >
																		<option value='' disabled selected><?php esc_html_e( 'Select values', 'woocommerce-ac' ); ?></option>																		
																	</select><br></br>
                                                   <textarea v-if="row.emails" name='wcap_rules_email_addresses' id='wcap_rules_email_addresses' rows='3' cols='35' v-model="row.emails">{{row.emails}}</textarea>
																</td>
	
                                                            <td>
		                                                         <button type="button" class="btn btn-outline-primary blue-button btn-sm add-new" v-show="row.add" @click="save_rule( index, row )"><?php echo esc_html__( 'Save Rule', 'woocommerce-ac' ); ?></button>
				                                                  <a  title="Update" data-toggle="tooltip" v-show="row.edit && !row.add" @click="update_rule( index, row )"><?php echo esc_html__( 'Update', 'woocommerce-ac' ); ?></a>
				                                                  <a  title="Cancel" class="cancel_button" data-toggle="tooltip" v-show="row.add" @click="cancel_rule( index, row )"><?php echo esc_html__( 'Cancel', 'woocommerce-ac' ); ?></a>
				                                                   <a title="edit" data-toggle="tooltip" v-show="!row.edit" @click="edit_rule( index, row )"><?php echo esc_html__( 'Edit', 'woocommerce-ac' ); ?></a>
				                                                  <a class="disable" title="Delete" v-show="!row.add" @click="delete_rule( index )"><?php echo esc_html__( 'Delete', 'woocommerce-ac' ); ?></a>
			                                                 </td>
                                                         </tr>
                                                        
                                                      </tbody>
                                                   </table> 
                                                   </div>
                                                   <div class="add-more-link">
                                                      <a class="al-link add_new_template_range" id="add_product_availability"  @click="add_rule()"  ><img src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-plus.svg" alt="Icon" /><?php esc_html_e( 'Add Rule', 'woocommerce-ac' ); ?></a>
                                                   </div>

                                                   <div class="tm1-row flx-center">
                                                      <div class="col-left">
                                                          <label><?php esc_html_e( 'Template Name', 'woocommerce-ac' ); ?></label>
                                                      </div>
                                                      <div class="col-right">
                                                          <div class="rc-flx-wrap flx-aln-center">
                                                              <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL );?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'Enter a template name for reference' , 'woocommerce-ac' ) ?>">
                                                              <input class="ib-xl" type="text"  placeholder=""  id="woocommerce_ac_template_name" name="woocommerce_ac_template_name"  v-model="settings.woocommerce_ac_template_name" >
                                                          </div>
                                                      </div>
                                                  </div>
                                                  
                                                  <div class="tm1-row flx-center" id="send_email_after">
                                                      <div class="col-left">
                                                         <label><?php esc_html_e( 'Send this Email', 'woocommerce-ac' ) ; ?></label>
                                                      </div>
                                                      <div class="col-right">
                                                         <div class="rc-flx-wrap flx-aln-center">
														 <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="">
                                                             
                                                            <select class="ib-sm ib-small" style="width:60px"  id="email_frequency" name="email_frequency"  v-model="settings.email_frequency" >
                                                                  <?php  for( $i = 1; $i < 60; $i++ ) { ?>
																  <option value="<?php echo esc_html( $i ); ; ?>" ><?php echo esc_html( $i ); ?></option>
																  <?php } ?>															  
                                                            </select>
                                                            <select class="ib-sm ib-small"  id="day_or_hour" name="day_or_hour"  v-model="settings.day_or_hour" >
                                                               <option value="Minutes" ><?php esc_html_e( 'Minutes', 'woocommerce-ac' ) ; ?></option>
                                                               <option value="Hours" ><?php esc_html_e( 'Hours', 'woocommerce-ac' ) ; ?></option>
                                                               <option value="Days" ><?php esc_html_e( 'Days', 'woocommerce-ac' ) ; ?></option>
                                                            </select>
															<?php echo esc_html_e( 'after cart is abandoned.', 'woocommerce-ac' ); ?>
                                                         </div>
                                                      </div>
                                                   </div>

                                                   <div class="tm1-row flx-center">
                                                      <div class="col-left">
                                                          <label><?php esc_html_e( 'Subject', 'woocommerce-ac' ) ; ?></label>
                                                      </div>
                                                      <div class="col-right">
                                                          <div class="rc-flx-wrap flx-aln-center">
                                                              <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'Enter the subject that should appear in the email sent', 'woocommerce-ac' ); ?>" >
                                                              <input class="ib-xl" type="text"  placeholder=""  id="woocommerce_ac_email_subject" name="woocommerce_ac_email_subject"  v-model="settings.woocommerce_ac_email_subject">
                                                          </div>
                                                      </div>
                                                  </div>
												  
												  <div class="tm1-row border-top-0 pt-0">
                                             <div class="col-left">
                                                <label></label>
                                             </div>
                                             <div class="col-right">
                                                <div class="row-box-1">
                                                   <div class="rb1-right">
                                                      <div class="rb1-row flx-center">
														<p v-pre ><?php esc_html_e( 'Add the shortcode {{customer.firstname}} or {{product.name}} to include the Customer First Name and Product name ( first in the cart ) to the Subject Line. For e.g. Hi John!! You left some Protein Bread in your cart', 'woocommerce-ac' ) ; ?></p>
                                                      </div>
                                                   </div>
                                                </div>
                                             </div>
                                          </div>
                                                
                                                  <div class="tm1-row">
                                                      <div class="col-left">
                                                         <label><?php esc_html_e( 'Email Body', 'woocommerce-ac' ) ; ?></label>
                                                      </div>
                                                      <div class="col-right">
                                                         <div v-pre class="rc-flx-wrap">
                                                            <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php echo esc_html_e( 'Message to be sent in the reminder email.', 'woocommerce-ac' ); ?>">
                                                            
															<?php
															
                                                    wp_editor(
                                                        $template_settings['initial_data'],
                                                        'woocommerce_ac_email_body',
                                                        array(
                                                        'media_buttons' => true,
                                                        'textarea_rows' => 15,
                                                        'tabindex' => 4,
                                                        'tinymce' => array(
                                                            'theme_advanced_buttons1' => 'bold,italic,underline,|,bullist,numlist,blockquote,|,link,unlink,|,spellchecker,fullscreen,|,formatselect,styleselect',
                                                         ),
														 'editor_class' => 'ib-xl'
                                                        )
                                                    );
                                                ?>
                                                  
                                                    <span id="help_message" style="display:none" v-pre >
                                                      <?php
                                                        esc_html_e( '1. You can add customer & cart information in the template using this icon', 'woocommerce-ac' ); ?> <img width="20" height="20" src="<?php echo esc_url( plugins_url() ); ?>/woocommerce-abandon-cart-pro/assets/images/ac_editor_icon.png" /> <?php esc_html_e( 'in top left of the editor', 'woocommerce-ac' ); echo '<br>';
                                                        esc_html_e( '2. You can now customize the product information/cart contents table that is added when using the {{products.cart}} merge field', 'woocommerce-ac' ); echo '<br>';
                                                        esc_html_e( '3. Add/Remove columns from the default table by selecting the column and clicking on the Remove Column Icon in the editor', 'woocommerce-ac' ); echo '<br>';
                                                        esc_html_e( '4. Insert/Remove any of the new shortcodes that have been included for the product table', 'woocommerce-ac' ); echo '<br>';
                                                        esc_html_e( '5. Change the look and feel of the table by modifying the table style properties using the Edit Table Icon in the editor', 'woocommerce-ac' ); echo '<br>';
                                                        esc_html_e( '6. Change the background color of the table rows by using the Edit Table Row Icon in the editor', 'woocommerce-ac' ); echo '<br>';
                                                    ?>
                                                    </span>
                                                         </div>
                                                      </div>
                                                   </div>

                                                   <div class="tm1-row">
                                                      <div class="col-left">
                                                         <label><?php esc_html_e( 'Use WooCommerce Template Style', 'woocommerce-ac' ) ; ?></label>
                                                      </div>
													  <div class="col-right">
                                                <div class="row-box-1">
                                                   <div class="rb1-right">
                                                      <div class="rb1-row flx-center">													  
															<img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'Use WooCommerce default style template for abandoned cart reminder emails.', 'woocommerce-ac' ) ?>">
															<label class="el-switch el-switch-green">
																 <input  type="checkbox" placeholder="Abandoned cart reminder"  id="is_wc_template" name="is_wc_template"  v-model="settings.is_wc_template" true-value="1" false-value="">
															<span class="el-switch-style"></span>
                                                         </label>
                                                      </div>
                                                   </div>
                                                </div>
                                             </div>
                                                      
                                                   </div>												  
												   
										<div class="tm1-row border-top-0 pt-0">
                                             <div class="col-left">
                                                <label></label>
                                             </div>
                                             <div class="col-right">
                                                <div class="row-box-1">
                                                   <div class="rb1-right">
                                                      <div class="rb1-row flx-center">
                                                      	<img class="tt-info vhidden" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="">
                                                         
															<button type="button"  class="trietary-btn reverse" data-toggle="modal" data-target="#new_template" @click="set_popup_data( 'wcap_preview_wc_email' )" ><?php esc_html_e( 'Preview WooCommerce Email', 'woocommerce-ac' ) ; ?></button>
                                                            &nbsp; &nbsp; 
                                                            <button type="button"   class="trietary-btn reverse" data-toggle="modal" data-target="#new_template" @click="set_popup_data( 'wcap_preview_email' )" ><?php esc_html_e( 'Preview Custom Email', 'woocommerce-ac' ) ; ?></button>
                                                         
                                                      </div>
                                                   </div>
                                                </div>
                                             </div>
                                          </div>

                                                   <div class="tm1-row flx-center">
                                                      <div class="col-left">
                                                          <label><?php esc_html_e( 'Email Template Header Text', 'woocommerce-ac' ) ; ?></label>
                                                      </div>
                                                      <div class="col-right">
                                                          <div class="rc-flx-wrap flx-aln-center">
                                                              <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title='<?php esc_html_e( 'Enter the header which will appear in the abandoned WooCommerce email sent. This is only applicable when only used when "Use WooCommerce Template Style:" is checked.', 'woocommerce-ac' ) ?>'>
                                                              <input class="ib-xl" type="text" placeholder=<?php esc_html_e( 'Abandoned cart reminder', 'woocommerce-ac' ); ?>  id="wcap_wc_email_header" name="wcap_wc_email_header"  v-model="settings.wcap_wc_email_header">
                                                          </div>
                                                      </div>
                                                  </div>

                                                   <div class="tm1-row dw-row">
                                                      <div class="col-left">
                                                       <label><?php esc_html_e( 'Generate unique coupon codes', 'woocommerce-ac' ) ; ?></label>
                                                      </div>
                                                      <div class="col-right">
                                                         <div class="row-box-1">
                                                            <div class="rb1-left">
                                                               <img class="tt-info aw-text" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'Replace this coupon with unique coupon codes for each customer', 'woocommerce-ac' ) ?>">
                                                            </div>
                                                            <div class="rb1-right">
                                                               <div class="rb1-row">
                                                                   <label class="el-switch el-switch-green">
                                                                       <input type="checkbox"  id="unique_coupon" name="unique_coupon"  v-model="settings.unique_coupon" true-value="1" false-value="" >
                                                                       <span class="el-switch-style"></span>
                                                                   </label>
                                                               </div>
                                                            </div>
                                                         </div>
                                                      </div>
                                                   </div>
                                               
                                                   <div  v-show="'1' == settings.unique_coupon " class="flx-100 mt-20 tbl-responsive-sm">
                                                      <div class="tm2-inner-wrap" >
                                                         <div class="tm1-row flx-center">
                                                            <div class="col-left">
                                                               <label><?php esc_html_e( 'Discount Type', 'woocommerce-ac' ) ; ?></label>
                                                            </div>
                                                            <div class="col-right">
                                                               <div class="rc-flx-wrap flx-aln-center">
                                                                 <img class="tt-info vhidden" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="">
                                                         
																 <select class="ib-md"  id="wcap_discount_type" name="wcap_discount_type"  v-model="settings.wcap_discount_type" >
                                                                        <option value="percent" ><?php esc_html_e( 'Percentage Discount', 'woocommerce-ac' ) ; ?></option>
                                                                        <option value="fixed" ><?php esc_html_e( 'Fixed Cart discount ', 'woocommerce-ac' ) ; ?></option>
                                                                  </select>
                                                               </div>
                                                            </div>
                                                         </div>
                                                          
                                                         <div class="tm1-row flx-center">
                                                            <div class="col-left">
                                                                <label><?php esc_html_e( 'Coupon amount', 'woocommerce-ac' ) ; ?></label>
                                                            </div>
                                                            <div class="col-right">
                                                                <div class="rc-flx-wrap flx-aln-center">
                                                                    <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'Value of the coupon.' , 'woocommerce-ac' ) ?>">
                                                                    <input class="ib-sm" type="text" placeholder="0"  id="wcap_coupon_amount" name="wcap_coupon_amount"  v-model="settings.wcap_coupon_amount">
                                                                </div>
                                                            </div>
                                                        </div>

                                                         <div class="tm1-row dw-row">
                                                            <div class="col-left">
                                                            <label><?php esc_html_e( 'Allow free shipping', 'woocommerce-ac' ) ; ?></label>
                                                            </div>
                                                            <div class="col-right">
                                                               <div class="row-box-1">
                                                                  <div class="rb1-left">
                                                                     <img class="tt-info aw-text" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title='<?php esc_html_e( 'Check this box if the coupon grants free shipping. A free shipping method must be enabled in your shipping zone and be set to require "a valid free shipping coupon" (see the "Free Shipping Requires" setting).', 'woocommerce-ac' ) ?>'>
                                                                  </div>
                                                                  <div class="rb1-right">
                                                                     <div class="rb1-row">
                                                                        <label class="el-switch el-switch-green">
                                                                           <input type="checkbox"  id="wcap_allow_free_shipping" name="wcap_allow_free_shipping"  v-model="settings.wcap_allow_free_shipping" true-value="yes" false-value="" >
                                                                           <span class="el-switch-style"></span>
                                                                        </label>
                                                                     </div>
                                                                  </div>
                                                               </div>
                                                            </div>
                                                         </div>
                                                         <div class="tm1-row flx-center">
                                                            <div class="col-left">
                                                               <label><?php esc_html_e( 'Coupon validity', 'woocommerce-ac' ) ; ?>:<label>
                                                            </div>
                                                            <div class="col-right">
                                                               <div class="rc-flx-wrap flx-aln-center">
                                                                  <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'The coupon code which will be sent in the reminder emails will be expired based the validity set here. E.g if the coupon code sent in the reminder email should be expired after 7 days then set 7 Day(s) for this option.', 'woocommerce-ac' ) ?>">
                                                                  <input type="text"  class="ib-sm ib-small" style="width:60px"  id="wcac_coupon_expiry" name="wcac_coupon_expiry"  v-model="settings.wcac_coupon_expiry">
                                                                   
                                                                  <select class="ib-sm ib-small"  id="expiry_day_or_hour" name="expiry_day_or_hour"  v-model="settings.expiry_day_or_hour" >
                                                                     <option value="hours"><?php esc_html_e( 'Hours', 'woocommerce-ac' ) ; ?></option>
                                                    				 <option selected="selected" value="days"><?php esc_html_e( 'Days', 'woocommerce-ac' ) ; ?></option>
                                                    			 </select>
                                                                  </select>
                                                               </div>
                                                            </div>
                                                         </div>

                                                         <div class="tm1-row dw-row">
                                                            <div class="col-left">
                                                            <label><?php esc_html_e( 'Individual use only', 'woocommerce-ac' ) ; ?>:<label>
                                                            </div>
                                                            <div class="col-right">
                                                               <div class="row-box-1">
                                                                  <div class="rb1-left">
                                                                     <img class="tt-info aw-text" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'Check this box if the coupon cannot be used in conjunction with other coupons.', 'woocommerce-ac' ) ?>">
                                                                  </div>
                                                                  <div class="rb1-right">
                                                                     <div class="rb1-row">
                                                                        <label class="el-switch el-switch-green">
                                                                           <input type="checkbox"  id="individual_use" name="individual_use"  v-model="settings.individual_use" true-value="1" false-value="" >
                                                                           <span class="el-switch-style"></span>
                                                                        </label>
                                                                     </div>
                                                                  </div>
                                                               </div>
                                                            </div>
                                                         </div>
                                                      </div>
                                                   </div>
                                                   <div class="tm1-row flx-center">
                                                      <div class="col-right "style="text-align:center">
                                                          <h3><?php esc_html_e( 'OR', 'woocommerce-ac' ); ?></h3>
                                                      </div>
                                                   </div>

                                                   <div class="tm1-row flx-center" id="email_coupon">
                                                      <div class="col-left">
                                                          <label><?php esc_html_e( 'Enter a coupon code to add into email', 'woocommerce-ac' ) ; ?></label>
                                                      </div>
                                                      <div class="col-right">
                                                          <div class="rc-flx-wrap flx-aln-center">
                                                              <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'Search & select one coupon code that customers should use to get a discount.  Generated coupon code which will be sent in email reminder will have the settings of coupon selected in this option.', 'woocommerce-ac' ) ?>">
                                                              <select id="coupon_ids" name="coupon_ids[]" class="wc-product-search" multiple="multiple" style="width: 99%;" data-placeholder="<?php esc_attr_e( 'Search for a Coupon&hellip;', 'woocommerce-ac' ); ?>" data-action="wcap_json_find_coupons">
																<option v-for="( value, key ) in settings.coupon_ids" :value="key" selected  >{{value}}</option>
											                  </select>
                                                          </div>
                                                      </div>
                                                  </div>
                                                  
                                                   <div class="tm1-row flx-center">
                                                      <div class="col-left">
                                                         <label><?php esc_html_e( 'Send a test email to', 'woocommerce-ac' ) ; ?></label>
                                                      </div>
                                                      <div class="col-right">
                                                         <div class="rc-flx-wrap flx-aln-center">
                                                            <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'Enter the email id to which the test email needs to be sent.', 'woocommerce-ac' ) ?>">
                                                            <input class="ib-md" type="text" placeholder=""  id="send_test_email" v-model = "settings.send_test_email_preview"  >
                                                            <button type="button" class="trietary-btn reverse" @click ="wcap_send_test_email()"><?php esc_html_e( 'Send a test email', 'woocommerce-ac' ) ; ?></button>
															<img id="ajax_email_img" src="<?php echo esc_url( WCAP_PLUGIN_URL ) . '/assets/images/ajax-loader.gif';?>" style="display:none;" />
															<div id="preview_email_sent_msg" style="display:none;"></div>
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
                              <div class="ss-foot">
                                 <button type="button" class="top-back" @click="back_to_templates_lists()"><?php esc_html_e( 'Back', 'woocommerce-ac' ); ?></button>
                                 <button type="button" @click="wcap_emailtemplates_save_settings()"><?php esc_html_e( 'Save Settings', 'woocommerce-ac' ); ?></button>
                             </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>

		</form>
		
		 <!-- Modal -->
      <div class="modal fade" id="new_template" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" >
         <div class="modal-dialog modal-dialog-centered  modal-lg" role="document">
            <div class="modal-content">
               <div class="modal-header">
                  <h5 class="modal-title" id="exampleModalLabel"><?php esc_html_e('Email preview', 'woocommerce-ac' ); ?></h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                  </button>
               </div>
               <div class="modal-body">
                  <div class="custom-popup">
<div id="ac_events_loader" class=""  v-show="saving">
					<div class="ac_events_loader_wrapper">
						...<?php esc_html_e('loading', 'woocommerce-ac' ); ?><img src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/ajax-loader.gif">
					</div>
				</div>                
                     <div class="template-main" v-html="popup_data">					 
					 {{popup_data}}
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
	  <?php 
	  
	   wc_get_template( 
                'preview_modal.php', 
                '', 
                'woocommerce-abandon-cart-pro/',
                WCAP_PLUGIN_PATH . '/includes/template/preview_modal/' );
				
				?>
	  
		</div>
		<!-- Content Area End -->
	
	<?php include_once( dirname( __FILE__ ) . '/' . '../ac-footer.php' ); ?>