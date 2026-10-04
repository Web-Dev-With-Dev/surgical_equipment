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
    header('Location: /surgical_equipment/equipment/inventory-list.php');
    exit;
}

$editId = (int) ($_GET['edit'] ?? 0);
$item   = null;

try {
    $pdo = getDbConnection();
    if ($editId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM equipment_inventory WHERE id = :id');
        $stmt->execute([':id' => $editId]);
        $item = $stmt->fetch();
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title"><?= $item ? 'Edit Equipment' : 'Add Equipment' ?></h1>
            <p class="page-breadcrumb">
                <a href="/surgical_equipment/dashboard.php">Home</a> /
                <a href="/surgical_equipment/equipment/inventory-list.php">Inventory</a> /
                <?= $item ? 'Edit' : 'Add' ?>
            </p>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="card" style="max-width:780px;">
        <form method="post" action="/surgical_equipment/process-action.php">
            <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action"       value="<?= $item ? 'equipment_update' : 'equipment_create' ?>">
            <?php if ($item): ?>
                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
            <?php endif; ?>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="equipment_code">Equipment Code <span style="color:var(--color-danger)">*</span></label>
                    <input type="text" id="equipment_code" name="equipment_code" class="form-control" required
                           value="<?= htmlspecialchars($item['equipment_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                    <label for="name">Name <span style="color:var(--color-danger)">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" required
                           value="<?= htmlspecialchars($item['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                    <label for="category">Category <span style="color:var(--color-danger)">*</span></label>
                    <input type="text" id="category" name="category" class="form-control" required
                           value="<?= htmlspecialchars($item['category'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="form-control">
                        <?php $curStatus = $item['status'] ?? 'available'; ?>
                        <?php foreach (['available','in_use','under_maintenance','sterilising','retired'] as $s): ?>
                            <option value="<?= $s ?>" <?= $curStatus===$s ? 'selected':'' ?>>
                                <?= ucfirst(str_replace('_',' ',$s)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="serial_number">Serial Number</label>
                    <input type="text" id="serial_number" name="serial_number" class="form-control"
                           value="<?= htmlspecialchars($item['serial_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                    <label for="current_location">Current Location</label>
                    <input type="text" id="current_location" name="current_location" class="form-control"
                           value="<?= htmlspecialchars($item['current_location'] ?? 'Main Store', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                    <label for="purchase_date">Purchase Date</label>
                    <input type="date" id="purchase_date" name="purchase_date" class="form-control"
                           value="<?= htmlspecialchars($item['purchase_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                    <label for="warranty_expiry">Warranty Expiry</label>
                    <input type="date" id="warranty_expiry" name="warranty_expiry" class="form-control"
                           value="<?= htmlspecialchars($item['warranty_expiry'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" class="form-control" rows="3"><?= htmlspecialchars($item['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div class="flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary btn-lg"><?= $item ? 'Save Changes' : 'Add Equipment' ?></button>
                <a href="/surgical_equipment/equipment/inventory-list.php" class="btn btn-secondary btn-lg">Cancel</a>
            </div>
        </form>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
