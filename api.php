<?php
/**
 * GECC Recruitment API
 * Clean, minimal backend
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    // Get database credentials from environment
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $user = $_ENV['DB_USER'] ?? 'root';
    $pass = $_ENV['DB_PASS'] ?? '';
    $port = (int)($_ENV['DB_PORT'] ?? 3306);
    $dbname = $_ENV['DB_NAME'] ?? 'railway';

    // Connect to database
    $conn = new mysqli($host, $user, $pass, $dbname, $port);

    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    $conn->set_charset('utf8mb4');

    // Ensure table exists
    $sql = "CREATE TABLE IF NOT EXISTS applications (
        id INT PRIMARY KEY AUTO_INCREMENT,
        fullName VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE KEY,
        phone VARCHAR(20) NOT NULL,
        experience VARCHAR(50) NOT NULL,
        background LONGTEXT NOT NULL,
        resumeFileName VARCHAR(255),
        resumeData LONGBLOB,
        resumeMimeType VARCHAR(100),
        terms TINYINT(1) DEFAULT 0,
        status VARCHAR(50) DEFAULT 'pending',
        appliedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $conn->query($sql);

    // Route handling - just handle POST
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        // Validate form data
        $fullName = trim($_POST['fullName'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $experience = trim($_POST['experience'] ?? '');
        $background = trim($_POST['background'] ?? '');
        $terms = isset($_POST['terms']) ? 1 : 0;

        // Validation
        if (empty($fullName) || strlen($fullName) < 2) {
            throw new Exception('Full name is required');
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Valid email is required');
        }

        if (empty($phone)) {
            throw new Exception('Phone is required');
        }

        if (empty($experience) || !in_array($experience, ['0-1', '1-3', '3-5', '5+'])) {
            throw new Exception('Valid experience level is required');
        }

        if (empty($background) || strlen($background) < 50) {
            throw new Exception('Background must be at least 50 characters');
        }

        if (!$terms) {
            throw new Exception('You must agree to the terms');
        }

        if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Resume file is required');
        }

        $file = $_FILES['resume'];

        // Validate file
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new Exception('File size exceeds 10MB limit');
        }

        if ($file['size'] < 100) {
            throw new Exception('File is too small');
        }

        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowed = ['application/pdf', 'application/msword', 
                   'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

        if (!in_array($mimeType, $allowed)) {
            throw new Exception('Invalid file type. Only PDF, DOC, DOCX allowed.');
        }

        // Read file data
        $resumeData = file_get_contents($file['tmp_name']);
        if (!$resumeData) {
            throw new Exception('Could not read file');
        }

        // Check for duplicate email
        $stmt = $conn->prepare("SELECT id FROM applications WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception('Email already has an application');
        }
        $stmt->close();

        // Insert application
        $stmt = $conn->prepare(
            "INSERT INTO applications (fullName, email, phone, experience, background, resumeFileName, resumeData, resumeMimeType, terms) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            'ssssssssi',
            $fullName, $email, $phone, $experience, $background,
            $file['name'], $resumeData, $mimeType, $terms
        );

        if (!$stmt->execute()) {
            throw new Exception('Could not save application');
        }

        $appId = $stmt->insert_id;
        $stmt->close();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Application submitted! We\'ll review it soon.',
            'applicationId' => $appId
        ]);

    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }

    $conn->close();

} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
