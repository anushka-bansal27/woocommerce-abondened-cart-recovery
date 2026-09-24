<?php
/**
 * Include header for Admin pages
 *
 * @package ACP/Header
 * @since 8.23.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<?php

$menu_items = apply_filters(
	'wcap_admin_menu_items',
	array(
		'dashboard_advanced' => 'Dashboard',
		'listcart'           => 'Abandoned Orders',
		'cart_recovery'      => 'Templates',
		'emailsettings'      => 'Settings',
		'emailstats'         => 'Reminders Sent',
		'report'             => 'Product Report',
	)
);

?>
<div class="ac-header">
	<div class="container-fluid">
		<div class="row">
			<div class="col-md-12">
				<div class="header-wrap">
					<div class="branding">
						<a href=""><img src="<?php echo esc_url( WCAP_PLUGIN_URL ) . '/assets/images/ac-logo.svg'; // phpcs:ignore ?>" alt="Logo" /></a>
					</div>
					<nav class="navbar navbar-expand-lg navbar-light ac-navigation">
						<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
							<span class="navbar-toggler-icon">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
									<path fill="none" d="M0 0h24v24H0z"/>
									<path d="M3 4h18v2H3V4zm0 7h12v2H3v-2zm0 7h18v2H3v-2z" fill="rgba(255,255,255,1)"/>
								</svg>
							</span>
						</button>
						<div class="collapse navbar-collapse" id="navbarNav">
							<ul class="navbar-nav menu nav-menu">
							<?php foreach( $menu_items as $key => $label ) : ?>
								<?php if ( 'dashboard_advanced' === $key ): ?>
									<li class="nav-item <?php echo ( '' === $action ) ? 'active' : ''; ?>">
										<a class="nav-link" href="<?php echo 'admin.php?page=woocommerce_ac_page'; ?>"><?php echo esc_html( $label ); ?></a>
									</li>
								<?php else: ?>
								<li class="nav-item <?php echo ( $key === $action ) ? 'active' : ''; ?>">
									<a class="nav-link" href="<?php echo 'admin.php?page=woocommerce_ac_page&action=' . esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></a>
								</li>
								<?php endif; ?>

							<?php endforeach; ?>
							<?php
									do_action( 'wcap_add_settings_tab' );
									do_action( 'wcap_add_tabs' );
									if ( has_action( 'wcap_add_tabs' ) ) {
										if ( isset( $_GET['action'] ) && 'wcap_crm' === $_GET['action'] ) { // phpcs:ignore.
											settings_errors();
										}
									}
							?>
							</ul>
						</div>
					</nav>
					<div class="ac-version">                          
						<p class="ver-fig">
							Abandoned Cart Pro <br/><?php echo 'Version ' . WCAP_PLUGIN_VERSION; // phpcs:ignore ?></p>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<!-- Header End -->