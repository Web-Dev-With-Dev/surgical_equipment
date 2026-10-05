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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Stylesheet -->
    <link rel="stylesheet" href="/surgical_equipment/assets/css/public.css">
</head>
<body>

    <!-- TOPBAR -->
    <header class="pub-topbar">
        <div class="pub-container pub-topbar-inner">
            <div class="pub-topbar-left">
                <span class="pub-badge-tag">CE & CDSCO Compliant</span>
                <span class="pub-topbar-divider">|</span>
                <span>Authorized Medical Device Supplier</span>
            </div>
            <div class="pub-topbar-right">
                <a href="tel:+917863884678" class="pub-topbar-link">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                    +91 78638 84678
                </a>
                <a href="mailto:swetazala05@gmail.com" class="pub-topbar-link">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    swetazala05@gmail.com
                </a>
                <a href="/surgical_equipment/auth/login.php" class="pub-topbar-portal">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                    Staff Portal
                </a>
            </div>
        </div>
    </header>

    <!-- MAIN NAVBAR -->
    <nav class="pub-navbar">
        <div class="pub-container pub-nav-inner">
            <a href="/surgical_equipment/index.php" class="pub-logo">
                <div class="pub-logo-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M2 12h20M7 7l10 10M17 7L7 17"/></svg>
                </div>
                <div class="pub-logo-text">
                    <span class="pub-brand-title">APEX</span>
                    <span class="pub-brand-subtitle">SURGICAL & MEDICAL</span>
                </div>
            </a>

            <button class="pub-mobile-toggle" id="pubMobileToggle" aria-label="Toggle menu">
                <span></span><span></span><span></span>
            </button>

            <div class="pub-nav-menu" id="pubNavMenu">
                <ul class="pub-nav-links">
                    <li><a href="/surgical_equipment/index.php" class="<?= ($currentPage === 'index.php' || $currentPage === '') ? 'active' : '' ?>">Home</a></li>
                    <li><a href="/surgical_equipment/products.php" class="<?= ($currentPage === 'products.php' || $currentPage === 'product-details.php') ? 'active' : '' ?>">Products</a></li>
                    <li><a href="/surgical_equipment/services.php" class="<?= ($currentPage === 'services.php') ? 'active' : '' ?>">Services</a></li>
                    <li><a href="/surgical_equipment/about.php" class="<?= ($currentPage === 'about.php') ? 'active' : '' ?>">About Us</a></li>
                    <li><a href="/surgical_equipment/brands.php" class="<?= ($currentPage === 'brands.php') ? 'active' : '' ?>">Brands</a></li>
                    <li><a href="/surgical_equipment/certifications.php" class="<?= ($currentPage === 'certifications.php') ? 'active' : '' ?>">Quality & Certs</a></li>
                    <li><a href="/surgical_equipment/contact.php" class="<?= ($currentPage === 'contact.php') ? 'active' : '' ?>">Contact Us</a></li>
                </ul>

                <div class="pub-nav-actions">
                    <a href="/surgical_equipment/contact.php#quote" class="pub-btn pub-btn-primary">
                        <span>Request Quote</span>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                    </a>
                </div>
            </div>
        </div>
    </nav>
