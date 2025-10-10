<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Apply database schema modifications for the plugin.
 * This function is called on plugin activation or version update.
 */
function custom_lottery_apply_schema_mods() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    // Define the schema for the modification requests table.
    // dbDelta will create the table if it doesn't exist, or alter it if it does.
    $table_name = $wpdb->prefix . 'lotto_modification_requests';
    $sql_requests = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        entry_id mediumint(9) NOT NULL,
        agent_id mediumint(9) NOT NULL,
        request_notes text NOT NULL,
        new_lottery_number VARCHAR(2) DEFAULT NULL,
        new_amount DECIMAL(10,2) DEFAULT NULL,
        status varchar(20) DEFAULT 'pending' NOT NULL, -- pending, approved, rejected
        requested_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        resolved_by mediumint(9) NULL,
        resolved_at datetime NULL,
        PRIMARY KEY  (id),
        KEY entry_id (entry_id),
        KEY agent_id (agent_id)
    ) $charset_collate;";
    dbDelta( $sql_requests );

    // Add 'has_mod_request' column to lotto_entries table if it doesn't exist.
    $table_entries = $wpdb->prefix . 'lotto_entries';
    $column_name = 'has_mod_request';

    $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '{$table_entries}' AND column_name = '{$column_name}'"  );

    if(empty($row)){
       $wpdb->query("ALTER TABLE {$table_entries} ADD {$column_name} TINYINT(1) NOT NULL DEFAULT 0");
    }
}