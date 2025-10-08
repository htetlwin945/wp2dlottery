<?php

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Lotto_Customers_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => __('Customer', 'custom-lottery'),
            'plural'   => __('Customers', 'custom-lottery'),
            'ajax'     => false
        ]);
    }

    public function get_columns() {
        return [
            'cb'            => '<input type="checkbox" />',
            'customer_name' => __('Customer Name', 'custom-lottery'),
            'phone'         => __('Phone', 'custom-lottery'),
            'last_seen'     => __('Last Seen', 'custom-lottery'),
        ];
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            default:
                return esc_html($item[$column_name]);
        }
    }

    function column_customer_name($item) {
        $delete_nonce = wp_create_nonce('cl_delete_customer_' . $item['id']);
        $page = 'custom-lottery-customers';

        $actions = [
            'edit' => sprintf('<a href="?page=%s&action=%s&customer_id=%s">Edit</a>', $page, 'edit', $item['id']),
            'delete' => sprintf(
                '<a href="?page=%s&action=%s&customer_id=%s&_wpnonce=%s" onclick="return confirm(\'Are you sure you want to delete this customer? This cannot be undone.\')">Delete</a>',
                $page,
                'delete',
                $item['id'],
                $delete_nonce
            ),
        ];
        return sprintf('%1$s %2$s', esc_html($item['customer_name']), $this->row_actions($actions));
    }

    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="customer_id[]" value="%s" />', $item['id']);
    }

    protected function extra_tablenav($which) {
        if ($which == 'top' && current_user_can('manage_options')) {
            global $wpdb;
            $table_agents = $wpdb->prefix . 'lotto_agents';
            $agents = $wpdb->get_results($wpdb->prepare("SELECT id, user_id FROM $table_agents WHERE agent_type = %s", 'commission'));

            if (!empty($agents)) {
                $current_agent_filter = isset($_GET['filter_agent_id']) ? absint($_GET['filter_agent_id']) : 0;
                echo '<div class="alignleft actions">';
                echo '<select name="filter_agent_id">';
                echo '<option value="">' . esc_html__('All Agents', 'custom-lottery') . '</option>';
                foreach ($agents as $agent) {
                    $user = get_userdata($agent->user_id);
                    $display_name = $user ? $user->display_name : 'Unknown User';
                    printf(
                        '<option value="%s"%s>%s</option>',
                        esc_attr($agent->id),
                        selected($current_agent_filter, $agent->id, false),
                        esc_html($display_name)
                    );
                }
                echo '</select>';
                submit_button(__('Filter'), 'secondary', 'filter_action', false);
                echo '</div>';
            }
        }
    }

    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lotto_customers';
        $table_agents = $wpdb->prefix . 'lotto_agents';
        $per_page = 20;

        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];

        $sortable_columns = $this->get_sortable_columns();
        $orderby = isset($_GET['orderby']) && in_array($_GET['orderby'], array_keys($sortable_columns)) ? sanitize_key($_GET['orderby']) : 'last_seen';
        $order = isset($_GET['order']) && in_array(strtolower($_GET['order']), ['asc', 'desc']) ? strtolower($_GET['order']) : 'desc';
        $filter_agent_id = isset($_GET['filter_agent_id']) ? absint($_GET['filter_agent_id']) : 0;

        $where_clauses = [];
        $query_params = [];

        $current_user = wp_get_current_user();
        if (in_array('commission_agent', (array) $current_user->roles)) {
            $agent_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_agents WHERE user_id = %d", $current_user->ID));
            if ($agent_id) {
                $where_clauses[] = "agent_id = %d";
                $query_params[] = $agent_id;
            } else {
                $where_clauses[] = "1=0";
            }
        } elseif (current_user_can('manage_options') && $filter_agent_id > 0) {
            $where_clauses[] = "agent_id = %d";
            $query_params[] = $filter_agent_id;
        }

        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }

        $current_page = $this->get_pagenum();

        $total_items_query = "SELECT COUNT(id) FROM $table_name $where_sql";
        $total_items = $wpdb->get_var($wpdb->prepare($total_items_query, $query_params));

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page
        ]);

        $offset = ($current_page - 1) * $per_page;

        $query = "SELECT * FROM $table_name $where_sql ORDER BY $orderby $order LIMIT %d OFFSET %d";

        $this->items = $wpdb->get_results(
            $wpdb->prepare($query, array_merge($query_params, [$per_page, $offset])),
            ARRAY_A
        );
    }

    public function get_sortable_columns() {
        return [
            'customer_name' => ['customer_name', false],
            'last_seen' => ['last_seen', true],
        ];
    }
}