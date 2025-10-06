<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * The code that runs during plugin activation.
 * This function creates the necessary database tables.
 */
function activate_custom_lottery_plugin() {
    global $wpdb;
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    $charset_collate = $wpdb->get_charset_collate();

    // Table for lottery entries
    $table_name_entries = $wpdb->prefix . 'lotto_entries';
    $sql_entries = "CREATE TABLE $table_name_entries (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        customer_name varchar(255) NOT NULL,
        phone varchar(20) NOT NULL,
        lottery_number varchar(2) NOT NULL,
        amount decimal(10, 2) NOT NULL,
        draw_session varchar(10) NOT NULL,
        timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        is_winner tinyint(1) DEFAULT 0 NOT NULL,
        paid_status tinyint(1) DEFAULT 0 NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql_entries );

    // Table for number limits/blocks
    $table_name_limits = $wpdb->prefix . 'lotto_limits';
    $sql_limits = "CREATE TABLE $table_name_limits (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        lottery_number varchar(2) NOT NULL,
        draw_date date NOT NULL,
        draw_session varchar(10) NOT NULL,
        is_blocked tinyint(1) DEFAULT 1 NOT NULL,
        limit_type varchar(10) NOT NULL, -- 'manual' or 'auto'
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql_limits );

    // Table for audit log
    $table_name_audit = $wpdb->prefix . 'lotto_audit_log';
    $sql_audit = "CREATE TABLE $table_name_audit (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        action varchar(255) NOT NULL,
        details text NOT NULL,
        timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql_audit );

    // Table for customers - ensuring this is created on activation/update
    $table_name_customers = $wpdb->prefix . 'lotto_customers';
    $sql_customers = "CREATE TABLE $table_name_customers (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        customer_name varchar(255) NOT NULL,
        phone varchar(20) NOT NULL,
        last_seen datetime NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY phone (phone)
    ) $charset_collate;";
    dbDelta( $sql_customers );
}