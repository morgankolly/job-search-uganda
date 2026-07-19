<?php
require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../models/JobModel.php';


$jobModel = new JobModel($pdo);
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
    $success = "";


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



    /* ---------------- Validation ---------------- */

    if (
        empty($company_name) ||
        empty($contact_person) ||
        empty($email) ||
        empty($phone) ||
        empty($job_title) ||
        empty($category_id) ||
        empty($job_type) ||
        empty($location) ||
        empty($description)
    ) {

        $error = "Please fill in all required fields.";

    }



    /* ---------------- Create New Category ---------------- */

    if ($category_id == "new" && empty($error)) {


        $newCategory = trim($_POST['new_category']);


        if (empty($newCategory)) {


            $error = "Please enter a category name.";


        } else {


            $check = $pdo->prepare(
                "SELECT category_id 
                 FROM job_categories 
                 WHERE category_name=?"
            );


            $check->execute([$newCategory]);


            $existing = $check->fetch(PDO::FETCH_ASSOC);



            if ($existing) {


                $category_id = $existing['category_id'];


            } else {


                $insert = $pdo->prepare(
                    "INSERT INTO job_categories(category_name)
                     VALUES(?)"
                );


                $insert->execute([$newCategory]);


                $category_id = $pdo->lastInsertId();

            }

        }

    }





    /* ---------------- Upload Logo ---------------- */


    $logo = "";


    if (empty($error)) {


        if (
            isset($_FILES['logo']) &&
            $_FILES['logo']['error'] == 0
        ) {


            $uploadDir = __DIR__ . "/../../uploads/logos/";


            if (!is_dir($uploadDir)) {

                mkdir($uploadDir,0777,true);

            }



            $extension = pathinfo(
                $_FILES['logo']['name'],
                PATHINFO_EXTENSION
            );


            $logo = time() . "." . $extension;



            move_uploaded_file(
                $_FILES['logo']['tmp_name'],
                $uploadDir . $logo
            );

        }

    }




    /* ---------------- Save Job ---------------- */


    if (empty($error)) {


        // Generate unique reference
        $jobReference = generateJobReference($pdo);



        $jobReference = $jobModel->createGuestJob(

            $jobReference,

            $company_name,

            $contact_person,

            $email,

            $phone,

            $logo,

            $job_title,

            $category_id,

            $job_type,

            $location,

            $salary,

            $description,

            $requirements,

            $deadline,

            $max_applications

        );
        if ($jobReference) {




    // Get job type name
    $jobTypeName = $jobModel->getJobTypeName($job_type);



            $emailSent = sendGuestJobEmail(

               $email,
    $contact_person,
    $jobReference,
    $company_name,
    $job_title,
    $job_type,
    $location,
    $salary,
    $description,
    $requirements,
    $deadline,
    $max_applications

            );





            // Send WhatsApp

            $whatsappSent = sendGuestWhatsApp(

                $phone,

                $contact_person,

                $jobReference,

                $company_name,

                $job_title,

                $jobTypeName,

                $location

            );





            if ($emailSent || $whatsappSent) {


                $success =
                "Job posted successfully. Confirmation sent.";

            } else {


                $success =
                "Job posted successfully, but notifications failed.";

            }





        } else {


            $error = "Failed to post job.";

        }

    }

}

