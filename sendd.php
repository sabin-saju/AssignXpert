<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer manually (adjust path if needed)
// Correct file paths (adjust if necessary)
require __DIR__ . '/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/SMTP.php';
require __DIR__ . '/PHPMailer/Exception.php';

$mail = new PHPMailer(true);

try {
    // SMTP Configuration
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'sabinsaju05@gmail.com'; // Your email
    $mail->Password   = 'wnux urnp udwa opqs'; // Use App Password if using Gmail
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Email Details
    $mail->setFrom('sabinsaju05@gmail.com', 'Admin');
    $mail->addAddress('sabinsaju2027@gmail.com'); // Replace with the actual recipient's email
    $mail->Subject = 'Test Email';
    $mail->Body    = 'This is a test email sent using PHPMailer.';

    // Send Email
    $mail->send();
    echo '✅ Email sent successfully!';
} catch (Exception $e) {
    echo "❌ Email could not be sent. Error: {$mail->ErrorInfo}";
}
?>
