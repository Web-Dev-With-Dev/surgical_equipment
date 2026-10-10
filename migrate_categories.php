<?php

require_once __DIR__ . '/config/db.php';

echo "=== APEX Surgical Equipment - Category System Migration ===\n\n";

try {
    $pdo = getDbConnection();

    // 1. Create super_categories
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `super_categories` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `name` VARCHAR(150) NOT NULL UNIQUE,
          `slug` VARCHAR(150) NOT NULL UNIQUE,
          `description` TEXT DEFAULT NULL,
          `icon` VARCHAR(100) DEFAULT NULL,
          `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
          `sort_order` INT NOT NULL DEFAULT 0,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "[OK] Table 'super_categories' ready.\n";

    // 2. Create categories
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `categories` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `super_category_id` INT NOT NULL,
          `name` VARCHAR(150) NOT NULL,
          `slug` VARCHAR(150) NOT NULL,
          `description` TEXT DEFAULT NULL,
          `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
          `sort_order` INT NOT NULL DEFAULT 0,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          CONSTRAINT `fk_cat_super` FOREIGN KEY (`super_category_id`) REFERENCES `super_categories`(`id`) ON DELETE CASCADE,
          UNIQUE KEY `unique_super_cat_name` (`super_category_id`, `name`),
          UNIQUE KEY `unique_cat_slug` (`slug`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "[OK] Table 'categories' ready.\n";

    // 3. Create sub_categories
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `sub_categories` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `category_id` INT NOT NULL,
          `name` VARCHAR(150) NOT NULL,
          `slug` VARCHAR(150) NOT NULL,
          `description` TEXT DEFAULT NULL,
          `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
          `sort_order` INT NOT NULL DEFAULT 0,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          CONSTRAINT `fk_subcat_cat` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
          UNIQUE KEY `unique_cat_subname` (`category_id`, `name`),
          UNIQUE KEY `unique_subcat_slug` (`slug`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "[OK] Table 'sub_categories' ready.\n";

    // 4. Check & alter equipment_inventory columns
    $cols = $pdo->query("SHOW COLUMNS FROM `equipment_inventory`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('super_category_id', $cols, true)) {
        $pdo->exec("ALTER TABLE `equipment_inventory` ADD COLUMN `super_category_id` INT DEFAULT NULL AFTER `category`");
        $pdo->exec("ALTER TABLE `equipment_inventory` ADD CONSTRAINT `fk_equip_super_cat` FOREIGN KEY (`super_category_id`) REFERENCES `super_categories`(`id`) ON DELETE SET NULL");
        echo "[OK] Added 'super_category_id' to equipment_inventory.\n";
    }
    if (!in_array('category_id', $cols, true)) {
        $pdo->exec("ALTER TABLE `equipment_inventory` ADD COLUMN `category_id` INT DEFAULT NULL AFTER `super_category_id`");
        $pdo->exec("ALTER TABLE `equipment_inventory` ADD CONSTRAINT `fk_equip_cat` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL");
        echo "[OK] Added 'category_id' to equipment_inventory.\n";
    }
    if (!in_array('sub_category_id', $cols, true)) {
        $pdo->exec("ALTER TABLE `equipment_inventory` ADD COLUMN `sub_category_id` INT DEFAULT NULL AFTER `category_id`");
        $pdo->exec("ALTER TABLE `equipment_inventory` ADD CONSTRAINT `fk_equip_subcat` FOREIGN KEY (`sub_category_id`) REFERENCES `sub_categories`(`id`) ON DELETE SET NULL");
        echo "[OK] Added 'sub_category_id' to equipment_inventory.\n";
    }

    // 5. Seed initial hierarchical medical category data if empty
    $count = (int) $pdo->query("SELECT COUNT(*) FROM `super_categories`")->fetchColumn();
    if ($count === 0) {
        echo "Seeding initial hierarchy data...\n";

        $seedData = [
            [
                'name' => 'Surgical & Operating Room (OR) Equipment',
                'slug' => 'surgical-operating-room-equipment',
                'desc' => 'High-precision surgical instrumentation, electro-surgery energy devices, and minimally invasive operative sets.',
                'icon' => 'activity',
                'categories' => [
                    [
                        'name' => 'General Surgery',
                        'slug' => 'general-surgery',
                        'desc' => 'Standard and specialized dissecting, cutting, grasping, and suturing surgical sets.',
                        'sub_categories' => [
                            ['name' => 'Dissecting & Surgical Scissors', 'slug' => 'dissecting-surgical-scissors', 'desc' => 'Metzenbaum, Mayo, and iris precision cutting scissors.'],
                            ['name' => 'Forceps & Hemostats', 'slug' => 'forceps-hemostats', 'desc' => 'Babcock, Allis, and artery hemostatic forceps.'],
                            ['name' => 'Surgical Retractors', 'slug' => 'surgical-retractors', 'desc' => 'Weitlaner, Balfour, and handheld surgical retractors.'],
                        ]
                    ],
                    [
                        'name' => 'Laparoscopy & Minimally Invasive',
                        'slug' => 'laparoscopy-minimally-invasive',
                        'desc' => 'Trocar systems, laparoscopic scissors, graspers, and endo-surgery optics.',
                        'sub_categories' => [
                            ['name' => 'Laparoscopic Scissors & Graspers', 'slug' => 'laparoscopic-scissors-graspers', 'desc' => '5mm and 10mm insulated endoscopic hand instruments.'],
                            ['name' => 'Trocars & Cannulas', 'slug' => 'trocars-cannulas', 'desc' => 'Optical and bladeless peritoneal access ports.'],
                        ]
                    ],
                    [
                        'name' => 'Electrosurgery & Energy Devices',
                        'slug' => 'electrosurgery-energy-devices',
                        'desc' => 'High-frequency generators, bipolar coagulation forceps, and harmonic scalpel handpieces.',
                        'sub_categories' => [
                            ['name' => 'Bipolar & Monopolar Forceps', 'slug' => 'bipolar-monopolar-forceps', 'desc' => 'Non-stick coagulation instruments.'],
                            ['name' => 'Harmonic & Ultrasonic Blades', 'slug' => 'harmonic-ultrasonic-blades', 'desc' => 'Vessel sealing and ultrasonic soft-tissue dissection.'],
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Specialized Surgical Systems',
                'slug' => 'specialized-surgical-systems',
                'desc' => 'Orthopedic power tools, neurosurgical microsurgery suites, and cardiovascular kits.',
                'icon' => 'shield',
                'categories' => [
                    [
                        'name' => 'Orthopedics',
                        'slug' => 'orthopedics',
                        'desc' => 'Bone drill systems, oscillating sagittal saws, and reamers.',
                        'sub_categories' => [
                            ['name' => 'Bone Drills & Saws', 'slug' => 'bone-drills-saws', 'desc' => 'Battery-powered orthopedic sagittal and reciprocating saws.'],
                            ['name' => 'Joint Replacement Instruments', 'slug' => 'joint-replacement-instruments', 'desc' => 'Acetabular reamers and hip/knee trial instruments.'],
                        ]
                    ],
                    [
                        'name' => 'Neurosurgery',
                        'slug' => 'neurosurgery',
                        'desc' => 'Micro-scissors, cranial drills, and aneurysm clip appliers.',
                        'sub_categories' => [
                            ['name' => 'Yasargil Micro Instruments', 'slug' => 'yasargil-micro-instruments', 'desc' => 'Ultra-fine bayonet micro-scissors and forceps.'],
                            ['name' => 'Cranial Perforators & Drills', 'slug' => 'cranial-perforators-drills', 'desc' => 'High-speed neuro drill attachments and burrs.'],
                        ]
                    ]
                ]
            ],
            [
                'name' => 'CSSD & Infection Control',
                'slug' => 'cssd-infection-control',
                'desc' => 'Central sterile services equipment, steam autoclaves, plasma sterilizers, and biological monitoring.',
                'icon' => 'check-circle',
                'categories' => [
                    [
                        'name' => 'Autoclaves & Sterilizers',
                        'slug' => 'autoclaves-sterilizers',
                        'desc' => 'High-pressure vacuum steam autoclaves and low-temp hydrogen peroxide sterilizers.',
                        'sub_categories' => [
                            ['name' => 'Steam Autoclaves 134°C', 'slug' => 'steam-autoclaves-134c', 'desc' => 'Class B pre-vacuum hospital sterilizing chambers.'],
                            ['name' => 'H2O2 Plasma Sterilizers', 'slug' => 'h2o2-plasma-sterilizers', 'desc' => 'Low-temperature sterilization for delicate optics.'],
                        ]
                    ],
                    [
                        'name' => 'Decontamination & Trays',
                        'slug' => 'decontamination-trays',
                        'desc' => 'Ultrasonic washing tanks, DIN wire mesh baskets, and container systems.',
                        'sub_categories' => [
                            ['name' => 'Ultrasonic Cleaners', 'slug' => 'ultrasonic-cleaners', 'desc' => 'Multi-chamber cavitation cleaning units.'],
                            ['name' => 'Sterilization Trays & Cassettes', 'slug' => 'sterilization-trays-cassettes', 'desc' => 'Anodized aluminum and stainless mesh trays.'],
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Critical Care & Patient Monitoring',
                'slug' => 'critical-care-patient-monitoring',
                'desc' => 'ICU ventilators, multi-parameter vital signs monitors, anaesthesia workstations, and defibrillators.',
                'icon' => 'heart',
                'categories' => [
                    [
                        'name' => 'ICU & Anaesthesia Equipment',
                        'slug' => 'icu-anaesthesia-equipment',
                        'desc' => 'High-acuity patient support, vaporizers, and mechanical breathing units.',
                        'sub_categories' => [
                            ['name' => 'Anaesthesia Workstations', 'slug' => 'anaesthesia-workstations', 'desc' => 'Integrated gas delivery and ventilator circuits.'],
                            ['name' => 'Multi-Parameter Patient Monitors', 'slug' => 'multi-parameter-patient-monitors', 'desc' => 'ECG, SpO2, NIBP, and capnography monitoring.'],
                        ]
                    ]
                ]
            ]
        ];

        $stmtSuper = $pdo->prepare("INSERT INTO `super_categories` (`name`, `slug`, `description`, `icon`, `status`, `sort_order`) VALUES (:name, :slug, :desc, :icon, 'active', :sort)");
        $stmtCat = $pdo->prepare("INSERT INTO `categories` (`super_category_id`, `name`, `slug`, `description`, `status`, `sort_order`) VALUES (:super_id, :name, :slug, :desc, 'active', :sort)");
        $stmtSub = $pdo->prepare("INSERT INTO `sub_categories` (`category_id`, `name`, `slug`, `description`, `status`, `sort_order`) VALUES (:cat_id, :name, :slug, :desc, 'active', :sort)");

        $superOrder = 1;
        foreach ($seedData as $sData) {
            $stmtSuper->execute([
                ':name' => $sData['name'],
                ':slug' => $sData['slug'],
                ':desc' => $sData['desc'],
                ':icon' => $sData['icon'],
                ':sort' => $superOrder++
            ]);
            $superId = (int) $pdo->lastInsertId();

            $catOrder = 1;
            foreach ($sData['categories'] as $cData) {
                $stmtCat->execute([
                    ':super_id' => $superId,
                    ':name'     => $cData['name'],
                    ':slug'     => $cData['slug'],
                    ':desc'     => $cData['desc'],
                    ':sort'     => $catOrder++
                ]);
                $catId = (int) $pdo->lastInsertId();

                $subOrder = 1;
                foreach ($cData['sub_categories'] as $subData) {
                    $stmtSub->execute([
                        ':cat_id' => $catId,
                        ':name'   => $subData['name'],
                        ':slug'   => $subData['slug'],
                        ':desc'   => $subData['desc'],
                        ':sort'   => $subOrder++
                    ]);
                }
            }
        }
        echo "[OK] Hierarchical seed data populated successfully.\n";
    }

    // Link existing equipment items to corresponding categories where possible
    $equipment = $pdo->query("SELECT id, name, category FROM `equipment_inventory`")->fetchAll();
    foreach ($equipment as $eq) {
        $catText = trim($eq['category']);
        // Find matching category
        $match = $pdo->prepare("SELECT c.id AS cat_id, c.super_category_id FROM `categories` c WHERE c.name LIKE :n LIMIT 1");
        $match->execute([':n' => '%' . $catText . '%']);
        $catRow = $match->fetch();
        if ($catRow) {
            $pdo->prepare("UPDATE `equipment_inventory` SET super_category_id = :sid, category_id = :cid WHERE id = :id AND category_id IS NULL")
                ->execute([
                    ':sid' => $catRow['super_category_id'],
                    ':cid' => $catRow['cat_id'],
                    ':id'  => $eq['id']
                ]);
        }
    }
    echo "[OK] Linked existing inventory equipment to categories.\n";

    echo "\n=== Migration Finished Successfully! ===\n";

} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}
