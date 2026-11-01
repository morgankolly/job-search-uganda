<?php
require_once __DIR__ . '/main/auth-middleware.php';
require_once __DIR__ . '/../admin/controllers/HrDashboardController.php';
include_once __DIR__ . '/../admin/components/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Dashboard – Job Search Uganda</title>

    <link rel="stylesheet" href="../assets/libs/flaticon/css/all/all.css">
    <link rel="stylesheet" href="../assets/libs/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../assets/libs/apexcharts/bootstrap-select/bootstrap-select.min.css">
    <link rel="stylesheet" href="../assets/libs/simplebar/simplebar.css">
    <link rel="stylesheet" href="../assets/libs/node-waves/waves.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

    <style>
        body { background: #f5f7fb; color: #212529; }
        .stat-card, .card { border: 0; border-radius: 14px; }
        .stat-value { font-size: 1.8rem; font-weight: 700; line-height: 1.2; }
        .icon-wrap { width: 46px; height: 46px; min-width: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; }
        .card-header { border-radius: 14px 14px 0 0 !important; padding: 1rem 1.25rem; }
        .table th { font-size: .82rem; white-space: nowrap; }
        .table td { font-size: .9rem; }
    </style>
</head>
<body>

<main class="app-wrapper-expand-lg">
<div class="container py-4">

    <!-- Page header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h4 class="mb-0 fw-bold">
                Welcome back, <?= htmlspecialchars($user['user_name'] ?? 'HR Manager') ?>
            </h4>
            <small class="text-muted">
                <?= date('l, d F Y') ?> &mdash; <?= htmlspecialchars(ucfirst($user['role'] ?? 'HR')) ?>
            </small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="job-post.php"     class="btn btn-primary btn-sm"><i class="fa fa-plus me-1"></i>Post Job</a>
            <a href="manage-jobs.php"  class="btn btn-outline-secondary btn-sm"><i class="fa fa-briefcase me-1"></i>Manage Jobs</a>
            <a href="applications.php" class="btn btn-outline-secondary btn-sm"><i class="fa fa-file-alt me-1"></i>Applications</a>
        </div>
    </div>

    <!-- ==================== STAT CARDS ==================== -->
    <div class="row g-3 mb-4">

        <div class="col-6 col-md-3">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon-wrap bg-primary bg-opacity-10 text-primary"><i class="fa fa-briefcase"></i></div>
                    <div>
                        <div class="stat-value text-primary"><?= (int) $totalJobs ?></div>
                        <div class="text-muted small">My Jobs</div>
                        <div class="text-muted" style="font-size:.72rem;"><?= (int) $openJobs ?> open</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon-wrap bg-warning bg-opacity-10 text-warning"><i class="fa fa-calendar-check"></i></div>
                    <div>
                        <div class="stat-value text-warning"><?= (int) $totalAppointments ?></div>
                        <div class="text-muted small">Scheduled Interviews</div>
                        <div class="text-muted" style="font-size:.72rem;">Upcoming appointments</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon-wrap bg-success bg-opacity-10 text-success"><i class="fa fa-users"></i></div>
                    <div>
                        <div class="stat-value text-success"><?= (int) $totalApps ?></div>
                        <div class="text-muted small">Applications</div>
                        <div class="text-muted" style="font-size:.72rem;"><?= (int) $newAppsWeek ?> this week · <?= (int) $pendingApps ?> pending</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon-wrap bg-info bg-opacity-10 text-info"><i class="fa fa-check-double"></i></div>
                    <div>
                        <div class="stat-value text-info"><?= (int) ($appStatuses['shortlisted'] ?? 0) ?></div>
                        <div class="text-muted small">Shortlisted</div>
                        <div class="text-muted" style="font-size:.72rem;">Candidates to review</div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- ==================== CHARTS ROW ==================== -->
    <div class="row g-3 mb-4">

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

        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-0 pb-0">
                    <h6 class="card-title mb-0">My Jobs by Category</h6>
                </div>
                <div class="card-body">
                    <canvas id="chartCategory" height="60"></canvas>
                </div>
            </div>
        </div>

    </div>

    <!-- ==================== UPCOMING APPOINTMENTS ==================== -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0">
                        Upcoming Interviews
                        <?php if ($totalAppointments > 0): ?>
                            <span class="badge bg-warning text-dark ms-1"><?= (int) $totalAppointments ?></span>
                        <?php endif; ?>
                    </h6>
                    <a href="appointments.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Candidate</th>
                                    <th>Job Role</th>
                                    <th>Date &amp; Time</th>
                                    <th>Notes</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($upcomingAppointments)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">
                                            <i class="fa fa-calendar text-muted me-1"></i>
                                            No upcoming interviews scheduled.
                                        </td>
                                    </tr>
                                <?php else: foreach ($upcomingAppointments as $apt): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars($apt['applicant_name'] ?? 'Applicant') ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($apt['email'] ?? '') ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($apt['job_title'] ?? '—') ?></td>
                                        <td>
                                            <span class="text-success fw-semibold"><?= date('d M Y', strtotime($apt['appointment_date'] ?? '')) ?></span><br>
                                            <small class="text-muted"><?= date('h:i A', strtotime($apt['appointment_date'] ?? '')) ?></small>
                                        </td>
                                        <td><small class="text-muted"><?= htmlspecialchars($apt['notes'] ?? '—') ?></small></td>
                                        <td class="text-end">
                                            <a href="appointments.php?id=<?= (int) $apt['appointment_id'] ?>" class="btn btn-sm btn-outline-secondary">Manage</a>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                                    <th>Applied</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentApps)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">No applications yet.</td>
                                    </tr>
                                <?php else: foreach ($recentApps as $app): ?>
                                    <tr>
                                        <td class="text-muted small">#<?= (int) $app['application_id'] ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars($app['applicant_name'] ?? 'Applicant') ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($app['email'] ?? '') ?></small>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($app['job_title'] ?? '(deleted)') ?><br>
                                            <small class="text-muted"><?= htmlspecialchars($app['company_name'] ?? '') ?></small>
                                        </td>
                                        <td><?= date('d M Y', strtotime($app['created_at'] ?? '')) ?></td>
                                        <td>
                                            <span class="<?= $statusColors[$app['status'] ?? 'pending'] ?? 'badge bg-secondary' ?>">
                                                <?= ucfirst($app['status'] ?? 'pending') ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="applications.php?view=<?= (int) $app['application_id'] ?>" class="btn btn-sm btn-outline-secondary">Review</a>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
</main>

<!-- ==================== CHARTS JS ==================== -->
<script>
const chartDefaults = {
    responsive: true,
    plugins: { legend: { position: 'bottom' } }
};

/* Monthly trend (line) */
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
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
}

/* Applications by status (donut) */
const statusCtx = document.getElementById('chartStatus');
if (statusCtx) {
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: <?= $chartAppLabels ?>,
            datasets: [{
                data: <?= $chartAppData ?>,
                backgroundColor: ['#ffc107', '#0dcaf0', '#198754', '#dc3545', '#6c757d'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: chartDefaults
    });
}

/* Jobs by category (horizontal bar) */
const catCtx = document.getElementById('chartCategory');
if (catCtx) {
    new Chart(catCtx, {
        type: 'bar',
        data: {
            labels: <?= $chartCatLabels ?>,
            datasets: [{
                label: 'My Jobs',
                data: <?= $chartCatData ?>,
                backgroundColor: 'rgba(13,110,253,.7)',
                borderRadius: 6
            }]
        },
        options: {
            ...chartDefaults,
            indexAxis: 'y',
            scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } },
            plugins: { legend: { display: false } }
        }
    });
}
</script>

</body>
</html>