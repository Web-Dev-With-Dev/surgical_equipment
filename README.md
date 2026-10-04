# Surgical Equipment Management System

A web-based Surgical Equipment Tracking and Sterilisation Management System built with PHP, MySQL, and Vanilla CSS.

---

## 🚀 Step-by-Step Guide to Run on Any Machine

Follow these exact steps when running this project on a new or different machine:

### Step 1: Install Prerequisites
* Install **[XAMPP](https://www.apachefriends.org/)** (with PHP 8.x and MySQL) on the machine.

---

### Step 2: Place Project Files in XAMPP
Copy the project folder onto the machine so its path is:
`C:\xampp\htdocs\surgical_equipment`

*(Or if keeping project in a custom folder, open PowerShell as Admin and run:)*
```cmd
mklink /J "C:\xampp\htdocs\surgical_equipment" "YOUR_PROJECT_PATH_HERE"
```

---

### Step 3: Start XAMPP & Run Setup Script
1. Open **XAMPP Control Panel** and click **Start** for **Apache** and **MySQL**.
2. Open Command Prompt (CMD) or PowerShell, navigate to the project directory:
   ```cmd
   cd C:\xampp\htdocs\surgical_equipment
   ```
3. Run the automated database setup and password synchronization script:
   ```cmd
   C:\xampp\php\php.exe setup_db.php
   ```
   > 💡 **What `setup_db.php` automatically does:**
   > - Connects to local MySQL server.
   > - Creates the `surgical_equipment_db` database and all 9 tables.
   > - Generates local `config/.env` file.
   > - Populates initial realistic demo data (10 tools, 5 suppliers, 5 POs, sterilisation & maintenance logs).
   > - Re-hashes passwords so `Password123!` works seamlessly across different machines.

---

### Step 4: Open in Browser & Log In
1. Open your browser and go to:
   **[http://localhost/surgical_equipment/auth/login.php](http://localhost/surgical_equipment/auth/login.php)**
2. Log in using any demo account:
   - **Admin**: `admin` / `Password123!`
   - **Sterilisation Tech**: `tech_alex` / `Password123!`
   - **Nurse**: `nurse_mary` / `Password123!`
   - **Surgeon**: `surgeon_john` / `Password123!`
   - **Auditor**: `auditor_david` / `Password123!`

---

## 🔑 Demo Credentials Summary

> **Default Password for ALL demo accounts:** `Password123!`

| Role | Username | Full Name | Permissions |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin` | Dr. Sarah Connor | Full system administration, equipment, user, & batch management |
| **Sterilisation Tech** | `tech_alex` | Alex Turner | Manage sterilisation logs, equipment batches, cycle tracking |
| **Nurse** | `nurse_mary` | Mary Johnson | Track & request equipment, view sterilisation logs |
| **Surgeon** | `surgeon_john` | Dr. John Smith | View equipment status, tracking, and logs |
| **Auditor** | `auditor_david` | David Miller | View audit trails, logs, reports (Read-only) |

---

## 🧪 Sterilisation Module FAQ

### Which items can be added to a Sterilisation Batch?
Any equipment from the **Equipment Inventory** that meets the following conditions:
1. **Status**: Must be set to `Available` (not currently checked out or under maintenance).
2. **Not in Active Batch**: Must not already be assigned to the selected sterilisation batch.
3. **User Permissions**: Must be logged in as an `admin` or `sterilisation_tech`.
