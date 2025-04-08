<?php
header('Content-Type: application/json');
require 'vendor/autoload.php';
require('config.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function generateRandomPassword($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $password;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if email and department are set and not empty
    if (!isset($_POST['email']) || empty($_POST['email']) || !isset($_POST['department']) || empty($_POST['department'])) {
        die(json_encode([
            'success' => false,
            'message' => 'Email and department are required'
        ]));
    }

    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        die(json_encode([
            'success' => false,
            'message' => 'Invalid email format'
        ]));
    }

    $department = $_POST['department'];

    try {
        $conn->begin_transaction();

        // Generate random password
        $randomPassword = bin2hex(random_bytes(5)); // 10 characters
        $hashedPassword = password_hash($randomPassword, PASSWORD_DEFAULT);

        // Insert into users table
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role_id) SELECT ?, ?, ?, role_id FROM roles WHERE role_name = 'hod'");
        $stmt->bind_param("sss", $email, $email, $hashedPassword);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create user account: " . $stmt->error);
        }

        $userId = $conn->insert_id;

        // Set default values for required fields
        $name = $email; // Use email as name initially
        $mobile = ''; // Empty placeholder
        $gender = 'other'; // Default gender
        $qualification = '';
        $research = '';
        $subject_expertise = '';

        // Insert into hod table with department and default values
        $stmt = $conn->prepare("INSERT INTO hod (user_id, name, mobile, gender, qualification, research, subject_expertise, department_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssssi", $userId, $name, $mobile, $gender, $qualification, $research, $subject_expertise, $department);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create HOD record: " . $stmt->error);
        }

        // Send email with credentials
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->SMTPDebug = 0; // Disable debug output
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'sabinsaju05@gmail.com';
            $mail->Password   = 'wnux urnp udwa opqs';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom('sabinsaju05@gmail.com', 'AssignXpert Admin');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Your AssignXpert Account Details';
            $mail->Body    = "
                <html>
                <body style='font-family: Arial, sans-serif; color: #333;'>
                    <h2 style='color: #2c3e50;'>Welcome to AssignXpert!</h2>
                    <p>Your account has been created successfully.</p>
                    <p><strong>Your login credentials are:</strong></p>
                    <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px;'>
                        <p><strong>Email:</strong> {$email}</p>
                        <p><strong>Password:</strong> {$randomPassword}</p>
                    </div>
                    <p style='color: #e74c3c;'><strong>Important:</strong> Please change your password after logging in.</p>
                </body>
                </html>
            ";
            $mail->AltBody = "Welcome to AssignXpert!\n\nLogin credentials:\nEmail: {$email}\nPassword: {$randomPassword}";

            $mail->send();
        } catch (Exception $e) {
            // Log email error but continue
            error_log("Email error: " . $e->getMessage());
        }

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'HOD added successfully',
            'password' => $randomPassword // Remove this in production
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
?>