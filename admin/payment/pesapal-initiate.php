<?php
// admin/payment/pesapal-initiate.php

require_once __DIR__ . '/../lib/PesapalClient.php';
require_once __DIR__ . '/../lib/PesapalHelper.php';

session_start();

try {
    $client = new PesapalClient();
    $config = $client->config();

    // Get IPN ID (cached)
    $ipnId = PesapalHelper::getCachedIpnId($client, $config['ipn_url']);

    // Build order data
    $orderData = [
        'id' => 'JOB_' . time() . '_' . uniqid(),  // this is the Merchant Reference Pesapal expects
        'amount' => 500,
        'currency' => $config['currency'],
        'description' => 'Job Posting Fee',
        'callback_url' => $config['callback_url'],
        'notification_id' => $ipnId,
        'billing_address' => [
            'email_address' => $_SESSION['user_email'] ?? 'customer@example.com',
            'phone_number' => $_SESSION['user_phone'] ?? '256744802591',
            'country_code' => 'UG',
            'first_name' => 'Customer',
            'last_name' => '',
        ],
    ];

    // Submit order to Pesapal
    $response = $client->submitOrder($orderData);

    // Save order tracking ID to session
    $_SESSION['pesapal_order_tracking_id'] = $response['order_tracking_id'] ?? null;

    // Redirect to Pesapal payment page
    header('Location: ' . $response['redirect_url']);
    exit;

} catch (Exception $e) {
    // Log the error
    error_log("Pesapal Initiation Error: " . $e->getMessage());

    // Display error page
    ?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>Payment Error</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                padding: 50px;
            }

            .error {
                background: #f8d7da;
                color: #721c24;
                padding: 20px;
                border-radius: 5px;
            }

            .error h2 {
                margin-top: 0;
            }
        </style>
    </head>

    <body>
        <div class="error">
            <h2>Payment Error</h2>
            <p>We're having trouble connecting to the payment system. Please try again later.</p>
            <p><strong>Error:</strong> <?php echo htmlspecialchars($e->getMessage()); ?></p>
            <p><a href="javascript:history.back()">Go Back</a></p>
        </div>
    </body>

    </html>
    <?php
    exit;
}
?>