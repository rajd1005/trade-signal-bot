<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TSB_Ajax_Trade_Update {

    public function __construct() {
        // Logged-in
        add_action( 'wp_ajax_tsb_update_entry', array( $this, 'update_entry' ) );
        add_action( 'wp_ajax_tsb_update_live', array( $this, 'update_live_data' ) );
        
        // Logged-out
        add_action( 'wp_ajax_nopriv_tsb_update_entry', array( $this, 'update_entry' ) );
        add_action( 'wp_ajax_nopriv_tsb_update_live', array( $this, 'update_live_data' ) );
    }

    public function update_entry() {
        global $wpdb;
        if ( ! get_option( 'tsb_allow_entry_update', 0 ) ) {
            wp_send_json_error( 'Disabled' );
        }
        
        $id = intval( $_POST['id'] );
        $new_entry = floatval( $_POST['new_entry'] );
        
        $table = $wpdb->prefix . 'tsb_trade_journal';
        $trade = $wpdb->get_row( "SELECT * FROM $table WHERE id = $id", ARRAY_A );
        
        if ( ! $trade || $trade['trade_status'] != 'Pending' ) {
            wp_send_json_error( 'Trade not pending or not found' );
        }
        
        // Recalculate SL/Targets based on new Entry
        $sl_pts = floatval( $trade['entry_price'] ) - floatval( $trade['sl_price'] );
        $m_t1 = get_option( 'tsb_calc_t1', 0.5 );
        $m_t2 = get_option( 'tsb_calc_t2', 1.0 );
        $m_t3 = get_option( 'tsb_calc_t3', 1.5 );

        $new_sl = $new_entry - $sl_pts;
        $new_t1 = $new_entry + ( $sl_pts * $m_t1 );
        $new_t2 = $new_entry + ( $sl_pts * $m_t2 );
        $new_t3 = $new_entry + ( $sl_pts * $m_t3 );

        $wpdb->update( 
            $table, 
            array(
                'entry_price' => $new_entry,
                'sl_price'    => $new_sl,
                't1'          => $new_t1,
                't2'          => $new_t2,
                't3'          => $new_t3
            ), 
            array( 'id' => $id ) 
        );

        // Send Telegram Reply
        if ( get_option( 'tsb_msg_on_update', 1 ) ) {
            $tpl = get_option( 'tsb_tpl_update', "🔄 *UPDATE ENTRY*\nNew Entry: {entry}\nNew SL: {sl}\nT1: {t1} | T2: {t2}" );
            $msg = str_replace(
                array( '{entry}', '{sl}', '{t1}', '{t2}', '{t3}' ), 
                array( $new_entry, $new_sl, $new_t1, $new_t2, $new_t3 ), 
                $tpl
            );

            $telegram = new TSB_Telegram_API();
            $msg_id = $telegram->send_message( $msg, $trade['telegram_msg_id'], $trade['telegram_chat_id'] );
            
            if ( $msg_id ) {
                $old_ids = $trade['reply_ids'] ? $trade['reply_ids'] . ',' : '';
                $wpdb->update( 
                    $table, 
                    array(
                        'telegram_msg_id' => $msg_id, 
                        'reply_ids'       => $old_ids . $trade['telegram_msg_id'] 
                    ), 
                    array( 'id' => $id ) 
                );
            }
        }
        
        // Fetch fresh row and return HTML for UI update
        $updated_row = $wpdb->get_row( "SELECT * FROM $table WHERE id = $id" );
        $html = TSB_Frontend_UI::get_trade_row_html($updated_row);
        
        // Fix: Return Stats so status bar updates
        $stats = $this->get_current_stats();

        wp_send_json_success( array( 
            'html' => $html,
            'stats' => $stats
        ) );
    }

    public function update_live_data() {
        global $wpdb;
        $id    = intval( $_POST['id'] );
        $f     = sanitize_text_field( $_POST['field'] );
        $v     = floatval( $_POST['val'] );
        $table = $wpdb->prefix . 'tsb_trade_journal';

        if ( in_array( $f, array( 'high_price', 'low_price' ) ) ) {
            
            $wpdb->update( $table, array( $f => $v ), array( 'id' => $id ) );
            
            $new_pl = 0;
            if ( $f == 'high_price' && $v > 0 ) {
                $trade = $wpdb->get_row( "SELECT entry_price, lot_size FROM $table WHERE id = $id", ARRAY_A );
                if ( $trade ) {
                    $new_pl = ( $v - floatval( $trade['entry_price'] ) ) * floatval( $trade['lot_size'] );
                    $wpdb->update( $table, array( 'profit_loss' => $new_pl ), array( 'id' => $id ) );
                }
            } else {
                $new_pl = $wpdb->get_var( "SELECT profit_loss FROM $table WHERE id = $id" );
            }

            $pl_mult = get_option( 'tsb_pl_multiplier', 6 );
            $display_pl = number_format( $new_pl * $pl_mult, 2 );

            $stats = $this->get_current_stats();

            wp_send_json_success( array(
                'pl'    => $display_pl,
                'stats' => $stats
            ) );

        } else {
            wp_send_json_error( 'Invalid Field' );
        }
    }

    // Helper to get stats
    private function get_current_stats() {
        global $wpdb;
        $pl_mult = get_option( 'tsb_pl_multiplier', 6 );
        $today_start = current_time( 'Y-m-d 00:00:00' );
        $today_end   = current_time( 'Y-m-d 23:59:59' );
        
        $stats = $wpdb->get_row( $wpdb->prepare( "
            SELECT 
                SUM(profit_loss) as tpl, 
                SUM(CASE WHEN profit_loss > 0 THEN 1 ELSE 0 END) as w, 
                SUM(CASE WHEN profit_loss < 0 THEN 1 ELSE 0 END) as l, 
                SUM(CASE WHEN trade_status = 'Pending' OR trade_status = 'Active' THEN 1 ELSE 0 END) as p 
            FROM {$wpdb->prefix}tsb_trade_journal 
            WHERE entry_date BETWEEN %s AND %s
        ", $today_start, $today_end ) );
        
        $total_pl = ( $stats->tpl ) ? ( $stats->tpl * $pl_mult ) : 0;
        
        $total_closed = intval( $stats->w ) + intval( $stats->l );
        $acc = ( $total_closed > 0 ) ? round( ( intval( $stats->w ) / $total_closed ) * 100 ) : 0;

        return array(
            'pl'  => number_format( $total_pl, 2 ),
            'w'   => intval( $stats->w ),
            'l'   => intval( $stats->l ),
            'p'   => intval( $stats->p ),
            'acc' => $acc
        );
    }
}