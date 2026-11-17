<?php
// db_config.php - Central Database Configuration for Joule Asset Valuers
// Database credentials provided for Hostinger / cPanel MySQL

define('DB_HOST', 'localhost');
define('DB_USER', 'u948237985_JSValuersOffl');
define('DB_PASS', 'Jouleassetvaluers$#2001');
define('DB_NAME', 'u948237985_JSValuersOffl');
define('DB_PORT', 3306);

/**
 * Returns an active PDO connection to MySQL.
 * Automatically creates the inquiries table if it does not already exist.
 */
function getDbConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4;port=" . DB_PORT;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

        // Ensure submissions / inquiries table exists
        $createTableSql = "
            CREATE TABLE IF NOT EXISTS website_inquiries (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                email VARCHAR(191) NOT NULL,
                phone VARCHAR(50) NOT NULL,
                location VARCHAR(150) DEFAULT NULL,
                service VARCHAR(191) NOT NULL,
                message TEXT NOT NULL,
                page_source VARCHAR(255) DEFAULT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                user_agent VARCHAR(255) DEFAULT NULL,
                status VARCHAR(50) DEFAULT 'New',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $pdo->exec($createTableSql);

        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        throw $e;
    }
}
