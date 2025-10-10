<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Creates or updates the new commission-related database tables.
 */
function custom_lottery_commission_db_setup() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    // Table for individual commission records
    $table_ledger = $wpdb->prefix . 'lotto_commission_ledger';
    $sql_ledger = "CREATE TABLE $table_ledger (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        agent_id bigint(20) NOT NULL,
        entry_id bigint(20) NOT NULL,
        commission_amount decimal(10,2) NOT NULL,
        status varchar(20) DEFAULT 'unsettled' NOT NULL,
        settlement_id bigint(20) DEFAULT NULL,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id),
        KEY agent_id (agent_id),
        KEY entry_id (entry_id),
        KEY status (status),
        KEY settlement_id (settlement_id)
    ) $charset_collate;";
    dbDelta( $sql_ledger );

    // Table for settlement transactions
    $table_settlements = $wpdb->prefix . 'lotto_commission_settlements';
    $sql_settlements = "CREATE TABLE $table_settlements (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        agent_id bigint(20) NOT NULL,
        total_amount decimal(10,2) NOT NULL,
        settlement_date datetime NOT NULL,
        settled_by_user_id bigint(20) NOT NULL,
        notes text,
        PRIMARY KEY  (id),
        KEY agent_id (agent_id)
    ) $charset_collate;";
    dbDelta( $sql_settlements );
}

/**
 * A wrapper function to be called on plugin activation/update.
 */
function custom_lottery_apply_commission_schema() {
    custom_lottery_commission_db_setup();
}