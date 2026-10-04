<?php

require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /surgical_equipment/auth/login.php');
    exit;
}

$role       = $_SESSION['role'] ?? '';
$csrf_token = $_SESSION['csrf_token'] ?? '';

$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

$search       = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$catFilter    = trim($_GET['category'] ?? '');

try {
    $pdo = getDbConnection();

    $where  = [];
    $params = [];

    if ($search !== '') {
        $where[] = '(name LIKE :s OR equipment_code LIKE :s2 OR serial_number LIKE :s3)';
        $params[':s']  = '%' . $search . '%';
        $params[':s2'] = '%' . $search . '%';
        $params[':s3'] = '%' . $search . '%';
    }
    if ($statusFilter !== '') {
        $where[] = 'status = :status';
        $params[':status'] = $statusFilter;
    }
    if ($catFilter !== '') {
        $where[] = 'category LIKE :cat';
        $params[':cat'] = '%' . $catFilter . '%';
    }

    $whereClause = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $pdo->prepare("SELECT COUNT(*) AS c FROM equipment_inventory $whereClause");
    $countStmt->execute($params);
    $totalRows  = (int) $countStmt->fetch()['c'];
    $totalPages = max(1, (int) ceil($totalRows / $perPage));

    $dataStmt = $pdo->prepare("SELECT * FROM equipment_inventory $whereClause ORDER BY equipment_code ASC LIMIT :lim OFFSET :off");
    foreach ($params as $k => $v) { $dataStmt->bindValue($k, $v); }
    $dataStmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
    $dataStmt->bindValue(':off', $offset,  PDO::PARAM_INT);
    $dataStmt->execute();
    $items = $dataStmt->fetchAll();

    $categories = $pdo->query("SELECT DISTINCT category FROM equipment_inventory WHERE category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $items = []; $totalRows = 0; $totalPages = 1; $categories = [];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Equipment Inventory</h1>
            <p class="page-breadcrumb"><a href="/surgical_equipment/dashboard.php">Home</a> / Inventory</p>
        </div>
        <?php if ($role === 'admin'): ?>
            <a href="/surgical_equipment/equipment/add-equipment.php" class="btn btn-primary">+ Add Equipment</a>
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

    <div class="card mb-4">
        <form method="get" class="flex gap-2" style="flex-wrap:wrap; align-items:flex-end;">
            <div style="flex:2; min-width:180px;">
                <input type="text" name="search" class="form-control" placeholder="Search name, code, serial..."
                       value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div style="min-width:160px;">
                <select name="status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="available"         <?= $statusFilter === 'available'         ? 'selected' : '' ?>>Available</option>
                    <option value="in_use"            <?= $statusFilter === 'in_use'            ? 'selected' : '' ?>>In Use</option>
                    <option value="under_maintenance" <?= $statusFilter === 'under_maintenance' ? 'selected' : '' ?>>Under Maintenance</option>
                    <option value="sterilising"       <?= $statusFilter === 'sterilising'       ? 'selected' : '' ?>>Sterilising</option>
                    <option value="retired"           <?= $statusFilter === 'retired'           ? 'selected' : '' ?>>Retired</option>
                </select>
            </div>
            <div style="min-width:160px;">
                <select name="category" class="form-control">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>" <?= $catFilter === $cat ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="/surgical_equipment/equipment/inventory-list.php" class="btn btn-secondary">Clear</a>
        </form>
    </div>

    <div class="flex flex-between mb-2">
        <span class="text-muted" style="font-size:var(--font-size-sm);">
            Showing <?= count($items) ?> of <?= number_format($totalRows) ?> items
        </span>
    </div>

    <div class="card-grid mb-4">
        <?php if (count($items) > 0): ?>
            <?php foreach ($items as $item): ?>
                <div class="session-card">

                    <div class="session-card-header">
                        <div style="min-width:0;">
                            <span class="session-card-title"><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span style="display:block; font-size:0.75rem; color:var(--color-text-muted); margin-top:2px; font-family:var(--font-sans);"><?= htmlspecialchars($item['equipment_code'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div style="position:relative; flex-shrink:0;">
                            <button class="session-card-menu-btn"
                                style="background:none; border:none; cursor:pointer; color:var(--color-text-subtle); padding:4px; border-radius:var(--radius-sm); transition:var(--transition);">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="5" r="1"></circle><circle cx="12" cy="12" r="1"></circle><circle cx="12" cy="19" r="1"></circle>
                                </svg>
                            </button>
                            <div class="session-card-dropdown" style="display:none; position:absolute; right:0; top:100%; background:var(--color-surface); border:1px solid var(--color-border); border-radius:var(--radius-md); box-shadow:0 8px 24px rgba(0,0,0,.08); z-index:50; min-width:140px; padding:6px 0; margin-top:4px;">
                                <a href="/surgical_equipment/equipment/equipment-details.php?id=<?= (int)$item['id'] ?>"
                                   style="display:block; padding:7px 14px; font-size:0.8125rem; color:var(--color-text-body); font-weight:500; text-decoration:none;">View Details</a>
                                <?php if ($role === 'admin'): ?>
                                    <a href="/surgical_equipment/equipment/add-equipment.php?edit=<?= (int)$item['id'] ?>"
                                       style="display:block; padding:7px 14px; font-size:0.8125rem; color:var(--color-text-body); font-weight:500; text-decoration:none;">Edit Item</a>
                                <?php endif; ?>
                                <?php if (in_array($role, ['admin', 'nurse', 'sterilisation_tech'], true)): ?>
                                    <a href="/surgical_equipment/maintenance/log-maintenance.php?equipment_id=<?= (int)$item['id'] ?>"
                                       style="display:block; padding:7px 14px; font-size:0.8125rem; color:var(--color-text-body); font-weight:500; text-decoration:none;">Log Maintenance</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div style="flex:1;">
                        <div class="session-card-row">
                            <span class="session-card-label">Status</span>
                            <span class="session-card-value">
                                <span class="status-badge status-<?= htmlspecialchars($item['status'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $item['status'])), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </span>
                        </div>
                        <div class="session-card-row">
                            <span class="session-card-label">Category</span>
                            <span class="session-card-value"><?= htmlspecialchars($item['category'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="session-card-row">
                            <span class="session-card-label">Location</span>
                            <span class="session-card-value"><?= htmlspecialchars($item['current_location'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>

                    <div class="session-card-footer">
                        <span class="session-card-date">
                            Sterilised: <?= !empty($item['last_sterilised_at']) ? date('M j, Y', strtotime($item['last_sterilised_at'])) : 'Never' ?>
                        </span>
                        <a href="/surgical_equipment/equipment/equipment-details.php?id=<?= (int)$item['id'] ?>" class="btn-session-action">
                            <span>View Detail</span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                        </a>
                    </div>

                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card w-full text-center" style="grid-column:1/-1; padding:48px 0; color:var(--color-text-muted);">
                No equipment found matching your filters.
            </div>
        <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="flex flex-between mt-4">
            <span class="text-muted" style="font-size:var(--font-size-sm);">Page <?= $page ?> of <?= $totalPages ?></span>
            <div class="flex gap-2">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&category=<?= urlencode($catFilter) ?>" class="btn btn-sm btn-secondary">← Previous</a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&category=<?= urlencode($catFilter) ?>" class="btn btn-sm btn-secondary">Next →</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
