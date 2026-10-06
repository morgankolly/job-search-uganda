<?php
// admin/payment/pesapal-initiate.php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../models/PaymentModel.php';
require_once __DIR__ . '/../lib/PesapalClient.php';
require_once __DIR__ . '/../lib/PesapalHelper.php';

try {

    /*
    |--------------------------------------------------------------------------
    | 1. GET PAYMENT REFERENCE
    |--------------------------------------------------------------------------
    */

    $ref = trim($_GET['ref'] ?? '');

    if ($ref === '') {
        throw new Exception('Payment reference is missing.');
    }


    /*
    |--------------------------------------------------------------------------
    | 2. FIND PAYMENT
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT *
        FROM payments
        WHERE payment_reference = ?
        LIMIT 1
    ");

    $stmt->execute([$ref]);

    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        throw new Exception(
            'Payment record not found for reference: ' . $ref
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 3. MAKE SURE THIS IS A GUEST PAYMENT
    |--------------------------------------------------------------------------
    */

    if (
        isset($payment['payer_type']) &&
        strtolower((string) $payment['payer_type']) !== 'guest'
    ) {
        throw new Exception(
            'This payment is not registered as a guest payment.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 4. MAKE SURE IT IS A PAY-PER-POST PAYMENT
    |--------------------------------------------------------------------------
    */

    if (
        isset($payment['payment_type']) &&
        strtolower((string) $payment['payment_type']) !== 'pay_per_post'
    ) {
        throw new Exception(
            'This payment is not a guest job posting payment.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 5. DO NOT CREATE ANOTHER PAYMENT IF ALREADY PAID
    |--------------------------------------------------------------------------
    */

    $status = strtolower(
        trim((string) ($payment['status'] ?? 'pending'))
    );

    if (
        in_array($status, [
            'verified',
            'completed',
            'paid',
            'success'
        ], true)
    ) {

        throw new Exception(
            'This payment has already been completed.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 6. GET STAGED JOB DATA
    |--------------------------------------------------------------------------
    |
    | Your createGuestJob() code stores the complete guest job
    | inside payments.job_payload.
    |
    */

    $jobData = json_decode(
        $payment['job_payload'] ?? '',
        true
    );

    if (!is_array($jobData)) {
        throw new Exception(
            'The guest job information attached to this payment is invalid.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 7. CUSTOMER DETAILS
    |--------------------------------------------------------------------------
    */

    $email = trim(
        (string) (
            $payment['payer_email']
            ?? $jobData['email']
            ?? ''
        )
    );

    $phone = trim(
        (string) (
            $payment['payer_phone']
            ?? $jobData['phone']
            ?? ''
        )
    );

    $payerName = trim(
        (string) (
            $payment['payer_name']
            ?? $jobData['contact_person']
            ?? 'Guest Customer'
        )
    );


    if ($email === '') {
        throw new Exception(
            'Customer email address is missing.'
        );
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception(
            'Customer email address is invalid.'
        );
    }

    if ($phone === '') {
        throw new Exception(
            'Customer phone number is missing.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 8. SPLIT NAME
    |--------------------------------------------------------------------------
    */

    $nameParts = preg_split(
        '/\s+/',
        $payerName,
        2
    );

    $firstName = $nameParts[0] ?? 'Guest';
    $lastName = $nameParts[1] ?? 'Customer';


   

    $client = new PesapalClient();

    $config = $client->config();


    /*
    |--------------------------------------------------------------------------
    | 10. GET REGISTERED IPN
    |--------------------------------------------------------------------------
    */

    $ipnId = PesapalHelper::getCachedIpnId(
        $client,
        $config['ipn_url']
    );


   

    $merchantReference =
        $ref . '-' . strtoupper(
            bin2hex(random_bytes(4))
        );

  

    $merchantReference = substr(
        $merchantReference,
        0,
        50
    );


  

    $amount = (float) (
        $config['guest_job_fee'] ?? 0
    );

    if ($amount <= 0) {
        throw new Exception(
            'Invalid guest job posting fee configured.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 13. CURRENCY
    |--------------------------------------------------------------------------
    */

    $currency = strtoupper(
        trim(
            (string) (
                $payment['currency']
                ?? $config['currency']
                ?? 'UGX'
            )
        )
    );


    /*
    |--------------------------------------------------------------------------
    | 14. JOB TITLE
    |--------------------------------------------------------------------------
    */

    $jobTitle = trim(
        (string) (
            $jobData['job_title']
            ?? 'Guest Job Posting'
        )
    );


    /*
    |--------------------------------------------------------------------------
    | 15. BUILD PESAPAL ORDER
    |--------------------------------------------------------------------------
    */

    $orderData = [

        'id' => $merchantReference,

        'amount' => $amount,

        'currency' => $currency,

        'description' =>
            'Guest Job Posting - ' . $jobTitle,

        'callback_url' =>
            $config['callback_url'],

        'notification_id' =>
            $ipnId,

        'billing_address' => [

            'email_address' =>
                $email,

            'phone_number' =>
                $phone,

            'country_code' =>
                'UG',

            'first_name' =>
                $firstName,

            'last_name' =>
                $lastName,

        ],
    ];


    /*
    |--------------------------------------------------------------------------
    | 16. SUBMIT TO PESAPAL
    |--------------------------------------------------------------------------
    */

    $response = $client->submitOrder(
        $orderData
    );


    /*
    |--------------------------------------------------------------------------
    | 17. CHECK RESPONSE
    |--------------------------------------------------------------------------
    */

    if (
        empty(
        $response['order_tracking_id']
    )
    ) {
        throw new Exception(
            'Pesapal did not return an OrderTrackingId. Response: ' .
            json_encode($response)
        );
    }

    if (
        empty(
        $response['redirect_url']
    )
    ) {
        throw new Exception(
            'Pesapal did not return a redirect URL. Response: ' .
            json_encode($response)
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 18. SAVE PESAPAL INFORMATION
    |--------------------------------------------------------------------------
    */

    $trackingId =
        $response['order_tracking_id'];


    /*
     * We update the payment using payment_id,
     * which your pay.php already uses.
     */

    $update = $pdo->prepare("
        UPDATE payments
        SET
            merchant_reference = ?,
            order_tracking_id = ?,
            updated_at = NOW()
        WHERE payment_id = ?
    ");

    $update->execute([
        $merchantReference,
        $trackingId,
        $payment['payment_id']
    ]);


    /*
    |--------------------------------------------------------------------------
    | 19. SAVE SESSION INFORMATION
    |--------------------------------------------------------------------------
    */

    session_start();

    $_SESSION['pesapal_payment_reference'] =
        $ref;

    $_SESSION['pesapal_merchant_reference'] =
        $merchantReference;

    $_SESSION['pesapal_order_tracking_id'] =
        $trackingId;


    /*
    |--------------------------------------------------------------------------
    | 20. SEND CUSTOMER TO PESAPAL
    |--------------------------------------------------------------------------
    */

    header(
        'Location: ' .
        $response['redirect_url']
    );

    exit;


} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | ERROR LOG
    |--------------------------------------------------------------------------
    */

    error_log(
        'Pesapal Initiation Error: ' .
        $e->getMessage()
    );


    /*
    |--------------------------------------------------------------------------
    | ERROR PAGE
    |--------------------------------------------------------------------------
    */

    http_response_code(500);

    ?>

    <!DOCTYPE html>

    <html lang="en">

    <head>

        <meta charset="UTF-8">

        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Payment Could Not Be Started</title>

        <style>
            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                font-family:
                    Arial,
                    Helvetica,
                    sans-serif;

                background: #f4f6f8;

                min-height: 100vh;

                display: flex;

                justify-content: center;

                align-items: center;
            }

            .card {
                width: 92%;
                max-width: 600px;

                background: #ffffff;

                border-radius: 16px;

                padding: 35px;

                box-shadow:
                    0 15px 40px rgba(0, 0, 0, .08);
            }

            .icon {
                width: 65px;
                height: 65px;

                border-radius: 50%;

                background: #ffe7e7;

                color: #c62828;

                display: flex;

                align-items: center;

                justify-content: center;

                font-size: 32px;

                font-weight: bold;

                margin-bottom: 20px;
            }

            h2 {
                margin: 0 0 12px;

                color: #222;
            }

            p {
                color: #555;

                line-height: 1.6;
            }

            .error {
                margin-top: 20px;

                padding: 15px;

                border-radius: 8px;

                background: #fff3f3;

                border: 1px solid #ffcaca;

                color: #a00000;

                word-break: break-word;
            }

            .reference {
                margin-top: 15px;

                font-size: 13px;

                color: #777;
            }

            .button {
                display: inline-block;

                margin-top: 20px;

                padding: 12px 22px;

                border-radius: 8px;

                background: #1769e0;

                color: white;

                text-decoration: none;

                font-weight: 600;
            }
        </style>

    </head>

    <body>

        <div class="card">

            <div class="icon">
                !
            </div>

            <h2>
                Payment Could Not Be Started
            </h2>

            <p>
                We were unable to connect to the
                payment service.
                Your guest job has not been charged.
            </p>

            <div class="reference">
                Payment Reference:
                <strong>
                    <?= htmlspecialchars($ref ?? '') ?>
                </strong>
            </div>

            <div class="error">

                <strong>Error:</strong>

                <?= htmlspecialchars(
                    $e->getMessage(),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>

            <a href="../pay.php?ref=<?= urlencode($ref ?? '') ?>" class="button">
                Return to Payment
            </a>

        </div>

    </body>

    </html>

    <?php

    exit;
}