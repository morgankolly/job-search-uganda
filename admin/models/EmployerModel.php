<?php

class EmployerModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /* ── Registration / lookup ─────────────────────────────── */

    public function getByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT u.*, r.role_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.role_id
            WHERE u.email = ? LIMIT 1
        ");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getById(int $user_id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT u.*, r.role_name,
                   ep.company_name, ep.company_logo, ep.company_description,
                   ep.industry, ep.website, ep.phone AS company_phone,
                   ep.address, ep.city, ep.country
            FROM users u
            LEFT JOIN roles r  ON u.role_id  = r.role_id
            LEFT JOIN employer_profiles ep ON u.user_id = ep.user_id
            WHERE u.user_id = ? LIMIT 1
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM users WHERE email = ?"
        );
        $stmt->execute([$email]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function register(
        string $username,
        string $email,
        string $password
    ): int {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("
            INSERT INTO users (user_name, email, password, role_id, employer_type, status)
            VALUES (:username, :email, :password, 2, 'registered', 'pending_verification')
        ");
        $stmt->execute([
            ':username' => $username,
            ':email'    => $email,
            ':password' => $hash,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /* ── Email verification ─────────────────────────────────── */

    public function createVerificationToken(int $user_id): string
    {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

        // Invalidate any previous unused tokens.
        $this->pdo->prepare(
            "DELETE FROM email_verifications WHERE user_id = ? AND verified_at IS NULL"
        )->execute([$user_id]);

        $stmt = $this->pdo->prepare("
            INSERT INTO email_verifications (user_id, token, expires_at)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$user_id, $token, $expires]);
        return $token;
    }

    public function verifyEmailToken(string $token): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM email_verifications
            WHERE token = ? AND verified_at IS NULL AND expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return false;

        $this->pdo->prepare(
            "UPDATE email_verifications SET verified_at = NOW() WHERE verification_id = ?"
        )->execute([$row['verification_id']]);

        $this->pdo->prepare(
            "UPDATE users SET email_verified_at = NOW(), status = 'active' WHERE user_id = ?"
        )->execute([$row['user_id']]);

        return true;
    }

    /* ── Password reset ─────────────────────────────────────── */

    public function createPasswordResetToken(string $email): ?string
    {
        if (!$this->emailExists($email)) return null;

        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $this->pdo->prepare(
            "DELETE FROM password_resets WHERE email = ?"
        )->execute([$email]);

        $this->pdo->prepare("
            INSERT INTO password_resets (email, token, expires_at)
            VALUES (?, ?, ?)
        ")->execute([$email, $token, $expires]);

        return $token;
    }

    public function resetPasswordWithToken(string $token, string $newPassword): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM password_resets
            WHERE token = ? AND used = 0 AND expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return false;

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);

        $this->pdo->prepare(
            "UPDATE users SET password = ? WHERE email = ?"
        )->execute([$hash, $row['email']]);

        $this->pdo->prepare(
            "UPDATE password_resets SET used = 1 WHERE reset_id = ?"
        )->execute([$row['reset_id']]);

        return true;
    }

    /* ── Company profile ─────────────────────────────────────── */

    public function getProfile(int $user_id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM employer_profiles WHERE user_id = ? LIMIT 1"
        );
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function saveProfile(int $user_id, array $data): bool
    {
        $existing = $this->getProfile($user_id);

        if ($existing) {
            $stmt = $this->pdo->prepare("
                UPDATE employer_profiles
                SET company_name        = :company_name,
                    company_logo        = :company_logo,
                    company_description = :company_description,
                    industry            = :industry,
                    website             = :website,
                    phone               = :phone,
                    address             = :address,
                    city                = :city,
                    country             = :country
                WHERE user_id = :user_id
            ");
        } else {
            $stmt = $this->pdo->prepare("
                INSERT INTO employer_profiles
                    (user_id, company_name, company_logo, company_description,
                     industry, website, phone, address, city, country)
                VALUES
                    (:user_id, :company_name, :company_logo, :company_description,
                     :industry, :website, :phone, :address, :city, :country)
            ");
        }

        return $stmt->execute([
            ':user_id'             => $user_id,
            ':company_name'        => $data['company_name']        ?? '',
            ':company_logo'        => $data['company_logo']        ?? null,
            ':company_description' => $data['company_description'] ?? null,
            ':industry'            => $data['industry']            ?? null,
            ':website'             => $data['website']             ?? null,
            ':phone'               => $data['phone']               ?? null,
            ':address'             => $data['address']             ?? null,
            ':city'                => $data['city']                ?? null,
            ':country'             => $data['country']             ?? 'Uganda',
        ]);
    }

    /* ── Subscription check ─────────────────────────────────── */

    public function hasActiveSubscription(int $user_id): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM subscriptions
            WHERE user_id = ?
              AND status   = 'active'
              AND end_date >= CURDATE()
        ");
        $stmt->execute([$user_id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function getActiveSubscription(int $user_id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT s.*, sp.plan_name, sp.plan_type, sp.price, sp.currency
            FROM subscriptions s
            JOIN subscription_plans sp ON s.plan_id = sp.plan_id
            WHERE s.user_id = ?
              AND s.status  = 'active'
              AND s.end_date >= CURDATE()
            ORDER BY s.end_date DESC
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /* ── Admin: list all employers ──────────────────────────── */

    public function getAllEmployers(): array
    {
        $stmt = $this->pdo->query("
            SELECT u.user_id, u.user_name, u.email, u.status,
                   u.employer_type, u.email_verified_at, u.created_at,
                   ep.company_name, ep.city,
                   (SELECT COUNT(*) FROM jobs j WHERE j.employer_id = u.user_id) AS job_count,
                   s.status AS sub_status, s.end_date AS sub_end_date,
                   sp.plan_name
            FROM users u
            LEFT JOIN employer_profiles ep ON u.user_id = ep.user_id
            LEFT JOIN subscriptions s ON u.user_id = s.user_id AND s.status = 'active'
            LEFT JOIN subscription_plans sp ON s.plan_id = sp.plan_id
            WHERE u.employer_type = 'registered'
            ORDER BY u.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus(int $user_id, string $status): bool
    {
        $allowed = ['active', 'suspended', 'pending_verification'];
        if (!in_array($status, $allowed, true)) return false;

        $stmt = $this->pdo->prepare(
            "UPDATE users SET status = ? WHERE user_id = ?"
        );
        return $stmt->execute([$status, $user_id]);
    }
}
