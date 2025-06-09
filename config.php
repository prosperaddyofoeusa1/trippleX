<?php
// SMTP configuration
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'support@trippleexchange.com');
define('SMTP_PASSWORD', 'AAaa112233!!!');
define('FROM_EMAIL', 'support@trippleexchange.com');
define('FROM_NAME', 'Tripple Exchange');

// Database connection
$mysqli = new mysqli('localhost', 'u925878138_admin', 'Chills@1008!!', 'u925878138_tripplex');

// Check connection
if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
?>