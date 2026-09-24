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
<template id="email_templates">
<!-- Content Area -->
          <div class="container fas-page-wrap ac-page-head pb-0" id="email_templates_cover" >

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
                           <!-- <p class="mb-4"><?php //esc_html_e( 'Add email templates at different intervals to maximize the possibility of recovering your abandoned carts', 'woocommerce-ac' ); ?></p> -->
						  <p v-html="connector_message" v-if = "connector_message">{{connector_message}}</p>
                           <div class="email-btn mb-4">
                              <button href="" class="trietary-btn reverse"><a id="add_email_button" href="<?php echo esc_url( admin_url( 'admin.php?page=woocommerce_ac_page&action=cart_recovery&section=emailtemplates&mode=addnewtemplate' ) ); ?>" > <i class="fas fa-plus"></i> <?php esc_html_e( 'Add New Templates', 'woocommerce-ac' ); ?></a></button>
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
                                 <button class="trietary-btn reverse" type="button" @click="bulk_action()" ><?php esc_html_e( 'Apply', 'woocommerce-ac' ); ?></button>
                              </div>
                           <div class="tm1-row bdr-0 delvry-sch-bottom p-0 mt-3" style="float: right;">
                                
                           <div class="col-box" id="pagination" >
                              <div class="tablenav-pages">
                                 <span   id="items_div" class="mb-0">{{settings.total_items}} <?php esc_html_e( 'items', 'woocommerce-ac' ) ; ?></span>
                                 <span v-show="settings.total_pages > 1 ">
                                    
                                 <span @click="get_paginated_data( 1 , settings.previous_disabled )" :disabled="'disabled' == settings.previous_disabled" :class="'trietary-btn reverse ' + settings.previous_disabled" :data-paged="1" aria-hidden="true">«</span>
                                 <span @click="get_paginated_data( settings.previous_page, settings.previous_disabled )" :disabled="'disabled' == settings.previous_disabled" :class="'trietary-btn reverse ' + settings.previous_disabled" :data-paged="settings.previous_page"  aria-hidden="true">‹</span>
                                    
                                 <span class="paging-input"><label for="current-page-selector" class="screen-reader-text">Current Page</label><input v-model="settings.current_page" @change="get_paginated_data( settings.current_page )" class="current-page" id="current-page-selector" type="text" name="paged" size="1" aria-describedby="table-paging"><span class="tablenav-paging-text"> of <span class="total-pages">{{settings.total_pages}}</span></span></span>
                                             
                                 <span   @click="get_paginated_data( settings.next_page, settings.next_disabled )" :disabled="'disabled' == settings.next_disabled"  :class="'trietary-btn reverse ' + settings.next_disabled"  :data-paged="settings.next_page" ><span class="screen-reader-text">Next page</span><span aria-hidden="true">›</span></span>
                                             <span @click="get_paginated_data( settings.last_page, settings.next_disabled  )" :disabled="'disabled' == settings.next_disabled"  :class="'trietary-btn reverse ' + settings.next_disabled"  :data-paged="settings.last_page"><span class="screen-reader-text">Last page</span><span aria-hidden="true">»</span></span>
                                    
                                    
                                 </span>
                              </div>
                           </div>
                        </div>
                           </div>
                        </div>
                     </div>
                     <div class="obp-content">
                        <div class="wbc-box">
                           <div class="tbl-mod-1">
                              <div class="custom-integrations">
                                 <div class="bts-content">
                                    <div class="tbl-mod-2 flx-100 border-0">
                                       <div class="tm2-inner-wrap tbl-responsive">
                                          <table class="table">
                                             <thead>
                                                <tr>
                                                   <th>
                                                      <div class="custom-control custom-checkbox">
                                                         <input type="checkbox" class="custom-control-input" id="customCheck1"  @change="bulk_select_ids( select_all )" v-model="select_all"   true-value="true" false-value="false"  >
                                                         <label class="custom-control-label" for="customCheck1"></label>
                                                      </div>
                                                   </th>
                                                   <th><?php esc_html_e( 'Sr', 'woocommerce-ac' ) ; ?></th>
                                                   <th width="250px"><?php esc_html_e( 'Name Of Template', 'woocommerce-ac' ) ; ?></th>
                                                   <th><?php esc_html_e( 'Sent After Set Time', 'woocommerce-ac' ) ; ?></th>
                                                   <th><?php esc_html_e( 'Send To Segment(s)', 'woocommerce-ac' ) ; ?></th>
                                                   <th><?php esc_html_e( 'No.of Emails Sent', 'woocommerce-ac' ) ; ?></th>
                                                   <th><?php esc_html_e( 'Open Rate(%)', 'woocommerce-ac' ) ; ?></th>
                                                   <th><?php esc_html_e( 'Link Click Rate(%)', 'woocommerce-ac' ) ; ?></th>
                                                   <th><?php esc_html_e( 'Coupon Redemption Rate(%)', 'woocommerce-ac' ) ; ?></th>
                                                   <th><?php esc_html_e( 'Conversion Rate(%)', 'woocommerce-ac' ) ; ?></th>
                                                   <th><?php esc_html_e( 'Start Sending', 'woocommerce-ac' ) ; ?></th>
                                                </tr>
                                             </thead>
                                             <tbody>
											 
                                                 <tr class="cloned-row" v-for="( row, index ) in settings.email_templates" :key="index">
                                                   <td>
                                                     <div class="custom-control custom-checkbox">
                                                         <input type="checkbox"  class="custom-control-input" :id="'setting_id_' + row.id "  v-model="bulk_selected_ids[row.id]"  @click="toggle_bulk_select(row.id)" true-value="true" false-value="false" >
                                                         <label class="custom-control-label" :for="'setting_id_' + row.id "></label>
                                                      </div>
                                                   </td>
                                                   <td>{{ row.sr }}</td>
                                                   <td>{{row.template_name}}
                                                      <div class="edit-action">
                                                         <a class="green-text" :href="row.edit_link"><?php esc_html_e('Edit', 'woocommerce-ac' ); ?></a> <a :href="row.copy_link" ><?php esc_html_e('Duplicate', 'woocommerce-ac' ); ?></a> <a class="red-text" @click="delete_template( row.id )"  ><?php esc_html_e('Delete', 'woocommerce-ac' ); ?></a>
                                                      </div>
                                                   </td>                                                   
                                                   <td>{{row.sent_time}}</td>
                                                   <td>{{row.template_filter}}</td>
                                                   <td>{{row.email_sent}}</td>
                                                   <td>{{row.open_rate}}</td>
                                                   <td>{{row.link_rate}}</td>
                                                   <td>{{row.coupon_rate}}</td>
                                                   <td>{{row.percentage_recovery}}</td>												   
                                                   <td>
                                                      <label class="el-switch el-switch-green">
                                                      <input type="checkbox" name="cb_opt_nm_23" value="on" unchecked-value="off" v-model = 'row.is_active' true-value="1" false-value="0"  @change="toggle_template_status( row.is_active, row.id, 'emailtemplates' )">
                                                      <span class="el-switch-style"></span>
                                                      </label>
                                                   </td>
                                                </tr>
                                                
                                                
                                               
                                             </tbody>
                                             <thead>
                                                <tr>
                                                   <th>
                                                      <div class="custom-control custom-checkbox">
                                                         <input type="checkbox" class="custom-control-input" id="customCheck8"  @change="bulk_select_ids( select_all )" v-model="select_all"   true-value="true" false-value="false" >
                                                         <label class="custom-control-label" for="customCheck8"></label>
                                                      </div>
                                                   </th>
                                                   <th><?php esc_html_e( 'Sr', 'woocommerce-ac' ) ; ?></th>
                                                   <th><?php esc_html_e( 'Name Of Template', 'woocommerce-ac' ) ; ?></th>
                                                   <th><?php esc_html_e( 'Sent After Set Time', 'woocommerce-ac' ) ; ?></th>
                                                   <th><?php esc_html_e( 'Send To Segment(s)', 'woocommerce-ac' ) ; ?></th>
                                                   <th><?php esc_html_e( 'No.of Emails Sent', 'woocommerce-ac' ) ; ?></th>
                                                   <th><?php esc_html_e( 'Open Rate(%)', 'woocommerce-ac' ) ; ?></th>
                                                   <th><?php esc_html_e( 'Link Click Rate(%)', 'woocommerce-ac' ) ; ?></th>
                                                   <th><?php esc_html_e( 'Coupon Redemption Rate(%)', 'woocommerce-ac' ) ; ?></th>
                                                   <th><?php esc_html_e( 'Conversion Rate(%)', 'woocommerce-ac' ) ; ?></th>
                                                   <th><?php esc_html_e( 'Start Sending', 'woocommerce-ac' ) ; ?></th>
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

               <div class="tm1-row bdr-0 delvry-sch-bottom p-0 mt-3" style="float: right;">
                                
                  <div class="col-box" id="pagination" >
                     <div class="tablenav-pages">
                        <span   id="items_div" class="mb-0">{{settings.total_items}} <?php esc_html_e( 'items', 'woocommerce-ac' ) ; ?></span>
                        <span v-show="settings.total_pages > 1 ">
                           
                        <span @click="get_paginated_data( 1 , settings.previous_disabled )" :disabled="'disabled' == settings.previous_disabled" :class="'trietary-btn reverse ' + settings.previous_disabled" :data-paged="1" aria-hidden="true">«</span>
                        <span @click="get_paginated_data( settings.previous_page, settings.previous_disabled )" :disabled="'disabled' == settings.previous_disabled" :class="'trietary-btn reverse ' + settings.previous_disabled" :data-paged="settings.previous_page"  aria-hidden="true">‹</span>
                           
                        <span class="paging-input"><label for="current-page-selector" class="screen-reader-text">Current Page</label><input v-model="settings.current_page" @change="get_paginated_data( settings.current_page )" class="current-page" id="current-page-selector" type="text" name="paged" size="1" aria-describedby="table-paging"><span class="tablenav-paging-text"> of <span class="total-pages">{{settings.total_pages}}</span></span></span>
                                    
                        <span   @click="get_paginated_data( settings.next_page, settings.next_disabled )" :disabled="'disabled' == settings.next_disabled"  :class="'trietary-btn reverse ' + settings.next_disabled"  :data-paged="settings.next_page" ><span class="screen-reader-text">Next page</span><span aria-hidden="true">›</span></span>
                                    <span @click="get_paginated_data( settings.last_page, settings.next_disabled  )" :disabled="'disabled' == settings.next_disabled"  :class="'trietary-btn reverse ' + settings.next_disabled"  :data-paged="settings.last_page"><span class="screen-reader-text">Last page</span><span aria-hidden="true">»</span></span>
                           
                           
                        </span>
                     </div>
                  </div>
               </div>


                     <div class="tm1-row align-items-center bdr-0 pt-0 delvry-sch-bottom mb-4">
                        <div class="abulk-box pt-0 ">
                           <select class="ib-small" v-model="wcap_action" >
                                    <option value="" ><?php esc_html_e( 'Bulk Action', 'woocommerce-ac' ); ?></option>                                   
                                    <option value="wcap_delete_template" ><?php esc_html_e( 'Delete', 'woocommerce-ac' ); ?></option>                                   
                           </select>
                           <button class="trietary-btn reverse" type="button" @click="bulk_action()" ><?php esc_html_e( 'Apply', 'woocommerce-ac' ); ?></button>
                        </div>
                        <div class="action-url" style="display:none;">
                           <p class="m-0">{{settings.total_items}} <?php esc_html_e( 'items', 'woocommerce-ac' ); ?></p>
                        </div>
                     </div>
                     <p class="mb-4"><b><?php esc_html_e( 'Open Rate', 'woocommerce-ac' ); ?> : </b><?php esc_html_e( 'Number of emails opened versus number of emails sent.', 'woocommerce-ac' ); ?></p>
                     <p class="mb-4"><b><?php esc_html_e( 'Link Click Rate', 'woocommerce-ac' ); ?> : </b> <?php esc_html_e( 'Number of links clicked versus number of emails sent. In cases where coupons are present for the template, the coupon application rate will be same as Link Click Rate, since coupons are auto applied when a link is clicked.', 'woocommerce-ac' ); ?></p>
                     <p class="mb-4"><b><?php esc_html_e( 'Coupon Redemption Rate', 'woocommerce-ac' ); ?> : </b> <?php esc_html_e( 'Number of Coupons applied (i.e. number of links clicked) versus number of emails opened.', 'woocommerce-ac' ); ?></p>
                     <p class="mb-0"><b><?php esc_html_e( 'Conversion Rate', 'woocommerce-ac' ); ?> : </b> <?php esc_html_e( 'Number of carts recovered versus number of emails sent.', 'woocommerce-ac' ); ?></p>
                  </div>
               </div>
            </div>
			   
			    <!-- Modal -->
      <div class="modal fade" id="new-template" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
         <div class="modal-dialog modal-dialog-centered  modal-lg" role="document">
            <div class="modal-content">
               <div class="modal-header">
                  <h5 class="modal-title" id="exampleModalLabel">Template #{{popup_data.id}}   |   Active</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                  </button>
               </div>
               <div class="modal-body">
                  <div class="custom-popup">
                     <h3 class="border-bottom pb-3"><?php esc_html_e(' Template Details', 'woocommerce-ac' ); ?></h3>
                     <div class="template-main">
                        <div class="template-item clearfix">
                           <b><?php esc_html_e(' Template Name', 'woocommerce-ac' ); ?>:</b>
                           <span>{{popup_data.template_name}}</span>
                        </div>
                        <div class="template-item clearfix">
                           <b><?php esc_html_e(' Template Type', 'woocommerce-ac' ); ?>:</b>
                           <span>{{popup_data.type}}</span>
                        </div>
                        <div class="template-item clearfix">
                           <b><?php esc_html_e(' Number of Views', 'woocommerce-ac' ); ?>:</b>
                           <span>{{popup_data.viewed}}</span>
                        </div>
                        <div class="template-item clearfix">
                           <b><?php esc_html_e(' Number of Emails captured', 'woocommerce-ac' ); ?>:</b>
                           <span>{{popup_data.email_captured}}</span>
                        </div>
                        <div class="template-item clearfix">
                           <b><?php esc_html_e(' Number of times No Thanks was clicked', 'woocommerce-ac' ); ?>:</b>
                           <span>{{popup_data.no_thanks}}</span>
                        </div>
                        <div class="template-item clearfix">
                           <b><?php esc_html_e(' Number of times coupon was applied', 'woocommerce-ac' ); ?>:</b>
                           <span>{{popup_data.template_coupons_cnt}}</span>
                        </div>
                        <div class="template-item clearfix">
                           <b><?php esc_html_e(' Number of orders placed', 'woocommerce-ac' ); ?>:</b>
                           <span>{{popup_data.orders_count}}</span>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
			   
            </div>
	</template>
