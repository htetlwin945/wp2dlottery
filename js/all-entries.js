jQuery(document).ready(function($) {
    /**
     * Handles the click event for the "View Details" link within the list table.
     * Toggles the visibility of the detailed entry list for a given customer group.
     */
    $('.wp-list-table').on('click', '.view-entries-details', function(e) {
        e.preventDefault();
        var targetId = $(this).data('target');
        var $target = $(targetId);

        $target.slideToggle();

        var currentText = $(this).text();
        var newText = (currentText === 'View Details' || currentText === 'Детайли') ? 'Hide Details' : 'View Details';
        $(this).text(newText);
    });

    /**
     * Initializes the jQuery UI Dialog for the "Add New Entry" popup.
     * The dialog is set to not auto-open and is modal.
     */
    var entryPopup = $("#lottery-entry-popup").dialog({
        autoOpen: false,
        modal: true,
        width: 600,
        height: 'auto',
        open: function() {
            // When the dialog opens, it's crucial to initialize the form's JavaScript.
            // This call ensures that customer autocomplete, "Add More" rows, and submission work inside the popup.
            if (window.initializeLotteryForm) {
                window.initializeLotteryForm($(this));
            }
        },
        close: function() {
            // Reset the form when the popup is closed to ensure it's clean for the next use.
            var $form = $('#lottery-entry-form', this);
            if ($form.length) {
                $form[0].reset();
            }
            $('#form-response', this).html('');
            $('#print-receipt-button', this).hide();
        }
    });

    // Binds the click event to the "Add New Entry" button to open the dialog.
    $('#add-new-entry-popup').on('click', function() {
        entryPopup.dialog('open');
    });

    /**
     * Listens for successful AJAX calls for submitting lottery entries.
     * If an entry is successfully submitted from within the popup, this function
     * closes the dialog and reloads the page to display the updated list of entries.
     */
    $(document).ajaxSuccess(function(event, xhr, settings) {
        if (settings.data && settings.data.includes('action=submit_lottery_entries')) {
            if (xhr.responseJSON && xhr.responseJSON.success) {
                if ($("#lottery-entry-popup").is(':ui-dialog') && $("#lottery-entry-popup").dialog('isOpen')) {
                    // Use a short delay before reloading to allow the user to see the success message.
                    setTimeout(function() {
                        entryPopup.dialog('close');
                        location.reload(); // Refresh the page to show the new entries.
                    }, 1000);
                }
            }
        }
    });
});