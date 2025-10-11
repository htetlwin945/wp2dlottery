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
        $columns = [
            'requested_at'  => __( 'Date Requested', 'custom-lottery' ),
            'amount'        => __( 'Amount Requested', 'custom-lottery' ),
            'status'        => __( 'Status', 'custom-lottery' ),
            'resolved_at'   => __( 'Date Processed', 'custom-lottery' ),
            'admin_notes'   => __( 'Admin Notes', 'custom-lottery' ),
            'actions'       => __( 'Actions', 'custom-lottery' ),
        ];
        return $columns;
    }

    public function prepare_items() {
        global $wpdb;

        $this->_column_headers = [ $this->get_columns(), [], [] ];

        $current_user_id = get_current_user_id();
        $agent_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}lotto_agents WHERE user_id = %d", $current_user_id));

        if (!$agent_id) {
            $this->items = [];
            return;
        }

        $this->items = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}lotto_payout_requests WHERE agent_id = %d ORDER BY requested_at DESC",
            $agent_id
        ), ARRAY_A );
    }

    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'requested_at':
                return date('Y-m-d H:i', strtotime($item['requested_at']));
            case 'amount':
                return number_format($item['amount'], 2) . ' Kyat';
            case 'status':
                return ucfirst(str_replace('_', ' ', esc_html($item['status'])));
            case 'resolved_at':
                return $item['resolved_at'] ? date('Y-m-d H:i', strtotime($item['resolved_at'])) : 'N/A';
            case 'admin_notes':
                return esc_html($item['admin_notes']);
            default:
                return '';
        }
    }

    public function column_actions( $item ) {
        if ($item['status'] === 'pending') {
            $nonce = wp_create_nonce('agent_cancel_payout_request_' . $item['id']);
            return sprintf(
                '<a href="#" class="cancel-payout-request" data-request-id="%s" data-nonce="%s">%s</a>',
                esc_attr($item['id']),
                esc_attr($nonce),
                __('Cancel Request', 'custom-lottery')
            );
        }
        return 'N/A';
    }

    protected function no_items() {
        _e( 'You have not made any payout requests.', 'custom-lottery' );
    }
}
