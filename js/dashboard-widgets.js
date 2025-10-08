jQuery(document).ready(function($) {

    function fetchWidgetData() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_dashboard_widgets_data',
                nonce: $('#dashboard_nonce').val() // Re-use the nonce from the charts
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;

                    // Update Winning Numbers
                    $('#winning-number-morning').text(data.winning_numbers.morning);
                    $('#winning-number-evening').text(data.winning_numbers.evening);

                    // Update Live Sales Ticker
                    $('#live-sales-session').text(data.live_sales.session);
                    $('#live-sales-total').text(data.live_sales.total_sales.toLocaleString());

                    // Update Top 5 Hot Numbers
                    const hotNumbersList = $('#hot-numbers-list');
                    hotNumbersList.empty(); // Clear current list
                    if (data.hot_numbers.length > 0) {
                        $.each(data.hot_numbers, function(index, item) {
                            hotNumbersList.append('<li>' + item.lottery_number + ' (' + item.purchase_count + ' times)</li>');
                        });
                    } else {
                        hotNumbersList.append('<li>No sales data for today yet.</li>');
                    }
                } else {
                    console.error('Failed to fetch dashboard widget data.');
                }
            },
            error: function() {
                console.error('AJAX error while fetching dashboard widget data.');
            }
        });
    }

    // Fetch data on page load
    fetchWidgetData();

    // Refresh data every 30 seconds
    setInterval(fetchWidgetData, 30000);

});