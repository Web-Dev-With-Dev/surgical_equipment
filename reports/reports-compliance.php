<?php

require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_error'] = 'Please sign in.';
    header('Location: /surgical_equipment/auth/login.php');
    exit;
}

$allowed_roles = ['admin', 'auditor', 'sterilisation_tech'];
if (!in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    $_SESSION['flash_error'] = 'You do not have permission to view compliance reports.';
    header('Location: /surgical_equipment/dashboard.php');
    exit;
}

$role = $_SESSION['role'] ?? '';

$STERILE_DUE_DAYS   = 30;
$WARRANTY_SOON_DAYS = 60;

try {
    $pdo = getDbConnection();

    $overdueMaintStmt = $pdo->query("
        SELECT mr.id, mr.type, mr.status, mr.scheduled_date, mr.description,
               ei.equipment_code, ei.name AS equipment_name, ei.id AS equipment_id
        FROM maintenance_records mr
        JOIN equipment_inventory ei ON mr.equipment_id = ei.id
        WHERE mr.status IN ('pending','in_progress')
          AND mr.scheduled_date IS NOT NULL
          AND mr.scheduled_date < CURDATE()
        ORDER BY mr.scheduled_date ASC
    ");
    $overdueMaintenance = $overdueMaintStmt->fetchAll();

    $overdueSterilStmt = $pdo->prepare("
        SELECT id, equipment_code, name, category, status, last_sterilised_at
        FROM equipment_inventory
        WHERE status NOT IN ('retired')
          AND (
              last_sterilised_at IS NULL
              OR last_sterilised_at < DATE_SUB(NOW(), INTERVAL :days DAY)
          )
        ORDER BY last_sterilised_at IS NULL DESC, last_sterilised_at ASC
    ");
    $overdueSterilStmt->execute([':days' => $STERILE_DUE_DAYS]);
    $overdueSterilisation = $overdueSterilStmt->fetchAll();

    $warrantyStmt = $pdo->prepare("
        SELECT id, equipment_code, name, warranty_expiry, status
        FROM equipment_inventory
        WHERE warranty_expiry IS NOT NULL
          AND warranty_expiry <= DATE_ADD(CURDATE(), INTERVAL :days DAY)
        ORDER BY warranty_expiry ASC
    ");
    $warrantyStmt->execute([':days' => $WARRANTY_SOON_DAYS]);
    $warrantyExpiring = $warrantyStmt->fetchAll();

    $failedBatchesStmt = $pdo->query("
        SELECT sb.id, sb.batch_number, sb.cycle_type, sb.completed_at,
               sb.chemical_indicator_passed, sb.biological_indicator_passed,
               (SELECT COUNT(*) FROM sterilization_batch_items sbi WHERE sbi.batch_id = sb.id) AS item_count
        FROM sterilization_batches sb
        WHERE sb.status = 'failed'
           OR sb.chemical_indicator_passed = 0
           OR sb.biological_indicator_passed = 0
        ORDER BY sb.completed_at DESC
        LIMIT 25
    ");
    $failedBatches = $failedBatchesStmt->fetchAll();

    $totalsStmt = $pdo->query('SELECT COUNT(*) AS total FROM equipment_inventory WHERE status != \'retired\'');
    $totalActiveEquipment = (int) $totalsStmt->fetch()['total'];

} catch (PDOException $e) {
    error_log('Compliance report error: ' . $e->getMessage());
    $overdueMaintenance   = [];
    $overdueSterilisation = [];
    $warrantyExpiring     = [];
    $failedBatches        = [];
    $totalActiveEquipment = 0;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Compliance Reports</h1>
            <p class="page-breadcrumb">
                <a href="/surgical_equipment/dashboard.php">Home</a> / Compliance Reports
            </p>
        </div>
        <div class="text-muted" style="font-size: var(--font-size-xs);">
            Generated <?= date('M j, Y g:i A') ?>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- ── Summary Stat Cards ── -->
    <div class="card-grid mb-6">
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                </span>
                <span>Active Equipment</span>
            </div>
            <div class="stat-value"><?= number_format($totalActiveEquipment) ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-icon" style="color: var(--color-danger);">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                </span>
                <span style="color: var(--color-danger);">Overdue Maintenance</span>
            </div>
            <div class="stat-value" style="color: var(--color-danger);"><?= count($overdueMaintenance) ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-icon" style="color: var(--color-warning-hover);">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                </span>
                <span style="color: var(--color-warning-hover);">Sterilisation Overdue (<?= $STERILE_DUE_DAYS ?>d)</span>
            </div>
            <div class="stat-value" style="color: var(--color-warning-hover);"><?= count($overdueSterilisation) ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                </span>
                <span>Warranty Expiring (<?= $WARRANTY_SOON_DAYS ?>d)</span>
            </div>
            <div class="stat-value"><?= count($warrantyExpiring) ?></div>
        </div>
    </div>

    <!-- ── Overdue Maintenance ─────────────────────────────── -->
    <div class="card mb-6">
        <div class="card-header">
            <h3 class="card-title">Overdue Maintenance</h3>
            <a href="/surgical_equipment/maintenance/maintenance-schedule.php?status=pending" class="btn btn-sm btn-secondary">View Schedule</a>
        </div>
        <?php if (count($overdueMaintenance) > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Equipment</th>
                            <th>Type</th>
                            <th>Scheduled</th>
                            <th>Days Overdue</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($overdueMaintenance as $m): ?>
                            <?php $daysOverdue = (int) floor((strtotime('today') - strtotime($m['scheduled_date'])) / 86400); ?>
                            <tr>
                                <td>
                                    <a href="/surgical_equipment/equipment/equipment-details.php?id=<?= (int) $m['equipment_id'] ?>">
                                        <?= htmlspecialchars($m['equipment_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                    <br><span class="text-muted" style="font-size: var(--font-size-xs);"><?= htmlspecialchars($m['equipment_code'], ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $m['type'])), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-muted" style="font-size: var(--font-size-xs);"><?= date('M j, Y', strtotime($m['scheduled_date'])) ?></td>
                                <td><span class="status-badge status-failed"><?= $daysOverdue ?> days</span></td>
                                <td>
                                    <span class="status-badge status-<?= htmlspecialchars($m['status'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $m['status'])), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted text-center" style="padding: 20px 0;">No overdue maintenance records.</p>
        <?php endif; ?>
    </div>

    <!-- ── Overdue Sterilisation ────────────────────────────── -->
    <div class="card mb-6">
        <div class="card-header">
            <h3 class="card-title">Equipment Overdue for Sterilisation</h3>
            <a href="/surgical_equipment/sterilization/sterilization-log.php" class="btn btn-sm btn-secondary">View Sterilisation Log</a>
        </div>
        <?php if (count($overdueSterilisation) > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Equipment</th>
                            <th>Category</th>
                            <th>Current Status</th>
                            <th>Last Sterilised</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($overdueSterilisation as $s): ?>
                            <tr>
                                <td>
                                    <a href="/surgical_equipment/equipment/equipment-details.php?id=<?= (int) $s['id'] ?>">
                                        <?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                    <br><span class="text-muted" style="font-size: var(--font-size-xs);"><?= htmlspecialchars($s['equipment_code'], ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td class="text-muted"><?= htmlspecialchars($s['category'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="status-badge status-<?= htmlspecialchars($s['status'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $s['status'])), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td class="text-muted" style="font-size: var(--font-size-xs);">
                                    <?= $s['last_sterilised_at'] ? date('M j, Y g:i A', strtotime($s['last_sterilised_at'])) : 'Never' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted text-center" style="padding: 20px 0;">All active equipment is within the sterilisation window.</p>
        <?php endif; ?>
    </div>

    <!-- ── Warranty Expiring ────────────────────────────────── -->
    <div class="card mb-6">
        <div class="card-header">
            <h3 class="card-title">Warranty Expiring / Expired</h3>
        </div>
        <?php if (count($warrantyExpiring) > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Equipment</th>
                            <th>Status</th>
                            <th>Warranty Expiry</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($warrantyExpiring as $w): ?>
                            <?php $isExpired = strtotime($w['warranty_expiry']) < strtotime('today'); ?>
                            <tr>
                                <td>
                                    <a href="/surgical_equipment/equipment/equipment-details.php?id=<?= (int) $w['id'] ?>">
                                        <?= htmlspecialchars($w['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                    <br><span class="text-muted" style="font-size: var(--font-size-xs);"><?= htmlspecialchars($w['equipment_code'], ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= htmlspecialchars($w['status'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $w['status'])), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?= $isExpired ? 'status-failed' : 'status-pending' ?>">
                                        <?= date('M j, Y', strtotime($w['warranty_expiry'])) ?><?= $isExpired ? ' (expired)' : '' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted text-center" style="padding: 20px 0;">No warranties expiring within <?= $WARRANTY_SOON_DAYS ?> days.</p>
        <?php endif; ?>
    </div>

    <!-- ── Failed Sterilisation Indicators ──────────────────── -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Failed Sterilisation Indicators</h3>
        </div>
        <?php if (count($failedBatches) > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Batch</th>
                            <th>Cycle Type</th>
                            <th>Items</th>
                            <th>Chemical</th>
                            <th>Biological</th>
                            <th>Completed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($failedBatches as $fb): ?>
                            <tr>
                                <td>
                                    <a href="/surgical_equipment/sterilization/sterilization-log.php?batch_id=<?= (int) $fb['id'] ?>">
                                        <strong><?= htmlspecialchars($fb['batch_number'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $fb['cycle_type'])), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int) $fb['item_count'] ?></td>
                                <td><?= $fb['chemical_indicator_passed'] === null ? '—' : ((int) $fb['chemical_indicator_passed'] === 1 ? '<span class="indicator-pass">Pass</span>' : '<span class="indicator-fail">Fail</span>') ?></td>
                                <td><?= $fb['biological_indicator_passed'] === null ? '—' : ((int) $fb['biological_indicator_passed'] === 1 ? '<span class="indicator-pass">Pass</span>' : '<span class="indicator-fail">Fail</span>') ?></td>
                                <td class="text-muted" style="font-size: var(--font-size-xs);">
                                    <?= $fb['completed_at'] ? date('M j, Y g:i A', strtotime($fb['completed_at'])) : '—' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted text-center" style="padding: 20px 0;">No failed sterilisation indicators on record.</p>
        <?php endif; ?>
    </div>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
