<?php
/**
 * Plugin Name:       Custom 2-Digit Lottery
 * Plugin URI:        https://example.com/
 * Description:       A custom plugin to manage a 2-digit lottery system in WordPress.
 * Version:           1.1.0
 * Author:            Jules
 * Author URI:        https://example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       custom-lottery
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Define plugin constants
define( 'CUSTOM_LOTTERY_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'CUSTOM_LOTTERY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include the class files and functions.
require_once( CUSTOM_LOTTERY_PLUGIN_PATH . 'includes/class-lotto-entries-list-table.php' );
require_once( CUSTOM_LOTTERY_PLUGIN_PATH . 'includes/db-setup.php' );
require_once( CUSTOM_LOTTERY_PLUGIN_PATH . 'includes/admin-pages.php' );
require_once( CUSTOM_LOTTERY_PLUGIN_PATH . 'includes/ajax-handlers.php' );
require_once( CUSTOM_LOTTERY_PLUGIN_PATH . 'includes/cron-jobs.php' );
require_once( CUSTOM_LOTTERY_PLUGIN_PATH . 'includes/utils.php' );
require_once( CUSTOM_LOTTERY_PLUGIN_PATH . 'includes/user-roles.php' );
require_once( CUSTOM_LOTTERY_PLUGIN_PATH . 'includes/shortcodes.php' );

/**
 * Register activation and deactivation hooks.
 */
register_activation_hook( __FILE__, 'activate_custom_lottery_plugin' );
register_activation_hook( __FILE__, 'custom_lottery_add_roles' );
register_deactivation_hook( __FILE__, 'custom_lottery_clear_cron_jobs' );
register_deactivation_hook( __FILE__, 'custom_lottery_remove_roles' );

/**
 * Enqueue scripts and styles for the admin pages.
 */
function custom_lottery_enqueue_scripts($hook) {
    // Only load on our plugin's pages
    if (strpos($hook, 'custom-lottery') === false) {
        return;
    }

    // For the entry page
    if ($hook === 'lottery_page_custom-lottery-entry' || strpos($hook, 'custom-lottery-all-entries') !== false) {
        wp_enqueue_style('jquery-ui-css', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css');
        wp_enqueue_script(
            'custom-lottery-entry',
            CUSTOM_LOTTERY_PLUGIN_URL . 'js/lottery-entry.js',
            ['jquery', 'jquery-ui-autocomplete'],
            '1.1.0',
            true
        );
    }

    // For the dashboard page
    if ($hook === 'toplevel_page_custom-lottery-dashboard') {
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '3.7.0', true);
        wp_enqueue_script(
            'custom-lottery-dashboard',
            CUSTOM_LOTTERY_PLUGIN_URL . 'js/lottery-dashboard.js',
            ['chart-js', 'jquery'],
            '1.0.0',
            true
        );
    }
}
add_action('admin_enqueue_scripts', 'custom_lottery_enqueue_scripts');

/**
 * Enqueue scripts for the frontend portal.
 */
function custom_lottery_frontend_scripts() {
    // Only load the script if the shortcode is present on the page
    if (is_singular() && has_shortcode(get_post()->post_content, 'lottery_portal')) {
        wp_enqueue_script(
            'lottery-portal-js',
            CUSTOM_LOTTERY_PLUGIN_URL . 'js/lottery-portal.js',
            ['jquery'],
            '1.0.0',
            true
        );

        // Pass the AJAX URL to the script
        wp_localize_script(
            'lottery-portal-js',
            'lottery_portal_ajax',
            ['ajax_url' => admin_url('admin-ajax.php')]
        );
    }
}
add_action('wp_enqueue_scripts', 'custom_lottery_frontend_scripts');