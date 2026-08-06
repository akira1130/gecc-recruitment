<?php
/**
 * GECC - Application Handler
 * Security-Improved Backend
 * 
 * Features:
 * - Enhanced input validation
 * - Secure file upload handling
 * - Proper error handling
 * - Prepared statements
 * - MIME type validation
 */

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST, PUT');

// Error handling
set_error_handler(function ($errno, $errstr) {
    error_log("PHP Error: $errstr ($errno)");
    return true;
});

try {
    // Include config with absolute path
    $configFile = __DIR__ . '/config.php';
    if (!file_exists($configFile)) {
        throw new Exception('Config file not found at: ' . $configFile);
    }
    require_once $configFile;
    
    error_log("Config loaded successfully");
    
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection failed: ' . ($conn->connect_error ?? 'Unknown error'));
    }
    
    error_log("Database connected successfully");

    // Create uploads directory
    $uploadsDir = __DIR__ . '/uploads/resumes/';
    // Note: Files are now stored in database, not on filesystem

    // Handle POST (submit application)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        error_log("POST received. Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
        error_log("POST data: " . json_encode($_POST));
        error_log("FILES: " . json_encode(array_keys($_FILES)));
        
        // Sanitize all POST data
        $_POST = sanitizeInput($_POST);

        // Extract and validate input
        $fullName = trim($_POST['fullName'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $experience = trim($_POST['experience'] ?? '');
        $background = trim($_POST['background'] ?? '');
        $terms = isset($_POST['terms']) ? 1 : 0;

        error_log("Form data - Name: $fullName, Email: $email, Phone: $phone, Exp: $experience");

        // Validation rules
        if (empty($fullName) || strlen($fullName) < 2 || strlen($fullName) > 255) {
            throw new Exception('Full name must be between 2 and 255 characters');
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address');
        }

        if (empty($phone) || !preg_match('/^[\d\s\-\(\)\+]+$/', $phone)) {
            throw new Exception('Please enter a valid phone number');
        }

        if (empty($experience) || !in_array($experience, ['0-1', '1-3', '3-5', '5+'])) {
            throw new Exception('Please select a valid experience level');
        }

        if (empty($background) || strlen($background) < 50) {
            throw new Exception('Background must be at least 50 characters');
        }

        if (!$terms) {
            throw new Exception('You must agree to the terms of service');
        }

        // Handle file upload - store in database
        if (!isset($_FILES['resume'])) {
            throw new Exception('No file uploaded');
        }

        $file = $_FILES['resume'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $file['error']);
        }

        // Validate file
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new Exception('File size exceeds 10MB limit');
        }

        if ($file['size'] < 100) {
            throw new Exception('File appears to be empty');
        }

        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception('Invalid file type. Only PDF, DOC, and DOCX are allowed.');
        }

        // Validate extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['pdf', 'doc', 'docx'])) {
            throw new Exception('Invalid file extension');
        }

        // Read file content for database storage
        $resumeData = file_get_contents($file['tmp_name']);
        if ($resumeData === false) {
            throw new Exception('Failed to read uploaded file');
        }

        $resumeFileName = $file['name'];

        // Check for duplicate email
        $stmt = $conn->prepare("SELECT id FROM applications WHERE email = ?");
        if (!$stmt) {
            throw new Exception('Database error');
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            $stmt->close();
            throw new Exception('An application with this email already exists');
        }
        $stmt->close();

        // Insert application with file data stored in database
        $stmt = $conn->prepare(
            "INSERT INTO applications (fullName, email, phone, experience, background, resumeFileName, resumeData, resumeMimeType, terms) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            throw new Exception('Database error');
        }

        $stmt->bind_param("ssssssssi", $fullName, $email, $phone, $experience, $background, $resumeFileName, $resumeData, $mimeType, $terms);

        if (!$stmt->execute()) {
            $stmt->close();
            throw new Exception('Failed to save application');
        }

        $applicationId = $stmt->insert_id;
        $stmt->close();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Thank you for your application! We will review your CV and contact you within 48 hours.',
            'applicationId' => $applicationId
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get applications (for admin panel)
        $result = $conn->query(
            "SELECT id, fullName, email, phone, experience, resume, status, appliedAt FROM applications ORDER BY appliedAt DESC"
        );

        if (!$result) {
            throw new Exception('Query failed');
        }

        $applications = [];
        while ($row = $result->fetch_assoc()) {
            $applications[] = $row;
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'applications' => $applications
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // Update application status
        parse_str(file_get_contents("php://input"), $input);
        $input = sanitizeInput($input);

        $id = (int)($input['id'] ?? 0);
        $status = $input['status'] ?? '';
        $reason = $input['rejectionReason'] ?? '';

        if (!$id || !in_array($status, ['approved', 'rejected', 'pending'])) {
            throw new Exception('Invalid parameters');
        }

        if ($status === 'rejected' && strlen($reason) < 10) {
            throw new Exception('Rejection reason must be at least 10 characters');
        }

        $stmt = $conn->prepare(
            "UPDATE applications SET status = ?, reviewedAt = NOW()" .
            ($reason ? ", rejectionReason = ?" : "") .
            " WHERE id = ?"
        );

        if (!$stmt) {
            throw new Exception('Database error');
        }

        if ($reason) {
            $stmt->bind_param("ssi", $status, $reason, $id);
        } else {
            $stmt->bind_param("si", $status, $id);
        }

        if (!$stmt->execute()) {
            throw new Exception('Failed to update application');
        }

        $stmt->close();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Application updated successfully'
        ]);

    } else {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
    }

} catch (Exception $e) {
    error_log('Application error: ' . $e->getMessage());

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);

} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

?>
