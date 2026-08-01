<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/config/connection.php';
require_once __DIR__ . '/helpers/functions.php';

// Auth gate.
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}


$totalJobs        = $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
$openJobs         = $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'Open'")->fetchColumn();
$totalGuestJobs   = $pdo->query("SELECT COUNT(*) FROM guest_jobs")->fetchColumn();
$pendingGuestJobs = $pdo->query("SELECT COUNT(*) FROM guest_jobs WHERE status = 'pending'")->fetchColumn();
$approvedGuest    = $pdo->query("SELECT COUNT(*) FROM guest_jobs WHERE status = 'approved'")->fetchColumn();
$totalApps        = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$newAppsWeek      = $pdo->query("SELECT COUNT(*) FROM applications WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
$pendingApps      = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'")->fetchColumn();
$totalUsers       = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// --- Applications by status (for donut chart) ---
$appStatuses = $pdo->query("
    SELECT status, COUNT(*) AS cnt
    FROM applications
    GROUP BY status
    ORDER BY cnt DESC
")->fetchAll(PDO::FETCH_KEY_PAIR);

// --- Jobs by category (bar chart) ---
$jobsByCategory = $pdo->query("
    SELECT jc.category_name, COUNT(j.job_id) AS cnt
    FROM job_categories jc
    LEFT JOIN jobs j ON j.job_category = jc.category_id
    GROUP BY jc.category_id, jc.category_name
    ORDER BY cnt DESC
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

// Guest jobs by category too
$guestByCategory = $pdo->query("
    SELECT jc.category_name, COUNT(gj.job_id) AS cnt
    FROM job_categories jc
    LEFT JOIN guest_jobs gj ON gj.category_id = jc.category_id AND gj.status = 'approved'
    GROUP BY jc.category_id, jc.category_name
    ORDER BY cnt DESC
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

// --- Monthly applications trend (last 6 months) ---
$monthlyApps = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%b %Y') AS month,
           DATE_FORMAT(created_at, '%Y-%m') AS sort_key,
           COUNT(*) AS cnt
    FROM applications
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY sort_key, month
    ORDER BY sort_key ASC
")->fetchAll(PDO::FETCH_ASSOC);

// --- Recent 8 applications ---
$recentApps = $pdo->query("
    SELECT
        a.application_id,
        a.applicant_name,
        a.email,
        a.status,
        a.created_at,
        a.job_source,
        COALESCE(j.job_title,  gj.job_title)       AS job_title,
        COALESCE(j.company_name, gj.company_name)  AS company_name
    FROM applications a
    LEFT JOIN jobs j
        ON a.job_source = 'jobs' AND a.job_id = j.job_id
    LEFT JOIN guest_jobs gj
        ON a.job_source = 'guest' AND a.job_id = gj.job_id
    ORDER BY a.created_at DESC
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

// --- Guest jobs pending approval (most recent 5) ---
$pendingJobs = $pdo->query("
    SELECT gj.job_id, gj.job_title, gj.company_name,
           gj.contact_person, gj.email, gj.created_at,
           jt.type_name AS job_type
    FROM guest_jobs gj
    LEFT JOIN job_types jt ON gj.job_type = jt.type_id
    WHERE gj.status = 'pending'
    ORDER BY gj.created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guest_job_id'], $_POST['action'])) {
    $guestJobId = (int) $_POST['guest_job_id'];
    $allowed    = ['approved' => 'approved', 'rejected' => 'rejected'];
    if (isset($allowed[$_POST['action']])) {
        $pdo->prepare("UPDATE guest_jobs SET status = ? WHERE job_id = ?")
            ->execute([$allowed[$_POST['action']], $guestJobId]);
    }
    header("Location: dashboard.php");
    exit;
}

// --- Logged-in user info ---
$stmt = $pdo->prepare("
    SELECT u.user_name, u.email, u.profile, r.role_name AS role
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.role_id
    WHERE u.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

/* --- Chart data as JSON for JS --- */
$chartAppLabels  = json_encode(array_keys($appStatuses));
$chartAppData    = json_encode(array_values($appStatuses));
$chartCatLabels  = json_encode(array_column($jobsByCategory, 'category_name'));
$chartCatData    = json_encode(array_column($jobsByCategory, 'cnt'));
$chartMonthLabel = json_encode(array_column($monthlyApps,   'month'));
$chartMonthData  = json_encode(array_column($monthlyApps,   'cnt'));

$statusColors = [
    'pending'     => 'badge bg-warning text-dark',
    'reviewed'    => 'badge bg-info text-dark',
    'shortlisted' => 'badge bg-success',
    'rejected'    => 'badge bg-danger',
];

include_once __DIR__ . '/components/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – Job Search Admin</title>

    <link rel="stylesheet" href="../assets/libs/flaticon/css/all/all.css">
    <link rel="stylesheet" href="../assets/libs/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../assets/libs/simplebar/simplebar.css">
    <link rel="stylesheet" href="../assets/libs/node-waves/waves.css">

    <link rel="stylesheet" href="../assets/css/.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

</head>
<body>

<main class="app-wrapper-expand-lg">
<div class="container py-4">

    <!-- Page header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-9">
        <div>
            <h4 class="mb-0 fw-bold">
                Welcome back, <?= htmlspecialchars($user['user_name'] ?? 'Admin') ?>
            </h4>
            <small class="text-muted">
                <?= date('l, d F Y') ?> &mdash; <?= htmlspecialchars(ucfirst($user['role'] ?? 'Admin')) ?>
            </small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="job-post.php"     class="btn btn-primary btn-sm"><i class="fa fa-plus me-1"></i>Post Job</a>
            <a href="jobs.php"         class="btn btn-outline-secondary btn-sm"><i class="fa fa-briefcase me-1"></i>Manage Jobs</a>
            <a href="applications.php" class="btn btn-outline-secondary btn-sm"><i class="fa fa-file-alt me-1"></i>Applications</a>
        </div>
    </div>

    <!-- ==================== STAT CARDS ==================== -->
    <div class="row g-3 mb-4">

        <div class="col-6 col-md-3">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon-wrap bg-primary bg-opacity-10 text-primary">
                        <i class="fa fa-briefcase"></i>
                    </div>
                    <div>
                        <div class="stat-value text-primary"><?= (int)$totalJobs + (int)$totalGuestJobs ?></div>
                        <div class="text-muted small">Total Jobs</div>
                        <div class="text-muted" style="font-size:.72rem;"><?= (int)$openJobs + (int)$approvedGuest ?> open</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon-wrap bg-warning bg-opacity-10 text-warning">
                        <i class="fa fa-clock"></i>
                    </div>
                    <div>
                        <div class="stat-value text-warning"><?= (int)$pendingGuestJobs ?></div>
                        <div class="text-muted small">Pending Approval</div>
                        <div class="text-muted" style="font-size:.72rem;">Guest jobs awaiting review</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon-wrap bg-success bg-opacity-10 text-success">
                        <i class="fa fa-users"></i>
                    </div>
                    <div>
                        <div class="stat-value text-success"><?= (int)$totalApps ?></div>
                        <div class="text-muted small">Applications</div>
                        <div class="text-muted" style="font-size:.72rem;"><?= (int)$newAppsWeek ?> this week · <?= (int)$pendingApps ?> pending</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon-wrap bg-info bg-opacity-10 text-info">
                        <i class="fa fa-user-shield"></i>
                    </div>
                    <div>
                        <div class="stat-value text-info"><?= (int)$totalUsers ?></div>
                        <div class="text-muted small">Admin Users</div>
                        <div class="text-muted" style="font-size:.72rem;">Registered accounts</div>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /stat cards -->

    <!-- ==================== CHARTS ROW ==================== -->
    <div class="row g-3 mb-4">

        <!-- Monthly applications trend (line) -->
        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h6 class="card-title mb-0">Applications – Last 6 Months</h6>
                </div>
                <div class="card-body">
                    <canvas id="chartMonthly" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- Applications by status (donut) -->
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h6 class="card-title mb-0">Applications by Status</h6>
                </div>
                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                    <?php if (array_sum($appStatuses) > 0): ?>
                        <canvas id="chartStatus" style="max-height:200px;"></canvas>
                    <?php else: ?>
                        <p class="text-muted text-center mt-3">No applications yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Jobs by category (horizontal bar) -->
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-0 pb-0">
                    <h6 class="card-title mb-0">Employer Jobs by Category</h6>
                </div>
                <div class="card-body">
                    <canvas id="chartCategory" height="60"></canvas>
                </div>
            </div>
        </div>

    </div><!-- /charts row -->

    <!-- ==================== PENDING GUEST JOBS ==================== -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0">
                        Pending Guest Jobs
                        <?php if ($pendingGuestJobs > 0): ?>
                            <span class="badge bg-warning text-dark ms-1"><?= (int)$pendingGuestJobs ?></span>
                        <?php endif; ?>
                    </h6>
                    <a href="jobs.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Job Title</th>
                                    <th>Company</th>
                                    <th>Contact</th>
                                    <th>Type</th>
                                    <th>Submitted</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($pendingJobs)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">
                                        <i class="fa fa-check-circle text-success me-1"></i>
                                        No pending jobs — you're all caught up!
                                    </td>
                                </tr>
                            <?php else: foreach ($pendingJobs as $pj): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($pj['job_title']) ?></td>
                                    <td><?= htmlspecialchars($pj['company_name']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($pj['contact_person']) ?><br>
                                        <small class="text-muted"><?= htmlspecialchars($pj['email']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($pj['job_type'] ?? '—') ?></td>
                                    <td><?= date('d M Y', strtotime($pj['created_at'])) ?></td>
                                    <td class="text-end">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="guest_job_id" value="<?= (int)$pj['job_id'] ?>">
                                            <button name="action" value="approved"
                                                class="btn btn-sm btn-success">
                                                <i class="fa fa-check me-1"></i>Approve
                                            </button>
                                            <button name="action" value="rejected"
                                                class="btn btn-sm btn-danger">
                                                <i class="fa fa-times me-1"></i>Reject
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /pending jobs -->

    <!-- ==================== RECENT APPLICATIONS ==================== -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0">Recent Applications</h6>
                    <a href="applications.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Applicant</th>
                                    <th>Job</th>
                                    <th>Source</th>
                                    <th>Applied</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($recentApps)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-3">No applications yet.</td>
                                </tr>
                            <?php else: foreach ($recentApps as $app): ?>
                                <tr>
                                    <td class="text-muted small">#<?= (int)$app['application_id'] ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($app['applicant_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($app['email']) ?></small>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($app['job_title'] ?? '(deleted)') ?><br>
                                        <small class="text-muted"><?= htmlspecialchars($app['company_name'] ?? '') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge <?= $app['job_source'] === 'guest' ? 'bg-secondary' : 'bg-primary' ?>">
                                            <?= $app['job_source'] === 'guest' ? 'Guest' : 'Employer' ?>
                                        </span>
                                    </td>
                                    <td><?= date('d M Y', strtotime($app['created_at'])) ?></td>
                                    <td>
                                        <span class="<?= $statusColors[$app['status']] ?? 'badge bg-secondary' ?>">
                                            <?= ucfirst($app['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="applications.php" class="btn btn-sm btn-outline-secondary">Manage</a>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /recent applications -->

</div><!-- /container -->



</main><!-- /app-wrapper -->
<?php include_once __DIR__ . '/components/footer.php'; ?>
<!-- ==================== CHARTS JS ==================== -->
<script>
const chartDefaults = {
    responsive: true,
    plugins: { legend: { position: 'bottom' } }
};

/* -- Monthly trend (line) -- */
const monthlyCtx = document.getElementById('chartMonthly');
if (monthlyCtx) {
    new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: <?= $chartMonthLabel ?>,
            datasets: [{
                label: 'Applications',
                data: <?= $chartMonthData ?>,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13,110,253,.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointBackgroundColor: '#0d6efd'
            }]
        },
        options: {
            ...chartDefaults,
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
}

/* -- Applications by status (donut) -- */
const statusCtx = document.getElementById('chartStatus');
if (statusCtx) {
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: <?= $chartAppLabels ?>,
            datasets: [{
                data: <?= $chartAppData ?>,
                backgroundColor: ['#ffc107','#0dcaf0','#198754','#dc3545','#6c757d'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: chartDefaults
    });
}

/* -- Jobs by category (horizontal bar) -- */
const catCtx = document.getElementById('chartCategory');
if (catCtx) {
    new Chart(catCtx, {
        type: 'bar',
        data: {
            labels: <?= $chartCatLabels ?>,
            datasets: [{
                label: 'Employer Jobs',
                data: <?= $chartCatData ?>,
                backgroundColor: 'rgba(13,110,253,.7)',
                borderRadius: 6
            }]
        },
        options: {
            ...chartDefaults,
            indexAxis: 'y',
            scales: {
                x: { beginAtZero: true, ticks: { stepSize: 1 } }
            },
            plugins: { legend: { display: false } }
        }
    });
}
</script>

</body>
</html>
