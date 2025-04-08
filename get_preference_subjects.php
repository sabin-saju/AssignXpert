<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}

try {
    $conn = connectDB();
    
    $semesterId = $_GET['semester_id'] ?? null;
    $courseId = $_GET['course_id'] ?? null;
    $departmentId = $_GET['department_id'] ?? null;
    
    if (!$semesterId || !$courseId || !$departmentId) {
        throw new Exception('Missing required parameters');
    }
    
    $stmt = $conn->prepare("
        SELECT id, name 
        FROM subjects 
        WHERE semester_id = ? 
        AND course_id = ? 
        AND department_id = ?
        AND is_disabled = 0
    ");
    
    $stmt->bind_param("iii", $semesterId, $courseId, $departmentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $subjects = $result->fetch_all(MYSQLI_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($subjects);
    
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => $e->getMessage()]);
}
?>