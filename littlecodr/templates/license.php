<?php
/**
 * Include header for Admin pages
 *
 * @package ACP/License
 * @since 8.23.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( class_exists( 'WCAP_License' ) && method_exists( 'WCAP_License', 'wcap_check_if_license_renewed' ) ) {
	$license_data = WCAP_License::wcap_check_if_license_renewed();
}

$license_error = get_option( 'wcap_edd_license_error', '' );
switch ( $license_error ) {
	case 'missing':
		$license_error = __( ' - ( Incorrect License Key )', 'woocommerce-ac' );
		break;
	case 'expired':
		$license_error = __( ' - ( License is expired )', 'woocommerce-ac' );
		break;
	default:
		break;
}

?>
<template id="license">
	<!-- Content Area -->
	<div class="container pl-page-wrap" id="license_cover" >
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
					{{message}}...<img src="<?php echo WCAP_PLUGIN_URL ; // phpcs:ignore ?>/assets/images/ajax-loader.gif">
				</div>
			</div>
		<div class="row">
			<div class="col-md-12">
				<div class="col-left mb-4">
					<h1><?php esc_html_e( 'Activate License', 'woocommerce-ac' ); ?></h1>
				</div>
				<div class="wbc-box">
					<div class="wbc-head">
						<h2><?php esc_html_e( 'Plugin License Options', 'woocommerce-ac' ); ?></h2>
					</div>
					<div class="wbc-content">
						<div class="tbl-mod-1">
							<div class="tm1-row flx-center">
								<div class="col-left">
									<label><?php esc_html_e( 'Status', 'woocommerce-ac' ); ?>:</label>
								</div>
								<div class="col-right" v-show="settings.edd_sample_license_status_ac_woo =='valid'"><span class="mode-active"><?php esc_html_e( 'Active', 'woocommerce-ac' ); ?></span></div>
								<div class="col-right" v-show="settings.edd_sample_license_status_ac_woo !='valid'"><span class="mode-deactive"><?php esc_html_e( 'Inactive', 'woocommerce-ac' ); echo $license_error ; ?> </span></div>
							</div>
							<div class="tm1-row flx-fs-space">
								<div class="col-left">
									<label><?php esc_html_e( 'License Key', 'woocommerce-ac' ); ?>:</label>
								</div>
								<div class="col-right">
									<div class="row-box-1">
										<div class="rb1-right">
											<div class="rb1-row flx-center">
												<div class="rb-col">
													<input class="ib-lg" type="text" id="edd_sample_license_key_ac_woo" name="edd_sample_license_key_ac_woo"  v-model="settings.edd_sample_license_key_ac_woo" >
												</div>
												<div class="rb-col">
													<input @click="save_license_settings" v-show="settings.edd_sample_license_status_ac_woo =='valid'" class="secondary-btn btn-red" type="submit" value="<?php esc_html_e( 'Deactivate', 'woocommerce-ac' ); ?>">
													<input @click="save_license_settings"  v-show="settings.edd_sample_license_status_ac_woo !='valid'" class="btn-small" type="submit" value="<?php esc_html_e( 'Save & Activate', 'woocommerce-ac' ); ?>">
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
	</div>
</template>
