<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/product-helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: /surgical_equipment/auth/login.php');
    exit;
}

$orderId = (int) ($_GET['id'] ?? 0);
$userId  = (int) $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'registered_user';

if ($orderId <= 0) {
    header('Location: /surgical_equipment/user/orders.php');
    exit;
}

try {
    $pdo = getDbConnection();

    // Fetch order - allow admin to view any order, customer to view their own
    if (in_array($userRole, ['admin', 'surgeon', 'nurse', 'sterilisation_tech', 'auditor'])) {
        $ordStmt = $pdo->prepare("
            SELECT o.*, u.username, u.full_name AS user_full_name, u.organization AS user_org
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.id = :id
            LIMIT 1
        ");
        $ordStmt->execute([':id' => $orderId]);
    } else {
        $ordStmt = $pdo->prepare("
            SELECT o.*, u.username, u.full_name AS user_full_name, u.organization AS user_org
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.id = :id AND o.user_id = :uid
            LIMIT 1
        ");
        $ordStmt->execute([':id' => $orderId, ':uid' => $userId]);
    }

    $order = $ordStmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        $_SESSION['flash_error'] = 'Order not found or you do not have permission to view it.';
        header('Location: /surgical_equipment/user/orders.php');
        exit;
    }

    // Fetch order items
    $itemsStmt = $pdo->prepare("
        SELECT oi.*, e.category, e.notes AS equip_notes
        FROM order_items oi
        LEFT JOIN equipment_inventory e ON oi.equipment_id = e.id
        WHERE oi.order_id = :oid
        ORDER BY oi.id ASC
    ");
    $itemsStmt->execute([':oid' => $orderId]);
    $orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("View order DB error: " . $e->getMessage());
    $_SESSION['flash_error'] = 'A system error occurred while retrieving order details.';
    header('Location: /surgical_equipment/user/orders.php');
    exit;
}

$pageTitle = "Order #" . htmlspecialchars($order['order_number']);
require_once __DIR__ . '/../includes/user-header.php';
?>

<div style="display:flex; flex-direction:column; gap:24px;">

    <!-- TOP BREADCRUMB & HEADER -->
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px;">
        <div>
            <div style="font-size:0.8125rem; color:#64748b; margin-bottom:4px;">
                <a href="/surgical_equipment/user/home.php" style="color:inherit; text-decoration:none;">Dashboard</a> /
                <a href="/surgical_equipment/user/orders.php" style="color:inherit; text-decoration:none;">My Orders</a> /
                <span style="color:var(--user-primary); font-weight:700;"><?= htmlspecialchars($order['order_number']) ?></span>
            </div>

            <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                <h1 style="font-family:'Fraunces', Georgia, serif; font-size:2rem; font-weight:700; margin:0; color:var(--user-dark);">
                    <?= htmlspecialchars($order['order_number']) ?>
                </h1>
                
                <?php
                $st = $order['status'];
                if ($st === 'delivered') {
                    echo '<span style="background:#dcfce7; color:#15803d; font-weight:800; font-size:0.8125rem; padding:4px 12px; border-radius:999px;">✓ DELIVERED</span>';
                } elseif ($st === 'processing') {
                    echo '<span style="background:#e0f2fe; color:#0369a1; font-weight:800; font-size:0.8125rem; padding:4px 12px; border-radius:999px;">● PROCESSING</span>';
                } elseif ($st === 'shipped') {
                    echo '<span style="background:#fef3c7; color:#b45309; font-weight:800; font-size:0.8125rem; padding:4px 12px; border-radius:999px;">🚚 SHIPPED</span>';
                } elseif ($st === 'cancelled') {
                    echo '<span style="background:#fee2e2; color:#b91c1c; font-weight:800; font-size:0.8125rem; padding:4px 12px; border-radius:999px;">✕ CANCELLED</span>';
                } else {
                    echo '<span style="background:#f1f5f9; color:#64748b; font-weight:800; font-size:0.8125rem; padding:4px 12px; border-radius:999px;">⏳ ' . strtoupper($st) . '</span>';
                }
                ?>
            </div>
            
            <p style="margin:6px 0 0; font-size:0.875rem; color:#64748b;">
                Placed on <strong><?= date('F d, Y \a\t h:i A', strtotime($order['created_at'])) ?></strong> &bull; Consignee: <?= htmlspecialchars($order['shipping_name']) ?>
            </p>
        </div>

        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <button type="button" class="btn btn-secondary" onclick="window.print()" style="font-weight:700; display:inline-flex; align-items:center; gap:6px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                <span>Print Pro-Forma Invoice</span>
            </button>
            <a href="/surgical_equipment/user/shop.php" class="btn btn-primary" style="font-weight:700;">
                + Shop More Equipment
            </a>
        </div>
    </div>

    <!-- 2. INTERACTIVE ORDER PROGRESS TRACKER -->
    <?php
    $steps = [
        'pending'    => 1,
        'confirmed'  => 2,
        'processing' => 2,
        'shipped'    => 3,
        'delivered'  => 4,
        'cancelled'  => 0
    ];
    $currentStep = $steps[$order['status']] ?? 1;
    ?>
    <div class="card p-4" style="border-radius:16px;">
        <h3 style="font-size:0.9375rem; font-weight:700; color:var(--user-dark); margin:0 0 20px; text-transform:uppercase; letter-spacing:0.04em;">
            Procurement &amp; Sterilization Fulfillment Timeline
        </h3>

        <div style="display:flex; justify-content:space-between; position:relative; max-width:900px; margin:0 auto; padding:0 20px;">
            <!-- Background progress line -->
            <div style="position:absolute; top:20px; left:40px; right:40px; height:4px; background:#e2e8f0; z-index:1;"></div>
            <div style="position:absolute; top:20px; left:40px; width:<?= $currentStep >= 4 ? 'calc(100% - 80px)' : ($currentStep == 3 ? '66%' : ($currentStep >= 2 ? '33%' : '0%')) ?>; height:4px; background:var(--user-primary); z-index:2; transition:width 0.5s ease;"></div>

            <!-- Step 1 -->
            <div style="position:relative; z-index:3; text-align:center; width:120px;">
                <div style="width:40px; height:40px; border-radius:50%; background:<?= $currentStep >= 1 ? 'var(--user-primary)' : '#e2e8f0' ?>; color:#ffffff; display:flex; align-items:center; justify-content:center; margin:0 auto 8px; font-weight:700;">
                    ✓
                </div>
                <div style="font-weight:700; font-size:0.8125rem; color:var(--user-dark);">Order Placed</div>
                <div style="font-size:0.6875rem; color:#64748b;"><?= date('M d, h:i A', strtotime($order['created_at'])) ?></div>
            </div>

            <!-- Step 2 -->
            <div style="position:relative; z-index:3; text-align:center; width:120px;">
                <div style="width:40px; height:40px; border-radius:50%; background:<?= $currentStep >= 2 ? 'var(--user-primary)' : '#cbd5e1' ?>; color:#ffffff; display:flex; align-items:center; justify-content:center; margin:0 auto 8px; font-weight:700;">
                    <?= $currentStep >= 2 ? '✓' : '2' ?>
                </div>
                <div style="font-weight:700; font-size:0.8125rem; color:var(--user-dark);">Clinical Review &amp; QC</div>
                <div style="font-size:0.6875rem; color:#64748b;"><?= $currentStep >= 2 ? 'Verified &amp; Inspected' : 'Pending' ?></div>
            </div>

            <!-- Step 3 -->
            <div style="position:relative; z-index:3; text-align:center; width:120px;">
                <div style="width:40px; height:40px; border-radius:50%; background:<?= $currentStep >= 3 ? 'var(--user-primary)' : '#cbd5e1' ?>; color:#ffffff; display:flex; align-items:center; justify-content:center; margin:0 auto 8px; font-weight:700;">
                    <?= $currentStep >= 3 ? '✓' : '3' ?>
                </div>
                <div style="font-weight:700; font-size:0.8125rem; color:var(--user-dark);">Sterilized &amp; Dispatched</div>
                <div style="font-size:0.6875rem; color:#64748b;">
                    <?= !empty($order['tracking_number']) ? htmlspecialchars($order['tracking_number']) : ($currentStep >= 3 ? 'In Transit' : 'Pending') ?>
                </div>
            </div>

            <!-- Step 4 -->
            <div style="position:relative; z-index:3; text-align:center; width:120px;">
                <div style="width:40px; height:40px; border-radius:50%; background:<?= $currentStep >= 4 ? '#15803d' : '#cbd5e1' ?>; color:#ffffff; display:flex; align-items:center; justify-content:center; margin:0 auto 8px; font-weight:700;">
                    <?= $currentStep >= 4 ? '✓' : '4' ?>
                </div>
                <div style="font-weight:700; font-size:0.8125rem; color:var(--user-dark);">Hospital Delivery</div>
                <div style="font-size:0.6875rem; color:#64748b;"><?= $currentStep >= 4 ? 'Delivered &amp; Accepted' : 'Est. Delivery' ?></div>
            </div>
        </div>
    </div>

    <!-- 3. ORDER ITEMS BREAKDOWN & SHIPPING INFO GRID -->
    <div style="display:grid; grid-template-columns:2fr 1fr; gap:24px; align-items:start;">
        
        <!-- Left: Itemized Table -->
        <div class="card p-0" style="overflow:hidden; border-radius:var(--radius-xl); box-shadow:var(--shadow-sm);">
            <div style="padding:18px 24px; border-bottom:1px solid var(--color-border); background:#fafbfa;">
                <h3 style="margin:0; font-size:1.125rem; font-weight:700; color:var(--color-text-heading);">
                    Itemized Surgical Products (<?= count($orderItems) ?> items)
                </h3>
            </div>

            <div class="table-responsive">
                <table class="table-structured">
                    <thead>
                        <tr>
                            <th>Surgical Instrument</th>
                            <th style="width:130px;">SKU / Code</th>
                            <th style="width:120px; text-align:right;">Unit Price</th>
                            <th style="width:80px; text-align:center;">Qty</th>
                            <th style="width:130px; text-align:right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderItems as $it): 
                            $img = get_product_image_url($it['category'] ?? '', $it['item_name'], $it['sku'] ?? '', $it['equip_notes'] ?? '');
                        ?>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($it['item_name']) ?>" style="width:44px; height:44px; border-radius:6px; object-fit:cover; background:#f1f5f9; flex-shrink:0;" onerror="this.src='/surgical_equipment/assets/images/placeholder.jpg'">
                                        <div>
                                            <div style="font-weight:700; font-size:0.9375rem; color:var(--color-text-heading);">
                                                <?= htmlspecialchars($it['item_name']) ?>
                                            </div>
                                            <div style="font-size:0.75rem; color:var(--color-text-muted);">
                                                <?= htmlspecialchars($it['category'] ?? 'Surgical Supply') ?> &bull; CE &amp; ISO 13485
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-family:monospace; font-size:0.75rem; font-weight:700; background:#f1f5f9; padding:3px 7px; border-radius:4px;">
                                        <?= htmlspecialchars($it['sku'] ?? 'N/A') ?>
                                    </span>
                                </td>
                                <td style="text-align:right; font-weight:600; font-size:0.875rem;">
                                    $<?= number_format((float)$it['unit_price'], 2) ?>
                                </td>
                                <td style="text-align:center; font-weight:700; font-size:0.875rem;">
                                    <?= (int)$it['quantity'] ?>
                                </td>
                                <td style="text-align:right; font-weight:800; font-size:0.9375rem; color:var(--color-primary);">
                                    $<?= number_format((float)$it['total_price'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Total Calculation Section -->
            <div style="padding:20px 24px; background:#fafbfa; border-top:1px solid var(--color-border);">
                <div style="display:flex; flex-direction:column; gap:8px; max-width:320px; margin-left:auto;">
                    <div style="display:flex; justify-content:space-between; font-size:0.875rem; color:#64748b;">
                        <span>Products Subtotal:</span>
                        <span style="font-weight:700; color:var(--color-text);">$<?= number_format((float)$order['total_amount'] - (float)$order['tax_amount'] - (float)$order['shipping_amount'], 2) ?></span>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:0.875rem; color:#64748b;">
                        <span>Medical GST / Tax (9%):</span>
                        <span style="font-weight:700; color:var(--color-text);">$<?= number_format((float)$order['tax_amount'], 2) ?></span>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:0.875rem; color:#64748b;">
                        <span>Sterilized Logistics &amp; Freight:</span>
                        <span style="font-weight:700; color:var(--color-text);">$<?= number_format((float)$order['shipping_amount'], 2) ?></span>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:1.125rem; font-weight:800; color:var(--user-primary); border-top:2px solid #cbd5e1; padding-top:10px; margin-top:4px;">
                        <span>Total Payable:</span>
                        <span>$<?= number_format((float)$order['total_amount'], 2) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Shipping & Hospital Delivery Details -->
        <div style="display:flex; flex-direction:column; gap:20px;">
            
            <!-- Consignee Card -->
            <div class="card p-4" style="border-radius:16px;">
                <h3 style="font-size:0.875rem; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; color:#64748b; margin:0 0 14px;">
                    Consignee &amp; Hospital Address
                </h3>
                
                <div style="font-weight:800; font-size:1rem; color:var(--user-dark); margin-bottom:4px;">
                    <?= htmlspecialchars($order['shipping_name']) ?>
                </div>
                <div style="font-size:0.8125rem; font-weight:600; color:var(--user-primary); margin-bottom:8px;">
                    <?= htmlspecialchars($order['user_org'] ?? 'Medical Facility') ?>
                </div>
                <p style="font-size:0.875rem; color:#475569; margin:0 0 12px; line-height:1.5;">
                    <?= nl2br(htmlspecialchars($order['shipping_address'])) ?><br>
                    <?= htmlspecialchars($order['city'] ?? '') ?> <?= htmlspecialchars($order['pincode'] ?? '') ?>
                </p>

                <div style="border-top:1px solid #f1f5f9; padding-top:10px; font-size:0.8125rem; color:#64748b;">
                    <div><strong>Phone:</strong> <?= htmlspecialchars($order['shipping_phone'] ?: 'N/A') ?></div>
                    <div><strong>Email:</strong> <?= htmlspecialchars($order['shipping_email'] ?: 'N/A') ?></div>
                </div>
            </div>

            <!-- Payment & Terms Card -->
            <div class="card p-4" style="border-radius:16px;">
                <h3 style="font-size:0.875rem; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; color:#64748b; margin:0 0 14px;">
                    Procurement &amp; Payment Terms
                </h3>
                
                <div style="font-weight:700; font-size:0.875rem; color:var(--user-dark); margin-bottom:4px;">
                    <?= htmlspecialchars($order['payment_method']) ?>
                </div>
                
                <div style="margin-top:8px;">
                    <span style="font-size:0.6875rem; font-weight:700; text-transform:uppercase; color:#64748b;">Payment Status:</span>
                    <?php if ($order['payment_status'] === 'paid'): ?>
                        <span style="background:#dcfce7; color:#166534; font-weight:700; font-size:0.75rem; padding:2px 8px; border-radius:4px; margin-left:6px;">PAID IN FULL</span>
                    <?php else: ?>
                        <span style="background:#fef9c3; color:#854d0e; font-weight:700; font-size:0.75rem; padding:2px 8px; border-radius:4px; margin-left:6px;">PRO-FORMA INVOICE ISSUED</span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($order['notes'])): ?>
                    <div style="margin-top:14px; padding:10px; background:#f8fafc; border-radius:6px; border:1px solid #e2e8f0; font-size:0.75rem; color:#475569;">
                        <strong>Clinical Instructions:</strong><br>
                        <?= nl2br(htmlspecialchars($order['notes'])) ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

    </div>

</div>

<?php if (isset($_SESSION['clear_cart_trigger'])): ?>
    <script>
        // Clear local storage cart once order is confirmed
        localStorage.removeItem('apex_hospital_cart_v1');
    </script>
    <?php unset($_SESSION['clear_cart_trigger']); ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/user-footer.php'; ?>
