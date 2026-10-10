<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['user_id'])) {
    header('Location: /surgical_equipment/user/shop.php');
    exit;
} else {
    // If not logged in, route to user shop or prompt login
    header('Location: /surgical_equipment/user/shop.php');
    exit;
}
