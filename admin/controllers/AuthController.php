<?php




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
            $userModel = new UserModel($pdo);
            $success = $userModel->registerUser(
                $username,
                $email,
                $password,
                'default.png',
                $role
            );

            if ($success) {
                $message = "User registered successfully!";
            } else {
                $message = "Registration failed.";
            }

        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
        }
    }
}


if (isset($_POST["login"]) && ($_POST["action"] ?? '') === "login_user") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $user = $UserModel->getUserByEmail($email);

    if ($user && password_verify($password, $user["password"])) {

        session_regenerate_id(true);

        $_SESSION['user_id']  = $user['user_id'];
        $_SESSION['username'] = $user['user_name'];
        $_SESSION['name']     = $user['user_name'];
        $_SESSION['email']    = $user['email'];
        $_SESSION['role_id']  = $user['role_id'];
        $_SESSION['role']     = strtolower($user['role_name'] ?? 'user');

        header("Location: dashboard.php");
        exit;
    }

    $loginError = "Invalid credentials";
}
function requireLogin()
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

function requireRole(array $allowedRoles)
{
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles)) {
        http_response_code(403);
        die("Access denied");
    }
}










