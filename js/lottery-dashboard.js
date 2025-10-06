jQuery(document).ready(function($) {
    var salesPayoutsChart = null;
    var netProfitChart = null;

    function renderCharts(data) {
        var salesPayoutsCtx = document.getElementById('salesPayoutsChart').getContext('2d');
        var netProfitCtx = document.getElementById('netProfitChart').getContext('2d');

        if (salesPayoutsChart) {
            salesPayoutsChart.destroy();
        }
        salesPayoutsChart = new Chart(salesPayoutsCtx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Total Sales (Kyat)',
                    data: data.sales,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }, {
                    label: 'Total Payouts (Kyat)',
                    data: data.payouts,
                    backgroundColor: 'rgba(255, 99, 132, 0.6)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Calculate Net Profit
        var netProfitData = data.sales.map((sale, index) => sale - data.payouts[index]);

        if (netProfitChart) {
            netProfitChart.destroy();
        }
        netProfitChart = new Chart(netProfitCtx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Net Profit (Kyat)',
                    data: netProfitData,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    function fetchData(range) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_dashboard_data',
                range: range,
                nonce: $('#dashboard_nonce').val()
            },
            success: function(response) {
                if (response.success) {
                    renderCharts(response.data);
                }
            },
            error: function() {
                console.error('Failed to fetch dashboard data.');
            }
        });
    }

    // Initial data load
    fetchData($('#dashboard-range-selector').val());

    // Handle range change
    $('#dashboard-range-selector').on('change', function() {
        fetchData($(this).val());
    });
});