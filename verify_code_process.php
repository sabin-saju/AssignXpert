<?php
session_start();
header('Content-Type: application/json');

// Check if session variables are set
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['verification_code']) || !isset($_SESSION['code_expiry'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Session expired. Please restart the password reset process.'
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
    // Check if the verification code has expired
    if ($_SESSION['code_expiry'] < time()) {
        // Clear session variables
        unset($_SESSION['reset_email']);
        unset($_SESSION['verification_code']);
        unset($_SESSION['code_expiry']);
        
        throw new Exception('Verification code has expired. Please request a new one.');
    }
    
    // Get the submitted code
    $submitted_code = isset($_POST['code']) ? trim($_POST['code']) : '';
    
    if (empty($submitted_code)) {
        throw new Exception('Please enter the verification code.');
    }
    
    // Verify the code
    if ($submitted_code === $_SESSION['verification_code']) {
        // Code verified, set verified flag in session
        $_SESSION['code_verified'] = true;
        
        echo json_encode([
            'success' => true,
            'message' => 'Verification successful. Redirecting to password reset page.'
        ]);
    } else {
        throw new Exception('Invalid verification code. Please try again.');
    }
    
} catch (Exception $e) {
    logError('Verification Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 