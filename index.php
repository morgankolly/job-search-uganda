<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
require_once __DIR__ . '/admin/config/connection.php';
require_once __DIR__ . '/admin/helpers/functions.php';
require_once __DIR__ . '/admin/models/JobModel.php';


$jobModel = new JobModel($pdo);


// Employer jobs that are open.
$jobs = $jobModel->getAllOpenJobs();

// Only approved guest jobs are shown publicly.
$guestJobs = $jobModel->getApprovedGuestJobs();
$jobCategories = $jobModel->getJobCategories();


// Tag employer jobs so detail/apply links know their source.
foreach ($jobs as &$job) {
    $job['source'] = 'jobs';
}
unset($job);


foreach ($guestJobs as &$guestJob) {

    $guestJob['company_name']   = $guestJob['company_name'] ?? '';
    $guestJob['contact_person'] = $guestJob['contact_person'] ?? '';
    $guestJob['email']          = $guestJob['email'] ?? '';
    $guestJob['phone']          = $guestJob['phone'] ?? '';

    $guestJob['employer'] = $guestJob['company_name'];
    $guestJob['source']   = 'guest';
}

unset($guestJob);


$jobs = array_merge($jobs, $guestJobs);



usort($jobs, function ($a, $b) {

    return strtotime($b['created_at']) - strtotime($a['created_at']);

});
?>
<!DOCTYPE html>
<html lang="en">

<head>


    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Search System</title>
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
    <link rel="stylesheet" href="assets/css/custom.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- end::GXON CSS Stylesheet -->

  

</head>
<body>

<div class="container py-5">

    <!-- System Process / How it Works Header -->
    <div class="row g-4 mb-5 text-center">
        <div class="col-12">
            <h3 class="fw-bold">How Our System Works</h3>
            <p class="text-muted">Follow three simple steps to secure your next career opportunity.</p>
        </div>
        
        <div class="col-md-4">
            <div class="card h-100 border-0 bg-light p-3">
                <div class="card-body">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px; font-weight: bold; font-size: 1.25rem;">
                        1
                    </div>
                    <h5 class="fw-bold">Filter & Search</h5>
                    <p class="text-muted small mb-0">Use the search bar, job categories, or job types below to instantly narrow down positions that match your skills.</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 border-0 bg-light p-3">
                <div class="card-body">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px; font-weight: bold; font-size: 1.25rem;">
                        2
                    </div>
                    <h5 class="fw-bold">Review Details</h5>
                    <p class="text-muted small mb-0">Click "View Details" on any job card to read the full description, company requirements, salary info, and deadline dates.</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 border-0 bg-light p-3">
                <div class="card-body">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px; font-weight: bold; font-size: 1.25rem;">
                        3
                    </div>
                    <h5 class="fw-bold">Apply Instantly</h5>
                    <p class="text-muted small mb-0">Hit "Apply Now" to submit your application directly to the employer or reach out using the provided contact info.</p>
                </div>
            </div>
        </div>
    </div>

    <hr class="mb-5 text-muted opacity-25">

    <!-- Existing Job Opportunities Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Job Opportunities</h2>
            <p class="text-muted">Browse available jobs and apply.</p>
        </div>

        <span class="badge bg-primary fs-6">
            <?= count($jobs) ?> Jobs Available
        </span>
    </div>

    <!-- Search & Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <input type="text"
                           id="searchJob"
                           class="form-control"
                           placeholder="Search job title...">
                </div>

                <div class="col-md-4">
                    <select id="typeFilter" class="form-select">
                        <option value="">All Job Types</option>
                        <?php
                        $stmt = $pdo->query("
                            SELECT DISTINCT type_name
                            FROM job_types
                            ORDER BY type_name
                        ");
                        while($type = $stmt->fetch(PDO::FETCH_ASSOC)):
                        ?>
                        <option value="<?= strtolower($type['type_name']) ?>">
                            <?= htmlspecialchars($type['type_name']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <select id="categoryFilter" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach($jobCategories as $category): ?>
                            <option value="<?= strtolower($category['category_name']) ?>">
                                <?= htmlspecialchars($category['category_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Job Cards Row -->
    <div class="row">
    <?php if(!empty($jobs)): ?>
        <?php foreach($jobs as $job): ?>
        <div class="col-lg-4 col-md-6 mb-4 job-card"
            data-title="<?= strtolower($job['job_title'] ?? '') ?>"
            data-type="<?= strtolower($job['job_type'] ?? '') ?>"
            data-category="<?= strtolower($job['category_name'] ?? '') ?>">

            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="fw-bold">
                        <?= htmlspecialchars($job['job_title'] ?? '') ?>
                    </h5>
                    <span class="badge bg-success mb-3">
                        <?= htmlspecialchars($job['job_type'] ?? '') ?>
                    </span>
                    <p><strong>Employer:</strong> <?= htmlspecialchars($job['employer'] ?? $job['company_name'] ?? 'N/A') ?></p>
                    <?php if(!empty($job['contact_person'])): ?><p><strong>Contact:</strong> <?= htmlspecialchars($job['contact_person']) ?></p><?php endif; ?>
                    <?php if(!empty($job['email'])): ?><p><strong>Email:</strong> <?= htmlspecialchars($job['email']) ?></p><?php endif; ?>
                    <?php if(!empty($job['phone'])): ?><p><strong>Phone:</strong> <?= htmlspecialchars($job['phone']) ?></p><?php endif; ?>
                    <p><strong>Category:</strong> <?= htmlspecialchars($job['category_name'] ?? '') ?></p>
                    <p><strong>Location:</strong> <?= htmlspecialchars($job['location'] ?? '') ?></p>
                    <p><strong>Salary:</strong> <?= htmlspecialchars($job['salary'] ?? 'Negotiable') ?></p>
                    <p><?= htmlspecialchars(substr($job['description'] ?? '',0,120)) ?>...</p>
                    <?php if(!empty($job['deadline'])): ?>
                    <p class="text-danger"><strong>Deadline:</strong> <?= date('d M Y',strtotime($job['deadline'])) ?></p>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-white">
                    <a href="job-details.php?id=<?= $job['job_id'] ?>&source=<?= $job['source'] ?? 'jobs' ?>" class="btn btn-outline-primary w-100 mb-2">View Details</a>
                    <a href="apply-job.php?id=<?= $job['job_id'] ?>&source=<?= $job['source'] ?? 'jobs' ?>" class="btn btn-primary w-100">Apply Now</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-warning">No jobs available.</div>
        </div>
    <?php endif; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
   
</body>
<script>
const searchInput = document.getElementById("searchJob");
const typeFilter = document.getElementById("typeFilter");
const categoryFilter = document.getElementById("categoryFilter");

searchInput.addEventListener("keyup", filterJobs);
typeFilter.addEventListener("change", filterJobs);
categoryFilter.addEventListener("change", filterJobs);

function filterJobs() {

    let search = searchInput.value.toLowerCase();
    let type = typeFilter.value.toLowerCase();
    let category = categoryFilter.value.toLowerCase();

    let jobs = document.querySelectorAll(".job-card");

    jobs.forEach(job => {

        let title = job.dataset.title;
        let jobType = job.dataset.type;
        let jobCategory = job.dataset.category;

        let visible = true;

        if (search !== "" && !title.includes(search))
            visible = false;

        if (type !== "" && jobType !== type)
            visible = false;

        if (category !== "" && jobCategory !== category)
            visible = false;

        job.style.display = visible ? "block" : "none";

    });

}
</script>
</html>