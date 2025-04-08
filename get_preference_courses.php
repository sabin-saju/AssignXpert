<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}

try {
    $conn = connectDB();
    $departmentId = $_GET['department_id'] ?? null;
    
    if (!$departmentId) {
        throw new Exception('Department ID is required');
    }
    
    $stmt = $conn->prepare("
        SELECT id, name 
        FROM courses 
        WHERE department_id = ? 
        AND is_disabled = 0
    ");
    
    $stmt->bind_param("i", $departmentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $courses = $result->fetch_all(MYSQLI_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($courses);
    
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => $e->getMessage()]);
}
?>