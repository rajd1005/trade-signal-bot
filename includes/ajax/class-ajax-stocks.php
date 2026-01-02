<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TSB_Ajax_Stocks {
    public function __construct() {
        add_action( 'wp_ajax_tsb_get_stock_details', array( $this, 'get_stock_details' ) );
        add_action( 'wp_ajax_nopriv_tsb_get_stock_details', array( $this, 'get_stock_details' ) );
    }

    public function get_stock_details() {
        global $wpdb;
        $symbol = sanitize_text_field( $_POST['symbol'] );
        $result = $wpdb->get_row( $wpdb->prepare( "SELECT stop_loss, lot_size FROM {$wpdb->prefix}tsb_master_stocks WHERE symbol_name = %s", $symbol ), ARRAY_A );
        if($result) {
            $result['m_t1'] = get_option('tsb_calc_t1', 0.5);
            $result['m_t2'] = get_option('tsb_calc_t2', 1.0);
            $result['m_t3'] = get_option('tsb_calc_t3', 1.5);
            wp_send_json_success( $result );
        } else wp_send_json_error();
    }
}