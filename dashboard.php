<?php

require_once __DIR__ . '/config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /surgical_equipment/auth/login.php');
    exit;
}

$role     = $_SESSION['role'] ?? '';
$fullName = $_SESSION['full_name'] ?? 'User';

$firstName = explode(' ', $fullName)[0];

try {
    $pdo = getDbConnection();

    $totalsStmt = $pdo->query("
        SELECT
            COUNT(*)                                                  AS total,
            SUM(status = 'available')                                 AS count_available,
            SUM(status = 'in_use')                                    AS count_in_use,
            SUM(status = 'under_maintenance')                         AS count_maintenance,
            SUM(status = 'sterilising')                               AS count_sterilising
        FROM equipment_inventory
        WHERE status != 'retired'
    ");
    $counts = $totalsStmt->fetch();

    $total       = (int) ($counts['total'] ?? 0);
    $available   = (int) ($counts['count_available'] ?? 0);
    $inUse       = (int) ($counts['count_in_use'] ?? 0);
    $maintenance = (int) ($counts['count_maintenance'] ?? 0);
    $sterilising = (int) ($counts['count_sterilising'] ?? 0);

    $overdueStmt = $pdo->query("
        SELECT COUNT(*) AS overdue
        FROM maintenance_records
        WHERE status = 'pending'
          AND scheduled_date IS NOT NULL
          AND scheduled_date < CURDATE()
     ");
    $overdueMaint = (int) $overdueStmt->fetch()['overdue'];

    $logsStmt = $pdo->query("
        SELECT tl.action, tl.to_location AS location, tl.created_at,
               ei.equipment_code, ei.name AS equipment_name,
               u.full_name AS user_name
        FROM tracking_logs tl
        JOIN equipment_inventory ei ON tl.equipment_id = ei.id
        LEFT JOIN users u ON tl.user_id = u.id
        ORDER BY tl.created_at DESC
        LIMIT 5
    ");
    $recentLogs = $logsStmt->fetchAll();

    $maintStmt = $pdo->query("
        SELECT mr.id, mr.equipment_id, mr.type, mr.scheduled_date, mr.status,
               ei.equipment_code, ei.name AS equipment_name
        FROM maintenance_records mr
        JOIN equipment_inventory ei ON mr.equipment_id = ei.id
        WHERE mr.status IN ('pending','in_progress')
          AND mr.scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ORDER BY mr.scheduled_date ASC
        LIMIT 5
    ");
    $upcomingMaint = $maintStmt->fetchAll();

} catch (PDOException $e) {
    error_log('Dashboard error: ' . $e->getMessage());
    $total = $available = $inUse = $maintenance = $sterilising = $overdueMaint = 0;
    $recentLogs = [];
    $upcomingMaint = [];
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Overview</h1>
            <p class="page-breadcrumb">Welcome back, <?= htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8') ?>. Here's what's happening today.</p>
        </div>
        <div class="flex gap-2">
            <?php if (in_array($role, ['admin'])): ?>
                <a href="/surgical_equipment/equipment/add-equipment.php" class="btn btn-primary">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <span>Create Item</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- ── Vishleshan Minimalist Metric Cards ── -->
    <div class="card-grid mb-6">
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                </span>
                <span>Total Equipment</span>
            </div>
            <div class="stat-value"><?= number_format($total) ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </span>
                <span>Available</span>
            </div>
            <div class="stat-value"><?= number_format($available) ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                </span>
                <span>In Use</span>
            </div>
            <div class="stat-value"><?= number_format($inUse) ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-icon" style="color: var(--color-warning-hover);">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                </span>
                <span style="color: var(--color-warning-hover);">Maintenance</span>
            </div>
            <div class="stat-value" style="color: var(--color-warning-hover);"><?= number_format($maintenance) ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                </span>
                <span>Sterilising</span>
            </div>
            <div class="stat-value"><?= number_format($sterilising) ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-icon" style="color: var(--color-danger);">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                </span>
                <span style="color: var(--color-danger);">Overdue Maint.</span>
            </div>
            <div class="stat-value" style="color: var(--color-danger);"><?= number_format($overdueMaint) ?></div>
        </div>
    </div>

    <!-- ── Quick Navigation links ── -->
    <div class="flex gap-2 mb-6" style="flex-wrap: wrap;">
        <a href="/surgical_equipment/equipment/inventory-list.php" class="btn btn-secondary">View Inventory</a>
        <?php if (in_array($role, ['admin', 'nurse', 'sterilisation_tech'])): ?>
            <a href="/surgical_equipment/maintenance/log-maintenance.php" class="btn btn-secondary">Log Maintenance</a>
            <a href="/surgical_equipment/sterilization/sterilization-log.php" class="btn btn-secondary">Sterilisation Log</a>
        <?php endif; ?>
        <?php if ($overdueMaint > 0): ?>
            <a href="/surgical_equipment/reports/reports-compliance.php" class="btn btn-danger" style="background-color: var(--color-danger); color: white; border-color: var(--color-danger);"><?= $overdueMaint ?> Overdue Items</a>
        <?php endif; ?>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;" class="dashboard-grid">

        <!-- Recent Tracking Activity mimicking Vishleshan Recent Sessions -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Tracking Activity</h3>
                <a href="/surgical_equipment/tracking/tracking-logs.php" class="btn btn-sm btn-secondary">View All</a>
            </div>
            <?php if (count($recentLogs) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Equipment</th>
                                <th>Action</th>
                                <th>Location</th>
                                <th>When</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentLogs as $log): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:600; color: var(--color-text-heading);">
                                            <?= htmlspecialchars($log['equipment_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div class="text-muted" style="font-size: 0.75rem;">
                                            <?= htmlspecialchars($log['equipment_code'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= ($log['action'] === 'check_in' || $log['action'] === 'sterilised') ? 'available' : 'in_use' ?>">
                                            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $log['action'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td class="text-muted" style="font-size: 0.8125rem;">
                                        <?= htmlspecialchars($log['location'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="text-muted" style="font-size: 0.75rem; white-space: nowrap;">
                                        <?= !empty($log['created_at']) ? date('M j, g:i A', strtotime($log['created_at'])) : '—' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted text-center" style="padding: 24px 0;">No recent tracking activity.</p>
            <?php endif; ?>
        </div>

        <!-- Upcoming Maintenance -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Upcoming Maintenance (7 Days)</h3>
                <a href="/surgical_equipment/maintenance/maintenance-schedule.php" class="btn btn-sm btn-secondary">View All</a>
            </div>
            <?php if (count($upcomingMaint) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Equipment</th>
                                <th>Type</th>
                                <th>Scheduled</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcomingMaint as $m): ?>
                                <tr>
                                    <td>
                                        <a href="/surgical_equipment/equipment/equipment-details.php?id=<?= (int) ($m['equipment_id'] ?? 0) ?>"
                                           style="font-weight:600; color: var(--color-text-heading);">
                                            <?= htmlspecialchars($m['equipment_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                        <div class="text-muted" style="font-size: 0.75rem;">
                                            <?= htmlspecialchars($m['equipment_code'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </td>
                                    <td class="text-muted" style="font-size: 0.8125rem;">
                                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $m['type'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="text-muted" style="font-size: 0.75rem;">
                                        <?= !empty($m['scheduled_date']) ? date('M j, Y', strtotime($m['scheduled_date'])) : '—' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted text-center" style="padding: 24px 0;">No maintenance scheduled in the next 7 days.</p>
            <?php endif; ?>
        </div>

    </div>
</main>

<style>
@media (max-width: 768px) {
    .dashboard-grid { grid-template-columns: 1fr !important; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
