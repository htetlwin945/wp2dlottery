<?php
/**
 * Plugin Name: Custom 2-Digit Lottery
 * Description: A WordPress plugin to manage a 2-digit lottery system.
 * Version: 1.0.0
 * Author: Jules
 * Author URI: https://example.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: custom-lottery
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-custom-lottery-activator.php
 */
function activate_custom_lottery() {
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $table_name_entries = $wpdb->prefix . 'lottery_entries';
    $sql_entries = "CREATE TABLE $table_name_entries (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        customer_name tinytext NOT NULL,
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

    $table_name_limits = $wpdb->prefix . 'lottery_limits';
    $sql_limits = "CREATE TABLE $table_name_limits (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        lottery_number varchar(2) NOT NULL,
        draw_date date NOT NULL,
        draw_session varchar(10) NOT NULL,
        is_blocked tinyint(1) DEFAULT 0 NOT NULL,
        limit_type varchar(20) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql_limits );

    $table_name_audit_log = $wpdb->prefix . 'lottery_audit_log';
    $sql_audit_log = "CREATE TABLE $table_name_audit_log (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        action varchar(255) NOT NULL,
        details longtext NOT NULL,
        timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql_audit_log );
}

register_activation_hook( __FILE__, 'activate_custom_lottery' );

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_custom_lottery() {
    // No action needed on deactivation for now.
    // This is a placeholder for future logic.
}

register_deactivation_hook( __FILE__, 'deactivate_custom_lottery' );