<?php
// Shared setup for every page and endpoint: config, session, DB, helpers.
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
date_default_timezone_set('Asia/Manila');

const STATUSES = ['Pending', 'In Progress', 'Resolved'];
const CONCERN_TYPES = [
    'Reset Password' => ['icon' => 'lock_reset',     'hint' => 'Forgot or expired password'],
    '2FA'            => ['icon' => 'phonelink_lock', 'hint' => 'Lost access to authenticator'],
    'Others'         => ['icon' => 'help',           'hint' => 'Anything else about your account'],
];
const PHINMAED_DOMAIN = 'phinmaed.com';
const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_LOCK_MINUTES = 10;

if (session_status() === PHP_SESSION_NONE) {
    session_name('upang_ec');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = require __DIR__ . '/../config/db.php';
        $pdo = new PDO(
            "mysql:host={$c['host']};port={$c['port']};dbname={$c['dbname']};charset=utf8mb4",
            $c['user'],
            $c['pass'],
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
        $pdo->exec("SET time_zone = '+08:00'");
    }
    return $pdo;
}

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function full_name(array $r): string
{
    return trim(preg_replace('/\s+/', ' ', ($r['first_name'] ?? '') . ' ' . ($r['middle_name'] ?? '') . ' ' . ($r['last_name'] ?? '')));
}

function status_class(string $status): string
{
    return 'status-' . strtolower(str_replace(' ', '-', $status));
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_valid(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

/* ---------- JSON helpers ---------- */

function json_out(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_input(): array
{
    $data = json_decode(file_get_contents('php://input') ?: '', true);
    if (!is_array($data)) {
        json_out(['success' => false, 'message' => 'Invalid request body.'], 400);
    }
    return $data;
}

/** For JSON POST endpoints: enforce method and CSRF header. */
function require_post_json(): array
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_out(['success' => false, 'message' => 'Method not allowed.'], 405);
    }
    if (!csrf_valid($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
        json_out(['success' => false, 'message' => 'Your session expired. Refresh the page and try again.'], 419);
    }
    return json_input();
}

/* ---------- Admin auth ---------- */

function is_admin(): bool
{
    return !empty($_SESSION['admin']);
}

function require_admin(): void
{
    if (!is_admin()) {
        header('Location: login.php');
        exit;
    }
}

function require_admin_api(): void
{
    if (!is_admin()) {
        json_out(['success' => false, 'message' => 'Not signed in.'], 401);
    }
}

function admin_name(): string
{
    return (string) ($_SESSION['admin'] ?? '');
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/* ---------- Concern queries shared by admin pages ---------- */

/**
 * Build a WHERE clause from list filters.
 * Supported keys: q, status, type, range (today|week|month), from, to.
 */
function concern_filters(array $f): array
{
    $where = [];
    $params = [];

    if (!empty($f['status']) && in_array($f['status'], STATUSES, true)) {
        $where[] = 'status = :status';
        $params[':status'] = $f['status'];
    }
    if (!empty($f['type']) && array_key_exists($f['type'], CONCERN_TYPES)) {
        $where[] = 'concern = :type';
        $params[':type'] = $f['type'];
    }
    switch ($f['range'] ?? '') {
        case 'today':
            $where[] = 'submission_date >= CURDATE()';
            break;
        case 'week':
            // Sunday-start week, matches the old "This Week" page.
            $where[] = 'submission_date >= DATE_SUB(CURDATE(), INTERVAL (DAYOFWEEK(CURDATE()) - 1) DAY)';
            break;
        case 'month':
            $where[] = "submission_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')";
            break;
    }
    if (!empty($f['from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['from'])) {
        $where[] = 'submission_date >= :from';
        $params[':from'] = $f['from'] . ' 00:00:00';
    }
    if (!empty($f['to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['to'])) {
        $where[] = 'submission_date <= :to';
        $params[':to'] = $f['to'] . ' 23:59:59';
    }
    $q = trim((string) ($f['q'] ?? ''));
    if ($q !== '') {
        $cols = ['reference_no', 'first_name', 'middle_name', 'last_name', 'student_id', 'personal_email', 'phinmaed_email', 'concern', 'details'];
        $or = [];
        foreach ($cols as $i => $col) {
            $or[] = "$col LIKE :q$i";
            $params[":q$i"] = '%' . $q . '%';
        }
        $or[] = "CONCAT_WS(' ', first_name, last_name) LIKE :qfull";
        $params[':qfull'] = '%' . $q . '%';
        $where[] = '(' . implode(' OR ', $or) . ')';
    }

    return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $params];
}

function concern_public_fields(array $r): array
{
    return [
        'id'            => (int) $r['id'],
        'reference'     => $r['reference_no'],
        'name'          => full_name($r),
        'firstName'     => $r['first_name'],
        'middleName'    => $r['middle_name'],
        'lastName'      => $r['last_name'],
        'studentId'     => $r['student_id'],
        'personalEmail' => $r['personal_email'],
        'phinmaedEmail' => $r['phinmaed_email'],
        'type'          => $r['concern'],
        'details'       => $r['details'],
        'status'        => $r['status'] ?: 'Pending',
        'note'          => $r['admin_note'],
        'handledBy'     => $r['handled_by'],
        'submittedAt'   => $r['submission_date'],
        'updatedAt'     => $r['updated_at'],
        'resolvedAt'    => $r['resolved_at'],
    ];
}
