<?php
session_start();
require 'config_backup.php'; // Your database config

header('Content-Type: application/json');

try {
    // Check if OTP was verified
    if (!isset($_SESSION['signup_verified']) || $_SESSION['signup_verified'] !== true) {
        throw new Exception("OTP verification required");
    }

    // Validate inputs
    $required = ['username', 'email', 'password', 'confirmPassword', 'terms'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Verify email matches the one that was OTP verified
    if ($_POST['email'] !== $_SESSION['signup_email']) {
        throw new Exception("Email verification mismatch");
    }

    // Check password match
    if ($_POST['password'] !== $_POST['confirmPassword']) {
        throw new Exception("Passwords don't match");
    }

    // Check terms agreement
    if ($_POST['terms'] !== 'on') {
        throw new Exception("You must agree to the terms");
    }

    // Hash password
    $passwordHash = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Insert user
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, phone, created_at) 
                          VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([
        $_POST['username'],
        $_POST['email'],
        $passwordHash,
        $_POST['phone'] ?? null
    ]);

    // Clear verification session
    unset($_SESSION['signup_verified']);
    unset($_SESSION['signup_email']);

    echo json_encode([
        'status' => 'success',
        'message' => 'Account created successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>