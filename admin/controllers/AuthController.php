<?php

// Use the model object if it already exists, otherwise create it
$UserModel  = $UserModel ?? new UserModel($pdo);
$message    = $message ?? '';
$loginError = '';

// ---------- REGISTER ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registerUser'])) {
    $username = trim($_POST['user_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = isset($_POST['role_id']) ? (int) $_POST['role_id'] : 0;

    if ($username === '' || $email === '' || $password === '' || $role === 0) {
        $message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email address.";
    } else {
        try {
            $success = $UserModel->registerUser($username, $email, $password, 'default.png', $role);
            $message = $success ? "User registered successfully!" : "Registration failed.";
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
        }
    }
}

// ---------- LOGIN ----------
if (isset($_POST['login']) && ($_POST['action'] ?? '') === 'login_user') {

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $user = $UserModel->getUserByEmail($email);

    if ($user && password_verify($password, $user['password'])) {

        session_regenerate_id(true);

        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['username']  = $user['user_name'];
        $_SESSION['name']      = $user['user_name'];
        $_SESSION['email']     = $user['email'];
        $_SESSION['role_id']   = (int) $user['role_id'];
        $_SESSION['role']      = strtolower($user['role_name'] ?? 'user');   // for your existing pages
        $_SESSION['role_name'] = strtolower($user['role_name'] ?? 'user');   // used by the role guard

        header('Location: /job-search-system/' . $UserModel->getDashboardPath($user['role_name']));
        exit;
    }

    $loginError = "Invalid credentials";
}