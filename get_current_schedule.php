<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

// Check if user is logged in and is an HOD
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $conn = connectDB();
    $user_id = $_SESSION['user_id'];
    
    // Get HOD's department ID
    $stmt = $conn->prepare("SELECT department_id FROM hod WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("HOD department not found");
    }
    
    $hodData = $result->fetch_assoc();
    $department_id = $hodData['department_id'];
    
    // Get all schedules for the HOD's department
    $query = "
        SELECT 
            cs.id,
            cs.teacher_id,
            cs.subject_id,
            cs.day_of_week,
            cs.hour,
            cs.is_theory,
            cs.is_enabled,
            t.name as teacher_name,
            s.name as subject_name,
            c.name as course_name,
            sem.name as semester_name
        FROM 
            class_schedules cs
            JOIN teachers t ON cs.teacher_id = t.id
            JOIN subjects s ON cs.subject_id = s.id
            JOIN courses c ON s.course_id = c.id
            JOIN semesters sem ON s.semester_id = sem.id
        WHERE 
            cs.department_id = ?
        ORDER BY 
            CASE 
                WHEN cs.day_of_week = 'Monday' THEN 1
                WHEN cs.day_of_week = 'Tuesday' THEN 2
                WHEN cs.day_of_week = 'Wednesday' THEN 3
                WHEN cs.day_of_week = 'Thursday' THEN 4
                WHEN cs.day_of_week = 'Friday' THEN 5
                WHEN cs.day_of_week = 'Saturday' THEN 6
                ELSE 7
            END,
            cs.hour ASC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
    
    // Debug info - log what we're returning
    error_log("Returning " . count($schedules) . " schedules for department ID: " . $department_id);
    
    echo json_encode([
        'success' => true,
        'schedules' => $schedules,
        'debug' => [
            'department_id' => $department_id,
            'schedule_count' => count($schedules)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_current_schedule.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'error_details' => $e->getTraceAsString()
    ]);
}
?> 