<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /surgical_equipment/contact.php');
    exit;
}

$fullName         = trim($_POST['full_name'] ?? '');
$email            = trim($_POST['email'] ?? '');
$phone            = trim($_POST['phone'] ?? '');
$hospitalName     = trim($_POST['hospital_name'] ?? '');
$inquiryType      = trim($_POST['inquiry_type'] ?? 'General Inquiry');
$equipmentCategory = trim($_POST['equipment_category'] ?? '');
$message          = trim($_POST['message'] ?? '');
$ipAddress        = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

// Validation
$errors = [];
if (empty($fullName)) {
    $errors[] = 'Please enter your Full Name.';
}
if (empty($phone)) {
    $errors[] = 'Please enter your Phone / Mobile number for WhatsApp/Call follow-up.';
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please provide a valid Email address.';
}
if (empty($message)) {
    $errors[] = 'Please provide details about your equipment requirement or inquiry.';
}

if (!empty($errors)) {
    $_SESSION['pub_error'] = implode(' ', $errors);
    $_SESSION['form_data'] = $_POST;
    header('Location: /surgical_equipment/contact.php#contact-form');
    exit;
}

try {
    $pdo = getDbConnection();

    // Auto-create table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `inquiries` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `full_name` VARCHAR(150) NOT NULL,
            `email` VARCHAR(150) NOT NULL,
            `phone` VARCHAR(50) NOT NULL,
            `hospital_name` VARCHAR(150) DEFAULT NULL,
            `inquiry_type` VARCHAR(100) DEFAULT 'General Inquiry',
            `equipment_category` VARCHAR(100) DEFAULT NULL,
            `message` TEXT NOT NULL,
            `ip_address` VARCHAR(45) DEFAULT NULL,
            `status` ENUM('new', 'in_progress', 'quoted', 'closed') DEFAULT 'new',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $stmt = $pdo->prepare("
        INSERT INTO `inquiries` (`full_name`, `email`, `phone`, `hospital_name`, `inquiry_type`, `equipment_category`, `message`, `ip_address`)
        VALUES (:full_name, :email, :phone, :hospital_name, :inquiry_type, :equipment_category, :message, :ip_address)
    ");

    $stmt->execute([
        ':full_name'          => $fullName,
        ':email'              => $email,
        ':phone'              => $phone,
        ':hospital_name'      => $hospitalName ?: null,
        ':inquiry_type'       => $inquiryType,
        ':equipment_category' => $equipmentCategory ?: null,
        ':message'            => $message,
        ':ip_address'         => $ipAddress,
    ]);

    unset($_SESSION['form_data']);
    $_SESSION['pub_success'] = 'Thank you, ' . htmlspecialchars($fullName) . '! Your inquiry/quote request has been received successfully. Our surgical biomedical team will contact you shortly.';

} catch (PDOException $e) {
    error_log('Inquiry Submission Error: ' . $e->getMessage());
    $_SESSION['pub_error'] = 'A temporary database error occurred. Please call us directly or message us on WhatsApp.';
}

header('Location: /surgical_equipment/contact.php#contact-form');
exit;
