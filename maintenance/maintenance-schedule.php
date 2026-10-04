<?php

require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_error'] = 'Please sign in to access the maintenance schedule.';
    header('Location: /surgical_equipment/auth/login.php');
    exit;
}

$allowed_roles = ['admin', 'nurse', 'sterilisation_tech', 'surgeon', 'auditor'];
if (!in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    $_SESSION['flash_error'] = 'You do not have permission to view the maintenance schedule.';
    header('Location: /surgical_equipment/dashboard.php');
    exit;
}

$csrf_token = $_SESSION['csrf_token'] ?? '';
$role       = $_SESSION['role'] ?? '';

$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

$search       = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$typeFilter   = $_GET['type'] ?? '';

try {
    $pdo = getDbConnection();

    $where  = [];
    $params = [];

    if ($search !== '') {
        $where[] = '(ei.name LIKE :search OR ei.equipment_code LIKE :search_code OR mr.description LIKE :search_desc)';
        $params[':search']      = '%' . $search . '%';
        $params[':search_code'] = '%' . $search . '%';
        $params[':search_desc'] = '%' . $search . '%';
    }

    if ($statusFilter !== '') {
        $where[] = 'mr.status = :status';
        $params[':status'] = $statusFilter;
    }

    if ($typeFilter !== '') {
        $where[] = 'mr.type = :type';
        $params[':type'] = $typeFilter;
    }

    $whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

    $countSql = "
        SELECT COUNT(*) AS total
        FROM maintenance_records mr
        JOIN equipment_inventory ei ON mr.equipment_id = ei.id
        {$whereClause}
    ";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRows  = (int) $countStmt->fetch()['total'];
    $totalPages = max(1, (int) ceil($totalRows / $perPage));

    $dataSql = "
        SELECT mr.*,
               ei.equipment_code, ei.name AS equipment_name, ei.current_location,
               u.full_name AS performed_by_name
        FROM maintenance_records mr
        JOIN equipment_inventory ei ON mr.equipment_id = ei.id
        LEFT JOIN users u ON mr.performed_by = u.id
        {$whereClause}
        ORDER BY
            CASE mr.status WHEN 'pending' THEN 0 WHEN 'in_progress' THEN 1 ELSE 2 END,
            mr.scheduled_date ASC,
            mr.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    $dataStmt = $pdo->prepare($dataSql);
    foreach ($params as $key => $val) {
        $dataStmt->bindValue($key, $val);
    }
    $dataStmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
    $dataStmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
    $dataStmt->execute();
    $records = $dataStmt->fetchAll();

    $summaryStmt = $pdo->query("
        SELECT
            SUM(status = 'pending')     AS pending_count,
            SUM(status = 'in_progress') AS in_progress_count,
            SUM(status = 'completed')   AS completed_count,
            SUM(status = 'pending' AND scheduled_date IS NOT NULL AND scheduled_date < CURDATE()) AS overdue_count
        FROM maintenance_records
    ");
    $summary = $summaryStmt->fetch();

} catch (PDOException $e) {
    error_log('Maintenance schedule error: ' . $e->getMessage());
    $records    = [];
    $totalRows  = 0;
    $totalPages = 1;
    $summary    = ['pending_count' => 0, 'in_progress_count' => 0, 'completed_count' => 0, 'overdue_count' => 0];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Maintenance Schedule</h1>
            <p class="page-breadcrumb">
                <a href="/surgical_equipment/dashboard.php">Home</a> / Maintenance
            </p>
        </div>
        <div class="flex gap-2">
            <?php if (in_array($role, ['admin', 'nurse', 'sterilisation_tech'], true)): ?>
                <a href="/surgical_equipment/maintenance/log-maintenance.php" class="btn btn-primary">Log Maintenance</a>
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

    <!-- ── Summary Stat Cards ── -->
    <div class="card-grid mb-6">
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-icon" style="color: var(--color-warning-hover);">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                </span>
                <span style="color: var(--color-warning-hover);">Pending</span>
            </div>
            <div class="stat-value" style="color: var(--color-warning-hover);"><?= (int) ($summary['pending_count'] ?? 0) ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                </span>
                <span>In Progress</span>
            </div>
            <div class="stat-value"><?= (int) ($summary['in_progress_count'] ?? 0) ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </span>
                <span>Completed</span>
            </div>
            <div class="stat-value"><?= (int) ($summary['completed_count'] ?? 0) ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-icon" style="color: var(--color-danger);">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                </span>
                <span style="color: var(--color-danger);">Overdue</span>
            </div>
            <div class="stat-value" style="color: var(--color-danger);"><?= (int) ($summary['overdue_count'] ?? 0) ?></div>
        </div>
    </div>

    <!-- ── Search & Filter Bar ─────────────────────────────── -->
    <div class="card mb-4">
        <form method="get" action="/surgical_equipment/maintenance/maintenance-schedule.php" class="flex flex-between gap-4" style="flex-wrap: wrap;">
            <div class="flex gap-2" style="flex: 2; min-width: 200px;">
                <input type="text" name="search" class="form-control"
                       placeholder="Search by equipment, code, or description..."
                       value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="flex gap-2" style="flex: 1; min-width: 150px;">
                <select name="status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="pending"     <?= $statusFilter === 'pending'     ? 'selected' : '' ?>>Pending</option>
                    <option value="in_progress" <?= $statusFilter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="completed"   <?= $statusFilter === 'completed'   ? 'selected' : '' ?>>Completed</option>
                    <option value="cancelled"   <?= $statusFilter === 'cancelled'   ? 'selected' : '' ?>>Cancelled</option>
                </select>
                <select name="type" class="form-control">
                    <option value="">All Types</option>
                    <option value="routine"     <?= $typeFilter === 'routine'     ? 'selected' : '' ?>>Routine Inspection</option>
                    <option value="repair"      <?= $typeFilter === 'repair'      ? 'selected' : '' ?>>Repair</option>
                    <option value="calibration" <?= $typeFilter === 'calibration' ? 'selected' : '' ?>>Calibration</option>
                    <option value="inspection"  <?= $typeFilter === 'inspection'  ? 'selected' : '' ?>>Inspection</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="/surgical_equipment/maintenance/maintenance-schedule.php" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>

    <div class="flex flex-between mb-2">
        <span class="text-muted" style="font-size: var(--font-size-sm);">
            Showing <?= count($records) ?> of <?= number_format($totalRows) ?> records
        </span>
    </div>

    <!-- ── Maintenance Table ───────────────────────────────── -->
    <div class="card" style="padding: 0; overflow: hidden;">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Equipment</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Scheduled</th>
                        <th>Completed</th>
                        <th>Cost</th>
                        <th>Logged By</th>
                        <?php if (in_array($role, ['admin', 'sterilisation_tech'], true)): ?>
                            <th style="width: 100px; text-align:center;">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($records) > 0): ?>
                        <?php foreach ($records as $rec): ?>
                            <?php
                                $isOverdue = ($rec['status'] ?? '') === 'pending'
                                    && !empty($rec['scheduled_date'])
                                    && strtotime($rec['scheduled_date']) < strtotime('today');
                                $desc = $rec['description'] ?? '';
                            ?>
                            <tr>
                                <td>
                                    <a href="/surgical_equipment/equipment/equipment-details.php?id=<?= (int) ($rec['equipment_id'] ?? 0) ?>" style="font-weight: 500;">
                                        <?= htmlspecialchars($rec['equipment_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                    <br><span class="text-muted" style="font-size: var(--font-size-xs);">
                                        <?= htmlspecialchars($rec['equipment_code'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $rec['type'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                <td style="max-width: 240px;">
                                    <?= htmlspecialchars(substr($desc, 0, 80), ENT_QUOTES, 'UTF-8') ?><?= strlen($desc) > 80 ? '...' : '' ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= htmlspecialchars($rec['status'] ?? 'pending', ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $rec['status'] ?? 'pending')), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <?php if ($isOverdue): ?>
                                        <br><span class="status-badge status-failed mt-1" style="display:inline-block;">Overdue</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted" style="font-size: var(--font-size-xs); white-space: nowrap;">
                                    <?= !empty($rec['scheduled_date']) ? date('M j, Y', strtotime($rec['scheduled_date'])) : '—' ?>
                                </td>
                                <td class="text-muted" style="font-size: var(--font-size-xs); white-space: nowrap;">
                                    <?= !empty($rec['completed_date']) ? date('M j, Y', strtotime($rec['completed_date'])) : '—' ?>
                                </td>
                                <td class="text-muted" style="font-size: var(--font-size-xs);">
                                    <?= $rec['cost'] !== null ? '$' . number_format((float) $rec['cost'], 2) : '—' ?>
                                </td>
                                <td class="text-muted" style="font-size: var(--font-size-xs);">
                                    <?= htmlspecialchars($rec['performed_by_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <?php if (in_array($role, ['admin', 'sterilisation_tech'], true)): ?>
                                    <td style="text-align:center;">
                                        <?php if (in_array($rec['status'], ['pending', 'in_progress'], true)): ?>
                                            <form method="post" action="/surgical_equipment/process-action.php" onsubmit="return confirm('Mark this maintenance record as completed?');">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="action" value="maintenance_complete">
                                                <input type="hidden" name="id" value="<?= (int) $rec['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-success">Complete</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center" style="padding: 40px 0; color: var(--color-text-secondary);">
                                <?= $search || $statusFilter || $typeFilter
                                    ? 'No maintenance records match your filters.'
                                    : 'No maintenance records have been logged yet.' ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ── Pagination ──────────────────────────────────────── -->
    <?php if ($totalPages > 1): ?>
        <div class="flex flex-between mt-4">
            <div class="text-muted" style="font-size: var(--font-size-sm);">
                Page <?= $page ?> of <?= $totalPages ?>
            </div>
            <div class="flex gap-2">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&type=<?= urlencode($typeFilter) ?>"
                       class="btn btn-sm btn-secondary">← Previous</a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&type=<?= urlencode($typeFilter) ?>"
                       class="btn btn-sm btn-secondary">Next →</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
