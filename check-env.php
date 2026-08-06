<?php
/**
 * Check Environment Variables on Railway
 */

header('Content-Type: application/json');

$vars = [
    'MYSQL_HOST' => getenv('MYSQL_HOST'),
    'MYSQL_USER' => getenv('MYSQL_USER'),
    'MYSQL_PASSWORD' => getenv('MYSQL_PASSWORD') ? '***set***' : 'not set',
    'MYSQL_PORT' => getenv('MYSQL_PORT'),
    'MYSQL_DB_NAME' => getenv('MYSQL_DB_NAME'),
    'DB_HOST' => getenv('DB_HOST'),
    'DB_USER' => getenv('DB_USER'),
    'DB_PASS' => getenv('DB_PASS') ? '***set***' : 'not set',
    'DB_NAME' => getenv('DB_NAME'),
    'DB_PORT' => getenv('DB_PORT'),
];

echo json_encode([
    'environment' => $vars,
    'php_version' => phpversion(),
    'mysqli_available' => extension_loaded('mysqli') ? 'yes' : 'no',
    'cwd' => getcwd()
], JSON_PRETTY_PRINT);
?>
