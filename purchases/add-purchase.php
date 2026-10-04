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
    $_SESSION['flash_error'] = 'Admin access required.';
    header('Location: /surgical_equipment/purchases/purchases.php');
    exit;
}

$editId = (int) ($_GET['edit'] ?? 0);
$purchase = null;

try {
    $pdo = getDbConnection();

    $suppliers = $pdo->query("SELECT id, name FROM suppliers ORDER BY name ASC")->fetchAll();

    if ($editId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM purchases WHERE id = :id');
        $stmt->bindValue(':id', $editId, PDO::PARAM_INT);
        $stmt->execute();
        $purchase = $stmt->fetch();
        if (!$purchase) {
            $_SESSION['flash_error'] = 'Purchase order record not found.';
            header('Location: /surgical_equipment/purchases/purchases.php');
            exit;
        }
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    $suppliers = [];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title"><?= $editId > 0 ? 'Edit Purchase Order' : 'Record Purchase Order' ?></h1>
            <p class="page-breadcrumb">
                <a href="/surgical_equipment/dashboard.php">Home</a> /
                <a href="/surgical_equipment/purchases/purchases.php">Purchases</a> /
                <?= $editId > 0 ? 'Edit' : 'Record' ?>
            </p>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="card" style="max-width:850px;">
        <form method="post" action="/surgical_equipment/process-action.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="<?= $editId > 0 ? 'purchase_update' : 'purchase_create' ?>">
            <?php if ($editId > 0): ?>
                <input type="hidden" name="id" value="<?= $editId ?>">
            <?php endif; ?>

            <h3 style="margin-bottom:16px; border-bottom:1px solid var(--color-border); padding-bottom:8px; color:var(--color-primary);"><?= $editId > 0 ? 'Update Details' : '1. Purchase Details' ?></h3>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="purchase_order_number">PO Number <span style="color:var(--color-danger)">*</span></label>
                    <input type="text" id="purchase_order_number" name="purchase_order_number" class="form-control" required
                           placeholder="e.g. PO-2026-101"
                           value="<?= $editId > 0 ? htmlspecialchars($purchase['purchase_order_number'] ?? '', ENT_QUOTES, 'UTF-8') : 'PO-' . date('Y-md') . '-' . rand(10,99) ?>">
                </div>

                <div class="form-group">
                    <label for="supplier_id">Supplier <span style="color:var(--color-danger)">*</span></label>
                    <select id="supplier_id" name="supplier_id" class="form-control" required>
                        <option value="">— Choose Supplier —</option>
                        <?php foreach ($suppliers as $sup): ?>
                            <option value="<?= (int) $sup['id'] ?>" <?= ($purchase && (int)$purchase['supplier_id'] === (int)$sup['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sup['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="purchase_date">Purchase Date <span style="color:var(--color-danger)">*</span></label>
                    <input type="date" id="purchase_date" name="purchase_date" class="form-control" required
                           value="<?= $purchase ? htmlspecialchars($purchase['purchase_date'] ?? '', ENT_QUOTES, 'UTF-8') : date('Y-m-d') ?>">
                </div>

                <div class="form-group">
                    <label for="total_cost">Total Cost ($) <span style="color:var(--color-danger)">*</span></label>
                    <input type="number" id="total_cost" name="total_cost" class="form-control" step="0.01" required placeholder="0.00"
                           value="<?= $purchase ? htmlspecialchars($purchase['total_cost'] ?? '', ENT_QUOTES, 'UTF-8') : '' ?>">
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="completed" <?= ($purchase && $purchase['status'] === 'completed') ? 'selected' : '' ?>>Completed</option>
                        <option value="pending" <?= ($purchase && $purchase['status'] === 'pending') ? 'selected' : '' ?>>Pending</option>
                        <option value="cancelled" <?= ($purchase && $purchase['status'] === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="notes">Purchase Notes</label>
                <textarea id="notes" name="notes" class="form-control" rows="2" placeholder="Acquisition reasons, shipment references, billing terms..."><?= $purchase ? htmlspecialchars($purchase['notes'] ?? '', ENT_QUOTES, 'UTF-8') : '' ?></textarea>
            </div>

            <!-- Auto Create Equipment (Only available for new purchase orders) -->
            <?php if ($editId === 0): ?>
                <div class="card" style="background:var(--color-primary-light); border-color:#bfdbfe; margin-top:24px; padding:20px;">
                    <div class="flex gap-2" style="align-items:center; margin-bottom:12px;">
                        <input type="checkbox" id="auto_create_items" name="auto_create_items" value="1" onchange="toggleAutoCreateSection()" style="width:18px; height:18px;">
                        <label for="auto_create_items" style="font-weight:700; margin-bottom:0; color:var(--color-primary); cursor:pointer;">
                            Automatically generate and link new Equipment Inventory items?
                        </label>
                    </div>

                    <div id="auto_create_section" style="display:none; border-top:1px solid #bfdbfe; padding-top:16px; margin-top:8px;">
                        <p class="text-muted" style="font-size:var(--font-size-xs); margin-bottom:16px;">
                            Check this box to auto-generate multiple identical equipment instances in the database. Their warranty dates will be calculated from the purchase date.
                        </p>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                            <div class="form-group">
                                <label for="equip_name">Equipment Name</label>
                                <input type="text" id="equip_name" name="equip_name" class="form-control" placeholder="e.g. Scalpel Handle #3">
                            </div>
                            <div class="form-group">
                                <label for="equip_category">Category</label>
                                <input type="text" id="equip_category" name="equip_category" class="form-control" placeholder="e.g. Scalpels" list="categories-list">
                                <datalist id="categories-list">
                                    <option value="Surgical Forceps">
                                    <option value="Laparoscopy">
                                    <option value="Electrosurgery">
                                    <option value="Orthopedics">
                                    <option value="Retractors">
                                </datalist>
                            </div>
                            <div class="form-group">
                                <label for="code_prefix">Code Prefix</label>
                                <input type="text" id="code_prefix" name="code_prefix" class="form-control" placeholder="e.g. EQ-20" value="EQ-">
                            </div>
                            <div class="form-group">
                                <label for="start_num">Starting Number</label>
                                <input type="number" id="start_num" name="start_num" class="form-control" placeholder="e.g. 101" value="2001">
                            </div>
                            <div class="form-group">
                                <label for="quantity">Quantity to Generate</label>
                                <input type="number" id="quantity" name="quantity" class="form-control" min="1" max="100" placeholder="e.g. 5" value="1">
                            </div>
                            <div class="form-group">
                                <label for="warranty_months">Warranty Duration (Months)</label>
                                <input type="number" id="warranty_months" name="warranty_months" class="form-control" placeholder="e.g. 12" value="12">
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary btn-lg"><?= $editId > 0 ? 'Update Purchase' : 'Record & Save' ?></button>
                <a href="/surgical_equipment/purchases/purchases.php" class="btn btn-secondary btn-lg">Cancel</a>
            </div>
        </form>
    </div>
</main>

<script>
function toggleAutoCreateSection() {
    var checkbox = document.getElementById('auto_create_items');
    var section  = document.getElementById('auto_create_section');
    if (!checkbox || !section) return;
    if (checkbox.checked) {
        section.style.display = 'block';
        document.getElementById('equip_name').setAttribute('required', 'true');
        document.getElementById('equip_category').setAttribute('required', 'true');
        document.getElementById('code_prefix').setAttribute('required', 'true');
        document.getElementById('start_num').setAttribute('required', 'true');
        document.getElementById('quantity').setAttribute('required', 'true');
    } else {
        section.style.display = 'none';
        document.getElementById('equip_name').removeAttribute('required');
        document.getElementById('equip_category').removeAttribute('required');
        document.getElementById('code_prefix').removeAttribute('required');
        document.getElementById('start_num').removeAttribute('required');
        document.getElementById('quantity').removeAttribute('required');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
