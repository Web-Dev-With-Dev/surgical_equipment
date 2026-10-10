<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require login for user portal
if (empty($_SESSION['user_id'])) {
    $_SESSION['flash_error'] = 'Please sign in to access your registered hospital portal.';
    header('Location: /surgical_equipment/auth/login.php');
    exit;
}

$currentUser = [
    'id'           => $_SESSION['user_id'] ?? 0,
    'username'     => $_SESSION['username'] ?? 'user',
    'full_name'    => $_SESSION['full_name'] ?? 'Medical Buyer',
    'role'         => $_SESSION['role'] ?? 'registered_user',
    'organization' => $_SESSION['organization'] ?? 'Medical Facility',
];

$activePage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | APEX Registered Portal' : 'Hospital Portal | APEX Surgical' ?></title>
    <meta name="description" content="APEX Surgical Equipment Registered Customer &amp; Hospital Procurement Portal.">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&family=Manrope:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="/surgical_equipment/assets/css/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/surgical_equipment/assets/css/public.css?v=<?= time() ?>">
    <style>
        :root {
            --user-primary: #3E531B;
            --user-primary-light: #F1F7DD;
            --user-accent: #0284c7;
            --user-dark: #1E280D;
            --user-card-bg: #ffffff;
            --user-border: #E0E7DC;
        }

        body.user-portal-body {
            background-color: #F6F8F3;
            font-family: 'Manrope', system-ui, sans-serif;
            color: #1E280D;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Top Navigation Header for Registered User */
        .user-portal-navbar {
            background: #ffffff;
            border-bottom: 1px solid var(--user-border);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 20px -8px rgba(30, 40, 13, 0.08);
        }

        .user-portal-navbar-inner {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 24px;
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .user-nav-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--user-dark);
        }

        .user-nav-brand .brand-logo {
            font-family: 'Fraunces', Georgia, serif;
            font-size: 1.625rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            color: var(--user-dark);
        }

        .user-nav-brand .brand-badge {
            font-size: 0.6875rem;
            font-weight: 700;
            background: var(--user-primary-light);
            color: var(--user-primary);
            padding: 3px 8px;
            border-radius: 999px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .user-nav-links {
            display: flex;
            align-items: center;
            gap: 8px;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .user-nav-link {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 16px;
            border-radius: 999px;
            font-size: 0.875rem;
            font-weight: 600;
            color: #475569;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .user-nav-link:hover {
            color: var(--user-primary);
            background: rgba(62, 83, 27, 0.06);
        }

        .user-nav-link.active {
            color: #ffffff;
            background: var(--user-primary);
        }

        .user-nav-actions {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        /* Cart Floating Button */
        .user-cart-btn {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #ffffff;
            border: 1px solid var(--user-border);
            padding: 8px 16px;
            border-radius: 999px;
            font-size: 0.875rem;
            font-weight: 700;
            color: var(--user-dark);
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .user-cart-btn:hover {
            border-color: var(--user-primary);
            background: var(--user-primary-light);
        }

        .user-cart-badge {
            background: #dc2626;
            color: #ffffff;
            font-size: 0.6875rem;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 999px;
            line-height: 1;
        }

        .user-account-dropdown {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 4px 8px 4px 4px;
            border-radius: 999px;
            background: #F4F6F0;
            border: 1px solid var(--user-border);
            text-decoration: none;
            color: inherit;
        }

        .user-avatar-circle {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: var(--user-primary);
            color: #ffffff;
            font-weight: 700;
            font-size: 0.8125rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-account-info {
            line-height: 1.2;
            padding-right: 6px;
        }

        .user-account-name {
            font-size: 0.8125rem;
            font-weight: 700;
            color: var(--user-dark);
            display: block;
            max-width: 140px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-account-org {
            font-size: 0.6875rem;
            color: #64748b;
            display: block;
            max-width: 140px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-portal-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 24px 60px;
            width: 100%;
            flex: 1;
        }

        /* Responsive */
        @media (max-width: 960px) {
            .user-nav-links { display: none; }
            .user-account-info { display: none; }
        }
    </style>
</head>
<body class="user-portal-body">

    <!-- USER PORTAL NAVBAR -->
    <header class="user-portal-navbar">
        <div class="user-portal-navbar-inner">
            <a href="/surgical_equipment/user/home.php" class="user-nav-brand">
                <span class="brand-logo">APEX</span>
                <span class="brand-badge">Hospital Portal</span>
            </a>

            <ul class="user-nav-links">
                <li>
                    <a href="/surgical_equipment/user/home.php" class="user-nav-link <?= $activePage === 'home' ? 'active' : '' ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="/surgical_equipment/user/shop.php" class="user-nav-link <?= $activePage === 'shop' ? 'active' : '' ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                        <span>Shop Surgical Equipment</span>
                    </a>
                </li>
                <li>
                    <a href="/surgical_equipment/user/orders.php" class="user-nav-link <?= in_array($activePage, ['orders', 'view-order']) ? 'active' : '' ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                        <span>My Orders</span>
                    </a>
                </li>
                <li>
                    <a href="/surgical_equipment/index.php" class="user-nav-link" target="_blank">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                        <span>Public Site</span>
                    </a>
                </li>
            </ul>

            <div class="user-nav-actions">
                <button type="button" class="user-cart-btn" onclick="openCartDrawer()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                    <span>Order Cart</span>
                    <span class="user-cart-badge" id="navCartCount">0</span>
                </button>

                <div class="user-account-dropdown">
                    <div class="user-avatar-circle">
                        <?= strtoupper(substr($currentUser['full_name'], 0, 1)) ?>
                    </div>
                    <div class="user-account-info">
                        <span class="user-account-name"><?= htmlspecialchars($currentUser['full_name']) ?></span>
                        <span class="user-account-org"><?= htmlspecialchars($currentUser['organization'] ?: 'Medical Buyer') ?></span>
                    </div>
                </div>

                <a href="/surgical_equipment/auth/logout.php" class="btn btn-secondary btn-sm" style="padding:6px 12px; font-size:0.75rem;" title="Sign Out">
                    Sign Out
                </a>
            </div>
        </div>
    </header>

    <div class="user-portal-container">
