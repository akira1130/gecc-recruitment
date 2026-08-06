<?php
/**
 * Check Environment Variables on Railway
 */

header('Content-Type: application/json');

$vars = [
    'DATABASE_URL' => getenv('DATABASE_URL') ? substr(getenv('DATABASE_URL'), 0, 50) . '...' : 'not set',
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

// Show all environment variables for debugging
$allEnv = getenv();
$dbRelated = [];
foreach ($allEnv as $key => $value) {
    if (stripos($key, 'db') !== false || stripos($key, 'mysql') !== false || stripos($key, 'database') !== false) {
        $dbRelated[$key] = strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value;
    }
}

echo json_encode([
    'environment' => $vars,
    'database_related_vars' => $dbRelated,
    'php_version' => phpversion(),
    'mysqli_available' => extension_loaded('mysqli') ? 'yes' : 'no',
    'cwd' => getcwd()
], JSON_PRETTY_PRINT);
?>

