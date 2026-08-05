<?php
/**
 * Test Database Connection
 */

header('Content-Type: application/json');

// Check if mysqli extension is available
if (!extension_loaded('mysqli')) {
    echo json_encode([
        'error' => 'mysqli extension not loaded',
        'extensions' => get_loaded_extensions()
    ]);
    exit;
}

// Load environment
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
        if (!empty($key)) putenv("{$key}={$value}");
    }
}

$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$db = getenv('DB_NAME');

echo json_encode([
    'mysqli_loaded' => extension_loaded('mysqli'),
    'host' => $host,
    'user' => $user,
    'password' => strlen($pass) > 0 ? 'set' : 'empty',
    'database' => $db,
    'attempting_connection' => true
]);

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode([
        'error' => 'Connection failed: ' . $conn->connect_error,
        'host' => $host,
        'user' => $user
    ]);
} else {
    echo json_encode([
        'success' => true,
        'message' => 'Database connected successfully',
        'host' => $host,
        'user' => $user
    ]);
    $conn->close();
}
?>
