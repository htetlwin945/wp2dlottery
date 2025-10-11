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

    // Check if the modification requests table exists and create it if not.
    $table_name = $wpdb->prefix . 'lotto_modification_requests';
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            entry_id mediumint(9) NOT NULL,
            agent_id mediumint(9) NOT NULL,
            request_notes text NOT NULL,
            status varchar(20) DEFAULT 'pending' NOT NULL, -- pending, approved, rejected
            requested_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            resolved_by mediumint(9) NULL,
            resolved_at datetime NULL,
            PRIMARY KEY  (id),
            KEY entry_id (entry_id),
            KEY agent_id (agent_id)
        ) $charset_collate;";
        dbDelta( $sql );
    }

    // Add 'has_mod_request' column to lotto_entries table if it doesn't exist.
    $table_entries = $wpdb->prefix . 'lotto_entries';
    $column_name = 'has_mod_request';

    $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '{$table_entries}' AND column_name = '{$column_name}'"  );

    if(empty($row)){
       $wpdb->query("ALTER TABLE {$table_entries} ADD {$column_name} TINYINT(1) NOT NULL DEFAULT 0");
    }

    // Add agent-specific session time columns to the agents table
    $table_agents_name = $wpdb->prefix . 'lotto_agents';
    $agent_columns = [
        'morning_open'  => 'TIME NULL DEFAULT NULL',
        'morning_close' => 'TIME NULL DEFAULT NULL',
        'evening_open'  => 'TIME NULL DEFAULT NULL',
        'evening_close' => 'TIME NULL DEFAULT NULL',
    ];

    foreach ($agent_columns as $column => $type) {
        if (empty($wpdb->get_results("SHOW COLUMNS FROM {$table_agents_name} LIKE '{$column}'"))) {
            $wpdb->query("ALTER TABLE {$table_agents_name} ADD {$column} {$type}");
        }
    }
}