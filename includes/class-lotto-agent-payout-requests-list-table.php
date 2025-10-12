<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Lotto_Agent_Payout_Requests_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( [
            'singular' => __( 'Payout Request', 'custom-lottery' ),
            'plural'   => __( 'Payout Requests', 'custom-lottery' ),
            'ajax'     => false
        ] );
    }

    public function get_columns() {
        return [
            'requested_at'  => __( 'Date Requested', 'custom-lottery' ),
            'amount'        => __( 'Amount Requested', 'custom-lottery' ),
            'status'        => __( 'Status', 'custom-lottery' ),
            'resolved_at'   => __( 'Date Processed', 'custom-lottery' ),
            'admin_notes'   => __( 'Admin Notes', 'custom-lottery' ),
            'actions'       => __( 'Actions', 'custom-lottery' ),
        ];
    }

    public function prepare_items() {
        global $wpdb;

        $this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];
        $current_user_id = get_current_user_id();
        $agent_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}lotto_agents WHERE user_id = %d", $current_user_id));

        if (!$agent_id) {
            $this->items = [];
            return;
        }

        $table_requests = $wpdb->prefix . 'lotto_payout_requests';
        $where_sql = $wpdb->prepare("WHERE agent_id = %d", $agent_id);

        $total_items  = $wpdb->get_var("SELECT COUNT(id) FROM $table_requests $where_sql");

        $this->set_pagination_args([ 'total_items' => $total_items, 'per_page' => 20 ]);

        $this->items = $wpdb->get_results(
            "SELECT * FROM $table_requests $where_sql ORDER BY requested_at DESC",
            ARRAY_A
        );
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