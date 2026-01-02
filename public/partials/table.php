<div class="tsb-module table-module">
    <div class="table-responsive">
        <table class="tsb-table">
            <thead><tr><th>Date</th><th>Symbol</th><th>Entry</th><th>SL</th><th>Targets</th><th>High/Low</th><th>P/L</th><th>Actions</th><?php if($allow_edit): ?><th>X</th><?php endif; ?></tr></thead>
            <tbody id="journal-body">
                <?php 
                foreach($journal as $row): 
                    echo TSB_Frontend_UI::get_trade_row_html($row);
                endforeach; 
                ?>
            </tbody>
        </table>
    </div>
</div>