<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Sign out only via POST with a valid token, so a link on another site can't log you out.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valid($_POST['csrf'] ?? null)) {
    $_SESSION = [];
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    session_destroy();
}

header('Location: login.php');
exit;
