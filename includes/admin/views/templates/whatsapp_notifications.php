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
<template id="whatsapp_notifications">
<!-- Content Area -->
          <div class="container fas-page-wrap ac-page-head pb-0" id="sms_notifications_cover" >

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
						{{message}}...<img src="<?php echo esc_url( esc_url( WCAP_PLUGIN_URL ) ) ;?>/assets/images/ajax-loader.gif">
					</div>
				</div>
               
			   <div class="container fas-page-wrap">
               <div class="row">
                  <div class="col-md-12">
                     <div class="phw-btn pb-0 justify-content-between">
                        <div class="col-left">
                           <!-- <h1>Email Template</h1> -->
                           <!-- <p class="mb-3"><?php //esc_html_e( 'Add SMS Notifications at different intervals to maximize the possibility of recovering your abandoned carts', 'woocommerce-ac' ); ?></p> -->
                           <!-- <p class="mb-3"><b><?php //esc_html_e( 'Edit Instructions', 'woocommerce-ac' ); ?> : </b> <?php //esc_html_e( 'Please click on the Text Message to edit it. Then click on the Save button. You can edit multiple text messages at once.', 'woocommerce-ac' ); ?></p> -->
                           <div class="email-btn mb-4">
                              <button type="button" class="trietary-btn reverse" data-toggle="modal" data-target="#Fb-2" @click="clean_pop_up_data()"> <i class="fas fa-plus"></i> <?php esc_html_e( 'Add New Template', 'woocommerce-ac' ); ?></button>
                           </div>
                        </div>
                     </div>
                     <div class="row">
                        <div class="col-md-12">
                           <div class="tm1-row align-items-center bdr-0 pt-0 delvry-sch-bottom mb-4">
                              <div class="abulk-box pt-0 ">
									<select class="ib-small" v-model="wcap_action" >
                                    <option value=""  ><?php esc_html_e( 'Bulk Action', 'woocommerce-ac' ); ?></option>                                   
                                    <option value="wcap_delete_template" ><?php esc_html_e( 'Delete', 'woocommerce-ac' ); ?></option>                                   
									</select>
                                 <button class="trietary-btn reverse" type="button"  @click="bulk_action()" ><?php esc_html_e( 'Apply', 'woocommerce-ac' ); ?></button>
                              </div>
                              <div class="action-url">
                                 <p class="m-0">{{settings.total_items}} <?php esc_html_e( 'items', 'woocommerce-ac' ); ?></p>
                              </div>
                           </div>
                        </div>
                     </div>
                     <div class="obp-content">
                        <div class="wbc-box">
                           <div class="tbl-mod-1">
                              <div class="custom-integrations">
                                 <div class="bts-content">
                                    <div class="tbl-mod-2 flx-100">
                                       <div class="tm2-inner-wrap tbl-responsive">
                                          <table class="table">
                                             <thead>
                                                <tr>
                                                   <th style="width:50px;" >
                                                      <div class="custom-control custom-checkbox">
                                                         <input type="checkbox" class="custom-control-input" id="customCheck1"  @change="bulk_select_ids( select_all )" v-model="select_all"   true-value="true" false-value="false" >
                                                         <label class="custom-control-label" for="customCheck1"></label>
                                                      </div>
                                                   </th>
                                                   <th style="width:50px;" ><?php esc_html_e( 'ID', 'woocommerce-ac' ); ?></th>
                                                   <th style="width:150px;" ><?php esc_html_e( 'Template Name', 'woocommerce-ac' ); ?></th>
                                                   <th  style="width:100px;"  ><?php esc_html_e( 'Coupon Code', 'woocommerce-ac' ); ?></th>
                                                   <th style="width:100px;" ><?php esc_html_e( 'Sent After Set Time', 'woocommerce-ac' ); ?></th>
                                                   <th style="width:100px;" ><?php esc_html_e( 'Link Rate(%)', 'woocommerce-ac' ); ?></th>
                                                   <th style="width:100px;" ><?php esc_html_e( 'Conversion Rate(%)', 'woocommerce-ac' ); ?></th>
                                                   <th style="width:100px;" ><?php esc_html_e( 'Start Sending', 'woocommerce-ac' ); ?></th>
                                                   <th style="width:100px;"  ><?php esc_html_e( 'Action', 'woocommerce-ac' ); ?></th>
                                                </tr>
                                             </thead>
                                             <tbody>
                                                <tr class="cloned-row" v-for="( row, index ) in settings.whatsapp_templates" :key="index" :id="'row_' + index">
                                                   <td>
                                                      <div class="custom-control custom-checkbox">
                                                         <input type="checkbox" class="custom-control-input" :id="'setting_id_' + row.id "  v-model="bulk_selected_ids[row.id]"  @click="toggle_bulk_select(row.id)" true-value="true" false-value="false" >
                                                         <label class="custom-control-label"  :for="'setting_id_' + row.id "></label>
                                                      </div>
                                                   </td>
                                                   <td>{{row.id}}</td> 
                                                   <td class="">		
														<div>{{row.template_name}}</div>
													</td>
													<td class="" >
													<div>{{row.coupon_text}}</div>													
														
													</td>
													<td  class="">	
													<div style="width:100px;">													
														<div><span  v-if="row.frequency != 0 ">{{row.frequency}} {{row.day_or_hour}} <span v-if = "'' == row.day_or_hour" > <?php esc_html_e( 'Minutes', 'woocommerce-ac' ) ; ?> </span><br><?php esc_html_e( 'After Abandonment', 'woocommerce-ac' ); ?></span></div>
													</div>
													</td>												   
                                                   <td>{{row.click_rate}}</td>
                                                   <td>{{row.conversion_rate}}</td>
                                                   <td>
                                                      <label class="el-switch el-switch-green">
                                                      <input type="checkbox" name="cb_opt_nm_23" value="on" unchecked-value="off" v-model = 'row.is_active' true-value="1" false-value="0"  @change="toggle_template_status( row.is_active, row.id , 'whatsapp' )">
                                                      <span class="el-switch-style"></span>
                                                      </label>
                                                   </td>
                                                   <td>
														<a title="edit" data-toggle="modal" data-target="#Fb-2"  @click="edit_template( index, row )"> <?php echo esc_html__( 'Edit', 'woocommerce-ac' ); ?></a>
														<a class="disable" title="Delete" @click="delete_template( row.id )"><?php echo esc_html__( 'Delete', 'woocommerce-ac' ); ?></a>
													</td>
                                                </tr>                                               
                                             </tbody>
                                             <thead>
                                                <tr>
                                                   <th>
                                                      <div class="custom-control custom-checkbox">
                                                         <input type="checkbox" class="custom-control-input" id="customCheck7"   @change="bulk_select_ids( select_all )" v-model="select_all"   true-value="true" false-value="false" >
                                                         <label class="custom-control-label" for="customCheck7"></label>
                                                      </div>
                                                   </th>
                                                   <th><?php esc_html_e( 'ID', 'woocommerce-ac' ); ?></th>
                                                   <th><?php esc_html_e( 'Name', 'woocommerce-ac' ); ?></th>
                                                   <th><?php esc_html_e( 'Coupon Code', 'woocommerce-ac' ); ?></th>
                                                   <th><?php esc_html_e( 'Sent After Set Time', 'woocommerce-ac' ); ?></th>
                                                   <th><?php esc_html_e( 'Link Rate(%)', 'woocommerce-ac' ); ?></th>
                                                   <th><?php esc_html_e( 'Conversion Rate(%)', 'woocommerce-ac' ); ?></th>
                                                   <th><?php esc_html_e( 'Start Sending', 'woocommerce-ac' ); ?></th>
                                                   <th class="action-width"><?php esc_html_e( 'Action', 'woocommerce-ac' ); ?></th>
                                                </tr>
                                             </thead>
                                          </table>
                                       </div>
                                    </div>
                                 </div>
                              </div>
                           </div>
                           <div class="rb1-row flx-center mb-4">
                           </div>
                        </div>
                     </div>
                     <div class="tm1-row align-items-center bdr-0 pt-0 delvry-sch-bottom mb-3">
                        <div class="abulk-box pt-0 ">
                           <select class="ib-small" v-model="wcap_action" >
                                    <option value="" ><?php esc_html_e( 'Bulk Action', 'woocommerce-ac' ); ?></option>                                   
                                    <option value="wcap_delete_template" ><?php esc_html_e( 'Delete', 'woocommerce-ac' ); ?></option>                                   
                           </select>
                           <button class="trietary-btn reverse" type="button"  @click="bulk_action()" ><?php esc_html_e( 'Apply', 'woocommerce-ac' ); ?></button>
                        </div>
                        <div class="action-url">
                           <p class="m-0">{{settings.total_items}} <?php esc_html_e( 'items', 'woocommerce-ac' ); ?></p>
                        </div>
                     </div>
					 <div v-pre>
                     <h4 class="mb-2" ><?php esc_html_e( 'Merge tags available for WhatsApp Messages', 'woocommerce-ac' ); ?>:</h4><p class="mb-2" >{{user.name}} - <?php esc_html_e( 'First Name of the User', 'woocommerce-ac' ); ?></p><p class="mb-2">{{shop.name}} - <?php esc_html_e( 'Shop Name', 'woocommerce-ac' ); ?> [<?php echo esc_html( get_bloginfo( 'name' ) ); ?>]</p><p class="mb-2">{{shop.link}} - <?php esc_html_e( 'Shop Link', 'woocommerce-ac' ); ?> [<?php echo esc_url( home_url() ); ?>]</p><p class="mb-2">{{date.abandoned}} - <?php esc_html_e( 'Date on which the Cart was abandoned', 'woocommerce-ac' ); ?></p><p class="mb-2">{{coupon.code}} - <?php esc_html_e( 'Discount coupon code', 'woocommerce-ac' ); ?> </p><p class="mb-2">{{checkout.link}} - <?php esc_html_e( 'Checkout Link to complete the purchase', 'woocommerce-ac' ); ?></p><p>{{phone.number}} - <?php esc_html_e( 'Admin Phone number', 'woocommerce-ac' ); ?></p>
					</div>
				  </div>
               </div>
            </div>


            <!-- Modal -->
       <div class="modal fade" id="Fb-2" tabindex="-1" role="dialog"  ref="Dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
         <div class="modal-dialog modal-dialog-centered  modal-lg" role="document">
            <div class="modal-content">
               <div class="modal-header" style="background-color: #58419c;">
                  <h5 v-if="popup_data.id" class="modal-title d-flex" id="exampleModalLabel" style="color:#fff"><?php esc_html_e( 'Edit - WhatsApp Template #', 'woocommerce-ac' ); ?>{{popup_data.id}}</h5>
                  <h5 v-else class="modal-title d-flex" id="exampleModalLabel" style="color:#fff"><?php esc_html_e( 'Add New - WhatsApp Template', 'woocommerce-ac' ); ?></h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true" style="color: #fff;">&times;</span>
                  </button>
               </div>

               <div class="modal-body">
                  <div class="fb-popup sms-popup row tbl-mod-1">
                     <div class="col-lef col-lg-12 col-md-12">
                        <div class="tm1-row" >
                           <p style="font-style: italic;"><?php esc_html_e( 'Note: The template name, language, and variable placeholders must exactly match the template definition in your WhatsApp Cloud API account.', 'woocommerce-ac' ); ?></p>
                        </div>
                        <div class="tm1-row" >
                           <div class="col-left" style="margin-top: 10px ;">
                              <label style="padding-right:05px ;"><?php esc_html_e( 'Template Name', 'woocommerce-ac' ); ?></label>
                           </div>
                           <div class="col-right">
                              <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'Enter the exact name of the WhatsApp message template as created in your WhatsApp Cloud API account.', 'woocommerce-ac' ) ; ?>">
                              <input  style="width:100px;" type="text" class='template_name' :id="'template_name'+popup_data.index" :name="'template_name'+popup_data.index"  v-model ="popup_data.template_name" >
                           </div>
                        </div>
                        <div class="tm1-row" >
                           <div class="col-left" style="margin-top:10px ;">
                              <label style="padding-right:90px ;"><?php esc_html_e( 'Send', 'woocommerce-ac' );?></label> 
                                 <input v-if="popup_data.id" type="hidden" name="template_id" v-model="popup_data.id"/>
                           </div>
                           <div class="col-right" style="display:flex; align-items:center;">
                                 <div>
                                    <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'Set how long after the cart is abandoned the WhatsApp message should be sent (e.g., 15 minutes, 1 hour, 24 hours).', 'woocommerce-ac' ) ; ?>">
                                 </div>
                                 <div class="select-container">
                                    <select class="ib-sm ib-small" style="width:60px"  id="'frequency_' + popup_data.index" name="'frequency_' + popup_data.index"  v-model="popup_data.frequency" >
                                          <?php  for( $i = 1; $i < 60; $i++ ) { ?>
                                             <option value="<?php echo esc_html( $i ); ?>" ><?php echo esc_html( $i ); ?></option>   
                                                <?php } ?>                                               
                                    </select>
                                    <select class="ib-sm ib-small"  id="'day_or_hour_' + popup_data.index" name="'day_or_hour_' + popup_data.index"  v-model="popup_data.day_or_hour" >
                                       <option value="Minutes" ><?php esc_html_e( 'Minutes', 'woocommerce-ac' ) ; ?></option>
                                       <option value="Hours" ><?php esc_html_e( 'Hours', 'woocommerce-ac' ) ; ?></option>
                                       <option value="Days" ><?php esc_html_e( 'Days', 'woocommerce-ac' ) ; ?></option>
                                    </select>
                                    <p><?php esc_html_e( 'after cart is abandoned.', 'woocommerce-ac' ); ?></p>
                                 </div>
                                 
                           </div>
                        </div>

                        <div class="tm1-row" >
                           <div class="col-left" style="margin-top: 10px ;">
                              <label style="padding-right:05px ;"><?php esc_html_e( 'Template Language', 'woocommerce-ac' ); ?></label>
                           </div>
                           <div class="col-right">
                              <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'Select the language used when you created the template in your WhatsApp Cloud API account. This must match the template language there.', 'woocommerce-ac' ) ; ?>">
                              <select class="template_name"  :id="'template_language'+popup_data.index" :name="'template_language'+popup_data.index"  v-model="popup_data.template_language">
                                    <?php foreach ( WCAP_WHATSAPP_TEMPLATE_LANGUAGES as $key => $value ) : ?>
                                       <option value="<?php echo esc_attr( $key ); ?>" ><?php esc_html_e( $value ); ?></option>
                                    <?php endforeach; ?>
                              </select>
                           </div>
                        </div>
                  
                        <div class="tm1-row" >
                           <div class="col-left" style="margin-top: 10px;flex: 0 0 200px;display: flex;align-items: center;">
                              <label style="padding-right:05px ;"><?php esc_html_e( 'Header Variables', 'woocommerce-ac' ); ?></label>
                           </div>
                           <div class="col-right" style="flex: 1;display: flex;align-items: center;">
                                 <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'Provide the values for any variables defined in the template’s header. These should be in the same order and count as set in your templates within WhatsApp Cloud API account.', 'woocommerce-ac' ) ; ?>">
                                 <div style="display: flex;flex-direction: column;"> 
                                    <input  style="width:100px;" type="text" class='template_name' :id="'header_variables'+popup_data.index" :name="'header_variables'+popup_data.index"  v-model ="popup_data.header_variables" ><br>
                                    <small v-pre><?php echo esc_html__( "Enter variables in the format - <name or number variable>:<merge_tag>", "woocommerce-abandon-cart"); ?></small>  
                                    <small v-pre><?php echo esc_html__( "Example - ", "woocommerce-abandon-cart"); ?><code>{{1}}:{{shop.link}},{{2}}:{{user.name}}</code></small>
                                 </div>                           
                           </div>
                           
                        </div>

                        <div class="tm1-row" >
                           <div class="col-left" style="margin-top: 10px;flex: 0 0 200px;display: flex;align-items: center;">
                              <label style="padding-right:05px ;"><?php esc_html_e( 'Body Variables', 'woocommerce-ac' ); ?></label>
                           </div>
                           <div class="col-right" style="flex: 1;display: flex;align-items: center;">
                              <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'Provide the values for any variables defined in the template body. These should be in the same order and count as set in your templates within WhatsApp Cloud API account.', 'woocommerce-ac' ) ; ?>">
                              <div style="display: flex;flex-direction: column;"> 
                                 <input  style="width:100px;" type="text" class='template_name' :id="'body_variables'+popup_data.index" :name="'body_variables'+popup_data.index"  v-model ="popup_data.body_variables" >
                                 <small v-pre><?php echo esc_html__( "Enter variables in the format - <name or number variable>:<merge_tag>", "woocommerce-abandon-cart"); ?></small>  
                                 <small v-pre><?php echo esc_html__( "Example - ", "woocommerce-abandon-cart"); ?><code>{{shop_link}}:{{shop.link}},{{user_name}}:{{user.name}}</code></small>
                              </div>
                           </div>
                           
                        </div>

                        <div class="tm1-row" >
                           <div class="col-left" style="margin-top: 10px;flex: 0 0 200px;display: flex;align-items: center;">
                              <label style="padding-right:05px ;"><?php esc_html_e( 'Button Variables', 'woocommerce-ac' ); ?></label>
                           </div>
                           <div class="col-right" style="flex: 1;display: flex;align-items: center;">
                              <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'If the template contains buttons with variables (e.g., dynamic URLs or quick replies), provide the values here.', 'woocommerce-ac' ) ; ?>">

                              <div style="display: flex;flex-direction: column;">
                                 <input  style="width:100px;" type="text" class='template_name' :id="'button_variables'+popup_data.index" :name="'button_variables'+popup_data.index"  v-model="popup_data.button_variables" >
                                 <small v-pre><?php echo esc_html__( "Enter variables in the format - <name or number variable>:<merge_tag>", "woocommerce-abandon-cart"); ?></small>  
                                 <small v-pre><?php echo esc_html__( "Example - ", "woocommerce-abandon-cart"); ?><code>{{1}}:{{checkout.link}}</code></small>
                              </div>
                           </div>
                          
                        </div>

                        <div class="tm1-row" >
                           <div class="col-left" style="margin-top:10px ;">
                              <label style="padding-right:20px ;"><?php esc_html_e( 'Coupon Code', 'woocommerce-ac' ); ?></label>
                           </div>
                           <div class="col-right">
                              <img class="tt-info" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/icon-info.svg" alt="Info" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'Select coupon code to send to customers when {{coupon_code}} merge tag is used.', 'woocommerce-ac' ) ; ?>">
                              <select :id="'coupon_ids_' + popup_data.index " name="coupon_ids[]" class="wc-page-search" style="width: 100px;" data-placeholder="<?php esc_attr_e( 'Search for a Coupon&hellip;', 'woocommerce-ac' ); ?>" data-action="wcap_json_find_coupons"  multiple='multiple'>
                                 <option v-for="( value, key ) in popup_data.coupon_ids" :value="key" selected  >{{value}}</option>
                              </select>
                           </div>
                        </div>
                        
                        <div class="ss-foot">            
                           <button v-if="popup_data.id" type="button" @click="update_template( popup_data.index, popup_data )"><?php esc_html_e( 'Update', 'woocommerce-ac' ); ?></button>
                           <button v-else type="button" @click="save_template( popup_data.index, popup_data )"><?php esc_html_e( 'Save', 'woocommerce-ac' ); ?></button>
                        </div>
                     </div>
                  </div> 
               </div>
            </div>
      </div>
		</div>
</template>
