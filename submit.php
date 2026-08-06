<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Log all requests
error_log("Submit.php called. Method: " . $_SERVER['REQUEST_METHOD']);

try {
    // Use SQLite - no connection issues
    $dbFile = __DIR__ . '/data/applications.db';
    
    // Create data directory if needed
    if (!is_dir(__DIR__ . '/data')) {
        @mkdir(__DIR__ . '/data', 0755, true);
        error_log("Created data directory");
    }

    error_log("Connecting to SQLite: " . $dbFile);
    $db = new PDO('sqlite:' . $dbFile);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create table if needed
    $db->exec("CREATE TABLE IF NOT EXISTS applications (
        id INTEGER PRIMARY KEY,
        fullName TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        phone TEXT NOT NULL,
        experience TEXT NOT NULL,
        background TEXT NOT NULL,
        resumeFileName TEXT,
        resumeData BLOB,
        terms INTEGER,
        createdAt DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    // Validate input
    $fullName = trim($_POST['fullName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $background = trim($_POST['background'] ?? '');
    $terms = isset($_POST['terms']) ? 1 : 0;

    if (!$fullName || strlen($fullName) < 2) {
        throw new Exception('Full name required');
    }

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Valid email required');
    }

    if (!$phone) {
        throw new Exception('Phone required');
    }

    if (!in_array($experience, ['0-1', '1-3', '3-5', '5+'])) {
        throw new Exception('Valid experience required');
    }

    if (strlen($background) < 50) {
        throw new Exception('Background must be at least 50 characters');
    }

    if (!$terms) {
        throw new Exception('You must agree to terms');
    }

    // Handle file
    if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Resume file required');
    }

    $file = $_FILES['resume'];

    if ($file['size'] > 10 * 1024 * 1024) {
        throw new Exception('File too large');
    }

    $resumeData = file_get_contents($file['tmp_name']);
    if (!$resumeData) {
        throw new Exception('Could not read file');
    }

    // Insert
    $stmt = $db->prepare(
        "INSERT INTO applications (fullName, email, phone, experience, background, resumeFileName, resumeData, terms) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->execute([$fullName, $email, $phone, $experience, $background, $file['name'], $resumeData, $terms]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Application submitted! We\'ll review it soon.'
    ]);

} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
