<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

// Enable full error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log all inputs for debugging
error_log("save_schedule.php called with POST data: " . json_encode($_POST));

// Check if user is logged in and is an HOD
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if (!isset($_POST['teacher_id']) || !isset($_POST['subject_id']) || !isset($_POST['day_of_week']) || !isset($_POST['hour'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$teacher_id = intval($_POST['teacher_id']);
$subject_id = intval($_POST['subject_id']);
$day_of_week = $_POST['day_of_week'];
$hour = intval($_POST['hour']);
$isTheory = isset($_POST['isTheory']) ? intval($_POST['isTheory']) : 1;

// Validate inputs - CHANGED: Removed Saturday from allowed days
if (!$teacher_id || !$subject_id || !in_array($day_of_week, ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']) || $hour < 1 || $hour > 7) {
    echo json_encode(['success' => false, 'message' => 'Invalid input parameters']);
    exit;
}

try {
    $conn = connectDB();
    
    // Get the HOD's department_id
    $query = "SELECT department_id FROM hod WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'HOD department not found']);
        exit;
    }
    
    $hodData = $result->fetch_assoc();
    $department_id = $hodData['department_id'];
    
    // Check if the teacher exists in the preferences for this department
    $query = "SELECT t.id, t.name, t.designation 
              FROM teachers t 
              JOIN teacher_preferences tp ON t.id = tp.teacher_id 
              WHERE t.id = ? AND tp.department_id = ?
              LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $teacher_id, $department_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        error_log("Teacher not found: $teacher_id for department: $department_id");
        echo json_encode(['success' => false, 'message' => 'Teacher not found in this department']);
        exit;
    }
    
    $teacher = $result->fetch_assoc();
    $designation = $teacher['designation'];
    error_log("Teacher data: " . json_encode($teacher));
    
    // Get subject details including the course_id
    $query = "SELECT s.id, s.name, s.subject_type, s.course_id
              FROM subjects s
              JOIN teacher_preferences tp ON s.id = tp.subject_id
              WHERE s.id = ? AND tp.teacher_id = ? AND tp.is_disabled = 0";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $subject_id, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        error_log("Subject not found: $subject_id for teacher: $teacher_id");
        echo json_encode(['success' => false, 'message' => 'Subject not found in teacher preferences']);
        exit;
    }
    
    $subject = $result->fetch_assoc();
    $subject_type = $subject['subject_type'];
    $course_id = $subject['course_id'];
    error_log("Subject data: " . json_encode($subject));
    
    // Get lab and theory hours for this day
    $query = "SELECT cs.subject_id, s.subject_type, COUNT(*) as hours
              FROM class_schedules cs 
              JOIN subjects s ON cs.subject_id = s.id
              WHERE cs.teacher_id = ? AND cs.day_of_week = ?
              GROUP BY cs.subject_id, s.subject_type";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $teacher_id, $day_of_week);
    $stmt->execute();
    $hours_result = $stmt->get_result();
    
    $lab_hours_today = 0;
    $theory_hours_today = 0;
    $lab_subjects = []; // To track individual lab subjects and their hours
    $theory_subjects = []; // To track individual theory subjects and their hours
    
    while ($row = $hours_result->fetch_assoc()) {
        if ($row['subject_type'] === 'lab') {
            $lab_hours_today += $row['hours'];
            $lab_subjects[$row['subject_id']] = $row['hours'];
        } else {
            $theory_hours_today += $row['hours'];
            $theory_subjects[$row['subject_id']] = $row['hours'];
        }
    }
    
  
    // Check if this is a lab subject and if the teacher already has 5 lab subjects
    if ($subject_type === 'lab') {
        $unique_lab_subjects = count($lab_subjects);
        
        // If adding a new lab subject when teacher already has 5
        if ($unique_lab_subjects >= 5 && !isset($lab_subjects[$subject_id])) {
            echo json_encode(['success' => false, 'message' => 'Maximum 5 lab subjects can be scheduled per day']);
            exit;
        }
    }
    
    // Check if this is a theory subject and there's a lab with 5 hours already scheduled
    if ($subject_type === 'theory' && $lab_hours_today >= 5) {
        // Only one theory hour is allowed when a lab has 5 hours
        if ($theory_hours_today >= 1) {
            echo json_encode(['success' => false, 'message' => 'Only one theory hour is allowed when a lab has 5 hours on the same day']);
            exit;
        }
    }
    
    // Check if the subject is already assigned to another teacher
    $query = "SELECT cs.teacher_id 
              FROM class_schedules cs
              WHERE cs.subject_id = ? AND cs.teacher_id != ?
              LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $subject_id, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $assigned_teacher = $result->fetch_assoc();
        echo json_encode(['success' => false, 'message' => 'This subject is already assigned to another teacher']);
        exit;
    }
    
    // Count total weekly hours for this teacher
    $query = "SELECT COUNT(*) as total_count FROM class_schedules WHERE teacher_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_weekly_hours = $row['total_count'];
    
    // Check if adding this hour would exceed the weekly limit
    if (($total_weekly_hours + 1) > 20 && $designation === 'Junior Assistant Professor') {
        echo json_encode(['success' => false, 'message' => 'Junior Assistant Professor cannot exceed 20 teaching hours per week']);
        exit;
    }

    // Count total weekly hours for this specific subject
    $query = "SELECT COUNT(*) as subject_count FROM class_schedules WHERE subject_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $subject_weekly_hours = $row['subject_count'];
    
    // Check weekly subject hour limits - same 5-hour limit for all designations
    if (($subject_weekly_hours + 1) > 5) {
        echo json_encode(['success' => false, 'message' => 'A subject cannot exceed 5 hours per week']);
        exit;
    }
    
    // Count hours for this subject on this day
    $query = "SELECT COUNT(*) as subject_day_count FROM class_schedules WHERE subject_id = ? AND day_of_week = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $subject_id, $day_of_week);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $subject_hours_today = $row['subject_day_count'];
    
    // Implement specific constraints for each designation
    if ($designation === 'Junior Assistant Professor') {
        if ($total_weekly_hours >= 20) {
            echo json_encode(['success' => false, 'message' => 'Junior Assistant Professor cannot exceed 20 teaching hours per week']);
            exit;
        }
        
        // Check theory subject constraints
        if ($subject_type === 'theory' && $subject_hours_today >= 2) {
            echo json_encode(['success' => false, 'message' => 'A theory subject cannot exceed 2 hours per day']);
            exit;
        }
        
        // Lab hour constraint - can have up to 5 lab hours for a single lab subject
        if ($subject_type === 'lab' && $subject_hours_today >= 5) {
            echo json_encode(['success' => false, 'message' => 'A lab subject cannot exceed 5 hours per day']);
            exit;
        }
    }
    // Implement constraints for Senior Assistant Professor
    else if ($designation === 'Senior Assistant Professor') {
        if ($total_weekly_hours >= 18) {
            echo json_encode(['success' => false, 'message' => 'Senior Assistant Professor cannot exceed 18 teaching hours per week']);
            exit;
        }
        
        if (($theory_hours_today + $lab_hours_today) >= 5) {
            echo json_encode(['success' => false, 'message' => 'Senior Assistant Professor cannot exceed 5 teaching hours per day']);
            exit;
        }
        
        // Special constraint: If the same theory subject would exceed 2 hours per day
        if ($subject_type === 'theory' && $subject_hours_today >= 2) {
            echo json_encode(['success' => false, 'message' => 'A theory subject cannot exceed 2 hours per day']);
            exit;
        }
    }
    // Implement constraints for Associate Professor
    else if ($designation === 'Associate Professor') {
        if ($total_weekly_hours >= 16) {
            echo json_encode(['success' => false, 'message' => 'Associate Professor cannot exceed 16 teaching hours per week']);
            exit;
        }
        
        if (($theory_hours_today + $lab_hours_today) >= 4) {
            echo json_encode(['success' => false, 'message' => 'Associate Professor cannot exceed 4 teaching hours per day']);
            exit;
        }
        
        // Special constraint: If the same theory subject would exceed 2 hours per day
        if ($subject_type === 'theory' && $subject_hours_today >= 2) {
            echo json_encode(['success' => false, 'message' => 'A theory subject cannot exceed 2 hours per day']);
            exit;
        }
    }
    
    // Check if the time slot is already assigned to another subject from the SAME COURSE
    $query = "SELECT cs.*, s.name as subject_name, s.course_id 
              FROM class_schedules cs
              JOIN subjects s ON cs.subject_id = s.id
              WHERE cs.day_of_week = ? AND cs.hour = ? AND s.course_id = ? AND cs.subject_id != ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("siis", $day_of_week, $hour, $course_id, $subject_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $existing = $result->fetch_assoc();
        error_log("Time slot conflict within same course: " . json_encode($existing));
        echo json_encode(['success' => false, 'message' => 'This time slot is already scheduled for another subject in the same course']);
        exit;
    }
    
    // Check if a schedule already exists for this subject, day and hour
    $query = "SELECT * FROM class_schedules WHERE subject_id = ? AND day_of_week = ? AND hour = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isi", $subject_id, $day_of_week, $hour);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Same schedule already exists
        echo json_encode(['success' => false, 'message' => 'This schedule already exists']);
        exit;
    }
    
    // Insert new schedule
    $is_theory = ($subject_type === 'theory') ? 1 : 0;
    $query = "INSERT INTO class_schedules (teacher_id, subject_id, day_of_week, hour, is_theory, department_id) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iisiii", $teacher_id, $subject_id, $day_of_week, $hour, $is_theory, $department_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Database error: " . $stmt->error);
    }
    
    error_log("Schedule inserted successfully");
    echo json_encode(['success' => true, 'message' => 'Schedule saved successfully']);
    
} catch (Exception $e) {
    error_log("Error in save_schedule.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 