jQuery(document).ready(function($) {
    // Assign cover agent
    $('#cover-requests-section').on('click', '.assign-cover-agent', function() {
        const button = $(this);
        const requestId = button.data('request-id');
        const agentId = button.siblings('.cover-agent-dropdown').val();
        const actionCell = button.closest('td');

        if (!agentId) {
            alert('Please select an agent to assign.');
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'assign_cover_agent',
                nonce: cover_requests_vars.nonce,
                request_id: requestId,
                agent_id: agentId
            },
            success: function(response) {
                if (response.success) {
                    actionCell.html('<span>' + response.data.agent_name + '</span> <button class="button confirm-cover" data-request-id="' + requestId + '">Confirm Cover</button>');
                    actionCell.siblings('.request-status').text('Assigned');
                } else {
                    alert(response.data.message);
                }
            }
        });
    });

    // Confirm cover
    $('#cover-requests-section').on('click', '.confirm-cover', function() {
        const button = $(this);
        const requestId = button.data('request-id');
        const actionCell = button.closest('td');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'confirm_cover',
                nonce: cover_requests_vars.nonce,
                request_id: requestId
            },
            success: function(response) {
                if (response.success) {
                    actionCell.html('<span>Confirmed</span>');
                    actionCell.siblings('.request-status').text('Confirmed');
                } else {
                    alert(response.data.message);
                }
            }
        });
    });

    // Copy pending requests to clipboard
    $('#copy-pending-requests').on('click', function() {
        let textToCopy = '';
        $('#cover-requests-body tr').each(function() {
            const row = $(this);
            const status = row.find('.request-status').text().trim();
            if (status === 'Pending') {
                const number = row.find('td:first-child').text().trim();
                const amount = row.find('td:nth-child(2)').text().trim();
                textToCopy += number + ' - ' + amount + '\n';
            }
        });

        if (textToCopy) {
            navigator.clipboard.writeText(textToCopy).then(function() {
                alert('Pending requests copied to clipboard.');
            }, function(err) {
                alert('Failed to copy text: ', err);
            });
        } else {
            alert('No pending requests to copy.');
        }
    });
});