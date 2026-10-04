<?php

require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_error'] = 'Please sign in.';
    header('Location: /surgical_equipment/auth/login.php');
    exit;
}

$allowed_roles = ['admin', 'nurse', 'sterilisation_tech'];
if (!in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    $_SESSION['flash_error'] = 'You do not have permission to check out equipment.';
    header('Location: /surgical_equipment/equipment/inventory-list.php');
    exit;
}

$csrf_token    = $_SESSION['csrf_token'] ?? '';
$preSelectedId = (int) ($_GET['equipment_id'] ?? 0);

try {
    $pdo = getDbConnection();

    $equipStmt = $pdo->query("
        SELECT id, equipment_code, name, current_location
        FROM equipment_inventory
        WHERE status = 'available'
        ORDER BY equipment_code ASC
    ");
    $equipmentOptions = $equipStmt->fetchAll();

    $preSelectedEquip = null;
    if ($preSelectedId > 0) {
        $selStmt = $pdo->prepare("SELECT * FROM equipment_inventory WHERE id = :id");
        $selStmt->execute([':id' => $preSelectedId]);
        $preSelectedEquip = $selStmt->fetch();
    }

} catch (PDOException $e) {
    error_log('Check-out page error: ' . $e->getMessage());
    $equipmentOptions = [];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Issue Equipment (Check Out)</h1>
            <p class="page-breadcrumb">
                <a href="/surgical_equipment/dashboard.php">Home</a> /
                <a href="/surgical_equipment/equipment/inventory-list.php">Inventory</a> /
                Check Out
            </p>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="card" style="max-width: 700px;">
        <?php if ($preSelectedEquip && $preSelectedEquip['status'] !== 'available'): ?>
            <div class="alert alert-warning">
                Equipment <strong><?= htmlspecialchars($preSelectedEquip['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                is currently <strong><?= htmlspecialchars(str_replace('_', ' ', $preSelectedEquip['status'])) ?></strong> and cannot be checked out.
            </div>
            <a href="/surgical_equipment/equipment/inventory-list.php" class="btn btn-secondary">← Back to Inventory</a>
        <?php elseif (count($equipmentOptions) === 0): ?>
            <div class="alert alert-warning">No equipment is currently available for checkout. All items are in use, sterilising, or under maintenance.</div>
            <a href="/surgical_equipment/equipment/inventory-list.php" class="btn btn-secondary">← Back to Inventory</a>
        <?php else: ?>
            <form method="post" action="/surgical_equipment/process-action.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="tracking_log">
                <input type="hidden" name="track_action" value="check_out">

                <!-- Equipment Dropdown -->
                <div class="form-group">
                    <label for="equipment_id">Equipment <span style="color: var(--color-danger);">*</span></label>
                    <select id="equipment_id" name="equipment_id" class="form-control" required onchange="updateFromLocation()">
                        <option value="">— Choose Equipment —</option>
                        <?php foreach ($equipmentOptions as $eq): ?>
                            <option value="<?= (int) $eq['id'] ?>"
                                    data-location="<?= htmlspecialchars($eq['current_location'], ENT_QUOTES, 'UTF-8') ?>"
                                    <?= ($preSelectedId === (int) $eq['id']) ? 'selected' : '' ?>>
                                [<?= htmlspecialchars($eq['equipment_code'], ENT_QUOTES, 'UTF-8') ?>]
                                <?= htmlspecialchars($eq['name'], ENT_QUOTES, 'UTF-8') ?>
                                (Currently: <?= htmlspecialchars($eq['current_location'], ENT_QUOTES, 'UTF-8') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Locations Grid -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label for="from_location_display">From Location</label>
                        <input type="text" id="from_location_display" class="form-control" readonly disabled value="">
                        <input type="hidden" id="from_location" name="from_location" value="">
                    </div>
                    <div class="form-group">
                        <label for="to_location">To Location (Destination) <span style="color: var(--color-danger);">*</span></label>
                        <input type="text" id="to_location" name="to_location" class="form-control" required
                               placeholder="e.g., OR 1, ICU, Ward B" list="locations-list">
                        <datalist id="locations-list">
                            <option value="OR 1">
                            <option value="OR 2">
                            <option value="OR 3">
                            <option value="OR 4">
                            <option value="ICU Bay A">
                            <option value="Emergency Room">
                            <option value="Ward A">
                            <option value="Main Store">
                        </datalist>
                    </div>
                </div>

                <!-- Notes -->
                <div class="form-group">
                    <label for="notes">Checkout Notes</label>
                    <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Specify procedure, doctor name, or checklist info..."></textarea>
                </div>

                <div class="flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">Complete Checkout</button>
                    <a href="/surgical_equipment/equipment/inventory-list.php" class="btn btn-secondary btn-lg">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</main>

<script>
function updateFromLocation() {
    var select = document.getElementById('equipment_id');
    var selectedOption = select.options[select.selectedIndex];
    var location = selectedOption.getAttribute('data-location') || '';

    document.getElementById('from_location_display').value = location ? location : '—';
    document.getElementById('from_location').value = location;
}

// Run onload to pre-populate from pre-selected item
document.addEventListener('DOMContentLoaded', function() {
    updateFromLocation();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
