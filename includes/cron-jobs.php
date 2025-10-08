<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Fetches winning numbers from the API and stores them in the database.
 * It also identifies the winning entries for the fetched session.
 */
function custom_lottery_fetch_winning_numbers() {
    global $wpdb;
    $table_winning_numbers = $wpdb->prefix . 'lotto_winning_numbers';
    $api_url = get_option('custom_lottery_api_url_live', 'https://api.thaistock2d.com/live');

    // Fetch data from the API
    $response = wp_remote_get($api_url, ['timeout' => 15]);

    // Handle API errors
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        error_log('Lottery Plugin API Error: ' . (is_wp_error($response) ? $response->get_error_message() : 'Invalid response code.'));
        return;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Handle invalid data format
    if (empty($data['result']) || !is_array($data['result'])) {
        error_log('Lottery Plugin API Error: Invalid data format received.');
        return;
    }

    $timezone = new DateTimeZone('Asia/Yangon');
    $current_date_str = (new DateTime('now', $timezone))->format('Y-m-d');

    foreach ($data['result'] as $result) {
        $session_time = $result['open_time'];
        $session_map = [
            '12:01:00' => '12:01 PM',
            '16:30:00' => '4:30 PM',
        ];

        // Check if the result is for a session we care about
        if (isset($session_map[$session_time])) {
            $session_label = $session_map[$session_time];
            $winning_number = sanitize_text_field($result['twod']);

            // Attempt to insert the new winning number.
            // The unique key on (draw_date, draw_session) will prevent duplicates.
            $inserted = $wpdb->insert(
                $table_winning_numbers,
                [
                    'winning_number' => $winning_number,
                    'draw_date'      => $current_date_str,
                    'draw_session'   => $session_label,
                ],
                ['%s', '%s', '%s']
            );

            // If the insert was successful (a new row was added), find the winners.
            if ($inserted) {
                custom_lottery_identify_winners($session_label, $winning_number, $current_date_str);
            }
        }
    }
}

/**
 * Identifies and flags winning entries in the database.
 * This fixes the bug from the previous implementation.
 */
function custom_lottery_identify_winners($session, $winning_number, $date) {
    global $wpdb;
    $table_entries = $wpdb->prefix . 'lotto_entries';

    $start_datetime = $date . ' 00:00:00';
    $end_datetime = $date . ' 23:59:59';

    // The WHERE clause is now correctly constructed within the $wpdb->prepare call.
    $wpdb->query($wpdb->prepare(
        "UPDATE $table_entries SET is_winner = 1 WHERE lottery_number = %s AND draw_session = %s AND timestamp BETWEEN %s AND %s",
        $winning_number,
        $session,
        $start_datetime,
        $end_datetime
    ));
}

/**
 * Schedule cron jobs.
 */
function custom_lottery_schedule_cron_jobs() {
    $session_times = custom_lottery_get_session_times();

    if (!wp_next_scheduled('custom_lottery_fetch_1201')) {
        $morning_cron_time_str = $session_times['morning_close'];
        $time = new DateTime($morning_cron_time_str, new DateTimeZone('Asia/Yangon'));
        $time->modify('+2 minutes'); // Run 2 minutes after closing
        $time->setTimezone(new DateTimeZone('UTC'));
        wp_schedule_event($time->getTimestamp(), 'daily', 'custom_lottery_fetch_1201');
    }
    if (!wp_next_scheduled('custom_lottery_fetch_1630')) {
        $evening_cron_time_str = $session_times['evening_close'];
        $time = new DateTime($evening_cron_time_str, new DateTimeZone('Asia/Yangon'));
        $time->modify('+2 minutes'); // Run 2 minutes after closing
        $time->setTimezone(new DateTimeZone('UTC'));
        wp_schedule_event($time->getTimestamp(), 'daily', 'custom_lottery_fetch_1630');
    }
}
add_action('init', 'custom_lottery_schedule_cron_jobs');
add_action('custom_lottery_fetch_1201', 'custom_lottery_fetch_winning_numbers');
add_action('custom_lottery_fetch_1630', 'custom_lottery_fetch_winning_numbers');

/**
 * Clear cron jobs on deactivation.
 */
function custom_lottery_clear_cron_jobs() {
    wp_clear_scheduled_hook('custom_lottery_fetch_1201');
    wp_clear_scheduled_hook('custom_lottery_fetch_1630');
}