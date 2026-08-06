<?php
header('Content-Type: application/json');

$env = [
    '$_ENV[DB_HOST]' => $_ENV['DB_HOST'] ?? 'not set',
    '$_SERVER[DB_HOST]' => $_SERVER['DB_HOST'] ?? 'not set',
    'getenv(DB_HOST)' => getenv('DB_HOST') ?: 'not set',
    '$_ENV[DB_NAME]' => $_ENV['DB_NAME'] ?? 'not set',
    '$_SERVER[DB_NAME]' => $_SERVER['DB_NAME'] ?? 'not set',
    'getenv(DB_NAME)' => getenv('DB_NAME') ?: 'not set',
];

echo json_encode($env, JSON_PRETTY_PRINT);
?>
