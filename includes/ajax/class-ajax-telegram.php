<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TSB_Ajax_Telegram {
    public function __construct() {
        add_action( 'wp_ajax_tsb_trigger_telegram', array( $this, 'trigger_telegram_update' ) );
        add_action( 'wp_ajax_nopriv_tsb_trigger_telegram', array( $this, 'trigger_telegram_update' ) );
    }

    public function trigger_telegram_update() {
        global $wpdb;
        $id = intval($_POST['trade_id']);
        $type = sanitize_text_field($_POST['type']);
        $table = $wpdb->prefix . 'tsb_trade_journal';
        
        // Fetch original trade data
        $trade = $wpdb->get_row("SELECT * FROM $table WHERE id = $id", ARRAY_A);
        
        if(!$trade) { wp_send_json_error(); return; }
        
        $pl_mult = get_option( 'tsb_pl_multiplier', 6 );
        $price = 0; $profit = 0; $lot = floatval($trade['lot_size']); $entry = floatval($trade['entry_price']);
        $tpl = ""; $target_label = $type; $should_send = false;

        // Logic Handler
        if($type == 'Entry') {
            $price = $entry;
            $tpl = get_option('tsb_tpl_active', "🔵 *ACTIVE*");
            
            // 1. Update Database
            $wpdb->update($table, array('trade_status'=>'Active'), array('id'=>$id));
            
            // 2. CRITICAL FIX: Manually update local array to ensure HTML is correct immediately
            $trade['trade_status'] = 'Active'; 
            
            $should_send = get_option('tsb_msg_on_active', 1);
        } elseif($type == 'High') {
            $price = floatval($trade['high_price']);
            $tpl = get_option('tsb_tpl_high');
            if(get_option('tsb_show_high_pl')) {
                $high_pl_raw = ($price - $entry) * $lot; 
                $final_pl = $high_pl_raw * $pl_mult;
                $tpl .= "\n💰 P/L: " . round($final_pl, 2);
            }
            $should_send = get_option('tsb_msg_on_high', 1);
        } else {
            if($type == 'T1') { $price = $trade['t1']; $target_label="Target 1"; $should_send = get_option('tsb_msg_on_target', 1); }
            if($type == 'T2') { $price = $trade['t2']; $target_label="Target 2"; $should_send = get_option('tsb_msg_on_target', 1); }
            if($type == 'T3') { $price = $trade['t3']; $target_label="Target 3"; $should_send = get_option('tsb_msg_on_target', 1); }
            if($type == 'SL') { $price = $trade['sl_price']; $should_send = get_option('tsb_msg_on_sl', 1); }

            $profit = ($price - $entry) * $lot; 
            
            // Update Database & Local Array
            $wpdb->update($table, array('trade_status'=>$type, 'profit_loss'=>round($profit, 2)), array('id'=>$id));
            $trade['trade_status'] = $type;
            $trade['profit_loss'] = round($profit, 2);

            $tpl = ($type == 'SL') ? get_option('tsb_tpl_sl') : get_option('tsb_tpl_target');
        }

        // Telegram Sending Logic
        if($should_send) {
            if(!$tpl) $tpl = "{symbol} Update: {price}";
            $msg_pl = round($profit * $pl_mult, 2); 
            $msg = str_replace(array('{symbol}','{entry}','{sl}','{t1}','{t2}','{t3}','{price}','{pl}','{target}'), array($trade['symbol_display'], $entry, $trade['sl_price'], $trade['t1'], $trade['t2'], $trade['t3'], $price, $msg_pl, $target_label), $tpl);
            $telegram = new TSB_Telegram_API();
            if(!empty($trade['telegram_chat_id']) && !empty($trade['telegram_msg_id'])) {
                $msg_id = $telegram->send_message($msg, $trade['telegram_msg_id'], $trade['telegram_chat_id']);
                if($msg_id) {
                    $ex_ids = $trade['reply_ids'] ? $trade['reply_ids'] . ',' : '';
                    $wpdb->update($table, array('reply_ids' => $ex_ids . $msg_id), array('id' => $id));
                }
            }
        }
        
        // Generate HTML using the LOCALLY modified object (guarantees accuracy)
        $html = TSB_Frontend_UI::get_trade_row_html((object)$trade);
        
        // Return Data
        $this->return_updated_data($id, $profit * $pl_mult, $html);
    }

    private function return_updated_data($id, $row_pl, $html) {
        global $wpdb;
        $pl_mult = get_option( 'tsb_pl_multiplier', 6 );
        $today_start = current_time('Y-m-d 00:00:00'); $today_end = current_time('Y-m-d 23:59:59');
        
        // FIX: Removed "OR trade_status='Active'" so Pending count drops when Active is clicked
        $stats = $wpdb->get_row($wpdb->prepare("SELECT SUM(profit_loss) as tpl, SUM(CASE WHEN profit_loss>0 THEN 1 ELSE 0 END) as w, SUM(CASE WHEN profit_loss<0 THEN 1 ELSE 0 END) as l, SUM(CASE WHEN trade_status='Pending' THEN 1 ELSE 0 END) as p FROM {$wpdb->prefix}tsb_trade_journal WHERE entry_date BETWEEN %s AND %s", $today_start, $today_end));
        
        $total_pl = ($stats->tpl) ? ($stats->tpl * $pl_mult) : 0;
        
        $total_closed = intval($stats->w) + intval($stats->l);
        $accuracy = ($total_closed > 0) ? round((intval($stats->w) / $total_closed) * 100) : 0;
        
        wp_send_json_success(array(
            'stats' => array(
                'pl' => number_format($total_pl, 2), 
                'w' => intval($stats->w), 
                'l' => intval($stats->l), 
                'p' => intval($stats->p), 
                'acc' => $accuracy
            ),
            'row_pl' => number_format($row_pl, 2),
            'html' => $html
        ));
    }
}