<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $conn = connectDB();
    $user_id = $_SESSION['user_id'];
    $semester_id = isset($_GET['semester_id']) ? intval($_GET['semester_id']) : 0;
    
    // Get teacher ID from user ID
    $stmt = $conn->prepare("SELECT id FROM teachers WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Teacher not found");
    }
    
    $teacherData = $result->fetch_assoc();
    $teacher_id = $teacherData['id'];
    
    // Build query based on whether a semester is selected
    $query = "
        SELECT 
            cs.id,
            cs.day_of_week,
            cs.hour,
            cs.is_theory,
            cs.is_enabled,
            s.name as subject_name,
            c.name as course_name,
            sem.name as semester_name,
            sem.id as semester_id
        FROM 
            class_schedules cs
            JOIN subjects s ON cs.subject_id = s.id
            JOIN courses c ON s.course_id = c.id
            JOIN semesters sem ON s.semester_id = sem.id
        WHERE 
            cs.teacher_id = ?
    ";
    
    // Add semester filter if provided
    if ($semester_id > 0) {
        $query .= " AND sem.id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $teacher_id, $semester_id);
    } else {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $teacher_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'schedules' => $schedules,
        'count' => count($schedules)
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_teacher_schedule.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?> 