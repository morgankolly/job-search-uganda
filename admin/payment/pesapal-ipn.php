<?php
/**
 * Pesapal calls this directly, server-to-server — no browser session.
 * This is the authoritative confirmation; the browser callback above
 * is just a nicety for the customer, this is what guarantees the job
 * gets published even if they close the tab right after paying.
 */
error_reporting(E_ALL);
ini_set('display_errors', 0); // never leak errors to Pesapal's caller

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../models/PaymentModel.php';
require_once __DIR__ . '/../models/JobModel.php';
require_once __DIR__ . '/../lib/PesapalHelper.php';


declare(strict_types=1);

require_once __DIR__ . '/../lib/PesapalClient.php';

header('Content-Type: application/json');

try {

    /*
     * Pesapal may send these as GET or POST.
     */
    $trackingId =
        trim(
            $_POST['OrderTrackingId']
            ?? $_GET['OrderTrackingId']
            ?? ''
        );

    $notificationType =
        trim(
            $_POST['OrderNotificationType']
            ?? $_GET['OrderNotificationType']
            ?? ''
        );

    $merchantReference =
        trim(
            $_POST['OrderMerchantReference']
            ?? $_GET['OrderMerchantReference']
            ?? ''
        );


    if ($trackingId === '') {

        http_response_code(400);

        echo json_encode([
            'status' => 500,
            'message' =>
                'OrderTrackingId is missing'
        ]);

        exit;
    }


    /*
     * Query Pesapal for the actual status.
     */
    $client =
        new PesapalClient();

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
     * Log IPN for debugging.
     */
    error_log(
        'Pesapal IPN: ' .
        json_encode([
            'tracking_id' =>
                $trackingId,

            'merchant_reference' =>
                $merchantReference,

            'notification_type' =>
                $notificationType,

            'status_code' =>
                $statusCode,

            'status' =>
                $status['payment_status_description']
                ?? null,
        ])
    );


    /*
     * =====================================================
     * UPDATE YOUR DATABASE HERE
     * =====================================================
     *
     * COMPLETED = mark transaction paid
     * FAILED    = mark transaction failed
     * REVERSED  = mark transaction reversed
     * 0         = invalid
     */


    /*
     * Tell Pesapal that the notification
     * was received successfully.
     */
    http_response_code(200);

    echo json_encode([

        'orderNotificationType' =>
            $notificationType,

        'orderTrackingId' =>
            $trackingId,

        'orderMerchantReference' =>
            $merchantReference,

        'status' =>
            200,
    ]);

    exit;


} catch (Throwable $e) {

    error_log(
        'Pesapal IPN Error: ' .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'status' => 500,
        'message' =>
            'IPN processing failed'
    ]);

    exit;
}
