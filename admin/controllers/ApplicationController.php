<?php
require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../models/JobModel.php';
require_once __DIR__ . '/../models/ApplicationModel.php';

$applicationModel = new ApplicationModel ($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['application_id'], $_POST['status'])) {
    $appId  = (int) $_POST['application_id'];
    $status = $_POST['status'];

    if ($applicationModel->updateStatus($appId, $status)) {
        $notice = "Application #{$appId} updated to {$status}.";
    } else {
        $notice = "Could not update application #{$appId}.";
    }
}