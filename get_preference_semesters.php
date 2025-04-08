<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}

try {
    $conn = connectDB();
    $courseId = $_GET['course_id'] ?? null;
    
    if (!$courseId) {
        throw new Exception('Course ID is required');
    }
    
    $stmt = $conn->prepare("
        SELECT id, name 
        FROM semesters 
        WHERE course_id = ? 
        AND is_disabled = 0
    ");
    
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $semesters = $result->fetch_all(MYSQLI_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($semesters);
    
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => $e->getMessage()]);
}
?>