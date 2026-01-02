<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TSB_Admin_Master_DB {

    public function render() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tsb_master_stocks';
        
        $edit_mode = false;
        $edit_data = null;

        // Edit
        if ( isset( $_GET['edit'] ) ) {
            $edit_id = intval( $_GET['edit'] );
            $edit_data = $wpdb->get_row( "SELECT * FROM $table_name WHERE id = $edit_id" );
            if ( $edit_data ) $edit_mode = true;
        }

        // Save
        if ( isset( $_POST['tsb_save_stock'] ) && check_admin_referer( 'tsb_save_stock_nonce' ) ) {
            $symbol = sanitize_text_field( $_POST['symbol'] );
            $lot = intval( $_POST['lot'] );
            $sl = floatval( $_POST['sl'] );
            
            if ( ! empty( $_POST['stock_id'] ) ) {
                $wpdb->update( $table_name, array( 'symbol_name' => $symbol, 'lot_size' => $lot, 'stop_loss' => $sl ), array( 'id' => intval( $_POST['stock_id'] ) ) );
                echo '<div class="notice notice-success is-dismissible"><p>Stock Updated.</p></div>';
                $edit_mode = false; $edit_data = null;
            } else {
                $wpdb->insert( $table_name, array( 'symbol_name' => $symbol, 'lot_size' => $lot, 'stop_loss' => $sl ) );
                echo '<div class="notice notice-success is-dismissible"><p>Stock Added.</p></div>';
            }
        }

        // Delete
        if ( isset( $_GET['delete'] ) ) {
            $wpdb->delete( $table_name, array( 'id' => intval( $_GET['delete'] ) ) );
            echo '<div class="notice notice-success is-dismissible"><p>Stock Deleted.</p></div>';
        }

        // Import
        if ( isset( $_POST['tsb_bulk_import'] ) && check_admin_referer( 'tsb_bulk_import_nonce' ) ) {
             $defaults = array(
                array('MUTHOOTFIN', 275, 5), array('APOLLOHOSP', 125, 15), array('BAJAJFINSV', 250, 4),
                array('HCLTECH', 350, 4), array('HDFCBANK', 550, 4), array('AXISBANK', 625, 3),
                array('DIVISLAB', 100, 13), array('EICHERMOT', 100, 13), array('LT', 175, 7),
                array('RELIANCE', 500, 8), array('TCS', 175, 13), array('SIEMENS', 175, 8),
                array('ACC', 300, 8), array('NATURALGAS', 1250, 5), array('CRUDEOIL', 100, 18),
                array('BANKNIFTY', 30, 50), array('NIFTY50', 65, 20), array('SENSEX', 20, 55)
            );
            foreach($defaults as $item) {
                $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_name WHERE symbol_name = %s", $item[0] ) );
                if( ! $exists ) $wpdb->insert( $table_name, array( 'symbol_name' => $item[0], 'lot_size' => $item[1], 'stop_loss' => $item[2] ) );
            }
            echo '<div class="notice notice-success is-dismissible"><p>Bulk Stocks Imported.</p></div>';
        }

        $stocks = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY symbol_name ASC" );
        ?>
        <div style="margin-top: 20px; background: #fff; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3><?php echo $edit_mode ? 'Edit Stock' : 'Add New Stock'; ?></h3>
            <form method="post" action="?page=tsb-master-db&tab=master_db">
                <?php wp_nonce_field( 'tsb_save_stock_nonce' ); ?>
                <input type="hidden" name="stock_id" value="<?php echo $edit_mode ? esc_attr($edit_data->id) : ''; ?>">
                <div style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                    <div style="flex: 1;"><label>Symbol</label><input type="text" name="symbol" value="<?php echo $edit_mode ? esc_attr($edit_data->symbol_name) : ''; ?>" class="regular-text" style="width: 100%;" required></div>
                    <div style="width: 120px;"><label>Lot</label><input type="number" name="lot" value="<?php echo $edit_mode ? esc_attr($edit_data->lot_size) : ''; ?>" style="width: 100%;" required></div>
                    <div style="width: 120px;"><label>SL</label><input type="number" step="0.01" name="sl" value="<?php echo $edit_mode ? esc_attr($edit_data->stop_loss) : ''; ?>" style="width: 100%;" required></div>
                    <div><input type="submit" name="tsb_save_stock" class="button button-primary" value="<?php echo $edit_mode ? 'Update' : 'Add'; ?>"></div>
                </div>
            </form>
            <?php if(!$edit_mode): ?>
                <div style="margin-top: 10px; text-align: right;"><form method="post" action=""><input type="hidden" name="tsb_bulk_import_nonce" value="<?php echo wp_create_nonce('tsb_bulk_import_nonce'); ?>"><input type="submit" name="tsb_bulk_import" class="button" value="Import Defaults"></form></div>
            <?php endif; ?>
        </div>
        <table class="wp-list-table widefat fixed striped" style="margin-top:20px;">
            <thead><tr><th>Symbol</th><th>Lot</th><th>SL</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach($stocks as $s): ?>
                <tr><td><?php echo $s->symbol_name; ?></td><td><?php echo $s->lot_size; ?></td><td><?php echo $s->stop_loss; ?></td>
                <td><a href="?page=tsb-master-db&tab=master_db&edit=<?php echo $s->id; ?>">Edit</a> | <a href="?page=tsb-master-db&tab=master_db&delete=<?php echo $s->id; ?>" style="color:#a00;">Delete</a></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
}