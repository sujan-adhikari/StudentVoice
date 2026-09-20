<?php
/**
 * College Complaint Management System
 * Database Connection Configuration
 * 
 * Concept: This file creates a secure connection to MySQL using PDO (PHP Data Objects).
 * PDO is preferred over mysqli because it supports prepared statements and works 
 * with multiple database engines.
 */

// Database credentials for default XAMPP setup
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'college_complaint_system');
define('DB_PORT', 3306);
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a singleton PDO database connection instance.
 * 
 * @return PDO
 */
function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        $options = [
            // Throw exceptions on SQL errors so we catch them easily
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // Return query results as associative arrays by default (e.g., $row['title'])
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Disable emulated prepares to ensure true prepared statements are sent to MySQL
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In development, show error details; in production, show a friendly message
            die("Database Connection Failed: " . htmlspecialchars($e->getMessage()));
        }
    }

    return $pdo;
}
