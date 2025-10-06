<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Register the admin menu pages.
 */
function custom_lottery_admin_menu() {
    add_menu_page(
        __( 'Lottery', 'custom-lottery' ),
        __( 'Lottery', 'custom-lottery' ),
        'manage_options',
        'custom-lottery-dashboard',
        'custom_lottery_dashboard_page_callback',
        'dashicons-tickets-alt',
        20
    );

    add_submenu_page(
        'custom-lottery-dashboard',
        __( 'Dashboard', 'custom-lottery' ),
        __( 'Dashboard', 'custom-lottery' ),
        'manage_options',
        'custom-lottery-dashboard',
        'custom_lottery_dashboard_page_callback'
    );

    add_submenu_page(
        'custom-lottery-dashboard',
        __( 'Lottery Entry', 'custom-lottery' ),
        __( 'Lottery Entry', 'custom-lottery' ),
        'enter_lottery_numbers', // Use custom capability
        'custom-lottery-entry',
        'custom_lottery_entry_page_callback'
    );

    add_submenu_page(
        'custom-lottery-dashboard',
        __( 'Reports', 'custom-lottery' ),
        __( 'Reports', 'custom-lottery' ),
        'manage_options',
        'custom-lottery-reports',
        'custom_lottery_reports_page_callback'
    );

    add_submenu_page(
        'custom-lottery-dashboard',
        __( 'Advanced Reports', 'custom-lottery' ),
        __( 'Advanced Reports', 'custom-lottery' ),
        'manage_options',
        'custom-lottery-advanced-reports',
        'custom_lottery_advanced_reports_page_callback'
    );

    add_submenu_page(
        'custom-lottery-dashboard',
        __( 'Payouts', 'custom-lottery' ),
        __( 'Payouts', 'custom-lottery' ),
        'manage_options',
        'custom-lottery-payouts',
        'custom_lottery_payouts_page_callback'
    );

    add_submenu_page(
        'custom-lottery-dashboard',
        __( 'Number Limiting', 'custom-lottery' ),
        __( 'Number Limiting', 'custom-lottery' ),
        'manage_options',
        'custom-lottery-limits',
        'custom_lottery_limits_page_callback'
    );

    add_submenu_page(
        'custom-lottery-dashboard',
        __( 'All Entries', 'custom-lottery' ),
        __( 'All Entries', 'custom-lottery' ),
        'manage_options',
        'custom-lottery-all-entries',
        'custom_lottery_all_entries_page_callback'
    );

    add_submenu_page(
        'custom-lottery-dashboard',
        __( 'Tools', 'custom-lottery' ),
        __( 'Tools', 'custom-lottery' ),
        'manage_options',
        'custom-lottery-tools',
        'custom_lottery_tools_page_callback'
    );

    add_submenu_page(
        'custom-lottery-dashboard',
        __( 'Customers', 'custom-lottery' ),
        __( 'Customers', 'custom-lottery' ),
        'manage_options',
        'custom-lottery-customers',
        'custom_lottery_customers_page_callback'
    );
}
add_action( 'admin_menu', 'custom_lottery_admin_menu' );

/**
 * Callback for the Customers page.
 */
function custom_lottery_customers_page_callback() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'lotto_customers';
    $page_slug = 'custom-lottery-customers';

    $action = isset($_REQUEST['action']) ? sanitize_key($_REQUEST['action']) : 'list';
    $customer_id = isset($_REQUEST['customer_id']) ? absint($_REQUEST['customer_id']) : 0;

    // Handle Add/Edit form submission
    if (isset($_POST['submit_customer']) && check_admin_referer('cl_save_customer_action', 'cl_save_customer_nonce')) {
        $customer_id = absint($_POST['customer_id']);
        $customer_name = sanitize_text_field($_POST['customer_name']);
        $phone = sanitize_text_field($_POST['phone']);

        $data = ['customer_name' => $customer_name, 'phone' => $phone];

        if ($customer_id > 0) { // Update existing customer
            $original_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $customer_id), ARRAY_A);
            if ($wpdb->update($table_name, $data, ['id' => $customer_id])) {
                custom_lottery_log_action('customer_edited', ['customer_id' => $customer_id, 'original_data' => $original_data, 'new_data' => $data]);
                echo '<div class="updated"><p>' . esc_html__('Customer updated successfully.', 'custom-lottery') . '</p></div>';
            }
        } else { // Add new customer
            $data['last_seen'] = current_time('mysql');
            if ($wpdb->insert($table_name, $data)) {
                $new_customer_id = $wpdb->insert_id;
                custom_lottery_log_action('customer_added', ['customer_id' => $new_customer_id, 'data' => $data]);
                echo '<div class="updated"><p>' . esc_html__('Customer added successfully.', 'custom-lottery') . '</p></div>';
            }
        }
        $action = 'list'; // Go back to the list view
    }

    // Handle deletion
    if ($action === 'delete' && $customer_id > 0) {
        $nonce = isset($_REQUEST['_wpnonce']) ? $_REQUEST['_wpnonce'] : '';
        if (wp_verify_nonce($nonce, 'cl_delete_customer_' . $customer_id)) {
            $customer_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $customer_id), ARRAY_A);
            if ($wpdb->delete($table_name, ['id' => $customer_id])) {
                custom_lottery_log_action('customer_deleted', ['customer_id' => $customer_id, 'deleted_data' => $customer_data]);
                echo '<div class="updated"><p>' . esc_html__('Customer deleted successfully.', 'custom-lottery') . '</p></div>';
            }
        }
        $action = 'list'; // Go back to the list view
    }

    // Display add/edit form or the list table
    if ($action === 'add' || ($action === 'edit' && $customer_id > 0)) {
        $customer = null;
        if ($customer_id > 0) {
            $customer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $customer_id));
        }
        $form_title = $customer ? __('Edit Customer', 'custom-lottery') : __('Add New Customer', 'custom-lottery');
        $button_text = $customer ? __('Save Changes', 'custom-lottery') : __('Add Customer', 'custom-lottery');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html($form_title); ?></h1>
            <a href="?page=<?php echo esc_attr($page_slug); ?>" class="button">&larr; <?php esc_html_e('Back to Customers List', 'custom-lottery'); ?></a>
            <form method="post" style="margin-top: 20px;">
                <input type="hidden" name="customer_id" value="<?php echo esc_attr($customer_id); ?>">
                <?php wp_nonce_field('cl_save_customer_action', 'cl_save_customer_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="customer_name"><?php esc_html_e('Customer Name', 'custom-lottery'); ?></label></th>
                        <td><input type="text" id="customer_name" name="customer_name" value="<?php echo $customer ? esc_attr($customer->customer_name) : ''; ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="phone"><?php esc_html_e('Phone', 'custom-lottery'); ?></label></th>
                        <td><input type="text" id="phone" name="phone" value="<?php echo $customer ? esc_attr($customer->phone) : ''; ?>" class="regular-text" required></td>
                    </tr>
                </table>
                <?php submit_button($button_text, 'primary', 'submit_customer'); ?>
            </form>
        </div>
        <?php
    } else {
        echo '<div class="wrap">';
        echo '<h1 class="wp-heading-inline">' . esc_html__('Customers', 'custom-lottery') . '</h1>';
        echo '<a href="?page=' . esc_attr($page_slug) . '&action=add" class="page-title-action">' . esc_html__('Add New', 'custom-lottery') . '</a>';

        $customers_list_table = new Lotto_Customers_List_Table();
        $customers_list_table->prepare_items();
        $customers_list_table->display();

        echo '</div>';
    }
}

/**
 * Callback for the Tools page.
 */
function custom_lottery_tools_page_callback() {
    global $wpdb;

    // Handle the data clearing form submission
    if (isset($_POST['clear_data_submit']) && check_admin_referer('clear_data_action', 'clear_data_nonce')) {
        $date_to_clear = sanitize_text_field($_POST['clear_data_date']);
        if (!empty($date_to_clear)) {
            $start_datetime = $date_to_clear . ' 00:00:00';
            $end_datetime = $date_to_clear . ' 23:59:59';

            $table_entries = $wpdb->prefix . 'lotto_entries';
            $table_limits = $wpdb->prefix . 'lotto_limits';
            $table_audit = $wpdb->prefix . 'lotto_audit_log';

            // Delete entries
            $wpdb->query($wpdb->prepare("DELETE FROM $table_entries WHERE timestamp BETWEEN %s AND %s", $start_datetime, $end_datetime));

            // Delete limits
            $wpdb->query($wpdb->prepare("DELETE FROM $table_limits WHERE draw_date = %s", $date_to_clear));

            // Delete audit logs
            $wpdb->query($wpdb->prepare("DELETE FROM $table_audit WHERE timestamp BETWEEN %s AND %s", $start_datetime, $end_datetime));

            echo '<div class="updated"><p>' . esc_html__('All data for the selected date has been cleared.', 'custom-lottery') . '</p></div>';
        } else {
            echo '<div class="error"><p>' . esc_html__('Please select a date to clear.', 'custom-lottery') . '</p></div>';
        }
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Lottery Tools', 'custom-lottery'); ?></h1>
        <div class="card">
            <h2 style="color: red;"><?php echo esc_html__('Clear Data by Date (Destructive Action)', 'custom-lottery'); ?></h2>
            <p><?php echo esc_html__('This tool will permanently delete all lottery entries, limits, and audit logs for the selected date. This action cannot be undone.', 'custom-lottery'); ?></p>
            <form method="post">
                <?php wp_nonce_field('clear_data_action', 'clear_data_nonce'); ?>
                <label for="clear-data-date"><?php echo esc_html__('Select Date:', 'custom-lottery'); ?></label>
                <input type="date" id="clear-data-date" name="clear_data_date" required>
                <button type="submit" name="clear_data_submit" class="button button-danger" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to permanently delete all data for the selected date? This cannot be undone.', 'custom-lottery')); ?>');">
                    <?php echo esc_html__('Clear All Data for Selected Date', 'custom-lottery'); ?>
                </button>
            </form>
        </div>
    </div>
    <?php
}

/**
 * Callback function for the Dashboard page.
 */
function custom_lottery_dashboard_page_callback() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Lottery Dashboard', 'custom-lottery'); ?></h1>

        <div class="dashboard-controls">
            <label for="dashboard-range-selector"><?php echo esc_html__('Select Date Range:', 'custom-lottery'); ?></label>
            <select id="dashboard-range-selector">
                <option value="last_7_days" selected><?php echo esc_html__('Last 7 Days', 'custom-lottery'); ?></option>
                <option value="this_month"><?php echo esc_html__('This Month', 'custom-lottery'); ?></option>
            </select>
            <?php wp_nonce_field('dashboard_nonce', 'dashboard_nonce'); ?>
        </div>

        <div class="dashboard-charts" style="width: 80%; margin-top: 20px;">
            <div class="chart-container" style="position: relative; height:40vh; width:80vw; margin-bottom: 40px;">
                <h2><?php echo esc_html__('Sales vs. Payouts', 'custom-lottery'); ?></h2>
                <canvas id="salesPayoutsChart"></canvas>
            </div>
            <div class="chart-container" style="position: relative; height:40vh; width:80vw;">
                <h2><?php echo esc_html__('Net Profit Over Time', 'custom-lottery'); ?></h2>
                <canvas id="netProfitChart"></canvas>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Callback function for the Advanced Reports page.
 */
function custom_lottery_advanced_reports_page_callback() {
    global $wpdb;
    $table_entries = $wpdb->prefix . 'lotto_entries';

    $timezone = new DateTimeZone('Asia/Yangon');
    $default_start_date = (new DateTime('first day of this month', $timezone))->format('Y-m-d');
    $default_end_date = (new DateTime('last day of this month', $timezone))->format('Y-m-d');
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : $default_start_date;
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : $default_end_date;

    $query_start_date = $start_date . ' 00:00:00';
    $query_end_date = $end_date . ' 23:59:59';

    $top_customers = $wpdb->get_results($wpdb->prepare(
        "SELECT customer_name, phone, SUM(amount) as total_spent FROM $table_entries WHERE timestamp BETWEEN %s AND %s GROUP BY customer_name, phone ORDER BY total_spent DESC LIMIT 20",
        $query_start_date, $query_end_date
    ));

    $hot_numbers = $wpdb->get_results($wpdb->prepare(
        "SELECT lottery_number, COUNT(id) as purchase_count FROM $table_entries WHERE timestamp BETWEEN %s AND %s GROUP BY lottery_number ORDER BY purchase_count DESC LIMIT 10",
        $query_start_date, $query_end_date
    ));

    $cold_numbers = $wpdb->get_results($wpdb->prepare(
        "SELECT lottery_number, COUNT(id) as purchase_count FROM $table_entries WHERE timestamp BETWEEN %s AND %s GROUP BY lottery_number ORDER BY purchase_count ASC LIMIT 10",
        $query_start_date, $query_end_date
    ));
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Advanced Reports', 'custom-lottery'); ?></h1>
        <div class="report-section">
            <h2><?php echo esc_html__('Top Customers & Hot/Cold Numbers', 'custom-lottery'); ?></h2>
            <form method="get">
                <input type="hidden" name="page" value="custom-lottery-advanced-reports">
                <label for="start-date"><?php echo esc_html__('Start Date:', 'custom-lottery'); ?></label>
                <input type="date" id="start-date" name="start_date" value="<?php echo esc_attr($start_date); ?>">
                <label for="end-date"><?php echo esc_html__('End Date:', 'custom-lottery'); ?></label>
                <input type="date" id="end-date" name="end_date" value="<?php echo esc_attr($end_date); ?>">
                <button type="submit" class="button"><?php echo esc_html__('View Report', 'custom-lottery'); ?></button>
            </form>

            <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Customer Name', 'custom-lottery'); ?></th>
                        <th><?php echo esc_html__('Phone', 'custom-lottery'); ?></th>
                        <th><?php echo esc_html__('Total Amount Spent (Kyat)', 'custom-lottery'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($top_customers) : foreach ($top_customers as $customer) : ?>
                        <tr>
                            <td><?php echo esc_html($customer->customer_name); ?></td>
                            <td><?php echo esc_html($customer->phone); ?></td>
                            <td><?php echo number_format($customer->total_spent, 2); ?></td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="3"><?php echo esc_html__('No data found for this period.', 'custom-lottery'); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="report-section" style="margin-top: 40px; display: flex; gap: 40px;">
                <div style="flex: 1;">
                    <h3><?php echo esc_html__('Hot Numbers (Most Frequent)', 'custom-lottery'); ?></h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Number', 'custom-lottery'); ?></th>
                                <th><?php echo esc_html__('Times Purchased', 'custom-lottery'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($hot_numbers) : foreach ($hot_numbers as $number) : ?>
                                <tr>
                                    <td><?php echo esc_html($number->lottery_number); ?></td>
                                    <td><?php echo esc_html($number->purchase_count); ?></td>
                                </tr>
                            <?php endforeach; else : ?>
                                <tr><td colspan="2"><?php echo esc_html__('No data found.', 'custom-lottery'); ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div style="flex: 1;">
                    <h3><?php echo esc_html__('Cold Numbers (Least Frequent)', 'custom-lottery'); ?></h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Number', 'custom-lottery'); ?></th>
                                <th><?php echo esc_html__('Times Purchased', 'custom-lottery'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($cold_numbers) : foreach ($cold_numbers as $number) : ?>
                                <tr>
                                    <td><?php echo esc_html($number->lottery_number); ?></td>
                                    <td><?php echo esc_html($number->purchase_count); ?></td>
                                </tr>
                            <?php endforeach; else : ?>
                                <tr><td colspan="2"><?php echo esc_html__('No data found.', 'custom-lottery'); ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Callback function for the All Entries page.
 */
function custom_lottery_all_entries_page_callback() {
    global $wpdb;
    $table_entries = $wpdb->prefix . 'lotto_entries';
    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

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
        $action = '';
    }

    if ($action === 'delete' && !empty($_GET['entry_id'])) {
        $entry_id = absint($_GET['entry_id']);
        if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'cl_delete_entry')) {
            $entry_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_entries WHERE id = %d", $entry_id), ARRAY_A);
            if ($wpdb->delete($table_entries, ['id' => $entry_id])) {
                custom_lottery_log_action('entry_deleted', ['entry_id' => $entry_id, 'deleted_data' => $entry_data]);
                echo '<div class="updated"><p>Entry deleted successfully.</p></div>';
            }
        }
        $action = '';
    }

    if ($action === 'edit' && !empty($_GET['entry_id'])) {
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
 * Callback function for the Lottery Entry page.
 */
function custom_lottery_entry_page_callback() {
    $timezone = new DateTimeZone('Asia/Yangon');
    $current_time = new DateTime('now', $timezone);
    $time_1201 = new DateTime($current_time->format('Y-m-d') . ' 12:01:00', $timezone);
    $time_1630 = new DateTime($current_time->format('Y-m-d') . ' 16:30:00', $timezone);

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
        <button id="print-receipt-button" class="button" style="display: none; margin-top: 10px;"><?php echo esc_html__( 'Print Last Receipt', 'custom-lottery' ); ?></button>

        <hr style="margin-top: 40px;">

        <h2><?php echo esc_html__( 'Quick Entry Mode (Bulk Import)', 'custom-lottery' ); ?></h2>
        <p><?php echo esc_html__( 'Enter multiple bets at once. Format: Number-Amount, Number R-Amount (e.g., 23-1000, 45 R-500, 81-2000)', 'custom-lottery' ); ?></p>
        <form id="lottery-bulk-entry-form">
            <textarea id="bulk-entry-data" rows="10" cols="50" placeholder="23-1000, 45 R-500, 81-2000"></textarea>
            <p class="submit">
                <button type="submit" class="button button-primary"><?php echo esc_html__( 'Add Bulk Entries', 'custom-lottery' ); ?></button>
            </p>
        </form>
        <div id="bulk-form-response"></div>
    </div>
    <?php
}

/**
 * Callback function for the Reports page.
 */
function custom_lottery_reports_page_callback() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'lotto_entries';

    $timezone = new DateTimeZone('Asia/Yangon');
    $current_datetime = new DateTime('now', $timezone);
    $default_date = $current_datetime->format('Y-m-d');

    $time_1201 = new DateTime($current_datetime->format('Y-m-d') . ' 12:01:00', $timezone);
    $time_1630 = new DateTime($current_datetime->format('Y-m-d') . ' 16:30:00', $timezone);
    $default_session = '12:01 PM';
    if ($current_datetime > $time_1201 && $current_datetime <= $time_1630) {
        $default_session = '4:30 PM';
    }

    $selected_date = isset($_GET['report_date']) ? sanitize_text_field($_GET['report_date']) : $default_date;
    $selected_session = isset($_GET['draw_session']) ? sanitize_text_field($_GET['draw_session']) : $default_session;

    $start_datetime = $selected_date . ' 00:00:00';
    $end_datetime = $selected_date . ' 23:59:59';

    $total_sales = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(amount) FROM $table_name WHERE draw_session = %s AND timestamp BETWEEN %s AND %s",
        $selected_session, $start_datetime, $end_datetime
    ));
    $total_sales = $total_sales ? $total_sales : 0;

    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT lottery_number, SUM(amount) as total_amount FROM $table_name WHERE draw_session = %s AND timestamp BETWEEN %s AND %s GROUP BY lottery_number ORDER BY lottery_number ASC",
        $selected_session, $start_datetime, $end_datetime
    ));

    ?>
    <style>.highlight-risk { color: red; font-weight: bold; }</style>
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
                <?php if ($results) : foreach ($results as $row) :
                    $potential_payout = $row->total_amount * 80;
                    $risk_class = $potential_payout > $total_sales ? 'highlight-risk' : '';
                ?>
                    <tr class="<?php echo $risk_class; ?>">
                        <td><?php echo esc_html($row->lottery_number); ?></td>
                        <td><?php echo number_format($row->total_amount, 2); ?></td>
                        <td><?php echo number_format($potential_payout, 2); ?></td>
                    </tr>
                <?php endforeach; else : ?>
                    <tr><td colspan="3"><?php echo esc_html__('No entries found for this session.', 'custom-lottery'); ?></td></tr>
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

    // Handle manual winner processing
    if (isset($_POST['manual_process']) && check_admin_referer('manual_process_winners_action', 'manual_process_nonce')) {
        $winning_number = sanitize_text_field($_POST['winning_number']);
        $process_date = isset($_GET['payout_date']) ? sanitize_text_field($_GET['payout_date']) : (new DateTime('now', new DateTimeZone('Asia/Yangon')))->format('Y-m-d');
        $process_session = isset($_GET['draw_session']) ? sanitize_text_field($_GET['draw_session']) : '12:01 PM';

        if (preg_match('/^\d{2}$/', $winning_number)) {
            custom_lottery_identify_winners($process_session, $winning_number, $process_date);
            echo '<div class="updated"><p>' . esc_html__('Winners processed successfully for number: ', 'custom-lottery') . esc_html($winning_number) . '</p></div>';
        } else {
            echo '<div class="error"><p>' . esc_html__('Invalid winning number format.', 'custom-lottery') . '</p></div>';
        }
    }

    // Handle marking an entry as paid
    if (isset($_GET['action']) && $_GET['action'] === 'mark_paid' && isset($_GET['entry_id'])) {
        if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'mark_paid_' . $_GET['entry_id'])) {
            $entry_id = absint($_GET['entry_id']);
            $wpdb->update($table_entries, ['paid_status' => 1], ['id' => $entry_id]);
            echo '<div class="updated"><p>' . esc_html__('Winner marked as paid.', 'custom-lottery') . '</p></div>';
        }
    }

    $timezone = new DateTimeZone('Asia/Yangon');
    $current_datetime = new DateTime('now', $timezone);
    $default_date = $current_datetime->format('Y-m-d');

    $time_1201 = new DateTime($current_datetime->format('Y-m-d') . ' 12:01:00', $timezone);
    $time_1630 = new DateTime($current_datetime->format('Y-m-d') . ' 16:30:00', $timezone);
    $default_session = '12:01 PM';
    if ($current_datetime > $time_1201 && $current_datetime <= $time_1630) {
        $default_session = '4:30 PM';
    }

    $selected_date = isset($_GET['payout_date']) ? sanitize_text_field($_GET['payout_date']) : $default_date;
    $selected_session = isset($_GET['draw_session']) ? sanitize_text_field($_GET['draw_session']) : $default_session;

    $start_datetime = $selected_date . ' 00:00:00';
    $end_datetime = $selected_date . ' 23:59:59';

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

        <hr>
        <div style="margin: 20px 0;">
            <h3><?php echo esc_html__('Manual Winner Processing', 'custom-lottery'); ?></h3>
            <p><?php echo esc_html__('If the automatic fetch fails or for testing, you can manually trigger the winner identification process here.', 'custom-lottery'); ?></p>
            <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=custom-lottery-payouts&payout_date=' . $selected_date . '&draw_session=' . $selected_session)); ?>">
                <?php wp_nonce_field('manual_process_winners_action', 'manual_process_nonce'); ?>
                <label for="winning-number"><?php echo esc_html__('Winning Number:', 'custom-lottery'); ?></label>
                <input type="text" id="winning-number" name="winning_number" maxlength="2" pattern="\d{2}" required>
                <button type="submit" name="manual_process" class="button button-secondary"><?php echo esc_html__('Process Winners', 'custom-lottery'); ?></button>
            </form>
        </div>
        <hr>

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
                <?php if ($winners) : foreach ($winners as $winner) :
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
                <?php endforeach; else : ?>
                    <tr><td colspan="6"><?php echo esc_html__('No winners found for this session.', 'custom-lottery'); ?></td></tr>
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
                <?php if ($blocked_numbers) : foreach ($blocked_numbers as $row) : ?>
                    <tr>
                        <td><?php echo esc_html($row->lottery_number); ?></td>
                        <td><?php echo esc_html($row->draw_date); ?></td>
                        <td><?php echo esc_html($row->draw_session); ?></td>
                        <td><?php echo esc_html(ucfirst($row->limit_type)); ?></td>
                        <td><a href="?page=custom-lottery-limits&action=unblock&limit_id=<?php echo $row->id; ?>" class="button button-secondary"><?php echo esc_html__('Unblock', 'custom-lottery'); ?></a></td>
                    </tr>
                <?php endforeach; else : ?>
                    <tr><td colspan="5"><?php echo esc_html__('No numbers are currently blocked.', 'custom-lottery'); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}