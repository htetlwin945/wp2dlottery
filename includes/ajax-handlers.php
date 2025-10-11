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

    // Get agent_id if the current user is a commission agent
    $agent_id = null;
    $current_user = wp_get_current_user();
    if (in_array('commission_agent', (array) $current_user->roles)) {
        $agent_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_agents WHERE user_id = %d", $current_user->ID));
    }

    // Get customer and session data
    $customer_name = sanitize_text_field($_POST['customer_name']);
    $phone = sanitize_text_field($_POST['phone']);
    $draw_session = sanitize_text_field($_POST['draw_session']);

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