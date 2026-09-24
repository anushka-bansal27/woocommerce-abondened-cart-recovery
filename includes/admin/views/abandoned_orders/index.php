<?php
/**
 * Include header for Admin pages
 *
 * @package WooCommerce Abandon Cart Pro/Admin/Views
 * @since 8.23.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( is_hpos_enabled() ) { // HPOS usage is enabled.
	$orders_url = admin_url( 'admin.php?page=wc-orders' );
} else {
	$orders_url = admin_url( 'edit.php?post_type=shop_order' );
}
$google_sheet_id  = null !== get_option( 'wcap_google_sheet_id', null ) ? get_option( 'wcap_google_sheet_id' ) : '';
$google_sheet_url = '' !== $google_sheet_id ? 'https://docs.google.com/spreadsheets/d/' . $google_sheet_id : '';
?>
<!-- Content Area -->
<div class="ac-content-area" id="abandoned_orders">

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
						{{message}}...<img src="<?php echo esc_url( WCAP_PLUGIN_URL ); ?>/assets/images/ajax-loader.gif">
					</div>
				</div>

	<div class="container fas-page-wrap ac-page-head pb-0">
			<div class="row">
				<div class="col-md-12">
					<div class="booking-links d-flex justify-content-between align-items-center mb-4">
						<div class="booking-links-left">
						<span v-bind:class="section=='wcap_all_abandoned' ? 'active' : '' "  ><a data-section="wcap_all_abandoned" v-on:click.prevent.stop ="ajax_link( 'wcap_all_abandoned' )" href ="admin.php?page=woocommerce_ac_page&action=listcart&wcap_section=wcap_all_abandoned" ><?php esc_html_e( 'All', 'woocommerce-ac' ); ?> <span>( {{settings.total_all_count}} )</span></a> | </span>
						<span v-bind:class="section=='wcap_trash_abandoned' ? 'active' : '' "  v-if="settings.trash_count > 0 "> <a  data-section="wcap_trash_abandoned" v-on:click.prevent.stop="ajax_link( 'wcap_trash_abandoned' ); " href="admin.php?page=woocommerce_ac_page&action=listcart&wcap_section=wcap_trash_abandoned"><?php esc_html_e( 'Trash', 'woocommerce-ac' ); ?> <span>( {{settings.trash_count}} )</span></a> | </span>
							<span v-bind:class="section=='wcap_all_registered' ? 'active' : '' "  v-if="settings.registered_count > 0 "> <a  data-section="wcap_all_registered"  v-on:click.prevent.stop ="ajax_link( 'wcap_all_registered' )"  href="admin.php?page=woocommerce_ac_page&action=listcart&wcap_section=wcap_all_registered" ><?php esc_html_e( 'Registered Users', 'woocommerce-ac' ); ?> <span>( {{settings.registered_count}} )</span></a> | </span>
							<span  v-bind:class="section=='wcap_all_guest' ? 'active' : '' " v-if="settings.guest_user_count > 0 "> <a  data-section="wcap_all_guest"  v-on:click.prevent.stop ="ajax_link( 'wcap_all_guest' )"    href="admin.php?page=woocommerce_ac_page&action=listcart&wcap_section=wcap_all_guest"><?php esc_html_e( 'Guest Users', 'woocommerce-ac' ); ?> <span>( {{settings.guest_user_count}} )</span></a><span v-if="settings.visitor_user_count > 0"> | </span> </span>
							<span  v-bind:class="section=='wcap_all_visitor' ? 'active' : '' " v-if="settings.visitor_user_count > 0 "> <a  data-section="wcap_all_visitor"  v-on:click.prevent.stop ="ajax_link( 'wcap_all_visitor' )"  href="admin.php?page=woocommerce_ac_page&action=listcart&wcap_section=wcap_all_visitor"><?php esc_html_e( ' Carts without Customer Details ', 'woocommerce-ac' ); ?> <span>( {{settings.visitor_user_count}} )</span></a> | </span>
							<span  v-bind:class="section=='wcap_all_unsubscribe_carts' ? 'active' : '' " v-if="settings.unsubscribe_carts_count > 0 "> <a  data-section="wcap_all_unsubscribe_carts"  v-on:click.prevent.stop ="ajax_link( 'wcap_all_unsubscribe_carts' )" href="admin.php?page=woocommerce_ac_page&action=listcart&wcap_section=wcap_all_unsubscribe_carts"><?php esc_html_e( 'Unsubscribed Carts', 'woocommerce-ac' ); ?> <span>( {{settings.unsubscribe_carts_count}} )</span></a> </span>
						<input type="hidden" v-model="section" id="wcap_section" name="wcap_section" />
						</div>
					</div>
					<div class="tm1-row bdr-0 pt-0 delvry-sch-bottom ">
						<div class="abulk-box pt-0 ">
						<p class="mb-0"><?php esc_html_e( 'The list below shows all the carts that were abandoned and subsequently recovered.', 'woocommerce-ac' ); ?></p>
						</div>

					</div>
					<div class="tm1-row bdr-0 pt-0 delvry-sch-bottom">
					<div id="wcap-view-cart-data" style="display:none;"></div>
					<div id="wcap_print_data" style="display:none;"></div>

						<div class="wcap-view-abandoned-orders-msg" id="wcap-view-abandoned-orders-msg" :style="'visibility: ' + show_progess_bar ">
						<div id="wcap_myProgress">
						<div id="wcap_myBar" data-added="0"  :style="'width: ' + progress + '%'" >{{progress_text}}</div>
						</div>
						</div>
						<div class="action-url">
						<a href="<?php echo esc_url( $google_sheet_url ); ?>" target="_blank" class="mr-3" v-if=settings.google_sheets_enabled><i class="fas fa-file-alt"></i> <?php esc_html_e( 'Live Google Sheet', 'woocommerce-ac' ); ?></a>
						<a href="" class="mr-3" v-on:click.prevent.stop ="print_csv( 1, 'csv' )" ><i class="fas fa-file-alt"></i> <?php esc_html_e( 'CSV', 'woocommerce-ac' ); ?></a>
						<a href="" v-on:click.prevent.stop ="print_csv( 1, 'print' )"><i class="fas fa-print"></i> <?php esc_html_e( 'Print', 'woocommerce-ac' ); ?></a>
						</div>
					</div>

					<div class="tm1-row bdr-0 pt-0 delvry-sch-bottom mb-4">
						<div class="abulk-box d-flex pt-0">                           
						<div class="col-box">
							<div class="abulk-box d-flex pt-0">
								<select id="duration_select" name="duration_select"  v-model="duration_select" @change="load_dates( duration_select )">
									<option v-for="( value, key ) in duration_range_select" :value="key" >{{value}}</option>
								</select>

								<input v-model="start_date" type="date" class="ib-small" placehoder="<?php echo esc_attr__( 'Select Date', 'woocommerce-ac' ); ?>" name="start_date"  >

								<input v-model="end_date" type="date" class="ib-small" placehoder="<?php echo esc_attr__( 'Select Date', 'woocommerce-ac' ); ?>" name="end_date"  >

									<input v-model="hidden_start" type="hidden" name="hidden_start"/> 
									<input v-model="hidden_end" type="hidden" name="hidden_end"/> 

									<select   v-model="cart_status" id="cart_status" name="cart_status">
										<option v-for="( value, key ) in valid_statuses" :value="key" >{{value}}</option>
									</select>
									<select v-model="cart_source" id="cart_source" name="cart_source">
										<option v-for="( value, key ) in valid_sources" :value="key" >{{value}}</option>
									</select>
									<button class="trietary-btn reverse" type="button" @click="filter_orders( $event )"><?php esc_html_e( 'Apply Filter', 'woocommerce-ac' ); ?></button>
								</div>
						</div>
						</div>

					</div>
					<div class="tost-message text-center">
						<div class="alert alert-success mb-4" role="alert" v-html="settings.recovered_text">
						{{settings.recovered_text}}
						</div>
					</div>

					<div class="tm1-row bdr-0 pt-0 delvry-sch-bottom mb-4">
						<div class="abulk-box d-flex pt-0">
						<div class="col-box mr-5">
							<select class="ib-small" id="bulk_action" name="bulk_action" v-model="bulk_action">
								<option value=""><?php esc_html_e( 'Bulk Action', 'woocommerce-ac' ); ?></option>
								<option v-if ="section !== 'wcap_trash_abandoned'" v-for="( value, key ) in wcap_abandoned_bulk_actions" :value="key"  :data-action="value" :data-url="value" >{{value}}</option>
								<option v-if ="section == 'wcap_trash_abandoned' && key !== 'wcap_empty_trash' " v-for="( value, key ) in wcap_trash_bulk_actions" :value="key"  :data-action="value" :data-url="value" >{{value}}</option>
							</select>
							<button class="trietary-btn reverse" type="button" @click="bulk_action_apply( )" ><?php esc_html_e( 'Apply', 'woocommerce-ac' ); ?></button>
						</div>

						<div v-show="section == 'wcap_trash_abandoned'" class="tost-message text-center">
						<button class="trietary-btn reverse" type="button" @click="bulk_action_apply( 'wcap_empty_trash' )" ><?php esc_html_e( 'Empty Trash', 'woocommerce-ac' ); ?></button>

					</div>

						<div class="col-box" id="pagination" >
								<div class="tablenav-pages">
									<span  id="items_div" class="mb-0">{{settings.total_items}} <?php esc_html_e( 'items', 'woocommerce-ac' ); ?></span>
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
												<button type="button" class="btn btn-outline-primary blue-button btn-sm add-new">Save Setting', 'woocommerce-ac' ) ; ?></button>
												<a class="edit-delvry-sche edit" data-toggle="collapse" href="#" id="action-edit" role="button" aria-expanded="false" aria-controls="collapseExample" style="display: none;"> Edit', 'woocommerce-ac' ) ; ?></a>
												<!-- <a class="enable" title="Enable" style="display: none;">Enable</a> --> <a class="delete ml-2" title="Enable"><i class="fas fa-trash"></i></a>
											</td>
										</tr>
									</tbody>
									</table>
									<table class="table">
									<thead>
										<tr>
											<th width="30px">
												<div class="custom-control custom-checkbox">
													<input type="checkbox" class="custom-control-input" id="customCheck1"  @change="bulk_select_ids( select_all )" v-model="select_all"   true-value="true" false-value="false"  >
													<label class="custom-control-label" for="customCheck1"></label>
												</div>
											</th>
											<th><?php esc_html_e( 'Id', 'woocommerce-ac' ); ?></th>
											<th style="width: 200px;"><?php esc_html_e( 'Email Address', 'woocommerce-ac' ); ?></th>
											<th><?php esc_html_e( 'Customer Details', 'woocommerce-ac' ); ?></th>
											<th><?php esc_html_e( 'Cart Total', 'woocommerce-ac' ); ?></th>
											<th><?php esc_html_e( 'Abandoned Date/ Time', 'woocommerce-ac' ); ?></th>
											<th style="width: 100px;"><?php esc_html_e( 'Coupon Used/ Status', 'woocommerce-ac' ); ?></th>
											<th><?php esc_html_e( 'Captured By', 'woocommerce-ac' ); ?></th>
											<th><?php esc_html_e( 'Cart Status', 'woocommerce-ac' ); ?></th>
											<?php do_action( 'wcap_abandoned_orders_header_html' ); ?>
										</tr>
									</thead>
									<tbody>
										<tr class="cloned-row" v-for="( row, index ) in settings.abandoned_carts" :key="index">											 
											<td>
												<div class="custom-control custom-checkbox" data-toggle="modal" data-target="#cart-1">
												<input type="checkbox" class="custom-control-input"  :id="'order_id_' + row.id "  v-model="bulk_selected_ids[row.id]"  @click="toggle_bulk_select(row.id)" true-value="true" false-value="false"  >
												<label class="custom-control-label" :for="'order_id_' + row.id "></label>
												</div>
											</td>
											<td>{{row.id}}</td>
											<td style="width: 200px;">
																{{row.email}}
																<a
																oncontextmenu="return false;"
																class="wcap-js-edit_email"
																data-modal-type="ajax"
																:data-wcap-cart-id="row.id"
																:data-wcap-user-id="row.user_id"
																:href="row.wcap_js_edit_email" >
																<i class="fa fa-pencil-square-o"></i>
																</a>
												<div class="edit-action">
												<span v-show="section !== 'wcap_trash_abandoned'">
												<a  data-toggle="modal" data-target="#Fb-2" @click="set_pop_up_data( row )" ><?php esc_html_e( 'View', 'woocommerce-ac' ); ?></a>   
												<span v-if=" !( row.recovered_cart > 0) " ><a data-toggle="modal" data-target="#Fb-2" @click ="open_mark_as_recovered(  row.id )"><?php esc_html_e( 'Mark As Recovered', 'woocommerce-ac' ); ?></a><br/></span>
												<a v-if=" !( 1 == row.unsubscribe_link || row.recovered_cart > 0  || '' == row.manual_email_link ) " v-on:click.prevent.stop ="row_action( 'unsubscribe', row.id )" ><?php esc_html_e( 'Unsubscribe', 'woocommerce-ac' ); ?></a> 

												<a v-if="row.needs_manual_sync "  v-on:click.prevent.stop ="row_action(  'sync_manually', row.id )" :href="row.manual_sync_link"><?php esc_html_e( 'Sync Manually', 'woocommerce-ac' ); ?></a>
												<a v-if="!( 1 == row.unsubscribe_link || row.recovered_cart > 0 || '' == row.manual_email_link )" :href="row.manual_email_link"><?php esc_html_e( 'Send Custom Email', 'woocommerce-ac' ); ?></a>
													<a class="red-text"  v-on:click.prevent.stop ="trash_row( row.id )"><?php esc_html_e( 'Trash', 'woocommerce-ac' ); ?></a> 
												</span>
												<span v-show="section == 'wcap_trash_abandoned'">
												<a class="red-text"  v-on:click.prevent.stop ="restore_row( row.id )"><?php esc_html_e( 'Restore', 'woocommerce-ac' ); ?></a> 
												<a  data-toggle="modal" data-target="#Fb-2" v-on:click.prevent.stop ="delete_row( row.id )" ><?php esc_html_e( 'Delete Permanently', 'woocommerce-ac' ); ?></a>   
												</span>
												</div>
											</td>
											<td  v-html="row.customer_details" >{{row.customer_details}}
											</td>
											<td  v-html="row.order_total" >{{row.order_total}}
											</td>
											<td>{{row.date}}</td>
											<td>
												{{row.coupon_code_used}}<br/>
												<span :class="'wcap_row_coupon_status '+ row.coupon_code_status" v-html="row.coupon_code_status" >{{row.coupon_code_status}}</span>
											</td>
											<td>{{row.captured_by}}</td>
											<td>
												<div :class="'row_cart_status ' + row.status_original" v-html="row.status_original">{{row.status_original}}</div>
												<div :class="'row_connector_status ' + row.connector_status" v-html="row.connector_status">{{row.connector_status}}</div>
											</td>
											<?php do_action( 'wcap_abandoned_orders_row_html' ); ?>
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
											<th><?php esc_html_e( 'Id', 'woocommerce-ac' ); ?></th>
											<th><?php esc_html_e( 'Email', 'woocommerce-ac' ); ?></th>
											<th><?php esc_html_e( 'Customer Details', 'woocommerce-ac' ); ?></th>
											<th><?php esc_html_e( 'Cart Total', 'woocommerce-ac' ); ?></th>
											<th><?php esc_html_e( 'Abandoned Date / Time', 'woocommerce-ac' ); ?></th>
											<th><?php esc_html_e( 'Coupon Used Status', 'woocommerce-ac' ); ?></th>
											<th><?php esc_html_e( 'Captured By', 'woocommerce-ac' ); ?></th>
											<th><?php esc_html_e( 'Cart Status', 'woocommerce-ac' ); ?></th>
											<?php do_action( 'wcap_abandoned_orders_header_html' ); ?>
										</tr>
									</thead>
									</table>
								</div>
							</div>	
							<div class="tm1-row bdr-0 pt-0 delvry-sch-bottom mb-4 bottom_bulk_actions">
						<div class="abulk-box d-flex pt-0">
						<div class="col-box mr-5">
							<select class="ib-small" id="bulk_action" name="bulk_action" v-model="bulk_action">
								<option value=""><?php esc_html_e( 'Bulk Action', 'woocommerce-ac' ); ?></option>
								<option v-if ="section !== 'wcap_trash_abandoned'" v-for="( value, key ) in wcap_abandoned_bulk_actions" :value="key"  :data-action="value" :data-url="value" >{{value}}</option>
								<option v-if ="section == 'wcap_trash_abandoned'" v-for="( value, key ) in wcap_trash_bulk_actions" :value="key"  :data-action="value" :data-url="value" >{{value}}</option>
							</select>
							<button class="trietary-btn reverse" type="button" @click="bulk_action_apply( )" ><?php esc_html_e( 'Apply', 'woocommerce-ac' ); ?></button>
						</div>

						<div v-show="section == 'wcap_trash_abandoned'" class="tost-message text-center">
						<button class="trietary-btn reverse" type="button" @click="bulk_action_apply( 'wcap_empty_trash' )" ><?php esc_html_e( 'Empty Trash', 'woocommerce-ac' ); ?></button>

					</div>
						<div class="col-box" id="pagination" >
								<div class="tablenav-pages">
									<span   id="items_div" class="mb-0">{{settings.total_items}} <?php esc_html_e( 'items', 'woocommerce-ac' ); ?></span>
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
				<!-- Modal -->
	<div class="modal fade" id="Fb-2" tabindex="-1" role="dialog"  ref="Dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered  modal-lg" role="document">
			<div class="modal-content">
			<div v-show="'show_cart'=== popup_view">
			<div class="modal-header" style="background-color: #58419c;">
				<h5 class="modal-title d-flex" id="exampleModalLabel" style="color:#fff" ><?php esc_html_e( 'Cart', 'woocommerce-ac' ); ?> # {{popup_id}} &nbsp;<span v-html="recovered_order_text"> {{recovered_order_text}} </span></h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
				<span aria-hidden="true" style="color: #fff;">&times;</span>
				</button>
			</div>

			<div class="modal-body" v-html="popup_html" >
				{{popup_html}}
			</div> 
			<div class="wcap-modal__footer">
			<a v-show ="'' !== popup_manual_email_link " class="trietary-btn " :href="popup_manual_email_link"><?php esc_html_e( 'Send Custom Email', 'woocommerce-ac' ); ?></a>
			<span v-show ="'' !== popup_manual_email_link " class="trietary-btn reverse " v-on:click.prevent.stop ="row_action( 'unsubscribe', popup_id )" ><?php esc_html_e( 'Unsubscribe', 'woocommerce-ac' ); ?></span>
				<span class="trietary-btn reverse wcap-icon-close-footer wcap-js-close-modal" @click = "modal_close()" ><?php echo esc_html__( 'Close', 'woocommerce-ac' ); ?></span>							   
			</div>
			</div>
			<div id="mark_recover_popup" v-show="'mark_as_recovered' === popup_view" class="wbc-box ">
			<div class="modal-header" style="background-color: #58419c;">
				<h5 class="modal-title d-flex" id="exampleModalLabel" style="color:#fff" ><?php esc_html_e( 'Mark as Recovered', 'woocommerce-ac' ); ?> # {{popup_id}}</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
				<span aria-hidden="true" style="color: #fff;">&times;</span>
				</button>
			</div>
			<div class="modal-body  " class="wbc-content" >
				<p>
					<input v-model="mark_order_type" type="radio" id="wcap_create_order" class="wcap_order_type" name="mark_recovered" value="wcap_create"><label for="wcap_create_order" ><?php esc_html_e( 'Create a WooCommerce Order against which the cart will be marked as recovered.', 'woocommerce-ac' ); ?></label>
					<br>
					<input v-model="mark_order_type" type="radio" id="wcap_existing_order" class="wcap_order_type" name="mark_recovered" value="wcap_existing" checked=""><label for="wcap_existing_order" ><?php esc_html_e( 'Enter your WooCommerce Order ID against which you wish to link the cart and mark as Recovered.', 'woocommerce-ac' ); ?></label>
					<input type="hidden" v-model="wcap_hidden_order_id" name="wcap_hidden_order_id" id="wcap_hidden_order_id" />
					</p>
					<p v-show="'wcap_existing' === mark_order_type" > 
					<input v-model = "recover_search_id" id="wcap_link_wc_order" type="text" name="wcap_link_wc_order" class="wcap_link_wc_order ib-lg" placeholder="<?php esc_html_e( 'Search WooCommerce Orders', 'woocommerce-ac' ); ?>" />
					<span class="trietary-btn reverse " @click="search_mark_order()" >
						<?php esc_html_e( 'Search', 'woocommerce-ac' ); ?>
					</span>
					<br/>
					<span id="order_warn_msg" style=" display: none;"></span>
					<br>
					<?php esc_html_e( 'WooCommerce orders can be found', 'woocommerce-ac' ); ?> <a target="_blank" href="<?php echo esc_url( $orders_url ); ?>"><?php esc_html_e( 'here', 'woocommerce-ac' ); ?></a>.
				</p>
			</div>	
			<div class="wcap-modal__footer">
				<input type="hidden" id="wcap_hidden_order_id" class="wcap_hidden_order_id " value="">
				<div class="wcap_edit_footer" >
					<span v-show=" ( '' !== this.wcap_hidden_order_id && 'wcap_existing' === this.mark_order_type ) || 'wcap_create' === this.mark_order_type" class="trietary-btn reverse " data-wcap-cart-id="60" data-modal-type="ajax"  @click="recovered_order">
						<?php esc_html_e( 'Mark as Recovered', 'woocommerce-ac' ); ?>
					</span>
					<div style="margin-top: 1%;"><span class="wcap-error-msg" style="display: none;"><?php esc_html_e( 'Something went wrong. Please check whether the cart contains atleast 1 product and try again.', 'woocommerce-ac' ); ?></span></div>
				</div>
			</div>

			</div>
			</div>
		</div>
	</div>
</div>
<!-- Content Area End -->
<?php require_once WCAP_INCLUDE_PATH . '/admin/views/ac-footer.php'; ?>
