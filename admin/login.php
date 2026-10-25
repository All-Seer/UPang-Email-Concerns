<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (is_admin()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$username = '';
$adminFile = __DIR__ . '/../config/admin.php';
$configured = is_file($adminFile);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $configured) {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    try {
        $pdo = db();
        $recent = $pdo->prepare('SELECT COUNT(*) FROM admin_login_attempts WHERE ip = :ip AND attempted_at > DATE_SUB(NOW(), INTERVAL ' . LOGIN_LOCK_MINUTES . ' MINUTE)');
        $recent->execute([':ip' => client_ip()]);
        $locked = (int) $recent->fetchColumn() >= LOGIN_MAX_ATTEMPTS;
    } catch (Throwable $ex) {
        error_log('login: ' . $ex->getMessage());
        $pdo = null;
        $locked = false;
    }

    $admin = require $adminFile;

    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif ($locked) {
        $error = 'Too many failed attempts. Try again in ' . LOGIN_LOCK_MINUTES . ' minutes.';
    } elseif (
        $admin['password_hash'] !== ''
        && hash_equals((string) $admin['username'], $username)
        && password_verify($password, $admin['password_hash'])
    ) {
        if ($pdo) {
            $pdo->prepare('DELETE FROM admin_login_attempts WHERE ip = :ip')->execute([':ip' => client_ip()]);
        }
        session_regenerate_id(true);
        $_SESSION['admin'] = $username;
        unset($_SESSION['csrf']);
        header('Location: dashboard.php');
        exit;
    } else {
        if ($pdo) {
            $pdo->prepare('INSERT INTO admin_login_attempts (ip) VALUES (:ip)')->execute([':ip' => client_ip()]);
        }
        $error = 'Incorrect username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin sign in · UPang Email Concerns</title>
    <link rel="icon" href="../public/upangnavlogo.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0..1,0&display=block" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body class="login-page">
    <main class="login-card">
        <div class="login-brand">
            <img src="../public/upanglogoo-1.png" alt="PHINMA University of Pangasinan" width="72" height="72">
            <div>
                <p class="eyebrow">Admin console</p>
                <h1>Email Concerns</h1>
            </div>
        </div>

        <?php if (!$configured): ?>
            <div class="alert alert-error" role="alert">
                <span class="material-symbols-outlined">settings_alert</span>
                <span>Admin login isn't set up yet. Copy <code>config/admin.example.php</code> to <code>config/admin.php</code> and set a password hash.</span>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-error" role="alert">
                    <span class="material-symbols-outlined">error</span>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="stack" autocomplete="on">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <label class="field">
                    <span class="field-label">Username</span>
                    <input type="text" name="username" value="<?= e($username) ?>" autocomplete="username" required autofocus>
                </label>
                <label class="field">
                    <span class="field-label">Password</span>
                    <span class="input-affix">
                        <input type="password" name="password" id="password" autocomplete="current-password" required>
                        <button type="button" class="icon-btn" id="togglePassword" aria-label="Show password">
                            <span class="material-symbols-outlined">visibility</span>
                        </button>
                    </span>
                </label>
                <button type="submit" class="btn btn-primary btn-block">Sign in</button>
            </form>
        <?php endif; ?>

        <p class="login-foot">Authorized personnel only. Failed attempts are rate limited.</p>
    </main>

    <script>
        const toggle = document.getElementById('togglePassword');
        if (toggle) {
            toggle.addEventListener('click', () => {
                const input = document.getElementById('password');
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                toggle.querySelector('span').textContent = show ? 'visibility_off' : 'visibility';
                toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        }
    </script>
</body>

</html>
