<?php
/**
 * Where the customer's browser lands after paying on Pesapal's site.
 * We don't render anything new here — we reconcile the real status,
 * then redirect back to pay.php, which already has the UI for
 * "verified" / "pending" states.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../models/PaymentModel.php';
require_once __DIR__ . '/../models/JobModel.php';
require_once __DIR__ . '/../lib/PesapalHelper.php';

$paymentModel = new PaymentModel($pdo);
$jobModel     = new JobModel($pdo);


declare(strict_types=1);

require_once __DIR__ . '/../lib/PesapalClient.php';

session_start();

try {

    $trackingId =
        trim(
            $_GET['OrderTrackingId']
            ?? ''
        );

    $merchantReference =
        trim(
            $_GET['OrderMerchantReference']
            ?? ''
        );

    if ($trackingId === '') {

        throw new Exception(
            'Pesapal OrderTrackingId is missing.'
        );
    }

    $client =
        new PesapalClient();


    /*
     * IMPORTANT:
     * Callback does not contain the payment status.
     *
     * Ask Pesapal for the actual transaction status.
     */
    $status =
        $client->getTransactionStatus(
            $trackingId
        );


    $statusCode =
        (int)(
            $status['status_code']
            ?? 0
        );


    /*
     * Pesapal:
     *
     * 0 = INVALID
     * 1 = COMPLETED
     * 2 = FAILED
     * 3 = REVERSED
     */

    if ($statusCode === 1) {

        /*
         * Payment successful.
         *
         * IMPORTANT:
         * Here you should update guest_jobs
         * and transactions in your database.
         */

        $_SESSION[
            'guest_payment_status'
        ] = 'COMPLETED';

        $_SESSION[
            'guest_payment_tracking_id'
        ] = $trackingId;

        $_SESSION[
            'guest_payment_reference'
        ] =
            $merchantReference;

        header(
            'Location: payment-success.php'
        );

        exit;
    }


    if ($statusCode === 2) {

        $_SESSION[
            'guest_payment_status'
        ] = 'FAILED';

        header(
            'Location: payment-failed.php'
        );

        exit;
    }


    if ($statusCode === 3) {

        $_SESSION[
            'guest_payment_status'
        ] = 'REVERSED';

        header(
            'Location: payment-failed.php'
        );

        exit;
    }


    /*
     * Pending / unknown.
     */
    $_SESSION[
        'guest_payment_status'
    ] = 'PENDING';

    $_SESSION[
        'guest_payment_tracking_id'
    ] = $trackingId;

    header(
        'Location: payment-pending.php'
    );

    exit;


} catch (Throwable $e) {

    error_log(
        'Pesapal Callback Error: ' .
        $e->getMessage()
    );

    $_SESSION[
        'guest_payment_status'
    ] = 'ERROR';

    header(
        'Location: payment-failed.php'
    );

    exit;
}
