<?php

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Lotto_Mod_Requests_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => __('Modification Request', 'custom-lottery'),
            'plural'   => __('Modification Requests', 'custom-lottery'),
            'ajax'     => false
        ]);
    }

    public function get_columns() {
        return [
            'entry_details' => __('Entry Details', 'custom-lottery'),
            'agent_name'    => __('Agent', 'custom-lottery'),
            'request_notes' => __('Request Notes', 'custom-lottery'),
            'status'        => __('Status', 'custom-lottery'),
            'requested_at'  => __('Date Requested', 'custom-lottery'),
        ];
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'agent_name':
            case 'request_notes':
            case 'status':
            case 'requested_at':
                return esc_html($item[$column_name]);
            default:
                return print_r($item, true);
        }
    }

    protected function extra_tablenav($which) {
        if ($which == "top") {
            global $wpdb;
            $table_agents = $wpdb->prefix . 'lotto_agents';
            $agents = $wpdb->get_results("SELECT a.id, u.display_name FROM $table_agents a JOIN {$wpdb->users} u ON a.user_id = u.ID WHERE a.agent_type = 'commission' ORDER BY u.display_name ASC");

            $filter_agent_id = isset($_GET['filter_agent_id']) ? absint($_GET['filter_agent_id']) : '';
            $filter_session = isset($_GET['filter_session']) ? sanitize_text_field($_GET['filter_session']) : '';
            $filter_date = isset($_GET['filter_date']) ? sanitize_text_field($_GET['filter_date']) : '';
            ?>
            <div class="alignleft actions">
                <select name="filter_agent_id">
                    <option value=""><?php _e('All Agents', 'custom-lottery'); ?></option>
                    <?php foreach ($agents as $agent) : ?>
                        <option value="<?php echo esc_attr($agent->id); ?>" <?php selected($filter_agent_id, $agent->id); ?>><?php echo esc_html($agent->display_name); ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="filter_session">
                    <option value=""><?php _e('All Sessions', 'custom-lottery'); ?></option>
                    <option value="12:01 PM" <?php selected($filter_session, '12:01 PM'); ?>>12:01 PM</option>
                    <option value="4:30 PM" <?php selected($filter_session, '4:30 PM'); ?>>4:30 PM</option>
                </select>

                <input type="date" name="filter_date" value="<?php echo esc_attr($filter_date); ?>" />

                <?php submit_button(__('Filter'), 'action', 'filter_action', false, ['id' => 'post-query-submit']); ?>
            </div>
            <?php
        }
    }

    function column_entry_details($item) {
        // Main container for flexbox layout
        $output = '<div class="entry-details-container">';

        // Div for the textual details
        $output .= '<div class="details-text">';
        $output .= sprintf(
            '<strong>Customer:</strong> %s (%s)<br>',
            esc_html($item['customer_name']),
            esc_html($item['phone'])
        );
        $output .= '<ul>';
        $output .= sprintf(
            '<li><strong>Original:</strong> %s - %s Kyat</li>',
            esc_html($item['original_number']),
            number_format($item['original_amount'], 2)
        );
        $output .= sprintf(
            '<li style="color: #2271b1;"><strong>Proposed:</strong> %s - %s Kyat</li>',
            esc_html($item['new_lottery_number']),
            number_format($item['new_amount'], 2)
        );
        $output .= '</ul>';
        $output .= '</div>'; // End .details-text

        // Div for the action buttons
        if ($item['status'] === 'pending') {
            $approve_nonce = wp_create_nonce('mod_request_approve_' . $item['id']);
            $reject_nonce = wp_create_nonce('mod_request_reject_' . $item['id']);

            $output .= '<div class="details-actions">';
            $output .= sprintf(
                '<a href="#" class="button button-primary approve-mod-request" data-request-id="%d" data-nonce="%s">%s</a>',
                $item['id'],
                $approve_nonce,
                __('Approve', 'custom-lottery')
            );
            $output .= sprintf(
                '<a href="#" class="button button-secondary reject-mod-request" data-request-id="%d" data-nonce="%s" style="margin-left: 5px;">%s</a>',
                $item['id'],
                $reject_nonce,
                __('Reject', 'custom-lottery')
            );
            $output .= '</div>'; // End .details-actions
        }

        $output .= '</div>'; // End .entry-details-container

        return $output;
    }

    public function prepare_items() {
        global $wpdb;
        $table_requests = $wpdb->prefix . 'lotto_modification_requests';
        $table_entries = $wpdb->prefix . 'lotto_entries';
        $table_agents = $wpdb->prefix . 'lotto_agents';
        $table_users = $wpdb->users;

        $per_page = 20;
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = [];
        $this->_column_headers = [$columns, $hidden, $sortable];

        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        // Filtering logic
        $where_clauses = [];
        $params = [];
        if (!empty($_GET['filter_agent_id'])) {
            $where_clauses[] = "r.agent_id = %d";
            $params[] = absint($_GET['filter_agent_id']);
        }
        if (!empty($_GET['filter_session'])) {
            $where_clauses[] = "e.draw_session = %s";
            $params[] = sanitize_text_field($_GET['filter_session']);
        }
        if (!empty($_GET['filter_date'])) {
            $date = sanitize_text_field($_GET['filter_date']);
            $where_clauses[] = "DATE(r.requested_at) = %s";
            $params[] = $date;
        }
        $where_sql = !empty($where_clauses) ? "WHERE " . implode(' AND ', $where_clauses) : '';

        $total_items = $wpdb->get_var($wpdb->prepare("SELECT COUNT(r.id) FROM $table_requests r LEFT JOIN $table_entries e ON r.entry_id = e.id $where_sql", $params));

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page
        ]);

        $query = "SELECT
                    r.id, r.entry_id, r.request_notes, r.status, r.requested_at,
                    r.new_lottery_number, r.new_amount,
                    e.customer_name, e.phone,
                    e.lottery_number as original_number, e.amount as original_amount,
                    u.display_name as agent_name
                 FROM $table_requests r
                 JOIN $table_entries e ON r.entry_id = e.id
                 JOIN $table_agents a ON r.agent_id = a.id
                 JOIN $table_users u ON a.user_id = u.ID
                 $where_sql
                 ORDER BY r.requested_at DESC
                 LIMIT %d OFFSET %d";

        $query_params = array_merge($params, [$per_page, $offset]);
        $this->items = $wpdb->get_results($wpdb->prepare($query, $query_params), ARRAY_A);
    }
}