<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$current_page = basename($_SERVER['PHP_SELF'], '.php');

$page_title_map = [
    'dashboard'            => 'Dashboard',
    'inventory-list'       => 'Equipment Inventory',
    'add-equipment'        => 'Add Equipment',
    'equipment-details'    => 'Equipment Details',
    'tracking-logs'        => 'Tracking Logs',
    'maintenance-schedule' => 'Maintenance Schedule',
    'log-maintenance'      => 'Log Maintenance',
    'sterilization-log'    => 'Sterilisation Log',
    'reports-compliance'   => 'Compliance Reports',
    'activity-logs'        => 'Activity Log',
    'login'                => 'Sign In',
];

$html_title = ($page_title_map[$current_page] ?? ucfirst(str_replace('-', ' ', $current_page)))
    . ' — Surgical Equipment Mgmt';

$fullName = $_SESSION['full_name'] ?? 'User Name';
$initials = '';
$words = explode(' ', $fullName);
foreach ($words as $w) {
    $initials .= strtoupper(substr($w, 0, 1));
}
$initials = substr($initials, 0, 2);
if (empty($initials)) {
    $initials = 'US';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Surgical Equipment Management System">
    <title><?= htmlspecialchars($html_title, ENT_QUOTES, 'UTF-8') ?></title>

    <!-- Preconnect to Google Fonts to speed up DNS lookup -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Load fonts in parallel with stylesheet -->
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Manrope:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/surgical_equipment/assets/css/style.css">
</head>
<body>
<?php if ($current_page !== 'login'): ?>
<div class="app-layout">
<?php endif; ?>
