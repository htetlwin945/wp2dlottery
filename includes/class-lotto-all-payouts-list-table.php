<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Lotto_All_Payouts_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( [
            'singular' => __( 'Payout Transaction', 'custom-lottery' ),
            'plural'   => __( 'Payout Transactions', 'custom-lottery' ),
            'ajax'     => false
        ] );
    }

    public function get_columns() {
        $columns = [
            'agent_name' => __( 'Agent Name', 'custom-lottery' ),
            'timestamp'  => __( 'Date', 'custom-lottery' ),
            'amount'     => __( 'Payout Amount (Kyat)', 'custom-lottery' ),
            'payout_method' => __( 'Payout Method', 'custom-lottery' ),
            'proof'      => __( 'Proof', 'custom-lottery' ),
            'notes'      => __( 'Admin Notes', 'custom-lottery' ),
        ];
        return $columns;
    }

    public function prepare_items() {
        global $wpdb;

        $this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];

        $per_page     = $this->get_items_per_page( 'all_payouts_per_page', 20 );
        $current_page = $this->get_pagenum();

        $table_transactions = $wpdb->prefix . 'lotto_agent_transactions';
        $table_agents = $wpdb->prefix . 'lotto_agents';
        $table_users = $wpdb->users;

        $where_sql = "WHERE t.type = 'payout'";

        $total_items  = $wpdb->get_var("SELECT COUNT(t.id) FROM $table_transactions t $where_sql");

        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page
        ] );

        $orderby = isset($_REQUEST['orderby']) ? sanitize_sql_orderby($_REQUEST['orderby']) : 'timestamp';
        $order = isset($_REQUEST['order']) ? strtoupper(sanitize_key($_REQUEST['order'])) : 'DESC';
        if (!in_array($orderby, ['agent_name', 'timestamp', 'amount', 'payout_method'])) {
            $orderby = 'timestamp';
        }
        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'DESC';
        }

        $offset = ( $current_page - 1 ) * $per_page;

        $query = "SELECT t.*, u.display_name as agent_name
                  FROM $table_transactions t
                  JOIN $table_agents a ON t.agent_id = a.id
                  JOIN $table_users u ON a.user_id = u.ID
                  $where_sql
                  ORDER BY $orderby $order
                  LIMIT %d OFFSET %d";

        $this->items = $wpdb->get_results( $wpdb->prepare($query, $per_page, $offset), ARRAY_A );
    }

    protected function get_sortable_columns() {
        $sortable_columns = array(
            'agent_name' => array('agent_name', false),
            'amount'  => array('amount', false),
            'timestamp' => array('timestamp', true),
            'payout_method' => array('payout_method', false),
        );
        return $sortable_columns;
    }

    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'agent_name':
                return '<strong>' . esc_html($item['agent_name']) . '</strong>';
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