<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/product-helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /surgical_equipment/auth/login.php');
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    $_SESSION['flash_error'] = 'Admin access required.';
    header('Location: /surgical_equipment/equipment/inventory-list.php');
    exit;
}

$editId = (int) ($_GET['edit'] ?? 0);
$item   = null;
$existingCategories = [];

try {
    $pdo = getDbConnection();
    if ($editId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM equipment_inventory WHERE id = :id');
        $stmt->execute([':id' => $editId]);
        $item = $stmt->fetch();
    }

    $catStmt = $pdo->query("SELECT DISTINCT category FROM equipment_inventory WHERE category != '' ORDER BY category ASC");
    $existingCategories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

    // Also fetch categories and subcategories from the hierarchy tables
    $dbCatNames = $pdo->query("SELECT name FROM categories WHERE status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
    $dbSubCatNames = $pdo->query("SELECT name FROM sub_categories WHERE status = 'active'")->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $dbCatNames = [];
    $dbSubCatNames = [];
}

$standardCategories = [
    'Surgical Instruments',
    'OT Equipment',
    'Hospital Furniture',
    'ICU Equipment',
    'Laparoscopy Instruments',
    'Orthopedic Instruments',
    'Anaesthesia Equipment',
    'Autoclave & Sterilizers',
    'Suction Machines',
    'Surgical Disposables',
    'General Surgery',
    'Electrosurgery',
    'Neurosurgery',
    'Retractors'
];

$allCategoryOptions = array_values(array_unique(array_merge($existingCategories, $standardCategories, $dbCatNames, $dbSubCatNames)));
sort($allCategoryOptions);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title"><?= $item ? 'Edit Product / Equipment' : 'Add New Product / Equipment' ?></h1>
            <p class="page-breadcrumb">
                <a href="/surgical_equipment/dashboard.php">Home</a> /
                <a href="/surgical_equipment/equipment/inventory-list.php">Inventory</a> /
                <?= $item ? 'Edit' : 'Add' ?>
            </p>
        </div>
        <?php if ($item): ?>
            <div class="flex gap-2">
                <a href="/surgical_equipment/product-details.php?id=<?= (int)$item['id'] ?>" target="_blank" class="btn btn-secondary">
                    <span>View in Public Store ↗</span>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="card" style="max-width:820px;">
        <?php 
        $currentImg = get_product_image_url($item['category'] ?? '', $item['name'] ?? '', $item['equipment_code'] ?? '', $item['notes'] ?? '');
        ?>
        <div style="display:flex; align-items:center; gap:16px; margin-bottom:20px; padding-bottom:16px; border-bottom:1px solid var(--color-border);">
            <div style="width:72px; height:72px; border-radius:var(--radius-md); overflow:hidden; border:1px solid var(--color-border); background:#FAFBF8; flex-shrink:0; display:flex; align-items:center; justify-content:center; padding:4px;">
                <img id="productImagePreview" src="<?= htmlspecialchars($currentImg) ?>" alt="Product Preview" style="max-width:100%; max-height:100%; width:auto; height:auto; object-fit:contain;">
            </div>
            <div>
                <h4 style="margin:0 0 4px 0; font-size:1rem; font-weight:700; color:var(--color-text-body);">Catalogue Photography Integration</h4>
                <p style="margin:0; font-size:0.8125rem; color:var(--color-text-muted);">
                    Apex dynamically associates high-resolution clinical photographs matching the category and instrument name across the public store.
                </p>
            </div>
        </div>

        <form method="post" action="/surgical_equipment/process-action.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action"       value="<?= $item ? 'equipment_update' : 'equipment_create' ?>">
            <?php if ($item): ?>
                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
            <?php endif; ?>

            <!-- Equipment Image Upload Field -->
            <div class="form-group mb-4" style="background:#FAFBF8; border:1px dashed var(--color-border); padding:20px; border-radius:var(--radius-md);">
                <label for="product_image" style="font-weight:700; display:flex; align-items:center; gap:6px; margin-bottom:8px; font-size:0.9375rem; color:var(--color-text-body);">
                    <span>Product Photograph</span>
                    <span style="color:var(--color-danger)">*</span>
                    <?php if ($item): ?>
                        <span style="font-size:0.75rem; color:var(--color-text-muted); font-weight:normal;">(Leave empty to keep existing photograph)</span>
                    <?php endif; ?>
                </label>
                <div style="display:flex; gap:16px; align-items:center; flex-wrap:wrap;">
                    <input type="file" id="product_image" name="product_image" class="form-control"
                           accept="image/png, image/jpeg, image/jpg, image/webp"
                           <?= !$item ? 'required' : '' ?>
                           onchange="previewUploadedImage(this);"
                           style="flex:1; min-width:220px; background:#ffffff;">
                    <div style="font-size:0.8125rem; color:var(--color-text-muted);">
                        Supported: JPG, JPEG, PNG, WEBP (Max 10MB)
                    </div>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="equipment_code">Equipment Code / SKU <span style="color:var(--color-danger)">*</span></label>
                    <input type="text" id="equipment_code" name="equipment_code" class="form-control" required
                           placeholder="e.g. EQ-SI-105"
                           value="<?= htmlspecialchars($item['equipment_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                    <label for="name">Product Name <span style="color:var(--color-danger)">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" required
                           placeholder="e.g. Micro Vascular Needle Holder"
                           value="<?= htmlspecialchars($item['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                    <label for="category">Category <span style="color:var(--color-danger)">*</span></label>
                    <input type="text" id="category" name="category" list="categoryDatalist" class="form-control" required
                           placeholder="Select or type category..."
                           value="<?= htmlspecialchars($item['category'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <datalist id="categoryDatalist">
                        <?php foreach ($allCategoryOptions as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="form-group">
                    <label for="status">Inventory Status <span style="color:var(--color-danger)">*</span></label>
                    <select id="status" name="status" class="form-control" required>
                        <?php $curStatus = $item['status'] ?? 'available'; ?>
                        <?php foreach (['available','in_use','under_maintenance','sterilising','retired'] as $s): ?>
                            <option value="<?= $s ?>" <?= $curStatus===$s ? 'selected':'' ?>>
                                <?= ucfirst(str_replace('_',' ',$s)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="serial_number">Model / Dimension / Serial No. <span style="color:var(--color-danger)">*</span></label>
                    <input type="text" id="serial_number" name="serial_number" class="form-control" required
                           placeholder="e.g. 7 Inch (180mm) / Model Pro-900"
                           value="<?= htmlspecialchars($item['serial_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                    <label for="current_location">Hospital Location <span style="color:var(--color-danger)">*</span></label>
                    <input type="text" id="current_location" name="current_location" class="form-control" required
                           placeholder="e.g. OT Store A / ICU Bay 2"
                           value="<?= htmlspecialchars($item['current_location'] ?? 'Main Store', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                    <label for="purchase_date">Procurement / Launch Date <span style="color:var(--color-danger)">*</span></label>
                    <input type="date" id="purchase_date" name="purchase_date" class="form-control" required
                           value="<?= htmlspecialchars($item['purchase_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                    <label for="warranty_expiry">Warranty Expiry <span style="color:var(--color-danger)">*</span></label>
                    <input type="date" id="warranty_expiry" name="warranty_expiry" class="form-control" required
                           value="<?= htmlspecialchars($item['warranty_expiry'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="notes">Description &amp; Specifications (Syncs to Public Catalogue) <span style="color:var(--color-danger)">*</span></label>
                <textarea id="notes" name="notes" class="form-control" rows="4" required placeholder="Enter clinical description, material grade, and specifications (e.g. Material: German Stainless Steel; Brand: Apex OEM; Specs: Autoclavable, CE Approved)"><?= htmlspecialchars($item['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                <small style="color:var(--color-text-muted); font-size:12px; margin-top:4px; display:block;">
                    Tip: Format lines with <code>Material: ...</code>, <code>Brand: ...</code>, and <code>Specs: item1, item2</code> to display rich highlights on public product cards.
                </small>
            </div>

            <div class="flex gap-2 mt-4" style="align-items:center;">
                <button type="submit" class="btn btn-primary btn-lg"><?= $item ? 'Save & Sync Product' : 'Add & Publish Product' ?></button>
                <a href="/surgical_equipment/equipment/inventory-list.php" class="btn btn-secondary btn-lg">Cancel</a>
                <?php if ($item): ?>
                    <button type="button" class="btn btn-danger btn-lg" style="margin-left:auto;" onclick="if(confirm('Are you sure you want to permanently delete this product?')) { document.getElementById('deleteForm').submit(); }">
                        Delete Product
                    </button>
                <?php endif; ?>
            </div>
        </form>
        <?php if ($item): ?>
            <form id="deleteForm" method="post" action="/surgical_equipment/process-action.php" style="display:none;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="equipment_delete">
                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
            </form>
        <?php endif; ?>
    </div>

    <script>
    function previewUploadedImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('productImagePreview');
                if (preview) {
                    preview.src = e.target.result;
                }
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
    </script>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
