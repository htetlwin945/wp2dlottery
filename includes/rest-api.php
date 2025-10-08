<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

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

    // Get all customers
    register_rest_route( $namespace, '/customers', [
        'methods'             => 'GET',
        'callback'            => 'custom_lottery_get_customers',
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
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

    // Get a single customer
    register_rest_route( $namespace, '/customers/(?P<id>\d+)', [
        'methods'             => 'GET',
        'callback'            => 'custom_lottery_get_customer',
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

/**
 * Callback to get all customers.
 */
function custom_lottery_get_customers( WP_REST_Request $request ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'lotto_customers';
    $customers = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY id DESC" );

    return new WP_REST_Response( $customers, 200 );
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
 * Callback to get a single customer by ID.
 */
function custom_lottery_get_customer( WP_REST_Request $request ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'lotto_customers';
    $id = (int) $request['id'];

    $customer = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ) );

    if ( ! $customer ) {
        return new WP_Error( 'not_found', 'Customer not found.', [ 'status' => 404 ] );
    }

    return new WP_REST_Response( $customer, 200 );
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