r<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/connection.php';
require_once __DIR__ . '/helpers/functions.php';
require_once __DIR__ . '/models/JobModel.php';

// Auth gate — redirect if no session.
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Verify the session user actually exists in the current DB.
// Catches stale sessions after a DB re-import or reimport.
$_chk = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ? LIMIT 1");
$_chk->execute([$_SESSION['user_id']]);
if (!$_chk->fetch()) {
    // Session is pointing to a user that no longer exists — destroy it.
    session_unset();
    session_destroy();
    header('Location: index.php?reason=session_expired');
    exit;
}

$jobModel = new JobModel($pdo);

$error   = '';
$success = '';

/* ── Load lookups ─────────────────────────────────────────── */
$categories = $pdo->query(
    "SELECT category_id, category_name FROM job_categories ORDER BY category_name ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$jobTypes = $pdo->query(
    "SELECT type_id, type_name FROM job_types WHERE status = 'active' ORDER BY type_name ASC"
)->fetchAll(PDO::FETCH_ASSOC);

/* ── Handle POST ──────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['createJob'])) {

    $job_title        = trim($_POST['job_title']        ?? '');
    $company_name     = trim($_POST['company_name']     ?? '');
    $category_id      = trim($_POST['job_category']     ?? '');
    $location         = trim($_POST['location']         ?? '');
    $salary           = trim($_POST['salary']           ?? '');
    $job_type         = trim($_POST['job_type']         ?? '');
    $deadline         = $_POST['deadline']              ?? '';
    $max_applications = max(1, (int) ($_POST['max_applications'] ?? 50));
    $description      = trim($_POST['description']      ?? '');
    $requirements     = trim($_POST['requirements']     ?? '');

    // Validate required fields.
    if (!$job_title || !$company_name || !$category_id || !$job_type || !$description) {
        $error = "Please fill in all required fields (Job Title, Company, Category, Job Type, Description).";
    }

    // Handle new category.
    if (!$error && $category_id === 'new') {
        $newCat = trim($_POST['new_category'] ?? '');
        if (!$newCat) {
            $error = "Please enter a name for the new category.";
        } else {
            $chk = $pdo->prepare("SELECT category_id FROM job_categories WHERE category_name = ?");
            $chk->execute([$newCat]);
            if ($row = $chk->fetch()) {
                $category_id = $row['category_id'];
            } else {
                $ins = $pdo->prepare("INSERT INTO job_categories (category_name) VALUES (?)");
                $ins->execute([$newCat]);
                $category_id = $pdo->lastInsertId();
            }
        }
    }

    if (!$error) {
        $ok = $jobModel->createJob(
            $_SESSION['user_id'],
            $job_title,
            $company_name,
            $category_id,
            $location,
            $salary,
            $job_type,
            $description,
            $requirements,
            $deadline ?: null,
            $max_applications
        );

        if ($ok) {
            $success = "Job posted successfully! It is now live on the public board.";
            // Clear values after success.
            $_POST = [];
        } else {
            $error = "Database error — failed to save the job. Please try again.";
        }
    }
}

/* ── Dashboard stats for sidebar ─────────────────────────── */
$totalJobs  = (int) $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
$openJobs   = (int) $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'Open'")->fetchColumn();
$totalApps  = (int) $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();

$pageTitle = 'Post a Job';
include_once __DIR__ . '/components/header.php';
?>



<div class="container-fluid py-4 px-4">

    <!-- Page heading -->
    <div class="mb-4">
        <h4 class="fw-bold mb-0">Post a New Job</h4>
        <p class="text-muted small mb-0">Fill in the details below — the listing goes live instantly.</p>
    </div>

    <?php if ($success): ?>
    <!-- ── Success banner ── -->
    <div class="alert alert-success d-flex align-items-start gap-3 shadow-sm rounded-3 mb-4" role="alert">
        <div style="font-size:1.6rem; line-height:1;">✅</div>
        <div>
            <div class="fw-bold mb-1">Job posted!</div>
            <?= htmlspecialchars($success) ?><br>
            <a href="jobs.php" class="btn btn-sm btn-success mt-2 me-2">
                <i class="fas fa-list me-1"></i>View All Jobs
            </a>
            <a href="job-post.php" class="btn btn-sm btn-outline-success mt-2">
                <i class="fas fa-plus me-1"></i>Post Another
            </a>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger d-flex gap-2 align-items-center rounded-3 mb-4">
        <i class="fas fa-exclamation-circle"></i>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- ═══════════════════ FORM (left) ═══════════════════ -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 rounded-3">

                <div class="card-body p-4 p-lg-5">
                    <form method="POST" id="jobForm" novalidate>

                        <!-- ── SECTION 1: Basic Info ── -->
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-building text-indigo-400"></i> Company & Role
                            </div>

                            <div class="row g-3">
                                <div class="col-md-7">
                                    <label class="form-label">
                                        Job Title <span class="req">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-briefcase"></i></span>
                                        <input type="text" name="job_title" id="jobTitleInput"
                                               class="form-control"
                                               placeholder="e.g. Senior Software Engineer"
                                               value="<?= htmlspecialchars($_POST['job_title'] ?? '') ?>"
                                               maxlength="150" required>
                                    </div>
                                </div>

                                <div class="col-md-5">
                                    <label class="form-label">
                                        Company Name <span class="req">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-city"></i></span>
                                        <input type="text" name="company_name" id="companyInput"
                                               class="form-control"
                                               placeholder="e.g. Acme Ltd"
                                               value="<?= htmlspecialchars($_POST['company_name'] ?? '') ?>"
                                               maxlength="150" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">
                                        Job Category <span class="req">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                        <select name="job_category" id="job_category"
                                                class="form-select" required
                                                style="border-radius: 0 8px 8px 0;">
                                            <option value="">Select a category…</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= $cat['category_id'] ?>"
                                                    <?= (($_POST['job_category'] ?? '') == $cat['category_id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($cat['category_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                            <option value="new" <?= (($_POST['job_category'] ?? '') === 'new') ? 'selected' : '' ?>>
                                                ＋ Add new category…
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6" id="newCategoryDiv"
                                     style="display:<?= (($_POST['job_category'] ?? '') === 'new') ? 'block' : 'none' ?>;">
                                    <label class="form-label">New Category Name <span class="req">*</span></label>
                                    <input type="text" name="new_category" id="new_category"
                                           class="form-control"
                                           placeholder="Enter new category"
                                           value="<?= htmlspecialchars($_POST['new_category'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <!-- ── SECTION 2: Job Details ── -->
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-sliders-h"></i> Position Details
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">
                                        Job Type <span class="req">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-clock"></i></span>
                                        <select name="job_type" class="form-select" required
                                                style="border-radius: 0 8px 8px 0;">
                                            <option value="">Select type…</option>
                                            <?php foreach ($jobTypes as $type): ?>
                                                <option value="<?= $type['type_id'] ?>"
                                                    <?= (($_POST['job_type'] ?? '') == $type['type_id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($type['type_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Location</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                        <input type="text" name="location"
                                               class="form-control"
                                               placeholder="e.g. Kampala, Uganda"
                                               value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Salary / Range</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-coins"></i></span>
                                        <input type="text" name="salary"
                                               class="form-control"
                                               placeholder="UGX 1,500,000"
                                               value="<?= htmlspecialchars($_POST['salary'] ?? '') ?>">
                                    </div>
                                    <small class="text-muted">Leave blank for "Negotiable".</small>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">
                                        Max Applications <span class="req">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-users"></i></span>
                                        <input type="number" name="max_applications"
                                               class="form-control" min="1" max="9999"
                                               value="<?= htmlspecialchars($_POST['max_applications'] ?? '50') ?>"
                                               required>
                                    </div>
                                    <small class="text-muted">Job auto-closes at this limit.</small>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Application Deadline</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                        <input type="date" name="deadline"
                                               class="form-control"
                                               min="<?= date('Y-m-d') ?>"
                                               value="<?= htmlspecialchars($_POST['deadline'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── SECTION 3: Content ── -->
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-align-left"></i> Job Content
                            </div>

                            <div class="mb-3">
                                <label class="form-label d-flex justify-content-between">
                                    <span>Job Description <span class="req">*</span></span>
                                    <span class="char-count"><span id="descCount">0</span>/2000</span>
                                </label>
                                <textarea name="description" id="descInput"
                                          class="form-control" rows="7"
                                          maxlength="2000"
                                          placeholder="Describe the role, responsibilities, and what a typical day looks like…"
                                          required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-0">
                                <label class="form-label d-flex justify-content-between">
                                    <span>Requirements & Qualifications</span>
                                    <span class="char-count"><span id="reqCount">0</span>/2000</span>
                                </label>
                                <textarea name="requirements" id="reqInput"
                                          class="form-control" rows="6"
                                          maxlength="2000"
                                          placeholder="List the skills, qualifications, and experience needed…"><?= htmlspecialchars($_POST['requirements'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <!-- ── Submit ── -->
                        <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-4">
                            <button type="reset" class="btn btn-outline-secondary px-4"
                                    onclick="resetPreview()">
                                <i class="fas fa-undo me-1"></i>Clear
                            </button>
                            <button type="submit" name="createJob"
                                    class="btn btn-primary px-5 py-2 fw-semibold"
                                    style="border-radius: 10px; letter-spacing: .03em;">
                                <i class="fas fa-paper-plane me-2"></i>Publish Job
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <!-- ═══════════════════ SIDEBAR (right) ═══════════════════ -->
        <div class="col-lg-4">

            <!-- Live Preview Card -->
            <div class="card border-0 shadow-sm rounded-3 mb-4" id="previewCard">
                <div class="card-header bg-white border-0 pb-0 pt-4 px-4">
                    <div class="section-pill"><i class="fas fa-eye"></i> Live Preview</div>
                    <p class="text-muted small mb-0">Updates as you type.</p>
                </div>
                <div class="card-body px-4 pb-4">
                    <div id="previewJobTitle" class="mb-1">—</div>
                    <div id="previewCompany" class="mb-2">—</div>
                    <div id="previewBadges"></div>
                    <hr class="my-3">
                    <div id="previewMeta" class="small text-muted" style="line-height:1.9;">
                        <div><i class="fas fa-map-marker-alt me-2 text-primary"></i><span id="previewLocation">—</span></div>
                        <div><i class="fas fa-coins me-2 text-success"></i><span id="previewSalary">Negotiable</span></div>
                        <div><i class="fas fa-users me-2 text-info"></i>Up to <span id="previewMax">50</span> applicants</div>
                        <div id="previewDeadlineRow" style="display:none;">
                            <i class="fas fa-calendar me-2 text-danger"></i>Deadline: <span id="previewDeadline"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Card -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-body px-4 py-3">
                    <div class="section-pill mb-3"><i class="fas fa-chart-bar"></i> Board Stats</div>
                    <div class="row g-0 text-center">
                        <div class="col-4 stat-mini">
                            <div class="num text-primary"><?= $totalJobs ?></div>
                            <div class="lbl">Total Jobs</div>
                        </div>
                        <div class="col-4 stat-mini" style="border-left:1px solid #f3f4f6; border-right:1px solid #f3f4f6;">
                            <div class="num text-success"><?= $openJobs ?></div>
                            <div class="lbl">Open</div>
                        </div>
                        <div class="col-4 stat-mini">
                            <div class="num text-info"><?= $totalApps ?></div>
                            <div class="lbl">Applications</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tips Card -->
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body px-4 pt-4 pb-3">
                    <div class="section-pill mb-3"><i class="fas fa-lightbulb"></i> Posting Tips</div>

                    <div class="tip-item">
                        <div class="tip-icon bg-primary bg-opacity-10 text-primary">
                            <i class="fas fa-heading"></i>
                        </div>
                        <div>
                            <div class="fw-semibold" style="font-size:.82rem;">Be specific with the title</div>
                            <div class="text-muted" style="font-size:.77rem;">
                                "Senior PHP Developer" attracts better candidates than "Developer".
                            </div>
                        </div>
                    </div>

                    <div class="tip-item">
                        <div class="tip-icon bg-success bg-opacity-10 text-success">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div>
                            <div class="fw-semibold" style="font-size:.82rem;">Show the salary</div>
                            <div class="text-muted" style="font-size:.77rem;">
                                Jobs with salary ranges receive 35% more applications on average.
                            </div>
                        </div>
                    </div>

                    <div class="tip-item">
                        <div class="tip-icon bg-warning bg-opacity-10 text-warning">
                            <i class="fas fa-list-ul"></i>
                        </div>
                        <div>
                            <div class="fw-semibold" style="font-size:.82rem;">Use bullet points</div>
                            <div class="text-muted" style="font-size:.77rem;">
                                Break the description into short bullets — easier to scan on mobile.
                            </div>
                        </div>
                    </div>

                    <div class="tip-item">
                        <div class="tip-icon bg-info bg-opacity-10 text-info">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div>
                            <div class="fw-semibold" style="font-size:.82rem;">Set a realistic deadline</div>
                            <div class="text-muted" style="font-size:.77rem;">
                                2–4 weeks gives enough time without rushing applicants.
                            </div>
                        </div>
                    </div>

                    <div class="tip-item">
                        <div class="tip-icon bg-danger bg-opacity-10 text-danger">
                            <i class="fas fa-check-double"></i>
                        </div>
                        <div>
                            <div class="fw-semibold" style="font-size:.82rem;">Keep requirements realistic</div>
                            <div class="text-muted" style="font-size:.77rem;">
                                List only what's truly needed — too many requirements reduce quality applications.
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div><!-- /sidebar -->

    </div><!-- /row -->
</div><!-- /container -->
<?php
include_once __DIR__ . '/components/footer.php';

?>
<script>
/* ── Live preview ──────────────────────────────────── */
const previewTitle    = document.getElementById('previewJobTitle');
const previewCompany  = document.getElementById('previewCompany');
const previewBadges   = document.getElementById('previewBadges');
const previewLocation = document.getElementById('previewLocation');
const previewSalary   = document.getElementById('previewSalary');
const previewMax      = document.getElementById('previewMax');
const previewDeadline = document.getElementById('previewDeadline');
const previewDeadlineRow = document.getElementById('previewDeadlineRow');

function updatePreview() {
    // Title
    const t = document.getElementById('jobTitleInput').value.trim();
    previewTitle.textContent = t || '—';

    // Company
    const c = document.getElementById('companyInput').value.trim();
    previewCompany.textContent = c || '—';

    // Badges (type + category)
    previewBadges.innerHTML = '';
    const typeSelect = document.querySelector('[name="job_type"]');
    const catSelect  = document.getElementById('job_category');
    if (typeSelect && typeSelect.value) {
        const badge = document.createElement('span');
        badge.className = 'badge bg-success';
        badge.textContent = typeSelect.options[typeSelect.selectedIndex].text;
        previewBadges.appendChild(badge);
    }
    if (catSelect && catSelect.value && catSelect.value !== 'new') {
        const badge = document.createElement('span');
        badge.className = 'badge bg-primary bg-opacity-75';
        badge.textContent = catSelect.options[catSelect.selectedIndex].text;
        previewBadges.appendChild(badge);
    }

    // Meta
    const loc = document.querySelector('[name="location"]').value.trim();
    previewLocation.textContent = loc || '—';

    const sal = document.querySelector('[name="salary"]').value.trim();
    previewSalary.textContent = sal || 'Negotiable';

    previewMax.textContent = document.querySelector('[name="max_applications"]').value || '50';

    const deadline = document.querySelector('[name="deadline"]').value;
    if (deadline) {
        const d = new Date(deadline);
        previewDeadline.textContent = d.toLocaleDateString('en-GB', {day:'numeric',month:'short',year:'numeric'});
        previewDeadlineRow.style.display = 'block';
    } else {
        previewDeadlineRow.style.display = 'none';
    }
}

// Bind all inputs.
document.querySelectorAll('#jobForm input, #jobForm select, #jobForm textarea').forEach(el => {
    el.addEventListener('input', updatePreview);
    el.addEventListener('change', updatePreview);
});
updatePreview();

/* ── Character counters ────────────────────────────── */
function initCounter(inputId, countId) {
    const el  = document.getElementById(inputId);
    const cnt = document.getElementById(countId);
    if (!el || !cnt) return;
    cnt.textContent = el.value.length;
    el.addEventListener('input', () => {
        cnt.textContent = el.value.length;
        cnt.style.color = el.value.length > el.maxLength * 0.9 ? '#ef4444' : '#9ca3af';
    });
}
initCounter('descInput', 'descCount');
initCounter('reqInput',  'reqCount');

/* ── New category toggle ───────────────────────────── */
const catSelect      = document.getElementById('job_category');
const newCategoryDiv = document.getElementById('newCategoryDiv');
const newCategoryInp = document.getElementById('new_category');

catSelect.addEventListener('change', function () {
    if (this.value === 'new') {
        newCategoryDiv.style.display = 'block';
        newCategoryInp.required = true;
    } else {
        newCategoryDiv.style.display = 'none';
        newCategoryInp.required = false;
        newCategoryInp.value = '';
    }
});

function resetPreview() {
    setTimeout(updatePreview, 50);
}
</script>

</body>
</html>
