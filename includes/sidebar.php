<?php
$role = $_SESSION['role'] ?? 'guest';
$base = '/surgical_equipment';

$menu_items = [
    'dashboard' => [
        'label' => 'Dashboard',
        'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>',
        'href'  => $base . '/dashboard.php',
        'match' => ['dashboard'],
        'roles' => ['admin','surgeon','nurse','sterilisation_tech','auditor'],
    ],
    'equipment' => [
        'label' => 'Inventory',
        'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>',
        'href'  => $base . '/equipment/inventory-list.php',
        'match' => ['inventory-list','add-equipment','equipment-details','check-out','check-in'],
        'roles' => ['admin','surgeon','nurse','sterilisation_tech','auditor'],
    ],
    'suppliers' => [
        'label' => 'Suppliers',
        'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="9" y1="6" x2="9" y2="6.01"></line><line x1="15" y1="6" x2="15" y2="6.01"></line><line x1="9" y1="10" x2="9" y2="10.01"></line><line x1="15" y1="10" x2="15" y2="10.01"></line><line x1="9" y1="14" x2="9" y2="14.01"></line><line x1="15" y1="14" x2="15" y2="14.01"></line><path d="M10 22v-4h4v4"></path></svg>',
        'href'  => $base . '/suppliers/suppliers.php',
        'match' => ['suppliers','add-supplier'],
        'roles' => ['admin','auditor','nurse'],
    ],
    'purchases' => [
        'label' => 'Purchase Orders',
        'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>',
        'href'  => $base . '/purchases/purchases.php',
        'match' => ['purchases','add-purchase'],
        'roles' => ['admin','auditor'],
    ],
    'tracking' => [
        'label' => 'Tracking Logs',
        'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>',
        'href'  => $base . '/tracking/tracking-logs.php',
        'match' => ['tracking-logs'],
        'roles' => ['admin','nurse','sterilisation_tech'],
    ],
    'maintenance' => [
        'label' => 'Maintenance',
        'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>',
        'href'  => $base . '/maintenance/maintenance-schedule.php',
        'match' => ['maintenance-schedule','log-maintenance'],
        'roles' => ['admin','sterilisation_tech','nurse'],
    ],
    'sterilisation' => [
        'label' => 'Sterilisation Log',
        'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>',
        'href'  => $base . '/sterilization/sterilization-log.php',
        'match' => ['sterilization-log'],
        'roles' => ['admin','sterilisation_tech'],
    ],
    'reports' => [
        'label' => 'Compliance',
        'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>',
        'href'  => $base . '/reports/reports-compliance.php',
        'match' => ['reports-compliance'],
        'roles' => ['admin','auditor','sterilisation_tech'],
    ],
    'inquiries' => [
        'label' => 'Web Leads & Quotes',
        'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>',
        'href'  => $base . '/reports/inquiries.php',
        'match' => ['inquiries'],
        'roles' => ['admin','auditor','nurse'],
    ],
    'audit' => [
        'label' => 'Activity Log',
        'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>',
        'href'  => $base . '/reports/activity-logs.php',
        'match' => ['activity-logs'],
        'roles' => ['admin','auditor'],
    ],
];
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header" style="display:flex; align-items:center; justify-content:space-between;">
        <a href="/surgical_equipment/dashboard.php" style="text-decoration:none; display:flex; align-items:baseline; gap:6px;">
            <span class="brand-text" style="color:var(--color-primary); font-family:var(--font-serif); font-size:1.65rem; font-weight:700; letter-spacing:-0.02em;">APEX</span>
            <span style="font-size:0.6875rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; background:var(--color-primary-light); color:var(--color-primary); padding:2px 6px; border-radius:4px;">Admin</span>
        </a>
        <a href="/surgical_equipment/index.php" target="_blank" title="View Public Website" style="color:var(--color-text-muted); font-size:0.75rem; display:flex; align-items:center; text-decoration:none; padding:4px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
        </a>
    </div>
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <?php foreach ($menu_items as $key => $menuItem): ?>
                <?php if (in_array($role, $menuItem['roles'], true)): ?>
                    <?php $isActive = in_array($current_page, $menuItem['match'], true); ?>
                    <li class="nav-item <?= $isActive ? 'active' : '' ?>">
                        <a href="<?= htmlspecialchars($menuItem['href'], ENT_QUOTES, 'UTF-8') ?>" class="nav-link">
                            <span class="nav-icon"><?= $menuItem['icon'] ?></span>
                            <span class="nav-label"><?= htmlspecialchars($menuItem['label'], ENT_QUOTES, 'UTF-8') ?></span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </nav>
    <?php
    $fullName = $_SESSION['full_name'] ?? 'User Name';
    $initials = '';
    $words = explode(' ', $fullName);
    foreach ($words as $w) {
        $initials .= strtoupper(substr($w, 0, 1));
    }
    $initials = substr($initials, 0, 2);
    if (empty($initials)) $initials = 'US';
    ?>
    <div class="sidebar-footer" style="display:flex; flex-direction:column; align-items:stretch;">
        <div class="user-profile" style="padding:0 14px 12px 14px; margin-bottom:8px; border-bottom:1px solid var(--color-border); display:flex; align-items:center; gap:10px;">
            <div class="user-avatar" style="width:32px; height:32px; font-size:0.75rem; flex-shrink:0;"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
            <div style="min-width:0; display:flex; flex-direction:column;">
                <span style="font-size:0.8125rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; display:block; font-weight:600; color:var(--color-text-heading);"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></span>
                <span style="font-size:0.6875rem; text-transform:capitalize; color:var(--color-text-muted); display:block; margin-top:1px;"><?= htmlspecialchars(str_replace('_', ' ', $_SESSION['role'] ?? 'Staff'), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
        <a href="/surgical_equipment/auth/logout.php" class="sidebar-logout" style="padding:10px 14px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            <span>Logout</span>
        </a>
    </div>
</aside>
