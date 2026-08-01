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

header('Content-Type: application/json');

$orderTrackingId  = $_GET['OrderTrackingId'] ?? $_POST['OrderTrackingId'] ?? null;
$orderMerchantRef = $_GET['OrderMerchantReference'] ?? $_POST['OrderMerchantReference'] ?? null;

if (!$orderTrackingId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing OrderTrackingId']);
    exit;
}

try {
    $paymentModel = new PaymentModel($pdo);
    $jobModel     = new JobModel($pdo);

    $payment = PesapalHelper::reconcile($pdo, $paymentModel, $jobModel, $orderTrackingId);

    echo json_encode([
        'orderNotificationType'  => 'IPNCHANGE',
        'orderTrackingId'        => $orderTrackingId,
        'orderMerchantReference' => $orderMerchantRef ?: $payment['payment_reference'],
        'status'                 => 200,
    ]);
} catch (Throwable $e) {
    error_log('Pesapal IPN error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal error']);
}
