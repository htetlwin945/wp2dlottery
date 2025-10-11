<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Lotto_Payouts_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( [
            'singular' => __( 'Transaction', 'custom-lottery' ),
            'plural'   => __( 'Transactions', 'custom-lottery' ),
            'ajax'     => false
        ] );
    }

    public function get_columns() {
        $columns = [
            'agent_name' => __( 'Agent Name', 'custom-lottery' ),
            'type'       => __( 'Type', 'custom-lottery' ),
            'amount'     => __( 'Amount (Kyat)', 'custom-lottery' ),
            'notes'      => __( 'Notes', 'custom-lottery' ),
            'timestamp'  => __( 'Date', 'custom-lottery' ),
        ];
        return $columns;
    }

    public function prepare_items( $agent_id = null ) {
        global $wpdb;

        $this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];

        $per_page     = $this->get_items_per_page( 'transactions_per_page', 20 );
        $current_page = $this->get_pagenum();

        $table_transactions = $wpdb->prefix . 'lotto_agent_transactions';
        $table_agents = $wpdb->prefix . 'lotto_agents';
        $table_users = $wpdb->users;

        $where_sql = '';
        $query_params = [];
        if ( $agent_id ) {
            $where_sql = "WHERE t.agent_id = %d";
            $query_params[] = $agent_id;
        }

        $total_items  = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(t.id) FROM $table_transactions t $where_sql", $query_params) );

        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page
        ] );

        $orderby = isset($_REQUEST['orderby']) ? sanitize_sql_orderby($_REQUEST['orderby']) : 'timestamp';
        $order = isset($_REQUEST['order']) ? strtoupper(sanitize_key($_REQUEST['order'])) : 'DESC';
        if (!in_array($orderby, ['agent_name', 'type', 'amount', 'timestamp'])) {
            $orderby = 'timestamp';
        }
        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'DESC';
        }

        $offset = ( $current_page - 1 ) * $per_page;
        $query_params[] = $per_page;
        $query_params[] = $offset;

        $query = "SELECT t.*, u.display_name as agent_name
             FROM $table_transactions t
             JOIN $table_agents a ON t.agent_id = a.id
             JOIN $table_users u ON a.user_id = u.ID
             $where_sql
             ORDER BY $orderby $order
             LIMIT %d OFFSET %d";

        $prepared_query = call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $query ), $query_params ) );

        $this->items = $wpdb->get_results( $prepared_query, ARRAY_A );
    }

    protected function get_sortable_columns() {
        $sortable_columns = array(
            'agent_name' => array('agent_name', false),
            'type'    => array('type', false),
            'amount'  => array('amount', false),
            'timestamp' => array('timestamp', true),
        );
        return $sortable_columns;
    }

    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'agent_name':
                return '<strong>' . esc_html($item['agent_name']) . '</strong>';
            case 'type':
                return ucfirst(esc_html($item['type']));
            case 'amount':
                $amount = (float) $item['amount'];
                if ($item['type'] === 'payout') {
                    // Payouts are stored as negative, so we show them as positive red numbers.
                    return '<span style="color: red;">' . number_format(abs($amount), 2) . '</span>';
                } else {
                     return '<span style="color: green;">' . number_format($amount, 2) . '</span>';
                }
            case 'notes':
                return esc_html($item['notes']);
            case 'timestamp':
                return date('Y-m-d H:i:s', strtotime($item['timestamp']));
            default:
                return print_r( $item, true ); //Show the whole array for troubleshooting
        }
    }
}
