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
<template id="connectors">
        <!-- Content Area -->
         <div class="container pl-page-wrap" id="connectors_cover" >
		 <div id="ac_events_loader" style="display:none;" >
					<div class="ac_events_loader_wrapper">
						<?php esc_html_e( 'Loading', 'woocommerce-ac' ); ?>...<img src="<?php echo esc_url( WCAP_PLUGIN_URL ) ;?>/assets/images/ajax-loader.gif">
					</div>
				</div>
            <div class="row">
                <div class="col-md-12">
                  <div class="col-left">
                     <h1><?php esc_html_e( 'Connectors', 'woocommerce-ac' ); ?></h1>
                     <p><?php

					$contact = apply_filters( 
						'wcap_connectors_page_description', 
						array(
							'desc' => __( 'Please note that the plugin will no longer send reminder emails to abandoned carts if integration is enabled with a third party CRM/email marketing tool. If you still wish to send reminder emails with the integration enabled, please get in touch with %s.', 'woocommerce-ac' ),
							'link' => '<a href="https://woocommerce.com/my-account/contact-support/" target="_blank">support at Woocommerce</a>',
						)
					);

					printf(
						esc_html( $contact['desc'] ),//phpcs:ignore
						$contact['link'] //phpcs:ignore
					);
					
		?></p>
                 </div>
				 <?php
					$wcap_all_integrators      = '';
					$wcap_active_integrators   = '';
					$wcap_inactive_integrators = '';
					$wcap_section_view         = isset( $_GET['wcap_section_view'] ) ? sanitize_text_field( wp_unslash( $_GET['wcap_section_view'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
					switch ( $wcap_section_view ) {
						case 'all':
						case '':
						default:
							$wcap_all_integrators = 'current';
							$type                 = '';
							break;
						case 'active':
							$wcap_active_integrators = 'current';
							$type                    = 'active';
							break;
						case 'inactive':
							$wcap_inactive_integrators = 'current';
							$type                      = 'inactive';
							break;
					}
					$inactive_count = Wcap_Connectors_Common::wcap_get_inactive_connectors_count();
					$active_count   = Wcap_Connectors_Common::wcap_get_active_connectors_count();
					$all_count      = $inactive_count + $active_count;

					$active_link   = $active_count === 0 ? 'no_link' : '';
					$inactive_link = $inactive_count === 0 ? 'no_link' : '';
				?>
                    <div class=" ">
						<ul class='subsubsub' id='wcap_integrators_list'>
							<li>
								<a href='javascript:void(0)' id='wcap_all' class='wcap_integrators_view <?php echo esc_attr( $wcap_all_integrators ); ?>'><?php esc_html_e( 'All', 'woocommerce-ac' ); ?> (<?php echo esc_html( $all_count ); ?>)</a> |
							</li>
							<li>
								<a data-wcap-count='<?php echo esc_html( $active_count ); ?>' id='wcap_active' class='wcap_integrators_view <?php echo esc_attr( $wcap_active_integrators . $active_link ); ?>'><?php esc_html_e( 'Active', 'woocommerce-ac' ); ?>(<span id='wcap_active_count'><?php echo esc_html( $active_count ); ?></span>)</a> |
							</li>
							<li>
								<a data-wcap-count='<?php echo esc_html( $inactive_count ); ?>' id='wcap_inactive' class='wcap_integrators_view <?php echo esc_attr( $wcap_inactive_integrators  . $inactive_link ); ?>'><?php esc_html_e( 'Inactive', 'woocommerce-ac' ); ?>(<span id='wcap_inactive_count'><?php echo esc_html( $inactive_count ); ?></span>)</a>
							</li>
						</ul>
						
						<div id="wcap_connectors_list">
						<?php
							Wcap_Integrations::wcap_display_connectors( $type );
						?>
						</div>
					</div>                   
                </div>
            </div>
        </div>
	</template>
