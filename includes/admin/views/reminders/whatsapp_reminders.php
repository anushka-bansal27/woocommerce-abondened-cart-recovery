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
<template id="whatsapp_reminders">
<!-- Content Area -->
          <div class="container fas-page-wrap ac-page-head pb-0" id="whatsapp_reminders_cover" >

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
               
			<div class="ac-content-area">
            <div class="container fas-page-wrap pb-0">
               <div class="row">
                  <div class="col-md-12">
                     <div class="tm1-row bdr-0 pt-0 delvry-sch-bottom mb-4">
                        <div class="abulk-box pt-0 ">
                           <p class="mb-0"><?php esc_html_e( 'The report below shows the WhatsApp messages sent, links clicked and other related stats.', 'woocommerce-ac' ); ?></p>
                        </div>
                     </div>
					 
					 <div class="tm1-row bdr-0 pt-0 delvry-sch-bottom mb-4">
                        <div class="action-url">
                           <span class="trietary-btn reverse"><?php esc_html_e( 'Messages Sent', 'woocommerce-ac' ); ?>: {{settings.total_items}}</span>
                           <span class="trietary-btn reverse"><?php esc_html_e( 'Links Clicked', 'woocommerce-ac' ); ?>: {{settings.link_click_count}}</span>
                        </div>
                     </div>
					 
					 
                      
					 <div class="tm1-row bdr-0 pt-0 delvry-sch-bottom mb-4">
                        <div class="abulk-box d-flex pt-0">
								   <select id="duration_select" name="duration_select"  v-model="duration_select" @change="load_dates( duration_select )">
									   <option v-for="( value, key ) in duration_range_select" :value="key" >{{value}}</option>
								   </select>

								   <input v-model="start_date" type="date" class="ib-small" placehoder="<?php echo esc_attr__( 'Select Date', 'woocommerce-ac' ); ?>" name="start_date"  >

								   <input v-model="end_date" type="date" class="ib-small" placehoder="<?php echo esc_attr__( 'Select Date', 'woocommerce-ac' ); ?>" name="end_date"  >
									
									<input v-model="hidden_start" type="hidden" name="hidden_start"/> 
									<input v-model="hidden_end" type="hidden" name="hidden_end"/> 
									
									<button class="trietary-btn reverse" type="button" @click="filter_orders( $event )"><?php esc_html_e( 'Apply Filter', 'woocommerce-ac' ) ; ?></button>
						</div>
                        <div class="col-box" id="pagination" >
								<div class="tablenav-pages">
									<span  id="items_div" class="mb-0">{{settings.total_items}} <?php esc_html_e( 'items', 'woocommerce-ac' ) ; ?></span>
									<span v-show="settings.total_pages > 1 ">
									
									<span @click="get_paginated_data( 1 , settings.previous_disabled )" :disabled="'disabled' == settings.previous_disabled" :class="'trietary-btn reverse ' + settings.previous_disabled" :data-paged="1" aria-hidden="true">«</span>
									<span @click="get_paginated_data( settings.previous_page, settings.previous_disabled )" :disabled="'disabled' == settings.previous_disabled" :class="'trietary-btn reverse ' + settings.previous_disabled" :data-paged="settings.previous_page"  aria-hidden="true">‹</span>
									
									<span class="paging-input"><label for="current-page-selector" class="screen-reader-text">Current Page</label><input v-model="settings.current_page" @change="get_paginated_data( settings.current_page )" class="current-page" id="current-page-selector" type="text" name="paged" size="1" aria-describedby="table-paging"><span class="tablenav-paging-text"> of <span class="total-pages">{{settings.total_pages}}</span></span></span>
                                    
									<span  @click="get_paginated_data( settings.next_page, settings.next_disabled )" :disabled="'disabled' == settings.next_disabled"  :class="'trietary-btn reverse ' + settings.next_disabled"  :data-paged="settings.next_page" ><span class="screen-reader-text">Next page</span><span aria-hidden="true">›</span></span>
                                    <span @click="get_paginated_data( settings.last_page, settings.next_disabled  )" :disabled="'disabled' == settings.next_disabled"  :class="'trietary-btn reverse ' + settings.next_disabled"  :data-paged="settings.last_page"><span class="screen-reader-text">Last page</span><span aria-hidden="true">»</span></span>
									
									
									</span>
								</div>
						   </div>
                     </div>
                     <div class="tbl-mod-1">
                        <div class="custom-integrations">
                           <div class="bts-content">
                              <div class="tbl-mod-2 flx-100">
                                 <div class="tm2-inner-wrap tbl-responsive">
                                    <table class="for-action" style="display: none;">
                                       <tbody>
                                          <tr>
                                             <td>
                                                <button type="button" class="btn btn-outline-primary blue-button btn-sm add-new">Save Setting', 'woocommerce-ac' ); ?></button>
                                                <a class="edit-delvry-sche edit" data-toggle="collapse" href="#" id="action-edit" role="button" aria-expanded="false" aria-controls="collapseExample" style="display: none;"> Edit', 'woocommerce-ac' ); ?></a>
                                                <!-- <a class="enable" title="Enable" style="display: none;">Enable</a> --> <a class="delete ml-2" title="Enable"><i class="fas fa-trash"></i></a>
                                             </td>
                                          </tr>
                                       </tbody>
                                    </table>
                                    <table class="table">
                                       <thead>
                                          <tr>
                                             <th><?php esc_html_e( 'User Phone Number', 'woocommerce-ac' ); ?></th>
                                             <th><?php esc_html_e( 'Message Sent Time', 'woocommerce-ac' ); ?></th>
                                             <th><?php esc_html_e( 'Date / Time Link Opened', 'woocommerce-ac' ); ?></th>
                                             <th><?php esc_html_e( 'Link Clicked', 'woocommerce-ac' ); ?></th>
                                             <th><?php esc_html_e( 'Sent WhatsApp Template', 'woocommerce-ac' ); ?></th>
                                          </tr>
                                       </thead>
                                       <tbody>
                                          <tr class="cloned-row" v-for="( row, index ) in settings.whatsapp_reminders" :key="index">
										
                                             <td>{{row.user_phone_number}}
                                             <div class="edit-action">
                                             <a  v-if="row.recover_order_link === '' " data-toggle="modal" data-target="#Fb-2" @click="set_pop_up_data( row )" >{{row.display_link}}</a>
                                             <a  v-else v-bind:href="row.recover_order_link">{{row.display_link}}</a>  
                                                
                                             </div>
                                             </td>
                                             <td>{{row.sent_time}}</td>
                                             <td>{{row.date_time_opened}}</td>
                                             <td>{{row.link_clicked}}</td>
                                             <td>{{row.template_name}}</td>
                                          </tr>
                                     </tbody>
                                       <thead>
                                          <tr>
                                             <th><?php esc_html_e( 'User Phone Number', 'woocommerce-ac' ); ?></th>
                                             <th><?php esc_html_e( 'Message Sent Time', 'woocommerce-ac' ); ?></th>
                                             <th><?php esc_html_e( 'Date / Time Link Opened', 'woocommerce-ac' ); ?></th>
                                             <th><?php esc_html_e( 'Link Clicked', 'woocommerce-ac' ); ?></th>
                                             <th><?php esc_html_e( 'Sent WhatsApp Template', 'woocommerce-ac' ); ?></th>
                                          </tr>
                                       </thead>
                                    </table>
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
		 
		 <div class="modal fade" id="Fb-2" tabindex="-1" role="dialog"  ref="Dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
         <div class="modal-dialog modal-dialog-centered  modal-lg" role="document">
            <div class="modal-content">
			<div >
               <div class="modal-header" style="background-color: #58419c;">
                  <h5 class="modal-title d-flex" id="exampleModalLabel" style="color:#fff" ><?php esc_html_e( 'Cart', 'woocommerce-ac' ); ?> # {{popup_id}} &nbsp;</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true" style="color: #fff;">&times;</span>
                  </button>
               </div>

               <div class="modal-body" v-html="popup_html" >
                 {{popup_html}}
               </div> 
			   
			   </div>

            </div>
         </div>
      </div>
			   
            </div>
	</template>


     