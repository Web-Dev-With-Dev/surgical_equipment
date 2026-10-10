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
    $_SESSION['flash_error'] = 'Admin access required to add sub categories.';
    header('Location: /surgical_equipment/categories/sub-categories.php');
    exit;
}

$csrf_token     = $_SESSION['csrf_token'] ?? '';
$presetCategory = (int) ($_GET['category_id'] ?? 0);

try {
    $pdo = getDbConnection();

    // Get super categories
    $superCats = $pdo->query("SELECT id, name FROM super_categories WHERE status = 'active' ORDER BY sort_order ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);

    // Get all active categories with their super category info
    $categories = $pdo->query("
        SELECT c.id, c.name, c.super_category_id, sc.name AS super_category_name
        FROM categories c
        JOIN super_categories sc ON c.super_category_id = sc.id
        WHERE c.status = 'active'
        ORDER BY sc.sort_order ASC, c.sort_order ASC, c.name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (empty($categories)) {
        $_SESSION['flash_error'] = 'Please create at least one Category first before adding sub categories.';
        header('Location: /surgical_equipment/categories/add-category.php');
        exit;
    }

    // Find parent super category for preset category if provided
    $selectedSuperId = 0;
    if ($presetCategory > 0) {
        foreach ($categories as $cat) {
            if ((int)$cat['id'] === $presetCategory) {
                $selectedSuperId = (int)$cat['super_category_id'];
                break;
            }
        }
    }

} catch (PDOException $e) {
    error_log($e->getMessage());
    $categories = [];
    $superCats = [];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Add Sub Category (Level 3)</h1>
            <p class="page-breadcrumb">
                <a href="/surgical_equipment/dashboard.php">Home</a> /
                <a href="/surgical_equipment/categories/super-categories.php">Super Categories</a> /
                <a href="/surgical_equipment/categories/categories.php">Categories</a> /
                <a href="/surgical_equipment/categories/sub-categories.php">Sub Categories</a> /
                <span>Add Sub Category</span>
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
                <input type="hidden" name="action" value="sub_category_create">

                <!-- Filter / Cascade Super Category -->
                <div class="form-group mb-3">
                    <label for="super_cat_filter" class="form-label" style="font-weight:600;">
                        1. Super Category (Level 1)
                    </label>
                    <select id="super_cat_filter" class="form-control">
                        <option value="">-- All Super Categories --</option>
                        <?php foreach ($superCats as $sc): ?>
                            <option value="<?= (int)$sc['id'] ?>" <?= $selectedSuperId === (int)$sc['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sc['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Filter the category list below by medical division.</small>
                </div>

                <!-- Select Parent Category -->
                <div class="form-group mb-3">
                    <label for="category_id" class="form-label" style="font-weight:600;">
                        2. Parent Category (Level 2) <span style="color:#ef4444;">*</span>
                    </label>
                    <select id="category_id" name="category_id" class="form-control" required>
                        <option value="">-- Choose Category --</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" data-super="<?= (int)$c['super_category_id'] ?>" <?= $presetCategory === (int)$c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['super_category_name'] . ' → ' . $c['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">The instrument family or clinical equipment group.</small>
                </div>

                <!-- Sub Category Name -->
                <div class="form-group mb-3">
                    <label for="name" class="form-label" style="font-weight:600;">
                        3. Sub Category Name <span style="color:#ef4444;">*</span>
                    </label>
                    <input type="text" id="name" name="name" class="form-control" required
                           placeholder="e.g. Dissecting & Surgical Scissors">
                </div>

                <!-- Slug -->
                <div class="form-group mb-3">
                    <label for="slug" class="form-label" style="font-weight:600;">
                        URL Slug
                    </label>
                    <input type="text" id="slug" name="slug" class="form-control"
                           placeholder="e.g. dissecting-surgical-scissors">
                    <small class="text-muted">Auto-generated if left blank.</small>
                </div>

                <!-- Description -->
                <div class="form-group mb-3">
                    <label for="description" class="form-label" style="font-weight:600;">
                        Description / Specifications
                    </label>
                    <textarea id="description" name="description" class="form-control" rows="3"
                              placeholder="Key specifications, instrument models, or surgical applications..."></textarea>
                </div>

                <div class="row" style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                    <div class="form-group mb-3">
                        <label for="status" class="form-label" style="font-weight:600;">
                            Status
                        </label>
                        <select id="status" name="status" class="form-control">
                            <option value="active" selected>Active (Visible)</option>
                            <option value="inactive">Inactive (Hidden)</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label for="sort_order" class="form-label" style="font-weight:600;">
                            Display Sort Order
                        </label>
                        <input type="number" id="sort_order" name="sort_order" class="form-control" value="1" min="0">
                        <small class="text-muted">Order within parent category.</small>
                    </div>
                </div>

                <div style="margin-top:24px; display:flex; gap:12px; align-items:center;">
                    <button type="submit" class="btn btn-primary">
                        Create Sub Category
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
                    Sub Category Role
                </h4>
                <p style="font-size:0.8125rem; color:var(--color-text-muted); line-height:1.5; margin-bottom:12px;">
                    Sub Categories represent exact product clusters, such as specific scissor types, optical cannula gauges, or generator accessories.
                </p>
                <div style="padding:10px 12px; background:#ffffff; border:1px solid var(--color-border); border-radius:8px; margin-bottom:8px;">
                    <div style="font-weight:700; color:var(--color-primary); font-size:0.8125rem;">Level 1: Super Category</div>
                    <div style="font-size:0.75rem; color:var(--color-text-muted);">Surgical & Operating Room</div>
                </div>
                <div style="padding:10px 12px; background:#ffffff; border:1px solid var(--color-border); border-radius:8px; margin-bottom:8px; margin-left:12px;">
                    <div style="font-weight:700; color:#0284c7; font-size:0.8125rem;">↳ Level 2: Category</div>
                    <div style="font-size:0.75rem; color:var(--color-text-muted);">General Surgery</div>
                </div>
                <div style="padding:10px 12px; background:#ffffff; border:1px solid #d8b4fe; border-radius:8px; margin-left:24px;">
                    <div style="font-weight:700; color:#8b5cf6; font-size:0.8125rem;">↳ Level 3: Sub Category (New)</div>
                    <div style="font-size:0.75rem; color:var(--color-text-muted);">Dissecting & Surgical Scissors</div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const superSelect = document.getElementById('super_cat_filter');
    const catSelect = document.getElementById('category_id');
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');

    // Filter categories when super category changes
    function filterCategories() {
        const selectedSuper = superSelect.value;
        const options = catSelect.querySelectorAll('option');

        options.forEach(opt => {
            if (!opt.value) return; // Keep default prompt
            const superId = opt.getAttribute('data-super');
            if (!selectedSuper || superId === selectedSuper) {
                opt.style.display = '';
            } else {
                opt.style.display = 'none';
                if (opt.selected) {
                    catSelect.value = '';
                }
            }
        });
    }

    if (superSelect) {
        superSelect.addEventListener('change', filterCategories);
        if (superSelect.value) {
            filterCategories();
        }
    }

    if (nameInput && slugInput) {
        nameInput.addEventListener('input', function() {
            if (!slugInput.dataset.touched) {
                slugInput.value = nameInput.value
                    .toLowerCase()
                    .trim()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/[\s_-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }
        });
        slugInput.addEventListener('input', function() {
            slugInput.dataset.touched = "true";
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
