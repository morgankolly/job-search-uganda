<?php
require_once __DIR__ . '/require.php';   // starts the JS_HR session

$allowedRoles = $allowedRoles ?? ['hr', 'admin'];

if (!isset($_SESSION['user_id']) || empty($_SESSION['role_name'])) {
    header('Location: ./login.php');
    exit;
}

if (!in_array(strtolower($_SESSION['role_name']), array_map('strtolower', $allowedRoles), true)) {
    header('Location: /job-search-system/' . $UserModel->getDashboardPath($_SESSION['role_name']));
    exit;
}