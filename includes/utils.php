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
        $data_to_update = [
            'customer_name' => $name,
            'last_seen'     => current_time('mysql'),
        ];
        if ($agent_id) {
            $data_to_update['agent_id'] = $agent_id;
        }
        $wpdb->update($table_customers, $data_to_update, ['id' => $existing_customer->id]);
    } else {
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
 * Checks if a number's total purchased amount exceeds the custom limit and blocks it or creates a cover request.
 */
function check_and_auto_block_number($number, $session, $date, $agent_id = null, $amount = 0) {
    global $wpdb;
    $table_entries = $wpdb->prefix . 'lotto_entries';
    $table_limits = $wpdb->prefix . 'lotto_limits';
    $table_agents = $wpdb->prefix . 'lotto_agents';
    $table_cover_requests = $wpdb->prefix . 'lotto_cover_requests';

    if ($agent_id) {
        // --- Commission Agent Logic: Block if their personal limit is exceeded ---
        if (!get_option('custom_lottery_enable_auto_blocking')) return;

        $limit_amount = $wpdb->get_var($wpdb->prepare("SELECT per_number_limit FROM $table_agents WHERE id = %d", $agent_id));
        if (empty($limit_amount) || $limit_amount <= 0) return;

        $agent_sales = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM $table_entries WHERE lottery_number = %s AND draw_session = %s AND agent_id = %d AND timestamp BETWEEN %s AND %s",
            $number, $session, $agent_id, $date . ' 00:00:00', $date . ' 23:59:59'
        ));

        if ($agent_sales >= $limit_amount) {
            $is_already_blocked = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_limits WHERE lottery_number = %s AND draw_date = %s AND draw_session = %s", $number, $date, $session));
            if (!$is_already_blocked) {
                $wpdb->insert($table_limits, ['lottery_number' => $number, 'draw_date' => $date, 'draw_session' => $session, 'limit_type' => 'auto']);
            }
        }
    } else {
        // --- Admin/Manager Logic: Create cover request or block based on settings ---
        $cover_system_enabled = get_option('custom_lottery_enable_cover_agent_system');
        $autoblock_enabled = get_option('custom_lottery_enable_auto_blocking');

        if (!$cover_system_enabled && !$autoblock_enabled) return;

        $global_limit = get_option('custom_lottery_number_limit', 5000);
        if (empty($global_limit) || $global_limit <= 0) return;

        $total_sales = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM $table_entries WHERE lottery_number = %s AND draw_session = %s AND timestamp BETWEEN %s AND %s",
            $number, $session, $date . ' 00:00:00', $date . ' 23:59:59'
        ));

        if ($total_sales > $global_limit) {
            if ($cover_system_enabled) {
                $sales_before_this_entry = $total_sales - $amount;
                $cover_amount = $total_sales - max($global_limit, $sales_before_this_entry);

                if ($cover_amount > 0) {
                    $wpdb->insert($table_cover_requests, [
                        'lottery_number' => $number,
                        'draw_date'      => $date,
                        'draw_session'   => $session,
                        'amount'         => $cover_amount,
                        'status'         => 'pending',
                        'timestamp'      => current_time('mysql'),
                        'commission_agent_id' => null,
                        'cover_agent_id' => null,
                    ]);
                }
            } elseif ($autoblock_enabled) {
                $is_already_blocked = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_limits WHERE lottery_number = %s AND draw_date = %s AND draw_session = %s", $number, $date, $session));
                if (!$is_already_blocked) {
                    $wpdb->insert($table_limits, ['lottery_number' => $number, 'draw_date' => $date, 'draw_session' => $session, 'limit_type' => 'auto']);
                }
            }
        }
    }
}


/**
 * Retrieves the configured session times with defaults.
 */
function custom_lottery_get_session_times($agent_id = null) {
    global $wpdb;

    $defaults = [
        'morning_open'  => get_option('custom_lottery_morning_session_open', '08:00'),
        'morning_close' => get_option('custom_lottery_morning_session_close', '12:00'),
        'evening_open'  => get_option('custom_lottery_evening_session_open', '13:00'),
        'evening_close' => get_option('custom_lottery_evening_session_close', '16:30'),
    ];

    if ($agent_id) {
        $table_agents = $wpdb->prefix . 'lotto_agents';
        $agent_times = $wpdb->get_row($wpdb->prepare(
            "SELECT morning_open, morning_close, evening_open, evening_close FROM $table_agents WHERE id = %d",
            $agent_id
        ), ARRAY_A);

        if ($agent_times) {
            // Use agent-specific time if it's set, otherwise fall back to default.
            return [
                'morning_open'  => !empty($agent_times['morning_open']) ? date('H:i', strtotime($agent_times['morning_open'])) : $defaults['morning_open'],
                'morning_close' => !empty($agent_times['morning_close']) ? date('H:i', strtotime($agent_times['morning_close'])) : $defaults['morning_close'],
                'evening_open'  => !empty($agent_times['evening_open']) ? date('H:i', strtotime($agent_times['evening_open'])) : $defaults['evening_open'],
                'evening_close' => !empty($agent_times['evening_close']) ? date('H:i', strtotime($agent_times['evening_close'])) : $defaults['evening_close'],
            ];
        }
    }

    return $defaults;
}

/**
 * Determines the current active lottery session based on the time.
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