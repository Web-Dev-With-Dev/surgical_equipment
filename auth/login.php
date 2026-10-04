<?php

require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['user_id'])) {
    header('Location: /surgical_equipment/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $pdo  = getDbConnection();
            $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :u LIMIT 1');
            $stmt->execute([':u' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['username']   = $user['username'];
                $_SESSION['full_name']  = $user['full_name'];
                $_SESSION['role']       = $user['role'];
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                $audit = $pdo->prepare('
                    INSERT INTO system_audit_trails (user_id, action, entity_type, entity_id, ip_address, user_agent)
                    VALUES (:uid, :action, :etype, :eid, :ip, :ua)
                ');
                $audit->execute([
                    ':uid'    => $user['id'],
                    ':action' => 'user.login',
                    ':etype'  => 'users',
                    ':eid'    => $user['id'],
                    ':ip'     => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                    ':ua'     => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                ]);

                header('Location: /surgical_equipment/dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
                sleep(1);
            }
        } catch (PDOException $e) {
            error_log('Login error: ' . $e->getMessage());
            $error = 'A system error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — Surgical Equipment Mgmt</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Manrope:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --font-serif: 'Fraunces', Georgia, serif;
            --font-sans:  'Manrope', system-ui, sans-serif;
            --color-bg:      #f7f7f5;
            --color-surface: #ffffff;
            --color-border:  #e4e4e4;
            --color-text:    #0a0a0a;
            --color-muted:   #777;
            --color-danger:  #c0392b;
        }

        html, body {
            height: 100%;
            font-family: var(--font-sans);
            background-color: var(--color-bg);
            color: var(--color-text);
        }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 24px;
            /* Subtle grid background like the reference */
            background-image:
                linear-gradient(to right, #e8e8e6 1px, transparent 1px),
                linear-gradient(to bottom, #e8e8e6 1px, transparent 1px);
            background-size: 48px 48px;
            background-color: var(--color-bg);
        }

        .login-container {
            width: 100%;
            max-width: 480px;
        }

        /* Big serif brand name — the hero element */
        .brand-name {
            font-family: var(--font-serif);
            font-size: clamp(2.8rem, 6vw, 4rem);
            font-weight: 600;
            color: var(--color-text);
            letter-spacing: -0.03em;
            line-height: 1.05;
            margin-bottom: 8px;
        }

        .brand-tagline {
            font-family: var(--font-sans);
            font-size: 0.875rem;
            color: var(--color-muted);
            font-weight: 400;
            margin-bottom: 40px;
            letter-spacing: 0.01em;
        }

        .form-section {
            display: flex;
            flex-direction: column;
            gap: 22px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .field label {
            font-family: var(--font-sans);
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--color-muted);
        }

        .field input {
            width: 100%;
            padding: 13px 16px;
            font-family: var(--font-sans);
            font-size: 0.9375rem;
            color: var(--color-text);
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: 6px;
            transition: border-color 0.15s, box-shadow 0.15s;
            outline: none;
        }

        .field input::placeholder {
            color: #bbb;
        }

        .field input:focus {
            border-color: #999;
            box-shadow: 0 0 0 3px rgba(0,0,0,0.06);
        }

        .error-msg {
            font-size: 0.8125rem;
            color: var(--color-danger);
            padding: 10px 14px;
            background: #fdf2f2;
            border: 1px solid #f5c6c6;
            border-radius: 6px;
        }

        .btn-signin {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 15px 24px;
            background: var(--color-text);
            color: #fff;
            border: none;
            border-radius: 6px;
            font-family: var(--font-sans);
            font-size: 0.9375rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s, transform 0.1s;
            letter-spacing: 0.01em;
            margin-top: 6px;
        }

        .btn-signin:hover { background: #222; }
        .btn-signin:active { transform: scale(0.99); }

        .btn-signin svg {
            transition: transform 0.15s;
        }
        .btn-signin:hover svg {
            transform: translateX(3px);
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--color-muted);
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--color-border);
        }

        .hint-box {
            margin-top: 28px;
            padding: 14px 16px;
            border: 1px solid var(--color-border);
            border-radius: 6px;
            background: var(--color-surface);
        }

        .hint-box .hint-title {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--color-text);
            margin-bottom: 5px;
        }

        .hint-box .hint-users {
            font-size: 0.75rem;
            color: var(--color-muted);
            line-height: 1.6;
        }

        .hint-box .hint-users a {
            color: var(--color-text);
            font-weight: 600;
            text-decoration: none;
            cursor: default;
        }
    </style>
</head>
<body>
    <div class="login-container">

        <h1 class="brand-name">Surgical Equipment</h1>
        <p class="brand-tagline">Management System — Sign in to continue</p>

        <?php if ($error !== ''): ?>
            <div class="error-msg" style="margin-bottom:20px;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" action="/surgical_equipment/auth/login.php">
            <div class="form-section">

                <div class="field">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="e.g. admin"
                        autocomplete="username"
                        required
                        value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="••••••••••••"
                        autocomplete="current-password"
                        required>
                </div>

                <button type="submit" class="btn-signin">
                    Sign In
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </button>

            </div>
        </form>

        <div class="hint-box">
            <div class="hint-title">Demo credentials &mdash; password: <code>Password123!</code></div>
            <div class="hint-users">
                <a>admin</a> &nbsp;/&nbsp;
                <a>surgeon_john</a> &nbsp;/&nbsp;
                <a>nurse_mary</a> &nbsp;/&nbsp;
                <a>tech_alex</a> &nbsp;/&nbsp;
                <a>auditor_david</a>
            </div>
        </div>

    </div>
</body>
</html>
