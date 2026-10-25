<?php
// Creates or upgrades the database. Safe to re-run.
// Runs only from the command line (php api/setup_database.php) or from this machine (localhost).
declare(strict_types=1);

$isCli = PHP_SAPI === 'cli';
if (!$isCli && !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Run this setup from the server itself.');
}
if (!$isCli) {
    header('Content-Type: text/plain; charset=UTF-8');
}

$c = require __DIR__ . '/../config/db.php';

try {
    $pdo = new PDO("mysql:host={$c['host']};port={$c['port']};charset=utf8mb4", $c['user'], $c['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $db = str_replace('`', '', $c['dbname']);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$db`");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS student_concerns (
            id INT AUTO_INCREMENT PRIMARY KEY,
            reference_no VARCHAR(20) NULL,
            first_name VARCHAR(50) NOT NULL,
            middle_name VARCHAR(50) NULL,
            last_name VARCHAR(50) NOT NULL,
            student_id VARCHAR(20) NOT NULL,
            personal_email VARCHAR(100) NOT NULL,
            phinmaed_email VARCHAR(100) NOT NULL,
            concern VARCHAR(50) NOT NULL,
            details TEXT NULL,
            submission_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            status ENUM('Pending', 'In Progress', 'Resolved') NOT NULL DEFAULT 'Pending',
            admin_note TEXT NULL,
            handled_by VARCHAR(50) NULL,
            updated_at DATETIME NULL,
            resolved_at DATETIME NULL,
            INDEX (status),
            INDEX (submission_date),
            INDEX (student_id)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
    ");

    // Upgrade tables created by older versions.
    $columns = array_column($pdo->query('SHOW COLUMNS FROM student_concerns')->fetchAll(PDO::FETCH_ASSOC), 'Field');
    $add = [
        'phinmaed_email' => "VARCHAR(100) NOT NULL DEFAULT '' AFTER personal_email",
        'reference_no'   => 'VARCHAR(20) NULL AFTER id',
        'details'        => 'TEXT NULL AFTER concern',
        'admin_note'     => 'TEXT NULL',
        'handled_by'     => 'VARCHAR(50) NULL',
        'updated_at'     => 'DATETIME NULL',
        'resolved_at'    => 'DATETIME NULL',
    ];
    foreach ($add as $col => $def) {
        if (!in_array($col, $columns, true)) {
            $pdo->exec("ALTER TABLE student_concerns ADD COLUMN $col $def");
            echo "Added column $col\n";
        }
    }

    $pdo->exec("UPDATE student_concerns SET status = 'Pending' WHERE status IS NULL OR status = ''");
    $pdo->exec("ALTER TABLE student_concerns MODIFY status ENUM('Pending', 'In Progress', 'Resolved') NOT NULL DEFAULT 'Pending'");
    $pdo->exec('ALTER TABLE student_concerns MODIFY middle_name VARCHAR(50) NULL');
    $pdo->exec('ALTER TABLE student_concerns MODIFY concern VARCHAR(50) NOT NULL');

    // Give older rows a reference number so students can track them.
    $missing = $pdo->query("SELECT id, submission_date FROM student_concerns WHERE reference_no IS NULL OR reference_no = ''")->fetchAll(PDO::FETCH_ASSOC);
    $set = $pdo->prepare('UPDATE student_concerns SET reference_no = :ref WHERE id = :id');
    foreach ($missing as $row) {
        $date = date('ymd', strtotime((string) $row['submission_date']) ?: time());
        $set->execute([':ref' => sprintf('EC-%s-%04d', $date, $row['id']), ':id' => $row['id']]);
    }
    if ($missing) {
        echo 'Assigned reference numbers to ' . count($missing) . " existing concern(s)\n";
    }

    $hasIndex = $pdo->query("SHOW INDEX FROM student_concerns WHERE Key_name = 'uniq_reference'")->fetch();
    if (!$hasIndex) {
        $pdo->exec('ALTER TABLE student_concerns ADD UNIQUE KEY uniq_reference (reference_no)');
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip VARCHAR(45) NOT NULL,
            attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX (ip, attempted_at)
        )
    ");

    echo "Database and tables are ready.\n";
} catch (PDOException $e) {
    http_response_code(500);
    exit('Setup failed: ' . $e->getMessage() . "\n");
}
