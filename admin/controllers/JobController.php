<?php
require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../models/JobModel.php';
require_once __DIR__ . '/../models/PaymentModel.php';

$jobModel = new JobModel($pdo);
$paymentModel = new PaymentModel($pdo); 
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
        if (!isset($error)) {

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
            } else {
                $error = "Failed to post job.";
            }
        }
    }
}



if (isset($_POST['createGuestJob'])) {

    $error = "";

    /* ---------------- Collect Data ---------------- */
    $company_name     = trim($_POST['company_name']);
    $contact_person   = trim($_POST['contact_person']);
    $email            = trim($_POST['email']);
    $phone            = trim($_POST['phone']);

    $job_title        = trim($_POST['job_title']);
    $category_id      = trim($_POST['job_category']);
    $job_type         = trim($_POST['job_type']);

    $location         = trim($_POST['location']);
    $salary           = trim($_POST['salary']);

    $description      = trim($_POST['description']);
    $requirements     = trim($_POST['requirements']);

    $deadline         = $_POST['deadline'];
    $max_applications = (int) $_POST['max_applications'];

    $payment_method   = trim($_POST['payment_method'] ?? 'mobile_money');

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
            'company_name'      => $company_name,
            'contact_person'    => $contact_person,
            'email'             => $email,
            'phone'             => $phone,
            'company_logo'      => $logo,
            'job_title'         => $job_title,
            'category_id'       => $category_id,
            'job_type'          => $job_type,
            'location'          => $location,
            'salary'            => $salary,
            'description'       => $description,
            'requirements'      => $requirements,
            'deadline'          => $deadline,
            'max_applications'  => $max_applications,
        ];

        $postAmount = (float) ($paymentModel->getConfig('pay_per_post_amount') ?? 0);

        $paymentId = $paymentModel->createPending([
            'payment_type'    => 'pay_per_post',
            'payer_type'      => 'guest',
            'user_id'         => null,
            'guest_job_id'    => null,          // doesn't exist yet
            'subscription_id' => null,
            'amount'          => $postAmount,
            'currency'        => 'UGX',
            'payment_method'  => $payment_method,
            'payer_phone'     => $phone,
            'payer_name'      => $contact_person,
            'job_payload'     => json_encode($jobData),
        ]);

        $payment = $paymentModel->getById($paymentId);

        // Send payment instructions/redirect to payment page — nothing is "posted" yet
        header("Location:pay.php?ref=" . urlencode($payment['payment_reference']));
        exit;
    }
}

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
if (isset($_POST['verifyPayment'], $_POST['payment_id'], $_POST['transaction_id'])) {

    $payment_id     = (int) $_POST['payment_id'];
    $transaction_id = trim($_POST['transaction_id']);

    if ($paymentModel->verify($payment_id, $_SESSION['admin_id'], $transaction_id)) {

        publishGuestJobFromPayment($pdo, $jobModel, $paymentModel, $payment_id);

        $success = "Payment verified and job published.";
    } else {
        $error = "Payment verification failed. Please check the transaction ID.";
    }
}
