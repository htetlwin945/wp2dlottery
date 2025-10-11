jQuery(document).ready(function($) {
    $('#the-list').on('click', '.approve-mod-request, .reject-mod-request', function(e) {
        e.preventDefault();

        var $link = $(this);
        var requestId = $link.data('request-id');
        var nonce = $link.data('nonce');
        var action = $link.hasClass('approve-mod-request') ? 'approve_modification_request' : 'reject_modification_request';

        // Disable the row actions to prevent multiple clicks
        var $actionsContainer = $link.closest('.details-actions');
        $actionsContainer.css('pointer-events', 'none');
        $actionsContainer.append('<span class="spinner is-active" style="float: none; margin-left: 5px;"></span>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: action,
                request_id: requestId,
                nonce: nonce
            },
            success: function(response) {
                var $row = $link.closest('tr');
                if (response.success) {
                    // Update the status column and remove the action buttons
                    $row.find('td.column-status').text(response.data.new_status);
                    $actionsContainer.remove();
                } else {
                    alert('Error: ' + response.data);
                    // Re-enable the buttons on failure
                    $actionsContainer.css('pointer-events', '');
                    $actionsContainer.find('.spinner').remove();
                }
            },
            error: function() {
                alert('An unexpected error occurred. Please try again.');
                // Re-enable the buttons on error
                $actionsContainer.css('pointer-events', '');
                $actionsContainer.find('.spinner').remove();
            }
        });
    });
});