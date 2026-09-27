<?php
/**
 * Add to Cart popup modal template, it wll be displayed on shop, category, and products pages. 
 * @author   Tyche Softwares
 * @package Abandoned-Cart-Pro-for-WooCommerce/Frontend/Unsubscribe Landing Page
 * @since 6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$icon_url = get_site_icon_url();
?>
<meta name="viewport" content="width=device-width, initial-scale=1.0"> 
	<div style="position: absolute; top: -10%; bottom: 0; left: 0; right: 0; margin: auto; text-align: center; width: 80%; height: 40%;">
		<?php
		if ( '' !== $icon_url ) {
			?>
			<figure><img style="height: 32px; width: 60px;" alt="Site logo" src="<?php echo esc_url( $icon_url ); ?>"></figure><div style="display: inline-block;"></div>
			<?php
		}
		?>
		<div style="display: inline-block;">
			<p style="font-size: 28pt; font-family: Helvetica;"><?php echo esc_html( get_option( 'blogname' ) ); ?></p>
			<p>&nbsp;</p>
		</div>
		<p style="font-family: Helvetica; font-size: 18pt;"><strong><?php esc_html_e( 'Cart Reminder Unsubscribe Confirmation', 'woocommerce-ac' ); ?></strong></p>
		<p style="font-family: Helvetica; font-size: 14pt;"><?php echo wp_kses_post( $content ); ?></p>
		<p>&nbsp;</p>
	</div>
</meta>