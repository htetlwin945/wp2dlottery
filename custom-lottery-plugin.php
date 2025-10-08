<?php
/**
 * Plugin Name:       Custom 2-Digit Lottery
 * Plugin URI:        https://example.com/
 * Description:       A custom plugin to manage a 2-digit lottery system in WordPress.
 * Version:           1.3.3
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
define( 'CUSTOM_LOTTERY_VERSION', '1.3.3' );
define( 'CUSTOM_LOTTERY_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'CUSTOM_LOTTERY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include the class files and functions.
require_once( CUSTOM_LOTTERY_PLUGIN_PATH . 'includes/class-lotto-entries-list-table.php' );
require_once( CUSTOM_LOTTERY_PLUGIN_PATH . 'includes/class-lotto-customers-list-table.php' );
require_once( CUSTOM_LOTTERY_PLUGIN_PATH . 'includes/db-setup.php' );
require_once( CUSTOM_LOTTERY_PLUGIN_PATH . 'includes/admin-pages.php' );
require_once( CUSTOM_LOTTERY_PLUGIN_PATH . 'includes/ajax-handlers.php' );
require_once( CUSTOM_LOTTERY_PLUGIN_PATH . 'includes/cron-jobs.php' );
require_once( CUSTOM_LOTTERY_PLUGIN_PATH . 'includes/utils.php' );
require_once( CUSTOM_LOTTERY_PLUGIN_PATH . 'includes/user-roles.php' );
require_once( CUSTOM_LOTTERY_PLUGIN_PATH . 'includes/shortcodes.php' );
require_once( CUSTOM_LOTTERY_PLUGIN_PATH . 'includes/dashboard-widgets.php' );
require_once( CUSTOM_LOTTERY_PLUGIN_PATH . 'includes/class-lotto-winning-numbers-widget.php' );
require_once( CUSTOM_LOTTERY_PLUGIN_PATH . 'includes/settings-page.php' );

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

    // For pages with the lottery entry form (Lottery Entry & All Entries)
    if ($hook === 'lottery_page_custom-lottery-entry' || strpos($hook, 'custom-lottery-all-entries') !== false) {
        wp_enqueue_style('jquery-ui-css', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css');
        wp_enqueue_script(
            'custom-lottery-entry',
            CUSTOM_LOTTERY_PLUGIN_URL . 'js/lottery-entry.js',
            ['jquery', 'jquery-ui-autocomplete'],
            '1.2.0',
            true
        );
    }

    // For the All Entries page (additionally load popup logic)
    if (strpos($hook, 'custom-lottery-all-entries') !== false) {
        wp_enqueue_script(
            'custom-lottery-all-entries',
            CUSTOM_LOTTERY_PLUGIN_URL . 'js/all-entries.js',
            ['jquery', 'jquery-ui-dialog', 'custom-lottery-entry'],
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
            ['chart-js', 'jquery', 'dashboard', 'postbox'],
            '1.0.0',
            true
        );
        wp_enqueue_script(
            'custom-lottery-dashboard-widgets',
            CUSTOM_LOTTERY_PLUGIN_URL . 'js/dashboard-widgets.js',
            ['jquery'],
            CUSTOM_LOTTERY_VERSION,
            true
        );
    }

    // For the Tools page, which includes the manual import button
    if ($hook === 'lottery_page_custom-lottery-tools') {
        wp_enqueue_script(
            'custom-lottery-admin-scripts',
            CUSTOM_LOTTERY_PLUGIN_URL . 'js/admin-scripts.js',
            ['jquery'],
            CUSTOM_LOTTERY_VERSION,
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

/**
 * Check the plugin version and run the activation function if the version has changed.
 */
function custom_lottery_check_version() {
    if ( get_site_option( 'custom_lottery_version' ) != CUSTOM_LOTTERY_VERSION ) {
        activate_custom_lottery_plugin();
        update_site_option( 'custom_lottery_version', CUSTOM_LOTTERY_VERSION );
    }
}
add_action( 'plugins_loaded', 'custom_lottery_check_version' );