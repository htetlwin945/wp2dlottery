jQuery(document).ready(function($) {

    function fetchWidgetData() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_dashboard_widgets_data',
                nonce: $('#dashboard_nonce').val()
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;

                    // Handle API data and errors
                    if (data.api_error) {
                        const errorMessage = 'API Error: ' + data.api_error;
                        $('#live-market-data-widget .inside').html('<p style="color: red;">' + errorMessage + '</p>');
                        $('#winning-numbers-widget .inside').html('<p style="color: red;">' + errorMessage + '</p>');
                    } else if (data.api_data) {
                        // Update Live Market Data
                        if (data.api_data.live) {
                            $('#live-set-index').text(data.api_data.live.set || '--');
                            $('#live-value').text(data.api_data.live.value || '--');
                            $('#live-twod').text(data.api_data.live.twod || '--');
                        }

                        // Update Today's Winning Numbers
                        let morningWinner = '--';
                        let eveningWinner = '--';
                        if (data.api_data.result && Array.isArray(data.api_data.result)) {
                            data.api_data.result.forEach(function(res) {
                                if (res.open_time === '12:01:00') {
                                    morningWinner = res.twod;
                                } else if (res.open_time === '16:30:00') {
                                    eveningWinner = res.twod;
                                }
                            });
                        }
                        $('#winning-number-morning').text(morningWinner);
                        $('#winning-number-evening').text(eveningWinner);
                    }

                    // Update Live Sales Ticker (Local DB)
                    if (data.live_sales) {
                        $('#live-sales-session').text(data.live_sales.session);
                        $('#live-sales-total').text(data.live_sales.total_sales.toLocaleString());
                    }

                    // Update Top 5 Hot Numbers (Local DB)
                    const hotNumbersList = $('#hot-numbers-list');
                    hotNumbersList.empty();
                    if (data.hot_numbers && data.hot_numbers.length > 0) {
                        $.each(data.hot_numbers, function(index, item) {
                            hotNumbersList.append('<li>' + item.lottery_number + ' (' + item.purchase_count + ' times)</li>');
                        });
                    } else {
                        hotNumbersList.append('<li>No sales data for today yet.</li>');
                    }
                } else {
                    const errorMessage = 'Failed to fetch widget data.';
                    console.error(errorMessage, response);
                    $('.postbox .inside').html('<p style="color: red;">' + errorMessage + '</p>');
                }
            },
            error: function() {
                const errorMessage = 'AJAX error while fetching widget data.';
                console.error(errorMessage);
                $('.postbox .inside').html('<p style="color: red;">' + errorMessage + '</p>');
            }
        });
    }

    // Fetch data on page load
    fetchWidgetData();

    // Refresh data every 30 seconds
    setInterval(fetchWidgetData, 30000);

});