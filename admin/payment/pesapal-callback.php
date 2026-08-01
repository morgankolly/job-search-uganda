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

$orderTrackingId = $_GET['OrderTrackingId'] ?? null;

if (!$orderTrackingId) {
    die("Missing payment reference. If you were charged, contact support.");
}

try {
    $payment = PesapalHelper::reconcile($pdo, $paymentModel, $jobModel, $orderTrackingId);
    header('Location: ../../pay.php?ref=' . urlencode($payment['payment_reference']));
    exit;
} catch (Throwable $e) {
    error_log('Pesapal callback error: ' . $e->getMessage());
    die("We couldn't verify your payment right now. If you were charged, contact support with your reference and we'll sort it out.");
}
