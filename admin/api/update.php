<?php
// Admin: change status and/or response note for one or more concerns.
// Body: { ids: [1,2], status?: "Resolved", note?: "..." }
require_once __DIR__ . '/../../includes/bootstrap.php';
require_admin_api();
$in = require_post_json();

$ids = array_values(array_unique(array_filter(array_map('intval', (array) ($in['ids'] ?? [])))));
if (!$ids) {
    json_out(['success' => false, 'message' => 'No concern selected.'], 422);
}

$sets = ['updated_at = NOW()', 'handled_by = :by'];
$params = [':by' => admin_name()];

if (array_key_exists('status', $in)) {
    if (!in_array($in['status'], STATUSES, true)) {
        json_out(['success' => false, 'message' => 'Unknown status.'], 422);
    }
    $sets[] = 'status = :status';
    $sets[] = "resolved_at = CASE WHEN :status2 = 'Resolved' THEN COALESCE(resolved_at, NOW()) ELSE NULL END";
    $params[':status'] = $in['status'];
    $params[':status2'] = $in['status'];
}
if (array_key_exists('note', $in)) {
    $note = mb_substr(trim((string) $in['note']), 0, 2000);
    $sets[] = 'admin_note = :note';
    $params[':note'] = $note === '' ? null : $note;
}
if (count($sets) === 2) {
    json_out(['success' => false, 'message' => 'Nothing to update.'], 422);
}

$marks = [];
foreach ($ids as $i => $id) {
    $marks[] = ":id$i";
    $params[":id$i"] = $id;
}
$inList = implode(',', $marks);

try {
    $pdo = db();
    $stmt = $pdo->prepare('UPDATE student_concerns SET ' . implode(', ', $sets) . " WHERE id IN ($inList)");
    $stmt->execute($params);

    $idParams = array_filter($params, fn($k) => str_starts_with($k, ':id'), ARRAY_FILTER_USE_KEY);
    $rows = $pdo->prepare("SELECT * FROM student_concerns WHERE id IN ($inList)");
    $rows->execute($idParams);

    json_out([
        'success' => true,
        'updated' => $stmt->rowCount(),
        'data'    => array_map('concern_public_fields', $rows->fetchAll()),
    ]);
} catch (Throwable $ex) {
    error_log('admin update: ' . $ex->getMessage());
    json_out(['success' => false, 'message' => 'Could not save changes.'], 500);
}
