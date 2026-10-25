<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
require __DIR__ . '/partials/layout.php';

$stats = ['total' => 0, 'today' => 0, 'week' => 0, 'avgHours' => null];
$byType = [];
$daily = [];
$recent = [];
$dbError = false;

try {
    $pdo = db();
    $counts = admin_nav_counts();
    $stats['total'] = array_sum($counts);
    $stats['today'] = (int) $pdo->query('SELECT COUNT(*) FROM student_concerns WHERE submission_date >= CURDATE()')->fetchColumn();
    $stats['week'] = (int) $pdo->query('SELECT COUNT(*) FROM student_concerns WHERE submission_date >= DATE_SUB(CURDATE(), INTERVAL (DAYOFWEEK(CURDATE()) - 1) DAY)')->fetchColumn();
    $avg = $pdo->query("SELECT AVG(TIMESTAMPDIFF(MINUTE, submission_date, resolved_at)) FROM student_concerns WHERE status = 'Resolved' AND resolved_at IS NOT NULL")->fetchColumn();
    $stats['avgHours'] = $avg !== null ? round($avg / 60, 1) : null;

    foreach ($pdo->query("SELECT concern, COUNT(*) n, SUM(status <> 'Resolved') open FROM student_concerns GROUP BY concern ORDER BY n DESC") as $r) {
        $byType[] = $r;
    }

    // Last 14 days, including empty days.
    $rows = $pdo->query('SELECT DATE(submission_date) d, COUNT(*) n FROM student_concerns WHERE submission_date >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) GROUP BY d')->fetchAll(PDO::FETCH_KEY_PAIR);
    for ($i = 13; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $daily[$d] = (int) ($rows[$d] ?? 0);
    }

    $recent = $pdo->query("SELECT * FROM student_concerns WHERE status <> 'Resolved' ORDER BY submission_date ASC LIMIT 6")->fetchAll();
} catch (Throwable $ex) {
    error_log('dashboard: ' . $ex->getMessage());
    $dbError = true;
    $counts = ['Pending' => 0, 'In Progress' => 0, 'Resolved' => 0];
}

$maxDaily = max(1, ...array_values($daily ?: [0]));
$open = $counts['Pending'] + $counts['In Progress'];
$resolvedPct = $stats['total'] ? round($counts['Resolved'] / $stats['total'] * 100) : 0;

function ago(string $dt): string
{
    $s = time() - strtotime($dt);
    if ($s < 3600) return max(1, intdiv($s, 60)) . 'm ago';
    if ($s < 86400) return intdiv($s, 3600) . 'h ago';
    $d = intdiv($s, 86400);
    return $d . ' day' . ($d > 1 ? 's' : '') . ' ago';
}

layout_start('Dashboard', 'dashboard');
?>

<?php if ($dbError): ?>
    <div class="alert alert-error"><span class="material-symbols-outlined">database</span>
        <span>Can't reach the database. Check <code>config/db.php</code> and run <code>api/setup_database.php</code>.</span></div>
<?php endif; ?>

<section class="stat-grid">
    <a class="stat-card stat-accent" href="concerns.php?view=pending">
        <span class="stat-label">Waiting for action</span>
        <span class="stat-value"><?= $open ?></span>
        <span class="stat-sub"><?= $counts['Pending'] ?> pending · <?= $counts['In Progress'] ?> in progress</span>
    </a>
    <a class="stat-card" href="concerns.php?view=week">
        <span class="stat-label">This week</span>
        <span class="stat-value"><?= $stats['week'] ?></span>
        <span class="stat-sub"><?= $stats['today'] ?> today</span>
    </a>
    <a class="stat-card" href="concerns.php?view=resolved">
        <span class="stat-label">Resolved</span>
        <span class="stat-value"><?= $counts['Resolved'] ?></span>
        <span class="stat-sub"><?= $resolvedPct ?>% of <?= $stats['total'] ?> total</span>
    </a>
    <div class="stat-card">
        <span class="stat-label">Avg. time to resolve</span>
        <span class="stat-value"><?= $stats['avgHours'] === null ? '–' : ($stats['avgHours'] < 48 ? $stats['avgHours'] . '<small>h</small>' : round($stats['avgHours'] / 24, 1) . '<small>d</small>') ?></span>
        <span class="stat-sub">from submission to resolved</span>
    </div>
</section>

<section class="dash-grid">
    <div class="card">
        <div class="card-head">
            <h2>Submissions, last 14 days</h2>
            <span class="muted"><?= array_sum($daily) ?> total</span>
        </div>
        <div class="bar-chart" role="img" aria-label="Daily submissions over the last 14 days">
            <?php foreach ($daily as $d => $n): ?>
                <div class="bar-col" title="<?= e(date('D, M j', strtotime($d))) ?>: <?= $n ?>">
                    <span class="bar-val"><?= $n ?: '' ?></span>
                    <span class="bar" style="height: <?= $n ? max(4, round($n / $maxDaily * 100)) : 0 ?>%"></span>
                    <span class="bar-day <?= $d === date('Y-m-d') ? 'today' : '' ?>"><?= e(date('j', strtotime($d))) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <h2>By concern type</h2>
        </div>
        <?php if (!$byType): ?>
            <p class="muted">No data yet.</p>
        <?php else: ?>
            <ul class="type-list">
                <?php foreach ($byType as $t):
                    $icon = CONCERN_TYPES[$t['concern']]['icon'] ?? 'label'; ?>
                    <li>
                        <a href="concerns.php?type=<?= urlencode($t['concern']) ?>">
                            <span class="type-icon material-symbols-outlined"><?= e($icon) ?></span>
                            <span class="type-name"><?= e($t['concern']) ?></span>
                            <span class="type-meter"><span style="width: <?= round($t['n'] / max(1, $stats['total']) * 100) ?>%"></span></span>
                            <span class="type-n"><?= (int) $t['n'] ?></span>
                        </a>
                        <?php if ((int) $t['open']): ?><span class="type-open"><?= (int) $t['open'] ?> open</span><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>

<section class="card">
    <div class="card-head">
        <h2>Oldest open concerns</h2>
        <a href="concerns.php?view=pending&sort=oldest" class="link">View queue <span class="material-symbols-outlined">arrow_forward</span></a>
    </div>
    <?php if (!$recent): ?>
        <div class="empty">
            <span class="material-symbols-outlined">celebration</span>
            <p>All caught up. No open concerns.</p>
        </div>
    <?php else: ?>
        <ul class="queue-list">
            <?php foreach ($recent as $r): ?>
                <li>
                    <a href="concerns.php?q=<?= urlencode($r['reference_no']) ?>&open=<?= (int) $r['id'] ?>">
                        <span class="queue-main">
                            <strong><?= e(full_name($r)) ?></strong>
                            <span class="muted"><?= e($r['concern']) ?> · <?= e($r['student_id']) ?></span>
                        </span>
                        <span class="pill <?= status_class($r['status']) ?>"><?= e($r['status']) ?></span>
                        <span class="muted queue-age"><?= e(ago($r['submission_date'])) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<?php layout_end(); ?>
