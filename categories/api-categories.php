<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = trim($_GET['action'] ?? '');

try {
    $pdo = getDbConnection();

    if ($action === 'get_categories') {
        $superId = (int) ($_GET['super_category_id'] ?? 0);
        if ($superId > 0) {
            $stmt = $pdo->prepare("SELECT id, name, slug FROM categories WHERE super_category_id = :sid AND status = 'active' ORDER BY sort_order ASC, name ASC");
            $stmt->execute([':sid' => $superId]);
        } else {
            $stmt = $pdo->query("SELECT id, super_category_id, name, slug FROM categories WHERE status = 'active' ORDER BY sort_order ASC, name ASC");
        }
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    if ($action === 'get_sub_categories') {
        $catId = (int) ($_GET['category_id'] ?? 0);
        if ($catId > 0) {
            $stmt = $pdo->prepare("SELECT id, name, slug FROM sub_categories WHERE category_id = :cid AND status = 'active' ORDER BY sort_order ASC, name ASC");
            $stmt->execute([':cid' => $catId]);
        } else {
            $stmt = $pdo->query("SELECT id, category_id, name, slug FROM sub_categories WHERE status = 'active' ORDER BY sort_order ASC, name ASC");
        }
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    echo json_encode(['error' => 'Invalid action']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
