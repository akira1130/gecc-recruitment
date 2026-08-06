<?php
/**
 * Database Setup for Railway
 * Creates tables with BLOB storage for resumes
 */

header('Content-Type: application/json');

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
$port = getenv('MYSQL_PORT') ?: getenv('DB_PORT') ?: 3306;
$dbname = getenv('MYSQL_DB_NAME') ?: getenv('DB_NAME') ?: 'gecc_db';

error_log("Setup script attempting connection to $host:$port as $user");

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname, (int)$port);

if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    echo json_encode([
        'success' => false,
        'message' => 'Connection failed: ' . $conn->connect_error,
        'host' => $host,
        'user' => $user,
        'port' => $port,
        'dbname' => $dbname
    ]);
    exit;
}

error_log("Connected successfully to database");

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
    error_log("Error creating table: " . $conn->error);
    echo json_encode([
        'success' => false,
        'message' => 'Error creating table: ' . $conn->error
    ]);
    exit;
}

error_log("Table created successfully");

$conn->close();

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
?>
