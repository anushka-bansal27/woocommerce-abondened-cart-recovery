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
<!-- Dashboard Area -->
<div class="dashboard-area" id="wcap-dashboard-area">
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
	<div class="container">
		<div class="row">
			<div class="col-md-12">
					<div class="col-box mt-4">
						<select class="ib-small mr-2" name="duration_select" v-model="wcap_data_filter" @change="show_data_filter(wcap_data_filter)">
							<option value=""><?php echo esc_html_e( 'Select Date Range:', 'woocommerce-ac' ); ?></option>
							<option value="this_month"><?php echo esc_html_e( 'This Month', 'woocommerce-ac' ); ?></option>
							<option value="last_month"><?php echo esc_html_e( 'Last Month', 'woocommerce-ac' ); ?></option>
							<option value="this_quarter"><?php echo esc_html_e( 'This Quarter', 'woocommerce-ac' ); ?></option>
							<option value="last_quarter"><?php echo esc_html_e( 'Last Quarter', 'woocommerce-ac' ); ?></option>
							<option value="this_year"><?php echo esc_html_e( 'This Year', 'woocommerce-ac' ); ?></option>
							<option value="last_year"><?php echo esc_html_e( 'Last Year', 'woocommerce-ac' ); ?></option>
							<option value="other"><?php echo esc_html_e( 'Custom', 'woocommerce-ac' ); ?></option>
						</select>
	                    	<input type="date"  name="wcap_start_date"  id="wcap_start_date_div" class="ib-small" placeholder="Select Date" v-model="wcap_start_date"/>
	                    	<input type="date"  name="wcap_end_date" id="wcap_end_date_div"  class="ib-small" placeholder="Select Date" v-model="wcap_end_date" />
						<button class="trietary-btn" type="button" @click="change_data_filter($event)"><?php esc_html_e( 'Apply filters', 'woocommerce-ac' );?></button>
					</div>
			</div>
			<div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12 dashboard-box">
				<div class="dashboard-item">
					<div class="dashboard-ico">
						<span>
							<svg width="23" height="13" viewBox="0 0 23 13" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path fill-rule="evenodd" clip-rule="evenodd" d="M22.9587 2.33333C22.9587 3.47917 22.0212 4.41667 20.8753 4.41667C20.6878 4.41667 20.5107 4.39583 20.3441 4.34375L16.6357 8.04167C16.6878 8.20833 16.7087 8.39583 16.7087 8.58333C16.7087 9.72917 15.7712 10.6667 14.6253 10.6667C13.4795 10.6667 12.542 9.72917 12.542 8.58333C12.542 8.39583 12.5628 8.20833 12.6149 8.04167L9.95866 5.38542C9.79199 5.4375 9.60449 5.45833 9.41699 5.45833C9.22949 5.45833 9.04199 5.4375 8.87533 5.38542L4.13574 10.1354C4.18783 10.3021 4.20866 10.4792 4.20866 10.6667C4.20866 11.8125 3.27116 12.75 2.12533 12.75C0.979492 12.75 0.0419922 11.8125 0.0419922 10.6667C0.0419922 9.52083 0.979492 8.58333 2.12533 8.58333C2.31283 8.58333 2.48991 8.60417 2.65658 8.65625L7.40658 3.91667C7.35449 3.75 7.33366 3.5625 7.33366 3.375C7.33366 2.22917 8.27116 1.29167 9.41699 1.29167C10.5628 1.29167 11.5003 2.22917 11.5003 3.375C11.5003 3.5625 11.4795 3.75 11.4274 3.91667L14.0837 6.57292C14.2503 6.52083 14.4378 6.5 14.6253 6.5C14.8128 6.5 15.0003 6.52083 15.167 6.57292L18.8649 2.86458C18.8128 2.69792 18.792 2.52083 18.792 2.33333C18.792 1.1875 19.7295 0.25 20.8753 0.25C22.0212 0.25 22.9587 1.1875 22.9587 2.33333Z" fill="#41278D"/>
							</svg>
						</span>
					</div>
					<p class="mb-1"><?php esc_html_e( 'Recovered Amount', 'woocommerce-ac' ); ?></p>
					<h2><?php echo esc_html( get_woocommerce_currency_symbol() ); ?>{{ message.Recovered_amount }}</h2>
					<ul>
						<li><b>{{ message.Recovered_orders }}</b> <?php esc_html_e( ' Recovered Orders', 'woocommerce-ac' ); ?></li>
						<li><b>{{ message.ratio_of_recovered_orders }}%</b> <?php esc_html_e( ' of Abandoned Carts Recovered', 'woocommerce-ac' ); ?></li>
					</ul>
				</div>
			</div>
			<div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12 dashboard-box">
				<div class="dashboard-item">
					<div class="dashboard-ico">
						<span>
							<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path fill-rule="evenodd" clip-rule="evenodd" d="M6.41699 15.5C5.40866 15.5 4.59283 16.325 4.59283 17.3334C4.59283 18.3417 5.40866 19.1667 6.41699 19.1667C7.42533 19.1667 8.25033 18.3417 8.25033 17.3334C8.25033 16.325 7.42533 15.5 6.41699 15.5ZM0.916992 1.75004C0.916992 2.25421 1.32949 2.66671 1.83366 2.66671H2.75033L6.05033 9.62421L4.81283 11.8609C4.14366 13.0892 5.02366 14.5834 6.41699 14.5834H16.5003C17.0045 14.5834 17.417 14.1709 17.417 13.6667C17.417 13.1625 17.0045 12.75 16.5003 12.75H6.41699L7.42533 10.9167H14.2545C14.942 10.9167 15.547 10.5409 15.8587 9.97254L19.1403 4.02337C19.4795 3.41837 19.0395 2.66671 18.3428 2.66671H4.77616L4.16199 1.35587C4.01533 1.03504 3.68533 0.833374 3.33699 0.833374H1.83366C1.32949 0.833374 0.916992 1.24587 0.916992 1.75004ZM15.5837 15.5C14.5753 15.5 13.7595 16.325 13.7595 17.3334C13.7595 18.3417 14.5753 19.1667 15.5837 19.1667C16.592 19.1667 17.417 18.3417 17.417 17.3334C17.417 16.325 16.592 15.5 15.5837 15.5Z" fill="#41278D"/>
							</svg>
						</span>
					</div>
					<p class="mb-1"><?php esc_html_e( 'Abandoned Amount', 'woocommerce-ac' ); ?></p>
					<h2><?php echo esc_html( get_woocommerce_currency_symbol() ); ?>{{ message.amount_of_abandoned_orders }}</h2>
					<ul>
						<li><b> {{ message.Abandoned_orders }}</b> <?php esc_html_e( 'Abandoned Orders', 'woocommerce-ac' ); ?></li>
					</ul>
				</div>
			</div>
			<div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12 dashboard-box">
				<div class="dashboard-item">
					<div class="dashboard-ico">
						<span>
							<svg width="20" height="16" viewBox="0 0 20 16" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path fill-rule="evenodd" clip-rule="evenodd" d="M17.333 0.666626H2.66634C1.65801 0.666626 0.842174 1.49163 0.842174 2.49996L0.833008 13.5C0.833008 14.5083 1.65801 15.3333 2.66634 15.3333H17.333C18.3413 15.3333 19.1663 14.5083 19.1663 13.5V2.49996C19.1663 1.49163 18.3413 0.666626 17.333 0.666626ZM16.9663 4.56246L10.4855 8.61413C10.1922 8.79746 9.80717 8.79746 9.51384 8.61413L3.03301 4.56246C2.80384 4.41579 2.66634 4.16829 2.66634 3.90246C2.66634 3.28829 3.33551 2.92163 3.85801 3.24246L9.99967 7.08329L16.1413 3.24246C16.6638 2.92163 17.333 3.28829 17.333 3.90246C17.333 4.16829 17.1955 4.41579 16.9663 4.56246Z" fill="#41278D"/>
							</svg>
						</span>
					</div>
					<p class="mb-1"><?php esc_html_e( 'Number of Emails Sent', 'woocommerce-ac' ); ?></p>
					<h2>{{ message.Number_of_emails_sent }}</h2>
					<ul>
						<li><b>{{ message.Emails_opened }}</b> <?php esc_html_e( ' Emails Opened', 'woocommerce-ac' ); ?></li>
						<li><b>{{ message.Emails_clicked }}</b> <?php esc_html_e( ' Emails Clicked', 'woocommerce-ac' ); ?></li>
					</ul>
				</div>
			</div>
			<div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12 dashboard-box">
				<div class="dashboard-item">
					<div class="dashboard-ico">
						<span>
							<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path fill-rule="evenodd" clip-rule="evenodd" d="M15.4167 0.75H2.58333C1.575 0.75 0.75 1.575 0.75 2.58333V15.4167C0.75 16.425 1.575 17.25 2.58333 17.25H15.4167C16.425 17.25 17.25 16.425 17.25 15.4167V2.58333C17.25 1.575 16.425 0.75 15.4167 0.75ZM5.33333 13.5833C4.82917 13.5833 4.41667 13.1708 4.41667 12.6667V8.08333C4.41667 7.57917 4.82917 7.16667 5.33333 7.16667C5.8375 7.16667 6.25 7.57917 6.25 8.08333V12.6667C6.25 13.1708 5.8375 13.5833 5.33333 13.5833ZM9 13.5833C8.49583 13.5833 8.08333 13.1708 8.08333 12.6667V5.33333C8.08333 4.82917 8.49583 4.41667 9 4.41667C9.50417 4.41667 9.91667 4.82917 9.91667 5.33333V12.6667C9.91667 13.1708 9.50417 13.5833 9 13.5833ZM12.6667 13.5833C12.1625 13.5833 11.75 13.1708 11.75 12.6667V10.8333C11.75 10.3292 12.1625 9.91667 12.6667 9.91667C13.1708 9.91667 13.5833 10.3292 13.5833 10.8333V12.6667C13.5833 13.1708 13.1708 13.5833 12.6667 13.5833Z" fill="#41278D"/>
							</svg>
						</span>
					</div>
					<p class="mb-1"><?php esc_html_e( 'Email Capture Popup Displayed', 'woocommerce-ac' ); ?></p>
					<h2>{{ message.Email_capture_pop_displayed }}</h2>
					<ul>
						<li><b>{{ message.email_addresses_captured_from_add_to_cart_popups }}</b> <?php esc_html_e( ' email addresses captured from Add to Cart popups', 'woocommerce-ac' ); ?></li>
						<li><b>{{ message.email_addresses_captured_from_exit_intent_popups }}</b> <?php esc_html_e( ' email addresses captured from Exit Intent popups', 'woocommerce-ac' ); ?></li>
					</ul>
				</div>
			</div>
		</div>
	</div>
	


		<div class="abandone-chart">
			<div class="container">
			<div class="row">
				<div class="col-md-12">
					<div class="abandone-chart-box mt-4">
						<div class="abandone-header">
						<div class="row">
							<div class="col-xl-6 col-lg-8 col-md-8 col-sm-12 col-12">
								<div class="abandone-title d-flex align-items-center">
									<h2 class="mb-0 mr-4"><?php esc_html_e( 'Abandoned Cart', 'woocommerce-ac' ); ?></h2>
									<div class="abandone-lable">
									<span><span class="abandone-point" style="background-color: #58419C;"></span> <?php esc_html_e( 'Abandoned Revenue', 'woocommerce-ac' ); ?></span>
									<span><span class="abandone-point"></span><?php esc_html_e( 'Recovered Revenue', 'woocommerce-ac' ); ?> </span>
									</div>
								</div>
							</div>
							<div class="col-xl-6 col-lg-4 col-md-4 col-sm-12 col-12">
							</div>
						</div>
						</div>
						<div id="abandone-line-chart"></div>
					</div>
				</div>
			</div>
			</div>
		</div>

		<div class="abandone-chart">
				<div class="container">
					<div class="row">
						<div class="col-md-12">
							<div class="abandone-chart-box">
								<div class="abandone-header">
									<div class="row">
										<div class="col-xl-8 col-lg-8 col-md-12 col-sm-12 col-12">
											<div class="abandone-title d-flex align-items-center">
												<h2 class="mb-0 mr-4"><?php esc_html_e( 'Abandoned Cart', 'woocommerce-ac' ); ?></h2>
												<div class="abandone-lable">
													<span><span class="abandone-point" style="background-color: #58419C;"></span><?php esc_html_e( 'Abandoned Revenue', 'woocommerce-ac' ); ?></span>
													<span><span class="abandone-point"></span> <?php esc_html_e( 'Recovered Revenue', 'woocommerce-ac' ); ?></span>
												</div>
											</div>
										</div>
										<div class="col-xl-4 col-lg-4 col-md-12 col-sm-12 col-12">
										</div>
									</div>
								</div>
								<div id="abandone-chart"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		
		<div class="product-report">
			<div class="container">
			<div class="row">
				<div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
					<div class="bts-content mt-4">
						<h2><?php esc_html_e( 'Product Report', 'woocommerce-ac' ); ?></h2>
						<div class="tbl-mod-2 flx-100">
						<div class="tm2-inner-wrap table-responsive">
							<table class="table">
								<thead>
									<tr>
									<th @click="get_sorted_data( reports.product_name_order )"><?php esc_html_e( 'Product Name', 'woocommerce-ac' ); ?> <i class="fas fa-angle-up"></i></th>
									<th @click="get_sorted_data(  reports.abandoned_number_order )"><?php esc_html_e( 'Number of Times Abandoned', 'woocommerce-ac' ); ?> <i class="fas fa-angle-up"></i></th>
									<th @click="get_sorted_data( reports.recover_number_order  )"><?php esc_html_e( 'Number of Times Recovered', 'woocommerce-ac' ); ?> <i class="fas fa-angle-up"></i></th>
									</tr>
								</thead>
								<tbody>
									<tr class="cloned-row" v-for="( row, index ) in reports.product_reports" :key="index" v-if="index <= 4">
									<td>{{row.product_name}}</td>
									<td>{{row.abandoned_number}} ( <span v-html="row.product_total_price">{{row.product_total_price}}</span> )</td>
									<td>{{row.recover_number}} ( <span v-html="row.recover_total_price" >{{row.recover_total_price}} </span> )</td>
									</tr>
									
								</tbody>
							</table>
						</div>
						</div>
					</div>
				</div>
				<div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
					<div class="bts-content mt-4">
						<h2><?php esc_html_e( 'Abandoned Cart Details', 'woocommerce-ac' ); ?></h2>
						<div class="tbl-mod-2 flx-100">
						<div class="tm2-inner-wrap table-responsive">
							<table class="table">
								<thead>
									<tr>
									<th><?php esc_html_e( 'Customer Details', 'woocommerce-ac' ); ?></th>
									<th><?php esc_html_e( 'Cart Total', 'woocommerce-ac' ); ?></th>
									<th><?php esc_html_e( 'Abandoned Date / Time', 'woocommerce-ac' ); ?></th>
									<th><?php esc_html_e( 'Cart Status', 'woocommerce-ac' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<tr class="cloned-row" v-for="( row, index ) in settings.abandoned_carts" :key="index" v-if="index <= 4">
									<td><span v-html="row.customer_details">{{row.customer_details}}</td>
									<td v-html="row.order_total" >{{row.order_total}}</td>
									<td>{{row.date}}</td>
									<td>
										<div :class="'row_cart_status ' + row.status_original" role="alert" v-html="row.status_original">{{row.status_original}}</div>
									</td>
									</tr>
									
								</tbody>
							</table>
						</div>
						</div>
					</div>
				</div>
			</div>
			</div>
		</div>
</div>
<!-- Dashboard Area End -->
<?php // phpcs:disable ?>

	
	<!-- Optional JavaScript -- jQuery first, then Popper.js, then Bootstrap JS -->
	<script src="https://code.highcharts.com/highcharts.js"></script>
	<script src="https://code.highcharts.com/modules/exporting.js"></script>
	<script src="https://code.highcharts.com/modules/export-data.js"></script>
	<script src="https://code.highcharts.com/modules/accessibility.js"></script>
	<?php include_once( dirname( __FILE__ ) . '/' . '../ac-footer.php' ); ?>
