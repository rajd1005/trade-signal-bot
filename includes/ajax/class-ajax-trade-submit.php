<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TSB_Ajax_Trade_Submit {

    public function __construct() {
        add_action( 'wp_ajax_tsb_submit_trade', array( $this, 'submit_trade' ) );
        add_action( 'wp_ajax_tsb_delete_trade', array( $this, 'delete_trade' ) );
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

        // Render the new HTML Row
        $row = (object)array_merge($data, array('id'=>$insert_id, 'high_price'=>0, 'low_price'=>0, 'profit_loss'=>0));
        
        // Fetch specific permissions to render buttons correctly
        $allow_edit = (int) get_option('tsb_allow_edit', 0);
        $allow_upd  = (int) get_option('tsb_allow_entry_update', 0);
        
        ob_start();
        ?>
        <tr id="row-<?php echo $row->id; ?>" data-entry="<?php echo $row->entry_price; ?>" data-t1="<?php echo $row->t1; ?>" data-t2="<?php echo $row->t2; ?>" data-t3="<?php echo $row->t3; ?>" data-status="Pending">
            <td style="font-size:10px; white-space:nowrap; color:#666;"><?php echo date('d M H:i', strtotime($row->entry_date)); ?></td>
            <td><strong><?php echo $row->symbol_display; ?></strong><br><small style="font-size:10px; color:#888;"><?php echo $row->channel_name; ?></small></td>
            <td style="font-weight:bold;">
                <?php echo $row->entry_price; ?>
                <?php if($allow_upd): ?>
                    <button class="edit-entry-btn" data-id="<?php echo $row->id; ?>" style="font-size:10px; border:none; background:none; color:#2196f3; cursor:pointer;">✎</button>
                <?php endif; ?>
            </td>
            <td style="color:#c00;"><?php echo $row->sl_price; ?></td>
            <td style="font-size:10px;">T1:<?php echo $row->t1; ?><br>T2:<?php echo $row->t2; ?><br>T3:<?php echo $row->t3; ?></td>
            <td>
                <div style="display:flex; align-items:center; margin-bottom:2px;"><span style="font-size:9px; width:12px;">H</span><span class="hl-display hl-high-display" data-id="<?php echo $row->id; ?>">-</span><input type="number" step="0.05" class="live-update hl-input hl-high-input" data-field="high_price" data-id="<?php echo $row->id; ?>" value="0" style="display:none; width:50px; font-size:11px;" disabled><button class="hl-edit-btn" data-target="high" data-id="<?php echo $row->id; ?>" disabled style="border:none; background:none; color:#2196f3; cursor:pointer; font-size:12px; opacity:0.4;">✎</button><button class="high-btn tg-act" data-id="<?php echo $row->id; ?>" data-type="High" disabled style="opacity:0.4;" title="Trigger High">📢</button></div>
                <div style="display:flex; align-items:center;"><span style="font-size:9px; width:12px;">L</span><span class="hl-display hl-low-display" data-id="<?php echo $row->id; ?>">-</span><input type="number" step="0.05" class="live-update hl-input hl-low-input" data-field="low_price" data-id="<?php echo $row->id; ?>" value="0" style="display:none; width:50px; font-size:11px;" disabled><button class="hl-edit-btn" data-target="low" data-id="<?php echo $row->id; ?>" disabled style="border:none; background:none; color:#2196f3; cursor:pointer; font-size:12px; opacity:0.4;">✎</button></div>
            </td>
            <td><span class="pl-text" style="font-weight:bold; color:green">0.00</span><br><span class="status-badge" style="font-size:9px;">Pending</span></td>
            <td style="min-width:130px;">
                <div style="margin-bottom:2px;"><button class="btn-tiny tg-act" data-id="<?php echo $row->id; ?>" data-type="Entry" style="background:#2196f3; color:#fff; width:100%;">Active</button></div>
                <div style="display:flex; gap:1px;"><button class="btn-tiny tg-act btn-sl" data-id="<?php echo $row->id; ?>" data-type="SL" disabled style="opacity:0.4;">SL</button><button class="btn-tiny tg-act btn-t1" data-id="<?php echo $row->id; ?>" data-type="T1" disabled style="opacity:0.4;">T1</button><button class="btn-tiny tg-act btn-t2" data-id="<?php echo $row->id; ?>" data-type="T2" disabled style="opacity:0.4;">T2</button><button class="btn-tiny tg-act btn-t3" data-id="<?php echo $row->id; ?>" data-type="T3" disabled style="opacity:0.4;">T3</button></div>
            </td>
            <?php if($allow_edit): ?><td><button class="delete-row" data-id="<?php echo $row->id; ?>" style="color:red; border:none; background:none; cursor:pointer; font-weight:bold;">X</button></td><?php endif; ?>
        </tr>
        <?php
        $html = ob_get_clean();
        
        // Calculate & Return Fresh Stats
        $pl_mult = get_option( 'tsb_pl_multiplier', 6 );
        $today_start = date('Y-m-d 00:00:00', current_time('timestamp'));
        $today_end = date('Y-m-d 23:59:59', current_time('timestamp'));
        
        $stats = $wpdb->get_row($wpdb->prepare("SELECT SUM(profit_loss) as tpl, SUM(CASE WHEN profit_loss > 0 THEN 1 ELSE 0 END) as w, SUM(CASE WHEN profit_loss < 0 THEN 1 ELSE 0 END) as l, SUM(CASE WHEN trade_status='Pending' OR trade_status='Active' THEN 1 ELSE 0 END) as p FROM {$wpdb->prefix}tsb_trade_journal WHERE entry_date BETWEEN %s AND %s", $today_start, $today_end));
        $total_pl = ($stats->tpl) ? ($stats->tpl * $pl_mult) : 0;
        
        wp_send_json_success(array(
            'html' => $html,
            'stats' => array(
                'pl' => number_format($total_pl, 2),
                'w' => intval($stats->w),
                'l' => intval($stats->l),
                'p' => intval($stats->p)
            )
        ));
    }

    public function delete_trade() {
        global $wpdb;
        // Verify Permission
        if(!get_option('tsb_allow_edit', 0)) { wp_send_json_error('Permission Denied'); return; }
        
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
            
            // Return Fresh Stats (Matches Submit Logic)
            $pl_mult = get_option( 'tsb_pl_multiplier', 6 );
            $today_start = date('Y-m-d 00:00:00', current_time('timestamp'));
            $today_end = date('Y-m-d 23:59:59', current_time('timestamp'));
            $stats = $wpdb->get_row($wpdb->prepare("SELECT SUM(profit_loss) as tpl, SUM(CASE WHEN profit_loss > 0 THEN 1 ELSE 0 END) as w, SUM(CASE WHEN profit_loss < 0 THEN 1 ELSE 0 END) as l, SUM(CASE WHEN trade_status='Pending' OR trade_status='Active' THEN 1 ELSE 0 END) as p FROM {$wpdb->prefix}tsb_trade_journal WHERE entry_date BETWEEN %s AND %s", $today_start, $today_end));
            $total_pl = ($stats->tpl) ? ($stats->tpl * $pl_mult) : 0;
            
            wp_send_json_success(array(
                'stats' => array(
                    'pl' => number_format($total_pl, 2),
                    'w' => intval($stats->w),
                    'l' => intval($stats->l),
                    'p' => intval($stats->p)
                )
            ));
        } else {
            wp_send_json_error();
        }
    }
}