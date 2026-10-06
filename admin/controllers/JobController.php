<?php
require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../models/JobModel.php';
require_once __DIR__ . '/../models/PaymentModel.php';
require_once __DIR__ . '/../models/ApplicationModel.php';

$jobModel = new JobModel($pdo);
$paymentModel = new PaymentModel($pdo);
$applicationModel = new ApplicationModel($pdo);
if (isset($_POST['createJob'])) {

    $employer_id = $_SESSION['user_id'];

    $job_title = trim($_POST['job_title']);
    $company_name = trim($_POST['company_name']);
    $category_id = trim($_POST['job_category']);
    $location = trim($_POST['location']);
    $salary = trim($_POST['salary']);
    $job_type = trim($_POST['job_type']);
    $description = trim($_POST['description']);
    $requirements = trim($_POST['requirements']);
    $deadline = $_POST['deadline'];
    $max_applications = (int) $_POST['max_applications'];

    if (
        empty($job_title) ||
        empty($company_name) ||
        empty($category_id) ||
        empty($job_type)
    ) {

        $error = "Please fill in all required fields.";

    } else {

        // If user selected "Add New Category"
        if ($category_id == "new") {

            $newCategory = trim($_POST['new_category']);

            if (!empty($newCategory)) {

                // Check whether category already exists
                $check = $pdo->prepare("SELECT category_id FROM job_categories WHERE category_name = ?");
                $check->execute([$newCategory]);

                if ($row = $check->fetch(PDO::FETCH_ASSOC)) {

                    $category_id = $row['category_id'];

                } else {

                    // Insert new category
                    $insert = $pdo->prepare("INSERT INTO job_categories (category_name) VALUES (?)");
                    $insert->execute([$newCategory]);

                    $category_id = $pdo->lastInsertId();
                }

            } else {

                $error = "Please enter the new category name.";
            }
        }

        // Only save the job if no errors occurred
        if (empty($error)) {

            // Save the job
            $result = $jobModel->createJob(
                $employer_id,
                $job_title,
                $company_name,
                $category_id,
                $location,
                $salary,
                $job_type,
                $description,
                $requirements,
                $deadline,
                $max_applications
            );

            if ($result) {
                $success = "Job posted successfully.";

                if (function_exists('sendEmail')) {
                    $userStmt = $pdo->prepare("SELECT email, user_name FROM users WHERE user_id = ? LIMIT 1");
                    $userStmt->execute([$employer_id]);
                    $employer = $userStmt->fetch(PDO::FETCH_ASSOC);

                    if ($employer && !empty($employer['email'])) {

                        // Resolve readable names for category + job type
                        $catStmt = $pdo->prepare("SELECT category_name FROM job_categories WHERE category_id = ? LIMIT 1");
                        $catStmt->execute([$category_id]);
                        $categoryName = $catStmt->fetchColumn() ?: 'Not specified';

                        $jobTypeName = $jobModel->getJobTypeName($job_type);

                        $body = "
            <div style='font-family:Arial,sans-serif;'>
                <h2>Hello " . htmlspecialchars($employer['user_name'] ?? 'there') . ",</h2>
                <p>Your job listing has been posted successfully and is now live.</p>

                <table style='border-collapse:collapse; width:100%; max-width:500px;'>
                    <tr><td style='padding:6px 0; color:#555;'>Job Title</td><td style='padding:6px 0;'><strong>" . htmlspecialchars($job_title) . "</strong></td></tr>
                    <tr><td style='padding:6px 0; color:#555;'>Company</td><td style='padding:6px 0;'>" . htmlspecialchars($company_name) . "</td></tr>
                    <tr><td style='padding:6px 0; color:#555;'>Category</td><td style='padding:6px 0;'>" . htmlspecialchars($categoryName) . "</td></tr>
                    <tr><td style='padding:6px 0; color:#555;'>Job Type</td><td style='padding:6px 0;'>" . htmlspecialchars($jobTypeName) . "</td></tr>
                    <tr><td style='padding:6px 0; color:#555;'>Location</td><td style='padding:6px 0;'>" . htmlspecialchars($location ?: 'Not specified') . "</td></tr>
                    <tr><td style='padding:6px 0; color:#555;'>Salary</td><td style='padding:6px 0;'>" . htmlspecialchars($salary ?: 'Negotiable') . "</td></tr>
                    <tr><td style='padding:6px 0; color:#555;'>Max Applications</td><td style='padding:6px 0;'>" . htmlspecialchars((string) $max_applications) . "</td></tr>
                    <tr><td style='padding:6px 0; color:#555;'>Deadline</td><td style='padding:6px 0;'>" . htmlspecialchars($deadline ?: 'No deadline set') . "</td></tr>
                </table>

                <h3 style='margin-top:20px;'>Description</h3>
                <p>" . nl2br(htmlspecialchars($description)) . "</p>

                " . (!empty($requirements) ? "
                <h3>Requirements</h3>
                <p>" . nl2br(htmlspecialchars($requirements)) . "</p>
                " : "") . "
            </div>
        ";
                        @sendEmail($employer['email'], "Your job posting is live", $body);
                    }
                }

            } else {
                $error = "Failed to post job.";
            }
        }
    }
}



if (isset($_POST['createGuestJob'])) {

    $error = "";

    /* ---------------- Collect Data ---------------- */
    $company_name = trim($_POST['company_name']);
    $contact_person = trim($_POST['contact_person']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    $job_title = trim($_POST['job_title']);
    $category_id = trim($_POST['job_category']);
    $job_type = trim($_POST['job_type']);

    $location = trim($_POST['location']);
    $salary = trim($_POST['salary']);

    $description = trim($_POST['description']);
    $requirements = trim($_POST['requirements']);

    $deadline = $_POST['deadline'];
    $max_applications = (int) $_POST['max_applications'];

    $payment_method = trim($_POST['payment_method'] ?? 'mobile_money');

    /* ---------------- Validation ---------------- */
    if (
        empty($company_name) || empty($contact_person) || empty($email) ||
        empty($phone) || empty($job_title) || empty($category_id) ||
        empty($job_type) || empty($location) || empty($description)
    ) {
        $error = "Please fill in all required fields.";
    }

    /* ---------------- Resolve/Create Category (safe to do now — no job row yet) ---------------- */
    if ($category_id == "new" && empty($error)) {
        $newCategory = trim($_POST['new_category']);

        if (empty($newCategory)) {
            $error = "Please enter a category name.";
        } else {
            $check = $pdo->prepare("SELECT category_id FROM job_categories WHERE category_name=?");
            $check->execute([$newCategory]);
            $existing = $check->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $category_id = $existing['category_id'];
            } else {
                $insert = $pdo->prepare("INSERT INTO job_categories(category_name) VALUES(?)");
                $insert->execute([$newCategory]);
                $category_id = $pdo->lastInsertId();
            }
        }
    }

    /* ---------------- Upload Logo (file itself has to land somewhere now; job row comes later) ---------------- */
    $logo = "";
    if (empty($error)) {
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $uploadDir = __DIR__ . "/../../uploads/logos/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $logo = time() . "_" . bin2hex(random_bytes(4)) . "." . $extension;
            move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . $logo);
        }
    }

    /* ---------------- Stage job data + create pending payment (NO guest_jobs insert yet) ---------------- */
    if (empty($error)) {

        $jobData = [
            'company_name' => $company_name,
            'contact_person' => $contact_person,
            'email' => $email,
            'phone' => $phone,
            'company_logo' => $logo,
            'job_title' => $job_title,
            'category_id' => $category_id,
            'job_type' => $job_type,
            'location' => $location,
            'salary' => $salary,
            'description' => $description,
            'requirements' => $requirements,
            'deadline' => $deadline,
            'max_applications' => $max_applications,
        ];

        $postAmount = (float) ($paymentModel->getConfig('pay_per_post_amount') ?? 0);

        $paymentId = $paymentModel->createPending([
            'payment_type' => 'pay_per_post',
            'payer_type' => 'guest',
            'user_id' => null,
            'guest_job_id' => null,          // doesn't exist yet
            'subscription_id' => null,
            'amount' => $postAmount,
            'currency' => 'UGX',
            'payment_method' => $payment_method,
            'payer_phone' => $phone,
            'payer_name' => $contact_person,
            'job_payload' => json_encode($jobData),
        ]);

        $payment = $paymentModel->getById($paymentId);

        // Confirm submission received — job isn't live yet, payment still pending.
        if (function_exists('sendEmail')) {
            $body = "
                <div style='font-family:Arial,sans-serif;'>
                    <h2>Hello " . htmlspecialchars($contact_person) . ",</h2>
                    <p>We've received your job submission for <strong>" . htmlspecialchars($job_title) . "</strong>
                    at <strong>" . htmlspecialchars($company_name) . "</strong>.</p>
                    <p>Your listing will go live as soon as payment is completed.
                    Reference: <strong>" . htmlspecialchars($payment['payment_reference']) . "</strong></p>
                </div>
            ";
            @sendEmail($email, "We've received your job submission", $body);
        }

        // Send payment instructions/redirect to payment page — nothing is "posted" yet
        header("Location:pay.php?ref=" . urlencode($payment['payment_reference']));
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['applyJob'])) {

    if (!$job) {
        $error = "This job is no longer available.";
    } elseif (!$available) {
        $error = "Applications for this job are closed.";
    } else {

        $name = trim($_POST['applicant_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $cover_letter = trim($_POST['cover_letter'] ?? '');

        if ($name === '' || $email === '') {
            $error = "Name and email are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } elseif ($applicationModel->hasApplied($id, $source, $email)) {
            $error = "You have already applied to this job with this email.";
        }

        /* ---------------- CV upload ---------------- */
        $cvPath = null;

        if (!$error && isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {

            $allowed = ['pdf', 'doc', 'docx'];
            $ext = strtolower(pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed, true)) {
                $error = "CV must be a PDF, DOC, or DOCX file.";
            } elseif ($_FILES['cv']['size'] > 5 * 1024 * 1024) {
                $error = "CV must be smaller than 5MB.";
            } else {
                $uploadDir = __DIR__ . '/../../uploads/cv/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $filename = uniqid('cv_', true) . '.' . $ext;

                if (move_uploaded_file($_FILES['cv']['tmp_name'], $uploadDir . $filename)) {
                    $cvPath = 'uploads/cv/' . $filename;
                } else {
                    $error = "Failed to upload CV. Please try again.";
                }
            }
        }

        /* ---------------- Save ---------------- */
        if (!$error) {

            $saved = $applicationModel->createApplication(
                $id,
                $source,
                $name,
                $email,
                $phone,
                $cover_letter,
                $cvPath
            );

            if ($saved) {

                // Update the counter and auto-close if the limit is reached.
                $jobModel->incrementApplications($source, $id);
                $jobModel->closeJobIfFull($source, $id);

                // Best-effort confirmation email (does not block success).
                $subject = "Application received: " . $job['job_title'];
                $body = "
                    <div style='font-family:Arial,sans-serif;'>
                        <h2>Hello " . htmlspecialchars($name) . ",</h2>
                        <p>Your application for
                        <strong>" . htmlspecialchars($job['job_title']) . "</strong>
                        has been received.</p>
                        <p>The employer will contact you if you are shortlisted.</p>
                        <p>Regards,<br><strong>Job Search Uganda</strong></p>
                    </div>
                ";

                if (function_exists('sendEmail')) {
                    @sendEmail($email, $subject, $body);
                }
                if (function_exists('sendEmail')) {
                    @sendEmail($email, $subject, $body);
                }

                /* ---------------- Notify the job owner ---------------- */
                if (function_exists('sendEmail')) {

                    if ($source === 'jobs') {
                        $ownerStmt = $pdo->prepare("SELECT email, user_name FROM users WHERE user_id = ? LIMIT 1");
                        $ownerStmt->execute([$job['employer_id']]);
                        $owner = $ownerStmt->fetch(PDO::FETCH_ASSOC);
                        $ownerEmail = $owner['email'] ?? null;
                        $ownerName = $owner['user_name'] ?? 'there';
                    } else {
                        $ownerEmail = $job['email'] ?? null;
                        $ownerName = $job['contact_person'] ?? 'there';
                    }

                    if (!empty($ownerEmail)) {
                        $ownerSubject = "New application for: " . $job['job_title'];
                        $ownerBody = "
            <div style='font-family:Arial,sans-serif;'>
                <h2>Hello " . htmlspecialchars($ownerName) . ",</h2>
                <p>You have a new application for <strong>" . htmlspecialchars($job['job_title']) . "</strong>.</p>

                <table style='border-collapse:collapse; width:100%; max-width:500px;'>
                    <tr><td style='padding:6px 0; color:#555;'>Applicant Name</td><td style='padding:6px 0;'><strong>" . htmlspecialchars($name) . "</strong></td></tr>
                    <tr><td style='padding:6px 0; color:#555;'>Email</td><td style='padding:6px 0;'>" . htmlspecialchars($email) . "</td></tr>
                    <tr><td style='padding:6px 0; color:#555;'>Phone</td><td style='padding:6px 0;'>" . htmlspecialchars($phone ?: 'Not provided') . "</td></tr>
                    <tr><td style='padding:6px 0; color:#555;'>CV Attached</td><td style='padding:6px 0;'>" . ($cvPath ? 'Yes' : 'No') . "</td></tr>
                </table>

                " . (!empty($cover_letter) ? "
                <h3 style='margin-top:20px;'>Cover Letter</h3>
                <p>" . nl2br(htmlspecialchars($cover_letter)) . "</p>
                " : "") . "

                <p style='margin-top:20px; color:#555;'>Applications so far: " . ((int) $job['current_applications'] + 1) . " / " . htmlspecialchars((string) $job['max_applications']) . "</p>
            </div>
        ";
                        @sendEmail($ownerEmail, $ownerSubject, $ownerBody);
                    }
                }

                $success = "Your application has been submitted successfully!";
            } else {
                $error = "Something went wrong while submitting your application.";
            }
        }
    }
}