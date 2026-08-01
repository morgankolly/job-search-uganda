<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);



if (isset($_SESSION['user_id'])) {
    header("Location: ./dashboard.php");
    exit;
}
require_once './main/require.php';



?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In</title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/libs/fontawesome/css/all.min.css">
</head>

<body style="min-height:100vh; display:flex; align-items:center; justify-content:center; background:#f5f6fa;">

    <div class="container-fluid vh-60 d-flex justify-content-center align-items-center">
        <div class="row justify-content-center w-100">

            <div class="col-xl-5 col-lg-4 col-md-9 col-sm-20 col-15">

                <div class="card shadow-lg border-7 rounded-4">
                    <div class="card-body p-7 p-sm-9">

                        <!-- Logo -->
                        <div class="text-center mb-4">
                            <a href="../index.html">
                                <img src="../assets/images/Logo 1.png" alt="Logo" style="width:80px;height:auto;">
                            </a>
                        </div>

                        <!-- Heading -->
                        <div class="text-center mb-4">
                            <h2 class="fw-bold">Welcome Back!</h2>
                            <p class="text-muted">
                                Login to your account.
                            </p>
                        </div>

                        <!-- Session / login notices -->
                        <?php if (!empty($loginError)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($loginError) ?></div>
                        <?php endif; ?>
                        <?php if (($_GET['reason'] ?? '') === 'session_expired'): ?>
                            <div class="alert alert-warning">
                                <i class="fa fa-clock me-1"></i>
                                Your session has expired or the database was refreshed. Please log in again.
                            </div>
                        <?php endif; ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="login_user" />

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input class="form-control form-control-lg" type="email" name="email"
                                    placeholder="Enter your email" required />
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input class="form-control form-control-lg" type="password" name="password"
                                    placeholder="Enter your password" required />
                            </div>

                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="termsConditions">

                                <label class="form-check-label" for="termsConditions">
                                    I agree to
                                    <a href="#">Privacy Policy & Terms</a>
                                </label>
                            </div>

                            <div class="d-grid mb-3">
                                <button name="login" type="submit" class="btn btn-primary">
                                    Sign In
                                </button>
                            </div>

                            <p class="text-center mb-4">
                                Don't have an account?
                                <a href="register.php" class="text-decoration-none">
                                    Sign Up
                                </a>
                            </p>

                            <div class="position-relative text-center my-4">
                                <hr>
                                <span class="bg-white px-3 position-absolute top-50 start-50 translate-middle">
                                    Or Continue With
                                </span>
                            </div>

                            <div class="d-flex justify-content-center gap-3">

                                <!-- Google Login -->
                                <a href="google-login.php"
                                    class="btn btn-outline-danger rounded-circle d-flex align-items-center justify-content-center"
                                    style="width:50px;height:50px;">
                                    <i class="fab fa-google"></i>
                                </a>

                                <!-- GitHub Login -->
                                <a href="github-login.php"
                                    class="btn btn-outline-dark rounded-circle d-flex align-items-center justify-content-center"
                                    style="width:50px;height:50px;">
                                    <i class="fab fa-github"></i>
                                </a>

                            </div>

                        </form>

                    </div>
                </div>

            </div>

        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="../assets/libs/global/global.min.js"></script>
    <script src="../assets/js/appSettings.js"></script>
    <script src="../assets/js/main.js"></script>

</body>

</html>
