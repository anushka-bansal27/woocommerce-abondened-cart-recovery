<?php
/**
 * Abandoned Cart Pro for WooCommerce
 *
 * Load FB Messenger frontend actions
 * 
 * @author   LittleCodr
 * @package  Abandoned-Cart-Pro-for-WooCommerce/FB-Messenger
 * @category Modules
 * @since    7.10.0
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;   //Exit if accessed directly.
}

if ( !class_exists( 'WCAP_FB_Frontend_Actions' ) ) {

    /**
     * 
     */
    class WCAP_FB_Frontend_Actions {

        private $user_ref;
        
        function __construct() {

            // set Individual user reference
            //$this->user_ref = mt_rand() . microtime();
            $this->user_ref = 'wcap_' . microtime();
            $this->user_ref = str_replace(' ', '_', $this->user_ref );

            add_action( 'wp_footer', array( &$this, 'wcap_fb_load_scripts' ) );
            add_action( 'woocommerce_after_add_to_cart_button', array( &$this, 'wcap_display_checkbox' ) );
            //add_action( 'woocommerce_after_add_to_cart_form', array( &$this, 'wcap_display_checkbox' ) );
            //add_action( 'wcap_atc_after_email_field', array( &$this, 'wcap_display_checkbox' ) );
            add_action( 'wp_head', array( &$this, 'wcap_fb_load_checkbox' ) );

            add_filter( 'wcap_shortlinks_filter', array( &$this, 'wcap_add_fb_list' ), 10, 1 );
        }

        public function wcap_fb_load_scripts(){

            /**
             * @todo add minified files and add $suffix variable
             */
            wp_register_script( 
                'wcap_fb_script', 
                esc_url( WCAP_PLUGIN_URL ) . '/includes/fb-recovery/assets/js/wcap_fb_scripts.js',
                '',
                '',
                true );
            wp_enqueue_script( 'wcap_fb_script' );
            wp_localize_script( 
                'wcap_fb_script', 
                'wcap_fb_params', 
                array(
                    'locale' => get_locale(),
                    'aid'    => WCAP_FB_APP_ID,
                    'pid'    => WCAP_FB_PAGE_ID,
                    'consent'=> __( get_option( 'wcap_fb_consent_text' ), 'woocommerce-ac' ),//phpcs:ignore
                )
            );
        }

        public function wcap_fb_load_checkbox() {
            $atc_active = wcap_get_popup_active_status( 'atc' );
            if( !is_product() && get_option( 'wcap_enable_fb_reminders_popup' ) == 'on' && $atc_active ){
                echo '<div style="display: none;">';
                $this->wcap_display_checkbox();
                echo "</div>";
            }
        }

        public function wcap_display_checkbox() {

            //$this->wcap_fb_load_scripts();
            $display = "";
            if ( get_option( 'wcap_enable_fb_reminders_popup' ) == 'on' && wcap_get_popup_active_status( 'atc' ) && !is_user_logged_in() ) {
                $display = "display: none;";
            }

            if ( WCAP_FB_APP_ID !== '' && !isset( $_POST['wcap_user_ref'] ) ) {//phpcs:ignore
                ?>
                    <div class="wcap_user_checkbox" style="<?php echo esc_html( $display ); ?>">
                        <div class="clear"></div>

                        <div class="wcap_fb_consent_msg" >
                        <?php esc_html_e( get_option( 'wcap_fb_consent_text' ), 'woocommerce-ac' );//phpcs:ignore ?>
                        </div>
                        <div
                            style="text-align: center;" 
                            class="fb-messenger-checkbox"  
                            origin="<?php echo esc_url( get_home_url() ); ?>"
                            page_id="<?php echo esc_html( WCAP_FB_PAGE_ID );?>"
                            messenger_app_id="<?php echo esc_html( WCAP_FB_APP_ID ); ?>"
                            user_ref="<?php echo esc_html( $this->user_ref ); ?>"
                            prechecked="true"
                            allow_login="true"
                            size="<?php echo esc_html( get_option( 'wcap_fb_user_icon' ) ); ?>"
                            skin="light"
                            center_align="true">
                        </div>

                        <input type="hidden" name="wcap_checkbox_status" id="wcap_checkbox_status" value="">
                        <input type="hidden" name="wcap_user_ref" id="wcap_user_ref" value="">
                    </div>
                <?php
            }
        }

        public function wcap_add_fb_list( $list ) {

            array_push( $list, 'fb_link' );

            return $list;
        }
    }
}

return new WCAP_FB_Frontend_Actions();