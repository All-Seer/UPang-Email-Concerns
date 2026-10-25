<?php
// Shared admin shell: sidebar + top bar. Use layout_start('Title', 'nav-key') ... layout_end().

function admin_nav_counts(): array
{
    static $counts = null;
    if ($counts === null) {
        $counts = ['Pending' => 0, 'In Progress' => 0, 'Resolved' => 0];
        try {
            foreach (db()->query('SELECT status, COUNT(*) n FROM student_concerns GROUP BY status') as $r) {
                $counts[$r['status']] = (int) $r['n'];
            }
        } catch (Throwable $ex) {
            error_log('nav counts: ' . $ex->getMessage());
        }
    }
    return $counts;
}

function layout_start(string $title, string $active): void
{
    $c = admin_nav_counts();
    $nav = [
        ['dashboard', 'dashboard.php', 'space_dashboard', 'Dashboard', null],
        ['all', 'concerns.php', 'inbox', 'All concerns', null],
        ['pending', 'concerns.php?view=pending', 'pending_actions', 'Pending', $c['Pending']],
        ['progress', 'concerns.php?view=progress', 'autorenew', 'In progress', $c['In Progress']],
        ['resolved', 'concerns.php?view=resolved', 'task_alt', 'Resolved', null],
        ['week', 'concerns.php?view=week', 'date_range', 'This week', null],
    ];
    ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title) ?> · Email Concerns Admin</title>
    <link rel="icon" href="../public/upangnavlogo.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0..1,0&display=block" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body class="admin">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <img src="../public/upanglogoo-1.png" alt="" width="40" height="40">
            <div>
                <strong>Email Concerns</strong>
                <span>PHINMA UPang Urdaneta</span>
            </div>
        </div>
        <nav class="sidebar-nav" aria-label="Admin">
            <?php foreach ($nav as [$key, $href, $icon, $label, $count]): ?>
                <a href="<?= e($href) ?>" class="<?= $key === $active ? 'active' : '' ?>" <?= $key === $active ? 'aria-current="page"' : '' ?>>
                    <span class="material-symbols-outlined"><?= e($icon) ?></span>
                    <span class="nav-label"><?= e($label) ?></span>
                    <?php if ($count): ?><span class="nav-count"><?= (int) $count ?></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-foot">
            <div class="who">
                <span class="avatar"><?= e(mb_strtoupper(mb_substr(admin_name(), 0, 1))) ?></span>
                <span><?= e(admin_name()) ?></span>
            </div>
            <form method="POST" action="logout.php">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <button type="submit" class="icon-btn" title="Sign out" aria-label="Sign out">
                    <span class="material-symbols-outlined">logout</span>
                </button>
            </form>
        </div>
    </aside>
    <div class="scrim" id="scrim"></div>

    <div class="shell">
        <header class="topbar">
            <button class="icon-btn menu-btn" id="menuBtn" aria-label="Open menu">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="page-title"><?= e($title) ?></h1>
            <form class="global-search" action="concerns.php" method="GET" role="search">
                <span class="material-symbols-outlined">search</span>
                <input type="search" name="q" placeholder="Search name, ID, email, reference" aria-label="Search concerns" value="<?= e($_GET['q'] ?? '') ?>">
                <kbd>/</kbd>
            </form>
        </header>
        <main class="content">
<?php
}

function layout_end(array $scripts = []): void
{
    ?>
        </main>
    </div>

    <div class="toast-stack" id="toasts" aria-live="polite"></div>
    <script src="../assets/js/admin-common.js"></script>
    <?php foreach ($scripts as $s): ?>
        <script src="<?= e($s) ?>"></script>
    <?php endforeach; ?>
</body>

</html>
<?php
}
