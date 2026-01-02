<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TSB_Ajax_Loader {
    public function __construct() {
        // Load Stock Logic
        require_once TSB_PLUGIN_DIR . 'includes/ajax/class-ajax-stocks.php';
        new TSB_Ajax_Stocks();

        // Load Telegram Logic
        require_once TSB_PLUGIN_DIR . 'includes/ajax/class-ajax-telegram.php';
        new TSB_Ajax_Telegram();

        // Load Trade Logic (Split)
        require_once TSB_PLUGIN_DIR . 'includes/ajax/class-ajax-trade-submit.php';
        new TSB_Ajax_Trade_Submit();

        require_once TSB_PLUGIN_DIR . 'includes/ajax/class-ajax-trade-update.php';
        new TSB_Ajax_Trade_Update();
    }
}