<?php
// admin/controllers/HrDashboardController.php
// The session is already started by hr/main/require.php (via the HR auth middleware).

error_reporting(E_ALL);
ini_set('display_errors', '1');          // development only: turn off in production

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../models/HrDashboardModel.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    exit('Database connection is unavailable.');
}

// ---------- Who is viewing ----------
$userId   = (int) ($_SESSION['user_id'] ?? 0);
$roleName = strtolower(trim((string) ($_SESSION['role_name'] ?? '')));
$isAdmin  = ($roleName === 'admin');
$isHr     = ($roleName === 'hr');

if ($userId === 0 || (!$isAdmin && !$isHr)) {
    http_response_code(403);
    exit('Access denied. HR or Admin only.');
}

// ---------- Data from the model ----------
$hrDashboardModel = new HrDashboardModel($pdo);

try {
    $data = $hrDashboardModel->getDashboardData($userId, $isAdmin);
} catch (RuntimeException $e) {
    http_response_code(500);
    exit($e->getMessage());
}

$user                 = $hrDashboardModel->getUserProfile($userId);
$totalJobs            = $data['totalJobs'];
$openJobs             = $data['openJobs'];
$totalApps            = $data['totalApps'];
$newAppsWeek          = $data['newAppsWeek'];
$pendingApps          = $data['pendingApps'];
$appStatuses          = $data['appStatuses'];
$monthlyApps          = $data['monthlyApps'];
$recentApps           = $data['recentApps'];
$jobsByCategory       = $data['jobsByCategory'];
$totalAppointments    = $data['totalAppointments'];
$upcomingAppointments = $data['upcomingAppointments'];

// ---------- View helpers (data only) ----------
$statusColors = [
    'pending'     => 'badge bg-warning text-dark',
    'reviewed'    => 'badge bg-info text-dark',
    'shortlisted' => 'badge bg-success',
    'rejected'    => 'badge bg-danger',
    'accepted'    => 'badge bg-success',
    'approved'    => 'badge bg-success',
];

// Safe JSON for Chart.js; empty charts render without PHP notices.
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;

$chartMonthLabel = json_encode(array_values(array_column($monthlyApps, 'month')), $jsonFlags) ?: '[]';
$chartMonthData  = json_encode(array_map('intval', array_column($monthlyApps, 'cnt')), $jsonFlags) ?: '[]';

$chartAppLabels  = json_encode(array_map('strval', array_keys($appStatuses)), $jsonFlags) ?: '[]';
$chartAppData    = json_encode(array_map('intval', array_values($appStatuses)), $jsonFlags) ?: '[]';

$chartCatLabels  = json_encode(array_values(array_column($jobsByCategory, 'category_name')), $jsonFlags) ?: '[]';
$chartCatData    = json_encode(array_map('intval', array_column($jobsByCategory, 'cnt')), $jsonFlags) ?: '[]';