<?php
require_once __DIR__ . '/PesapalClient.php';

class PaymentHelper
{
    /**
     * Start a payment: creates a `payments` row and asks Pesapal for a
     * redirect URL. Call this, then `header('Location: '.$result['redirect_url'])`.
     *
     * @param PDO    $pdo
     * @param int    $userId
     * @param string $type          'job_post' | 'application' | 'subscription'
     * @param float  $amount
     * @param string $description   shown on the Pesapal payment page
     * @param array  $billing       ['email' => ..., 'phone' => ..., 'first_name' => ..., 'last_name' => ...]
     * @param int|null $referenceId optional FK to jobs/applications row, if it already exists
     * @param array|null $pendingPayload data to persist until payment is confirmed
     *                                    (e.g. the job-post form fields, so the job
     *                                    row is only created after the fee clears)
     */
    public static function initiate(
        PDO $pdo,
        int $userId,
        string $type,
        float $amount,
        string $description,
        array $billing,
        ?int $referenceId = null,
        ?array $pendingPayload = null
    ): array {
        $pesapal = new PesapalClient();
        $config  = $pesapal->config();

        $merchantRef = strtoupper($type) . '-' . $userId . '-' . time() . '-' . bin2hex(random_bytes(3));

        // 1. Record the attempt BEFORE calling Pesapal, so we never lose track of it.
        $stmt = $pdo->prepare(
            "INSERT INTO payments
                (user_id, payment_type, reference_id, merchant_reference, amount, currency, status, pending_payload)
             VALUES (?, ?, ?, ?, ?, ?, 'PENDING', ?)"
        );
        $stmt->execute([
            $userId,
            $type,
            $referenceId,
            $merchantRef,
            $amount,
            $config['currency'],
            $pendingPayload ? json_encode($pendingPayload) : null,
        ]);
        $paymentId = (int) $pdo->lastInsertId();

        // 2. Register the IPN URL (Pesapal is idempotent about this — safe to call every time,
        //    but caching the ipn_id avoids an extra API round trip; see getCachedIpnId()).
        $ipnId = self::getCachedIpnId($pesapal, $config['ipn_url']);

        // 3. Ask Pesapal for a payment page.
        $order = $pesapal->submitOrder([
            'id'               => $merchantRef,
            'currency'         => $config['currency'],
            'amount'           => $amount,
            'description'      => substr($description, 0, 100),
            'callback_url'     => $config['callback_url'],
            'notification_id'  => $ipnId,
            'billing_address'  => [
                'email_address' => $billing['email']      ?? '',
                'phone_number'  => $billing['phone']      ?? '',
                'country_code'  => 'UG',
                'first_name'    => $billing['first_name'] ?? '',
                'last_name'     => $billing['last_name']  ?? '',
            ],
        ]);

        // 4. Save Pesapal's own tracking id so we can look this payment up later.
        $upd = $pdo->prepare("UPDATE payments SET order_tracking_id = ? WHERE payment_id = ?");
        $upd->execute([$order['order_tracking_id'], $paymentId]);

        return [
            'payment_id'   => $paymentId,
            'redirect_url' => $order['redirect_url'],
        ];
    }

    /** Cache the ipn_id in a small local file so we don't re-register on every payment. */
    private static function getCachedIpnId(PesapalClient $pesapal, string $ipnUrl): string
    {
        $cacheFile = __DIR__ . '/../config/.ipn_id_cache';
        if (file_exists($cacheFile)) {
            $cached = trim(file_get_contents($cacheFile));
            if ($cached !== '') {
                return $cached;
            }
        }
        $ipnId = $pesapal->registerIpn($ipnUrl);
        file_put_contents($cacheFile, $ipnId);
        return $ipnId;
    }

    /**
     * Look up a payment's live status from Pesapal and, if it just became
     * COMPLETED, run the type-specific finalization exactly once.
     * Safe to call repeatedly (from both callback.php and ipn.php).
     */
    public static function reconcile(PDO $pdo, string $orderTrackingId): array
    {
        $stmt = $pdo->prepare("SELECT * FROM payments WHERE order_tracking_id = ? LIMIT 1");
        $stmt->execute([$orderTrackingId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payment) {
            throw new RuntimeException("Unknown order_tracking_id: $orderTrackingId");
        }

        // Already finalized — nothing to do. Prevents double-crediting.
        if ($payment['status'] === 'COMPLETED') {
            return $payment;
        }

        $pesapal = new PesapalClient();
        $result  = $pesapal->getTransactionStatus($orderTrackingId);

        $statusMap = [1 => 'COMPLETED', 2 => 'FAILED', 0 => 'PENDING', 3 => 'INVALID'];
        $newStatus = $statusMap[$result['status_code'] ?? -1] ?? 'PENDING';

        $upd = $pdo->prepare(
            "UPDATE payments
                SET status = ?, status_description = ?, confirmation_code = ?, payment_method = ?
              WHERE payment_id = ?"
        );
        $upd->execute([
            $newStatus,
            $result['payment_status_description'] ?? null,
            $result['confirmation_code'] ?? null,
            $result['payment_method'] ?? null,
            $payment['payment_id'],
        ]);
        $payment['status'] = $newStatus;

        if ($newStatus === 'COMPLETED') {
            self::finalize($pdo, $payment);
        }

        return $payment;
    }

    /** Runs once per payment, only when status has just become COMPLETED. */
    private static function finalize(PDO $pdo, array $payment): void
    {
        $payload = $payment['pending_payload'] ? json_decode($payment['pending_payload'], true) : null;

        switch ($payment['payment_type']) {

            case 'job_post':
                if (!$payload) break;
                require_once __DIR__ . '/../models/JobModel.php';
                $jobModel = new JobModel($pdo);
                $jobId = $jobModel->createJob(
                    $payment['user_id'],
                    $payload['job_title'],
                    $payload['company_name'],
                    $payload['category_id'],
                    $payload['location'],
                    $payload['salary'],
                    $payload['job_type'],
                    $payload['description'],
                    $payload['requirements'],
                    $payload['deadline'],
                    $payload['max_applications']
                );
                $pdo->prepare("UPDATE payments SET reference_id = ? WHERE payment_id = ?")
                    ->execute([$jobId, $payment['payment_id']]);
                break;

            case 'application':
                if (!$payload) break;
                // Adjust to match your real ApplicationModel / applications table.
                $stmt = $pdo->prepare(
                    "INSERT INTO applications (user_id, job_id, payment_id, payment_status, applied_at)
                     VALUES (?, ?, ?, 'paid', NOW())"
                );
                $stmt->execute([$payment['user_id'], $payload['job_id'], $payment['payment_id']]);
                break;

            case 'subscription':
                $config = (require __DIR__ . '/../config/pesapal.php')['subscription_days'];
                $stmt = $pdo->prepare(
                    "INSERT INTO subscriptions (user_id, payment_id, starts_at, expires_at, status)
                     VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? DAY), 'active')"
                );
                $stmt->execute([$payment['user_id'], $payment['payment_id'], $config]);
                break;
        }
    }
}
