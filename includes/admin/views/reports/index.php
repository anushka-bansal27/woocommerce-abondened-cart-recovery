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
<div class="ac-content-area" id="product_reports">


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
            <div class="container fas-page-wrap ac-page-head pb-0">
               <div class="row">
                  <div class="col-md-12">
                     <div class="tm1-row bdr-0 pt-0 delvry-sch-bottom mb-4">
                        <div class="abulk-box pt-0 ">
                           <p class="mb-0"><?php esc_html_e( 'The below list shows the products which have been abandoned and also the number of times they have been recovered.', 'woocommerce-ac' ); ?></p>
                        </div>
						
                     </div>
					 
					 <div class="tm1-row bdr-0 pt-0 delvry-sch-bottom mb-4">
                       

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
                                            <th style="width:33%;" :class=" 'product_name' == settings.orderby ? 'sorted_column ': '' " @click="get_sorted_data( settings.product_name_order )" ><span ><?php esc_html_e( 'Product Name', 'woocommerce-ac' ); ?></span><span :class=" 'product_name' == settings.orderby ? 'ascdesc ' + settings.order : '' " >&nbsp;</span></th> 
                                            <th style="width:33%;"  :class=" 'abandoned_number' == settings.orderby  ? 'sorted_column ': '' " @click="get_sorted_data(  settings.abandoned_number_order )" ><span ><?php esc_html_e( 'Number of Times Abandoned', 'woocommerce-ac' ); ?></span><span :class=" 'abandoned_number' == settings.orderby ? 'ascdesc ' + settings.order : '' " >&nbsp;</span></th> 
                                            <th style="width:33%;" :class=" 'recover_number' == settings.orderby ? 'sorted_column ': ''  " @click="get_sorted_data( settings.recover_number_order  )" ><span ><?php esc_html_e( 'Number of Times Recovered', 'woocommerce-ac' ); ?></span><span :class=" 'recover_number' == settings.orderby ? 'ascdesc ' + settings.order : '' " >&nbsp;</span></th> 
                                          </tr>
                                       </thead>
                                       <tbody>
                                          <tr class="cloned-row" v-for="( row, index ) in settings.product_reports" :key="index">											 
											<td>{{row.product_name}}</td>
                                             <td >{{row.abandoned_number}} ( <span v-html="row.product_total_price">{{row.product_total_price}}</span> )</td>
                                             <td >{{row.recover_number}} ( <span v-html="row.recover_total_price" >{{row.recover_total_price}} </span> )</td>
                                            </tr>                                        
                                       </tbody>
                                       <thead>
                                          <tr>
                                             <th :class=" 'product_name' == settings.orderby ? 'sorted_column ': '' " @click="get_sorted_data( settings.product_name_order )" ><span ><?php esc_html_e( 'Product Name', 'woocommerce-ac' ); ?></span><span :class=" 'product_name' == settings.orderby ? 'ascdesc ' + settings.order : '' " >&nbsp;</span></th> 
                                            <th :class=" 'abandoned_number' == settings.orderby  ? 'sorted_column ': '' " @click="get_sorted_data(  settings.abandoned_number_order )" ><span ><?php esc_html_e( 'Number of Times Abandoned', 'woocommerce-ac' ); ?></span><span :class=" 'abandoned_number' == settings.orderby ? 'ascdesc ' + settings.order : '' " >&nbsp;</span></th> 
                                            <th :class=" 'recover_number' == settings.orderby ? 'sorted_column ': ''  " @click="get_sorted_data( settings.recover_number_order  )" ><span ><?php esc_html_e( 'Number of Times Recovered', 'woocommerce-ac' ); ?></span><span :class=" 'recover_number' == settings.orderby ? 'ascdesc ' + settings.order : '' " >&nbsp;</span></th> 
                                          </tr>
                                       </thead>
                                    </table>
                                 </div>
                              </div>
                              <div class="tm1-row bdr-0 delvry-sch-bottom p-0 mt-3">
                                
								 
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
                  </div>
               </div>
            </div>
         </div>
		
</div>
		<!-- Content Area End -->
	
	<?php include_once( dirname( __FILE__ ) . '/' . '../ac-footer.php' ); ?>