<?php

$role = $_SESSION['role'] ?? 'guest';

$base = '/surgical_equipment';

$menu_items = [
    'dashboard' => [
        'label' => 'Dashboard',
        'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>',
        'href'  => $base . '/dashboard.php',
        'match' => ['dashboard'],
        'roles' => ['admin','surgeon','nurse','sterilisation_tech','auditor'],
    ],
    'equipment' => [
        'label' => 'Equipment Inventory',
        'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>',
        'href'  => $base . '/inventory-list.php',
        'match' => ['inventory-list','add-equipment','equipment-details','check-out','check-in'],
        'roles' => ['admin','surgeon','nurse','sterilisation_tech','auditor'],
    ],
    'suppliers' => [
        'label' => 'Suppliers',
        'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="9" y1="6" x2="9" y2="6.01"></line><line x1="15" y1="6" x2="15" y2="6.01"></line><line x1="9" y1="10" x2="9" y2="10.01"></line><line x1="15" y1="10" x2="15" y2="10.01"></line><line x1="9" y1="14" x2="9" y2="14.01"></line><line x1="15" y1="14" x2="15" y2="14.01"></line><path d="M10 22v-4h4v4"></path></svg>',
        'href'  => $base . '/suppliers.php',
        'match' => ['suppliers','add-supplier'],
        'roles' => ['admin','auditor','nurse'],
    ],
    'purchases' => [
        'label' => 'Purchase Orders',
        'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>',
        'href'  => $base . '/purchases.php',
        'match' => ['purchases','add-purchase'],
        'roles' => ['admin','auditor'],
    ],
    'tracking' => [
        'label' => 'Tracking Logs',
        'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>',
        'href'  => $base . '/tracking-logs.php',
        'match' => ['tracking-logs'],
        'roles' => ['admin','nurse','sterilisation_tech'],
    ],
    'maintenance' => [
        'label' => 'Maintenance',
        'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>',
        'href'  => $base . '/maintenance-schedule.php',
        'match' => ['maintenance-schedule','log-maintenance'],
        'roles' => ['admin','sterilisation_tech','nurse'],
    ],
        'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>',
        'href'  => $base . '/sterilization-log.php',
        'match' => ['sterilization-log'],
        'roles' => ['admin','sterilisation_tech'],
    ],
    'reports' => [
        'label' => 'Compliance',
        'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>',
        'href'  => $base . '/reports-compliance.php',
        'match' => ['reports-compliance'],
        'roles' => ['admin','auditor','sterilisation_tech'],
    ],
    'audit' => [
        'label' => 'Activity Log',
        'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>',
        'href'  => $base . '/activity-logs.php',
        'match' => ['activity-logs'],
        'roles' => ['admin','auditor'],
    ],
];
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <span class="brand-text">SurgicalEquip</span>
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
    <div class="sidebar-footer">
        <a href="/surgical_equipment/auth/logout.php" class="sidebar-logout">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            <span>Logout</span>
        </a>
    </div>
</aside>

            <span>Sterilisation Rate</span>
            <span>94%</span>
        </div>
        <div class="sidebar-widget-progress">
            <div class="sidebar-widget-progress-fill" style="width: 94%;"></div>
        </div>
    </div>

    <div class="sidebar-footer">
        <a href="/surgical_equipment/auth/logout.php" class="sidebar-logout">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            <span>Logout</span>
        </a>
    </div>
</aside>
