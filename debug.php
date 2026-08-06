<?php
header('Content-Type: application/json');

$env = [
    'getenv(DB_HOST)' => getenv('DB_HOST') ?: 'not set',
    'getenv(DB_USER)' => getenv('DB_USER') ?: 'not set',
    'getenv(DB_PASS)' => getenv('DB_PASS') ?: 'not set',
    'getenv(DB_PORT)' => getenv('DB_PORT') ?: 'not set',
    'getenv(DB_NAME)' => getenv('DB_NAME') ?: 'not set',
    'getenv(DATABASE_URL)' => getenv('DATABASE_URL') ?: 'not set',
];

// Get first 500 chars of DATABASE_URL if exists
if (getenv('DATABASE_URL')) {
    $url = getenv('DATABASE_URL');
    $env['DATABASE_URL_PREVIEW'] = substr($url, 0, 100) . (strlen($url) > 100 ? '...' : '');
}

echo json_encode($env, JSON_PRETTY_PRINT);
?>
