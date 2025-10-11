jQuery(document).ready(function($) {
    $('#the-list').on('click', '.approve-mod-request, .reject-mod-request', function(e) {
        e.preventDefault();

        var $link = $(this);
        var requestId = $link.data('request-id');
        var nonce = $link.data('nonce');
        var action = $link.hasClass('approve-mod-request') ? 'approve_modification_request' : 'reject_modification_request';

        // Disable the row actions to prevent multiple clicks
        $link.closest('.row-actions').css('pointer-events', 'none');
        $link.closest('td').append('<span class="spinner is-active" style="float: none;"></span>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: action,
                request_id: requestId,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    // Reload the page to show the updated status and remove the processed row from the pending list.
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                    $link.closest('.details-actions').css('pointer-events', '');
                    $row.find('.spinner').remove();
                }
            },
            error: function() {
                alert('An unexpected error occurred. Please try again.');
                $link.closest('.row-actions').css('pointer-events', '');
                $link.closest('td').find('.spinner').remove();
            }
        });
    });
});