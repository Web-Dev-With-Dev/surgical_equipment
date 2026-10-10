<?php
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /surgical_equipment/auth/login.php');
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    $_SESSION['flash_error'] = 'Admin access required to edit sub categories.';
    header('Location: /surgical_equipment/categories/sub-categories.php');
    exit;
}

$csrf_token = $_SESSION['csrf_token'] ?? '';
$id         = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['flash_error'] = 'Invalid Sub Category ID.';
    header('Location: /surgical_equipment/categories/sub-categories.php');
    exit;
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('
        SELECT sub.*, c.super_category_id
        FROM sub_categories sub
        JOIN categories c ON sub.category_id = c.id
        WHERE sub.id = :id
    ');
    $stmt->execute([':id' => $id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        $_SESSION['flash_error'] = 'Sub Category not found.';
        header('Location: /surgical_equipment/categories/sub-categories.php');
        exit;
    }

    $superCats = $pdo->query("SELECT id, name FROM super_categories WHERE status = 'active' OR id = " . (int)$item['super_category_id'] . " ORDER BY sort_order ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);

    $categories = $pdo->query("
        SELECT c.id, c.name, c.super_category_id, sc.name AS super_category_name
        FROM categories c
        JOIN super_categories sc ON c.super_category_id = sc.id
        WHERE c.status = 'active' OR c.id = " . (int)$item['category_id'] . "
        ORDER BY sc.sort_order ASC, c.sort_order ASC, c.name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Linked equipment count
    $equipStmt = $pdo->prepare("SELECT COUNT(*) FROM equipment_inventory WHERE sub_category_id = :id");
    $equipStmt->execute([':id' => $id]);
    $equipCount = (int) $equipStmt->fetchColumn();

} catch (PDOException $e) {
    error_log($e->getMessage());
    $_SESSION['flash_error'] = 'Database error fetching sub category.';
    header('Location: /surgical_equipment/categories/sub-categories.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Edit Sub Category (Level 3)</h1>
            <p class="page-breadcrumb">
                <a href="/surgical_equipment/dashboard.php">Home</a> /
                <a href="/surgical_equipment/categories/super-categories.php">Super Categories</a> /
                <a href="/surgical_equipment/categories/categories.php">Categories</a> /
                <a href="/surgical_equipment/categories/sub-categories.php">Sub Categories</a> /
                <span>Edit (<?= htmlspecialchars($item['name']) ?>)</span>
            </p>
        </div>
        <a href="/surgical_equipment/categories/sub-categories.php" class="btn btn-secondary">
            ← Back to Sub Categories
        </a>
    </div>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns: 1fr 340px; gap:24px; align-items:start;">
        <div class="card">
            <form method="post" action="/surgical_equipment/process-action.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="sub_category_update">
                <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">

                <!-- Filter / Cascade Super Category -->
                <div class="form-group mb-3">
                    <label for="super_cat_filter" class="form-label" style="font-weight:600;">
                        1. Super Category (Level 1)
                    </label>
                    <select id="super_cat_filter" class="form-control">
                        <option value="">-- All Super Categories --</option>
                        <?php foreach ($superCats as $sc): ?>
                            <option value="<?= (int)$sc['id'] ?>" <?= (int)$item['super_category_id'] === (int)$sc['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sc['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Select Parent Category -->
                <div class="form-group mb-3">
                    <label for="category_id" class="form-label" style="font-weight:600;">
                        2. Parent Category (Level 2) <span style="color:#ef4444;">*</span>
                    </label>
                    <select id="category_id" name="category_id" class="form-control" required>
                        <option value="">-- Choose Category --</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" data-super="<?= (int)$c['super_category_id'] ?>" <?= (int)$item['category_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['super_category_name'] . ' → ' . $c['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Sub Category Name -->
                <div class="form-group mb-3">
                    <label for="name" class="form-label" style="font-weight:600;">
                        3. Sub Category Name <span style="color:#ef4444;">*</span>
                    </label>
                    <input type="text" id="name" name="name" class="form-control" required
                           value="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <!-- Slug -->
                <div class="form-group mb-3">
                    <label for="slug" class="form-label" style="font-weight:600;">
                        URL Slug
                    </label>
                    <input type="text" id="slug" name="slug" class="form-control"
                           value="<?= htmlspecialchars($item['slug'], ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <!-- Description -->
                <div class="form-group mb-3">
                    <label for="description" class="form-label" style="font-weight:600;">
                        Description / Specifications
                    </label>
                    <textarea id="description" name="description" class="form-control" rows="3"><?= htmlspecialchars($item['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="row" style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                    <div class="form-group mb-3">
                        <label for="status" class="form-label" style="font-weight:600;">
                            Status
                        </label>
                        <select id="status" name="status" class="form-control">
                            <option value="active" <?= $item['status'] === 'active' ? 'selected' : '' ?>>Active (Visible)</option>
                            <option value="inactive" <?= $item['status'] === 'inactive' ? 'selected' : '' ?>>Inactive (Hidden)</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label for="sort_order" class="form-label" style="font-weight:600;">
                            Display Sort Order
                        </label>
                        <input type="number" id="sort_order" name="sort_order" class="form-control"
                               value="<?= (int)$item['sort_order'] ?>" min="0">
                    </div>
                </div>

                <div style="margin-top:24px; display:flex; gap:12px; align-items:center;">
                    <button type="submit" class="btn btn-primary">
                        Save Changes
                    </button>
                    <a href="/surgical_equipment/categories/sub-categories.php" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        <div>
            <div class="card" style="background:#FAFBF8; border-color:var(--color-border);">
                <h4 style="margin:0 0 12px 0; font-size:0.9375rem; font-weight:700; color:var(--color-text-heading);">
                    Item Linkages
                </h4>
                <p style="font-size:0.8125rem; color:var(--color-text-muted); line-height:1.5; margin-bottom:14px;">
                    This subcategory has <strong><?= $equipCount ?></strong> direct equipment inventory items associated.
                </p>
                <a href="/surgical_equipment/equipment/inventory-list.php" class="btn btn-secondary btn-sm" style="width:100%; text-align:center;">
                    View Inventory Items
                </a>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const superSelect = document.getElementById('super_cat_filter');
    const catSelect = document.getElementById('category_id');

    function filterCategories() {
        const selectedSuper = superSelect.value;
        const options = catSelect.querySelectorAll('option');

        options.forEach(opt => {
            if (!opt.value) return;
            const superId = opt.getAttribute('data-super');
            if (!selectedSuper || superId === selectedSuper) {
                opt.style.display = '';
            } else {
                opt.style.display = 'none';
            }
        });
    }

    if (superSelect) {
        superSelect.addEventListener('change', filterCategories);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
