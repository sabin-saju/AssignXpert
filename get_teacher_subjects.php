<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

// Add error logging to see what's happening
error_log("get_teacher_subjects.php called with: " . json_encode($_GET));

// Check if user is logged in and is an HOD
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
    error_log("Unauthorized access attempt in get_teacher_subjects.php");
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if (!isset($_GET['teacher_id']) || !is_numeric($_GET['teacher_id'])) {
    error_log("Missing or invalid teacher_id in get_teacher_subjects.php");
    echo json_encode(['success' => false, 'message' => 'Invalid teacher ID']);
    exit;
}

$teacher_id = intval($_GET['teacher_id']);
$subject_type = $_GET['subject_type'] ?? 'theory';

// Debug logs
error_log("Processing request for teacher_id: $teacher_id, subject_type: $subject_type");

try {
    $conn = connectDB();
    
    // Get the HOD's department_id
    $query = "SELECT department_id FROM hod WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        error_log("HOD department not found for user_id: " . $_SESSION['user_id']);
        echo json_encode(['success' => false, 'message' => 'HOD department not found']);
        exit;
    }
    
    $hodData = $result->fetch_assoc();
    $department_id = $hodData['department_id'];
    
    error_log("HOD department_id: $department_id");
    
    // Get the teacher's subject preferences that match the subject type
    $query = "SELECT 
                tp.id as preference_id, 
                tp.subject_id, 
                sub.name as subject_name, 
                sub.subject_type, 
                c.name as course_name, 
                s.name as semester_name
              FROM 
                teacher_preferences tp
                JOIN subjects sub ON tp.subject_id = sub.id
                JOIN courses c ON tp.course_id = c.id
                JOIN semesters s ON tp.semester_id = s.id
              WHERE 
                tp.teacher_id = ?
                AND tp.department_id = ? 
                AND tp.is_disabled = 0 
                AND sub.subject_type = ?
              ORDER BY 
                sub.name";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iis", $teacher_id, $department_id, $subject_type);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $subjects = [];
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
    
    error_log("Found " . count($subjects) . " subjects for teacher $teacher_id");
    
    // Important: Return plain array rather than wrapped object
    echo json_encode($subjects);
    
} catch (Exception $e) {
    error_log("Error in get_teacher_subjects.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 