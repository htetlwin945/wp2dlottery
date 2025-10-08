<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Logs a specific admin action to the audit log table.
 */
function custom_lottery_log_action($action, $details) {
    global $wpdb;
    $table_audit = $wpdb->prefix . 'lotto_audit_log';

    $wpdb->insert($table_audit, [
        'user_id' => get_current_user_id(),
        'action' => $action,
        'details' => wp_json_encode($details),
        'timestamp' => current_time('mysql'),
    ]);
}

/**
 * Creates or updates a customer in the database based on the phone number.
 */
function custom_lottery_update_or_create_customer($name, $phone, $agent_id = null) {
    global $wpdb;
    $table_customers = $wpdb->prefix . 'lotto_customers';

    if ($agent_id) {
        $wpdb->query($wpdb->prepare(
            "INSERT INTO $table_customers (customer_name, phone, last_seen, agent_id) VALUES (%s, %s, %s, %d)
             ON DUPLICATE KEY UPDATE customer_name = %s, last_seen = %s, agent_id = IF(agent_id IS NULL, %d, agent_id)",
            $name, $phone, current_time('mysql'), $agent_id,
            $name, current_time('mysql'), $agent_id
        ));
    } else {
        $wpdb->query($wpdb->prepare(
            "INSERT INTO $table_customers (customer_name, phone, last_seen) VALUES (%s, %s, %s)
             ON DUPLICATE KEY UPDATE customer_name = %s, last_seen = %s",
            $name, $phone, current_time('mysql'),
            $name, current_time('mysql')
        ));
    }
}

/**
 * Checks if a number's total purchased amount exceeds the custom limit and blocks it if necessary.
 */
function check_and_auto_block_number($number, $session, $date) {
    global $wpdb;
    $table_entries = $wpdb->prefix . 'lotto_entries';
    $table_limits = $wpdb->prefix . 'lotto_limits';
    $table_agents = $wpdb->prefix . 'lotto_agents';

    if (!get_option('custom_lottery_enable_auto_blocking')) {
        return;
    }

    $current_user = wp_get_current_user();
    $limit_amount = 0;
    $number_total_amount = 0;

    $start_datetime = $date . ' 00:00:00';
    $end_datetime = $date . ' 23:59:59';

    $is_agent = in_array('commission_agent', (array) $current_user->roles) && get_option('custom_lottery_enable_commission_agent_system');

    if ($is_agent) {
        $agent = $wpdb->get_row($wpdb->prepare("SELECT id, per_number_limit FROM $table_agents WHERE user_id = %d", $current_user->ID));
        if ($agent && $agent->per_number_limit > 0) {
            $limit_amount = $agent->per_number_limit;
            $number_total_amount = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(amount) FROM $table_entries WHERE lottery_number = %s AND draw_session = %s AND agent_id = %d AND timestamp BETWEEN %s AND %s",
                $number, $session, $agent->id, $start_datetime, $end_datetime
            ));
        }
    } else {
        $limit_amount = get_option('custom_lottery_number_limit', 5000);
        $number_total_amount = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM $table_entries WHERE lottery_number = %s AND draw_session = %s AND timestamp BETWEEN %s AND %s",
            $number, $session, $start_datetime, $end_datetime
        ));
    }

    if ($limit_amount > 0 && $number_total_amount >= $limit_amount) {
        $is_already_blocked = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_limits WHERE lottery_number = %s AND draw_date = %s AND draw_session = %s",
            $number, $date, $session
        ));

        if (!$is_already_blocked) {
            $wpdb->insert($table_limits, [
                'lottery_number' => $number,
                'draw_date'      => $date,
                'draw_session'   => $session,
                'limit_type'     => 'auto'
            ]);
        }
    }
}

/**
 * Retrieves the configured session times with defaults.
 *
 * @return array Associative array of session times.
 */
function custom_lottery_get_session_times() {
    $defaults = [
        'morning_open'  => '09:30',
        'morning_close' => '12:00',
        'evening_open'  => '14:00',
        'evening_close' => '16:30',
    ];
    $session_times = get_option('custom_lottery_session_times', $defaults);
    return wp_parse_args($session_times, $defaults);
}

/**
 * Determines the current active lottery session based on the time.
 *
 * @return string|null The current session ('12:01 PM' or '4:30 PM') or null if no session is active.
 */
function custom_lottery_get_current_session() {
    $session_times = custom_lottery_get_session_times();
    $timezone = new DateTimeZone('Asia/Yangon');
    $current_time = new DateTime('now', $timezone);

    $morning_close_time = new DateTime($current_time->format('Y-m-d') . ' ' . $session_times['morning_close'] . ':00', $timezone);
    $evening_close_time = new DateTime($current_time->format('Y-m-d') . ' ' . $session_times['evening_close'] . ':00', $timezone);

    if ($current_time <= $morning_close_time) {
        return '12:01 PM';
    } elseif ($current_time > $morning_close_time && $current_time <= $evening_close_time) {
        return '4:30 PM';
    } else {
        return null; // No active session
    }
}

/**
 * Fetches data from the Thai Stock 2D API.
 *
 * @return array|WP_Error The decoded JSON data or a WP_Error on failure.
 */
function custom_lottery_fetch_api_data() {
    $api_url = get_option('custom_lottery_api_url_live', 'https://api.thaistock2d.com/live');

    $response = wp_remote_get($api_url, ['timeout' => 15]);

    if (is_wp_error($response)) {
        return $response;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
        return new WP_Error('api_error', 'API returned a non-200 response code.', ['status' => $response_code]);
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return new WP_Error('json_error', 'Error decoding JSON response from API.', ['json_error' => json_last_error_msg()]);
    }

    return $data;
}