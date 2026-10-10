<?php
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: /surgical_equipment/auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /surgical_equipment/user/shop.php');
    exit;
}

// CSRF Validation
$csrfToken = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
    $_SESSION['flash_error'] = 'Security session expired. Please try placing your order again.';
    header('Location: /surgical_equipment/user/shop.php');
    exit;
}

$cartDataJson = $_POST['cart_data'] ?? '[]';
$cartItems    = json_decode($cartDataJson, true);

if (empty($cartItems) || !is_array($cartItems)) {
    $_SESSION['flash_error'] = 'Your cart is empty. Please add items before placing an order.';
    header('Location: /surgical_equipment/user/shop.php');
    exit;
}

$userId          = (int) $_SESSION['user_id'];
$shippingName    = trim($_POST['shipping_name'] ?? '');
$shippingPhone   = trim($_POST['shipping_phone'] ?? '');
$shippingEmail   = trim($_POST['shipping_email'] ?? '');
$shippingAddress = trim($_POST['shipping_address'] ?? '');
$city            = trim($_POST['city'] ?? '');
$pincode         = trim($_POST['pincode'] ?? '');
$paymentMethod   = trim($_POST['payment_method'] ?? 'Pro-forma Medical Invoice / PO');
$notes           = trim($_POST['notes'] ?? '');

if ($shippingName === '' || $shippingAddress === '') {
    $_SESSION['flash_error'] = 'Please provide both Consignee Name and Delivery Address.';
    header('Location: /surgical_equipment/user/shop.php');
    exit;
}

try {
    $pdo = getDbConnection();
    $pdo->beginTransaction();

    // 1. Calculate totals
    $subtotal = 0.00;
    $validatedItems = [];

    foreach ($cartItems as $c) {
        $itemId  = (int)($c['id'] ?? 0);
        $itemQty = max(1, (int)($c['qty'] ?? 1));
        
        // Fetch current product from DB to ensure accurate price
        $pStmt = $pdo->prepare("SELECT id, name, equipment_code, unit_price, stock_quantity FROM equipment_inventory WHERE id = :id FOR UPDATE");
        $pStmt->execute([':id' => $itemId]);
        $prod = $pStmt->fetch(PDO::FETCH_ASSOC);

        if ($prod) {
            $unitPrice = (float)$prod['unit_price'];
            $itemTotal = $unitPrice * $itemQty;
            $subtotal += $itemTotal;

            $validatedItems[] = [
                'equipment_id' => (int)$prod['id'],
                'item_name'    => $prod['name'],
                'sku'          => $prod['equipment_code'],
                'unit_price'   => $unitPrice,
                'quantity'     => $itemQty,
                'total_price'  => $itemTotal
            ];

            // Decrement stock quantity
            $newStock = max(0, ((int)$prod['stock_quantity'] - $itemQty));
            $updStock = $pdo->prepare("UPDATE equipment_inventory SET stock_quantity = :stk WHERE id = :id");
            $updStock->execute([':stk' => $newStock, ':id' => $prod['id']]);
        }
    }

    if (empty($validatedItems)) {
        throw new Exception("No valid items in the cart to process.");
    }

    $taxAmount      = $subtotal * 0.09;
    $shippingAmount = 120.00;
    $grandTotal     = $subtotal + $taxAmount + $shippingAmount;

    // 2. Generate unique Order Number: ORD-2026-XXXX
    $randCode = strtoupper(bin2hex(random_bytes(2)));
    $orderNumber = 'ORD-' . date('Y') . '-' . rand(1000, 9999);

    // 3. Insert into orders table
    $ordStmt = $pdo->prepare("
        INSERT INTO orders (
            order_number, user_id, total_amount, tax_amount, shipping_amount, 
            status, payment_status, payment_method, shipping_name, shipping_phone, 
            shipping_email, shipping_address, city, pincode, notes, tracking_number
        ) VALUES (
            :ord_num, :uid, :tot, :tax, :ship,
            'pending', 'invoice_requested', :pm, :sname, :sphone,
            :semail, :saddr, :city, :pin, :notes, NULL
        )
    ");
    $ordStmt->execute([
        ':ord_num' => $orderNumber,
        ':uid'     => $userId,
        ':tot'     => $grandTotal,
        ':tax'     => $taxAmount,
        ':ship'    => $shippingAmount,
        ':pm'      => $paymentMethod,
        ':sname'   => $shippingName,
        ':sphone'  => $shippingPhone,
        ':semail'  => $shippingEmail,
        ':saddr'   => $shippingAddress,
        ':city'    => $city,
        ':pin'     => $pincode,
        ':notes'   => $notes
    ]);
    $newOrderId = (int) $pdo->lastInsertId();

    // 4. Insert into order_items table
    $itemStmt = $pdo->prepare("
        INSERT INTO order_items (
            order_id, equipment_id, item_name, sku, unit_price, quantity, total_price
        ) VALUES (
            :oid, :eid, :iname, :sku, :uprice, :qty, :tprice
        )
    ");

    foreach ($validatedItems as $vi) {
        $itemStmt->execute([
            ':oid'    => $newOrderId,
            ':eid'    => $vi['equipment_id'],
            ':iname'  => $vi['item_name'],
            ':sku'    => $vi['sku'],
            ':uprice' => $vi['unit_price'],
            ':qty'    => $vi['quantity'],
            ':tprice' => $vi['total_price']
        ]);
    }

    // 5. System audit trail
    $audit = $pdo->prepare('
        INSERT INTO system_audit_trails (user_id, action, entity_type, entity_id, details, ip_address, user_agent)
        VALUES (:uid, :action, :etype, :eid, :details, :ip, :ua)
    ');
    $audit->execute([
        ':uid'     => $userId,
        ':action'  => 'order.create',
        ':etype'   => 'orders',
        ':eid'     => $newOrderId,
        ':details' => json_encode(['order_number' => $orderNumber, 'total' => $grandTotal]),
        ':ip'      => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        ':ua'      => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
    ]);

    $pdo->commit();

    // Clear client-side cart via session flag
    $_SESSION['clear_cart_trigger'] = true;
    $_SESSION['flash_success'] = "Order #{$orderNumber} placed successfully! An official Pro-Forma Invoice has been generated.";

    header("Location: /surgical_equipment/user/view-order.php?id={$newOrderId}");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Order creation error: " . $e->getMessage());
    $_SESSION['flash_error'] = 'An error occurred while creating your order: ' . $e->getMessage();
    header('Location: /surgical_equipment/user/shop.php');
    exit;
}
