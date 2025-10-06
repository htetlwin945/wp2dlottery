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
            'entries'       => __('Entries', 'custom-lottery'),
            'total_amount'  => __('Total Amount', 'custom-lottery'),
            'draw_session'  => __('Session', 'custom-lottery'),
            'timestamp'     => __('Date', 'custom-lottery'),
        ];
    }

    public function column_cb($item) {
        // The value of the checkbox will be the customer's phone number
        return sprintf('<input type="checkbox" name="customer_phone[]" value="%s" />', esc_attr($item['phone']));
    }

    protected function get_bulk_actions() {
        return [
            'bulk-delete' => __('Delete', 'custom-lottery'),
        ];
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'total_amount':
                return number_format($item['total_amount'], 2);
            case 'timestamp':
                return date('Y-m-d', strtotime($item['timestamp']));
            case 'phone':
            case 'draw_session':
            case 'customer_name':
                return $item[$column_name];
            default:
                return ''; // Should not happen
        }
    }

    function column_customer_name($item) {
        return $item['customer_name'];
    }

    function column_entries($item) {
        if (empty($item['entries'])) {
            return '';
        }

        $output = '<ul style="margin: 0; padding-left: 1.5em;">';
        foreach ($item['entries'] as $entry) {
            $delete_nonce = wp_create_nonce('cl_delete_entry_' . $entry['id']);

            $url_params = ['page' => $_REQUEST['page']];
            if (isset($_GET['filter_date'])) $url_params['filter_date'] = $_GET['filter_date'];
            if (isset($_GET['filter_session'])) $url_params['filter_session'] = $_GET['filter_session'];

            $edit_url = add_query_arg(array_merge($url_params, ['action' => 'edit', 'entry_id' => $entry['id']]), admin_url('admin.php'));
            $delete_url = add_query_arg(array_merge($url_params, ['action' => 'delete', 'entry_id' => $entry['id'], '_wpnonce' => $delete_nonce]), admin_url('admin.php'));

            $actions = [
                'edit' => sprintf('<a href="%s">Edit</a>', esc_url($edit_url)),
                'delete' => sprintf(
                    '<a href="%s" onclick="return confirm(\'Are you sure you want to delete this entry?\')">Delete</a>',
                    esc_url($delete_url)
                ),
            ];

            $entry_display = sprintf(
                '%s - %s',
                esc_html($entry['lottery_number']),
                number_format($entry['amount'], 2)
            );

            if ($entry['is_winner']) {
                $entry_display .= ' <span style="color: green; font-weight: bold;"> (Winner)</span>';
                if ($entry['paid_status']) {
                    $entry_display .= ' <span style="color: blue;">(Paid)</span>';
                } else {
                     $entry_display .= ' <span style="color: red;">(Unpaid)</span>';
                }
            }

            $output .= '<li style="padding: 2px 0;">' . $entry_display . $this->row_actions($actions, true) . '</li>';
        }
        $output .= '</ul>';

        return $output;
    }

    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lotto_entries';
        $per_page = 20;

        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];

        $orderby = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'customer_name';
        $order = isset($_GET['order']) ? sanitize_key($_GET['order']) : 'asc';

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

        // Fetch all matching entries
        $all_items_query = "SELECT * FROM $table_name $where_sql ORDER BY customer_name, phone, timestamp ASC";
        $all_items = $wpdb->get_results($wpdb->prepare($all_items_query, $query_params), ARRAY_A);

        // Group items by customer (using phone as a unique identifier)
        $grouped_items = [];
        foreach ($all_items as $item) {
            $key = $item['phone'];
            if (!isset($grouped_items[$key])) {
                $grouped_items[$key] = [
                    'customer_name' => $item['customer_name'],
                    'phone'         => $item['phone'],
                    'draw_session'  => $item['draw_session'],
                    'timestamp'     => $item['timestamp'],
                    'total_amount'  => 0,
                    'entries'       => [],
                ];
            }
            $grouped_items[$key]['entries'][] = $item;
            $grouped_items[$key]['total_amount'] += $item['amount'];
        }

        // Sort the grouped data
        usort($grouped_items, function ($a, $b) use ($orderby, $order) {
            $val_a = $a[$orderby];
            $val_b = $b[$orderby];

            if ($orderby === 'total_amount') {
                $result = $val_a <=> $val_b;
            } else {
                $result = strcasecmp($val_a, $val_b);
            }

            return ($order === 'asc') ? $result : -$result;
        });

        $current_page = $this->get_pagenum();
        $total_items = count($grouped_items);

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page
        ]);

        // Slice the data for pagination
        $this->items = array_slice($grouped_items, (($current_page - 1) * $per_page), $per_page);
    }

    public function get_sortable_columns() {
        return [
            'customer_name' => ['customer_name', true],
            'total_amount'  => ['total_amount', false],
            'timestamp'     => ['timestamp', true],
        ];
    }
}