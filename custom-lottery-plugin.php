<?php
/**
 * Plugin Name:       Custom 2-Digit Lottery
 * Plugin URI:        https://example.com/
 * Description:       A custom plugin to manage a 2-digit lottery system in WordPress.
 * Version:           1.0.0
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
}
register_activation_hook( __FILE__, 'activate_custom_lottery_plugin' );

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_custom_lottery_plugin() {
    // Deactivation code will go here.
}
register_deactivation_hook( __FILE__, 'deactivate_custom_lottery_plugin' );

/**
 * Register the admin menu pages.
 */
function custom_lottery_admin_menu() {
    add_menu_page(
        __( 'Lottery', 'custom-lottery' ),
        __( 'Lottery', 'custom-lottery' ),
        'manage_options',
        'custom-lottery',
        'custom_lottery_entry_page_callback',
        'dashicons-tickets-alt',
        20
    );

    add_submenu_page(
        'custom-lottery',
        __( 'Lottery Entry', 'custom-lottery' ),
        __( 'Lottery Entry', 'custom-lottery' ),
        'manage_options',
        'custom-lottery',
        'custom_lottery_entry_page_callback'
    );

    add_submenu_page(
        'custom-lottery',
        __( 'Reports', 'custom-lottery' ),
        __( 'Reports', 'custom-lottery' ),
        'manage_options',
        'custom-lottery-reports',
        'custom_lottery_reports_page_callback'
    );

    add_submenu_page(
        'custom-lottery',
        __( 'Payouts', 'custom-lottery' ),
        __( 'Payouts', 'custom-lottery' ),
        'manage_options',
        'custom-lottery-payouts',
        'custom_lottery_payouts_page_callback'
    );

    add_submenu_page(
        'custom-lottery',
        __( 'Number Limiting', 'custom-lottery' ),
        __( 'Number Limiting', 'custom-lottery' ),
        'manage_options',
        'custom-lottery-limits',
        'custom_lottery_limits_page_callback'
    );

    add_submenu_page(
        'custom-lottery',
        __( 'All Entries', 'custom-lottery' ),
        __( 'All Entries', 'custom-lottery' ),
        'manage_options',
        'custom-lottery-all-entries',
        'custom_lottery_all_entries_page_callback'
    );
}
add_action( 'admin_menu', 'custom_lottery_admin_menu' );

// Include the WP_List_Table class file
require_once(plugin_dir_path(__FILE__) . 'includes/class-lotto-entries-list-table.php');

/**
 * Callback function for the All Entries page.
 */
function custom_lottery_all_entries_page_callback() {
    global $wpdb;
    $table_entries = $wpdb->prefix . 'lotto_entries';
    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

    // Handle Edit form submission
    if (isset($_POST['submit_edit_entry']) && check_admin_referer('cl_edit_entry_action', 'cl_edit_entry_nonce')) {
        $entry_id = absint($_POST['entry_id']);
        $original_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_entries WHERE id = %d", $entry_id), ARRAY_A);

        $data_to_update = [
            'customer_name' => sanitize_text_field($_POST['customer_name']),
            'phone' => sanitize_text_field($_POST['phone']),
            'lottery_number' => sanitize_text_field($_POST['lottery_number']),
            'amount' => absint($_POST['amount']),
        ];

        if ($wpdb->update($table_entries, $data_to_update, ['id' => $entry_id])) {
            custom_lottery_log_action('entry_edited', ['entry_id' => $entry_id, 'original_data' => $original_data, 'new_data' => $data_to_update]);
            echo '<div class="updated"><p>Entry updated successfully.</p></div>';
        }
        $action = ''; // Reset action to show the table
    }

    // Handle Delete action
    if ($action === 'delete' && !empty($_GET['entry_id'])) {
        $entry_id = absint($_GET['entry_id']);
        if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'cl_delete_entry')) {
            $entry_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_entries WHERE id = %d", $entry_id), ARRAY_A);
            if ($wpdb->delete($table_entries, ['id' => $entry_id])) {
                custom_lottery_log_action('entry_deleted', ['entry_id' => $entry_id, 'deleted_data' => $entry_data]);
                echo '<div class="updated"><p>Entry deleted successfully.</p></div>';
            }
        }
        $action = ''; // Reset action
    }

    if ($action === 'edit' && !empty($_GET['entry_id'])) {
        // Display Edit Form
        $entry_id = absint($_GET['entry_id']);
        $entry = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_entries WHERE id = %d", $entry_id));
        if ($entry) {
            ?>
            <div class="wrap">
                <h1><?php echo esc_html__('Edit Lottery Entry', 'custom-lottery'); ?></h1>
                <form method="post">
                    <input type="hidden" name="entry_id" value="<?php echo esc_attr($entry->id); ?>">
                    <?php wp_nonce_field('cl_edit_entry_action', 'cl_edit_entry_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="customer_name">Customer Name</label></th>
                            <td><input type="text" id="customer_name" name="customer_name" value="<?php echo esc_attr($entry->customer_name); ?>" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="phone">Phone</label></th>
                            <td><input type="text" id="phone" name="phone" value="<?php echo esc_attr($entry->phone); ?>" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="lottery_number">Lottery Number</label></th>
                            <td><input type="text" id="lottery_number" name="lottery_number" value="<?php echo esc_attr($entry->lottery_number); ?>" maxlength="2" pattern="\d{2}" class="small-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="amount">Amount</label></th>
                            <td><input type="number" id="amount" name="amount" value="<?php echo esc_attr($entry->amount); ?>" class="small-text" required></td>
                        </tr>
                    </table>
                    <?php submit_button(__('Save Changes'), 'primary', 'submit_edit_entry'); ?>
                </form>
            </div>
            <?php
        }
    } else {
        // Display List Table
        $lotto_list_table = new Lotto_Entries_List_Table();
        $lotto_list_table->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html__('All Lottery Entries', 'custom-lottery'); ?></h1>
            <form method="post">
                <?php $lotto_list_table->display(); ?>
            </form>
        </div>
        <?php
    }
}

/**
 * Logs a specific admin action to the audit log table.
 */
function custom_lottery_log_action($action, $details) {
    global $wpdb;
    $table_audit = $wpdb->prefix . 'lotto_audit_log';

    $wpdb->insert($table_audit, [
        'user_id' => get_current_user_id(),
        'action' => $action,
        'details' => wp_json_encode($details),
        'timestamp' => current_time('mysql'),
    ]);
}

/**
 * Callback function for the Lottery Entry page.
 */
function custom_lottery_entry_page_callback() {
    // Set the timezone to Myanmar time.
    $timezone = new DateTimeZone('Asia/Yangon');
    $current_time = new DateTime('now', $timezone);
    $time_1201 = new DateTime($current_time->format('Y-m-d') . ' 12:01:00', $timezone);
    $time_1630 = new DateTime($current_time->format('Y-m-d') . ' 16:30:00', $timezone);

    // Determine the default draw session.
    $default_session = '12:01 PM';
    if ($current_time > $time_1201 && $current_time <= $time_1630) {
        $default_session = '4:30 PM';
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__( 'Lottery Entry', 'custom-lottery' ); ?></h1>
        <form id="lottery-entry-form" method="post">
            <?php wp_nonce_field( 'lottery_entry_action', 'lottery_entry_nonce' ); ?>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><label for="customer-name"><?php echo esc_html__( 'Customer Name', 'custom-lottery' ); ?></label></th>
                        <td><input type="text" id="customer-name" name="customer_name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="phone"><?php echo esc_html__( 'Phone', 'custom-lottery' ); ?></label></th>
                        <td><input type="text" id="phone" name="phone" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lottery-number"><?php echo esc_html__( 'Lottery Number', 'custom-lottery' ); ?></label></th>
                        <td><input type="text" id="lottery-number" name="lottery_number" maxlength="2" pattern="\d{2}" class="small-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="amount"><?php echo esc_html__( 'Amount (Kyat)', 'custom-lottery' ); ?></label></th>
                        <td><input type="number" id="amount" name="amount" class="small-text" step="100" min="0" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="reverse-entry"><?php echo esc_html__( 'Reverse ("R")', 'custom-lottery' ); ?></label></th>
                        <td><input type="checkbox" id="reverse-entry" name="reverse_entry" value="1"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="draw-session"><?php echo esc_html__( 'Draw Session', 'custom-lottery' ); ?></label></th>
                        <td>
                            <select id="draw-session" name="draw_session">
                                <option value="12:01 PM" <?php selected( $default_session, '12:01 PM' ); ?>>12:01 PM</option>
                                <option value="4:30 PM" <?php selected( $default_session, '4:30 PM' ); ?>>4:30 PM</option>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p class="submit">
                <button type="submit" class="button button-primary"><?php echo esc_html__( 'Add Entry', 'custom-lottery' ); ?></button>
            </p>
        </form>
        <div id="form-response"></div>
    </div>
    <?php
}

/**
 * Callback function for the Reports page.
 */
function custom_lottery_reports_page_callback() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'lotto_entries';

    // Set timezone and get current date/session for defaults
    $timezone = new DateTimeZone('Asia/Yangon');
    $current_datetime = new DateTime('now', $timezone);
    $default_date = $current_datetime->format('Y-m-d');

    $time_1201 = new DateTime($current_datetime->format('Y-m-d') . ' 12:01:00', $timezone);
    $time_1630 = new DateTime($current_datetime->format('Y-m-d') . ' 16:30:00', $timezone);
    $default_session = '12:01 PM';
    if ($current_datetime > $time_1201 && $current_datetime <= $time_1630) {
        $default_session = '4:30 PM';
    }

    // Get selected date and session from form, or use defaults
    $selected_date = isset($_GET['report_date']) ? sanitize_text_field($_GET['report_date']) : $default_date;
    $selected_session = isset($_GET['draw_session']) ? sanitize_text_field($_GET['draw_session']) : $default_session;

    // Prepare for DB query
    $start_datetime = $selected_date . ' 00:00:00';
    $end_datetime = $selected_date . ' 23:59:59';

    // Get total sales for the selected session
    $total_sales = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(amount) FROM $table_name WHERE draw_session = %s AND timestamp BETWEEN %s AND %s",
        $selected_session, $start_datetime, $end_datetime
    ));
    $total_sales = $total_sales ? $total_sales : 0;

    // Get total amount per number
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT lottery_number, SUM(amount) as total_amount FROM $table_name WHERE draw_session = %s AND timestamp BETWEEN %s AND %s GROUP BY lottery_number ORDER BY lottery_number ASC",
        $selected_session, $start_datetime, $end_datetime
    ));

    ?>
    <style>
        .highlight-risk { color: red; font-weight: bold; }
    </style>
    <div class="wrap">
        <h1><?php echo esc_html__('Financial Report', 'custom-lottery'); ?></h1>
        <form method="get">
            <input type="hidden" name="page" value="custom-lottery-reports">
            <label for="report-date"><?php echo esc_html__('Date:', 'custom-lottery'); ?></label>
            <input type="date" id="report-date" name="report_date" value="<?php echo esc_attr($selected_date); ?>">

            <label for="draw-session-report"><?php echo esc_html__('Session:', 'custom-lottery'); ?></label>
            <select id="draw-session-report" name="draw_session">
                <option value="12:01 PM" <?php selected($selected_session, '12:01 PM'); ?>>12:01 PM</option>
                <option value="4:30 PM" <?php selected($selected_session, '4:30 PM'); ?>>4:30 PM</option>
            </select>
            <button type="submit" class="button"><?php echo esc_html__('View Report', 'custom-lottery'); ?></button>
        </form>

        <h2><?php printf(esc_html__('Report for %s session on %s', 'custom-lottery'), esc_html($selected_session), esc_html($selected_date)); ?></h2>
        <h3><?php printf(esc_html__('Total Sales: %s Kyat', 'custom-lottery'), number_format($total_sales, 2)); ?></h3>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Lottery Number', 'custom-lottery'); ?></th>
                    <th><?php echo esc_html__('Total Amount Purchased (Kyat)', 'custom-lottery'); ?></th>
                    <th><?php echo esc_html__('Potential Payout (x80) (Kyat)', 'custom-lottery'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($results) : ?>
                    <?php foreach ($results as $row) :
                        $potential_payout = $row->total_amount * 80;
                        $risk_class = $potential_payout > $total_sales ? 'highlight-risk' : '';
                    ?>
                        <tr class="<?php echo $risk_class; ?>">
                            <td><?php echo esc_html($row->lottery_number); ?></td>
                            <td><?php echo number_format($row->total_amount, 2); ?></td>
                            <td><?php echo number_format($potential_payout, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="3"><?php echo esc_html__('No entries found for this session.', 'custom-lottery'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

/**
 * Callback function for the Payouts page.
 */
function custom_lottery_payouts_page_callback() {
    global $wpdb;
    $table_entries = $wpdb->prefix . 'lotto_entries';

    // Handle marking an entry as paid
    if (isset($_GET['action']) && $_GET['action'] === 'mark_paid' && isset($_GET['entry_id'])) {
        if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'mark_paid_' . $_GET['entry_id'])) {
            $entry_id = absint($_GET['entry_id']);
            $wpdb->update($table_entries, ['paid_status' => 1], ['id' => $entry_id]);
            echo '<div class="updated"><p>' . esc_html__('Winner marked as paid.', 'custom-lottery') . '</p></div>';
        }
    }

    // Set timezone and get current date/session for defaults
    $timezone = new DateTimeZone('Asia/Yangon');
    $current_datetime = new DateTime('now', $timezone);
    $default_date = $current_datetime->format('Y-m-d');

    $time_1201 = new DateTime($current_datetime->format('Y-m-d') . ' 12:01:00', $timezone);
    $time_1630 = new DateTime($current_datetime->format('Y-m-d') . ' 16:30:00', $timezone);
    $default_session = '12:01 PM';
    if ($current_datetime > $time_1201 && $current_datetime <= $time_1630) {
        $default_session = '4:30 PM';
    }

    // Get selected date and session from form, or use defaults
    $selected_date = isset($_GET['payout_date']) ? sanitize_text_field($_GET['payout_date']) : $default_date;
    $selected_session = isset($_GET['draw_session']) ? sanitize_text_field($_GET['draw_session']) : $default_session;

    // Prepare for DB query
    $start_datetime = $selected_date . ' 00:00:00';
    $end_datetime = $selected_date . ' 23:59:59';

    // Get winners for the selected session
    $winners = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_entries WHERE is_winner = 1 AND draw_session = %s AND timestamp BETWEEN %s AND %s ORDER BY customer_name ASC",
        $selected_session, $start_datetime, $end_datetime
    ));
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Payouts Management', 'custom-lottery'); ?></h1>
        <form method="get">
            <input type="hidden" name="page" value="custom-lottery-payouts">
            <label for="payout-date"><?php echo esc_html__('Date:', 'custom-lottery'); ?></label>
            <input type="date" id="payout-date" name="payout_date" value="<?php echo esc_attr($selected_date); ?>">

            <label for="draw-session-payout"><?php echo esc_html__('Session:', 'custom-lottery'); ?></label>
            <select id="draw-session-payout" name="draw_session">
                <option value="12:01 PM" <?php selected($selected_session, '12:01 PM'); ?>>12:01 PM</option>
                <option value="4:30 PM" <?php selected($selected_session, '4:30 PM'); ?>>4:30 PM</option>
            </select>
            <button type="submit" class="button"><?php echo esc_html__('View Winners', 'custom-lottery'); ?></button>
        </form>

        <h2><?php printf(esc_html__('Winners for %s session on %s', 'custom-lottery'), esc_html($selected_session), esc_html($selected_date)); ?></h2>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Customer Name', 'custom-lottery'); ?></th>
                    <th><?php echo esc_html__('Phone', 'custom-lottery'); ?></th>
                    <th><?php echo esc_html__('Winning Number', 'custom-lottery'); ?></th>
                    <th><?php echo esc_html__('Amount Won (Kyat)', 'custom-lottery'); ?></th>
                    <th><?php echo esc_html__('Status', 'custom-lottery'); ?></th>
                    <th><?php echo esc_html__('Action', 'custom-lottery'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($winners) : ?>
                    <?php foreach ($winners as $winner) :
                        $amount_won = $winner->amount * 80;
                    ?>
                        <tr>
                            <td><?php echo esc_html($winner->customer_name); ?></td>
                            <td><?php echo esc_html($winner->phone); ?></td>
                            <td><?php echo esc_html($winner->lottery_number); ?></td>
                            <td><?php echo number_format($amount_won, 2); ?></td>
                            <td><?php echo $winner->paid_status ? esc_html__('Paid', 'custom-lottery') : esc_html__('Unpaid', 'custom-lottery'); ?></td>
                            <td>
                                <?php if (!$winner->paid_status) :
                                    $mark_paid_url = wp_nonce_url(
                                        add_query_arg([
                                            'page' => 'custom-lottery-payouts',
                                            'action' => 'mark_paid',
                                            'entry_id' => $winner->id,
                                            'payout_date' => $selected_date,
                                            'draw_session' => $selected_session,
                                        ], admin_url('admin.php')),
                                        'mark_paid_' . $winner->id
                                    );
                                ?>
                                    <a href="<?php echo esc_url($mark_paid_url); ?>" class="button button-primary"><?php echo esc_html__('Mark as Paid', 'custom-lottery'); ?></a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="6"><?php echo esc_html__('No winners found for this session.', 'custom-lottery'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}


/**
 * Callback function for the Number Limiting page.
 */
function custom_lottery_limits_page_callback() {
    global $wpdb;
    $table_limits = $wpdb->prefix . 'lotto_limits';

    // Handle form submissions for blocking/unblocking
    if (isset($_POST['block_number_nonce']) && wp_verify_nonce($_POST['block_number_nonce'], 'block_number_action')) {
        $number_to_block = sanitize_text_field($_POST['number_to_block']);
        $block_date = sanitize_text_field($_POST['block_date']);
        $block_session = sanitize_text_field($_POST['block_session']);
        if (preg_match('/^\d{2}$/', $number_to_block) && !empty($block_date) && !empty($block_session)) {
            $wpdb->insert($table_limits, [
                'lottery_number' => $number_to_block,
                'draw_date' => $block_date,
                'draw_session' => $block_session,
                'limit_type' => 'manual'
            ]);
            echo '<div class="updated"><p>Number blocked successfully.</p></div>';
        } else {
            echo '<div class="error"><p>Invalid data provided.</p></div>';
        }
    }

    if (isset($_GET['action']) && $_GET['action'] === 'unblock' && isset($_GET['limit_id'])) {
        $limit_id = absint($_GET['limit_id']);
        $wpdb->delete($table_limits, ['id' => $limit_id]);
        echo '<div class="updated"><p>Number unblocked successfully.</p></div>';
    }

    // Get current blocked numbers
    $blocked_numbers = $wpdb->get_results("SELECT * FROM $table_limits ORDER BY draw_date DESC, draw_session ASC, lottery_number ASC");

    $timezone = new DateTimeZone('Asia/Yangon');
    $current_datetime = new DateTime('now', $timezone);
    $default_date = $current_datetime->format('Y-m-d');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Manual Number Blocking', 'custom-lottery'); ?></h1>
        <form method="post">
            <?php wp_nonce_field('block_number_action', 'block_number_nonce'); ?>
            <label for="number-to-block"><?php echo esc_html__('Number (2 digits):', 'custom-lottery'); ?></label>
            <input type="text" id="number-to-block" name="number_to_block" maxlength="2" pattern="\d{2}" required>

            <label for="block-date"><?php echo esc_html__('Date:', 'custom-lottery'); ?></label>
            <input type="date" id="block-date" name="block_date" value="<?php echo esc_attr($default_date); ?>" required>

            <label for="block-session"><?php echo esc_html__('Session:', 'custom-lottery'); ?></label>
            <select id="block-session" name="block_session" required>
                <option value="12:01 PM">12:01 PM</option>
                <option value="4:30 PM">4:30 PM</option>
            </select>

            <button type="submit" class="button button-primary"><?php echo esc_html__('Block Number', 'custom-lottery'); ?></button>
        </form>

        <hr>

        <h2><?php echo esc_html__('Currently Blocked Numbers', 'custom-lottery'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Number', 'custom-lottery'); ?></th>
                    <th><?php echo esc_html__('Date', 'custom-lottery'); ?></th>
                    <th><?php echo esc_html__('Session', 'custom-lottery'); ?></th>
                    <th><?php echo esc_html__('Block Type', 'custom-lottery'); ?></th>
                    <th><?php echo esc_html__('Action', 'custom-lottery'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($blocked_numbers) : ?>
                    <?php foreach ($blocked_numbers as $row) : ?>
                        <tr>
                            <td><?php echo esc_html($row->lottery_number); ?></td>
                            <td><?php echo esc_html($row->draw_date); ?></td>
                            <td><?php echo esc_html($row->draw_session); ?></td>
                            <td><?php echo esc_html(ucfirst($row->limit_type)); ?></td>
                            <td>
                                <a href="?page=custom-lottery-limits&action=unblock&limit_id=<?php echo $row->id; ?>" class="button button-secondary"><?php echo esc_html__('Unblock', 'custom-lottery'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5"><?php echo esc_html__('No numbers are currently blocked.', 'custom-lottery'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

/**
 * Enqueue scripts and styles for the admin pages.
 */
function custom_lottery_enqueue_scripts($hook) {
    // Only load on our plugin's pages
    if (strpos($hook, 'custom-lottery') === false) {
        return;
    }

    wp_enqueue_script(
        'custom-lottery-entry',
        plugin_dir_url(__FILE__) . 'js/lottery-entry.js',
        array('jquery'),
        '1.0.0',
        true
    );
}
add_action('admin_enqueue_scripts', 'custom_lottery_enqueue_scripts');


/**
 * AJAX handler for adding a new lottery entry.
 */
function add_lottery_entry_callback() {
    check_ajax_referer('lottery_entry_action', 'lottery_entry_nonce');

    global $wpdb;
    $table_entries = $wpdb->prefix . 'lotto_entries';
    $table_limits = $wpdb->prefix . 'lotto_limits';

    $timezone = new DateTimeZone('Asia/Yangon');
    $current_datetime = new DateTime('now', $timezone);
    $current_date = $current_datetime->format('Y-m-d');

    // Sanitize and validate input
    $customer_name = sanitize_text_field($_POST['customer_name']);
    $phone = sanitize_text_field($_POST['phone']);
    $lottery_number = sanitize_text_field($_POST['lottery_number']);
    $amount = absint($_POST['amount']);
    $draw_session = sanitize_text_field($_POST['draw_session']);
    $is_reverse = isset($_POST['reverse_entry']) && $_POST['reverse_entry'] == '1';

    if (empty($customer_name) || empty($phone) || !preg_match('/^\d{2}$/', $lottery_number) || empty($amount) || empty($draw_session)) {
        wp_send_json_error('All fields are required and must be in the correct format.');
        return;
    }

    // Check if number is blocked
    $is_blocked = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table_limits WHERE lottery_number = %s AND draw_date = %s AND draw_session = %s",
        $lottery_number, $current_date, $draw_session
    ));

    if ($is_blocked) {
        wp_send_json_error("Number {$lottery_number} is currently blocked for this session.");
        return;
    }

    // Insert the original entry
    $wpdb->insert($table_entries, [
        'customer_name' => $customer_name,
        'phone' => $phone,
        'lottery_number' => $lottery_number,
        'amount' => $amount,
        'draw_session' => $draw_session,
        'timestamp' => $current_datetime->format('Y-m-d H:i:s'),
    ]);

    check_and_auto_block_number($lottery_number, $draw_session, $current_date);
    $message = "Entry for {$lottery_number} added successfully.";

    // Handle the "R" (reverse) entry
    if ($is_reverse) {
        $reversed_number = strrev($lottery_number);
        if ($lottery_number !== $reversed_number) {
            $is_rev_blocked = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_limits WHERE lottery_number = %s AND draw_date = %s AND draw_session = %s",
                $reversed_number, $current_date, $draw_session
            ));

            if ($is_rev_blocked) {
                wp_send_json_error("Cannot add reversed entry: Number {$reversed_number} is currently blocked for this session.");
                return;
            }

            $wpdb->insert($table_entries, [
                'customer_name' => $customer_name,
                'phone' => $phone,
                'lottery_number' => $reversed_number,
                'amount' => $amount,
                'draw_session' => $draw_session,
                'timestamp' => $current_datetime->format('Y-m-d H:i:s'),
            ]);
            check_and_auto_block_number($reversed_number, $draw_session, $current_date);
            $message .= " Reversed entry for {$reversed_number} also added.";
        }
    }

    wp_send_json_success($message);
}
add_action('wp_ajax_add_lottery_entry', 'add_lottery_entry_callback');

/**
 * Checks if a number's potential payout exceeds total sales and blocks it if necessary.
 */
function check_and_auto_block_number($number, $session, $date) {
    global $wpdb;
    $table_entries = $wpdb->prefix . 'lotto_entries';
    $table_limits = $wpdb->prefix . 'lotto_limits';

    $start_datetime = $date . ' 00:00:00';
    $end_datetime = $date . ' 23:59:59';

    // Get total sales for the session
    $total_sales = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(amount) FROM $table_entries WHERE draw_session = %s AND timestamp BETWEEN %s AND %s",
        $session, $start_datetime, $end_datetime
    ));

    // Get total amount for the specific number
    $number_total_amount = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(amount) FROM $table_entries WHERE lottery_number = %s AND draw_session = %s AND timestamp BETWEEN %s AND %s",
        $number, $session, $start_datetime, $end_datetime
    ));

    $potential_payout = $number_total_amount * 80;

    if ($potential_payout > $total_sales) {
        // Check if it's already blocked to avoid duplicates
        $is_already_blocked = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_limits WHERE lottery_number = %s AND draw_date = %s AND draw_session = %s",
            $number, $date, $session
        ));

        if (!$is_already_blocked) {
            $wpdb->insert($table_limits, [
                'lottery_number' => $number,
                'draw_date' => $date,
                'draw_session' => $session,
                'limit_type' => 'auto'
            ]);
        }
    }
}


/**
 * Fetches the winning numbers from the API.
 */
function custom_lottery_fetch_winning_numbers() {
    $api_url = 'https://api.thaistock2d.com/live';
    $response = wp_remote_get($api_url);

    if (is_wp_error($response)) {
        // Handle API error, maybe log it
        return;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (empty($data['result']) || !is_array($data['result'])) {
        // Handle invalid data
        return;
    }

    $timezone = new DateTimeZone('Asia/Yangon');
    $current_date = new DateTime('now', $timezone);
    $option_date_key = 'custom_lottery_last_fetch_date';
    $last_fetch_date = get_option($option_date_key, '');

    // If it's a new day, reset the fetched flags
    if ($last_fetch_date !== $current_date->format('Y-m-d')) {
        update_option('custom_lottery_fetched_1201', 0);
        update_option('custom_lottery_fetched_1630', 0);
        update_option($option_date_key, $current_date->format('Y-m-d'));
    }

    foreach ($data['result'] as $result) {
        if ($result['open_time'] === '12:01:00' && !get_option('custom_lottery_fetched_1201')) {
            $winning_number = $result['twod'];
            update_option('custom_lottery_winning_number_1201', $winning_number);
            update_option('custom_lottery_fetched_1201', 1);
            custom_lottery_identify_winners('12:01 PM', $winning_number, $current_date->format('Y-m-d'));
        }
        if ($result['open_time'] === '16:30:00' && !get_option('custom_lottery_fetched_1630')) {
            $winning_number = $result['twod'];
            update_option('custom_lottery_winning_number_1630', $winning_number);
            update_option('custom_lottery_fetched_1630', 1);
            custom_lottery_identify_winners('4:30 PM', $winning_number, $current_date->format('Y-m-d'));
        }
    }
}

/**
 * Identifies and flags winning entries in the database.
 */
function custom_lottery_identify_winners($session, $winning_number, $date) {
    global $wpdb;
    $table_entries = $wpdb->prefix . 'lotto_entries';

    $start_datetime = $date . ' 00:00:00';
    $end_datetime = $date . ' 23:59:59';

    $wpdb->update(
        $table_entries,
        ['is_winner' => 1], // Data to update
        [
            'lottery_number' => $winning_number,
            'draw_session' => $session,
            'timestamp' => $wpdb->prepare('BETWEEN %s AND %s', $start_datetime, $end_datetime)
        ]
    );
}

/**
 * Schedule cron jobs.
 */
function custom_lottery_schedule_cron_jobs() {
    if (!wp_next_scheduled('custom_lottery_fetch_1201')) {
        // Schedule to run daily at 12:02 PM (Asia/Yangon time)
        $time = new DateTime('12:02:00', new DateTimeZone('Asia/Yangon'));
        $time->setTimezone(new DateTimeZone('UTC'));
        wp_schedule_event($time->getTimestamp(), 'daily', 'custom_lottery_fetch_1201');
    }
    if (!wp_next_scheduled('custom_lottery_fetch_1630')) {
        // Schedule to run daily at 4:32 PM (Asia/Yangon time)
        $time = new DateTime('16:32:00', new DateTimeZone('Asia/Yangon'));
        $time->setTimezone(new DateTimeZone('UTC'));
        wp_schedule_event($time->getTimestamp(), 'daily', 'custom_lottery_fetch_1630');
    }
}
add_action('init', 'custom_lottery_schedule_cron_jobs');
add_action('custom_lottery_fetch_1201', 'custom_lottery_fetch_winning_numbers');
add_action('custom_lottery_fetch_1630', 'custom_lottery_fetch_winning_numbers');


/**
 * Clear cron jobs on deactivation.
 */
function custom_lottery_clear_cron_jobs() {
    wp_clear_scheduled_hook('custom_lottery_fetch_1201');
    wp_clear_scheduled_hook('custom_lottery_fetch_1630');
}
register_deactivation_hook(__FILE__, 'custom_lottery_clear_cron_jobs');