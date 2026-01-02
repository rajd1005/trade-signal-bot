<?php
/**
 * Plugin Name: Trade Signal Bot
 * Description: Modular Trade Journal and Signal Bot.
 * Version: 2.2
 * Author: Your Name
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'TSB_VERSION', '2.2' );
define( 'TSB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TSB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// 1. Load Core Helpers
require_once TSB_PLUGIN_DIR . 'includes/class-db-manager.php';
require_once TSB_PLUGIN_DIR . 'includes/class-telegram-api.php';

// 2. Load AJAX Controller
require_once TSB_PLUGIN_DIR . 'includes/class-ajax-loader.php';

// 3. Load Admin Classes (CRITICAL: Load all 3 files)
require_once TSB_PLUGIN_DIR . 'admin/class-admin-master-db.php';  // <--- Was missing/not loaded
require_once TSB_PLUGIN_DIR . 'admin/class-admin-settings.php';   // <--- Was missing/not loaded
require_once TSB_PLUGIN_DIR . 'admin/class-admin-manager.php';

// 4. Load Frontend Controller
require_once TSB_PLUGIN_DIR . 'public/class-frontend-ui.php';

// Activation Hook
register_activation_hook( __FILE__, array( 'TSB_DB_Manager', 'create_tables' ) );

// Initialize Plugin
function tsb_init_plugin() {
    
    // Initialize Admin UI only in WP Admin
    if ( is_admin() ) {
        new TSB_Admin_Manager();
    }
    
    // Initialize Ajax Listeners (Always needed for ajax requests)
    new TSB_Ajax_Loader();
    
    // Initialize Frontend UI
    new TSB_Frontend_UI();
}
add_action( 'plugins_loaded', 'tsb_init_plugin' );