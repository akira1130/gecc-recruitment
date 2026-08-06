<?php
/**
 * Test Database Connection
 */

header('Content-Type: application/json');

try {
    // Get variables using $_ENV or getenv
    $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'localhost';
    $user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?? 'root';
    $pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? '';
    $port = (int)($_ENV['DB_PORT'] ?? getenv('DB_PORT') ?? 3306);
    $dbname = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'railway';

    error_log("Connection attempt: $host:$port user=$user db=$dbname");

    // Try to connect
    $conn = new mysqli($host, $user, $pass, $dbname, $port);

    if ($conn->connect_error) {
        throw new Exception('Connection failed: ' . $conn->connect_error);
    }

    // Test query
    $result = $conn->query("SELECT 1 as test");
    if (!$result) {
        throw new Exception('Query failed: ' . $conn->error);
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Connected to database successfully!',
        'connection' => [
            'host' => $host,
            'database' => $dbname,
            'port' => $port,
            'user' => $user
        ],
        'test_query' => 'SELECT 1 executed successfully'
    ]);

    $conn->close();

} catch (Exception $e) {
    error_log("Connection error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'attempted_connection' => [
            'host' => $host ?? 'unknown',
            'database' => $dbname ?? 'unknown',
            'port' => $port ?? 'unknown',
            'user' => $user ?? 'unknown'
        ]
    ]);
}
?>
