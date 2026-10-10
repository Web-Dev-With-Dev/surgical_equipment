<?php
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_error'] = 'Please sign in to continue.';
    header('Location: /surgical_equipment/auth/login.php');
    exit;
}

$role       = $_SESSION['role'] ?? '';
$csrf_token = $_SESSION['csrf_token'] ?? '';

$allowed_roles = ['admin', 'surgeon', 'nurse', 'sterilisation_tech', 'auditor'];
if (!in_array($role, $allowed_roles, true)) {
    $_SESSION['flash_error'] = 'You do not have permission to view Sub Categories.';
    header('Location: /surgical_equipment/dashboard.php');
    exit;
}

$search              = trim($_GET['search'] ?? '');
$superCategoryFilter = (int) ($_GET['super_category_id'] ?? 0);
$categoryFilter      = (int) ($_GET['category_id'] ?? 0);
$statusFilter        = trim($_GET['status'] ?? '');

$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

try {
    $pdo = getDbConnection();

    // Summary counts
    $totalSuper = (int) $pdo->query("SELECT COUNT(*) FROM super_categories")->fetchColumn();
    $totalCats  = (int) $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $totalSubs  = (int) $pdo->query("SELECT COUNT(*) FROM sub_categories")->fetchColumn();
    $totalEquip = (int) $pdo->query("SELECT COUNT(*) FROM equipment_inventory")->fetchColumn();

    // Fetch Super Categories for filter
    $superCats = $pdo->query("SELECT id, name FROM super_categories ORDER BY sort_order ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Categories for filter (if superCategoryFilter is selected, filter categories)
    if ($superCategoryFilter > 0) {
        $catStmt = $pdo->prepare("SELECT id, name, super_category_id FROM categories WHERE super_category_id = :sid ORDER BY sort_order ASC, name ASC");
        $catStmt->execute([':sid' => $superCategoryFilter]);
        $allCategories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $allCategories = $pdo->query("SELECT id, name, super_category_id FROM categories ORDER BY sort_order ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    $where = [];
    $params = [];

    if ($search !== '') {
        $where[] = '(sub.name LIKE :s OR sub.slug LIKE :s2 OR sub.description LIKE :s3 OR c.name LIKE :s4 OR sc.name LIKE :s5)';
        $params[':s']  = '%' . $search . '%';
        $params[':s2'] = '%' . $search . '%';
        $params[':s3'] = '%' . $search . '%';
        $params[':s4'] = '%' . $search . '%';
        $params[':s5'] = '%' . $search . '%';
    }

    if ($categoryFilter > 0) {
        $where[] = 'sub.category_id = :cat_id';
        $params[':cat_id'] = $categoryFilter;
    } elseif ($superCategoryFilter > 0) {
        $where[] = 'c.super_category_id = :super_id';
        $params[':super_id'] = $superCategoryFilter;
    }

    if ($statusFilter !== '' && in_array($statusFilter, ['active', 'inactive'], true)) {
        $where[] = 'sub.status = :status';
        $params[':status'] = $statusFilter;
    }

    $whereClause = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $pdo->prepare("
        SELECT COUNT(*) AS c
        FROM sub_categories sub
        JOIN categories c ON sub.category_id = c.id
        JOIN super_categories sc ON c.super_category_id = sc.id
        $whereClause
    ");
    $countStmt->execute($params);
    $totalRows  = (int) $countStmt->fetch()['c'];
    $totalPages = max(1, (int) ceil($totalRows / $perPage));

    $dataStmt = $pdo->prepare("
        SELECT sub.*,
               c.id AS category_id,
               c.name AS category_name,
               c.slug AS category_slug,
               sc.id AS super_category_id,
               sc.name AS super_category_name,
               (SELECT COUNT(*) FROM equipment_inventory WHERE sub_category_id = sub.id OR name LIKE CONCAT('%', sub.name, '%')) AS equipment_count
        FROM sub_categories sub
        JOIN categories c ON sub.category_id = c.id
        JOIN super_categories sc ON c.super_category_id = sc.id
        $whereClause
        ORDER BY sc.sort_order ASC, c.sort_order ASC, sub.sort_order ASC, sub.name ASC
        LIMIT :lim OFFSET :off
    ");

    foreach ($params as $k => $v) {
        $dataStmt->bindValue($k, $v);
    }
    $dataStmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
    $dataStmt->bindValue(':off', $offset,  PDO::PARAM_INT);
    $dataStmt->execute();
    $subCategories = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $subCategories = [];
    $superCats = [];
    $allCategories = [];
    $totalRows = 0; $totalPages = 1;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header" style="margin-bottom: 20px;">
        <div>
            <h1 class="page-title" style="font-size: 1.875rem;">Sub Categories (Level 3)</h1>
            <p class="page-breadcrumb">
                <a href="/surgical_equipment/dashboard.php">Home</a> /
                <a href="/surgical_equipment/categories/super-categories.php">Catalogue Hierarchy</a> /
                <a href="/surgical_equipment/categories/categories.php">Categories</a> /
                <span>Sub Categories</span>
            </p>
        </div>
        <?php if ($role === 'admin'): ?>
            <div class="flex gap-2">
                <a href="/surgical_equipment/categories/add-sub-category.php<?= $categoryFilter > 0 ? '?category_id=' . $categoryFilter : '' ?>" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <span>Add Sub Category</span>
                </a>
            </div>
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

    <!-- 1. COMPACT 4-COLUMN METRICS ROW -->
    <div class="cat-stats-row">
        <div class="cat-stat-box" style="--accent-bar: var(--color-primary);">
            <div class="cat-stat-title">Super Categories</div>
            <div class="cat-stat-num"><?= number_format($totalSuper) ?></div>
            <div class="cat-stat-sub"><a href="/surgical_equipment/categories/super-categories.php" style="color:var(--color-primary); font-weight:600;">View Level 1 Divisions →</a></div>
        </div>
        <div class="cat-stat-box" style="--accent-bar: #0284c7;">
            <div class="cat-stat-title">Categories</div>
            <div class="cat-stat-num"><?= number_format($totalCats) ?></div>
            <div class="cat-stat-sub"><a href="/surgical_equipment/categories/categories.php" style="color:#0284c7; font-weight:600;">View Level 2 Categories →</a></div>
        </div>
        <div class="cat-stat-box" style="--accent-bar: #9333ea;">
            <div class="cat-stat-title">Sub Categories Active</div>
            <div class="cat-stat-num"><?= number_format($totalSubs) ?></div>
            <div class="cat-stat-sub">Level 3 • Specialized instrument clusters</div>
        </div>
        <div class="cat-stat-box" style="--accent-bar: #16a34a;">
            <div class="cat-stat-title">Catalogue Items</div>
            <div class="cat-stat-num"><?= number_format($totalEquip) ?></div>
            <div class="cat-stat-sub"><a href="/surgical_equipment/equipment/inventory-list.php" style="color:#16a34a; font-weight:600;">View Equipment Inventory →</a></div>
        </div>
    </div>

    <!-- 2. NAVIGATION BAR -->
    <div class="cat-tabs-wrap">
        <div class="cat-tabs-group">
            <a href="/surgical_equipment/categories/super-categories.php" class="cat-tab-btn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                1. Super Categories (<?= $totalSuper ?>)
            </a>
            <a href="/surgical_equipment/categories/categories.php" class="cat-tab-btn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                2. Categories (<?= $totalCats ?>)
            </a>
            <a href="/surgical_equipment/categories/sub-categories.php" class="cat-tab-btn active">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                3. Sub Categories (<?= $totalSubs ?>)
            </a>
        </div>

        <a href="/surgical_equipment/categories/super-categories.php?view=tree" class="cat-view-btn" style="background:#ffffff; border:1px solid var(--color-border); color:var(--color-primary); font-weight:700;">
            🌲 View Full Hierarchy Tree
        </a>
    </div>

    <!-- 3. SEARCH & FILTER CARD -->
    <div class="cat-filter-card">
        <form method="get" class="flex gap-2" style="flex-wrap:wrap; align-items:center; justify-content:space-between;">
            <div style="display:flex; gap:12px; flex:1; min-width:280px; align-items:center; flex-wrap:wrap;">
                <div style="flex:1; min-width:180px;">
                    <input type="text" id="search" name="search" class="form-control" placeholder="Search sub category, slug, or scope..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div style="min-width:180px;">
                    <select id="super_category_id" name="super_category_id" class="form-control" onchange="this.form.submit()">
                        <option value="">All Super Categories</option>
                        <?php foreach ($superCats as $sc): ?>
                            <option value="<?= (int)$sc['id'] ?>" <?= $superCategoryFilter === (int)$sc['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sc['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="min-width:180px;">
                    <select id="category_id" name="category_id" class="form-control">
                        <option value="">All Categories</option>
                        <?php foreach ($allCategories as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= $categoryFilter === (int)$c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="min-width:120px;">
                    <select id="status" name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <?php if ($search !== '' || $superCategoryFilter > 0 || $categoryFilter > 0 || $statusFilter !== ''): ?>
                    <a href="/surgical_equipment/categories/sub-categories.php" class="btn btn-secondary btn-sm">Clear</a>
                <?php endif; ?>
            </div>

            <div style="font-size:0.8125rem; color:var(--color-text-muted);">
                Showing <strong><?= count($subCategories) ?></strong> of <strong><?= $totalRows ?></strong> Sub Categories
            </div>
        </form>
    </div>

    <!-- 4. STRUCTURED DATA TABLE -->
    <div class="card p-0" style="overflow:hidden; border-radius:var(--radius-xl);">
        <div class="table-responsive">
            <table class="table-structured">
                <thead>
                    <tr>
                        <th style="width:55px; text-align:center;">#</th>
                        <th>Sub Category (Level 3)</th>
                        <th>Parent Category (Level 2)</th>
                        <th>Super Category (Level 1)</th>
                        <th style="width:170px;">URL Slug</th>
                        <th style="width:95px; text-align:center;">Products</th>
                        <th style="width:85px; text-align:center;">Status</th>
                        <th style="width:180px; text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subCategories)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                No sub categories found. <?php if ($role === 'admin'): ?><a href="/surgical_equipment/categories/add-sub-category.php">Add a sub category now</a>.<?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($subCategories as $item): ?>
                            <tr>
                                <td style="text-align:center;">
                                    <span style="font-weight:700; color:var(--color-text-muted); font-size:0.75rem; background:rgba(0,0,0,0.05); padding:2px 6px; border-radius:4px;">
                                        #<?= (int)$item['sort_order'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight:700; color:var(--color-text-heading); font-size:0.9375rem;">
                                        <a href="/surgical_equipment/categories/view-sub-category.php?id=<?= (int)$item['id'] ?>" style="color:inherit; text-decoration:none;" title="View sub category details">
                                            <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </div>
                                    <?php if (!empty($item['description'])): ?>
                                        <div style="font-size:0.75rem; color:var(--color-text-muted); max-width:260px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="/surgical_equipment/categories/view-category.php?id=<?= (int)$item['category_id'] ?>" style="text-decoration:none;" title="View parent category">
                                        <span style="background:#e0f2fe; color:#0369a1; font-weight:700; font-size:0.6875rem; padding:3px 8px; border-radius:4px; display:inline-block; max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                            <?= htmlspecialchars($item['category_name'], ENT_QUOTES, 'UTF-8') ?> →
                                        </span>
                                    </a>
                                </td>
                                <td>
                                    <a href="/surgical_equipment/categories/categories.php?super_category_id=<?= (int)$item['super_category_id'] ?>" style="text-decoration:none;">
                                        <span style="background:var(--color-primary-light); color:var(--color-primary); font-weight:700; font-size:0.6875rem; padding:3px 8px; border-radius:4px; display:inline-block; max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                            <?= htmlspecialchars($item['super_category_name'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </a>
                                </td>
                                <td>
                                    <span class="slug-badge" style="font-size:0.6875rem; max-width:160px;"><?= htmlspecialchars($item['slug'], ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td style="text-align:center;">
                                    <span style="font-weight:700; font-size:0.75rem; color:#047857; background:#ecfdf5; padding:3px 8px; border-radius:6px; display:inline-block;">
                                        <?= (int)$item['equipment_count'] ?> items
                                    </span>
                                </td>
                                <td style="text-align:center;">
                                    <?php if ($item['status'] === 'active'): ?>
                                        <span style="background:#dcfce7; color:#15803d; padding:2px 7px; border-radius:999px; font-size:0.6875rem; font-weight:700;">Active</span>
                                    <?php else: ?>
                                        <span style="background:#f1f5f9; color:#64748b; padding:2px 7px; border-radius:999px; font-size:0.6875rem; font-weight:700;">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right;">
                                    <div class="flex gap-1" style="justify-content:flex-end;">
                                        <a href="/surgical_equipment/categories/view-sub-category.php?id=<?= (int)$item['id'] ?>" class="btn btn-primary btn-sm" style="padding:4px 9px; font-size:0.75rem;" title="View Sub Category Details & Products">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                            View
                                        </a>
                                        <?php if ($role === 'admin'): ?>
                                            <a href="/surgical_equipment/categories/edit-sub-category.php?id=<?= (int)$item['id'] ?>" class="btn btn-secondary btn-sm" style="padding:4px 8px; font-size:0.75rem;" title="Edit Sub Category">
                                                Edit
                                            </a>
                                            <form method="post" action="/surgical_equipment/process-action.php" style="display:inline;" onsubmit="return confirm('Delete sub category \'<?= addslashes($item['name']) ?>\'?');">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="action" value="sub_category_delete">
                                                <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" style="padding:4px 8px; font-size:0.75rem;" title="Delete Sub Category">×</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div style="display:flex; justify-content:space-between; align-items:center; padding:14px 20px; border-top:1px solid var(--color-border); background:#fafbf8;">
                <div style="font-size:0.8125rem; color:var(--color-text-muted);">
                    Showing <?= $offset + 1 ?> to <?= min($offset + $perPage, $totalRows) ?> of <?= $totalRows ?> subcategories
                </div>
                <div class="flex gap-1">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&super_category_id=<?= $superCategoryFilter ?>&category_id=<?= $categoryFilter ?>&status=<?= urlencode($statusFilter) ?>"
                           class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-secondary' ?>">
                            <?= $p ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
