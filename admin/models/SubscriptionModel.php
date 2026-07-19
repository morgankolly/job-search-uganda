<?php

class SubscriptionModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /* ── Plans ──────────────────────────────────────────────── */

    public function getActivePlans(): array
    {
        $stmt = $this->pdo->query(
            "SELECT * FROM subscription_plans WHERE status = 'active' ORDER BY price ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPlanById(int $plan_id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM subscription_plans WHERE plan_id = ? LIMIT 1"
        );
        $stmt->execute([$plan_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /* ── Create / activate subscriptions ───────────────────── */

    public function createPendingSubscription(int $user_id, int $plan_id): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO subscriptions (user_id, plan_id, status)
            VALUES (?, ?, 'pending')
        ");
        $stmt->execute([$user_id, $plan_id]);
        return (int) $this->pdo->lastInsertId();
    }

    public function activateSubscription(int $subscription_id): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT s.subscription_id, s.user_id, sp.duration_days
            FROM subscriptions s
            JOIN subscription_plans sp ON s.plan_id = sp.plan_id
            WHERE s.subscription_id = ? LIMIT 1
        ");
        $stmt->execute([$subscription_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return false;

        $start = date('Y-m-d');
        $end   = date('Y-m-d', strtotime("+{$row['duration_days']} days"));

        $this->pdo->prepare("
            UPDATE subscriptions
            SET status = 'active', start_date = ?, end_date = ?
            WHERE subscription_id = ?
        ")->execute([$start, $end, $subscription_id]);

        return true;
    }

    public function cancelSubscription(int $subscription_id): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE subscriptions SET status = 'cancelled' WHERE subscription_id = ?"
        );
        return $stmt->execute([$subscription_id]);
    }

    /* ── Expiry check (run periodically) ───────────────────── */

    public function expireOverdueSubscriptions(): int
    {
        $stmt = $this->pdo->prepare("
            UPDATE subscriptions
            SET status = 'expired'
            WHERE status = 'active' AND end_date < CURDATE()
        ");
        $stmt->execute();
        return $stmt->rowCount();
    }

    /* ── Admin monitoring ───────────────────────────────────── */

    public function getAllSubscriptions(): array
    {
        $stmt = $this->pdo->query("
            SELECT s.*, sp.plan_name, sp.plan_type, sp.price, sp.currency,
                   u.user_name, u.email,
                   ep.company_name
            FROM subscriptions s
            JOIN subscription_plans sp ON s.plan_id   = sp.plan_id
            JOIN users              u  ON s.user_id   = u.user_id
            LEFT JOIN employer_profiles ep ON u.user_id = ep.user_id
            ORDER BY s.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStats(): array
    {
        return [
            'total'   => (int) $this->pdo->query("SELECT COUNT(*) FROM subscriptions")->fetchColumn(),
            'active'  => (int) $this->pdo->query("SELECT COUNT(*) FROM subscriptions WHERE status='active' AND end_date>=CURDATE()")->fetchColumn(),
            'pending' => (int) $this->pdo->query("SELECT COUNT(*) FROM subscriptions WHERE status='pending'")->fetchColumn(),
            'revenue' => (float) $this->pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_type='subscription' AND status='verified'")->fetchColumn(),
        ];
    }
}
