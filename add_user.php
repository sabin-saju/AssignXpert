<?php
require_once 'config.php';
require 'vendor/autoload.php'; // Make sure PHPMailer is installed via composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Set headers for JSON response and error reporting
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Comprehensive error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Logging function
function logError($message) {
    $logFile = 'user_add_error_log.txt';
    $timestamp = date('[Y-m-d H:i:s] ');
    file_put_contents($logFile, $timestamp . $message . PHP_EOL, FILE_APPEND);
}

// Handle OPTIONS preflight request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Establish database connection
    $conn = connectDB();
    
    // Check database connection
    if (!$conn) {
        throw new Exception('Database connection failed: ' . mysqli_connect_error());
    }

    // Only process POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Prefer JSON input, fallback to POST
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        // Log incoming data for debugging
        logError('Incoming Data: ' . print_r($input, true));

        // Validate required fields
        if (!isset($input['email']) || empty($input['email'])) {
            throw new Exception('Email is required');
        }

        // Sanitize and validate email
        $email = filter_var(trim($input['email']), FILTER_VALIDATE_EMAIL);
        if (!$email) {
            throw new Exception('Invalid email format');
        }

        // Set default role and validate department
        $role = isset($input['role']) ? trim($input['role']) : 'teacher';
        $department_id = isset($input['department_id']) ? intval($input['department_id']) : null;

        if (!$department_id) {
            throw new Exception('Valid Department ID is required');
        }

        // Start database transaction
        $conn->begin_transaction();

        try {
            // Check for existing email
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                throw new Exception('Email already exists');
            }
            $stmt->close();

            // Validate role
            $stmt = $conn->prepare("SELECT role_id FROM roles WHERE role_name = ?");
            $stmt->bind_param("s", $role);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception('Invalid role');
            }
            
            $role_row = $result->fetch_assoc();
            $role_id = $role_row['role_id'];
            $stmt->close();

            // Generate secure random password
            $random_password = bin2hex(random_bytes(4)); // 8 characters
            $hashed_password = password_hash($random_password, PASSWORD_DEFAULT);

            // Insert user
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $email, $email, $hashed_password, $role_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Error creating user: ' . $stmt->error);
            }

            $user_id = $conn->insert_id;

            // Insert role-specific details
            if ($role === 'teacher') {
                $designation = $input['designation'] ?? 'Junior Assistant Professor';
                $stmt = $conn->prepare("INSERT INTO teachers (user_id, name, department_id, designation) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isis", $user_id, $email, $department_id, $designation);
                
                if (!$stmt->execute()) {
                    throw new Exception('Error creating teacher record: ' . $stmt->error);
                }
            } elseif ($role === 'hod') {
                $stmt = $conn->prepare("INSERT INTO hod (user_id, name, department_id) VALUES (?, ?, ?)");
                $stmt->bind_param("isi", $user_id, $email, $department_id);
                
                if (!$stmt->execute()) {
                    throw new Exception('Error creating HOD record: ' . $stmt->error);
                }
            }
            
            // Send email with credentials
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'sabinsaju05@gmail.com'; 
                $mail->Password = 'wnux urnp udwa opqs';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                
                $mail->setFrom('sabinsaju05@gmail.com', 'AssignXpert Admin');
                $mail->addAddress($email);
                
                $mail->isHTML(true);
                $mail->Subject = 'Your AssignXpert Login Credentials';
                $mail->Body = "
                    <html>
                    <body style='font-family: Arial, sans-serif; color: #333;'>
                        <h2 style='color: #2c3e50;'>Welcome to AssignXpert!</h2>
                        <p>Your account has been created successfully.</p>
                        <p>You have been added as a " . ucfirst($role) . ".</p>
                        <p><strong>Your login credentials are:</strong></p>
                        <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px;'>
                            <p><strong>Email:</strong> {$email}</p>
                            <p><strong>Password:</strong> {$random_password}</p>
                        </div>
                        <p style='color: #e74c3c;'><strong>Important:</strong> Please change your password after logging in.</p>
                    </body>
                    </html>
                ";
                $mail->AltBody = "Welcome to AssignXpert!\n\nYour login credentials:\nEmail: {$email}\nPassword: {$random_password}";

                $mail->send();
                error_log("Email sent successfully to {$email}");
            } catch (Exception $e) {
                error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
                // Continue execution even if email fails
            }

            // Commit transaction
            $conn->commit();

            // Send success response
            echo json_encode([
                'success' => true,
                'message' => ucfirst($role) . ' added successfully',
                'password' => $random_password  // Remove this in production
            ]);

        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            throw $e;
        }
    } else {
        throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    // Log and return error
    logError('User Creation Error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    // Always close database connection
    if (isset($conn)) {
        $conn->close();
    }
}