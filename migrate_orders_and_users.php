<?php
require_once __DIR__ . '/config/db.php';

try {
    $pdo = getDbConnection();
    echo "Connected to database successfully.\n";

    // 1. Update users table role enum and add profile fields
    echo "Updating users table schema...\n";
    $pdo->exec("
        ALTER TABLE `users` 
        MODIFY `role` ENUM('admin', 'surgeon', 'nurse', 'sterilisation_tech', 'auditor', 'registered_user', 'customer') NOT NULL DEFAULT 'customer';
    ");

    // Add columns to users if they don't exist
    $userCols = $pdo->query("SHOW COLUMNS FROM `users`")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('phone', $userCols)) {
        $pdo->exec("ALTER TABLE `users` ADD `phone` VARCHAR(30) DEFAULT NULL AFTER `email`");
    }
    if (!in_array('organization', $userCols)) {
        $pdo->exec("ALTER TABLE `users` ADD `organization` VARCHAR(150) DEFAULT NULL AFTER `phone`");
    }
    if (!in_array('shipping_address', $userCols)) {
        $pdo->exec("ALTER TABLE `users` ADD `shipping_address` TEXT DEFAULT NULL AFTER `organization`");
    }
    if (!in_array('city', $userCols)) {
        $pdo->exec("ALTER TABLE `users` ADD `city` VARCHAR(100) DEFAULT NULL AFTER `shipping_address`");
    }
    if (!in_array('pincode', $userCols)) {
        $pdo->exec("ALTER TABLE `users` ADD `pincode` VARCHAR(20) DEFAULT NULL AFTER `city`");
    }
    if (!in_array('status', $userCols)) {
        $pdo->exec("ALTER TABLE `users` ADD `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active' AFTER `role`");
    }

    // 2. Update equipment_inventory table with unit_price & stock_quantity
    echo "Updating equipment_inventory table schema...\n";
    $equipCols = $pdo->query("SHOW COLUMNS FROM `equipment_inventory`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('unit_price', $equipCols)) {
        $pdo->exec("ALTER TABLE `equipment_inventory` ADD `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 450.00 AFTER `status`");
    }
    if (!in_array('stock_quantity', $equipCols)) {
        $pdo->exec("ALTER TABLE `equipment_inventory` ADD `stock_quantity` INT NOT NULL DEFAULT 25 AFTER `unit_price`");
    }
    if (!in_array('image_url', $equipCols)) {
        $pdo->exec("ALTER TABLE `equipment_inventory` ADD `image_url` VARCHAR(255) DEFAULT NULL AFTER `notes`");
    }

    // Populate prices for existing products
    $pdo->exec("UPDATE `equipment_inventory` SET `unit_price` = 320.00, `stock_quantity` = 45 WHERE `id` = 1");
    $pdo->exec("UPDATE `equipment_inventory` SET `unit_price` = 480.00, `stock_quantity` = 30 WHERE `id` = 2");
    $pdo->exec("UPDATE `equipment_inventory` SET `unit_price` = 3200.00, `stock_quantity` = 8 WHERE `id` = 3");
    $pdo->exec("UPDATE `equipment_inventory` SET `unit_price` = 5500.00, `stock_quantity` = 12 WHERE `id` = 4");
    $pdo->exec("UPDATE `equipment_inventory` SET `unit_price` = 210.00, `stock_quantity` = 60 WHERE `id` = 5");
    $pdo->exec("UPDATE `equipment_inventory` SET `unit_price` = 290.00, `stock_quantity` = 40 WHERE `id` = 6");
    $pdo->exec("UPDATE `equipment_inventory` SET `unit_price` = 890.00, `stock_quantity` = 15 WHERE `id` = 7");
    $pdo->exec("UPDATE `equipment_inventory` SET `unit_price` = 340.00, `stock_quantity` = 35 WHERE `id` = 8");
    $pdo->exec("UPDATE `equipment_inventory` SET `unit_price` = 7400.00, `stock_quantity` = 6 WHERE `id` = 9");
    $pdo->exec("UPDATE `equipment_inventory` SET `unit_price` = 620.00, `stock_quantity` = 28 WHERE `id` = 10");

    // 3. Create orders table
    echo "Creating orders table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `orders` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `order_number` VARCHAR(50) NOT NULL UNIQUE,
            `user_id` INT NOT NULL,
            `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `shipping_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `status` ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
            `payment_status` ENUM('unpaid', 'paid', 'invoice_requested', 'cod') NOT NULL DEFAULT 'invoice_requested',
            `payment_method` VARCHAR(50) DEFAULT 'Pro-forma Medical Invoice / PO',
            `shipping_name` VARCHAR(100) NOT NULL,
            `shipping_phone` VARCHAR(30) DEFAULT NULL,
            `shipping_email` VARCHAR(100) DEFAULT NULL,
            `shipping_address` TEXT NOT NULL,
            `city` VARCHAR(100) DEFAULT NULL,
            `pincode` VARCHAR(20) DEFAULT NULL,
            `notes` TEXT DEFAULT NULL,
            `tracking_number` VARCHAR(100) DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 4. Create order_items table
    echo "Creating order_items table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `order_items` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `order_id` INT NOT NULL,
            `equipment_id` INT DEFAULT NULL,
            `item_name` VARCHAR(150) NOT NULL,
            `sku` VARCHAR(50) DEFAULT NULL,
            `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `quantity` INT NOT NULL DEFAULT 1,
            `total_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_order_items_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment_inventory`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 5. Seed sample registered customer user
    echo "Seeding sample registered customer user...\n";
    $hashedPass = password_hash('Password123!', PASSWORD_BCRYPT);
    $userStmt = $pdo->prepare("
        INSERT INTO `users` (`username`, `password_hash`, `full_name`, `email`, `phone`, `organization`, `shipping_address`, `city`, `pincode`, `role`, `status`)
        VALUES (:u, :p, :fn, :em, :ph, :org, :addr, :city, :pin, 'registered_user', 'active')
        ON DUPLICATE KEY UPDATE 
            `full_name` = VALUES(`full_name`),
            `email` = VALUES(`email`),
            `phone` = VALUES(`phone`),
            `organization` = VALUES(`organization`),
            `shipping_address` = VALUES(`shipping_address`),
            `city` = VALUES(`city`),
            `pincode` = VALUES(`pincode`);
    ");
    $userStmt->execute([
        ':u'    => 'dr_rachel',
        ':p'    => $hashedPass,
        ':fn'   => 'Dr. Rachel Adams (Chief Surgeon)',
        ':em'   => 'rachel.adams@metrohealth.org',
        ':ph'   => '+1 (555) 349-2810',
        ':org'  => 'Metro Multi-Speciality Surgical Hospital',
        ':addr' => '742 Healthcare Boulevard, Surgical Wing Block C',
        ':city' => 'Chicago, IL',
        ':pin'  => '60611'
    ]);

    // Retrieve user ID for dr_rachel
    $drIdStmt = $pdo->prepare("SELECT id FROM users WHERE username = 'dr_rachel' LIMIT 1");
    $drIdStmt->execute();
    $userId = (int)$drIdStmt->fetchColumn();

    if ($userId > 0) {
        echo "Found customer user ID: $userId. Seeding sample customer orders...\n";
        
        // Order 1: Delivered
        $ord1Check = $pdo->prepare("SELECT id FROM orders WHERE order_number = 'ORD-2026-8742'");
        $ord1Check->execute();
        if (!$ord1Check->fetch()) {
            $ord1 = $pdo->prepare("
                INSERT INTO `orders` (`order_number`, `user_id`, `total_amount`, `tax_amount`, `shipping_amount`, `status`, `payment_status`, `payment_method`, `shipping_name`, `shipping_phone`, `shipping_email`, `shipping_address`, `city`, `pincode`, `notes`, `tracking_number`, `created_at`)
                VALUES ('ORD-2026-8742', :uid, 12900.00, 1161.00, 250.00, 'delivered', 'paid', 'Hospital Purchase Order / Net 30', 'Dr. Rachel Adams', '+1 (555) 349-2810', 'rachel.adams@metrohealth.org', '742 Healthcare Boulevard, Surgical Wing Block C', 'Chicago, IL', '60611', 'Delivered and verified by Biomedical Dept.', 'TRK-DHL-9921841', NOW() - INTERVAL 12 DAY)
            ");
            $ord1->execute([':uid' => $userId]);
            $ord1Id = (int)$pdo->lastInsertId();

            $pdo->exec("
                INSERT INTO `order_items` (`order_id`, `equipment_id`, `item_name`, `sku`, `unit_price`, `quantity`, `total_price`)
                VALUES 
                    ($ord1Id, 4, 'Orthopedic Bone Drill Set', 'EQ-1004', 5500.00, 1, 5500.00),
                    ($ord1Id, 9, 'Stryker System 8 Oscillating Saw', 'EQ-1009', 7400.00, 1, 7400.00)
            ");
        }

        // Order 2: Processing
        $ord2Check = $pdo->prepare("SELECT id FROM orders WHERE order_number = 'ORD-2026-8801'");
        $ord2Check->execute();
        if (!$ord2Check->fetch()) {
            $ord2 = $pdo->prepare("
                INSERT INTO `orders` (`order_number`, `user_id`, `total_amount`, `tax_amount`, `shipping_amount`, `status`, `payment_status`, `payment_method`, `shipping_name`, `shipping_phone`, `shipping_email`, `shipping_address`, `city`, `pincode`, `notes`, `tracking_number`, `created_at`)
                VALUES ('ORD-2026-8801', :uid, 2270.00, 204.30, 120.00, 'processing', 'invoice_requested', 'Pro-forma Medical Invoice', 'Dr. Rachel Adams', '+1 (555) 349-2810', 'rachel.adams@metrohealth.org', '742 Healthcare Boulevard, Surgical Wing Block C', 'Chicago, IL', '60611', 'Priority dispatch for upcoming cardiovascular theater schedule.', 'TRK-FEDEX-488201', NOW() - INTERVAL 2 DAY)
            ");
            $ord2->execute([':uid' => $userId]);
            $ord2Id = (int)$pdo->lastInsertId();

            $pdo->exec("
                INSERT INTO `order_items` (`order_id`, `equipment_id`, `item_name`, `sku`, `unit_price`, `quantity`, `total_price`)
                VALUES 
                    ($ord2Id, 1, 'Titanium Micro-Forceps #4', 'EQ-1001', 320.00, 2, 640.00),
                    ($ord2Id, 2, 'Laparoscopic Curved Scissors 5mm', 'EQ-1002', 480.00, 2, 960.00),
                    ($ord2Id, 8, 'Weitlaner Retractor 3x4 Teeth', 'EQ-1008', 340.00, 1, 340.00),
                    ($ord2Id, 5, 'Metzenbaum Dissecting Scissors 7\"', 'EQ-1005', 210.00, 1, 210.00)
            ");
        }

        // Order 3: Pending
        $ord3Check = $pdo->prepare("SELECT id FROM orders WHERE order_number = 'ORD-2026-8915'");
        $ord3Check->execute();
        if (!$ord3Check->fetch()) {
            $ord3 = $pdo->prepare("
                INSERT INTO `orders` (`order_number`, `user_id`, `total_amount`, `tax_amount`, `shipping_amount`, `status`, `payment_status`, `payment_method`, `shipping_name`, `shipping_phone`, `shipping_email`, `shipping_address`, `city`, `pincode`, `notes`, `tracking_number`, `created_at`)
                VALUES ('ORD-2026-8915', :uid, 3820.00, 343.80, 150.00, 'pending', 'invoice_requested', 'Hospital Purchase Order / Net 30', 'Dr. Rachel Adams', '+1 (555) 349-2810', 'rachel.adams@metrohealth.org', '742 Healthcare Boulevard, Surgical Wing Block C', 'Chicago, IL', '60611', 'Awaiting administrative verification and PO sign-off.', NULL, NOW() - INTERVAL 3 HOUR)
            ");
            $ord3->execute([':uid' => $userId]);
            $ord3Id = (int)$pdo->lastInsertId();

            $pdo->exec("
                INSERT INTO `order_items` (`order_id`, `equipment_id`, `item_name`, `sku`, `unit_price`, `quantity`, `total_price`)
                VALUES 
                    ($ord3Id, 3, 'Harmonic Scalpel Handpiece', 'EQ-1003', 3200.00, 1, 3200.00),
                    ($ord3Id, 10, 'Bipolar Coagulation Forceps', 'EQ-1010', 620.00, 1, 620.00)
            ");
        }
    }

    echo "Migration completed successfully!\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
