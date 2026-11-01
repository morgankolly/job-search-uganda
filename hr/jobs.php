
<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../admin/config/connection.php';
require_once __DIR__ . '/../admin/helpers/functions.php';
require_once __DIR__ . '/../admin/models/JobModel.php';
require_once __DIR__ . '/../admin/models/UserModel.php';

// Authentication
if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    exit('Database connection is unavailable.');
}

$hrId = (int) $_SESSION['user_id'];

// Verify HR/Admin role
$role = strtolower(trim((string)(
    $_SESSION['role'] ?? $_SESSION['role_name'] ?? ''
)));

if ($role === '') {
    $roleStmt = $pdo->prepare("
        SELECT r.role_name
        FROM users u
        LEFT JOIN roles r ON r.role_id = u.role_id
        WHERE u.user_id = ?
        LIMIT 1
    ");
    $roleStmt->execute([$hrId]);
    $role = strtolower(trim((string)$roleStmt->fetchColumn()));
}

if (!in_array($role, ['hr', 'admin', 'administrator'], true)) {
    http_response_code(403);
    exit('Access denied. HR or Admin only.');
}

$jobModel = new JobModel($pdo);
$userModel = new UserModel($pdo);

$notice = '';
$pageTitle = 'Manage My Jobs';

/*
|--------------------------------------------------------------------------
| Fetch only jobs posted by the logged-in HR
|--------------------------------------------------------------------------
| jobs.employer_id stores the user_id of the person who posted the job.
*/

try {
    $stmt = $pdo->prepare("
        SELECT
            j.*,
            jc.category_name
        FROM jobs j
        LEFT JOIN job_categories jc
            ON j.job_category = jc.category_id
        WHERE j.employer_id = :hr_id
        ORDER BY j.created_at DESC
    ");

    $stmt->execute(['hr_id' => $hrId]);
    $employerJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('HR manage jobs error: ' . $e->getMessage());
    exit('Unable to load your jobs. Please check the jobs table structure.');
}

// Statistics
$totalJobs = count($employerJobs);
$openJobs = 0;
$closedJobs = 0;

foreach ($employerJobs as $job) {
    $status = strtolower(trim($job['status'] ?? ''));

    if ($status === 'open') {
        $openJobs++;
    } elseif ($status === 'closed') {
        $closedJobs++;
    }
}

$statusColors = [
    'pending'  => 'warning',
    'approved' => 'success',
    'rejected' => 'danger',
    'closed'   => 'secondary',
    'open'     => 'success'
];

include_once __DIR__ . '/../admin/components/header.php';
?>

<div class="container-fluid py-4 px-4">

    <!-- Page heading -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1 fw-bold">Manage My Jobs</h4>
            <p class="text-muted mb-0">
                View and manage jobs posted under your HR account.
            </p>
        </div>

        <a href="job-post.php" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i> Post New Job
        </a>
    </div>

    <!-- Statistics -->
    <div class="row g-3 mb-4">

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">My Total Jobs</div>
                    <h3 class="fw-bold mb-0"><?= $totalJobs ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Open Jobs</div>
                    <h3 class="fw-bold text-success mb-0"><?= $openJobs ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Closed Jobs</div>
                    <h3 class="fw-bold text-secondary mb-0"><?= $closedJobs ?></h3>
                </div>
            </div>
        </div>

    </div>

    <!-- HR's own jobs -->
    <div class="card shadow-sm">

        <div class="card-header bg-white">
            <h5 class="mb-0">Jobs Posted By Me</h5>
        </div>

        <div class="card-body table-responsive">

            <table class="table table-hover align-middle">

                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Company</th>
                        <th>Category</th>
                        <th>Applications</th>
                        <th>Status</th>
                        <th>Date Posted</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (empty($employerJobs)): ?>

                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-briefcase fa-2x mb-2"></i>
                            <p class="mb-0">You have not posted any jobs yet.</p>
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($employerJobs as $j): ?>

                        <?php
                        $status = strtolower(trim($j['status'] ?? 'unknown'));
                        $badge = $statusColors[$status] ?? 'secondary';
                        ?>

                        <tr>
                            <td><?= (int)$j['job_id'] ?></td>

                            <td>
                                <strong>
                                    <?= htmlspecialchars($j['job_title'] ?? '') ?>
                                </strong>
                            </td>

                            <td>
                                <?= htmlspecialchars($j['company_name'] ?? '') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($j['category_name'] ?? 'N/A') ?>
                            </td>

                            <td>
                                <?= (int)($j['current_applications'] ?? 0) ?>
                                /
                                <?= (int)($j['max_applications'] ?? 0) ?>
                            </td>

                            <td>
                                <span class="badge bg-<?= $badge ?>">
                                    <?= htmlspecialchars(ucfirst($status)) ?>
                                </span>
                            </td>

                            <td>
                                <?php if (!empty($j['created_at'])): ?>
                                    <?= date('d M Y', strtotime($j['created_at'])) ?>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>
            </table>

        </div>
    </div>

</div>

</body>
</html>
