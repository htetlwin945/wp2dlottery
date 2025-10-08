jQuery(document).ready(function($) {
    // Manual Import AJAX handler
    $('#manual-import-button').on('click', function() {
        const button = $(this);
        const spinner = $('#manual-import-spinner');
        const status = $('#manual-import-status');

        button.prop('disabled', true);
        spinner.css('visibility', 'visible');
        status.text('Importing...').css('color', '');

        const data = {
            action: 'manual_import_winning_numbers',
            nonce: $('#manual_import_nonce').val()
        };

        $.post(ajaxurl, data, function(response) {
            if (response.success) {
                status.text(response.data.message).css('color', 'green');
            } else {
                status.text('Error: ' + response.data.message).css('color', 'red');
            }
        }).fail(function() {
            status.text('An unknown error occurred.').css('color', 'red');
        }).always(function() {
            button.prop('disabled', false);
            spinner.css('visibility', 'hidden');
        });
    });
});