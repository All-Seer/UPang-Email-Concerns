<?php

$host = "localhost";
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS users");
    $pdo->exec("USE users");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS student_concerns (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(50) NOT NULL,
            middle_name VARCHAR(50) NULL,
            last_name VARCHAR(50) NOT NULL,
            student_id VARCHAR(20) NOT NULL,
            personal_email VARCHAR(100) NOT NULL,
            phinmaed_email VARCHAR(100) NOT NULL,
            concern TEXT NOT NULL,
            submission_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            status ENUM('Pending', 'Resolved') DEFAULT 'Pending',
            INDEX (status),
            INDEX (submission_date)
        )
    ");

    // Upgrade tables created by older versions of this script.
    $hasPhinmaed = $pdo->query("SHOW COLUMNS FROM student_concerns LIKE 'phinmaed_email'")->fetch();
    if (!$hasPhinmaed) {
        $pdo->exec("ALTER TABLE student_concerns ADD COLUMN phinmaed_email VARCHAR(100) NOT NULL DEFAULT '' AFTER personal_email");
    }

    // The form's middle name is optional, so the column must accept NULL.
    $pdo->exec("ALTER TABLE student_concerns MODIFY middle_name VARCHAR(50) NULL");

    echo "Database and table are ready!";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
