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
            $status   = $_POST['status'] ?? 'available';
            $location = trim($_POST['current_location'] ?? 'Main Store');
            $serial   = trim($_POST['serial_number'] ?? '') ?: null;
            $purchase = trim($_POST['purchase_date'] ?? '') ?: null;
            $warranty = trim($_POST['warranty_expiry'] ?? '') ?: null;
            $notes    = trim($_POST['notes'] ?? '') ?: null;

            if ($code === '' || $name === '' || $category === '') {
                throw new RuntimeException('Code, name, and category are required.');
            }

            $stmt = $pdo->prepare('
                INSERT INTO equipment_inventory
                    (equipment_code, name, category, status, current_location, serial_number, purchase_date, warranty_expiry, notes)
                VALUES (:code, :name, :cat, :status, :loc, :serial, :purchase, :warranty, :notes)
            ');
            $stmt->execute([
                ':code' => $code, ':name' => $name, ':cat' => $category,
                ':status' => $status, ':loc' => $location, ':serial' => $serial,
                ':purchase' => $purchase, ':warranty' => $warranty, ':notes' => $notes,
            ]);
            $newId = (int) $pdo->lastInsertId();
            $logAudit('equipment.create', 'equipment_inventory', $newId, "Created: $name ($code)");
            $_SESSION['flash_success'] = "Equipment \"$name\" added successfully.";
            header('Location: /surgical_equipment/equipment/equipment-details.php?id=' . $newId);
            exit;
        }

        case 'equipment_update': {
            $id       = (int) ($_POST['id'] ?? 0);
            $code     = trim($_POST['equipment_code'] ?? '');
            $name     = trim($_POST['name'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $status   = $_POST['status'] ?? 'available';
            $location = trim($_POST['current_location'] ?? 'Main Store');
            $serial   = trim($_POST['serial_number'] ?? '') ?: null;
            $purchase = trim($_POST['purchase_date'] ?? '') ?: null;
            $warranty = trim($_POST['warranty_expiry'] ?? '') ?: null;
            $notes    = trim($_POST['notes'] ?? '') ?: null;

            if ($id <= 0 || $code === '' || $name === '' || $category === '') {
                throw new RuntimeException('Missing required fields.');
            }

            $stmt = $pdo->prepare('
                UPDATE equipment_inventory
                SET equipment_code=:code, name=:name, category=:cat, status=:status,
                    current_location=:loc, serial_number=:serial,
                    purchase_date=:purchase, warranty_expiry=:warranty, notes=:notes
                WHERE id=:id
            ');
            $stmt->execute([
                ':code' => $code, ':name' => $name, ':cat' => $category,
                ':status' => $status, ':loc' => $location, ':serial' => $serial,
                ':purchase' => $purchase, ':warranty' => $warranty, ':notes' => $notes,
                ':id' => $id,
            ]);
            $logAudit('equipment.update', 'equipment_inventory', $id, "Updated: $name ($code)");
            $_SESSION['flash_success'] = "Equipment updated successfully.";
            header('Location: /surgical_equipment/equipment/equipment-details.php?id=' . $id);
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