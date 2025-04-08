<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

// Check if session variables are set and code is verified
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['code_verified']) || $_SESSION['code_verified'] !== true) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access. Please follow the proper password reset process.'
    ]);
    exit();
}

// Function for logging
function logError($message) {
    $logFile = 'password_reset_log.txt';
    $timestamp = date('[Y-m-d H:i:s] ');
    file_put_contents($logFile, $timestamp . $message . PHP_EOL, FILE_APPEND);
}

try {
    // Get the new password
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';
    
    // Validate password
    if (empty($password)) {
        throw new Exception('Password is required.');
    }
    
    if (strlen($password) < 8) {
        throw new Exception('Password must be at least 8 characters long.');
    }
    
    // Check if passwords match
    if ($password !== $confirm_password) {
        throw new Exception('Passwords do not match.');
    }
    
    // Get email from session
    $email = $_SESSION['reset_email'];
    
    // Hash the new password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Update password in database
    $conn = connectDB();
    if (!$conn) {
        throw new Exception('Database connection failed.');
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update the password
        $stmt = $conn->prepare("UPDATE users SET password = ?, is_first_login = 0 WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $email);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update password: ' . $stmt->error);
        }
        
        // Check if any rows were affected
        if ($stmt->affected_rows === 0) {
            throw new Exception('No user found with this email address.');
        }
        
        // Commit transaction
        $conn->commit();
        
        // Clear session variables
        unset($_SESSION['reset_email']);
        unset($_SESSION['verification_code']);
        unset($_SESSION['code_expiry']);
        unset($_SESSION['code_verified']);
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Your password has been reset successfully. Redirecting to login page...'
        ]);
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        throw $e;
    } finally {
        // Close connection
        $conn->close();
    }
    
} catch (Exception $e) {
    logError('Password Reset Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 