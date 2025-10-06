<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   Custom_Lottery
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;
$table_name_entries = $wpdb->prefix . 'lottery_entries';
$table_name_limits = $wpdb->prefix . 'lottery_limits';
$table_name_audit_log = $wpdb->prefix . 'lottery_audit_log';

$wpdb->query( "DROP TABLE IF EXISTS {$table_name_entries}" );
$wpdb->query( "DROP TABLE IF EXISTS {$table_name_limits}" );
$wpdb->query( "DROP TABLE IF EXISTS {$table_name_audit_log}" );