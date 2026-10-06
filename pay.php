<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/admin/config/connection.php';
require_once __DIR__ . '/admin/models/PaymentModel.php';
require_once __DIR__ . '/admin/models/JobModel.php';
require_once __DIR__ . '/admin/helpers/functions.php';
$paymentModel = new PaymentModel($pdo);

$error = "";
$notice = "";

$ref = trim($_GET['ref'] ?? '');

if (empty($ref)) {
    die("Invalid payment link. No reference provided.");
}

if (!empty($_GET['card_error'])) {
    $error = "Card payment could not be started. Please try again, or use mobile money below.";
}

// Look up the payment by reference.
$stmt = $pdo->prepare("SELECT * FROM payments WHERE payment_reference = ? LIMIT 1");
$stmt->execute([$ref]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    die("Payment record not found. Please check your link or contact support.");
}

// If a transaction ID was already submitted and it's no longer pending, show status instead of the form.
$alreadySubmitted = !empty($payment['transaction_id']);
$isVerified = $payment['status'] === 'verified';

// Handle transaction ID submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transaction_id']) && $payment['status'] === 'pending') {

    $transaction_id = trim($_POST['transaction_id']);

    if (empty($transaction_id)) {
        $error = "Please enter your transaction ID.";
    } else {
        $update = $pdo->prepare("UPDATE payments SET transaction_id = ? WHERE payment_id = ?");
        $update->execute([$transaction_id, $payment['payment_id']]);

        // Refresh local copy
        $payment['transaction_id'] = $transaction_id;
        $alreadySubmitted = true;

        $notice = "Thanks! Your transaction ID has been submitted and is awaiting verification. "
            . "Your job will be published as soon as it's confirmed.";
    }
}

$amount = number_format((float) $payment['amount'], 0);
$currency = htmlspecialchars($payment['currency'] ?? 'UGX');
$payerName = htmlspecialchars($payment['payer_name'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Payment</title>
    <meta charset="UTF-8">

    <link rel="stylesheet" href="assets/libs/flaticon/css/all/all.css">
    <link rel="stylesheet" href="assets/libs/lucide/lucide.css">
    <link rel="stylesheet" href="assets/libs/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/libs/simplebar/simplebar.css">
    <link rel="stylesheet" href="assets/libs/node-waves/waves.css">
    <link rel="stylesheet" href="assets/libs/bootstrap-select/css/bootstrap-select.min.css">
    <!-- end::GXON Required Stylesheet -->

    <!-- begin::GXON CSS Stylesheet -->
    <link rel="stylesheet" href="assets/libs/flatpickr/flatpickr.min.css">
    <link rel="stylesheet" href="assets/libs/datatables/datatables.min.css">



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- end::GXON CSS Stylesheet -->

    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 0;
            color: #222;
        }

        .wrap {
            max-width: 480px;
            margin: 40px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            padding: 30px;
        }

        h1 {
            font-size: 20px;
            margin-top: 0;
            margin-bottom: 4px;
        }

        .ref {
            color: #666;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .amount {
            font-size: 32px;
            font-weight: 700;
            color: #1a7f37;
            margin: 10px 0 20px;
        }

        .box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.6;
        }

        .box strong {
            color: #111;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        input[type="text"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 15px;
            box-sizing: border-box;
            margin-bottom: 16px;
        }

        button {
            width: 100%;
            background: #1a7f37;
            color: #fff;
            border: none;
            padding: 12px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
        }

        button:hover {
            background: #166a2e;
        }

        .msg {
            padding: 12px 14px;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .msg.error {
            background: #fdecea;
            color: #b42318;
            border: 1px solid #f5c2c0;
        }

        .msg.notice {
            background: #eaf7ee;
            color: #1a7f37;
            border: 1px solid #bfe8cb;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fef3cd;
            color: #92720d;
        }

        .status-verified {
            background: #d4edda;
            color: #1a7f37;
        }

        .card-btn {
            display: inline-block;
            background: #1a3e7f;
            color: #fff;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
        }

        .card-btn:hover {
            background: #15316a;
        }
    </style>
</head>

<body>
    <div class="wrap">
        <h1>Complete Your Payment</h1>
        <div class="ref">Reference: <strong><?= htmlspecialchars($payment['payment_reference']) ?></strong></div>

        <div class="amount"><?= $currency ?> <?= $amount ?></div>

        <?php if ($error): ?>
            <div class="msg error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($notice): ?>
            <div class="msg notice"><?= htmlspecialchars($notice) ?></div>
        <?php endif; ?>

        <?php if ($isVerified): ?>

            <div class="box">
                <span class="status-badge status-verified">Verified</span>
                <p>This payment has already been verified. Your job posting is live.</p>
            </div>

        <?php else: ?>

            <div class="box">
                <p><strong>Pay via Mobile Money</strong></p>
                <p>Send <strong><?= $currency ?>     <?= $amount ?></strong> to:</p>
                <p style="font-size:18px; font-weight:700;">0700 000 000</p>
                <p>Once you've sent the payment, enter the transaction ID / confirmation code you received via SMS below.
                </p>
            </div>

            <div class="box" style="text-align:center;">
                <p><strong>Or pay instantly by card</strong></p>
                <p style="font-size:13px; color:#666;">Mastercard, Visa — confirmed automatically, no need to submit a
                    transaction ID.</p>
                <a href="/job-search-system/admin/payment/pesapal-initiate.php?ref=<?= urlencode($payment['payment_reference']) ?>"
                    class="card-btn">
                    💳 Pay <?= $currency ?>     <?= $amount ?> with Card
                </a>
            </div>

            <?php if ($alreadySubmitted): ?>

                <div class="box">
                    <span class="status-badge status-pending">Pending Verification</span>
                    <p>Submitted transaction ID: <strong><?= htmlspecialchars($payment['transaction_id']) ?></strong></p>
                    <p>We'll publish your job as soon as this is verified. This usually takes a few minutes during business
                        hours.</p>
                </div>

            <?php else: ?>

                <form method="POST" action="">
                    <label for="transaction_id">Transaction ID</label>
                    <input type="text" id="transaction_id" name="transaction_id" placeholder="e.g. MP240730.1234.A56789"
                        required>
                    <button type="submit">Submit for Verification</button>
                </form>

            <?php endif; ?>

        <?php endif; ?>

    </div>
</body>

</html>