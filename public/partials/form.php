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
                        <div class="col"><label>Channel</label><select name="channel_name" id="channel_name" required><?php foreach($available_channels as $ac): ?><option value="<?php echo esc_attr($ac['name']); ?>"><?php echo esc_html($ac['name']); ?></option><?php endforeach; ?></select></div>
                        
                        <div class="col">
                            <label>Instrument</label>
                            <select name="stock_name" id="stock_name" required>
                                <option value="">Select Stock</option>
                                <?php foreach($stocks as $s): ?>
                                    <option value="<?php echo esc_attr($s->symbol_name); ?>"><?php echo esc_html($s->symbol_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col" style="max-width:80px;"><label>Strike</label><input type="number" id="strike_price" placeholder="2400" required></div>
                    </div>
                    <div class="compact-row" style="align-items:flex-end;">
                        <div class="col" style="max-width:120px;">
                            <div class="toggle-group"><button type="button" class="type-btn active" data-val="CE">CE</button><button type="button" class="type-btn" data-val="PE">PE</button><input type="hidden" id="ce_pe" value="CE"></div>
                        </div>
                        <div class="col"><label>Entry</label><input type="number" step="0.05" id="entry_price" placeholder="0.00" required></div>
                        <div class="col" id="submit-col"><button type="submit" id="submit-trade">Publish</button></div>
                    </div>
                </div>
                <div class="form-section-details details-content" <?php echo $layout_style; ?>>
                    <div class="compact-row">
                        <div class="col"><label>Date</label><input type="text" id="trade_date" readonly></div>
                        <div class="col"><label>Expiry</label><input type="text" name="expiry" id="expiry" value="Recent Expiry" required></div>
                    </div>
                    <div class="compact-row">
                        <div class="col"><label>Lot</label><input type="hidden" id="lot_size" name="lot_size"><input type="text" id="view_lot_size" readonly></div>
                        <div class="col"><label>SL Pts</label><input type="hidden" id="sl_points_db"><input type="text" id="view_sl" readonly></div>
                        <div class="col"><label>SL Price</label><input type="number" step="0.05" id="sl_price" required></div>
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