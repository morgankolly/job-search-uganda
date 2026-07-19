<?php

class PaymentModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /* ── Helpers ────────────────────────────────────────────── */

    public function generateReference(string $prefix = 'PAY'): string
    {
        do {
            $ref = $prefix . '-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
            $exists = $this->pdo->prepare(
                "SELECT payment_id FROM payments WHERE payment_reference = ? LIMIT 1"
            );
            $exists->execute([$ref]);
        } while ($exists->fetch());

        return $ref;
    }

    /* ── Create a pending payment record ───────────────────── */

    /**
     * @param array $data Keys: payment_type, payer_type, user_id, guest_job_id,
     *                    subscription_id, amount, currency, payment_method,
     *                    payer_phone, payer_name
     */
    public function createPending(array $data): int
    {
        $ref  = $this->generateReference();
        $stmt = $this->pdo->prepare("
            INSERT INTO payments
                (payment_reference, payment_type, payer_type, user_id, guest_job_id,
                 subscription_id, amount, currency, payment_method, payer_phone, payer_name)
            VALUES
                (:ref, :type, :payer_type, :user_id, :guest_job_id,
                 :sub_id, :amount, :currency, :method, :phone, :name)
        ");
        $stmt->execute([
            ':ref'         => $ref,
            ':type'        => $data['payment_type'],
            ':payer_type'  => $data['payer_type'],
            ':user_id'     => $data['user_id']         ?? null,
            ':guest_job_id'=> $data['guest_job_id']    ?? null,
            ':sub_id'      => $data['subscription_id'] ?? null,
            ':amount'      => $data['amount'],
            ':currency'    => $data['currency']        ?? 'UGX',
            ':method'      => $data['payment_method'],
            ':phone'       => $data['payer_phone']     ?? null,
            ':name'        => $data['payer_name']      ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /* ── Admin: verify a payment ────────────────────────────── */

    public function verify(int $payment_id, int $admin_user_id, ?string $transaction_id = null): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE payments
            SET status = 'verified',
                verified_by = ?,
                verified_at = NOW(),
                transaction_id = COALESCE(?, transaction_id)
            WHERE payment_id = ? AND status = 'pending'
        ");
        return $stmt->execute([$admin_user_id, $transaction_id, $payment_id]);
    }

    public function fail(int $payment_id, int $admin_user_id, ?string $notes = null): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE payments
            SET status = 'failed',
                verified_by = ?,
                verified_at = NOW(),
                notes = COALESCE(?, notes)
            WHERE payment_id = ? AND status = 'pending'
        ");
        return $stmt->execute([$admin_user_id, $notes, $payment_id]);
    }

    /* ── Lookups ─────────────────────────────────────────────── */

    public function getById(int $payment_id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM payments WHERE payment_id = ? LIMIT 1"
        );
        $stmt->execute([$payment_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getByReference(string $ref): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM payments WHERE payment_reference = ? LIMIT 1"
        );
        $stmt->execute([$ref]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getForGuestJob(int $guest_job_id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM payments
            WHERE guest_job_id = ? AND payment_type = 'pay_per_post'
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([$guest_job_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /* ── Admin: list all payments ───────────────────────────── */

    public function getAll(): array
    {
        $stmt = $this->pdo->query("
            SELECT p.*,
                   u.user_name  AS employer_name,
                   u.email      AS employer_email,
                   ep.company_name,
                   gj.job_title AS guest_job_title,
                   sp.plan_name,
                   adm.user_name AS verified_by_name
            FROM payments p
            LEFT JOIN users              u   ON p.user_id         = u.user_id
            LEFT JOIN employer_profiles  ep  ON u.user_id         = ep.user_id
            LEFT JOIN guest_jobs         gj  ON p.guest_job_id    = gj.job_id
            LEFT JOIN subscriptions      s   ON p.subscription_id = s.subscription_id
            LEFT JOIN subscription_plans sp  ON s.plan_id         = sp.plan_id
            LEFT JOIN users              adm ON p.verified_by     = adm.user_id
            ORDER BY p.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPending(): array
    {
        $stmt = $this->pdo->query("
            SELECT p.*,
                   u.user_name AS employer_name,
                   u.email     AS employer_email,
                   ep.company_name,
                   gj.job_title AS guest_job_title,
                   sp.plan_name
            FROM payments p
            LEFT JOIN users              u  ON p.user_id      = u.user_id
            LEFT JOIN employer_profiles  ep ON u.user_id      = ep.user_id
            LEFT JOIN guest_jobs         gj ON p.guest_job_id = gj.job_id
            LEFT JOIN subscriptions      s  ON p.subscription_id = s.subscription_id
            LEFT JOIN subscription_plans sp ON s.plan_id      = sp.plan_id
            WHERE p.status = 'pending'
            ORDER BY p.created_at ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ── Stats ──────────────────────────────────────────────── */

    public function getStats(): array
    {
        return [
            'pending'       => (int)   $this->pdo->query("SELECT COUNT(*) FROM payments WHERE status='pending'")->fetchColumn(),
            'verified'      => (int)   $this->pdo->query("SELECT COUNT(*) FROM payments WHERE status='verified'")->fetchColumn(),
            'failed'        => (int)   $this->pdo->query("SELECT COUNT(*) FROM payments WHERE status='failed'")->fetchColumn(),
            'total_revenue' => (float) $this->pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='verified'")->fetchColumn(),
            'sub_revenue'   => (float) $this->pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='verified' AND payment_type='subscription'")->fetchColumn(),
            'ppp_revenue'   => (float) $this->pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='verified' AND payment_type='pay_per_post'")->fetchColumn(),
        ];
    }

    /* ── Config helper ──────────────────────────────────────── */

    public function getConfig(string $key): ?string
    {
        $stmt = $this->pdo->prepare(
            "SELECT config_value FROM system_config WHERE config_key = ? LIMIT 1"
        );
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['config_value'] : null;
    }
}
