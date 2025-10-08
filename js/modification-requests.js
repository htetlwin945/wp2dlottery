jQuery(document).ready(function($) {
    var dialog, form;
    var entryId;

    dialog = $("#modification-request-popup").dialog({
        autoOpen: false,
        height: 300,
        width: 350,
        modal: true,
        close: function() {
            form[0].reset();
            $('#mod-request-response').empty();
        }
    });

    form = dialog.find("form").on("submit", function(event) {
        event.preventDefault();

        var requestNotes = $('#mod-request-notes').val();
        var nonce = $('#mod_request_nonce').val();

        if (!requestNotes) {
            $('#mod-request-response').html('<p style="color: red;">Please enter your modification request.</p>');
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'request_entry_modification',
                entry_id: entryId,
                request_notes: requestNotes,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#mod-request-response').html('<p style="color: green;">' + response.data + '</p>');
                    setTimeout(function() {
                        dialog.dialog("close");
                        location.reload(); // Reload to show the status update
                    }, 1500);
                } else {
                    $('#mod-request-response').html('<p style="color: red;">' + response.data + '</p>');
                }
            },
            error: function() {
                $('#mod-request-response').html('<p style="color: red;">An unexpected error occurred. Please try again.</p>');
            }
        });
    });

    // Use event delegation for dynamically loaded content in the list table
    $('#the-list').on('click', '.request-modification-link', function(e) {
        e.preventDefault();
        entryId = $(this).data('entry-id');
        $('#mod-request-entry-id').val(entryId);
        dialog.dialog("open");
    });
});