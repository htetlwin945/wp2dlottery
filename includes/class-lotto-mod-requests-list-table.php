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

        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_requests");

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page
        ]);

        $query = $wpdb->prepare(
            "SELECT
                r.id, r.entry_id, r.request_notes, r.status, r.requested_at,
                r.new_lottery_number, r.new_amount,
                e.customer_name, e.phone,
                e.lottery_number as original_number, e.amount as original_amount,
                u.display_name as agent_name
             FROM $table_requests r
             JOIN $table_entries e ON r.entry_id = e.id
             JOIN $table_agents a ON r.agent_id = a.id
             JOIN $table_users u ON a.user_id = u.ID
             ORDER BY r.requested_at DESC
             LIMIT %d OFFSET %d",
            $per_page,
            $offset
        );

        $this->items = $wpdb->get_results($query, ARRAY_A);
    }
}