jQuery(document).ready(function($) {
    // Handle the click event for the "View Details" link
    $('.wp-list-table').on('click', '.view-entries-details', function(e) {
        e.preventDefault();
        var targetId = $(this).data('target');
        var $target = $(targetId);

        $target.slideToggle();

        // Optional: Change the link text
        var currentText = $(this).text();
        var newText = (currentText === 'View Details') ? 'Hide Details' : 'View Details';
        $(this).text(newText);
    });

    // Initialize the popup (dialog)
    var entryPopup = $("#lottery-entry-popup").dialog({
        autoOpen: false,
        modal: true,
        width: 600,
        height: 'auto',
        open: function() {
            // Initialize form scripts when the dialog opens
            if (window.initializeLotteryForm) {
                window.initializeLotteryForm($(this));
            }
        },
        close: function() {
            // Reset the form when closing the popup
            var $form = $('#lottery-entry-form', this);
            if ($form.length) {
                $form[0].reset();
            }
            $('#form-response', this).html('');
        }
    });

    // Open the popup when the button is clicked
    $('#add-new-entry-popup').on('click', function() {
        entryPopup.dialog('open');
    });

    // Close the popup on successful submission and refresh the page
    $(document).ajaxSuccess(function(event, xhr, settings) {
        if (settings.data.includes('action=submit_lottery_entries') && xhr.responseJSON && xhr.responseJSON.success) {
            if ($("#lottery-entry-popup").dialog('isOpen')) {
                entryPopup.dialog('close');
                location.reload(); // Refresh the page to show the new entries
            }
        }
    });
});