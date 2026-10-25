<?php
// Admin: download the current filtered list as CSV (opens in Excel).
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

[$where, $params] = concern_filters($_GET);
$sort = ($_GET['sort'] ?? 'newest') === 'oldest' ? 'ASC' : 'DESC';

$stmt = db()->prepare("SELECT * FROM student_concerns $where ORDER BY submission_date $sort, id $sort");
$stmt->execute($params);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="email-concerns-' . date('Y-m-d') . '.csv"');
header('Cache-Control: no-store');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel reads UTF-8 names correctly
fputcsv($out, ['Reference', 'Last name', 'First name', 'Middle name', 'Student ID', 'PHINMAED email', 'Personal email', 'Concern', 'Details', 'Status', 'Response', 'Handled by', 'Submitted', 'Resolved']);

// Prevent spreadsheet formula injection from student-entered text.
$safe = fn($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;

while ($r = $stmt->fetch()) {
    fputcsv($out, array_map($safe, [
        $r['reference_no'], $r['last_name'], $r['first_name'], $r['middle_name'], $r['student_id'],
        $r['phinmaed_email'], $r['personal_email'], $r['concern'], $r['details'], $r['status'],
        $r['admin_note'], $r['handled_by'], $r['submission_date'], $r['resolved_at'],
    ]));
}
fclose($out);
