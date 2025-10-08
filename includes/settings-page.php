<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Register settings
function custom_lottery_register_settings() {
    register_setting('custom_lottery_settings_group', 'custom_lottery_payout_rate');
    register_setting('custom_lottery_settings_group', 'custom_lottery_api_url_live');
    register_setting('custom_lottery_settings_group', 'custom_lottery_api_url_historical');
    register_setting('custom_lottery_settings_group', 'custom_lottery_session_times');
    register_setting('custom_lottery_settings_group', 'custom_lottery_number_limit');
}
add_action('admin_init', 'custom_lottery_register_settings');

// Add settings page to menu
function custom_lottery_add_settings_page() {
    add_submenu_page(
        'custom-lottery-main',
        'Lottery Settings',
        'Settings',
        'manage_options',
        'custom-lottery-settings',
        'custom_lottery_settings_page_callback'
    );
}
add_action('admin_menu', 'custom_lottery_add_settings_page');

// Settings page callback
function custom_lottery_settings_page_callback() {
    ?>
    <div class="wrap">
        <h1>Lottery Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('custom_lottery_settings_group');
            do_settings_sections('custom_lottery_settings_group');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Payout Rate</th>
                    <td><input type="number" name="custom_lottery_payout_rate" value="<?php echo esc_attr(get_option('custom_lottery_payout_rate', 80)); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Live API URL</th>
                    <td><input type="text" name="custom_lottery_api_url_live" value="<?php echo esc_attr(get_option('custom_lottery_api_url_live', 'https://api.thaistock2d.com/live')); ?>" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Historical API URL</th>
                    <td><input type="text" name="custom_lottery_api_url_historical" value="<?php echo esc_attr(get_option('custom_lottery_api_url_historical', 'https://api.thaistock2d.com/2d_result')); ?>" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Default Session Times</th>
                    <td>
                        <label>Morning Session (HH:MM)</label><br/>
                        <input type="time" name="custom_lottery_session_times[morning_open]" value="<?php echo esc_attr(get_option('custom_lottery_session_times')['morning_open'] ?? '09:30'); ?>" />
                        <input type="time" name="custom_lottery_session_times[morning_close]" value="<?php echo esc_attr(get_option('custom_lottery_session_times')['morning_close'] ?? '12:00'); ?>" /><br/>
                        <label>Evening Session (HH:MM)</label><br/>
                        <input type="time" name="custom_lottery_session_times[evening_open]" value="<?php echo esc_attr(get_option('custom_lottery_session_times')['evening_open'] ?? '14:00'); ?>" />
                        <input type="time" name="custom_lottery_session_times[evening_close]" value="<?php echo esc_attr(get_option('custom_lottery_session_times')['evening_close'] ?? '16:30'); ?>" />
                    </td>
                </tr>
                 <tr valign="top">
                    <th scope="row">Default Number Limit</th>
                    <td><input type="number" name="custom_lottery_number_limit" value="<?php echo esc_attr(get_option('custom_lottery_number_limit', 5000)); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}