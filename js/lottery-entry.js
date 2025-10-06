jQuery(document).ready(function($) {
    // Customer search autocomplete for the phone field
    $('#phone').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: ajaxurl,
                dataType: "json",
                data: {
                    action: 'search_customers',
                    term: request.term,
                    lottery_entry_nonce: $('#lottery_entry_nonce').val() // Pass nonce for security
                },
                success: function(data) {
                    response(data);
                }
            });
        },
        minLength: 2, // Start searching after 2 characters
        select: function(event, ui) {
            event.preventDefault(); // Prevent the default action of replacing the value with the 'value' property
            $('#phone').val(ui.item.value); // Set phone number
            $('#customer-name').val(ui.item.name); // Set customer name
        }
    });

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