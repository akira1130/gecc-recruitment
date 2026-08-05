<?php
/**
 * GECC - Database Configuration
 * Infinity Free Hosting Version
 */

// ===== INFINITY FREE HOSTING CREDENTIALS =====
define('DB_HOST', 'sql200.infinityfree.com');
define('DB_USER', 'if0_42363894');
define('DB_PASS', 'cR3R3MxdIApDq2');
define('DB_NAME', 'if0_42363894_gecc');
define('DB_PORT', 3306);
define('DB_CHARSET', 'utf8mb4');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Create log directory if needed
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

ini_set('error_log', __DIR__ . '/logs/php-error.log');

// Debug logging
error_log('DB Connection: ' . DB_HOST . ' | User: ' . DB_USER . ' | DB: ' . DB_NAME);

// Create connection with improved error handling
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// Check connection
if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    die(json_encode([
        'success' => false,
        'message' => 'Database connection failed. Please check your credentials.'
    ]));
}

// Set charset and variables
$conn->set_charset(DB_CHARSET);
$conn->query("SET SESSION sql_mode='STRICT_TRANS_TABLES'");

// Create tables if they don't exist
$sql = "CREATE TABLE IF NOT EXISTS applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fullName VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    experience VARCHAR(50) NOT NULL,
    background LONGTEXT NOT NULL,
    resume VARCHAR(255),
    terms TINYINT(1) DEFAULT 0,
    status VARCHAR(50) DEFAULT 'pending',
    rejectionReason LONGTEXT,
    appliedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    reviewedAt DATETIME NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_appliedAt (appliedAt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if (!$conn->query($sql)) {
    error_log('Failed to create table: ' . $conn->error);
}

// Function to sanitize input
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
}

?>

