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

$allowed_roles = ['admin', 'auditor', 'nurse'];
if (!in_array($role, $allowed_roles, true)) {
    $_SESSION['flash_error'] = 'You do not have permission to view suppliers.';
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
        $where[] = '(name LIKE :s OR contact_person LIKE :s2 OR email LIKE :s3 OR phone LIKE :s4)';
        $params[':s']  = '%' . $search . '%';
        $params[':s2'] = '%' . $search . '%';
        $params[':s3'] = '%' . $search . '%';
        $params[':s4'] = '%' . $search . '%';
    }

    $whereClause = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $pdo->prepare("SELECT COUNT(*) AS c FROM suppliers $whereClause");
    $countStmt->execute($params);
    $totalRows  = (int) $countStmt->fetch()['c'];
    $totalPages = max(1, (int) ceil($totalRows / $perPage));

    $dataStmt = $pdo->prepare("
        SELECT s.*,
               (SELECT COUNT(*) FROM equipment_inventory WHERE supplier_id = s.id) AS equipment_count,
               (SELECT COUNT(*) FROM purchases WHERE supplier_id = s.id) AS order_count
        FROM suppliers s
        $whereClause
        ORDER BY s.name ASC
        LIMIT :lim OFFSET :off
    ");

    foreach ($params as $k => $v) {
        $dataStmt->bindValue($k, $v);
    }
    $dataStmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
    $dataStmt->bindValue(':off', $offset,  PDO::PARAM_INT);
    $dataStmt->execute();
    $suppliers = $dataStmt->fetchAll();

} catch (PDOException $e) {
    error_log($e->getMessage());
    $suppliers = []; $totalRows = 0; $totalPages = 1;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Supplier Management</h1>
            <p class="page-breadcrumb"><a href="/surgical_equipment/dashboard.php">Home</a> / Suppliers</p>
        </div>
        <?php if ($role === 'admin'): ?>
            <a href="/surgical_equipment/suppliers/add-supplier.php" class="btn btn-primary">Add Supplier</a>
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
                <input type="text" name="search" class="form-control" placeholder="Search supplier name, contact, email or phone..."
                       value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="/surgical_equipment/suppliers/suppliers.php" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="card" style="padding:0; overflow:hidden;">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Supplier Name</th>
                        <th>Contact Person</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Link Count</th>
                        <?php if ($role === 'admin'): ?>
                            <th style="width:140px; text-align:center;">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($suppliers) > 0): ?>
                        <?php foreach ($suppliers as $sup): ?>
                            <tr>
                                <td>
                                    <strong style="color:var(--color-primary);"><?= htmlspecialchars($sup['name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></strong>
                                </td>
                                <td><?= htmlspecialchars($sup['contact_person'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php if (!empty($sup['email'])): ?>
                                        <a href="mailto:<?= htmlspecialchars($sup['email'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($sup['email'], ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($sup['phone'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span style="font-size:var(--font-size-xs);" class="text-muted">
                                        <?= htmlspecialchars($sup['address'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 6px; align-items: flex-start;">
                                        <span class="status-badge status-in_use" style="font-size: 0.75rem; white-space: nowrap;">
                                            <?= (int) $sup['equipment_count'] ?> item(s)
                                        </span>
                                        <span class="status-badge status-completed" style="font-size: 0.75rem; white-space: nowrap;">
                                            <?= (int) $sup['order_count'] ?> order(s)
                                        </span>
                                    </div>
                                </td>
                                <?php if ($role === 'admin'): ?>
                                    <td style="text-align:center; white-space:nowrap;">
                                        <a href="/surgical_equipment/suppliers/add-supplier.php?edit=<?= (int)$sup['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
                                        <form method="post" action="/surgical_equipment/process-action.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this supplier? This will affect related purchases.');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="action" value="supplier_delete">
                                            <input type="hidden" name="id" value="<?= (int)$sup['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= ($role === 'admin') ? 7 : 6 ?>" class="text-center text-muted" style="padding:40px;">
                                No suppliers found matching search filters.
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
