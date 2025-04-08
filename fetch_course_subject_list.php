<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
        throw new Exception('Unauthorized access');
    }

    $conn = connectDB();
    $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
    
    if ($courseId <= 0) {
        throw new Exception('Invalid course ID');
    }
    
    // The query needs to be updated to match your database schema
    // Let's check the subjects related to this course
    $query = "SELECT s.id, s.name, s.has_credits, s.credit_points 
              FROM subjects s
              JOIN semesters sem ON s.semester_id = sem.id
              WHERE sem.course_id = ? AND s.is_disabled = 0 
              ORDER BY s.name ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $subjects = [];
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'subjects' => $subjects,
        'course_id' => $courseId
    ]);

} catch (Exception $e) {
    error_log("Error in fetch_course_subject_list.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'course_id' => $courseId ?? null,
        'debug_info' => $e->getTraceAsString()
    ]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
?>
