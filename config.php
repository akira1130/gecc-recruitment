<?php
/**
 * GECC - Database Configuration
 * Supports Railway and Local Development
 */

// Load environment variables if .env exists (local development)
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
            (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
            $value = substr($value, 1, -1);
        }
        if (!empty($key) && !getenv($key)) {
            putenv("{$key}={$value}");
        }
    }
}

// Database credentials (Railway environment variables or local .env)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'gecc_db');
define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));
define('DB_CHARSET', 'utf8mb4');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Create log directory if needed and writable
$logsDir = __DIR__ . '/logs';
if (!is_dir($logsDir)) {
    @mkdir($logsDir, 0755, true);
}
if (is_writable($logsDir)) {
    ini_set('error_log', $logsDir . '/php-error.log');
}

// Debug logging
error_log('DB Connection: ' . DB_HOST . ' | User: ' . DB_USER . ' | DB: ' . DB_NAME);

// Create connection with improved error handling
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// Check connection
if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    // Don't die here - let the calling code handle it
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

