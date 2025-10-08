<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Ensure utility functions are available for our callbacks.
require_once( CUSTOM_LOTTERY_PLUGIN_PATH . 'includes/utils.php' );

/**
 * Register all custom REST API endpoints.
 */
function custom_lottery_register_rest_routes() {
    $namespace = 'lottery/v1';

    // Register the dashboard data endpoint
    register_rest_route( $namespace, '/dashboard', [
        'methods'             => 'GET',
        'callback'            => 'custom_lottery_get_dashboard_rest_data',
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ] );

    // Register customer endpoints
    custom_lottery_register_customer_rest_routes();

    // Register lottery entry endpoints
    custom_lottery_register_entry_rest_routes();
}
add_action( 'rest_api_init', 'custom_lottery_register_rest_routes' );

/**
 * REST API callback for getting dashboard data.
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response The response object.
 */
function custom_lottery_get_dashboard_rest_data( WP_REST_Request $request ) {
    // This can be expanded later with real data fetching logic from the dashboard.
    $data = [
        'message' => 'Welcome to the dashboard!',
    ];

    return new WP_REST_Response( $data, 200 );
}


// --- Customer Endpoints ---

/**
 * Register customer REST API endpoints.
 */
function custom_lottery_register_customer_rest_routes() {
    $namespace = 'lottery/v1';

    // Search for customers
    register_rest_route( $namespace, '/customers/search', [
        'methods'             => 'GET',
        'callback'            => 'custom_lottery_search_customers_rest',
        'permission_callback' => function () {
            return current_user_can( 'enter_lottery_numbers' );
        },
    ] );

    // Create a customer
    register_rest_route( $namespace, '/customers', [
        'methods'             => 'POST',
        'callback'            => 'custom_lottery_create_customer',
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ] );

    // Update a customer
    register_rest_route( $namespace, '/customers/(?P<id>\d+)', [
        'methods'             => 'PUT',
        'callback'            => 'custom_lottery_update_customer',
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ] );

    // Delete a customer
    register_rest_route( $namespace, '/customers/(?P<id>\d+)', [
        'methods'             => 'DELETE',
        'callback'            => 'custom_lottery_delete_customer',
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ] );
}

// --- Lottery Entry Endpoints ---

/**
 * Register lottery entry REST API endpoints.
 */
function custom_lottery_register_entry_rest_routes() {
    $namespace = 'lottery/v1';

    // Submit new entries
    register_rest_route( $namespace, '/entries', [
        'methods'             => 'POST',
        'callback'            => 'custom_lottery_submit_entries_rest',
        'permission_callback' => function () {
            return current_user_can( 'enter_lottery_numbers' );
        },
    ] );
}

/**
 * Callback to create a new customer.
 */
function custom_lottery_create_customer( WP_REST_Request $request ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'lotto_customers';

    $customer_name = sanitize_text_field( $request->get_param( 'customer_name' ) );
    $phone = sanitize_text_field( $request->get_param( 'phone' ) );

    if ( empty( $customer_name ) || empty( $phone ) ) {
        return new WP_Error( 'missing_fields', 'Customer name and phone are required.', [ 'status' => 400 ] );
    }

    $data = [
        'customer_name' => $customer_name,
        'phone'         => $phone,
        'last_seen'     => current_time( 'mysql' ),
    ];

    $wpdb->insert( $table_name, $data );
    $new_id = $wpdb->insert_id;

    custom_lottery_log_action('customer_added_api', ['customer_id' => $new_id, 'data' => $data]);

    $new_customer = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $new_id ) );

    return new WP_REST_Response( $new_customer, 201 );
}

/**
 * Callback to update an existing customer.
 */
function custom_lottery_update_customer( WP_REST_Request $request ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'lotto_customers';
    $id = (int) $request['id'];

    $original_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id), ARRAY_A);
    if (!$original_data) {
        return new WP_Error( 'not_found', 'Customer not found.', [ 'status' => 404 ] );
    }

    $customer_name = sanitize_text_field( $request->get_param( 'customer_name' ) );
    $phone = sanitize_text_field( $request->get_param( 'phone' ) );

    $data = [];
    if ( ! empty( $customer_name ) ) {
        $data['customer_name'] = $customer_name;
    }
    if ( ! empty( $phone ) ) {
        $data['phone'] = $phone;
    }

    if ( empty( $data ) ) {
        return new WP_Error( 'no_data', 'No data provided to update.', [ 'status' => 400 ] );
    }

    $wpdb->update( $table_name, $data, [ 'id' => $id ] );

    custom_lottery_log_action('customer_edited_api', ['customer_id' => $id, 'original_data' => $original_data, 'new_data' => $data]);

    $updated_customer = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ) );

    return new WP_REST_Response( $updated_customer, 200 );
}

/**
 * Callback to delete a customer.
 */
function custom_lottery_delete_customer( WP_REST_Request $request ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'lotto_customers';
    $id = (int) $request['id'];

    $customer_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id), ARRAY_A);
    if (!$customer_data) {
        return new WP_Error( 'not_found', 'Customer not found.', [ 'status' => 404 ] );
    }

    $deleted = $wpdb->delete( $table_name, [ 'id' => $id ] );

    if ( ! $deleted ) {
        return new WP_Error( 'delete_failed', 'Failed to delete customer.', [ 'status' => 500 ] );
    }

    custom_lottery_log_action('customer_deleted_api', ['customer_id' => $id, 'deleted_data' => $customer_data]);

    return new WP_REST_Response( [ 'message' => 'Customer deleted successfully.' ], 200 );
}

/**
 * REST API callback for searching customers.
 */
function custom_lottery_search_customers_rest( WP_REST_Request $request ) {
    global $wpdb;
    $table_customers = $wpdb->prefix . 'lotto_customers';

    $term = sanitize_text_field( $request->get_param( 'term' ) );

    if ( empty( $term ) ) {
        return new WP_REST_Response( [], 200 );
    }

    $results = $wpdb->get_results( $wpdb->prepare(
        "SELECT id, customer_name, phone FROM $table_customers WHERE customer_name LIKE %s OR phone LIKE %s LIMIT 10",
        '%' . $wpdb->esc_like( $term ) . '%',
        '%' . $wpdb->esc_like( $term ) . '%'
    ) );

    return new WP_REST_Response( $results, 200 );
}

/**
 * REST API callback for submitting lottery entries.
 */
function custom_lottery_submit_entries_rest( WP_REST_Request $request ) {
    global $wpdb;
    $table_entries = $wpdb->prefix . 'lotto_entries';
    $table_limits = $wpdb->prefix . 'lotto_limits';

    $timezone = new DateTimeZone('Asia/Yangon');
    $current_datetime = new DateTime('now', $timezone);
    $current_date = $current_datetime->format('Y-m-d');

    // Get data from request
    $customer_name = sanitize_text_field($request->get_param('customer_name'));
    $phone = sanitize_text_field($request->get_param('phone'));
    $draw_session = sanitize_text_field($request->get_param('draw_session'));
    $entries = $request->get_param('entries');

    if (empty($customer_name) || empty($phone) || empty($draw_session) || empty($entries) || !is_array($entries)) {
        return new WP_Error( 'missing_fields', 'Missing or invalid form data.', [ 'status' => 400 ] );
    }

    custom_lottery_update_or_create_customer($customer_name, $phone);

    $success_count = 0;
    $total_amount = 0;
    $error_messages = [];

    foreach ($entries as $entry) {
        $lottery_number = sanitize_text_field($entry['number']);
        $amount = absint($entry['amount']);
        $is_reverse = filter_var($entry['is_reverse'], FILTER_VALIDATE_BOOLEAN);

        if (!preg_match('/^\d{2}$/', $lottery_number) || empty($amount)) {
            $error_messages[] = "Invalid data for number {$lottery_number}.";
            continue;
        }

        // Process the main number
        $is_blocked = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_limits WHERE lottery_number = %s AND draw_date = %s AND draw_session = %s", $lottery_number, $current_date, $draw_session));
        if ($is_blocked) {
            $error_messages[] = "Number {$lottery_number} is blocked.";
            continue;
        }

        $wpdb->insert($table_entries, ['customer_name' => $customer_name, 'phone' => $phone, 'lottery_number' => $lottery_number, 'amount' => $amount, 'draw_session' => $draw_session, 'timestamp' => $current_datetime->format('Y-m-d H:i:s')]);
        check_and_auto_block_number($lottery_number, $draw_session, $current_date);
        $success_count++;
        $total_amount += $amount;

        // Process the reversed number if applicable
        if ($is_reverse) {
            $reversed_number = strrev($lottery_number);
            if ($lottery_number !== $reversed_number) {
                $is_rev_blocked = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_limits WHERE lottery_number = %s AND draw_date = %s AND draw_session = %s", $reversed_number, $current_date, $draw_session));
                if ($is_rev_blocked) {
                    $error_messages[] = "Reversed number {$reversed_number} is blocked.";
                    continue;
                }
                $wpdb->insert($table_entries, ['customer_name' => $customer_name, 'phone' => $phone, 'lottery_number' => $reversed_number, 'amount' => $amount, 'draw_session' => $draw_session, 'timestamp' => $current_datetime->format('Y-m-d H:i:s')]);
                check_and_auto_block_number($reversed_number, $draw_session, $current_date);
                $success_count++;
                $total_amount += $amount;
            }
        }
    }

    if (!empty($error_messages)) {
         return new WP_Error( 'entry_error', implode(' ', $error_messages), [ 'status' => 400 ] );
    }

    return new WP_REST_Response([
        'message' => "Transaction complete. {$success_count} entries added successfully.",
        'total_amount' => $total_amount,
    ], 201 );
}