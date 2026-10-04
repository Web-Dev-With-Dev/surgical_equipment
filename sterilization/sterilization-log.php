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

$allowed_view_roles = ['admin', 'sterilisation_tech', 'nurse', 'surgeon', 'auditor'];
if (!in_array($_SESSION['role'] ?? '', $allowed_view_roles, true)) {
    $_SESSION['flash_error'] = 'You do not have permission to view the sterilisation log.';
    header('Location: /surgical_equipment/dashboard.php');
    exit;
}

$csrf_token = $_SESSION['csrf_token'] ?? '';
$role       = $_SESSION['role'] ?? '';
$canManage  = in_array($role, ['admin', 'sterilisation_tech'], true);

$batchId = (int) ($_GET['batch_id'] ?? 0);

try {
    $pdo = getDbConnection();

    if ($batchId > 0) {

        $batchStmt = $pdo->prepare('
            SELECT sb.*, u.full_name AS started_by_name
            FROM sterilization_batches sb
            LEFT JOIN users u ON sb.started_by = u.id
            WHERE sb.id = :id
        ');
        $batchStmt->execute([':id' => $batchId]);
        $batch = $batchStmt->fetch();

        if (!$batch) {
            $_SESSION['flash_error'] = 'Sterilisation batch not found.';
            header('Location: /surgical_equipment/sterilization/sterilization-log.php');
            exit;
        }

        $itemsStmt = $pdo->prepare('
            SELECT sbi.id AS item_id, ei.id AS equipment_id, ei.equipment_code, ei.name, ei.category
            FROM sterilization_batch_items sbi
            JOIN equipment_inventory ei ON sbi.equipment_id = ei.id
            WHERE sbi.batch_id = :batch_id
            ORDER BY ei.equipment_code ASC
        ');
        $itemsStmt->execute([':batch_id' => $batchId]);
        $batchItems = $itemsStmt->fetchAll();

        $eligibleStmt = $pdo->prepare("
            SELECT ei.id, ei.equipment_code, ei.name
            FROM equipment_inventory ei
            WHERE ei.status = 'available'
              AND ei.id NOT IN (
                  SELECT equipment_id FROM sterilization_batch_items WHERE batch_id = :batch_id
              )
            ORDER BY ei.equipment_code ASC
        ");
        $eligibleStmt->execute([':batch_id' => $batchId]);
        $eligibleEquip = $eligibleStmt->fetchAll();

    } else {

        $statusFilter = $_GET['status'] ?? '';
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 15;
        $offset  = ($page - 1) * $perPage;

        $where  = [];
        $params = [];
        if ($statusFilter !== '') {
            $where[] = 'sb.status = :status';
            $params[':status'] = $statusFilter;
        }
        $whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

        $countStmt = $pdo->prepare("SELECT COUNT(*) AS total FROM sterilization_batches sb {$whereClause}");
        $countStmt->execute($params);
        $totalRows  = (int) $countStmt->fetch()['total'];
        $totalPages = max(1, (int) ceil($totalRows / $perPage));

        $listStmt = $pdo->prepare("
            SELECT sb.*, u.full_name AS started_by_name,
                   (SELECT COUNT(*) FROM sterilization_batch_items sbi WHERE sbi.batch_id = sb.id) AS item_count
            FROM sterilization_batches sb
            LEFT JOIN users u ON sb.started_by = u.id
            {$whereClause}
            ORDER BY sb.started_at DESC
            LIMIT :limit OFFSET :offset
        ");
        foreach ($params as $key => $val) {
            $listStmt->bindValue($key, $val);
        }
        $listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $listStmt->execute();
        $batches = $listStmt->fetchAll();

        $todayCode = 'STER-' . date('Ymd') . '-';
        $seqStmt = $pdo->prepare("
            SELECT MAX(CAST(SUBSTRING(batch_number, LENGTH(:prefix) + 1) AS UNSIGNED)) AS max_seq
            FROM sterilization_batches WHERE batch_number LIKE :like
        ");
        $seqStmt->execute([':prefix' => $todayCode, ':like' => $todayCode . '%']);
        $seqRow = $seqStmt->fetch();
        $nextSeq = ((int) ($seqRow['max_seq'] ?? 0)) + 1;
        $suggestedBatchCode = $todayCode . str_pad((string) $nextSeq, 3, '0', STR_PAD_LEFT);
    }

} catch (PDOException $e) {
    error_log('Sterilisation log error: ' . $e->getMessage());
    $_SESSION['flash_error'] = 'A database error occurred.';
    $batches = [];
    $totalRows = 0;
    $totalPages = 1;
    $suggestedBatchCode = 'STER-' . date('Ymd') . '-001';
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">

    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <?php if ($batchId > 0 && isset($batch)): ?>
        <!-- ═══════════════════════════════════════════════
             SINGLE BATCH VIEW
             ═══════════════════════════════════════════════ -->
        <div class="page-header">
            <div>
                <h1 class="page-title"><?= htmlspecialchars($batch['batch_number'], ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="page-breadcrumb">
                    <a href="/surgical_equipment/dashboard.php">Home</a> /
                    <a href="/surgical_equipment/sterilization/sterilization-log.php">Sterilisation Log</a> /
                    <?= htmlspecialchars($batch['batch_number'], ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
            <div class="flex gap-2">
                <span class="status-badge status-<?= htmlspecialchars($batch['status'], ENT_QUOTES, 'UTF-8') ?>" style="font-size: var(--font-size-base); padding: 4px 14px;">
                    <?= htmlspecialchars(ucfirst($batch['status']), ENT_QUOTES, 'UTF-8') ?>
                </span>
                <a href="/surgical_equipment/sterilization/sterilization-log.php" class="btn btn-secondary">Back to Log</a>
            </div>
        </div>

        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">Batch Information</h3>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div>
                    <span class="text-muted" style="font-size: var(--font-size-xs); display: block;">Cycle Type</span>
                    <strong><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $batch['cycle_type'])), ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div>
                    <span class="text-muted" style="font-size: var(--font-size-xs); display: block;">Started By</span>
                    <strong><?= htmlspecialchars($batch['started_by_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div>
                    <span class="text-muted" style="font-size: var(--font-size-xs); display: block;">Started At</span>
                    <strong><?= date('M j, Y g:i A', strtotime($batch['started_at'])) ?></strong>
                </div>
                <div>
                    <span class="text-muted" style="font-size: var(--font-size-xs); display: block;">Completed At</span>
                    <strong><?= $batch['completed_at'] ? date('M j, Y g:i A', strtotime($batch['completed_at'])) : '—' ?></strong>
                </div>
                <?php if ($batch['status'] === 'completed'): ?>
                    <div>
                        <span class="text-muted" style="font-size: var(--font-size-xs); display: block;">Chemical Indicator</span>
                        <strong><?= $batch['chemical_indicator_passed'] === null ? '—' : ((int) $batch['chemical_indicator_passed'] === 1 ? '<span class="indicator-pass">Pass</span>' : '<span class="indicator-fail">Fail</span>') ?></strong>
                    </div>
                    <div>
                        <span class="text-muted" style="font-size: var(--font-size-xs); display: block;">Biological Indicator</span>
                        <strong><?= $batch['biological_indicator_passed'] === null ? '—' : ((int) $batch['biological_indicator_passed'] === 1 ? '<span class="indicator-pass">Pass</span>' : '<span class="indicator-fail">Fail</span>') ?></strong>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($batch['notes']): ?>
                <div class="mt-4">
                    <span class="text-muted" style="font-size: var(--font-size-xs); display: block;">Operator Notes</span>
                    <p style="margin-top: 4px;"><?= nl2br(htmlspecialchars($batch['notes'], ENT_QUOTES, 'UTF-8')) ?></p>
                </div>
            <?php endif; ?>
        </div>

        <div style="display: grid; grid-template-columns: <?= $batch['status'] === 'in_progress' && $canManage ? '1fr 1fr' : '1fr' ?>; gap: 24px;">

            <!-- Batch Items -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Items in Batch</h3>
                    <span class="badge badge-admin"><?= count($batchItems) ?></span>
                </div>
                <?php if (count($batchItems) > 0): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($batchItems as $item): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($item['equipment_code'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                        <td>
                                            <a href="/surgical_equipment/equipment/equipment-details.php?id=<?= (int) $item['equipment_id'] ?>">
                                                <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                        </td>
                                        <td class="text-muted"><?= htmlspecialchars($item['category'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center" style="padding: 20px 0;">No items added to this batch yet.</p>
                <?php endif; ?>
            </div>

            <?php if ($batch['status'] === 'in_progress' && $canManage): ?>
                <!-- Manage Batch (add items / complete) -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Add Item to Batch</h3>
                    </div>
                    <?php if (count($eligibleEquip) > 0): ?>
                        <form method="post" action="/surgical_equipment/process-action.php" class="flex gap-2 mb-6">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="action" value="sterilisation_add_item">
                            <input type="hidden" name="batch_id" value="<?= (int) $batch['id'] ?>">
                            <select name="equipment_id" class="form-control" required>
                                <option value="">— Choose Equipment —</option>
                                <?php foreach ($eligibleEquip as $eq): ?>
                                    <option value="<?= (int) $eq['id'] ?>">
                                        [<?= htmlspecialchars($eq['equipment_code'], ENT_QUOTES, 'UTF-8') ?>]
                                        <?= htmlspecialchars($eq['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary" style="white-space: nowrap;">Add</button>
                        </form>
                    <?php else: ?>
                        <p class="text-muted mb-6" style="font-size: var(--font-size-sm);">No available equipment left to add.</p>
                    <?php endif; ?>

                    <div class="card-header">
                        <h3 class="card-title">Complete Batch</h3>
                    </div>

                    <?php if (count($batchItems) === 0): ?>
                        <div class="alert alert-danger" style="margin-bottom:16px;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                            You must add at least one item to this batch before completing it.
                        </div>
                    <?php endif; ?>

                    <form method="post" action="/surgical_equipment/process-action.php"
                          onsubmit="return (<?= count($batchItems) ?> > 0) && confirm('Mark this batch as completed? All items will be set to Available.');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="action" value="sterilisation_complete_batch">
                        <input type="hidden" name="id" value="<?= (int) $batch['id'] ?>">

                        <div class="form-group">
                            <label for="chemical_indicator">Chemical Indicator</label>
                            <select id="chemical_indicator" name="chemical_indicator" class="form-control">
                                <option value="">— Not Recorded —</option>
                                <option value="1">Pass</option>
                                <option value="0">Fail</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="biological_indicator">Biological Indicator</label>
                            <select id="biological_indicator" name="biological_indicator" class="form-control">
                                <option value="">— Not Recorded —</option>
                                <option value="1">Pass</option>
                                <option value="0">Fail</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="operator_notes">Operator Notes</label>
                            <textarea id="operator_notes" name="operator_notes" class="form-control" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-full"
                            <?= count($batchItems) === 0 ? 'disabled style="opacity:0.45; cursor:not-allowed;"' : '' ?>>
                            Mark Batch Completed
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <!-- ═══════════════════════════════════════════════
             BATCH LIST VIEW
             ═══════════════════════════════════════════════ -->
        <div class="page-header">
            <div>
                <h1 class="page-title">Sterilisation Log</h1>
                <p class="page-breadcrumb">
                    <a href="/surgical_equipment/dashboard.php">Home</a> / Sterilisation
                </p>
            </div>
        </div>

        <?php if ($canManage): ?>
            <!-- ── Start New Batch ─────────────────────────── -->
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title">Start New Sterilisation Batch</h3>
                </div>
                <form method="post" action="/surgical_equipment/process-action.php" style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 16px; align-items: end;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="sterilisation_create_batch">

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="batch_code">Batch Code <span style="color: var(--color-danger);">*</span></label>
                        <input type="text" id="batch_code" name="batch_code" class="form-control"
                               value="<?= htmlspecialchars($suggestedBatchCode, ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="cycle_type">Cycle Type <span style="color: var(--color-danger);">*</span></label>
                        <select id="cycle_type" name="cycle_type" class="form-control" required>
                            <option value="Standard Autoclave 134°C">Standard Autoclave 134°C</option>
                            <option value="Standard Autoclave 121°C">Standard Autoclave 121°C</option>
                            <option value="Ethylene Oxide Gas">Ethylene Oxide Gas</option>
                            <option value="Low Temp Hydrogen Peroxide Plasma">Low Temp Hydrogen Peroxide Plasma</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg">Start Batch</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- ── Filter Bar ─────────────────────────────────── -->
        <div class="card mb-4">
            <form method="get" action="/surgical_equipment/sterilization/sterilization-log.php" class="flex gap-2">
                <select name="status" class="form-control" style="max-width: 240px;">
                    <option value="">All Statuses</option>
                    <option value="in_progress" <?= ($_GET['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="completed"   <?= ($_GET['status'] ?? '') === 'completed'   ? 'selected' : '' ?>>Completed</option>
                    <option value="failed"      <?= ($_GET['status'] ?? '') === 'failed'      ? 'selected' : '' ?>>Failed</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="/surgical_equipment/sterilization/sterilization-log.php" class="btn btn-secondary">Clear</a>
            </form>
        </div>

        <div class="flex flex-between mb-2">
            <span class="text-muted" style="font-size: var(--font-size-sm);">
                Showing <?= count($batches) ?> of <?= number_format($totalRows) ?> batches
            </span>
        </div>

        <!-- ── Batches Table ────────────────────────────────── -->
        <div class="card" style="padding: 0; overflow: hidden;">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Batch Code</th>
                            <th>Cycle Type</th>
                            <th>Items</th>
                            <th>Status</th>
                            <th>Started By</th>
                            <th>Started At</th>
                            <th>Completed At</th>
                            <th style="width: 90px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($batches) > 0): ?>
                            <?php foreach ($batches as $b): ?>
                                <tr>
                                    <td style="white-space: nowrap;"><strong><?= htmlspecialchars($b['batch_number'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td style="white-space: nowrap;"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $b['cycle_type'])), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td style="text-align: center; font-weight: 600;"><?= (int) $b['item_count'] ?></td>
                                    <td style="white-space: nowrap;">
                                        <span class="status-badge status-<?= htmlspecialchars($b['status'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $b['status'])), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td style="white-space: nowrap; font-size: 0.8125rem; color: var(--color-text-body);">
                                        <?= htmlspecialchars($b['started_by_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="text-muted" style="font-size: var(--font-size-xs); white-space: nowrap;">
                                        <?= date('M j, Y g:i A', strtotime($b['started_at'])) ?>
                                    </td>
                                    <td class="text-muted" style="font-size: var(--font-size-xs); white-space: nowrap;">
                                        <?= $b['completed_at'] ? date('M j, Y g:i A', strtotime($b['completed_at'])) : '—' ?>
                                    </td>
                                    <td style="white-space: nowrap; text-align: center;">
                                        <a href="/surgical_equipment/sterilization/sterilization-log.php?batch_id=<?= (int) $b['id'] ?>" class="btn btn-sm btn-secondary">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center" style="padding: 40px 0; color: var(--color-text-secondary);">
                                    No sterilisation batches recorded yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="flex flex-between mt-4">
                <div class="text-muted" style="font-size: var(--font-size-sm);">
                    Page <?= $page ?> of <?= $totalPages ?>
                </div>
                <div class="flex gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&status=<?= urlencode($_GET['status'] ?? '') ?>" class="btn btn-sm btn-secondary">← Previous</a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&status=<?= urlencode($_GET['status'] ?? '') ?>" class="btn btn-sm btn-secondary">Next →</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
