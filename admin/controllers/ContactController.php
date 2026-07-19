<?php


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


$ContactModel->fetchAllMessages();
$totalMessages = count($ContactModel->fetchAllMessages());

if (isset($_GET['deleteMessage'])) {
    $contact_id = (int) $_GET['contact_id'];

    if ($ContactModel->deleteMessage($contact_id)) {

        header("Location: messages.php?success=deleted");
        exit;
    } else {

        header("Location: messages.php?error=delete_failed");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_id'])) {
    $contact_id = (int) $_POST['contact_id'];

    if ($ContactModel->deleteMessage($contact_id)) {
        echo "Message deleted successfully.";
    } else {
        echo "Failed to delete message.";
    }
    exit;
}


if (isset($_POST['saveAndSendMessage'])) {
    $name = trim($_POST['contact_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if (empty($name) || empty($email) || empty($message)) {
        header("Location: Contact.php?error=empty_fields");
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: Contact.php?error=invalid_email");
        exit;
    }
    if ($ContactModel->saveAndSendMessage($name, $email, $message)) {
        $subject = "We've received your message";
        $body = "
        <p>Hello <strong>$name</strong>,</p>
        <p>Thank you for contacting us.</p>
        <p>We have received your message and will get back to you shortly.</p>
        <br>
        <p>Best regards,<br>Support Team</p>
    ";


        sendemail($email, $name, $subject, $body);



    } else {
        header("Location: Contact.php?error=Database_insertion_failed");
        exit;
    }
}





