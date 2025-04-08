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
    
    // Validate teacher's total weekly hours
    $existingHours = getTeacherCurrentHours($conn, $_POST['teacher_id']);
    $newHours = array_sum($_POST['hours']);
    
    if (($existingHours + $newHours) > 22) {
        throw new Exception('Teacher cannot exceed 22 hours per week');
    }
    
    // Validate daily hours
    foreach ($_POST['hours'] as $day => $hours) {
        $dailyHours = getTeacherDailyHours($conn, $_POST['teacher_id'], $day);
        if (($dailyHours + $hours) > 5) {
            throw new Exception('Teacher cannot exceed 5 hours per day');
        }
    }
    
    // Insert into database
    // ... (implementation details)
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
