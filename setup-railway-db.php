<?php
/**
 * Database Setup for Railway
 * Creates tables with BLOB storage for resumes
 */

// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

try {
    // Load environment variables
    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        $lines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines !== false) {
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                if (!empty($key) && !getenv($key)) {
                    @putenv("{$key}={$value}");
                }
            }
        }
    }

    // Get database credentials from environment (Railway or local)
    $host = getenv('MYSQL_HOST') ?: getenv('DB_HOST') ?: 'localhost';
    $user = getenv('MYSQL_USER') ?: getenv('DB_USER') ?: 'root';
    $pass = getenv('MYSQL_PASSWORD') ?: getenv('DB_PASS') ?: '';
    $port = (int)(getenv('MYSQL_PORT') ?: getenv('DB_PORT') ?: 3306);
    $dbname = getenv('MYSQL_DB_NAME') ?: getenv('DB_NAME') ?: 'gecc_db';

    error_log("Setup: Attempting connection to $host:$port");

    // Create connection
    $conn = new mysqli($host, $user, $pass, $dbname, $port);

    if ($conn->connect_error) {
        throw new Exception('Connection failed: ' . $conn->connect_error);
    }

    error_log("Setup: Connected successfully");
} catch (Exception $e) {
    error_log("Setup error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error: ' . $e->getMessage(),
        'host' => $host ?? 'unknown',
        'user' => $user ?? 'unknown',
        'port' => $port ?? 'unknown',
        'dbname' => $dbname ?? 'unknown'
    ]);
    exit;
}

error_log("Setup: Connected successfully");

try {
    // Create applications table with BLOB storage for resumes
    $sql = "CREATE TABLE IF NOT EXISTS applications (
        id INT PRIMARY KEY AUTO_INCREMENT,
        fullName VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        phone VARCHAR(20) NOT NULL,
        experience VARCHAR(50) NOT NULL,
        background LONGTEXT NOT NULL,
        resumeFileName VARCHAR(255),
        resumeData LONGBLOB,
        resumeMimeType VARCHAR(100),
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
        throw new Exception('Error creating table: ' . $conn->error);
    }

    error_log("Setup: Table created successfully");

    $conn->close();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Database tables created successfully!',
        'details' => [
            'host' => $host,
            'database' => $dbname,
            'table' => 'applications',
            'status' => 'ready'
        ]
    ]);

} catch (Exception $e) {
    error_log("Setup table creation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error creating table: ' . $e->getMessage()
    ]);
    exit;
}
?>
