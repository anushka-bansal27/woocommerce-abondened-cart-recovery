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
<template id="facebook_messenger">
<!-- Content Area -->
          <div class="container fas-page-wrap ac-page-head pb-0" id="facebook_messenger_cover" >

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
               
			<div class="container fas-page-wrap">
               <div class="row">
                  <div class="col-md-12">
                     <div class="phw-btn justify-content-between pb-0">
                        <div class="col-left">
                           <!-- <p class="mb-3"><?php //esc_html_e( 'Add Facebook Messenger templates to be sent at different intervals to maximize the possibility of recovering your abandoned carts.', 'woocommerce-ac' ); ?></p> -->
                           <div class="email-btn mb-4">
                              <button href="" class="trietary-btn reverse" data-toggle="modal" data-target="#Fb-2" @click="clean_pop_up_data()" > <i class="fas fa-plus"></i> <?php esc_html_e( 'Add New Templates', 'woocommerce-ac' ); ?></button>
                           </div>
                        </div>
                     </div>
                     <div class="row">
                        <div class="col-md-12">
                           <div class="tm1-row align-items-center bdr-0 pt-0 delvry-sch-bottom mb-4">
                              <div class="abulk-box pt-0 ">
                                 <select class="ib-small" v-model="wcap_action" >
                                    <option value="" ><?php esc_html_e( 'Bulk Action', 'woocommerce-ac' ); ?></option>                                   
                                    <option value="wcap_delete_template" ><?php esc_html_e( 'Delete', 'woocommerce-ac' ); ?></option>                                   
									</select>
                                 <button class="trietary-btn reverse" type="button"  @click="bulk_action()" ><?php esc_html_e( 'Apply', 'woocommerce-ac' ); ?></button>
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
                                                <tr class="cloned">
                                                   <th>
                                                      <div class="custom-control custom-checkbox">
                                                         <input type="checkbox" class="custom-control-input" id="customCheck1"  @change="bulk_select_ids( select_all )" v-model="select_all"   true-value="true" false-value="false" >
                                                         <label class="custom-control-label" for="customCheck1"></label>
                                                      </div>
                                                   </th>
                                                   <th><?php esc_html_e( 'ID', 'woocommerce-ac' ); ?></th>
                                                   <th><?php esc_html_e( 'Text', 'woocommerce-ac' ); ?></th>
                                                   <th><?php esc_html_e( 'Template send time after abandonment', 'woocommerce-ac' ); ?></th>
                                                   <th><?php esc_html_e( 'Link Rate(%)', 'woocommerce-ac' ); ?></th>
                                                   <th><?php esc_html_e( 'Conversion Rate(%)', 'woocommerce-ac' ); ?></th>
                                                   <th><?php esc_html_e( 'Start Sending', 'woocommerce-ac' ); ?></th>
                                                </tr>
                                             </thead>
                                             <tbody>
                                                <tr class="cloned-row" v-for="( row, index ) in settings.fb_templates" :key="index">
													<td>
                                                      <div class="custom-control custom-checkbox mr-3">
                                                         <input type="checkbox" class="custom-control-input" :id="'setting_id_' + row.id "  v-model="bulk_selected_ids[row.id]"  @click="toggle_bulk_select(row.id)" true-value="true" false-value="false"  >
                                                         <label class="custom-control-label"   :for="'setting_id_' + row.id " ></label>
                                                      </div>
                                                   </td>
                                                   <td>{{row.id}}</td>
                                                   <td>
												   {{row.subject}}
                                                      <div class="edit-action">
                                                         <a class="green-text"   data-toggle="modal" data-target="#Fb-2" @click="edit_template( index, row )"  ><?php esc_html_e( 'Edit', 'woocommerce-ac' ); ?> </a><a class="red-text" @click="delete_template( row.id )" ><?php esc_html_e( 'Delete', 'woocommerce-ac' ); ?></a>
                                                      </div>
                                                   </td>
                                                   <td>{{row.sent_time}}</td>
                                                   <td>{{row.click_rate}}</td>
                                                   <td>{{row.conversion_rate}}</td>
                                                   <td>
                                                      <label class="el-switch el-switch-green">
                                                      <input type="checkbox" name="cb_opt_nm_23" value="on" unchecked-value="off" v-model = 'row.is_active' true-value="1" false-value="0"  @change="toggle_template_status( row.is_active, row.id )">
                                                      <span class="el-switch-style"></span>
                                                      </label>
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
                                                   <th><?php esc_html_e( 'Text', 'woocommerce-ac' ); ?></th>
                                                   <th><?php esc_html_e( 'Template send time after abandonment', 'woocommerce-ac' ); ?></th>
                                                   <th><?php esc_html_e( 'Link Rate(%)', 'woocommerce-ac' ); ?></th>
                                                   <th><?php esc_html_e( 'Conversion Rate(%)', 'woocommerce-ac' ); ?></th>
                                                   <th><?php esc_html_e( 'Start Sending', 'woocommerce-ac' ); ?></th>
                                                </tr>
                                             </thead>
                                          </table>
                                          <!-- <div class="add-more-link link-flx">
                                             <a class="al-link" data-toggle="collapse" href="#col_opt_id_1" role="button" aria-expanded="false" aria-controls="col_opt_id_1"><img src="assets/images/icon-plus.svg" alt="Icon"> Add More</a>
                                             </div> -->
                                       </div>
                                    </div>
                                 </div>
                              </div>
                           </div>
                           <div class="rb1-row flx-center mb-4">
                           </div>
                        </div>
                     </div>
                     <div class="tm1-row align-items-center bdr-0 pt-0 delvry-sch-bottom mb-4">
                        <div class="abulk-box pt-0 ">
                           <select class="ib-small" v-model="wcap_action" >
                                    <option value=""  ><?php esc_html_e( 'Bulk Action', 'woocommerce-ac' ); ?></option>                                   
                                    <option value="wcap_delete_template" ><?php esc_html_e( 'Delete', 'woocommerce-ac' ); ?></option>                                   
                           </select>
                           <button class="trietary-btn reverse" type="button"  @click="bulk_action()" ><?php esc_html_e( 'Apply', 'woocommerce-ac' ); ?></button>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
			
			  <!-- Modal -->
       <div class="modal fade" id="Fb-2" tabindex="-1" role="dialog"  ref="Dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
         <div class="modal-dialog modal-dialog-centered  modal-lg" role="document">
            <div class="modal-content">
               <div class="modal-header" style="background-color: #58419c;">
                  <h5 v-if="popup_data.id" class="modal-title d-flex" id="exampleModalLabel" style="color:#fff"><?php esc_html_e( 'Edit - Facebook Template #', 'woocommerce-ac' ); ?>{{popup_data.id}}</h5>
                  <h5 v-else class="modal-title d-flex" id="exampleModalLabel" style="color:#fff"><?php esc_html_e( 'Add New - Facebook Template', 'woocommerce-ac' ); ?></h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true" style="color: #fff;">&times;</span>
                  </button>
               </div>

               <div class="modal-body">
                  <div class="fb-popup row tbl-mod-1">
                     <div class="col-lef col-lg-7 col-md-7">
                        <div class="tm1-row" >
							<div class="col-left" style="margin-top:10px ;">
							   <label style="padding-right:90px ;"><?php esc_html_e( 'Send', 'woocommerce-ac' );?></label> 
<input v-if="popup_data.id" type="hidden" name="template_id" v-model="popup_data.id"/>						   
							</div>
							<div class="col-right">
								<select class="ib-sm ib-small"  name="wcap_frequency" id="wcap_frequency" v-model="popup_data.frequency">
								 <option v-for="n in 60" :value="n+1">{{n+1}}</option>
								  </select>
							   <select class="ib-sm ib-small" name="wcap_day_or_hour" id="wcap_day_or_hour" v-model="popup_data.day_or_hour" >
								  <option value="Minutes"><?php esc_html_e( 'Minute(s)', 'woocommerce-ac' ); ?></option>
								  <option value="Hours"><?php esc_html_e( 'Hour(s)', 'woocommerce-ac' ); ?></option>
								  <option value="Days"><?php esc_html_e( 'Day(s)', 'woocommerce-ac' ); ?></option>
							   </select>
							   <br/><?php esc_html_e( 'After cart is abandoned', 'woocommerce-ac' ); ?>
							</div>
						</div>
						
						
                     <div class="tm1-row" >
                        <div class="col-left" style="margin-top:10px ;">
                           <label style="padding-right:70px ;" ><?php esc_html_e( 'Subject', 'woocommerce-ac' ); ?></label>                          
                        </div>
						<div class="col-right">
							<!-- <input class="ib-lg" type="text" placeholder=""  name="wcap_subject" id="wcap_subject" v-model="popup_data.subject" > -->
                     <textarea class="ib-lg" type="text" placeholder=""  name="wcap_subject" id="wcap_subject" v-model="popup_data.subject"></textarea>
							<br/><?php esc_html_e( 'Use this as an identifier and an introduction message', 'woocommerce-ac' ); ?>
						</div>
						</div>
						<div class="tm1-row" >
                        <div class="col-left" style="margin-top:10px ;">
                              <label style="padding-right:20px ;"><?php esc_html_e( 'Checkout Label', 'woocommerce-ac' ); ?></label>
							  </div>
						<div class="col-right">
							<input class="ib-lg" type="text" placeholder=""  name="wcap_checkout" id="wcap_checkout" v-model="popup_data.checkout_text">
                        </div>
						</div>
                       <div class="tm1-row" >
                        <div class="col-left" style="margin-top: 10px ;">
                           <label style="padding-right:05px ;"><?php esc_html_e( 'Unsubscribe Text', 'woocommerce-ac' ); ?></label>
                            </div>
						<div class="col-right">
							<input class="ib-lg" type="text" placeholder="" name="wcap_unsubscribe_text" id="wcap_unsubscribe_text" class="form-control" v-model="popup_data.unsubscribe_text">
                       </div>
                         </div>
                     </div> 

			<div class="col-lg-5 col-md-5">

				<div class="rounded"  v-if="popup_data.subject" >
					<div class="wcap_preview_subject">
						{{popup_data.subject}}
					</div>
				</div>

				<div class="rounded ">
					<div class="wcap_fb_header center">					
						<img width="120" height="120" src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/wcap-fb-template1.jpg">
					</div>

					<div class="wcap_fb_list row">
						<div class="col-lg-12 col-md-12 col-sm-12 wcap_product_details">
							<h2 class="wcap_product_title"><?php esc_html_e( 'Cool Blue T-Shirt', 'woocommerce-ac' ); ?></h2>
							<span class="wcap_product_subdetails"><?php esc_html_e( '1 x $100', 'woocommerce-ac' ); ?></span>
							<br>
							<a class="wcap_product_subdetails" v-bind:href="popup_data.checkout_text" ><?php echo esc_url( get_site_url() );?></a> 
						</div>
					</div>
					<div class="wcap_button">
						<button class="wcap_unsubscribe_button">{{popup_data.checkout_text}}</button>
					</div>
					<div class="wcap_button wcap_button_radius">
						<button class="wcap_unsubscribe_button">{{popup_data.unsubscribe_text}}</button>
					</div>
				</div>

			</div>

			
                  </div>
				  
				  <div class="ss-foot">				  
                           <button type="button" @click="save_template( popup_data )"><?php esc_html_e( 'Save', 'woocommerce-ac' ); ?></button>
				  </div>
               </div> 
            </div>
         </div>
      </div>
			   
            </div>
	</template>


     