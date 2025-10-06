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

    var lastTransaction = null;

    // Add new entry row
    $('#add-entry-row').on('click', function() {
        var newRow = $('#lottery-entries-container .entry-row:first').clone();
        newRow.find('input').val('');
        newRow.find('input[type="checkbox"]').prop('checked', false);
        newRow.find('.remove-entry-row').show();
        $('#lottery-entries-container').append(newRow);
    });

    // Remove entry row
    $('#lottery-entries-container').on('click', '.remove-entry-row', function() {
        $(this).closest('.entry-row').remove();
    });


    $('#lottery-entry-form').on('submit', function(e) {
        e.preventDefault();

        var form = $(this);
        var responseDiv = $('#form-response');
        var submitButton = form.find('button[type="submit"]');
        var printButton = $('#print-receipt-button');

        printButton.hide();
        var entries = [];
        var isValid = true;
        $('.entry-row').each(function() {
            var numberInput = $(this).find('input[name="lottery_number[]"]');
            var amountInput = $(this).find('input[name="amount[]"]');
            var number = numberInput.val();
            var amount = amountInput.val();
            var isReverse = $(this).find('input[name="reverse_entry[]"]').is(':checked');

            if (!/^\d{2}$/.test(number) || !amount || parseInt(amount) <= 0) {
                isValid = false;
                return false; // Break the loop
            }

            entries.push({
                number: number,
                amount: amount,
                isReverse: isReverse
            });
        });

        if (!isValid) {
            responseDiv.html('<div class="error"><p>Please ensure all entries have a valid 2-digit number and a positive amount.</p></div>');
            return;
        }

        var data = {
            action: 'add_lottery_entry',
            lottery_entry_nonce: $('#lottery_entry_nonce').val(),
            customer_name: $('#customer-name').val(),
            phone: $('#phone').val(),
            draw_session: $('#draw-session').val(),
            entries: entries
        };

        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: JSON.stringify(data),
            contentType: 'application/json',
            beforeSend: function() {
                submitButton.prop('disabled', true);
                responseDiv.html('<p>Submitting...</p>');
            },
            success: function(response) {
                if (response.success) {
                    responseDiv.html('<div class="updated"><p>' + response.data + '</p></div>');
                    lastTransaction = {
                        customerName: data.customer_name,
                        phone: data.phone,
                        session: data.draw_session,
                        entries: entries,
                        date: new Date().toLocaleString('en-US', { timeZone: 'Asia/Yangon' })
                    };
                    printButton.show();

                    // Reset form to initial state
                    $('.entry-row:not(:first)').remove();
                    form[0].reset();
                    $('.entry-row:first').find('input').val('');
                    $('.entry-row:first').find('input[type="checkbox"]').prop('checked', false);
                    $('.entry-row:first input[name="lottery_number[]"]').focus();

                } else {
                    responseDiv.html('<div class="error"><p>' + response.data + '</p></div>');
                    printButton.hide();
                    lastTransaction = null;
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

    $('#print-receipt-button').on('click', function() {
        if (!lastTransaction) return;

        var receiptContent = `
            <html>
            <head>
                <title>Lottery Receipt</title>
                <style>
                    body { font-family: monospace; }
                    .receipt { width: 300px; margin: 0 auto; padding: 10px;}
                    h2 { text-align: center; margin: 0; }
                    p { margin: 5px 0; }
                    .item { display: flex; justify-content: space-between; }
                    hr { border: none; border-top: 1px dashed #000; }
                </style>
            </head>
            <body>
                <div class="receipt">
                    <h2>Lottery Receipt</h2>
                    <p><strong>Date:</strong> ${lastTransaction.date}</p>
                    <p><strong>Customer:</strong> ${lastTransaction.customerName}</p>
                    <p><strong>Phone:</strong> ${lastTransaction.phone}</p>
                    <p><strong>Session:</strong> ${lastTransaction.session}</p>
                    <hr>
        `;

        var totalAmount = 0;
        lastTransaction.entries.forEach(function(entry) {
            receiptContent += `
                <div class="item">
                    <span>${entry.number}</span>
                    <span>${entry.amount} Ks</span>
                </div>
            `;
            totalAmount += parseInt(entry.amount);

            if (entry.isReverse) {
                var reversedNumber = entry.number.split('').reverse().join('');
                if (entry.number !== reversedNumber) {
                    receiptContent += `
                        <div class="item">
                            <span>${reversedNumber} (R)</span>
                            <span>${entry.amount} Ks</span>
                        </div>
                    `;
                    totalAmount += parseInt(entry.amount);
                }
            }
        });

        receiptContent += `
                    <hr>
                    <div class="item">
                        <strong>Total:</strong>
                        <strong>${totalAmount} Ks</strong>
                    </div>
                </div>
            </body>
            </html>
        `;

        var printWindow = window.open('', '', 'height=400,width=350');
        printWindow.document.write(receiptContent);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
    });

    // Handle the bulk entry form submission
    $('#lottery-bulk-entry-form').on('submit', function(e) {
        e.preventDefault();

        var form = $(this);
        var responseDiv = $('#bulk-form-response');
        var submitButton = form.find('button[type="submit"]');

        // Get data from the main form as well
        var customerName = $('#customer-name').val();
        var phone = $('#phone').val();
        var drawSession = $('#draw-session').val();
        var bulkData = $('#bulk-entry-data').val();
        var nonce = $('#lottery_entry_nonce').val();

        if (!customerName || !phone || !drawSession || !bulkData) {
            responseDiv.html('<div class="error"><p>Please fill in customer name, phone, and session in the main form before using quick entry.</p></div>');
            return;
        }

        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                action: 'bulk_add_lottery_entry',
                customer_name: customerName,
                phone: phone,
                draw_session: drawSession,
                bulk_data: bulkData,
                lottery_entry_nonce: nonce
            },
            beforeSend: function() {
                submitButton.prop('disabled', true);
                responseDiv.html('<p>Submitting...</p>');
            },
            success: function(response) {
                if (response.success) {
                    responseDiv.html('<div class="updated"><p>' + response.data + '</p></div>');
                    form[0].reset(); // Clear the bulk entry form
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