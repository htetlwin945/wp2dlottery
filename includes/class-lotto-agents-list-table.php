<?php

if ( ! defined( 'WPINC' ) ) {
    die;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Lotto_Agents_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( [
            'singular' => __( 'Agent', 'custom-lottery' ),
            'plural'   => __( 'Agents', 'custom-lottery' ),
            'ajax'     => false
        ] );
    }

    public function get_columns() {
        $columns = [
            'cb'         => '<input type="checkbox" />',
            'user_id'    => __( 'User', 'custom-lottery' ),
            'agent_type' => __( 'Type', 'custom-lottery' ),
            'status'     => __( 'Status', 'custom-lottery' ),
            'created_at' => __( 'Date Created', 'custom-lottery' )
        ];
        return $columns;
    }

    public function get_sortable_columns() {
        $sortable_columns = array(
            'user_id'    => array('user_id', false),
            'agent_type' => array('agent_type', false),
            'status'     => array('status', false),
            'created_at' => array('created_at', true)
        );
        return $sortable_columns;
    }

    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lotto_agents';

        $per_page = 20;
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];

        $orderby = ( ! empty( $_GET['orderby'] ) ) ? esc_sql( $_GET['orderby'] ) : 'created_at';
        $order = ( ! empty( $_GET['order'] ) ) ? esc_sql( $_GET['order'] ) : 'DESC';

        $current_page = $this->get_pagenum();
        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page
        ] );

        $offset = ( $current_page - 1 ) * $per_page;
        $this->items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ), ARRAY_A
        );
    }

    protected function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="agent[]" value="%s" />', $item['id']
        );
    }

    protected function column_user_id( $item ) {
        $user = get_userdata( $item['user_id'] );
        if ( ! $user ) {
            return __( 'Unknown User', 'custom-lottery' );
        }
        $delete_nonce = wp_create_nonce( 'cl_delete_agent_' . $item['id'] );
        $actions = [
            'edit' => sprintf( '<a href="?page=%s&action=%s&agent_id=%s">' . __( 'Edit', 'custom-lottery' ) . '</a>', esc_attr( $_REQUEST['page'] ), 'edit', absint( $item['id'] ) ),
            'delete' => sprintf( '<a href="?page=%s&action=delete&agent_id=%s&_wpnonce=%s">' . __( 'Delete', 'custom-lottery' ) . '</a>', esc_attr( $_REQUEST['page'] ), absint( $item['id'] ), $delete_nonce )
        ];

        return '<strong>' . esc_html( $user->display_name ) . '</strong> (' . esc_html($user->user_email) . ')' . $this->row_actions( $actions );
    }

    protected function column_agent_type( $item ) {
        return ucfirst( $item['agent_type'] );
    }

    protected function column_status( $item ) {
        return ucfirst( $item['status'] );
    }

    protected function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'created_at':
                return $item[ $column_name ];
            default:
                return print_r( $item, true ); //Show the whole array for troubleshooting purposes
        }
    }
}