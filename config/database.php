<?php
/**
 * Database connection using PDO.
 * Returns a single shared PDO instance to every file that needs it.
 */

$DB_HOST = 'localhost';
$DB_NAME = 'timesync_db';
$DB_USER = 'root';
$DB_PASS = ''; // default XAMPP password is empty

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Never show raw DB errors to the user.
    error_log('DB Connection Error: ' . $e->getMessage());
    die('<div style="font-family:sans-serif;padding:40px;text-align:center;color:#dc2626;">
        <h2>Service temporarily unavailable</h2>
        <p>We could not connect to the database. Please make sure MySQL is running in XAMPP
        and that the <code>timesync_db</code> database has been imported.</p>
        </div>');
}
