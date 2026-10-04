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
    $_SESSION['flash_error'] = 'You do not have permission to check in equipment.';
    header('Location: /surgical_equipment/equipment/inventory-list.php');
    exit;
}

$csrf_token    = $_SESSION['csrf_token'] ?? '';
$preSelectedId = (int) ($_GET['equipment_id'] ?? 0);

try {
    $pdo = getDbConnection();

    $equipStmt = $pdo->query("
        SELECT id, equipment_code, name, current_location, status
        FROM equipment_inventory
        WHERE status IN ('in_use', 'under_maintenance', 'sterilising')
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
    error_log('Check-in page error: ' . $e->getMessage());
    $equipmentOptions = [];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Return Equipment (Check In)</h1>
            <p class="page-breadcrumb">
                <a href="/surgical_equipment/dashboard.php">Home</a> /
                <a href="/surgical_equipment/equipment/inventory-list.php">Inventory</a> /
                Check In
            </p>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="card" style="max-width: 700px;">
        <?php if ($preSelectedEquip && $preSelectedEquip['status'] === 'available'): ?>
            <div class="alert alert-warning">
                Equipment <strong><?= htmlspecialchars($preSelectedEquip['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                is already marked as <strong>Available</strong> and does not need checkout check-in.
            </div>
            <a href="/surgical_equipment/equipment/inventory-list.php" class="btn btn-secondary">Back to Inventory</a>
        <?php elseif (count($equipmentOptions) === 0): ?>
            <div class="alert alert-warning">No equipment is currently checked out or locked in processing. All items are already available in inventory.</div>
            <a href="/surgical_equipment/equipment/inventory-list.php" class="btn btn-secondary">Back to Inventory</a>
        <?php else: ?>
            <form method="post" action="/surgical_equipment/process-action.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="tracking_log">
                <input type="hidden" name="track_action" value="check_in">

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
                                (At: <?= htmlspecialchars($eq['current_location'], ENT_QUOTES, 'UTF-8') ?> — Status: <?= ucfirst(str_replace('_',' ',$eq['status'])) ?>)
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
                        <label for="to_location">To Location (Return Destination) <span style="color: var(--color-danger);">*</span></label>
                        <input type="text" id="to_location" name="to_location" class="form-control" required
                               placeholder="e.g., Main Store, CSSD, Workshop" list="locations-list">
                        <datalist id="locations-list">
                            <option value="Main Store">
                            <option value="CSSD Storage Bay A">
                            <option value="CSSD Storage Bay B">
                            <option value="Biomedical Workshop">
                            <option value="OR 3">
                        </datalist>
                    </div>
                </div>

                <!-- Notes -->
                <div class="form-group">
                    <label for="notes">Check-in Notes</label>
                    <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Notes on instrument condition, return check results, cleaning needed..."></textarea>
                </div>

                <div class="flex gap-2 mt-4">
                    <button type="submit" class="btn btn-success btn-lg">Complete Return</button>
                    <a href="/surgical_equipment/equipment/inventory-list.php" class="btn btn-secondary btn-lg">Cancel</a>
                </div>
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
