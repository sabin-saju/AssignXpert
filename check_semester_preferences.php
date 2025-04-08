<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    echo json_encode(['error' => 'Access denied']);
    exit;
}

try {
    $conn = connectDB();
    $semesterId = $_GET['semester_id'] ?? null;
    
    if (!$semesterId) {
        throw new Exception('Semester ID is required');
    }
    
    // Get teacher ID
    $stmt = $conn->prepare("SELECT id FROM teachers WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $teacher = $stmt->get_result()->fetch_assoc();
    
    if (!$teacher) {
        throw new Exception('Teacher not found');
    }
    
    $teacherId = $teacher['id'];
    
    // Count active preferences for this semester
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM teacher_preferences 
        WHERE teacher_id = ? AND semester_id = ? AND is_disabled = 0
    ");
    $stmt->bind_param("ii", $teacherId, $semesterId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    echo json_encode([
        'count' => (int)$result['count'],
        'semester_id' => $semesterId
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
