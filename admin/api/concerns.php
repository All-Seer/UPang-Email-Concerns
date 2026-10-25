<?php
// Admin: paginated, filtered list of concerns.
require_once __DIR__ . '/../../includes/bootstrap.php';
require_admin_api();

$perPage = 20;
$page = max(1, (int) ($_GET['page'] ?? 1));
$sort = ($_GET['sort'] ?? 'newest') === 'oldest' ? 'ASC' : 'DESC';

[$where, $params] = concern_filters($_GET);

try {
    $pdo = db();
    $count = $pdo->prepare("SELECT COUNT(*) FROM student_concerns $where");
    $count->execute($params);
    $total = (int) $count->fetchColumn();
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min($page, $pages);

    $stmt = $pdo->prepare("SELECT * FROM student_concerns $where ORDER BY submission_date $sort, id $sort LIMIT :lim OFFSET :off");
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':off', ($page - 1) * $perPage, PDO::PARAM_INT);
    $stmt->execute();

    json_out([
        'success' => true,
        'data'    => array_map('concern_public_fields', $stmt->fetchAll()),
        'total'   => $total,
        'page'    => $page,
        'pages'   => $pages,
        'perPage' => $perPage,
    ]);
} catch (Throwable $ex) {
    error_log('admin concerns: ' . $ex->getMessage());
    json_out(['success' => false, 'message' => 'Could not load concerns.'], 500);
}
