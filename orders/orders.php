<?php
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require staff / admin login
if (empty($_SESSION['user_id']) || in_array($_SESSION['role'] ?? '', ['registered_user', 'customer'])) {
    header('Location: /surgical_equipment/auth/login.php');
    exit;
}

$pageTitle = "Customer & Hospital Orders";
$role = $_SESSION['role'] ?? 'admin';
$csrf_token = $_SESSION['csrf_token'] ?? '';

$statusFilter = trim($_GET['status'] ?? '');
$search       = trim($_GET['search'] ?? '');

try {
    $pdo = getDbConnection();

    // Handle status update POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order_status') {
        $orderId   = (int)($_POST['order_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        $tracking  = trim($_POST['tracking_number'] ?? '');
        $payStatus = trim($_POST['payment_status'] ?? '');

        if ($orderId > 0 && in_array($newStatus, ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'])) {
            $updStmt = $pdo->prepare("
                UPDATE orders 
                SET status = :st, tracking_number = :trk, payment_status = :ps, updated_at = NOW()
                WHERE id = :id
            ");
            $updStmt->execute([
                ':st'  => $newStatus,
                ':trk' => $tracking,
                ':ps'  => $payStatus,
                ':id'  => $orderId
            ]);

            // Audit
            $audit = $pdo->prepare("
                INSERT INTO system_audit_trails (user_id, action, entity_type, entity_id, details, ip_address, user_agent)
                VALUES (:uid, 'order.update_status', 'orders', :eid, :dt, :ip, :ua)
            ");
            $audit->execute([
                ':uid' => $_SESSION['user_id'],
                ':eid' => $orderId,
                ':dt'  => json_encode(['status' => $newStatus, 'tracking' => $tracking, 'payment_status' => $payStatus]),
                ':ip'  => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                ':ua'  => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ]);

            $_SESSION['flash_success'] = "Order #{$orderId} status updated successfully.";
            header("Location: /surgical_equipment/orders/orders.php");
            exit;
        }
    }

    $whereParts = ["1=1"];
    $params = [];

    if ($statusFilter !== '') {
        $whereParts[] = "o.status = :st";
        $params[':st'] = $statusFilter;
    }

    if ($search !== '') {
        $whereParts[] = "(o.order_number LIKE :s OR o.shipping_name LIKE :s OR u.organization LIKE :s OR o.tracking_number LIKE :s)";
        $params[':s'] = "%$search%";
    }

    $whereClause = 'WHERE ' . implode(' AND ', $whereParts);

    $stmt = $pdo->prepare("
        SELECT o.*, u.username, u.organization AS customer_org,
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count
        FROM orders o
        JOIN users u ON o.user_id = u.id
        $whereClause
        ORDER BY o.created_at DESC
    ");
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Summary metrics
    $mStmt = $pdo->query("
        SELECT 
            COUNT(*) AS total_orders,
            SUM(CASE WHEN status IN ('pending', 'processing', 'confirmed') THEN 1 ELSE 0 END) AS pending_orders,
            SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) AS shipped_orders,
            SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) AS delivered_orders,
            COALESCE(SUM(total_amount), 0) AS total_revenue
        FROM orders
    ");
    $metrics = $mStmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Admin orders DB error: " . $e->getMessage());
    $orders = [];
    $metrics = ['total_orders' => 0, 'pending_orders' => 0, 'shipped_orders' => 0, 'delivered_orders' => 0, 'total_revenue' => 0];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header" style="margin-bottom: 20px;">
        <div>
            <h1 class="page-title" style="font-size: 1.875rem;">Customer &amp; Hospital Orders</h1>
            <p class="page-breadcrumb">
                <a href="/surgical_equipment/dashboard.php">Home</a> /
                <span>Procurement Orders</span>
            </p>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <!-- 4-METRICS ROW -->
    <div class="cat-stats-row">
        <div class="cat-stat-box" style="--accent-bar: var(--color-primary);">
            <div class="cat-stat-label">Total Customer Orders</div>
            <div class="cat-stat-value"><?= (int)$metrics['total_orders'] ?></div>
            <div class="cat-stat-sub">Across all registered hospitals</div>
        </div>
        <div class="cat-stat-box" style="--accent-bar: #0284c7;">
            <div class="cat-stat-label">Processing &amp; Pending</div>
            <div class="cat-stat-value" style="color:#0284c7;"><?= (int)$metrics['pending_orders'] ?></div>
            <div class="cat-stat-sub">Awaiting QA &amp; dispatch</div>
        </div>
        <div class="cat-stat-box" style="--accent-bar: #b45309;">
            <div class="cat-stat-label">In-Transit / Shipped</div>
            <div class="cat-stat-value" style="color:#b45309;"><?= (int)$metrics['shipped_orders'] ?></div>
            <div class="cat-stat-sub">With active courier tracking</div>
        </div>
        <div class="cat-stat-box" style="--accent-bar: #047857;">
            <div class="cat-stat-label">Total Order Revenue</div>
            <div class="cat-stat-value" style="color:#047857;">$<?= number_format((float)$metrics['total_revenue'], 2) ?></div>
            <div class="cat-stat-sub">Institutional billed value</div>
        </div>
    </div>

    <!-- FILTER & SEARCH -->
    <div class="card p-3 mb-4" style="border-radius:12px;">
        <form method="get" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
            <div style="flex:1; min-width:240px;">
                <input type="text" name="search" class="form-control" placeholder="Search by Order #, hospital, consignee, or tracking..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div style="min-width:180px;">
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="processing" <?= $statusFilter === 'processing' ? 'selected' : '' ?>>Processing</option>
                    <option value="shipped" <?= $statusFilter === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                    <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                    <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <?php if ($search !== '' || $statusFilter !== ''): ?>
                <a href="/surgical_equipment/orders/orders.php" class="btn btn-secondary btn-sm">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- ORDERS TABLE -->
    <div class="card p-0" style="overflow:hidden; border-radius:var(--radius-xl); box-shadow:var(--shadow-sm);">
        <div class="table-responsive">
            <table class="table-structured">
                <thead>
                    <tr>
                        <th style="width:130px;">Order #</th>
                        <th style="width:120px;">Date</th>
                        <th>Hospital &amp; Consignee</th>
                        <th style="width:90px; text-align:center;">Items</th>
                        <th style="width:120px; text-align:right;">Amount</th>
                        <th style="width:120px; text-align:center;">Status</th>
                        <th style="width:130px; text-align:center;">Tracking</th>
                        <th style="width:140px; text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">No orders found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $ord): ?>
                            <tr>
                                <td>
                                    <a href="/surgical_equipment/user/view-order.php?id=<?= (int)$ord['id'] ?>" style="font-family:monospace; font-weight:700; color:var(--color-primary); text-decoration:none;">
                                        <?= htmlspecialchars($ord['order_number']) ?>
                                    </a>
                                </td>
                                <td style="font-size:0.8125rem; color:var(--color-text-muted);">
                                    <?= date('M d, Y', strtotime($ord['created_at'])) ?>
                                </td>
                                <td>
                                    <div style="font-weight:700; font-size:0.875rem; color:var(--color-text-heading);">
                                        <?= htmlspecialchars($ord['shipping_name']) ?>
                                    </div>
                                    <div style="font-size:0.75rem; color:var(--color-text-muted);">
                                        <?= htmlspecialchars($ord['customer_org'] ?: 'Medical Client') ?>
                                    </div>
                                </td>
                                <td style="text-align:center;">
                                    <span style="font-weight:700; font-size:0.75rem; background:#f1f5f9; padding:3px 8px; border-radius:4px;">
                                        <?= (int)$ord['item_count'] ?>
                                    </span>
                                </td>
                                <td style="text-align:right; font-weight:800; font-size:0.875rem; color:var(--color-primary);">
                                    $<?= number_format((float)$ord['total_amount'], 2) ?>
                                </td>
                                <td style="text-align:center;">
                                    <?php
                                    $st = $ord['status'];
                                    if ($st === 'delivered') {
                                        echo '<span style="background:#dcfce7; color:#15803d; font-weight:700; font-size:0.6875rem; padding:2px 7px; border-radius:999px;">Delivered</span>';
                                    } elseif ($st === 'processing') {
                                        echo '<span style="background:#e0f2fe; color:#0369a1; font-weight:700; font-size:0.6875rem; padding:2px 7px; border-radius:999px;">Processing</span>';
                                    } elseif ($st === 'shipped') {
                                        echo '<span style="background:#fef3c7; color:#b45309; font-weight:700; font-size:0.6875rem; padding:2px 7px; border-radius:999px;">Shipped</span>';
                                    } else {
                                        echo '<span style="background:#f1f5f9; color:#64748b; font-weight:700; font-size:0.6875rem; padding:2px 7px; border-radius:999px;">' . ucfirst($st) . '</span>';
                                    }
                                    ?>
                                </td>
                                <td style="text-align:center;">
                                    <?php if (!empty($ord['tracking_number'])): ?>
                                        <span style="font-family:monospace; font-size:0.6875rem; font-weight:700; color:#0284c7; background:#e0f2fe; padding:2px 6px; border-radius:4px;">
                                            <?= htmlspecialchars($ord['tracking_number']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="font-size:0.6875rem; color:#94a3b8;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right;">
                                    <div class="flex gap-1" style="justify-content:flex-end;">
                                        <!-- VIEW ORDER BUTTON -->
                                        <a href="/surgical_equipment/user/view-order.php?id=<?= (int)$ord['id'] ?>" class="btn btn-primary btn-sm" style="padding:4px 9px; font-size:0.75rem;" title="View Order Details &amp; Invoice">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                            View
                                        </a>
                                        <button type="button" class="btn btn-secondary btn-sm" style="padding:4px 8px; font-size:0.75rem;" onclick="openStatusModal(<?= (int)$ord['id'] ?>, '<?= addslashes($ord['status']) ?>', '<?= addslashes($ord['tracking_number'] ?? '') ?>', '<?= addslashes($ord['payment_status']) ?>')">
                                            Status
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Status Update Modal -->
<div id="adminStatusModal" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:2000; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#ffffff; border-radius:14px; max-width:440px; width:100%; padding:24px; box-shadow:0 20px 40px rgba(0,0,0,0.2);">
        <h3 style="margin:0 0 16px; font-size:1.125rem; font-weight:700;">Update Order Status</h3>
        <form method="post" action="/surgical_equipment/orders/orders.php">
            <input type="hidden" name="action" value="update_order_status">
            <input type="hidden" name="order_id" id="modalOrderId">

            <div style="display:flex; flex-direction:column; gap:14px; margin-bottom:18px;">
                <div>
                    <label style="display:block; font-size:0.75rem; font-weight:700; text-transform:uppercase; margin-bottom:4px;">Fulfillment Status</label>
                    <select name="status" id="modalStatus" class="form-control">
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div>
                    <label style="display:block; font-size:0.75rem; font-weight:700; text-transform:uppercase; margin-bottom:4px;">Courier Tracking Number</label>
                    <input type="text" name="tracking_number" id="modalTracking" class="form-control" placeholder="e.g. TRK-FEDEX-99201">
                </div>

                <div>
                    <label style="display:block; font-size:0.75rem; font-weight:700; text-transform:uppercase; margin-bottom:4px;">Payment Status</label>
                    <select name="payment_status" id="modalPayment" class="form-control">
                        <option value="invoice_requested">Invoice Requested</option>
                        <option value="paid">Paid in Full</option>
                        <option value="unpaid">Unpaid / Overdue</option>
                    </select>
                </div>
            </div>

            <div style="display:flex; gap:8px; justify-content:flex-end;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeStatusModal()">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openStatusModal(id, status, tracking, payment) {
    document.getElementById('modalOrderId').value = id;
    document.getElementById('modalStatus').value = status;
    document.getElementById('modalTracking').value = tracking;
    document.getElementById('modalPayment').value = payment;
    document.getElementById('adminStatusModal').style.display = 'flex';
}
function closeStatusModal() {
    document.getElementById('adminStatusModal').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
