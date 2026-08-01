<?php
/**
 * Browser-facing return page. The customer lands here after paying on
 * Pesapal's site. Per Pesapal's own docs, this URL's query params do
 * NOT carry a trustworthy status — we re-check via the API (through
 * PaymentHelper::reconcile, same function the IPN uses) before showing
 * anything or crediting anything.
 */
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../lib/PaymentHelper.php';

$orderTrackingId = $_GET['OrderTrackingId'] ?? null;

$statusLabel = 'unknown';
$message     = 'We could not find that payment.';
$payment     = null;

if ($orderTrackingId) {
    try {
        $payment = PaymentHelper::reconcile($pdo, $orderTrackingId);
        $statusLabel = $payment['status'];

        $message = match ($statusLabel) {
            'COMPLETED' => 'Payment successful!',
            'FAILED'    => 'Payment failed. You have not been charged.',
            'PENDING'   => 'Payment is still processing — this page will update shortly.',
            default     => 'Payment could not be verified. If money left your account, contact support.',
        };
    } catch (Throwable $e) {
        error_log('Pesapal callback error: ' . $e->getMessage());
        $message = 'Something went wrong verifying your payment. Contact support with your reference if you were charged.';
    }
}

$pageTitle = 'Payment Result';
include_once __DIR__ . '/../components/header.php';
?>

<div class="container py-5" style="max-width:520px;">
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-5 text-center">

            <?php if ($statusLabel === 'COMPLETED'): ?>
                <div style="font-size:3rem;">✅</div>
                <h4 class="fw-bold mt-3">Payment Successful</h4>
            <?php elseif ($statusLabel === 'FAILED'): ?>
                <div style="font-size:3rem;">❌</div>
                <h4 class="fw-bold mt-3">Payment Failed</h4>
            <?php elseif ($statusLabel === 'PENDING'): ?>
                <div style="font-size:3rem;">⏳</div>
                <h4 class="fw-bold mt-3">Processing…</h4>
            <?php else: ?>
                <div style="font-size:3rem;">⚠️</div>
                <h4 class="fw-bold mt-3">Could Not Verify Payment</h4>
            <?php endif; ?>

            <p class="text-muted mt-2"><?= htmlspecialchars($message) ?></p>

            <?php if ($payment): ?>
                <p class="small text-muted mb-4">
                    Reference: <?= htmlspecialchars($payment['merchant_reference']) ?>
                </p>
            <?php endif; ?>

            <?php if ($statusLabel === 'COMPLETED' && $payment['payment_type'] === 'job_post'): ?>
                <a href="/jobs.php" class="btn btn-primary px-4">View Your Job Listing</a>
            <?php elseif ($statusLabel === 'PENDING'): ?>
                <a href="?OrderTrackingId=<?= urlencode($orderTrackingId) ?>" class="btn btn-outline-primary px-4">
                    Refresh Status
                </a>
            <?php else: ?>
                <a href="/dashboard.php" class="btn btn-outline-secondary px-4">Back to Dashboard</a>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../components/footer.php'; ?>
