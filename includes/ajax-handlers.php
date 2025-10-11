<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * AJAX handler for getting dashboard chart data.
 */
function custom_lottery_get_dashboard_data_callback() {
    check_ajax_referer('dashboard_nonce', 'nonce');

    global $wpdb;
    $table_entries = $wpdb->prefix . 'lotto_entries';
    $range = sanitize_text_field($_POST['range']);
    $timezone = new DateTimeZone('Asia/Yangon');

    switch ($range) {
        case 'last_7_days':
            $start_date = new DateTime('6 days ago', $timezone);
            $end_date = new DateTime('today', $timezone);
            break;
        case 'this_month':
            $start_date = new DateTime('first day of this month', $timezone);
            $end_date = new DateTime('last day of this month', $timezone);
            break;
        default:
            $start_date = new DateTime('6 days ago', $timezone);
            $end_date = new DateTime('today', $timezone);
    }

    $sales_data = $wpdb->get_results($wpdb->prepare(
        "SELECT DATE(timestamp) as entry_date, SUM(amount) as total_sales
         FROM $table_entries
         WHERE timestamp BETWEEN %s AND %s
         GROUP BY entry_date
         ORDER BY entry_date ASC",
        $start_date->format('Y-m-d 00:00:00'),
        $end_date->format('Y-m-d 23:59:59')
    ), ARRAY_A);

    $payout_rate = (int) get_option('custom_lottery_payout_rate', 80);
    $payout_data = $wpdb->get_results($wpdb->prepare(
        "SELECT DATE(timestamp) as entry_date, SUM(amount * $payout_rate) as total_payouts
         FROM $table_entries
         WHERE is_winner = 1 AND timestamp BETWEEN %s AND %s
         GROUP BY entry_date
         ORDER BY entry_date ASC",
        $start_date->format('Y-m-d 00:00:00'),
        $end_date->format('Y-m-d 23:59:59')
    ), ARRAY_A);

    $labels = [];
    $sales = [];
    $payouts = [];

    $period = new DatePeriod($start_date, new DateInterval('P1D'), $end_date->modify('+1 day'));
    foreach ($period as $date) {
        $formatted_date = $date->format('Y-m-d');
        $labels[] = $formatted_date;

        $sale_found = array_search($formatted_date, array_column($sales_data, 'entry_date'));
        $sales[] = ($sale_found !== false) ? (float)$sales_data[$sale_found]['total_sales'] : 0;

        $payout_found = array_search($formatted_date, array_column($payout_data, 'entry_date'));
        $payouts[] = ($payout_found !== false) ? (float)$payout_data[$payout_found]['total_payouts'] : 0;
    }

    wp_send_json_success(['labels' => $labels, 'sales' => $sales, 'payouts' => $payouts]);
}
add_action('wp_ajax_get_dashboard_data', 'custom_lottery_get_dashboard_data_callback');


/**
 * AJAX handler for searching customers by phone number.
 */
function custom_lottery_search_customers_callback() {
    if ( ! current_user_can( 'enter_lottery_numbers' ) ) {
        wp_send_json_error( 'Permission denied.' );
        return;
    }
    // Nonce is checked in the form script, but let's re-verify. The nonce name is 'lottery_entry_nonce' in the form.
    check_ajax_referer('lottery_entry_action', 'nonce');

    global $wpdb;
    $table_customers = $wpdb->prefix . 'lotto_customers';
    $table_agents = $wpdb->prefix . 'lotto_agents';

    $term = isset($_REQUEST['term']) ? sanitize_text_field($_REQUEST['term']) : '';

    if (empty($term)) {
        wp_send_json([]);
    }

    $current_user = wp_get_current_user();
    $query = "SELECT customer_name, phone FROM $table_customers WHERE phone LIKE %s";
    $params = ['%' . $wpdb->esc_like($term) . '%'];

    // If the user is a commission agent and not an admin, filter by their agent_id
    if (in_array('commission_agent', (array) $current_user->roles) && !current_user_can('manage_options')) {
        $agent_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_agents WHERE user_id = %d", $current_user->ID));
        if ($agent_id) {
            $query .= " AND agent_id = %d";
            $params[] = $agent_id;
        } else {
            // If user is an agent but has no agent record, return no results.
            wp_send_json([]);
            return;
        }
    }

    $query .= " LIMIT 10";

    $results = $wpdb->get_results($wpdb->prepare($query, $params));

    $suggestions = [];
    if ($results) {
        foreach ($results as $result) {
            $suggestions[] = [
                'label' => $result->customer_name . ' (' . $result->phone . ')',
                'value' => $result->phone,
                'name'  => $result->customer_name
            ];
        }
    }

    wp_send_json($suggestions);
}
add_action('wp_ajax_search_customers', 'custom_lottery_search_customers_callback');


/**
 * AJAX handler for submitting a batch of lottery entries.
 */
function custom_lottery_submit_entries_callback() {
    if ( ! current_user_can( 'enter_lottery_numbers' ) ) {
        wp_send_json_error( 'Permission denied.' );
        return;
    }
    check_ajax_referer('lottery_entry_action', 'nonce');

    global $wpdb;
    $table_entries = $wpdb->prefix . 'lotto_entries';
    $table_limits = $wpdb->prefix . 'lotto_limits';
    $table_agents = $wpdb->prefix . 'lotto_agents';

    $timezone = new DateTimeZone('Asia/Yangon');
    $current_datetime = new DateTime('now', $timezone);
    $current_date = $current_datetime->format('Y-m-d');
    $draw_session = sanitize_text_field($_POST['draw_session']);

    // Advanced time-based entry restriction logic
    $current_user = wp_get_current_user();

    // Start with global default session times.
    $session_times = custom_lottery_get_session_times();

    // If the user is an agent, check for custom overrides.
    if (in_array('commission_agent', (array) $current_user->roles)) {
        $agent = $wpdb->get_row($wpdb->prepare("SELECT morning_open, morning_close, evening_open, evening_close FROM $table_agents WHERE user_id = %d", $current_user->ID));
        if ($agent) {
            // Override global times with agent-specific times, only if they are set.
            if (!empty($agent->morning_open)) {
                $session_times['morning_open'] = $agent->morning_open;
            }
            if (!empty($agent->morning_close)) {
                $session_times['morning_close'] = $agent->morning_close;
            }
            if (!empty($agent->evening_open)) {
                $session_times['evening_open'] = $agent->evening_open;
            }
            if (!empty($agent->evening_close)) {
                $session_times['evening_close'] = $agent->evening_close;
            }
        }
    }

    // Determine which session window to use
    if ($draw_session === '12:01 PM') {
        $open_time_str = $session_times['morning_open'];
        $close_time_str = $session_times['morning_close'];
    } else { // 4:30 PM
        $open_time_str = $session_times['evening_open'];
        $close_time_str = $session_times['evening_close'];
    }

    // Create DateTime objects for comparison
    try {
        $session_open_time = new DateTime($current_date . ' ' . $open_time_str, $timezone);
        $session_close_time = new DateTime($current_date . ' ' . $close_time_str, $timezone);

        if ($current_datetime < $session_open_time || $current_datetime > $session_close_time) {
            wp_send_json_error( 'The entry session for ' . $draw_session . ' is currently closed.' );
            return;
        }
    } catch (Exception $e) {
        // Handle potential DateTime creation errors if times are invalid
        wp_send_json_error( 'Invalid session time configuration.' );
        return;
    }
    // End of advanced time-based entry restriction logic

    // Get agent_id and commission rate if the current user is a commission agent
    $agent_id = null;
    $agent_commission_rate = 0.00;
    if (in_array('commission_agent', (array) $current_user->roles)) {
        $agent_data = $wpdb->get_row($wpdb->prepare("SELECT id, commission_rate FROM $table_agents WHERE user_id = %d", $current_user->ID));
        if ($agent_data) {
            $agent_id = $agent_data->id;
            $agent_commission_rate = (float) $agent_data->commission_rate;
        }
    }

    // Get customer and session data
    $customer_name = sanitize_text_field($_POST['customer_name']);
    $phone = sanitize_text_field($_POST['phone']);

    // Get and decode the entries JSON
    $entries_json = stripslashes($_POST['entries']);
    $entries = json_decode($entries_json, true);

    if (empty($customer_name) || empty($phone) || empty($draw_session) || json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error('Missing or invalid form data.');
        return;
    }

    custom_lottery_update_or_create_customer($customer_name, $phone, $agent_id);

    $success_count = 0;
    $total_amount = 0;
    $error_messages = [];
    $processed_entries_for_receipt = [];

    foreach ($entries as $entry) {
        $lottery_number = sanitize_text_field($entry['number']);
        $amount = absint($entry['amount']);
        $is_reverse = filter_var($entry['is_reverse'], FILTER_VALIDATE_BOOLEAN);

        if (!preg_match('/^\d{2}$/', $lottery_number) || empty($amount)) {
            $error_messages[] = "Invalid data for number {$lottery_number}. Skipping.";
            continue;
        }

        // Process the main number
        $is_blocked = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_limits WHERE lottery_number = %s AND draw_date = %s AND draw_session = %s", $lottery_number, $current_date, $draw_session));
        if ($is_blocked) {
            $error_messages[] = "Number {$lottery_number} is blocked. Skipping.";
            continue;
        }

        $entry_data = [
            'customer_name' => $customer_name,
            'phone' => $phone,
            'lottery_number' => $lottery_number,
            'amount' => $amount,
            'draw_session' => $draw_session,
            'timestamp' => $current_datetime->format('Y-m-d H:i:s'),
        ];
        if ($agent_id) {
            $entry_data['agent_id'] = $agent_id;
        }
        $wpdb->insert($table_entries, $entry_data);
        $entry_id = $wpdb->insert_id; // Get the ID of the entry just inserted

        // If the entry was submitted by an agent, calculate and record commission
        if ($agent_id && $agent_commission_rate > 0) {
            $commission_amount = $amount * ($agent_commission_rate / 100);

            // Record the commission transaction
            $wpdb->insert($wpdb->prefix . 'lotto_agent_transactions', [
                'agent_id' => $agent_id,
                'type' => 'commission',
                'amount' => $commission_amount,
                'related_entry_id' => $entry_id,
                'timestamp' => $current_datetime->format('Y-m-d H:i:s'),
            ]);

            // Update the agent's balance
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}lotto_agents SET balance = COALESCE(balance, 0) + %s WHERE id = %d",
                $commission_amount,
                $agent_id
            ));
        }

        check_and_auto_block_number($lottery_number, $draw_session, $current_date, $agent_id, $amount);
        $success_count++;
        $total_amount += $amount;
        $processed_entries_for_receipt[] = ['lottery_number' => $lottery_number, 'amount' => $amount, 'is_reverse' => false];

        // Process the reversed number if applicable
        if ($is_reverse) {
            $reversed_number = strrev($lottery_number);
            if ($lottery_number !== $reversed_number) {
                $is_rev_blocked = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_limits WHERE lottery_number = %s AND draw_date = %s AND draw_session = %s", $reversed_number, $current_date, $draw_session));
                if ($is_rev_blocked) {
                    $error_messages[] = "Reversed number {$reversed_number} is blocked. Skipping.";
                    continue;
                }
                $rev_entry_data = [
                    'customer_name' => $customer_name,
                    'phone' => $phone,
                    'lottery_number' => $reversed_number,
                    'amount' => $amount,
                    'draw_session' => $draw_session,
                    'timestamp' => $current_datetime->format('Y-m-d H:i:s'),
                ];
                if ($agent_id) {
                    $rev_entry_data['agent_id'] = $agent_id;
                }
                $wpdb->insert($table_entries, $rev_entry_data);
                $rev_entry_id = $wpdb->insert_id;

                // If the entry was submitted by an agent, calculate and record commission for reversed number
                if ($agent_id && $agent_commission_rate > 0) {
                    $commission_amount = $amount * ($agent_commission_rate / 100);

                    // Record the commission transaction
                    $wpdb->insert($wpdb->prefix . 'lotto_agent_transactions', [
                        'agent_id' => $agent_id,
                        'type' => 'commission',
                        'amount' => $commission_amount,
                        'related_entry_id' => $rev_entry_id,
                        'timestamp' => $current_datetime->format('Y-m-d H:i:s'),
                    ]);

                    // Update the agent's balance
                    $wpdb->query($wpdb->prepare(
                        "UPDATE {$wpdb->prefix}lotto_agents SET balance = COALESCE(balance, 0) + %s WHERE id = %d",
                        $commission_amount,
                        $agent_id
                    ));
                }

                check_and_auto_block_number($reversed_number, $draw_session, $current_date, $agent_id, $amount);
                $success_count++;
                $total_amount += $amount;
                $processed_entries_for_receipt[] = ['lottery_number' => $reversed_number, 'amount' => $amount, 'is_reverse' => true];
            }
        }
    }

    $final_message = "Transaction complete. {$success_count} entries added successfully.";
    if (!empty($error_messages)) {
        $final_message .= " The following errors occurred: " . implode(' ', $error_messages);
        wp_send_json_error($final_message);
    } else {
        wp_send_json_success([
            'message' => $final_message,
            'entries' => $processed_entries_for_receipt,
            'total_amount' => $total_amount,
        ]);
    }
}
add_action('wp_ajax_submit_lottery_entries', 'custom_lottery_submit_entries_callback');


/**
 * AJAX handler for fetching a customer's lottery results for the frontend portal.
 */
function custom_lottery_get_customer_results_callback() {
    check_ajax_referer('lottery_portal_nonce_action', 'nonce');

    global $wpdb;
    $table_entries = $wpdb->prefix . 'lotto_entries';
    $phone = sanitize_text_field($_POST['phone']);

    if (empty($phone)) {
        wp_send_json_error('Please enter a phone number.');
        return;
    }

    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT DATE(timestamp) as date, draw_session, lottery_number, amount, is_winner
         FROM $table_entries
         WHERE phone = %s
         ORDER BY timestamp DESC
         LIMIT 20",
        $phone
    ));

    wp_send_json_success($results);
}
add_action('wp_ajax_get_customer_lottery_results', 'custom_lottery_get_customer_results_callback');
add_action('wp_ajax_nopriv_get_customer_lottery_results', 'custom_lottery_get_customer_results_callback'); // For non-logged-in users


/**
 * AJAX handler for getting combined real-time dashboard widget data.
 */
function custom_lottery_get_dashboard_widgets_data_callback() {
    check_ajax_referer('dashboard_nonce', 'nonce');

    // Fetch data from the external API
    $api_data = custom_lottery_fetch_api_data();
    $api_error = null;

    if (is_wp_error($api_data)) {
        $api_error = $api_data->get_error_message();
        $api_data = []; // Reset to avoid errors in JS
    }

    // Fetch data from the local database
    $live_sales_data = custom_lottery_get_live_sales_data();
    $hot_numbers_data = custom_lottery_get_top_hot_numbers();

    // Combine all data into a single response
    $response_data = [
        'api_data'    => $api_data,
        'api_error'   => $api_error,
        'live_sales'  => $live_sales_data,
        'hot_numbers' => $hot_numbers_data,
    ];

    wp_send_json_success($response_data);
}
add_action('wp_ajax_get_dashboard_widgets_data', 'custom_lottery_get_dashboard_widgets_data_callback');


/**
 * AJAX handler for manually importing winning numbers from the last 10 days.
 */
function custom_lottery_manual_import_winning_numbers_handler() {
    // Check for nonce for security
    check_ajax_referer('manual_import_nonce', 'nonce');

    global $wpdb;
    $table_winning_numbers = $wpdb->prefix . 'lotto_winning_numbers';
    $api_url = get_option('custom_lottery_api_url_historical', 'https://api.thaistock2d.com/2d_result');

    // Fetch data from the API
    $response = wp_remote_get($api_url, ['timeout' => 15]);

    // Handle API errors
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        wp_send_json_error(['message' => 'Failed to fetch data from the API.']);
        return;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Handle invalid data format
    if (empty($data) || !is_array($data)) {
        wp_send_json_error(['message' => 'Invalid data format received from the API.']);
        return;
    }

    $imported_count = 0;
    $session_map = [
        '12:01:00' => '12:01 PM',
        '16:30:00' => '4:30 PM',
    ];

    // The API returns results for more than 10 days, so we limit it here.
    $days_to_import = array_slice($data, 0, 10);

    foreach ($days_to_import as $day_result) {
        $draw_date = $day_result['date'];
        foreach ($day_result['child'] as $result) {
            $session_time = $result['time'];
            if (isset($session_map[$session_time])) {
                $session_label = $session_map[$session_time];
                $winning_number = sanitize_text_field($result['twod']);

                // Use insert ignore to avoid errors on duplicate entries
                $inserted = $wpdb->query($wpdb->prepare(
                    "INSERT IGNORE INTO {$table_winning_numbers} (winning_number, draw_date, draw_session) VALUES (%s, %s, %s)",
                    $winning_number,
                    $draw_date,
                    $session_label
                ));

                if ($inserted) {
                    $imported_count++;
                }
            }
        }
    }

    wp_send_json_success(['message' => "Manual import complete. {$imported_count} new winning numbers were added."]);
}
add_action('wp_ajax_manual_import_winning_numbers', 'custom_lottery_manual_import_winning_numbers_handler');

/**
 * AJAX handler for assigning a cover agent to a request.
 */
function custom_lottery_assign_cover_agent_callback() {
    check_ajax_referer('cover_requests_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied.']);
        return;
    }

    global $wpdb;
    $table_cover_requests = $wpdb->prefix . 'lotto_cover_requests';

    $request_id = isset($_POST['request_id']) ? absint($_POST['request_id']) : 0;
    $agent_id = isset($_POST['agent_id']) ? absint($_POST['agent_id']) : 0;

    if (empty($request_id) || empty($agent_id)) {
        wp_send_json_error(['message' => 'Invalid request or agent ID.']);
        return;
    }

    $updated = $wpdb->update(
        $table_cover_requests,
        ['status' => 'assigned', 'cover_agent_id' => $agent_id],
        ['id' => $request_id]
    );

    if ($updated) {
        $table_agents = $wpdb->prefix . 'lotto_agents';
        $agent_user_id = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM $table_agents WHERE id = %d", $agent_id));
        $user = get_userdata($agent_user_id);
        $agent_name = $user ? $user->display_name : 'Unknown';
        wp_send_json_success(['message' => 'Agent assigned successfully.', 'agent_name' => $agent_name]);
    } else {
        wp_send_json_error(['message' => 'Failed to assign agent.']);
    }
}
add_action('wp_ajax_assign_cover_agent', 'custom_lottery_assign_cover_agent_callback');

/**
 * AJAX handler for confirming a cover request.
 */
function custom_lottery_confirm_cover_callback() {
    check_ajax_referer('cover_requests_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied.']);
        return;
    }

    global $wpdb;
    $table_cover_requests = $wpdb->prefix . 'lotto_cover_requests';

    $request_id = isset($_POST['request_id']) ? absint($_POST['request_id']) : 0;

    if (empty($request_id)) {
        wp_send_json_error(['message' => 'Invalid request ID.']);
        return;
    }

    $updated = $wpdb->update(
        $table_cover_requests,
        ['status' => 'confirmed'],
        ['id' => $request_id]
    );

    if ($updated) {
        wp_send_json_success(['message' => 'Cover confirmed successfully.']);
    } else {
        wp_send_json_error(['message' => 'Failed to confirm cover.']);
    }
}
add_action('wp_ajax_confirm_cover', 'custom_lottery_confirm_cover_callback');

/**
 * AJAX handler for an agent to request a modification to an entry.
 */
function custom_lottery_request_entry_modification_callback() {
    check_ajax_referer('request_modification_nonce', 'nonce');

    $entry_id = isset($_POST['entry_id']) ? absint($_POST['entry_id']) : 0;
    $request_notes = isset($_POST['request_notes']) ? sanitize_textarea_field($_POST['request_notes']) : '';
    $new_number = isset($_POST['new_number']) ? sanitize_text_field($_POST['new_number']) : null;
    $new_amount = isset($_POST['new_amount']) ? sanitize_text_field($_POST['new_amount']) : null;

    if (empty($entry_id) || empty($request_notes) || !preg_match('/^\d{2}$/', $new_number) || !is_numeric($new_amount)) {
        wp_send_json_error('Invalid data provided. Please fill all fields correctly.');
        return;
    }

    $current_user = wp_get_current_user();
    if (!in_array('commission_agent', (array) $current_user->roles)) {
        wp_send_json_error('You do not have permission to perform this action.');
        return;
    }

    global $wpdb;
    $table_agents = $wpdb->prefix . 'lotto_agents';
    $table_entries = $wpdb->prefix . 'lotto_entries';
    $table_requests = $wpdb->prefix . 'lotto_modification_requests';

    $agent_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_agents WHERE user_id = %d", $current_user->ID));
    if (!$agent_id) {
        wp_send_json_error('Could not verify your agent status.');
        return;
    }

    // Security check: Verify the agent owns the entry
    $entry_agent_id = $wpdb->get_var($wpdb->prepare("SELECT agent_id FROM $table_entries WHERE id = %d", $entry_id));
    if ($entry_agent_id != $agent_id) {
        wp_send_json_error('You can only request modifications for your own entries.');
        return;
    }

    // Insert the modification request
    $inserted = $wpdb->insert($table_requests, [
        'entry_id'           => $entry_id,
        'agent_id'           => $agent_id,
        'request_notes'      => $request_notes,
        'new_lottery_number' => $new_number,
        'new_amount'         => $new_amount,
        'status'             => 'pending',
        'requested_at'       => current_time('mysql'),
    ]);

    if ($inserted) {
        // Update the entry to flag that it has a pending modification request
        $wpdb->update(
            $table_entries,
            ['has_mod_request' => 1, 'mod_request_status' => 'pending'],
            ['id' => $entry_id]
        );
        wp_send_json_success('Modification request submitted successfully.');
    } else {
        wp_send_json_error('Failed to save the modification request. Please try again.');
    }
}
add_action('wp_ajax_request_entry_modification', 'custom_lottery_request_entry_modification_callback');


/**
 * AJAX handler for an admin to approve a modification request.
 */
function custom_lottery_approve_modification_request_callback() {
    if (!isset($_POST['request_id']) || !isset($_POST['nonce'])) {
        wp_send_json_error('Invalid request.');
    }

    $request_id = absint($_POST['request_id']);
    check_ajax_referer('mod_request_approve_' . $request_id, 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied.');
    }

    global $wpdb;
    $table_requests = $wpdb->prefix . 'lotto_modification_requests';
    $table_entries = $wpdb->prefix . 'lotto_entries';

    $request = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_requests WHERE id = %d", $request_id));

    if (!$request) {
        wp_send_json_error('Request not found.');
    }

    // Update the original entry with the new data from the request
    if (isset($request->new_lottery_number) && isset($request->new_amount)) {
        $wpdb->update(
            $table_entries,
            [
                'lottery_number' => $request->new_lottery_number,
                'amount'         => $request->new_amount,
            ],
            ['id' => $request->entry_id]
        );
    }

    // Update request status to 'approved'
    $wpdb->update(
        $table_requests,
        ['status' => 'approved', 'resolved_by' => get_current_user_id(), 'resolved_at' => current_time('mysql')],
        ['id' => $request_id]
    );

    // Clear the modification request flag and set the status to 'approved'
    $wpdb->update(
        $table_entries,
        ['has_mod_request' => 0, 'mod_request_status' => 'approved'],
        ['id' => $request->entry_id]
    );

    wp_send_json_success(['message' => 'Request approved and entry updated.', 'new_status' => 'Approved']);
}
add_action('wp_ajax_approve_modification_request', 'custom_lottery_approve_modification_request_callback');


/**
 * AJAX handler for an admin to reject a modification request.
 */
function custom_lottery_reject_modification_request_callback() {
     if (!isset($_POST['request_id']) || !isset($_POST['nonce'])) {
        wp_send_json_error('Invalid request.');
    }

    $request_id = absint($_POST['request_id']);
    check_ajax_referer('mod_request_reject_' . $request_id, 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied.');
    }

    global $wpdb;
    $table_requests = $wpdb->prefix . 'lotto_modification_requests';
    $table_entries = $wpdb->prefix . 'lotto_entries';

    $request = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_requests WHERE id = %d", $request_id));

    if (!$request) {
        wp_send_json_error('Request not found.');
    }

    // Update request status
    $wpdb->update($table_requests,
        ['status' => 'rejected', 'resolved_by' => get_current_user_id(), 'resolved_at' => current_time('mysql')],
        ['id' => $request_id]
    );

    // Clear the flag on the entry and set the status to 'rejected'
    $wpdb->update(
        $table_entries,
        ['has_mod_request' => 0, 'mod_request_status' => 'rejected'],
        ['id' => $request->entry_id]
    );

    wp_send_json_success(['message' => 'Request rejected.', 'new_status' => 'Rejected']);
}
add_action('wp_ajax_reject_modification_request', 'custom_lottery_reject_modification_request_callback');

/**
 * AJAX handler for making a payout to an agent.
 */
function custom_lottery_make_payout_callback() {
    check_ajax_referer('make_payout_nonce_action', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied.']);
        return;
    }

    global $wpdb;
    $table_agents = $wpdb->prefix . 'lotto_agents';
    $table_transactions = $wpdb->prefix . 'lotto_agent_transactions';

    $agent_id = isset($_POST['agent_id']) ? absint($_POST['agent_id']) : 0;
    $amount = isset($_POST['amount']) ? (float) $_POST['amount'] : 0;
    $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
    $payout_method = isset($_POST['payout_method']) ? sanitize_text_field($_POST['payout_method']) : 'Cash';
    $attachment_url = '';

    if (empty($agent_id) || empty($amount) || $amount <= 0) {
        wp_send_json_error(['message' => 'Invalid agent ID or amount.']);
        return;
    }

    // Handle file upload
    if (isset($_FILES['proof_attachment']) && $_FILES['proof_attachment']['error'] === UPLOAD_ERR_OK) {
        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }
        $uploaded_file = $_FILES['proof_attachment'];
        $upload_overrides = ['test_form' => false];
        $movefile = wp_handle_upload($uploaded_file, $upload_overrides);

        if ($movefile && !isset($movefile['error'])) {
            $attachment_url = $movefile['url'];
        } else {
            wp_send_json_error(['message' => 'File upload error: ' . $movefile['error']]);
            return;
        }
    }

    // Start a transaction
    $wpdb->query('START TRANSACTION');

    // Insert the payout transaction
    $transaction_inserted = $wpdb->insert($table_transactions, [
        'agent_id' => $agent_id,
        'type' => 'payout',
        'amount' => -$amount, // Store payout as a negative value
        'notes' => $notes,
        'timestamp' => current_time('mysql'),
        'payout_method' => $payout_method,
        'proof_attachment' => $attachment_url,
    ]);

    // Subtract the amount from the agent's balance
    $balance_updated = $wpdb->query($wpdb->prepare(
        "UPDATE $table_agents SET balance = balance - %f WHERE id = %d",
        $amount,
        $agent_id
    ));

    if ($transaction_inserted && $balance_updated) {
        $wpdb->query('COMMIT');
        $new_balance = $wpdb->get_var($wpdb->prepare("SELECT balance FROM $table_agents WHERE id = %d", $agent_id));
        wp_send_json_success([
            'message' => 'Payout recorded successfully.',
            'new_balance' => $new_balance
        ]);
    } else {
        $wpdb->query('ROLLBACK');
        wp_send_json_error(['message' => 'Failed to record payout. Please try again.']);
    }
}
add_action('wp_ajax_make_payout', 'custom_lottery_make_payout_callback');

/**
 * AJAX handler for an agent to request a payout.
 */
function custom_lottery_agent_request_payout_callback() {
    check_ajax_referer('agent_request_payout_action', 'nonce');

    if ( ! current_user_can('enter_lottery_numbers') || !in_array('commission_agent', (array) wp_get_current_user()->roles) ) {
        wp_send_json_error(['message' => 'Permission denied.']);
        return;
    }

    global $wpdb;
    $table_agents = $wpdb->prefix . 'lotto_agents';
    $table_requests = $wpdb->prefix . 'lotto_payout_requests';
    $current_user_id = get_current_user_id();

    $agent = $wpdb->get_row($wpdb->prepare("SELECT id, balance, payout_threshold FROM $table_agents WHERE user_id = %d", $current_user_id));

    if (!$agent) {
        wp_send_json_error(['message' => 'Could not verify your agent status.']);
        return;
    }

    $amount = isset($_POST['amount']) ? (float) $_POST['amount'] : 0;
    $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

    // Determine the payout threshold
    $default_threshold = (float) get_option('custom_lottery_default_payout_threshold', 10000);
    $payout_threshold = !empty($agent->payout_threshold) ? (float) $agent->payout_threshold : $default_threshold;

    if (empty($amount) || $amount <= 0) {
        wp_send_json_error(['message' => 'Invalid amount requested.']);
        return;
    }

    if ($amount < $payout_threshold) {
        wp_send_json_error(['message' => 'Requested amount is less than your payout threshold.']);
        return;
    }

    if ($amount > (float) $agent->balance) {
        wp_send_json_error(['message' => 'Requested amount exceeds your current balance.']);
        return;
    }

    // Check for an existing pending request
    $pending_request = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_requests WHERE agent_id = %d AND status = 'pending'", $agent->id));
    if ($pending_request) {
        wp_send_json_error(['message' => 'You already have a pending payout request. Please wait for an admin to review it.']);
        return;
    }

    $inserted = $wpdb->insert($table_requests, [
        'agent_id' => $agent->id,
        'amount' => $amount,
        'status' => 'pending',
        'requested_at' => current_time('mysql'),
        'notes' => $notes,
    ]);

    if ($inserted) {
        wp_send_json_success(['message' => 'Payout request submitted successfully. An admin will review it shortly.']);
    } else {
        wp_send_json_error(['message' => 'Failed to submit your request. Please try again.']);
    }
}
add_action('wp_ajax_agent_request_payout', 'custom_lottery_agent_request_payout_callback');

/**
 * Consolidated AJAX handler for managing a payout request (approving or rejecting).
 */
function custom_lottery_manage_payout_request_callback() {
    check_ajax_referer('payout_manage_action', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied.']);
        return;
    }

    global $wpdb;
    $table_requests = $wpdb->prefix . 'lotto_payout_requests';
    $table_agents = $wpdb->prefix . 'lotto_agents';
    $table_transactions = $wpdb->prefix . 'lotto_agent_transactions';

    $request_id = isset($_POST['request_id']) ? absint($_POST['request_id']) : 0;
    $outcome = isset($_POST['outcome']) ? sanitize_key($_POST['outcome']) : '';

    if (empty($request_id) || !in_array($outcome, ['approve', 'reject'])) {
        wp_send_json_error(['message' => 'Invalid request data.']);
        return;
    }

    // Start a transaction
    $wpdb->query('START TRANSACTION');

    if ($outcome === 'reject') {
        $admin_notes = isset($_POST['admin_notes']) ? sanitize_textarea_field($_POST['admin_notes']) : '';
        $updated = $wpdb->update(
            $table_requests,
            ['status' => 'rejected', 'admin_notes' => $admin_notes, 'resolved_by' => get_current_user_id(), 'resolved_at' => current_time('mysql')],
            ['id' => $request_id, 'status' => 'pending']
        );
        if ($updated) {
            $wpdb->query('COMMIT');
            wp_send_json_success(['message' => 'Request rejected.']);
        } else {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(['message' => 'Failed to reject request. It might have been processed already.']);
        }
        return;
    }

    // --- Handle Approval ---
    $agent_id = isset($_POST['agent_id']) ? absint($_POST['agent_id']) : 0;
    $final_amount = isset($_POST['final_amount']) ? (float) $_POST['final_amount'] : 0;
    $admin_notes = isset($_POST['admin_notes']) ? sanitize_textarea_field($_POST['admin_notes']) : '';
    $payout_method = isset($_POST['payout_method']) ? sanitize_text_field($_POST['payout_method']) : 'Cash';
    $attachment_url = '';

    if (empty($agent_id) || $final_amount <= 0) {
        $wpdb->query('ROLLBACK');
        wp_send_json_error(['message' => 'Invalid agent ID or final amount.']);
        return;
    }

    // Handle file upload
    if (isset($_FILES['proof_attachment']) && $_FILES['proof_attachment']['error'] === UPLOAD_ERR_OK) {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        $movefile = wp_handle_upload($_FILES['proof_attachment'], ['test_form' => false]);
        if ($movefile && !isset($movefile['error'])) {
            $attachment_url = $movefile['url'];
        } else {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(['message' => 'File upload error: ' . $movefile['error']]);
            return;
        }
    }

    // Get original requested amount to determine status
    $original_amount = (float) $wpdb->get_var($wpdb->prepare("SELECT amount FROM $table_requests WHERE id = %d", $request_id));
    $status = ($final_amount < $original_amount) ? 'partially_paid' : 'approved';

    // 1. Update the request status
    $request_updated = $wpdb->update(
        $table_requests,
        ['status' => $status, 'final_amount' => $final_amount, 'admin_notes' => $admin_notes, 'resolved_by' => get_current_user_id(), 'resolved_at' => current_time('mysql')],
        ['id' => $request_id, 'status' => 'pending']
    );

    // 2. Insert the payout transaction
    $transaction_inserted = $wpdb->insert($table_transactions, [
        'agent_id' => $agent_id, 'type' => 'payout', 'amount' => -$final_amount,
        'notes' => $admin_notes, 'timestamp' => current_time('mysql'),
        'payout_method' => $payout_method, 'proof_attachment' => $attachment_url,
    ]);

    // 3. Subtract the amount from the agent's balance
    $balance_updated = $wpdb->query($wpdb->prepare("UPDATE $table_agents SET balance = balance - %f WHERE id = %d", $final_amount, $agent_id));

    if ($request_updated && $transaction_inserted && $balance_updated) {
        $wpdb->query('COMMIT');
        wp_send_json_success(['message' => 'Payout processed and request updated successfully.']);
    } else {
        $wpdb->query('ROLLBACK');
        wp_send_json_error(['message' => 'A database error occurred. The transaction has been rolled back.']);
    }
}
add_action('wp_ajax_manage_payout_request', 'custom_lottery_manage_payout_request_callback');

/**
 * AJAX handler for an agent to cancel their own payout request.
 */
function custom_lottery_agent_cancel_payout_request_callback() {
    $request_id = isset($_POST['request_id']) ? absint($_POST['request_id']) : 0;
    check_ajax_referer('agent_cancel_payout_request_' . $request_id, 'nonce');

    if (!current_user_can('enter_lottery_numbers') || !in_array('commission_agent', (array) wp_get_current_user()->roles)) {
        wp_send_json_error(['message' => 'Permission denied.']);
        return;
    }

    global $wpdb;
    $table_requests = $wpdb->prefix . 'lotto_payout_requests';
    $table_agents = $wpdb->prefix . 'lotto_agents';
    $current_user_id = get_current_user_id();
    $agent_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_agents WHERE user_id = %d", $current_user_id));

    if (!$agent_id) {
        wp_send_json_error(['message' => 'Could not verify your agent status.']);
        return;
    }

    // Security check: Make sure the agent owns this request
    $owner_agent_id = $wpdb->get_var($wpdb->prepare("SELECT agent_id FROM $table_requests WHERE id = %d", $request_id));
    if ($owner_agent_id != $agent_id) {
        wp_send_json_error(['message' => 'You do not have permission to cancel this request.']);
        return;
    }

    $deleted = $wpdb->delete($table_requests, ['id' => $request_id, 'status' => 'pending']);

    if ($deleted) {
        wp_send_json_success(['message' => 'Payout request cancelled successfully.']);
    } else {
        wp_send_json_error(['message' => 'Failed to cancel request. It may have already been processed by an admin.']);
    }
}
add_action('wp_ajax_agent_cancel_payout_request', 'custom_lottery_agent_cancel_payout_request_callback');


/**
 * AJAX handler for updating an agent's details.
 */
function custom_lottery_update_agent_callback() {
    check_ajax_referer('cl_save_agent_action', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied.']);
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'lotto_agents';

    // Validation and Sanitization
    $agent_id = isset($_POST['agent_id']) ? absint($_POST['agent_id']) : 0;
    $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
    $agent_type = isset($_POST['agent_type']) ? sanitize_text_field($_POST['agent_type']) : '';
    $commission_rate = isset($_POST['commission_rate']) ? (float) sanitize_text_field($_POST['commission_rate']) : 0.0;
    $per_number_limit = isset($_POST['per_number_limit']) ? (int) sanitize_text_field($_POST['per_number_limit']) : 0;
    $payout_threshold = isset($_POST['payout_threshold']) ? sanitize_text_field($_POST['payout_threshold']) : ''; // Keep as string for empty check, then cast
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
    $morning_open = isset($_POST['morning_open']) ? sanitize_text_field($_POST['morning_open']) : '';
    $morning_close = isset($_POST['morning_close']) ? sanitize_text_field($_POST['morning_close']) : '';
    $evening_open = isset($_POST['evening_open']) ? sanitize_text_field($_POST['evening_open']) : '';
    $evening_close = isset($_POST['evening_close']) ? sanitize_text_field($_POST['evening_close']) : '';

    if (empty($user_id) || empty($agent_type) || empty($status)) {
        wp_send_json_error(['message' => 'Missing required fields: User, Agent Type, or Status.']);
        return;
    }

    $data = [
        'user_id' => $user_id,
        'agent_type' => $agent_type,
        'commission_rate' => ($agent_type === 'commission') ? $commission_rate : 0,
        'per_number_limit' => ($agent_type === 'commission') ? $per_number_limit : 0,
        'payout_threshold' => !empty($payout_threshold) ? (float) $payout_threshold : null,
        'status' => $status,
        'morning_open' => !empty($morning_open) ? $morning_open : null,
        'morning_close' => !empty($morning_close) ? $morning_close : null,
        'evening_open' => !empty($evening_open) ? $evening_open : null,
        'evening_close' => !empty($evening_close) ? $evening_close : null,
    ];

    if ($agent_id > 0) {
        $result = $wpdb->update($table_name, $data, ['id' => $agent_id]);
        if ($result !== false) {
            wp_send_json_success(['message' => 'Agent updated successfully.']);
        } else {
            wp_send_json_error(['message' => 'Failed to update agent.']);
        }
    } else {
        $result = $wpdb->insert($table_name, $data);
        if ($result) {
            wp_send_json_success(['message' => 'Agent added successfully.']);
        } else {
            wp_send_json_error(['message' => 'Failed to add agent.']);
        }
    }
}
add_action('wp_ajax_update_agent', 'custom_lottery_update_agent_callback');