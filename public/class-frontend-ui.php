<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TSB_Frontend_UI {

    public function __construct() {
        add_shortcode( 'trade_signal_form', array( $this, 'render_ui' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function enqueue_assets() {
        wp_enqueue_style( 'tsb-style', TSB_PLUGIN_URL . 'public/css/style.css' );
        wp_enqueue_script( 'tsb-core-js', TSB_PLUGIN_URL . 'public/js/tsb-core.js', array( 'jquery' ), '2.5', true );
        wp_enqueue_script( 'tsb-form-js', TSB_PLUGIN_URL . 'public/js/tsb-form.js', array( 'jquery', 'tsb-core-js' ), '2.5', true );
        wp_enqueue_script( 'tsb-table-js', TSB_PLUGIN_URL . 'public/js/tsb-table.js', array( 'jquery', 'tsb-core-js' ), '2.5', true );
        wp_localize_script( 'tsb-core-js', 'tsb_ajax', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
    }

    public function render_ui() {
        global $wpdb;
        $stocks = $wpdb->get_results( "SELECT symbol_name FROM {$wpdb->prefix}tsb_master_stocks ORDER BY symbol_name ASC" );
        
        $allow_edit    = (int) get_option( 'tsb_allow_edit', 0 );
        $allow_upd     = (int) get_option( 'tsb_allow_entry_update', 0 );
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
        
        // Accuracy Calculation
        $total_closed = intval($stats->w) + intval($stats->l);
        $accuracy = ($total_closed > 0) ? round((intval($stats->w) / $total_closed) * 100) : 0;

        $journal = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tsb_trade_journal ORDER BY id DESC LIMIT 20");

        ob_start();
        echo '<div class="tsb-main-wrapper">';
        echo '<div id="tsb-toast" class="tsb-toast">Notification</div>';
        
        $this->render_form_module($available_channels, $stocks, $channels, $layout_style);
        $this->render_stats_bar($display_pl, $pl_color, $stats, $accuracy);
        $this->render_table_module($journal, $pl_multiplier, $allow_edit, $allow_upd);
        
        echo '</div>';
        return ob_get_clean();
    }

    private function render_stats_bar($display_pl, $pl_color, $stats, $accuracy) {
        ?>
        <div class="tsb-stats-bar">
            <div class="stat-box pl-box" style="border-left: 4px solid <?php echo $pl_color; ?>">
                <span class="stat-label">P/L Today (<span id="dash-acc"><?php echo $accuracy; ?></span>%)</span>
                <span class="stat-val" id="dash-pl" style="color:<?php echo $pl_color; ?>"><?php echo number_format($display_pl, 2); ?></span>
            </div>
            <div class="stat-box"><span class="stat-label">Wins</span><span class="stat-val green" id="dash-w"><?php echo intval($stats->w); ?></span></div>
            <div class="stat-box"><span class="stat-label">Losses</span><span class="stat-val red" id="dash-l"><?php echo intval($stats->l); ?></span></div>
            <div class="stat-box"><span class="stat-label">Pending</span><span class="stat-val blue" id="dash-p"><?php echo intval($stats->p); ?></span></div>
        </div>
        <?php
    }

    private function render_form_module($available_channels, $stocks, $all_channels, $layout_style) {
        ?>
        <div class="tsb-module form-module">
            <?php if ( empty( $all_channels ) ) : ?>
                <div class="alert-box" style="color:red; text-align:center;">Configure Channels in Admin Settings.</div>
            <?php elseif ( empty( $available_channels ) ) : ?>
                <div class="alert-box">Daily limit reached.</div>
            <?php else : ?>
                <form id="tsb-trade-form">
                    <div class="form-header"><h3>New Signal</h3><button type="button" class="details-toggle">Details +</button></div>
                    <div class="form-grid-layout">
                        <div class="form-section-main">
                            <div class="compact-row">
                                <div class="col"><label>Channel</label><select name="channel_name" id="channel_name"><?php foreach($available_channels as $ac): ?><option value="<?php echo esc_attr($ac['name']); ?>"><?php echo esc_html($ac['name']); ?></option><?php endforeach; ?></select></div>
                                <div class="col"><label>Instrument</label><input list="stock_list" name="stock_name" id="stock_name" placeholder="Search"><datalist id="stock_list"><?php foreach($stocks as $s): ?><option value="<?php echo esc_attr($s->symbol_name); ?>"><?php endforeach; ?></datalist></div>
                                <div class="col" style="max-width:80px;"><label>Strike</label><input type="number" id="strike_price" placeholder="2400"></div>
                            </div>
                            <div class="compact-row" style="align-items:flex-end;">
                                <div class="col" style="max-width:120px;">
                                    <div class="toggle-group"><button type="button" class="type-btn active" data-val="CE">CE</button><button type="button" class="type-btn" data-val="PE">PE</button><input type="hidden" id="ce_pe" value="CE"></div>
                                </div>
                                <div class="col"><label>Entry</label><input type="number" step="0.05" id="entry_price" placeholder="0.00" required></div>
                                <div class="col"><button type="submit" id="submit-trade">Publish</button></div>
                            </div>
                        </div>
                        <div class="form-section-details details-content" <?php echo $layout_style; ?>>
                            <div class="compact-row">
                                <div class="col"><label>Date</label><input type="text" id="trade_date" readonly></div>
                                <div class="col"><label>Expiry</label><input type="text" name="expiry" id="expiry" value="Recent Expiry"></div>
                            </div>
                            <div class="compact-row">
                                <div class="col"><label>Lot</label><input type="hidden" id="lot_size" name="lot_size"><input type="text" id="view_lot_size" readonly></div>
                                <div class="col"><label>SL Pts</label><input type="hidden" id="sl_points_db"><input type="text" id="view_sl" readonly></div>
                                <div class="col"><label>SL Price</label><input type="number" step="0.05" id="sl_price"></div>
                            </div>
                            <div class="compact-row">
                                <div class="col"><input type="number" step="0.05" id="t1" readonly placeholder="T1"></div>
                                <div class="col"><input type="number" step="0.05" id="t2" readonly placeholder="T2"></div>
                                <div class="col"><input type="number" step="0.05" id="t3" readonly placeholder="T3"></div>
                            </div>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_table_module($journal, $pl_multiplier, $allow_edit, $allow_upd) {
        ?>
        <div class="tsb-module table-module">
            <div class="table-responsive">
                <table class="tsb-table">
                    <thead><tr><th>Date</th><th>Symbol</th><th>Entry</th><th>SL</th><th>Targets</th><th>High / Low</th><th>P/L</th><th>Actions</th><?php if($allow_edit): ?><th>X</th><?php endif; ?></tr></thead>
                    <tbody id="journal-body">
                        <?php foreach($journal as $row): 
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
                        ?>
                        <tr id="row-<?php echo $row->id; ?>" data-entry="<?php echo $row->entry_price; ?>" data-t1="<?php echo $row->t1; ?>" data-t2="<?php echo $row->t2; ?>" data-t3="<?php echo $row->t3; ?>" data-status="<?php echo $status; ?>">
                            <td style="font-size:10px; white-space:nowrap; color:#666;"><?php echo date('d M H:i', strtotime($row->entry_date)); ?></td>
                            <td><strong><?php echo $row->symbol_display; ?></strong><br><small style="font-size:10px; color:#888;"><?php echo $row->channel_name; ?></small></td>
                            <td style="font-weight:bold;"><?php echo $row->entry_price; ?><?php if($is_pend && $allow_upd): ?><button class="edit-entry-btn" data-id="<?php echo $row->id; ?>" style="font-size:10px; border:none; background:none; color:#2196f3; cursor:pointer;">✎</button><?php endif; ?></td>
                            <td style="color:#c00;"><?php echo $row->sl_price; ?></td>
                            <td style="font-size:10px;">T1:<?php echo $row->t1; ?><br>T2:<?php echo $row->t2; ?><br>T3:<?php echo $row->t3; ?></td>
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
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
}