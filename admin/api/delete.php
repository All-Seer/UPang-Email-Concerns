<?php
// Admin: delete one or more concerns. Body: { ids: [1,2] }
require_once __DIR__ . '/../../includes/bootstrap.php';
require_admin_api();
$in = require_post_json();

$ids = array_values(array_unique(array_filter(array_map('intval', (array) ($in['ids'] ?? [])))));
if (!$ids) {
    json_out(['success' => false, 'message' => 'No concern selected.'], 422);
}

try {
    $marks = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("DELETE FROM student_concerns WHERE id IN ($marks)");
    $stmt->execute($ids);
    json_out(['success' => true, 'deleted' => $stmt->rowCount()]);
} catch (Throwable $ex) {
    error_log('admin delete: ' . $ex->getMessage());
    json_out(['success' => false, 'message' => 'Could not delete.'], 500);
}
