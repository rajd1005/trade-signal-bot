<div class="tsb-stats-bar">
    <div class="stat-box" style="border-left: 5px solid <?php echo $pl_color; ?>">
        <span class="stat-label">P/L Today</span>
        <span class="stat-val" id="dash-pl" style="color:<?php echo $pl_color; ?>"><?php echo number_format($display_pl, 2); ?></span>
    </div>
    <div class="stat-box">
        <span class="stat-label">Wins</span>
        <span class="stat-val green" id="dash-w"><?php echo intval($stats->wins); ?></span>
    </div>
    <div class="stat-box">
        <span class="stat-label">Losses</span>
        <span class="stat-val red" id="dash-l"><?php echo intval($stats->losses); ?></span>
    </div>
    <div class="stat-box">
        <span class="stat-label">Pending</span>
        <span class="stat-val blue" id="dash-p"><?php echo intval($stats->pending); ?></span>
    </div>
</div>