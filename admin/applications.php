<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/config/connection.php';
require_once __DIR__ . '/helpers/functions.php';
require_once __DIR__ . '/models/ApplicationModel.php';

// Auth gate.
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }

$applicationModel = new ApplicationModel($pdo);
$notice = '';

/* ---------------- Handle status update ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['application_id'], $_POST['status'])) {
    $appId  = (int) $_POST['application_id'];
    $status = $_POST['status'];

    if ($applicationModel->updateStatus($appId, $status)) {
        $notice = "Application #{$appId} updated to {$status}.";
    } else {
        $notice = "Could not update application #{$appId}.";
    }
}

$applications = $applicationModel->getAllApplications();

$statusColors = [
    'pending'     => 'warning',
    'reviewed'    => 'info',
    'shortlisted' => 'success',
    'rejected'    => 'danger',
];

// CV paths are stored relative to the project root; admin/ is one level down.
function cvUrl(?string $path): ?string
{
    if (!$path) {
        return null;
    }
    return '../' . ltrim($path, '/');
}

$pageTitle = 'Applications';
include_once __DIR__ . '/components/header.php';
?>
<div class="container-fluid py-4 px-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold">Applications</h4>
    </div>

    <?php if ($notice): ?>
        <div class="alert alert-info"><?= htmlspecialchars($notice) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Applicant</th>
                        <th>Job</th>
                        <th>Contact</th>
                        <th>CV</th>
                        <th>Applied</th>
                        <th>Status</th>
                        <th class="text-end">Update</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($applications)): ?>
                    <tr><td colspan="8" class="text-center text-muted">No applications yet.</td></tr>
                <?php else: foreach ($applications as $a): ?>
                    <tr>
                        <td><?= (int) $a['application_id'] ?></td>
                        <td><?= htmlspecialchars($a['applicant_name']) ?></td>
                        <td>
                            <?= htmlspecialchars($a['job_title'] ?? '(deleted job)') ?><br>
                            <small class="text-muted"><?= htmlspecialchars($a['company_name'] ?? '') ?></small>
                        </td>
                        <td>
                            <?= htmlspecialchars($a['email']) ?><br>
                            <small class="text-muted"><?= htmlspecialchars($a['phone'] ?? '') ?></small>
                        </td>
                        <td>
                            <?php if ($cv = cvUrl($a['cv_path'])): ?>
                                <a href="<?= htmlspecialchars($cv) ?>" target="_blank" class="btn btn-sm btn-outline-primary">View CV</a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d M Y', strtotime($a['created_at'])) ?></td>
                        <td>
                            <span class="badge bg-<?= $statusColors[$a['status']] ?? 'secondary' ?>">
                                <?= htmlspecialchars($a['status']) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <form method="POST" class="d-flex gap-1 justify-content-end">
                                <input type="hidden" name="application_id" value="<?= (int) $a['application_id'] ?>">
                                <select name="status" class="form-select form-select-sm w-auto">
                                    <?php foreach (array_keys($statusColors) as $s): ?>
                                        <option value="<?= $s ?>" <?= $a['status'] === $s ? 'selected' : '' ?>>
                                            <?= ucfirst($s) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-sm btn-primary">Save</button>
                            </form>
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
