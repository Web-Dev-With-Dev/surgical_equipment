<?php
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: /surgical_equipment/auth/login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$pageTitle = "My Hospital Orders & Invoices";

$statusFilter = trim($_GET['status'] ?? '');
$search       = trim($_GET['search'] ?? '');

try {
    $pdo = getDbConnection();

    $whereParts = ["user_id = :uid"];
    $params = [':uid' => $userId];

    if ($statusFilter !== '') {
        $whereParts[] = "status = :st";
        $params[':st'] = $statusFilter;
    }

    if ($search !== '') {
        $whereParts[] = "(order_number LIKE :s OR shipping_name LIKE :s OR tracking_number LIKE :s)";
        $params[':s'] = "%$search%";
    }

    $whereClause = 'WHERE ' . implode(' AND ', $whereParts);

    $stmt = $pdo->prepare("
        SELECT o.*,
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count,
               (SELECT GROUP_CONCAT(item_name SEPARATOR ', ') FROM order_items WHERE order_id = o.id) AS items_preview
        FROM orders o
        $whereClause
        ORDER BY o.created_at DESC
    ");
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Counts for filter pills
    $countsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) AS count_all,
            SUM(CASE WHEN status IN ('pending', 'confirmed', 'processing', 'shipped') THEN 1 ELSE 0 END) AS count_active,
            SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) AS count_delivered,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS count_cancelled
        FROM orders
        WHERE user_id = :uid
    ");
    $countsStmt->execute([':uid' => $userId]);
    $counts = $countsStmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Orders list DB error: " . $e->getMessage());
    $orders = [];
    $counts = ['count_all' => 0, 'count_active' => 0, 'count_delivered' => 0, 'count_cancelled' => 0];
}

require_once __DIR__ . '/../includes/user-header.php';
?>

<div style="display:flex; flex-direction:column; gap:24px;">

    <!-- PAGE TITLE & CTA -->
    <div style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:16px;">
        <div>
            <h1 style="font-family:'Fraunces', Georgia, serif; font-size:2rem; font-weight:700; margin:0 0 6px; color:var(--user-dark);">
                My Hospital Orders
            </h1>
            <p style="margin:0; font-size:0.875rem; color:#64748b;">
                Track real-time shipment status, review pro-forma invoices, and inspect order histories.
            </p>
        </div>

        <a href="/surgical_equipment/user/shop.php" class="btn btn-primary" style="padding:10px 20px; font-weight:700;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
            <span>Place New Order</span>
        </a>
    </div>

    <!-- FILTER BUTTON PILLS -->
    <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
        <a href="?status=" class="btn btn-sm <?= $statusFilter === '' ? 'btn-primary' : 'btn-secondary' ?>" style="font-weight:700;">
            All Orders (<?= (int)$counts['count_all'] ?>)
        </a>
        <a href="?status=processing" class="btn btn-sm <?= $statusFilter === 'processing' ? 'btn-primary' : 'btn-secondary' ?>" style="font-weight:700;">
            Active / In-Transit (<?= (int)$counts['count_active'] ?>)
        </a>
        <a href="?status=delivered" class="btn btn-sm <?= $statusFilter === 'delivered' ? 'btn-primary' : 'btn-secondary' ?>" style="font-weight:700;">
            Delivered (<?= (int)$counts['count_delivered'] ?>)
        </a>
        <?php if ((int)$counts['count_cancelled'] > 0): ?>
            <a href="?status=cancelled" class="btn btn-sm <?= $statusFilter === 'cancelled' ? 'btn-primary' : 'btn-secondary' ?>" style="font-weight:700;">
                Cancelled (<?= (int)$counts['count_cancelled'] ?>)
            </a>
        <?php endif; ?>
    </div>

    <!-- SEARCH BAR -->
    <div class="card p-3" style="border-radius:12px;">
        <form method="get" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
            <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
            <div style="flex:1; min-width:260px;">
                <input type="text" name="search" class="form-control" placeholder="Search by Order #, consignee, or tracking number..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <button type="submit" class="btn btn-primary btn-sm" style="font-weight:700; padding:8px 16px;">Search</button>
            <?php if ($search !== '' || $statusFilter !== ''): ?>
                <a href="/surgical_equipment/user/orders.php" class="btn btn-secondary btn-sm">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- ORDERS TABLE -->
    <div class="card p-0" style="overflow:hidden; border-radius:var(--radius-xl); box-shadow:var(--shadow-sm);">
        <div class="table-responsive">
            <table class="table-structured">
                <thead>
                    <tr>
                        <th style="width:140px;">Order #</th>
                        <th style="width:130px;">Date</th>
                        <th>Instruments &amp; Equipment</th>
                        <th style="width:100px; text-align:center;">Units</th>
                        <th style="width:130px; text-align:right;">Grand Total</th>
                        <th style="width:130px; text-align:center;">Fulfillment</th>
                        <th style="width:140px; text-align:center;">Payment</th>
                        <th style="width:130px; text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                No hospital orders found matching your criteria.
                                <div style="margin-top:10px;">
                                    <a href="/surgical_equipment/user/shop.php" class="btn btn-primary btn-sm">+ Shop Surgical Equipment</a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $ord): ?>
                            <tr>
                                <td>
                                    <a href="/surgical_equipment/user/view-order.php?id=<?= (int)$ord['id'] ?>" style="font-weight:700; color:var(--user-primary); font-family:monospace; font-size:0.875rem; text-decoration:none;">
                                        <?= htmlspecialchars($ord['order_number'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </td>
                                <td style="font-size:0.8125rem; color:var(--color-text-muted);">
                                    <?= date('M d, Y', strtotime($ord['created_at'])) ?><br>
                                    <span style="font-size:0.6875rem; color:#94a3b8;"><?= date('h:i A', strtotime($ord['created_at'])) ?></span>
                                </td>
                                <td>
                                    <div style="font-weight:600; font-size:0.875rem; color:var(--color-text-heading); max-width:320px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= htmlspecialchars($ord['items_preview'] ?? '') ?>">
                                        <?= htmlspecialchars($ord['items_preview'] ?? 'Medical Items') ?>
                                    </div>
                                    <?php if (!empty($ord['tracking_number'])): ?>
                                        <div style="font-size:0.6875rem; color:#0284c7; font-family:monospace; font-weight:700;">
                                            📦 <?= htmlspecialchars($ord['tracking_number']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:center;">
                                    <span style="font-weight:700; font-size:0.8125rem; background:#f1f5f9; padding:3px 8px; border-radius:6px; color:#475569;">
                                        <?= (int)$ord['item_count'] ?> items
                                    </span>
                                </td>
                                <td style="text-align:right; font-weight:800; font-size:0.9375rem; color:var(--color-text-heading);">
                                    $<?= number_format((float)$ord['total_amount'], 2) ?>
                                </td>
                                <td style="text-align:center;">
                                    <?php
                                    $st = $ord['status'];
                                    if ($st === 'delivered') {
                                        echo '<span style="background:#dcfce7; color:#15803d; font-weight:700; font-size:0.75rem; padding:3px 8px; border-radius:999px;">Delivered</span>';
                                    } elseif ($st === 'processing') {
                                        echo '<span style="background:#e0f2fe; color:#0369a1; font-weight:700; font-size:0.75rem; padding:3px 8px; border-radius:999px;">Processing</span>';
                                    } elseif ($st === 'shipped') {
                                        echo '<span style="background:#fef3c7; color:#b45309; font-weight:700; font-size:0.75rem; padding:3px 8px; border-radius:999px;">Shipped</span>';
                                    } elseif ($st === 'cancelled') {
                                        echo '<span style="background:#fee2e2; color:#b91c1c; font-weight:700; font-size:0.75rem; padding:3px 8px; border-radius:999px;">Cancelled</span>';
                                    } else {
                                        echo '<span style="background:#f1f5f9; color:#64748b; font-weight:700; font-size:0.75rem; padding:3px 8px; border-radius:999px;">' . ucfirst($st) . '</span>';
                                    }
                                    ?>
                                </td>
                                <td style="text-align:center;">
                                    <?php if ($ord['payment_status'] === 'paid'): ?>
                                        <span style="font-size:0.6875rem; font-weight:700; background:#dcfce7; color:#166534; padding:2px 7px; border-radius:4px;">PAID</span>
                                    <?php else: ?>
                                        <span style="font-size:0.6875rem; font-weight:700; background:#fef9c3; color:#854d0e; padding:2px 7px; border-radius:4px;">PRO-FORMA PO</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right;">
                                    <!-- VIEW ORDER BUTTON -->
                                    <a href="/surgical_equipment/user/view-order.php?id=<?= (int)$ord['id'] ?>" class="btn btn-primary btn-sm" style="padding:5px 12px; font-size:0.75rem; font-weight:700;" title="View Order Details &amp; Pro-Forma Invoice">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                        <span>View Order</span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/user-footer.php'; ?>
