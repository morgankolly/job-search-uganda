<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include './main/require.php';
require_once './config/connection.php';
require_once './helpers/functions.php';
require_once './models/UserModel.php';

$userModel = new UserModel($pdo);

$message = "";


?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register</title>

  <!-- CSS -->
  <link rel="stylesheet" href="../assets/css/styles.css">
  <link rel="stylesheet" href="../assets/libs/fontawesome/css/all.min.css">


</head>



<body style="min-height:100vh; display:flex; align-items:center; justify-content:center; background:#f5f6fa;">

  <div class="container">
    <div class="row justify-content-center">

      <div class="col-lg-5 col-md-7 col-sm-10">

        <div class="card shadow-lg border-0 rounded-4 p-4">

          <div class="text-center mb-3">
            <a href="../index.html">
              <img src="../uploads/profile/Job-Search-logo.png" alt="Logo" style="width:80px;height:auto;">
            </a>
          </div>

          <!-- Heading -->
          <div class="text-center mb-4">
            <h2 class="fw-bold">Sign Up Here!</h2>
            <p class="text-muted">Sign up to create your secure admin.</p>
          </div>

          <!-- FORM -->
        <form method="POST" enctype="multipart/form-data">

    <div class="mb-3">
        <label class="form-label">Full Name</label>
        <input
    type="text"
    class="form-control"
    name="username"
    placeholder="Full Name"
    required>
    </div>

    <div class="mb-3">
        <label class="form-label">Email Address</label>
        <input type="email" class="form-control" name="email" placeholder="info@example.com" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" class="form-control" name="password" placeholder="********" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Profile Image</label>
        <input class="form-control" type="file" name="profile" accept="image/*">
    </div>

    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" id="termsConditions" required>
        <label class="form-check-label">
            I agree to privacy policy & terms
        </label>
    </div>

   <button type="submit" name="createUser" class="btn btn-primary w-100">
    Sign Up
</button>

    <p class="text-center">
        Have an account?
        <a href="index.php">Sign In</a>
    </p>

</form>

        </div>

      </div>

    </div>
  </div>



  <!-- JS -->
  <script src="../assets/libs/global/global.min.js"></script>
  <script src="../assets/js/appSettings.js"></script>
  <script src="../assets/js/main.js"></script>

</body>

</html>