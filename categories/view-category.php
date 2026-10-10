<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/product-helpers.php';

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
$id         = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['flash_error'] = 'Invalid category ID.';
    header('Location: /surgical_equipment/categories/categories.php');
    exit;
}

try {
    $pdo = getDbConnection();

    // Fetch Category with Super Category
    $stmt = $pdo->prepare("
        SELECT c.*,
               sc.id AS super_category_id,
               sc.name AS super_category_name,
               sc.slug AS super_category_slug
        FROM categories c
        JOIN super_categories sc ON c.super_category_id = sc.id
        WHERE c.id = :id
    ");
    $stmt->execute([':id' => $id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        $_SESSION['flash_error'] = 'Category not found.';
        header('Location: /surgical_equipment/categories/categories.php');
        exit;
    }

    // Fetch Subcategories under this category
    $subStmt = $pdo->prepare("
        SELECT sub.*,
               (SELECT COUNT(*) FROM equipment_inventory WHERE sub_category_id = sub.id OR name LIKE CONCAT('%', sub.name, '%')) AS product_count
        FROM sub_categories sub
        WHERE sub.category_id = :id
        ORDER BY sub.sort_order ASC, sub.name ASC
    ");
    $subStmt->execute([':id' => $id]);
    $subCategories = $subStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Linked Equipment Items
    $equipStmt = $pdo->prepare("
        SELECT e.*, s.name AS supplier_name
        FROM equipment_inventory e
        LEFT JOIN suppliers s ON e.supplier_id = s.id
        WHERE e.category_id = :id OR e.category LIKE :catName
        ORDER BY e.name ASC
    ");
    $equipStmt->execute([
        ':id'      => $id,
        ':catName' => '%' . $category['name'] . '%'
    ]);
    $products = $equipStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $_SESSION['flash_error'] = 'Database error loading category details.';
    header('Location: /surgical_equipment/categories/categories.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    <!-- Top Breadcrumb & Action Header -->
    <div class="page-header" style="margin-bottom: 20px;">
        <div>
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                <a href="/surgical_equipment/categories/categories.php?super_category_id=<?= (int)$category['super_category_id'] ?>" style="text-decoration:none;">
                    <span style="font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; background:var(--color-primary-light); color:var(--color-primary); padding:3px 8px; border-radius:4px;">
                        <?= htmlspecialchars($category['super_category_name'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </a>
                <span style="color:var(--color-text-muted);">/</span>
                <span style="font-size:0.75rem; font-weight:700; color:var(--color-text-muted);">Level 2 Category</span>
            </div>
            <h1 class="page-title" style="font-size: 2rem; margin:0;">
                <?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?>
            </h1>
            <p class="page-breadcrumb" style="margin-top:4px;">
                <a href="/surgical_equipment/dashboard.php">Home</a> /
                <a href="/surgical_equipment/categories/super-categories.php">Catalogue Hierarchy</a> /
                <a href="/surgical_equipment/categories/categories.php">Categories</a> /
                <span><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></span>
            </p>
        </div>

        <div class="flex gap-2" style="align-items:center;">
            <a href="/surgical_equipment/categories/categories.php" class="btn btn-secondary">
                ← Back to Categories
            </a>
            <?php if ($role === 'admin'): ?>
                <a href="/surgical_equipment/categories/add-sub-category.php?category_id=<?= (int)$category['id'] ?>" class="btn btn-secondary">
                    + Add Sub Category
                </a>
                <a href="/surgical_equipment/categories/edit-category.php?id=<?= (int)$category['id'] ?>" class="btn btn-primary">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    Edit Category
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <!-- Category Metadata Card -->
    <div class="card mb-4" style="border-radius:var(--radius-xl); background:linear-gradient(180deg, #ffffff 0%, #fafcf8 100%);">
        <div style="display:grid; grid-template-columns: 1fr auto; gap:24px; align-items:center;">
            <div>
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px; flex-wrap:wrap;">
                    <span style="font-weight:700; color:var(--color-text-muted); font-size:0.8125rem; background:rgba(0,0,0,0.05); padding:3px 8px; border-radius:4px;">
                        Order Position: #<?= (int)$category['sort_order'] ?>
                    </span>
                    <span class="slug-badge" style="font-size:0.8125rem;">
                        Slug: <?= htmlspecialchars($category['slug'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <?php if ($category['status'] === 'active'): ?>
                        <span style="background:#dcfce7; color:#15803d; font-size:0.75rem; font-weight:700; padding:3px 10px; border-radius:999px;">
                            ● Active (Published)
                        </span>
                    <?php else: ?>
                        <span style="background:#f1f5f9; color:#64748b; font-size:0.75rem; font-weight:700; padding:3px 10px; border-radius:999px;">
                            ○ Inactive (Hidden)
                        </span>
                    <?php endif; ?>
                </div>

                <p style="font-size:0.9375rem; color:var(--color-text-body); margin:0; line-height:1.6; max-width:850px;">
                    <?= htmlspecialchars($category['description'] ?? 'No technical description provided for this surgical category.', ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>

            <!-- Quick Metrics Pills -->
            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <div style="background:#ffffff; border:1px solid var(--color-border); border-radius:var(--radius-lg); padding:12px 18px; text-align:center; min-width:120px; box-shadow:var(--shadow-sm);">
                    <div style="font-size:0.6875rem; font-weight:700; text-transform:uppercase; color:var(--color-text-muted); letter-spacing:0.04em;">Subcategories</div>
                    <div style="font-family:var(--font-serif); font-size:1.625rem; font-weight:700; color:#7e22ce; line-height:1.2; margin-top:2px;">
                        <?= count($subCategories) ?>
                    </div>
                </div>
                <div style="background:#ffffff; border:1px solid var(--color-border); border-radius:var(--radius-lg); padding:12px 18px; text-align:center; min-width:120px; box-shadow:var(--shadow-sm);">
                    <div style="font-size:0.6875rem; font-weight:700; text-transform:uppercase; color:var(--color-text-muted); letter-spacing:0.04em;">Equipment Items</div>
                    <div style="font-family:var(--font-serif); font-size:1.625rem; font-weight:700; color:#16a34a; line-height:1.2; margin-top:2px;">
                        <?= count($products) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 1: Child Subcategories -->
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
        <h3 style="font-size:1.1875rem; font-weight:700; margin:0; color:var(--color-text-heading); display:flex; align-items:center; gap:8px;">
            <span style="width:10px; height:10px; border-radius:50%; background:#9333ea; display:inline-block;"></span>
            Child Sub Categories (Level 3)
            <span style="font-size:0.8125rem; font-weight:normal; color:var(--color-text-muted);">(<?= count($subCategories) ?> clusters)</span>
        </h3>
        <?php if ($role === 'admin'): ?>
            <a href="/surgical_equipment/categories/add-sub-category.php?category_id=<?= (int)$category['id'] ?>" class="btn btn-secondary btn-sm">
                + Add Sub Category
            </a>
        <?php endif; ?>
    </div>

    <div class="card p-0 mb-5" style="overflow:hidden; border-radius:var(--radius-xl);">
        <div class="table-responsive">
            <table class="table-structured">
                <thead>
                    <tr>
                        <th style="width:65px; text-align:center;">Order</th>
                        <th>Sub Category Name</th>
                        <th style="width:220px;">Slug</th>
                        <th>Description / Specifications</th>
                        <th style="width:120px; text-align:center;">Products</th>
                        <th style="width:90px; text-align:center;">Status</th>
                        <?php if ($role === 'admin'): ?>
                            <th style="width:140px; text-align:right;">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subCategories)): ?>
                        <tr>
                            <td colspan="<?= $role === 'admin' ? '7' : '6' ?>" class="text-center py-4 text-muted">
                                No subcategories created under <?= htmlspecialchars($category['name']) ?> yet.
                                <?php if ($role === 'admin'): ?>
                                    <a href="/surgical_equipment/categories/add-sub-category.php?category_id=<?= (int)$category['id'] ?>" style="color:var(--color-primary); font-weight:600; text-decoration:underline;">Add one now</a>.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($subCategories as $sub): ?>
                            <tr>
                                <td style="text-align:center;">
                                    <span style="font-weight:700; color:var(--color-text-muted); font-size:0.8125rem; background:rgba(0,0,0,0.05); padding:3px 8px; border-radius:4px;">
                                        #<?= (int)$sub['sort_order'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight:700; font-size:0.9375rem; color:var(--color-text-heading);">
                                        <a href="/surgical_equipment/categories/view-sub-category.php?id=<?= (int)$sub['id'] ?>" style="color:inherit; text-decoration:none;" title="View subcategory details & products">
                                            <?= htmlspecialchars($sub['name'], ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <span class="slug-badge"><?= htmlspecialchars($sub['slug'], ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td>
                                    <div style="font-size:0.8125rem; color:var(--color-text-muted); max-width:340px;">
                                        <?= htmlspecialchars($sub['description'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </td>
                                <td style="text-align:center;">
                                    <a href="/surgical_equipment/categories/view-sub-category.php?id=<?= (int)$sub['id'] ?>" style="text-decoration:none;">
                                        <span style="font-weight:700; font-size:0.8125rem; color:#7e22ce; background:#f3e8ff; padding:3px 10px; border-radius:6px; display:inline-block;">
                                            <?= (int)$sub['product_count'] ?> items →
                                        </span>
                                    </a>
                                </td>
                                <td style="text-align:center;">
                                    <?php if ($sub['status'] === 'active'): ?>
                                        <span style="background:#dcfce7; color:#15803d; padding:3px 8px; border-radius:999px; font-size:0.75rem; font-weight:700;">Active</span>
                                    <?php else: ?>
                                        <span style="background:#f1f5f9; color:#64748b; padding:3px 8px; border-radius:999px; font-size:0.75rem; font-weight:700;">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right;">
                                    <div class="flex gap-1" style="justify-content:flex-end;">
                                        <a href="/surgical_equipment/categories/view-sub-category.php?id=<?= (int)$sub['id'] ?>" class="btn btn-primary btn-sm" style="padding:4px 9px; font-size:0.75rem;" title="View subcategory details">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                            View
                                        </a>
                                        <?php if ($role === 'admin'): ?>
                                            <a href="/surgical_equipment/categories/edit-sub-category.php?id=<?= (int)$sub['id'] ?>" class="btn btn-secondary btn-sm" style="padding:4px 8px; font-size:0.75rem;">
                                                Edit
                                            </a>
                                            <form method="post" action="/surgical_equipment/process-action.php" style="display:inline;" onsubmit="return confirm('Delete sub category \'<?= addslashes($sub['name']) ?>\'?');">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="action" value="sub_category_delete">
                                                <input type="hidden" name="id" value="<?= (int)$sub['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" style="padding:4px 8px; font-size:0.75rem;">Delete</button>
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
    </div>

    <!-- Section 2: Linked Equipment Items -->
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
        <h3 style="font-size:1.1875rem; font-weight:700; margin:0; color:var(--color-text-heading); display:flex; align-items:center; gap:8px;">
            <span style="width:10px; height:10px; border-radius:50%; background:#16a34a; display:inline-block;"></span>
            Linked Equipment & Products in this Category
            <span style="font-size:0.8125rem; font-weight:normal; color:var(--color-text-muted);">(<?= count($products) ?> instruments)</span>
        </h3>
        <?php if ($role === 'admin'): ?>
            <a href="/surgical_equipment/equipment/add-equipment.php" class="btn btn-primary btn-sm">
                + Add Equipment
            </a>
        <?php endif; ?>
    </div>

    <div class="card p-0" style="overflow:hidden; border-radius:var(--radius-xl);">
        <div class="table-responsive">
            <table class="table-structured">
                <thead>
                    <tr>
                        <th style="width:70px;">Photo</th>
                        <th style="width:120px;">Code / SKU</th>
                        <th>Product / Instrument Name</th>
                        <th>Supplier / Brand</th>
                        <th>Location</th>
                        <th style="width:110px; text-align:center;">Status</th>
                        <th style="width:150px; text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                No equipment inventory records linked to this category yet.
                                <?php if ($role === 'admin'): ?>
                                    <a href="/surgical_equipment/equipment/add-equipment.php" style="color:var(--color-primary); font-weight:600; text-decoration:underline;">Add equipment now</a>.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $p): ?>
                            <?php 
                            $imgUrl = get_product_image_url($p['category'], $p['name'], $p['equipment_code'], $p['notes'] ?? '');
                            ?>
                            <tr>
                                <td>
                                    <div style="width:44px; height:44px; border-radius:6px; overflow:hidden; border:1px solid var(--color-border); background:#FAFBF8; display:flex; align-items:center; justify-content:center;">
                                        <img src="<?= htmlspecialchars($imgUrl) ?>" alt="Product" style="max-width:100%; max-height:100%; object-fit:contain;">
                                    </div>
                                </td>
                                <td>
                                    <code style="font-size:0.8125rem; font-weight:700; color:var(--color-primary);">
                                        <?= htmlspecialchars($p['equipment_code'], ENT_QUOTES, 'UTF-8') ?>
                                    </code>
                                </td>
                                <td>
                                    <div style="font-weight:700; font-size:0.9375rem; color:var(--color-text-heading);">
                                        <?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-size:0.8125rem; color:var(--color-text-muted);">
                                        <?= htmlspecialchars($p['supplier_name'] ?? 'Apex OEM Partner', ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="font-size:0.8125rem; color:var(--color-text-body);">
                                        <?= htmlspecialchars($p['current_location'] ?? 'Main Store', ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td style="text-align:center;">
                                    <span style="font-size:0.75rem; font-weight:700; padding:3px 8px; border-radius:999px; text-transform:capitalize; background:<?= $p['status']==='available' ? '#dcfce7; color:#15803d;' : '#fee2e2; color:#ef4444;' ?>">
                                        <?= htmlspecialchars(str_replace('_', ' ', $p['status'])) ?>
                                    </span>
                                </td>
                                <td style="text-align:right;">
                                    <div class="flex gap-1" style="justify-content:flex-end;">
                                        <a href="/surgical_equipment/product-details.php?id=<?= (int)$p['id'] ?>" target="_blank" class="btn btn-secondary btn-sm" title="View in Public Store">
                                            Store ↗
                                        </a>
                                        <a href="/surgical_equipment/equipment/equipment-details.php?id=<?= (int)$p['id'] ?>" class="btn btn-secondary btn-sm" title="View in Inventory Management">
                                            Manage
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
