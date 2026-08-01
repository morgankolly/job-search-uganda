<?php
/**
 * Admin header + sidebar component.
 * Included at the top of every authenticated admin page.
 */

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../helpers/functions.php';

// Redirect if no session.
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Verify the session user still exists in the DB (guards against stale
// sessions after a DB re-import, password reset, or user deletion).
static $_headerUserVerified = false;
if (!$_headerUserVerified) {
    $_vStmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ? LIMIT 1");
    $_vStmt->execute([$_SESSION['user_id']]);
    if (!$_vStmt->fetch()) {
        session_unset();
        session_destroy();
        header('Location: index.php?reason=session_expired');
        exit;
    }
    $_headerUserVerified = true;
}

// ── Logged-in user ────────────────────────────────────────────────────────────
$_headerStmt = $pdo->prepare("
    SELECT u.user_name, u.email, u.profile, r.role_name AS role
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.role_id
    WHERE u.user_id = ?
");
$_headerStmt->execute([$_SESSION['user_id']]);
$user = $_headerStmt->fetch(PDO::FETCH_ASSOC) ?: [
    'user_name' => 'Admin',
    'email'     => '',
    'profile'   => '',
    'role'      => 'admin',
];

// Sync session name/role so other pages can read them.
$_SESSION['name']  = $user['user_name'];
$_SESSION['email'] = $user['email'];
$_SESSION['role']  = $user['role'];

// ── Live badge counts ─────────────────────────────────────────────────────────
$_pendingJobs = (int) $pdo->query(
    "SELECT COUNT(*) FROM guest_jobs WHERE status = 'pending'"
)->fetchColumn();

$_pendingApps = (int) $pdo->query(
    "SELECT COUNT(*) FROM applications WHERE status = 'pending'"
)->fetchColumn();

// ── Active-page detection ─────────────────────────────────────────────────────
$_currentPage = basename($_SERVER['PHP_SELF']);

function _isActive(string $page): string {
    global $_currentPage;
    return $_currentPage === $page ? 'active' : '';
}

// Profile avatar: if stored filename (no slash) build uploads path, else use as-is.
$_profileSrc = !empty($user['profile']) && strpos($user['profile'], '/') === false
    ? '../uploads/profile/' . htmlspecialchars($user['profile'])
    : (empty($user['profile']) ? '' : htmlspecialchars($user['profile']));
if (empty($_profileSrc)) {
    $_profileSrc = "http://localhost/Job-Search-System/uploads/profile/" . $filename;
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' – ' : '' ?>Job Search Uganda Admin</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

   
    <style>
        /* ── Sidebar ─────────────────────────────────────── */
        :root {
            --sidebar-w: 240px;
            --topbar-h: 56px;
            --sidebar-bg: #1a1f2e;
            --sidebar-hover: rgba(255,255,255,.06);
            --sidebar-active: rgba(13,110,253,.25);
            --sidebar-text: #c8cfdf;
            --sidebar-heading: #6b7280;
        }

        body { padding-left: var(--sidebar-w); padding-top: var(--topbar-h); }

        /* Topbar */
        #adminTopbar {
            position: fixed; top: 0; left: var(--sidebar-w); right: 0;
            height: var(--topbar-h); z-index: 1030;
            background: #fff; border-bottom: 1px solid #e5e7eb;
            display: flex; align-items: center; padding: 0 1.25rem; gap: .75rem;
        }
        [data-bs-theme="dark"] #adminTopbar {
            background: #111827; border-color: #374151;
        }

        /* Sidebar */
        #adminSidebar {
            position: fixed; top: 0; left: 0; bottom: 0;
            width: var(--sidebar-w); z-index: 1040;
            background: var(--sidebar-bg);
            display: flex; flex-direction: column;
            overflow-y: auto; overflow-x: hidden;
        }
        .sidebar-brand {
            display: flex; align-items: center; gap: .75rem;
            padding: 1rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,.07);
            min-height: var(--topbar-h); text-decoration: none;
        }
        .sidebar-brand img { width: 32px; height: 32px; border-radius: 8px; }
        .sidebar-brand span { color: #fff; font-weight: 700; font-size: .95rem; }

        /* Nav */
        .sidebar-nav { flex: 1; padding: .75rem 0; }
        .nav-heading {
            font-size: .68rem; font-weight: 600; letter-spacing: .08em;
            text-transform: uppercase; color: var(--sidebar-heading);
            padding: 1rem 1.25rem .35rem;
        }
        .sidebar-link {
            display: flex; align-items: center; gap: .75rem;
            padding: .55rem 1.25rem; text-decoration: none;
            color: var(--sidebar-text); font-size: .875rem;
            border-radius: 6px; margin: 1px .5rem;
            transition: background .15s, color .15s;
            position: relative;
        }
        .sidebar-link:hover { background: var(--sidebar-hover); color: #fff; }
        .sidebar-link.active {
            background: var(--sidebar-active); color: #60a5fa; font-weight: 600;
        }
        .sidebar-link .fa, .sidebar-link .fas, .sidebar-link .far, .sidebar-link .fab {
            width: 18px; text-align: center; font-size: .9rem;
        }
        .sidebar-badge {
            margin-left: auto;
            font-size: .65rem; padding: .2em .55em;
        }

        /* Collapsible sub-items */
        .sidebar-sub { list-style: none; padding: 0; margin: 0; }
        .sidebar-sub .sidebar-link { padding-left: 2.75rem; font-size: .83rem; }

        /* Sidebar footer */
        .sidebar-footer {
            padding: .75rem 1rem; border-top: 1px solid rgba(255,255,255,.07);
        }
        .sidebar-footer a {
            display: flex; align-items: center; gap: .5rem;
            color: var(--sidebar-text); text-decoration: none;
            font-size: .82rem; padding: .4rem .5rem; border-radius: 5px;
        }
        .sidebar-footer a:hover { background: var(--sidebar-hover); color: #fff; }

        /* Mobile: collapse sidebar */
        @media (max-width: 991.98px) {
            body { padding-left: 0; }
            #adminTopbar { left: 0; }
            #adminSidebar { transform: translateX(-100%); transition: transform .25s; }
            #adminSidebar.show { transform: translateX(0); }
            #sidebarBackdrop {
                display: none; position: fixed; inset: 0; z-index: 1039;
                background: rgba(0,0,0,.45);
            }
            #sidebarBackdrop.show { display: block; }
        }

        /* ── Dark mode — full admin area ─────────────────── */
        [data-bs-theme="dark"] body {
            background: #0f1117 !important;
            color: #e5e7eb;
        }
        [data-bs-theme="dark"] #adminTopbar {
            background: #111827 !important;
            border-color: #1f2937 !important;
            color: #f3f4f6;
        }
        [data-bs-theme="dark"] .card {
            background: #1c2333 !important;
            border-color: #1f2937 !important;
        }
        [data-bs-theme="dark"] .card-header,
        [data-bs-theme="dark"] .card-footer {
            background: #1c2333 !important;
            border-color: #2d3748 !important;
        }
        [data-bs-theme="dark"] .table {
            color: #d1d5db;
        }
        [data-bs-theme="dark"] .table thead th,
        [data-bs-theme="dark"] .table-light {
            background: #111827 !important;
            color: #9ca3af;
            border-color: #374151;
        }
        [data-bs-theme="dark"] .table tbody tr:hover td {
            background: rgba(255,255,255,.04);
        }
        [data-bs-theme="dark"] .table td, [data-bs-theme="dark"] .table th {
            border-color: #2d3748;
        }
        [data-bs-theme="dark"] .form-control,
        [data-bs-theme="dark"] .form-select {
            background: #1f2937;
            border-color: #374151;
            color: #e5e7eb;
        }
        [data-bs-theme="dark"] .form-control::placeholder { color: #6b7280; }
        [data-bs-theme="dark"] .form-control:focus,
        [data-bs-theme="dark"] .form-select:focus {
            background: #1f2937;
            border-color: #6366f1;
            color: #f3f4f6;
            box-shadow: 0 0 0 3px rgba(99,102,241,.2);
        }
        [data-bs-theme="dark"] .input-group-text {
            background: #111827;
            border-color: #374151;
            color: #6b7280;
        }
        [data-bs-theme="dark"] .modal-content {
            background: #1c2333;
            border-color: #2d3748;
        }
        [data-bs-theme="dark"] .modal-header,
        [data-bs-theme="dark"] .modal-footer {
            border-color: #2d3748;
        }
        [data-bs-theme="dark"] .dropdown-menu {
            background: #1c2333;
            border-color: #374151;
        }
        [data-bs-theme="dark"] .dropdown-item {
            color: #d1d5db;
        }
        [data-bs-theme="dark"] .dropdown-item:hover {
            background: #2d3748;
            color: #f3f4f6;
        }
        [data-bs-theme="dark"] .dropdown-divider { border-color: #374151; }
        [data-bs-theme="dark"] .alert-success {
            background: #064e3b; border-color: #065f46; color: #6ee7b7;
        }
        [data-bs-theme="dark"] .alert-danger {
            background: #7f1d1d; border-color: #991b1b; color: #fca5a5;
        }
        [data-bs-theme="dark"] .alert-warning {
            background: #78350f; border-color: #92400e; color: #fcd34d;
        }
        [data-bs-theme="dark"] .alert-info {
            background: #1e3a5f; border-color: #1e40af; color: #93c5fd;
        }
        [data-bs-theme="dark"] .text-muted { color: #9ca3af !important; }
        [data-bs-theme="dark"] .border,
        [data-bs-theme="dark"] .border-top,
        [data-bs-theme="dark"] .border-bottom { border-color: #374151 !important; }
        [data-bs-theme="dark"] hr { border-color: #374151; }
        [data-bs-theme="dark"] .bg-light { background: #1f2937 !important; }
        [data-bs-theme="dark"] .bg-white { background: #1c2333 !important; }
        [data-bs-theme="dark"] .shadow-sm {
            box-shadow: 0 1px 6px rgba(0,0,0,.4) !important;
        }

        /* ── Theme toggle pill button ───────────────────── */
        #themePill {
            display: inline-flex; align-items: center; gap: .35rem;
            padding: .28rem .7rem; border-radius: 20px;
            font-size: .78rem; font-weight: 600;
            border: 1.5px solid #e5e7eb;
            background: #f9fafb; color: #374151;
            cursor: pointer; transition: all .2s;
            user-select: none;
        }
        #themePill:hover { border-color: #6366f1; color: #6366f1; }
        [data-bs-theme="dark"] #themePill {
            background: #1f2937; border-color: #374151; color: #d1d5db;
        }
        [data-bs-theme="dark"] #themePill:hover { border-color: #818cf8; color: #818cf8; }
        #themePillIcon { transition: transform .35s; }
        #themePill.rotating #themePillIcon { transform: rotate(360deg); }
    </style>
</head>
<body>

<!-- ═══════════════════════════════════════════
     SIDEBAR
═════════════════════════════════════════════ -->
<div id="sidebarBackdrop" onclick="toggleSidebar()"></div>

<aside id="adminSidebar">

    <!-- Brand -->
    <a href="dashboard.php" class="sidebar-brand">
        <div style="width:32px;height:32px;background:#0d6efd;border-radius:8px;display:flex;align-items:center;justify-content:center;">
            <i class="fas fa-briefcase text-white" style="font-size:.85rem;"></i>
        </div>
        <span>Job Search Uganda</span>
    </a>

    <!-- Navigation -->
    <nav class="sidebar-nav">

        <!-- Overview -->
        <div class="nav-heading">Overview</div>

        <a href="dashboard.php" class="sidebar-link <?= _isActive('dashboard.php') ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
            <?php if ($_pendingJobs > 0): ?>
                <span class="badge bg-warning text-dark sidebar-badge"><?= $_pendingJobs ?></span>
            <?php endif; ?>
        </a>

        <!-- Jobs -->
        <div class="nav-heading">Jobs</div>

        <a href="job-post.php" class="sidebar-link <?= _isActive('job-post.php') ?>">
            <i class="fas fa-plus-circle"></i>
            <span>Post a Job</span>
        </a>

        <!-- Manage Jobs (collapsible) -->
        <a class="sidebar-link <?= in_array($_currentPage, ['jobs.php']) ? 'active' : '' ?>"
           href="#manageJobsSub" data-bs-toggle="collapse"
           aria-expanded="<?= in_array($_currentPage, ['jobs.php']) ? 'true' : 'false' ?>">
            <i class="fas fa-briefcase"></i>
            <span>Manage Jobs</span>
            <?php if ($_pendingJobs > 0): ?>
                <span class="badge bg-warning text-dark sidebar-badge"><?= $_pendingJobs ?></span>
            <?php else: ?>
                <i class="fas fa-chevron-down ms-auto" style="font-size:.65rem;opacity:.5;"></i>
            <?php endif; ?>
        </a>
        <div class="collapse <?= in_array($_currentPage, ['jobs.php']) ? 'show' : '' ?>" id="manageJobsSub">
            <ul class="sidebar-sub">
                <li>
                    <a href="jobs.php" class="sidebar-link <?= _isActive('jobs.php') ?>">
                        <i class="fas fa-list"></i><span>All Jobs</span>
                    </a>
                </li>
                <li>
                    <a href="jobs.php?filter=pending" class="sidebar-link">
                        <i class="fas fa-clock"></i>
                        <span>Pending Approval</span>
                        <?php if ($_pendingJobs > 0): ?>
                            <span class="badge bg-warning text-dark sidebar-badge"><?= $_pendingJobs ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="jobs.php?filter=approved" class="sidebar-link">
                        <i class="fas fa-check-circle"></i><span>Approved Guest Jobs</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Applications -->
        <div class="nav-heading">Recruitment</div>

        <a href="applications.php" class="sidebar-link <?= _isActive('applications.php') ?>">
            <i class="fas fa-file-alt"></i>
            <span>Applications</span>
            <?php if ($_pendingApps > 0): ?>
                <span class="badge bg-danger sidebar-badge"><?= $_pendingApps ?></span>
            <?php endif; ?>
        </a>

        <!-- Admin -->
        <div class="nav-heading">Administration</div>

        <a href="register.php" class="sidebar-link <?= _isActive('register.php') ?>">
            <i class="fas fa-user-plus"></i>
            <span>Add Admin User</span>
        </a>

        <!-- Public site link -->
        <div class="nav-heading">Site</div>

        <a href="../index.php" target="_blank" class="sidebar-link">
            <i class="fas fa-globe"></i>
            <span>View Public Site</span>
            <i class="fas fa-external-link-alt ms-auto" style="font-size:.65rem;opacity:.5;"></i>
        </a>

        <a href="../job_posting.php" target="_blank" class="sidebar-link">
            <i class="fas fa-bullhorn"></i>
            <span>Guest Posting Page</span>
            <i class="fas fa-external-link-alt ms-auto" style="font-size:.65rem;opacity:.5;"></i>
        </a>

    </nav>

    <!-- Sidebar footer: theme toggle + logout -->
    <div class="sidebar-footer">
        <a href="#" onclick="toggleTheme(); return false;" id="sidebarThemeLink">
            <i id="sidebarThemeIcon" class="fas fa-moon"></i>
            <span id="sidebarThemeLabel">Dark Mode</span>
        </a>
        <a href="logout.php" class="mt-1">
            <i class="fas fa-sign-out-alt text-danger"></i>
            <span>Log Out</span>
        </a>
    </div>

</aside>

<!-- ═══════════════════════════════════════════
     TOP NAVBAR
═════════════════════════════════════════════ -->
<nav id="adminTopbar">

    <!-- Mobile hamburger -->
    <button class="btn btn-sm btn-outline-secondary d-lg-none me-2 border-0"
            onclick="toggleSidebar()" aria-label="Toggle sidebar">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Page title (optional breadcrumb area) -->
    <span class="fw-semibold text-muted d-none d-md-inline" style="font-size:.9rem;">
        <?php
        $pageTitles = [
            'dashboard.php'    => 'Dashboard',
            'job-post.php'     => 'Post a Job',
            'jobs.php'         => 'Manage Jobs',
            'applications.php' => 'Applications',
            'register.php'     => 'Add Admin User',
            'profile.php'      => 'My Profile',
        ];
        echo $pageTitles[$_currentPage] ?? 'Admin Panel';
        ?>
    </span>

    <!-- Spacer -->
    <div class="flex-grow-1"></div>

    <!-- Pending badges (topbar shortcuts) -->
    <?php if ($_pendingJobs > 0): ?>
        <a href="jobs.php?filter=pending" class="btn btn-sm btn-warning me-2 d-none d-sm-inline-flex align-items-center gap-1">
            <i class="fas fa-clock"></i>
            <span><?= $_pendingJobs ?> pending</span>
        </a>
    <?php endif; ?>

    <!-- Theme toggle pill -->
    <button id="themePill" onclick="toggleTheme()" title="Toggle light / dark mode">
        <i id="themePillIcon" class="fas fa-moon"></i>
        <span id="themePillLabel">Dark</span>
    </button>

    <!-- User dropdown -->
    <div class="dropdown">
        <a href="#" class="d-flex align-items-center gap-2 text-decoration-none"
           data-bs-toggle="dropdown" aria-expanded="false">
            <img src="<?= $_profileSrc ?>"
                 alt="<?= htmlspecialchars($user['user_name']) ?>"
                 width="34" height="34"
                 class="rounded-circle border"
                 style="object-fit:cover;">
            <div class="d-none d-md-block lh-sm">
                <div class="fw-semibold" style="font-size:.85rem;">
                    <?= htmlspecialchars($user['user_name']) ?>
                </div>
                <div class="text-muted" style="font-size:.73rem;">
                    <?= htmlspecialchars(ucfirst($user['role'] ?? 'Admin')) ?>
                </div>
            </div>
            <i class="fas fa-chevron-down text-muted d-none d-md-inline" style="font-size:.65rem;"></i>
        </a>

        <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:200px;">
            <!-- Mini profile header -->
            <li class="px-3 py-2 d-flex align-items-center gap-2 border-bottom mb-1">
                <img src="<?= $_profileSrc ?>" width="38" height="38"
                     class="rounded-circle" style="object-fit:cover;" alt="">
                <div class="lh-sm">
                    <div class="fw-semibold" style="font-size:.85rem;">
                        <?= htmlspecialchars($user['user_name']) ?>
                    </div>
                    <div class="text-muted" style="font-size:.75rem;">
                        <?= htmlspecialchars($user['email']) ?>
                    </div>
                </div>
            </li>

            <li>
                <a class="dropdown-item d-flex align-items-center gap-2"
                   href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
                    <i class="fas fa-user fa-fw text-muted"></i> My Profile
                </a>
            </li>
            <li>
                <a class="dropdown-item d-flex align-items-center gap-2" href="register.php">
                    <i class="fas fa-user-plus fa-fw text-muted"></i> Add User
                </a>
            </li>

            <li><hr class="dropdown-divider my-1"></li>

            <li>
                <a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="logout.php">
                    <i class="fas fa-sign-out-alt fa-fw"></i> Log Out
                </a>
            </li>
        </ul>
    </div>

</nav>

<!-- ═══════════════════════════════════════════
     PROFILE MODAL
═════════════════════════════════════════════ -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title">My Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center pt-2">
                <img src="<?= $_profileSrc ?>"
                     class="rounded-circle mb-2 border"
                     width="80" height="80"
                     style="object-fit:cover;"
                     alt="<?= htmlspecialchars($user['user_name']) ?>">

                <h6 class="mb-0"><?= htmlspecialchars($user['user_name']) ?></h6>
                <span class="badge bg-primary mb-3"><?= htmlspecialchars(ucfirst($user['role'] ?? 'Admin')) ?></span>

                <hr class="my-2">

                <div class="text-start small">
                    <p class="mb-1">
                        <i class="fas fa-envelope text-muted me-2"></i>
                        <?= htmlspecialchars($user['email'] ?: 'Not set') ?>
                    </p>
                    <p class="mb-0">
                        <i class="fas fa-circle text-success me-2" style="font-size:.5rem;vertical-align:middle;"></i>
                        Active
                    </p>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════
     SCRIPTS (sidebar toggle + theme)
═════════════════════════════════════════════ -->
<script>
/* ── Sidebar toggle (mobile) ── */
function toggleSidebar() {
    document.getElementById('adminSidebar').classList.toggle('show');
    document.getElementById('sidebarBackdrop').classList.toggle('show');
}

/* ── Theme toggle ── */
function applyTheme(theme) {
    const html      = document.documentElement;
    const isDark    = theme === 'dark';

    html.setAttribute('data-bs-theme', isDark ? 'dark' : 'light');
    localStorage.setItem('adminTheme', theme);

    // Topbar pill
    const pill      = document.getElementById('themePill');
    const pillIcon  = document.getElementById('themePillIcon');
    const pillLabel = document.getElementById('themePillLabel');
    if (pillIcon)  pillIcon.className  = isDark ? 'fas fa-sun'  : 'fas fa-moon';
    if (pillLabel) pillLabel.textContent = isDark ? 'Light' : 'Dark';

    // Spin animation
    if (pill) {
        pill.classList.add('rotating');
        setTimeout(() => pill.classList.remove('rotating'), 400);
    }

    // Sidebar footer link
    const sIcon  = document.getElementById('sidebarThemeIcon');
    const sLabel = document.getElementById('sidebarThemeLabel');
    if (sIcon)  sIcon.className   = isDark ? 'fas fa-sun text-warning' : 'fas fa-moon';
    if (sLabel) sLabel.textContent = isDark ? 'Light Mode' : 'Dark Mode';
}

function toggleTheme() {
    const current = localStorage.getItem('adminTheme') || 'light';
    applyTheme(current === 'dark' ? 'light' : 'dark');
}

document.addEventListener('DOMContentLoaded', function () {
    const saved = localStorage.getItem('adminTheme') || 'light';
    applyTheme(saved);
});
</script>
