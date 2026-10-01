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

require dirname( __FILE__ ) . '/general-settings.php';
require dirname( __FILE__ ) . '/popup-templates.php';
require dirname( __FILE__ ) . '/facebook-messenger.php';
require dirname( __FILE__ ) . '/sms.php';
require dirname( __FILE__ ) . '/whatsapp.php';
require dirname( __FILE__ ) . '/email-reports.php';
require dirname( __FILE__ ) . '/connectors.php';
require dirname( __FILE__ ) . '/import-export.php';

?>

<?php 

$additional_files = apply_filters( 'wcap_include_additional_files', array() );
if ( is_array( $additional_files ) ) {
	foreach ( $additional_files as $file ) {
		if ( file_exists( $file ) ) {
			require $file;
		}
	}
}

$display_tabs = apply_filters( 'wcap_show_LittleCodr_exclusive_tabs', false );
wp_localize_script( 'wcap_settings', 'wcap_config', array( 'show_tabs' => $display_tabs ) );
?>

<!-- Content Area -->
<div class="ordd-content-area" id="secondary-nav-wrap">
			<div class="container cw-full secondary-nav">
				<div class="row">
					<div class="col-md-12">
						<!-- Secondary Navigation -->
						<div class="secondary-nav-wrap">
							<ul>
								<li v-for="tab in settings_tabs"
									v-bind:key="tab.id"
									v-bind:class="{ 'current-menu-item': currentSettingsTab === tab.id }"
									v-on:click="currentSettingsTab = tab.id"> 
									<router-link :to="{name: tab.id }">{{ tab.text }} </router-link>
								</li>
							</ul>
						</div>
						<!-- Secondary Navigation - End -->
					</div>
				</div>
			</div>

		<router-view></router-view>
		</div>
		<!-- Content Area End -->
	
	<?php include_once( dirname( __FILE__ ) . '/' . '../ac-footer.php' ); ?>