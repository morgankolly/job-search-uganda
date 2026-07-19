<?php


error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
require_once __DIR__ . '/admin/config/connection.php';
require_once __DIR__ . '/admin/helpers/functions.php';
require_once __DIR__ . '/admin/models/JobModel.php';
require_once __DIR__ . '/admin/controllers/JobController.php';   
$message = "";

$stmt = $pdo->query("SELECT * FROM job_categories ORDER BY category_name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT type_id, type_name FROM job_types WHERE status = 'active' ORDER BY type_name ASC");
$stmt->execute();
$jobTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <title>Post Job Without Registration</title>

    
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

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Premium custom styling applied purely via CSS -->
    <style>
        body.bg-light {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%) !important;
            font-family: system-ui, -apple-system, sans-serif;
            color: #334155;
        }
        
       
    </style>

</head>

<body class="bg-light">

<!-- Attractive Hero Header & Job System Information -->
    <div class="system-hero">
        <div class="container">
            <h1>Connect with Exceptional Talent</h1>
            <p>Our seamless guest job board lets you list vacant opportunities without the friction of account setups or long registrations. Submitted jobs are reviewed by our team and go live once approved.</p>
            
            <!-- Key System Information Points -->
            <div class="info-features">
                <div class="feature-item">
                    <i class="fa-solid fa-user-slash"></i>
                    <div class="feature-text">
                        <h6>No Account Setup</h6>
                        <p>Skip signup entirely. Post in 2 minutes.</p>
                    </div>
                </div>
                <div class="feature-item">
                    <i class="fa-solid fa-bullhorn"></i>
                    <div class="feature-text">
                        <h6>Instant Visibility</h6>
                        <p>Targeted straight to active candidates.</p>
                    </div>
                </div>
                    <div class="feature-item">
                    <i class="fa-solid fa-envelope-open-text"></i>
                    <div class="feature-text">
                        <h6>Direct Responses</h6>
                        <p>Applications land straight in your email.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-5">

        <div class="row justify-content-center">

            <div class="col-lg-20 ">

                <div class="card shadow">

                    <div class="card-header bg-primary text-white">

                        <h3>Post a Job (Guest Employer)</h3>

                        <p class="mb-3">
                            No account required. Fill in the form below.
                        </p>

                    </div>

                    <div class="card-body">

                      <?php if (!empty($error)) : ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<?php if (!empty($success)) : ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

                     <form method="POST" enctype="multipart/form-data">

    <h5 class="mb-3">Employer Information</h5>

    <div class="row">

        <div class="col-md-6 mb-3">
            <label class="form-label">Company Name</label>
            <input type="text"
                name="company_name"
                class="form-control"
                value="<?= htmlspecialchars($_POST['company_name'] ?? '') ?>"
                required>
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label">Contact Person</label>
            <input type="text"
                name="contact_person"
                class="form-control"
                value="<?= htmlspecialchars($_POST['contact_person'] ?? '') ?>"
                required>
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label">Email Address</label>
            <input type="email"
                name="email"
                class="form-control"
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                required>
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label">Phone Number</label>
            <input type="text"
                name="phone"
                class="form-control"
                value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                required>
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label">Company Logo</label>
            <input type="file"
                name="logo"
                class="form-control"
                accept=".jpg,.jpeg,.png,.gif,.webp">
        </div>

    </div>

    <hr>

    <h5 class="mb-3">Job Details</h5>

    <div class="row">

        <div class="col-md-6 mb-3">
            <label class="form-label">Job Title</label>
            <input type="text"
                name="job_title"
                class="form-control"
                value="<?= htmlspecialchars($_POST['job_title'] ?? '') ?>"
                required>
        </div>

        <div class="col-md-6 mb-3">

            <label class="form-label">Job Category</label>

            <select
                name="job_category"
                id="job_category"
                class="form-select"
                required>

                <option value="">Select Category</option>

                <?php foreach ($categories as $category): ?>

                    <option
                        value="<?= $category['category_id']; ?>"
                        <?= (($_POST['job_category'] ?? '') == $category['category_id']) ? 'selected' : ''; ?>>

                        <?= htmlspecialchars($category['category_name']); ?>

                    </option>

                <?php endforeach; ?>

                <option value="new"
                    <?= (($_POST['job_category'] ?? '') == 'new') ? 'selected' : ''; ?>>
                    + Add New Category
                </option>

            </select>

        </div>

        <div class="col-md-6 mb-3" id="newCategoryBox" style="display:none;">

            <label class="form-label">New Category</label>

            <input type="text"
                name="new_category"
                class="form-control"
                value="<?= htmlspecialchars($_POST['new_category'] ?? '') ?>"
                placeholder="Enter new category">

        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label">Location</label>
            <input type="text"
                name="location"
                class="form-control"
                value="<?= htmlspecialchars($_POST['location'] ?? '') ?>"
                required>
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label">Maximum Applications</label>

            <input type="number"
                name="max_applications"
                class="form-control"
                min="1"
                value="<?= htmlspecialchars($_POST['max_applications'] ?? '50') ?>"
                required>

            <small class="text-muted">
                The job will automatically close after reaching this limit.
            </small>
        </div>

        <div class="col-md-6 mb-3">

            <label class="form-label">Job Type</label>

            <select
                name="job_type"
                class="form-select"
                required>

                <option value="">Select Job Type</option>

                <?php foreach ($jobTypes as $type): ?>

                    <option
                        value="<?= $type['type_id']; ?>"
                        <?= (($_POST['job_type'] ?? '') == $type['type_id']) ? 'selected' : ''; ?>>

                        <?= htmlspecialchars($type['type_name']); ?>

                    </option>

                <?php endforeach; ?>

            </select>

        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label">Salary</label>
            <input type="text"
                name="salary"
                class="form-control"
                value="<?= htmlspecialchars($_POST['salary'] ?? '') ?>">
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label">Application Deadline</label>
            <input type="date"
                name="deadline"
                class="form-control"
                value="<?= htmlspecialchars($_POST['deadline'] ?? '') ?>">
        </div>

        <div class="col-12 mb-3">
            <label class="form-label">Job Description</label>

            <textarea
                name="description"
                rows="6"
                class="form-control"
                required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <div class="col-12 mb-3">
            <label class="form-label">Requirements</label>

            <textarea
                name="requirements"
                rows="5"
                class="form-control"><?= htmlspecialchars($_POST['requirements'] ?? '') ?></textarea>
        </div>

    </div>

    <div class="text-end">

        <button
            type="submit"
            name="createGuestJob"
            class="btn btn-primary btn-lg">

            Submit Job

        </button>

    </div>

</form>

                    </div>

                </div>

            </div>

        </div>

    </div>

</body>
<script>

const category = document.getElementById('job_category');
const newCategoryBox = document.getElementById('newCategoryBox');

function toggleCategory(){

    if(category.value === 'new'){
        newCategoryBox.style.display = 'block';
    }else{
        newCategoryBox.style.display = 'none';
    }

}

category.addEventListener('change', toggleCategory);

toggleCategory();

</script>
</html>