<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Get the winning numbers for today's sessions.
 *
 * @return array
 */
function custom_lottery_get_todays_winning_numbers() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'lotto_winning_numbers';
    $today = (new DateTime('now', new DateTimeZone('Asia/Yangon')))->format('Y-m-d');

    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT session, number FROM {$table_name} WHERE DATE(timestamp) = %s",
        $today
    ));

    $winning_numbers = [
        'morning' => '--',
        'evening' => '--',
    ];

    foreach ($results as $result) {
        if ($result->session === '12:01 PM') {
            $winning_numbers['morning'] = $result->number;
        } elseif ($result->session === '4:30 PM') {
            $winning_numbers['evening'] = $result->number;
        }
    }

    return $winning_numbers;
}

/**
 * Get the live sales data for the current active session.
 *
 * @return array
 */
function custom_lottery_get_live_sales_data() {
    global $wpdb;
    $table_entries = $wpdb->prefix . 'lotto_entries';
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

    $total_sales = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(amount) FROM {$table_entries} WHERE draw_session = %s AND timestamp BETWEEN %s AND %s",
        $current_session,
        $today_start,
        $today_end
    ));

    return [
        'session' => $current_session,
        'total_sales' => $total_sales ? (int) $total_sales : 0,
    ];
}

/**
 * Get the top 5 hot numbers for the current day.
 *
 * @return array
 */
function custom_lottery_get_top_hot_numbers() {
    global $wpdb;
    $table_entries = $wpdb->prefix . 'lotto_entries';
    $timezone = new DateTimeZone('Asia/Yangon');
    $today_start = (new DateTime('now', $timezone))->format('Y-m-d 00:00:00');
    $today_end = (new DateTime('now', $timezone))->format('Y-m-d 23:59:59');

    $hot_numbers = $wpdb->get_results($wpdb->prepare(
        "SELECT lottery_number, COUNT(id) as purchase_count
         FROM {$table_entries}
         WHERE timestamp BETWEEN %s AND %s
         GROUP BY lottery_number
         ORDER BY purchase_count DESC
         LIMIT 5",
        $today_start,
        $today_end
    ));

    return $hot_numbers;
}