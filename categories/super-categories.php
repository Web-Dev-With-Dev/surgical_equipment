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
    $_SESSION['flash_error'] = 'You do not have permission to view Super Categories.';
    header('Location: /surgical_equipment/dashboard.php');
    exit;
}

$search       = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$viewMode     = trim($_GET['view'] ?? 'tree'); // 'tree' or 'table'

try {
    $pdo = getDbConnection();

    // Summary counts
    $totalSuper = (int) $pdo->query("SELECT COUNT(*) FROM super_categories")->fetchColumn();
    $totalCats  = (int) $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $totalSubs  = (int) $pdo->query("SELECT COUNT(*) FROM sub_categories")->fetchColumn();
    $totalEquip = (int) $pdo->query("SELECT COUNT(*) FROM equipment_inventory")->fetchColumn();

    $where = [];
    $params = [];

    if ($search !== '') {
        $where[] = '(sc.name LIKE :s OR sc.slug LIKE :s2 OR sc.description LIKE :s3)';
        $params[':s']  = '%' . $search . '%';
        $params[':s2'] = '%' . $search . '%';
        $params[':s3'] = '%' . $search . '%';
    }

    if ($statusFilter !== '' && in_array($statusFilter, ['active', 'inactive'], true)) {
        $where[] = 'sc.status = :status';
        $params[':status'] = $statusFilter;
    }

    $whereClause = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Fetch Super Categories
    $stmt = $pdo->prepare("
        SELECT sc.*,
               (SELECT COUNT(*) FROM categories WHERE super_category_id = sc.id) AS category_count,
               (SELECT COUNT(*) FROM sub_categories sub JOIN categories c ON sub.category_id = c.id WHERE c.super_category_id = sc.id) AS subcategory_count,
               (SELECT COUNT(*) FROM equipment_inventory WHERE super_category_id = sc.id) AS equipment_count
        FROM super_categories sc
        $whereClause
        ORDER BY sc.sort_order ASC, sc.name ASC
    ");
    $stmt->execute($params);
    $superCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch full hierarchy tree for the tree view
    $treeData = [];
    if (!empty($superCategories)) {
        $allCatsStmt = $pdo->query("
            SELECT c.*,
                   (SELECT COUNT(*) FROM sub_categories WHERE category_id = c.id) AS sub_count,
                   (SELECT COUNT(*) FROM equipment_inventory WHERE category_id = c.id) AS equip_count
            FROM categories c
            ORDER BY c.sort_order ASC, c.name ASC
        ");
        $allCats = $allCatsStmt->fetchAll(PDO::FETCH_ASSOC);

        $allSubsStmt = $pdo->query("
            SELECT sub.*,
                   (SELECT COUNT(*) FROM equipment_inventory WHERE sub_category_id = sub.id) AS equip_count
            FROM sub_categories sub
            ORDER BY sub.sort_order ASC, sub.name ASC
        ");
        $allSubs = $allSubsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Group subcategories by category_id
        $subsByCat = [];
        foreach ($allSubs as $sub) {
            $subsByCat[$sub['category_id']][] = $sub;
        }

        // Group categories by super_category_id
        $catsBySuper = [];
        foreach ($allCats as $cat) {
            $cat['subcategories'] = $subsByCat[$cat['id']] ?? [];
            $catsBySuper[$cat['super_category_id']][] = $cat;
        }

        foreach ($superCategories as $sc) {
            $sc['categories'] = $catsBySuper[$sc['id']] ?? [];
            $treeData[] = $sc;
        }
    }

} catch (PDOException $e) {
    error_log($e->getMessage());
    $superCategories = [];
    $treeData = [];
    $totalSuper = 0; $totalCats = 0; $totalSubs = 0; $totalEquip = 0;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    <!-- Top Header -->
    <div class="page-header" style="margin-bottom: 20px;">
        <div>
            <h1 class="page-title" style="font-size: 1.875rem;">Catalogue Hierarchy</h1>
            <p class="page-breadcrumb">
                <a href="/surgical_equipment/dashboard.php">Home</a> /
                <span>Catalogue Hierarchy</span> /
                <span>Super Categories (Level 1)</span>
            </p>
        </div>
        <?php if ($role === 'admin'): ?>
            <div class="flex gap-2">
                <a href="/surgical_equipment/categories/add-super-category.php" class="btn btn-primary" style="box-shadow: var(--shadow-sm);">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <span>Add Super Category</span>
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
            <div class="cat-stat-sub">Level 1 • Top-level divisions</div>
        </div>
        <div class="cat-stat-box" style="--accent-bar: #0284c7;">
            <div class="cat-stat-title">Categories</div>
            <div class="cat-stat-num"><?= number_format($totalCats) ?></div>
            <div class="cat-stat-sub"><a href="/surgical_equipment/categories/categories.php" style="color:#0284c7; font-weight:600;">View Level 2 Categories →</a></div>
        </div>
        <div class="cat-stat-box" style="--accent-bar: #9333ea;">
            <div class="cat-stat-title">Sub Categories</div>
            <div class="cat-stat-num"><?= number_format($totalSubs) ?></div>
            <div class="cat-stat-sub"><a href="/surgical_equipment/categories/sub-categories.php" style="color:#9333ea; font-weight:600;">View Level 3 Sub Categories →</a></div>
        </div>
        <div class="cat-stat-box" style="--accent-bar: #16a34a;">
            <div class="cat-stat-title">Linked Products</div>
            <div class="cat-stat-num"><?= number_format($totalEquip) ?></div>
            <div class="cat-stat-sub"><a href="/surgical_equipment/equipment/inventory-list.php" style="color:#16a34a; font-weight:600;">Open Inventory Store →</a></div>
        </div>
    </div>

    <!-- 2. NAVIGATION BAR & VIEW SWITCHER -->
    <div class="cat-tabs-wrap">
        <div class="cat-tabs-group">
            <a href="/surgical_equipment/categories/super-categories.php" class="cat-tab-btn active">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                1. Super Categories (<?= $totalSuper ?>)
            </a>
            <a href="/surgical_equipment/categories/categories.php" class="cat-tab-btn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                2. Categories (<?= $totalCats ?>)
            </a>
            <a href="/surgical_equipment/categories/sub-categories.php" class="cat-tab-btn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                3. Sub Categories (<?= $totalSubs ?>)
            </a>
        </div>

        <div class="cat-view-toggles">
            <a href="?view=tree<?= $search !== '' ? '&search=' . urlencode($search) : '' ?><?= $statusFilter !== '' ? '&status=' . urlencode($statusFilter) : '' ?>" class="cat-view-btn <?= $viewMode === 'tree' ? 'active' : '' ?>" title="Visual Structural Tree View">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                Tree View
            </a>
            <a href="?view=table<?= $search !== '' ? '&search=' . urlencode($search) : '' ?><?= $statusFilter !== '' ? '&status=' . urlencode($statusFilter) : '' ?>" class="cat-view-btn <?= $viewMode === 'table' ? 'active' : '' ?>" title="Table View">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                Table View
            </a>
        </div>
    </div>

    <!-- 3. SEARCH & FILTER CARD -->
    <div class="cat-filter-card">
        <form method="get" class="flex gap-2" style="flex-wrap:wrap; align-items:center; justify-content:space-between;">
            <input type="hidden" name="view" value="<?= htmlspecialchars($viewMode) ?>">
            
            <div style="display:flex; gap:12px; flex:1; min-width:280px; align-items:center; flex-wrap:wrap;">
                <div style="flex:1; min-width:220px;">
                    <input type="text" id="search" name="search" class="form-control" placeholder="Search super category, slug, or scope..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div style="min-width:150px;">
                    <select id="status" name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <?php if ($search !== '' || $statusFilter !== ''): ?>
                    <a href="/surgical_equipment/categories/super-categories.php?view=<?= urlencode($viewMode) ?>" class="btn btn-secondary btn-sm">Clear</a>
                <?php endif; ?>
            </div>

            <div style="font-size:0.8125rem; color:var(--color-text-muted);">
                Showing <strong><?= count($superCategories) ?></strong> Super Categories
            </div>
        </form>
    </div>

    <?php if ($viewMode === 'tree'): ?>
        <!-- ========================================================
             VIEW MODE 1: INTERACTIVE STRUCTURAL TREE EXPLORER
             ======================================================== -->
        <div class="struct-tree">
            <?php if (empty($treeData)): ?>
                <div class="card text-center py-5">
                    <p class="text-muted" style="font-size:0.9375rem;">No super categories found matching your criteria.</p>
                    <?php if ($role === 'admin'): ?>
                        <div style="margin-top:12px;">
                            <a href="/surgical_equipment/categories/add-super-category.php" class="btn btn-primary">+ Create First Super Category</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($treeData as $sc): ?>
                    <div class="struct-super-card">
                        <!-- Super Category Top Level Header -->
                        <div class="struct-super-header" onclick="toggleTreeSuper(<?= (int)$sc['id'] ?>)">
                            <div class="struct-super-title-group">
                                <div class="struct-icon-badge" style="background:var(--color-primary-light); color:var(--color-primary);">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                                </div>
                                <div>
                                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                        <span style="font-size:0.6875rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; background:rgba(0,0,0,0.06); padding:2px 6px; border-radius:4px; color:var(--color-text-muted);">
                                            Level 1 • #<?= (int)$sc['sort_order'] ?>
                                        </span>
                                        <h3 style="margin:0; font-size:1.0625rem; font-weight:700; color:var(--color-text-heading);">
                                            <?= htmlspecialchars($sc['name'], ENT_QUOTES, 'UTF-8') ?>
                                        </h3>
                                        <span class="slug-badge"><?= htmlspecialchars($sc['slug'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if ($sc['status'] === 'active'): ?>
                                            <span style="background:#dcfce7; color:#15803d; font-size:0.6875rem; font-weight:700; padding:2px 8px; border-radius:999px;">Active</span>
                                        <?php else: ?>
                                            <span style="background:#f1f5f9; color:#64748b; font-size:0.6875rem; font-weight:700; padding:2px 8px; border-radius:999px;">Inactive</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($sc['description'])): ?>
                                        <div style="font-size:0.8125rem; color:var(--color-text-muted); margin-top:4px;">
                                            <?= htmlspecialchars($sc['description'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Metrics & Actions -->
                            <div style="display:flex; align-items:center; gap:12px; flex-shrink:0;" onclick="event.stopPropagation();">
                                <span style="background:#e0f2fe; color:#0369a1; font-weight:700; font-size:0.75rem; padding:4px 10px; border-radius:6px;">
                                    <?= count($sc['categories']) ?> Categories
                                </span>
                                <span style="background:#ecfdf5; color:#047857; font-weight:700; font-size:0.75rem; padding:4px 10px; border-radius:6px;">
                                    <?= (int)$sc['equipment_count'] ?> Products
                                </span>

                                <?php if ($role === 'admin'): ?>
                                    <div class="flex gap-1">
                                        <a href="/surgical_equipment/categories/add-category.php?super_category_id=<?= (int)$sc['id'] ?>" class="btn btn-primary btn-sm" title="Add Category under this Super Category">
                                            + Add Category
                                        </a>
                                        <a href="/surgical_equipment/categories/add-super-category.php?edit=<?= (int)$sc['id'] ?>" class="btn btn-secondary btn-sm">
                                            Edit
                                        </a>
                                        <form method="post" action="/surgical_equipment/process-action.php" style="display:inline;" onsubmit="return confirm('Delete Super Category \'<?= addslashes($sc['name']) ?>\' and all nested items?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="action" value="super_category_delete">
                                            <input type="hidden" name="id" value="<?= (int)$sc['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </div>
                                <?php endif; ?>

                                <button type="button" class="btn btn-secondary btn-sm" style="padding:6px;" onclick="toggleTreeSuper(<?= (int)$sc['id'] ?>)">
                                    <svg id="chevron-super-<?= (int)$sc['id'] ?>" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="transition:transform 0.2s;"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Nested Categories Branch (Level 2 & Level 3) -->
                        <div id="tree-super-body-<?= (int)$sc['id'] ?>" class="struct-super-body">
                            <?php if (empty($sc['categories'])): ?>
                                <div style="padding:12px 16px; background:#f8faf5; border:1px dashed var(--color-border); border-radius:8px; font-size:0.8125rem; color:var(--color-text-muted); display:flex; justify-content:space-between; align-items:center;">
                                    <span>No categories created under this Super Category yet.</span>
                                    <?php if ($role === 'admin'): ?>
                                        <a href="/surgical_equipment/categories/add-category.php?super_category_id=<?= (int)$sc['id'] ?>" class="btn btn-primary btn-sm">+ Add First Category</a>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <?php foreach ($sc['categories'] as $cat): ?>
                                    <div class="struct-cat-branch">
                                        <div class="struct-cat-card">
                                            <div class="struct-cat-header">
                                                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                                                    <div style="width:28px; height:28px; border-radius:6px; background:#e0f2fe; color:#0284c7; display:flex; align-items:center; justify-content:center; font-weight:700;">
                                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                                                    </div>
                                                    <div>
                                                        <span style="font-size:0.6875rem; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; color:#0284c7;">Level 2 Category</span>
                                                        <div style="font-weight:700; font-size:0.9375rem; color:var(--color-text-heading);">
                                                            <a href="/surgical_equipment/categories/sub-categories.php?category_id=<?= (int)$cat['id'] ?>" style="color:inherit; text-decoration:none;">
                                                                <?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?>
                                                            </a>
                                                            <span class="slug-badge" style="margin-left:6px; font-weight:normal;"><?= htmlspecialchars($cat['slug'], ENT_QUOTES, 'UTF-8') ?></span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div style="display:flex; align-items:center; gap:6px;">
                                                    <a href="/surgical_equipment/categories/view-category.php?id=<?= (int)$cat['id'] ?>" class="btn btn-primary btn-sm" style="padding:4px 9px; font-size:0.75rem;" title="View category details">
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                                        View
                                                    </a>
                                                    <span style="font-size:0.75rem; font-weight:600; color:#7e22ce; background:#f3e8ff; padding:3px 8px; border-radius:4px;">
                                                        <?= count($cat['subcategories']) ?> Subs
                                                    </span>

                                                    <?php if ($role === 'admin'): ?>
                                                        <a href="/surgical_equipment/categories/add-sub-category.php?category_id=<?= (int)$cat['id'] ?>" class="btn btn-secondary btn-sm" style="padding:4px 8px; font-size:0.75rem;" title="Add Sub Category">
                                                            + Sub
                                                        </a>
                                                        <a href="/surgical_equipment/categories/edit-category.php?id=<?= (int)$cat['id'] ?>" class="btn btn-secondary btn-sm" style="padding:4px 8px; font-size:0.75rem;">
                                                            Edit
                                                        </a>
                                                        <form method="post" action="/surgical_equipment/process-action.php" style="display:inline;" onsubmit="return confirm('Delete category \'<?= addslashes($cat['name']) ?>\'?');">
                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                                            <input type="hidden" name="action" value="category_delete">
                                                            <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm" style="padding:4px 8px; font-size:0.75rem;">×</button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <!-- Level 3: Sub Category Pills Container -->
                                            <div class="struct-sub-container">
                                                <span style="font-size:0.6875rem; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; color:var(--color-text-muted); margin-right:4px;">
                                                    Level 3 Subcategories:
                                                </span>
                                                <?php if (empty($cat['subcategories'])): ?>
                                                    <span style="font-size:0.75rem; color:var(--color-text-muted); font-style:italic;">No subcategories yet.</span>
                                                    <?php if ($role === 'admin'): ?>
                                                        <a href="/surgical_equipment/categories/add-sub-category.php?category_id=<?= (int)$cat['id'] ?>" style="font-size:0.75rem; color:#8b5cf6; font-weight:600; text-decoration:underline;">+ Add now</a>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?php foreach ($cat['subcategories'] as $sub): ?>
                                                        <div class="struct-sub-pill">
                                                            <span style="width:6px; height:6px; border-radius:50%; background:#8b5cf6; display:inline-block;"></span>
                                                            <a href="/surgical_equipment/categories/view-sub-category.php?id=<?= (int)$sub['id'] ?>" style="color:inherit; text-decoration:none; font-weight:700;" title="View Sub Category Details">
                                                                <?= htmlspecialchars($sub['name'], ENT_QUOTES, 'UTF-8') ?>
                                                            </a>
                                                            <a href="/surgical_equipment/categories/view-sub-category.php?id=<?= (int)$sub['id'] ?>" style="color:#7e22ce; font-size:0.6875rem; padding:2px 6px; border-radius:4px; background:#ede9fe; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:3px;" title="View Sub Category">
                                                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                                                View
                                                            </a>
                                                            <?php if ($role === 'admin'): ?>
                                                                <a href="/surgical_equipment/categories/edit-sub-category.php?id=<?= (int)$sub['id'] ?>" style="color:var(--color-text-muted); font-size:0.6875rem; padding:2px 5px; border-radius:3px; background:#f1f5f9; text-decoration:none;" title="Edit Sub Category">✎</a>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <!-- ========================================================
             VIEW MODE 2: REFINED STRUCTURED DATA TABLE
             ======================================================== -->
        <div class="card p-0" style="overflow:hidden; border-radius:var(--radius-xl);">
            <div class="table-responsive">
                <table class="table-structured">
                    <thead>
                        <tr>
                            <th style="width:65px; text-align:center;">Order</th>
                            <th>Super Category Name & Scope</th>
                            <th style="width:240px;">URL Slug</th>
                            <th style="width:130px; text-align:center;">Categories</th>
                            <th style="width:130px; text-align:center;">Sub Categories</th>
                            <th style="width:130px; text-align:center;">Products</th>
                            <th style="width:100px; text-align:center;">Status</th>
                            <th style="width:160px; text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($superCategories)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    No super categories found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($superCategories as $item): ?>
                                <tr>
                                    <td style="text-align:center;">
                                        <span style="font-weight:700; color:var(--color-text-muted); font-size:0.8125rem; background:rgba(0,0,0,0.05); padding:3px 8px; border-radius:4px;">
                                            #<?= (int)$item['sort_order'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:12px;">
                                            <div style="width:34px; height:34px; border-radius:8px; background:var(--color-primary-light); color:var(--color-primary); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                                            </div>
                                            <div>
                                                <a href="/surgical_equipment/categories/categories.php?super_category_id=<?= (int)$item['id'] ?>" style="font-weight:700; color:var(--color-text-heading); text-decoration:none; font-size:0.9375rem;">
                                                    <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>
                                                </a>
                                                <?php if (!empty($item['description'])): ?>
                                                    <div style="font-size:0.75rem; color:var(--color-text-muted); max-width:380px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8') ?>">
                                                        <?= htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8') ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="slug-badge"><?= htmlspecialchars($item['slug'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </td>
                                    <td style="text-align:center;">
                                        <a href="/surgical_equipment/categories/categories.php?super_category_id=<?= (int)$item['id'] ?>" style="text-decoration:none; font-weight:700; font-size:0.8125rem; color:#0369a1; background:#e0f2fe; padding:4px 10px; border-radius:6px; display:inline-block;">
                                            <?= (int)$item['category_count'] ?> Categories →
                                        </a>
                                    </td>
                                    <td style="text-align:center;">
                                        <span style="font-weight:700; font-size:0.8125rem; color:#7e22ce; background:#f3e8ff; padding:4px 10px; border-radius:6px; display:inline-block;">
                                            <?= (int)$item['subcategory_count'] ?> Subcategories
                                        </span>
                                    </td>
                                    <td style="text-align:center;">
                                        <span style="font-weight:700; font-size:0.8125rem; color:#047857; background:#ecfdf5; padding:4px 10px; border-radius:6px; display:inline-block;">
                                            <?= (int)$item['equipment_count'] ?> Products
                                        </span>
                                    </td>
                                    <td style="text-align:center;">
                                        <?php if ($item['status'] === 'active'): ?>
                                            <span style="background:#dcfce7; color:#15803d; padding:4px 10px; border-radius:999px; font-size:0.75rem; font-weight:700;">Active</span>
                                        <?php else: ?>
                                            <span style="background:#f1f5f9; color:#64748b; padding:4px 10px; border-radius:999px; font-size:0.75rem; font-weight:700;">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <?php if ($role === 'admin'): ?>
                                            <div class="flex gap-1" style="justify-content:flex-end;">
                                                <a href="/surgical_equipment/categories/add-super-category.php?edit=<?= (int)$item['id'] ?>" class="btn btn-secondary btn-sm">
                                                    Edit
                                                </a>
                                                <form method="post" action="/surgical_equipment/process-action.php" style="display:inline;" onsubmit="return confirm('Delete Super Category \'<?= addslashes($item['name']) ?>\'?');">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="action" value="super_category_delete">
                                                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <a href="/surgical_equipment/categories/categories.php?super_category_id=<?= (int)$item['id'] ?>" class="btn btn-secondary btn-sm">Explore</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</main>

<script>
function toggleTreeSuper(id) {
    const body = document.getElementById('tree-super-body-' + id);
    const chevron = document.getElementById('chevron-super-<?= $sc['id'] ?? 0 ?>') || document.querySelector('#tree-super-body-' + id).parentElement.querySelector('svg');
    if (body) {
        if (body.style.display === 'none') {
            body.style.display = 'flex';
            if (chevron) chevron.style.transform = 'rotate(0deg)';
        } else {
            body.style.display = 'none';
            if (chevron) chevron.style.transform = 'rotate(-90deg)';
        }
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
