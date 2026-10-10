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
    $_SESSION['flash_error'] = 'Invalid sub category ID.';
    header('Location: /surgical_equipment/categories/sub-categories.php');
    exit;
}

try {
    $pdo = getDbConnection();

    // Fetch Sub Category with parent Category and Super Category
    $stmt = $pdo->prepare("
        SELECT sub.*,
               c.id AS category_id,
               c.name AS category_name,
               c.slug AS category_slug,
               sc.id AS super_category_id,
               sc.name AS super_category_name,
               sc.slug AS super_category_slug
        FROM sub_categories sub
        JOIN categories c ON sub.category_id = c.id
        JOIN super_categories sc ON c.super_category_id = sc.id
        WHERE sub.id = :id
    ");
    $stmt->execute([':id' => $id]);
    $subCategory = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$subCategory) {
        $_SESSION['flash_error'] = 'Sub Category not found.';
        header('Location: /surgical_equipment/categories/sub-categories.php');
        exit;
    }

    // Fetch Linked Products
    $equipStmt = $pdo->prepare("
        SELECT e.*, s.name AS supplier_name
        FROM equipment_inventory e
        LEFT JOIN suppliers s ON e.supplier_id = s.id
        WHERE e.sub_category_id = :id OR e.name LIKE :subName
        ORDER BY e.name ASC
    ");
    $equipStmt->execute([
        ':id'      => $id,
        ':subName' => '%' . $subCategory['name'] . '%'
    ]);
    $products = $equipStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $_SESSION['flash_error'] = 'Database error loading sub category details.';
    header('Location: /surgical_equipment/categories/sub-categories.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    <!-- Top Breadcrumb & Action Header -->
    <div class="page-header" style="margin-bottom: 20px;">
        <div>
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px; flex-wrap:wrap;">
                <a href="/surgical_equipment/categories/categories.php?super_category_id=<?= (int)$subCategory['super_category_id'] ?>" style="text-decoration:none;">
                    <span style="font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; background:var(--color-primary-light); color:var(--color-primary); padding:3px 8px; border-radius:4px;">
                        <?= htmlspecialchars($subCategory['super_category_name'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </a>
                <span style="color:var(--color-text-muted);">/</span>
                <a href="/surgical_equipment/categories/view-category.php?id=<?= (int)$subCategory['category_id'] ?>" style="text-decoration:none;">
                    <span style="font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; background:#e0f2fe; color:#0369a1; padding:3px 8px; border-radius:4px;">
                        <?= htmlspecialchars($subCategory['category_name'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </a>
                <span style="color:var(--color-text-muted);">/</span>
                <span style="font-size:0.75rem; font-weight:700; color:var(--color-text-muted);">Level 3 Subcategory</span>
            </div>
            <h1 class="page-title" style="font-size: 2rem; margin:0;">
                <?= htmlspecialchars($subCategory['name'], ENT_QUOTES, 'UTF-8') ?>
            </h1>
            <p class="page-breadcrumb" style="margin-top:4px;">
                <a href="/surgical_equipment/dashboard.php">Home</a> /
                <a href="/surgical_equipment/categories/super-categories.php">Catalogue Hierarchy</a> /
                <a href="/surgical_equipment/categories/categories.php">Categories</a> /
                <a href="/surgical_equipment/categories/sub-categories.php">Sub Categories</a> /
                <span><?= htmlspecialchars($subCategory['name'], ENT_QUOTES, 'UTF-8') ?></span>
            </p>
        </div>

        <div class="flex gap-2" style="align-items:center;">
            <a href="/surgical_equipment/categories/view-category.php?id=<?= (int)$subCategory['category_id'] ?>" class="btn btn-secondary">
                ← Parent Category
            </a>
            <a href="/surgical_equipment/categories/sub-categories.php" class="btn btn-secondary">
                Sub Categories List
            </a>
            <?php if ($role === 'admin'): ?>
                <a href="/surgical_equipment/categories/edit-sub-category.php?id=<?= (int)$subCategory['id'] ?>" class="btn btn-primary">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    Edit Sub Category
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <!-- Subcategory Metadata Card -->
    <div class="card mb-4" style="border-radius:var(--radius-xl); background:linear-gradient(180deg, #ffffff 0%, #fafcf8 100%);">
        <div style="display:grid; grid-template-columns: 1fr auto; gap:24px; align-items:center;">
            <div>
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px; flex-wrap:wrap;">
                    <span style="font-weight:700; color:var(--color-text-muted); font-size:0.8125rem; background:rgba(0,0,0,0.05); padding:3px 8px; border-radius:4px;">
                        Order Position: #<?= (int)$subCategory['sort_order'] ?>
                    </span>
                    <span class="slug-badge" style="font-size:0.8125rem;">
                        Slug: <?= htmlspecialchars($subCategory['slug'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <?php if ($subCategory['status'] === 'active'): ?>
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
                    <?= htmlspecialchars($subCategory['description'] ?? 'No technical specifications provided for this subcategory cluster.', ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>

            <!-- Metric Box -->
            <div style="background:#ffffff; border:1px solid var(--color-border); border-radius:var(--radius-lg); padding:14px 22px; text-align:center; min-width:140px; box-shadow:var(--shadow-sm);">
                <div style="font-size:0.6875rem; font-weight:700; text-transform:uppercase; color:var(--color-text-muted); letter-spacing:0.04em;">Linked Products</div>
                <div style="font-family:var(--font-serif); font-size:1.75rem; font-weight:700; color:#16a34a; line-height:1.2; margin-top:2px;">
                    <?= count($products) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Linked Equipment Items Table -->
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
        <h3 style="font-size:1.1875rem; font-weight:700; margin:0; color:var(--color-text-heading); display:flex; align-items:center; gap:8px;">
            <span style="width:10px; height:10px; border-radius:50%; background:#16a34a; display:inline-block;"></span>
            Linked Instruments & Devices in this Subcategory
            <span style="font-size:0.8125rem; font-weight:normal; color:var(--color-text-muted);">(<?= count($products) ?> items)</span>
        </h3>
        <?php if ($role === 'admin'): ?>
            <a href="/surgical_equipment/equipment/add-equipment.php" class="btn btn-primary btn-sm">
                + Add Equipment Item
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
                        <th>Supplier / Manufacturer</th>
                        <th>Current Location</th>
                        <th style="width:110px; text-align:center;">Status</th>
                        <th style="width:160px; text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                No equipment inventory records linked to this subcategory yet.
                                <?php if ($role === 'admin'): ?>
                                    <div style="margin-top:8px;">
                                        <a href="/surgical_equipment/equipment/add-equipment.php" class="btn btn-primary btn-sm">+ Add equipment item</a>
                                    </div>
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
