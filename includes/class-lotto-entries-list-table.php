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

        // Get filter values
        $timezone = new DateTimeZone('Asia/Yangon');
        $current_time = new DateTime('now', $timezone);
        $default_date = $current_time->format('Y-m-d');

        $time_1201 = new DateTime($current_time->format('Y-m-d') . ' 12:01:00', $timezone);
        $time_1630 = new DateTime($current_time->format('Y-m-d') . ' 16:30:00', $timezone);
        $default_session = '12:01 PM';
        if ($current_time > $time_1201 && $current_time <= $time_1630) {
            $default_session = '4:30 PM';
        }

        $filter_date = isset($_GET['filter_date']) && !empty($_GET['filter_date']) ? sanitize_text_field($_GET['filter_date']) : $default_date;
        $filter_session = isset($_GET['filter_session']) ? sanitize_text_field($_GET['filter_session']) : $default_session;

        $where_clauses = [];
        $query_params = [];

        // Date filter
        $start_datetime = $filter_date . ' 00:00:00';
        $end_datetime = $filter_date . ' 23:59:59';
        $where_clauses[] = "timestamp BETWEEN %s AND %s";
        $query_params[] = $start_datetime;
        $query_params[] = $end_datetime;

        // Session filter
        if ($filter_session !== 'all') {
            $where_clauses[] = "draw_session = %s";
            $query_params[] = $filter_session;
        }

        $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

        $current_page = $this->get_pagenum();

        // Get total items for pagination
        $total_items_query = "SELECT COUNT(id) FROM $table_name $where_sql";
        $total_items = $wpdb->get_var($wpdb->prepare($total_items_query, $query_params));

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page
        ]);

        $offset = ($current_page - 1) * $per_page;
        $query_params[] = $per_page;
        $query_params[] = $offset;

        $items_query = "SELECT * FROM $table_name $where_sql ORDER BY $orderby $order LIMIT %d OFFSET %d";
        $this->items = $wpdb->get_results(
            $wpdb->prepare($items_query, $query_params),
            ARRAY_A
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