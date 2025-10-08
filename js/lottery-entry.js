window.initializeLotteryForm = function($container) {
    var $ = jQuery;

    // --- Unbind any previous events to prevent double-firing ---
    $container.find('#lottery-entry-form').off('submit');
    $container.find('#add-entry-row').off('click');
    $container.find('#entry-rows-wrapper').off('click', '.remove-entry-row');
    $container.find('#print-receipt-button').off('click');
    if ($container.find('#phone').data('ui-autocomplete')) {
        $container.find('#phone').autocomplete('destroy');
    }

    // --- Re-initialize all logic within the provided container ---

    $container.find('#phone').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: ajaxurl,
                dataType: "json",
                data: {
                    action: 'search_customers',
                    term: request.term,
                    lottery_entry_nonce: $container.find('#lottery_entry_nonce').val()
                },
                success: function(data) {
                    response(data);
                }
            });
        },
        minLength: 2,
        select: function(event, ui) {
            event.preventDefault();
            $container.find('#phone').val(ui.item.value);
            $container.find('#customer-name').val(ui.item.name);
        },
        appendTo: $container.parent()
    });

    var lastTransaction = null;
    var entryRowWrapper = $container.find('#entry-rows-wrapper');

    $container.find('#add-entry-row').on('click', function() {
        var newRow = entryRowWrapper.find('.entry-row:first').clone(true);
        newRow.find('input').val('');
        newRow.find('input[type="checkbox"]').prop('checked', false);
        newRow.find('.remove-entry-row').show();
        entryRowWrapper.append(newRow);
        newRow.find('input[name="lottery_number[]"]').focus();
    });

    entryRowWrapper.on('click', '.remove-entry-row', function() {
        if (entryRowWrapper.find('.entry-row').length > 1) {
            $(this).closest('.entry-row').remove();
        }
    });

    $container.find('#lottery-entry-form').on('submit', function(e) {
        e.preventDefault();

        var form = $(this);
        var responseDiv = $container.find('#form-response');
        var submitButton = form.find('button[type="submit"]');
        var printButton = $container.find('#print-receipt-button');

        printButton.hide();
        responseDiv.html('');

        var entries = [];
        var isValid = true;
        $container.find('.entry-row').each(function() {
            var row = $(this);
            var lotteryNumber = row.find('input[name="lottery_number[]"]').val();
            var amount = row.find('input[name="amount[]"]').val();
            var isReverse = row.find('input[name="reverse_entry[]"]').is(':checked');

            if (!/^\d{2}$/.test(lotteryNumber) || !/^\d+$/.test(amount) || parseInt(amount) <= 0) {
                isValid = false;
                return false;
            }

            entries.push({
                number: lotteryNumber,
                amount: amount,
                is_reverse: isReverse
            });
        });

        if (!isValid) {
            responseDiv.html('<div class="error"><p>All entry rows must have a valid 2-digit number and a positive amount.</p></div>');
            return;
        }

        if (entries.length === 0) {
            responseDiv.html('<div class="error"><p>Please add at least one lottery entry.</p></div>');
            return;
        }

        var formData = {
            action: 'submit_lottery_entries',
            lottery_entry_nonce: $container.find('#lottery_entry_nonce').val(),
            customer_name: $container.find('#customer-name').val(),
            phone: $container.find('#phone').val(),
            draw_session: $container.find('#draw-session').val(),
            entries: JSON.stringify(entries)
        };

        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: formData,
            beforeSend: function() {
                submitButton.prop('disabled', true);
                responseDiv.html('<p>Submitting...</p>');
            },
            success: function(response) {
                if (response.success) {
                    responseDiv.html('<div class="updated"><p>' + response.data.message + '</p></div>');

                    lastTransaction = {
                        customerName: formData.customer_name,
                        phone: formData.phone,
                        session: formData.draw_session,
                        entries: response.data.entries,
                        totalAmount: response.data.total_amount,
                        date: new Date().toLocaleString('en-US', { timeZone: 'Asia/Yangon' })
                    };

                    printButton.show();

                    if (!$container.is('.ui-dialog-content')) {
                         entryRowWrapper.html('');
                         $container.find('#add-entry-row').trigger('click');
                         $container.find('#customer-name').val('').focus();
                         $container.find('#phone').val('');
                    }

                } else {
                    responseDiv.html('<div class="error"><p>' + (response.data || 'An unknown error occurred.') + '</p></div>');
                    printButton.hide();
                    lastTransaction = null;
                }
            },
            error: function() {
                responseDiv.html('<div class="error"><p>An AJAX error occurred. Please try again.</p></div>');
            },
            complete: function() {
                submitButton.prop('disabled', false);
            }
        });
    });

    $container.find('#print-receipt-button').on('click', function() {
        if (!lastTransaction) return;

        var receiptItems = '';
        lastTransaction.entries.forEach(function(entry) {
            receiptItems += `
                <div class="item">
                    <span>${entry.lottery_number} ${entry.is_reverse ? '(R)' : ''}</span>
                    <span>${parseInt(entry.amount).toLocaleString()} Ks</span>
                </div>
            `;
        });

        var receiptContent = `
            <html>
            <head>
                <title>Lottery Receipt</title>
                <style>
                    body { font-family: monospace; font-size: 12px; }
                    .receipt { width: 280px; margin: 0 auto; padding: 10px;}
                    h2 { text-align: center; margin: 0 0 10px 0; }
                    p { margin: 5px 0; }
                    .item { display: flex; justify-content: space-between; }
                    hr { border: none; border-top: 1px dashed #000; margin: 5px 0;}
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
                    ${receiptItems}
                    <hr>
                    <div class="item">
                        <strong>Total:</strong>
                        <strong>${lastTransaction.totalAmount.toLocaleString()} Ks</strong>
                    </div>
                </div>
            </body>
            </html>
        `;

        var printWindow = window.open('', '', 'height=500,width=350');
        printWindow.document.write(receiptContent);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
    });
};

jQuery(document).ready(function($) {
    if ($('body.lottery_page_custom-lottery-entry').length) {
        window.initializeLotteryForm($('.wrap'));
    }
});