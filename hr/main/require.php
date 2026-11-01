<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('JS_HR');
    session_set_cookie_params([
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/../../admin/main/Model.php';   // defines APPROOT, DB connection

require_once APPROOT . '/admin/controllers/UserController.php';
require_once APPROOT . '/admin/controllers/AuthController.php';