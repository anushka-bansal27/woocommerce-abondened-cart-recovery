<?php
/**
 * Footer Template
 *
 * @package order-delivery-date/footer
 */
?>

<?php $display_links = apply_filters( 'wcap_show_LittleCodr_exclusive_tabs', false ); ?>

<!-- Footer -->
	<div class="ac-footer">
			<div class="container">
				<div class="row">
					<div class="col-md-12">
						<div class="footer-wrap">
							
							<div class="ft-text">
								<p>
									<?php if( $display_links ): ?>
										<a href="<?php echo esc_url( 'https://github.com/LittleCodr' ); ?>" target="_blank"><?php esc_html_e( 'Need Support?', 'woocommerce-ac' ); ?></a> <strong><?php esc_html_e( 'We’re always happy to help you.', 'woocommerce-ac' ); ?></strong></p>
									<?php else: ?>
										<strong><?php esc_html_e( 'We’re always happy to help you.', 'woocommerce-ac' ); ?><a style="font-size: 16px;" href="<?php echo esc_url( 'https://woocommerce.com/my-account/contact-support/' );// phpcs:ignore ?>" target="_blank"><?php echo __( 'Get Support', 'woocommerce-ac' );?> </a></strong></p>
									<?php endif; ?>

								</p>								
								<?php if( $display_links ): ?>
								<p>
								<?php printf(
    								/* translators: %s: Name of a city */
    								esc_html__( 'If this plugin helped you, %s please rate it %s %s', 'my-plugin' ),
    								'<a href="' . esc_url( 'https://github.com/LittleCodr' ) .'"  target="_blank" />',
									'</a>',
									'<span class="rating">★★★★★</span>'
									)
									?>
								<br/>
								<?php esc_html_e( 'Thanks for your support', 'woocommerce-ac' ); ?>
								</p>
								<?php endif; ?>
								
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- Footer End -->
</div>