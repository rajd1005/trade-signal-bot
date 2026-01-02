<div class="tsb-module table-module">
    <div class="table-responsive">
        <table class="tsb-table">
            <thead><tr><th>Date</th><th>Symbol</th><th>Entry</th><th>SL</th><th>Targets</th><th>High/Low</th><th>P/L</th><th>Actions</th><?php if($allow_edit): ?><th>X</th><?php endif; ?></tr></thead>
            <tbody id="journal-body">
                <?php foreach($journal as $row): 
                    $status = $row->trade_status;
                    $is_pend = ($status=='Pending'); $is_act = ($status=='Active');
                    $t1_hit = (strpos($status, 'T') !== false); $sl_hit = ($status=='SL');
                    $has_low = ($row->low_price > 0);

                    $dis_upside = ($has_low || $is_pend) ? 'disabled style="opacity:0.4; pointer-events:none;"' : '';
                    $dis_low = ($is_pend || $t1_hit || $sl_hit) ? 'disabled style="background:#f0f0f0;"' : '';
                    
                    // Buttons Logic (Same as JS mirror)
                    $btn_act_dis = (!$is_pend) ? 'disabled style="opacity:0.4;"' : '';
                    $btn_sl_dis  = ($is_pend || $sl_hit || $t1_hit) ? 'disabled style="opacity:0.4;"' : '';
                    $btn_t1_dis  = (!$is_act) ? 'disabled style="opacity:0.4;"' : ''; 
                    $btn_t2_dis  = ($status != 'T1') ? 'disabled style="opacity:0.4;"' : '';
                    $btn_t3_dis  = ($status != 'T2') ? 'disabled style="opacity:0.4;"' : '';
                    if($sl_hit || $has_low) { $btn_t1_dis='disabled style="opacity:0.4;"'; $btn_t2_dis=$btn_t1_dis; $btn_t3_dis=$btn_t1_dis; }
                ?>
                <tr id="row-<?php echo $row->id; ?>">
                    <td style="font-size:11px; white-space:nowrap;"><?php echo date('d M', strtotime($row->entry_date)); ?><br><span style="color:#888;"><?php echo date('H:i', strtotime($row->entry_date)); ?></span></td>
                    <td><strong><?php echo $row->symbol_display; ?></strong><br><small style="color:#666;"><?php echo $row->channel_name; ?></small></td>
                    <td><strong><?php echo $row->entry_price; ?></strong><?php if($is_pend && $allow_upd): ?><button class="edit-entry-btn" data-id="<?php echo $row->id; ?>" style="font-size:10px; cursor:pointer; border:none; background:none; color:#2196f3;">✎</button><?php endif; ?></td>
                    <td style="color:#c00;"><?php echo $row->sl_price; ?></td>
                    <td style="font-size:11px;">T1:<?php echo $row->t1; ?><br>T2:<?php echo $row->t2; ?><br>T3:<?php echo $row->t3; ?></td>
                    <td>
                        <div style="display:flex; align-items:center; margin-bottom:2px;"><span style="font-size:10px; width:10px;">H</span><input type="number" step="0.05" class="live-update live-input" data-field="high_price" data-id="<?php echo $row->id; ?>" value="<?php echo $row->high_price; ?>" <?php echo $dis_upside; ?>><button class="high-btn tg-act" data-id="<?php echo $row->id; ?>" data-type="High" <?php echo $dis_upside; ?>>📢</button></div>
                        <div style="display:flex; align-items:center;"><span style="font-size:10px; width:10px;">L</span><input type="number" step="0.05" class="live-update live-input" data-field="low_price" data-id="<?php echo $row->id; ?>" value="<?php echo $row->low_price; ?>" <?php echo $dis_low; ?>></div>
                    </td>
                    <td>
                        <?php $rpl = ($row->profit_loss) ? ($row->profit_loss * $pl_multiplier) : 0; echo '<span class="pl-text" style="color:'.(($rpl>=0)?'green':'red').'">'.number_format($rpl,2).'</span>'; ?><br><span class="status-badge"><?php echo $status; ?></span>
                    </td>
                    <td style="min-width:140px;">
                        <div style="margin-bottom:4px;"><button class="btn-tiny tg-act" data-id="<?php echo $row->id; ?>" data-type="Entry" style="<?php echo $is_pend?'background:#2196f3; color:#fff;':'background:#eee; color:#aaa;'; ?> width:100%;" <?php echo $btn_act_dis; ?>>Active</button></div>
                        <div style="display:flex; gap:1px;">
                            <button class="btn-tiny tg-act btn-sl" data-id="<?php echo $row->id; ?>" data-type="SL" <?php echo $btn_sl_dis; ?>>SL</button>
                            <button class="btn-tiny tg-act btn-t1" data-id="<?php echo $row->id; ?>" data-type="T1" <?php echo $btn_t1_dis; ?>>T1</button>
                            <button class="btn-tiny tg-act btn-t2" data-id="<?php echo $row->id; ?>" data-type="T2" <?php echo $btn_t2_dis; ?>>T2</button>
                            <button class="btn-tiny tg-act btn-t3" data-id="<?php echo $row->id; ?>" data-type="T3" <?php echo $btn_t3_dis; ?>>T3</button>
                        </div>
                    </td>
                    <?php if($allow_edit): ?><td><button class="delete-row" data-id="<?php echo $row->id; ?>" style="color:red; cursor:pointer; font-weight:bold; border:none; background:none;">X</button></td><?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>