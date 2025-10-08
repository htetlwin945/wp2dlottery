<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Get the live sales data for the current active session from the local database.
 *
 * @return array
 */
function custom_lottery_get_live_sales_data() {
    global $wpdb;
    $table_entries = $wpdb->prefix . 'lotto_entries';
    $table_agents = $wpdb->prefix . 'lotto_agents';
    $timezone = new DateTimeZone('Asia/Yangon');
    $current_time = new DateTime('now', $timezone);

    $current_session = custom_lottery_get_current_session();

    if (!$current_session) {
        return [
            'session' => 'Closed',
            'total_sales' => 0,
        ];
    }

    $today_start = $current_time->format('Y-m-d 00:00:00');
    $today_end = $current_time->format('Y-m-d 23:59:59');

    $query = "SELECT SUM(amount) FROM {$table_entries} WHERE draw_session = %s AND timestamp BETWEEN %s AND %s";
    $params = [$current_session, $today_start, $today_end];

    $current_user = wp_get_current_user();
    if (in_array('commission_agent', (array) $current_user->roles) && get_option('custom_lottery_enable_commission_agent_system')) {
        $agent_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_agents WHERE user_id = %d", $current_user->ID));
        if ($agent_id) {
            $query .= " AND agent_id = %d";
            $params[] = $agent_id;
        } else {
            return ['session' => $current_session, 'total_sales' => 0];
        }
    }

    $total_sales = $wpdb->get_var($wpdb->prepare($query, ...$params));

    return [
        'session' => $current_session,
        'total_sales' => $total_sales ? (int) $total_sales : 0,
    ];
}

/**
 * Get the top 5 hot numbers for the current day from the local database.
 *
 * @return array
 */
function custom_lottery_get_top_hot_numbers() {
    global $wpdb;
    $table_entries = $wpdb->prefix . 'lotto_entries';
    $table_agents = $wpdb->prefix . 'lotto_agents';
    $timezone = new DateTimeZone('Asia/Yangon');
    $today_start = (new DateTime('now', $timezone))->format('Y-m-d 00:00:00');
    $today_end = (new DateTime('now', $timezone))->format('Y-m-d 23:59:59');

    $query = "SELECT lottery_number, COUNT(id) as purchase_count FROM {$table_entries} WHERE timestamp BETWEEN %s AND %s";
    $params = [$today_start, $today_end];

    $current_user = wp_get_current_user();
    if (in_array('commission_agent', (array) $current_user->roles) && get_option('custom_lottery_enable_commission_agent_system')) {
        $agent_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_agents WHERE user_id = %d", $current_user->ID));
        if ($agent_id) {
            $query .= " AND agent_id = %d";
            $params[] = $agent_id;
        } else {
            return []; // No agent record, so no hot numbers
        }
    }

    $query .= " GROUP BY lottery_number ORDER BY purchase_count DESC LIMIT 5";

    $hot_numbers = $wpdb->get_results($wpdb->prepare($query, ...$params));

    return $hot_numbers;
}

/**
 * Add all dashboard widgets.
 */
function custom_lottery_add_dashboard_widgets() {
    // Add screen option for layout columns
    add_screen_option(
        'layout_columns',
        array(
            'max'     => 4,
            'default' => 2,
        )
    );

    $screen_id = get_current_screen()->id;

    // Main (Normal) Column Widgets
    add_meta_box('custom_lottery_charts_widget', __('Sales & Profit Charts', 'custom-lottery'), 'custom_lottery_render_charts_widget', $screen_id, 'normal', 'high');
    add_meta_box('custom_lottery_winning_numbers_history_widget', __('Winning Numbers History', 'custom-lottery'), 'custom_lottery_render_winning_numbers_history_widget_callback', $screen_id, 'normal', 'default');

    // Side Column Widgets
    add_meta_box('custom_lottery_winning_numbers_widget', __('Today\'s Winning Numbers (API)', 'custom-lottery'), 'custom_lottery_render_winning_numbers_widget', $screen_id, 'side', 'high');
    add_meta_box('custom_lottery_live_market_data_widget', __('Live Market Data (API)', 'custom-lottery'), 'custom_lottery_render_live_market_data_widget', $screen_id, 'side', 'high');
    add_meta_box('custom_lottery_live_sales_widget', __('Live Sales Ticker (Local)', 'custom-lottery'), 'custom_lottery_render_live_sales_widget', $screen_id, 'side', 'default');
    add_meta_box('custom_lottery_hot_numbers_widget', __('Today\'s Top 5 Hot Numbers (Local)', 'custom-lottery'), 'custom_lottery_render_hot_numbers_widget', $screen_id, 'side', 'default');
}

/**
 * Render the 'Today's Winning Numbers' widget content.
 */
function custom_lottery_render_winning_numbers_widget() {
    ?>
    <p><strong><?php echo esc_html__('Morning (12:01 PM):', 'custom-lottery'); ?></strong> <span id="winning-number-morning">--</span></p>
    <p><strong><?php echo esc_html__('Evening (4:30 PM):', 'custom-lottery'); ?></strong> <span id="winning-number-evening">--</span></p>
    <?php
}

/**
 * Render the 'Live Market Data' widget content.
 */
function custom_lottery_render_live_market_data_widget() {
    ?>
    <p><strong><?php echo esc_html__('SET Index:', 'custom-lottery'); ?></strong> <span id="live-set-index">--</span></p>
    <p><strong><?php echo esc_html__('Value:', 'custom-lottery'); ?></strong> <span id="live-value">--</span></p>
    <p><strong><?php echo esc_html__('2D:', 'custom-lottery'); ?></strong> <span id="live-twod">--</span></p>
    <?php
}

/**
 * Render the 'Live Sales Ticker' widget content.
 */
function custom_lottery_render_live_sales_widget() {
    ?>
    <p><strong><?php echo esc_html__('Current Session:', 'custom-lottery'); ?></strong> <span id="live-sales-session">--</span></p>
    <p><strong><?php echo esc_html__('Total Sales:', 'custom-lottery'); ?></strong> <span id="live-sales-total">0</span> Kyat</p>
    <?php
}

/**
 * Render the 'Top 5 Hot Numbers' widget content.
 */
function custom_lottery_render_hot_numbers_widget() {
    ?>
    <ul id="hot-numbers-list" style="margin-top: 0;">
        <li><?php echo esc_html__('Loading...', 'custom-lottery'); ?></li>
    </ul>
    <?php
}

/**
 * Render the 'Winning Numbers History' widget content.
 */
function custom_lottery_render_winning_numbers_history_widget_callback() {
    custom_lottery_render_winning_numbers_history_widget();
}

/**
 * Render the 'Sales & Profit Charts' widget content.
 */
function custom_lottery_render_charts_widget() {
    ?>
    <div class="dashboard-controls">
        <label for="dashboard-range-selector"><?php echo esc_html__('Select Date Range:', 'custom-lottery'); ?></label>
        <select id="dashboard-range-selector">
            <option value="last_7_days" selected><?php echo esc_html__('Last 7 Days', 'custom-lottery'); ?></option>
            <option value="this_month"><?php echo esc_html__('This Month', 'custom-lottery'); ?></option>
        </select>
        <?php wp_nonce_field('dashboard_nonce', 'dashboard_nonce'); ?>
    </div>

    <div class="dashboard-charts" style="width: 100%; margin-top: 20px;">
        <div class="chart-container" style="position: relative; height:40vh; width:100%; margin-bottom: 40px;">
            <h2><?php echo esc_html__('Sales vs. Payouts', 'custom-lottery'); ?></h2>
            <canvas id="salesPayoutsChart"></canvas>
        </div>
        <div class="chart-container" style="position: relative; height:40vh; width:100%;">
            <h2><?php echo esc_html__('Net Profit Over Time', 'custom-lottery'); ?></h2>
            <canvas id="netProfitChart"></canvas>
        </div>
    </div>
    <?php
}