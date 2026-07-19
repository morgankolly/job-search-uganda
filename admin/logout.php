 <?php
require_once './main/require.php';

 unset($_SESSION['username']);
    unset($_SESSION['user_id']);
    unset($_SESSION['password']);
    header("Location: index.php");
    exit;