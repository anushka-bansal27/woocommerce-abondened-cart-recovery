<?php
/**
 * It will show the dashboard data.
 * @package     Abandoned-Cart-Pro-for-WooCommerce/Classes
 * @since       3.5
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * It will show the dashboard data.
 */
class Wcap_Advanced_Report_Action {

    /**
     * It will generate all the reports for the Dashboard.
     * It will show charts, ATC stats, Template stats.
     * @param string $selected_data_range Selected range of date
     * @param string $start_date Start Date
     * @param string $end_date End date
     * @globals mixed $wpdb
     * @since 3.5
     */
    function wcap_get_all_reports( $selected_data_range, $start_date, $end_date ){

        global $wpdb;

        include_once( 'class_wcap_dashboard_report.php' );

        $wcap_month_total_orders_amount   = $wcap_month_recovered_cart_amount = 0;
        $wcap_month_recovered_cart_count  = $wcap_month_abandoned_cart_count  = 0;
        $ratio_of_recovered_number        = 0;
        $wcap_month_wc_orders             = 0;
        $ratio_of_recovered               = 0;
        $ratio_of_total_vs_abandoned      = 0;
        $wcap_atc_data                    = array();
        $orders = new Wcap_Dashboard_Report();

        $wcap_month_total_orders_amount   = $orders->get_this_month_amount_reports( 'wc_total_sales' , $selected_data_range, $start_date, $end_date );
        $wcap_month_recovered_cart_amount = $orders->get_this_month_amount_reports( 'recover'        , $selected_data_range, $start_date, $end_date );

        // if total order amount goes less than zero, then set it to 0.
        if ( $wcap_month_total_orders_amount < 0 ){

            $wcap_month_total_orders_amount = 0 ;
        }
        if ( $wcap_month_recovered_cart_amount > 0 && $wcap_month_total_orders_amount > 0 ){
            $ratio_of_recovered            = ( $wcap_month_recovered_cart_amount / $wcap_month_total_orders_amount ) * 100;
            $ratio_of_recovered            = round( $ratio_of_recovered, wc_get_price_decimals() );
        }

        /**
         * Stats structure
         * 
         * array(
         *   'abandoned_count' => $abandoned_count,
         *   'recovered_count' => $recovered_count,
         *   'abandoned_amount' => $abandoned_amount,
         *   'recovered_amount' => $recovered_amount
         * );
         */
        $stats = $orders->get_adv_stats( $selected_data_range, $start_date, $end_date );

        $wcap_month_abandoned_cart_count   = $stats['abandoned_count'];
        $wcap_month_recovered_cart_count   = $stats['recovered_count'];

        if ( $wcap_month_recovered_cart_count > 0 && $wcap_month_abandoned_cart_count > 0 ){
            $ratio_of_recovered_number     = ( $wcap_month_recovered_cart_count / $wcap_month_abandoned_cart_count ) * 100;
            $ratio_of_recovered_number     = round( $ratio_of_recovered_number, wc_get_price_decimals() );
        }

        $wcap_month_wc_orders              = $orders->get_this_month_total_vs_abandoned_order( 'wc_total_orders', $selected_data_range, $start_date, $end_date );

        if ( $wcap_month_abandoned_cart_count > 0 && $wcap_month_wc_orders > 0 ){
            $ratio_of_total_vs_abandoned   = ( $wcap_month_abandoned_cart_count / $wcap_month_wc_orders  ) * 100;
            $ratio_of_total_vs_abandoned   = round( $ratio_of_total_vs_abandoned, wc_get_price_decimals() );
        }

        $wcap_email_sent_count             = $orders->wcap_get_email_report( "total_sent", $selected_data_range, $start_date, $end_date );

        $wcap_email_opened_count           = $orders->wcap_get_email_report( "total_opened", $selected_data_range, $start_date, $end_date );

        $wcap_email_clicked_count          = $orders->wcap_get_email_report( "total_clicked", $selected_data_range, $start_date, $end_date );

        $wcap_atc_data                     = $orders->wcap_get_atc_data_of_range( $selected_data_range, $start_date, $end_date );

        $graph_data = $orders->get_abandoned_data( $selected_data_range, $start_date, $end_date );

        wp_localize_script(
            'wcap_graph_js', 
            'wcap_graph_data', 
            array(
                'data'   => $graph_data,
            ) 
        );

        wp_enqueue_script ( 'wcap_graph_js' );
        $dashboar_arr = array();
        $this->search_by_date();
            $dashboar_arr['Recovered_amount'] = $wcap_month_recovered_cart_amount;
            $dashboar_arr['Recovered_orders'] = $wcap_month_recovered_cart_count;
            $dashboar_arr['ratio_of_recovered_orders'] = $ratio_of_recovered_number;
            $dashboar_arr['ratio_of_total_revenue'] = $ratio_of_recovered;
            $dashboar_arr['Abandoned_orders'] = $wcap_month_abandoned_cart_count;
            $dashboar_arr['amount_of_abandoned_orders'] = round( $stats['abandoned_amount'], wc_get_price_decimals() );
            $dashboar_arr['Number_of_emails_sent'] = $wcap_email_sent_count;
            $dashboar_arr['Emails_opened'] = $wcap_email_opened_count;
            $dashboar_arr['Emails_clicked'] = $wcap_email_clicked_count;
            $dashboar_arr['Email_capture_pop_displayed'] = $wcap_atc_data[ 'wcap_atc_open' ];
            $dashboar_arr['email_addresses_captured_from_add_to_cart_popups'] = $wcap_atc_data[ 'wcap_has_email' ];
            $dashboar_arr['email_addresses_captured_from_exit_intent_popups'] = $wcap_atc_data[ 'wcap_ei_email' ];
            $dashboar_arr['graph_data'] = $graph_data;
            return wp_send_json( $dashboar_arr );
    }

    /**
     * It will display the search filter on the dashboard.
     * @since 3.5
     */
    public function search_by_date(  ) {
        //phpcs:disable
        if ( isset( $_POST['duration_select'] ) ) {
            $duration_range = sanitize_text_field( wp_unslash( $_POST['duration_select'] ) );
        } else {
            $duration_range = "this_month";
        }
       

                $start_date_range = "";
                if ( isset( $_POST['wcap_start_date'] ) ) {
                    $start_date_range = sanitize_text_field( wp_unslash( $_POST['wcap_start_date'] ) );
                }

                $end_date_range = "";
                if ( isset( $_POST['wcap_end_date'] ) ){
                    $end_date_range = sanitize_text_field( wp_unslash( $_POST['wcap_end_date'] ) );
                }
                $start_end_date_div_show = 'block';
                if ( !isset($_POST['duration_select']) || $_POST['duration_select'] != 'other' ) {
                    $start_end_date_div_show = 'none';
                }//phpcs:enable

    }
}
