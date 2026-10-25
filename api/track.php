<?php
// Public endpoint: a student looks up a concern by reference number + student ID.
require_once __DIR__ . '/../includes/bootstrap.php';

$in = require_post_json();
$ref = strtoupper(trim((string) ($in['reference'] ?? '')));
$sid = strtoupper(trim((string) ($in['studentId'] ?? '')));

if ($ref === '' || $sid === '') {
    json_out(['success' => false, 'message' => 'Enter both your reference number and student ID.'], 422);
}

try {
    $stmt = db()->prepare('SELECT * FROM student_concerns WHERE reference_no = :ref AND student_id = :sid LIMIT 1');
    $stmt->execute([':ref' => $ref, ':sid' => $sid]);
    $row = $stmt->fetch();
} catch (Throwable $ex) {
    error_log('track: ' . $ex->getMessage());
    json_out(['success' => false, 'message' => 'Something went wrong on our side. Please try again.'], 500);
}

if (!$row) {
    json_out(['success' => false, 'message' => 'No concern matches that reference number and student ID.'], 404);
}

json_out([
    'success' => true,
    'data' => [
        'reference'   => $row['reference_no'],
        'type'        => $row['concern'],
        'status'      => $row['status'],
        'note'        => $row['admin_note'],
        'submittedAt' => $row['submission_date'],
        'updatedAt'   => $row['updated_at'],
        'resolvedAt'  => $row['resolved_at'],
    ],
]);
