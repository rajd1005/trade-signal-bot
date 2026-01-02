<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TSB_DB_Manager {

    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_stocks = $wpdb->prefix . 'tsb_master_stocks';
        $sql_stocks = "CREATE TABLE $table_stocks (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            symbol_name varchar(50) NOT NULL,
            lot_size int NOT NULL DEFAULT 1,
            stop_loss float NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $table_journal = $wpdb->prefix . 'tsb_trade_journal';
        $sql_journal = "CREATE TABLE $table_journal (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            entry_date datetime DEFAULT CURRENT_TIMESTAMP,
            channel_name varchar(50) NOT NULL,
            symbol_display varchar(100) NOT NULL,
            lot_size int NOT NULL DEFAULT 1,
            entry_price float NOT NULL,
            sl_price float NOT NULL,
            t1 float NOT NULL,
            t2 float NOT NULL,
            t3 float NOT NULL,
            high_price float DEFAULT 0,
            low_price float DEFAULT 0,
            profit_loss float DEFAULT 0,
            trade_status varchar(20) DEFAULT 'Pending',
            telegram_msg_id varchar(50) DEFAULT NULL,
            telegram_chat_id varchar(50) DEFAULT NULL,
            reply_ids text DEFAULT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql_stocks );
        dbDelta( $sql_journal );
    }
}