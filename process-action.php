<?php

require_once __DIR__ . '/config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /surgical_equipment/auth/login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$role   = $_SESSION['role'] ?? '';

$token = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    $_SESSION['flash_error'] = 'Invalid request token. Please try again.';
    header('Location: /surgical_equipment/dashboard.php');
    exit;
}

$action = trim($_POST['action'] ?? '');

$roleRestrictions = [
    'equipment_create'            => ['admin'],
    'equipment_update'            => ['admin'],
    'equipment_delete'            => ['admin'],
    'maintenance_create'          => ['admin','nurse','sterilisation_tech'],
    'maintenance_complete'        => ['admin','sterilisation_tech'],
    'sterilisation_create_batch'  => ['admin','sterilisation_tech'],
    'sterilisation_add_item'      => ['admin','sterilisation_tech'],
    'sterilisation_complete_batch'=> ['admin','sterilisation_tech'],
    'tracking_log'                => ['admin','nurse','sterilisation_tech'],
    'supplier_create'             => ['admin'],
    'supplier_update'             => ['admin'],
    'supplier_delete'             => ['admin'],
    'purchase_create'             => ['admin'],
    'purchase_delete'             => ['admin'],
    'super_category_create'       => ['admin'],
    'super_category_update'       => ['admin'],
    'super_category_delete'       => ['admin'],
    'category_create'             => ['admin'],
    'category_update'             => ['admin'],
    'category_delete'             => ['admin'],
    'sub_category_create'         => ['admin'],
    'sub_category_update'         => ['admin'],
    'sub_category_delete'         => ['admin'],
];

if (isset($roleRestrictions[$action]) && !in_array($role, $roleRestrictions[$action], true)) {
    $_SESSION['flash_error'] = 'You do not have permission to perform this action.';
    header('Location: /surgical_equipment/dashboard.php');
    exit;
}

try {
    $pdo = getDbConnection();

    $logAudit = function (string $auditAction, string $entityType, int $entityId, string $details = '') use ($pdo, $userId): void {
        $stmt = $pdo->prepare('
            INSERT INTO system_audit_trails (user_id, action, entity_type, entity_id, details, ip_address, user_agent)
            VALUES (:uid, :action, :etype, :eid, :details, :ip, :ua)
        ');
        $stmt->execute([
            ':uid'     => $userId,
            ':action'  => $auditAction,
            ':etype'   => $entityType,
            ':eid'     => $entityId,
            ':details' => $details,
            ':ip'      => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ':ua'      => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    };

    switch ($action) {

        case 'equipment_create': {
            $code     = trim($_POST['equipment_code'] ?? '');
            $name     = trim($_POST['name'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $status   = trim($_POST['status'] ?? 'available');
            $location = trim($_POST['current_location'] ?? '');
            $serial   = trim($_POST['serial_number'] ?? '');
            $purchase = trim($_POST['purchase_date'] ?? '');
            $warranty = trim($_POST['warranty_expiry'] ?? '');
            $notes    = trim($_POST['notes'] ?? '');

            // All fields are required
            if ($code === '' || $name === '' || $category === '' || $status === '' || $location === '' || $serial === '' || $purchase === '' || $warranty === '' || $notes === '') {
                throw new RuntimeException('All fields are required. Please fill in all equipment details.');
            }

            // Image file upload handling (Required on create)
            $imagePath = null;
            if (!isset($_FILES['product_image']) || $_FILES['product_image']['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Product image is required. Please upload a clear photograph of the equipment.');
            } else {
                $file = $_FILES['product_image'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                if (!in_array($ext, $allowed, true)) {
                    throw new RuntimeException('Invalid image format. Allowed formats: JPG, JPEG, PNG, WEBP.');
                }
                
                $uploadDir = __DIR__ . '/assets/images/photos';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                // Name file after sanitized product name or code
                $cleanBase = preg_replace('/[^\w\s\(\)#-]/u', '', $name);
                $cleanBase = trim($cleanBase) ?: preg_replace('/[^a-zA-Z0-9_-]/', '', $code);
                $targetFilename = $cleanBase . '.' . $ext;
                $destination = $uploadDir . '/' . $targetFilename;

                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    throw new RuntimeException('Failed to save uploaded product image to server.');
                }

                $imagePath = '/surgical_equipment/assets/images/photos/' . rawurlencode($targetFilename);
                // Append or prepend image note
                $notes = "Image: " . $imagePath . "\n" . $notes;
            }

            // Check if duplicate equipment code exists
            $dupStmt = $pdo->prepare('SELECT id FROM equipment_inventory WHERE equipment_code = :code LIMIT 1');
            $dupStmt->execute([':code' => $code]);
            if ($dupStmt->fetch()) {
                throw new RuntimeException("Equipment code/SKU \"$code\" already exists. Please use a unique SKU.");
            }

            $stmt = $pdo->prepare('
                INSERT INTO equipment_inventory
                    (equipment_code, name, category, status, current_location, serial_number, purchase_date, warranty_expiry, notes)
                VALUES (:code, :name, :cat, :status, :loc, :serial, :purchase, :warranty, :notes)
            ');
            $stmt->execute([
                ':code'     => $code,
                ':name'     => $name,
                ':cat'      => $category,
                ':status'   => $status,
                ':loc'      => $location,
                ':serial'   => $serial,
                ':purchase' => $purchase,
                ':warranty' => $warranty,
                ':notes'    => $notes,
            ]);
            $newId = (int) $pdo->lastInsertId();
            $logAudit('equipment.create', 'equipment_inventory', $newId, "Created: $name ($code)");
            $_SESSION['flash_success'] = "Equipment \"$name\" and photo added and published successfully.";
            header('Location: /surgical_equipment/equipment/equipment-details.php?id=' . $newId);
            exit;
        }

        case 'equipment_update': {
            $id       = (int) ($_POST['id'] ?? 0);
            $code     = trim($_POST['equipment_code'] ?? '');
            $name     = trim($_POST['name'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $status   = trim($_POST['status'] ?? 'available');
            $location = trim($_POST['current_location'] ?? '');
            $serial   = trim($_POST['serial_number'] ?? '');
            $purchase = trim($_POST['purchase_date'] ?? '');
            $warranty = trim($_POST['warranty_expiry'] ?? '');
            $notes    = trim($_POST['notes'] ?? '');

            if ($id <= 0) {
                throw new RuntimeException('Invalid equipment ID.');
            }

            // All fields are required
            if ($code === '' || $name === '' || $category === '' || $status === '' || $location === '' || $serial === '' || $purchase === '' || $warranty === '' || $notes === '') {
                throw new RuntimeException('All fields are required. Please fill in all equipment details.');
            }

            // Check for new image upload
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['product_image'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                if (!in_array($ext, $allowed, true)) {
                    throw new RuntimeException('Invalid image format. Allowed formats: JPG, JPEG, PNG, WEBP.');
                }
                
                $uploadDir = __DIR__ . '/assets/images/photos';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $cleanBase = preg_replace('/[^\w\s\(\)#-]/u', '', $name);
                $cleanBase = trim($cleanBase) ?: preg_replace('/[^a-zA-Z0-9_-]/', '', $code);
                $targetFilename = $cleanBase . '.' . $ext;
                $destination = $uploadDir . '/' . $targetFilename;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $imagePath = '/surgical_equipment/assets/images/photos/' . rawurlencode($targetFilename);
                    // Update notes with new image path
                    $cleanNotes = trim(preg_replace('/Image:\s*[^\n]+\n?/i', '', $notes));
                    $notes = "Image: " . $imagePath . "\n" . $cleanNotes;
                }
            }

            $stmt = $pdo->prepare('
                UPDATE equipment_inventory
                SET equipment_code=:code, name=:name, category=:cat, status=:status,
                    current_location=:loc, serial_number=:serial,
                    purchase_date=:purchase, warranty_expiry=:warranty, notes=:notes
                WHERE id=:id
            ');
            $stmt->execute([
                ':code'     => $code,
                ':name'     => $name,
                ':cat'      => $category,
                ':status'   => $status,
                ':loc'      => $location,
                ':serial'   => $serial,
                ':purchase' => $purchase,
                ':warranty' => $warranty,
                ':notes'    => $notes,
                ':id'       => $id,
            ]);
            $logAudit('equipment.update', 'equipment_inventory', $id, "Updated: $name ($code)");
            $_SESSION['flash_success'] = "Equipment \"$name\" updated successfully.";
            header('Location: /surgical_equipment/equipment/equipment-details.php?id=' . $id);
            exit;
        }

        case 'equipment_delete': {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new RuntimeException('Invalid equipment ID for deletion.');
            }

            // Fetch info before deleting for audit logging
            $checkStmt = $pdo->prepare('SELECT id, name, equipment_code FROM equipment_inventory WHERE id = :id LIMIT 1');
            $checkStmt->execute([':id' => $id]);
            $equipItem = $checkStmt->fetch();

            if (!$equipItem) {
                throw new RuntimeException('Equipment item not found or already deleted.');
            }

            $deleteStmt = $pdo->prepare('DELETE FROM equipment_inventory WHERE id = :id');
            $deleteStmt->execute([':id' => $id]);

            $logAudit('equipment.delete', 'equipment_inventory', $id, "Deleted: {$equipItem['name']} ({$equipItem['equipment_code']})");
            $_SESSION['flash_success'] = "Equipment \"{$equipItem['name']}\" ({$equipItem['equipment_code']}) deleted successfully.";
            header('Location: /surgical_equipment/equipment/inventory-list.php');
            exit;
        }

        case 'maintenance_create': {
            $equipId  = (int) ($_POST['equipment_id'] ?? 0);
            $type     = $_POST['type'] ?? 'routine_inspection';
            $desc     = trim($_POST['description'] ?? '');
            $notes    = trim($_POST['notes'] ?? '') ?: null;
            $schedDate = trim($_POST['scheduled_date'] ?? '') ?: null;
            $cost     = isset($_POST['cost']) && $_POST['cost'] !== '' ? (float) $_POST['cost'] : null;

            if ($equipId <= 0 || $desc === '') {
                throw new RuntimeException('Equipment and description are required.');
            }

            $stmt = $pdo->prepare('
                INSERT INTO maintenance_records
                    (equipment_id, type, status, description, notes, scheduled_date, cost, performed_by)
                VALUES (:eq, :type, \'pending\', :desc, :notes, :sched, :cost, :by)
            ');
            $stmt->execute([
                ':eq' => $equipId, ':type' => $type, ':desc' => $desc,
                ':notes' => $notes, ':sched' => $schedDate, ':cost' => $cost, ':by' => $userId,
            ]);
            $newId = (int) $pdo->lastInsertId();

            $pdo->prepare("UPDATE equipment_inventory SET status='under_maintenance' WHERE id=:id")
                ->execute([':id' => $equipId]);

            $logAudit('maintenance.create', 'maintenance_records', $newId, "Type: $type");
            $_SESSION['flash_success'] = 'Maintenance record logged.';
            header('Location: /surgical_equipment/maintenance/maintenance-schedule.php');
            exit;
        }

        case 'maintenance_complete': {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) throw new RuntimeException('Invalid record ID.');

            $stmt = $pdo->prepare("
                UPDATE maintenance_records SET status='completed', completed_date=CURDATE() WHERE id=:id
            ");
            $stmt->execute([':id' => $id]);

            $rec = $pdo->prepare('SELECT equipment_id FROM maintenance_records WHERE id=:id');
            $rec->execute([':id' => $id]);
            $row = $rec->fetch();
            if ($row) {
                $pending = (int) $pdo->prepare("
                    SELECT COUNT(*) FROM maintenance_records
                    WHERE equipment_id=:eid AND status IN ('pending','in_progress')
                ")->execute([':eid' => $row['equipment_id']]) ? 0 : 0;

                $chk = $pdo->prepare("SELECT COUNT(*) AS c FROM maintenance_records WHERE equipment_id=:eid AND status IN ('pending','in_progress')");
                $chk->execute([':eid' => $row['equipment_id']]);
                if ((int) $chk->fetch()['c'] === 0) {
                    $pdo->prepare("UPDATE equipment_inventory SET status='available' WHERE id=:id AND status='under_maintenance'")
                        ->execute([':id' => $row['equipment_id']]);
                }
            }

            $logAudit('maintenance.complete', 'maintenance_records', $id);
            $_SESSION['flash_success'] = 'Maintenance record marked as completed.';
            header('Location: /surgical_equipment/maintenance/maintenance-schedule.php');
            exit;
        }

        case 'sterilisation_create_batch': {
            $batchCode   = trim($_POST['batch_code'] ?? '');
            $cycleType   = $_POST['cycle_type'] ?? 'steam_autoclave';
            $cycleNumber = trim($_POST['cycle_number'] ?? '') ?: null;

            if ($batchCode === '') throw new RuntimeException('Batch code is required.');

            $stmt = $pdo->prepare('
                INSERT INTO sterilization_batches (batch_number, cycle_type, started_by, status)
                VALUES (:code, :cycle, :by, \'in_progress\')
            ');
            $stmt->execute([':code' => $batchCode, ':cycle' => $cycleType, ':by' => $userId]);
            $newId = (int) $pdo->lastInsertId();
            $logAudit('sterilisation.create_batch', 'sterilization_batches', $newId, "Batch: $batchCode");
            $_SESSION['flash_success'] = "Sterilisation batch \"$batchCode\" started.";
            header('Location: /surgical_equipment/sterilization/sterilization-log.php?batch_id=' . $newId);
            exit;
        }

        case 'sterilisation_add_item': {
            $batchId  = (int) ($_POST['batch_id'] ?? 0);
            $equipId  = (int) ($_POST['equipment_id'] ?? 0);
            if ($batchId <= 0 || $equipId <= 0) throw new RuntimeException('Invalid IDs.');

            $stmt = $pdo->prepare('INSERT INTO sterilization_batch_items (batch_id, equipment_id) VALUES (:bid, :eid)');
            $stmt->execute([':bid' => $batchId, ':eid' => $equipId]);

            $pdo->prepare("UPDATE equipment_inventory SET status='sterilising' WHERE id=:id")
                ->execute([':id' => $equipId]);

            $logAudit('sterilisation.add_item', 'sterilization_batch_items', (int)$pdo->lastInsertId());
            $_SESSION['flash_success'] = 'Item added to batch.';
            header('Location: /surgical_equipment/sterilization/sterilization-log.php?batch_id=' . $batchId);
            exit;
        }

        case 'sterilisation_complete_batch': {
            $batchId    = (int) ($_POST['id'] ?? 0);
            $chemPass   = isset($_POST['chemical_indicator'])  && $_POST['chemical_indicator']  !== '' ? (int)$_POST['chemical_indicator']  : null;
            $bioPass    = isset($_POST['biological_indicator']) && $_POST['biological_indicator'] !== '' ? (int)$_POST['biological_indicator'] : null;
            $notes      = trim($_POST['operator_notes'] ?? '') ?: null;

            if ($batchId <= 0) throw new RuntimeException('Invalid batch.');

            $chkItems = $pdo->prepare('SELECT COUNT(*) AS c FROM sterilization_batch_items WHERE batch_id = :id');
            $chkItems->execute([':id' => $batchId]);
            $itemCount = (int) $chkItems->fetch()['c'];

            if ($itemCount === 0) {
                throw new RuntimeException('Cannot complete a batch with no items. Please add at least one equipment item first.');
            }

            $stmt = $pdo->prepare("
                UPDATE sterilization_batches
                SET status='completed', completed_at=NOW(),
                    chemical_indicator_passed=:chem, biological_indicator_passed=:bio, notes=:notes
                WHERE id=:id
            ");
            $stmt->execute([':chem' => $chemPass, ':bio' => $bioPass, ':notes' => $notes, ':id' => $batchId]);

            $items = $pdo->prepare('SELECT equipment_id FROM sterilization_batch_items WHERE batch_id=:bid');
            $items->execute([':bid' => $batchId]);
            foreach ($items->fetchAll() as $item) {
                $pdo->prepare("UPDATE equipment_inventory SET status='available', last_sterilised_at=NOW() WHERE id=:id")
                    ->execute([':id' => $item['equipment_id']]);
            }

            $logAudit('sterilisation.complete_batch', 'sterilization_batches', $batchId);
            $_SESSION['flash_success'] = 'Sterilisation batch completed. All items set to Available.';
            header('Location: /surgical_equipment/sterilization/sterilization-log.php?batch_id=' . $batchId);
            exit;
        }

        case 'tracking_log': {
            $equipId  = (int) ($_POST['equipment_id'] ?? 0);
            $trackAct = $_POST['track_action'] ?? 'check_out';
            $fromLoc  = trim($_POST['from_location'] ?? '') ?: null;
            $toLoc    = trim($_POST['to_location'] ?? '') ?: null;
            $notes    = trim($_POST['notes'] ?? '') ?: null;

            if ($equipId <= 0) throw new RuntimeException('Invalid equipment.');

            $stmt = $pdo->prepare('
                INSERT INTO tracking_logs (equipment_id, user_id, action, from_location, to_location, notes)
                VALUES (:eq, :uid, :action, :from, :to, :notes)
            ');
            $stmt->execute([
                ':eq' => $equipId, ':uid' => $userId, ':action' => $trackAct,
                ':from' => $fromLoc, ':to' => $toLoc, ':notes' => $notes,
            ]);
            $newId = (int) $pdo->lastInsertId();

            if ($toLoc) {
                $pdo->prepare("UPDATE equipment_inventory SET current_location=:loc WHERE id=:id")
                    ->execute([':loc' => $toLoc, ':id' => $equipId]);
            }

            if ($trackAct === 'check_out') {
                $pdo->prepare("UPDATE equipment_inventory SET status='in_use' WHERE id=:id")
                    ->execute([':id' => $equipId]);
            } elseif ($trackAct === 'check_in') {
                $pdo->prepare("UPDATE equipment_inventory SET status='available' WHERE id=:id")
                    ->execute([':id' => $equipId]);
            }

            $logAudit('tracking.' . $trackAct, 'tracking_logs', $newId);
            $_SESSION['flash_success'] = 'Tracking event logged.';
            header('Location: /surgical_equipment/tracking/tracking-logs.php');
            exit;
        }

        case 'supplier_create': {
            $name    = trim($_POST['name'] ?? '');
            $contact = trim($_POST['contact_person'] ?? '') ?: null;
            $email   = trim($_POST['email'] ?? '') ?: null;
            $phone   = trim($_POST['phone'] ?? '') ?: null;
            $address = trim($_POST['address'] ?? '') ?: null;

            if ($name === '') {
                throw new RuntimeException('Supplier name is required.');
            }

            $stmt = $pdo->prepare('
                INSERT INTO suppliers (name, contact_person, email, phone, address)
                VALUES (:name, :contact, :email, :phone, :address)
            ');
            $stmt->execute([
                ':name' => $name, ':contact' => $contact,
                ':email' => $email, ':phone' => $phone, ':address' => $address
            ]);
            $newId = (int) $pdo->lastInsertId();
            $logAudit('supplier.create', 'suppliers', $newId, "Created: $name");
            $_SESSION['flash_success'] = "Supplier \"$name\" added successfully.";
            header('Location: /surgical_equipment/suppliers/suppliers.php');
            exit;
        }

        case 'supplier_update': {
            $id      = (int) ($_POST['id'] ?? 0);
            $name    = trim($_POST['name'] ?? '');
            $contact = trim($_POST['contact_person'] ?? '') ?: null;
            $email   = trim($_POST['email'] ?? '') ?: null;
            $phone   = trim($_POST['phone'] ?? '') ?: null;
            $address = trim($_POST['address'] ?? '') ?: null;

            if ($id <= 0 || $name === '') {
                throw new RuntimeException('Missing required fields.');
            }

            $stmt = $pdo->prepare('
                UPDATE suppliers
                SET name=:name, contact_person=:contact, email=:email, phone=:phone, address=:address
                WHERE id=:id
            ');
            $stmt->execute([
                ':name' => $name, ':contact' => $contact,
                ':email' => $email, ':phone' => $phone, ':address' => $address,
                ':id' => $id,
            ]);
            $logAudit('supplier.update', 'suppliers', $id, "Updated: $name");
            $_SESSION['flash_success'] = "Supplier \"$name\" updated successfully.";
            header('Location: /surgical_equipment/suppliers/suppliers.php');
            exit;
        }

        case 'supplier_delete': {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) throw new RuntimeException('Invalid supplier ID.');

            $stmt = $pdo->prepare('SELECT name FROM suppliers WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $name = $stmt->fetchColumn() ?: 'Unknown';

            $stmt = $pdo->prepare('DELETE FROM suppliers WHERE id = :id');
            $stmt->execute([':id' => $id]);

            $logAudit('supplier.delete', 'suppliers', $id, "Deleted: $name");
            $_SESSION['flash_success'] = "Supplier \"$name\" deleted successfully.";
            header('Location: /surgical_equipment/suppliers/suppliers.php');
            exit;
        }

        case 'purchase_create': {
            $poNum      = trim($_POST['purchase_order_number'] ?? '');
            $supplierId = (int) ($_POST['supplier_id'] ?? 0);
            $pDate      = trim($_POST['purchase_date'] ?? '');
            $totalCost  = (float) ($_POST['total_cost'] ?? 0.00);
            $status     = $_POST['status'] ?? 'completed';
            $notes      = trim($_POST['notes'] ?? '') ?: null;

            if ($poNum === '' || $supplierId <= 0 || $pDate === '') {
                throw new RuntimeException('PO number, supplier, and purchase date are required.');
            }

            $pdo->beginTransaction();

            try {
                $stmt = $pdo->prepare('
                    INSERT INTO purchases (purchase_order_number, supplier_id, purchase_date, total_cost, status, notes)
                    VALUES (:po, :sup, :pdate, :cost, :status, :notes)
                ');
                $stmt->execute([
                    ':po' => $poNum, ':sup' => $supplierId, ':pdate' => $pDate,
                    ':cost' => $totalCost, ':status' => $status, ':notes' => $notes
                ]);
                $purchaseId = (int) $pdo->lastInsertId();

                $autoCreate = isset($_POST['auto_create_items']) && $_POST['auto_create_items'] === '1';
                if ($autoCreate) {
                    $equipName     = trim($_POST['equip_name'] ?? '');
                    $equipCategory = trim($_POST['equip_category'] ?? '');
                    $codePrefix    = trim($_POST['code_prefix'] ?? '');
                    $startNum      = (int) ($_POST['start_num'] ?? 2001);
                    $quantity      = (int) ($_POST['quantity'] ?? 1);
                    $wMonths       = (int) ($_POST['warranty_months'] ?? 12);

                    $wExpiry = $wMonths > 0 ? date('Y-m-d', strtotime("+{$wMonths} months")) : null;

                    for ($i = 0; $i < $quantity; $i++) {
                        $eqCode = $codePrefix . str_pad($startNum + $i, 4, '0', STR_PAD_LEFT);
                        $stmt2 = $pdo->prepare('
                            INSERT INTO equipment_inventory
                                (equipment_code, name, category, status, current_location, purchase_date, warranty_expiry, notes)
                            VALUES (:code, :name, :cat, :status, :loc, :purchase, :warranty, :notes)
                        ');
                        $stmt2->execute([
                            ':code'     => $eqCode,
                            ':name'     => $equipName,
                            ':cat'      => $equipCategory,
                            ':status'   => 'available',
                            ':loc'      => 'Main Store',
                            ':purchase' => $pDate,
                            ':warranty' => $wExpiry,
                            ':notes'    => "Auto-created from PO: $poNum",
                        ]);
                    }
                }

                $pdo->commit();
                $logAudit('purchase.create', 'purchases', $purchaseId, "PO: $poNum");
                $_SESSION['flash_success'] = "Purchase order \"$poNum\" recorded successfully.";
                header('Location: /surgical_equipment/purchases/purchases.php');
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        }


        case 'purchase_delete': {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) throw new RuntimeException('Invalid purchase ID.');

            $stmt = $pdo->prepare('SELECT purchase_order_number FROM purchases WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $poNum = $stmt->fetchColumn() ?: 'Unknown';

            $stmt = $pdo->prepare('DELETE FROM purchases WHERE id = :id');
            $stmt->execute([':id' => $id]);

            $logAudit('purchase.delete', 'purchases', $id, "Deleted PO: $poNum");
            $_SESSION['flash_success'] = "Purchase PO \"$poNum\" deleted successfully.";
            header('Location: /surgical_equipment/purchases/purchases.php');
            exit;
        }

        case 'super_category_create': {
            $name        = trim($_POST['name'] ?? '');
            $slug        = trim($_POST['slug'] ?? '');
            $description = trim($_POST['description'] ?? '') ?: null;
            $icon        = trim($_POST['icon'] ?? '') ?: 'folder';
            $status      = in_array($_POST['status'] ?? 'active', ['active', 'inactive'], true) ? $_POST['status'] : 'active';
            $sortOrder   = (int) ($_POST['sort_order'] ?? 0);

            if ($name === '') {
                throw new RuntimeException('Super category name is required.');
            }
            if ($slug === '') {
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
            }

            // Check duplicate name or slug
            $chk = $pdo->prepare('SELECT id FROM super_categories WHERE name = :name OR slug = :slug LIMIT 1');
            $chk->execute([':name' => $name, ':slug' => $slug]);
            if ($chk->fetch()) {
                throw new RuntimeException("A super category with the name \"$name\" or slug \"$slug\" already exists.");
            }

            $stmt = $pdo->prepare('
                INSERT INTO super_categories (name, slug, description, icon, status, sort_order)
                VALUES (:name, :slug, :desc, :icon, :status, :sort)
            ');
            $stmt->execute([
                ':name'   => $name,
                ':slug'   => $slug,
                ':desc'   => $description,
                ':icon'   => $icon,
                ':status' => $status,
                ':sort'   => $sortOrder
            ]);
            $newId = (int) $pdo->lastInsertId();
            $logAudit('super_category.create', 'super_categories', $newId, "Created: $name ($slug)");
            $_SESSION['flash_success'] = "Super Category \"$name\" created successfully.";
            header('Location: /surgical_equipment/categories/super-categories.php');
            exit;
        }

        case 'super_category_update': {
            $id          = (int) ($_POST['id'] ?? 0);
            $name        = trim($_POST['name'] ?? '');
            $slug        = trim($_POST['slug'] ?? '');
            $description = trim($_POST['description'] ?? '') ?: null;
            $icon        = trim($_POST['icon'] ?? '') ?: 'folder';
            $status      = in_array($_POST['status'] ?? 'active', ['active', 'inactive'], true) ? $_POST['status'] : 'active';
            $sortOrder   = (int) ($_POST['sort_order'] ?? 0);

            if ($id <= 0 || $name === '') {
                throw new RuntimeException('Invalid super category ID or name.');
            }
            if ($slug === '') {
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
            }

            // Check duplicate on other records
            $chk = $pdo->prepare('SELECT id FROM super_categories WHERE (name = :name OR slug = :slug) AND id != :id LIMIT 1');
            $chk->execute([':name' => $name, ':slug' => $slug, ':id' => $id]);
            if ($chk->fetch()) {
                throw new RuntimeException("Another super category with the name \"$name\" or slug \"$slug\" already exists.");
            }

            $stmt = $pdo->prepare('
                UPDATE super_categories
                SET name = :name, slug = :slug, description = :desc, icon = :icon, status = :status, sort_order = :sort
                WHERE id = :id
            ');
            $stmt->execute([
                ':name'   => $name,
                ':slug'   => $slug,
                ':desc'   => $description,
                ':icon'   => $icon,
                ':status' => $status,
                ':sort'   => $sortOrder,
                ':id'     => $id
            ]);
            $logAudit('super_category.update', 'super_categories', $id, "Updated: $name ($slug)");
            $_SESSION['flash_success'] = "Super Category \"$name\" updated successfully.";
            header('Location: /surgical_equipment/categories/super-categories.php');
            exit;
        }

        case 'super_category_delete': {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) throw new RuntimeException('Invalid super category ID.');

            $stmt = $pdo->prepare('SELECT name FROM super_categories WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $name = $stmt->fetchColumn() ?: 'Unknown';

            $stmt = $pdo->prepare('DELETE FROM super_categories WHERE id = :id');
            $stmt->execute([':id' => $id]);

            $logAudit('super_category.delete', 'super_categories', $id, "Deleted: $name");
            $_SESSION['flash_success'] = "Super Category \"$name\" and related categories deleted successfully.";
            header('Location: /surgical_equipment/categories/super-categories.php');
            exit;
        }

        case 'category_create': {
            $superId     = (int) ($_POST['super_category_id'] ?? 0);
            $name        = trim($_POST['name'] ?? '');
            $slug        = trim($_POST['slug'] ?? '');
            $description = trim($_POST['description'] ?? '') ?: null;
            $status      = in_array($_POST['status'] ?? 'active', ['active', 'inactive'], true) ? $_POST['status'] : 'active';
            $sortOrder   = (int) ($_POST['sort_order'] ?? 0);

            if ($superId <= 0 || $name === '') {
                throw new RuntimeException('Super Category and Category name are required.');
            }
            if ($slug === '') {
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
            }

            // Check duplicate
            $chk = $pdo->prepare('SELECT id FROM categories WHERE (super_category_id = :sid AND name = :name) OR slug = :slug LIMIT 1');
            $chk->execute([':sid' => $superId, ':name' => $name, ':slug' => $slug]);
            if ($chk->fetch()) {
                throw new RuntimeException("A category with this name under the selected Super Category or slug \"$slug\" already exists.");
            }

            $stmt = $pdo->prepare('
                INSERT INTO categories (super_category_id, name, slug, description, status, sort_order)
                VALUES (:sid, :name, :slug, :desc, :status, :sort)
            ');
            $stmt->execute([
                ':sid'    => $superId,
                ':name'   => $name,
                ':slug'   => $slug,
                ':desc'   => $description,
                ':status' => $status,
                ':sort'   => $sortOrder
            ]);
            $newId = (int) $pdo->lastInsertId();
            $logAudit('category.create', 'categories', $newId, "Created: $name ($slug)");
            $_SESSION['flash_success'] = "Category \"$name\" created successfully.";
            header('Location: /surgical_equipment/categories/categories.php');
            exit;
        }

        case 'category_update': {
            $id          = (int) ($_POST['id'] ?? 0);
            $superId     = (int) ($_POST['super_category_id'] ?? 0);
            $name        = trim($_POST['name'] ?? '');
            $slug        = trim($_POST['slug'] ?? '');
            $description = trim($_POST['description'] ?? '') ?: null;
            $status      = in_array($_POST['status'] ?? 'active', ['active', 'inactive'], true) ? $_POST['status'] : 'active';
            $sortOrder   = (int) ($_POST['sort_order'] ?? 0);

            if ($id <= 0 || $superId <= 0 || $name === '') {
                throw new RuntimeException('Invalid category ID, super category, or name.');
            }
            if ($slug === '') {
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
            }

            // Check duplicate on other records
            $chk = $pdo->prepare('SELECT id FROM categories WHERE ((super_category_id = :sid AND name = :name) OR slug = :slug) AND id != :id LIMIT 1');
            $chk->execute([':sid' => $superId, ':name' => $name, ':slug' => $slug, ':id' => $id]);
            if ($chk->fetch()) {
                throw new RuntimeException("Another category with this name under the selected Super Category or slug \"$slug\" already exists.");
            }

            $stmt = $pdo->prepare('
                UPDATE categories
                SET super_category_id = :sid, name = :name, slug = :slug, description = :desc, status = :status, sort_order = :sort
                WHERE id = :id
            ');
            $stmt->execute([
                ':sid'    => $superId,
                ':name'   => $name,
                ':slug'   => $slug,
                ':desc'   => $description,
                ':status' => $status,
                ':sort'   => $sortOrder,
                ':id'     => $id
            ]);
            $logAudit('category.update', 'categories', $id, "Updated: $name ($slug)");
            $_SESSION['flash_success'] = "Category \"$name\" updated successfully.";
            header('Location: /surgical_equipment/categories/categories.php');
            exit;
        }

        case 'category_delete': {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) throw new RuntimeException('Invalid category ID.');

            $stmt = $pdo->prepare('SELECT name FROM categories WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $name = $stmt->fetchColumn() ?: 'Unknown';

            $stmt = $pdo->prepare('DELETE FROM categories WHERE id = :id');
            $stmt->execute([':id' => $id]);

            $logAudit('category.delete', 'categories', $id, "Deleted: $name");
            $_SESSION['flash_success'] = "Category \"$name\" and related sub categories deleted successfully.";
            header('Location: /surgical_equipment/categories/categories.php');
            exit;
        }

        case 'sub_category_create': {
            $catId       = (int) ($_POST['category_id'] ?? 0);
            $name        = trim($_POST['name'] ?? '');
            $slug        = trim($_POST['slug'] ?? '');
            $description = trim($_POST['description'] ?? '') ?: null;
            $status      = in_array($_POST['status'] ?? 'active', ['active', 'inactive'], true) ? $_POST['status'] : 'active';
            $sortOrder   = (int) ($_POST['sort_order'] ?? 0);

            if ($catId <= 0 || $name === '') {
                throw new RuntimeException('Category and Sub Category name are required.');
            }
            if ($slug === '') {
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
            }

            // Check duplicate
            $chk = $pdo->prepare('SELECT id FROM sub_categories WHERE (category_id = :cid AND name = :name) OR slug = :slug LIMIT 1');
            $chk->execute([':cid' => $catId, ':name' => $name, ':slug' => $slug]);
            if ($chk->fetch()) {
                throw new RuntimeException("A sub category with this name under the selected Category or slug \"$slug\" already exists.");
            }

            $stmt = $pdo->prepare('
                INSERT INTO sub_categories (category_id, name, slug, description, status, sort_order)
                VALUES (:cid, :name, :slug, :desc, :status, :sort)
            ');
            $stmt->execute([
                ':cid'    => $catId,
                ':name'   => $name,
                ':slug'   => $slug,
                ':desc'   => $description,
                ':status' => $status,
                ':sort'   => $sortOrder
            ]);
            $newId = (int) $pdo->lastInsertId();
            $logAudit('sub_category.create', 'sub_categories', $newId, "Created: $name ($slug)");
            $_SESSION['flash_success'] = "Sub Category \"$name\" created successfully.";
            header('Location: /surgical_equipment/categories/sub-categories.php');
            exit;
        }

        case 'sub_category_update': {
            $id          = (int) ($_POST['id'] ?? 0);
            $catId       = (int) ($_POST['category_id'] ?? 0);
            $name        = trim($_POST['name'] ?? '');
            $slug        = trim($_POST['slug'] ?? '');
            $description = trim($_POST['description'] ?? '') ?: null;
            $status      = in_array($_POST['status'] ?? 'active', ['active', 'inactive'], true) ? $_POST['status'] : 'active';
            $sortOrder   = (int) ($_POST['sort_order'] ?? 0);

            if ($id <= 0 || $catId <= 0 || $name === '') {
                throw new RuntimeException('Invalid sub category ID, category, or name.');
            }
            if ($slug === '') {
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
            }

            // Check duplicate on other records
            $chk = $pdo->prepare('SELECT id FROM sub_categories WHERE ((category_id = :cid AND name = :name) OR slug = :slug) AND id != :id LIMIT 1');
            $chk->execute([':cid' => $catId, ':name' => $name, ':slug' => $slug, ':id' => $id]);
            if ($chk->fetch()) {
                throw new RuntimeException("Another sub category with this name under the selected Category or slug \"$slug\" already exists.");
            }

            $stmt = $pdo->prepare('
                UPDATE sub_categories
                SET category_id = :cid, name = :name, slug = :slug, description = :desc, status = :status, sort_order = :sort
                WHERE id = :id
            ');
            $stmt->execute([
                ':cid'    => $catId,
                ':name'   => $name,
                ':slug'   => $slug,
                ':desc'   => $description,
                ':status' => $status,
                ':sort'   => $sortOrder,
                ':id'     => $id
            ]);
            $logAudit('sub_category.update', 'sub_categories', $id, "Updated: $name ($slug)");
            $_SESSION['flash_success'] = "Sub Category \"$name\" updated successfully.";
            header('Location: /surgical_equipment/categories/sub-categories.php');
            exit;
        }

        case 'sub_category_delete': {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) throw new RuntimeException('Invalid sub category ID.');

            $stmt = $pdo->prepare('SELECT name FROM sub_categories WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $name = $stmt->fetchColumn() ?: 'Unknown';

            $stmt = $pdo->prepare('DELETE FROM sub_categories WHERE id = :id');
            $stmt->execute([':id' => $id]);

            $logAudit('sub_category.delete', 'sub_categories', $id, "Deleted: $name");
            $_SESSION['flash_success'] = "Sub Category \"$name\" deleted successfully.";
            header('Location: /surgical_equipment/categories/sub-categories.php');
            exit;
        }

        default:
            $_SESSION['flash_error'] = 'Unknown action.';
            header('Location: /surgical_equipment/dashboard.php');
            exit;
    }

} catch (RuntimeException $e) {
    $_SESSION['flash_error'] = $e->getMessage();
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/surgical_equipment/dashboard.php'));
    exit;
} catch (PDOException $e) {
    error_log($e->getMessage());
    $_SESSION['flash_error'] = 'A database error occurred. Please try again.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/surgical_equipment/dashboard.php'));
    exit;
}