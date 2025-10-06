jQuery(document).ready(function($) {
    $('#lottery-portal-form').on('submit', function(e) {
        e.preventDefault();

        var form = $(this);
        var resultsDiv = $('#lottery-portal-results');
        var submitButton = form.find('button[type="submit"]');
        var phone = $('#portal-phone-number').val();
        var nonce = $('#lottery_portal_nonce').val();

        $.ajax({
            type: 'POST',
            url: lottery_portal_ajax.ajax_url,
            data: {
                action: 'get_customer_lottery_results',
                phone: phone,
                nonce: nonce
            },
            beforeSend: function() {
                submitButton.prop('disabled', true);
                resultsDiv.html('<p>Loading...</p>');
            },
            success: function(response) {
                if (response.success) {
                    var html = '<h3>Your Recent Entries</h3>';
                    if (response.data.length > 0) {
                        html += '<table><thead><tr><th>Date</th><th>Session</th><th>Number</th><th>Amount (Kyat)</th><th>Status</th></tr></thead><tbody>';
                        response.data.forEach(function(entry) {
                            var status = entry.is_winner == '1' ? '<strong>Winner!</strong>' : 'Not a winner';
                            html += '<tr>';
                            html += '<td>' + entry.date + '</td>';
                            html += '<td>' + entry.draw_session + '</td>';
                            html += '<td>' + entry.lottery_number + '</td>';
                            html += '<td>' + entry.amount + '</td>';
                            html += '<td>' + status + '</td>';
                            html += '</tr>';
                        });
                        html += '</tbody></table>';
                    } else {
                        html = '<p>No recent entries found for this phone number.</p>';
                    }
                    resultsDiv.html(html);
                } else {
                    resultsDiv.html('<p>' + response.data + '</p>');
                }
            },
            error: function() {
                resultsDiv.html('<p>An error occurred. Please try again.</p>');
            },
            complete: function() {
                submitButton.prop('disabled', false);
            }
        });
    });
});