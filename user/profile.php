<?php
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: /surgical_equipment/auth/login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$pageTitle = "Hospital & Shipping Profile";
$error = '';

try {
    $pdo = getDbConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrf = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
            $error = 'Security session expired. Please refresh and try again.';
        } else {
            $fullName     = trim($_POST['full_name'] ?? '');
            $organization = trim($_POST['organization'] ?? '');
            $phone        = trim($_POST['phone'] ?? '');
            $address      = trim($_POST['shipping_address'] ?? '');
            $city         = trim($_POST['city'] ?? '');
            $pincode      = trim($_POST['pincode'] ?? '');
            $newPassword  = $_POST['new_password'] ?? '';

            if ($fullName === '') {
                $error = 'Full Name cannot be empty.';
            } else {
                if ($newPassword !== '') {
                    if (strlen($newPassword) < 6) {
                        $error = 'New password must be at least 6 characters.';
                    } else {
                        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
                        $upd = $pdo->prepare("
                            UPDATE users 
                            SET full_name = :fn, organization = :org, phone = :ph, shipping_address = :addr, city = :city, pincode = :pin, password_hash = :pwd, updated_at = NOW()
                            WHERE id = :id
                        ");
                        $upd->execute([
                            ':fn'   => $fullName,
                            ':org'  => $organization,
                            ':ph'   => $phone,
                            ':addr' => $address,
                            ':city' => $city,
                            ':pin'  => $pincode,
                            ':pwd'  => $hash,
                            ':id'   => $userId
                        ]);
                    }
                } else {
                    $upd = $pdo->prepare("
                        UPDATE users 
                        SET full_name = :fn, organization = :org, phone = :ph, shipping_address = :addr, city = :city, pincode = :pin, updated_at = NOW()
                        WHERE id = :id
                    ");
                    $upd->execute([
                        ':fn'   => $fullName,
                        ':org'  => $organization,
                        ':ph'   => $phone,
                        ':addr' => $address,
                        ':city' => $city,
                        ':pin'  => $pincode,
                        ':id'   => $userId
                    ]);
                }

                if ($error === '') {
                    $_SESSION['full_name'] = $fullName;
                    $_SESSION['organization'] = $organization;
                    $_SESSION['flash_success'] = 'Profile and shipping preferences updated successfully.';
                    header('Location: /surgical_equipment/user/profile.php');
                    exit;
                }
            }
        }
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Profile DB error: " . $e->getMessage());
    $user = [];
}

require_once __DIR__ . '/../includes/user-header.php';
?>

<div style="max-width:800px; margin:0 auto; display:flex; flex-direction:column; gap:24px;">

    <div>
        <h1 style="font-family:'Fraunces', Georgia, serif; font-size:2rem; font-weight:700; margin:0 0 6px; color:var(--user-dark);">
            Hospital &amp; Shipping Profile
        </h1>
        <p style="margin:0; font-size:0.875rem; color:#64748b;">
            Manage your hospital institution credentials, delivery coordinates, and account security.
        </p>
    </div>

    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="card p-4" style="border-radius:16px;">
        <form method="post" action="/surgical_equipment/user/profile.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:18px;">
                
                <div style="grid-column:1/-1;">
                    <label style="display:block; font-size:0.75rem; font-weight:700; text-transform:uppercase; margin-bottom:4px; color:#475569;">Hospital / Medical Facility Name</label>
                    <input type="text" name="organization" class="form-control" value="<?= htmlspecialchars($user['organization'] ?? '') ?>" required>
                </div>

                <div>
                    <label style="display:block; font-size:0.75rem; font-weight:700; text-transform:uppercase; margin-bottom:4px; color:#475569;">Consignee / Doctor Name *</label>
                    <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
                </div>

                <div>
                    <label style="display:block; font-size:0.75rem; font-weight:700; text-transform:uppercase; margin-bottom:4px; color:#475569;">Username (Account ID)</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['username'] ?? '') ?>" disabled style="background:#f1f5f9; cursor:not-allowed;">
                </div>

                <div>
                    <label style="display:block; font-size:0.75rem; font-weight:700; text-transform:uppercase; margin-bottom:4px; color:#475569;">Official Work Email</label>
                    <input type="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled style="background:#f1f5f9; cursor:not-allowed;">
                </div>

                <div>
                    <label style="display:block; font-size:0.75rem; font-weight:700; text-transform:uppercase; margin-bottom:4px; color:#475569;">Contact Phone / WhatsApp</label>
                    <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                </div>

                <div style="grid-column:1/-1;">
                    <label style="display:block; font-size:0.75rem; font-weight:700; text-transform:uppercase; margin-bottom:4px; color:#475569;">Default Hospital Delivery Address</label>
                    <textarea name="shipping_address" class="form-control" rows="3"><?= htmlspecialchars($user['shipping_address'] ?? '') ?></textarea>
                </div>

                <div>
                    <label style="display:block; font-size:0.75rem; font-weight:700; text-transform:uppercase; margin-bottom:4px; color:#475569;">City / State</label>
                    <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($user['city'] ?? '') ?>">
                </div>

                <div>
                    <label style="display:block; font-size:0.75rem; font-weight:700; text-transform:uppercase; margin-bottom:4px; color:#475569;">Postal / Zip Code</label>
                    <input type="text" name="pincode" class="form-control" value="<?= htmlspecialchars($user['pincode'] ?? '') ?>">
                </div>

                <div style="grid-column:1/-1; border-top:1px solid #e2e8f0; padding-top:16px; margin-top:8px;">
                    <h3 style="font-size:0.9375rem; font-weight:700; color:var(--user-dark); margin:0 0 10px;">Change Password</h3>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <div>
                            <label style="display:block; font-size:0.75rem; font-weight:700; text-transform:uppercase; margin-bottom:4px; color:#475569;">New Password (Optional)</label>
                            <input type="password" name="new_password" class="form-control" placeholder="Leave blank to keep unchanged">
                        </div>
                    </div>
                </div>

                <div style="grid-column:1/-1; display:flex; justify-content:flex-end; gap:12px; margin-top:12px;">
                    <a href="/surgical_equipment/user/home.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" style="font-weight:700; padding:12px 24px;">
                        Save Profile Updates
                    </button>
                </div>

            </div>
        </form>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/user-footer.php'; ?>
