<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TSB_Admin_Settings {

    public function render() {
        // Save Logic
        if ( isset( $_POST['tsb_save_settings'] ) && check_admin_referer( 'tsb_settings_nonce' ) ) {
            
            // General
            update_option( 'tsb_bot_token', sanitize_text_field( $_POST['tsb_bot_token'] ) );
            
            // Permissions & UI (Strict Isset Check)
            update_option( 'tsb_allow_edit', isset($_POST['tsb_allow_edit']) ? 1 : 0 );
            update_option( 'tsb_allow_entry_update', isset($_POST['tsb_allow_entry_update']) ? 1 : 0 );
            update_option( 'tsb_show_high_pl', isset($_POST['tsb_show_high_pl']) ? 1 : 0 );
            update_option( 'tsb_form_layout_open', isset($_POST['tsb_form_layout_open']) ? 1 : 0 );
            
            // Message Toggles (Strict Isset Check)
            update_option( 'tsb_msg_on_new', isset($_POST['tsb_msg_on_new']) ? 1 : 0 );
            update_option( 'tsb_msg_on_active', isset($_POST['tsb_msg_on_active']) ? 1 : 0 );
            update_option( 'tsb_msg_on_update', isset($_POST['tsb_msg_on_update']) ? 1 : 0 );
            update_option( 'tsb_msg_on_target', isset($_POST['tsb_msg_on_target']) ? 1 : 0 );
            update_option( 'tsb_msg_on_sl', isset($_POST['tsb_msg_on_sl']) ? 1 : 0 );
            update_option( 'tsb_msg_on_high', isset($_POST['tsb_msg_on_high']) ? 1 : 0 );

            // Calculations
            update_option( 'tsb_pl_multiplier', floatval( $_POST['tsb_pl_multiplier'] ) );
            update_option( 'tsb_calc_t1', floatval( $_POST['tsb_calc_t1'] ) );
            update_option( 'tsb_calc_t2', floatval( $_POST['tsb_calc_t2'] ) );
            update_option( 'tsb_calc_t3', floatval( $_POST['tsb_calc_t3'] ) );
            
            // Templates
            update_option( 'tsb_tpl_new', wp_kses_post( $_POST['tsb_tpl_new'] ) );
            update_option( 'tsb_tpl_update', wp_kses_post( $_POST['tsb_tpl_update'] ) );
            update_option( 'tsb_tpl_target', wp_kses_post( $_POST['tsb_tpl_target'] ) );
            update_option( 'tsb_tpl_sl', wp_kses_post( $_POST['tsb_tpl_sl'] ) );
            update_option( 'tsb_tpl_high', wp_kses_post( $_POST['tsb_tpl_high'] ) );
            update_option( 'tsb_tpl_active', wp_kses_post( $_POST['tsb_tpl_active'] ) );

            // Channels
            if( isset( $_POST['channel_name'] ) ) {
                $channels = array();
                $names = $_POST['channel_name']; 
                $ids = $_POST['channel_id']; 
                $limits = $_POST['channel_limit'];
                
                for($i=0; $i < count($names); $i++) {
                    if(!empty($names[$i])) {
                        $channels[] = array(
                            'name' => sanitize_text_field($names[$i]), 
                            'id' => sanitize_text_field($ids[$i]), 
                            'limit' => intval($limits[$i])
                        );
                    }
                }
                update_option( 'tsb_channels_list', $channels );
            } else { 
                update_option( 'tsb_channels_list', array() ); 
            }
            
            echo '<div class="notice notice-success is-dismissible"><p>Settings Saved.</p></div>';
        }

        // Default Templates
        $def_new = "🚀 *NEW TRADE* 🚀\n*Symbol:* {symbol}\n*Entry:* {entry}\n*SL:* {sl}\n*Targets:* {t1} | {t2} | {t3}";
        $def_update = "🔄 *UPDATE ENTRY*\nNew Entry: {entry}\nNew SL: {sl}\nT1: {t1} | T2: {t2}";
        $def_target = "✅ *{target} HIT*\n{symbol}\nPrice: {price}\n💰 P/L: {pl}";
        $def_sl = "🛑 *STOP LOSS HIT*\n{symbol}\nPrice: {price}\n💰 P/L: {pl}";
        $def_high = "🔥 *NEW HIGH MADE*\n{symbol}\nHigh: {price}";
        $def_active = "🔵 *TRADE ACTIVE*\n{symbol}\nEntry Triggered at: {entry}";

        ?>
        <div style="background: #fff; padding: 20px;">
            <form method="post" action="">
                <?php wp_nonce_field( 'tsb_settings_nonce' ); ?>
                <h3>General Configuration</h3>
                <p><label><b>Bot Token</b></label><br><input name="tsb_bot_token" type="text" value="<?php echo esc_attr( get_option('tsb_bot_token') ); ?>" class="large-text code"></p>
                
                <div style="display:flex; gap:20px; margin-bottom:10px;">
                    <div><label><b>P/L Multiplier</b></label><br><input type="number" name="tsb_pl_multiplier" value="<?php echo get_option('tsb_pl_multiplier', 6); ?>" style="width:80px;"></div>
                </div>
                
                <p>
                    <label><input type="checkbox" name="tsb_allow_edit" value="1" <?php checked( get_option('tsb_allow_edit', 0), 1 ); ?>> Allow Delete in Journal</label><br>
                    <label><input type="checkbox" name="tsb_allow_entry_update" value="1" <?php checked( get_option('tsb_allow_entry_update', 0), 1 ); ?>> Allow Update Entry</label><br>
                    <label><input type="checkbox" name="tsb_show_high_pl" value="1" <?php checked( get_option('tsb_show_high_pl', 0), 1 ); ?>> Show P/L in High Msg</label><br>
                    <label><input type="checkbox" name="tsb_form_layout_open" value="1" <?php checked( get_option('tsb_form_layout_open', 0), 1 ); ?>> Form Details Open Default</label>
                </p>
                
                <hr>
                <h3>Telegram Message Toggles</h3>
                <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:10px; max-width:600px;">
                    <label><input type="checkbox" name="tsb_msg_on_new" value="1" <?php checked( get_option('tsb_msg_on_new', 1), 1 ); ?>> New Trade</label>
                    <label><input type="checkbox" name="tsb_msg_on_active" value="1" <?php checked( get_option('tsb_msg_on_active', 1), 1 ); ?>> Active</label>
                    <label><input type="checkbox" name="tsb_msg_on_update" value="1" <?php checked( get_option('tsb_msg_on_update', 1), 1 ); ?>> Update Entry</label>
                    <label><input type="checkbox" name="tsb_msg_on_target" value="1" <?php checked( get_option('tsb_msg_on_target', 1), 1 ); ?>> Targets</label>
                    <label><input type="checkbox" name="tsb_msg_on_sl" value="1" <?php checked( get_option('tsb_msg_on_sl', 1), 1 ); ?>> Stop Loss</label>
                    <label><input type="checkbox" name="tsb_msg_on_high" value="1" <?php checked( get_option('tsb_msg_on_high', 1), 1 ); ?>> High Made</label>
                </div>

                <hr>
                <h3>Message Templates</h3>
                <table class="form-table">
                    <tr><th>New Trade</th><td><textarea name="tsb_tpl_new" rows="3" class="large-text code"><?php echo esc_textarea(get_option('tsb_tpl_new', $def_new)); ?></textarea></td></tr>
                    <tr><th>Entry Update</th><td><textarea name="tsb_tpl_update" rows="2" class="large-text code"><?php echo esc_textarea(get_option('tsb_tpl_update', $def_update)); ?></textarea></td></tr>
                    <tr><th>Trade Active</th><td><textarea name="tsb_tpl_active" rows="2" class="large-text code"><?php echo esc_textarea(get_option('tsb_tpl_active', $def_active)); ?></textarea></td></tr>
                    <tr><th>Target Hit</th><td><textarea name="tsb_tpl_target" rows="2" class="large-text code"><?php echo esc_textarea(get_option('tsb_tpl_target', $def_target)); ?></textarea></td></tr>
                    <tr><th>Stop Loss</th><td><textarea name="tsb_tpl_sl" rows="2" class="large-text code"><?php echo esc_textarea(get_option('tsb_tpl_sl', $def_sl)); ?></textarea></td></tr>
                    <tr><th>High Made</th><td><textarea name="tsb_tpl_high" rows="2" class="large-text code"><?php echo esc_textarea(get_option('tsb_tpl_high', $def_high)); ?></textarea></td></tr>
                </table>

                <hr>
                <h3>Multipliers</h3>
                <div style="display:flex; gap:10px;">
                    <div>T1: <input type="number" step="0.1" name="tsb_calc_t1" value="<?php echo get_option('tsb_calc_t1', 0.5); ?>" style="width:60px"></div>
                    <div>T2: <input type="number" step="0.1" name="tsb_calc_t2" value="<?php echo get_option('tsb_calc_t2', 1.0); ?>" style="width:60px"></div>
                    <div>T3: <input type="number" step="0.1" name="tsb_calc_t3" value="<?php echo get_option('tsb_calc_t3', 1.5); ?>" style="width:60px"></div>
                </div>

                <hr>
                <h3>Channels</h3>
                <table class="widefat striped">
                    <thead><tr><th>Name</th><th>Chat ID</th><th>Limit</th><th>X</th></tr></thead>
                    <tbody id="channels-body">
                        <?php $channels = get_option('tsb_channels_list', array()); foreach($channels as $c): ?>
                            <tr>
                                <td><input type="text" name="channel_name[]" value="<?php echo esc_attr($c['name']); ?>"></td>
                                <td><input type="text" name="channel_id[]" value="<?php echo esc_attr($c['id']); ?>"></td>
                                <td><input type="number" name="channel_limit[]" value="<?php echo esc_attr($c['limit']); ?>" style="width:60px"></td>
                                <td><button type="button" class="button remove-row">X</button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p><button type="button" class="button" id="add-channel-row">+ Add Channel</button></p>
                <p class="submit"><input type="submit" name="tsb_save_settings" class="button button-primary" value="Save All Settings"></p>
            </form>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $('#add-channel-row').click(function() { $('#channels-body').append('<tr><td><input type="text" name="channel_name[]"></td><td><input type="text" name="channel_id[]"></td><td><input type="number" name="channel_limit[]" value="10"></td><td><button type="button" class="button remove-row">X</button></td></tr>'); });
                $(document).on('click', '.remove-row', function() { $(this).closest('tr').remove(); });
            });
        </script>
        <?php
    }
}