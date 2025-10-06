<?php

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Lotto_Entries_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => __('Lottery Entry', 'custom-lottery'),
            'plural'   => __('Lottery Entries', 'custom-lottery'),
            'ajax'     => false
        ]);
    }

    public function get_columns() {
        return [
            'cb'            => '<input type="checkbox" />',
            'customer_name' => __('Customer Name', 'custom-lottery'),
            'phone'         => __('Phone', 'custom-lottery'),
            'lottery_number'=> __('Number', 'custom-lottery'),
            'amount'        => __('Amount', 'custom-lottery'),
            'draw_session'  => __('Session', 'custom-lottery'),
            'timestamp'     => __('Date', 'custom-lottery'),
            'is_winner'     => __('Winner', 'custom-lottery'),
            'paid_status'   => __('Paid', 'custom-lottery'),
        ];
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'amount':
                return number_format($item[$column_name], 2);
            case 'is_winner':
                return $item[$column_name] ? 'Yes' : 'No';
            case 'paid_status':
                return $item[$column_name] ? 'Yes' : 'No';
            default:
                return $item[$column_name];
        }
    }

    function column_customer_name($item) {
        $delete_nonce = wp_create_nonce('cl_delete_entry');
        $actions = [
            'edit' => sprintf('<a href="?page=%s&action=%s&entry_id=%s">Edit</a>', 'custom-lottery-all-entries', 'edit', $item['id']),
            'delete' => sprintf('<a href="?page=%s&action=%s&entry_id=%s&_wpnonce=%s">Delete</a>', 'custom-lottery-all-entries', 'delete', $item['id'], $delete_nonce),
        ];
        return sprintf('%1$s %2$s', $item['customer_name'], $this->row_actions($actions));
    }

    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="entry[]" value="%s" />', $item['id']);
    }

    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lotto_entries';
        $per_page = 20;

        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];

        $orderby = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'timestamp';
        $order = isset($_GET['order']) ? sanitize_key($_GET['order']) : 'desc';

        $current_page = $this->get_pagenum();
        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page
        ]);

        $offset = ($current_page - 1) * $per_page;
        $this->items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ), ARRAY_A
        );
    }

    public function get_sortable_columns() {
        return [
            'customer_name' => ['customer_name', false],
            'lottery_number' => ['lottery_number', false],
            'amount' => ['amount', false],
            'timestamp' => ['timestamp', true],
        ];
    }
}