<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Register the admin menu pages.
 */
function custom_lottery_admin_menu() {
    // Main Menu Item - Points to the SPA Dashboard
    add_menu_page(
        __( 'Lottery SPA', 'custom-lottery' ),
        __( 'Lottery SPA', 'custom-lottery' ),
        'enter_lottery_numbers',
        'custom-lottery-spa', // Slug for the main SPA page
        'custom_lottery_spa_page_callback', // Callback for the SPA dashboard
        'dashicons-tickets-alt',
        20
    );

    // Submenu Item for SPA Dashboard
    add_submenu_page(
        'custom-lottery-spa',
        __( 'Dashboard', 'custom-lottery' ),
        __( 'Dashboard', 'custom-lottery' ),
        'enter_lottery_numbers',
        'custom-lottery-spa', // Must match parent slug to be the default page
        'custom_lottery_spa_page_callback'
    );

    // Submenu Item for SPA Customers
    add_submenu_page(
        'custom-lottery-spa',
        __( 'Customers', 'custom-lottery' ),
        __( 'Customers', 'custom-lottery' ),
        'manage_options',
        'custom-lottery-spa-customers', // Slug for SPA customers page
        'custom_lottery_spa_customers_page_callback' // Callback for SPA customers
    );

    // Submenu Item for SPA Lottery Entry
    add_submenu_page(
        'custom-lottery-spa',
        __( 'Lottery Entry', 'custom-lottery' ),
        __( 'Lottery Entry', 'custom-lottery' ),
        'enter_lottery_numbers',
        'custom-lottery-spa-entry', // Slug for SPA entry page
        'custom_lottery_spa_entry_page_callback' // Callback
    );

    // Keep the old pages but hide them from the menu by setting parent to null
    // This allows them to be accessible via direct URL if needed for a transition period,
    // but cleans up the main navigation.

    add_submenu_page(
        'custom-lottery-spa',
        __( 'Reports', 'custom-lottery' ),
        __( 'Reports', 'custom-lottery' ),
        'manage_options',
        'custom-lottery-reports',
        'custom_lottery_reports_page_callback'
    );

    add_submenu_page(
        'custom-lottery-spa',
        __( 'Advanced Reports', 'custom-lottery' ),
        __( 'Advanced Reports', 'custom-lottery' ),
        'manage_options',
        'custom-lottery-advanced-reports',
        'custom_lottery_advanced_reports_page_callback'
    );

    add_submenu_page(
        'custom-lottery-spa',
        __( 'Payouts', 'custom-lottery' ),
        __( 'Payouts', 'custom-lottery' ),
        'manage_options',
        'custom-lottery-payouts',
        'custom_lottery_payouts_page_callback'
    );

    add_submenu_page(
        'custom-lottery-spa',
        __( 'Number Limiting', 'custom-lottery' ),
        __( 'Number Limiting', 'custom-lottery' ),
        'manage_options',
        'custom-lottery-limits',
        'custom_lottery_limits_page_callback'
    );

    add_submenu_page(
        'custom-lottery-spa',
        __( 'All Entries', 'custom-lottery' ),
        __( 'All Entries', 'custom-lottery' ),
        'manage_options',
        'custom-lottery-all-entries',
        'custom_lottery_all_entries_page_callback'
    );

    add_submenu_page(
        'custom-lottery-spa',
        __( 'Tools', 'custom-lottery' ),
        __( 'Tools', 'custom-lottery' ),
        'manage_options',
        'custom-lottery-tools',
        'custom_lottery_tools_page_callback'
    );
}
add_action( 'admin_menu', 'custom_lottery_admin_menu' );

/**
 * Callback for the SPA page.
 * This will be the main entry point for the Vue app.
 */
function custom_lottery_spa_page_callback() {
    // The actual rendering is handled by the Inertia Adapter class.
    // We just need to call it with the initial component and props.
    custom_lottery_render_inertia('Dashboard', ['message' => 'Welcome to the Lottery SPA!']);
}

/**
 * Callback for the SPA Customers page.
 */
function custom_lottery_spa_customers_page_callback() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'lotto_customers';
    $customers = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY id DESC" );

    custom_lottery_render_inertia('Customers/Index', ['customers' => $customers]);
}

/**
 * Callback for the SPA Lottery Entry page.
 */
function custom_lottery_spa_entry_page_callback() {
    // We don't need to pass any initial props for a new entry form.
    custom_lottery_render_inertia('LotteryEntry/Index', []);
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