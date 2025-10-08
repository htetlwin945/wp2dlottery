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
function custom_lottery_update_or_create_customer($name, $phone) {
    global $wpdb;
    $table_customers = $wpdb->prefix . 'lotto_customers';

    $wpdb->query($wpdb->prepare(
        "INSERT INTO $table_customers (customer_name, phone, last_seen) VALUES (%s, %s, %s)
         ON DUPLICATE KEY UPDATE customer_name = %s, last_seen = %s",
        $name,
        $phone,
        current_time('mysql'),
        $name,
        current_time('mysql')
    ));
}

/**
 * Checks if a number's potential payout exceeds total sales and blocks it if necessary.
 */
function check_and_auto_block_number($number, $session, $date) {
    global $wpdb;
    $table_entries = $wpdb->prefix . 'lotto_entries';
    $table_limits = $wpdb->prefix . 'lotto_limits';

    $start_datetime = $date . ' 00:00:00';
    $end_datetime = $date . ' 23:59:59';

    $total_sales = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(amount) FROM $table_entries WHERE draw_session = %s AND timestamp BETWEEN %s AND %s",
        $session, $start_datetime, $end_datetime
    ));

    $number_total_amount = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(amount) FROM $table_entries WHERE lottery_number = %s AND draw_session = %s AND timestamp BETWEEN %s AND %s",
        $number, $session, $start_datetime, $end_datetime
    ));

    $potential_payout = $number_total_amount * 80;

    if ($potential_payout > $total_sales) {
        $is_already_blocked = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_limits WHERE lottery_number = %s AND draw_date = %s AND draw_session = %s",
            $number, $date, $session
        ));

        if (!$is_already_blocked) {
            $wpdb->insert($table_limits, [
                'lottery_number' => $number,
                'draw_date' => $date,
                'draw_session' => $session,
                'limit_type' => 'auto'
            ]);
        }
    }
}

/**
 * Determines the current active lottery session based on the time.
 *
 * @return string|null The current session ('12:01 PM' or '4:30 PM') or null if no session is active.
 */
function custom_lottery_get_current_session() {
    $timezone = new DateTimeZone('Asia/Yangon');
    $current_time = new DateTime('now', $timezone);
    $time_1201 = new DateTime($current_time->format('Y-m-d') . ' 12:01:00', $timezone);
    $time_1630 = new DateTime($current_time->format('Y-m-d') . ' 16:30:00', $timezone);

    if ($current_time <= $time_1201) {
        return '12:01 PM';
    } elseif ($current_time > $time_1201 && $current_time <= $time_1630) {
        return '4:30 PM';
    } else {
        return null; // No active session
    }
}