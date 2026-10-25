<?php
// Public endpoint: a student submits a concern.
require_once __DIR__ . '/../includes/bootstrap.php';

$in = require_post_json();

// Honeypot: real users never fill this hidden field.
if (!empty($in['website'])) {
    json_out(['success' => true, 'reference' => 'EC-000000-0000']);
}

$clean = fn($k, $max) => mb_substr(trim((string) ($in[$k] ?? '')), 0, $max);

$data = [
    'firstName'     => $clean('firstName', 50),
    'middleName'    => $clean('middleName', 50),
    'lastName'      => $clean('lastName', 50),
    'studentId'     => strtoupper($clean('studentId', 20)),
    'personalEmail' => strtolower($clean('personalEmail', 100)),
    'phinmaedEmail' => strtolower($clean('phinmaedEmail', 100)),
    'concern'       => $clean('concern', 50),
    'details'       => $clean('details', 1000),
];

$errors = [];
foreach (['firstName' => 'First name', 'lastName' => 'Last name', 'studentId' => 'Student ID'] as $k => $label) {
    if ($data[$k] === '') {
        $errors[$k] = "$label is required.";
    }
}
if ($data['studentId'] !== '' && !preg_match('/^[0-9A-Z-]{6,20}$/', $data['studentId'])) {
    $errors['studentId'] = 'Use numbers and dashes only, as shown on your ID.';
}
if (!filter_var($data['personalEmail'], FILTER_VALIDATE_EMAIL)) {
    $errors['personalEmail'] = 'Enter a valid email address.';
}
if (!filter_var($data['phinmaedEmail'], FILTER_VALIDATE_EMAIL) || !str_ends_with($data['phinmaedEmail'], '@' . PHINMAED_DOMAIN)) {
    $errors['phinmaedEmail'] = 'Must end with @' . PHINMAED_DOMAIN . '.';
}
if (!isset($errors['personalEmail']) && $data['personalEmail'] === $data['phinmaedEmail']) {
    $errors['personalEmail'] = 'Use an email other than your PHINMAED account, so we can reach you.';
}
if (!array_key_exists($data['concern'], CONCERN_TYPES)) {
    $errors['concern'] = 'Choose what you need help with.';
}
if ($data['concern'] === 'Others' && mb_strlen($data['details']) < 10) {
    $errors['details'] = 'Tell us a bit more (at least 10 characters).';
}

if ($errors) {
    json_out(['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors], 422);
}

try {
    $pdo = db();

    // Don't open a second ticket for the same open problem.
    $dup = $pdo->prepare("SELECT reference_no FROM student_concerns
        WHERE student_id = :sid AND concern = :type AND status <> 'Resolved'
        ORDER BY submission_date DESC LIMIT 1");
    $dup->execute([':sid' => $data['studentId'], ':type' => $data['concern']]);
    if ($existing = $dup->fetchColumn()) {
        json_out([
            'success'   => false,
            'duplicate' => true,
            'reference' => $existing,
            'message'   => "You already have an open {$data['concern']} concern ($existing). Track it instead of submitting again.",
        ], 409);
    }

    $insert = $pdo->prepare("INSERT INTO student_concerns
        (reference_no, first_name, middle_name, last_name, student_id, personal_email, phinmaed_email, concern, details, status, submission_date)
        VALUES (:ref, :fn, :mn, :ln, :sid, :pe, :pm, :type, :details, 'Pending', NOW())");

    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    for ($try = 0; $try < 5; $try++) {
        $suffix = '';
        for ($i = 0; $i < 4; $i++) {
            $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $ref = 'EC-' . date('ymd') . '-' . $suffix;
        try {
            $insert->execute([
                ':ref'     => $ref,
                ':fn'      => $data['firstName'],
                ':mn'      => $data['middleName'] ?: null,
                ':ln'      => $data['lastName'],
                ':sid'     => $data['studentId'],
                ':pe'      => $data['personalEmail'],
                ':pm'      => $data['phinmaedEmail'],
                ':type'    => $data['concern'],
                ':details' => $data['details'] ?: null,
            ]);
            json_out(['success' => true, 'reference' => $ref]);
        } catch (PDOException $ex) {
            if ($ex->errorInfo[1] !== 1062) { // not a duplicate reference
                throw $ex;
            }
        }
    }
    throw new RuntimeException('Could not generate a reference number.');
} catch (Throwable $ex) {
    error_log('submit_concern: ' . $ex->getMessage());
    json_out(['success' => false, 'message' => 'Something went wrong on our side. Please try again in a moment.'], 500);
}
