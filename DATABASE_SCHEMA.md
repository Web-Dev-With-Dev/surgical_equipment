# Database Schema & Data Dictionary Document
**Project Name:** Surgical Equipment Management System & Public Catalogue  
**Database Name:** `surgical_equipment_db`  
**Database Engine:** MySQL / MariaDB (InnoDB)  
**Character Set:** `utf8mb4_unicode_ci`  

---

## 📋 Table Index

1. [Table 1: `users`](#table-1-users) — User Accounts & Role-Based Access Control
2. [Table 2: `suppliers`](#table-2-suppliers) — Medical Equipment Manufacturers & Suppliers
3. [Table 3: `purchases`](#table-3-purchases) — Purchase Orders & Procurement Records
4. [Table 4: `super_categories`](#table-4-super_categories) — Super Categories (Level 1 Medical Divisions)
5. [Table 5: `categories`](#table-5-categories) — Categories (Level 2 Equipment Families)
6. [Table 6: `sub_categories`](#table-6-sub_categories) — Sub Categories (Level 3 Instrument Clusters)
7. [Table 7: `equipment_inventory`](#table-7-equipment_inventory) — Surgical Equipment & Instruments Master Table
8. [Table 8: `system_audit_trails`](#table-8-system_audit_trails) — Security & Audit Log Records
9. [Table 9: `maintenance_records`](#table-9-maintenance_records) — Preventive Maintenance, Repair & Calibration Records
10. [Table 10: `tracking_logs`](#table-10-tracking_logs) — Equipment Check-in, Check-out & Location Transfers
11. [Table 11: `sterilization_batches`](#table-11-sterilization_batches) — Autoclave & Sterilization Cycle Batches
12. [Table 12: `sterilization_batch_items`](#table-12-sterilization_batch_items) — Equipment Items Linked to Sterilization Cycles
13. [Table 13: `inquiries`](#table-13-inquiries) — Customer Lead Inquiries & Quotation Requests

---

### Table 1: `users`
> **Purpose:** Stores user credentials, profiles, and role-based permissions (Admin, Surgeon, Nurse, Sterilization Tech, Auditor).

| SR No. | Field Name | Data Type | Size | Constraint | Description |
|:---:|---|---|:---:|---|---|
| 1 | `id` | INT | 11 | PRIMARY KEY, AUTO_INCREMENT, NOT NULL | Unique identifier for each system user |
| 2 | `username` | VARCHAR | 50 | UNIQUE, NOT NULL | Unique login username for authentication |
| 3 | `password_hash` | VARCHAR | 255 | NOT NULL | BCRYPT securely hashed user password |
| 4 | `full_name` | VARCHAR | 100 | NOT NULL | Full name and title of the user |
| 5 | `email` | VARCHAR | 100 | NULL | Official email address for notifications |
| 6 | `role` | ENUM | — | NOT NULL, DEFAULT 'nurse'<br>('admin', 'surgeon', 'nurse', 'sterilisation_tech', 'auditor') | Access control role determining system privileges |
| 7 | `created_at` | DATETIME | — | DEFAULT CURRENT_TIMESTAMP | Date and time when the user account was created |
| 8 | `updated_at` | DATETIME | — | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Timestamp of the latest account update |

---

### Table 2: `suppliers`
> **Purpose:** Stores details of medical device manufacturers, vendors, and authorized distributors.

| SR No. | Field Name | Data Type | Size | Constraint | Description |
|:---:|---|---|:---:|---|---|
| 1 | `id` | INT | 11 | PRIMARY KEY, AUTO_INCREMENT, NOT NULL | Unique identifier for each supplier |
| 2 | `name` | VARCHAR | 150 | NOT NULL | Registered company/manufacturer name |
| 3 | `contact_person` | VARCHAR | 100 | NULL | Name of key sales or account manager |
| 4 | `email` | VARCHAR | 100 | NULL | Official business email address |
| 5 | `phone` | VARCHAR | 30 | NULL | Primary contact phone / mobile number |
| 6 | `address` | TEXT | — | NULL | Physical corporate or warehouse address |
| 7 | `created_at` | DATETIME | — | DEFAULT CURRENT_TIMESTAMP | Timestamp when supplier record was added |
| 8 | `updated_at` | DATETIME | — | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Timestamp of latest supplier record modification |

---

### Table 3: `purchases`
> **Purpose:** Tracks purchase orders, supplier procurement, total cost, and order completion status.

| SR No. | Field Name | Data Type | Size | Constraint | Description |
|:---:|---|---|:---:|---|---|
| 1 | `id` | INT | 11 | PRIMARY KEY, AUTO_INCREMENT, NOT NULL | Unique identifier for purchase order record |
| 2 | `purchase_order_number` | VARCHAR | 50 | UNIQUE, NOT NULL | Unique PO code (e.g., PO-2026-001) |
| 3 | `supplier_id` | INT | 11 | FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE CASCADE, NOT NULL | Reference ID linking to the vendor/supplier |
| 4 | `purchase_date` | DATE | — | NOT NULL | Date when purchase order was placed |
| 5 | `total_cost` | DECIMAL | 10,2 | NOT NULL, DEFAULT 0.00 | Total financial amount of the purchase order |
| 6 | `status` | ENUM | — | NOT NULL, DEFAULT 'completed'<br>('pending', 'completed', 'cancelled') | Current status of the purchase order |
| 7 | `notes` | TEXT | — | NULL | Additional procurement remarks and details |
| 8 | `created_at` | DATETIME | — | DEFAULT CURRENT_TIMESTAMP | Timestamp when record was created |
| 9 | `updated_at` | DATETIME | — | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Timestamp of latest order modification |

---

### Table 4: `super_categories`
> **Purpose:** Top-level hierarchy grouping (Level 1) representing primary hospital departments and surgical divisions.

| SR No. | Field Name | Data Type | Size | Constraint | Description |
|:---:|---|---|:---:|---|---|
| 1 | `id` | INT | 11 | PRIMARY KEY, AUTO_INCREMENT, NOT NULL | Unique identifier for super category |
| 2 | `name` | VARCHAR | 150 | UNIQUE, NOT NULL | Super category name (e.g. Surgical & OR Equipment) |
| 3 | `slug` | VARCHAR | 150 | UNIQUE, NOT NULL | SEO-friendly URL slug |
| 4 | `description` | TEXT | — | NULL | Clinical division description and overview |
| 5 | `icon` | VARCHAR | 100 | NULL | Icon identifier or feather icon class |
| 6 | `status` | ENUM | — | NOT NULL, DEFAULT 'active'<br>('active', 'inactive') | Visibility status across store and management |
| 7 | `sort_order` | INT | 11 | NOT NULL, DEFAULT 0 | Display sorting index (lower number appears first) |
| 8 | `created_at` | DATETIME | — | DEFAULT CURRENT_TIMESTAMP | Timestamp when record was created |
| 9 | `updated_at` | DATETIME | — | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Timestamp of latest record modification |

---

### Table 5: `categories`
> **Purpose:** Secondary hierarchy level (Level 2) organizing instrument families under parent super categories.

| SR No. | Field Name | Data Type | Size | Constraint | Description |
|:---:|---|---|:---:|---|---|
| 1 | `id` | INT | 11 | PRIMARY KEY, AUTO_INCREMENT, NOT NULL | Unique identifier for category |
| 2 | `super_category_id` | INT | 11 | FOREIGN KEY (`super_category_id`) REFERENCES `super_categories`(`id`) ON DELETE CASCADE, NOT NULL | Parent Super Category foreign key reference |
| 3 | `name` | VARCHAR | 150 | NOT NULL | Category name (e.g. General Surgery, Laparoscopy) |
| 4 | `slug` | VARCHAR | 150 | UNIQUE, NOT NULL | Unique URL slug |
| 5 | `description` | TEXT | — | NULL | Technical summary and clinical applications |
| 6 | `status` | ENUM | — | NOT NULL, DEFAULT 'active'<br>('active', 'inactive') | Visibility status |
| 7 | `sort_order` | INT | 11 | NOT NULL, DEFAULT 0 | Sorting sequence within super category |
| 8 | `created_at` | DATETIME | — | DEFAULT CURRENT_TIMESTAMP | Creation timestamp |
| 9 | `updated_at` | DATETIME | — | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Modification timestamp |

---

### Table 6: `sub_categories`
> **Purpose:** Tertiary hierarchy level (Level 3) organizing specific surgical product clusters and instrument subsets under categories.

| SR No. | Field Name | Data Type | Size | Constraint | Description |
|:---:|---|---|:---:|---|---|
| 1 | `id` | INT | 11 | PRIMARY KEY, AUTO_INCREMENT, NOT NULL | Unique identifier for sub category |
| 2 | `category_id` | INT | 11 | FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE, NOT NULL | Parent Category foreign key reference |
| 3 | `name` | VARCHAR | 150 | NOT NULL | Sub category name (e.g. Dissecting & Surgical Scissors) |
| 4 | `slug` | VARCHAR | 150 | UNIQUE, NOT NULL | Unique URL slug |
| 5 | `description` | TEXT | — | NULL | Product model and subtype descriptions |
| 6 | `status` | ENUM | — | NOT NULL, DEFAULT 'active'<br>('active', 'inactive') | Visibility status |
| 7 | `sort_order` | INT | 11 | NOT NULL, DEFAULT 0 | Sorting sequence within parent category |
| 8 | `created_at` | DATETIME | — | DEFAULT CURRENT_TIMESTAMP | Creation timestamp |
| 9 | `updated_at` | DATETIME | — | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Modification timestamp |

---

### Table 7: `equipment_inventory`
> **Purpose:** Master repository storing individual surgical instruments, OT equipment, ICU systems, locations, and lifecycle statuses.

| SR No. | Field Name | Data Type | Size | Constraint | Description |
|:---:|---|---|:---:|---|---|
| 1 | `id` | INT | 11 | PRIMARY KEY, AUTO_INCREMENT, NOT NULL | Unique internal ID for equipment item |
| 2 | `equipment_code` | VARCHAR | 50 | UNIQUE, NOT NULL | Asset/Barcode tracking code (e.g., EQ-1001) |
| 3 | `name` | VARCHAR | 150 | NOT NULL | Full equipment or instrument name |
| 4 | `category` | VARCHAR | 100 | NOT NULL | Department/Category string representation |
| 5 | `super_category_id` | INT | 11 | FOREIGN KEY (`super_category_id`) REFERENCES `super_categories`(`id`) ON DELETE SET NULL | Optional relational link to Super Category |
| 6 | `category_id` | INT | 11 | FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL | Optional relational link to Category |
| 7 | `sub_category_id` | INT | 11 | FOREIGN KEY (`sub_category_id`) REFERENCES `sub_categories`(`id`) ON DELETE SET NULL | Optional relational link to Sub Category |
| 8 | `status` | ENUM | — | NOT NULL, DEFAULT 'available'<br>('available', 'in_use', 'under_maintenance', 'sterilising', 'retired') | Real-time clinical operational status |
| 9 | `current_location` | VARCHAR | 150 | DEFAULT 'Main Store' | Current physical room, rack, or OT Suite |
| 10 | `last_sterilised_at` | DATETIME | — | NULL | Timestamp of the most recent sterilization cycle |
| 11 | `purchase_date` | DATE | — | NULL | Date item was purchased |
| 12 | `warranty_expiry` | DATE | — | NULL | Expiration date of OEM warranty |
| 13 | `serial_number` | VARCHAR | 100 | NULL | Manufacturer serial number (e.g., SN-TF4-8891) |
| 14 | `supplier_id` | INT | 11 | FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE SET NULL | Reference ID of supplier/vendor |
| 15 | `purchase_id` | INT | 11 | FOREIGN KEY (`purchase_id`) REFERENCES `purchases`(`id`) ON DELETE SET NULL | Reference ID of associated purchase order |
| 16 | `notes` | TEXT | — | NULL | Technical specifications and maintenance notes |
| 17 | `created_at` | DATETIME | — | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |
| 18 | `updated_at` | DATETIME | — | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Latest update timestamp |

---

### Table 8: `system_audit_trails`
> **Purpose:** Immutable audit log tracking user logins, critical data changes, equipment actions, IP addresses, and user agents.

| SR No. | Field Name | Data Type | Size | Constraint | Description |
|:---:|---|---|:---:|---|---|
| 1 | `id` | INT | 11 | PRIMARY KEY, AUTO_INCREMENT, NOT NULL | Unique audit log identifier |
| 2 | `user_id` | INT | 11 | FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL | User who performed the action |
| 3 | `action` | VARCHAR | 100 | NOT NULL | Operation performed (e.g., Login, Status Change, Batch Created) |
| 4 | `entity_type` | VARCHAR | 100 | NULL | Affected table/module (e.g., equipment, sterilization) |
| 5 | `entity_id` | INT | 11 | NULL | Primary ID of the modified entity |
| 6 | `details` | TEXT | — | NULL | Detailed description or JSON payload of the action |
| 7 | `ip_address` | VARCHAR | 45 | NULL | IPv4 or IPv6 address of user |
| 8 | `user_agent` | VARCHAR | 255 | NULL | Browser / Operating System client identifier |
| 9 | `created_at` | DATETIME | — | DEFAULT CURRENT_TIMESTAMP | Exact date and time when activity occurred |

---

### Table 6: `maintenance_records`
> **Purpose:** Logs routine servicing, breakdown repairs, calibration audits, and maintenance costs for equipment.

| SR No. | Field Name | Data Type | Size | Constraint | Description |
|:---:|---|---|:---:|---|---|
| 1 | `id` | INT | 11 | PRIMARY KEY, AUTO_INCREMENT, NOT NULL | Unique maintenance record ID |
| 2 | `equipment_id` | INT | 11 | FOREIGN KEY (`equipment_id`) REFERENCES `equipment_inventory`(`id`) ON DELETE CASCADE, NOT NULL | Reference ID of the serviced equipment |
| 3 | `type` | ENUM | — | NOT NULL, DEFAULT 'routine'<br>('routine', 'repair', 'calibration', 'inspection') | Nature of maintenance performed |
| 4 | `status` | ENUM | — | NOT NULL, DEFAULT 'pending'<br>('pending', 'in_progress', 'completed', 'cancelled') | Operational state of maintenance work |
| 5 | `scheduled_date` | DATE | — | NULL | Date when maintenance was scheduled |
| 6 | `completed_date` | DATE | — | NULL | Actual completion date |
| 7 | `performed_by` | INT | 11 | FOREIGN KEY (`performed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL | Technician or engineer who performed servicing |
| 8 | `cost` | DECIMAL | 10,2 | DEFAULT 0.00 | Financial expense incurred for service/parts |
| 9 | `description` | TEXT | — | NULL | Description of faults, diagnostics, and work done |
| 10 | `notes` | TEXT | — | NULL | Warranty contract coverage & follow-up notes |
| 11 | `created_at` | DATETIME | — | DEFAULT CURRENT_TIMESTAMP | Timestamp when record was entered |

---

### Table 7: `tracking_logs`
> **Purpose:** Logs movement of surgical equipment between CSSD storage, Operating Theatre suites, and biomedical workshops.

| SR No. | Field Name | Data Type | Size | Constraint | Description |
|:---:|---|---|:---:|---|---|
| 1 | `id` | INT | 11 | PRIMARY KEY, AUTO_INCREMENT, NOT NULL | Unique tracking transaction ID |
| 2 | `equipment_id` | INT | 11 | FOREIGN KEY (`equipment_id`) REFERENCES `equipment_inventory`(`id`) ON DELETE CASCADE, NOT NULL | Equipment being transferred or checked |
| 3 | `user_id` | INT | 11 | FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL | Staff member authorizing the transfer |
| 4 | `action` | ENUM | — | NOT NULL<br>('check_out', 'check_in', 'transfer') | Type of transfer action |
| 5 | `from_location` | VARCHAR | 150 | NULL | Origin location/department |
| 6 | `to_location` | VARCHAR | 150 | NULL | Destination location/department |
| 7 | `notes` | TEXT | — | NULL | Clinical purpose or surgery procedure details |
| 8 | `created_at` | DATETIME | — | DEFAULT CURRENT_TIMESTAMP | Timestamp of the transfer transaction |

---

### Table 8: `sterilization_batches`
> **Purpose:** Records CSSD autoclave sterilisation cycles, cycle parameters, autoclave units, and biological/chemical indicator test results.

| SR No. | Field Name | Data Type | Size | Constraint | Description |
|:---:|---|---|:---:|---|---|
| 1 | `id` | INT | 11 | PRIMARY KEY, AUTO_INCREMENT, NOT NULL | Unique batch identifier |
| 2 | `batch_number` | VARCHAR | 50 | UNIQUE, NOT NULL | Alphanumeric cycle identifier (e.g. STER-20260720-001) |
| 3 | `autoclave_id` | VARCHAR | 50 | NULL | Name/ID of autoclave machine used |
| 4 | `cycle_type` | VARCHAR | 100 | DEFAULT 'Standard Autoclave 134°C' | Cycle protocol (e.g. 134°C Steam, Low Temp Plasma) |
| 5 | `started_by` | INT | 11 | FOREIGN KEY (`started_by`) REFERENCES `users`(`id`) ON DELETE SET NULL | Sterilization technician who loaded cycle |
| 6 | `status` | ENUM | — | NOT NULL, DEFAULT 'in_progress'<br>('in_progress', 'completed', 'failed') | Real-time cycle processing state |
| 7 | `started_at` | DATETIME | — | DEFAULT CURRENT_TIMESTAMP | Start timestamp of sterilization cycle |
| 8 | `completed_at` | DATETIME | — | NULL | Cycle completion and verification timestamp |
| 9 | `chemical_indicator_passed` | TINYINT | 1 | DEFAULT 0 | 1 if Class 5/6 chemical indicator strip passed, else 0 |
| 10 | `biological_indicator_passed` | TINYINT | 1 | DEFAULT 0 | 1 if Geobacillus spore test passed, else 0 |
| 11 | `notes` | TEXT | — | NULL | Sterilization process remarks and temperature notes |
| 12 | `created_at` | DATETIME | — | DEFAULT CURRENT_TIMESTAMP | Timestamp when batch was registered |

---

### Table 9: `sterilization_batch_items`
> **Purpose:** Junction table linking individual surgical tools and equipment sets included in each sterilization autoclave batch.

| SR No. | Field Name | Data Type | Size | Constraint | Description |
|:---:|---|---|:---:|---|---|
| 1 | `id` | INT | 11 | PRIMARY KEY, AUTO_INCREMENT, NOT NULL | Unique link identifier |
| 2 | `batch_id` | INT | 11 | FOREIGN KEY (`batch_id`) REFERENCES `sterilization_batches`(`id`) ON DELETE CASCADE, NOT NULL | ID of the sterilization batch |
| 3 | `equipment_id` | INT | 11 | FOREIGN KEY (`equipment_id`) REFERENCES `equipment_inventory`(`id`) ON DELETE CASCADE, NOT NULL | ID of the equipment item included in the batch |

---

### Table 10: `inquiries`
> **Purpose:** Captures customer leads, institutional quote requests, AMC queries, and contact messages submitted from the public website.

| SR No. | Field Name | Data Type | Size | Constraint | Description |
|:---:|---|---|:---:|---|---|
| 1 | `id` | INT | 11 | PRIMARY KEY, AUTO_INCREMENT, NOT NULL | Unique inquiry/lead identifier |
| 2 | `full_name` | VARCHAR | 150 | NOT NULL | Customer / Doctor / Purchase Officer name |
| 3 | `email` | VARCHAR | 150 | NOT NULL | Customer contact email address |
| 4 | `phone` | VARCHAR | 50 | NOT NULL | Customer mobile/WhatsApp phone number |
| 5 | `hospital_name` | VARCHAR | 150 | NULL | Hospital, Nursing Home, or Clinic name |
| 6 | `inquiry_type` | VARCHAR | 100 | DEFAULT 'General Inquiry' | Purpose (Quote Request, AMC, Tender, Repair, General) |
| 7 | `equipment_category` | VARCHAR | 100 | NULL | Medical department category selected |
| 8 | `message` | TEXT | — | NOT NULL | Detailed equipment requirements or custom specifications |
| 9 | `ip_address` | VARCHAR | 45 | NULL | Submitter IP address for security & logging |
| 10 | `status` | ENUM | — | DEFAULT 'new'<br>('new', 'in_progress', 'quoted', 'closed') | Lead processing status in admin panel |
| 11 | `created_at` | DATETIME | — | DEFAULT CURRENT_TIMESTAMP | Timestamp when inquiry was submitted |

---

## 🔗 Entity Relationship Summary (Foreign Keys Overview)

```
+---------------+        1:N        +-----------------------+        1:N        +-----------------------------+
|   suppliers   | ----------------> |  equipment_inventory  | ----------------> |  maintenance_records        |
+---------------+                   +-----------------------+                   +-----------------------------+
        |                                       |                                              |
        | 1:N                                   | 1:N                                          | N:1
        v                                       v                                              v
+---------------+                   +-----------------------+                   +-----------------------------+
|   purchases   |                   |     tracking_logs     |                   |            users            |
+---------------+                   +-----------------------+                   +-----------------------------+
                                                |                                              |
                                                | 1:N                                          | 1:N
                                                v                                              v
                                    +-----------------------+        N:1        +-----------------------------+
                                    |sterilization_batch_it.| <---------------- |    sterilization_batches    |
                                    +-----------------------+                   +-----------------------------+
```

---
*Generated for Surgical Equipment Management System & Public Catalogue Portal.*
