<?php
// Admin area session (HR gets its own cookie in hr/main/require.php)
if (session_status() === PHP_SESSION_NONE) {
    session_name('JS_ADMIN');
    session_set_cookie_params([
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/Model.php';

require_once APPROOT . '/admin/controllers/UserController.php';   // creates $UserModel
require_once APPROOT . '/admin/controllers/AuthController.php';   // login uses $UserModel
require_once APPROOT . '/admin/controllers/CategoryController.php';
require_once APPROOT . '/admin/controllers/RolesController.php';
require_once APPROOT . '/admin/controllers/ContactController.php';