<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

try {
    $conn = connectDB();
    
    // Validate total hours
    $totalHours = array_sum($_POST['hours']);
    $subject = getSubjectDetails($conn, $_POST['subject_id']);
    
    if ($subject['subject_type'] === 'theory') {
        if ($totalHours > 5) {
            throw new Exception('Theory subjects cannot exceed 5 hours per week');
        }
    } else {
        // Lab subject validation
        $maxHours = ($subject['credit_points'] >= 9) ? 6 : 4;
        if ($totalHours > $maxHours) {
            throw new Exception("Lab subjects with these credits cannot exceed $maxHours hours per week");
        }
    }
    
    // Insert into database
    // ... (implementation details)
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
