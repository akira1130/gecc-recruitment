<?php
// Router for PHP built-in server
$requested_file = __DIR__ . $_SERVER["REQUEST_URI"];

// If the requested file exists and is a file, serve it
if (is_file($requested_file)) {
    return false;
}

// Otherwise, route to index.html for frontend or handle PHP files
if (strpos($_SERVER["REQUEST_URI"], ".php") !== false) {
    // PHP file requested, let it execute
    return false;
}

// Default to index.html
$_SERVER["REQUEST_URI"] = "/index.html";
include __DIR__ . "/index.html";
return true;
?>
