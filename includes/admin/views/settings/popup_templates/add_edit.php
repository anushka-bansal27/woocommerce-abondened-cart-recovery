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
<!-- Content Area -->
<div class="ac-content-area" id="secondary-nav-wrap">
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
									<a href="<?php echo esc_url( admin_url('admin.php?page=woocommerce_ac_page&action=emailsettings') ); ?>" :link="tab.link" @click = " change_link( tab.link, $event )">{{ tab.text }}</a>
								</li>
							</ul>
						</div>
						<!-- Secondary Navigation - End -->
					</div>
				</div>
			</div>
			<form method="post" >
			 <div class="container max-1100" id="template_add_edit" >
			 
			 <input type="hidden" name="mode" :value="settings.mode" />
				<input type="hidden" name="id" class='template_id'  :value="settings.template_id" />
				<input type="hidden" name="atc_settings_frm" :value="settings.save_mode" />
               <div class="row">
                  <div class="col-md-12">
                     <div class="ac-page-head phw-btn justify-content-between">
                        <div class="col-left">
                           <h1><?php  esc_html_e( 'Popup Templates', 'woocommerce-ac' ) ; ?></h1>
                           <p><?php  esc_html_e( 'Add different Add to Cart popup templates for different pages to maximize the possibility of collecting email addresses from users.', 'woocommerce-ac' ) ; ?></p>
                        </div>
                        <div class="col-right">
                           <button type="button" class="top-back" @click="back_popup_templates_list()" ><?php  esc_html_e( 'Back', 'woocommerce-ac' ) ; ?></button>
                           <button type="button" @click="wcap_add_to_cart_popup_save_settings()"><?php  esc_html_e( 'Save Settings', 'woocommerce-ac' ) ; ?></button>
                        </div>
                     </div>
                     <div class="wbc-accordion">
                        <div class="panel-group ac-accordian" id="wbc-accordion">
                           <!-- First Panel -->
                           <div class="panel panel-default mb-4">
                              <div class="panel-heading">
                                 <h2 class="panel-title" data-toggle="collapse" data-target="#collapseOne" aria-expanded="false">
                                    <?php  esc_html_e( 'Edit/Add Popup Templates', 'woocommerce-ac' ) ; ?>
                                 </h2>
                              </div>
                              <div id="collapseOne" class="panel-collapse collapse show">
                                 <div class="panel-body">
                                    <div class="tbl-mod-1">
                                       <div class="tm1-row align-items-center">
                                          <div class="col-left">
                                             <label><?php  esc_html_e( 'Template Name', 'woocommerce-ac' ) ; ?>:<label>
                                          </div>
                                          <div class="col-right">
                                             <div class="row-box-1">                                                
                                                <div class="rb1-right">
                                                   <div class="rb1-row flx-center">
                                                      <div class="rb-col">
                                                         <input class="ib-xl" type="text" placeholder="Popup Templates name goes here..." id="wcap_template_name" name="wcap_template_name" v-model ="settings.wcap_template_name" >
                                                      </div>
                                                   </div>
                                                </div>
                                             </div>
                                          </div>
                                       </div>
                                       <div class="tm1-row align-items-center">
                                          <div class="col-left">
                                             <label><?php  esc_html_e( 'Template Type', 'woocommerce-ac' ) ; ?>:<label>
                                          </div>
                                          <div class="col-right">
                                             <div class="row-box-1">                                                
                                                <div class="rb1-right">
                                                   <div class="rb1-row flx-center">
                                                      <div class="rb-col">
                                                         <select class="ib-md"   id="wcap_template_type" name="wcap_template_type"  v-model="settings.wcap_template_type" @change="change_atc_text()" >
                                                            <option value='atc' ><?php  esc_html_e( 'Add To Cart', 'woocommerce-ac' ) ; ?></option>
                                                            <option value='exit_intent' ><?php  esc_html_e( 'Exit Intent', 'woocommerce-ac' ) ; ?></option>
                                                         </select>
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
                           <!-- First Panel -->
                           <div class="panel panel-default mb-4">
                              <div class="panel-heading">
                                 <h2 class="panel-title" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false">
                                    Rules
                                 </h2>
                              </div>
                              <div id="collapseTwo" class="panel-collapse collapse show">
                                 <div class="panel-body">
                                    <div class="tbl-mod-1">
                                       <div class="custom-integrations mb-4">
                                          <div class="bts-content">
                                             <div class="tbl-mod-2 flx-100">
                                                <div class="tm2-inner-wrap tbl-responsive">
                                                   <table class="for-action" style="display: none;">
                                                      <tbody>
                                                         <tr>
                                                            <td>
                                                               <button type="button" class="btn btn-outline-primary blue-button btn-sm add-new">Save Setting</button>
                                                               <a class="edit-delvry-sche edit" data-toggle="collapse" href="#" id="action-edit" role="button" aria-expanded="false" aria-controls="collapseExample" style="display: none;"> Edit</a>
                                                               <!-- <a class="enable" title="Enable" style="display: none;">Enable</a> --> <a class="delete ml-2" title="Enable"><i class="fas fa-trash"></i></a>
                                                            </td>
                                                         </tr>
                                                      </tbody>
                                                   </table>
                                                   <table class="table">
                                                      <thead>
                                                         <tr>
                                                            <th class="rule_td" ><?php  esc_html_e( 'Rule Type', 'woocommerce-ac' ) ; ?></th>
                                                            <th  class="rule_td" ><?php  esc_html_e( 'Conditions', 'woocommerce-ac' ) ; ?></th>
                                                            <th  class="rule_td" ><?php  esc_html_e( 'Values', 'woocommerce-ac' ) ; ?></th>
                                                            <th  class="rule_td" ><?php  esc_html_e( 'Actions', 'woocommerce-ac' ) ; ?></th>
                                                         </tr>
                                                      </thead>
                                                      <tbody>
													     <tr class="cloned-row" v-for="( row, index ) in settings.rules" :key="index" :id="index">
                                                            <td  v-show="!row.edit">{{settings.rule_types[row.rule_type]}}</td>
                                                            <td  v-show="!row.edit">{{settings.rule_conditions[row.rule_condition]}}</td>
                                                            <td v-show="!row.edit" >{{row.rule_selText}}</td>
															
															<td v-show="row.edit" class="rule_td">		
																<select class='wcap_rule_type' :id="'wcap_rule_type_'+index" :name="'wcap_rule_type_'+index" @change='wcap_rule_values( $event )' v-model ="row.rule_type" >
																	<option v-for="( value, key ) in settings.rule_types" :value="key"  >{{value}}</option>
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
																	</select>
																</td>
	
                                                            <td>
		                                                         <button type="button" class="btn btn-outline-primary blue-button btn-sm add-new" v-show="row.add" @click="save_rule( index, row )"><?php echo esc_html__( 'Save Rule', 'woocommerce-ac' ); ?></button>
				                                                  <a  title="Update" data-toggle="tooltip" v-show="row.edit && !row.add" @click="update_rule( index, row )"><?php echo esc_html__( 'Update', 'woocommerce-ac' ); ?></a>
				                                                   <a title="edit" data-toggle="tooltip" v-show="!row.edit" @click="edit_rule( index, row )"><?php echo esc_html__( 'Edit', 'woocommerce-ac' ); ?></a>
				                                                  <a class="disable" title="Cancel" v-show="row.add" @click="delete_rule( index )"><?php echo esc_html__( 'Cancel', 'woocommerce-ac' ); ?></a>
				                                                  <a class="disable" title="Delete" v-show="!row.add" @click="delete_rule( index )"><?php echo esc_html__( 'Delete', 'woocommerce-ac' ); ?></a>
			                                                 </td>
                                                         </tr>
                                                        
                                                      </tbody>
                                                   </table>                                                   
                                                </div>
                                                <div class="add-more-link">
                                                   <a class="al-link add_new_template_range" id="add_product_availability" @click="add_rule()"  ><img src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-plus.svg" alt="Icon" / > <?php  esc_html_e( 'Add Rule', 'woocommerce-ac' ) ; ?></a>
                                                </div>
                                             </div>
                                          </div>
                                       </div>
                                       <div class="tm1-row border-top-0 pt-0 pb-0" v-show=" this.settings.rules.length > 0 ">
                                          <div class="col-left">
                                             <label><?php esc_html_e('Match Rules', 'woocommerce-ac' ) ; ?>:<label>
                                          </div>
                                          <div class="col-right">
                                             <div class="row-box-1">                                                
                                                <div class="rb1-right">
                                                   <div class="rb1-row flx-center">
                                                      <div class="rb-col">
                                                         <select class="ib-md"   id="wcap_match_rules" name="wcap_match_rules"  v-model="settings.wcap_match_rules" >
                                                            <option value=''><?php esc_html_e( 'Select a value', 'woocommerce-ac' ); ?></option>
															<option value='all' ><?php esc_html_e( 'Match all rules', 'woocommerce-ac' ); ?></option>
				<option value='any' ><?php esc_html_e( 'Match any rule(s)', 'woocommerce-ac' ); ?></option>
                                                         </select>
                                                      </div>
                                                   </div>
                                                </div>
                                             </div>
                                          </div>
                                       </div>
                                    </div>
                                    <div class="rb1-row flx-center mb-4">
                                    </div>
                                 </div>
                              </div>
                           </div>
                           <!-- First Panel -->
                           <div class="panel panel-default mb-4">
                              <div class="panel-heading">
                                 <h2 class="panel-title" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false">
                                    <?php esc_html_e('Coupon Settings', 'woocommerce-ac' ); ?>
                                 </h2>
                              </div>
                              <div id="collapseThree" class="panel-collapse collapse show">
                                 <div class="panel-body">
                                    <div class="tbl-mod-1">
                                       <div class="tm1-row">
                                          <div class="col-left">
                                             <label><?php esc_html_e('Offer coupons on email address capture', 'woocommerce-ac' ) ; ?>:<label>
                                          </div>
                                          <div class="col-right">
                                             <div class="row-box-1">
                                               <div class="rb1-right">
                                                   <div class="rb1-row flx-center">
                                                      <label class="el-switch el-switch-green">
                                                      <input type="checkbox"   id="wcap_auto_apply_coupons_atc" name="wcap_auto_apply_coupons_atc"  v-model="settings.wcap_auto_apply_coupons_atc" true-value="on" false-value="" >
                                                      <span class="el-switch-style"></span>
                                                      </label>
                                                   </div>												   
                                                </div>
                                             </div>
                                          </div>
                                       </div>
                                       <div class="coupon__main coupon__one" v-show="'on' == settings.wcap_auto_apply_coupons_atc">
                                          <div class="tm1-row align-items-center">
                                             <div class="col-left">
                                                <label><?php esc_html_e('Type of Coupon to apply', 'woocommerce-ac' ) ; ?>:<label>
                                             </div>
                                             <div class="col-right">
                                                <div class="row-box-1">                                                   
                                                   <div class="rb1-right">
                                                      <div class="rb1-row flx-center">
                                                         <div class="rb-col">
                                                            <select class="ib-md"   id="wcap_atc_coupon_type" name="wcap_atc_coupon_type"  v-model="settings.wcap_atc_coupon_type" >
                                                               <option value='pre-selected' ><?php esc_html_e( 'Existing Coupons', 'woocommerce-ac' ); ?></option>
								                                <option value='unique' ><?php esc_html_e( 'Generate Unique Coupon code', 'woocommerce-ac' ); ?></option>
                                                            </select>
                                                         </div>
                                                      </div>
                                                   </div>
                                                </div>
                                             </div>
                                          </div>
										  <div v-show="'unique' == settings.wcap_atc_coupon_type">
                                          <div class="tm1-row align-items-center">
                                             <div class="col-left">
                                                <label><?php esc_html_e('Discount Type', 'woocommerce-ac' ) ; ?>:<label>
                                             </div>
                                             <div class="col-right">
                                                <div class="row-box-1">
                                                   <div class="rb1-right">
                                                      <div class="rb1-row flx-center">
                                                         <div class="rb-col">
                                                            <select class="ib-md"   id="wcap_atc_discount_type" name="wcap_atc_discount_type"  v-model="settings.wcap_atc_discount_type">
                                                               <option value='percent' ><?php esc_html_e( 'Percentage Discount', 'woocommerce-ac' ); ?></option>
								                                <option value='fixed_cart' ><?php esc_html_e( 'Fixed Cart Amount', 'woocommerce-ac' ); ?></option>
							
                                                            </select>
                                                         </div>
                                                      </div>
                                                   </div>
                                                </div>
                                             </div>
                                          </div>
                                          <div class="tm1-row align-items-center">
                                             <div class="col-left">
                                                <label><?php esc_html_e( 'Discount Amount', 'woocommerce-ac' ) ; ?>:<label>
                                             </div>
                                             <div class="col-right">
                                                <div class="row-box-1">
                                                   <div class="rb1-right">
                                                      <div class="rb1-row flx-center">
                                                         <div class="rb-col">
                                                            <input class="ib-mb"  type="number" id="wcap_atc_discount_amount" name="wcap_atc_discount_amount"  v-model="settings.wcap_atc_discount_amount">
                                                         </div>
                                                      </div>
                                                   </div>
                                                </div>
                                             </div>
                                          </div>
                                          <div class="tm1-row">
                                             <div class="col-left">
                                                <label><?php esc_html_e( 'Allow Free Shiping?', 'woocommerce-ac' ) ; ?>:<label>
                                             </div>
                                             <div class="col-right">
                                                <div class="row-box-1">
                                                   <div class="rb1-right">
                                                      <div class="rb1-row flx-center">
                                                         <label class="el-switch el-switch-green">
                                                         <input type="checkbox"   id="wcap_atc_coupon_free_shipping" name="wcap_atc_coupon_free_shipping"  v-model="settings.wcap_atc_coupon_free_shipping" true-value="on" false-value="">
                                                         <span class="el-switch-style"></span>
                                                         </label>
                                                      </div>
                                                   </div>
                                                </div>
                                             </div>
                                          </div>
										  </div>
										  
										   <div class="tm1-row align-items-center"  v-show="'pre-selected' == settings.wcap_atc_coupon_type">
                                             <div class="col-left">
                                                <label><?php esc_html_e( 'Coupon code to apply', 'woocommerce-ac' ) ; ?>:<label>
                                             </div>
                                             <div class="col-right">
                                                <div class="row-box-1">
                                                   <div class="rb1-right">
                                                      <div class="rb1-row flx-center">
                                                         <div class="rb-col">
                                                            
															<select id="coupon_ids" name="coupon_ids[]" class="wc-product-search" multiple="multiple" style="width: 99%;" data-placeholder="<?php esc_attr_e( 'Search for a Coupon&hellip;', 'woocommerce-ac' ); ?>" data-action="wcap_json_find_coupons">
										<option v-for="( value, key ) in settings.coupon_ids" :value="key" selected  >{{value}}</option>
											</select>
                                                         </div>
                                                      </div>
                                                   </div>
                                                </div>
                                             </div>
                                          </div>
										  
                                          <div class="tm1-row align-items-center">
                                             <div class="col-left">
                                                <label><?php esc_html_e( 'Coupon validity (in minutes)', 'woocommerce-ac' ) ; ?>:<label>
                                             </div>
                                             <div class="col-right">
                                                <div class="row-box-1">
                                                   <div class="rb1-right">
                                                      <div class="rb1-row flx-center">
                                                         <div class="rb-col">
                                                            <input class="ib-mb" type="text"  id="wcap_atc_coupon_validity" name="wcap_atc_coupon_validity"  v-model="settings.wcap_atc_coupon_validity">
                                                         </div>
                                                      </div>
                                                   </div>
                                                </div>
                                             </div>
                                          </div>
                                          <div class="tm1-row align-items-center">
                                             <div class="col-left">
                                                <label><?php esc_html_e( 'Urgency message to boost your conversions', 'woocommerce-ac' ) ; ?>:<label>
                                             </div>
                                             <div class="col-right">
                                                <div class="row-box-1">                                                  
                                                   <div class="rb1-right">
                                                      <div class="rb1-row flx-center">
                                                         <div class="rb-col">
                                                            <input class="ib-xl" type="text"   id="wcap_countdown_msg" name="wcap_countdown_msg"  v-model="settings.wcap_countdown_msg" placeholder="<?php echo esc_attr( 'Coupon <coupon_code> expires in <hh:mm:ss>. Avail it now.' ); ?>">
                                                         </div>
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
                                                         <p class="mb-0"><?php echo esc_html_e( 'Merge tags available: <coupon_code>, <hh:mm:ss>', 'woocommerce-ac' ); ?></p>
                                                      </div>
                                                   </div>
                                                </div>
                                             </div>
                                          </div>
                                          <div class="tm1-row align-items-center">
                                             <div class="col-left">
                                                <label><?php esc_html_e( 'Message to display after coupon validity is reached', 'woocommerce-ac' ) ; ?>:<label>
                                             </div>
                                             <div class="col-right">
                                                <div class="row-box-1">
                                                   <div class="rb1-right">
                                                      <div class="rb1-row flx-center">
                                                         <div class="rb-col">
                                                            <input class="ib-xl" type="text" placeholder="the offer is no longer valid."   id="wcap_countdown_msg_expired" name="wcap_countdown_msg_expired"  v-model="settings.wcap_countdown_msg_expired">
                                                         </div>
                                                      </div>
                                                   </div>
                                                </div>
                                             </div>
                                          </div>
                                          <div class="tm1-row">
                                             <div class="col-left">
                                                <label><?php esc_html_e( 'Display Urgency message on Cart page (If disabled it will  display only on Checkout page)', 'woocommerce-ac' ) ; ?>:<label>
                                             </div>
                                             <div class="col-right">
                                                <div class="row-box-1">
                                                   <div class="rb1-right">
                                                      <div class="rb1-row flx-center">
                                                         <label class="el-switch el-switch-green">
                                                         <input type="checkbox"   id="wcap_countdown_timer_cart" name="wcap_countdown_timer_cart"  v-model="settings.wcap_countdown_timer_cart" true-value="on" false-value="">
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
                                                         <p class="mb-0"><?php esc_html_e( 'Note: Orders that use the coupon selected/generated by the popup module will be marked as "ATC Coupon Used" in WooCommerce->Orders.', 'woocommerce-ac' ); ?></p>
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
                           <!-- First Panel -->
                           <div class="panel-add-cart" id="panel-add-cart-div" v-show="'atc' == settings.wcap_template_type">
                              <div class="panel panel-default mb-4">
                                 <div class="panel-heading">
                                    <h2 class="panel-title" data-toggle="collapse" data-target="#collapseFour" aria-expanded="false">
                                       <?php esc_html_e( 'Configure popup', 'woocommerce-ac' ); ?>
                                    </h2>
                                 </div>
                                 <div id="collapseFour" class="panel-collapse collapse show">
                                    <div class="panel-body pt-0 pl-0 pb-0">
                                       <div class="tbl-mod-1">
                                          <div class="row align-items-center">
                                             <div class="col-xl-4 col-lg-4 col-md-12 col-sm-12 col-12">
                                                <div class="configure-popup">
                                                   <div class="configure-head">
                                                      <h2><?php esc_html_e( 'Configure popup', 'woocommerce-ac' ); ?></h2>
                                                   </div>
                                                   <div class="configure-body">
                                                      <div class="tbl-mod-1">
                                                         <div class="tm1-row align-items-center">
                                                            <div class="col-left">
                                                               <label><?php esc_html_e( 'Modal Image', 'woocommerce-ac' ) ; ?>:<label>
                                                               
                                                            </div>
                                                            <div class="col-right">
                                                               <div class="row-box-1">
                                                                  <div class="rb1-right">
                                                                     <div class="rb1-row flx-center mb-2">
                                                                        <div class="rb-col mt-2">
                                                                           <input class="ib-md" type="file"   id="wcap_heading_section_text_image" name="wcap_heading_section_text_image"  ref="file_atc" @change="selectFile_atc">
                                                                        </div>
                                                                     </div>
                                                                  </div>
                                                               </div>
                                                            </div>
                                                         </div>
                                                         <div class="tm1-row align-items-center">
                                                            <div class="col-left">
                                                               <label><?php esc_html_e( 'Modal Heading', 'woocommerce-ac' ) ; ?>:<label>
                                                               
                                                            </div>
                                                            <div class="col-right">
                                                               <div class="row-box-1">
                                                                  <div class="rb1-right">
                                                                     <div class="rb1-row flx-center mb-2">
                                                                        <div class="rb-col mt-2">
                                                                           <input class="ib-md" type="text" placeholder="Subscribe Now Our Newsletter!"   id="wcap_heading_section_text_email" name="wcap_heading_section_text_email"  v-model="settings.wcap_heading_section_text_email">
                                                                        </div>
                                                                     </div>
                                                                     <div class="color-picker color-swither">
                                                                         <input type="color" class="holiday-color" value="#E72C2C"   id="wcap_popup_heading_color_picker" name="wcap_popup_heading_color_picker"  v-model="settings.wcap_popup_heading_color_picker" @change = " wcap_popup_heading_color_picker = settings.wcap_popup_heading_color_picker " > <span for="favcolor" class="holiday-color">{{wcap_popup_heading_color_picker}}</span> 
                                                                     </div>
                                                                  </div>
                                                               </div>
                                                            </div>
                                                         </div>
                                                         <div class="tm1-row align-items-center">
                                                            <div class="col-left">
                                                               <label><?php esc_html_e( 'Modal Text', 'woocommerce-ac' ) ; ?>:<label>
                                                             </div>
                                                            <div class="col-right">
                                                               <div class="row-box-1">
                                                                  <div class="rb1-right">
                                                                     <div class="rb1-row flx-center mb-2">
                                                                        <div class="rb-col mt-2">
                                                                           <input class="ib-md" type="text" name="" placeholder="Modal text"   id="wcap_text_section_text" name="wcap_text_section_text"  v-model="settings.wcap_text_section_text">
                                                                        </div>
                                                                     </div>
                                                                     <div class="color-picker color-swither">
                                                                         <input type="color"  class="holiday-color"  @change = " wcap_popup_text_color_picker = settings.wcap_popup_text_color_picker "    v-model="settings.wcap_popup_text_color_picker"  > <span for="favcolor" class="holiday-color">{{wcap_popup_text_color_picker}}</span> 
                                                                     </div>
                                                                  </div>
                                                               </div>
                                                            </div>
                                                         </div>
														 <div class="tm1-row align-items-center">
                                                            <div class="col-left">
                                                               <label><?php esc_html_e( 'Email placeholder', 'woocommerce-ac' ); ?>:<label>
                                                            </div>
                                                            <div class="col-right">
                                                               <div class="row-box-1">
                                                                  <div class="rb1-right">
                                                                     <div class="rb1-row flx-center mb-2">
                                                                        <div class="rb-col mt-2">
                                                                           <input class="ib-md" type="text" name="" placeholder="Modal text"   id="wcap_email_placeholder_section_input_text" name="wcap_email_placeholder_section_input_text"  v-model="settings.wcap_email_placeholder_section_input_text">
                                                                        </div>
                                                                     </div>                                                                     
                                                                  </div>
                                                               </div>
                                                            </div>
                                                         </div>
                                                         <div class="tm1-row align-items-center">
                                                            <div class="col-left">
                                                               <label><?php  esc_html_e( 'Add to cart button text', 'woocommerce-ac' ) ; ?>:<label>
                                                            </div>
                                                            <div class="col-right">
                                                               <div class="row-box-1">
                                                                  <div class="rb1-right">
                                                                     <div class="rb1-row flx-center mb-2">
                                                                        <div class="rb-col mt-2">
                                                                           <input class="ib-md" type="text"  placeholder="Add To Cart" id="wcap_button_section_input_text" name="wcap_button_section_input_text"  v-model="settings.wcap_button_section_input_text">
                                                                        </div>
                                                                     </div>
                                                                     <div class="d-flex">
                                                                        <div class="color-picker color-swither mr-2">
                                                                            <input type="color" class="holiday-color" value="#FFBA00" id="wcap_button_color_picker" name="wcap_button_color_picker"  v-model="settings.wcap_button_color_picker"  @change = " wcap_button_color_picker = settings.wcap_button_color_picker " > <span for="favcolor" class="holiday-color">{{wcap_button_color_picker}}</span> 
                                                                        </div>
                                                                        <div class="color-picker color-swither">
                                                                            <input type="color"  class="holiday-color" value="#1A8D34"  id="wcap_button_text_color_picker" name="wcap_button_text_color_picker"  v-model="settings.wcap_button_text_color_picker"  @change = " wcap_button_text_color_picker = settings.wcap_button_text_color_picker " > <span for="favcolor" class="holiday-color">{{wcap_button_text_color_picker}}</span> 
                                                                        </div>
                                                                     </div>
                                                                  </div>
                                                               </div>
                                                            </div>
                                                         </div>
                                                         <div class="tm1-row align-items-center">
                                                            <div class="col-left">
                                                               <label><?php esc_html_e( 'Email address is mandatory?', 'woocommerce-ac' ); ?></label>
                                                               <label class="el-switch el-switch-green ml-3">
                                                                    <input type="checkbox"   id="wcap_switch_atc_modal_mandatory" name="wcap_switch_atc_modal_mandatory"  v-model="settings.wcap_atc_mandatory_email" true-value="on" false-value="">
                                                                    <span class="el-switch-style"></span>
                                                                </label>
                                                            </div>
                                                         </div>
																			<div class="tm1-row align-items-center" id="enable_seasonal_price_div" v-show=" ( ! settings.wcap_atc_mandatory_email || '' == settings.wcap_atc_mandatory_email || 'off' == settings.wcap_atc_mandatory_email ) && ( ! settings.wcap_switch_atc_phone_mandatory || '' == settings.wcap_switch_atc_phone_mandatory || 'off' === settings.wcap_switch_atc_phone_mandatory ) ">
                                                            <div class="col-left">
                                                               <label><?php  esc_html_e( 'Not mandatory text', 'woocommerce-ac' ) ; ?>:<label>
                                                            </div>
                                                            <div class="col-right">
                                                               <div class="row-box-1">
                                                                  <div class="rb1-right">
                                                                     <div class="rb1-row flx-center mb-2">
                                                                        <div class="rb-col mt-2">
                                                                           <input class="ib-md" type="text" placeholder="<?php  esc_html_e('No Thanks', 'woocommerce-ac' ); ?>"   id="wcap_non_mandatory_modal_section_fields_input_text" name="wcap_non_mandatory_modal_section_fields_input_text"  v-model="settings.wcap_non_mandatory_text">
                                                                        </div>
                                                                     </div>
                                                                  </div>
                                                               </div>
                                                            </div>
                                                         </div>
                                                         <div class="tm1-row align-items-center">
                                                            <div class="col-left">
                                                               <label><?php  esc_html_e( 'Capture Phone', 'woocommerce-ac' ) ; ?>:<label>
                                                               <label class="el-switch el-switch-green ml-3">
                                                                    <input type="checkbox"  id="wcap_switch_atc_capture_phone" name="wcap_switch_atc_capture_phone"  v-model="settings.wcap_atc_capture_phone" true-value="on" false-value="">
                                                                    <span class="el-switch-style"></span>
                                                                </label>
                                                            </div>
                                                         </div>
                                                         <div class="tm1-row partial_payment_div align-items-center" v-show="'on' == settings.wcap_atc_capture_phone " >
                                                            <div class="col-left">
                                                               <label><?php  esc_html_e( 'Phone placeholder', 'woocommerce-ac' ) ; ?>:<label>
                                                               <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="" data-original-title="Tooltip content goes here">
                                                            </div>
                                                            <div class="col-right">
                                                               <div class="row-box-1">
                                                                  <div class="rb1-right">
                                                                     <div class="rb1-row flx-center mb-2">
                                                                        <div class="rb-col mt-2">
																									<input class="ib-md" type="text" placeholder="<?php esc_html_e( 'Phone number (e.g. +19876543210)', 'woocommerce-ac' ); ?>" id="wcap_phone_placeholder_section_input_text" name="wcap_phone_placeholder_section_input_text"  v-model="settings.wcap_atc_phone_placeholder" true-value="on" false-value="">
                                                                        </div>
                                                                     </div>
                                                                  </div>
                                                               </div>
                                                            </div>
                                                         </div>
                                                         <div class="tm1-row align-items-center" v-show="'on' == settings.wcap_atc_capture_phone ">
                                                            <div class="col-left">
                                                               <label><?php  esc_html_e( 'Phone number is mandatory?', 'woocommerce-ac' ) ; ?><label>
                                                               <label class="el-switch el-switch-green ml-3">
                                                                    <input type="checkbox"  id="wcap_switch_atc_phone_mandatory" name="wcap_switch_atc_phone_mandatory"  v-model="settings.wcap_switch_atc_phone_mandatory" true-value="on" false-value="">
                                                                    <span class="el-switch-style"></span>
                                                                </label>
                                                            </div>
                                                         </div>
                                                      </div>
                                                   </div>
                                                </div>
                                             </div>
											 
                                             <div class="col-xl-8 col-lg-8 col-md-12 col-sm-12 col-12">
                                                <div class="subscribe-body">
                                                   <div class="subscribe-head">
                                                      <div v-if="file !== ''">
                                                         <img style="border-radius:50%;" v-bind:src="settings.wcap_path + file" width="100" height="104" />
                                                         <div class="Cancel-icon" @click="deleteFile_image()" style="background-image: url( '<?php echo esc_url( WCAP_PLUGIN_URL );?>/assets/images/cancel-icon.jpg')">
                                                         </div>
                                                      </div>
                                                      <h1 class="mb-0" :style="{ color: wcap_popup_heading_color_picker }" >{{settings.wcap_heading_section_text_email}}</h1>
													               <p :style="{ color: wcap_popup_text_color_picker }" > {{settings.wcap_text_section_text}} </p>
                                                      <input class="ib-md" type="text" name="" readonly :placeholder="settings.wcap_email_placeholder_section_input_text">
                                                      <input v-show="'on'== settings.wcap_atc_capture_phone" class="ib-md min-auto" type="text" name="" readonly :placeholder="settings.wcap_phone_placeholder_section_input_text">
                                                      <button type="button" class="mr-2" :style="{ backgroundColor: wcap_button_color_picker, color: wcap_button_text_color_picker  }" >{{settings.wcap_button_section_input_text}}</button>
                                                      <div class="subscribe-btn" v-show=" '' == settings.wcap_atc_mandatory_email && ( '' == settings.wcap_switch_atc_phone_mandatory || 'off' === settings.wcap_switch_atc_phone_mandatory )">
                                                         <a href="">{{settings.wcap_non_mandatory_modal_section_fields_input_text}}</a>
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
                           <!-- First Panel -->
                           <div class="panel-by-now " id="panel-by-now-div"   v-show="'exit_intent' == settings.wcap_template_type" >
                              <div class="panel panel-default mb-4">
                                 <div class="panel-heading">
                                    <h2 class="panel-title" data-toggle="collapse" data-target="#collapseFive" aria-expanded="false">
                                       <?php  esc_html_e( 'Configure popup for guest users', 'woocommerce-ac' ); ?>
                                    </h2>
                                 </div>
                                 <div id="collapseFive" class="panel-collapse collapse show">
                                    <div class="panel-body pt-0 pl-0 pb-0">
                                       <div class="tbl-mod-1">
                                          <div class="row align-items-center">
                                             <div class="col-xl-4 col-lg-4 col-md-12 col-sm-12 col-12">
                                                <div class="configure-popup">
                                                   <div class="configure-head">
                                                      <h2><?php  esc_html_e( 'Configure popup for guest users', 'woocommerce-ac' ); ?>
													   <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php  esc_html_e( 'These settings would show a popup to motivate the user to redirect to the Checkout page. Note: This popup would appear for guest users by default where email address has not been captured until then.', 'woocommerce-ac' ); ?>">
													   </h2>
                                                   </div>
                                                   <div class="configure-body">
                                                      <div class="tbl-mod-1">
                                                         <div class="tm1-row align-items-center border-top-0 pt-0">
                                                            <div class="col-left">
                                                               <label><?php  esc_html_e( 'Modal Image', 'woocommerce-ac' ) ; ?>:<label>
                                                             </div>
                                                            <div class="col-right">
                                                               <div class="row-box-1">
                                                                  <div class="rb1-right">
                                                                     <div class="rb1-row flx-center mb-2">
                                                                        <div class="rb-col mt-2">
                                                                           <input class="ib-md" type="file"   id="wcap_heading_section_text_image" name="wcap_heading_section_text_image" ref="file" @change="selectFile">
                                                                        </div>
                                                                     </div>
                                                                  </div>
                                                               </div>
                                                            </div>
                                                         </div>
                                                         <div class="tm1-row align-items-center">
                                                            <div class="col-left">
                                                               <label><?php  esc_html_e( 'Modal Heading', 'woocommerce-ac' ) ; ?>:<label>
                                                             </div>
                                                            <div class="col-right">
                                                               <div class="row-box-1">
                                                                  <div class="rb1-right">
                                                                     <div class="rb1-row flx-center mb-2">
                                                                        <div class="rb-col mt-2">
                                                                           <input class="ib-md" type="text" placeholder="<?php  esc_html_e( 'We are sad to see you leave', 'woocommerce-ac' ) ; ?>"   id="wcap_heading_section_text_email" name="wcap_heading_section_text_email"  v-model="settings.wcap_heading_section_text_email">
                                                                        </div>
                                                                     </div>
                                                                     <div class="color-picker color-swither">
                                                                         <input type="color" class="holiday-color2" id="wcap_popup_heading_color_picker" name="wcap_popup_heading_color_picker"  v-model="settings.wcap_popup_heading_color_picker" @change=" wcap_popup_heading_color_picker = settings.wcap_popup_heading_color_picker"  > <span for="favcolor" class="holiday-color">{{wcap_popup_heading_color_picker}}</span> 
                                                                     </div>
                                                                  </div>
                                                               </div>
                                                            </div>
                                                         </div>
                                                         <div class="tm1-row align-items-center">
                                                            <div class="col-left">
                                                               <label><?php  esc_html_e( 'Modal Text', 'woocommerce-ac' ) ; ?>:<label>
                                                             </div>
                                                            <div class="col-right">
                                                               <div class="row-box-1">
                                                                  <div class="rb1-right">
                                                                     <div class="rb1-row flx-center mb-2">
                                                                        <div class="rb-col mt-2">
                                                                           <input class="ib-md" type="text" placeholder="<?php  esc_html_e( 'There are some items in your cart. These will not last long. Please proceed to checkout to complete the purchase.', 'woocommerce-ac' ) ; ?>"   id="wcap_text_section_text" name="wcap_text_section_text"  v-model="settings.wcap_text_section_text" >
                                                                        </div>
                                                                     </div>
                                                                     <div class="color-picker color-swither">
                                                                         <input type="color" class="holiday-color"  id="wcap_quick_ck_popup_text_color_picker" name="wcap_popup_text_color_picker"  v-model="settings.wcap_popup_text_color_picker" @change=" wcap_popup_text_color_picker = settings.wcap_popup_text_color_picker" > <span for="favcolor" class="holiday-color">{{wcap_popup_text_color_picker}}</span> 
                                                                     </div>
                                                                  </div>
                                                               </div>
                                                            </div>
                                                         </div>
														 <div class="tm1-row align-items-center">
                                                            <div class="col-left">
                                                               <label><?php esc_html_e( 'Email placeholder', 'woocommerce-ac' ); ?>:<label>
                                                            </div>
                                                            <div class="col-right">
                                                               <div class="row-box-1">
                                                                  <div class="rb1-right">
                                                                     <div class="rb1-row flx-center mb-2">
                                                                        <div class="rb-col mt-2">
                                                                           <input class="ib-md" type="text" name="" placeholder="Modal text"   id="wcap_email_placeholder_section_input_text" name="wcap_email_placeholder_section_input_text"  v-model="settings.wcap_email_placeholder_section_input_text">
                                                                        </div>
                                                                     </div>                                                                     
                                                                  </div>
                                                               </div>
                                                            </div>
                                                         </div>
                                                         <div class="tm1-row align-items-center">
                                                            <div class="col-left">
                                                               <label><?php  esc_html_e( 'Link Text', 'woocommerce-ac' ); ?></label>
                                                            </div>
                                                            <div class="col-right">
                                                               <div class="row-box-1">
                                                                  <div class="rb1-right">
                                                                     <div class="rb1-row flx-center mb-2">
                                                                        <div class="rb-col mt-2">
                                                                           <input class="ib-md" type="text" placeholder="<?php  esc_html_e( 'Complete my order!', 'woocommerce-ac' ); ?>"   id="wcap_button_section_input_text" name="wcap_button_section_input_text"  v-model="settings.wcap_button_section_input_text" >
                                                                        </div>
                                                                     </div>
                                                                     <div class="color-picker color-swither mr-2">
                                                                         <input type="color"  class="holiday-color"  value="#FFFFFF"   id="wcap_button_color_picker" name="wcap_button_color_picker"  v-model="settings.wcap_button_color_picker" @change=" wcap_button_color_picker = settings.wcap_button_color_picker" > <span for="favcolor" class="holiday-color">{{wcap_button_color_picker}}</span> 
                                                                     </div>
																	 <div class="color-picker color-swither mr-2">
                                                                         <input type="color"  class="holiday-color"  value="#FFFFFF"   id="wcap_button_text_color_picker" name="wcap_button_text_color_picker"  v-model="settings.wcap_button_text_color_picker" @change=" wcap_button_text_color_picker = settings.wcap_button_text_color_picker" > <span for="favcolor" class="holiday-color">{{wcap_button_text_color_picker}}</span> 
                                                                     </div>
                                                                  </div>
                                                               </div>
                                                            </div>
                                                         </div>
                                                           <div class="tm1-row align-items-center">
                                                            <div class="col-left">
                                                               <label><?php esc_html_e( 'Email address is mandatory?', 'woocommerce-ac' ); ?></label>
                                                               <label class="el-switch el-switch-green ml-3">
                                                                    <input type="checkbox"   id="wcap_switch_atc_modal_mandatory" name="wcap_switch_atc_modal_mandatory"  v-model="settings.wcap_atc_mandatory_email" true-value="on" false-value="">
                                                                    <span class="el-switch-style"></span>
                                                                </label>
                                                            </div>
                                                         </div>
                                                         <div class="tm1-row align-items-center" id="enable_seasonal_price_div" v-show=" settings.wcap_atc_mandatory_email =='' ">
                                                            <div class="col-left">
                                                               <label><?php  esc_html_e( 'Not mandatory text', 'woocommerce-ac' ) ; ?>:<label>
                                                            </div>
                                                            <div class="col-right">
                                                               <div class="row-box-1">
                                                                  <div class="rb1-right">
                                                                     <div class="rb1-row flx-center mb-2">
                                                                        <div class="rb-col mt-2">
                                                                           <input class="ib-md" type="text" placeholder="<?php  esc_html_e('No Thanks', 'woocommerce-ac' ); ?>"   id="wcap_non_mandatory_modal_section_fields_input_text" name="wcap_non_mandatory_modal_section_fields_input_text"  v-model="settings.wcap_non_mandatory_modal_section_fields_input_text">
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
											 
											 
 <div class="col-xl-8 col-lg-8 col-md-12 col-sm-12 col-12">
                                                <div class="subscribe-body">
                                                   <div class="subscribe-head">
                                                   	<div v-if="file !== ''">
                                                         <img style="border-radius:50%;" v-bind:src="settings.wcap_path + file" width="100" height="104" />
                                                         <div class="Cancel-icon" @click="deleteFile_image()" style="background-image: url('<?php echo esc_url( WCAP_PLUGIN_URL );?>/assets/images/cancel-icon.jpg')">
                                                         </div>
                                                      </div>
                                                      <h1 class="mb-0" :style="{ color: wcap_popup_heading_color_picker }" >{{settings.wcap_heading_section_text_email}}</h1>
													  <p :style="{ color: wcap_popup_text_color_picker }" > {{settings.wcap_text_section_text}} </p>
													  <input class="ib-md" type="text" name="" readonly :placeholder="settings.wcap_email_placeholder_section_input_text">
													  <textarea v-show="'on'== settings.wcap_switch_atc_capture_phone" class="ib-md min-auto" rows="3" :placeholder="settings.wcap_phone_placeholder_section_input_text"></textarea>
                                         <div>
													  <button type="button" class="trietary-btn eic_popup" :style="{ backgroundColor: wcap_button_color_picker, color: wcap_button_text_color_picker  }" >{{settings.wcap_button_section_input_text}}</button>
                                         </div>
													  <div class="subscribe-btn" v-show="''== settings.wcap_atc_mandatory_email">
                                                        <a class="etc_link"href="">{{settings.wcap_non_mandatory_modal_section_fields_input_text}}</a>
                                                       </div>


                                                   </div>
                                                   
                                                </div>
                                             </div> 
											 
											 
											 
                                             
											 
											 
											 
											 
											 
                                          </div>
                                       </div>
                                    </div>
                                 </div>
                              </div>
                              <div class="panel panel-default mb-4">
                                 <div class="panel-heading">
                                    <h2 class="panel-title" data-toggle="collapse" data-target="#collapseSix" aria-expanded="false">
                                      <?php  esc_html_e( 'Configure popup for logged-in users', 'woocommerce-ac' ); ?>
                                    </h2>
                                 </div>
                                 <div id="collapseSix" class="panel-collapse collapse show">
                                    <div class="panel-body pt-0 pl-0 pb-0">
                                       <div class="tbl-mod-1">
                                          <div class="row align-items-center">
                                             <div class="col-xl-4 col-lg-4 col-md-12 col-sm-12 col-12">
                                                <div class="configure-popup">
                                                   <div class="configure-head">
                                                      <h2><?php  esc_html_e( 'Configure popup for logged-in users', 'woocommerce-ac' ); ?>
													  <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php  esc_html_e( 'These settings would show a popup to force the user to redirect to the Checkout page. Note: This popup would appear for logged in users by default and can be forced for Guest users without email address as well.', 'woocommerce-ac' ); ?>">
                                                               
													  </h2>
                                                   </div>
                                                   <div class="configure-body">
                                                      <div class="tbl-mod-1">
																			<div class="tm1-row align-items-center">
																				<div class="col-left">
																					<label><?php esc_html_e( 'Enable Exit Intent popup for logged-in users', 'woocommerce-ac' ); ?></label>
																					<label class="el-switch el-switch-green ml-1">
																						<input type="checkbox" id="wcap_enable_ei_for_registered_users" name="wcap_enable_ei_for_registered_users" v-model="settings.wcap_enable_ei_for_registered_users" true-value="on" false-value="">
																						<span class="el-switch-style"></span>
																					</label>
																					<img class="tt-info" src="<?php echo esc_url( esc_url( WCAP_PLUGIN_URL ) ); ?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'Please note that if this setting is disabled, the popup will not appear for logged-in users.', 'woocommerce-ac' ); ?>">
																				</div>
																			</div>
                                                         <div class="tm1-row align-items-center">
                                                            <div class="col-left">
                                                               <label><?php esc_html_e( 'Allow all users (including guest users) to checkout without capturing email', 'woocommerce-ac' ); ?></label>
                                                               <label class="el-switch el-switch-green ml-1">
                                                                    <input type="checkbox"   id="wcap_quick_ck_force_user_to_checkout" name="wcap_quick_ck_force_user_to_checkout"  v-model="settings.wcap_quick_ck_force_user_to_checkout" true-value="on" false-value="">
                                                                    <span class="el-switch-style"></span>
                                                                </label>
																<img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php  esc_html_e( 'Please note that if this setting is enabled, then the email address capture popup will not appear for Guest users.', 'woocommerce-ac' ); ?>">
                                                               
                                                            </div>
                                                         </div>
                                                         <div class="tm1-row align-items-center border-top-0 pt-0">
                                                            <div class="col-left">
                                                               <label><?php  esc_html_e( 'Modal Image', 'woocommerce-ac' ) ; ?>:<label>
                                                             </div>
                                                            <div class="col-right">
                                                               <div class="row-box-1">
                                                                  <div class="rb1-right">
                                                                     <div class="rb1-row flx-center mb-2">
                                                                        <div class="rb-col mt-2">
                                                                           <input class="ib-md" type="file"   id="wcap_heading_section_ei_text_image" name="wcap_heading_section_ei_text_image"  ref="file1" @change="selectFile_ei">
                                                                        </div>
                                                                     </div>
                                                                     
                                                                  </div>
                                                               </div>
                                                            </div>
                                                         </div>
                                                         <div class="tm1-row align-items-center">
                                                            <div class="col-left">
                                                               <label><?php  esc_html_e( 'Modal Heading', 'woocommerce-ac' ) ; ?>:<label>
                                                             </div>
                                                            <div class="col-right">
                                                               <div class="row-box-1">
                                                                  <div class="rb1-right">
                                                                     <div class="rb1-row flx-center mb-2">
                                                                        <div class="rb-col mt-2">
                                                                           <input class="ib-md" type="text" placeholder="<?php  esc_html_e( 'We are sad to see you leave', 'woocommerce-ac' ) ; ?>"   id="wcap_quick_ck_heading_section_text_email" name="wcap_quick_ck_heading_section_text_email"  v-model="settings.wcap_quick_ck_heading_section_text_email">
                                                                        </div>
                                                                     </div>
                                                                     <div class="color-picker color-swither">
                                                                         <input type="color"  class="holiday-color2" value="#737f97" id="wcap_quick_ck_popup_heading_color_picker" name="wcap_quick_ck_popup_heading_color_picker"  v-model="settings.wcap_quick_ck_popup_heading_color_picker" @change=" wcap_quick_ck_popup_heading_color_picker = settings.wcap_quick_ck_popup_heading_color_picker " > <span for="favcolor" class="holiday-color">{{wcap_quick_ck_popup_heading_color_picker}}</span> 
                                                                     </div>
                                                                  </div>
                                                               </div>
                                                            </div>
                                                         </div>
                                                         <div class="tm1-row align-items-center">
                                                            <div class="col-left">
                                                               <label><?php  esc_html_e( 'Modal Text', 'woocommerce-ac' ) ; ?>:<label>
                                                             </div>
                                                            <div class="col-right">
                                                               <div class="row-box-1">
                                                                  <div class="rb1-right">
                                                                     <div class="rb1-row flx-center mb-2">
                                                                        <div class="rb-col mt-2">
                                                                           <input class="ib-md" type="text" placeholder="<?php  esc_html_e( 'There are some items in your cart. These will not last long. Please proceed to checkout to complete the purchase.', 'woocommerce-ac' ) ; ?>"   id="wcap_quick_ck_text_section_text" name="wcap_quick_ck_text_section_text"  v-model="settings.wcap_quick_ck_text_section_text" >
                                                                        </div>
                                                                     </div>
                                                                     <div class="color-picker color-swither">
                                                                         <input type="color"  class="holiday-color"  value="#000000"   id="wcap_quick_ck_popup_text_color_picker" name="wcap_quick_ck_popup_text_color_picker"  v-model="settings.wcap_quick_ck_popup_text_color_picker" @change=" wcap_quick_ck_popup_text_color_picker = settings.wcap_quick_ck_popup_text_color_picker" > <span for="favcolor" class="holiday-color">{{wcap_quick_ck_popup_text_color_picker}}</span> 
                                                                     </div>
                                                                  </div>
                                                               </div>
                                                            </div>
                                                         </div>
                                                         <div class="tm1-row align-items-center">
                                                            <div class="col-left">
                                                               <label><?php  esc_html_e( 'Link Text', 'woocommerce-ac' ); ?></label>
                                                            </div>
                                                            <div class="col-right">
                                                               <div class="row-box-1">
                                                                  <div class="rb1-right">
                                                                     <div class="rb1-row flx-center mb-2">
                                                                        <div class="rb-col mt-2">
                                                                           <input class="ib-md" type="text" placeholder="<?php  esc_html_e( 'Complete my order!', 'woocommerce-ac' ); ?>"   id="wcap_quick_ck_button_section_input_text" name="wcap_quick_ck_button_section_input_text"  v-model="settings.wcap_quick_ck_button_section_input_text" >
                                                                        </div>
                                                                     </div>
                                                                     <div class="color-picker color-swither mr-2">
                                                                         <input type="color"  class="holiday-color"  value="#FFFFFF"   id="wcap_quick_ck_button_color_picker" name="wcap_quick_ck_button_color_picker"  v-model="settings.wcap_quick_ck_button_color_picker" @change=" wcap_quick_ck_button_color_picker = settings.wcap_quick_ck_button_color_picker" > <span for="favcolor" class="holiday-color">{{wcap_quick_ck_button_color_picker}}</span> 
                                                                     </div>
																	 <div class="color-picker color-swither mr-2">
                                                                         <input type="color"  class="holiday-color"  value="#FFFFFF"   id="wcap_quick_ck_button_text_color_picker" name="wcap_quick_ck_button_text_color_picker"  v-model="settings.wcap_quick_ck_button_text_color_picker" @change=" wcap_quick_ck_button_text_color_picker = settings.wcap_quick_ck_button_text_color_picker" > <span for="favcolor" class="holiday-color">{{wcap_quick_ck_button_text_color_picker}}</span> 
                                                                     </div>
                                                                  </div>
                                                               </div>
                                                            </div>
                                                         </div>
                                                         <div class="tm1-row pb-0 align-items-center">
                                                            <div class="col-left">
                                                               <label><?php  esc_html_e( 'Link to redirect to', 'woocommerce-ac' ); ?></label>
                                                            </div>
                                                            <div class="col-right">
                                                               <div class="row-box-1">
                                                                  <div class="rb1-right">
                                                                     <div class="rb1-row flx-center mb-2">
                                                                        <div class="rb-col mt-2">
                                                                           <input class="ib-md" type="text" :placeholder="settings.wcap_quick_ck_redirect_to"   id="wcap_quick_ck_redirect_to" name="wcap_quick_ck_redirect_to"  v-model="settings.wcap_quick_ck_redirect_to">
                                                                           <p class="mt-2 mb-0"><?php echo  esc_html_e( 'URL of the page where the popup should redirect. Leaving blank here will take the user to the Checkout page.', 'woocommerce-ac' ); ?></p>
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
											 <div class="col-xl-8 col-lg-8 col-md-12 col-sm-12 col-12">
                                                <div class="subscribe-body buy-subscribe-box text-center">
                                                   <div class="subscribe-head">
                                                   	<div v-if="file_ei !== ''">
                                                            <img style="border-radius:50%;" v-bind:src="settings.wcap_path + file_ei" width="100" height="104" />
                                                            <div class="Cancel-icon" @click="deleteFile_image_ei()" style="background-image: url( '<?php echo esc_url( WCAP_PLUGIN_URL );?>/assets/images/cancel-icon.jpg')">
                                                            </div>
                                                      </div>
                                                      <h1 class="mb-0" :style="{ color: wcap_quick_ck_popup_heading_color_picker }">{{settings.wcap_quick_ck_heading_section_text_email}}</h1>
                                                      <p :style="{ color: wcap_quick_ck_popup_text_color_picker }" >{{settings.wcap_quick_ck_text_section_text}}</p>
                                                      <button class="trietary-btn"  :style="{ backgroundColor: wcap_quick_ck_button_color_picker, color: wcap_quick_ck_button_text_color_picker  }"  >{{settings.wcap_quick_ck_button_section_input_text}}</button>
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
					 
					 
					 
					    <div class="tbl-mod-1">
                                       <div class="tm1-row align-items-center">
                                          <div class="col-left">
                                             <button class="secondary-btn btn-red" @click ="reset_template('<?php echo esc_attr( $template_settings['template_id'] ); ?>')" ><?php  esc_html_e( 'Reset to default configuration', 'woocommerce-ac' ) ; ?></button>
                                                        
                                          </div>
                                          <div class="col-right">
                                             <div class="row-box-1 add_edit_save">   
                                                <button type="button" class="top-back" @click="back_popup_templates_list()" ><?php  esc_html_e( 'Back', 'woocommerce-ac' ) ; ?></button>                                             
                                                <button type="button" @click="wcap_add_to_cart_popup_save_settings()"><?php  esc_html_e( 'Save Settings', 'woocommerce-ac' ) ; ?></button>
                                                     
                                             </div>
                                          </div>
                                       </div>                                       
                                    </div>
					 
					 
					 
                     <div class="save-btn mt-4 text-right">
					 <div class="coupon__main coupon__two tbl-mod-1">
                                          
                                          <div class="tm1-row align-items-center">
                                             <div class="col-left">
                                                <div class="rb-col">
                                                             </div>
                                             </div>
                                             <div class="col-right">
                                                <div class="row-box-1">
                                                   <div class="rb1-right">
                                                      <div class="rb1-row flx-center">
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

		</form>
		</div>
		<!-- Content Area End -->
	
	<?php include_once( dirname( __FILE__ ) . '/' . '../../ac-footer.php' ); ?>