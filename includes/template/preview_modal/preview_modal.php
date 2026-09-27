<div class="modal wcap-preview-modal" id="wcap-preview-modal">
	
	<!-- Modal content -->
	<div class="wcap-preview-contents">

		<div class="wcap-preview-header">

			<div class="wcap-preview-header-title modal-title">
				<h1 class="product_title entry-title">
					<?php esc_html_e( 'Select a Template to insert', 'woocommerce-ac' ); ?>
				</h1>
			</div>
			<div class="wcap-preview-header-close" data-dismiss="modal">
			</div>

			<div style="clear: both;"></div>
		</div>


		<div id="modal-body" class="modal-body">

			<div class="row" v-if="showThumbnail">
				<wcap-thumbnail
					v-for="item in templateList"
					v-on:checked="on_select"
					v-bind:templates="item"
					v-bind:keyt="item.id"
					v-bind:showThumbnail="showThumbnail"
					>
				</wcap-thumbnail>
			</div>

			<div class="row" v-if="!showThumbnail">
				<div class="col-sm-12 col-md-12 col-lg-12 text-center">
					<input 
						type="button" 
						name="viewBack"
						id="viewBack"
						v-on:click="wcap_view_back"
						value="<?php esc_html_e( 'Back', 'woocommerce-ac' ); ?>" 
						class="button-primary button"
					/>
				</div>
				<div class="col-sm-12 col-md-12 col-lg-12 text-center">
					<img v-bind:src="label">
				</div>
			</div>

		</div>

		<div class="modal-footer">
			
			<input 
				type="button" 
				name="wcap-insert-html" 
				id="wcap-insert-html"
				@click="wcap_insert_html()"
				value="<?php esc_html_e( 'Insert Template', 'woocommerce-ac' ); ?>" 
				class="button-primary button" 
			/>

			<input 
				type="button" 
				name="cancel_modal" 
				id="cancel_modal"
				value="<?php esc_html_e( 'Cancel', 'woocommerce-ac' ); ?>" 
				class="button-secondary button"
				data-dismiss="modal"
			/>
		</div>

	</div>
</div>
