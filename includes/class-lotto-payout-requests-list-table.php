<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Lotto_Payout_Requests_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( [
            'singular' => __( 'Payout Request', 'custom-lottery' ),
            'plural'   => __( 'Payout Requests', 'custom-lottery' ),
            'ajax'     => false
        ] );
    }

    public function get_columns() {
        $columns = [
            'agent_name'    => __( 'Agent Name', 'custom-lottery' ),
            'amount'        => __( 'Amount Requested', 'custom-lottery' ),
            'final_amount'  => __( 'Amount Paid', 'custom-lottery' ),
            'status'        => __( 'Status', 'custom-lottery' ),
            'requested_at'  => __( 'Date Requested', 'custom-lottery' ),
            'resolved_at'   => __( 'Date Processed', 'custom-lottery' ),
            'notes'         => __( 'Agent Notes', 'custom-lottery' ),
            'admin_notes'   => __( 'Admin Notes', 'custom-lottery' ),
            'actions'       => __( 'Actions', 'custom-lottery' ),
        ];
        return $columns;
    }

    public function prepare_items() {
        global $wpdb;

        $this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];

        $per_page     = $this->get_items_per_page( 'payout_requests_per_page', 20 );
        $current_page = $this->get_pagenum();

        $table_requests = $wpdb->prefix . 'lotto_payout_requests';
        $table_agents = $wpdb->prefix . 'lotto_agents';
        $table_users = $wpdb->users;

        $total_items  = $wpdb->get_var("SELECT COUNT(id) FROM $table_requests");

        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page
        ] );

        $orderby = isset($_REQUEST['orderby']) ? sanitize_sql_orderby($_REQUEST['orderby']) : 'requested_at';
        $order = isset($_REQUEST['order']) ? strtoupper(sanitize_key($_REQUEST['order'])) : 'DESC';
        if (!in_array($orderby, ['amount', 'status', 'requested_at'])) {
            $orderby = 'requested_at';
        }
        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'DESC';
        }

        $offset = ( $current_page - 1 ) * $per_page;

        $this->items = $wpdb->get_results( $wpdb->prepare(
            "SELECT pr.*, u.display_name as agent_name
             FROM $table_requests pr
             JOIN $table_agents a ON pr.agent_id = a.id
             JOIN $table_users u ON a.user_id = u.ID
             ORDER BY $orderby $order
             LIMIT %d OFFSET %d",
            $per_page, $offset
        ), ARRAY_A );
    }

    protected function get_sortable_columns() {
        return [
            'amount'       => ['amount', false],
            'status'       => ['status', false],
            'requested_at' => ['requested_at', true],
        ];
    }

    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'agent_name':
                return '<strong>' . esc_html($item['agent_name']) . '</strong>';
            case 'amount':
                return number_format($item['amount'], 2) . ' Kyat';
            case 'final_amount':
                return $item['final_amount'] ? number_format($item['final_amount'], 2) . ' Kyat' : 'N/A';
            case 'status':
                $status_text = ucfirst(esc_html($item['status']));
                if ($item['status'] === 'approved' && $item['final_amount'] < $item['amount']) {
                    $status_text = __('Partially Paid', 'custom-lottery');
                }
                return $status_text;
            case 'notes':
                return esc_html($item['notes']);
            case 'admin_notes':
                return esc_html($item['admin_notes']);
            case 'requested_at':
                return date('Y-m-d H:i:s', strtotime($item['requested_at']));
            case 'resolved_at':
                return $item['resolved_at'] ? date('Y-m-d H:i:s', strtotime($item['resolved_at'])) : 'N/A';
            default:
                return print_r( $item, true );
        }
    }

    public function column_actions( $item ) {
        if ($item['status'] === 'pending') {
            $nonce = wp_create_nonce('manage_payout_request_nonce');
            $actions = sprintf(
                '<button class="button button-primary process-payout-button" data-request-id="%1$d" data-agent-id="%2$d" data-agent-name="%3$s" data-amount="%4$s" data-agent-notes="%5$s" data-nonce="%6$s">%7$s</button> ' .
                '<button class="button button-secondary reject-payout-button" data-request-id="%1$d" data-agent-name="%3$s" data-nonce="%6$s">%8$s</button>',
                esc_attr($item['id']),
                esc_attr($item['agent_id']),
                esc_attr($item['agent_name']),
                esc_attr($item['amount']),
                esc_attr($item['notes']),
                esc_attr($nonce),
                __('Process Payout', 'custom-lottery'),
                __('Reject', 'custom-lottery')
            );
            return $actions;
        }
        return 'N/A';
    }
}
