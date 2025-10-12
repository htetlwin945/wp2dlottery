<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Lotto_Agent_Wallet_History_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( [
            'singular' => __( 'Payout History', 'custom-lottery' ),
            'plural'   => __( 'Payout History', 'custom-lottery' ),
            'ajax'     => false
        ] );
    }

    public function get_columns() {
        $columns = [
            'requested_at'  => __( 'Date Requested', 'custom-lottery' ),
            'amount'        => __( 'Amount', 'custom-lottery' ),
            'status'        => __( 'Status', 'custom-lottery' ),
            'resolved_at'   => __( 'Date Processed', 'custom-lottery' ),
            'admin_notes'   => __( 'Admin Notes', 'custom-lottery' ),
            'actions'       => __( 'Actions', 'custom-lottery' ),
        ];
        return $columns;
    }

    public function prepare_items() {
        global $wpdb;

        $this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];

        $per_page     = $this->get_items_per_page( 'payout_history_per_page', 20 );
        $current_page = $this->get_pagenum();

        $current_user_id = get_current_user_id();
        $agent_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}lotto_agents WHERE user_id = %d", $current_user_id));

        if ( ! $agent_id ) {
            $this->items = [];
            return;
        }

        $table_requests = $wpdb->prefix . 'lotto_payout_requests';
        $where_sql = $wpdb->prepare("WHERE agent_id = %d", $agent_id);

        $total_items  = $wpdb->get_var("SELECT COUNT(id) FROM $table_requests $where_sql");

        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page
        ] );

        $orderby = isset($_REQUEST['orderby']) ? sanitize_sql_orderby($_REQUEST['orderby']) : 'requested_at';
        $order = isset($_REQUEST['order']) ? strtoupper(sanitize_key($_REQUEST['order'])) : 'DESC';
        if (!in_array($orderby, ['requested_at', 'amount', 'status', 'resolved_at'])) {
            $orderby = 'requested_at';
        }
        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'DESC';
        }

        $offset = ( $current_page - 1 ) * $per_page;

        $query = "SELECT * FROM $table_requests $where_sql ORDER BY $orderby $order LIMIT %d OFFSET %d";

        $this->items = $wpdb->get_results( $wpdb->prepare($query, $per_page, $offset), ARRAY_A );
    }

    protected function get_sortable_columns() {
        return [
            'requested_at' => ['requested_at', true],
            'amount'       => ['amount', false],
            'status'       => ['status', false],
            'resolved_at'  => ['resolved_at', false],
        ];
    }

    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'amount':
                return number_format($item['amount'], 2) . ' Kyat';
            case 'status':
                return ucfirst(esc_html($item['status']));
            case 'admin_notes':
                return esc_html($item['admin_notes']);
            case 'requested_at':
                return date('Y-m-d H:i', strtotime($item['requested_at']));
            case 'resolved_at':
                return $item['resolved_at'] ? date('Y-m-d H:i', strtotime($item['resolved_at'])) : 'N/A';
            default:
                return '';
        }
    }

    public function column_actions( $item ) {
        if ($item['status'] === 'pending') {
            $nonce = wp_create_nonce('agent_cancel_payout_request_nonce');
            return sprintf(
                '<button class="button button-secondary agent-cancel-payout-request" data-request-id="%d" data-nonce="%s">%s</button>',
                esc_attr($item['id']),
                esc_attr($nonce),
                __('Cancel Request', 'custom-lottery')
            );
        }
        return 'N/A';
    }
}
