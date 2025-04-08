<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

// Check if user is logged in and is an HOD
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if (!isset($_POST['schedule_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing schedule ID']);
    exit;
}

$schedule_id = intval($_POST['schedule_id']);

try {
    $conn = connectDB();
    
    // Delete the schedule
    $query = "DELETE FROM class_schedules WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $schedule_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Schedule deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting schedule']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 