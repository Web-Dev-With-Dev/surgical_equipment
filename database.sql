-- ========================================================
-- Surgical Equipment Management System - Database Schema
-- Database Name: surgical_equipment_db
-- ========================================================

CREATE DATABASE IF NOT EXISTS `surgical_equipment_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `surgical_equipment_db`;

-- Drop existing tables in reverse order of foreign keys to avoid conflicts
DROP TABLE IF EXISTS `sterilization_batch_items`;
DROP TABLE IF EXISTS `sterilization_batches`;
DROP TABLE IF EXISTS `tracking_logs`;
DROP TABLE IF EXISTS `maintenance_records`;
DROP TABLE IF EXISTS `system_audit_trails`;
DROP TABLE IF EXISTS `equipment_inventory`;
DROP TABLE IF EXISTS `purchases`;
DROP TABLE IF EXISTS `suppliers`;
DROP TABLE IF EXISTS `users`;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `role` ENUM('admin', 'surgeon', 'nurse', 'sterilisation_tech', 'auditor') NOT NULL DEFAULT 'nurse',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: suppliers
-- --------------------------------------------------------
CREATE TABLE `suppliers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `contact_person` VARCHAR(100) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: purchases
-- --------------------------------------------------------
CREATE TABLE `purchases` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `purchase_order_number` VARCHAR(50) NOT NULL UNIQUE,
  `supplier_id` INT NOT NULL,
  `purchase_date` DATE NOT NULL,
  `total_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('pending', 'completed', 'cancelled') NOT NULL DEFAULT 'completed',
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_purchase_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: equipment_inventory
-- --------------------------------------------------------
CREATE TABLE `equipment_inventory` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `equipment_code` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(150) NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `status` ENUM('available', 'in_use', 'under_maintenance', 'sterilising', 'retired') NOT NULL DEFAULT 'available',
  `current_location` VARCHAR(150) DEFAULT 'Main Store',
  `last_sterilised_at` DATETIME DEFAULT NULL,
  `purchase_date` DATE DEFAULT NULL,
  `warranty_expiry` DATE DEFAULT NULL,
  `serial_number` VARCHAR(100) DEFAULT NULL,
  `supplier_id` INT DEFAULT NULL,
  `purchase_id` INT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_equip_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_equip_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `purchases`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: system_audit_trails
-- --------------------------------------------------------
CREATE TABLE `system_audit_trails` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(100) DEFAULT NULL,
  `entity_id` INT DEFAULT NULL,
  `details` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: maintenance_records
-- --------------------------------------------------------
CREATE TABLE `maintenance_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `equipment_id` INT NOT NULL,
  `type` ENUM('routine', 'repair', 'calibration', 'inspection') NOT NULL DEFAULT 'routine',
  `status` ENUM('pending', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  `scheduled_date` DATE DEFAULT NULL,
  `completed_date` DATE DEFAULT NULL,
  `performed_by` INT DEFAULT NULL,
  `cost` DECIMAL(10,2) DEFAULT 0.00,
  `description` TEXT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_maint_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment_inventory`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_maint_user` FOREIGN KEY (`performed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: tracking_logs
-- --------------------------------------------------------
CREATE TABLE `tracking_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `equipment_id` INT NOT NULL,
  `user_id` INT DEFAULT NULL,
  `action` ENUM('check_out', 'check_in', 'transfer') NOT NULL,
  `from_location` VARCHAR(150) DEFAULT NULL,
  `to_location` VARCHAR(150) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_track_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment_inventory`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_track_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: sterilization_batches
-- --------------------------------------------------------
CREATE TABLE `sterilization_batches` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `batch_number` VARCHAR(50) NOT NULL UNIQUE,
  `autoclave_id` VARCHAR(50) DEFAULT NULL,
  `cycle_type` VARCHAR(100) DEFAULT 'Standard Autoclave 134°C',
  `started_by` INT DEFAULT NULL,
  `status` ENUM('in_progress', 'completed', 'failed') NOT NULL DEFAULT 'in_progress',
  `started_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `completed_at` DATETIME DEFAULT NULL,
  `chemical_indicator_passed` TINYINT(1) DEFAULT 0,
  `biological_indicator_passed` TINYINT(1) DEFAULT 0,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_steril_user` FOREIGN KEY (`started_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: sterilization_batch_items
-- --------------------------------------------------------
CREATE TABLE `sterilization_batch_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `batch_id` INT NOT NULL,
  `equipment_id` INT NOT NULL,
  CONSTRAINT `fk_batch_item_batch` FOREIGN KEY (`batch_id`) REFERENCES `sterilization_batches`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_batch_item_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment_inventory`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Sample Data Insertion (Default Passwords: 'Password123!')
-- Password Hash generated via password_hash('Password123!', PASSWORD_BCRYPT)
-- --------------------------------------------------------

INSERT INTO `users` (`username`, `password_hash`, `full_name`, `email`, `role`)
VALUES
  ('admin', '$2y$10$puppCkSIsyee9AEGpSDkoerHH.YdajY2DsWN0BRr214ViLWy7QvUa', 'Dr. Sarah Connor (Admin)', 'admin@hospital.org', 'admin'),
  ('surgeon_john', '$2y$10$puppCkSIsyee9AEGpSDkoerHH.YdajY2DsWN0BRr214ViLWy7QvUa', 'Dr. John Smith', 'john.smith@hospital.org', 'surgeon'),
  ('nurse_mary', '$2y$10$puppCkSIsyee9AEGpSDkoerHH.YdajY2DsWN0BRr214ViLWy7QvUa', 'Mary Johnson (Nurse)', 'mary.johnson@hospital.org', 'nurse'),
  ('tech_alex', '$2y$10$puppCkSIsyee9AEGpSDkoerHH.YdajY2DsWN0BRr214ViLWy7QvUa', 'Alex Turner (CSSD Tech)', 'alex.turner@hospital.org', 'sterilisation_tech'),
  ('auditor_david', '$2y$10$puppCkSIsyee9AEGpSDkoerHH.YdajY2DsWN0BRr214ViLWy7QvUa', 'David Miller (Auditor)', 'david.miller@hospital.org', 'auditor')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Insert Sample Suppliers
INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `email`, `phone`, `address`)
VALUES
  (1, 'MedTech Solutions Inc.', 'John Doe', 'john@medtech.com', '+1-555-0199', '100 Medical Parkway, Ste 200, Boston, MA'),
  (2, 'SurgiKraft Instruments', 'Jane Smith', 'jane.smith@surgikraft.com', '+1-555-0188', '450 Precision Ave, Chicago, IL'),
  (3, 'Apex BioMedical Devices', 'Robert Lee', 'robert@apexbiomed.com', '+1-555-0177', '78 Science Drive, San Diego, CA'),
  (4, 'Aesculap Surgical Systems', 'Elena Rostova', 'elena@aesculap-med.com', '+1-555-0166', '890 Healthcare Blvd, Philadelphia, PA'),
  (5, 'Stryker Orthopedic Tools', 'Marcus Vance', 'marcus.vance@stryker-tools.com', '+1-555-0155', '310 Innovation Way, Kalamazoo, MI')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Insert Sample Purchases
INSERT INTO `purchases` (`id`, `purchase_order_number`, `supplier_id`, `purchase_date`, `total_cost`, `status`, `notes`)
VALUES
  (1, 'PO-2026-001', 1, '2026-01-10', 12500.00, 'completed', 'Initial order for Micro-Forceps and Electrosurgical kits'),
  (2, 'PO-2026-002', 2, '2026-02-15', 8400.00, 'completed', 'Surgical scissors and retractors order'),
  (3, 'PO-2026-003', 3, '2026-03-01', 25000.00, 'completed', 'Harmonic Scalpel Handpieces calibration upgrade'),
  (4, 'PO-2026-004', 4, '2026-04-12', 34200.00, 'completed', 'Neurosurgical instrument set & micro-suction tubes'),
  (5, 'PO-2026-005', 5, '2026-05-20', 18900.00, 'completed', 'Power tools, orthopedic saws, and reamers')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Insert Sample Equipment Inventory
INSERT INTO `equipment_inventory` (`id`, `equipment_code`, `name`, `category`, `status`, `current_location`, `last_sterilised_at`, `purchase_date`, `warranty_expiry`, `serial_number`, `supplier_id`, `purchase_id`, `notes`)
VALUES
  (1, 'EQ-1001', 'Titanium Micro-Forceps #4', 'Surgical Forceps', 'available', 'CSSD Storage Bay A', NOW() - INTERVAL 1 DAY, '2026-01-15', '2027-01-15', 'SN-TF4-8891', 1, 1, 'Precision micro-forceps for vascular procedures'),
  (2, 'EQ-1002', 'Laparoscopic Curved Scissors 5mm', 'Laparoscopy', 'available', 'CSSD Rack 03', NOW() - INTERVAL 3 DAY, '2026-02-10', '2027-06-10', 'SN-LS5-3312', 2, 2, 'Sharp curved tips, reusable sleeve'),
  (3, 'EQ-1003', 'Harmonic Scalpel Handpiece', 'Electrosurgery', 'under_maintenance', 'Biomedical Workshop', NOW() - INTERVAL 15 DAY, '2026-03-20', '2027-11-20', 'SN-HSH-9901', 3, 3, 'Transducer calibration required'),
  (4, 'EQ-1004', 'Orthopedic Bone Drill Set', 'Orthopedics', 'sterilising', 'Autoclave Unit 1', NOW() - INTERVAL 20 DAY, '2026-02-01', '2028-02-01', 'SN-OBD-4412', 5, 5, 'Includes keyless chuck & lithium battery module'),
  (5, 'EQ-1005', 'Metzenbaum Dissecting Scissors 7"', 'General Surgery', 'available', 'Main Sterilisation Store', NOW() - INTERVAL 2 DAY, '2026-02-15', '2028-02-15', 'SN-MDS-1022', 2, 2, 'Tungsten carbide insert blades'),
  (6, 'EQ-1006', 'Babcock Tissue Grasping Forceps', 'General Surgery', 'in_use', 'OR Suite 2', NOW() - INTERVAL 4 DAY, '2026-02-15', '2028-02-15', 'SN-BTG-5541', 2, 2, 'Atraumatic jaws for delicate tissue'),
  (7, 'EQ-1007', 'Yasargil Neuro Micro Scissors', 'Neurosurgery', 'available', 'Neuro Cabinet B', NOW() - INTERVAL 1 DAY, '2026-04-12', '2029-04-12', 'SN-YNM-9920', 4, 4, 'Bayonet shape 225mm'),
  (8, 'EQ-1008', 'Weitlaner Retractor 3x4 Teeth', 'Retractors', 'available', 'CSSD Storage Bay B', NOW() - INTERVAL 5 DAY, '2026-01-10', '2027-01-10', 'SN-WRT-7731', 1, 1, 'Self-retaining blunt teeth'),
  (9, 'EQ-1009', 'Stryker System 8 Oscillating Saw', 'Orthopedics', 'available', 'Ortho Store Room 1', NOW() - INTERVAL 6 DAY, '2026-05-20', '2028-05-20', 'SN-SOS-8802', 5, 5, 'High speed sagittal saw for total joint replacement'),
  (10, 'EQ-1010', 'Bipolar Coagulation Forceps', 'Electrosurgery', 'available', 'CSSD Storage Bay A', NOW() - INTERVAL 2 DAY, '2026-03-01', '2027-03-01', 'SN-BCF-4409', 3, 3, 'Non-stick silver alloy tips')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Insert Sample Sterilization Batches
INSERT INTO `sterilization_batches` (`id`, `batch_number`, `autoclave_id`, `cycle_type`, `started_by`, `status`, `started_at`, `completed_at`, `chemical_indicator_passed`, `biological_indicator_passed`, `notes`)
VALUES
  (1, 'STER-20260720-001', 'Autoclave Alpha-1', 'Standard Autoclave 134°C', 4, 'completed', NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY + INTERVAL 45 MINUTE, 1, 1, 'Standard 134C steam cycle passed all chemical and bio indicators.'),
  (2, 'STER-20260721-001', 'Autoclave Beta-2', 'Standard Autoclave 134°C', 4, 'completed', NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 1 DAY + INTERVAL 45 MINUTE, 1, 1, 'Routine daily sterilisation load.'),
  (3, 'STER-20260722-001', 'Plasma Unit 01', 'H2O2 Low Temp Plasma 55°C', 4, 'in_progress', NOW() - INTERVAL 30 MINUTE, NULL, 1, 0, 'Low-temp hydrogen peroxide plasma sterilisation load in progress.')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Link Batch Items
INSERT INTO `sterilization_batch_items` (`batch_id`, `equipment_id`)
VALUES
  (1, 1), (1, 2), (1, 5),
  (2, 7), (2, 8), (2, 10),
  (3, 4)
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Insert Sample Maintenance Records
INSERT INTO `maintenance_records` (`equipment_id`, `type`, `status`, `scheduled_date`, `completed_date`, `performed_by`, `cost`, `description`, `notes`)
VALUES
  (3, 'repair', 'in_progress', CURRENT_DATE() - INTERVAL 15 DAY, NULL, 1, 450.00, 'Replaced faulty transducer wire and recalibrated generator frequency.', 'Warranty repair covered under service contract.'),
  (9, 'calibration', 'completed', CURRENT_DATE() - INTERVAL 30 DAY, CURRENT_DATE() - INTERVAL 28 DAY, 1, 250.00, 'Routine motor torque and blade speed calibration.', 'Passes all OEM operating tolerances.')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Insert Sample Tracking Logs
INSERT INTO `tracking_logs` (`equipment_id`, `action`, `from_location`, `to_location`, `user_id`, `notes`, `created_at`)
VALUES
  (6, 'check_out', 'CSSD Storage Bay A', 'OR Suite 2', 3, 'Checked out for elective laparoscopic cholecystectomy', NOW() - INTERVAL 4 HOUR),
  (1, 'check_in', 'OR Suite 1', 'CSSD Storage Bay A', 4, 'Returned post-op, cleaned and sterilised in Batch STER-20260720-001', NOW() - INTERVAL 1 DAY)
ON DUPLICATE KEY UPDATE `id`=`id`;

