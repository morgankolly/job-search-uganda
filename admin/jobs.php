<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/config/connection.php';
require_once __DIR__ . '/helpers/functions.php';
require_once __DIR__ . '/models/JobModel.php';

// Auth gate — redirect before any output if not logged in.
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }

$jobModel = new JobModel($pdo);
$notice   = '';

/* ---------------- Handle guest job moderation ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guest_job_id'], $_POST['action'])) {

    $guestJobId = (int) $_POST['guest_job_id'];
    $action     = $_POST['action'];

    $map = [
        'approve' => 'approved',
        'reject'  => 'rejected',
        'close'   => 'closed',
        'pending' => 'pending',
    ];

    if (isset($map[$action]) && $jobModel->updateGuestJobStatus($guestJobId, $map[$action])) {

        $notice = "Guest job #{$guestJobId} marked as {$map[$action]}.";

        // Notify the employer when their job goes live.
        if ($action === 'approve') {
            $guestJob = $jobModel->getGuestJobById($guestJobId);
            if ($guestJob && function_exists('sendEmail')) {
                $body = "
                    <div style='font-family:Arial,sans-serif;'>
                        <h2>Hello " . htmlspecialchars($guestJob['contact_person']) . ",</h2>
                        <p>Your job <strong>" . htmlspecialchars($guestJob['job_title']) . "</strong>
                        has been approved and is now live on Job Search Uganda.</p>
                        <p>Reference: " . htmlspecialchars($guestJob['job_reference'] ?? '') . "</p>
                    </div>
                ";
                @sendEmail($guestJob['email'], "Your job is now live", $body);
            }
        }
    } else {
        $notice = "Could not update guest job #{$guestJobId}.";
    }
}

$guestJobs    = $jobModel->getAllGuestJobsForAdmin();
$employerJobs = $jobModel->getAllEmployerJobs();

$statusColors = [
    'pending'  => 'warning',
    'approved' => 'success',
    'rejected' => 'danger',
    'closed'   => 'secondary',
    'Open'     => 'success',
    'Closed'   => 'secondary',
];

$pageTitle = 'Manage Jobs';
include_once __DIR__ . '/components/header.php';
?>
<div class="container-fluid py-4 px-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold">Manage Jobs</h4>
        <a href="job-post.php" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Post New Job</a>
    </div>

    <?php if ($notice): ?>
        <div class="alert alert-info"><?= htmlspecialchars($notice) ?></div>
    <?php endif; ?>

    <!-- Guest jobs -->
    <div class="card shadow-sm mb-5">
        <div class="card-header bg-white">
            <h5 class="mb-0">Guest Jobs (need approval)</h5>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Company</th>
                        <th>Contact</th>
                        <th>Apps</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($guestJobs)): ?>
                    <tr><td colspan="7" class="text-center text-muted">No guest jobs.</td></tr>
                <?php else: foreach ($guestJobs as $g): ?>
                    <tr>
                        <td><?= (int) $g['job_id'] ?></td>
                        <td><?= htmlspecialchars($g['job_title']) ?></td>
                        <td><?= htmlspecialchars($g['company_name']) ?></td>
                        <td>
                            <?= htmlspecialchars($g['contact_person']) ?><br>
                            <small class="text-muted"><?= htmlspecialchars($g['email']) ?></small>
                        </td>
                        <td><?= (int) $g['current_applications'] ?>/<?= (int) $g['max_applications'] ?></td>
                        <td>
                            <span class="badge bg-<?= $statusColors[$g['status']] ?? 'secondary' ?>">
                                <?= htmlspecialchars($g['status']) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="guest_job_id" value="<?= (int) $g['job_id'] ?>">
                                <?php if ($g['status'] !== 'approved'): ?>
                                    <button name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
                                <?php endif; ?>
                                <?php if ($g['status'] !== 'rejected'): ?>
                                    <button name="action" value="reject" class="btn btn-danger btn-sm">Reject</button>
                                <?php endif; ?>
                                <?php if ($g['status'] === 'approved'): ?>
                                    <button name="action" value="close" class="btn btn-secondary btn-sm">Close</button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Employer jobs -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Employer Jobs</h5>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Company</th>
                        <th>Category</th>
                        <th>Apps</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($employerJobs)): ?>
                    <tr><td colspan="6" class="text-center text-muted">No employer jobs.</td></tr>
                <?php else: foreach ($employerJobs as $j): ?>
                    <tr>
                        <td><?= (int) $j['job_id'] ?></td>
                        <td><?= htmlspecialchars($j['job_title']) ?></td>
                        <td><?= htmlspecialchars($j['company_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($j['category_name'] ?? '') ?></td>
                        <td><?= (int) $j['current_applications'] ?>/<?= (int) $j['max_applications'] ?></td>
                        <td>
                            <span class="badge bg-<?= $statusColors[$j['status']] ?? 'secondary' ?>">
                                <?= htmlspecialchars($j['status']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</body>
</html>
