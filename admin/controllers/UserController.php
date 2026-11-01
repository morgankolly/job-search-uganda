<?php
// admin/controllers/UserController.php

error_reporting(E_ALL);
ini_set('display_errors', 1);          // development only: turn off in production
ini_set('display_startup_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../helpers/functions.php';

$UserModel = new UserModel($pdo);
$userModel = $UserModel;               // alias so both names work

$message  = '';
$error    = '';
$listPage = 'userManager.php';         // change if your list page has another name

// Flash messages from redirects
if (!empty($_SESSION['success'])) { $message = $_SESSION['success']; unset($_SESSION['success']); }
if (!empty($_SESSION['error']))   { $error   = $_SESSION['error'];   unset($_SESSION['error']); }

// ---------- CREATE USER ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['createUser'])) {

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role_id  = (int)($_POST['role_id'] ?? 2);

    if ($username === '' || $email === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($userModel->emailExists($email)) {
        $error = 'That email is already registered.';
    } else {
        $profile = 'default.png';
        $saved   = $userModel->saveProfileImage($_FILES['profile'] ?? []);

        if ($saved === false) {
            $error = 'Profile image must be a jpg, png, gif or webp file.';
        } else {
            if ($saved !== null) {
                $profile = $saved;
            }
            if ($userModel->registerUser($username, $email, $password, $profile, $role_id)) {
                $message = 'User registered successfully!';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
     header("Location: index.php?message=" . urlencode($message) . "&error=" . urlencode($error)   );
    exit;
}

// ---------- DELETE USER ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteUserById'])) {

    $userId = (int)($_POST['user_id'] ?? 0);

    if ($userId > 0) {
        try {
            $user = $userModel->getUserById($userId);

            if ($userModel->deleteUserById($userId)) {
                if ($user) {
                    $userModel->deleteProfileImage($user['profile'] ?? null);
                }
                $_SESSION['success'] = 'User deleted successfully.';
            } else {
                $_SESSION['error'] = 'Failed to delete user.';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Failed to delete user.';
        }
    } else {
        $_SESSION['error'] = 'Invalid user ID.';
    }

    header("Location: $listPage");
    exit;
}

// ---------- UPDATE USER ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateUser'])) {

    $user_id  = (int)($_POST['user_id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $role_id  = (int)($_POST['role_id'] ?? 2);

    $oldUser = $userModel->getUserById($user_id);

    if (!$oldUser) {
        $error = 'User not found.';
    } elseif ($username === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid username and email.';
    } else {
        $profile = $oldUser['profile'];
        $saved   = $userModel->saveProfileImage($_FILES['profile'] ?? []);

        if ($saved === false) {
            $error = 'Profile image must be a jpg, png, gif or webp file.';
        } else {
            if ($saved !== null) {
                $userModel->deleteProfileImage($profile);
                $profile = $saved;
            }

            if ($userModel->updateUser($user_id, $username, $email, $profile)) {
                header("Location: $listPage?updated=1");
                exit;
            }
            $error = 'Failed to update user.';
        }
    }
}

// ---------- LOAD DATA FOR VIEWS ----------
$user = null;
if (isset($_GET['user_id'])) {
    $user = $userModel->getUserById((int)$_GET['user_id']);
}

$users      = $userModel->getAllUsers() ?? [];
$totalUsers = count($users);