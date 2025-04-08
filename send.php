<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // If using Composer
// OR include manually if you installed it manually

$mail = new PHPMailer(true);

try {
    // SMTP Configuration
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'sabinsaju05@gmail.com'; // Your Gmail
    $mail->Password   = 'wnux urnp udwa opqs'; // Your Gmail App Password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Email Details
    $mail->setFrom('sabinsaju05@gmail.com', 'Admin');
    $mail->addAddress('sabinsaju2027@gmail.com'); // Recipient Email
    $mail->Subject = 'Test Email';
    $mail->Body    = 'This is a test email sent using PHPMailer.';

    // Send Email
    $mail->send();
    echo 'Email sent successfully!';
} catch (Exception $e) {
    echo "Email could not be sent. Error: {$mail->ErrorInfo}";
}
?>
