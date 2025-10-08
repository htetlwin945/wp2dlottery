<?php

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Lotto_Agents_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => __('Agent', 'custom-lottery'),
            'plural'   => __('Agents', 'custom-lottery'),
            'ajax'     => false
        ]);
    }

    public function get_columns() {
        return [
            'cb'               => '<input type="checkbox" />',
            'id'               => __('ID', 'custom-lottery'),
            'user_id'          => __('User', 'custom-lottery'),
            'agent_type'       => __('Agent Type', 'custom-lottery'),
            'commission_rate'  => __('Commission Rate', 'custom-lottery'),
            'per_number_limit' => __('Per-Number Limit', 'custom-lottery'),
            'status'           => __('Status', 'custom-lottery'),
        ];
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'id':
                return $item[$column_name];
            case 'agent_type':
            case 'status':
                return ucwords(str_replace('_', ' ', $item[$column_name]));
            case 'commission_rate':
                return $item['agent_type'] === 'commission' ? esc_html($item[$column_name]) . '%' : 'N/A';
            case 'per_number_limit':
                return $item['agent_type'] === 'commission' ? number_format(floatval($item['per_number_limit']), 2) . ' Kyat' : 'N/A';
            default:
                return esc_html($item[$column_name]);
        }
    }

    function column_user_id($item) {
        $user = get_userdata($item['user_id']);
        $display_name = $user ? $user->display_name : __('Unknown User', 'custom-lottery');

        $page = 'custom-lottery-agents';
        $delete_nonce = wp_create_nonce('cl_delete_agent_' . $item['id']);

        $actions = [
            'edit' => sprintf('<a href="?page=%s&action=%s&agent_id=%s">Edit</a>', $page, 'edit', $item['id']),
            'delete' => sprintf(
                '<a href="?page=%s&action=%s&agent_id=%s&_wpnonce=%s" onclick="return confirm(\'Are you sure you want to delete this agent? This cannot be undone.\')">Delete</a>',
                $page,
                'delete',
                $item['id'],
                $delete_nonce
            ),
        ];
        return sprintf('%1$s %2$s', esc_html($display_name), $this->row_actions($actions));
    }

    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="agent_id[]" value="%s" />', $item['id']);
    }

    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lotto_agents';
        $per_page = 20;

        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];

        // Whitelist orderby parameter to prevent SQL injection
        $orderby = isset($_GET['orderby']) && in_array($_GET['orderby'], array_keys($sortable), true) ? sanitize_key($_GET['orderby']) : 'id';
        $order = isset($_GET['order']) && in_array(strtolower($_GET['order']), ['asc', 'desc'], true) ? strtolower($_GET['order']) : 'desc';

        $current_page = $this->get_pagenum();
        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page
        ]);

        $offset = ($current_page - 1) * $per_page;

        // It's safe to use $orderby and $order here because they have been whitelisted.
        $query = "SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d";

        $this->items = $wpdb->get_results(
            $wpdb->prepare($query, $per_page, $offset),
            ARRAY_A
        );
    }

    public function get_sortable_columns() {
        return [
            'id'         => ['id', true], // true means it's the default sort
            'user_id'    => ['user_id', false],
            'agent_type' => ['agent_type', false],
            'status'     => ['status', false],
        ];
    }
}