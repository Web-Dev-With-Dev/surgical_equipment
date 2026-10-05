<?php
ob_start();

require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /surgical_equipment/auth/login.php');
    echo '<script>window.location.href="/surgical_equipment/auth/login.php";</script>';
    exit;
}

$id   = (int) ($_GET['id'] ?? 0);
$role = $_SESSION['role'] ?? '';

if ($id <= 0) {
    $_SESSION['flash_error'] = 'Please select a valid equipment item to view details.';
    header('Location: /surgical_equipment/equipment/inventory-list.php');
    echo '<script>window.location.href="/surgical_equipment/equipment/inventory-list.php";</script>';
    exit;
}

$item = null;
$trackLogs = [];
$maintRecords = [];

try {
    $pdo  = getDbConnection();
    $stmt = $pdo->prepare('SELECT * FROM equipment_inventory WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $item = $stmt->fetch();

    if (!$item) {
        $_SESSION['flash_error'] = 'Equipment record not found.';
        header('Location: /surgical_equipment/equipment/inventory-list.php');
        echo '<script>window.location.href="/surgical_equipment/equipment/inventory-list.php";</script>';
        exit;
    }

    $trackStmt = $pdo->prepare("
        SELECT tl.*, u.full_name AS user_name
        FROM tracking_logs tl
        LEFT JOIN users u ON tl.user_id = u.id
        WHERE tl.equipment_id = :id
        ORDER BY tl.created_at DESC LIMIT 10
    ");
    $trackStmt->execute([':id' => $id]);
    $trackLogs = $trackStmt->fetchAll();

    $maintStmt = $pdo->prepare("
        SELECT mr.*, u.full_name AS performed_by_name
        FROM maintenance_records mr
        LEFT JOIN users u ON mr.performed_by = u.id
        WHERE mr.equipment_id = :id
        ORDER BY mr.created_at DESC LIMIT 10
    ");
    $maintStmt->execute([':id' => $id]);
    $maintRecords = $maintStmt->fetchAll();

} catch (PDOException $e) {
    error_log($e->getMessage());
    $_SESSION['flash_error'] = 'Database error.';
    header('Location: /surgical_equipment/equipment/inventory-list.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title"><?= htmlspecialchars($item['name'] ?? 'Equipment Details', ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="page-breadcrumb">
                <a href="/surgical_equipment/dashboard.php">Home</a> /
                <a href="/surgical_equipment/equipment/inventory-list.php">Inventory</a> /
                <?= htmlspecialchars($item['equipment_code'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
            </p>
        </div>
        <div class="flex gap-2" style="align-items:center;">
            <a href="/surgical_equipment/product-details.php?id=<?= $id ?>" target="_blank" class="btn btn-secondary">
                <span>View in Public Store ↗</span>
            </a>
            <span class="status-badge status-<?= htmlspecialchars($item['status'] ?? 'available', ENT_QUOTES, 'UTF-8') ?>" style="font-size:var(--font-size-base); padding:6px 14px;">
                <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $item['status'] ?? 'available')), ENT_QUOTES, 'UTF-8') ?>
            </span>
            <?php if (in_array($role, ['admin', 'nurse', 'sterilisation_tech'], true)): ?>
                <a href="/surgical_equipment/maintenance/log-maintenance.php?equipment_id=<?= $id ?>" class="btn btn-secondary">Log Maintenance</a>
            <?php endif; ?>
            <?php if ($role === 'admin'): ?>
                <a href="/surgical_equipment/equipment/add-equipment.php?edit=<?= $id ?>" class="btn btn-primary">Edit</a>
                <form method="post" action="/surgical_equipment/process-action.php" onsubmit="return confirm('Are you sure you want to permanently delete this equipment (<?= htmlspecialchars(addslashes($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)? This action cannot be undone.');" style="display:inline; margin:0;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="equipment_delete">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <button type="submit" class="btn btn-danger" style="background-color:var(--color-danger); border-color:var(--color-danger); color:#ffffff;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                        <span>Delete</span>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <div class="card mb-6">
        <div class="card-header"><h3 class="card-title">Equipment Information</h3></div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px,1fr)); gap:20px;">
            <?php
            $fields = [
                'Equipment Code'   => $item['equipment_code'] ?? '—',
                'Category'         => $item['category'] ?? '—',
                'Serial Number'    => $item['serial_number'] ?? '—',
                'Current Location' => $item['current_location'] ?? '—',
                'Purchase Date'    => !empty($item['purchase_date']) ? date('M j, Y', strtotime($item['purchase_date'])) : '—',
                'Warranty Expiry'  => !empty($item['warranty_expiry']) ? date('M j, Y', strtotime($item['warranty_expiry'])) : '—',
                'Last Sterilised'  => !empty($item['last_sterilised_at']) ? date('M j, Y g:i A', strtotime($item['last_sterilised_at'])) : 'Never',
                'Added'            => !empty($item['created_at']) ? date('M j, Y', strtotime($item['created_at'])) : '—',
            ];
            foreach ($fields as $label => $val): ?>
                <div>
                    <span class="text-muted" style="font-size:var(--font-size-xs); display:block;"><?= $label ?></span>
                    <strong><?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (!empty($item['notes'])): ?>
            <div class="mt-4">
                <span class="text-muted" style="font-size:var(--font-size-xs); display:block;">Notes</span>
                <p><?= nl2br(htmlspecialchars($item['notes'], ENT_QUOTES, 'UTF-8')) ?></p>
            </div>
        <?php endif; ?>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Tracking History</h3>
            </div>
            <?php if (count($trackLogs) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead><tr><th>Action</th><th>From → To</th><th>By</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php foreach ($trackLogs as $t): ?>
                                <tr>
                                    <td><span class="status-badge" style="font-size:var(--font-size-xs);"><?= htmlspecialchars(ucfirst(str_replace('_',' ',$t['action'])), ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td class="text-muted" style="font-size:var(--font-size-xs);">
                                        <?= htmlspecialchars($t['from_location'] ?? '—', ENT_QUOTES, 'UTF-8') ?> →
                                        <?= htmlspecialchars($t['to_location'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="text-muted" style="font-size:var(--font-size-xs);"><?= htmlspecialchars($t['user_name'] ?? 'System', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-muted" style="font-size:var(--font-size-xs); white-space:nowrap;"><?= date('M j, Y', strtotime($t['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted text-center" style="padding:20px 0;">No tracking records yet.</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Maintenance History</h3>
            </div>
            <?php if (count($maintRecords) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead><tr><th>Type</th><th>Status</th><th>Scheduled</th><th>Cost</th></tr></thead>
                        <tbody>
                            <?php foreach ($maintRecords as $m): ?>
                                <tr>
                                    <td style="font-size:var(--font-size-xs);"><?= htmlspecialchars(ucfirst(str_replace('_',' ',$m['type'])), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="status-badge status-<?= htmlspecialchars($m['status'], ENT_QUOTES, 'UTF-8') ?>" style="font-size:var(--font-size-xs);"><?= htmlspecialchars(ucfirst($m['status']), ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td class="text-muted" style="font-size:var(--font-size-xs);"><?= $m['scheduled_date'] ? date('M j, Y', strtotime($m['scheduled_date'])) : '—' ?></td>
                                    <td class="text-muted" style="font-size:var(--font-size-xs);"><?= $m['cost'] !== null ? '$'.number_format((float)$m['cost'],2) : '—' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted text-center" style="padding:20px 0;">No maintenance records yet.</p>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
