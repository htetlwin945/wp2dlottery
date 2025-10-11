jQuery(document).ready(function($) {
    var modRequestPopup, form;

    // Initialize the jQuery UI Dialog
    modRequestPopup = $("#modification-request-popup").dialog({
        autoOpen: false,
        modal: true,
        width: 400,
        height: 'auto',
        close: function() {
            // Reset the form and clear any response messages when the dialog is closed
            if (form) {
                form[0].reset();
            }
            $('#mod-request-response').html('');
        }
    });

    // Find the form within the dialog
    form = modRequestPopup.find("form");

    // Use event delegation for the "Edit" link, as the list table can be updated via AJAX
    $('.wp-list-table').on('click', '.edit-entry-link', function(e) {
        e.preventDefault();

        // Retrieve data from the link's data attributes
        var entryId = $(this).data('entry-id');
        var currentNumber = $(this).data('current-number');
        var currentAmount = $(this).data('current-amount');

        // Populate the form fields in the popup
        $('#mod-request-entry-id').val(entryId);
        $('#mod-request-number').val(currentNumber);
        $('#mod-request-amount').val(currentAmount);

        // Open the dialog
        modRequestPopup.dialog('open');
    });


    // Handle the form submission
    form.on('submit', function(e) {
        e.preventDefault();

        var responseDiv = $('#mod-request-response');
        responseDiv.html('<p>Submitting...</p>');

        // Collect all form data
        var formData = {
            action: 'request_entry_modification',
            nonce: $('#mod_request_nonce').val(),
            entry_id: $('#mod-request-entry-id').val(),
            new_number: $('#mod-request-number').val(),
            new_amount: $('#mod-request-amount').val(),
            request_notes: $('#mod-request-notes').val()
        };

        // Perform the AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    responseDiv.html('<p style="color: green;">' + response.data + '</p>');
                    // Close the dialog and reload the page after a short delay
                    setTimeout(function() {
                        modRequestPopup.dialog("close");
                        location.reload();
                    }, 1500);
                } else {
                    responseDiv.html('<p style="color: red;">Error: ' + response.data + '</p>');
                }
            },
            error: function() {
                responseDiv.html('<p style="color: red;">An unexpected error occurred. Please try again.</p>');
            }
        });
    });
});