<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TSB_Admin_Manager {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
    }

    public function add_menu() {
        add_menu_page( 'Trade Bot', 'Trade Bot', 'manage_options', 'tsb-master-db', array( $this, 'render_wrapper' ), 'dashicons-chart-line', 6 );
    }

    public function render_wrapper() {
        $active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'master_db';
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Trade Signal Bot</h1>
            <hr class="wp-header-end">
            
            <h2 class="nav-tab-wrapper">
                <a href="?page=tsb-master-db&tab=master_db" class="nav-tab <?php echo $active_tab == 'master_db' ? 'nav-tab-active' : ''; ?>">Master DB</a>
                <a href="?page=tsb-master-db&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">Settings & Templates</a>
            </h2>

            <?php
            if ( $active_tab == 'master_db' ) {
                $db_page = new TSB_Admin_Master_DB();
                $db_page->render();
            } else {
                $settings_page = new TSB_Admin_Settings();
                $settings_page->render();
            }
            ?>
        </div>
        <?php
    }
}