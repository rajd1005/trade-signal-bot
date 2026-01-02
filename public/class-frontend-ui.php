<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TSB_Frontend_UI {

    public function __construct() {
        add_shortcode( 'trade_signal_form', array( $this, 'render_ui' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function enqueue_assets() {
        wp_enqueue_style( 'tsb-style', TSB_PLUGIN_URL . 'public/css/style.css' );
        wp_enqueue_script( 'tsb-core-js', TSB_PLUGIN_URL . 'public/js/tsb-core.js', array( 'jquery' ), '2.6', true );
        wp_enqueue_script( 'tsb-form-js', TSB_PLUGIN_URL . 'public/js/tsb-form.js', array( 'jquery', 'tsb-core-js' ), '2.6', true );
        wp_enqueue_script( 'tsb-table-js', TSB_PLUGIN_URL . 'public/js/tsb-table.js', array( 'jquery', 'tsb-core-js' ), '2.6', true );
        
        // No security nonce passed here, just the URL
        wp_localize_script( 'tsb-core-js', 'tsb_ajax', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
    }

    public function render_ui() {
        global $wpdb;
        $stocks = $wpdb->get_results( "SELECT symbol_name FROM {$wpdb->prefix}tsb_master_stocks ORDER BY symbol_name ASC" );
        
        $pl_multiplier = (float) get_option( 'tsb_pl_multiplier', 6 );
        $layout_style  = get_option( 'tsb_form_layout_open', 0 ) ? 'style="display:block;"' : 'style="display:none;"';
        
        $today_start = current_time('Y-m-d 00:00:00');
        $today_end   = current_time('Y-m-d 23:59:59');
        
        $channels = get_option( 'tsb_channels_list', array() );
        $available_channels = array();
        if ( ! empty( $channels ) ) {
            foreach( $channels as $c ) {
                $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}tsb_trade_journal WHERE channel_name = %s AND entry_date BETWEEN %s AND %s", $c['name'], $today_start, $today_end ));
                if ( $count < intval($c['limit']) ) { $available_channels[] = $c; }
            }
        }

        // Stats Calculation
        $stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                SUM(profit_loss) as tpl, 
                SUM(CASE WHEN profit_loss > 0 THEN 1 ELSE 0 END) as w, 
                SUM(CASE WHEN profit_loss < 0 THEN 1 ELSE 0 END) as l, 
                SUM(CASE WHEN trade_status = 'Pending' OR trade_status = 'Active' THEN 1 ELSE 0 END) as p 
            FROM {$wpdb->prefix}tsb_trade_journal 
            WHERE entry_date BETWEEN %s AND %s
        ", $today_start, $today_end));
        
        $display_pl = ($stats->tpl) ? ($stats->tpl * $pl_multiplier) : 0;
        $pl_color   = ($display_pl >= 0) ? '#2e7d32' : '#c62828';
        
        // Accuracy
        $total_closed = intval($stats->w) + intval($stats->l);
        $accuracy = ($total_closed > 0) ? round((intval($stats->w) / $total_closed) * 100) : 0;

        $journal = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tsb_trade_journal ORDER BY id DESC LIMIT 20");

        ob_start();
        echo '<div class="tsb-main-wrapper">';
        echo '<div id="tsb-toast" class="tsb-toast">Notification</div>';
        
        $this->render_form_module($available_channels, $stocks, $channels, $layout_style);
        $this->render_stats_bar($display_pl, $pl_color, $stats, $accuracy);
        $this->render_table_module($journal);
        
        echo '</div>';
        return ob_get_clean();
    }

    private function render_stats_bar($display_pl, $pl_color, $stats, $accuracy) {
        include TSB_PLUGIN_DIR . 'public/partials/stats.php';
    }

    private function render_form_module($available_channels, $stocks, $all_channels, $layout_style) {
        include TSB_PLUGIN_DIR . 'public/partials/form.php';
    }

    private function render_table_module($journal) {
        $allow_edit = (int) get_option( 'tsb_allow_edit', 0 );
        include TSB_PLUGIN_DIR . 'public/partials/table.php';
    }

    public static function get_trade_row_html($row) {
        $allow_edit    = (int) get_option( 'tsb_allow_edit', 0 );
        $allow_upd     = (int) get_option( 'tsb_allow_entry_update', 0 );
        $pl_multiplier = (float) get_option( 'tsb_pl_multiplier', 6 );
        
        $status = $row->trade_status;
        $is_pend = ($status=='Pending'); $is_act = ($status=='Active');
        $t1_hit = (strpos($status, 'T') !== false); $sl_hit = ($status=='SL');
        $has_low = ($row->low_price > 0);

        $dis_upside = ($has_low || $is_pend || $sl_hit) ? 'disabled style="opacity:0.4; pointer-events:none;"' : '';
        $dis_low = ($is_pend || $t1_hit || $sl_hit) ? 'disabled style="background:#f0f0f0;"' : '';
        
        $btn_act_dis = (!$is_pend) ? 'disabled style="opacity:0.4;"' : '';
        $btn_sl_dis  = ($is_pend || $sl_hit || $t1_hit) ? 'disabled style="opacity:0.4;"' : '';
        $btn_t1_dis  = (!$is_act) ? 'disabled style="opacity:0.4;"' : ''; 
        $btn_t2_dis  = ($status != 'T1') ? 'disabled style="opacity:0.4;"' : '';
        $btn_t3_dis  = ($status != 'T2') ? 'disabled style="opacity:0.4;"' : '';
        if($sl_hit || $has_low) { $btn_t1_dis='disabled style="opacity:0.4;"'; $btn_t2_dis=$btn_t1_dis; $btn_t3_dis=$btn_t1_dis; }

        ob_start();
        ?>
        <tr id="row-<?php echo $row->id; ?>" data-entry="<?php echo $row->entry_price; ?>" data-t1="<?php echo $row->t1; ?>" data-t2="<?php echo $row->t2; ?>" data-t3="<?php echo $row->t3; ?>" data-status="<?php echo $status; ?>">
            <td style="font-size:10px; white-space:nowrap; color:#666;"><?php echo date('d M H:i', strtotime($row->entry_date)); ?></td>
            <td><strong><?php echo esc_html($row->symbol_display); ?></strong><br><small style="font-size:10px; color:#888;"><?php echo esc_html($row->channel_name); ?></small></td>
            <td style="font-weight:bold;"><?php echo $row->entry_price; ?><?php if($is_pend && $allow_upd): ?><button class="edit-entry-btn" data-id="<?php echo $row->id; ?>" style="font-size:10px; border:none; background:none; color:#2196f3; cursor:pointer;">✎</button><?php endif; ?></td>
            <td style="color:#c00;"><?php echo $row->sl_price; ?></td>
            <td style="font-size:11px;">T1:<?php echo $row->t1; ?><br>T2:<?php echo $row->t2; ?><br>T3:<?php echo $row->t3; ?></td>
            <td>
                <div style="display:flex; align-items:center; margin-bottom:2px;">
                    <span style="font-size:9px; width:12px;">H</span>
                    <span class="hl-display hl-high-display" data-id="<?php echo $row->id; ?>"><?php echo $row->high_price > 0 ? $row->high_price : '-'; ?></span>
                    <input type="number" step="0.05" class="live-update hl-input hl-high-input" data-field="high_price" data-id="<?php echo $row->id; ?>" value="<?php echo $row->high_price; ?>" style="display:none; width:50px; font-size:11px;" <?php echo $dis_upside; ?>>
                    <button class="hl-edit-btn" data-target="high" data-id="<?php echo $row->id; ?>" <?php echo $dis_upside; ?> style="border:none; background:none; color:#2196f3; cursor:pointer; font-size:12px;">✎</button>
                    <button class="high-btn tg-act" data-id="<?php echo $row->id; ?>" data-type="High" <?php echo $dis_upside; ?> title="Trigger High">📢</button>
                </div>
                <div style="display:flex; align-items:center;">
                    <span style="font-size:9px; width:12px;">L</span>
                    <span class="hl-display hl-low-display" data-id="<?php echo $row->id; ?>"><?php echo $row->low_price > 0 ? $row->low_price : '-'; ?></span>
                    <input type="number" step="0.05" class="live-update hl-input hl-low-input" data-field="low_price" data-id="<?php echo $row->id; ?>" value="<?php echo $row->low_price; ?>" style="display:none; width:50px; font-size:11px;" <?php echo $dis_low; ?>>
                    <button class="hl-edit-btn" data-target="low" data-id="<?php echo $row->id; ?>" <?php echo $dis_low; ?> style="border:none; background:none; color:#2196f3; cursor:pointer; font-size:12px;">✎</button>
                </div>
            </td>
            <td>
                <?php $rpl = ($row->profit_loss) ? ($row->profit_loss * $pl_multiplier) : 0; echo '<span class="pl-text" style="font-weight:bold; color:'.(($rpl>=0)?'green':'red').'">'.number_format($rpl,2).'</span>'; ?><br><span class="status-badge" style="font-size:9px;"><?php echo $status; ?></span>
            </td>
            <td style="min-width:130px;">
                <div style="margin-bottom:2px;"><button class="btn-tiny tg-act" data-id="<?php echo $row->id; ?>" data-type="Entry" style="<?php echo $is_pend?'background:#2196f3; color:#fff;':'background:#eee; color:#aaa;'; ?> width:100%;" <?php echo $btn_act_dis; ?>>Active</button></div>
                <div style="display:flex; gap:1px;"><button class="btn-tiny tg-act btn-sl" data-id="<?php echo $row->id; ?>" data-type="SL" <?php echo $btn_sl_dis; ?>>SL</button><button class="btn-tiny tg-act btn-t1" data-id="<?php echo $row->id; ?>" data-type="T1" <?php echo $btn_t1_dis; ?>>T1</button><button class="btn-tiny tg-act btn-t2" data-id="<?php echo $row->id; ?>" data-type="T2" <?php echo $btn_t2_dis; ?>>T2</button><button class="btn-tiny tg-act btn-t3" data-id="<?php echo $row->id; ?>" data-type="T3" <?php echo $btn_t3_dis; ?>>T3</button></div>
            </td>
            <?php if($allow_edit): ?><td><button class="delete-row" data-id="<?php echo $row->id; ?>" style="color:red; border:none; background:none; cursor:pointer; font-weight:bold;">X</button></td><?php endif; ?>
        </tr>
        <?php
        return ob_get_clean();
    }
}