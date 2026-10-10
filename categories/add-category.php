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
    $_SESSION['flash_error'] = 'Admin access required to add categories.';
    header('Location: /surgical_equipment/categories/categories.php');
    exit;
}

$csrf_token          = $_SESSION['csrf_token'] ?? '';
$presetSuperCategory = (int) ($_GET['super_category_id'] ?? 0);

try {
    $pdo = getDbConnection();
    $superStmt = $pdo->query("SELECT id, name FROM super_categories WHERE status = 'active' ORDER BY sort_order ASC, name ASC");
    $superCategories = $superStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($superCategories)) {
        $_SESSION['flash_error'] = 'Please create at least one Super Category first before adding categories.';
        header('Location: /surgical_equipment/categories/add-super-category.php');
        exit;
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    $superCategories = [];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Add Category (Level 2)</h1>
            <p class="page-breadcrumb">
                <a href="/surgical_equipment/dashboard.php">Home</a> /
                <a href="/surgical_equipment/categories/super-categories.php">Super Categories</a> /
                <a href="/surgical_equipment/categories/categories.php">Categories</a> /
                <span>Add Category</span>
            </p>
        </div>
        <a href="/surgical_equipment/categories/categories.php" class="btn btn-secondary">
            ← Back to Categories
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
                <input type="hidden" name="action" value="category_create">

                <!-- Select Parent Super Category -->
                <div class="form-group mb-3">
                    <label for="super_category_id" class="form-label" style="font-weight:600;">
                        Parent Super Category <span style="color:#ef4444;">*</span>
                    </label>
                    <select id="super_category_id" name="super_category_id" class="form-control" required>
                        <option value="">-- Choose Super Category (Level 1) --</option>
                        <?php foreach ($superCategories as $sc): ?>
                            <option value="<?= (int)$sc['id'] ?>" <?= $presetSuperCategory === (int)$sc['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sc['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">The top-level medical division this category belongs under.</small>
                </div>

                <!-- Category Name -->
                <div class="form-group mb-3">
                    <label for="name" class="form-label" style="font-weight:600;">
                        Category Name <span style="color:#ef4444;">*</span>
                    </label>
                    <input type="text" id="name" name="name" class="form-control" required
                           placeholder="e.g. Laparoscopy & Minimally Invasive">
                </div>

                <!-- Slug -->
                <div class="form-group mb-3">
                    <label for="slug" class="form-label" style="font-weight:600;">
                        URL Slug
                    </label>
                    <input type="text" id="slug" name="slug" class="form-control"
                           placeholder="e.g. laparoscopy-minimally-invasive">
                    <small class="text-muted">Auto-generated if left blank.</small>
                </div>

                <!-- Description -->
                <div class="form-group mb-3">
                    <label for="description" class="form-label" style="font-weight:600;">
                        Description / Clinical Scope
                    </label>
                    <textarea id="description" name="description" class="form-control" rows="3"
                              placeholder="Brief description of the equipment types and clinical applications..."></textarea>
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
                        <small class="text-muted">Order within parent super category.</small>
                    </div>
                </div>

                <div style="margin-top:24px; display:flex; gap:12px; align-items:center;">
                    <button type="submit" class="btn btn-primary">
                        Create Category
                    </button>
                    <a href="/surgical_equipment/categories/categories.php" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        <div>
            <div class="card" style="background:#FAFBF8; border-color:var(--color-border);">
                <h4 style="margin:0 0 12px 0; font-size:0.9375rem; font-weight:700; color:var(--color-text-heading);">
                    Category Guidelines
                </h4>
                <p style="font-size:0.8125rem; color:var(--color-text-muted); line-height:1.5; margin-bottom:12px;">
                    Categories group related surgical instrument families and medical systems together.
                </p>
                <div style="padding:10px 12px; background:#ffffff; border:1px solid var(--color-border); border-radius:8px; margin-bottom:8px;">
                    <div style="font-weight:700; color:var(--color-primary); font-size:0.8125rem;">Super Category:</div>
                    <div style="font-size:0.75rem; color:var(--color-text-muted);">Surgical & Operating Room (OR)</div>
                </div>
                <div style="padding:10px 12px; background:#ffffff; border:1px solid #93c5fd; border-radius:8px; margin-bottom:8px; margin-left:12px;">
                    <div style="font-weight:700; color:#0284c7; font-size:0.8125rem;">↳ Category (New):</div>
                    <div style="font-size:0.75rem; color:var(--color-text-muted);">Electrosurgery & Energy Devices</div>
                </div>
                <div style="padding:10px 12px; background:#ffffff; border:1px solid var(--color-border); border-radius:8px; margin-left:24px;">
                    <div style="font-weight:700; color:#8b5cf6; font-size:0.8125rem;">↳ Sub Categories:</div>
                    <div style="font-size:0.75rem; color:var(--color-text-muted);">Bipolar Forceps, Harmonic Blades</div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');

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
