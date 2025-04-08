<?php
require_once 'config.php';
require 'vendor/autoload.php'; // Make sure this path is correct

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Start session for storing verification details
session_start();

// Set headers for JSON response
header('Content-Type: application/json');

// Comprehensive error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Logging function - identical to add_user.php
function logError($message) {
    $logFile = 'password_reset_log.txt';
    $timestamp = date('[Y-m-d H:i:s] ');
    file_put_contents($logFile, $timestamp . $message . PHP_EOL, FILE_APPEND);
}

try {
    // Establish database connection
    $conn = connectDB();
    
    // Check database connection
    if (!$conn) {
        throw new Exception('Database connection failed: ' . mysqli_connect_error());
    }

    // Get email from POST
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    
    // Log the received email for debugging
    logError('Received reset request for email: ' . $email);
    
    // Validate email
    if (empty($email)) {
        throw new Exception('Email is required');
    }
    
    $email = filter_var($email, FILTER_VALIDATE_EMAIL);
    if (!$email) {
        throw new Exception('Invalid email format');
    }
    
    // Check if email exists in the database
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('No account found with this email address');
    }
    
    // Generate 6-digit verification code
    $verificationCode = '';
    for ($i = 0; $i < 6; $i++) {
        $verificationCode .= rand(0, 9);
    }
    
    logError('Generated verification code: ' . $verificationCode . ' for ' . $email);
    
    // Store verification data in session
    $_SESSION['reset_email'] = $email;
    $_SESSION['verification_code'] = $verificationCode;
    $_SESSION['code_expiry'] = time() + 600; // 10 minutes expiry
    
    logError('Session data set for email: ' . $email);
    
    // Send email - follow the exact pattern from add_user.php
    try {
        $mail = new PHPMailer(true);
        
        // Server settings - identical to add_user.php
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'sabinsaju05@gmail.com'; 
        $mail->Password = 'wnux urnp udwa opqs';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Debug SMTP connection
        $mail->SMTPDebug = 1; // Output debug info to log file
        $mail->Debugoutput = function($str, $level) {
            logError("SMTP Debug: $str");
        };
        
        // Sender and recipient
        $mail->setFrom('sabinsaju05@gmail.com', 'AssignXpert Admin');
        $mail->addAddress($email);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Verification Code';
        $mail->Body = "
            <html>
            <body style='font-family: Arial, sans-serif; color: #333;'>
                <h2 style='color: #2c3e50;'>AssignXpert Password Reset</h2>
                <p>You have requested to reset your password.</p>
                <p><strong>Your verification code is:</strong></p>
                <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; font-size: 24px; text-align: center;'>
                    {$verificationCode}
                </div>
                <p>This code will expire in 10 minutes.</p>
                <p>If you did not request this password reset, please ignore this email.</p>
            </body>
            </html>
        ";
        $mail->AltBody = "Your verification code for password reset: {$verificationCode}. This code will expire in 10 minutes.";

        // Send the email
        $mail->send();
        logError('Email sent successfully to ' . $email);
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Verification code sent successfully. Please check your email.'
        ]);
        
    } catch (Exception $e) {
        logError('Email sending failed. Error: ' . $mail->ErrorInfo);
        throw new Exception('Failed to send verification email. Technical details: ' . $mail->ErrorInfo);
    }
    
} catch (Exception $e) {
    // Log and return error
    logError('Password Reset Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    // Close database connection
    if (isset($conn)) {
        $conn->close();
    }
}
?> 