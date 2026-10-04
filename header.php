<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$current_page = basename($_SERVER['PHP_SELF'], '.php');

$page_title_map = [
    'dashboard'            => 'Dashboard',
    'inventory-list'       => 'Equipment Inventory',
    'add-equipment'        => 'Add Equipment',
    'equipment-details'    => 'Equipment Details',
    'tracking-logs'        => 'Tracking Logs',
    'maintenance-schedule' => 'Maintenance Schedule',
    'log-maintenance'      => 'Log Maintenance',
    'sterilization-log'    => 'Sterilisation Log',
    'reports-compliance'   => 'Compliance Reports',
    'activity-logs'        => 'Activity Log',
    'login'                => 'Sign In',
];

$html_title = ($page_title_map[$current_page] ?? ucfirst(str_replace('-', ' ', $current_page)))
    . ' — Surgical Equipment Mgmt';

$fullName = $_SESSION['full_name'] ?? 'User Name';
$initials = '';
$words = explode(' ', $fullName);
foreach ($words as $w) {
    $initials .= strtoupper(substr($w, 0, 1));
}
$initials = substr($initials, 0, 2);
if (empty($initials)) {
    $initials = 'US';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Surgical Equipment Management System">
    <title><?= htmlspecialchars($html_title, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/surgical_equipment/assets/css/style.css">
</head>
<body>
<?php if ($current_page !== 'login'): ?>
<div class="app-layout">
<?php endif; ?>

            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
        </button>
    </div>

    <div class="topbar-right">
        <!-- User Profile -->
        <div class="user-profile">
            <div class="user-avatar"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="user-details">
                <span class="user-name"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></span>
                <span class="user-role"><?= htmlspecialchars(str_replace('_', ' ', $_SESSION['role'] ?? 'Staff'), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
    </div>
</div>
<div class="app-layout">
<?php endif; ?>

        <!-- User Profile -->
        <div class="user-profile">
            <div class="user-avatar"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="user-details">
                <span class="user-name"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></span>
                <span class="user-role"><?= htmlspecialchars(str_replace('_', ' ', $_SESSION['role'] ?? 'Staff'), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
    </div>
</div>
<div class="app-layout">
            height: var(--topbar-height);
            background: var(--color-surface);
            border-bottom: 1px solid var(--color-border);
            display: flex; align-items: center; padding: 0 20px;
            box-shadow: var(--shadow);
        }
        .topbar-brand {
            font-size: var(--font-size-lg);
            font-weight: 700;
            color: var(--color-primary);
            display: flex; align-items: center; gap: 8px;
            margin-right: 16px;
        }
        .topbar-right {
            margin-left: auto;
            display: flex; align-items: center; gap: 12px;
        }
        .topbar-user {
            font-size: var(--font-size-sm);
            color: var(--color-text-secondary);
        }
        .topbar-user strong { color: var(--color-text); }
        .badge-role {
            display: inline-block; padding: 2px 10px;
            border-radius: 20px; font-size: var(--font-size-xs);
            font-weight: 600; text-transform: uppercase; letter-spacing: .04em;
        }
        .badge-admin            { background: #fee2e2; color: #991b1b; }
        .badge-surgeon          { background: #dbeafe; color: #1e40af; }
        .badge-nurse            { background: #dcfce7; color: #166534; }
        .badge-sterilisation_tech { background: #fef3c7; color: #92400e; }
        .badge-auditor          { background: #f3e8ff; color: #5b21b6; }

        /* Hamburger */
        #sidebarToggle {
            background: none; border: none; cursor: pointer;
            padding: 6px; border-radius: var(--radius);
            color: var(--color-text-secondary); font-size: 1.25rem;
            margin-right: 12px; display: flex; align-items: center;
        }
        #sidebarToggle:hover { background: var(--color-bg); color: var(--color-text); }

        /* ── Sidebar ─────────────────────────────────────────── */
        .sidebar {
            position: fixed; top: var(--topbar-height); left: 0;
            width: var(--sidebar-width);
            height: calc(100vh - var(--topbar-height));
            background: var(--color-surface);
            border-right: 1px solid var(--color-border);
            overflow-y: auto; overflow-x: hidden;
            display: flex; flex-direction: column;
            transition: transform .25s ease, width .25s ease;
            z-index: 90;
        }
        .sidebar.collapsed { width: 56px; }
        .sidebar.collapsed .nav-label,
        .sidebar.collapsed .sidebar-footer { display: none; }
        .sidebar-nav { flex: 1; padding: 16px 0; }
        .nav-list { list-style: none; }
        .nav-item { margin: 2px 8px; }
        .nav-link {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 12px; border-radius: var(--radius);
            color: var(--color-text-secondary);
            font-size: var(--font-size-sm); font-weight: 500;
            transition: background .15s, color .15s;
            white-space: nowrap;
        }
        .nav-link:hover { background: var(--color-bg); color: var(--color-text); text-decoration: none; }
        .nav-item.active .nav-link { background: var(--color-primary-light); color: var(--color-primary); }
        .nav-icon { font-size: 1rem; flex-shrink: 0; width: 20px; text-align: center; }
        .sidebar-footer {
            padding: 12px 16px;
            border-top: 1px solid var(--color-border);
            font-size: var(--font-size-xs);
            color: var(--color-text-secondary);
            display: flex; justify-content: space-between;
        }

        /* Mobile sidebar */
        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
        }

        /* ── Main content area ──────────────────────────────── */
        .main-content {
            margin-top: var(--topbar-height);
            margin-left: var(--sidebar-width);
            padding: 28px;
            flex: 1; min-width: 0;
            transition: margin-left .25s ease;
        }
        @media (max-width: 1024px) { .main-content { margin-left: 0; } }

        /* ── Cards ──────────────────────────────────────────── */
        .card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }
        .card-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 16px;
        }
        .card-title { font-size: var(--font-size-lg); font-weight: 600; }
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        /* ── Stat cards ─────────────────────────────────────── */
        .stat-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius);
            padding: 20px;
            display: flex; align-items: center; gap: 16px;
            box-shadow: var(--shadow);
        }
        .stat-icon { font-size: 2rem; }
        .stat-label { font-size: var(--font-size-xs); color: var(--color-text-secondary); text-transform: uppercase; letter-spacing: .05em; }
        .stat-value { font-size: var(--font-size-2xl); font-weight: 700; }

        /* ── Buttons ─────────────────────────────────────────── */
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px; border-radius: var(--radius);
            font-size: var(--font-size-sm); font-weight: 600;
            border: 1px solid transparent; cursor: pointer;
            transition: background .15s, opacity .15s;
            text-decoration: none;
        }
        .btn:hover { opacity: .88; text-decoration: none; }
        .btn-primary   { background: var(--color-primary); color: #fff; border-color: var(--color-primary); }
        .btn-secondary { background: var(--color-surface); color: var(--color-text); border-color: var(--color-border); }
        .btn-success   { background: var(--color-success); color: #fff; border-color: var(--color-success); }
        .btn-danger    { background: var(--color-danger);  color: #fff; border-color: var(--color-danger); }
        .btn-warning   { background: var(--color-warning); color: #fff; border-color: var(--color-warning); }
        .btn-sm  { padding: 4px 10px; font-size: var(--font-size-xs); }
        .btn-lg  { padding: 10px 22px; font-size: var(--font-size-base); }
        .w-full  { width: 100%; justify-content: center; }

        /* ── Forms ──────────────────────────────────────────── */
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: var(--font-size-sm); font-weight: 500; margin-bottom: 6px; }
        .form-control {
            width: 100%; padding: 8px 12px;
            border: 1px solid var(--color-border); border-radius: var(--radius);
            font-size: var(--font-size-sm); color: var(--color-text);
            background: var(--color-surface); transition: border-color .15s;
        }
        .form-control:focus { outline: none; border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(37,99,235,.12); }
        textarea.form-control { resize: vertical; }

        /* ── Tables ─────────────────────────────────────────── */
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: var(--font-size-sm); }
        th {
            text-align: left; padding: 10px 14px;
            background: var(--color-bg);
            border-bottom: 2px solid var(--color-border);
            font-size: var(--font-size-xs); text-transform: uppercase; letter-spacing: .06em;
            color: var(--color-text-secondary); font-weight: 600;
        }
        td { padding: 11px 14px; border-bottom: 1px solid var(--color-border); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: var(--color-bg); }

        /* ── Alerts ─────────────────────────────────────────── */
        .alert {
            padding: 12px 16px; border-radius: var(--radius);
            margin-bottom: 16px; font-size: var(--font-size-sm);
        }
        .alert-success { background: var(--color-success-light); color: #14532d; border: 1px solid #bbf7d0; }
        .alert-danger  { background: var(--color-danger-light);  color: #7f1d1d; border: 1px solid #fecaca; }
        .alert-warning { background: var(--color-warning-light); color: #78350f; border: 1px solid #fde68a; }
        .alert-info    { background: var(--color-primary-light); color: #1e3a8a; border: 1px solid #bfdbfe; }

        /* ── Status badges ──────────────────────────────────── */
        .status-badge {
            display: inline-block; padding: 2px 10px;
            border-radius: 20px; font-size: var(--font-size-xs); font-weight: 600;
        }
        .status-available       { background: #dcfce7; color: #166534; }
        .status-in_use          { background: #dbeafe; color: #1e40af; }
        .status-under_maintenance { background: #fef3c7; color: #92400e; }
        .status-sterilising     { background: #f3e8ff; color: #5b21b6; }
        .status-retired         { background: #f1f5f9; color: #475569; }
        .status-pending         { background: #fef3c7; color: #92400e; }
        .status-in_progress     { background: #dbeafe; color: #1e40af; }
        .status-completed       { background: #dcfce7; color: #166534; }
        .status-cancelled       { background: #f1f5f9; color: #475569; }
        .status-failed          { background: #fee2e2; color: #991b1b; }

        /* ── Utility ─────────────────────────────────────────── */
        .flex        { display: flex; }
        .flex-between { display: flex; justify-content: space-between; align-items: center; }
        .gap-2       { gap: 8px; }
        .gap-4       { gap: 16px; }
        .mt-1        { margin-top: 4px; }
        .mt-4        { margin-top: 16px; }
        .mb-2        { margin-bottom: 8px; }
        .mb-4        { margin-bottom: 16px; }
        .mb-6        { margin-bottom: 24px; }
        .text-muted  { color: var(--color-text-secondary); }
        .text-center { text-align: center; }

        /* ── Page header ─────────────────────────────────────── */
        .page-header {
            display: flex; align-items: flex-start; justify-content: space-between;
            margin-bottom: 24px; gap: 16px; flex-wrap: wrap;
        }
        .page-title { font-size: var(--font-size-2xl); font-weight: 700; }
        .page-breadcrumb { font-size: var(--font-size-xs); color: var(--color-text-secondary); margin-top: 2px; }
        .page-breadcrumb a { color: var(--color-text-secondary); }
        .page-breadcrumb a:hover { color: var(--color-primary); }

        /* ── Login page ─────────────────────────────────────── */
        .login-wrapper {
            min-height: 100vh; display: flex;
            align-items: center; justify-content: center;
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            padding: 20px;
        }
        .login-card {
            background: var(--color-surface);
            border-radius: 12px; padding: 40px;
            width: 100%; max-width: 420px;
            box-shadow: 0 20px 60px rgba(0,0,0,.3);
        }
        .login-logo {
            text-align: center; margin-bottom: 28px;
        }
        .login-logo-icon { font-size: 3rem; }
        .login-logo h1 { font-size: var(--font-size-xl); font-weight: 700; margin-top: 8px; }
        .login-logo p  { font-size: var(--font-size-sm); color: var(--color-text-secondary); }
    </style>
</head>
<body>
<?php if ($current_page !== 'login'): ?>
<div class="topbar">
    <button id="sidebarToggle" aria-label="Toggle sidebar">☰</button>
    <span class="topbar-brand">🏥 SurgicalEquip</span>
    <div class="topbar-right">
        <span class="topbar-user">
            <strong><?= htmlspecialchars($_SESSION['full_name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></strong>
        </span>
        <span class="badge-role badge-<?= htmlspecialchars($_SESSION['role'] ?? 'nurse', ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars(str_replace('_', ' ', $_SESSION['role'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </span>
        <a href="/surgical_equipment/auth/logout.php" class="btn btn-sm btn-secondary">Sign out</a>
    </div>
</div>
<div class="app-layout">
<?php endif; ?>
