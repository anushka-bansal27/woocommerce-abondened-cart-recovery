<?php
/**
 * Import Export Settings Template
 *
 * @package woocommerce-ac
 * @since x.x.x
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<template id="import_export">
	<!-- Content Area -->
	<div class="container pl-page-wrap" id="import_export_cover">
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
		<div class="row">
			<div class="col-md-12">
				<div class="col-left">
					<h2><?php esc_html_e( 'Import / Export Settings', 'woocommerce-ac' ); ?></h2>
					<h4>
						<strong><?php esc_html_e( 'Transfer your Abandoned Cart configuration between stores.', 'woocommerce-ac' ); ?></strong>
					</h4>
					<p>
						<?php esc_html_e( 'Export your recovery setup or import a previously saved configuration to quickly replicate settings across stores.', 'woocommerce-ac' ); ?>
					</p>
				</div>
				<div class="row wcap-import-export-section">
					<!-- Export Settings -->
					<div class="col-md-6">
						<div class="wcap-settings-card">
							<h3 class="wcap-card-title">
								<span class="dashicons dashicons-download wcap-title-icon"></span>
								<?php esc_html_e( 'Export Settings', 'woocommerce-ac' ); ?>
							</h3>
							<p class="wcap_description"><?php esc_html_e( 'Download your plugin configuration as a JSON file.', 'woocommerce-ac' ); ?></p>
							<div class="wcap-export-options">
								<label>
									<input type="radio" name="wcap_export_type" value="all"  v-model="export_type">
									<?php esc_html_e( 'All Settings (Default)', 'woocommerce-ac' ); ?>
								</label>
								<label>
									<input type="radio" name="wcap_export_type" value="specific" v-model="export_type">
									<?php esc_html_e( 'Select specific settings to export:', 'woocommerce-ac' ); ?>
								</label>
								<div class="wcap-export-checkboxes">
									<label><input type="checkbox" v-model="export_settings.general_settings"> <?php esc_html_e( 'General Settings', 'woocommerce-ac' ); ?></label>
									<label><input type="checkbox"  v-model="export_settings.gdpr_settings"> <?php esc_html_e( 'GDPR Consent Settings', 'woocommerce-ac' ); ?></label>
									<label><input type="checkbox"  v-model="export_settings.restriction_settings"> <?php esc_html_e( 'Restriction Settings', 'woocommerce-ac' ); ?></label>
									<label><input type="checkbox"  v-model="export_settings.recovery_emails_settings"> <?php esc_html_e( 'Recovery Emails Settings', 'woocommerce-ac' ); ?></label>
									<label><input type="checkbox"  v-model="export_settings.sms_settings"> <?php esc_html_e( 'SMS Settings', 'woocommerce-ac' ); ?></label>
									<label><input type="checkbox"  v-model="export_settings.whatsapp_settings"> <?php esc_html_e( 'WhatsApp Settings', 'woocommerce-ac' ); ?></label>

									<label><input type="checkbox"  v-model="export_settings.email_templates"> <?php esc_html_e( 'Email Templates', 'woocommerce-ac' ); ?></label>
									<label><input type="checkbox"  v-model="export_settings.popup_templates"> <?php esc_html_e( 'Popup Templates', 'woocommerce-ac' ); ?></label>
									<label><input type="checkbox"  v-model="export_settings.sms_templates"> <?php esc_html_e( 'SMS Templates', 'woocommerce-ac' ); ?></label>
									<label><input type="checkbox"  v-model="export_settings.whatsapp_templates"> <?php esc_html_e( 'WhatsApp Templates', 'woocommerce-ac' ); ?></label>
									<label><input type="checkbox"  v-model="export_settings.facebook_templates"> <?php esc_html_e( 'Facebook Templates', 'woocommerce-ac' ); ?></label>
									<label><input type="checkbox"  v-model="export_settings.smtp_connector"> <?php esc_html_e( 'SMTP / Email Connector', 'woocommerce-ac' ); ?></label>
								</div>

								<div class="wcap-warning-box">
									<?php echo wp_kses_post( __( '<b>Sensitive credentials like API keys and SMTP passwords</b> will NOT be exported. You will need to re-enter them after import.', 'woocommerce-ac' ) ); ?>
								</div>
								<p>
									<button class="trietary-btn" id="wcap_export_settings" @click="export_settings_json" :disabled="exporting">
										{{ exporting ? exporting_message : export_setting_title }}
									</button>
								</p>
							</div>
						</div>
					</div>

					<!-- Import Settings -->
					<div class="col-md-6">
						<div class="wcap-settings-card">
							<h3 class="wcap-card-title">
								<span class="dashicons dashicons-upload wcap-title-icon"></span>
								<?php esc_html_e( 'Import Settings', 'woocommerce-ac' ); ?>
							</h3>

							<p class="wcap_description"><?php esc_html_e( 'Upload a previously exported settings file.', 'woocommerce-ac' ); ?></p>

							<div class="wcap-import-section">
								<p>
									<span class="wcap-step-label"><?php esc_html_e( 'Step 1:', 'woocommerce-ac' ); ?></span>
									<?php esc_html_e( 'Upload JSON File', 'woocommerce-ac' ); ?>
								</p>
								<input type="file" id="wcap_import_file" accept=".json" @change="handle_file_upload">
								<p class="description">
									<?php esc_html_e( 'Allowed format: json', 'woocommerce-ac' ); ?><br>
									<?php esc_html_e( 'Max size: 5MB', 'woocommerce-ac' ); ?>
								</p>
								<hr>
								<p>
									<span class="wcap-step-label"><?php esc_html_e( 'Step 2:', 'woocommerce-ac' ); ?></span>
									<?php esc_html_e( 'Template Import Mode', 'woocommerce-ac' ); ?>
								</p>
								<label>
									<input type="radio" name="wcap_template_mode" value="replace" v-model="import_mode">
									<strong><?php esc_html_e( 'Replace Existing Templates', 'woocommerce-ac' ); ?></strong>
									<p class="description">
										<?php esc_html_e( 'Deletes existing templates and imports new ones.', 'woocommerce-ac' ); ?>
									</p>
								</label>
								<label>
									<input type="radio" name="wcap_template_mode" value="merge" v-model="import_mode">
									<strong><?php esc_html_e( 'Merge with Existing Templates', 'woocommerce-ac' ); ?></strong>
									<p class="description">
										<?php esc_html_e( 'Keeps existing templates and imports new templates without deleting current ones.', 'woocommerce-ac' ); ?>
									</p>
								</label>
								<p>
									<button class="trietary-btn" id="wcap-import-preview" @click="preview_import" :disabled="!import_file">
										<?php esc_html_e( 'Upload & Preview', 'woocommerce-ac' ); ?>
									</button>
								</p>
							</div>
						</div>
					</div>

				</div><!-- row -->

			</div>
		</div>
		<!-- Preview modal popup -->
		<div id="wcap-import-preview-modal" class="modal fade" tabindex="-1" role="dialog">
		<div class="modal-dialog modal-dialog-centered modal-lg" role="document">
			<div class="modal-content">
				<!-- Header -->
				<div class="modal-header wcap-modal-header">
					<h5 class="modal-title">
						<?php esc_html_e( 'Preview Import Settings', 'woocommerce-ac' ); ?>
					</h5>
					<button type="button" class="close" data-dismiss="modal">
						<span>&times;</span>
					</button>
				</div>
				<div class="wcap-preview-desc">
					<p>
						<?php esc_html_e( 'Review before applying changes.', 'woocommerce-ac' ); ?>
					</p>
				</div>
				<!-- Body -->
				<div class="modal-body">
					<h4 class="wcap-section-title">
						<?php esc_html_e( 'Categories Found in File:', 'woocommerce-ac' ); ?>
					</h4>
					<!-- Categories -->
					<div class="wcap-preview-list">
						<label v-for="section in preview_sections" :key="section.key">
							<input type="checkbox"
								v-model="selected_import_sections"
								:value="section.key"
								checked>
							{{ section.label }}
							<span class="wcap-count" v-if="section.count">
								({{ section.count }})
							</span>
						</label>
					</div>
					<!-- Version info -->
					<div class="wcap-version-info">
						<p>
							<strong>
							<?php esc_html_e( 'Exported Plugin Version:', 'woocommerce-ac' ); ?>
							</strong>
							{{ exported_version }}
						</p>
						<p>
							<strong>
							<?php esc_html_e( 'Current Installed Version:', 'woocommerce-ac' ); ?>
							</strong>
							{{ current_version }}
						</p>
						<p class="wcap-version-ok">
							<span class="dashicons dashicons-yes"></span>
							<?php esc_html_e( 'Compatible', 'woocommerce-ac' ); ?>
						</p>
					</div>

					<!-- Warning -->
					<div class="wcap-warning-box">
						<p>
							⚠ <?php esc_html_e( 'API keys and SMTP credentials are not included.', 'woocommerce-ac' ); ?>
						</p>
						<p>
							⚠ <?php esc_html_e( 'We recommend taking a backup before proceeding.', 'woocommerce-ac' ); ?>
						</p>
					</div>
				</div>
				<!-- Footer -->
				<div class="modal-footer">
					<button class="button button-primary" @click="confirm_import">
						<?php esc_html_e( 'Confirm Import', 'woocommerce-ac' ); ?>
					</button>
					<button class="button" data-dismiss="modal">
						<?php esc_html_e( 'Cancel', 'woocommerce-ac' ); ?>
					</button>
				</div>
			</div>
		</div>
	</div>	
</template>
