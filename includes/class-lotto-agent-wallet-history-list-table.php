<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Lotto_Agent_Wallet_History_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( [
            'singular' => __( 'Payout', 'custom-lottery' ),
            'plural'   => __( 'Payouts', 'custom-lottery' ),
            'ajax'     => false
        ] );
    }

    public function get_columns() {
        $columns = [
            'timestamp'  => __( 'Date', 'custom-lottery' ),
            'amount'     => __( 'Payout Amount (Kyat)', 'custom-lottery' ),
            'payout_method' => __( 'Payout Method', 'custom-lottery' ),
            'proof'      => __( 'Proof', 'custom-lottery' ),
            'notes'      => __( 'Notes', 'custom-lottery' ),
        ];
        return $columns;
    }

    public function prepare_items() {
        global $wpdb;

        $this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];

        $per_page     = $this->get_items_per_page( 'payouts_per_page', 20 );
        $current_page = $this->get_pagenum();

        // Get current agent's ID
        $current_user_id = get_current_user_id();
        $agent_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}lotto_agents WHERE user_id = %d", $current_user_id));

        if ( ! $agent_id ) {
            $this->items = [];
            return;
        }

        $table_transactions = $wpdb->prefix . 'lotto_agent_transactions';

        $where_clauses = [
            $wpdb->prepare("agent_id = %d", $agent_id),
            $wpdb->prepare("type = %s", 'payout')
        ];

        // Date filtering
        if ( ! empty($_GET['start_date']) ) {
            $start_date = sanitize_text_field($_GET['start_date']) . ' 00:00:00';
            $where_clauses[] = $wpdb->prepare("timestamp >= %s", $start_date);
        }
        if ( ! empty($_GET['end_date']) ) {
            $end_date = sanitize_text_field($_GET['end_date']) . ' 23:59:59';
            $where_clauses[] = $wpdb->prepare("timestamp <= %s", $end_date);
        }

        $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

        $total_items  = $wpdb->get_var("SELECT COUNT(id) FROM $table_transactions $where_sql");

        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page
        ] );

        $orderby = isset($_REQUEST['orderby']) ? sanitize_sql_orderby($_REQUEST['orderby']) : 'timestamp';
        $order = isset($_REQUEST['order']) ? strtoupper(sanitize_key($_REQUEST['order'])) : 'DESC';
        if (!in_array($orderby, ['amount', 'timestamp', 'payout_method'])) {
            $orderby = 'timestamp';
        }
        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'DESC';
        }

        $offset = ( $current_page - 1 ) * $per_page;

        $query = "SELECT * FROM $table_transactions $where_sql ORDER BY $orderby $order LIMIT %d OFFSET %d";

        $this->items = $wpdb->get_results( $wpdb->prepare($query, $per_page, $offset), ARRAY_A );
    }

    protected function get_sortable_columns() {
        $sortable_columns = array(
            'amount'  => array('amount', false),
            'timestamp' => array('timestamp', true),
            'payout_method' => array('payout_method', false),
        );
        return $sortable_columns;
    }

    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'amount':
                return '<span style="color: red;">' . number_format(abs($item['amount']), 2) . '</span>';
            case 'payout_method':
                return esc_html($item['payout_method']);
            case 'proof':
                if ( ! empty($item['proof_attachment']) ) {
                    return sprintf('<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url($item['proof_attachment']), __('View Proof', 'custom-lottery'));
                }
                return 'N/A';
            case 'notes':
                return esc_html($item['notes']);
            case 'timestamp':
                return date('Y-m-d H:i:s', strtotime($item['timestamp']));
            default:
                return print_r( $item, true );
        }
    }
}
