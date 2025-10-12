<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Callback for the Payout Requests page.
 */
function custom_lottery_payout_requests_page_callback() {
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php echo esc_html__('Agent Payout Requests', 'custom-lottery'); ?></h1>
        <form method="post">
            <?php
            $payout_requests_list_table = new Lotto_Payout_Requests_List_Table();
            $payout_requests_list_table->prepare_items();
            $payout_requests_list_table->display();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Callback for the Modification Requests page.
 */
function custom_lottery_mod_requests_page_callback() {
    $mod_requests_list_table = new Lotto_Mod_Requests_List_Table();
    $mod_requests_list_table->prepare_items();
    ?>
    <style type="text/css">
        /* Use flexbox for the main container to position details and actions */
        .entry-details-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        /* The action buttons are now in their own div, which we can align */
        .details-actions {
            flex-shrink: 0; /* Prevents the actions div from shrinking */
            padding-left: 10px; /* Adds some space between text and buttons */
        }
    </style>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php echo esc_html__('Modification Requests', 'custom-lottery'); ?></h1>
        <form method="get">
            <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
            <?php $mod_requests_list_table->display(); ?>
        </form>
    </div>
    <?php
}

/**
 * Register the admin menu pages.
 */
function custom_lottery_admin_menu() {
    $current_user = wp_get_current_user();

    // If user is a Commission Agent AND NOT an admin/manager, show the Agent Portal.
    if (in_array('commission_agent', (array) $current_user->roles) && !current_user_can('manage_options')) {
        // Agent Portal Menu
        add_menu_page(
            __('Agent Portal', 'custom-lottery'),
            __('Agent Portal', 'custom-lottery'),
            'enter_lottery_numbers',
            'custom-lottery-agent-dashboard',
            'custom_lottery_agent_dashboard_page_callback',
            'dashicons-businessman',
            21
        );
        add_submenu_page(
            'custom-lottery-agent-dashboard',
            __('Dashboard', 'custom-lottery'),
            __('Dashboard', 'custom-lottery'),
            'enter_lottery_numbers',
            'custom-lottery-agent-dashboard',
            'custom_lottery_agent_dashboard_page_callback'
        );
        add_submenu_page(
            'custom-lottery-agent-dashboard',
            __('Lottery Entry', 'custom-lottery'),
            __('Lottery Entry', 'custom-lottery'),
            'enter_lottery_numbers',
            'custom-lottery-entry',
            'custom_lottery_entry_page_callback'
        );
        add_submenu_page(
            'custom-lottery-agent-dashboard',
            __('My Entries', 'custom-lottery'),
            __('My Entries', 'custom-lottery'),
            'enter_lottery_numbers',
            'custom-lottery-agent-entries',
            'custom_lottery_all_entries_page_callback'
        );
        add_submenu_page(
            'custom-lottery-agent-dashboard',
            __('My Customers', 'custom-lottery'),
            __('My Customers', 'custom-lottery'),
            'enter_lottery_numbers',
            'custom-lottery-agent-customers',
            'custom_lottery_customers_page_callback'
        );
         add_submenu_page(
            'custom-lottery-agent-dashboard',
            __('My Commission', 'custom-lottery'),
            __('My Commission', 'custom-lottery'),
            'enter_lottery_numbers',
            'custom-lottery-agent-commission',
            'custom_lottery_agent_commission_page_callback'
        );

        add_submenu_page(
            'custom-lottery-agent-dashboard',
            __('My Wallet', 'custom-lottery'),
            __('My Wallet', 'custom-lottery'),
            'enter_lottery_numbers',
            'custom-lottery-agent-wallet',
            'custom_lottery_agent_wallet_page_callback'
        );

    } else {
        // Original Menu for Admins, Managers, and other roles
        $dashboard_hook = add_menu_page(
            __('Dashboard', 'custom-lottery'), // Page Title
            __('Lottery', 'custom-lottery'),   // Menu Title
            'manage_options',
            'custom-lottery-dashboard',
            'custom_lottery_dashboard_page_callback',
            'dashicons-tickets-alt',
            20
        );

        add_submenu_page(
            'custom-lottery-dashboard',
            __('Modification Requests', 'custom-lottery'),
            __('Modification Requests', 'custom-lottery'),
            'manage_options',
            'custom-lottery-mod-requests',
            'custom_lottery_mod_requests_page_callback'
        );


        add_submenu_page(
            'custom-lottery-dashboard',
            __('Lottery Entry', 'custom-lottery'),
            __('Lottery Entry', 'custom-lottery'),
            'enter_lottery_numbers', // Use custom capability
            'custom-lottery-entry',
            'custom_lottery_entry_page_callback'
        );

        add_submenu_page(
            'custom-lottery-dashboard',
            __('Reports', 'custom-lottery'),
            __('Reports', 'custom-lottery'),
            'manage_options',
            'custom-lottery-reports',
            'custom_lottery_reports_page_callback'
        );

        add_submenu_page(
            'custom-lottery-dashboard',
            __('Advanced Reports', 'custom-lottery'),
            __('Advanced Reports', 'custom-lottery'),
            'manage_options',
            'custom-lottery-advanced-reports',
            'custom_lottery_advanced_reports_page_callback'
        );

        add_submenu_page(
            'custom-lottery-dashboard',
            __('Payouts', 'custom-lottery'),
            __('Payouts', 'custom-lottery'),
            'manage_options',
            'custom-lottery-payouts',
            'custom_lottery_payouts_page_callback'
        );

        add_submenu_page(
            'custom-lottery-dashboard',
            __('Number Limiting', 'custom-lottery'),
            __('Number Limiting', 'custom-lottery'),
            'manage_options',
            'custom-lottery-limits',
            'custom_lottery_limits_page_callback'
        );

        add_submenu_page(
            'custom-lottery-dashboard',
            __('All Entries', 'custom-lottery'),
            __('All Entries', 'custom-lottery'),
            'manage_options',
            'custom-lottery-all-entries',
            'custom_lottery_all_entries_page_callback'
        );

        add_submenu_page(
            'custom-lottery-dashboard',
            __('Tools', 'custom-lottery'),
            __('Tools', 'custom-lottery'),
            'manage_options',
            'custom-lottery-tools',
            'custom_lottery_tools_page_callback'
        );

        add_submenu_page(
            'custom-lottery-dashboard',
            __('Customers', 'custom-lottery'),
            __('Customers', 'custom-lottery'),
            'manage_options',
            'custom-lottery-customers',
            'custom_lottery_customers_page_callback'
        );

        add_submenu_page(
            'custom-lottery-dashboard',
            'Lottery Settings',
            'Settings',
            'manage_options',
            'custom-lottery-settings',
            'custom_lottery_settings_page_callback'
        );

        add_submenu_page(
            'custom-lottery-dashboard',
            __('Agents', 'custom-lottery'),
            __('Agents', 'custom-lottery'),
            'manage_options',
            'custom-lottery-agents',
            'custom_lottery_agents_page_callback'
        );

        add_submenu_page(
            'custom-lottery-dashboard',
            __('Payout Management', 'custom-lottery'),
            __('Payout Management', 'custom-lottery'),
            'manage_options',
            'custom-lottery-payout-management',
            'custom_lottery_payout_management_page_callback'
        );

        add_action("load-{$dashboard_hook}", 'custom_lottery_add_dashboard_widgets');
    }
}
add_action('admin_menu', 'custom_lottery_admin_menu');

/**
 * Callback for the consolidated Payout Management page.
 */
function custom_lottery_payout_management_page_callback() {
    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'payout_requests';
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php echo esc_html__('Payout Management', 'custom-lottery'); ?></h1>
        <nav class="nav-tab-wrapper wp-clearfix" aria-label="Secondary menu">
            <a href="?page=custom-lottery-payout-management&tab=payout_requests" class="nav-tab <?php echo $active_tab == 'payout_requests' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Payout Requests', 'custom-lottery'); ?></a>
            <a href="?page=custom-lottery-payout-management&tab=manual_payout" class="nav-tab <?php echo $active_tab == 'manual_payout' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Manual Payout', 'custom-lottery'); ?></a>
            <a href="?page=custom-lottery-payout-management&tab=transaction_ledger" class="nav-tab <?php echo $active_tab == 'transaction_ledger' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Transaction Ledger', 'custom-lottery'); ?></a>
        </nav>

        <!-- Process Payout Modal -->
        <div id="process-payout-modal" title="<?php esc_attr_e('Process Payout Request', 'custom-lottery'); ?>" style="display:none;">
            <form id="process-payout-form" enctype="multipart/form-data">
                <input type="hidden" id="process-request-id" name="request_id">
                <input type="hidden" id="process-agent-id" name="agent_id">
                <?php wp_nonce_field('manage_payout_request_nonce', 'nonce'); ?>
                <p><strong><?php esc_html_e('Agent:', 'custom-lottery'); ?></strong> <span id="process-agent-name"></span></p>
                <p><strong><?php esc_html_e('Amount Requested:', 'custom-lottery'); ?></strong> <span id="process-amount-requested"></span> Kyat</p>
                <p><strong><?php esc_html_e('Agent Notes:', 'custom-lottery'); ?></strong> <span id="process-agent-notes"></span></p>
                <hr>
                <p>
                    <label for="process-payout-amount"><?php esc_html_e('Payout Amount (Kyat)', 'custom-lottery'); ?></label>
                    <input type="number" id="process-payout-amount" name="final_amount" class="widefat" step="0.01" min="0" required>
                </p>
                <p>
                    <label for="process-payout-method"><?php esc_html_e('Payout Method', 'custom-lottery'); ?></label>
                    <select id="process-payout-method" name="payout_method" class="widefat" required>
                        <option value="Cash"><?php esc_html_e('Cash', 'custom-lottery'); ?></option>
                        <option value="Bank Transfer"><?php esc_html_e('Bank Transfer', 'custom-lottery'); ?></option>
                        <option value="E-Wallet"><?php esc_html_e('E-Wallet', 'custom-lottery'); ?></option>
                        <option value="Other"><?php esc_html_e('Other', 'custom-lottery'); ?></option>
                    </select>
                </p>
                 <p>
                    <label for="process-proof-attachment"><?php esc_html_e('Proof of Transfer', 'custom-lottery'); ?></label>
                    <input type="file" id="process-proof-attachment" name="proof_attachment" class="widefat" accept="image/*,application/pdf">
                </p>
                <p>
                    <label for="process-admin-notes"><?php esc_html_e('Admin Notes', 'custom-lottery'); ?></label>
                    <textarea id="process-admin-notes" name="admin_notes" class="widefat" rows="3"></textarea>
                </p>
                <input type="hidden" name="outcome" value="approve">
            </form>
            <div id="process-modal-response" style="margin-top:10px;"></div>
        </div>

        <!-- Reject Payout Modal -->
        <div id="reject-payout-modal" title="<?php esc_attr_e('Reject Payout Request', 'custom-lottery'); ?>" style="display:none;">
            <form id="reject-payout-form">
                 <input type="hidden" id="reject-request-id" name="request_id">
                 <?php wp_nonce_field('manage_payout_request_nonce', 'nonce_reject'); ?>
                <p><strong><?php esc_html_e('Agent:', 'custom-lottery'); ?></strong> <span id="reject-agent-name"></span></p>
                <p>
                    <label for="reject-admin-notes"><?php esc_html_e('Reason for Rejection (Admin Notes)', 'custom-lottery'); ?></label>
                    <textarea id="reject-admin-notes" name="admin_notes" class="widefat" rows="4" required></textarea>
                </p>
                 <input type="hidden" name="outcome" value="reject">
            </form>
            <div id="reject-modal-response" style="margin-top:10px;"></div>
        </div>
        <div class="tab-content" style="margin-top: 20px;">
            <?php
            if ($active_tab === 'payout_requests') {
                custom_lottery_payout_requests_page_callback();
            } elseif ($active_tab === 'manual_payout') {
                custom_lottery_agent_payouts_page_callback();
            } elseif ($active_tab === 'transaction_ledger') {
                if ( ! class_exists( 'Lotto_All_Payouts_List_Table' ) ) {
                    require_once plugin_dir_path( __FILE__ ) . 'class-lotto-all-payouts-list-table.php';
                }
                echo '<h2>' . esc_html__('All Payout Transactions', 'custom-lottery') . '</h2>';
                $all_payouts_list_table = new Lotto_All_Payouts_List_Table();
                $all_payouts_list_table->prepare_items();
                $all_payouts_list_table->display();
            }
            ?>
        </div>
    </div>
    <?php
}
/**
 * Callback for the Agent Payouts page.
 */
function custom_lottery_agent_payouts_page_callback() {
    global $wpdb;
    $table_agents = $wpdb->prefix . 'lotto_agents';
    $table_users = $wpdb->users;

    // Fetch all commission agents with their user data
    $agents = $wpdb->get_results("
        SELECT a.id, a.balance, u.display_name
        FROM $table_agents a
        JOIN $table_users u ON a.user_id = u.ID
        WHERE a.agent_type = 'commission'
        ORDER BY u.display_name ASC
    ");
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php echo esc_html__('Agent Payouts', 'custom-lottery'); ?></h1>

        <div id="col-container">
            <div id="col-left">
                <div class="col-wrap">
                    <h2><?php echo esc_html__('Agent Balances', 'custom-lottery'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th scope="col"><?php esc_html_e('Agent Name', 'custom-lottery'); ?></th>
                                <th scope="col"><?php esc_html_e('Current Balance (Kyat)', 'custom-lottery'); ?></th>
                                <th scope="col"><?php esc_html_e('Action', 'custom-lottery'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($agents) : foreach ($agents as $agent) : ?>
                                <tr id="agent-row-<?php echo esc_attr($agent->id); ?>">
                                    <td><strong><?php echo esc_html($agent->display_name); ?></strong></td>
                                    <td class="agent-balance"><?php echo number_format($agent->balance, 2); ?></td>
                                    <td>
                                        <button class="button button-primary make-payout-button"
                                                data-agent-id="<?php echo esc_attr($agent->id); ?>"
                                                data-agent-name="<?php echo esc_attr($agent->display_name); ?>">
                                            <?php esc_html_e('Make Payout', 'custom-lottery'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; else : ?>
                                <tr>
                                    <td colspan="3"><?php esc_html_e('No commission agents found.', 'custom-lottery'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div id="col-right">
                <div class="col-wrap">
                    <h2><?php echo esc_html__('Recent Transactions', 'custom-lottery'); ?></h2>
                    <?php
                    if ( ! class_exists( 'Lotto_Payouts_List_Table' ) ) {
                        require_once plugin_dir_path( __FILE__ ) . 'class-lotto-payouts-list-table.php';
                    }
                    $payouts_list_table = new Lotto_Payouts_List_Table();
                    $payouts_list_table->prepare_items();
                    $payouts_list_table->display();
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Payout Modal -->
    <div id="payout-modal" title="Make a Payout" style="display:none;">
        <form id="payout-form" enctype="multipart/form-data">
            <p><strong>Agent:</strong> <span id="modal-agent-name"></span></p>
            <input type="hidden" id="modal-agent-id" name="agent_id">
            <?php wp_nonce_field('make_payout_nonce_action', 'make_payout_nonce'); ?>
            <p>
                <label for="payout-amount"><?php esc_html_e('Payout Amount (Kyat)', 'custom-lottery'); ?></label>
                <input type="number" id="payout-amount" name="amount" class="widefat" step="0.01" min="0" required>
            </p>
            <p>
                <label for="payout-method"><?php esc_html_e('Payout Method', 'custom-lottery'); ?></label>
                <select id="payout-method" name="payout_method" class="widefat" required>
                    <option value="Cash"><?php esc_html_e('Cash', 'custom-lottery'); ?></option>
                    <option value="Bank Transfer"><?php esc_html_e('Bank Transfer', 'custom-lottery'); ?></option>
                    <option value="E-Wallet"><?php esc_html_e('E-Wallet', 'custom-lottery'); ?></option>
                    <option value="Other"><?php esc_html_e('Other', 'custom-lottery'); ?></option>
                </select>
            </p>
             <p>
                <label for="proof-attachment"><?php esc_html_e('Proof of Transfer', 'custom-lottery'); ?></label>
                <input type="file" id="proof-attachment" name="proof_attachment" class="widefat" accept="image/*,application/pdf">
            </p>
            <p>
                <label for="payout-notes"><?php esc_html_e('Notes (Optional)', 'custom-lottery'); ?></label>
                <textarea id="payout-notes" name="notes" class="widefat" rows="3"></textarea>
            </p>
            <button type="submit" class="button button-primary"><?php esc_html_e('Record Payout', 'custom-lottery'); ?></button>
        </form>
        <div id="modal-response" style="margin-top:10px;"></div>
    </div>
    <?php
}

/**
 * Callback for the Agent Wallet page.
 */
function custom_lottery_agent_wallet_page_callback() {
    global $wpdb;
    $current_user_id = get_current_user_id();
    $table_agents = $wpdb->prefix . 'lotto_agents';
    $table_requests = $wpdb->prefix . 'lotto_payout_requests';

    // Fetch the agent's data
    $agent = $wpdb->get_row($wpdb->prepare("SELECT id, balance, payout_threshold FROM $table_agents WHERE user_id = %d", $current_user_id));
    $total_balance = $agent ? (float) $agent->balance : 0;

    // Fetch pending payouts
    $pending_payouts = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(amount) FROM $table_requests WHERE agent_id = %d AND status = 'pending'",
        $agent->id
    ));
    $pending_payouts = $pending_payouts ? (float) $pending_payouts : 0;

    $available_balance = $total_balance - $pending_payouts;

    // Determine the payout threshold
    $default_threshold = (float) get_option('custom_lottery_default_payout_threshold', 10000);
    $payout_threshold = !empty($agent->payout_threshold) ? (float) $agent->payout_threshold : $default_threshold;
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php echo esc_html__('My Wallet', 'custom-lottery'); ?></h1>

        <div id="wallet-summary" style="display: flex; gap: 20px; margin-top: 20px;">
            <div class="postbox" style="flex: 1;">
                <h2 class="hndle"><span><?php esc_html_e('Total Balance', 'custom-lottery'); ?></span></h2>
                <div class="inside">
                    <p style="font-size: 24px; margin: 0;"><?php echo number_format($total_balance, 2); ?> Kyat</p>
                </div>
            </div>
            <div class="postbox" style="flex: 1;">
                <h2 class="hndle"><span><?php esc_html_e('Pending Payouts', 'custom-lottery'); ?></span></h2>
                <div class="inside">
                    <p style="font-size: 24px; margin: 0; color: orange;"><?php echo number_format($pending_payouts, 2); ?> Kyat</p>
                </div>
            </div>
            <div class="postbox" style="flex: 1;">
                <h2 class="hndle"><span><?php esc_html_e('Available Balance', 'custom-lottery'); ?></span></h2>
                <div class="inside">
                    <p style="font-size: 24px; margin: 0; color: green;"><?php echo number_format($available_balance, 2); ?> Kyat</p>
                </div>
            </div>
        </div>

        <?php if ($available_balance >= $payout_threshold) : ?>
            <button id="request-payout-button" class="button button-primary" style="margin-top: 20px;"><?php esc_html_e('Request Payout', 'custom-lottery'); ?></button>
        <?php else : ?>
            <p style="margin-top: 20px;"><i><?php esc_html_e('Your available balance is below the threshold to request a payout.', 'custom-lottery'); ?></i></p>
        <?php endif; ?>

        <h2 style="margin-top: 40px;"><?php echo esc_html__('Payout History', 'custom-lottery'); ?></h2>
        <p><?php echo esc_html__('Here is the history of all your payout requests.', 'custom-lottery'); ?></p>

        <form method="get">
            <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
            <?php
            $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
            $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';
            ?>
            <label for="start-date"><?php echo esc_html__('Start Date:', 'custom-lottery'); ?></label>
            <input type="date" id="start-date" name="start_date" value="<?php echo esc_attr($start_date); ?>">
            <label for="end-date"><?php echo esc_html__('End Date:', 'custom-lottery'); ?></label>
            <input type="date" id="end-date" name="end_date" value="<?php echo esc_attr($end_date); ?>">
            <input type="submit" class="button" value="<?php esc_attr_e('Filter', 'custom-lottery'); ?>">
        </form>

        <?php
        $wallet_history_table = new Lotto_Agent_Wallet_History_List_Table();
        $wallet_history_table->prepare_items();
        $wallet_history_table->display();
        ?>

        <!-- Payout Request Modal -->
        <div id="payout-request-modal" title="Request a Payout" style="display:none;">
            <form id="payout-request-form">
                <?php wp_nonce_field('agent_request_payout_action', 'agent_request_payout_nonce'); ?>
                <p>
                    <label for="request-amount"><?php esc_html_e('Payout Amount (Kyat)', 'custom-lottery'); ?></label>
                    <input type="number" id="request-amount" name="amount" class="widefat" step="0.01" min="<?php echo esc_attr($payout_threshold); ?>" max="<?php echo esc_attr($total_balance); ?>" required>
                    <p class="description"><?php printf(__('Minimum: %s, Maximum: %s (Your Current Balance)'), number_format($payout_threshold, 2), number_format($total_balance, 2)); ?></p>
                </p>
                <p>
                    <label for="request-notes"><?php esc_html_e('Notes for Admin (Optional)', 'custom-lottery'); ?></label>
                    <textarea id="request-notes" name="notes" class="widefat" rows="3"></textarea>
                </p>
                <button type="submit" class="button button-primary"><?php esc_html_e('Submit Request', 'custom-lottery'); ?></button>
            </form>
            <div id="request-modal-response" style="margin-top:10px;"></div>
        </div>
    </div>
    <?php
}


/**
 * Enqueue scripts and styles for the admin pages.
 */
function custom_lottery_admin_enqueue_scripts($hook) {
    // For the main dashboard, enqueue Chart.js
    if ($hook === 'toplevel_page_custom-lottery-dashboard') {
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '3.7.0', true);
    }

    // For the tools page, enqueue custom JS for manual import
    if ($hook === 'lottery_page_custom-lottery-tools') {
        wp_enqueue_script(
            'lottery-tools-js',
            plugin_dir_url(__FILE__) . '../js/tools-page.js',
            ['jquery'],
            '1.0.0',
            true
        );
        wp_localize_script('lottery-tools-js', 'lotteryToolsAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('manual_import_nonce')
        ]);
    }

    // For the reports page (financial report), enqueue custom JS for cover requests
    if ($hook === 'lottery_page_custom-lottery-reports') {
         wp_enqueue_script(
            'lottery-cover-requests-js',
            plugin_dir_url(__FILE__) . '../js/cover-requests.js',
            ['jquery'],
            '1.0.0',
            true
        );
        wp_localize_script('lottery-cover-requests-js', 'coverRequestsAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cover_requests_nonce')
        ]);
    }

    // Enqueue scripts for the entry page and any page that uses the entry form popup
    if (strpos($hook, 'custom-lottery-entry') !== false || strpos($hook, 'custom-lottery-all-entries') !== false) {
        // Enqueue jQuery UI dialog and autocomplete
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_script('jquery-ui-autocomplete');
        wp_enqueue_style('wp-admin'); // For dialog styling

        // Enqueue the main entry form script
        wp_enqueue_script(
            'lottery-entry-form-js',
            plugin_dir_url(__FILE__) . '../js/entry-form.js',
            ['jquery', 'jquery-ui-dialog', 'jquery-ui-autocomplete'],
            '1.1.0', // Incremented version
            true
        );

        // Localize script with necessary data
        wp_localize_script('lottery-entry-form-js', 'lotteryEntryAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lottery_entry_action'),
            'search_nonce' => wp_create_nonce('lottery_entry_action') // Using same nonce for simplicity
        ]);
    }

     if ($hook === 'lottery_page_custom-lottery-agent-payouts') {
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_style('wp-admin'); // For dialog styling

        wp_add_inline_script('jquery-ui-dialog', "
            jQuery(document).ready(function(\$) {
                var \$modal = \$('#payout-modal').dialog({
                    autoOpen: false,
                    modal: true,
                    width: 400,
                    close: function() {
                        \$('#payout-form')[0].reset();
                        \$('#modal-response').empty();
                    }
                });

                \$('.make-payout-button').on('click', function() {
                    var agentId = \$(this).data('agent-id');
                    var agentName = \$(this).data('agent-name');
                    \$('#modal-agent-id').val(agentId);
                    \$('#modal-agent-name').text(agentName);
                    \$modal.dialog('open');
                });

                \$('#payout-form').on('submit', function(e) {
                    e.preventDefault();
                    \$('#modal-response').text('Processing...').css('color', 'black');

                    var formData = new FormData(this);
                    formData.append('action', 'make_payout');
                    formData.append('nonce', \$('#make_payout_nonce').val());
                    var agentId = \$('#modal-agent-id').val();

                    \$.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                \$('#modal-response').text(response.data.message).css('color', 'green');
                                // Update the balance in the table
                                var newBalance = parseFloat(response.data.new_balance).toFixed(2);
                                \$('#agent-row-' + agentId).find('.agent-balance').text(newBalance.replace(/\\B(?=(\\d{3})+(?!\\d))/g, ','));
                                // Reload the page to show the updated recent transactions table
                                setTimeout(function() {
                                    location.reload();
                                }, 1500);
                            } else {
                                \$('#modal-response').text(response.data.message).css('color', 'red');
                            }
                        },
                        error: function() {
                            \$('#modal-response').text('An error occurred during the request.').css('color', 'red');
                        }
                    });
                });
            });
        ");
    }

    // For the Agent Wallet page
    if ($hook === 'agent-portal_page_custom-lottery-agent-wallet' || $hook === 'my-wallet_page_custom-lottery-agent-payout-requests') {
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_style('wp-admin'); // For dialog styling

        wp_add_inline_script('jquery-ui-dialog', "
            jQuery(document).ready(function($) {
                var requestModal = $('#payout-request-modal').dialog({
                    autoOpen: false,
                    modal: true,
                    width: 600,
                    height: 'auto',
                    dialogClass: 'wp-dialog',
                    close: function() {
                        // Reset the form when the popup is closed to ensure it's clean for the next use.
                        var $form = $('#payout-request-form', this);
                        if ($form.length) {
                            $form[0].reset();
                        }
                        $('#request-modal-response', this).html('');
                    }
                });

                $('#request-payout-button').on('click', function() {
                    requestModal.dialog('open');
                });

                $('#payout-request-form').on('submit', function(e) {
                    e.preventDefault();
                    $('#request-modal-response').text('Submitting request...').css('color', 'black');

                    var data = {
                        action: 'agent_request_payout',
                        nonce: $('#agent_request_payout_nonce').val(),
                        amount: $('#request-amount').val(),
                        notes: $('#request-notes').val()
                    };

                    $.post(ajaxurl, data, function(response) {
                        if (response.success) {
                            $('#request-modal-response').text(response.data.message).css('color', 'green');
                            setTimeout(function() {
                                requestModal.dialog('close');
                                location.reload(); // Reload to hide the button if balance drops below threshold
                            }, 2000);
                        } else {
                            $('#request-modal-response').text(response.data.message).css('color', 'red');
                        }
                    });
                });
            });
        ");
    }

    // For the Payout Requests page
    if (strpos($hook, 'custom-lottery-payout-management') !== false) {
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_style('wp-admin'); // For dialog styling

        wp_add_inline_script('jquery-ui-dialog', "
            jQuery(document).ready(function($) {
                // Process Modal
                var processModal = $('#process-payout-modal').dialog({
                    autoOpen: false,
                    modal: true,
                    width: 450,
                    buttons: {
                        'Submit': function() {
                            $('#process-payout-form').submit();
                        },
                        'Cancel': function() {
                            $(this).dialog('close');
                        }
                    },
                    close: function() {
                        $('#process-payout-form')[0].reset();
                        $('#process-modal-response').empty();
                    }
                });

                $('.process-payout-button').on('click', function() {
                    var button = $(this);
                    $('#process-request-id').val(button.data('request-id'));
                    $('#process-agent-id').val(button.data('agent-id'));
                    $('#process-agent-name').text(button.data('agent-name'));
                    $('#process-amount-requested').text(parseFloat(button.data('amount')).toFixed(2));
                    $('#process-agent-notes').text(button.data('agent-notes'));
                    $('#process-payout-amount').val(button.data('amount'));
                    processModal.dialog('open');
                });

                $('#process-payout-form').on('submit', function(e) {
                    e.preventDefault();
                    var formData = new FormData(this);
                    formData.append('action', 'manage_payout_request');
                    var requestId = $('#process-request-id').val();

                    $('#process-modal-response').text('Processing...').css('color', 'black');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                $('#process-modal-response').text(response.data.message).css('color', 'green');
                                setTimeout(function() {
                                    processModal.dialog('close');
                                    // Dynamically update the row
                                    var row = $('button[data-request-id=\'' + requestId + '\']').closest('tr');
                                    row.find('.status').text(response.data.new_status);
                                    row.find('.column-final_amount').text(response.data.final_amount);
                                    row.find('.column-resolved_at').text(response.data.resolved_at);
                                    row.find('.column-admin_notes').text(response.data.admin_notes);
                                    row.find('.column-actions').html('N/A');
                                }, 1500);
                            } else {
                                $('#process-modal-response').text(response.data.message).css('color', 'red');
                            }
                        },
                        error: function() {
                             $('#process-modal-response').text('An error occurred.').css('color', 'red');
                        }
                    });
                });


                // Reject Modal
                var rejectModal = $('#reject-payout-modal').dialog({
                    autoOpen: false,
                    modal: true,
                    width: 400,
                    buttons: {
                        'Submit Rejection': function() {
                            $('#reject-payout-form').submit();
                        },
                        'Cancel': function() {
                            $(this).dialog('close');
                        }
                    },
                    close: function() {
                        $('#reject-payout-form')[0].reset();
                        $('#reject-modal-response').empty();
                    }
                });

                $('.reject-payout-button').on('click', function() {
                    var button = $(this);
                    $('#reject-request-id').val(button.data('request-id'));
                    $('#reject-agent-name').text(button.data('agent-name'));
                    rejectModal.dialog('open');
                });

                $('#reject-payout-form').on('submit', function(e) {
                    e.preventDefault();
                    var requestId = $('#reject-request-id').val();
                    var data = {
                        action: 'manage_payout_request',
                        request_id: requestId,
                        nonce: $('#reject-payout-form #nonce_reject').val(),
                        admin_notes: $('#reject-admin-notes').val(),
                        outcome: 'reject'
                    };

                    $('#reject-modal-response').text('Processing...').css('color', 'black');

                    $.post(ajaxurl, data, function(response) {
                        if (response.success) {
                            $('#reject-modal-response').text(response.data.message).css('color', 'green');
                            setTimeout(function() {
                                rejectModal.dialog('close');
                                // Dynamically update the row
                                var row = $('button[data-request-id=\'' + requestId + '\']').closest('tr');
                                row.find('.status').text(response.data.new_status);
                                row.find('.column-resolved_at').text(response.data.resolved_at);
                                row.find('.column-admin_notes').text(response.data.admin_notes);
                                row.find('.column-actions').html('N/A');
                            }, 1500);
                        } else {
                            $('#reject-modal-response').text(response.data.message).css('color', 'red');
                        }
                    });
                });

            });
        ");
    }

    if ($hook === 'lottery_page_custom-lottery-agents') {
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', "
            jQuery(document).ready(function($) {
                $('#edit-agent-form').on('submit', function(e) {
                    e.preventDefault();

                    var \$form = $(this);
                    var \$responseDiv = $('#agent-form-response');
                    var \$submitButton = \$form.find('input[type=submit]');
                    var originalButtonText = \$submitButton.val();

                    \$responseDiv.html('<p class=\"notice notice-info\">Saving...</p>').show();
                    \$submitButton.val('Saving...').prop('disabled', true);

                    var data = \$form.serialize() + '&action=update_agent&nonce=' + '" . wp_create_nonce('cl_save_agent_action') . "';

                    $.post(ajaxurl, data, function(response) {
                        if (response.success) {
                            \$responseDiv.html('<p class=\"notice notice-success\">' + response.data.message + '</p>');
                             // Redirect back to the list after a short delay
                            setTimeout(function() {
                                window.location.href = '?page=custom-lottery-agents';
                            }, 1500);
                        } else {
                            \$responseDiv.html('<p class=\"notice notice-error\">' + response.data.message + '</p>');
                            \$submitButton.val(originalButtonText).prop('disabled', false);
                        }
                    }).fail(function() {
                        \$responseDiv.html('<p class=\"notice notice-error\">An unexpected error occurred. Please try again.</p>');
                        \$submitButton.val(originalButtonText).prop('disabled', false);
                    });
                });
            });
        ");
    }
}
add_action('admin_enqueue_scripts', 'custom_lottery_admin_enqueue_scripts');

/**
 * Callback for the Agent Dashboard page.
 */
function custom_lottery_agent_dashboard_page_callback() {
    global $wpdb;
    $table_agents = $wpdb->prefix . 'lotto_agents';
    $table_entries = $wpdb->prefix . 'lotto_entries';
    $current_user_id = get_current_user_id();

    $agent = $wpdb->get_row($wpdb->prepare("SELECT id, commission_rate, balance FROM $table_agents WHERE user_id = %d", $current_user_id));

    if (!$agent) {
        echo '<div class="wrap"><h1>' . esc_html__('Error', 'custom-lottery') . '</h1><p>' . esc_html__('Could not retrieve your agent information.', 'custom-lottery') . '</p></div>';
        return;
    }

    $agent_id = $agent->id;
    $commission_rate = $agent->commission_rate / 100;

    $timezone = new DateTimeZone('Asia/Yangon');

    // Today's stats
    $today_start = (new DateTime('today', $timezone))->format('Y-m-d H:i:s');
    $today_end = (new DateTime('tomorrow', $timezone))->format('Y-m-d H:i:s');
    $todays_sales = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(amount) FROM $table_entries WHERE agent_id = %d AND timestamp >= %s AND timestamp < %s",
        $agent_id, $today_start, $today_end
    ));
    $todays_commission = $todays_sales * $commission_rate;

    // This month's stats
    $month_start = (new DateTime('first day of this month', $timezone))->format('Y-m-d H:i:s');
    $month_end = (new DateTime('first day of next month', $timezone))->format('Y-m-d H:i:s');
    $monthly_sales = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(amount) FROM $table_entries WHERE agent_id = %d AND timestamp >= %s AND timestamp < %s",
        $agent_id, $month_start, $month_end
    ));
    $monthly_commission = $monthly_sales * $commission_rate;

    // Recent entries
    $recent_entries = $wpdb->get_results($wpdb->prepare(
        "SELECT customer_name, lottery_number, amount, timestamp FROM $table_entries WHERE agent_id = %d ORDER BY timestamp DESC LIMIT 10",
        $agent_id
    ));
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Agent Dashboard', 'custom-lottery'); ?></h1>

        <div id="dashboard-widgets-wrap">
            <div id="dashboard-widgets" class="metabox-holder">
                <div class="postbox-container" style="width: 100%;">
                    <div class="meta-box-sortables">
                        <div class="postbox">
                            <h2 class="hndle"><span><?php esc_html_e('Sales Summary', 'custom-lottery'); ?></span></h2>
                            <div class="inside">
                                <div style="display: flex; justify-content: space-around; text-align: center;">
                                    <div>
                                        <h3><?php esc_html_e('Today\'s Sales', 'custom-lottery'); ?></h3>
                                        <p style="font-size: 24px; margin: 0;"><?php echo number_format($todays_sales ?? 0, 2); ?> Kyat</p>
                                    </div>
                                    <div>
                                        <h3><?php esc_html_e('Today\'s Commission', 'custom-lottery'); ?></h3>
                                        <p style="font-size: 24px; margin: 0; color: green;"><?php echo number_format($todays_commission ?? 0, 2); ?> Kyat</p>
                                    </div>
                                    <div>
                                        <h3><?php esc_html_e('This Month\'s Sales', 'custom-lottery'); ?></h3>
                                        <p style="font-size: 24px; margin: 0;"><?php echo number_format($monthly_sales ?? 0, 2); ?> Kyat</p>
                                    </div>
                                     <div>
                                        <h3><?php esc_html_e('This Month\'s Commission', 'custom-lottery'); ?></h3>
                                        <p style="font-size: 24px; margin: 0; color: green;"><?php echo number_format($monthly_commission ?? 0, 2); ?> Kyat</p>
                                    </div>
                                    <div>
                                        <h3><?php esc_html_e('Current Balance', 'custom-lottery'); ?></h3>
                                        <p style="font-size: 24px; margin: 0; color: blue;"><?php echo number_format($agent->balance ?? 0, 2); ?> Kyat</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="postbox">
                            <h2 class="hndle"><span><?php esc_html_e('Recent Entries', 'custom-lottery'); ?></span></h2>
                            <div class="inside">
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e('Date', 'custom-lottery'); ?></th>
                                            <th><?php esc_html_e('Customer', 'custom-lottery'); ?></th>
                                            <th><?php esc_html_e('Number', 'custom-lottery'); ?></th>
                                            <th><?php esc_html_e('Amount (Kyat)', 'custom-lottery'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($recent_entries) : foreach ($recent_entries as $entry) : ?>
                                            <tr>
                                                <td><?php echo esc_html(date('Y-m-d H:i', strtotime($entry->timestamp))); ?></td>
                                                <td><?php echo esc_html($entry->customer_name); ?></td>
                                                <td><?php echo esc_html($entry->lottery_number); ?></td>
                                                <td><?php echo number_format($entry->amount, 2); ?></td>
                                            </tr>
                                        <?php endforeach; else : ?>
                                            <tr><td colspan="4"><?php esc_html_e('No recent entries found.', 'custom-lottery'); ?></td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="postbox">
                            <h2 class="hndle"><span><?php esc_html_e('Recent Wallet Transactions', 'custom-lottery'); ?></span></h2>
                            <div class="inside">
                                <?php
                                if ( ! class_exists( 'Lotto_Payouts_List_Table' ) ) {
                                    require_once plugin_dir_path( __FILE__ ) . 'class-lotto-payouts-list-table.php';
                                }
                                $transactions_list_table = new Lotto_Payouts_List_Table();
                                $transactions_list_table->prepare_items( $agent_id );
                                $transactions_list_table->display();
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Callback for the Agent Commission page.
 */
function custom_lottery_agent_commission_page_callback() {
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php echo esc_html__('My Commission History', 'custom-lottery'); ?></h1>
        <p><?php echo esc_html__('Here you can see the history of all commissions you have earned.', 'custom-lottery'); ?></p>

        <form method="get">
            <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
            <?php
            // We will instantiate and display the list table here in the next step.
            // For now, let's add the date filters.
            $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
            $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';
            ?>
            <label for="start-date"><?php echo esc_html__('Start Date:', 'custom-lottery'); ?></label>
            <input type="date" id="start-date" name="start_date" value="<?php echo esc_attr($start_date); ?>">
            <label for="end-date"><?php echo esc_html__('End Date:', 'custom-lottery'); ?></label>
            <input type="date" id="end-date" name="end_date" value="<?php echo esc_attr($end_date); ?>">
            <input type="submit" class="button" value="<?php esc_attr_e('Filter', 'custom-lottery'); ?>">
        </form>

        <?php
        // Create an instance of our package class...
        $commission_list_table = new Lotto_Commission_List_Table();
        // Fetch, prepare, sort, and filter our data...
        $commission_list_table->prepare_items();
        // ...and display it.
        $commission_list_table->display();
        ?>
    </div>
    <?php
}


/**
 * Callback for the Agents page.
 */
function custom_lottery_agents_page_callback() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'lotto_agents';
    $page_slug = 'custom-lottery-agents';
    $action = isset($_REQUEST['action']) ? sanitize_key($_REQUEST['action']) : 'list';
    $agent_id = isset($_REQUEST['agent_id']) ? absint($_REQUEST['agent_id']) : 0;

    // Handle deletion
    if ($action === 'delete' && $agent_id > 0) {
        $nonce = isset($_REQUEST['_wpnonce']) ? $_REQUEST['_wpnonce'] : '';
        if (wp_verify_nonce($nonce, 'cl_delete_agent_' . $agent_id)) {
            if ($wpdb->delete($table_name, ['id' => $agent_id])) {
                echo '<div class="updated"><p>' . esc_html__('Agent deleted successfully.', 'custom-lottery') . '</p></div>';
            }
        }
        $action = 'list'; // Go back to the list view
    }

    // Display add/edit form or the list table
    if ($action === 'add' || ($action === 'edit' && $agent_id > 0)) {
        $agent = null;
        if ($agent_id > 0) {
            $agent = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $agent_id));
        }
        $form_title = $agent ? __('Edit Agent', 'custom-lottery') : __('Add New Agent', 'custom-lottery');
        $button_text = $agent ? __('Save Changes', 'custom-lottery') : __('Add Agent', 'custom-lottery');

        $assigned_user_ids = $wpdb->get_col("SELECT user_id FROM $table_name");
        $all_users = get_users(['fields' => ['ID', 'display_name']]);
        $available_users = [];

        foreach ($all_users as $user) {
            $is_assigned = in_array($user->ID, $assigned_user_ids);
            if ($agent && $agent->user_id == $user->ID) {
                $available_users[] = $user;
            } elseif (!$is_assigned) {
                $available_users[] = $user;
            }
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html($form_title); ?></h1>
            <div id="agent-form-response"></div>
            <a href="?page=<?php echo esc_attr($page_slug); ?>" class="button">&larr; <?php esc_html_e('Back to Agents List', 'custom-lottery'); ?></a>
            <form id="edit-agent-form" style="margin-top: 20px;">
                <input type="hidden" name="agent_id" value="<?php echo esc_attr($agent_id); ?>">
                <?php wp_nonce_field('cl_save_agent_action', 'cl_save_agent_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="user_id"><?php esc_html_e('User', 'custom-lottery'); ?></label></th>
                        <td>
                            <select id="user_id" name="user_id" required>
                                <option value=""><?php esc_html_e('Select an available User', 'custom-lottery'); ?></option>
                                <?php foreach ($available_users as $user) : ?>
                                    <option value="<?php echo esc_attr($user->ID); ?>" <?php if ($agent) selected($agent->user_id, $user->ID); ?>><?php echo esc_html($user->display_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="agent_type"><?php esc_html_e('Agent Type', 'custom-lottery'); ?></label></th>
                        <td>
                            <select id="agent_type" name="agent_type" required>
                                <option value="commission" <?php if ($agent) selected($agent->agent_type, 'commission'); ?>><?php esc_html_e('Commission Agent', 'custom-lottery'); ?></option>
                                <option value="cover" <?php if ($agent) selected($agent->agent_type, 'cover'); ?>><?php esc_html_e('Cover Agent', 'custom-lottery'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr class="commission-only-row">
                        <th scope="row"><label for="commission_rate"><?php esc_html_e('Commission Rate (%)', 'custom-lottery'); ?></label></th>
                        <td><input type="number" id="commission_rate" name="commission_rate" value="<?php echo $agent ? esc_attr($agent->commission_rate) : '0.00'; ?>" step="0.01" min="0"></td>
                    </tr>
                     <tr class="commission-only-row">
                        <th scope="row"><label for="per_number_limit"><?php esc_html_e('Per-Number Limit Amount', 'custom-lottery'); ?></label></th>
                        <td><input type="number" id="per_number_limit" name="per_number_limit" value="<?php echo $agent ? esc_attr($agent->per_number_limit) : '0.00'; ?>" step="1" min="0"></td>
                    </tr>
                    <tr class="commission-only-row">
                        <th scope="row"><label for="payout_threshold"><?php esc_html_e('Individual Payout Threshold', 'custom-lottery'); ?></label></th>
                        <td><input type="number" id="payout_threshold" name="payout_threshold" value="<?php echo $agent ? esc_attr($agent->payout_threshold) : ''; ?>" step="0.01" min="0">
                        <p class="description"><?php esc_html_e('Leave blank to use the default threshold from the settings page.', 'custom-lottery'); ?></p></td>
                    </tr>
                    <tr class="commission-only-row">
                        <th scope="row"><label for="morning_open"><?php esc_html_e('Morning Open Time', 'custom-lottery'); ?></label></th>
                        <td><input type="time" id="morning_open" name="morning_open" value="<?php echo $agent ? esc_attr($agent->morning_open) : ''; ?>"><p class="description"><?php esc_html_e('Leave blank to use default time.', 'custom-lottery'); ?></p></td>
                    </tr>
                    <tr class="commission-only-row">
                        <th scope="row"><label for="morning_close"><?php esc_html_e('Morning Close Time', 'custom-lottery'); ?></label></th>
                        <td><input type="time" id="morning_close" name="morning_close" value="<?php echo $agent ? esc_attr($agent->morning_close) : ''; ?>"><p class="description"><?php esc_html_e('Leave blank to use default time.', 'custom-lottery'); ?></p></td>
                    </tr>
                    <tr class="commission-only-row">
                        <th scope="row"><label for="evening_open"><?php esc_html_e('Evening Open Time', 'custom-lottery'); ?></label></th>
                        <td><input type="time" id="evening_open" name="evening_open" value="<?php echo $agent ? esc_attr($agent->evening_open) : ''; ?>"><p class="description"><?php esc_html_e('Leave blank to use default time.', 'custom-lottery'); ?></p></td>
                    </tr>
                    <tr class="commission-only-row">
                        <th scope="row"><label for="evening_close"><?php esc_html_e('Evening Close Time', 'custom-lottery'); ?></label></th>
                        <td><input type="time" id="evening_close" name="evening_close" value="<?php echo $agent ? esc_attr($agent->evening_close) : ''; ?>"><p class="description"><?php esc_html_e('Leave blank to use default time.', 'custom-lottery'); ?></p></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="status"><?php esc_html_e('Status', 'custom-lottery'); ?></label></th>
                        <td>
                            <select id="status" name="status" required>
                                <option value="active" <?php if ($agent) selected($agent->status, 'active'); ?>><?php esc_html_e('Active', 'custom-lottery'); ?></option>
                                <option value="inactive" <?php if ($agent) selected($agent->status, 'inactive'); ?>><?php esc_html_e('Inactive', 'custom-lottery'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button($button_text, 'primary', 'submit_agent'); ?>
            </form>
        </div>
        <script>
            jQuery(document).ready(function($) {
                function toggleCommissionFields() {
                    if ($('#agent_type').val() === 'commission') {
                        $('.commission-only-row').show();
                    } else {
                        $('.commission-only-row').hide();
                    }
                }
                toggleCommissionFields();
                $('#agent_type').on('change', toggleCommissionFields);
            });
        </script>
        <?php
    } else {
        require_once plugin_dir_path(__FILE__) . 'class-lotto-agents-list-table.php';
        $agents_list_table = new Lotto_Agents_List_Table();
        $agents_list_table->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html__('Agents', 'custom-lottery'); ?></h1>
            <a href="?page=<?php echo esc_attr($_REQUEST['page']); ?>&action=add" class="page-title-action"><?php echo esc_html__('Add New', 'custom-lottery'); ?></a>
            <form method="post">
                <?php $agents_list_table->display(); ?>
            </form>
        </div>
        <?php
    }
}

/**
 * Handles the form submission for adding/editing customers for agents.
 * This function is called before the page callback to process form data.
 */
function custom_lottery_agent_customer_form_handler() {
    if (!isset($_POST['submit_customer']) || !isset($_POST['cl_save_customer_nonce'])) {
        return;
    }

    if (!wp_verify_nonce($_POST['cl_save_customer_nonce'], 'cl_save_customer_action')) {
        wp_die('Security check failed.');
    }

    global $wpdb;
    $table_customers = $wpdb->prefix . 'lotto_customers';
    $table_agents = $wpdb->prefix . 'lotto_agents';
    $current_user_id = get_current_user_id();

    $agent_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_agents WHERE user_id = %d", $current_user_id));

    if (!$agent_id) {
        wp_die('You are not registered as an agent.');
    }

    $customer_id = isset($_POST['customer_id']) ? absint($_POST['customer_id']) : 0;
    $customer_name = sanitize_text_field($_POST['customer_name']);
    $phone = sanitize_text_field($_POST['phone']);

    $data = [
        'customer_name' => $customer_name,
        'phone' => $phone,
        'agent_id' => $agent_id, // Always associate with the current agent
    ];

    if ($customer_id > 0) {
        // Security check: Ensure the agent owns this customer before updating
        $owner_agent_id = $wpdb->get_var($wpdb->prepare("SELECT agent_id FROM $table_customers WHERE id = %d", $customer_id));
        if ($owner_agent_id != $agent_id) {
            wp_die('You do not have permission to edit this customer.');
        }

        if ($wpdb->update($table_customers, $data, ['id' => $customer_id])) {
             add_action('admin_notices', function() {
                echo '<div class="updated"><p>' . esc_html__('Customer updated successfully.', 'custom-lottery') . '</p></div>';
            });
        }
    } else {
        $data['last_seen'] = current_time('mysql');
        if ($wpdb->insert($table_customers, $data)) {
            add_action('admin_notices', function() {
                echo '<div class="updated"><p>' . esc_html__('Customer added successfully.', 'custom-lottery') . '</p></div>';
            });
        }
    }
}
add_action('admin_init', 'custom_lottery_agent_customer_form_handler');


/**
 * Renders the add/edit form for customers.
 */
function custom_lottery_render_customer_form($customer = null) {
    $page_slug = isset($_REQUEST['page']) ? sanitize_key($_REQUEST['page']) : '';
    $form_title = $customer ? __('Edit Customer', 'custom-lottery') : __('Add New Customer', 'custom-lottery');
    $button_text = $customer ? __('Save Changes', 'custom-lottery') : __('Add Customer', 'custom-lottery');
    $customer_id = $customer ? $customer->id : 0;
    $customer_name = $customer ? $customer->customer_name : '';
    $phone = $customer ? $customer->phone : '';
    ?>
    <div class="wrap">
        <h1><?php echo esc_html($form_title); ?></h1>
        <a href="?page=<?php echo esc_attr($page_slug); ?>" class="button">&larr; <?php esc_html_e('Back to Customers List', 'custom-lottery'); ?></a>
        <form method="post" action="?page=<?php echo esc_attr($page_slug); ?>" style="margin-top: 20px;">
            <input type="hidden" name="customer_id" value="<?php echo esc_attr($customer_id); ?>">
            <?php wp_nonce_field('cl_save_customer_action', 'cl_save_customer_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="customer_name"><?php esc_html_e('Customer Name', 'custom-lottery'); ?></label></th>
                    <td><input type="text" id="customer_name" name="customer_name" value="<?php echo esc_attr($customer_name); ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="phone"><?php esc_html_e('Phone', 'custom-lottery'); ?></label></th>
                    <td><input type="text" id="phone" name="phone" value="<?php echo esc_attr($phone); ?>" class="regular-text" required></td>
                </tr>
            </table>
            <?php submit_button($button_text, 'primary', 'submit_customer'); ?>
        </form>
    </div>
    <?php
}

/**
 * Callback for the Customers page.
 */
function custom_lottery_customers_page_callback() {
    global $wpdb;
    $table_customers = $wpdb->prefix . 'lotto_customers';
    $table_agents = $wpdb->prefix . 'lotto_agents';
    $current_user_id = get_current_user_id();

    $action = isset($_REQUEST['action']) ? sanitize_key($_REQUEST['action']) : 'list';
    $customer_id = isset($_REQUEST['customer_id']) ? absint($_REQUEST['customer_id']) : 0;
    $page_slug = isset($_REQUEST['page']) ? sanitize_key($_REQUEST['page']) : '';

    // Handle deletion for agents
    if ($action === 'delete' && $customer_id > 0 && in_array($page_slug, ['custom-lottery-agent-customers'])) {
        $nonce = isset($_REQUEST['_wpnonce']) ? $_REQUEST['_wpnonce'] : '';
        if (wp_verify_nonce($nonce, 'cl_delete_customer_' . $customer_id)) {
            // Security check: Ensure the agent owns this customer before deleting
            $agent_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_agents WHERE user_id = %d", $current_user_id));
            $owner_agent_id = $wpdb->get_var($wpdb->prepare("SELECT agent_id FROM $table_customers WHERE id = %d", $customer_id));

            if ($agent_id && $owner_agent_id == $agent_id) {
                if ($wpdb->delete($table_customers, ['id' => $customer_id])) {
                    echo '<div class="updated"><p>' . esc_html__('Customer deleted successfully.', 'custom-lottery') . '</p></div>';
                }
            } else {
                 echo '<div class="error"><p>' . esc_html__('You do not have permission to delete this customer.', 'custom-lottery') . '</p></div>';
            }
        }
        $action = 'list'; // Go back to the list view
    }


    if (($action === 'add' || $action === 'edit') && in_array($page_slug, ['custom-lottery-customers', 'custom-lottery-agent-customers'])) {
        $customer = null;
        if ($action === 'edit' && $customer_id > 0) {
            $customer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_customers WHERE id = %d", $customer_id));

            // Security check for agents trying to edit
            if (!current_user_can('manage_options')) {
                $agent_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_agents WHERE user_id = %d", $current_user_id));
                if (!$customer || $customer->agent_id != $agent_id) {
                    wp_die('You do not have permission to edit this customer.');
                }
            }
        }
        custom_lottery_render_customer_form($customer);
    } else {
        $customers_list_table = new Lotto_Customers_List_Table();
        $customers_list_table->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html__('Customers', 'custom-lottery'); ?></h1>
            <a href="?page=<?php echo esc_attr($page_slug); ?>&action=add" class="page-title-action"><?php echo esc_html__('Add New', 'custom-lottery'); ?></a>
            <form method="post">
                <?php $customers_list_table->display(); ?>
            </form>
        </div>
        <?php
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
            $table_winning_numbers = $wpdb->prefix . 'lotto_winning_numbers';

            // Delete entries
            $wpdb->query($wpdb->prepare("DELETE FROM $table_entries WHERE timestamp BETWEEN %s AND %s", $start_datetime, $end_datetime));

            // Delete limits
            $wpdb->query($wpdb->prepare("DELETE FROM $table_limits WHERE draw_date = %s", $date_to_clear));

            // Delete winning numbers
            $wpdb->query($wpdb->prepare("DELETE FROM $table_winning_numbers WHERE draw_date = %s", $date_to_clear));

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

        <div class="card" style="margin-top: 20px;">
            <h2><?php echo esc_html__('Manual Winning Number Import', 'custom-lottery'); ?></h2>
            <p><?php echo esc_html__('Click the button below to import the winning numbers for the last 10 days from the API. This will not overwrite existing entries.', 'custom-lottery'); ?></p>
            <form id="manual-import-form">
                <?php wp_nonce_field('manual_import_nonce', 'manual_import_nonce'); ?>
                <button type="button" id="manual-import-button" class="button button-secondary">
                    <?php echo esc_html__('Import Last 10 Days', 'custom-lottery'); ?>
                </button>
                <span id="manual-import-spinner" class="spinner" style="float: none; visibility: hidden;"></span>
            </form>
            <p id="manual-import-status" style="margin-top: 10px;"></p>
        </div>
    </div>
    <?php
}

/**
 * Callback function for the Dashboard page.
 */
function custom_lottery_dashboard_page_callback() {
    $screen = get_current_screen();
    $columns = absint( get_user_option( 'screen_layout_' . $screen->id ) );
    if ( $columns < 1 || $columns > 4 ) {
        $columns = 2; // Default to 2 columns
    }

    $columns_css = 'columns-' . $columns;
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__( 'Lottery Dashboard', 'custom-lottery' ); ?></h1>

        <?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
        <?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>

        <div id="dashboard-widgets-wrap">
            <div id="dashboard-widgets" class="metabox-holder <?php echo esc_attr( $columns_css ); ?>">
                <div id="postbox-container-1" class="postbox-container">
                    <?php do_meta_boxes( $screen->id, 'normal', null ); ?>
                </div>
                <div id="postbox-container-2" class="postbox-container">
                    <?php do_meta_boxes( $screen->id, 'side', null ); ?>
                </div>
                <?php if ( $columns > 2 ) : ?>
                <div id="postbox-container-3" class="postbox-container">
                    <?php do_meta_boxes( $screen->id, 'column3', null ); ?>
                </div>
                <?php endif; ?>
                <?php if ( $columns > 3 ) : ?>
                <div id="postbox-container-4" class="postbox-container">
                    <?php do_meta_boxes( $screen->id, 'column4', null ); ?>
                </div>
                <?php endif; ?>
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
    $table_agents = $wpdb->prefix . 'lotto_agents';

    $timezone = new DateTimeZone('Asia/Yangon');
    $default_start_date = (new DateTime('first day of this month', $timezone))->format('Y-m-d');
    $default_end_date = (new DateTime('last day of this month', $timezone))->format('Y-m-d');
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : $default_start_date;
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : $default_end_date;

    $query_start_date = $start_date . ' 00:00:00';
    $query_end_date = $end_date . ' 23:59:59';

    $where_clauses = [];
    $query_params = [];

    // Agent-specific data segregation
    $current_user = wp_get_current_user();
    if (in_array('commission_agent', (array) $current_user->roles)) {
        $agent_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_agents WHERE user_id = %d", $current_user->ID));
        if ($agent_id) {
            $where_clauses[] = "agent_id = %d";
            $query_params[] = $agent_id;
        } else {
            $where_clauses[] = "1=0"; // See no data
        }
    }

    $where_clauses[] = "timestamp BETWEEN %s AND %s";
    array_push($query_params, $query_start_date, $query_end_date);
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

    $top_customers_sql = "SELECT customer_name, phone, SUM(amount) as total_spent FROM $table_entries $where_sql GROUP BY customer_name, phone ORDER BY total_spent DESC LIMIT 20";
    $top_customers = $wpdb->get_results($wpdb->prepare($top_customers_sql, $query_params));

    $hot_numbers_sql = "SELECT lottery_number, COUNT(id) as purchase_count FROM $table_entries $where_sql GROUP BY lottery_number ORDER BY purchase_count DESC LIMIT 10";
    $hot_numbers = $wpdb->get_results($wpdb->prepare($hot_numbers_sql, $query_params));

    $cold_numbers_sql = "SELECT lottery_number, COUNT(id) as purchase_count FROM $table_entries $where_sql GROUP BY lottery_number ORDER BY purchase_count ASC LIMIT 10";
    $cold_numbers = $wpdb->get_results($wpdb->prepare($cold_numbers_sql, $query_params));
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
    $action2 = isset($_REQUEST['action2']) ? $_REQUEST['action2'] : '';

    // Handle bulk delete
    $bulk_action = ($action === 'bulk-delete' || $action2 === 'bulk-delete') ? 'bulk-delete' : null;
    if ($bulk_action && isset($_POST['customer_phone']) && check_admin_referer('bulk-customers')) {
        $phones_to_delete = array_map('sanitize_text_field', $_POST['customer_phone']);

        $timezone = new DateTimeZone('Asia/Yangon');
        $current_time = new DateTime('now', $timezone);
        $default_date = $current_time->format('Y-m-d');
        $filter_date = isset($_GET['filter_date']) && !empty($_GET['filter_date']) ? sanitize_text_field($_GET['filter_date']) : $default_date;

        $default_session = custom_lottery_get_current_session() ?? '12:01 PM';
        $filter_session = isset($_GET['filter_session']) ? sanitize_text_field($_GET['filter_session']) : $default_session;

        $start_datetime = $filter_date . ' 00:00:00';
        $end_datetime = $filter_date . ' 23:59:59';

        $placeholders = implode(', ', array_fill(0, count($phones_to_delete), '%s'));
        $query = "DELETE FROM $table_entries WHERE phone IN ($placeholders) AND timestamp BETWEEN %s AND %s";
        $params = array_merge($phones_to_delete, [$start_datetime, $end_datetime]);

        if ($filter_session !== 'all') {
            $query .= " AND draw_session = %s";
            $params[] = $filter_session;
        }

        $deleted_count = $wpdb->query($wpdb->prepare($query, $params));

        if ($deleted_count > 0) {
            custom_lottery_log_action('bulk_entries_deleted', ['phones' => $phones_to_delete, 'filter_date' => $filter_date, 'filter_session' => $filter_session, 'count' => $deleted_count]);
            echo '<div class="updated"><p>' . sprintf(esc_html__('%d entries deleted successfully.', 'custom-lottery'), $deleted_count) . '</p></div>';
        }
    }

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
        if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'cl_delete_entry_' . $entry_id)) {
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

        // Get default filter values for display
        $timezone = new DateTimeZone('Asia/Yangon');
        $current_time = new DateTime('now', $timezone);
        $default_date = $current_time->format('Y-m-d');

        $default_session = custom_lottery_get_current_session() ?? '12:01 PM';

        $selected_date = isset($_GET['filter_date']) && !empty($_GET['filter_date']) ? sanitize_text_field($_GET['filter_date']) : $default_date;
        $selected_session = isset($_GET['filter_session']) ? sanitize_text_field($_GET['filter_session']) : $default_session;
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html__('All Lottery Entries', 'custom-lottery'); ?></h1>
            <button type="button" id="add-new-entry-popup" class="page-title-action"><?php echo esc_html__('Add New Entry', 'custom-lottery'); ?></button>

            <form method="get" style="margin-bottom: 15px;">
                <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>">

                <label for="filter-date"><?php echo esc_html__('Date:', 'custom-lottery'); ?></label>
                <input type="date" id="filter-date" name="filter_date" value="<?php echo esc_attr($selected_date); ?>">

                <label for="filter-session"><?php echo esc_html__('Session:', 'custom-lottery'); ?></label>
                <select name="filter_session">
                    <option value="all" <?php selected($selected_session, 'all'); ?>><?php _e('All Sessions', 'custom-lottery'); ?></option>
                    <option value="12:01 PM" <?php selected($selected_session, '12:01 PM'); ?>>12:01 PM</option>
                    <option value="4:30 PM" <?php selected($selected_session, '4:30 PM'); ?>>4:30 PM</option>
                </select>

                <input type="submit" class="button" value="<?php _e('Filter', 'custom-lottery'); ?>">
            </form>

            <form method="post">
                <?php $lotto_list_table->display(); ?>
            </form>

            <div id="lottery-entry-popup" title="<?php esc_attr_e('Add New Lottery Entry', 'custom-lottery'); ?>" style="display:none;">
                <?php custom_lottery_render_entry_form(); ?>
            </div>

            <?php if (in_array('commission_agent', (array) wp_get_current_user()->roles)) : ?>
            <div id="modification-request-popup" title="<?php esc_attr_e('Request Entry Modification', 'custom-lottery'); ?>" style="display:none;">
                <form id="modification-request-form">
                    <input type="hidden" id="mod-request-entry-id" name="entry_id">
                    <?php wp_nonce_field('request_modification_nonce', 'mod_request_nonce'); ?>

                    <p>
                        <label for="mod-request-number"><?php esc_html_e('New Number:', 'custom-lottery'); ?></label><br>
                        <input type="text" id="mod-request-number" name="new_number" class="small-text" maxlength="2" pattern="\d{2}" required>
                    </p>
                    <p>
                        <label for="mod-request-amount"><?php esc_html_e('New Amount:', 'custom-lottery'); ?></label><br>
                        <input type="number" id="mod-request-amount" name="new_amount" class="small-text" step="1" min="0" required>
                    </p>
                    <p>
                        <label for="mod-request-notes"><?php esc_html_e('Reason for change (Notes):', 'custom-lottery'); ?></label><br>
                        <textarea id="mod-request-notes" name="request_notes" rows="3" style="width: 100%;" required></textarea>
                    </p>

                    <button type="submit" class="button button-primary" style="margin-top: 10px;"><?php esc_html_e('Submit Request', 'custom-lottery'); ?></button>
                </form>
                 <div id="mod-request-response"></div>
            </div>
            <?php endif; ?>

        </div>
        <?php
    }
}

/**
 * Renders the reusable lottery entry form.
 */
function custom_lottery_render_entry_form() {
    $default_session = custom_lottery_get_current_session() ?? '12:01 PM';
    ?>
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

        <hr>
        <h2><?php echo esc_html__( 'Entries', 'custom-lottery' ); ?></h2>
        <div id="entry-rows-wrapper">
            <div class="entry-row">
                <input type="text" name="lottery_number[]" placeholder="Number (e.g., 45)" maxlength="2" pattern="\d{2}" class="small-text" required>
                <input type="number" name="amount[]" placeholder="Amount" class="small-text" step="100" min="0" required>
                <label style="margin-left: 5px; margin-right: 10px;">
                    <input type="checkbox" name="reverse_entry[]" value="1"> <?php echo esc_html__( 'Reverse ("R")', 'custom-lottery' ); ?>
                </label>
                <span class="remove-entry-row" style="display:none;">&times;</span>
            </div>
        </div>
        <button type="button" id="add-entry-row" class="button" style="margin-top: 10px;"><?php echo esc_html__( 'Add More', 'custom-lottery' ); ?></button>

        <p class="submit">
            <button type="submit" class="button button-primary"><?php echo esc_html__( 'Submit All Entries', 'custom-lottery' ); ?></button>
        </p>
    </form>
    <div id="form-response"></div>
    <button id="print-receipt-button" class="button" style="display: none; margin-top: 10px;"><?php echo esc_html__( 'Print Receipt', 'custom-lottery' ); ?></button>
    <?php
}

/**
 * Callback function for the Lottery Entry page.
 */
function custom_lottery_entry_page_callback() {
    ?>
    <style>
        .entry-row { display: flex; align-items: center; margin-bottom: 10px; }
        .entry-row input { margin-right: 10px; }
        .entry-row .remove-entry-row { cursor: pointer; color: red; }
    </style>
    <div class="wrap">
        <h1><?php echo esc_html__( 'Lottery Entry', 'custom-lottery' ); ?></h1>
        <?php custom_lottery_render_entry_form(); ?>
    </div>
    <?php
}

/**
 * Callback function for the Reports page.
 */
function custom_lottery_reports_page_callback() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'lotto_entries';
    $table_agents = $wpdb->prefix . 'lotto_agents';

    $timezone = new DateTimeZone('Asia/Yangon');
    $current_datetime = new DateTime('now', $timezone);
    $default_date = $current_datetime->format('Y-m-d');

    $default_session = custom_lottery_get_current_session() ?? '12:01 PM';

    $selected_date = isset($_GET['report_date']) ? sanitize_text_field($_GET['report_date']) : $default_date;
    $selected_session = isset($_GET['draw_session']) ? sanitize_text_field($_GET['draw_session']) : $default_session;

    $start_datetime = $selected_date . ' 00:00:00';
    $end_datetime = $selected_date . ' 23:59:59';

    // Base WHERE clauses for agent segregation
    $where_clauses = [];
    $query_params = [];
    $current_user = wp_get_current_user();
    if (in_array('commission_agent', (array) $current_user->roles)) {
        $agent_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_agents WHERE user_id = %d", $current_user->ID));
        if ($agent_id) {
            $where_clauses[] = "agent_id = %d";
            $query_params[] = $agent_id;
        } else {
            $where_clauses[] = "1=0"; // See no data
        }
    }

    // Build query for total sales
    $total_sales_where = array_merge($where_clauses, ["draw_session = %s", "timestamp BETWEEN %s AND %s"]);
    $total_sales_params = array_merge($query_params, [$selected_session, $start_datetime, $end_datetime]);
    $total_sales_sql = "SELECT SUM(amount) FROM $table_name WHERE " . implode(' AND ', $total_sales_where);
    $total_sales = $wpdb->get_var($wpdb->prepare($total_sales_sql, $total_sales_params));
    $total_sales = $total_sales ? $total_sales : 0;

    $payout_rate = get_option('custom_lottery_payout_rate', 80);

    // Build query for results grouped by number
    $results_where = array_merge($where_clauses, ["draw_session = %s", "timestamp BETWEEN %s AND %s"]);
    $results_params = array_merge($query_params, [$selected_session, $start_datetime, $end_datetime]);
    $results_sql = "SELECT lottery_number, SUM(amount) as total_amount FROM $table_name WHERE " . implode(' AND ', $results_where) . " GROUP BY lottery_number ORDER BY lottery_number ASC";
    $results = $wpdb->get_results($wpdb->prepare($results_sql, $results_params));

    // Fetch winning number to calculate profit/loss
    $table_winning_numbers = $wpdb->prefix . 'lotto_winning_numbers';
    $winning_number = $wpdb->get_var($wpdb->prepare(
        "SELECT winning_number FROM $table_winning_numbers WHERE draw_date = %s AND draw_session = %s",
        $selected_date, $selected_session
    ));

    $actual_payout = 0;
    if ($winning_number) {
        $payout_where = array_merge($where_clauses, ["lottery_number = %s", "draw_session = %s", "timestamp BETWEEN %s AND %s"]);
        $payout_params = array_merge($query_params, [$winning_number, $selected_session, $start_datetime, $end_datetime]);
        $payout_sql = "SELECT SUM(amount) FROM $table_name WHERE " . implode(' AND ', $payout_where);
        $winning_number_total_amount = $wpdb->get_var($wpdb->prepare($payout_sql, $payout_params));

        if ($winning_number_total_amount) {
            $actual_payout = $winning_number_total_amount * $payout_rate;
        }
    }
    $net_profit_loss = $total_sales - $actual_payout;

    ?>
    <style>
        .highlight-risk { color: red; font-weight: bold; }
        .profit { color: green; }
        .loss { color: red; }
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

        <div style="background: #fff; padding: 15px; margin-top: 15px; border: 1px solid #c3c4c7;">
            <h3><?php printf(esc_html__('Total Sales: %s Kyat', 'custom-lottery'), '<strong>' . number_format($total_sales, 2) . '</strong>'); ?></h3>
            <?php if ($winning_number): ?>
                <p><?php printf(esc_html__('Winning Number for this session: %s', 'custom-lottery'), '<strong>' . esc_html($winning_number) . '</strong>'); ?></p>
                <h3><?php printf(esc_html__('Actual Payout: %s Kyat', 'custom-lottery'), '<strong>' . number_format($actual_payout, 2) . '</strong>'); ?></h3>
                <h3 class="<?php echo $net_profit_loss >= 0 ? 'profit' : 'loss'; ?>">
                    <?php
                    if ($net_profit_loss >= 0) {
                        printf(esc_html__('Net Profit: %s Kyat', 'custom-lottery'), '<strong>' . number_format($net_profit_loss, 2) . '</strong>');
                    } else {
                        printf(esc_html__('Net Loss: %s Kyat', 'custom-lottery'), '<strong>' . number_format(abs($net_profit_loss), 2) . '</strong>');
                    }
                    ?>
                </h3>
            <?php else: ?>
                <p><em><?php echo esc_html__('Winning number not yet drawn. Profit/Loss will be calculated once the result is available.', 'custom-lottery'); ?></em></p>
            <?php endif; ?>
        </div>

        <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Lottery Number', 'custom-lottery'); ?></th>
                    <th><?php echo esc_html__('Total Amount Purchased (Kyat)', 'custom-lottery'); ?></th>
                    <th><?php printf(esc_html__('Potential Payout (x%d) (Kyat)', 'custom-lottery'), $payout_rate); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($results) : foreach ($results as $row) :
                    $potential_payout = $row->total_amount * $payout_rate;
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

    // Cover Agent Management UI
    if (get_option('custom_lottery_enable_cover_agent_system') && current_user_can('manage_options')) {
        $table_cover_requests = $wpdb->prefix . 'lotto_cover_requests';
        $table_agents = $wpdb->prefix . 'lotto_agents';

        // Fetch pending and assigned cover requests for the selected session
        $cover_requests = $wpdb->get_results($wpdb->prepare(
            "SELECT cr.*, u.display_name as assigned_agent_name
             FROM $table_cover_requests cr
             LEFT JOIN $table_agents a ON cr.cover_agent_id = a.id
             LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
             WHERE cr.draw_date = %s AND cr.draw_session = %s
             ORDER BY cr.status, cr.lottery_number ASC",
            $selected_date, $selected_session
        ));

        // Fetch active cover agents for the dropdown
        $active_cover_agents = $wpdb->get_results($wpdb->prepare(
            "SELECT a.id, u.display_name FROM $table_agents a
             JOIN {$wpdb->users} u ON a.user_id = u.ID
             WHERE a.agent_type = %s AND a.status = %s",
            'cover', 'active'
        ));
        ?>
        <div class="wrap" id="cover-requests-section" style="margin-top: 40px;">
            <h2><?php echo esc_html__('Cover Requests Management', 'custom-lottery'); ?></h2>
            <button id="copy-pending-requests" class="button"><?php echo esc_html__('Copy Pending to Clipboard', 'custom-lottery'); ?></button>
            <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Lottery Number', 'custom-lottery'); ?></th>
                        <th><?php echo esc_html__('Cover Amount (Kyat)', 'custom-lottery'); ?></th>
                        <th><?php echo esc_html__('Status', 'custom-lottery'); ?></th>
                        <th><?php echo esc_html__('Assigned To / Action', 'custom-lottery'); ?></th>
                    </tr>
                </thead>
                <tbody id="cover-requests-body">
                    <?php if ($cover_requests) : foreach ($cover_requests as $request) : ?>
                        <tr data-request-id="<?php echo esc_attr($request->id); ?>">
                            <td><?php echo esc_html($request->lottery_number); ?></td>
                            <td><?php echo number_format($request->amount, 2); ?></td>
                            <td class="request-status"><?php echo esc_html(ucfirst($request->status)); ?></td>
                            <td class="request-action">
                                <?php if ($request->status === 'pending') : ?>
                                    <select name="cover_agent_id_<?php echo esc_attr($request->id); ?>" class="cover-agent-dropdown">
                                        <option value=""><?php echo esc_html__('Select Cover Agent', 'custom-lottery'); ?></option>
                                        <?php foreach ($active_cover_agents as $agent) : ?>
                                            <option value="<?php echo esc_attr($agent->id); ?>"><?php echo esc_html($agent->display_name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="button button-primary assign-cover-agent" data-request-id="<?php echo esc_attr($request->id); ?>"><?php echo esc_html__('Assign', 'custom-lottery'); ?></button>
                                <?php elseif ($request->status === 'assigned') : ?>
                                    <span><?php echo esc_html($request->assigned_agent_name); ?></span>
                                    <button class="button confirm-cover" data-request-id="<?php echo esc_attr($request->id); ?>"><?php echo esc_html__('Confirm Cover', 'custom-lottery'); ?></button>
                                <?php else : ?>
                                    <span><?php echo esc_html__('Confirmed', 'custom-lottery'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="4"><?php echo esc_html__('No cover requests for this session.', 'custom-lottery'); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
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

    $default_session = custom_lottery_get_current_session() ?? '12:01 PM';

    $selected_date = isset($_GET['payout_date']) ? sanitize_text_field($_GET['payout_date']) : $default_date;
    $selected_session = isset($_GET['draw_session']) ? sanitize_text_field($_GET['draw_session']) : $default_session;

    // Fetch the winning number from the dedicated table
    $table_winning_numbers = $wpdb->prefix . 'lotto_winning_numbers';
    $winning_number_obj = $wpdb->get_row($wpdb->prepare(
        "SELECT winning_number FROM $table_winning_numbers WHERE draw_date = %s AND draw_session = %s",
        $selected_date, $selected_session
    ));
    $winning_number = $winning_number_obj ? $winning_number_obj->winning_number : null;

    // If a winning number is found, ensure all matching entries are flagged as winners.
    if ($winning_number) {
        custom_lottery_identify_winners($selected_session, $winning_number, $selected_date);
    }

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

        <?php if ($winning_number) : ?>
            <p><strong><?php printf(esc_html__('Winning Number: %s', 'custom-lottery'), esc_html($winning_number)); ?></strong></p>
        <?php endif; ?>

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
                <?php if ($winning_number) : ?>
                    <?php if ($winners) :
                        $payout_rate = get_option('custom_lottery_payout_rate', 80);
                        foreach ($winners as $winner) :
                        $amount_won = $winner->amount * $payout_rate;
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
                <?php else : ?>
                    <tr><td colspan="6"><?php echo esc_html__('No winning number has been recorded for this session yet.', 'custom-lottery'); ?></td></tr>
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
