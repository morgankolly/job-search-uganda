<?php


require_once 'require.php';

if (!isset($_SESSION['user_id'] )) {
    unset($_SESSION['username']);
    unset($_SESSION['user_id']);
    unset($_SESSION['password']);
    header("Location: ./index.php");
    exit;
}


