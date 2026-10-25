<?php
require_once __DIR__ . '/../includes/bootstrap.php';
header('Location: ' . (is_admin() ? 'dashboard.php' : 'login.php'));
exit;
