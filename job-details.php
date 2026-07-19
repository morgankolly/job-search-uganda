<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/admin/config/connection.php';
require_once __DIR__ . '/admin/helpers/functions.php';
require_once __DIR__ . '/admin/models/JobModel.php';

$jobModel = new JobModel($pdo);

$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$source = ($_GET['source'] ?? 'jobs') === 'guest' ? 'guest' : 'jobs';

$job       = null;
$available = false;

if ($id > 0) {
    if ($source === 'guest') {
        $job = $jobModel->getGuestJobById($id);
        // Guest jobs must be approved (and not closed/full) to be visible.
        $available = $job
            && $job['status'] === 'approved'
            && $job['current_applications'] < $job['max_applications'];
    } else {
        $job = $jobModel->getJobById($id);
        $available = $job
            && $job['status'] === 'Open'
            && $job['current_applications'] < $job['max_applications'];
    }
}

// Normalise the fields used by the view.
if ($job) {
    $job['job_type_label'] = $job['job_type_name'] ?? '';
    $job['employer_label'] = $job['employer'] ?? $job['company_name'] ?? 'N/A';
    $remaining = max(0, (int) $job['max_applications'] - (int) $job['current_applications']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $job ? htmlspecialchars($job['job_title']) : 'Job Not Found' ?> - Job Search System</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/libs/fontawesome/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">

<div class="container py-5">

    <a href="index.php" class="btn btn-link mb-3 ps-0">&larr; Back to all jobs</a>

    <?php if (!$job): ?>
        <div class="alert alert-danger">
            <h4 class="mb-1">Job not found</h4>
            <p class="mb-0">This job may have been removed or is no longer available.</p>
        </div>
    <?php else: ?>

        <div class="card shadow-sm">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-start flex-wrap">
                    <div>
                        <h2 class="fw-bold mb-1"><?= htmlspecialchars($job['job_title']) ?></h2>
                        <p class="text-muted mb-2">
                            <?= htmlspecialchars($job['employer_label']) ?>
                        </p>
                    </div>
                    <?php if (!empty($job['job_type_label'])): ?>
                        <span class="badge bg-success fs-6"><?= htmlspecialchars($job['job_type_label']) ?></span>
                    <?php endif; ?>
                </div>

                <hr>

                <div class="row g-3 mb-3">
                    <div class="col-md-6"><strong>Category:</strong> <?= htmlspecialchars($job['category_name'] ?? 'N/A') ?></div>
                    <div class="col-md-6"><strong>Location:</strong> <?= htmlspecialchars($job['location'] ?? 'N/A') ?></div>
                    <div class="col-md-6"><strong>Salary:</strong> <?= htmlspecialchars($job['salary'] ?: 'Negotiable') ?></div>
                    <div class="col-md-6"><strong>Openings left:</strong> <?= (int) $remaining ?></div>
                    <?php if (!empty($job['deadline'])): ?>
                        <div class="col-md-6 text-danger">
                            <strong>Deadline:</strong> <?= date('d M Y', strtotime($job['deadline'])) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($source === 'guest' && !empty($job['contact_person'])): ?>
                        <div class="col-md-6"><strong>Contact:</strong> <?= htmlspecialchars($job['contact_person']) ?></div>
                    <?php endif; ?>
                </div>

                <h5 class="fw-bold mt-4">Job Description</h5>
                <p style="white-space: pre-line;"><?= nl2br(htmlspecialchars($job['description'] ?? '')) ?></p>

                <?php if (!empty($job['requirements'])): ?>
                    <h5 class="fw-bold mt-4">Requirements</h5>
                    <p style="white-space: pre-line;"><?= nl2br(htmlspecialchars($job['requirements'])) ?></p>
                <?php endif; ?>

            </div>

            <div class="card-footer bg-white">
                <?php if ($available): ?>
                    <a href="apply-job.php?id=<?= (int) $id ?>&source=<?= $source ?>"
                       class="btn btn-primary btn-lg">Apply Now</a>
                <?php else: ?>
                    <button class="btn btn-secondary btn-lg" disabled>
                        Applications Closed
                    </button>
                <?php endif; ?>
            </div>
        </div>

    <?php endif; ?>

</div>

</body>
</html>
