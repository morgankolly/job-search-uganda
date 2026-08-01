<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../helpers/functions.php';


$UserModel = new UserModel($pdo);

$users = $UserModel->getAllUsers() ?? [];
$totalUsers = count($users);



$totalUsers = count($users);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['getAllUsers'])) {
    $username = $_POST['user_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $role = $_POST['role'] ?? '';
    $status = $_POST['Status'] ?? '';

}
$users = $UserModel->getAllUsers();
$totalUsers = count($users);

// Alias so both $userModel and $UserModel work throughout this controller.
$userModel = $UserModel;


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['createUser'])) {

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $email === '' || $password === '') {

        $message = "<div class='alert alert-danger'>All fields are required.</div>";

    } elseif ($userModel->userExists($username, $email)) {

        $message = "<div class='alert alert-danger'>Username or Email already exists.</div>";

    } else {

        // Upload directory
        $uploadDir = __DIR__ . '/../../uploads/profile/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $profile = "http://localhost/Job-Search-System/uploads/profile/" . $filename;

        if (
            isset($_FILES['profile']) &&
            $_FILES['profile']['error'] == UPLOAD_ERR_OK
        ) {

            $extension = strtolower(pathinfo($_FILES['profile']['name'], PATHINFO_EXTENSION));

            $allowed = ['jpg','jpeg','png','gif','webp'];

            if (in_array($extension, $allowed, true)) {

                $filename = uniqid() . "." . $extension;

                if (move_uploaded_file(
                    $_FILES['profile']['tmp_name'],
                    $uploadDir . $filename
                )) {
                    $profile = $filename;
                }
            }
        }

        // New self-registered accounts default to the employer role (role_id = 2).
        if ($userModel->registerUser($username, $email, $password, $profile, 2)) {
            header("Location: index.php?registered=1");
            exit;
        } else {
            $message = "<div class='alert alert-danger'>Registration failed.</div>";
        }
    }
}

   



if (isset($_POST['deleteUserById'])) {

    $user_id = intval($_POST['user_id']);

    var_dump($_POST['deleteUserById']);

    if ($UserModel->deleteUserById($user_id)) {
        echo "<script>alert('User deleted successfully'); window.location.href='userManager.php';</script>";
    } else {
        echo "<script>alert('Failed to delete user');</script>";
    }
}




if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteUserById'])) {

    $userId = (int)($_POST['user_id'] ?? 0);

    if ($userId > 0) {

        try {

            // Optional: fetch profile image first (to delete file)
            $user = $userModel->getUserById($userId);

            if ($user && !empty($user['profile_image']) && file_exists($user['profile_image'])) {
                unlink($user['profile_image']); // delete image file
            }

            $userModel->deleteUserById($userId);

            $_SESSION['success'] = "User deleted successfully.";

        } catch (Exception $e) {
            $_SESSION['error'] = "Failed to delete user.";
        }

    } else {
        $_SESSION['error'] = "Invalid user ID.";
    }

    header("Location: userManager.php");
    exit;
}

if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];
    $user = $UserModel->getUserById($user_id);
}






if (isset($_POST['updateUser'])) {

    $user_id  = $_POST['user_id'];
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $role_id  = $_POST['role_id'];

    $oldUser = $UserModel->getUserById($user_id);
    if (!$oldUser) {
        die('User not found');
    }

    $profile = $oldUser['profile'];

      $uploadDir = __DIR__ . '/../../uploads/profile/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true); // create if missing
    }



    if (
        isset($_FILES['profile'])
    ) {

        $filename = time() . '_' . basename($_FILES['profile']['name']);
        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($_FILES['profile']['tmp_name'], $destination)) {
            die('Failed to move uploaded file. Check folder permissions.');
        }
        $profile = "http://localhost/ticketing-system/uploads/profile/" . $filename;
    }

    $updated = $UserModel->updateUser(
        $user_id,
        $username,
        $email,
        $profile
    );

    if ($updated) {
        header("Location: usersManager.php?updated=1");
        exit;
    } else {
        echo "Failed to update user.";
    }
}




