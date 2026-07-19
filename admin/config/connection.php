<?php

// Resolve the application root (three levels up: config -> admin -> project root).
if (!defined('APPROOT')) {
    define('APPROOT', dirname(dirname(__DIR__)));
}

// Ensure Composer autoloader + environment variables are available even when
// this file is included directly (e.g. from a controller) before functions.php.
if (!isset($GLOBALS['__ENV_LOADED'])) {
    $autoload = APPROOT . '/vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
    }

    if (class_exists(\Dotenv\Dotenv::class) && file_exists(APPROOT . '/.env')) {
        \Dotenv\Dotenv::createImmutable(APPROOT)->safeLoad();
    }

    $GLOBALS['__ENV_LOADED'] = true;
}

$DB_HOST     = $_ENV['DB_HOST']     ?? '127.0.0.1';
$DB_USERNAME = $_ENV['DB_USERNAME'] ?? 'root';
$DB_PASSWORD = $_ENV['DB_PASSWORD'] ?? '';
$DB_DATABASE = $_ENV['DB_DATABASE'] ?? 'job_search';
$DB_CHARSET  = $_ENV['DB_CHARSET']  ?? 'utf8mb4';

try {
    $dsn = "mysql:host=$DB_HOST;dbname=$DB_DATABASE;charset=$DB_CHARSET";

    $pdo = new PDO($dsn, $DB_USERNAME, $DB_PASSWORD, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}
