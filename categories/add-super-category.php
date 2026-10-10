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
    $_SESSION['flash_error'] = 'Admin access required to add or edit super categories.';
    header('Location: /surgical_equipment/categories/super-categories.php');
    exit;
}

$csrf_token = $_SESSION['csrf_token'] ?? '';
$editId     = (int) ($_GET['edit'] ?? 0);
$item       = null;

if ($editId > 0) {
    try {
        $pdo  = getDbConnection();
        $stmt = $pdo->prepare('SELECT * FROM super_categories WHERE id = :id');
        $stmt->execute([':id' => $editId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            $_SESSION['flash_error'] = 'Super Category not found.';
            header('Location: /surgical_equipment/categories/super-categories.php');
            exit;
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $_SESSION['flash_error'] = 'Database error fetching super category.';
        header('Location: /surgical_equipment/categories/super-categories.php');
        exit;
    }
}

$isEdit = !empty($item);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title"><?= $isEdit ? 'Edit Super Category' : 'Add Super Category' ?></h1>
            <p class="page-breadcrumb">
                <a href="/surgical_equipment/dashboard.php">Home</a> /
                <a href="/surgical_equipment/categories/super-categories.php">Super Categories</a> /
                <?= $isEdit ? 'Edit (' . htmlspecialchars($item['name']) . ')' : 'Add New' ?>
            </p>
        </div>
        <a href="/surgical_equipment/categories/super-categories.php" class="btn btn-secondary">
            ← Back to Super Categories
        </a>
    </div>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns: 1fr 340px; gap:24px; align-items:start;">
        <!-- Form card -->
        <div class="card">
            <form method="post" action="/surgical_equipment/process-action.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="<?= $isEdit ? 'super_category_update' : 'super_category_create' ?>">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                <?php endif; ?>

                <div class="form-group mb-3">
                    <label for="name" class="form-label" style="font-weight:600;">
                        Super Category Name <span style="color:#ef4444;">*</span>
                    </label>
                    <input type="text" id="name" name="name" class="form-control" required
                           placeholder="e.g. Surgical & Operating Room (OR) Equipment"
                           value="<?= htmlspecialchars($item['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <small class="text-muted">Top level division under which medical equipment categories are organized.</small>
                </div>

                <div class="form-group mb-3">
                    <label for="slug" class="form-label" style="font-weight:600;">
                        URL Slug
                    </label>
                    <input type="text" id="slug" name="slug" class="form-control"
                           placeholder="e.g. surgical-operating-room-equipment"
                           value="<?= htmlspecialchars($item['slug'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <small class="text-muted">Unique URL identifier. Leave blank to generate automatically from name.</small>
                </div>

                <div class="form-group mb-3">
                    <label for="description" class="form-label" style="font-weight:600;">
                        Description / Clinical Scope
                    </label>
                    <textarea id="description" name="description" class="form-control" rows="4"
                              placeholder="Brief description of the departments, medical branches, or equipment covered by this super category..."><?= htmlspecialchars($item['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="row" style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                    <div class="form-group mb-3">
                        <label for="status" class="form-label" style="font-weight:600;">
                            Status
                        </label>
                        <select id="status" name="status" class="form-control">
                            <option value="active" <?= ($item['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active (Visible)</option>
                            <option value="inactive" <?= ($item['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive (Hidden)</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label for="sort_order" class="form-label" style="font-weight:600;">
                            Display Sort Order
                        </label>
                        <input type="number" id="sort_order" name="sort_order" class="form-control"
                               value="<?= (int)($item['sort_order'] ?? 1) ?>" min="0">
                        <small class="text-muted">Lower numbers appear first.</small>
                    </div>
                </div>

                <div style="margin-top:24px; display:flex; gap:12px; align-items:center;">
                    <button type="submit" class="btn btn-primary">
                        <?= $isEdit ? 'Save Changes' : 'Create Super Category' ?>
                    </button>
                    <a href="/surgical_equipment/categories/super-categories.php" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        <!-- Info & Live Preview card -->
        <div>
            <div class="card" style="background:#FAFBF8; border-color:var(--color-border);">
                <h4 style="margin:0 0 12px 0; font-size:0.9375rem; font-weight:700; color:var(--color-text-heading);">
                    Hierarchy Architecture
                </h4>
                <div style="font-size:0.8125rem; color:var(--color-text-muted); line-height:1.5; margin-bottom:16px;">
                    In APEX Catalogue, the 3-level medical taxonomy operates as follows:
                </div>
                
                <div style="padding:10px 12px; background:#ffffff; border:1px solid var(--color-border); border-radius:8px; margin-bottom:8px;">
                    <div style="font-weight:700; color:var(--color-primary); font-size:0.8125rem;">Level 1: Super Category (Current)</div>
                    <div style="font-size:0.75rem; color:var(--color-text-muted);">e.g. Surgical & OR Equipment</div>
                </div>

                <div style="padding:10px 12px; background:#ffffff; border:1px solid var(--color-border); border-radius:8px; margin-bottom:8px; margin-left:12px;">
                    <div style="font-weight:700; color:#0284c7; font-size:0.8125rem;">Level 2: Category</div>
                    <div style="font-size:0.75rem; color:var(--color-text-muted);">e.g. General Surgery Instruments</div>
                </div>

                <div style="padding:10px 12px; background:#ffffff; border:1px solid var(--color-border); border-radius:8px; margin-left:24px;">
                    <div style="font-weight:700; color:#8b5cf6; font-size:0.8125rem;">Level 3: Sub Category</div>
                    <div style="font-size:0.75rem; color:var(--color-text-muted);">e.g. Dissecting & Surgical Scissors</div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');

    if (nameInput && slugInput && !slugInput.value) {
        nameInput.addEventListener('input', function() {
            slugInput.value = nameInput.value
                .toLowerCase()
                .trim()
                .replace(/[^\w\s-]/g, '')
                .replace(/[\s_-]+/g, '-')
                .replace(/^-+|-+$/g, '');
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
