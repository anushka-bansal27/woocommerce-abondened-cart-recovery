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

require dirname( __FILE__ ) . '/email_templates.php';
require dirname( __FILE__ ) . '/sms_notifications.php';
require dirname( __FILE__ ) . '/facebook_messenger.php';
require dirname( __FILE__ ) . '/whatsapp_notifications.php';

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