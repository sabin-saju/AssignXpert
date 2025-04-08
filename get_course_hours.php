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
    $excludeSubject = isset($_GET['exclude_subject']) ? (int)$_GET['exclude_subject'] : 0;
    
    if ($courseId <= 0) {
        throw new Exception('Invalid course ID');
    }
    
    $conn = connectDB();
    
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
    
    // First check if we're calculating for a subject already in the database
    $existingHoursForSubject = 0;
    if ($excludeSubject > 0) {
        $existingQuery = "SELECT weekly_hours FROM subject_hours 
                           WHERE subject_id = ? AND department_id = ?";
        $existingStmt = $conn->prepare($existingQuery);
        $existingStmt->bind_param("ii", $excludeSubject, $departmentId);
        $existingStmt->execute();
        $existingResult = $existingStmt->get_result();
        
        if ($existingResult->num_rows > 0) {
            $existingHoursForSubject = (int)$existingResult->fetch_assoc()['weekly_hours'];
        }
        
        // Log this for debugging
        error_log("Existing hours for subject $excludeSubject: $existingHoursForSubject");
    }
    
    // Get total hours for all subjects in this course
    $query = "SELECT SUM(sh.weekly_hours) as total_hours,
                    COUNT(sh.id) as subject_count
              FROM subject_hours sh
              JOIN subjects s ON sh.subject_id = s.id
              WHERE s.course_id = ? 
                AND sh.department_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $courseId, $departmentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    // Calculate total hours for course excluding the current subject
    $totalHours = (int)($data['total_hours'] ?? 0);
    $subjectCount = (int)($data['subject_count'] ?? 0);
    $adjustedHours = $totalHours - $existingHoursForSubject;
    
    // Get detailed list of subjects and their hours for debugging
    $detailQuery = "SELECT s.name, sh.weekly_hours
                   FROM subject_hours sh
                   JOIN subjects s ON sh.subject_id = s.id
                   WHERE s.course_id = ? 
                     AND sh.department_id = ?
                   ORDER BY s.name";
    
    $detailStmt = $conn->prepare($detailQuery);
    $detailStmt->bind_param("ii", $courseId, $departmentId);
    $detailStmt->execute();
    $detailResult = $detailStmt->get_result();
    
    $subjectList = [];
    while ($row = $detailResult->fetch_assoc()) {
        $subjectList[] = $row;
    }
    
    // Log detailed calculations for debugging
    error_log("Course $courseId hours calculation:");
    error_log("Total hours in database: $totalHours");
    error_log("Subject being excluded: $excludeSubject with $existingHoursForSubject hours");
    error_log("Adjusted hours: $adjustedHours");
    error_log("Subject details: " . json_encode($subjectList));
    
    echo json_encode([
        'success' => true,
        'total_hours' => $adjustedHours,
        'raw_total' => $totalHours,
        'existing_subject_hours' => $existingHoursForSubject,
        'subject_count' => $subjectCount,
        'course_id' => $courseId,
        'subject_details' => $subjectList
    ]);
    
} catch (Exception $e) {
    error_log('Error in get_course_hours.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) $conn->close();
}
?>
