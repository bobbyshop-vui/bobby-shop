jQuery(function ($) {
    $('button.mycred-send-points-button').click(function () {
        var button = $(this);
        var originalLabel = button.text();

        // Collecting data securely, ensuring required fields are present
        var amount = button.data('amount');
        var recipient = button.data('to');
        var log = button.data('log');
        var reference = button.data('ref');
        var type = button.data('type');
        var postId = button.data('post-id');
        var token = myCREDsend.token;

        // Basic client-side validation
        if (!amount || !recipient || !token) {
            alert(myCREDsend.error_missing_data || 'Invalid data provided.');
            return;
        }

        // Send AJAX request to encrypt the amount
        $.ajax({
            type: 'POST',
            url: myCREDsend.ajaxurl,
            data: {
                action: 'mycred-encrypt-amount',
                amount: amount, // Send raw amount for server-side encryption
                token: token
            },
            dataType: 'JSON',
            success: function (response) {
                if (response.status === 'success' && response.encrypted_amount) {
                    // Proceed with sending points after encryption
                    $.ajax({
                        type: 'POST',
                        url: myCREDsend.ajaxurl,
                        data: {
                            action: 'mycred-send-points',
                            amount: response.encrypted_amount, // Use encrypted amount
                            recipient: recipient,
                            post_id: postId, // Include post ID
                            log: log,
                            reference: reference,
                            type: type,
                            token: token
                        },
                        dataType: 'JSON',
                        beforeSend: function () {
                            button.attr('disabled', 'disabled').text(myCREDsend.working);
                        },
                        success: function (data) {
                            if (data.status === 'success') {
                                button.text(myCREDsend.done);
                                setTimeout(function () {
                                    button.removeAttr('disabled').text(originalLabel);
                                }, 2000);
                            } else {
                                button.text(myCREDsend.error || data.message);
                                setTimeout(function () {
                                    button.removeAttr('disabled').text(originalLabel);
                                }, 2000);
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error('AJAX Error: ', status, error);
                            alert(myCREDsend.error || 'An error occurred while processing your request.');
                            button.removeAttr('disabled').text(originalLabel);
                        }
                    });
                } else {
                    alert(myCREDsend.error || 'Failed to encrypt the amount.');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error (Encryption): ', status, error);
                alert(myCREDsend.error || 'An error occurred while encrypting the amount.');
            }
        });
    });
});
