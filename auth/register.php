<?php
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (!empty($_SESSION['user_id'])) {
    if (in_array($_SESSION['role'] ?? '', ['registered_user', 'customer'])) {
        header('Location: /surgical_equipment/user/home.php');
    } else {
        header('Location: /surgical_equipment/dashboard.php');
    }
    exit;
}

$error = '';
$success = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
        $error = 'Security session expired. Please refresh the page and try again.';
    } else {
        $fullName     = trim($_POST['full_name'] ?? '');
        $organization = trim($_POST['organization'] ?? '');
        $email        = trim($_POST['email'] ?? '');
        $phone        = trim($_POST['phone'] ?? '');
        $username     = trim($_POST['username'] ?? '');
        $password     = $_POST['password'] ?? '';
        $confirmPass  = $_POST['confirm_password'] ?? '';
        $address      = trim($_POST['shipping_address'] ?? '');
        $city         = trim($_POST['city'] ?? '');
        $pincode      = trim($_POST['pincode'] ?? '');

        if ($fullName === '' || $email === '' || $username === '' || $password === '') {
            $error = 'Please fill in all required fields (Full Name, Email, Username, Password).';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($username) < 3) {
            $error = 'Username must be at least 3 characters.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif ($password !== $confirmPass) {
            $error = 'Passwords do not match. Please verify.';
        } else {
            try {
                $pdo = getDbConnection();

                // Check if username or email already exists
                $checkStmt = $pdo->prepare('SELECT id, username, email FROM users WHERE username = :u OR email = :e LIMIT 1');
                $checkStmt->execute([':u' => $username, ':e' => $email]);
                $existing = $checkStmt->fetch();

                if ($existing) {
                    if (strcasecmp($existing['username'], $username) === 0) {
                        $error = 'Username is already taken. Please choose another username.';
                    } else {
                        $error = 'An account with this email address already exists. <a href="/surgical_equipment/auth/login.php" style="color:inherit;text-decoration:underline;">Sign In here</a>.';
                    }
                } else {
                    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                    $insertStmt = $pdo->prepare('
                        INSERT INTO users (username, password_hash, full_name, email, phone, organization, shipping_address, city, pincode, role, status)
                        VALUES (:u, :p, :fn, :em, :ph, :org, :addr, :city, :pin, "registered_user", "active")
                    ');
                    $insertStmt->execute([
                        ':u'    => $username,
                        ':p'    => $passwordHash,
                        ':fn'   => $fullName,
                        ':em'   => $email,
                        ':ph'   => $phone,
                        ':org'  => $organization ?: 'Independent Medical Practitioner',
                        ':addr' => $address,
                        ':city' => $city,
                        ':pin'  => $pincode
                    ]);

                    $newUserId = (int)$pdo->lastInsertId();

                    // Log audit trail
                    $audit = $pdo->prepare('
                        INSERT INTO system_audit_trails (user_id, action, entity_type, entity_id, details, ip_address, user_agent)
                        VALUES (:uid, :action, :etype, :eid, :details, :ip, :ua)
                    ');
                    $audit->execute([
                        ':uid'     => $newUserId,
                        ':action'  => 'user.register',
                        ':etype'   => 'users',
                        ':eid'     => $newUserId,
                        ':details' => json_encode(['organization' => $organization, 'email' => $email]),
                        ':ip'      => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                        ':ua'      => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                    ]);

                    // Auto-login registered user
                    session_regenerate_id(true);
                    $_SESSION['user_id']      = $newUserId;
                    $_SESSION['username']     = $username;
                    $_SESSION['full_name']    = $fullName;
                    $_SESSION['role']         = 'registered_user';
                    $_SESSION['organization'] = $organization;
                    $_SESSION['csrf_token']   = bin2hex(random_bytes(32));
                    $_SESSION['flash_success'] = 'Welcome to APEX Surgical! Your medical account has been created successfully.';

                    header('Location: /surgical_equipment/user/home.php');
                    exit;
                }
            } catch (PDOException $e) {
                error_log('Registration error: ' . $e->getMessage());
                $error = 'A system error occurred during registration. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Account — APEX Surgical Equipment</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&family=Manrope:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --font-serif: 'Fraunces', Georgia, serif;
            --font-sans:  'Manrope', system-ui, sans-serif;
            --color-bg:      #F4F6F0;
            --color-surface: #ffffff;
            --color-border:  #E0E7DC;
            --color-text:    #1E280D;
            --color-primary: #3E531B;
            --color-primary-hover: #2E3E14;
            --color-muted:   #5E695D;
            --color-danger:  #c0392b;
            --color-lime-tint: #F1F7DD;
        }

        html, body {
            min-height: 100%;
            font-family: var(--font-sans);
            background-color: var(--color-bg);
            color: var(--color-text);
        }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 40px 20px;
            background-image:
                linear-gradient(to right, rgba(62, 83, 27, 0.08) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(62, 83, 27, 0.08) 1px, transparent 1px);
            background-size: 48px 48px;
            background-color: var(--color-bg);
        }

        .reg-container {
            width: 100%;
            max-width: 680px;
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: 16px;
            box-shadow: 0 20px 40px -15px rgba(30, 40, 13, 0.1);
            padding: 40px;
        }

        .reg-header {
            margin-bottom: 30px;
            text-align: center;
        }

        .brand-name {
            font-family: var(--font-serif);
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--color-text);
            letter-spacing: -0.03em;
            line-height: 1.1;
        }

        .brand-tagline {
            font-size: 0.9375rem;
            color: var(--color-muted);
            margin-top: 6px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .full-span {
            grid-column: 1 / -1;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .field label {
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--color-muted);
        }

        .field input, .field textarea {
            width: 100%;
            padding: 11px 14px;
            font-family: var(--font-sans);
            font-size: 0.9375rem;
            color: var(--color-text);
            background: #fafbfa;
            border: 1px solid var(--color-border);
            border-radius: 6px;
            transition: border-color 0.15s, box-shadow 0.15s;
            outline: none;
        }

        .field textarea {
            resize: vertical;
            min-height: 60px;
        }

        .field input:focus, .field textarea:focus {
            border-color: var(--color-primary);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(62, 83, 27, 0.12);
        }

        .error-msg {
            font-size: 0.875rem;
            color: var(--color-danger);
            padding: 12px 16px;
            background: #fdf2f2;
            border: 1px solid #f5c6c6;
            border-radius: 6px;
            margin-bottom: 24px;
        }

        .btn-register {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 15px 24px;
            background: var(--color-primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-family: var(--font-sans);
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s, transform 0.1s;
            margin-top: 10px;
        }

        .btn-register:hover { background: var(--color-primary-hover); }
        .btn-register:active { transform: scale(0.99); }

        .reg-footer {
            margin-top: 24px;
            text-align: center;
            font-size: 0.875rem;
            color: var(--color-muted);
        }

        .reg-footer a {
            color: var(--color-primary);
            font-weight: 700;
            text-decoration: none;
        }

        .reg-footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 640px) {
            .reg-container { padding: 24px; }
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="reg-container">
        <div class="reg-header">
            <h1 class="brand-name">APEX</h1>
            <p class="brand-tagline">Create your Verified Hospital &amp; Medical Buyer Account</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="error-msg"><?= $error ?></div>
        <?php endif; ?>

        <form method="post" action="/surgical_equipment/auth/register.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            
            <div class="form-grid">
                <div class="field full-span">
                    <label for="organization">Hospital / Clinic / Organization Name</label>
                    <input type="text" id="organization" name="organization" placeholder="e.g. Metro Multi-Speciality Surgical Hospital" value="<?= htmlspecialchars($_POST['organization'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="field">
                    <label for="full_name">Full Name (Doctor / Officer) *</label>
                    <input type="text" id="full_name" name="full_name" placeholder="e.g. Dr. Rachel Adams" value="<?= htmlspecialchars($_POST['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="field">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" placeholder="e.g. dr_rachel" value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="field">
                    <label for="email">Work / Official Email *</label>
                    <input type="email" id="email" name="email" placeholder="e.g. rachel@hospital.org" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="field">
                    <label for="phone">Phone / WhatsApp Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="e.g. +1 (555) 349-2810" value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="field">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" placeholder="Minimum 6 characters" required>
                </div>

                <div class="field">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
                </div>

                <div class="field full-span">
                    <label for="shipping_address">Delivery / Clinic Address</label>
                    <textarea id="shipping_address" name="shipping_address" placeholder="Surgical Wing, Department, Street Address"><?= htmlspecialchars($_POST['shipping_address'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="field">
                    <label for="city">City / State</label>
                    <input type="text" id="city" name="city" placeholder="e.g. Chicago, IL" value="<?= htmlspecialchars($_POST['city'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="field">
                    <label for="pincode">Postal / Zip Code</label>
                    <input type="text" id="pincode" name="pincode" placeholder="e.g. 60611" value="<?= htmlspecialchars($_POST['pincode'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="full-span">
                    <button type="submit" class="btn-register">
                        <span>Register &amp; Access Shop</span>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                    </button>
                </div>
            </div>
        </form>

        <div class="reg-footer">
            Already have an account? <a href="/surgical_equipment/auth/login.php">Sign In here</a> &bull; <a href="/surgical_equipment/index.php">Back to Home</a>
        </div>
    </div>
</body>
</html>
