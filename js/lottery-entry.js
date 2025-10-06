jQuery(document).ready(function($) {
    $('#lottery-entry-form').on('submit', function(e) {
        e.preventDefault();

        var form = $(this);
        var responseDiv = $('#form-response');
        var submitButton = form.find('button[type="submit"]');

        // Basic validation
        var lotteryNumber = $('#lottery-number').val();
        if (!/^\d{2}$/.test(lotteryNumber)) {
            responseDiv.html('<div class="error"><p>Please enter a valid 2-digit number.</p></div>');
            return;
        }

        var data = form.serialize();

        $.ajax({
            type: 'POST',
            url: ajaxurl, // ajaxurl is a global variable in WordPress admin
            data: data + '&action=add_lottery_entry',
            beforeSend: function() {
                submitButton.prop('disabled', true);
                responseDiv.html('<p>Submitting...</p>');
            },
            success: function(response) {
                if (response.success) {
                    responseDiv.html('<div class="updated"><p>' + response.data + '</p></div>');
                    form[0].reset(); // Clear the form
                    $('#lottery-number').focus(); // Set focus back to the number field
                } else {
                    responseDiv.html('<div class="error"><p>' + response.data + '</p></div>');
                }
            },
            error: function() {
                responseDiv.html('<div class="error"><p>An error occurred. Please try again.</p></div>');
            },
            complete: function() {
                submitButton.prop('disabled', false);
            }
        });
    });
});