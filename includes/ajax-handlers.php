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

    $payout_data = $wpdb->get_results($wpdb->prepare(
        "SELECT DATE(timestamp) as entry_date, SUM(amount * 80) as total_payouts
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
    check_ajax_referer('lottery_entry_action', 'lottery_entry_nonce');

    global $wpdb;
    $table_customers = $wpdb->prefix . 'lotto_customers';

    $term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';

    if (empty($term)) {
        wp_send_json([]);
    }

    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT customer_name, phone FROM $table_customers WHERE phone LIKE %s LIMIT 10",
        '%' . $wpdb->esc_like($term) . '%'
    ));

    $suggestions = [];
    foreach ($results as $result) {
        $suggestions[] = [
            'label' => $result->customer_name . ' (' . $result->phone . ')',
            'value' => $result->phone,
            'name'  => $result->customer_name
        ];
    }

    wp_send_json($suggestions);
}
add_action('wp_ajax_search_customers', 'custom_lottery_search_customers_callback');


/**
 * AJAX handler for adding a new lottery entry.
 */
function add_lottery_entry_callback() {
    check_ajax_referer('lottery_entry_action', 'lottery_entry_nonce');

    global $wpdb;
    $table_entries = $wpdb->prefix . 'lotto_entries';
    $table_limits = $wpdb->prefix . 'lotto_limits';

    $timezone = new DateTimeZone('Asia/Yangon');
    $current_datetime = new DateTime('now', $timezone);
    $current_date = $current_datetime->format('Y-m-d');

    $customer_name = sanitize_text_field($_POST['customer_name']);
    $phone = sanitize_text_field($_POST['phone']);
    $lottery_number = sanitize_text_field($_POST['lottery_number']);
    $amount = absint($_POST['amount']);
    $draw_session = sanitize_text_field($_POST['draw_session']);
    $is_reverse = isset($_POST['reverse_entry']) && $_POST['reverse_entry'] == '1';

    if (empty($customer_name) || empty($phone) || !preg_match('/^\d{2}$/', $lottery_number) || empty($amount) || empty($draw_session)) {
        wp_send_json_error('All fields are required and must be in the correct format.');
        return;
    }

    custom_lottery_update_or_create_customer($customer_name, $phone);

    $is_blocked = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table_limits WHERE lottery_number = %s AND draw_date = %s AND draw_session = %s",
        $lottery_number, $current_date, $draw_session
    ));

    if ($is_blocked) {
        wp_send_json_error("Number {$lottery_number} is currently blocked for this session.");
        return;
    }

    $wpdb->insert($table_entries, [
        'customer_name' => $customer_name,
        'phone' => $phone,
        'lottery_number' => $lottery_number,
        'amount' => $amount,
        'draw_session' => $draw_session,
        'timestamp' => $current_datetime->format('Y-m-d H:i:s'),
    ]);

    check_and_auto_block_number($lottery_number, $draw_session, $current_date);
    $message = "Entry for {$lottery_number} added successfully.";

    if ($is_reverse) {
        $reversed_number = strrev($lottery_number);
        if ($lottery_number !== $reversed_number) {
            $is_rev_blocked = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_limits WHERE lottery_number = %s AND draw_date = %s AND draw_session = %s",
                $reversed_number, $current_date, $draw_session
            ));

            if ($is_rev_blocked) {
                wp_send_json_error("Cannot add reversed entry: Number {$reversed_number} is currently blocked for this session.");
                return;
            }

            $wpdb->insert($table_entries, [
                'customer_name' => $customer_name,
                'phone' => $phone,
                'lottery_number' => $reversed_number,
                'amount' => $amount,
                'draw_session' => $draw_session,
                'timestamp' => $current_datetime->format('Y-m-d H:i:s'),
            ]);
            check_and_auto_block_number($reversed_number, $draw_session, $current_date);
            $message .= " Reversed entry for {$reversed_number} also added.";
        }
    }

    wp_send_json_success($message);
}
add_action('wp_ajax_add_lottery_entry', 'add_lottery_entry_callback');