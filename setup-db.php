<?php
/**
 * Database Setup Script for GECC
 * Run this once to create the database and tables
 */

// Local XAMPP credentials
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'gecc_db';

// Create connection without database first
$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die(json_encode([
        'success' => false,
        'message' => 'Connection failed: ' . $conn->connect_error
    ]));
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if (!$conn->query($sql)) {
    die(json_encode([
        'success' => false,
        'message' => 'Error creating database: ' . $conn->error
    ]));
}

// Select database
$conn->select_db($dbname);

// Create table
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
    die(json_encode([
        'success' => false,
        'message' => 'Error creating table: ' . $conn->error
    ]));
}

$conn->close();

echo json_encode([
    'success' => true,
    'message' => 'Database and table created successfully!'
]);
?>
