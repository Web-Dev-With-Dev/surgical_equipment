<?php

require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare('
            INSERT INTO system_audit_trails
                (user_id, action, entity_type, entity_id, ip_address, user_agent)
            VALUES
                (:user_id, :action, :entity_type, :entity_id, :ip_address, :user_agent)
        ');
        $stmt->execute([
            ':user_id'     => $_SESSION['user_id'],
            ':action'      => 'user.logout',
            ':entity_type' => 'users',
            ':entity_id'   => $_SESSION['user_id'],
            ':ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ':user_agent'  => substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255),
        ]);
    } catch (PDOException $e) {

        error_log('Logout audit error: ' . $e->getMessage());
    }
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

header('Location: /surgical_equipment/auth/login.php');
exit;
