<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TSB_Ajax_Trade_Submit {

    public function __construct() {
        // Logged-in users
        add_action( 'wp_ajax_tsb_submit_trade', array( $this, 'submit_trade' ) );
        add_action( 'wp_ajax_tsb_delete_trade', array( $this, 'delete_trade' ) );
        
        // Logged-out users
        add_action( 'wp_ajax_nopriv_tsb_submit_trade', array( $this, 'submit_trade' ) );
        add_action( 'wp_ajax_nopriv_tsb_delete_trade', array( $this, 'delete_trade' ) );
    }

    public function submit_trade() {
        global $wpdb;
        $table = $wpdb->prefix . 'tsb_trade_journal';
        $channel_name = sanitize_text_field($_POST['channel_name']);
        
        $channels_list = get_option('tsb_channels_list', array());
        $target_chat_id = '';
        foreach($channels_list as $c) { 
            if($c['name'] === $channel_name) { 
                $target_chat_id = $c['id']; 
                break; 
            } 
        }

        $data = array(
            'entry_date' => current_time('mysql'),
            'channel_name' => $channel_name,
            'symbol_display' => sanitize_text_field($_POST['symbol_display']),
            'lot_size' => intval($_POST['lot_size']),
            'entry_price' => floatval($_POST['entry_price']),
            'sl_price' => floatval($_POST['sl_price']),
            't1' => floatval($_POST['t1']), 
            't2' => floatval($_POST['t2']), 
            't3' => floatval($_POST['t3']),
            'trade_status' => 'Pending',
            'telegram_chat_id' => $target_chat_id
        );

        $wpdb->insert($table, $data);
        $insert_id = $wpdb->insert_id;

        // Telegram Notification (Check Setting)
        if( get_option('tsb_msg_on_new', 1) && !empty($target_chat_id) ) {
            $tpl = get_option('tsb_tpl_new', "🚀 *NEW TRADE* 🚀\n*Symbol:* {symbol}\n*Entry:* {entry}\n*SL:* {sl}\n*Targets:* {t1} | {t2} | {t3}");
            $msg = str_replace(
                array('{symbol}','{entry}','{sl}','{t1}','{t2}','{t3}'), 
                array($data['symbol_display'], $data['entry_price'], $data['sl_price'], $data['t1'], $data['t2'], $data['t3']), 
                $tpl
            );
            
            $telegram = new TSB_Telegram_API();
            $msg_id = $telegram->send_message($msg, null, $target_chat_id);
            if($msg_id) {
                $wpdb->update($table, array('telegram_msg_id' => $msg_id), array('id' => $insert_id));
            }
        }

        // Render the new HTML Row using the Shared Helper
        $row = (object)array_merge($data, array('id'=>$insert_id, 'high_price'=>0, 'profit_loss'=>0));
        $html = TSB_Frontend_UI::get_trade_row_html($row);
        
        wp_send_json_success(array(
            'html' => $html,
            'stats' => $this->get_current_stats()
        ));
    }

    public function delete_trade() {
        global $wpdb;
        // Verify Permission (Plugin setting check only)
        if(!get_option('tsb_allow_edit', 0)) { wp_send_json_error('Permission Denied via Settings'); return; }
        
        $id = intval($_POST['id']);
        $table = $wpdb->prefix . 'tsb_trade_journal';
        $trade = $wpdb->get_row("SELECT * FROM $table WHERE id = $id", ARRAY_A);
        
        if($trade) {
            $telegram = new TSB_Telegram_API();
            
            // Delete Messages
            if($trade['telegram_msg_id']) {
                $telegram->delete_message($trade['telegram_chat_id'], $trade['telegram_msg_id']);
            }
            if($trade['reply_ids']) {
                $ids = explode(',', $trade['reply_ids']);
                foreach($ids as $rid) {
                    $telegram->delete_message($trade['telegram_chat_id'], $rid);
                }
            }
            
            // Delete DB Entry
            $wpdb->delete($table, array('id' => $id));
            
            wp_send_json_success(array(
                'stats' => $this->get_current_stats()
            ));
        } else {
            wp_send_json_error();
        }
    }

    private function get_current_stats() {
        global $wpdb;
        $pl_mult = get_option( 'tsb_pl_multiplier', 6 );
        $today_start = date('Y-m-d 00:00:00', current_time('timestamp'));
        $today_end = date('Y-m-d 23:59:59', current_time('timestamp'));
        
        // FIXED: Only count Pending as Pending
        $stats = $wpdb->get_row($wpdb->prepare("SELECT SUM(profit_loss) as tpl, SUM(CASE WHEN profit_loss > 0 THEN 1 ELSE 0 END) as w, SUM(CASE WHEN profit_loss < 0 THEN 1 ELSE 0 END) as l, SUM(CASE WHEN trade_status='Pending' THEN 1 ELSE 0 END) as p FROM {$wpdb->prefix}tsb_trade_journal WHERE entry_date BETWEEN %s AND %s", $today_start, $today_end));
        $total_pl = ($stats->tpl) ? ($stats->tpl * $pl_mult) : 0;
        
        $total_closed = intval($stats->w) + intval($stats->l);
        $accuracy = ($total_closed > 0) ? round((intval($stats->w) / $total_closed) * 100) : 0;

        return array(
            'pl' => number_format($total_pl, 2),
            'w' => intval($stats->w),
            'l' => intval($stats->l),
            'p' => intval($stats->p),
            'acc' => $accuracy
        );
    }
}