<?php
ob_start();

require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /surgical_equipment/auth/login.php');
    exit;
}

$role = $_SESSION['role'] ?? '';
$pageTitle = 'Web Inquiries & Quote Requests';
$current_page = 'inquiries';

$inquiries = [];
$stats = [
    'total' => 0,
    'new' => 0,
    'quotes' => 0,
];

try {
    $pdo = getDbConnection();

    // Ensure table exists
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

    // Handle status update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
        $inquiryId = (int)$_POST['inquiry_id'];
        $newStatus = $_POST['status'] ?? 'new';
        $updateStmt = $pdo->prepare("UPDATE inquiries SET status = :status WHERE id = :id");
        $updateStmt->execute([':status' => $newStatus, ':id' => $inquiryId]);
        $_SESSION['flash_success'] = 'Inquiry status updated successfully.';
        header('Location: /surgical_equipment/reports/inquiries.php');
        exit;
    }

    $stmt = $pdo->query("SELECT * FROM inquiries ORDER BY created_at DESC");
    $inquiries = $stmt->fetchAll();

    $stats['total'] = count($inquiries);
    foreach ($inquiries as $inq) {
        if ($inq['status'] === 'new') $stats['new']++;
        if (stripos($inq['inquiry_type'], 'quote') !== false) $stats['quotes']++;
    }

} catch (PDOException $e) {
    error_log('Inquiry fetch error: ' . $e->getMessage());
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Website Inquiries & Quotation Leads</h1>
            <p class="page-breadcrumb">
                <a href="/surgical_equipment/dashboard.php">Home</a> / Leads & Inquiries
            </p>
        </div>
        <div class="flex gap-2">
            <a href="/surgical_equipment/contact.php" target="_blank" class="btn btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                <span>View Public Contact Page</span>
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success" style="padding:12px 16px; margin-bottom:20px; background:#dcfce7; color:#166534; border-radius:8px;">
            <?= htmlspecialchars($_SESSION['flash_success']) ?>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <!-- Summary KPI Cards -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:20px; margin-bottom:24px;">
        <div class="card" style="padding:20px;">
            <span class="text-muted" style="font-size:0.8125rem;">Total Inquiries Received</span>
            <div style="font-size:1.75rem; font-weight:700; margin-top:4px;"><?= $stats['total'] ?></div>
        </div>
        <div class="card" style="padding:20px; border-left:4px solid #0284c7;">
            <span class="text-muted" style="font-size:0.8125rem;">New / Unaddressed Leads</span>
            <div style="font-size:1.75rem; font-weight:700; color:#0284c7; margin-top:4px;"><?= $stats['new'] ?></div>
        </div>
        <div class="card" style="padding:20px; border-left:4px solid #10b981;">
            <span class="text-muted" style="font-size:0.8125rem;">Price Quote Requests</span>
            <div style="font-size:1.75rem; font-weight:700; color:#10b981; margin-top:4px;"><?= $stats['quotes'] ?></div>
        </div>
    </div>

    <!-- Leads Table -->
    <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <h3 class="card-title">All Customer Inquiries</h3>
            <span class="text-muted" style="font-size:0.8125rem;"><?= count($inquiries) ?> records</span>
        </div>

        <?php if (count($inquiries) > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Contact Person</th>
                            <th>Hospital / Organization</th>
                            <th>Phone / WhatsApp</th>
                            <th>Inquiry Purpose & Category</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inquiries as $inq): ?>
                            <tr>
                                <td style="white-space:nowrap; font-size:0.8125rem;" class="text-muted">
                                    <?= date('M j, Y g:i A', strtotime($inq['created_at'])) ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($inq['full_name']) ?></strong><br>
                                    <span class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($inq['email']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($inq['hospital_name'] ?: '—') ?></td>
                                <td>
                                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $inq['phone']) ?>?text=<?= urlencode('Hello ' . $inq['full_name'] . ', thank you for contacting Apex Surgical regarding your inquiry.') ?>" target="_blank" style="color:#16a34a; font-weight:600; display:inline-flex; align-items:center; gap:4px;">
                                        <?= htmlspecialchars($inq['phone']) ?>
                                    </a>
                                </td>
                                <td>
                                    <span style="font-weight:600; font-size:0.8125rem; display:block;"><?= htmlspecialchars($inq['inquiry_type']) ?></span>
                                    <?php if (!empty($inq['equipment_category'])): ?>
                                        <span class="status-badge" style="font-size:0.6875rem; margin-top:2px;"><?= htmlspecialchars($inq['equipment_category']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="max-width:240px; font-size:0.8125rem;">
                                    <?= nl2br(htmlspecialchars($inq['message'])) ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $inq['status'] === 'new' ? 'pending' : ($inq['status'] === 'quoted' ? 'available' : 'in_use') ?>">
                                        <?= ucfirst(htmlspecialchars($inq['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display:inline-flex; gap:4px; align-items:center;">
                                        <input type="hidden" name="inquiry_id" value="<?= $inq['id'] ?>">
                                        <select name="status" style="padding:4px 8px; font-size:0.75rem; border-radius:4px; border:1px solid #ccc;">
                                            <option value="new" <?= $inq['status'] === 'new' ? 'selected' : '' ?>>New</option>
                                            <option value="in_progress" <?= $inq['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                            <option value="quoted" <?= $inq['status'] === 'quoted' ? 'selected' : '' ?>>Quoted</option>
                                            <option value="closed" <?= $inq['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn btn-secondary" style="padding:4px 8px; font-size:0.75rem;">Save</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="text-align:center; padding:48px 20px;">
                <p class="text-muted">No website inquiries or quotation requests received yet.</p>
                <a href="/surgical_equipment/contact.php" target="_blank" class="btn btn-primary" style="margin-top:12px; display:inline-block;">Test Contact Form</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
