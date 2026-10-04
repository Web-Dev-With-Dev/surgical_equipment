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

$role       = $_SESSION['role'] ?? '';
$csrf_token = $_SESSION['csrf_token'] ?? '';

$allowed_roles = ['admin', 'auditor'];
if (!in_array($role, $allowed_roles, true)) {
    $_SESSION['flash_error'] = 'You do not have permission to view purchase records.';
    header('Location: /surgical_equipment/dashboard.php');
    exit;
}

$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

$search  = trim($_GET['search'] ?? '');

try {
    $pdo = getDbConnection();

    $where  = [];
    $params = [];

    if ($search !== '') {
        $where[] = '(p.purchase_order_number LIKE :s OR s.name LIKE :s2 OR p.notes LIKE :s3)';
        $params[':s']  = '%' . $search . '%';
        $params[':s2'] = '%' . $search . '%';
        $params[':s3'] = '%' . $search . '%';
    }

    $whereClause = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $pdo->prepare("
        SELECT COUNT(*) AS c
        FROM purchases p
        LEFT JOIN suppliers s ON p.supplier_id = s.id
        $whereClause
    ");
    $countStmt->execute($params);
    $totalRows  = (int) $countStmt->fetch()['c'];
    $totalPages = max(1, (int) ceil($totalRows / $perPage));

    $dataStmt = $pdo->prepare("
        SELECT p.*, s.name AS supplier_name
        FROM purchases p
        LEFT JOIN suppliers s ON p.supplier_id = s.id
        $whereClause
        ORDER BY p.purchase_date DESC, p.created_at DESC
        LIMIT :lim OFFSET :off
    ");

    foreach ($params as $k => $v) {
        $dataStmt->bindValue($k, $v);
    }
    $dataStmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
    $dataStmt->bindValue(':off', $offset,  PDO::PARAM_INT);
    $dataStmt->execute();
    $purchases = $dataStmt->fetchAll();

    $purchaseItems = [];
    if (count($purchases) > 0) {
        $purchaseIds = array_column($purchases, 'id');
        $inClause = implode(',', array_map('intval', $purchaseIds));

        $itemStmt = $pdo->query("
            SELECT id, equipment_code, name, purchase_id
            FROM equipment_inventory
            WHERE purchase_id IN ($inClause)
            ORDER BY equipment_code ASC
        ");

        while ($row = $itemStmt->fetch()) {
            $purchaseItems[$row['purchase_id']][] = $row;
        }
    }

} catch (PDOException $e) {
    error_log($e->getMessage());
    $purchases = []; $totalRows = 0; $totalPages = 1;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Purchase Management</h1>
            <p class="page-breadcrumb"><a href="/surgical_equipment/dashboard.php">Home</a> / Purchases</p>
        </div>
        <?php if ($role === 'admin'): ?>
            <a href="/surgical_equipment/purchases/add-purchase.php" class="btn btn-primary">Record Purchase</a>
        <?php endif; ?>
    </div>

    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- Search Bar -->
    <div class="card mb-4">
        <form method="get" class="flex gap-2" style="flex-wrap:wrap; align-items:flex-end;">
            <div style="flex:2; min-width:200px;">
                <input type="text" name="search" class="form-control" placeholder="Search PO number, supplier or notes..."
                       value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="/surgical_equipment/purchases/purchases.php" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="card" style="padding:0; overflow:hidden;">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>PO Number</th>
                        <th>Supplier</th>
                        <th>Purchase Date</th>
                        <th>Total Cost</th>
                        <th>Status</th>
                        <th>Equipment Acquired</th>
                        <th>Notes</th>
                        <?php if ($role === 'admin'): ?>
                            <th style="width:150px; text-align:center;">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($purchases) > 0): ?>
                        <?php foreach ($purchases as $p): ?>
                            <tr>
                                <td style="white-space: nowrap;">
                                    <strong style="color:var(--color-text-heading);">
                                        <?= htmlspecialchars($p['purchase_order_number'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                    </strong>
                                </td>
                                <td style="white-space: nowrap;"><?= htmlspecialchars($p['supplier_name'] ?? 'Unknown Supplier', ENT_QUOTES, 'UTF-8') ?></td>
                                <td style="white-space: nowrap;"><?= !empty($p['purchase_date']) ? date('M d, Y', strtotime($p['purchase_date'])) : '—' ?></td>
                                <td style="white-space: nowrap;">
                                    <strong>$<?= number_format((float) ($p['total_cost'] ?? 0), 2) ?></strong>
                                </td>
                                <td style="white-space: nowrap;">
                                    <span class="status-badge status-<?= htmlspecialchars($p['status'] ?? 'completed', ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars(ucfirst($p['status'] ?? 'completed'), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($purchaseItems[$p['id']])): ?>
                                        <div style="display:flex; flex-direction:column; gap:4px; max-width:280px;">
                                            <?php foreach ($purchaseItems[$p['id']] as $item): ?>
                                                <div style="font-size:0.75rem; line-height:1.4;">
                                                    <a href="/surgical_equipment/equipment/equipment-details.php?id=<?= (int)$item['id'] ?>" style="font-weight:600; color:var(--color-text-heading);">
                                                        <?= htmlspecialchars($item['equipment_code'], ENT_QUOTES, 'UTF-8') ?>
                                                    </a>
                                                    <span class="text-muted" style="margin-left:4px;"><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size:0.75rem;">None linked</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="text-muted" style="font-size:0.75rem;">
                                        <?= htmlspecialchars($p['notes'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <?php if ($role === 'admin'): ?>
                                    <td style="text-align:center; white-space:nowrap;">
                                        <a href="/surgical_equipment/purchases/add-purchase.php?edit=<?= (int)$p['id'] ?>" class="btn btn-sm btn-secondary" style="margin-right:4px;">Edit</a>
                                        <form method="post" action="/surgical_equipment/process-action.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this purchase record? Note: Equipment items will not be deleted but will be unlinked.');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="action" value="purchase_delete">
                                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= ($role === 'admin') ? 8 : 7 ?>" class="text-center text-muted" style="padding:40px;">
                                No purchase records found matching search filters.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="flex flex-between mt-4">
            <div class="text-muted" style="font-size: var(--font-size-sm);">
                Showing page <?= $page ?> of <?= $totalPages ?>
            </div>
            <div class="flex gap-2">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" class="btn btn-sm btn-secondary">← Previous</a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" class="btn btn-sm btn-secondary">Next →</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
