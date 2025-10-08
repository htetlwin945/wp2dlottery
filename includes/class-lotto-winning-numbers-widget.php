<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Renders the content of the Winning Numbers History widget.
 * This is now a standalone function to be called directly on the custom dashboard page.
 */
function custom_lottery_render_winning_numbers_history_widget() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'lotto_winning_numbers';

    // Get filter values, with defaults
    $filter_date = isset($_POST['filter_date']) ? sanitize_text_field($_POST['filter_date']) : '';
    $filter_session = isset($_POST['filter_session']) ? sanitize_text_field($_POST['filter_session']) : 'all';

    // Base query
    $query = "SELECT * FROM {$table_name}";
    $where_clauses = [];
    $params = [];

    if (!empty($filter_date)) {
        $where_clauses[] = "draw_date = %s";
        $params[] = $filter_date;
    }

    if ($filter_session !== 'all') {
        $where_clauses[] = "draw_session = %s";
        $params[] = $filter_session;
    }

    if (!empty($where_clauses)) {
        $query .= " WHERE " . implode(" AND ", $where_clauses);
    }

    $query .= " ORDER BY draw_date DESC, draw_session DESC LIMIT 50"; // Limit results to prevent large queries

    $results = $wpdb->get_results($wpdb->prepare($query, $params));
    ?>
    <form method="post" action="">
        <p>
            <label for="filter_date"><?php esc_html_e( 'Date:', 'custom-lottery' ); ?></label>
            <input type="date" id="filter_date" name="filter_date" value="<?php echo esc_attr($filter_date); ?>">

            <label for="filter_session"><?php esc_html_e( 'Session:', 'custom-lottery' ); ?></label>
            <select id="filter_session" name="filter_session">
                <option value="all" <?php selected($filter_session, 'all'); ?>><?php esc_html_e( 'All', 'custom-lottery' ); ?></option>
                <option value="12:01 PM" <?php selected($filter_session, '12:01 PM'); ?>>12:01 PM</option>
                <option value="4:30 PM" <?php selected($filter_session, '4:30 PM'); ?>>4:30 PM</option>
            </select>

            <input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'custom-lottery' ); ?>">
        </p>
    </form>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Date', 'custom-lottery' ); ?></th>
                <th><?php esc_html_e( 'Session', 'custom-lottery' ); ?></th>
                <th><?php esc_html_e( 'Winning Number', 'custom-lottery' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( ! empty( $results ) ) : ?>
                <?php foreach ( $results as $row ) : ?>
                    <tr>
                        <td><?php echo esc_html( $row->draw_date ); ?></td>
                        <td><?php echo esc_html( $row->draw_session ); ?></td>
                        <td><strong><?php echo esc_html( $row->winning_number ); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="3"><?php esc_html_e( 'No winning numbers found for the selected filters.', 'custom-lottery' ); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php
}