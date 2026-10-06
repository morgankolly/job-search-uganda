<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/admin/config/connection.php';
require_once __DIR__ . '/admin/helpers/functions.php';
require_once __DIR__ . '/admin/models/JobModel.php';
require_once __DIR__ . '/admin/models/ApplicationModel.php';
require_once __DIR__ . '/admin/components/Userheader.php';

$jobModel = new JobModel($pdo);
$applicationModel = new ApplicationModel($pdo);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$source = ($_GET['source'] ?? 'jobs') === 'guest' ? 'guest' : 'jobs';

$error = '';
$success = '';

/* ---------------- Load the job & availability ---------------- */

$job       = null;
$available = false;

if ($id > 0) {
    if ($source === 'guest') {
        $job = $jobModel->getGuestJobById($id);
        $available = $job && $job['status'] === 'approved' && $job['current_applications'] < $job['max_applications'];
    } else {
        $job = $jobModel->getJobById($id);
        $available = $job && $job['status'] === 'Open' && $job['current_applications'] < $job['max_applications'];
    }
}

/* ---------------- Handle submission ---------------- */

require_once __DIR__ . '/admin/controllers/JobController.php';

?>  
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply - <?= $job ? htmlspecialchars($job['job_title']) : 'Job' ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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

    <link rel="stylesheet" href="assets/css/styles.css">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- end::GXON CSS Stylesheet -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body class="bg-light">

    <div class="container-expand-lg py-5">

        <div class="row justify-content-center">

            <div class="col-lg-20 ">

                <div class="card shadow">

                    <div class="card-header  text-white">

                <a href="index.php" class="btn btn-link mb-3 ps-0">&larr; Back to all jobs</a>

                <?php if (!$job): ?>
                    <div class="alert alert-danger">This job could not be found.</div>

                <?php elseif ($success): ?>
                    <div class="alert alert-success">
                        <h4 class="mb-1">Application submitted</h4>
                        <p class="mb-0"><?= htmlspecialchars($success) ?></p>
                    </div>
                    <a href="index.php" class="btn btn-primary">Browse more jobs</a>

                <?php else: ?>

                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h3 class="mb-0">Apply for <?= htmlspecialchars($job['job_title']) ?></h3>
                            <small><?= htmlspecialchars($job['company_name'] ?? $job['employer'] ?? '') ?></small>
                        </div>

                        <div class="card-body">

                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                            <?php endif; ?>

                            <?php if (!$available): ?>
                                <div class="alert alert-warning">
                                    Applications for this job are currently closed.
                                </div>
                            <?php else: ?>

                                <form method="POST" enctype="multipart/form-data">

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Full Name</label>
                                            <input type="text" name="applicant_name" class="form-control"
                                                value="<?= htmlspecialchars($_POST['applicant_name'] ?? '') ?>" required>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email Address</label>
                                            <input type="email" name="email" class="form-control"
                                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Phone Number</label>
                                            <input type="text" name="phone" class="form-control"
                                                value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">CV / Resume (PDF, DOC, DOCX)</label>
                                            <input type="file" name="cv" class="form-control" accept=".pdf,.doc,.docx">
                                        </div>

                                        <div class="col-12 mb-3">
                                            <label class="form-label">Cover Letter</label>
                                            <textarea name="cover_letter" rows="5"
                                                class="form-control"><?= htmlspecialchars($_POST['cover_letter'] ?? '') ?></textarea>
                                        </div>
                                    </div>

                                    <div class="text-end">
                                        <button type="submit" name="applyJob" class="btn btn-primary btn-lg">
                                            Submit Application
                                        </button>
                                    </div>

                                </form>

                            <?php endif; ?>

                        </div>
                    </div>

                <?php endif; ?>

            </div>
        </div>

    </div>

</body>

</html>