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
<template id="popup_templates">
<!-- Content Area -->
          <div class="container fas-page-wrap ordd-page-head pb-0" id="popup_template_cover" >

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
						{{message}}...<img src="<?php echo esc_url( WCAP_PLUGIN_URL ); //phpcs:disable ?>/assets/images/ajax-loader.gif">
					</div>
				</div>

        
               <div class="row">
                  <div class="col-md-12">
                     <p class="mb-3 ac-page-head"><?php esc_html_e( 'Add different Add to Cart and Exit Intent popup templates for different pages to maximize the possibility of collecting email addresses from users.', 'woocommerce-ac' ); ?></p>
                     <div class="popup-btn mb-3">
                        <button class="trietary-btn reverse" ><a id="add_popup_button" href="<?php echo esc_url( admin_url( 'admin.php?page=woocommerce_ac_page&action=emailsettings&section=wcap_atc_settings&wcap_section=wcap_atc_settings&mode=addnewtemplate' ) ); ?>" > <i class="fas fa-plus"></i> <?php esc_html_e( 'Add New Template', 'woocommerce-ac' ); ?></a></button>
                     </div>
					 
					 
                     <div class="wbc-accordion">
                        <div class="panel-group ordd-accordian" id="wbc-accordion">
                           <!-- First Panel -->
                           <div class="row">
                              <div class="col-md-12">
                                 <div class="tm1-row bdr-0 pt-0 delvry-sch-bottom mb-4">
                                    <div class="abulk-box pt-0 ">
                                       <select class="ib-small">
									   		<option selected><?php esc_html_e( 'Bulk Action', 'woocommerce-ac' ); ?></option>	
											   <option><?php esc_html_e( 'Delete', 'woocommerce-ac' ); ?></option>
                                       </select>
                                       <button class="trietary-btn reverse" type="button" @click="bulk_delete_popup_templates()" ><?php esc_html_e( 'Apply', 'woocommerce-ac' ); ?></button>
                                    </div>
                                    <div class="action-url">
                                    <p class="mb-3" style="margin-right: 30px;"><a href="https://github.com/LittleCodr" target="_blank" ><?php esc_html_e( 'Documentation', 'woocommerce-ac' ); ?></a></p>
                                    </div>
                                 </div>
                              </div>
                           </div>
                           <div class="tbl-mod-1">
                              <div class="custom-integrations">
                                 <div class="bts-content">
                                    <div class="tbl-mod-2 flx-100">
									<?php
										$columns = array(
										  'cb'                 => '<div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input custom-checkbox" id="customCheck1" @change="bulk_select_for_delete( select_all )" v-model="select_all" true-value="true" false-value="false" /><label class="custom-control-label" for="customCheck1"></label></div>',
										  'sr'                 => __( 'Sr', 'woocommerce-ac' ),
										  'template_name'      => __( 'Name Of Template', 'woocommerce-ac' ),
										  'type'               => __( 'Type', 'woocommerce-ac' ),
										  'rules'              => __( 'Rules', 'woocommerce-ac' ),
										  'email_captured'     => __( 'Email Captured', 'woocommerce-ac' ),
										  'viewed'             => __( 'Viewed', 'woocommerce-ac' ),
										  'no_thanks'          => __( 'No Thanks', 'woocommerce-ac' ),
										  'activate'           => __( 'Enable/Disable', 'woocommerce-ac' ),
									  );
							    ?>
                                       <div class="tm2-inner-wrap tbl-responsive">
                                          <table class="table">
                                             <thead>
                                                <tr>                                                   
												   <?php foreach( $columns as $column ) { ?>
                                                   <th><?php echo ( $column ) ; ?></th>  
												   <?php } ?>
                                                </tr>
                                             </thead>
                                             <tbody>
											 <tr class="cloned-row" v-for="( row, index ) in settings.popup_templates" :key="index">
											 
												<td>
													<div class="custom-control custom-checkbox">
														<input type="checkbox" class="custom-control-input"  :id="'setting_id_' + row.id" @change="toggle_delete_checkbox(row.id)" v-model="bulk_select_popup_ids[row.id]" true-value="true" false-value="false" >
                                                        <label class="custom-control-label" :for="'setting_id_' + row.id"></label>
													</div>
                                                   </td>
												   <td>{{ index + 1 }}</td>
												   <td>
												   {{row.template_name}}
                                                      <div class="edit-action">
                                                         <a class="green-text" :href="row.edit_link">Edit </a> 
														 <a :href="row.copy_link" >Duplicate</a>
														 <a class="red-text"  @click="delete_atc_template(row.id)">Delete</a>
														 <a  data-tip= "View" data-modal-type="ajax"  data-toggle="modal" data-target="#new-template" @click="set_popup_data( row )" ><?php esc_html_e( 'View' , 'woocommerce-ac' ); ?></a>
												   
                                                      </div>
                                                   </td>
                                                   <td>{{row.type}}</td>
                                                   <td>{{row.rules}}</td>
                                                   <td>{{row.email_captured}}</td>
                                                   <td>{{row.viewed}}</td>
                                                   <td>{{row.no_thanks}}</td>
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
												   <?php foreach( $columns as $column ) { ?>
                                                   <th><?php echo ( $column ) ; ?></th>  
												   <?php } ?>
                                                </tr>
                                             </thead>
                                          </table>
                                       </div>
                                    </div>
                                    <div class="tm1-row bdr-0 delvry-sch-bottom mt-2">
                                       <div class="abulk-box pt-0 ">
                                          <select class="ib-small">
										  	<option selected><?php esc_html_e( 'Bulk Action', 'woocommerce-ac' ); ?></option>
											<option><?php esc_html_e( 'Delete', 'woocommerce-ac' ); ?></option>
                                          </select>
                                          <button class="trietary-btn reverse" type="button" @click="bulk_delete_popup_templates()" ><?php esc_html_e( 'Apply', 'woocommerce-ac' ); ?></button>
                                       </div>
                                       <div class="action-url">
                                          <p>{{settings.total_items}} Items</p>
                                       </div>
                                    </div>
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
			   
			    <!-- Modal -->
      <div class="modal fade" id="new-template" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
         <div class="modal-dialog modal-dialog-centered  modal-lg" role="document">
            <div class="modal-content">
               <div class="modal-header">
                  <h5 class="modal-title" id="exampleModalLabel">Template #{{popup_data.id}}   |   {{popup_data.template_status}}</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                  </button>
               </div>
               <div class="modal-body">
                  <div class="custom-popup">
                     <h3 class="border-bottom pb-3"><?php esc_html_e(' Template Details', 'woocommerce-ac' ); ?></h3>
                     <div class="template-main">
                        <div class="template-item clearfix">
                           <b><?php esc_html_e('Template Name', 'woocommerce-ac' ); ?>:</b>
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


     