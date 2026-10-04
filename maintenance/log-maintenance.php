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

$allowed_roles = ['admin', 'sterilisation_tech', 'nurse'];
if (!in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    $_SESSION['flash_error'] = 'You do not have permission to log maintenance.';
    header('Location: /surgical_equipment/maintenance/maintenance-schedule.php');
    exit;
}

$csrf_token    = $_SESSION['csrf_token'] ?? '';
$preSelectedId = (int) ($_GET['equipment_id'] ?? 0);

try {
    $pdo = getDbConnection();

    $equipStmt = $pdo->prepare("
        SELECT id, equipment_code, name, status
        FROM equipment_inventory
        WHERE status != 'retired'
        ORDER BY equipment_code ASC
    ");
    $equipStmt->execute();
    $equipmentOptions = $equipStmt->fetchAll();

} catch (PDOException $e) {
    error_log('Log-maintenance page error: ' . $e->getMessage());
    $equipmentOptions = [];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Log Maintenance</h1>
            <p class="page-breadcrumb">
                <a href="/surgical_equipment/dashboard.php">Home</a> /
                <a href="/surgical_equipment/maintenance/maintenance-schedule.php">Maintenance</a> /
                Log Maintenance
            </p>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="card" style="max-width: 700px;">
        <?php if (count($equipmentOptions) === 0): ?>
            <div class="alert alert-warning">No equipment available to log maintenance against.</div>
            <a href="/surgical_equipment/maintenance/maintenance-schedule.php" class="btn btn-secondary">← Back to Maintenance Schedule</a>
        <?php else: ?>
            <form method="post" action="/surgical_equipment/process-action.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="maintenance_create">

                <!-- Equipment Selection -->
                <div class="form-group">
                    <label for="equipment_id">Equipment <span style="color: var(--color-danger);">*</span></label>
                    <select id="equipment_id" name="equipment_id" class="form-control" required>
                        <option value="">— Choose Equipment —</option>
                        <?php foreach ($equipmentOptions as $eq): ?>
                            <option value="<?= (int) $eq['id'] ?>"
                                    <?= ($preSelectedId === (int) $eq['id']) ? 'selected' : '' ?>>
                                [<?= htmlspecialchars($eq['equipment_code'], ENT_QUOTES, 'UTF-8') ?>]
                                <?= htmlspecialchars($eq['name'], ENT_QUOTES, 'UTF-8') ?>
                                (Currently: <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $eq['status'])), ENT_QUOTES, 'UTF-8') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-muted mt-1" style="font-size: var(--font-size-xs);">
                        Logging a record will automatically set the equipment status to "Under Maintenance".
                    </p>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <!-- Type (Mapped to database enums) -->
                    <div class="form-group">
                        <label for="type">Maintenance Type <span style="color: var(--color-danger);">*</span></label>
                        <select id="type" name="type" class="form-control" required>
                            <option value="routine">Routine Inspection</option>
                            <option value="repair">Repair</option>
                            <option value="calibration">Calibration</option>
                            <option value="inspection">Inspection</option>
                        </select>
                    </div>

                    <!-- Scheduled Date -->
                    <div class="form-group">
                        <label for="scheduled_date">Scheduled Date</label>
                        <input type="date" id="scheduled_date" name="scheduled_date" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>

                    <!-- Cost -->
                    <div class="form-group">
                        <label for="cost">Estimated / Actual Cost ($)</label>
                        <input type="number" id="cost" name="cost" class="form-control" step="0.01" min="0" placeholder="0.00">
                    </div>

                    <!-- Vendor Reference -->
                    <div class="form-group">
                        <label for="vendor_reference">Vendor Reference #</label>
                        <input type="text" id="vendor_reference" name="vendor_reference" class="form-control" placeholder="e.g. PO-2026-0451">
                    </div>
                </div>

                <!-- Description -->
                <div class="form-group">
                    <label for="description">Description <span style="color: var(--color-danger);">*</span></label>
                    <textarea id="description" name="description" class="form-control" rows="3"
                              placeholder="Describe the issue, task, or reason for this maintenance record..." required></textarea>
                </div>

                <!-- Notes -->
                <div class="form-group">
                    <label for="notes">Additional Notes</label>
                    <textarea id="notes" name="notes" class="form-control" rows="2"
                              placeholder="Any additional context..."></textarea>
                </div>

                <div class="flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">Save Maintenance Record</button>
                    <a href="/surgical_equipment/maintenance/maintenance-schedule.php" class="btn btn-secondary btn-lg">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
