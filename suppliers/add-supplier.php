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
    header('Location: /surgical_equipment/suppliers/suppliers.php');
    exit;
}

$editId = (int) ($_GET['edit'] ?? 0);
$supplier = null;

try {
    $pdo = getDbConnection();
    if ($editId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM suppliers WHERE id = :id');
        $stmt->execute([':id' => $editId]);
        $supplier = $stmt->fetch();
        if (!$supplier) {
            $_SESSION['flash_error'] = 'Supplier not found.';
            header('Location: /surgical_equipment/suppliers/suppliers.php');
            exit;
        }
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
            <h1 class="page-title"><?= $supplier ? 'Edit Supplier' : 'Add Supplier' ?></h1>
            <p class="page-breadcrumb">
                <a href="/surgical_equipment/dashboard.php">Home</a> /
                <a href="/surgical_equipment/suppliers/suppliers.php">Suppliers</a> /
                <?= $supplier ? 'Edit' : 'Add' ?>
            </p>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="card" style="max-width:700px;">
        <form method="post" action="/surgical_equipment/process-action.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="<?= $supplier ? 'supplier_update' : 'supplier_create' ?>">
            <?php if ($supplier): ?>
                <input type="hidden" name="id" value="<?= (int) $supplier['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="name">Supplier Name <span style="color:var(--color-danger)">*</span></label>
                <input type="text" id="name" name="name" class="form-control" required placeholder="e.g. MedTech Supplies Corp."
                       value="<?= htmlspecialchars($supplier['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="contact_person">Contact Person</label>
                    <input type="text" id="contact_person" name="contact_person" class="form-control" placeholder="e.g. Jane Smith"
                           value="<?= htmlspecialchars($supplier['contact_person'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" class="form-control" placeholder="e.g. +1-555-0199"
                           value="<?= htmlspecialchars($supplier['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="e.g. contact@supplier.com"
                       value="<?= htmlspecialchars($supplier['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label for="address">Postal/Street Address</label>
                <textarea id="address" name="address" class="form-control" rows="3" placeholder="e.g. 100 Main St, Suite 2A..."><?= htmlspecialchars($supplier['address'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div class="flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary btn-lg"><?= $supplier ? 'Save Changes' : 'Add Supplier' ?></button>
                <a href="/surgical_equipment/suppliers/suppliers.php" class="btn btn-secondary btn-lg">Cancel</a>
            </div>
        </form>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
