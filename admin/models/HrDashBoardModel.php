<?php
// admin/models/HrDashboardModel.php

class HrDashboardModel
{
    private PDO $pdo;
    private array $columnCache = [];

    private const KNOWN_TABLES = [
        'jobs', 'applications', 'job_categories', 'appointments', 'users', 'roles',
    ];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /* =====================================================================
     * PUBLIC API
     * ===================================================================== */

    /**
     * All dashboard numbers and lists.
     * Admin sees everything; HR sees only jobs they own (and the applications
     * and appointments that belong to those jobs).
     *
     * @throws RuntimeException when HR scoping is impossible (no owner column)
     */
    public function getDashboardData(int $userId, bool $isAdmin): array
    {
        $ownerColumn = $this->firstColumn(
            $this->columns('jobs'),
            ['employer_id', 'hr_id', 'created_by']
        );

        if (!$isAdmin && $ownerColumn === null) {
            throw new RuntimeException(
                'The jobs table has no recognised owner column (employer_id, hr_id, or created_by). '
                . 'Please check the jobs table structure.'
            );
        }

        return array_merge(
            $this->jobStats($isAdmin, $userId, $ownerColumn),
            $this->applicationStats($isAdmin, $userId, $ownerColumn),
            ['jobsByCategory' => $this->jobsByCategory($isAdmin, $userId, $ownerColumn)],
            $this->appointmentStats($isAdmin, $userId, $ownerColumn)
        );
    }

    /** Logged-in user's name, email, profile and role name. */
    public function getUserProfile(int $userId): array
    {
        $userColumns = $this->columns('users');
        if (!in_array('user_id', $userColumns, true)) {
            return [];
        }

        $select = [];
        foreach (['user_name', 'email', 'profile'] as $col) {
            if (in_array($col, $userColumns, true)) {
                $select[] = "u.`$col`";
            }
        }
        if (!$select) {
            return [];
        }

        $roleColumns = $this->columns('roles');
        $roleJoin    = '';
        $roleSelect  = "'user' AS role";

        if (
            in_array('role_id', $userColumns, true)
            && in_array('role_id', $roleColumns, true)
            && in_array('role_name', $roleColumns, true)
        ) {
            $roleJoin   = ' LEFT JOIN roles r ON u.role_id = r.role_id';
            $roleSelect = "COALESCE(r.role_name, 'user') AS role";
        }

        $stmt = $this->pdo->prepare(
            'SELECT ' . implode(', ', $select) . ", $roleSelect
             FROM users u$roleJoin
             WHERE u.user_id = ?
             LIMIT 1"
        );
        $stmt->execute([$userId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /* =====================================================================
     * JOBS
     * ===================================================================== */

    private function jobStats(bool $isAdmin, int $userId, ?string $ownerColumn): array
    {
        $out        = ['totalJobs' => 0, 'openJobs' => 0];
        $jobColumns = $this->columns('jobs');

        if (!in_array('job_id', $jobColumns, true)) {
            return $out;
        }

        $conditions = [];
        $params     = [];
        if (!$isAdmin) {
            $conditions[] = "j.`$ownerColumn` = ?";
            $params[]     = $userId;
        }

        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        $out['totalJobs'] = $this->count("SELECT COUNT(*) FROM jobs j$where", $params);

        if (in_array('status', $jobColumns, true)) {
            $openConditions   = $conditions;
            $openConditions[] = "LOWER(j.status) = 'open'";
            $out['openJobs']  = $this->count(
                'SELECT COUNT(*) FROM jobs j WHERE ' . implode(' AND ', $openConditions),
                $params
            );
        }

        return $out;
    }

    private function jobsByCategory(bool $isAdmin, int $userId, ?string $ownerColumn): array
    {
        $jobColumns      = $this->columns('jobs');
        $categoryColumns = $this->columns('job_categories');
        $categoryColumn  = $this->firstColumn($jobColumns, ['category_id', 'job_category']);

        if (
            $categoryColumn === null
            || !in_array('category_id', $categoryColumns, true)
            || !in_array('category_name', $categoryColumns, true)
            || !in_array('job_id', $jobColumns, true)
        ) {
            return [];
        }

        $where  = '';
        $params = [];
        if (!$isAdmin) {
            $where  = " WHERE j.`$ownerColumn` = ?";
            $params = [$userId];
        }

        return $this->select(
            "SELECT jc.category_name, COUNT(j.job_id) AS cnt
             FROM job_categories jc
             INNER JOIN jobs j ON j.`$categoryColumn` = jc.category_id$where
             GROUP BY jc.category_id, jc.category_name
             ORDER BY cnt DESC
             LIMIT 8",
            $params
        );
    }

    /* =====================================================================
     * APPLICATIONS
     * ===================================================================== */

    private function applicationStats(bool $isAdmin, int $userId, ?string $ownerColumn): array
    {
        $out = [
            'totalApps'   => 0,
            'newAppsWeek' => 0,
            'pendingApps' => 0,
            'appStatuses' => [],
            'monthlyApps' => [],
            'recentApps'  => [],
        ];

        $appColumns = $this->columns('applications');
        $jobColumns = $this->columns('jobs');

        $linked = in_array('application_id', $appColumns, true)
            && in_array('job_id', $appColumns, true)
            && in_array('job_id', $jobColumns, true);

        if (!$linked) {
            return $out;
        }

        $conditions = [];
        $params     = [];

        if (!$isAdmin) {
            $conditions[] = "j.`$ownerColumn` = ?";
            $params[]     = $userId;
        }
        if (in_array('job_source', $appColumns, true)) {
            $conditions[] = "a.job_source = 'jobs'";
        }

        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        $from  = ' FROM applications a INNER JOIN jobs j ON a.job_id = j.job_id';

        $out['totalApps'] = $this->count("SELECT COUNT(*)$from$where", $params);

        if (in_array('created_at', $appColumns, true)) {
            $weekConditions   = $conditions;
            $weekConditions[] = 'a.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
            $out['newAppsWeek'] = $this->count(
                "SELECT COUNT(*)$from WHERE " . implode(' AND ', $weekConditions),
                $params
            );

            $monthConditions   = $conditions;
            $monthConditions[] = 'a.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)';
            $out['monthlyApps'] = $this->select(
                "SELECT DATE_FORMAT(a.created_at, '%b %Y') AS month,
                        DATE_FORMAT(a.created_at, '%Y-%m') AS sort_key,
                        COUNT(*) AS cnt
                 $from
                 WHERE " . implode(' AND ', $monthConditions) . "
                 GROUP BY sort_key, month
                 ORDER BY sort_key ASC",
                $params
            );
        }

        if (in_array('status', $appColumns, true)) {
            $pendingConditions   = $conditions;
            $pendingConditions[] = "LOWER(a.status) = 'pending'";
            $out['pendingApps']  = $this->count(
                "SELECT COUNT(*)$from WHERE " . implode(' AND ', $pendingConditions),
                $params
            );

            $rows = $this->select(
                "SELECT a.status, COUNT(*) AS cnt
                 $from$where
                 GROUP BY a.status
                 ORDER BY cnt DESC",
                $params
            );
            $out['appStatuses'] = array_column($rows, 'cnt', 'status');
        }

        $select = ['a.application_id'];
        foreach (['status', 'applicant_name', 'email', 'created_at'] as $col) {
            if (in_array($col, $appColumns, true)) {
                $select[] = "a.`$col`";
            }
        }
        foreach (['job_title', 'company_name'] as $col) {
            if (in_array($col, $jobColumns, true)) {
                $select[] = "j.`$col`";
            }
        }

        $orderBy = in_array('created_at', $appColumns, true)
            ? 'a.created_at DESC'
            : 'a.application_id DESC';

        $out['recentApps'] = $this->select(
            'SELECT ' . implode(', ', $select) . "
             $from$where
             ORDER BY $orderBy
             LIMIT 8",
            $params
        );

        return $out;
    }

    /* =====================================================================
     * APPOINTMENTS
     * ===================================================================== */

    private function appointmentStats(bool $isAdmin, int $userId, ?string $ownerColumn): array
    {
        $out = ['totalAppointments' => 0, 'upcomingAppointments' => []];

        $apColumns  = $this->columns('appointments');
        $appColumns = $this->columns('applications');
        $jobColumns = $this->columns('jobs');

        if (
            !in_array('appointment_id', $apColumns, true)
            || !in_array('status', $apColumns, true)
        ) {
            return $out;
        }

        $canJoin = in_array('application_id', $apColumns, true)
            && in_array('application_id', $appColumns, true)
            && in_array('job_id', $appColumns, true)
            && in_array('job_id', $jobColumns, true);

        $conditions = ["LOWER(ap.status) = 'scheduled'"];
        $params     = [];
        $from       = ' FROM appointments ap';

        if ($canJoin) {
            $from .= ' INNER JOIN applications a ON ap.application_id = a.application_id
                       INNER JOIN jobs j ON a.job_id = j.job_id';
        }

        if (!$isAdmin) {
            $appointmentOwner = $this->firstColumn($apColumns, ['hr_id', 'user_id', 'created_by']);

            if ($appointmentOwner !== null) {
                $conditions[] = "ap.`$appointmentOwner` = ?";
                $params[]     = $userId;
            } elseif ($canJoin && $ownerColumn !== null) {
                $conditions[] = "j.`$ownerColumn` = ?";
                $params[]     = $userId;
                if (in_array('job_source', $appColumns, true)) {
                    $conditions[] = "a.job_source = 'jobs'";
                }
            } else {
                // No verifiable ownership: never expose appointments to HR.
                $conditions[] = '1 = 0';
            }
        }

        $where = ' WHERE ' . implode(' AND ', $conditions);

        $out['totalAppointments'] = $this->count("SELECT COUNT(*)$from$where", $params);

        if ($canJoin && in_array('appointment_date', $apColumns, true)) {
            $select = ['ap.appointment_id', 'ap.appointment_date', 'ap.status'];
            if (in_array('notes', $apColumns, true)) {
                $select[] = 'ap.`notes`';
            }
            foreach (['applicant_name', 'email'] as $col) {
                if (in_array($col, $appColumns, true)) {
                    $select[] = "a.`$col`";
                }
            }
            if (in_array('job_title', $jobColumns, true)) {
                $select[] = 'j.`job_title`';
            }

            $out['upcomingAppointments'] = $this->select(
                'SELECT ' . implode(', ', $select) . "$from$where
                 ORDER BY ap.appointment_date ASC
                 LIMIT 5",
                $params
            );
        }

        return $out;
    }

    /* =====================================================================
     * SCHEMA + QUERY HELPERS
     * ===================================================================== */

    private function tableExists(string $table): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM information_schema.tables
                 WHERE table_schema = DATABASE() AND table_name = ?'
            );
            $stmt->execute([$table]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function columns(string $table): array
    {
        if (isset($this->columnCache[$table])) {
            return $this->columnCache[$table];
        }

        if (!in_array($table, self::KNOWN_TABLES, true) || !$this->tableExists($table)) {
            return $this->columnCache[$table] = [];
        }

        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM `$table`");
            $cols = array_values(array_filter(array_map(
                static fn($row) => $row['Field'] ?? null,
                $stmt->fetchAll(PDO::FETCH_ASSOC)
            )));
        } catch (Throwable $e) {
            $cols = [];
        }

        return $this->columnCache[$table] = $cols;
    }

    private function firstColumn(array $columns, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                return $candidate;
            }
        }
        return null;
    }

    private function count(string $sql, array $params = []): int
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log('HR dashboard count query failed: ' . $e->getMessage());
            return 0;
        }
    }

    private function select(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('HR dashboard select query failed: ' . $e->getMessage());
            return [];
        }
    }
}