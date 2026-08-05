<?php
/**
 * GECC - Send Email Notifications
 * Called from admin panel to send approval/rejection emails
 */

header('Content-Type: application/json; charset=utf-8');

try {
    // Load environment variables first
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

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid input');
    }

    $status = $input['status'] ?? '';
    $fullName = $input['fullName'] ?? '';
    $email = $input['email'] ?? '';
    $reason = $input['reason'] ?? '';

    // Validate
    if (!$status || !$fullName || !$email) {
        throw new Exception('Missing required fields');
    }

    // Load the mailer
    require_once 'mailer-unified.php';

    // Initialize mailer
    $notifier = new EmailNotifier();

    // Send appropriate email
    if ($status === 'approved') {
        $result = $notifier->sendApprovalEmail($fullName, $email);
    } elseif ($status === 'rejected') {
        $result = $notifier->sendRejectionEmail($fullName, $email, $reason);
    } else {
        throw new Exception('Invalid status');
    }

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Email sent successfully to ' . $email
        ]);
    } else {
        throw new Exception('Failed to send email');
    }

} catch (Exception $e) {
    error_log('Send Notification Error: ' . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

?>
