<?php
/**
 * Abandoned Cart Pro for WooCommerce
 *
 * Load FB Messenger Templates
 * 
 * @author   LittleCodr
 * @package  Abandoned-Cart-Pro-for-WooCommerce/FB-Messenger
 * @category Modules
 * @since    7.10.0
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;   //Exit if accessed directly.
}

if ( !class_exists( 'WCAP_FB_Templates' ) ) {

    /**
     * Class for FB Messenger Templates
     */
    class WCAP_FB_Templates {

        function __construct() {

            add_action( 'wp_ajax_wcap_fb_save_template', array( &$this, 'wcap_save_template_data' ) );
        }

        public function wcap_save_template_data(){

            if ( isset( $_POST['template_id'] ) ) {//phpcs:disable
                $temp_id = wcap_update_notifications(
                    sanitize_text_field( $_POST['template_id'] ), 
                    stripslashes( $_POST['body'] ), 
                    sanitize_text_field( $_POST['sent_time'] ), 
                    sanitize_text_field( $_POST['active'] ), 
                    '',
                    sanitize_text_field( $_POST['subject'] )
                );
            }else {
                $temp_id = wcap_insert_notifications( 
                    stripslashes( $_POST['body'] ), 
                    'fb', 
                    0, 
                    sanitize_text_field( $_POST['sent_time'] ), 
                    '', 
                    0, 
                    sanitize_text_field( $_POST['subject'] )
                );//phpcs:enable
            }
			$data = Wcap_Common::wcap_get_fb_templates( true );
			if ( $temp_id ) {
				$data['status'] = 'success';
			}
			wp_send_json( $data );
            die();
        }

        public static function wcap_fb_templates_list() {
            $is_valid = apply_filters( 'wcap_license_valid', true );
            
            if( $is_valid ):
            ?>
                <p><?php esc_html_e( 'Add Facebook Messenger templates to be sent at different intervals to maximize the possibility of recovering your abandoned carts.', 'woocommerce-ac' ); ?></p>

                <button id="add_fb_template" class="button-secondary" onclick="return false;" data-toggle="modal" data-target=".wcap-preview-modal">
                    <?php esc_html_e( 'Add New Template', 'woocommerce-ac' ); ?>
                </button>

                <div class="tablenav">
                    <?php
                        $fb_list = new WCAP_FB_Templates_List();
                        $fb_list->wcap_fb_templates_prepare_items();

                        wc_get_template( 
                            'wcap_fb_template_editor.php', 
                            '', 
                            'woocommerce-abandon-cart-pro/',
                            WCAP_PLUGIN_PATH . '/includes/fb-recovery/admin/' );
                    ?>

                    <div class="wrap">
                        <form id="wcap-sms-templates" method="get" >
                            <input type="hidden" name="page" value="woocommerce_ac_page" />
                            <input type="hidden" name="action" value="cart_recovery" />
                            <input type="hidden" name="section" value="sms" />
                            <?php $fb_list->display(); ?>
                        </form>
                    </div>

                </div>
            <?php
            endif;

            do_action( 'wcap_fb_templates_after' );
           
        }
    }
}

return new WCAP_FB_Templates();