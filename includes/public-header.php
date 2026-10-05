<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | Apex Surgical & Medical Equipment' : 'Apex Surgical & Medical Equipment | Leading Medical Supplier' ?></title>
    <meta name="description" content="<?= isset($pageDescription) ? htmlspecialchars($pageDescription) : 'Apex Surgical - High-precision surgical instruments, OT equipment, ICU systems, and sterilization solutions. CE & CDSCO compliant.' ?>">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Crimson+Text:ital,wght@0,400;0,600;0,700;1,400;1,600;1,700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Stylesheet -->
    <link rel="stylesheet" href="/surgical_equipment/assets/css/public.css">
</head>
<body class="figma-body">

    <!-- FIGMA MINIMALIST NAVBAR -->
    <nav class="figma-navbar-main" id="figmaNavbarMain">
        <div class="figma-nav-inner">
            <!-- Left: Logo (fades out on scroll) -->
            <a href="/surgical_equipment/index.php" class="figma-brand-logo" id="figmaBrandLogo">
                <span style="font-weight:800; letter-spacing:-0.03em;">APEX</span>
                <span style="font-size:12px; font-weight:600; color:var(--fg-olive); text-transform:uppercase; letter-spacing:0.08em; margin-left:4px;">Surgical</span>
            </a>

            <!-- Center: Floating Capsule Navigation Links -->
            <div class="figma-nav-capsule-wrapper" id="figmaNavCapsule">
                <ul class="figma-nav-center-links" id="figmaNavLinks">
                    <li><a href="/surgical_equipment/index.php" class="<?= ($currentPage === 'index.php' || $currentPage === '') ? 'active' : '' ?>">Home</a></li>
                    <li><a href="/surgical_equipment/products.php" class="<?= ($currentPage === 'products.php' || $currentPage === 'product-details.php') ? 'active' : '' ?>">Products</a></li>
                    <li><a href="/surgical_equipment/services.php" class="<?= ($currentPage === 'services.php') ? 'active' : '' ?>">Services</a></li>
                    <li><a href="/surgical_equipment/about.php" class="<?= ($currentPage === 'about.php') ? 'active' : '' ?>">About Us</a></li>
                    <li><a href="/surgical_equipment/brands.php" class="<?= ($currentPage === 'brands.php') ? 'active' : '' ?>">Brands</a></li>
                    <li><a href="/surgical_equipment/certifications.php" class="<?= ($currentPage === 'certifications.php') ? 'active' : '' ?>">Quality & Certs</a></li>
                    <li><a href="/surgical_equipment/contact.php" class="<?= ($currentPage === 'contact.php') ? 'active' : '' ?>">Contact Us</a></li>
                </ul>
            </div>

            <!-- Right: Action Button & Mobile Toggle (fades out on scroll) -->
            <div class="figma-nav-right-actions" id="figmaNavRight">
                <a href="/surgical_equipment/contact.php#contact-form" class="figma-btn-pill figma-btn-primary figma-nav-btn">
                    <span>Request Quote ↗</span>
                </a>
                <button class="figma-mobile-menu-btn" id="figmaMobileToggle" aria-label="Open Navigation Menu">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>

        <!-- Mobile Drawer Menu -->
        <div class="figma-mobile-drawer" id="figmaMobileDrawer">
            <div class="figma-drawer-header">
                <span class="figma-brand-logo">
                    <span style="font-weight:800;">APEX</span>
                    <span style="font-size:12px; font-weight:600; color:var(--fg-olive);">SURGICAL</span>
                </span>
                <button class="figma-drawer-close" id="figmaDrawerClose" aria-label="Close Navigation Menu">&times;</button>
            </div>
            <ul class="figma-drawer-links">
                <li><a href="/surgical_equipment/index.php" class="<?= ($currentPage === 'index.php' || $currentPage === '') ? 'active' : '' ?>">Home</a></li>
                <li><a href="/surgical_equipment/products.php" class="<?= ($currentPage === 'products.php' || $currentPage === 'product-details.php') ? 'active' : '' ?>">Products</a></li>
                <li><a href="/surgical_equipment/services.php" class="<?= ($currentPage === 'services.php') ? 'active' : '' ?>">Services</a></li>
                <li><a href="/surgical_equipment/about.php" class="<?= ($currentPage === 'about.php') ? 'active' : '' ?>">About Us</a></li>
                <li><a href="/surgical_equipment/brands.php" class="<?= ($currentPage === 'brands.php') ? 'active' : '' ?>">Brands</a></li>
                <li><a href="/surgical_equipment/certifications.php" class="<?= ($currentPage === 'certifications.php') ? 'active' : '' ?>">Quality & Certs</a></li>
                <li><a href="/surgical_equipment/contact.php" class="<?= ($currentPage === 'contact.php') ? 'active' : '' ?>">Contact Us</a></li>
            </ul>
            <div class="figma-drawer-footer">
                <a href="/surgical_equipment/contact.php#contact-form" class="figma-btn-pill figma-btn-primary" style="width: 100%; text-align: center;">
                    <span>Request Quote ↗</span>
                </a>
            </div>
        </div>
    </nav>


