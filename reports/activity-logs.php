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

$allowed_roles = ['admin', 'auditor'];
if (!in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    $_SESSION['flash_error'] = 'You do not have permission to view the activity log.';
    header('Location: /surgical_equipment/dashboard.php');
    exit;
}

$csrf_token = $_SESSION['csrf_token'] ?? '';

$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 25;
$offset  = ($page - 1) * $perPage;

$actionFilter = trim($_GET['action_filter'] ?? '');
$entityFilter = trim($_GET['entity_filter'] ?? '');
$userFilter   = (int) ($_GET['user_id'] ?? 0);
$dateFrom     = trim($_GET['date_from'] ?? '');
$dateTo       = trim($_GET['date_to'] ?? '');

try {
    $pdo = getDbConnection();

    $where  = [];
    $params = [];

    if ($actionFilter !== '') {
        $where[] = 'sat.action LIKE :action_filter';
        $params[':action_filter'] = '%' . $actionFilter . '%';
    }
    if ($entityFilter !== '') {
        $where[] = 'sat.entity_type = :entity_filter';
        $params[':entity_filter'] = $entityFilter;
    }
    if ($userFilter > 0) {
        $where[] = 'sat.user_id = :user_id';
        $params[':user_id'] = $userFilter;
    }
    if ($dateFrom !== '') {
        $where[] = 'sat.created_at >= :date_from';
        $params[':date_from'] = $dateFrom . ' 00:00:00';
    }
    if ($dateTo !== '') {
        $where[] = 'sat.created_at <= :date_to';
        $params[':date_to'] = $dateTo . ' 23:59:59';
    }

    $whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $pdo->prepare("SELECT COUNT(*) AS total FROM system_audit_trails sat {$whereClause}");
    $countStmt->execute($params);
    $totalRows  = (int) $countStmt->fetch()['total'];
    $totalPages = max(1, (int) ceil($totalRows / $perPage));

    $dataStmt = $pdo->prepare("
        SELECT sat.*, u.full_name AS user_name, u.username
        FROM system_audit_trails sat
        LEFT JOIN users u ON sat.user_id = u.id
        {$whereClause}
        ORDER BY sat.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    foreach ($params as $key => $val) {
        $dataStmt->bindValue($key, $val);
    }
    $dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $dataStmt->execute();
    $logs = $dataStmt->fetchAll();

    $entityStmt = $pdo->query("
        SELECT DISTINCT entity_type FROM system_audit_trails
        WHERE entity_type IS NOT NULL AND entity_type != '' ORDER BY entity_type
    ");
    $entityTypes = $entityStmt->fetchAll(PDO::FETCH_COLUMN);

    $userStmt = $pdo->query("
        SELECT id, full_name FROM users ORDER BY full_name ASC
    ");
    $allUsers = $userStmt->fetchAll();

} catch (PDOException $e) {
    error_log('Activity logs error: ' . $e->getMessage());
    $logs        = [];
    $totalRows   = 0;
    $totalPages  = 1;
    $entityTypes = [];
    $allUsers    = [];
}

function buildQuery(array $overrides): string {
    $params = $_GET;
    foreach ($overrides as $k => $v) {
        $params[$k] = $v;
    }
    return http_build_query($params);
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Activity Log</h1>
            <p class="page-breadcrumb">
                <a href="/surgical_equipment/dashboard.php">Home</a> / Activity Log
            </p>
        </div>
    </div>

    <!-- ── Filter Bar ─────────────────────────────────────── -->
    <div class="card mb-4">
        <form method="get" action="/surgical_equipment/reports/activity-logs.php" class="flex gap-2" style="flex-wrap: wrap; align-items: flex-end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label for="action_filter">Action</label>
                <input type="text" id="action_filter" name="action_filter" class="form-control" placeholder="e.g. login, create"
                       value="<?= htmlspecialchars($actionFilter, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label for="entity_filter">Entity Type</label>
                <select id="entity_filter" name="entity_filter" class="form-control">
                    <option value="">All Entities</option>
                    <?php foreach ($entityTypes as $etype): ?>
                        <option value="<?= htmlspecialchars($etype, ENT_QUOTES, 'UTF-8') ?>" <?= $entityFilter === $etype ? 'selected' : '' ?>>
                            <?= htmlspecialchars($etype, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label for="user_id">User</label>
                <select id="user_id" name="user_id" class="form-control">
                    <option value="0">All Users</option>
                    <?php foreach ($allUsers as $u): ?>
                        <option value="<?= (int) $u['id'] ?>" <?= $userFilter === (int) $u['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['full_name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label for="date_from">From</label>
                <input type="date" id="date_from" name="date_from" class="form-control" value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label for="date_to">To</label>
                <input type="date" id="date_to" name="date_to" class="form-control" value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="/surgical_equipment/reports/activity-logs.php" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>

    <div class="flex flex-between mb-2">
        <span class="text-muted" style="font-size: var(--font-size-sm);">
            Showing <?= count($logs) ?> of <?= number_format($totalRows) ?> entries
        </span>
    </div>

    <!-- ── Audit Log Table ─────────────────────────────────── -->
    <div class="card" style="padding: 0; overflow: hidden;">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>IP Address</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($logs) > 0): ?>
                        <?php foreach ($logs as $i => $log): ?>
                            <tr>
                                <td class="text-muted" style="font-size: var(--font-size-xs); white-space: nowrap;">
                                    <?= date('M j, Y g:i:s A', strtotime($log['created_at'])) ?>
                                </td>
                                <td style="font-size: var(--font-size-sm);">
                                    <?= htmlspecialchars($log['user_name'] ?? 'System', ENT_QUOTES, 'UTF-8') ?>
                                    <?php if (!empty($log['username'])): ?>
                                        <br><span class="text-muted" style="font-size: var(--font-size-xs);">@<?= htmlspecialchars($log['username'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code style="font-size: var(--font-size-xs); background: var(--color-bg); padding: 2px 6px; border-radius: 4px;">
                                        <?= htmlspecialchars($log['action'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                    </code>
                                </td>
                                <td class="text-muted" style="font-size: var(--font-size-xs);">
                                    <?= htmlspecialchars($log['entity_type'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                    <?= !empty($log['entity_id']) ? ' #' . (int) $log['entity_id'] : '' ?>
                                </td>
                                <td class="text-muted" style="font-size: var(--font-size-xs);">
                                    <?= htmlspecialchars($log['ip_address'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td style="font-size: var(--font-size-sm);">
                                    <?= htmlspecialchars($log['details'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center" style="padding: 40px 0; color: var(--color-text-secondary);">
                                No activity recorded for the selected filters.
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
                    <a href="?<?= buildQuery(['page' => $page - 1]) ?>" class="btn btn-sm btn-secondary">← Previous</a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?<?= buildQuery(['page' => $page + 1]) ?>" class="btn btn-sm btn-secondary">Next →</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
