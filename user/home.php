<?php
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require customer / registered login
if (empty($_SESSION['user_id'])) {
    header('Location: /surgical_equipment/auth/login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$pageTitle = "Hospital Procurement Dashboard";

try {
    $pdo = getDbConnection();

    // Fetch user details
    $userStmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
    $userStmt->execute([':id' => $userId]);
    $userProfile = $userStmt->fetch(PDO::FETCH_ASSOC);

    // Order metrics for this registered user
    $metricsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) AS total_orders,
            SUM(CASE WHEN status IN ('pending', 'confirmed', 'processing', 'shipped') THEN 1 ELSE 0 END) AS active_orders,
            SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) AS completed_orders,
            COALESCE(SUM(total_amount), 0) AS total_spend
        FROM orders
        WHERE user_id = :uid
    ");
    $metricsStmt->execute([':uid' => $userId]);
    $metrics = $metricsStmt->fetch(PDO::FETCH_ASSOC);

    // Recent 5 orders
    $recentStmt = $pdo->prepare("
        SELECT o.*, 
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count
        FROM orders o
        WHERE o.user_id = :uid
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $recentStmt->execute([':uid' => $userId]);
    $recentOrders = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

    // Featured catalogue items for quick ordering
    $featStmt = $pdo->query("
        SELECT id, name, equipment_code, category, unit_price, stock_quantity, notes
        FROM equipment_inventory
        WHERE status != 'retired'
        ORDER BY id ASC
        LIMIT 4
    ");
    $featuredEquip = $featStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("User home DB error: " . $e->getMessage());
    $userProfile = [];
    $metrics = ['total_orders' => 0, 'active_orders' => 0, 'completed_orders' => 0, 'total_spend' => 0];
    $recentOrders = [];
    $featuredEquip = [];
}

require_once __DIR__ . '/../includes/user-header.php';
?>

<div style="display:flex; flex-direction:column; gap:28px;">

    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success" style="margin-bottom:0;"><?= htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <!-- 1. WELCOME HOSPITAL HERO BANNER -->
    <div style="background: linear-gradient(135deg, #1E280D 0%, #3E531B 100%); border-radius:18px; padding:32px 36px; color:#ffffff; position:relative; overflow:hidden; box-shadow:0 12px 30px -10px rgba(30,40,13,0.3);">
        <div style="position:relative; z-index:2; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:20px;">
            <div>
                <div style="display:inline-flex; align-items:center; gap:8px; background:rgba(241, 247, 221, 0.15); border:1px solid rgba(241, 247, 221, 0.25); padding:4px 12px; border-radius:999px; font-size:0.75rem; font-weight:700; color:#F1F7DD; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:12px;">
                    <span style="width:7px; height:7px; border-radius:50%; background:#84cc16;"></span>
                    Verified Medical Institutional Partner
                </div>
                <h1 style="font-family:'Fraunces', Georgia, serif; font-size:2.25rem; font-weight:700; margin:0 0 6px; letter-spacing:-0.02em; color:#ffffff;">
                    Welcome, <?= htmlspecialchars($userProfile['full_name'] ?? $currentUser['full_name']) ?>
                </h1>
                <p style="margin:0; font-size:0.9375rem; color:#E0E7DC; max-width:600px;">
                    <?= htmlspecialchars($userProfile['organization'] ?? 'Medical Healthcare Organization') ?> &bull; Account ID: <span style="font-family:monospace; font-weight:700;">#MED-<?= str_pad($userId, 4, '0', STR_PAD_LEFT) ?></span>
                </p>
            </div>

            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <a href="/surgical_equipment/user/shop.php" class="btn" style="background:#F1F7DD; color:#1E280D; font-weight:700; padding:12px 22px; border-radius:8px; text-decoration:none; display:inline-flex; align-items:center; gap:8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                    <span>Browse Surgical Shop</span>
                </a>
                <a href="/surgical_equipment/user/orders.php" class="btn" style="background:rgba(255,255,255,0.12); color:#ffffff; border:1px solid rgba(255,255,255,0.25); font-weight:700; padding:12px 20px; border-radius:8px; text-decoration:none; display:inline-flex; align-items:center; gap:8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                    <span>View All Orders</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 2. FOUR-COLUMN METRIC STATS ROW -->
    <div class="cat-stats-row">
        <div class="cat-stat-box" style="--accent-bar: #3E531B;">
            <div class="cat-stat-label">Total Orders Placed</div>
            <div class="cat-stat-value"><?= (int)$metrics['total_orders'] ?></div>
            <div class="cat-stat-sub">Lifetime institutional purchases</div>
        </div>

        <div class="cat-stat-box" style="--accent-bar: #0284c7;">
            <div class="cat-stat-label">Active / In-Transit Orders</div>
            <div class="cat-stat-value" style="color:#0284c7;"><?= (int)$metrics['active_orders'] ?></div>
            <div class="cat-stat-sub">Processing &amp; in-transit to clinic</div>
        </div>

        <div class="cat-stat-box" style="--accent-bar: #047857;">
            <div class="cat-stat-label">Delivered Orders</div>
            <div class="cat-stat-value" style="color:#047857;"><?= (int)$metrics['completed_orders'] ?></div>
            <div class="cat-stat-sub">Verified &amp; accepted on site</div>
        </div>

        <div class="cat-stat-box" style="--accent-bar: #9333ea;">
            <div class="cat-stat-label">Total Procurement Value</div>
            <div class="cat-stat-value" style="color:#7e22ce;">$<?= number_format((float)$metrics['total_spend'], 2) ?></div>
            <div class="cat-stat-sub">Pro-forma &amp; PO billed amount</div>
        </div>
    </div>

    <!-- 3. RECENT ORDERS WITH VIEW ORDER BUTTON -->
    <div class="card p-0" style="overflow:hidden; border-radius:var(--radius-xl); box-shadow:var(--shadow-sm);">
        <div style="padding:20px 24px; border-bottom:1px solid var(--color-border); display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h2 style="font-size:1.25rem; font-weight:700; margin:0; color:var(--color-text-heading);">Recent Hospital Orders</h2>
                <p style="margin:2px 0 0; font-size:0.8125rem; color:var(--color-text-muted);">Track your recent procurement requests and view itemized invoices</p>
            </div>
            <a href="/surgical_equipment/user/orders.php" class="btn btn-secondary btn-sm" style="font-weight:700;">
                View All Orders &rarr;
            </a>
        </div>

        <div class="table-responsive">
            <table class="table-structured">
                <thead>
                    <tr>
                        <th style="width:140px;">Order #</th>
                        <th style="width:130px;">Date</th>
                        <th>Consignee &amp; Hospital</th>
                        <th style="width:100px; text-align:center;">Items</th>
                        <th style="width:130px; text-align:right;">Grand Total</th>
                        <th style="width:120px; text-align:center;">Status</th>
                        <th style="width:140px; text-align:center;">Payment</th>
                        <th style="width:130px; text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentOrders)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                You have not placed any orders yet. <a href="/surgical_equipment/user/shop.php" style="color:var(--color-primary); font-weight:700; text-decoration:underline;">Explore our surgical catalogue</a> to place your first order.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $ord): ?>
                            <tr>
                                <td>
                                    <a href="/surgical_equipment/user/view-order.php?id=<?= (int)$ord['id'] ?>" style="font-weight:700; color:var(--color-primary); font-family:monospace; font-size:0.875rem; text-decoration:none;">
                                        <?= htmlspecialchars($ord['order_number'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </td>
                                <td style="font-size:0.8125rem; color:var(--color-text-muted);">
                                    <?= date('M d, Y', strtotime($ord['created_at'])) ?>
                                </td>
                                <td>
                                    <div style="font-weight:600; font-size:0.875rem; color:var(--color-text-heading);">
                                        <?= htmlspecialchars($ord['shipping_name']) ?>
                                    </div>
                                    <div style="font-size:0.75rem; color:var(--color-text-muted); max-width:220px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        <?= htmlspecialchars($ord['shipping_address']) ?>
                                    </div>
                                </td>
                                <td style="text-align:center;">
                                    <span style="font-weight:700; font-size:0.8125rem; background:#f1f5f9; padding:3px 8px; border-radius:6px; color:#475569;">
                                        <?= (int)$ord['item_count'] ?> units
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
                                    <a href="/surgical_equipment/user/view-order.php?id=<?= (int)$ord['id'] ?>" class="btn btn-primary btn-sm" style="padding:5px 12px; font-size:0.75rem; font-weight:700;" title="View Order Details">
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

    <!-- 4. FEATURED SURGICAL INSTRUMENTS (QUICK ADD) -->
    <div>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <div>
                <h2 style="font-size:1.25rem; font-weight:700; margin:0; color:var(--color-text-heading);">Quick Restock &amp; Featured Equipment</h2>
                <p style="margin:2px 0 0; font-size:0.8125rem; color:var(--color-text-muted);">Commonly ordered precision surgical supplies for immediate dispatch</p>
            </div>
            <a href="/surgical_equipment/user/shop.php" class="btn btn-secondary btn-sm" style="font-weight:700;">
                Full Catalogue &rarr;
            </a>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(260px, 1fr)); gap:18px;">
            <?php foreach ($featuredEquip as $item): ?>
                <div class="card p-4" style="border-radius:14px; display:flex; flex-direction:column; justify-content:space-between; transition:transform 0.15s, box-shadow 0.15s;">
                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <span style="font-size:0.6875rem; font-weight:700; color:var(--color-primary); background:var(--color-primary-light); padding:2px 7px; border-radius:4px;">
                                <?= htmlspecialchars($item['category']) ?>
                            </span>
                            <span style="font-family:monospace; font-size:0.6875rem; color:#64748b;">
                                <?= htmlspecialchars($item['equipment_code']) ?>
                            </span>
                        </div>
                        <h3 style="font-size:0.9375rem; font-weight:700; margin:0 0 6px; color:var(--color-text-heading);">
                            <?= htmlspecialchars($item['name']) ?>
                        </h3>
                        <p style="font-size:0.75rem; color:var(--color-text-muted); margin-bottom:12px; line-height:1.4;">
                            CE &amp; ISO 13485 Certified. Autoclavable Grade-316L Alloy.
                        </p>
                    </div>

                    <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--color-border); padding-top:12px; margin-top:8px;">
                        <div>
                            <span style="font-size:0.6875rem; color:#64748b; display:block;">Unit Price</span>
                            <span style="font-size:1.125rem; font-weight:800; color:var(--color-primary);">$<?= number_format((float)$item['unit_price'], 2) ?></span>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" onclick="addToCart({ id: <?= (int)$item['id'] ?>, name: '<?= addslashes($item['name']) ?>', sku: '<?= addslashes($item['equipment_code']) ?>', price: <?= (float)$item['unit_price'] ?> })" style="padding:6px 12px; font-weight:700; font-size:0.75rem;">
                            + Add to Order
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/user-footer.php'; ?>
