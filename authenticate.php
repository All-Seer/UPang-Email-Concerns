<?php
session_start();

// Admin credentials live in config/admin.php (not committed).
// Copy config/admin.example.php to config/admin.php and set your own.
$adminFile = __DIR__ . '/config/admin.php';
if (!is_file($adminFile)) {
    http_response_code(500);
    die('Admin credentials not configured. Copy config/admin.example.php to config/admin.php.');
}
$admin = require $adminFile;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (hash_equals($admin['username'], $username) && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['isAuthenticated'] = true;
        header('Location: navigationItems/dashboard.php');
        exit();
    }
}

// If credentials are invalid or not POST request
header('Location: login.php?error=1');
exit();
