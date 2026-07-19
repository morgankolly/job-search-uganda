<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

if (!defined('APPROOT')) {
    define('APPROOT', dirname(dirname(dirname(__FILE__))));
}

// Only bootstrap the autoloader + env once, regardless of include order.
// connection.php uses the same flag, so whichever file runs first wins.
if (!isset($GLOBALS['__ENV_LOADED'])) {
    require_once APPROOT . '/vendor/autoload.php';
    if (file_exists(APPROOT . '/.env')) {
        \Dotenv\Dotenv::createImmutable(APPROOT)->safeLoad();
    }
    $GLOBALS['__ENV_LOADED'] = true;
}

// Ensure $pdo is available when functions.php is the first file loaded.
require_once APPROOT . '/admin/config/connection.php';


/*
|--------------------------------------------------------------------------
| SEND EMAIL
|--------------------------------------------------------------------------
*/

function sendEmail($to, $subject, $body)
{
    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        $mail->Host       = $_ENV['MAIL_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'];
        $mail->Password   = $_ENV['MAIL_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int) $_ENV['MAIL_PORT'];

        $mail->CharSet = 'UTF-8';

        $mail->setFrom(
            $_ENV['MAIL_FROM_ADDRESS'],
            $_ENV['MAIL_FROM_NAME']
        );

        $mail->addAddress($to);

        $mail->isHTML(true);

        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        return $mail->send();

    } catch (Exception $e) {

        error_log("PHPMailer Error: " . $mail->ErrorInfo);

        return false;
    }
}


/*
|--------------------------------------------------------------------------
| GUEST JOB EMAIL
|--------------------------------------------------------------------------
*/

function sendGuestJobEmail(
    $email,
    $contact_person,
    $jobId,
    $company_name,
    $job_title,
    $job_type,
    $location,
    $salary,
    $description,
    $requirements,
    $deadline,
    $max_applications
) {

    $subject = "Job Submitted Successfully - Reference #{$jobId}";


    $message = "

    <div style='font-family:Arial,sans-serif;'>

        <h2>Hello {$contact_person},</h2>

        <p>
        Thank you for posting your job on 
        <strong>Job Search Uganda</strong>.
        </p>

        <p>
        Your job has been successfully received and is currently awaiting payment/approval.
        </p>


        <h3>Job Details</h3>


        <table 
        border='1' 
        cellpadding='10' 
        cellspacing='0'
        style='border-collapse:collapse;width:100%;'>

            <tr>
                <td><strong>Job Reference</strong></td>
                <td>#{$jobId}</td>
            </tr>


            <tr>
                <td><strong>Company Name</strong></td>
                <td>{$company_name}</td>
            </tr>


            <tr>
                <td><strong>Job Title</strong></td>
                <td>{$job_title}</td>
            </tr>


            <tr>
                <td><strong>Job Type</strong></td>
                <td>{$job_type}</td>
            </tr>


            <tr>
                <td><strong>Location</strong></td>
                <td>{$location}</td>
            </tr>


            <tr>
                <td><strong>Salary</strong></td>
                <td>{$salary}</td>
            </tr>


            <tr>
                <td><strong>Application Deadline</strong></td>
                <td>{$deadline}</td>
            </tr>


            <tr>
                <td><strong>Maximum Applicants</strong></td>
                <td>{$max_applications}</td>
            </tr>


            <tr>
                <td><strong>Description</strong></td>
                <td>{$description}</td>
            </tr>


            <tr>
                <td><strong>Requirements</strong></td>
                <td>{$requirements}</td>
            </tr>


        </table>


        <br>


        <p>
        Once payment is confirmed, your job will be published and visible to job seekers.
        </p>


        <p>
        Regards,<br>
        <strong>Job Search Uganda Team</strong>
        </p>


    </div>

    ";


    return sendEmail(
        $email,
        $subject,
        $message
    );
}

function sendGuestJobWhatsApp(
    $phone,
    $name,
    $jobReference,
    $company_name,
    $job_title,
    $job_type,
    $location
) {

    $token = $_ENV['WHATSAPP_TOKEN'];
    $phoneNumberId = $_ENV['WHATSAPP_PHONE_ID'];


    // Remove spaces and + sign
    $phone = str_replace(['+', ' ', '-'], '', $phone);


    $message = "Hello {$name},

Thank you for posting your job on Job Search Uganda.

Your job has been received successfully.

Job Reference: {$jobReference}

Company: {$company_name}

Position: {$job_title}

Job Type: {$job_type}

Location: {$location}

Status: Awaiting Payment/Approval.

We will notify you once your job is published.

Thank you,
Job Search Uganda Team";


    $data = [

        "messaging_product" => "whatsapp",

        "recipient_type" => "individual",

        "to" => $phone,

        "type" => "text",

        "text" => [

            "preview_url" => false,

            "body" => $message

        ]

    ];


    $ch = curl_init();


    curl_setopt_array($ch, [

        CURLOPT_URL =>
        "https://graph.facebook.com/v23.0/{$phoneNumberId}/messages",

        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_POST => true,

        CURLOPT_HTTPHEADER => [

            "Authorization: Bearer {$token}",

            "Content-Type: application/json"

        ],

        CURLOPT_POSTFIELDS => json_encode($data)

    ]);


    $response = curl_exec($ch);


    if (curl_errno($ch)) {

        error_log(
            "WhatsApp Error: " . curl_error($ch)
        );

        curl_close($ch);

        return false;
    }


    curl_close($ch);


    return json_decode($response, true);
}

/*
|--------------------------------------------------------------------------
| APPLICATION REFERENCE
|--------------------------------------------------------------------------
*/

function generateApplicationReference(PDO $pdo): string
{
    do {
        $ref = 'APP-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        $stmt = $pdo->prepare(
            "SELECT application_id FROM applications WHERE application_reference = ? LIMIT 1"
        );
        $stmt->execute([$ref]);
    } while ($stmt->fetch());
    return $ref;
}

/*
|--------------------------------------------------------------------------
| PAYMENT REFERENCE
|--------------------------------------------------------------------------
*/

function generatePaymentReference(PDO $pdo, string $prefix = 'PAY'): string
{
    do {
        $ref = $prefix . '-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        $stmt = $pdo->prepare(
            "SELECT payment_id FROM payments WHERE payment_reference = ? LIMIT 1"
        );
        $stmt->execute([$ref]);
    } while ($stmt->fetch());
    return $ref;
}

/*
|--------------------------------------------------------------------------
| NEW APPLICATION → NOTIFY EMPLOYER
| Sent to the employer/contact when a job seeker submits an application.
|--------------------------------------------------------------------------
*/

function sendApplicationNotificationToEmployer(
    string  $employerEmail,
    string  $employerName,
    string  $applicantName,
    string  $applicantEmail,
    string  $applicantPhone,
    string  $jobTitle,
    string  $appReference,
    string  $submittedAt,
    ?string $cvUrl   = null,
    ?string $phone   = null   // employer WhatsApp phone
): void {
    /* --- Email --- */
    $subject = "New Application: {$jobTitle} — {$applicantName}";
    $body    = "
    <div style='font-family:Arial,sans-serif;'>
        <h2>New Job Application Received</h2>
        <p>Hello <strong>{$employerName}</strong>,</p>
        <p>A new application has been submitted for your job posting
           <strong>".htmlspecialchars($jobTitle)."</strong>.</p>
        <table border='1' cellpadding='8' cellspacing='0'
               style='border-collapse:collapse;width:100%;'>
            <tr><td><strong>Applicant Name</strong></td><td>".htmlspecialchars($applicantName)."</td></tr>
            <tr><td><strong>Email</strong></td><td>".htmlspecialchars($applicantEmail)."</td></tr>
            <tr><td><strong>Phone</strong></td><td>".htmlspecialchars($applicantPhone ?: 'N/A')."</td></tr>
            <tr><td><strong>Job Title</strong></td><td>".htmlspecialchars($jobTitle)."</td></tr>
            <tr><td><strong>Application Reference</strong></td><td>".htmlspecialchars($appReference)."</td></tr>
            <tr><td><strong>Submitted</strong></td><td>".htmlspecialchars($submittedAt)."</td></tr>
            ".($cvUrl ? "<tr><td><strong>CV</strong></td><td><a href='".htmlspecialchars($cvUrl)."'>Download CV</a></td></tr>" : '')."</p>
        </table>
        <p>Log in to your dashboard to review this application.</p>
        <p>Regards,<br><strong>Job Search Uganda</strong></p>
    </div>";

    if (function_exists('sendEmail')) {
        @sendEmail($employerEmail, $subject, $body);
    }

    /* --- WhatsApp --- */
    if ($phone && function_exists('sendGuestWhatsApp')) {
        $waMsg = "New Application Received\n\n"
            . "Job: {$jobTitle}\n"
            . "Applicant: {$applicantName}\n"
            . "Email: {$applicantEmail}\n"
            . "Phone: " . ($applicantPhone ?: 'N/A') . "\n"
            . "Reference: {$appReference}\n"
            . "Submitted: {$submittedAt}\n"
            . ($cvUrl ? "CV: {$cvUrl}\n" : '')
            . "\nLog in to your dashboard to review.";

        @sendGuestWhatsApp($phone, $employerName, $appReference, '', $jobTitle, '', '');
    }
}

/*
|--------------------------------------------------------------------------
| APPLICATION CONFIRMATION → JOB SEEKER
| Sent immediately after the applicant submits their application.
|--------------------------------------------------------------------------
*/

function sendApplicationConfirmationToSeeker(
    string  $seekerEmail,
    string  $seekerName,
    string  $jobTitle,
    string  $companyName,
    string  $appReference,
    string  $submittedAt,
    ?string $seekerPhone = null
): void {
    /* --- Email --- */
    $subject = "Application Received – {$appReference}";
    $body    = "
    <div style='font-family:Arial,sans-serif;'>
        <h2>Application Successfully Submitted</h2>
        <p>Hello <strong>".htmlspecialchars($seekerName)."</strong>,</p>
        <p>Your application has been successfully received.</p>
        <table border='1' cellpadding='8' cellspacing='0'
               style='border-collapse:collapse;width:100%;'>
            <tr><td><strong>Applicant Name</strong></td><td>".htmlspecialchars($seekerName)."</td></tr>
            <tr><td><strong>Job Title</strong></td><td>".htmlspecialchars($jobTitle)."</td></tr>
            <tr><td><strong>Company</strong></td><td>".htmlspecialchars($companyName)."</td></tr>
            <tr><td><strong>Application Reference</strong></td><td>".htmlspecialchars($appReference)."</td></tr>
            <tr><td><strong>Submission Time</strong></td><td>".htmlspecialchars($submittedAt)."</td></tr>
            <tr><td><strong>Status</strong></td><td>Successfully Submitted</td></tr>
        </table>
        <p>The employer will contact you if you are shortlisted. Keep this reference for your records.</p>
        <p>Good luck!<br><strong>Job Search Uganda Team</strong></p>
    </div>";

    if (function_exists('sendEmail')) {
        @sendEmail($seekerEmail, $subject, $body);
    }

    /* --- WhatsApp --- */
    if ($seekerPhone) {
        $phone = preg_replace('/[^0-9]/', '', $seekerPhone);
        if (substr($phone, 0, 1) === '0') {
            $phone = '256' . substr($phone, 1);
        }

        $token         = $_ENV['WHATSAPP_TOKEN']         ?? '';
        $phoneNumberId = $_ENV['WHATSAPP_PHONE_ID']      ?? '';

        if ($token && $phoneNumberId) {
            $msg = "Application Received ✅\n\n"
                . "Hello {$seekerName},\n"
                . "Your application has been received successfully.\n\n"
                . "Job: {$jobTitle}\n"
                . "Company: {$companyName}\n"
                . "Reference: {$appReference}\n"
                . "Submitted: {$submittedAt}\n\n"
                . "Status: Successfully Submitted\n\n"
                . "The employer will contact you if shortlisted.\n"
                . "Good luck!\n\nJob Search Uganda Team";

            $data = [
                'messaging_product' => 'whatsapp',
                'to'                => $phone,
                'type'              => 'text',
                'text'              => ['body' => $msg],
            ];
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => "https://graph.facebook.com/v23.0/{$phoneNumberId}/messages",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER     => [
                    "Authorization: Bearer {$token}",
                    'Content-Type: application/json',
                ],
                CURLOPT_POSTFIELDS     => json_encode($data),
            ]);
            curl_exec($ch);
            curl_close($ch);
        }
    }
}

function generateJobReference($pdo)
{
    do {

        $reference = "JOB-" . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));


        // Check if exists
        $stmt = $pdo->prepare(
            "SELECT job_id FROM jobs WHERE job_reference = ?"
        );

        $stmt->execute([$reference]);


        $exists = $stmt->fetch();


    } while ($exists);


    return $reference;
}

function formatUgandaPhone($phone)
{
    $phone = str_replace(' ', '', $phone);

    if (substr($phone,0,1) == '0') {
        $phone = '256' . substr($phone,1);
    }

    return $phone;
}
function sendGuestWhatsApp(
    $phone,
    $name,
    $jobReference,
    $companyName,
    $jobTitle,
    $jobType,
    $location
) {


    $token = $_ENV['WHATSAPP_TOKEN'];

    $phoneNumberId = $_ENV['WHATSAPP_PHONE_ID'];



    if(empty($token) || empty($phoneNumberId)){

        error_log("Missing WhatsApp credentials");

        return false;

    }



    // Format phone number

    $phone = preg_replace('/[^0-9]/', '', $phone);


    // Uganda numbers

    if(substr($phone,0,1) == "0"){

        $phone = "256" . substr($phone,1);

    }



    $message = "

Hello {$name},

Your job has been submitted successfully on Job Search Uganda.

Job Reference:
{$jobReference}


Company:
{$companyName}


Position:
{$jobTitle}


Job Type:
{$jobType}


Location:
{$location}


Your job is awaiting payment and approval.

Thank you.

Job Search Uganda Team

";



    $data = [

        "messaging_product"=>"whatsapp",

        "to"=>$phone,

        "type"=>"text",

        "text"=>[

            "body"=>$message

        ]

    ];



    $ch = curl_init();



    curl_setopt_array($ch,[

        CURLOPT_URL =>
        "https://graph.facebook.com/v23.0/".$phoneNumberId."/messages",

        CURLOPT_RETURNTRANSFER=>true,

        CURLOPT_POST=>true,

        CURLOPT_HTTPHEADER=>[

            "Authorization: Bearer ".$token,

            "Content-Type: application/json"

        ],

        CURLOPT_POSTFIELDS=>json_encode($data)

    ]);



    $response = curl_exec($ch);



    if(curl_errno($ch)){


        error_log(
            "Curl Error: ".curl_error($ch)
        );


        return false;

    }



    curl_close($ch);



    $result=json_decode($response,true);



    // Important: show Meta errors

    if(isset($result['error'])){


        error_log(
            "WhatsApp Error: ".$result['error']['message']
        );


        return false;

    }



    return true;

}

function authorizeDelete(array $allowedRoles)
{

    if (
        !isset($_SESSION['user_id']) ||
        !isset($_SESSION['role_id'])
    ) {

        http_response_code(401);

        exit("Unauthorized");
    }

    if (
        !in_array(
            strtolower($_SESSION['role_id']),
            array_map('strtolower', $allowedRoles)
        )
    ) {

        http_response_code(403);

        exit("❌ You do not have permission to delete this content.");
    }
}

function generateWhatsAppLink($phone, $message)
{

    // Remove spaces and symbols
    $phone = preg_replace('/[^0-9]/', '', $phone);


    // Uganda format
    if(substr($phone,0,1) == "0"){

        $phone = "256" . substr($phone,1);

    }


    $message = urlencode($message);


    return "https://wa.me/".$phone."?text=".$message;

}