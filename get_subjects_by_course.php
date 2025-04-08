<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
        throw new Exception('Unauthorized access');
    }

    $course_id = $_GET['course_id'] ?? 0;
    
    if (!$course_id) {
        throw new Exception('No course selected');
    }

    $conn = connectDB();

    // Get department ID for security check
    $dept_query = "SELECT department_id FROM hod WHERE user_id = ?";
    $stmt = $conn->prepare($dept_query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $department_id = $stmt->get_result()->fetch_assoc()['department_id'];

    // Get subjects for the course in this department
    $query = "SELECT id, name, subject_type, credit_points 
              FROM subjects 
              WHERE course_id = ? 
              AND department_id = ? 
              AND is_disabled = 0
              AND has_credits = 1
              ORDER BY name";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $course_id, $department_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $subjects = [];
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'subjects' => $subjects
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
