<?php
// Debug log
file_put_contents('otp_debug.log', date('c') . "\n" . file_get_contents('php://input') . "\n", FILE_APPEND);

// Set proper headers
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Ensure POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method. Use POST.']);
    exit;
}

// Load config
require 'login_config.php'; // defines $mysqli, SMTP_*, FROM_EMAIL, etc.

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer manually
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

// Parse JSON
$data = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');
$username = trim($data['username'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}
if (empty($username)) {
    echo json_encode(['success' => false, 'message' => 'Username is required']);
    exit;
}

// Generate OTP
$otp = rand(100000, 999999);
$expires = date('Y-m-d H:i:s', strtotime('+5 minutes'));

// Store OTP
$stmt = $mysqli->prepare("INSERT INTO signup_otps (email, otp, expires_at) VALUES (?, ?, ?) 
    ON DUPLICATE KEY UPDATE otp = VALUES(otp), expires_at = VALUES(expires_at)");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $mysqli->error]);
    exit;
}
$stmt->bind_param('sss', $email, $otp, $expires);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
    exit;
}
$stmt->close();

// Send email
try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = 'ssl';
    $mail->Port = SMTP_PORT;

    $mail->setFrom(FROM_EMAIL, FROM_NAME);
    $mail->addAddress($email, $username);
    $mail->isHTML(true);
    $mail->Subject = 'Triple Exchange Verification Code';
    $mail->Body = "
        <p>Hello <strong>$username</strong>,</p>
        <p>Your verification code is:</p>
        <h2 style='color:#0096FF;'>$otp</h2>
        <p>This code will expire in 5 minutes.</p>
        <br><p>- Triple Exchange Team</p>
    ";

    $mail->send();
    echo json_encode(['success' => true, 'message' => 'OTP sent']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Email error: ' . $mail->ErrorInfo]);
}
