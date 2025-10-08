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
        width: 'auto', // Adjust width as needed
        maxWidth: 600,
        height: 'auto',
        fluid: true,
        responsive: true,
        close: function() {
            // Optional: Reset the form when closing the popup
            $('#lottery-entry-form')[0].reset();
            $('#form-response').html('');
        }
    });

    // Open the popup when the button is clicked
    $('#add-new-entry-popup').on('click', function() {
        entryPopup.dialog('open');
    });

    // Close the popup on successful submission and refresh the page
    $(document).ajaxSuccess(function(event, xhr, settings) {
        if (settings.data.includes('action=submit_lottery_entries') && xhr.responseJSON && xhr.responseJSON.success) {
            entryPopup.dialog('close');
            location.reload(); // Refresh the page to show the new entries
        }
    });
});