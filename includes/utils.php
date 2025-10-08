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

    $existing_customer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_customers WHERE phone = %s", $phone));

    if ($existing_customer) {
        // Customer exists, update their name, last_seen, and agent_id if a new one is provided.
        $data_to_update = [
            'customer_name' => $name,
            'last_seen'     => current_time('mysql'),
        ];
        if ($agent_id) {
            $data_to_update['agent_id'] = $agent_id;
        }
        $wpdb->update(
            $table_customers,
            $data_to_update,
            ['id' => $existing_customer->id]
        );
    } else {
        // New customer, insert with agent_id if provided.
        $data = [
            'customer_name' => $name,
            'phone'         => $phone,
            'last_seen'     => current_time('mysql'),
        ];
        if ($agent_id) {
            $data['agent_id'] = $agent_id;
        }
        $wpdb->insert($table_customers, $data);
    }
}

/**
 * Checks if a number's total purchased amount exceeds the custom limit and blocks it if necessary.
 * This function handles both global limits and agent-specific limits.
 */
function check_and_auto_block_number($number, $session, $date, $agent_id = null) {
    // First, check if the master switch for auto-blocking is enabled.
    if (!get_option('custom_lottery_enable_auto_blocking')) {
        return;
    }

    global $wpdb;
    $table_entries = $wpdb->prefix . 'lotto_entries';
    $table_limits = $wpdb->prefix . 'lotto_limits';
    $table_agents = $wpdb->prefix . 'lotto_agents';

    $limit_amount = 0;
    $where_clauses = [
        "lottery_number = %s",
        "draw_session = %s",
        "timestamp BETWEEN %s AND %s"
    ];
    $query_params = [$number, $session, $date . ' 00:00:00', $date . ' 23:59:59'];

    if ($agent_id) {
        // For a commission agent, use their personal limit and scope the sales check to their entries.
        $limit_amount = $wpdb->get_var($wpdb->prepare("SELECT per_number_limit FROM $table_agents WHERE id = %d", $agent_id));
        $where_clauses[] = "agent_id = %d";
        $query_params[] = $agent_id;
    } else {
        // For admins or other users, use the global limit and check total sales across all agents.
        $limit_amount = get_option('custom_lottery_number_limit', 5000);
    }

    // If no limit is set (e.g., agent limit is 0), do not proceed with the check.
    if (empty($limit_amount) || $limit_amount <= 0) {
        return;
    }

    $where_sql = implode(' AND ', $where_clauses);
    $number_total_amount = $wpdb->get_var($wpdb->prepare("SELECT SUM(amount) FROM $table_entries WHERE $where_sql", $query_params));

    if ($number_total_amount >= $limit_amount) {
        // Check if the number is already blocked for the session to avoid duplicate entries.
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