jQuery(document).ready(function($) {
    // --- State Management ---
    let selectedDigits = [];
    let isReverseActive = false;
    let entries = []; // This will hold the list of numbers and amounts to be submitted
    let lastSuccessfulTransaction = null; // For printing receipts

    // --- Customer Autocomplete (preserved from old functionality) ---
    $('#phone').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: ajaxurl,
                dataType: "json",
                data: {
                    action: 'search_customers',
                    term: request.term,
                    lottery_entry_nonce: $('#lottery_entry_nonce').val()
                },
                success: function(data) {
                    response(data);
                }
            });
        },
        minLength: 2,
        select: function(event, ui) {
            event.preventDefault();
            $('#phone').val(ui.item.value);
            $('#customer-name').val(ui.item.name);
        }
    });

    // --- UI Element References ---
    const numberButtons = $('.number-btn');
    const reverseButton = $('.reverse-btn');
    const addButton = $('.add-btn');
    const amountInput = $('#amount');
    const rAmountInput = $('#r-amount');
    const selectedNumbersDiv = $('#selected-numbers');
    const form = $('#lottery-entry-form');
    const responseDiv = $('#form-response');
    const printButton = $('#print-receipt-button');

    // --- Functions ---
    function updateNumberDisplay() {
        numberButtons.removeClass('selected');
        numberButtons.each(function() {
            const btn = $(this);
            if (selectedDigits.includes(btn.text())) {
                btn.addClass('selected');
            }
        });
    }

    function updateReverseDisplay() {
        if (isReverseActive) {
            reverseButton.addClass('selected');
            rAmountInput.prop('disabled', false);
        } else {
            reverseButton.removeClass('selected');
            rAmountInput.prop('disabled', true).val('');
        }
    }

    function renderSelectedNumbers() {
        selectedNumbersDiv.empty();
        if (entries.length === 0) {
            selectedNumbersDiv.html('<p>No numbers added yet.</p>');
            return;
        }

        entries.forEach((entry, index) => {
            let text = `<strong>${entry.number}</strong> - ${entry.amount}`;
            if (entry.r_amount) {
                text += ` (R: ${entry.r_amount})`;
            }
            selectedNumbersDiv.append(`
                <div class="entry" data-index="${index}">
                    <span>${text}</span>
                    <span class="entry-remove" title="Remove">X</span>
                </div>
            `);
        });
    }

    function clearEntrySelection() {
        selectedDigits = [];
        isReverseActive = false;
        amountInput.val('');
        rAmountInput.val('');
        updateNumberDisplay();
        updateReverseDisplay();
        amountInput.focus();
    }

    // --- Event Handlers ---
    numberButtons.on('click', function() {
        const digit = $(this).text();
        if (selectedDigits.length < 2) {
            selectedDigits.push(digit);
            $(this).addClass('selected');
        }
    });

    reverseButton.on('click', function() {
        isReverseActive = !isReverseActive;
        updateReverseDisplay();
    });

    addButton.on('click', function() {
        const number = selectedDigits.join('');
        const amount = amountInput.val();
        const r_amount = rAmountInput.val();

        if (number.length === 0 || !amount) {
            alert('Please select a number (1 or 2 digits) and enter an amount.');
            return;
        }
        if (number.length > 2) {
             alert('Please select a number (1 or 2 digits)');
            return;
        }

        entries.push({
            number: number.padStart(2, '0'), // Pad with leading zero if single digit
            amount: parseInt(amount),
            r_amount: isReverseActive && r_amount ? parseInt(r_amount) : null
        });

        renderSelectedNumbers();
        clearEntrySelection();
    });

    selectedNumbersDiv.on('click', '.entry-remove', function() {
        const index = $(this).parent().data('index');
        entries.splice(index, 1);
        renderSelectedNumbers();
    });

    form.on('submit', function(e) {
        e.preventDefault();

        if (entries.length === 0) {
            responseDiv.html('<div class="error"><p>Please add at least one number to the list.</p></div>');
            return;
        }

        const submitButton = $('#save-entry');
        const data = {
            action: 'add_lottery_entry_json', // Action for WordPress AJAX routing
            customer_name: $('#customer-name').val(),
            phone: $('#phone').val(),
            draw_session: $('#draw-session').val(),
            entries: JSON.stringify(entries), // Stringify the array of entries
            lottery_entry_nonce: $('#lottery_entry_nonce').val()
        };

        $.ajax({
            type: 'POST',
            url: ajaxurl, // Standard WordPress AJAX URL
            data: data, // Send as form data
            beforeSend: function() {
                submitButton.prop('disabled', true);
                responseDiv.html('<p>Submitting...</p>');
            },
            success: function(response) {
                if (response.success) {
                    responseDiv.html('<div class="updated"><p>' + response.data.message + '</p></div>');
                    lastSuccessfulTransaction = response.data.receipt_data; // Store for printing
                    // Clear everything
                    entries = [];
                    renderSelectedNumbers();
                    form[0].reset();
                    updateReverseDisplay(); // Ensure R button is reset
                } else {
                    responseDiv.html('<div class="error"><p>' + response.data + '</p></div>');
                    lastSuccessfulTransaction = null;
                }
            },
            error: function() {
                responseDiv.html('<div class="error"><p>An error occurred. Please try again.</p></div>');
                lastSuccessfulTransaction = null;
            },
            complete: function() {
                submitButton.prop('disabled', false);
            }
        });
    });

    printButton.on('click', function() {
        if (!lastSuccessfulTransaction) {
            alert('You must save an entry successfully before printing a receipt.');
            return;
        }

        let itemsHtml = '';
        let totalAmount = 0;

        lastSuccessfulTransaction.entries.forEach(entry => {
            itemsHtml += `
                <div class="item">
                    <span>${entry.number}</span>
                    <span>${entry.amount} Ks</span>
                </div>
            `;
            totalAmount += entry.amount;

            if (entry.r_amount) {
                const reversedNumber = entry.number.split('').reverse().join('');
                 itemsHtml += `
                    <div class="item">
                        <span>${reversedNumber} (R)</span>
                        <span>${entry.r_amount} Ks</span>
                    </div>
                `;
                totalAmount += entry.r_amount;
            }
        });

        const receiptContent = `
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
                    <p><strong>Date:</strong> ${new Date().toLocaleString('en-US', { timeZone: 'Asia/Yangon' })}</p>
                    <p><strong>Customer:</strong> ${lastSuccessfulTransaction.customer_name}</p>
                    <p><strong>Phone:</strong> ${lastSuccessfulTransaction.phone}</p>
                    <p><strong>Session:</strong> ${lastSuccessfulTransaction.draw_session}</p>
                    <hr>
                    ${itemsHtml}
                    <hr>
                    <div class="item">
                        <strong>Total:</strong>
                        <strong>${totalAmount} Ks</strong>
                    </div>
                </div>
            </body>
            </html>
        `;

        const printWindow = window.open('', '', 'height=600,width=400');
        printWindow.document.write(receiptContent);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
    });

    // Initial render
    renderSelectedNumbers();
});