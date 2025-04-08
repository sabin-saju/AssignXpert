<?php
session_start();
require_once 'config.php';

// Set headers
header('Content-Type: application/json');

try {
    // Check authentication
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
        throw new Exception('Unauthorized access');
    }
    
    // Get parameters
    $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
    
    if ($courseId <= 0) {
        throw new Exception('Invalid course ID');
    }
    
    // Get department ID from session
    $departmentId = isset($_SESSION['department_id']) ? (int)$_SESSION['department_id'] : 0;
    if ($departmentId <= 0) {
        $hodId = $_SESSION['user_id'];
        $deptQuery = "SELECT department_id FROM hod WHERE user_id = ?";
        $deptStmt = $conn->prepare($deptQuery);
        $deptStmt->bind_param("i", $hodId);
        $deptStmt->execute();
        $deptResult = $deptStmt->get_result();
        
        if ($deptResult->num_rows > 0) {
            $departmentId = $deptResult->fetch_assoc()['department_id'];
            $_SESSION['department_id'] = $departmentId;
        } else {
            throw new Exception('HOD department not found');
        }
    }
    
    $conn = connectDB();
    
    // Log query parameters for debugging
    error_log("Getting subject hours for course ID: $courseId, department ID: $departmentId");
    
    // Get subject hours with daily breakdown
    $query = "SELECT sh.id, sh.subject_id, sh.weekly_hours, s.name as subject_name,
              MAX(CASE WHEN dsh.day_of_week = 'Monday' THEN dsh.hours ELSE 0 END) as monday_hours,
              MAX(CASE WHEN dsh.day_of_week = 'Tuesday' THEN dsh.hours ELSE 0 END) as tuesday_hours,
              MAX(CASE WHEN dsh.day_of_week = 'Wednesday' THEN dsh.hours ELSE 0 END) as wednesday_hours,
              MAX(CASE WHEN dsh.day_of_week = 'Thursday' THEN dsh.hours ELSE 0 END) as thursday_hours,
              MAX(CASE WHEN dsh.day_of_week = 'Friday' THEN dsh.hours ELSE 0 END) as friday_hours
              FROM subject_hours sh
              JOIN subjects s ON sh.subject_id = s.id
              LEFT JOIN daily_subject_hours dsh ON sh.id = dsh.subject_hours_id
              WHERE s.course_id = ? AND sh.department_id = ?
              GROUP BY sh.id, sh.subject_id, sh.weekly_hours, s.name
              ORDER BY s.name";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Query preparation failed: ' . $conn->error);
    }
    
    $stmt->bind_param("ii", $courseId, $departmentId);
    if (!$stmt->execute()) {
        throw new Exception('Query execution failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $subjectHours = [];
    
    // Fetch results
    while ($row = $result->fetch_assoc()) {
        $subjectHours[] = $row;
    }
    
    // Get the course information
    $courseQuery = "SELECT name FROM courses WHERE id = ?";
    $courseStmt = $conn->prepare($courseQuery);
    $courseStmt->bind_param("i", $courseId);
    $courseStmt->execute();
    $courseResult = $courseStmt->get_result();
    $courseName = $courseResult->num_rows > 0 ? $courseResult->fetch_assoc()['name'] : 'Unknown Course';
    
    // Debug log the results
    error_log("Found " . count($subjectHours) . " subject hours records for course $courseName");
    if (count($subjectHours) > 0) {
        error_log("Sample record: " . json_encode($subjectHours[0]));
    }
    
    echo json_encode([
        'success' => true,
        'subjectHours' => $subjectHours,
        'courseName' => $courseName,
        'courseId' => $courseId
    ]);
    
} catch (Exception $e) {
    error_log('Error in get_subject_hours.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) $conn->close();
}
?>
