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

$allowed_roles = ['admin', 'nurse', 'sterilisation_tech'];
if (!in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    $_SESSION['flash_error'] = 'You do not have permission to view tracking logs.';
    header('Location: /surgical_equipment/dashboard.php');
    exit;
}

$csrf_token = $_SESSION['csrf_token'] ?? '';

$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$search       = trim($_GET['search'] ?? '');
$actionFilter = $_GET['action_filter'] ?? '';

try {
    $pdo = getDbConnection();

    $where  = [];
    $params = [];

    if ($search !== '') {
        $where[] = '(ei.name LIKE :search OR ei.equipment_code LIKE :search_code OR tl.from_location LIKE :search_from OR tl.to_location LIKE :search_to)';
        $params[':search']      = '%' . $search . '%';
        $params[':search_code'] = '%' . $search . '%';
        $params[':search_from'] = '%' . $search . '%';
        $params[':search_to']   = '%' . $search . '%';
    }

    if ($actionFilter !== '') {
        $where[] = 'tl.action = :action_filter';
        $params[':action_filter'] = $actionFilter;
    }

    $whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $pdo->prepare("
        SELECT COUNT(*) AS total
        FROM tracking_logs tl
        JOIN equipment_inventory ei ON tl.equipment_id = ei.id
        {$whereClause}
    ");
    $countStmt->execute($params);
    $totalRows  = (int) $countStmt->fetch()['total'];
    $totalPages = max(1, (int) ceil($totalRows / $perPage));

    $dataStmt = $pdo->prepare("
        SELECT tl.*, ei.equipment_code, ei.name AS equipment_name, u.full_name AS user_name
        FROM tracking_logs tl
        JOIN equipment_inventory ei ON tl.equipment_id = ei.id
        LEFT JOIN users u ON tl.user_id = u.id
        {$whereClause}
        ORDER BY tl.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    foreach ($params as $key => $val) {
        $dataStmt->bindValue($key, $val);
    }
    $dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $dataStmt->execute();
    $logs = $dataStmt->fetchAll();

    $summaryStmt = $pdo->query("
        SELECT
            SUM(action = 'check_out' AND DATE(created_at) = CURDATE()) AS checkouts_today,
            SUM(action = 'check_in'  AND DATE(created_at) = CURDATE()) AS checkins_today,
            SUM(action = 'transfer'  AND DATE(created_at) = CURDATE()) AS transfers_today
        FROM tracking_logs
    ");
    $summary = $summaryStmt->fetch();

} catch (PDOException $e) {
    error_log('Tracking logs error: ' . $e->getMessage());
    $logs       = [];
    $totalRows  = 0;
    $totalPages = 1;
    $summary    = ['checkouts_today' => 0, 'checkins_today' => 0, 'transfers_today' => 0];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Tracking Logs</h1>
            <p class="page-breadcrumb">
                <a href="/surgical_equipment/dashboard.php">Home</a> / Tracking
            </p>
        </div>
        <div class="flex gap-2">
            <a href="/surgical_equipment/equipment/check-out.php" class="btn btn-primary">Check Out</a>
            <a href="/surgical_equipment/equipment/check-in.php" class="btn btn-success">Check In</a>
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
                <span class="stat-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path></svg>
                </span>
                <span>Checked Out Today</span>
            </div>
            <div class="stat-value"><?= (int) ($summary['checkouts_today'] ?? 0) ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                </span>
                <span>Checked In Today</span>
            </div>
            <div class="stat-value"><?= (int) ($summary['checkins_today'] ?? 0) ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path></svg>
                </span>
                <span>Transfers Today</span>
            </div>
            <div class="stat-value"><?= (int) ($summary['transfers_today'] ?? 0) ?></div>
        </div>
    </div>

    <!-- ── Search & Filter Bar ─────────────────────────────── -->
    <div class="card mb-4">
        <form method="get" action="/surgical_equipment/tracking/tracking-logs.php" class="flex flex-between gap-4" style="flex-wrap: wrap;">
            <div class="flex gap-2" style="flex: 2; min-width: 200px;">
                <input type="text" name="search" class="form-control"
                       placeholder="Search by equipment, code, or location..."
                       value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="flex gap-2" style="flex: 1; min-width: 150px;">
                <select name="action_filter" class="form-control">
                    <option value="">All Actions</option>
                    <option value="check_out" <?= $actionFilter === 'check_out' ? 'selected' : '' ?>>Checked Out</option>
                    <option value="check_in"  <?= $actionFilter === 'check_in'  ? 'selected' : '' ?>>Checked In</option>
                    <option value="transfer"  <?= $actionFilter === 'transfer'  ? 'selected' : '' ?>>Transferred</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="/surgical_equipment/tracking/tracking-logs.php" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>

    <div class="flex flex-between mb-2">
        <span class="text-muted" style="font-size: var(--font-size-sm);">
            Showing <?= count($logs) ?> of <?= number_format($totalRows) ?> log entries
        </span>
    </div>

    <!-- ── Tracking Log Table ──────────────────────────────── -->
    <div class="card" style="padding: 0; overflow: hidden;">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Equipment</th>
                        <th>Action</th>
                        <th>From</th>
                        <th>To</th>
                        <th>User</th>
                        <th>Notes</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($logs) > 0): ?>
                        <?php foreach ($logs as $log): ?>
                            <?php
                                $actKey = $log['action'] ?? '';
                                $badgeColor = match ($actKey) {
                                    'check_out' => ['bg' => '#dbeafe', 'fg' => '#1e40af'],
                                    'check_in'  => ['bg' => '#dcfce7', 'fg' => '#166534'],
                                    'transfer'  => ['bg' => '#fef3c7', 'fg' => '#92400e'],
                                    default     => ['bg' => '#f3e8ff', 'fg' => '#5b21b6'],
                                };
                                $actionName = match ($actKey) {
                                    'check_out' => 'Checked Out',
                                    'check_in'  => 'Checked In',
                                    'transfer'  => 'Transferred',
                                    default     => ucfirst(str_replace('_', ' ', $actKey)),
                                };
                            ?>
                            <tr>
                                <td>
                                    <a href="/surgical_equipment/equipment/equipment-details.php?id=<?= (int) ($log['equipment_id'] ?? 0) ?>" style="font-weight: 500;">
                                        <?= htmlspecialchars($log['equipment_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                    <br><span class="text-muted" style="font-size: var(--font-size-xs);">
                                        <?= htmlspecialchars($log['equipment_code'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge" style="background: <?= $badgeColor['bg'] ?>; color: <?= $badgeColor['fg'] ?>;">
                                        <?= htmlspecialchars($actionName, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td class="text-muted" style="font-size: var(--font-size-sm);">
                                    <?= htmlspecialchars($log['from_location'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="text-muted" style="font-size: var(--font-size-sm);">
                                    <?= htmlspecialchars($log['to_location'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="text-muted" style="font-size: var(--font-size-xs);">
                                    <?= htmlspecialchars($log['user_name'] ?? 'System', ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="text-muted" style="font-size: var(--font-size-xs); max-width: 200px;">
                                    <?= htmlspecialchars($log['notes'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="text-muted" style="font-size: var(--font-size-xs); white-space: nowrap;">
                                    <?= !empty($log['created_at']) ? date('M j, Y g:i A', strtotime($log['created_at'])) : '—' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center" style="padding: 40px 0; color: var(--color-text-secondary);">
                                No tracking activity matches your filters.
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
                    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&action_filter=<?= urlencode($actionFilter) ?>"
                       class="btn btn-sm btn-secondary">← Previous</a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&action_filter=<?= urlencode($actionFilter) ?>"
                       class="btn btn-sm btn-secondary">Next →</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
